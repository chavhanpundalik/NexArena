<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../db_connect.php";

$user_id = $_SESSION['user_id'];
$team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
$remove_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// Check if user is captain
$check_sql = $conn->prepare("SELECT role FROM team_members WHERE team_id = ? AND user_id = ? AND role = 'captain'");
$check_sql->bind_param("ii", $team_id, $user_id);
$check_sql->execute();
$check_result = $check_sql->get_result();

if ($check_result->num_rows == 0) {
    $_SESSION['error'] = "You don't have permission to remove players.";
    header("Location: teams.php");
    exit();
}

// Can't remove yourself
if ($remove_user_id == $user_id) {
    $_SESSION['error'] = "You cannot remove yourself as captain.";
    header("Location: team_details.php?id=" . $team_id);
    exit();
}

// Remove player
$remove_sql = $conn->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ? AND role != 'captain'");
$remove_sql->bind_param("ii", $team_id, $remove_user_id);

if ($remove_sql->execute() && $remove_sql->affected_rows > 0) {
    $_SESSION['success'] = "Player removed from team.";
} else {
    $_SESSION['error'] = "Failed to remove player.";
}

header("Location: team_details.php?id=" . $team_id);
?>