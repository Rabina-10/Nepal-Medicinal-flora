<?php
require_once "config/db.php";

$sql = "SELECT
            c.conservation_id,
            c.plant_id,
            c.conservation_status,
            c.threats,
            c.conservation_information,
            p.common_name,
            p.scientific_name
        FROM Conservation c
        LEFT JOIN Plants p ON c.plant_id = p.plant_id
        ORDER BY c.conservation_id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Database error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Conservation | Nepal Medicinal Flora</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f8f3;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #2f6b45;
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
            background: rgba(255, 255, 255, 0.12);
        }

        .page-title {
            color: #2f6b45;
            font-weight: bold;
        }

        .conservation-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            height: 100%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .conservation-image {
            display: none;
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .conservation-image.is-visible {
            display: block;
        }

        .image-placeholder {
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef3eb;
            color: #66736a;
            text-align: center;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .image-placeholder.is-hidden {
            display: none !important;
        }

        .image-credit {
            display: none;
            font-size: 12px;
            margin-top: -8px;
            margin-bottom: 15px;
        }

        .image-credit.is-visible {
            display: block;
        }

        .status {
            display: inline-block;
            background: #e5f2e8;
            color: #2f6b45;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
        }

        .info-title {
            color: #2f6b45;
            font-weight: bold;
        }

        footer {
            background: #2f6b45;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 50px;
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

<!-- Navigation: kept as in your original file -->
<?php
$activePage = 'conservation';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Main Content -->
<div class="container py-5">

    <div class="text-center mb-5">
        <h1 class="page-title">Plant Conservation</h1>

        <p class="text-muted">
            Explore conservation status, threats, and protection
            information for Nepal's medicinal plants.
        </p>
    </div>

    <div class="row g-4">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($row = $result->fetch_assoc()): ?>

                <?php
                $commonName = $row["common_name"] ?? "Unknown plant";
                $scientificName = $row["scientific_name"] ?? "";
                $wikiTitle = trim($scientificName !== "" ? $scientificName : $commonName);
                // "Bambusa spp." -> "Bambusa" so Wikipedia finds the genus page
                $wikiTitle = trim(preg_replace('/\s+spp?\.?$/i', '', $wikiTitle));
                ?>

                <div class="col-md-6 col-lg-4">
                    <div class="conservation-card">

                        <img
                                class="conservation-image"
                                data-wiki-title="<?php
                                echo htmlspecialchars($wikiTitle, ENT_QUOTES, 'UTF-8');
                                ?>"
                                alt="Photo of <?php
                                echo htmlspecialchars($commonName, ENT_QUOTES, 'UTF-8');
                                ?>"
                                loading="lazy"
                        >

                        <div class="image-placeholder">
                            Loading plant image…
                        </div>

                        <a
                                class="image-credit"
                                target="_blank"
                                rel="noopener noreferrer"
                        >
                            Image source: Wikipedia
                        </a>

                        <span class="status">
                            <?php
                            echo htmlspecialchars(
                                    $row["conservation_status"] ?? "Not specified",
                                    ENT_QUOTES,
                                    'UTF-8'
                            );
                            ?>
                        </span>

                        <h4 class="info-title mt-3">
                            <?php
                            echo htmlspecialchars($commonName, ENT_QUOTES, 'UTF-8');
                            ?>
                        </h4>

                        <p class="text-muted">
                            <i>
                                <?php
                                echo htmlspecialchars(
                                        $scientificName ?: "Scientific name unavailable",
                                        ENT_QUOTES,
                                        'UTF-8'
                                );
                                ?>
                            </i>
                        </p>

                        <hr>

                        <h6 class="info-title">Threats</h6>

                        <p>
                            <?php
                            echo nl2br(htmlspecialchars(
                                    $row["threats"] ?? "No threats information available.",
                                    ENT_QUOTES,
                                    'UTF-8'
                            ));
                            ?>
                        </p>

                        <h6 class="info-title">Conservation Information</h6>

                        <p>
                            <?php
                            echo nl2br(htmlspecialchars(
                                    $row["conservation_information"] ?? "No conservation information available.",
                                    ENT_QUOTES,
                                    'UTF-8'
                            ));
                            ?>
                        </p>

                    </div>
                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-12 text-center">
                <div class="alert alert-info">
                    No conservation records are available yet.
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<footer>
    <h5>🌿 Nepal Medicinal Flora Conservation System</h5>

    <p class="mb-0">
        Supporting Medicinal Plant Conservation and Biodiversity Monitoring
    </p>
</footer>

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

        // 2) fallback: search Wikipedia for this name
        return await wikiRequest({
            generator: "search",
            gsrsearch: title,
            gsrlimit: "1"
        });
    }

    async function loadConservationImage(card) {
        const img = card.querySelector(".conservation-image");
        const placeholder = card.querySelector(".image-placeholder");
        const credit = card.querySelector(".image-credit");

        try {
            const page = await findWikiImage(img.dataset.wikiTitle);

            if (page) {
                img.src = page.thumbnail.source;
                img.classList.add("is-visible");
                placeholder.classList.add("is-hidden");

                credit.href = "https://en.wikipedia.org/wiki/" +
                    encodeURIComponent(page.title.replaceAll(" ", "_"));
                credit.classList.add("is-visible");
            } else {
                placeholder.textContent = "No Wikipedia image found for this plant.";
            }
        } catch (error) {
            placeholder.textContent = "Plant image could not be loaded.";
        }
    }

    // IMPORTANT: observe the visible CARD, not the hidden <img>.
    // An image with display:none is never "intersecting", so the old
    // code never started loading it.
    const conservationImageObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    observer.unobserve(entry.target);
                    loadConservationImage(entry.target);
                }
            });
        },
        { rootMargin: "200px" }
    );

    document.querySelectorAll(".conservation-card").forEach(card => {
        if (card.querySelector(".conservation-image[data-wiki-title]")) {
            conservationImageObserver.observe(card);
        }
    });
</script>

</body>
</html>