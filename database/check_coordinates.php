<?php
$file = __DIR__ . "/plants_final_coordinates.csv";

if (!file_exists($file)) {
    die("plants_coordinates.csv file not found!");
}

$handle = fopen($file, "r");
$headers = fgetcsv($handle);

if (!$headers) {
    die("CSV file is empty!");
}

// Remove UTF-8 BOM
$headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

echo "<h2>Coordinate CSV opened successfully!</h2>";
echo "<h3>Column names:</h3>";
echo "<pre>";
print_r($headers);
echo "</pre>";

echo "<h3>First 3 data rows:</h3>";
for ($i = 0; $i < 3; $i++) {
    $row = fgetcsv($handle);
    if ($row === false) break;
    print_r($row);
    echo "<hr>";
}

fclose($handle);
?>
