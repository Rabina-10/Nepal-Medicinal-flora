<?php
session_start();

require_once "config/db.php";

// Only logged-in admins can add plants
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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

    // Check required fields
    if (
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
        $message = "Please enter valid latitude and longitude values.";
        $messageType = "danger";

    } else {

        try {
            // Start database transaction
            $conn->begin_transaction();

            // Insert plant information
            $sql = "INSERT INTO Plants
                    (common_name, scientific_name, family, medicinal_uses)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception("Plant query preparation failed.");
            }

            $stmt->bind_param(
                "ssss",
                $common_name,
                $scientific_name,
                $family,
                $medicinal_uses
            );

            if (!$stmt->execute()) {
                throw new Exception("Could not add plant information.");
            }

            // Get the newly created plant ID
            $plant_id = $conn->insert_id;

            $stmt->close();

            // Insert plant location using the new plant ID
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

            if (!$locationStmt) {
                throw new Exception("Location query preparation failed.");
            }

            $lat = (float)$latitude;
            $lng = (float)$longitude;

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

            if (!$locationStmt->execute()) {
                throw new Exception("Could not save plant location.");
            }

            $locationStmt->close();

            // Save both records together
            $conn->commit();

            $message = "Plant added successfully!";
            $messageType = "success";

        } catch (Exception $e) {

            // Undo both inserts if something goes wrong
            $conn->rollback();

            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add New Plant | Nepal Medicinal Flora</title>

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

        .container {
            max-width: 850px;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-top: 35px;
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

<?php $adminPage = 'add-plant'; include __DIR__ . '/includes/admin-navbar.php'; ?>

<div class="container">

    <div class="form-card">

        <h2 class="mb-2">Add New Medicinal Plant</h2>

        <p class="text-muted">
            Enter the plant information and its geographical location.
        </p>

        <?php if ($message !== ""): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <h5 class="mt-3 mb-3">Plant Information</h5>

            <div class="mb-3">
                <label class="form-label">
                    Common Name <span class="required">*</span>
                </label>
                <input type="text" name="common_name"
                       class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['common_name'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Scientific Name <span class="required">*</span>
                </label>
                <input type="text" name="scientific_name"
                       class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['scientific_name'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Family</label>
                <input type="text" name="family"
                       class="form-control"
                       value="<?php echo htmlspecialchars($_POST['family'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Medicinal Uses</label>
                <textarea name="medicinal_uses" class="form-control"
                          rows="4"><?php echo htmlspecialchars($_POST['medicinal_uses'] ?? ''); ?></textarea>
            </div>

            <hr>

            <h5 class="mb-3">Plant Location</h5>

            <div class="mb-3">
                <label class="form-label">
                    Province <span class="required">*</span>
                </label>
                <input type="text" name="province"
                       class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">
                    District <span class="required">*</span>
                </label>
                <input type="text" name="district"
                       class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['district'] ?? ''); ?>">
            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Latitude <span class="required">*</span>
                    </label>
                    <input type="number" step="any" name="latitude"
                           class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Longitude <span class="required">*</span>
                    </label>
                    <input type="number" step="any" name="longitude"
                           class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                </div>

            </div>

            <div class="mb-3">
                <label class="form-label">Coordinate Source</label>
                <input type="text" name="coordinate_source"
                       class="form-control"
                       placeholder="Example: GPS, research source, district reference"
                       value="<?php echo htmlspecialchars($_POST['coordinate_source'] ?? ''); ?>">
            </div>

            <div class="mb-4">
                <label class="form-label">Coordinate Confidence</label>
                <select name="coordinate_confidence" class="form-select">
                    <option value="">Select confidence</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                    <option value="Approximate">Approximate</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success px-4">
                Add Plant
            </button>

            <a href="admin.php" class="btn btn-secondary px-4">
                Back to Dashboard
            </a>

        </form>

    </div>
</div>

</body>
</html>
