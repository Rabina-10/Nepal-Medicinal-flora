<?php
require_once "config/db.php";

// Get search keyword
$search = trim($_GET['search'] ?? '');

// Fetch plants from database
$sql = "SELECT
            p.plant_id,
            p.common_name,
            p.scientific_name,
            p.family,
            p.description,
            p.medicinal_uses,
            l.province,
            l.district,
            l.latitude,
            l.longitude
        FROM Plants p
        LEFT JOIN Plant_Locations l
            ON p.plant_id = l.plant_id";

if ($search !== '') {
    $sql .= " WHERE p.common_name LIKE ?
              OR p.scientific_name LIKE ?
              OR p.family LIKE ?
              OR p.medicinal_uses LIKE ?
              OR l.province LIKE ?
              OR l.district LIKE ?";
}

$sql .= " ORDER BY p.common_name ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database query error: " . $conn->error);
}

if ($search !== '') {
    $keyword = "%" . $search . "%";

    $stmt->bind_param(
            "ssssss",
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword
    );
}

$stmt->execute();

$result = $stmt->get_result();
$totalPlants = $result->num_rows;
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Plant Database - Nepal Medicinal Flora</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
              rel="stylesheet">

        <style>
            body {
                background-color: #f5f8f3;
                font-family: Arial, sans-serif;
            }

            .navbar {
                background-color: #2f6b45;
            }

            .navbar-brand,
            .nav-link {
                color: white !important;
            }

            .navbar-brand {
                font-weight: bold;
            }

            .nav-link:hover,
            .nav-link.active {
                color: #d4edda !important;
                background-color: rgba(255, 255, 255, 0.12);
            }

            .page-title {
                color: #2f6b45;
                font-weight: bold;
            }

            .search-box {
                background: white;
                padding: 20px;
                border-radius: 12px;
                margin-bottom: 25px;
            }

            .plant-card {
                background: white;
                border: 1px solid #e0e9df;
                border-radius: 12px;
                padding: 22px;
                height: 100%;
                box-shadow: 0 2px 7px rgba(0, 0, 0, 0.05);
                transition: 0.2s;
            }

            .plant-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 5px 14px rgba(0, 0, 0, 0.10);
            }

            .plant-image {
                width: 100%;
                height: 220px;
                object-fit: cover;
                border-radius: 8px;
                margin-bottom: 15px;
                background-color: #eef3eb;
            }

            .image-placeholder {
                min-height: 100px;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #eef3eb;
                border-radius: 8px;
                margin-bottom: 15px;
                padding: 15px;
            }

            .image-credit {
                display: block;
                font-size: 12px;
                margin-top: -10px;
                margin-bottom: 12px;
            }

            .plant-card h5 {
                color: #2f6b45;
                font-weight: bold;
            }

            .scientific-name {
                color: #777;
                font-style: italic;
            }

            .search-btn,
            .details-btn {
                background-color: #2f6b45;
                color: white;
            }

            .search-btn:hover,
            .details-btn:hover {
                background-color: #245437;
                color: white;
            }

            footer {
                background-color: #2f6b45;
                color: white;
                padding: 20px;
                margin-top: 50px;
            }
        </style>
    </head>

    <body>

    <!-- Navigation -->
    <?php
    $activePage = 'plants';
    include __DIR__ . '/includes/navbar.php';
    ?>

    <!-- Main Content -->
    <div class="container my-5">

        <h2 class="page-title">
            Nepal Medicinal Plant Database
        </h2>

        <p class="text-muted">
            Explore medicinal plants found across different regions of Nepal.
        </p>

        <!-- Search Section -->
        <div class="search-box">

            <h5 class="page-title">
                Total Plants: <?php echo $totalPlants; ?>
            </h5>

            <form method="GET" action="plants.php" class="row g-2 mt-2">
                <div class="col-md-10">
                    <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search by common name, scientific name, family, province or district..."
                            value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn search-btn w-100">
                        Search
                    </button>
                </div>
            </form>

            <?php if ($search !== ''): ?>
                <a href="plants.php" class="btn btn-sm btn-outline-secondary mt-2">
                    Clear Search
                </a>
            <?php endif; ?>

        </div>

        <!-- Plant Cards -->
        <div class="row g-4">

            <?php if ($totalPlants > 0): ?>

                <?php while ($plant = $result->fetch_assoc()): ?>

                    <?php
                    $plantName = $plant['common_name'] ?? 'Medicinal plant';
                    $wikiTitle = trim($plant['scientific_name'] ?: $plantName);
                    // "Bambusa spp." -> "Bambusa" so Wikipedia finds the genus page
                    $wikiTitle = trim(preg_replace('/\s+spp?\.?$/i', '', $wikiTitle));
                    ?>

                    <div class="col-md-4">
                        <div class="plant-card">

                            <img
                                    class="plant-image"
                                    data-wiki-title="<?php echo htmlspecialchars($wikiTitle, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="Photo of <?php echo htmlspecialchars($plantName, ENT_QUOTES, 'UTF-8'); ?>"
                                    loading="lazy"
                                    hidden
                            >

                            <a
                                    class="image-credit"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    hidden
                            >
                                Image source: Wikipedia
                            </a>

                            <div class="image-placeholder text-muted text-center">
                                Loading plant image…
                            </div>

                            <h5>
                                <?php echo htmlspecialchars($plant['common_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </h5>

                            <p class="scientific-name">
                                <?php echo htmlspecialchars($plant['scientific_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <hr>

                            <p>
                                <strong>Family:</strong>
                                <?php echo htmlspecialchars($plant['family'] ?: 'Not available', ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <p>
                                <strong>Medicinal Uses:</strong><br>
                                <?php echo nl2br(htmlspecialchars($plant['medicinal_uses'] ?: 'Not available', ENT_QUOTES, 'UTF-8')); ?>
                            </p>

                            <p>
                                <strong>Province:</strong>
                                <?php echo htmlspecialchars($plant['province'] ?: 'Not available', ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <p>
                                <strong>District:</strong>
                                <?php echo htmlspecialchars($plant['district'] ?: 'Not available', ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <a
                                    href="plant-details.php?id=<?php echo (int) $plant['plant_id']; ?>"
                                    class="btn details-btn btn-sm"
                            >
                                View Details
                            </a>

                            <a href="map.php" class="btn btn-outline-success btn-sm">
                                View Map
                            </a>

                        </div>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="col-12">
                    <div class="alert alert-info">
                        No plants found. Please try another search.
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const WIKI_API = "https://en.wikipedia.org/w/api.php";

        // Ask Wikipedia for a page image. Returns the page (with thumbnail) or null.
        async function wikiRequest(extra) {
            const params = new URLSearchParams({
                action: "query",
                format: "json",
                origin: "*",
                prop: "pageimages",
                piprop: "thumbnail",
                pithumbsize: "500",
                redirects: "1",
                ...extra
            });

            const response = await fetch(WIKI_API + "?" + params.toString());
            if (!response.ok) {
                throw new Error("Wikipedia image request failed");
            }

            const data = await response.json();
            const pages = data.query && data.query.pages
                ? Object.values(data.query.pages)
                : [];

            return pages.find(p => p.thumbnail && p.thumbnail.source) || null;
        }

        async function findWikiImage(title) {
            // 1) exact article title (follows redirects)
            const exact = await wikiRequest({ titles: title });
            if (exact) {
                return exact;
            }

            // 2) fallback: search Wikipedia for this name and use the best match
            return await wikiRequest({
                generator: "search",
                gsrsearch: title,
                gsrlimit: "1"
            });
        }

        async function loadPlantImage(card) {
            const img = card.querySelector(".plant-image");
            const placeholder = card.querySelector(".image-placeholder");
            const credit = card.querySelector(".image-credit");
            const title = img.dataset.wikiTitle;

            try {
                const page = await findWikiImage(title);

                if (page) {
                    img.src = page.thumbnail.source;
                    img.hidden = false;
                    placeholder.hidden = true;

                    credit.href =
                        "https://en.wikipedia.org/wiki/" +
                        encodeURIComponent(page.title.replaceAll(" ", "_"));
                    credit.hidden = false;
                } else {
                    placeholder.textContent = "No Wikipedia image found for this plant.";
                }
            } catch (error) {
                placeholder.textContent = "Plant image could not be loaded.";
            }
        }

        // IMPORTANT: observe the visible card, not the hidden <img>.
        // An element with the "hidden" attribute (display:none) is never
        // "intersecting", so the old code never started loading the image.
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    observer.unobserve(entry.target);
                    loadPlantImage(entry.target);
                }
            });
        }, { rootMargin: "200px" });

        document.querySelectorAll(".plant-card").forEach(card => {
            if (card.querySelector(".plant-image[data-wiki-title]")) {
                imageObserver.observe(card);
            }
        });
    </script>

    </body>
    </html>

<?php
$stmt->close();
$conn->close();
?>