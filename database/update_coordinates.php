<?php
session_start();

// Only a logged-in admin may run this import script.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access denied. Please log in as admin first.");
}

require_once "../config/db.php";

$file = __DIR__ . "/plants_final_coordinates.csv";

if (!file_exists($file)) {
    die("plants_final_coordinates.csv file not found!");
}

$handle = fopen($file, "r");

if (!$handle) {
    die("Could not open coordinate CSV file.");
}

$headers = fgetcsv($handle);

if (!$headers) {
    die("CSV file is empty.");
}

// Remove UTF-8 BOM
$headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

$findPlant = $conn->prepare(
    "SELECT plant_id FROM Plants WHERE scientific_name = ? LIMIT 1"
);

$updateLocation = $conn->prepare(
    "UPDATE Plant_Locations
     SET province = ?, district = ?, latitude = ?, longitude = ?,
         coordinate_source = ?, coordinate_confidence = ?
     WHERE plant_id = ?"
);

$insertLocation = $conn->prepare(
    "INSERT INTO Plant_Locations
     (plant_id, province, district, latitude, longitude,
      coordinate_source, coordinate_confidence)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

$updated = 0;
$notFound = 0;
$skipped = 0;

$conn->begin_transaction();

try {
    while (($data = fgetcsv($handle)) !== false) {

        if (count($data) !== count($headers)) {
            $skipped++;
            continue;
        }

        $row = array_combine($headers, $data);

        $scientificName = trim($row['Scientific Name'] ?? '');
        $province = trim($row['Province'] ?? '');
        $district = trim($row['District'] ?? '');
        $latitudeText = trim($row['Latitude'] ?? '');
        $longitudeText = trim($row['Longitude'] ?? '');
        $confidence = trim($row['Confidence'] ?? '');
        $source = trim($row['Coordinate_Source'] ?? '');

        if (
            $scientificName === '' ||
            !is_numeric($latitudeText) ||
            !is_numeric($longitudeText)
        ) {
            $skipped++;
            continue;
        }

        $latitude = (float) $latitudeText;
        $longitude = (float) $longitudeText;

        // Find the existing plant
        $findPlant->bind_param("s", $scientificName);
        $findPlant->execute();
        $findPlant->bind_result($plantId);

        if (!$findPlant->fetch()) {
            $findPlant->free_result();
            $notFound++;
            continue;
        }

        $findPlant->free_result();

        // Check whether a location row already exists
        $checkLocation = $conn->prepare(
            "SELECT location_id FROM Plant_Locations
             WHERE plant_id = ? LIMIT 1"
        );

        $checkLocation->bind_param("i", $plantId);
        $checkLocation->execute();
        $checkLocation->store_result();

        if ($checkLocation->num_rows > 0) {

            // Update existing location
            $updateLocation->bind_param(
                "ssddssi",
                $province,
                $district,
                $latitude,
                $longitude,
                $source,
                $confidence,
                $plantId
            );

            $updateLocation->execute();

        } else {

            // Insert location if one does not exist
            $insertLocation->bind_param(
                "issddss",
                $plantId,
                $province,
                $district,
                $latitude,
                $longitude,
                $source,
                $confidence
            );

            $insertLocation->execute();
        }

        $checkLocation->close();
        $updated++;
    }

    $conn->commit();

} catch (Throwable $e) {
    $conn->rollback();
    fclose($handle);
    die("Import failed: " . htmlspecialchars($e->getMessage()));
}

$findPlant->close();
$updateLocation->close();
$insertLocation->close();
fclose($handle);

echo "<h2>Coordinate Update Completed!</h2>";
echo "<p>Plants updated: $updated</p>";
echo "<p>Plants not found: $notFound</p>";
echo "<p>Rows skipped: $skipped</p>";
?>
