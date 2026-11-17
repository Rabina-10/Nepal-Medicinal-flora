<?php
session_start();
require_once "config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $sql = "SELECT user_id, full_name, email, password, role
            FROM Users
            WHERE email = ? AND role = 'admin'
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin["password"])) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $admin["user_id"];
        $_SESSION["full_name"] = $admin["full_name"];
        $_SESSION["role"] = $admin["role"];

        header("Location: admin.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Nepal Medicinal Flora</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f8f3;
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 430px;
            margin: 70px auto;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .login-title {
            color: #2f6b45;
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
<?php $activePage = 'admin'; include __DIR__ . '/includes/navbar.php'; ?>

<div class="login-card">
    <div class="text-center mb-4">
        <h2 class="login-title fw-bold">🌿 Admin Login</h2>
        <p class="text-muted">Nepal Medicinal Flora</p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control"
                   placeholder="Enter admin email" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control"
                   placeholder="Enter password" required>
        </div>

        <button type="submit" class="btn btn-green w-100">
            Login
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="index.html" class="text-decoration-none">
            Back to Homepage
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>