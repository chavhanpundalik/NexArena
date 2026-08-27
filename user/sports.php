<?php

// ========================================
// SESSION
// ========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ========================================
// LOGIN CHECK
// ========================================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}


// ========================================
// DATABASE
// ========================================

require_once "../db_connect.php";


// ========================================
// GET ACTIVE SPORTS WITH EVENT COUNT
// ========================================

$sql = "
    SELECT
        s.sport_id,
        s.sport_name,
        s.category,
        s.description,
        s.status,
        s.icon,
        COUNT(e.event_id) AS event_count
    FROM sports s
    LEFT JOIN events e ON s.sport_id = e.sport_id 
        AND e.status = 'active' 
        AND e.event_date >= CURDATE()
    WHERE s.status = 'active'
    GROUP BY s.sport_id
    ORDER BY s.sport_name ASC
";

$result = $conn->query($sql);


// ========================================
// STORE SPORTS
// ========================================

$sports = [];
$categories = [];

if ($result) {

    while ($sport = $result->fetch_assoc()) {

        $sports[] = $sport;

        if (!empty($sport['category'])) {
            $categories[] = $sport['category'];
        }

    }

}


// ========================================
// REMOVE DUPLICATE CATEGORIES
// ========================================

$categories = array_unique($categories);

sort($categories);


// ========================================
// GET DARK MODE SETTING
// ========================================

$user_id = (int) $_SESSION['user_id'];

$dark_mode = 0;
$settings_sql = "SELECT dark_mode FROM user_settings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->bind_param("i", $user_id);
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();

if ($settings_result->num_rows > 0) {
    $settings_data = $settings_result->fetch_assoc();
    $dark_mode = $settings_data['dark_mode'] ?? 0;
}
$settings_stmt->close();

// Don't close connection here - sidebar needs it
// $conn->close();

?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $dark_mode ? 'dark' : 'light' ?>">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sports | NexArena</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Theme CSS (must be loaded first) -->
    <link rel="stylesheet" href="assets/theme.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/sports.css">

    <style>
        /* =========================================================
           ADDITIONAL STYLES FOR CLICKABLE SPORTS CARDS
        ========================================================= */
        
        .sport-card {
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            position: relative;
        }

        .sport-card:hover {
            transform: translateY(-6px) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12) !important;
            border-color: #f97316 !important;
        }

        .sport-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #f97316;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 16px 16px 0 0;
        }

        .sport-card:hover::after {
            opacity: 1;
        }

        .sport-card .sport-arrow {
            transition: transform 0.3s ease;
            display: inline-block;
        }

        .sport-card:hover .sport-arrow {
            transform: translateX(6px);
        }

        .sport-card .event-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            background: rgba(249, 115, 22, 0.1);
            color: #f97316;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .sport-card .event-count-badge i {
            font-size: 12px;
        }

        .sport-card .sport-icon-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Dark mode overrides */
        [data-theme="dark"] .sport-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4) !important;
            border-color: #f97316 !important;
        }

        [data-theme="dark"] .sport-card .event-count-badge {
            background: rgba(249, 115, 22, 0.15);
        }

        /* Click feedback */
        .sport-card:active {
            transform: scale(0.97) !important;
        }

        /* View Events button */
        .view-events-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: #f97316;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-top: 12px;
            width: 100%;
            justify-content: center;
        }

        .view-events-btn:hover {
            background: #ea580c;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
            color: #ffffff;
        }

        .view-events-btn i {
            font-size: 14px;
        }

        /* Card footer with event count */
        .sport-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color, #e2e8f0);
        }

        [data-theme="dark"] .sport-card-footer {
            border-top-color: rgba(255, 255, 255, 0.06);
        }

        .sport-card-footer .footer-left {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted, #64748b);
            font-size: 13px;
        }

        .sport-card-footer .footer-left i {
            color: #f97316;
        }

        .sport-card-footer .sport-arrow {
            color: #f97316;
            font-size: 20px;
            font-weight: 300;
        }

        /* No events badge */
        .no-events-badge {
            padding: 4px 12px;
            background: rgba(100, 116, 139, 0.1);
            color: #64748b;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        [data-theme="dark"] .no-events-badge {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sport-card {
                padding: 18px;
            }
            
            .view-events-btn {
                font-size: 12px;
                padding: 6px 14px;
            }
        }
    </style>

</head>


<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

<?php include "sidebar.php"; ?>


<!-- ========================================
     MAIN CONTENT
======================================== -->

<main class="sports-page">


    <!-- ========================================
         HEADER
    ======================================== -->

    <section class="sports-header">

        <div>

            <span class="page-label">
                <i class="fas fa-running"></i> NEXARENA SPORTS
            </span>

            <h1>
                <i class="fas fa-trophy" style="color:#f97316;"></i> Explore Sports
            </h1>

            <p>
                Discover your favorite sports and explore
                everything available on NexArena.
            </p>

        </div>

    </section>


    <!-- ========================================
         SPORTS INTRO
    ======================================== -->

    <section class="sports-intro">

        <div class="intro-icon">
            🏆
        </div>

        <div>

            <h2>
                Find Your Game
            </h2>

            <p>
                Search and filter sports to quickly find
                the game you are interested in. Click any sport to view events.
            </p>

        </div>

    </section>


    <!-- ========================================
         SEARCH & FILTER
    ======================================== -->

    <section class="sports-controls">


        <!-- SEARCH -->

        <div class="search-box">

            <span class="search-icon">
                <i class="fas fa-search"></i>
            </span>

            <input
                type="text"
                id="sportSearch"
                placeholder="Search sports..."
                autocomplete="off"
            >

        </div>


        <!-- CATEGORY FILTER -->

        <div class="category-box">

            <select id="categoryFilter">

                <option value="all">
                    All Categories
                </option>


                <?php foreach ($categories as $category): ?>

                    <option
                        value="<?php echo htmlspecialchars(
                            strtolower(trim($category)),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                    >

                        <?php echo htmlspecialchars(
                            $category,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


    </section>


    <!-- ========================================
         SPORTS SECTION
    ======================================== -->

    <section class="sports-section">


        <div class="section-heading">

            <div>

                <span>
                    AVAILABLE SPORTS
                </span>

                <h2>
                    Choose Your Sport
                </h2>

            </div>


            <div class="sport-count">

                <span id="sportCount">
                    <?php echo count($sports); ?>
                </span>

                Sports

            </div>

        </div>


        <!-- ========================================
             SPORTS GRID
        ======================================== -->

        <?php if (count($sports) > 0): ?>


            <div
                class="sports-grid"
                id="sportsGrid"
            >


                <?php foreach ($sports as $sport): ?>


                    <?php

                    $sportId = $sport['sport_id'] ?? 0;
                    $sportName = $sport['sport_name'] ?? 'Unknown Sport';
                    $category = $sport['category'] ?? 'Other';
                    $description = $sport['description'] ?? 'Explore this sport on NexArena.';
                    $eventCount = (int)($sport['event_count'] ?? 0);
                    $icon = $sport['icon'] ?? '🏅';

                    ?>


                    <article
                        class="sport-card"
                        data-name="<?php echo htmlspecialchars(
                            strtolower($sportName),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        data-category="<?php echo htmlspecialchars(
                            strtolower(trim($category)),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        data-sport-id="<?php echo $sportId; ?>"
                        onclick="viewSportEvents(<?php echo $sportId; ?>, '<?php echo htmlspecialchars($sportName, ENT_QUOTES, 'UTF-8'); ?>')"
                        style="cursor:pointer;"
                    >


                        <!-- CARD TOP -->

                        <div class="sport-card-top">


                            <div class="sport-icon">
                                <?php echo $icon; ?>
                            </div>


                            <span class="sport-status">

                                <i class="fas fa-circle" style="color:#22c55e;font-size:8px;"></i> ACTIVE

                            </span>


                        </div>


                        <!-- SPORT NAME -->

                        <h3>
                            <?php echo htmlspecialchars(
                                $sportName,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </h3>


                        <!-- CATEGORY -->

                        <div class="sport-category">

                            <span>
                                <i class="fas fa-tag"></i> CATEGORY
                            </span>

                            <strong>

                                <?php echo htmlspecialchars(
                                    $category,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </strong>

                        </div>


                        <!-- DESCRIPTION -->

                        <p class="sport-description">

                            <?php echo htmlspecialchars(
                                $description,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </p>


                        <!-- CARD FOOTER -->

                        <div class="sport-card-footer">

                            <div class="footer-left">
                                <i class="fas fa-calendar-alt"></i>
                                <?php if ($eventCount > 0): ?>
                                    <span class="event-count-badge">
                                        <i class="fas fa-calendar-check"></i> <?php echo $eventCount; ?> Active Event<?php echo $eventCount > 1 ? 's' : ''; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-events-badge">
                                        <i class="fas fa-calendar-times"></i> No Active Events
                                    </span>
                                <?php endif; ?>
                            </div>

                            <span class="sport-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </span>

                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


            <!-- ========================================
                 NO SEARCH RESULTS
            ======================================== -->

            <div
                class="no-search-results"
                id="noSearchResults"
                style="display: none;"
            >

                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>

                <h2>
                    No Sports Found
                </h2>

                <p>
                    Try another sport name or category.
                </p>

            </div>


        <?php else: ?>


            <!-- ========================================
                 NO SPORTS IN DATABASE
            ======================================== -->

            <div class="empty-sports">

                <div class="empty-icon">
                    🏅
                </div>

                <h2>
                    No Sports Available
                </h2>

                <p>
                    Sports will appear here when they
                    are added by the administrator.
                </p>

            </div>


        <?php endif; ?>


    </section>


</main>


<!-- ========================================
     SEARCH & FILTER JAVASCRIPT
======================================== -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    const searchInput =
        document.getElementById("sportSearch");


    const categoryFilter =
        document.getElementById("categoryFilter");


    const sportCards =
        document.querySelectorAll(".sport-card");


    const noResults =
        document.getElementById("noSearchResults");


    const sportCount =
        document.getElementById("sportCount");


    function filterSports() {


        const searchValue =
            searchInput.value
                .toLowerCase()
                .trim();


        const categoryValue =
            categoryFilter.value
                .toLowerCase()
                .trim();


        let visibleCount = 0;


        sportCards.forEach(function (card) {


            const sportName =
                card.dataset.name || "";


            const sportCategory =
                card.dataset.category || "";


            const matchesSearch =
                sportName.includes(searchValue);


            const matchesCategory =
                categoryValue === "all" ||
                sportCategory === categoryValue;


            if (
                matchesSearch &&
                matchesCategory
            ) {

                card.style.display = "";

                visibleCount++;

            } else {

                card.style.display = "none";

            }

        });


        // Update count

        if (sportCount) {

            sportCount.textContent =
                visibleCount;

        }


        // Show / hide no results message

        if (noResults) {

            if (visibleCount === 0) {

                noResults.style.display = "block";

            } else {

                noResults.style.display = "none";

            }

        }

    }


    // Search

    searchInput.addEventListener(
        "input",
        filterSports
    );


    // Category

    categoryFilter.addEventListener(
        "change",
        filterSports
    );


});


// ========================================
// VIEW SPORT EVENTS FUNCTION
// ========================================

function viewSportEvents(sportId, sportName) {
    if (!sportId || sportId == 0) {
        // If no sport ID, try to find it from the clicked card
        const card = event.currentTarget;
        const id = card.dataset.sportId;
        if (id && id != 0) {
            window.location.href = 'events.php?sport_id=' + id + '&sport=' + encodeURIComponent(sportName);
        } else {
            // Fallback: go to events page with sport name
            window.location.href = 'events.php?sport=' + encodeURIComponent(sportName);
        }
    } else {
        window.location.href = 'events.php?sport_id=' + sportId + '&sport=' + encodeURIComponent(sportName);
    }
}

// Also allow clicking on the card via keyboard (Enter key)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        const target = e.target.closest('.sport-card');
        if (target) {
            e.preventDefault();
            const sportId = target.dataset.sportId;
            const sportName = target.querySelector('h3')?.textContent || '';
            viewSportEvents(sportId, sportName);
        }
    }
});

</script>

<!-- THEME JAVASCRIPT - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>

</html>