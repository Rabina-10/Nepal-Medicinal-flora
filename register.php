<?php
session_start();
require_once "config/db.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($full_name === "" || $email === "" || $password === "" || $confirm_password === "") {
        $message = "Please fill in all fields.";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "danger";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
        $messageType = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } else {

        $check = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "This email is already registered.";
            $messageType = "danger";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = "user";

            $stmt = $conn->prepare(
                "INSERT INTO Users (full_name, email, password, role)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $message = "Registration successful! You can now log in.";
                $messageType = "success";
            } else {
                $message = "Registration failed. Please try again.";
                $messageType = "danger";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration | Nepal Medicinal Flora</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f8f3;
        }

        .register-card {
            max-width: 480px;
            margin: 60px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .btn-green {
            background: #2f6b45;
            color: white;
        }

        .btn-green:hover {
            background: #245536;
            color: white;
        }
    </style>
</head>

<body>
<?php $activePage = 'register'; include __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="register-card">

        <h3 class="text-center mb-2" style="color:#2f6b45;">
            🌿 Create an Account
        </h3>

        <p class="text-center text-muted mb-4">
            Nepal Medicinal Flora
        </p>

        <?php if ($message !== ""): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name"
                       class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email"
                       class="form-control" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password"
                       class="form-control" minlength="8" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password"
                       class="form-control" minlength="8" required>
            </div>

            <button type="submit" class="btn btn-green w-100">
                Register
            </button>

        </form>

        <p class="text-center mt-3 mb-0">
            Already have an account?
            <a href="login.php" style="color:#2f6b45;">Login</a>
        </p>

        <p class="text-center mt-3">
            <a href="index.html" style="color:#2f6b45;">Back to Home</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
