<?php

session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

// Get dark mode setting
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

function clean($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| LEADERBOARD DATA
|--------------------------------------------------------------------------
| Highest points appear first.
*/

$sql = "
    SELECT
        l.leaderboard_id,
        l.user_id,
        l.event_id,
        l.points,
        l.wins,
        l.losses,
        l.draws,
        u.full_name,
        u.username,
        e.event_name
    FROM leaderboard l

    INNER JOIN users u
        ON l.user_id = u.user_id

    INNER JOIN events e
        ON l.event_id = e.event_id

    ORDER BY l.points DESC, l.wins DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Database Error: " . $conn->error);
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

    <title>Leaderboard | NexArena</title>

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/leaderboard.css">

</head>

<body class="<?php echo $dark_mode_class; ?>">

<?php include "sidebar.php"; ?>
<main class="leaderboard-page">


    <!-- HEADER -->

    <section class="page-header">

        <div>

            <span class="page-label">
                NEXARENA
            </span>

            <h1>
                Leaderboard
            </h1>

            <p>
                Track player performance and rankings.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="back-btn"
        >
            ← Dashboard
        </a>

    </section>


    <!-- TOP THREE -->

    <?php

    $players = [];

    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }

    ?>

    <?php if (count($players) > 0): ?>

        <section class="top-players">

            <?php foreach (array_slice($players, 0, 3) as $index => $player): ?>

                <?php
                    $rank = $index + 1;

                    if ($rank === 1) {
                        $rank_class = "first";
                        $medal = "🥇";
                    } elseif ($rank === 2) {
                        $rank_class = "second";
                        $medal = "🥈";
                    } else {
                        $rank_class = "third";
                        $medal = "🥉";
                    }
                ?>

                <div class="top-card <?php echo $rank_class; ?>">

                    <div class="rank-medal">
                        <?php echo $medal; ?>
                    </div>

                    <div class="player-name">

                        <h2>
                            <?php echo clean($player['full_name']); ?>
                        </h2>

                        <span>
                            @<?php echo clean($player['username']); ?>
                        </span>

                    </div>

                    <div class="top-points">

                        <strong>
                            <?php echo (int)$player['points']; ?>
                        </strong>

                        <small>
                            POINTS
                        </small>

                    </div>

                </div>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>


    <!-- FULL LEADERBOARD -->

    <section class="leaderboard-section">

        <div class="section-heading">

            <div>

                <span>
                    RANKINGS
                </span>

                <h2>
                    Player Standings
                </h2>

            </div>

        </div>


        <?php if (count($players) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Rank
                            </th>

                            <th>
                                Player
                            </th>

                            <th>
                                Event
                            </th>

                            <th>
                                Points
                            </th>

                            <th>
                                Wins
                            </th>

                            <th>
                                Losses
                            </th>

                            <th>
                                Draws
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($players as $index => $player): ?>

                            <?php
                                $rank = $index + 1;

                                $current_user =
                                    ((int)$player['user_id'] ===
                                    (int)$_SESSION['user_id']);
                            ?>

                            <tr
                                class="<?php
                                    echo $current_user
                                        ? 'current-user'
                                        : '';
                                ?>"
                            >

                                <td>

                                    <span class="rank-number">

                                        <?php if ($rank <= 3): ?>

                                            <?php
                                            echo $rank === 1
                                                ? "🥇"
                                                : ($rank === 2
                                                    ? "🥈"
                                                    : "🥉");
                                            ?>

                                        <?php else: ?>

                                            <?php echo $rank; ?>

                                        <?php endif; ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="player-cell">

                                        <div class="avatar">

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
                                                echo clean(
                                                    $player['full_name']
                                                );
                                                ?>
                                            </strong>

                                            <small>
                                                @<?php
                                                echo clean(
                                                    $player['username']
                                                );
                                                ?>
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <td>
                                    <?php
                                    echo clean(
                                        $player['event_name']
                                    );
                                    ?>
                                </td>


                                <td>

                                    <strong class="points">
                                        <?php
                                        echo (int)$player['points'];
                                        ?>
                                    </strong>

                                </td>


                                <td class="win">
                                    <?php echo (int)$player['wins']; ?>
                                </td>


                                <td>
                                    <?php echo (int)$player['losses']; ?>
                                </td>


                                <td>
                                    <?php echo (int)$player['draws']; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">
                    🏆
                </div>

                <h2>
                    No Rankings Yet
                </h2>

                <p>
                    Leaderboard results will appear here
                    when player scores are added.
                </p>

                <a
                    href="events.php"
                    class="back-btn"
                >
                    Explore Events
                </a>

            </div>

        <?php endif; ?>

    </section>

</main>

<!-- Theme JavaScript - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>
</html>