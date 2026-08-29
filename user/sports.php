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
           MODERN SPORTS CARD DESIGN
        ========================================================= */
        
        /* Card Container */
        .sport-card {
            cursor: pointer !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative;
            background: var(--card-bg, #ffffff);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--border-color, #e2e8f0);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 280px;
        }

        .sport-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f97316, #fb923c, #f97316);
            background-size: 200% 100%;
            opacity: 0;
            transition: opacity 0.4s ease;
            border-radius: 20px 20px 0 0;
        }

        .sport-card:hover::before {
            opacity: 1;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .sport-card:hover {
            transform: translateY(-8px) !important;
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.15) !important;
            border-color: #f97316 !important;
        }

        /* Card Top - Icon & Status */
        .sport-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        /* Sport Icon - Enhanced */
        .sport-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            border-radius: 16px;
            font-size: 30px;
            color: #f97316;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
            position: relative;
        }

        .sport-icon i {
            font-size: 30px;
            color: #f97316;
            line-height: 1;
            transition: all 0.4s ease;
        }

        .sport-card:hover .sport-icon {
            transform: scale(1.08) rotate(-5deg);
            background: linear-gradient(135deg, #fed7aa, #fdba74);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.25);
        }

        .sport-card:hover .sport-icon i {
            color: #ea580c;
            transform: scale(1.1);
        }

        /* Status Badge */
        .sport-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: rgba(34, 197, 94, 0.12);
            color: #22c55e;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border: 1px solid rgba(34, 197, 94, 0.15);
        }

        .sport-status i {
            font-size: 6px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Sport Name */
        .sport-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary, #1f2937);
            margin: 0 0 4px 0;
            line-height: 1.3;
        }

        [data-theme="dark"] .sport-name {
            color: #f1f5f9;
        }

        /* Category */
        .sport-category {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted, #64748b);
            margin: 4px 0 12px 0;
            padding: 4px 12px;
            background: var(--bg-muted, #f1f5f9);
            border-radius: 12px;
            width: fit-content;
        }

        [data-theme="dark"] .sport-category {
            background: rgba(255, 255, 255, 0.05);
        }

        .sport-category i {
            color: #f97316;
            font-size: 11px;
        }

        .sport-category strong {
            color: var(--text-primary, #1f2937);
            font-weight: 600;
        }

        [data-theme="dark"] .sport-category strong {
            color: #e2e8f0;
        }

        /* Description */
        .sport-description {
            color: var(--text-muted, #64748b);
            font-size: 14px;
            line-height: 1.7;
            margin: 4px 0 16px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        /* Card Footer */
        .sport-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border-color, #e2e8f0);
        }

        [data-theme="dark"] .sport-card-footer {
            border-top-color: rgba(255, 255, 255, 0.06);
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted, #64748b);
            font-size: 13px;
        }

        .footer-left i {
            color: #f97316;
        }

        /* Event Count Badge */
        .event-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            background: rgba(249, 115, 22, 0.1);
            color: #f97316;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .sport-card:hover .event-count-badge {
            background: rgba(249, 115, 22, 0.2);
            transform: scale(1.05);
        }

        .event-count-badge i {
            font-size: 12px;
        }

        /* No Events Badge */
        .no-events-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            background: rgba(100, 116, 139, 0.08);
            color: #94a3b8;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        [data-theme="dark"] .no-events-badge {
            background: rgba(255, 255, 255, 0.05);
            color: #64748b;
        }

        /* Arrow Icon */
        .sport-arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: rgba(249, 115, 22, 0.08);
            border-radius: 50%;
            color: #f97316;
            font-size: 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sport-card:hover .sport-arrow {
            background: #f97316;
            color: white;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }

        .sport-arrow i {
            transition: transform 0.3s ease;
        }

        .sport-card:hover .sport-arrow i {
            transform: translateX(3px);
        }

        /* Dark Mode Overrides */
        [data-theme="dark"] .sport-card {
            background: #1e293b;
            border-color: rgba(255, 255, 255, 0.06);
        }

        [data-theme="dark"] .sport-card:hover {
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.2) !important;
            border-color: #f97316 !important;
        }

        [data-theme="dark"] .sport-icon {
            background: linear-gradient(135deg, #451a03, #78350f);
        }

        [data-theme="dark"] .sport-icon i {
            color: #fb923c;
        }

        [data-theme="dark"] .sport-card:hover .sport-icon {
            background: linear-gradient(135deg, #78350f, #92400e);
        }

        [data-theme="dark"] .sport-card:hover .sport-icon i {
            color: #f97316;
        }

        [data-theme="dark"] .sport-status {
            background: rgba(34, 197, 94, 0.15);
        }

        [data-theme="dark"] .sport-arrow {
            background: rgba(249, 115, 22, 0.15);
        }

        [data-theme="dark"] .sport-card:hover .sport-arrow {
            background: #f97316;
            color: white;
        }

        /* Click Feedback */
        .sport-card:active {
            transform: scale(0.97) !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sport-card {
                padding: 20px;
                min-height: 240px;
            }

            .sport-icon {
                width: 52px;
                height: 52px;
                font-size: 24px;
            }

            .sport-icon i {
                font-size: 24px;
            }

            .sport-name {
                font-size: 18px;
            }

            .sport-description {
                font-size: 13px;
                -webkit-line-clamp: 2;
            }

            .sport-arrow {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .sport-card {
                padding: 16px;
                min-height: 200px;
                border-radius: 16px;
            }

            .sport-icon {
                width: 44px;
                height: 44px;
                font-size: 20px;
                border-radius: 12px;
            }

            .sport-icon i {
                font-size: 20px;
            }

            .sport-name {
                font-size: 16px;
            }

            .sport-category {
                font-size: 11px;
                padding: 3px 10px;
            }

            .sport-description {
                font-size: 12px;
                -webkit-line-clamp: 2;
            }

            .sport-status {
                font-size: 9px;
                padding: 4px 10px;
            }

            .event-count-badge,
            .no-events-badge {
                font-size: 10px;
                padding: 3px 10px;
            }

            .sport-arrow {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }

            .footer-left {
                font-size: 11px;
            }
        }

        /* Grid Layout */
        .sports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        @media (max-width: 768px) {
            .sports-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 16px;
            }
        }

        @media (max-width: 480px) {
            .sports-grid {
                grid-template-columns: 1fr;
                gap: 12px;
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
                    
                    // Get icon from database
                    $iconClass = !empty($sport['icon']) ? $sport['icon'] : 'fa-circle';
                    $iconClass = trim($iconClass);
                    
                    // Ensure proper format
                    if (!empty($iconClass) && strpos($iconClass, 'fa-') !== 0) {
                        $iconClass = 'fa-' . $iconClass;
                    }

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
                        role="button"
                        tabindex="0"
                        aria-label="View events for <?php echo htmlspecialchars($sportName, ENT_QUOTES, 'UTF-8'); ?>"
                    >


                        <!-- CARD TOP - Icon & Status -->

                        <div class="sport-card-top">


                            <div class="sport-icon">
                                <i class="fas <?php echo htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8'); ?>"></i>
                            </div>


                            <span class="sport-status">

                                <i class="fas fa-circle"></i> Active

                            </span>


                        </div>


                        <!-- SPORT NAME -->

                        <h3 class="sport-name">
                            <?php echo htmlspecialchars(
                                $sportName,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </h3>


                        <!-- CATEGORY -->

                        <div class="sport-category">

                            <i class="fas fa-tag"></i>

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
                                        <i class="fas fa-calendar-check"></i> 
                                        <?php echo $eventCount; ?> Event<?php echo $eventCount > 1 ? 's' : ''; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-events-badge">
                                        <i class="fas fa-calendar-times"></i> No Events
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
                card.style.animation = "fadeIn 0.5s ease forwards";

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
        const card = event.currentTarget;
        const id = card.dataset.sportId;
        if (id && id != 0) {
            window.location.href = 'events.php?sport_id=' + id + '&sport=' + encodeURIComponent(sportName);
        } else {
            window.location.href = 'events.php?sport=' + encodeURIComponent(sportName);
        }
    } else {
        window.location.href = 'events.php?sport_id=' + sportId + '&sport=' + encodeURIComponent(sportName);
    }
}

// Keyboard support for accessibility
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        const target = e.target.closest('.sport-card');
        if (target) {
            e.preventDefault();
            const sportId = target.dataset.sportId;
            const sportName = target.querySelector('.sport-name')?.textContent || '';
            viewSportEvents(sportId, sportName);
        }
    }
});

</script>

<!-- THEME JAVASCRIPT - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>

</html>