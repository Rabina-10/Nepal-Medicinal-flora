
<?php
session_start();
require_once "config/db.php";

// Only administrators can access reports.
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: admin-login.php");
    exit();
}

// Summary statistics
$totalPlants = $conn->query(
    "SELECT COUNT(*) AS total FROM Plants"
)->fetch_assoc()["total"];

$totalObservations = $conn->query(
    "SELECT COUNT(*) AS total FROM Observations"
)->fetch_assoc()["total"];

$totalConservation = $conn->query(
    "SELECT COUNT(*) AS total FROM Conservation"
)->fetch_assoc()["total"];

$verifiedObservations = $conn->query(
    "SELECT COUNT(*) AS total FROM Observations WHERE status = 'verified'"
)->fetch_assoc()["total"];

$pendingObservations = $conn->query(
    "SELECT COUNT(*) AS total FROM Observations WHERE status = 'pending'"
)->fetch_assoc()["total"];

$rejectedObservations = $conn->query(
    "SELECT COUNT(*) AS total FROM Observations WHERE status = 'rejected'"
)->fetch_assoc()["total"];

// Plants by province
$provinceResult = $conn->query(
    "SELECT province, COUNT(DISTINCT plant_id) AS total
     FROM Plant_Locations
     WHERE province IS NOT NULL AND province <> ''
     GROUP BY province
     ORDER BY total DESC"
);

// Conservation records
$conservationResult = $conn->query(
    "SELECT p.common_name, p.scientific_name,
            c.conservation_status, c.threats
     FROM Conservation c
     INNER JOIN Plants p ON c.plant_id = p.plant_id
     ORDER BY p.common_name"
);

// Observation records
$observationResult = $conn->query(
    "SELECT o.observation_id, u.full_name,
            p.common_name, o.location,
            o.observation_date, o.status
     FROM Observations o
     LEFT JOIN Users u ON o.user_id = u.user_id
     LEFT JOIN Plants p ON o.plant_id = p.plant_id
     ORDER BY o.observation_date DESC, o.observation_id DESC"
);

$reportDate = date("d M Y, h:i A");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>System Reports | Nepal Medicinal Flora</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f8f3;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #2f6b45;
        }

        .report-title {
            color: #2f6b45;
            font-weight: bold;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            height: 100%;
            box-shadow: 0 3px 10px rgba(0,0,0,0.06);
        }

        .stat-card h2 {
            color: #2f6b45;
            font-weight: bold;
        }

        .report-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-top: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.06);
        }

        .section-heading {
            color: #2f6b45;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .status {
            font-weight: bold;
            text-transform: capitalize;
        }

        .print-button {
            background: #2f6b45;
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 6px;
        }

        .print-button:hover {
            background: #245536;
        }

        @media print {
            body {
                background: white;
            }

            .no-print, nav, footer {
                display: none !important;
            }

            .report-section, .stat-card {
                box-shadow: none;
                border: 1px solid #ddd;
                break-inside: avoid;
            }

            .container {
                max-width: 100%;
            }

            h1, h2, h3, h4, h5 {
                color: black !important;
            }

            table {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>

<?php $adminPage = 'reports'; include __DIR__ . '/includes/admin-navbar.php'; ?>


<div class="container py-5">

    <!-- Report Heading -->
    <div class="text-center mb-4">

        <h1 class="report-title">
            Nepal Medicinal Flora
        </h1>

        <h4>System Reports</h4>

        <p class="text-muted">
            Generated on: <?php echo htmlspecialchars($reportDate); ?>
        </p>

        <button onclick="window.print()" class="print-button no-print">
            Print / Save as PDF
        </button>

    </div>


    <!-- Summary Statistics -->
    <div class="row g-3">

        <div class="col-md-4">
            <div class="stat-card">
                <h2><?php echo number_format($totalPlants); ?></h2>
                <p>Total Medicinal Plants</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <h2><?php echo number_format($totalObservations); ?></h2>
                <p>Total Observations</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <h2><?php echo number_format($totalConservation); ?></h2>
                <p>Conservation Records</p>
            </div>
        </div>

    </div>


    <!-- Observation Summary -->
    <div class="report-section">

        <h4 class="section-heading">
            Observation Summary
        </h4>

        <div class="row text-center">

            <div class="col-md-4 mb-3">
                <h3 class="text-success">
                    <?php echo number_format($verifiedObservations); ?>
                </h3>
                <p>Verified</p>
            </div>

            <div class="col-md-4 mb-3">
                <h3 class="text-warning">
                    <?php echo number_format($pendingObservations); ?>
                </h3>
                <p>Pending</p>
            </div>

            <div class="col-md-4 mb-3">
                <h3 class="text-danger">
                    <?php echo number_format($rejectedObservations); ?>
                </h3>
                <p>Rejected</p>
            </div>

        </div>

    </div>


    <!-- Province Distribution -->
    <div class="report-section">

        <h4 class="section-heading">
            Medicinal Plant Distribution by Province
        </h4>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead class="table-success">
                <tr>
                    <th>Province</th>
                    <th>Number of Plants</th>
                </tr>
                </thead>

                <tbody>

                <?php if ($provinceResult && $provinceResult->num_rows > 0): ?>

                    <?php while ($row = $provinceResult->fetch_assoc()): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row["province"]); ?>
                            </td>

                            <td>
                                <?php echo number_format($row["total"]); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="2" class="text-center">
                            No province records available.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- Conservation Records -->
    <div class="report-section">

        <h4 class="section-heading">
            Conservation Records
        </h4>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead class="table-success">
                <tr>
                    <th>Plant Name</th>
                    <th>Scientific Name</th>
                    <th>Conservation Status</th>
                    <th>Threats</th>
                </tr>
                </thead>

                <tbody>

                <?php if ($conservationResult && $conservationResult->num_rows > 0): ?>

                    <?php while ($row = $conservationResult->fetch_assoc()): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row["common_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["scientific_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["conservation_status"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["threats"] ?? "Not specified"); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="text-center">
                            No conservation records available.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- Observation Records -->
    <div class="report-section">

        <h4 class="section-heading">
            Submitted Observation Records
        </h4>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead class="table-success">
                <tr>
                    <th>ID</th>
                    <th>Submitted By</th>
                    <th>Plant</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
                </thead>

                <tbody>

                <?php if ($observationResult && $observationResult->num_rows > 0): ?>

                    <?php while ($row = $observationResult->fetch_assoc()): ?>

                        <tr>
                            <td>
                                <?php echo (int)$row["observation_id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["full_name"] ?? "Unknown"); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["common_name"] ?? "Unknown"); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["location"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["observation_date"]); ?>
                            </td>

                            <td class="status">
                                <?php echo htmlspecialchars($row["status"]); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-center">
                            No observation records available.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <div class="text-center mt-4 no-print">

        <a href="admin.php" class="btn btn-secondary">
            Back to Admin Dashboard
        </a>

    </div>

</div>


<footer class="text-center p-3 mt-5 text-white"
        style="background:#2f6b45;">

    Nepal Medicinal Flora Conservation and Biodiversity Monitoring System

</footer>

</body>
</html>

