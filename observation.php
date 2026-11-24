<?php
session_start();
require_once "config/db.php";

// Only logged-in users and researchers can submit observations.
if (
        !isset($_SESSION["user_id"]) ||
        !in_array($_SESSION["role"] ?? "", ["user", "researcher"], true)
) {
    header("Location: login.php");
    exit();
}

// Provinces of Nepal and their districts (77 districts).
$nepal = [
        "Koshi" => [
                "Bhojpur", "Dhankuta", "Ilam", "Jhapa", "Khotang", "Morang",
                "Okhaldhunga", "Panchthar", "Sankhuwasabha", "Solukhumbu",
                "Sunsari", "Taplejung", "Terhathum", "Udayapur"
        ],
        "Madhesh" => [
                "Bara", "Dhanusha", "Mahottari", "Parsa", "Rautahat",
                "Saptari", "Sarlahi", "Siraha"
        ],
        "Bagmati" => [
                "Bhaktapur", "Chitwan", "Dhading", "Dolakha", "Kathmandu",
                "Kavrepalanchok", "Lalitpur", "Makwanpur", "Nuwakot",
                "Ramechhap", "Rasuwa", "Sindhuli", "Sindhupalchok"
        ],
        "Gandaki" => [
                "Baglung", "Gorkha", "Kaski", "Lamjung", "Manang", "Mustang",
                "Myagdi", "Nawalpur", "Parbat", "Syangja", "Tanahun"
        ],
        "Lumbini" => [
                "Arghakhanchi", "Banke", "Bardiya", "Dang", "Eastern Rukum",
                "Gulmi", "Kapilvastu", "Palpa", "Parasi", "Pyuthan",
                "Rolpa", "Rupandehi"
        ],
        "Karnali" => [
                "Dailekh", "Dolpa", "Humla", "Jajarkot", "Jumla", "Kalikot",
                "Mugu", "Salyan", "Surkhet", "Western Rukum"
        ],
        "Sudurpashchim" => [
                "Achham", "Baitadi", "Bajhang", "Bajura", "Dadeldhura",
                "Darchula", "Doti", "Kailali", "Kanchanpur"
        ]
];

// Places (municipalities etc.) grouped by district, shown in a dropdown.
// Districts not listed here use the "Other (type manually)" option instead.
// The district name must match the spelling used in $nepal above exactly.
// This is a starter list: please check it against the official list of
// local levels and add the remaining districts.
$places = [

    // ---- Koshi ----
        "Ilam" => [
                "Ilam Municipality", "Deumai Municipality", "Mai Municipality",
                "Suryodaya Municipality", "Phakphokthum Rural Municipality",
                "Mai Jogmai Rural Municipality", "Chulachuli Rural Municipality",
                "Rong Rural Municipality", "Mangsebung Rural Municipality",
                "Sandakpur Rural Municipality"
        ],
        "Jhapa" => [
                "Mechinagar Municipality", "Birtamod Municipality",
                "Damak Municipality", "Bhadrapur Municipality",
                "Shivasatakshi Municipality", "Arjundhara Municipality",
                "Kankai Municipality", "Gauradaha Municipality",
                "Kamal Rural Municipality", "Buddhashanti Rural Municipality",
                "Haldibari Rural Municipality", "Jhapa Rural Municipality",
                "Barhadashi Rural Municipality", "Gaurigunj Rural Municipality",
                "Kachankawal Rural Municipality"
        ],
        "Dhankuta" => [
                "Dhankuta Municipality", "Pakhribas Municipality",
                "Mahalaxmi Municipality", "Sangurigadhi Rural Municipality",
                "Chaubise Rural Municipality", "Sahidbhumi Rural Municipality",
                "Chhathar Jorpati Rural Municipality"
        ],

    // ---- Bagmati ----
        "Kathmandu" => [
                "Kathmandu Metropolitan City", "Kageshwari-Manohara Municipality",
                "Kirtipur Municipality", "Gokarneshwar Municipality",
                "Chandragiri Municipality", "Tokha Municipality",
                "Tarakeshwar Municipality", "Dakshinkali Municipality",
                "Nagarjun Municipality", "Budhanilkantha Municipality",
                "Shankharapur Municipality"
        ],
        "Lalitpur" => [
                "Lalitpur Metropolitan City", "Godawari Municipality",
                "Mahalaxmi Municipality", "Konjyosom Rural Municipality",
                "Bagmati Rural Municipality", "Mahankal Rural Municipality"
        ],
        "Bhaktapur" => [
                "Bhaktapur Municipality", "Madhyapur Thimi Municipality",
                "Changunarayan Municipality", "Suryabinayak Municipality"
        ],
        "Chitwan" => [
                "Bharatpur Metropolitan City", "Rapti Municipality",
                "Ratnanagar Municipality", "Khairahani Municipality",
                "Madi Municipality", "Kalika Municipality",
                "Ichchhakamana Rural Municipality"
        ],

    // ---- Gandaki ----
        "Kaski" => [
                "Pokhara Metropolitan City", "Annapurna Rural Municipality",
                "Machhapuchchhre Rural Municipality", "Madi Rural Municipality",
                "Rupa Rural Municipality"
        ],

    // ---- Lumbini ----
        "Dang" => [
                "Ghorahi Sub-Metropolitan City", "Tulsipur Sub-Metropolitan City",
                "Lamahi Municipality", "Rapti Rural Municipality",
                "Gadhawa Rural Municipality", "Rajpur Rural Municipality",
                "Shantinagar Rural Municipality", "Babai Rural Municipality",
                "Dangisharan Rural Municipality", "Banglachuli Rural Municipality"
        ],

    // ---- Karnali ----
        "Jumla" => [
                "Chandannath Municipality", "Tatopani Rural Municipality",
                "Tila Rural Municipality", "Kanakasundari Rural Municipality",
                "Sinja Rural Municipality", "Hima Rural Municipality",
                "Guthichaur Rural Municipality", "Patarasi Rural Municipality"
        ]
];

// Value of the "Other (type manually)" option in the place dropdown.
const PLACE_OTHER = "__other__";

$message = "";
$messageType = "";

// Get all plants for the dropdown.
$plants = $conn->query(
        "SELECT plant_id, common_name, scientific_name
     FROM Plants
     ORDER BY common_name ASC"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $plant_id = (int)($_POST["plant_id"] ?? 0);
    $province = trim($_POST["province"] ?? "");
    $district = trim($_POST["district"] ?? "");
    $place_choice = trim($_POST["place_choice"] ?? "");
    $place_other = mb_substr(trim($_POST["place_other"] ?? ""), 0, 100);
    $observation_date = $_POST["observation_date"] ?? "";
    $details = trim($_POST["details"] ?? "");

    // The place comes from the dropdown, or is typed after choosing "Other".
    $place = "";
    if ($place_choice === PLACE_OTHER) {
        $place = $place_other;
    } elseif ($place_choice !== "") {
        $place = $place_choice;
    }

    $photograph = null;

    if (
            $plant_id <= 0 ||
            $province === "" ||
            $district === "" ||
            $observation_date === "" ||
            $details === ""
    ) {
        $message = "Please fill in all required fields.";
        $messageType = "danger";

    } elseif (
            !isset($nepal[$province]) ||
            !in_array($district, $nepal[$province], true)
    ) {
        $message = "Please select a valid province and district.";
        $messageType = "danger";

    } elseif (
            $place_choice !== "" &&
            $place_choice !== PLACE_OTHER &&
            !in_array($place_choice, $places[$district] ?? [], true)
    ) {
        $message = "Please select a valid place for this district.";
        $messageType = "danger";

    } elseif ($place_choice === PLACE_OTHER && $place_other === "") {
        $message = "Please type the place name, or choose one from the list.";
        $messageType = "danger";

    } elseif (
            strtotime($observation_date) === false ||
            $observation_date > date("Y-m-d")
    ) {
        $message = "Please enter a valid observation date.";
        $messageType = "danger";

    } else {

        // Saved as one text value, e.g. "Madhyapur Thimi Municipality, Bhaktapur, Bagmati".
        $location = ($place !== "" ? $place . ", " : "") .
                $district . ", " . $province;

        // Check whether the selected plant exists.
        $plantCheck = $conn->prepare(
                "SELECT plant_id FROM Plants WHERE plant_id = ?"
        );

        $plantCheck->bind_param("i", $plant_id);
        $plantCheck->execute();

        $plantExists = $plantCheck->get_result()->num_rows === 1;

        $plantCheck->close();

        if (!$plantExists) {

            $message = "Please select a valid plant.";
            $messageType = "danger";

        } else {

            // Photograph is optional.
            if (
                    isset($_FILES["photograph"]) &&
                    $_FILES["photograph"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES["photograph"]["error"] !== UPLOAD_ERR_OK) {

                    $message = "There was a problem uploading the photograph.";
                    $messageType = "danger";

                } elseif ($_FILES["photograph"]["size"] > 5 * 1024 * 1024) {

                    $message = "Photograph must be smaller than 5 MB.";
                    $messageType = "danger";

                } else {

                    $allowedTypes = [
                            "image/jpeg" => "jpg",
                            "image/png" => "png",
                            "image/webp" => "webp"
                    ];

                    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $fileInfo->file(
                            $_FILES["photograph"]["tmp_name"]
                    );

                    if (!isset($allowedTypes[$mimeType])) {

                        $message = "Only JPG, PNG, and WEBP photographs are allowed.";
                        $messageType = "danger";

                    } else {

                        $uploadDirectory = __DIR__ . "/uploads/";

                        if (!is_dir($uploadDirectory)) {
                            mkdir($uploadDirectory, 0755, true);
                        }

                        $fileName = bin2hex(random_bytes(16)) . "." .
                                $allowedTypes[$mimeType];

                        $destination = $uploadDirectory . $fileName;

                        if (
                                move_uploaded_file(
                                        $_FILES["photograph"]["tmp_name"],
                                        $destination
                                )
                        ) {
                            $photograph = "uploads/" . $fileName;

                        } else {

                            $message = "Could not save the photograph.";
                            $messageType = "danger";
                        }
                    }
                }
            }

            // Save the observation if there were no errors.
            if ($message === "") {

                $user_id = (int)$_SESSION["user_id"];
                $status = "pending";

                $stmt = $conn->prepare(
                        "INSERT INTO Observations
                    (user_id, plant_id, location, observation_date,
                     photograph, details, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                        "iisssss",
                        $user_id,
                        $plant_id,
                        $location,
                        $observation_date,
                        $photograph,
                        $details,
                        $status
                );

                if ($stmt->execute()) {

                    $message = "Observation submitted successfully! Waiting for admin verification.";
                    $messageType = "success";

                } else {

                    $message = "Failed to submit observation. Please try again.";

                    // Remove uploaded image if database insertion failed.
                    if (
                            $photograph !== null &&
                            file_exists(__DIR__ . "/" . $photograph)
                    ) {
                        unlink(__DIR__ . "/" . $photograph);
                    }

                    $messageType = "danger";
                }

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Submit Observation | Nepal Medicinal Flora</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>

        body {
            background: #f5f8f3;
            font-family: Arial, sans-serif;
        }

        /* Navigation Bar */
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

        /* Main Form Card */
        .main-card {
            max-width: 850px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .btn-green {
            background: #2f6b45;
            color: white;
            border: none;
        }

        .btn-green:hover {
            background: #245536;
            color: white;
        }

        .form-label {
            font-weight: 600;
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

<?php $activePage = 'observation'; include __DIR__ . '/includes/navbar.php'; ?>


<!-- Main Content -->
<div class="container">

    <div class="main-card">

        <h2 class="text-center mb-2" style="color:#2f6b45;">
            🌿 Submit Plant Observation
        </h2>

        <p class="text-center text-muted mb-4">
            Welcome,
            <?php echo htmlspecialchars($_SESSION["full_name"] ?? ""); ?>!
            Share your medicinal plant observation.
        </p>

        <!-- Success or Error Message -->
        <?php if ($message !== ""): ?>

            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <!-- Observation Form -->
        <form method="POST"
              action="observation.php"
              enctype="multipart/form-data">

            <!-- Plant Selection -->
            <div class="mb-3">

                <label class="form-label">
                    Select Medicinal Plant *
                </label>

                <select name="plant_id" class="form-select" required>

                    <option value="">Choose a plant</option>

                    <?php if ($plants): ?>

                        <?php while ($plant = $plants->fetch_assoc()): ?>

                            <option
                                    value="<?php echo $plant["plant_id"]; ?>"
                                    <?php echo (
                                            (int)($_POST["plant_id"] ?? 0) ===
                                            (int)$plant["plant_id"]
                                    ) ? "selected" : ""; ?>>

                                <?php
                                echo htmlspecialchars(
                                        $plant["common_name"] .
                                        " (" . $plant["scientific_name"] . ")"
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

            </div>


            <!-- Province and District -->
            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Province *
                    </label>

                    <select name="province" id="province"
                            class="form-select" required>

                        <option value="">Choose a province</option>

                        <?php foreach (array_keys($nepal) as $name): ?>

                            <option value="<?php echo htmlspecialchars($name); ?>"
                                    <?php echo (($_POST["province"] ?? "") === $name)
                                            ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        District *
                    </label>

                    <select name="district" id="district"
                            class="form-select" required>

                        <option value="">Choose a province first</option>

                    </select>

                </div>

            </div>


            <!-- Place: chosen from a list for the selected district -->
            <div class="mb-3">

                <label class="form-label">
                    Place / Municipality
                </label>

                <select name="place_choice" id="place_choice"
                        class="form-select">

                    <option value="">Choose a district first</option>

                </select>

                <!-- Shown only when "Other (type manually)" is selected -->
                <input
                        type="text"
                        name="place_other"
                        id="place_other"
                        class="form-control mt-2 d-none"
                        maxlength="100"
                        placeholder="Type the village, ward, forest or trail name"
                        value="<?php echo htmlspecialchars($_POST["place_other"] ?? ""); ?>">

                <small class="text-muted">
                    Optional. Choose from the list, or select "Other" if your place is not listed.
                </small>

            </div>


            <!-- Observation Date -->
            <div class="mb-3">

                <label class="form-label">
                    Observation Date *
                </label>

                <input
                        type="date"
                        name="observation_date"
                        class="form-control"
                        max="<?php echo date("Y-m-d"); ?>"
                        value="<?php echo htmlspecialchars($_POST["observation_date"] ?? ""); ?>"
                        required>

            </div>


            <!-- Photograph Upload -->
            <div class="mb-3">

                <label class="form-label">
                    Upload Photograph
                </label>

                <input
                        type="file"
                        name="photograph"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                <small class="text-muted">
                    Optional. JPG, PNG, or WEBP. Maximum 5 MB.
                </small>

            </div>


            <!-- Observation Details -->
            <div class="mb-3">

                <label class="form-label">
                    Observation Details *
                </label>

                <textarea
                        name="details"
                        class="form-control"
                        rows="5"
                        placeholder="Describe the plant, where you found it, and any relevant details..."
                        required><?php echo htmlspecialchars($_POST["details"] ?? ""); ?></textarea>

            </div>


            <!-- Submit Button -->
            <button type="submit" class="btn btn-green w-100">
                Submit Observation
            </button>

        </form>


        <!-- Bottom Links -->
        <div class="text-center mt-3">

            <a href="plants.php" style="color:#2f6b45;">
                View Plant Database
            </a>

            |

            <a href="index.html" style="color:#2f6b45;">
                Home
            </a>

            |

            <a href="logout.php" style="color:#dc3545;">
                Logout
            </a>

        </div>

    </div>

</div>


<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

    // Provinces and districts from PHP
    const nepal = <?php
            echo json_encode(
                    $nepal,
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            );
            ?>;

    // Places per district from PHP ($places above)
    const places = <?php
            echo json_encode(
                    (object)$places,
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            );
            ?>;

    // District and place kept after a failed submit
    const selectedDistrict = <?php
            echo json_encode(
                    $_POST["district"] ?? "",
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            );
            ?>;

    const selectedPlace = <?php
            echo json_encode(
                    $_POST["place_choice"] ?? "",
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            );
            ?>;

    const OTHER = <?php echo json_encode(PLACE_OTHER); ?>;

    const provinceSelect = document.getElementById('province');
    const districtSelect = document.getElementById('district');
    const placeSelect = document.getElementById('place_choice');
    const placeOther = document.getElementById('place_other');

    // Fill the district list for the chosen province
    function fillDistricts(keepValue) {

        const list = nepal[provinceSelect.value] || [];

        districtSelect.innerHTML = '';

        const first = document.createElement('option');
        first.value = '';
        first.textContent = list.length ? 'Choose a district' : 'Choose a province first';
        districtSelect.appendChild(first);

        list.forEach(function(name) {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            if (name === keepValue) {
                option.selected = true;
            }
            districtSelect.appendChild(option);
        });

    }

    // Fill the place list for the chosen district
    function fillPlaces(keepValue) {

        const district = districtSelect.value;
        const list = places[district] || [];

        placeSelect.innerHTML = '';

        const first = document.createElement('option');
        first.value = '';

        if (district === '') {
            first.textContent = 'Choose a district first';
        } else if (list.length) {
            first.textContent = 'Choose a place (optional)';
        } else {
            first.textContent = 'No places listed yet - choose Other';
        }

        placeSelect.appendChild(first);

        list.forEach(function(name) {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            if (name === keepValue) {
                option.selected = true;
            }
            placeSelect.appendChild(option);
        });

        // "Other" is available once a district is chosen
        if (district !== '') {
            const other = document.createElement('option');
            other.value = OTHER;
            other.textContent = 'Other (type manually)';
            if (keepValue === OTHER) {
                other.selected = true;
            }
            placeSelect.appendChild(other);
        }

        togglePlaceOther();

    }

    // Show the text box only when "Other" is selected
    function togglePlaceOther() {

        if (placeSelect.value === OTHER) {
            placeOther.classList.remove('d-none');
        } else {
            placeOther.classList.add('d-none');
            placeOther.value = '';
        }

    }

    provinceSelect.addEventListener('change', function() {
        fillDistricts('');
        fillPlaces('');
    });

    districtSelect.addEventListener('change', function() {
        fillPlaces('');
    });

    placeSelect.addEventListener('change', togglePlaceOther);

    fillDistricts(selectedDistrict);
    fillPlaces(selectedPlace);

</script>

</body>
</html>
