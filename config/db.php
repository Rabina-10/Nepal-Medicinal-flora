<?php
/*
 * Database connection.
 *
 * ONLINE (Render):  the values come from Environment Variables
 *                   DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME, DB_SSL
 * LOCAL (XAMPP):    create config/db.local.php (copy db.local.example.php)
 *                   and put your own password there. That file is never
 *                   uploaded to GitHub.
 */

$host     = getenv('DB_HOST') ?: '127.0.0.1';
$port     = (int) (getenv('DB_PORT') ?: 3306);
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$database = getenv('DB_NAME') ?: 'medicinal_flora_db';
$useSsl   = getenv('DB_SSL') === '1';   // cloud databases (TiDB) need SSL

if (file_exists(__DIR__ . '/db.local.php')) {
    require __DIR__ . '/db.local.php';  // overrides the values above
}

$conn = mysqli_init();

if ($useSsl) {
    $conn->ssl_set(null, null, '/etc/ssl/certs/ca-certificates.crt', null, null);
}

try {
    $connected = $conn->real_connect(
        $host,
        $username,
        $password,
        $database,
        $port,
        null,
        $useSsl ? MYSQLI_CLIENT_SSL : 0
    );
} catch (mysqli_sql_exception $e) {
    $connected = false;
    error_log("DB connection error: " . $e->getMessage());
}

if (!$connected) {
    http_response_code(500);
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
