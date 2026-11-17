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

// Save conservation information
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $plant_id = (int)($_POST['plant_id'] ?? 0);
    $conservation_status = trim($_POST['conservation_status'] ?? '');
    $threats = trim($_POST['threats'] ?? '');
    $conservation_information = trim($_POST['conservation_information'] ?? '');

    if ($plant_id <= 0 || $conservation_status === '') {

        $message = "Please select a plant and enter its conservation status.";
        $messageType = "danger";

    } else {

        try {

            // Check whether conservation information already exists
            $checkSql = "SELECT conservation_id
                         FROM Conservation
                         WHERE plant_id = ?
                         LIMIT 1";

            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $plant_id);
            $checkStmt->execute();

            $checkResult = $checkStmt->get_result();
            $existingRecord = $checkResult->fetch_assoc();

            $checkStmt->close();

            if ($existingRecord) {

                // Update existing conservation record
                $sql = "UPDATE Conservation
                        SET conservation_status = ?,
                            threats = ?,
                            conservation_information = ?
                        WHERE conservation_id = ?";

                $stmt = $conn->prepare($sql);

                $conservation_id = (int)$existingRecord['conservation_id'];

                $stmt->bind_param(
                    "sssi",
                    $conservation_status,
                    $threats,
                    $conservation_information,
                    $conservation_id
                );

            } else {

                // Insert new conservation record
                $sql = "INSERT INTO Conservation
                        (
                            plant_id,
                            conservation_status,
                            threats,
                            conservation_information
                        )
                        VALUES (?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "isss",
                    $plant_id,
                    $conservation_status,
                    $threats,
                    $conservation_information
                );
            }

            if (!$stmt->execute()) {
                throw new Exception("Could not save conservation information.");
            }

            $stmt->close();

            $message = "Conservation information saved successfully!";
            $messageType = "success";

        } catch (Exception $e) {

            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Get all plants and existing conservation records
$sql = "SELECT
            p.plant_id,
            p.common_name,
            p.scientific_name,
            c.conservation_id,
            c.conservation_status,
            c.threats,
            c.conservation_information
        FROM Plants p
        LEFT JOIN Conservation c
            ON p.plant_id = c.plant_id
        ORDER BY p.common_name ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Could not load conservation records: " . htmlspecialchars($conn->error));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Conservation Management | Nepal Medicinal Flora</title>

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

        .page-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-top: 30px;
            margin-bottom: 40px;
        }

        h2, h4 {
            color: #2f6b45;
            font-weight: bold;
        }

        .table td {
            vertical-align: middle;
        }

        .status-badge {
            background: #e4efdf;
            color: #2f6b45;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
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

<?php $adminPage = 'conservation'; include __DIR__ . '/includes/admin-navbar.php'; ?>

<div class="container">

    <div class="page-card">

        <h2>Conservation Management</h2>

        <p class="text-muted">
            Add and update conservation information for medicinal plants.
        </p>

        <?php if ($message !== ""): ?>

            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <h4 class="mt-4 mb-3">Add or Update Conservation Information</h4>

        <form method="POST" action="manage-conservation.php">

            <div class="mb-3">

                <label class="form-label fw-bold">
                    Select Medicinal Plant
                </label>

                <select name="plant_id" class="form-select" required>

                    <option value="">Choose a plant</option>

                    <?php
                    // Use a separate query for the dropdown
                    $plantOptions = $conn->query(
                        "SELECT plant_id, common_name, scientific_name
                         FROM Plants
                         ORDER BY common_name ASC"
                    );

                    while ($plant = $plantOptions->fetch_assoc()):
                        ?>

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

            <div class="mb-3">

                <label class="form-label fw-bold">
                    Conservation Status
                </label>

                <select name="conservation_status"
                        class="form-select"
                        required>

                    <option value="">Select status</option>

                    <option value="Least Concern">Least Concern</option>

                    <option value="Near Threatened">Near Threatened</option>

                    <option value="Vulnerable">Vulnerable</option>

                    <option value="Endangered">Endangered</option>

                    <option value="Critically Endangered">Critically Endangered</option>

                    <option value="Data Deficient">Data Deficient</option>

                    <option value="Not Evaluated">Not Evaluated</option>

                </select>

            </div>

            <div class="mb-3">

                <label class="form-label fw-bold">
                    Threats
                </label>

                <textarea name="threats"
                          class="form-control"
                          rows="3"
                          placeholder="Example: Habitat destruction, deforestation, overharvesting"></textarea>

            </div>

            <div class="mb-4">

                <label class="form-label fw-bold">
                    Conservation Information
                </label>

                <textarea name="conservation_information"
                          class="form-control"
                          rows="4"
                          placeholder="Enter conservation methods, protection plans, and other information"></textarea>

            </div>

            <button type="submit" class="btn btn-success px-4">
                Save Conservation Information
            </button>

            <a href="admin.php" class="btn btn-secondary px-4">
                Back
            </a>

        </form>

        <hr class="my-4">

        <h4>Existing Conservation Records</h4>

        <div class="table-responsive">

            <table class="table table-bordered table-hover mt-3">

                <thead class="table-success">

                <tr>
                    <th>Plant Name</th>
                    <th>Scientific Name</th>
                    <th>Conservation Status</th>
                    <th>Threats</th>
                    <th>Conservation Information</th>
                </tr>

                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($record = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($record['common_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($record['scientific_name']); ?>
                            </td>

                            <td>

                                <?php if (!empty($record['conservation_status'])): ?>

                                    <span class="status-badge">
                                        <?php echo htmlspecialchars($record['conservation_status']); ?>
                                    </span>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Not added
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?php echo nl2br(htmlspecialchars($record['threats'] ?? '')); ?>
                            </td>

                            <td>
                                <?php echo nl2br(htmlspecialchars($record['conservation_information'] ?? '')); ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No plants found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>
