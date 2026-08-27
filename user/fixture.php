<?php

session_start();

require_once "../db_connect.php";


/* ========================================
   CHECK LOGIN
======================================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php?error=login_required");
    exit();

}


/* ========================================
   CHECK USER ROLE
======================================== */

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {

    header("Location: ../login.php?error=access_denied");
    exit();

}


/* ========================================
   GET DARK MODE SETTING
======================================== */

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

$dark_mode_class = ($dark_mode == 1) ? 'dark-mode' : '';


/* ========================================
   GET ALL FIXTURES
======================================== */

$fixture_sql = "
    SELECT
        f.fixture_id,
        f.event_name,
        f.team_one,
        f.team_two,
        f.fixture_date,
        f.fixture_time,
        f.venue,
        f.status,
        s.sport_name,
        s.category
    FROM fixtures f

    INNER JOIN sports s
        ON f.sport_id = s.sport_id

    ORDER BY
        f.fixture_date ASC,
        f.fixture_time ASC
";


$fixture_result = $conn->query($fixture_sql);


if (!$fixture_result) {

    die("Fixture Query Error: " . $conn->error);

}

// Don't close connection here - sidebar needs it
// $conn->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Fixtures | NexArena</title>

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/fixture.css">

</head>


<body class="<?php echo $dark_mode_class; ?>">

<?php include "sidebar.php"; ?>


<!-- ========================================
     MAIN CONTENT
======================================== -->

<div class="dashboard-main">


    <main class="fixtures-page">


        <!-- ========================================
             HEADER
        ========================================= -->

        <section class="fixtures-header">

            <div>

                <span class="page-label">
                    NEXARENA FIXTURES
                </span>

                <h1>
                    All Fixtures
                </h1>

                <p>
                    View upcoming, live and completed
                    matches across all sports.
                </p>

            </div>

        </section>


        <!-- ========================================
             FIXTURE SECTION
        ======================================== -->

        <section class="all-fixtures-section">


            <div class="section-heading">

                <div>

                    <span>
                        MATCH SCHEDULE
                    </span>

                    <h2>
                        Fixtures
                    </h2>

                </div>

            </div>


            <?php if ($fixture_result && $fixture_result->num_rows > 0): ?>


                <div class="all-fixtures-grid">


                    <?php while ($fixture = $fixture_result->fetch_assoc()): ?>


                        <article class="fixture-card">


                            <!-- TOP -->

                            <div class="fixture-top">


                                <span class="fixture-sport">

                                    <?php
                                    echo htmlspecialchars(
                                        $fixture['sport_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </span>


                                <span class="fixture-status">

                                    <?php

                                    echo htmlspecialchars(
                                        ucfirst(
                                            $fixture['status']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </span>


                            </div>


                            <!-- EVENT NAME -->

                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $fixture['event_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </h3>


                            <!-- TEAMS -->

                            <div class="fixture-teams">


                                <div class="fixture-team">

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $fixture['team_one'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </strong>

                                </div>


                                <div class="fixture-vs">
                                    VS
                                </div>


                                <div class="fixture-team">

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $fixture['team_two'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </strong>

                                </div>


                            </div>


                            <!-- DETAILS -->

                            <div class="fixture-details">


                                <div>

                                    <span>
                                        DATE
                                    </span>

                                    <strong>

                                        <?php

                                        if (
                                            !empty(
                                                $fixture['fixture_date']
                                            )
                                        ) {

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $fixture['fixture_date']
                                                )
                                            );

                                        } else {

                                            echo "TBA";

                                        }

                                        ?>

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        TIME
                                    </span>

                                    <strong>

                                        <?php

                                        if (
                                            !empty(
                                                $fixture['fixture_time']
                                            )
                                        ) {

                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $fixture['fixture_time']
                                                )
                                            );

                                        } else {

                                            echo "TBA";

                                        }

                                        ?>

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        VENUE
                                    </span>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $fixture['venue'] ?? 'TBA',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );

                                        ?>

                                    </strong>

                                </div>


                            </div>


                        </article>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <!-- ========================================
                     EMPTY STATE
                ========================================= -->

                <div class="no-fixtures">

                    <div class="no-fixtures-icon">
                        🏆
                    </div>

                    <h2>
                        No Fixtures Available
                    </h2>

                    <p>
                        Fixtures will appear here when
                        they are added by the administrator.
                    </p>

                </div>


            <?php endif; ?>


        </section>


    </main>


</div>

<!-- Theme JavaScript - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>

</html>