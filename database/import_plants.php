<?php
session_start();

// Only a logged-in admin may run this import script.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access denied. Please log in as admin first.");
}

require_once "../config/db.php";

$file = __DIR__ . "/plants.csv";

if (!file_exists($file)) {
    die("plants.csv file not found!");
}

$handle = fopen($file, "r");

if (!$handle) {
    die("Could not open CSV file.");
}

$headers = fgetcsv($handle);

if (!$headers) {
    die("CSV file is empty.");
}

// Remove the UTF-8 BOM from the first column name
$headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

$plantStmt = $conn->prepare(
    "INSERT INTO Plants
    (common_name, scientific_name, medicinal_uses)
    VALUES (?, ?, ?)"
);

$locationStmt = $conn->prepare(
    "INSERT INTO Plant_Locations
    (plant_id, province, district)
    VALUES (?, ?, ?)"
);

$imported = 0;
$skipped = 0;

while (($data = fgetcsv($handle)) !== false) {
    if (count($data) !== count($headers)) {
        $skipped++;
        continue;
    }

    $row = array_combine($headers, $data);

    $commonName = trim($row['Common Name'] ?? '');
    $scientificName = trim($row['Scientific Name'] ?? '');
    $medicinalUse = trim($row['medicinal use'] ?? '');
    $province = trim($row['Province'] ?? '');
    $district = trim($row['District'] ?? '');

    if ($commonName === '' || $scientificName === '') {
        $skipped++;
        continue;
    }

    // Check whether this scientific name already exists
    $check = $conn->prepare(
        "SELECT plant_id FROM Plants WHERE scientific_name = ? LIMIT 1"
    );
    $check->bind_param("s", $scientificName);
    $check->execute();
    $check->bind_result($existingPlantId);

    if ($check->fetch()) {
        $check->close();
        $skipped++;
        continue;
    }

    $check->close();

    $plantStmt->bind_param(
        "sss",
        $commonName,
        $scientificName,
        $medicinalUse
    );

    if ($plantStmt->execute()) {
        $plantId = $conn->insert_id;

        if ($province !== '' || $district !== '') {
            $locationStmt->bind_param(
                "iss",
                $plantId,
                $province,
                $district
            );
            $locationStmt->execute();
        }

        $imported++;
    } else {
        $skipped++;
    }
}

$plantStmt->close();
$locationStmt->close();
fclose($handle);

echo "<h2>Import completed!</h2>";
echo "<p>Plants imported: $imported</p>";
echo "<p>Rows skipped: $skipped</p>";
?>
