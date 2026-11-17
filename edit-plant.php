<?php
session_start();

require_once "config/db.php";

// Only admin can access this page
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: admin-login.php");
    exit();
}

$message = "";
$messageType = "";

$plant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Update plant information
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $plant_id = (int)($_POST['plant_id'] ?? 0);

    $common_name = trim($_POST['common_name'] ?? '');
    $scientific_name = trim($_POST['scientific_name'] ?? '');
    $family = trim($_POST['family'] ?? '');
    $medicinal_uses = trim($_POST['medicinal_uses'] ?? '');

    $province = trim($_POST['province'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    $coordinate_source = trim($_POST['coordinate_source'] ?? '');
    $coordinate_confidence = trim($_POST['coordinate_confidence'] ?? '');

    if (
        $plant_id <= 0 ||
        $common_name === '' ||
        $scientific_name === '' ||
        $province === '' ||
        $district === '' ||
        $latitude === '' ||
        $longitude === ''
    ) {
        $message = "Please fill in all required fields.";
        $messageType = "danger";

    } elseif (
        !is_numeric($latitude) ||
        !is_numeric($longitude) ||
        $latitude < -90 || $latitude > 90 ||
        $longitude < -180 || $longitude > 180
    ) {
        $message = "Please enter valid latitude and longitude.";
        $messageType = "danger";

    } else {

        try {
            $conn->begin_transaction();

            // Update plant information
            $sql = "UPDATE Plants
                    SET common_name = ?,
                        scientific_name = ?,
                        family = ?,
                        medicinal_uses = ?
                    WHERE plant_id = ?";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception("Could not prepare plant update.");
            }

            $stmt->bind_param(
                "ssssi",
                $common_name,
                $scientific_name,
                $family,
                $medicinal_uses,
                $plant_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Could not update plant information.");
            }

            $stmt->close();

            // Check whether a location already exists
            $checkSql = "SELECT location_id
                         FROM Plant_Locations
                         WHERE plant_id = ?
                         LIMIT 1";

            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $plant_id);
            $checkStmt->execute();

            $locationResult = $checkStmt->get_result();
            $existingLocation = $locationResult->fetch_assoc();

            $checkStmt->close();

            $lat = (float)$latitude;
            $lng = (float)$longitude;

            if ($existingLocation) {

                // Update existing location
                $locationSql = "UPDATE Plant_Locations
                                SET province = ?,
                                    district = ?,
                                    latitude = ?,
                                    longitude = ?,
                                    coordinate_source = ?,
                                    coordinate_confidence = ?
                                WHERE location_id = ?";

                $locationStmt = $conn->prepare($locationSql);

                $location_id = (int)$existingLocation['location_id'];

                $locationStmt->bind_param(
                    "ssddssi",
                    $province,
                    $district,
                    $lat,
                    $lng,
                    $coordinate_source,
                    $coordinate_confidence,
                    $location_id
                );

            } else {

                // Create location if one does not exist
                $locationSql = "INSERT INTO Plant_Locations
                                (
                                    plant_id,
                                    province,
                                    district,
                                    latitude,
                                    longitude,
                                    coordinate_source,
                                    coordinate_confidence
                                )
                                VALUES (?, ?, ?, ?, ?, ?, ?)";

                $locationStmt = $conn->prepare($locationSql);

                $locationStmt->bind_param(
                    "issddss",
                    $plant_id,
                    $province,
                    $district,
                    $lat,
                    $lng,
                    $coordinate_source,
                    $coordinate_confidence
                );
            }

            if (!$locationStmt->execute()) {
                throw new Exception("Could not update plant location.");
            }

            $locationStmt->close();

            $conn->commit();

            $message = "Plant updated successfully!";
            $messageType = "success";

        } catch (Exception $e) {

            $conn->rollback();

            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Load selected plant and its location
$plant = null;

if ($plant_id > 0) {

    $sql = "SELECT
                p.plant_id,
                p.common_name,
                p.scientific_name,
                p.family,
                p.medicinal_uses,
                l.province,
                l.district,
                l.latitude,
                l.longitude,
                l.coordinate_source,
                l.coordinate_confidence
            FROM Plants p
            LEFT JOIN Plant_Locations l
                ON p.plant_id = l.plant_id
            WHERE p.plant_id = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $plant_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $plant = $result->fetch_assoc();

    $stmt->close();
}

// Load all plants for selection
$plantsResult = $conn->query(
    "SELECT plant_id, common_name, scientific_name
     FROM Plants
     ORDER BY common_name ASC"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Plant | Nepal Medicinal Flora</title>

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

        .navbar-brand {
            color: white;
            font-weight: bold;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-top: 30px;
            margin-bottom: 40px;
        }

        h2 {
            color: #2f6b45;
            font-weight: bold;
        }

        .btn-success {
            background: #2f6b45;
            border-color: #2f6b45;
        }

        .btn-success:hover {
            background: #245437;
        }

        .required {
            color: red;
        }
    </style>
</head>

<body>

<?php $adminPage = 'edit-plant'; include __DIR__ . '/includes/admin-navbar.php'; ?>

<div class="container">

    <div class="form-card">

        <h2>Edit Medicinal Plant</h2>

        <p class="text-muted">
            Select a plant to update its information and location.
        </p>

        <?php if ($message !== ""): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Plant selection -->
        <form method="GET" action="edit-plant.php" class="mb-4">

            <label class="form-label fw-bold">Select Plant</label>

            <select name="id" class="form-select" required>
                <option value="">Choose a plant</option>

                <?php while ($row = $plantsResult->fetch_assoc()): ?>

                    <option value="<?php echo (int)$row['plant_id']; ?>"
                        <?php echo ($plant_id == $row['plant_id']) ? 'selected' : ''; ?>>

                        <?php
                        echo htmlspecialchars(
                            $row['common_name'] . " (" .
                            $row['scientific_name'] . ")"
                        );
                        ?>

                    </option>

                <?php endwhile; ?>

            </select>

            <button type="submit" class="btn btn-outline-success mt-2">
                Load Plant
            </button>

        </form>

        <hr>

        <?php if ($plant): ?>

            <form method="POST" action="edit-plant.php?id=<?php echo (int)$plant_id; ?>">

                <input type="hidden" name="plant_id"
                       value="<?php echo (int)$plant['plant_id']; ?>">

                <h5 class="mb-3">Plant Information</h5>

                <div class="mb-3">
                    <label class="form-label">
                        Common Name <span class="required">*</span>
                    </label>

                    <input type="text" name="common_name"
                           class="form-control" required
                           value="<?php echo htmlspecialchars($plant['common_name'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Scientific Name <span class="required">*</span>
                    </label>

                    <input type="text" name="scientific_name"
                           class="form-control" required
                           value="<?php echo htmlspecialchars($plant['scientific_name'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Family</label>

                    <input type="text" name="family"
                           class="form-control"
                           value="<?php echo htmlspecialchars($plant['family'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Medicinal Uses</label>

                    <textarea name="medicinal_uses"
                              class="form-control"
                              rows="4"><?php echo htmlspecialchars($plant['medicinal_uses'] ?? ''); ?></textarea>
                </div>

                <hr>

                <h5 class="mb-3">Plant Location</h5>

                <div class="mb-3">
                    <label class="form-label">
                        Province <span class="required">*</span>
                    </label>

                    <input type="text" name="province"
                           class="form-control" required
                           value="<?php echo htmlspecialchars($plant['province'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        District <span class="required">*</span>
                    </label>

                    <input type="text" name="district"
                           class="form-control" required
                           value="<?php echo htmlspecialchars($plant['district'] ?? ''); ?>">
                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Latitude <span class="required">*</span>
                        </label>

                        <input type="number" step="any" name="latitude"
                               class="form-control" required
                               value="<?php echo htmlspecialchars($plant['latitude'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Longitude <span class="required">*</span>
                        </label>

                        <input type="number" step="any" name="longitude"
                               class="form-control" required
                               value="<?php echo htmlspecialchars($plant['longitude'] ?? ''); ?>">
                    </div>

                </div>

                <div class="mb-3">
                    <label class="form-label">Coordinate Source</label>

                    <input type="text" name="coordinate_source"
                           class="form-control"
                           value="<?php echo htmlspecialchars($plant['coordinate_source'] ?? ''); ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label">Coordinate Confidence</label>

                    <select name="coordinate_confidence" class="form-select">

                        <option value="">Select confidence</option>

                        <?php
                        $confidenceOptions = [
                            "High",
                            "Medium",
                            "Low",
                            "Approximate"
                        ];

                        foreach ($confidenceOptions as $option):
                            ?>

                            <option value="<?php echo $option; ?>"
                                <?php echo (($plant['coordinate_confidence'] ?? '') === $option) ? 'selected' : ''; ?>>

                                <?php echo $option; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <button type="submit" class="btn btn-success px-4">
                    Update Plant
                </button>

                <a href="admin.php" class="btn btn-secondary px-4">
                    Back to Dashboard
                </a>

            </form>

        <?php elseif ($plant_id > 0): ?>

            <div class="alert alert-warning">
                Plant not found. Please select another plant.
            </div>

        <?php else: ?>

            <p class="text-muted">
                Select a plant above to display its information.
            </p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
