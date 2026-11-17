
<?php
session_start();
require_once "config/db.php";

// Only logged-in users and researchers can submit observations.
if (
        !isset($_SESSION["user_id"]) ||
        !in_array($_SESSION["role"] ?? "", ["user", "researcher"], true)
) {
    header("Location: login.php");
    exit();
}

$message = "";
$messageType = "";

// Get all plants for the dropdown.
$plants = $conn->query(
        "SELECT plant_id, common_name, scientific_name
     FROM Plants
     ORDER BY common_name ASC"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $plant_id = (int)($_POST["plant_id"] ?? 0);
    $location = trim($_POST["location"] ?? "");
    $observation_date = $_POST["observation_date"] ?? "";
    $details = trim($_POST["details"] ?? "");

    $photograph = null;

    if (
            $plant_id <= 0 ||
            $location === "" ||
            $observation_date === "" ||
            $details === ""
    ) {
        $message = "Please fill in all required fields.";
        $messageType = "danger";

    } elseif (
            strtotime($observation_date) === false ||
            $observation_date > date("Y-m-d")
    ) {
        $message = "Please enter a valid observation date.";
        $messageType = "danger";

    } else {

        // Check whether the selected plant exists.
        $plantCheck = $conn->prepare(
                "SELECT plant_id FROM Plants WHERE plant_id = ?"
        );

        $plantCheck->bind_param("i", $plant_id);
        $plantCheck->execute();

        $plantExists = $plantCheck->get_result()->num_rows === 1;

        $plantCheck->close();

        if (!$plantExists) {

            $message = "Please select a valid plant.";
            $messageType = "danger";

        } else {

            // Photograph is optional.
            if (
                    isset($_FILES["photograph"]) &&
                    $_FILES["photograph"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES["photograph"]["error"] !== UPLOAD_ERR_OK) {

                    $message = "There was a problem uploading the photograph.";
                    $messageType = "danger";

                } elseif ($_FILES["photograph"]["size"] > 5 * 1024 * 1024) {

                    $message = "Photograph must be smaller than 5 MB.";
                    $messageType = "danger";

                } else {

                    $allowedTypes = [
                            "image/jpeg" => "jpg",
                            "image/png" => "png",
                            "image/webp" => "webp"
                    ];

                    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $fileInfo->file(
                            $_FILES["photograph"]["tmp_name"]
                    );

                    if (!isset($allowedTypes[$mimeType])) {

                        $message = "Only JPG, PNG, and WEBP photographs are allowed.";
                        $messageType = "danger";

                    } else {

                        $uploadDirectory = __DIR__ . "/uploads/";

                        if (!is_dir($uploadDirectory)) {
                            mkdir($uploadDirectory, 0755, true);
                        }

                        $fileName = bin2hex(random_bytes(16)) . "." .
                                $allowedTypes[$mimeType];

                        $destination = $uploadDirectory . $fileName;

                        if (
                                move_uploaded_file(
                                        $_FILES["photograph"]["tmp_name"],
                                        $destination
                                )
                        ) {
                            $photograph = "uploads/" . $fileName;

                        } else {

                            $message = "Could not save the photograph.";
                            $messageType = "danger";
                        }
                    }
                }
            }

            // Save the observation if there were no errors.
            if ($message === "") {

                $user_id = (int)$_SESSION["user_id"];
                $status = "pending";

                $stmt = $conn->prepare(
                        "INSERT INTO Observations
                    (user_id, plant_id, location, observation_date,
                     photograph, details, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                        "iisssss",
                        $user_id,
                        $plant_id,
                        $location,
                        $observation_date,
                        $photograph,
                        $details,
                        $status
                );

                if ($stmt->execute()) {

                    $message = "Observation submitted successfully! Waiting for admin verification.";
                    $messageType = "success";

                } else {

                    $message = "Failed to submit observation. Please try again.";

                    // Remove uploaded image if database insertion failed.
                    if (
                            $photograph !== null &&
                            file_exists(__DIR__ . "/" . $photograph)
                    ) {
                        unlink(__DIR__ . "/" . $photograph);
                    }

                    $messageType = "danger";
                }

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Submit Observation | Nepal Medicinal Flora</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>

        body {
            background: #f5f8f3;
            font-family: Arial, sans-serif;
        }

        /* Navigation Bar */
        .navbar {
            background-color: #2f6b45;
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
            font-size: 22px;
        }

        .nav-link {
            color: white !important;
            font-size: 16px;
            padding: 12px !important;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.12);
            color: white !important;
        }

        /* Main Form Card */
        .main-card {
            max-width: 850px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .btn-green {
            background: #2f6b45;
            color: white;
            border: none;
        }

        .btn-green:hover {
            background: #245536;
            color: white;
        }

        .form-label {
            font-weight: 600;
        }

        @media (max-width: 991px) {
            .navbar-nav {
                padding-top: 10px;
            }

            .nav-link {
                padding: 8px 0 !important;
            }
        }

    </style>
</head>

<body>

<?php $activePage = 'observation'; include __DIR__ . '/includes/navbar.php'; ?>


<!-- Main Content -->
<div class="container">

    <div class="main-card">

        <h2 class="text-center mb-2" style="color:#2f6b45;">
            🌿 Submit Plant Observation
        </h2>

        <p class="text-center text-muted mb-4">
            Welcome,
            <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!
            Share your medicinal plant observation.
        </p>

        <!-- Success or Error Message -->
        <?php if ($message !== ""): ?>

            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <!-- Observation Form -->
        <form method="POST"
              action="observation.php"
              enctype="multipart/form-data">

            <!-- Plant Selection -->
            <div class="mb-3">

                <label class="form-label">
                    Select Medicinal Plant *
                </label>

                <select name="plant_id" class="form-select" required>

                    <option value="">Choose a plant</option>

                    <?php if ($plants): ?>

                        <?php while ($plant = $plants->fetch_assoc()): ?>

                            <option
                                    value="<?php echo $plant["plant_id"]; ?>"
                                    <?php echo (
                                            (int)($_POST["plant_id"] ?? 0) ===
                                            (int)$plant["plant_id"]
                                    ) ? "selected" : ""; ?>>

                                <?php
                                echo htmlspecialchars(
                                        $plant["common_name"] .
                                        " (" . $plant["scientific_name"] . ")"
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

            </div>


            <!-- Observation Location -->
            <div class="mb-3">

                <label class="form-label">
                    Observation Location *
                </label>

                <input
                        type="text"
                        name="location"
                        class="form-control"
                        placeholder="Enter district, province, or specific location"
                        value="<?php echo htmlspecialchars($_POST["location"] ?? ""); ?>"
                        required>

            </div>


            <!-- Observation Date -->
            <div class="mb-3">

                <label class="form-label">
                    Observation Date *
                </label>

                <input
                        type="date"
                        name="observation_date"
                        class="form-control"
                        max="<?php echo date("Y-m-d"); ?>"
                        value="<?php echo htmlspecialchars($_POST["observation_date"] ?? ""); ?>"
                        required>

            </div>


            <!-- Photograph Upload -->
            <div class="mb-3">

                <label class="form-label">
                    Upload Photograph
                </label>

                <input
                        type="file"
                        name="photograph"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                <small class="text-muted">
                    Optional. JPG, PNG, or WEBP. Maximum 5 MB.
                </small>

            </div>


            <!-- Observation Details -->
            <div class="mb-3">

                <label class="form-label">
                    Observation Details *
                </label>

                <textarea
                        name="details"
                        class="form-control"
                        rows="5"
                        placeholder="Describe the plant, where you found it, and any relevant details..."
                        required><?php echo htmlspecialchars($_POST["details"] ?? ""); ?></textarea>

            </div>


            <!-- Submit Button -->
            <button type="submit" class="btn btn-green w-100">
                Submit Observation
            </button>

        </form>


        <!-- Bottom Links -->
        <div class="text-center mt-3">

            <a href="plants.php" style="color:#2f6b45;">
                View Plant Database
            </a>

            |

            <a href="index.html" style="color:#2f6b45;">
                Home
            </a>

            |

            <a href="logout.php" style="color:#dc3545;">
                Logout
            </a>

        </div>

    </div>

</div>


<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
