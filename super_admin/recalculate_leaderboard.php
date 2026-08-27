<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// Clear existing leaderboard
$conn->query("TRUNCATE TABLE leaderboard");

// Recalculate from completed fixtures
$sql = "INSERT INTO leaderboard (team_id, event_id, sport_id, wins, losses, draws, matches_played)
        SELECT 
            COALESCE(f.team_one_id, f.team_two_id) AS team_id,
            f.event_id,
            f.sport_id,
            SUM(CASE 
                WHEN f.winner_team_id IS NOT NULL 
                AND f.winner_team_id = COALESCE(f.team_one_id, f.team_two_id) THEN 1 ELSE 0 END) AS wins,
            SUM(CASE 
                WHEN f.winner_team_id IS NOT NULL 
                AND f.winner_team_id != COALESCE(f.team_one_id, f.team_two_id) THEN 1 ELSE 0 END) AS losses,
            SUM(CASE WHEN f.result = 'draw' THEN 1 ELSE 0 END) AS draws,
            COUNT(*) AS matches_played
        FROM fixtures f
        WHERE f.status = 'completed'
        AND (f.team_one_id IS NOT NULL OR f.team_two_id IS NOT NULL)
        AND f.winner_team_id IS NOT NULL
        GROUP BY COALESCE(f.team_one_id, f.team_two_id), f.event_id, f.sport_id";

$conn->query($sql);

// Also include draws from fixtures where result is 'draw'
$drawSQL = "INSERT INTO leaderboard (team_id, event_id, sport_id, wins, losses, draws, matches_played)
            SELECT 
                COALESCE(f.team_one_id, f.team_two_id) AS team_id,
                f.event_id,
                f.sport_id,
                0 AS wins,
                0 AS losses,
                COUNT(*) AS draws,
                COUNT(*) AS matches_played
            FROM fixtures f
            WHERE f.status = 'completed'
            AND f.result = 'draw'
            AND (f.team_one_id IS NOT NULL OR f.team_two_id IS NOT NULL)
            GROUP BY COALESCE(f.team_one_id, f.team_two_id), f.event_id, f.sport_id
            ON DUPLICATE KEY UPDATE
                draws = draws + VALUES(draws),
                matches_played = matches_played + VALUES(matches_played)";
$conn->query($drawSQL);

header("Location: leaderboard.php?recalculated=1");
exit();
?>