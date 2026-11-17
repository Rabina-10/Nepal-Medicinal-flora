<?php
session_start();

require_once "config/db.php";

// Only admin can access dashboard
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: admin-login.php");
    exit();
}

// Count total plants
$result = $conn->query("SELECT COUNT(*) AS total FROM Plants");
$row = $result->fetch_assoc();
$totalPlants = $row['total'];

// Count total observations
$observationResult = $conn->query(
    "SELECT COUNT(*) AS total FROM Observations"
);
$observationRow = $observationResult->fetch_assoc();
$totalObservations = $observationRow['total'];

// Count pending observations
$pendingResult = $conn->query(
    "SELECT COUNT(*) AS total FROM Observations WHERE status = 'pending'"
);
$pendingRow = $pendingResult->fetch_assoc();
$pendingObservations = $pendingRow['total'];

// Count conservation records
$conservationResult = $conn->query(
    "SELECT COUNT(*) AS total FROM Conservation"
);
$conservationRow = $conservationResult->fetch_assoc();
$totalConservation = $conservationRow['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Nepal Medicinal Flora</title>

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

        .dashboard-container {
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            height: 100%;
        }

        h2, h4 {
            color: #2f6b45;
        }

        .stat-number {
            font-size: 35px;
            font-weight: bold;
            color: #2f6b45;
        }

        .btn-success {
            background: #2f6b45;
            border-color: #2f6b45;
        }

        .btn-success:hover {
            background: #245437;
        }
    </style>
</head>

<body>

<?php $adminPage = 'dashboard'; include __DIR__ . '/includes/admin-navbar.php'; ?>

<div class="container dashboard-container">

    <h2>
        Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
    </h2>

    <p class="text-muted">
        Manage medicinal plants and monitor biodiversity information.
    </p>

    <!-- Dashboard Statistics -->
    <div class="row mt-4">

        <div class="col-md-3 mb-3">
            <div class="dashboard-card">
                <h5>Total Medicinal Plants</h5>
                <div class="stat-number">
                    <?php echo $totalPlants; ?>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="dashboard-card">
                <h5>Total Observations</h5>
                <div class="stat-number">
                    <?php echo $totalObservations; ?>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="dashboard-card">
                <h5>Pending Observations</h5>
                <div class="stat-number">
                    <?php echo $pendingObservations; ?>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="dashboard-card">
                <h5>Conservation Records</h5>
                <div class="stat-number">
                    <?php echo $totalConservation; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Plant Management -->
    <div class="dashboard-card mt-4">

        <h4>Plant Management</h4>

        <p class="text-muted">
            Add new plants, update existing information, or remove plants.
        </p>

        <div class="d-flex flex-wrap gap-2">

            <a href="add-plant.php" class="btn btn-success">
                + Add New Plant
            </a>

            <a href="edit-plant.php" class="btn btn-primary">
                Edit Plant
            </a>

            <a href="delete-plant.php" class="btn btn-danger">
                Delete Plant
            </a>

        </div>

    </div>

    <!-- Observation Management -->
    <div class="dashboard-card mt-4">

        <h4>Observation Management</h4>

        <p class="text-muted">
            Review submitted plant observations and update their status.
        </p>

        <a href="manage-observations.php" class="btn btn-outline-success">
            Manage Observations
        </a>

    </div>

    <!-- Conservation Management -->
    <div class="dashboard-card mt-4">

        <h4>Conservation Management</h4>

        <p class="text-muted">
            Manage conservation status, threats, and protection information.
        </p>

        <a href="manage-conservation.php" class="btn btn-outline-success">
            Manage Conservation
        </a>

    </div>

    <!-- Website Management -->
    </a>
    <div class="dashboard-card mt-4">

        <h4>Website Management</h4>

        <p class="text-muted">
            View plant information and geographical distribution.
        </p>

        <div class="d-flex flex-wrap gap-2">

            <a href="plants.php" class="btn btn-outline-success">
                View Plant Database
            </a>

            <a href="map.php" class="btn btn-outline-success">
                View Distribution Map
            </a>
            <a href="reports.php" class="btn btn-success">
                View System Reports
            </a>

        </div>

    </div>

</div>

</body>
</html>