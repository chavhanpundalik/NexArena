<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../db_connect.php";

$user_id = $_SESSION['user_id'];
$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;

// Check if user is captain
$check_sql = $conn->prepare("SELECT role FROM team_members WHERE team_id = ? AND user_id = ? AND role = 'captain'");
$check_sql->bind_param("ii", $team_id, $user_id);
$check_sql->execute();
$check_result = $check_sql->get_result();

if ($check_result->num_rows == 0) {
    $_SESSION['error'] = "You don't have permission to approve requests.";
    header("Location: teams.php");
    exit();
}

// Get request details
$request_sql = $conn->prepare("SELECT user_id FROM team_join_requests WHERE request_id = ?");
$request_sql->bind_param("i", $request_id);
$request_sql->execute();
$request = $request_sql->get_result()->fetch_assoc();
$player_id = $request['user_id'];

// Update request status
$update_sql = $conn->prepare("UPDATE team_join_requests SET status = 'approved' WHERE request_id = ?");
$update_sql->bind_param("i", $request_id);
$update_sql->execute();

// Add player to team
$add_sql = $conn->prepare("INSERT INTO team_members (team_id, user_id, role, status) VALUES (?, ?, 'member', 'active')");
$add_sql->bind_param("ii", $team_id, $player_id);

if ($add_sql->execute()) {
    $_SESSION['success'] = "Player added to team successfully!";
} else {
    $_SESSION['error'] = "Failed to add player.";
}

header("Location: team_details.php?id=" . $team_id);
?>