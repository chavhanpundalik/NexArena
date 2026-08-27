<?php
session_start();

require_once "../db_connect.php";

/* ===============================
   FETCH SPORTS
================================ */

$sql = "SELECT sport_id, sport_name, category, description, status, created_at
        FROM sports
        ORDER BY sport_id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("SPORT QUERY ERROR: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sports Management | NexArena</title>

    <link rel="stylesheet" href="assets/sports.css">
</head>

<body>

    <!-- ===============================
         SIDEBAR
    ================================ -->

    <?php include "sidebar.php"; ?>


    <!-- ===============================
         MAIN CONTENT
    ================================ -->

    <main class="main-content">

        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>
                <span class="page-label">ADMIN MANAGEMENT</span>

                <h1>Sports</h1>

                <p>
                    Manage all sports available on NexArena.
                </p>
            </div>

            <a href="add_sport.php" class="add-sport-btn">
                + Add Sport
            </a>

        </div>


        <!-- ===============================
             FILTER BAR
        ================================ -->

        <div class="filter-bar">

            <div class="search-box">

                <span>⌕</span>

                <input
                    type="text"
                    id="sportSearch"
                    placeholder="Search sports..."
                >

            </div>


            <select id="categoryFilter">

                <option value="all">All Categories</option>
                <option value="Team Sport">Team Sport</option>
                <option value="Individual Sport">Individual Sport</option>
                <option value="Indoor">Indoor</option>
                <option value="Outdoor">Outdoor</option>
                <option value="Esports">Esports</option>

            </select>


            <select id="statusFilter">

                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>

            </select>

        </div>


        <!-- ===============================
             SPORTS GRID
        ================================ -->

        <div class="sports-grid" id="sportsGrid">

            <?php if (mysqli_num_rows($result) > 0): ?>

                <?php while ($sport = mysqli_fetch_assoc($result)): ?>

                    <div
                        class="sport-card"
                        data-name="<?= strtolower(htmlspecialchars($sport['sport_name'])) ?>"
                        data-category="<?= strtolower(htmlspecialchars($sport['category'])) ?>"
                        data-status="<?= strtolower($sport['status']) ?>"
                    >

                        <!-- CARD TOP -->

                        <div class="sport-card-top">

                            <div class="sport-icon">
                                <?= strtoupper(substr($sport['sport_name'], 0, 1)) ?>
                            </div>

                            <span
                                class="status-badge <?= strtolower($sport['status']) ?>"
                            >
                                <?= ucfirst($sport['status']) ?>
                            </span>

                        </div>


                        <!-- SPORT NAME -->

                        <h2>
                            <?= htmlspecialchars($sport['sport_name']) ?>
                        </h2>


                        <!-- CATEGORY -->

                        <div class="sport-category">

                            <?= htmlspecialchars($sport['category']) ?>

                        </div>


                        <!-- DESCRIPTION -->

                        <p class="sport-description">

                            <?php

                            if (!empty($sport['description'])) {

                                echo htmlspecialchars($sport['description']);

                            } else {

                                echo "No description available.";

                            }

                            ?>

                        </p>


                        <!-- DATE -->

                        <div class="sport-date">

                            Added:
                            <?= date("d M Y", strtotime($sport['created_at'])) ?>

                        </div>


                        <!-- ACTIONS -->

                        <div class="sport-actions">

                            <a
                                href="edit_sport.php?id=<?= $sport['sport_id'] ?>"
                                class="edit-btn"
                            >
                                Edit
                            </a>

                            <a
                                href="delete_sport.php?id=<?= $sport['sport_id'] ?>"
                                class="delete-btn"
                                onclick="return confirm('Are you sure you want to delete this sport?');"
                            >
                                Delete
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-state">

                    <div class="empty-icon">⚽</div>

                    <h2>No Sports Found</h2>

                    <p>
                        Add your first sport to start managing NexArena.
                    </p>

                    <a href="add_sport.php">
                        + Add Sport
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </main>


    <!-- ===============================
         SEARCH + FILTER
    ================================ -->

    <script>

        const searchInput =
            document.getElementById("sportSearch");

        const categoryFilter =
            document.getElementById("categoryFilter");

        const statusFilter =
            document.getElementById("statusFilter");

        const cards =
            document.querySelectorAll(".sport-card");


        function filterSports() {

            const search =
                searchInput.value.toLowerCase().trim();

            const category =
                categoryFilter.value.toLowerCase();

            const status =
                statusFilter.value.toLowerCase();


            cards.forEach(card => {

                const name =
                    card.dataset.name;

                const cardCategory =
                    card.dataset.category;

                const cardStatus =
                    card.dataset.status;


                const matchesSearch =
                    name.includes(search);

                const matchesCategory =
                    category === "all" ||
                    cardCategory === category;

                const matchesStatus =
                    status === "all" ||
                    cardStatus === status;


                if (
                    matchesSearch &&
                    matchesCategory &&
                    matchesStatus
                ) {

                    card.style.display = "";

                } else {

                    card.style.display = "none";

                }

            });

        }


        searchInput.addEventListener(
            "input",
            filterSports
        );

        categoryFilter.addEventListener(
            "change",
            filterSports
        );

        statusFilter.addEventListener(
            "change",
            filterSports
        );

    </script>

</body>
</html>