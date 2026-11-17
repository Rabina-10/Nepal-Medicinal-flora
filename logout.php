
<?php
session_start();

// Clear all session data.
$_SESSION = [];

// Destroy the current session.
session_destroy();

// Redirect to the homepage.
header("Location: index.html");
exit();
?>

