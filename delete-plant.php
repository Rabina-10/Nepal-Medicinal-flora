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

// Delete plant only after confirmation
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $plant_id = (int)($_POST['plant_id'] ?? 0);

    if ($plant_id <= 0) {
        $message = "Invalid plant selected.";
        $messageType = "danger";

    } else {

        try {
            $conn->begin_transaction();

            // Delete related plant locations first
            $locationSql = "DELETE FROM Plant_Locations WHERE plant_id = ?";
            $locationStmt = $conn->prepare($locationSql);
            $locationStmt->bind_param("i", $plant_id);
            $locationStmt->execute();
            $locationStmt->close();

            // Delete plant
            // Observations or conservation records may prevent deletion
            // because of foreign-key relationships.
            $plantSql = "DELETE FROM Plants WHERE plant_id = ?";
            $plantStmt = $conn->prepare($plantSql);
            $plantStmt->bind_param("i", $plant_id);
            $plantStmt->execute();

            if ($plantStmt->affected_rows === 0) {
                throw new Exception("Plant not found.");
            }

            $plantStmt->close();

            $conn->commit();

            $message = "Plant deleted successfully!";
            $messageType = "success";

        } catch (Exception $e) {

            $conn->rollback();

            $message = "Could not delete this plant. It may have related observation or conservation records.";
            $messageType = "danger";
        }
    }
}

// Load all plants
$result = $conn->query(
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

    <title>Delete Plant | Nepal Medicinal Flora</title>

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

        .delete-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-top: 35px;
        }

        h2 {
            color: #2f6b45;
            font-weight: bold;
        }
    </style>
</head>

<body>

<?php $adminPage = 'delete-plant'; include __DIR__ . '/includes/admin-navbar.php'; ?>

<div class="container">

    <div class="delete-card">

        <h2>Delete Medicinal Plant</h2>

        <p class="text-muted">
            Select a plant you want to remove from the database.
        </p>

        <?php if ($message !== ""): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="delete-plant.php"
              onsubmit="return confirm('Are you sure you want to delete this plant? This action cannot be undone.');">

            <div class="mb-3">

                <label class="form-label fw-bold">
                    Select Plant
                </label>

                <select name="plant_id" class="form-select" required>

                    <option value="">Choose a plant</option>

                    <?php while ($plant = $result->fetch_assoc()): ?>

                        <option value="<?php echo (int)$plant['plant_id']; ?>">

                            <?php
                            echo htmlspecialchars(
                                $plant['common_name'] .
                                " (" . $plant['scientific_name'] . ")"
                            );
                            ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <button type="submit" class="btn btn-danger">
                Delete Plant
            </button>

            <a href="admin.php" class="btn btn-secondary">
                Back to Dashboard
            </a>

        </form>

    </div>

</div>

</body>
</html>
