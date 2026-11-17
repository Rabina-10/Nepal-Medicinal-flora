
<?php
require_once "config/db.php";

// Fetch medicinal plants and their geographical locations
$sql = "SELECT
            p.common_name,
            p.scientific_name,
            p.medicinal_uses,
            l.province,
            l.district,
            l.latitude,
            l.longitude,
            l.coordinate_confidence
        FROM Plants p
        INNER JOIN Plant_Locations l
            ON p.plant_id = l.plant_id
        WHERE l.latitude IS NOT NULL
          AND l.longitude IS NOT NULL";

$result = $conn->query($sql);

$plants = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $plants[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nepal Medicinal Flora - Distribution Map</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>

        body {
            background-color: #f5f8f3;
            font-family: Arial, sans-serif;
        }

        /* Navigation bar */
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

        /* Page heading */
        .page-title {
            color: #2f6b45;
            font-weight: bold;
        }

        /* Map */
        #map {
            height: 550px;
            width: 100%;
            border-radius: 12px;
            border: 2px solid #dce8dc;
        }

        /* Information cards */
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .total-count {
            color: #2f6b45;
            font-weight: bold;
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


<?php $activePage = 'map'; include __DIR__ . '/includes/navbar.php'; ?>


<!-- Main Content -->
<div class="container my-4">

    <h2 class="page-title">
        Nepal Medicinal Plant Distribution Map
    </h2>

    <p class="text-muted">
        Explore the geographical distribution of medicinal plants across Nepal.
    </p>


    <!-- Total Plants Card -->
    <div class="info-card">

        <h5>Total Plants with Coordinates</h5>

        <h2 class="total-count">
            <?php echo count($plants); ?>
        </h2>

    </div>


    <!-- Map Card -->
    <div class="info-card">

        <div id="map"></div>

    </div>

</div>


<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>

    // Plant information from PHP database
    const plants = <?php
            echo json_encode(
                    $plants,
                    JSON_HEX_TAG |
                    JSON_HEX_APOS |
                    JSON_HEX_QUOT |
                    JSON_HEX_AMP
            );
            ?>;


    // Center the map on Nepal
    const map = L.map('map').setView([28.3949, 84.1240], 7);


    // OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);


    // Add a marker for each medicinal plant
    plants.forEach(function(plant) {

        const latitude = Number(plant.latitude);
        const longitude = Number(plant.longitude);

        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            return;
        }

        const marker = L.marker([latitude, longitude]).addTo(map);


        // Plant information popup
        const popupContent = `
            <strong>${escapeHtml(plant.common_name)}</strong><br>

            <i>${escapeHtml(plant.scientific_name)}</i><br>

            <b>Province:</b>
            ${escapeHtml(plant.province || 'Not available')}<br>

            <b>District:</b>
            ${escapeHtml(plant.district || 'Not available')}<br>

            <b>Medicinal Uses:</b>
            ${escapeHtml(plant.medicinal_uses || 'Not available')}<br>

            <b>Coordinate Confidence:</b>
            ${escapeHtml(plant.coordinate_confidence || 'Not available')}
        `;

        marker.bindPopup(popupContent);

    });


    // Prevent HTML injection in popup information
    function escapeHtml(value) {

        return String(value).replace(/[&<>"']/g, function(character) {

            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];

        });

    }

</script>

</body>
</html>
