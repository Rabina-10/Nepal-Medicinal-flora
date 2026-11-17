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

// Update observation status
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $observation_id = (int)($_POST['observation_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowedStatuses = ['pending', 'verified', 'rejected'];

    if ($observation_id <= 0 || !in_array($status, $allowedStatuses, true)) {

        $message = "Invalid observation or status.";
        $messageType = "danger";

    } else {

        $sql = "UPDATE Observations
                SET status = ?
                WHERE observation_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $observation_id);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $message = "Observation status updated successfully!";
            $messageType = "success";
        } else {
            $message = "Could not update observation status.";
            $messageType = "danger";
        }

        $stmt->close();
    }
}

// Get all observations with user and plant details
$sql = "SELECT
            o.observation_id,
            o.location,
            o.observation_date,
            o.photograph,
            o.details,
            o.status,
            u.full_name,
            p.common_name,
            p.scientific_name
        FROM Observations o
        LEFT JOIN Users u ON o.user_id = u.user_id
        LEFT JOIN Plants p ON o.plant_id = p.plant_id
        ORDER BY o.observation_id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Could not load observations: " . htmlspecialchars($conn->error));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Observations | Nepal Medicinal Flora</title>

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

        h2 {
            color: #2f6b45;
            font-weight: bold;
        }

        .observation-image {
            width: 90px;
            height: 75px;
            object-fit: cover;
            border-radius: 6px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-verified {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-rejected {
            background: #f8d7da;
            color: #842029;
        }

        .status-label {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .table td {
            vertical-align: middle;
        }
    </style>
</head>

<body>

<?php $adminPage = 'observations'; include __DIR__ . '/includes/admin-navbar.php'; ?>

<div class="container">

    <div class="page-card">

        <h2>Observation Management</h2>

        <p class="text-muted">
            Review submitted plant observations and update their status.
        </p>

        <?php if ($message !== ""): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">

            <table class="table table-bordered table-hover">

                <thead class="table-success">
                <tr>
                    <th>ID</th>
                    <th>Submitted By</th>
                    <th>Plant</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Photograph</th>
                    <th>Details</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($observation = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo (int)$observation['observation_id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($observation['full_name'] ?? 'Unknown'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($observation['common_name'] ?? 'Unknown'); ?>

                                <br>

                                <small class="text-muted">
                                    <?php echo htmlspecialchars($observation['scientific_name'] ?? ''); ?>
                                </small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($observation['location'] ?? ''); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($observation['observation_date'] ?? ''); ?>
                            </td>

                            <td>

                                <?php if (!empty($observation['photograph'])): ?>

                                    <a href="<?php echo htmlspecialchars($observation['photograph']); ?>"
                                       target="_blank" rel="noopener noreferrer">

                                        <img
                                            src="<?php echo htmlspecialchars($observation['photograph']); ?>"
                                            class="observation-image"
                                            alt="Observation photograph"
                                        >

                                    </a>

                                <?php else: ?>

                                    <span class="text-muted">No image</span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?php echo nl2br(htmlspecialchars($observation['details'] ?? '')); ?>
                            </td>

                            <td>

                                <span class="status-label status-<?php echo htmlspecialchars($observation['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($observation['status'])); ?>
                                </span>

                            </td>

                            <td>

                                <form method="POST" action="manage-observations.php">

                                    <input type="hidden"
                                           name="observation_id"
                                           value="<?php echo (int)$observation['observation_id']; ?>">

                                    <select name="status"
                                            class="form-select form-select-sm mb-2"
                                            required>

                                        <option value="pending"
                                            <?php echo $observation['status'] === 'pending' ? 'selected' : ''; ?>>
                                            Pending
                                        </option>

                                        <option value="verified"
                                            <?php echo $observation['status'] === 'verified' ? 'selected' : ''; ?>>
                                            Verified
                                        </option>

                                        <option value="rejected"
                                            <?php echo $observation['status'] === 'rejected' ? 'selected' : ''; ?>>
                                            Rejected
                                        </option>

                                    </select>

                                    <button type="submit"
                                            class="btn btn-success btn-sm w-100">
                                        Update Status
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            No observations have been submitted yet.
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
