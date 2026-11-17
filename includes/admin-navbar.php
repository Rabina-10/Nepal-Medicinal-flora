<?php
/*
 * Shared admin navbar.
 * Usage: set $adminPage before including this file.
 * The Dashboard button is hidden on the dashboard page itself.
 */
$adminPage = $adminPage ?? '';
?>

<style>
    .navbar {
        background-color: #2f6b45 !important;
        position: sticky;
        top: 0;
        z-index: 1030;
    }

    .navbar .navbar-brand {
        color: #fff !important;
        font-weight: bold;
        font-size: 22px;
    }
</style>

<nav class="navbar navbar-dark no-print">
    <div class="container">
        <a class="navbar-brand" href="admin.php">
            🌿 Nepal Medicinal Flora - Admin
        </a>

        <div class="d-flex gap-2">
            <?php if ($adminPage !== 'dashboard'): ?>
                <a href="admin.php" class="btn btn-light btn-sm">Dashboard</a>
            <?php endif; ?>

            <a href="admin-logout.php" class="btn btn-light btn-sm">Logout</a>
        </div>
    </div>
</nav>