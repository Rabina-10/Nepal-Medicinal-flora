<?php
/*
 * Shared public navbar.
 * Usage (before including): $activePage = 'plants';
 * Valid values: home, plants, map, observation, conservation, dashboard,
 *               register, login, admin
 */
$activePage = $activePage ?? '';

$navItems = [
        'home'         => ['index.html',       'Home'],
        'plants'       => ['plants.php',       'Plant Database'],
        'map'          => ['map.php',          'Distribution Map'],
        'observation'  => ['observation.php',  'Submit Observation'],
        'conservation' => ['conservation.php', 'Conservation'],
        'dashboard'    => ['dashboard.php',    'Dashboard'],
        'register'     => ['register.php',     'Register'],
        'login'        => ['login.php',        'Login'],
        'admin'        => ['admin-login.php',  'Admin'],
];
?>
<style>
    /* Shared navbar styles - identical on every public page */
    .navbar { background-color: #2f6b45 !important; }
    .navbar.sticky-top { position: sticky; top: 0; z-index: 1030; }
    .navbar .navbar-brand {
        color: #fff !important;
        font-weight: bold;
        font-size: 22px;
    }
    .navbar .nav-link {
        color: #fff !important;
        font-size: 16px;
        padding: 12px !important;
        border-radius: 6px;
    }
    .navbar .nav-link:hover,
    .navbar .nav-link.active {
        background-color: rgba(255, 255, 255, 0.12);
        color: #fff !important;
    }
    @media (max-width: 991px) {
        .navbar .navbar-nav { padding-top: 10px; }
        .navbar .nav-link { padding: 8px 0 !important; }
    }
</style>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.html">🌿 Nepal Medicinal Flora</a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($navItems as $key => [$href, $label]): ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo $key === $activePage ? ' active' : ''; ?>"
                           href="<?php echo $href; ?>"><?php echo $label; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>