
<?php
require_once "config/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid plant ID.");
}

$plant_id = (int) $_GET['id'];

$sql = "SELECT
            p.plant_id,
            p.common_name,
            p.scientific_name,
            p.family,
            p.description,
            p.medicinal_uses,
            l.province,
            l.district,
            l.latitude,
            l.longitude,
            l.coordinate_confidence,
            l.coordinate_source
        FROM Plants p
        LEFT JOIN Plant_Locations l ON p.plant_id = l.plant_id
        WHERE p.plant_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database query error: " . $conn->error);
}

$stmt->bind_param("i", $plant_id);
$stmt->execute();

$result = $stmt->get_result();
$plant = $result->fetch_assoc();

$stmt->close();

if (!$plant) {
    die("Plant not found.");
}

function showValue($value) {
    if ($value === null || trim((string)$value) === "") {
        return "Not available";
    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo showValue($plant['common_name']); ?> - Nepal Medicinal Flora
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f8f3;
        }

        .navbar {
            background: #2f6b45;
        }

        .navbar-brand,
        .nav-link {
            color: white !important;
        }

        .navbar-brand {
            font-weight: bold;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.12);
        }

        .details-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h2, h4 {
            color: #2f6b45;
        }

        .detail-item {
            padding: 14px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .detail-label {
            font-weight: 600;
            color: #333;
        }

        .detail-value {
            color: #555;
            margin-top: 5px;
            white-space: pre-line;
        }

        .plant-name {
            color: #2f6b45;
        }

        @media (max-width: 991px) {
            .nav-link {
                padding: 9px 0 !important;
            }
        }
    </style>
</head>

<body>

<?php $activePage = 'plants'; include __DIR__ . '/includes/navbar.php'; ?>


<!-- Plant Details -->
<div class="container py-5">

    <a href="plants.php" class="btn btn-outline-success mb-4">
        ← Back to Plant Database
    </a>

    <div class="details-card">

        <h2 class="fw-bold plant-name">
            <?php echo showValue($plant['common_name']); ?>
        </h2>

        <h5 class="text-muted fst-italic mb-4">
            <?php echo showValue($plant['scientific_name']); ?>
        </h5>

        <hr>

        <h4>Plant Information</h4>

        <div class="detail-item">
            <div class="detail-label">Family</div>
            <div class="detail-value">
                <?php echo showValue($plant['family']); ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Description</div>
            <div class="detail-value">
                <?php echo showValue($plant['description']); ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Medicinal Uses</div>
            <div class="detail-value">
                <?php echo showValue($plant['medicinal_uses']); ?>
            </div>
        </div>


        <!-- Geographical Distribution -->
        <h4 class="mt-4">Geographical Distribution</h4>

        <?php
        $locationFields = [
                'province' => 'Province',
                'district' => 'District',
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
                'coordinate_confidence' => 'Coordinate Confidence',
                'coordinate_source' => 'Coordinate Source'
        ];
        ?>

        <?php foreach ($locationFields as $field => $label): ?>

            <?php if ($plant[$field] !== null && trim((string)$plant[$field]) !== ""): ?>

                <div class="detail-item">

                    <div class="detail-label">
                        <?php echo $label; ?>
                    </div>

                    <div class="detail-value">
                        <?php echo showValue($plant[$field]); ?>
                    </div>

                </div>

            <?php endif; ?>

        <?php endforeach; ?>

    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
