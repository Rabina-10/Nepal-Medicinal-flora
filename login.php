
<?php
session_start();
require_once "config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $message = "Please enter your email and password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT user_id, full_name, email, password, role
             FROM Users
             WHERE email = ? AND role IN ('user', 'researcher')
             LIMIT 1"
        );

        if (!$stmt) {
            die("Database query error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];

                header("Location: observation.php");
                exit();

            } else {
                $message = "Incorrect email or password.";
            }

        } else {
            $message = "Incorrect email or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Login | Nepal Medicinal Flora</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f8f3;
        }

        .login-card {
            max-width: 450px;
            margin: 70px auto;
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
<?php $activePage = 'login'; include __DIR__ . '/includes/navbar.php'; ?>

<div class="container">

    <div class="login-card">

        <h3 class="text-center mb-2" style="color:#2f6b45;">
            🌿 User Login
        </h3>

        <p class="text-center text-muted mb-4">
            Nepal Medicinal Flora
        </p>

        <?php if ($message !== ""): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <div class="mb-3">
                <label class="form-label">Email Address</label>

                <input type="email"
                       name="email"
                       class="form-control"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>

                <input type="password"
                       name="password"
                       class="form-control"
                       required>
            </div>

            <button type="submit" class="btn btn-green w-100">
                Login
            </button>

        </form>

        <p class="text-center mt-3">
            Don't have an account?
            <a href="register.php" style="color:#2f6b45;">
                Register
            </a>
        </p>

        <p class="text-center">
            <a href="index.html" style="color:#2f6b45;">
                Back to Home
            </a>
        </p>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
