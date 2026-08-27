<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../db_connect.php";

$user_id = $_SESSION['user_id'];
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;

// Check if already in team
$check_member = $conn->prepare("SELECT * FROM team_members WHERE team_id = ? AND user_id = ?");
$check_member->bind_param("ii", $team_id, $user_id);
$check_member->execute();

if ($check_member->get_result()->num_rows > 0) {
    $_SESSION['error'] = "You are already a member of this team.";
    header("Location: team_details.php?id=" . $team_id);
    exit();
}

// Check if request already exists
$check_request = $conn->prepare("SELECT * FROM team_join_requests WHERE team_id = ? AND user_id = ? AND status = 'pending'");
$check_request->bind_param("ii", $team_id, $user_id);
$check_request->execute();

if ($check_request->get_result()->num_rows > 0) {
    $_SESSION['error'] = "You already have a pending request for this team.";
    header("Location: team_details.php?id=" . $team_id);
    exit();
}

// Create join request
$request_sql = $conn->prepare("INSERT INTO team_join_requests (team_id, user_id, message, status) VALUES (?, ?, ?, 'pending')");
$message = $_POST['message'] ?? 'I would like to join your team.';
$request_sql->bind_param("iis", $team_id, $user_id, $message);

if ($request_sql->execute()) {
    $_SESSION['success'] = "Your request to join the team has been sent!";
    
    // Notify captain
    $captain_sql = $conn->prepare("SELECT captain_id FROM teams WHERE team_id = ?");
    $captain_sql->bind_param("i", $team_id);
    $captain_sql->execute();
    $captain_id = $captain_sql->get_result()->fetch_assoc()['captain_id'];
    
    $notify_sql = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    $title = "New Join Request";
    $message = "A player has requested to join your team.";
    $link = "team_details.php?id=" . $team_id;
    $notify_sql->bind_param("issss", $captain_id, $title, $message, 'team', $link);
    $notify_sql->execute();
} else {
    $_SESSION['error'] = "Failed to send request.";
}

header("Location: team_details.php?id=" . $team_id);
?>