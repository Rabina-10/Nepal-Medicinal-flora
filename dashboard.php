<?php
require_once "config/db.php";

// Severity order used everywhere on this page
$statusOrder = [
        "Critically Endangered",
        "Endangered",
        "Vulnerable",
        "Near Threatened",
        "Least Concern",
        "Monitoring Required"
];
$threatenedStatuses = ["Critically Endangered", "Endangered", "Vulnerable"];

// Summary statistics
$totalPlants = $conn->query(
        "SELECT COUNT(*) AS total FROM Plants"
)->fetch_assoc()["total"];

$totalObservations = $conn->query(
        "SELECT COUNT(*) AS total FROM Observations"
)->fetch_assoc()["total"];

$pendingObservations = $conn->query(
        "SELECT COUNT(*) AS total FROM Observations WHERE status = 'pending'"
)->fetch_assoc()["total"];

$totalConservation = $conn->query(
        "SELECT COUNT(*) AS total FROM Conservation"
)->fetch_assoc()["total"];

$threatenedPlants = $conn->query(
        "SELECT COUNT(*) AS total FROM Conservation
         WHERE conservation_status IN ('Critically Endangered','Endangered','Vulnerable')"
)->fetch_assoc()["total"];


// Plants by province
$provinceLabels = [];
$provinceCounts = [];

$provinceResult = $conn->query(
        "SELECT pl.province, COUNT(DISTINCT p.plant_id) AS total
     FROM Plants p
     INNER JOIN Plant_Locations pl ON p.plant_id = pl.plant_id
     WHERE pl.province IS NOT NULL AND pl.province <> ''
     GROUP BY pl.province
     ORDER BY total DESC"
);

while ($row = $provinceResult->fetch_assoc()) {
    $provinceLabels[] = $row["province"];
    $provinceCounts[] = (int)$row["total"];
}


// Plants by family
$familyLabels = [];
$familyCounts = [];

$familyResult = $conn->query(
        "SELECT family, COUNT(*) AS total
     FROM Plants
     WHERE family IS NOT NULL AND family <> ''
     GROUP BY family
     ORDER BY total DESC
     LIMIT 10"
);

while ($row = $familyResult->fetch_assoc()) {
    $familyLabels[] = $row["family"];
    $familyCounts[] = (int)$row["total"];
}


// Conservation status (ordered from most to least threatened)
$statusLabels = [];
$statusCounts = [];

$statusResult = $conn->query(
        "SELECT conservation_status, COUNT(*) AS total
     FROM Conservation
     WHERE conservation_status IS NOT NULL
       AND conservation_status <> ''
     GROUP BY conservation_status
     ORDER BY FIELD(conservation_status,
         'Critically Endangered','Endangered','Vulnerable',
         'Near Threatened','Least Concern','Monitoring Required'),
         conservation_status"
);

while ($row = $statusResult->fetch_assoc()) {
    $statusLabels[] = $row["conservation_status"];
    $statusCounts[] = (int)$row["total"];
}


// Observation status
$observationLabels = [];
$observationCounts = [];

$observationResult = $conn->query(
        "SELECT status, COUNT(*) AS total
     FROM Observations
     GROUP BY status
     ORDER BY total DESC"
);

while ($row = $observationResult->fetch_assoc()) {
    $observationLabels[] = ucfirst($row["status"]);
    $observationCounts[] = (int)$row["total"];
}


// Most threatened plants (top 10, no duplicate species)
$threatenedList = [];
$seenSpecies = [];

$threatenedResult = $conn->query(
        "SELECT p.plant_id, p.common_name, p.scientific_name,
                c.conservation_status, c.threats
     FROM Conservation c
     INNER JOIN Plants p ON p.plant_id = c.plant_id
     WHERE c.conservation_status IN ('Critically Endangered','Endangered','Vulnerable')
     ORDER BY FIELD(c.conservation_status,
         'Critically Endangered','Endangered','Vulnerable'),
         p.common_name"
);

while ($row = $threatenedResult->fetch_assoc()) {
    $key = strtolower(trim($row["scientific_name"] ?? ""));
    if ($key !== "" && isset($seenSpecies[$key])) {
        continue;
    }
    $seenSpecies[$key] = true;
    $threatenedList[] = $row;
    if (count($threatenedList) >= 10) {
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Biodiversity Dashboard | Nepal Medicinal Flora</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        body {
            background: #f5f8f3;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #2f6b45;
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }

        .nav-link {
            color: white !important;
            padding: 12px !important;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: rgba(255,255,255,0.12);
        }

        .dashboard-title {
            color: #2f6b45;
            font-weight: bold;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            height: 100%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .stat-card h2 {
            color: #2f6b45;
            font-weight: bold;
            font-size: 32px;
        }

        .stat-card.alert-stat h2 {
            color: #c95c5c;
        }

        .stat-card p {
            color: #666;
            margin-bottom: 0;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            height: 100%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .chart-card h5 {
            color: #2f6b45;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 320px;
        }

        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            white-space: nowrap;
        }

        .badge-critically-endangered { background: #8e2a2a; }
        .badge-endangered            { background: #c95c5c; }
        .badge-vulnerable            { background: #e69b54; }

        .threat-table th {
            color: #2f6b45;
        }

        footer {
            background: #2f6b45;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 50px;
        }

        @media (max-width: 991px) {
            .navbar-nav {
                padding-top: 10px;
            }

            .nav-link {
                padding: 8px !important;
            }
        }

    </style>

</head>

<body>

<?php $activePage = 'dashboard'; include __DIR__ . '/includes/navbar.php'; ?>


<!-- Dashboard Heading -->
<div class="container py-5">

    <div class="text-center mb-5">

        <h1 class="dashboard-title">
            Biodiversity Dashboard
        </h1>

        <p class="text-muted">
            Overview of Nepal's medicinal plants, observations,
            geographical distribution, and conservation records.
        </p>

    </div>


    <!-- Summary Cards -->
    <div class="row g-4 mb-5">

        <div class="col-md-6 col-lg">
            <div class="stat-card">
                <h2><?php echo number_format($totalPlants); ?></h2>
                <p>Medicinal Plants</p>
            </div>
        </div>

        <div class="col-md-6 col-lg">
            <div class="stat-card alert-stat">
                <h2><?php echo number_format($threatenedPlants); ?></h2>
                <p>Threatened Plants</p>
            </div>
        </div>

        <div class="col-md-6 col-lg">
            <div class="stat-card">
                <h2><?php echo number_format($totalObservations); ?></h2>
                <p>Total Observations</p>
            </div>
        </div>

        <div class="col-md-6 col-lg">
            <div class="stat-card">
                <h2><?php echo number_format($pendingObservations); ?></h2>
                <p>Pending Observations</p>
            </div>
        </div>

        <div class="col-md-6 col-lg">
            <div class="stat-card">
                <h2><?php echo number_format($totalConservation); ?></h2>
                <p>Conservation Records</p>
            </div>
        </div>

    </div>


    <!-- Charts -->
    <div class="row g-4">

        <div class="col-lg-6">
            <div class="chart-card">

                <h5>Medicinal Plants by Province</h5>

                <div class="chart-container">
                    <canvas id="provinceChart"></canvas>
                </div>

            </div>
        </div>


        <div class="col-lg-6">
            <div class="chart-card">

                <h5>Medicinal Plants by Family (Top 10)</h5>

                <div class="chart-container">
                    <canvas id="familyChart"></canvas>
                </div>

            </div>
        </div>


        <div class="col-lg-6">
            <div class="chart-card">

                <h5>Conservation Status Distribution</h5>

                <div class="chart-container">
                    <canvas id="conservationChart"></canvas>
                </div>

            </div>
        </div>


        <div class="col-lg-6">
            <div class="chart-card">

                <h5>Observation Status</h5>

                <div class="chart-container">
                    <canvas id="observationChart"></canvas>
                </div>

            </div>
        </div>

    </div>


    <!-- Most threatened plants -->
    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="chart-card">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Most Threatened Plants</h5>
                    <a href="conservation.php" class="btn btn-sm btn-outline-success">
                        View all conservation records
                    </a>
                </div>

                <?php if (count($threatenedList) > 0): ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle threat-table mb-0">
                            <thead>
                            <tr>
                                <th>Plant</th>
                                <th>Scientific Name</th>
                                <th>Status</th>
                                <th>Main Threats</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($threatenedList as $item): ?>
                                <?php
                                $badgeClass = "badge-" . strtolower(str_replace(" ", "-", $item["conservation_status"]));
                                $threatRaw  = (string)($item["threats"] ?? "");
                                $threatText = strlen($threatRaw) > 110
                                        ? rtrim(substr($threatRaw, 0, 110)) . "..."
                                        : $threatRaw;
                                ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($item["common_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                        </strong>
                                    </td>
                                    <td class="fst-italic text-muted">
                                        <?php echo htmlspecialchars($item["scientific_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                    </td>
                                    <td>
                                        <span class="badge-status <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, "UTF-8"); ?>">
                                            <?php echo htmlspecialchars($item["conservation_status"], ENT_QUOTES, "UTF-8"); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($threatText, ENT_QUOTES, "UTF-8"); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>

                    <p class="text-muted mb-0">No threatened plants recorded yet.</p>

                <?php endif; ?>

            </div>
        </div>
    </div>

</div>


<!-- Footer -->
<footer>

    <h5>🌿 Nepal Medicinal Flora Conservation System</h5>

    <p class="mb-0">
        Supporting Medicinal Plant Conservation and Biodiversity Monitoring
    </p>

</footer>


<!-- Chart Configuration -->
<script>

    const provinceLabels = <?php echo json_encode($provinceLabels); ?>;
    const provinceCounts = <?php echo json_encode($provinceCounts); ?>;

    const familyLabels = <?php echo json_encode($familyLabels); ?>;
    const familyCounts = <?php echo json_encode($familyCounts); ?>;

    const statusLabels = <?php echo json_encode($statusLabels); ?>;
    const statusCounts = <?php echo json_encode($statusCounts); ?>;

    const observationLabels = <?php echo json_encode($observationLabels); ?>;
    const observationCounts = <?php echo json_encode($observationCounts); ?>;

    // One fixed colour per conservation status
    const statusColors = {
        "Critically Endangered": "#8e2a2a",
        "Endangered": "#c95c5c",
        "Vulnerable": "#e69b54",
        "Near Threatened": "#e4c76b",
        "Least Concern": "#2f6b45",
        "Monitoring Required": "#7b83a6"
    };


    // Province Chart
    new Chart(document.getElementById("provinceChart"), {

        type: "bar",

        data: {
            labels: provinceLabels,

            datasets: [{
                label: "Number of Plants",
                data: provinceCounts,
                backgroundColor: "#2f6b45"
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }

    });


    // Family Chart
    new Chart(document.getElementById("familyChart"), {

        type: "bar",

        data: {
            labels: familyLabels,

            datasets: [{
                label: "Number of Plants",
                data: familyCounts,
                backgroundColor: "#76a77e"
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: "y",

            plugins: {
                legend: {
                    display: false
                }
            },

            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }

    });


    // Conservation Chart
    new Chart(document.getElementById("conservationChart"), {

        type: "doughnut",

        data: {
            labels: statusLabels,

            datasets: [{
                data: statusCounts,

                backgroundColor: statusLabels.map(
                    label => statusColors[label] || "#a8a8a8"
                )
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    position: "right"
                }
            }
        }

    });


    // Observation Chart
    new Chart(document.getElementById("observationChart"), {

        type: "pie",

        data: {
            labels: observationLabels,

            datasets: [{
                data: observationCounts,

                backgroundColor: [
                    "#e4c76b",
                    "#2f8f55",
                    "#c95c5c"
                ]
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false
        }

    });

</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
