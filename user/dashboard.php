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

$user_id = $_SESSION['user_id'];

/* ========================================
   GET DARK MODE SETTING
======================================== */

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
   GET USER INFORMATION
======================================== */

$full_name = $_SESSION['full_name'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$phone = $_SESSION['phone'] ?? '';
$role = $_SESSION['role'] ?? 'user';


/* ========================================
   SAFE DISPLAY
======================================== */

$display_name = htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8');
$display_username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
$display_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$display_phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$display_role = htmlspecialchars(
    ucfirst($role),
    ENT_QUOTES,
    'UTF-8'
);


// ========================================
// GET UPCOMING FIXTURES
// ========================================

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
        s.sport_name
    FROM fixtures f
    INNER JOIN sports s
        ON f.sport_id = s.sport_id
    WHERE f.status = 'upcoming'
    ORDER BY f.fixture_date ASC, f.fixture_time ASC
    LIMIT 3
";

$fixture_result = $conn->query($fixture_sql);

// Don't close connection here - sidebar needs it
// $conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | NexArena</title>
    
    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">
    
    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/dashboard.css">
</head>

<body class="<?php echo $dark_mode_class; ?>">
    <?php if (isset($_GET['login']) && $_GET['login'] === 'success'): ?>
    <div class="success-message" id="successMessage">
        Login successful! Welcome back.
    </div>
<?php endif; ?>

<script>
document.addEventListener("click", function (event) {

    const message = document.getElementById("successMessage");

    if (message && event.target !== message) {
        message.remove();
    }

});
</script>

<?php include "sidebar.php"; ?>


<!-- RIGHT SIDE -->
<div class="dashboard-main">



    <main class="dashboard-container">

        <!-- Welcome -->
        <section class="welcome-section">

            <div class="welcome-text">

                <p class="welcome-small">
                    WELCOME BACK 👋
                </p>

                <h1>
                    Hello, <?php echo $display_name; ?>!
                </h1>

                <p>
                    Welcome to your NexArena dashboard.
                    Explore events, manage registrations,
                    teams and your profile.
                </p>

            </div>

            <div class="welcome-badge">
                <div class="big-icon">🏆</div>
                <span>NexArena Player</span>
            </div>

        </section>
        <!-- ========================================
     LEADERBOARD PREVIEW
======================================== -->

<section class="leaderboard-preview">

    <div class="section-heading">

        <div>
            <span>PERFORMANCE</span>
            <h2>Leaderboard</h2>
        </div>

        <a href="leaderboard.php" class="view-all">
            View Full Leaderboard →
        </a>

    </div>


    <?php

    $leaderboard_sql = "
        SELECT
            l.user_id,
            l.points,
            l.wins,
            l.losses,
            l.draws,
            u.full_name,
            u.username
        FROM leaderboard l

        INNER JOIN users u
            ON l.user_id = u.user_id

        ORDER BY l.points DESC, l.wins DESC

        LIMIT 5
    ";

    $leaderboard_result = $conn->query($leaderboard_sql);

    ?>


    <?php if ($leaderboard_result && $leaderboard_result->num_rows > 0): ?>

        <div class="leaderboard-table-wrapper">

            <table class="dashboard-leaderboard">

                <thead>

                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Points</th>
                        <th>Wins</th>
                        <th>Losses</th>
                    </tr>

                </thead>

                <tbody>

                    <?php

                    $rank = 1;

                    while ($player = $leaderboard_result->fetch_assoc()):

                        $is_current_user =
                            ((int)$player['user_id'] ===
                            (int)$_SESSION['user_id']);

                    ?>

                        <tr class="<?php echo $is_current_user ? 'current-player' : ''; ?>">

                            <td>

                                <span class="dashboard-rank">

                                    <?php if ($rank === 1): ?>

                                        🥇

                                    <?php elseif ($rank === 2): ?>

                                        🥈

                                    <?php elseif ($rank === 3): ?>

                                        🥉

                                    <?php else: ?>

                                        <?php echo $rank; ?>

                                    <?php endif; ?>

                                </span>

                            </td>


                            <td>

                                <div class="dashboard-player">

                                    <div class="dashboard-avatar">

                                        <?php
                                        echo strtoupper(
                                            substr(
                                                $player['full_name'],
                                                0,
                                                1
                                            )
                                        );
                                        ?>

                                    </div>

                                    <div>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $player['full_name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>
                                        </strong>

                                        <small>
                                            @<?php
                                            echo htmlspecialchars(
                                                $player['username'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <td>

                                <strong class="dashboard-points">
                                    <?php echo (int)$player['points']; ?>
                                </strong>

                            </td>


                            <td class="dashboard-wins">
                                <?php echo (int)$player['wins']; ?>
                            </td>


                            <td>
                                <?php echo (int)$player['losses']; ?>
                            </td>

                        </tr>

                    <?php

                        $rank++;

                    endwhile;

                    ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="leaderboard-empty">

            <div class="empty-leaderboard-icon">
                🏆
            </div>

            <h3>
                Leaderboard is Empty
            </h3>

            <p>
                Player rankings will appear here when
                competition results are added.
            </p>

        </div>

    <?php endif; ?>

            </section>


       <!-- ========================================
     UPCOMING FIXTURES
======================================== -->

<section class="fixtures-section">

    <div class="section-heading">

        <div>
            <span>UPCOMING</span>
            <h2>Fixtures</h2>
        </div>

        <a href="fixture.php" class="view-all">
            View All Fixtures →
        </a>

    </div>


    <?php if ($fixture_result && $fixture_result->num_rows > 0): ?>

        <div class="fixtures-grid">

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
                            Upcoming
                        </span>

                    </div>


                    <!-- EVENT -->

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


                    <!-- DATE / TIME / VENUE -->

                    <div class="fixture-details">

                        <div>

                            <span>DATE</span>

                            <strong>

                                <?php
                                echo !empty($fixture['fixture_date'])
                                    ? date(
                                        "d M Y",
                                        strtotime(
                                            $fixture['fixture_date']
                                        )
                                    )
                                    : "TBA";
                                ?>

                            </strong>

                        </div>


                        <div>

                            <span>TIME</span>

                            <strong>

                                <?php
                                echo !empty($fixture['fixture_time'])
                                    ? date(
                                        "h:i A",
                                        strtotime(
                                            $fixture['fixture_time']
                                        )
                                    )
                                    : "TBA";
                                ?>

                            </strong>

                        </div>


                        <div>

                            <span>VENUE</span>

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


                    <!-- BUTTON -->

                    <a
                        href="fixtures.php?id=<?php echo (int)$fixture['fixture_id']; ?>"
                        class="fixture-button"
                    >
                        View Fixture →
                    </a>

                </article>

            <?php endwhile; ?>

        </div>


    <?php else: ?>

        <div class="no-fixtures">

            <div class="no-fixtures-icon">
                🏆
            </div>

            <h3>
                No Upcoming Fixtures
            </h3>

            <p>
                Upcoming matches will appear here.
            </p>

        </div>

    <?php endif; ?>

</section>


 
        <!-- Profile --> 
        <section class="profile-section">

</section>
<br>

        <!-- Dashboard content -->
        <section class="cards-grid">

            <div class="dashboard-card">
                <div class="card-icon orange">🏆</div>

                <h3>Upcoming Events</h3>

                <p>
                    Discover and register for upcoming
                    sports events.
                </p>

                <a href="events.php" class="card-link">
                    View Events →
                </a>
            </div>


            <div class="dashboard-card">
                <div class="card-icon black">📝</div>

                <h3>My Registrations</h3>

                <p>
                    Check the events you have registered for.
                </p>

                <a href="registrations.php" class="card-link">
                    My Registrations →
                </a>
            </div>


            <div class="dashboard-card">
                <div class="card-icon orange">👥</div>

                <h3>My Team</h3>

                <p>
                    View your team and team members.
                </p>

                <a href="teams.php" class="card-link">
                    View Team →
                </a>
            </div>


            <div class="dashboard-card">
                <div class="card-icon black">🔔</div>

                <h3>Notifications</h3>

                <p>
                    Stay updated with NexArena activities.
                </p>

                <a href="notifications.php" class="card-link">
                    View Notifications →
                </a>
            </div>

        </section>


        <!-- Profile -->
        <section class="profile-section">

            <div class="section-heading">

                <div>
                    <span>ACCOUNT</span>
                    <h2>My Information</h2>
                </div>

                <a href="profile.php" class="edit-btn">
                    Edit Profile
                </a>

            </div>


            <div class="profile-grid">

                <div class="profile-item">
                    <span>Full Name</span>
                    <strong>
                        <?php echo $display_name; ?>
                    </strong>
                </div>


                <div class="profile-item">
                    <span>Username</span>
                    <strong>
                        @<?php echo $display_username; ?>
                    </strong>
                </div>


                <div class="profile-item">
                    <span>Email</span>
                    <strong>
                        <?php echo $display_email; ?>
                    </strong>
                </div>


                <div class="profile-item">
                    <span>Phone</span>
                    <strong>
                        <?php echo $display_phone; ?>
                    </strong>
                </div>


                <div class="profile-item">
                    <span>Account Type</span>

                    <strong class="role-badge">
                        <?php echo ucfirst($role); ?>
                    </strong>
                </div>


                <div class="profile-item">
                    <span>User ID</span>

                    <strong>
                        #<?php echo htmlspecialchars($user_id); ?>
                    </strong>
                </div>

            </div>

        </section>

    </main>


    <!-- Footer -->
    <footer>

        <div class="footer-logo">
            <span>Nex</span>Arena
        </div>

        <p>
            © <?php echo date("Y"); ?>
            NexArena. All Rights Reserved.
        </p>

    </footer>

</div>

<!-- Theme JavaScript - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>
</html>