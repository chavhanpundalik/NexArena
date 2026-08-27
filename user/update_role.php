<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../db_connect.php";

$user_id = $_SESSION['user_id'];
$team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
$player_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$new_role = $_POST['role'] ?? 'member';

// Check if user is captain
$check_sql = $conn->prepare("SELECT role FROM team_members WHERE team_id = ? AND user_id = ? AND role = 'captain'");
$check_sql->bind_param("ii", $team_id, $user_id);
$check_sql->execute();
$check_result = $check_sql->get_result();

if ($check_result->num_rows == 0) {
    $_SESSION['error'] = "You don't have permission to update roles.";
    header("Location: teams.php");
    exit();
}

// Update role
$update_sql = $conn->prepare("UPDATE team_members SET role = ? WHERE team_id = ? AND user_id = ? AND role != 'captain'");
$update_sql->bind_param("sii", $new_role, $team_id, $player_id);

if ($update_sql->execute()) {
    $_SESSION['success'] = "Player role updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update role.";
}

header("Location: team_details.php?id=" . $team_id);
?>