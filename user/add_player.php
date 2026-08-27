<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../db_connect.php";

$user_id = $_SESSION['user_id'];
$team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
$player_email = trim($_POST['email'] ?? '');

// Check if user is captain of this team
$check_sql = $conn->prepare("SELECT role FROM team_members WHERE team_id = ? AND user_id = ? AND role = 'captain'");
$check_sql->bind_param("ii", $team_id, $user_id);
$check_sql->execute();
$check_result = $check_sql->get_result();

if ($check_result->num_rows == 0) {
    $_SESSION['error'] = "You don't have permission to add players to this team.";
    header("Location: teams.php");
    exit();
}

if (empty($player_email)) {
    $_SESSION['error'] = "Please enter a valid email address.";
    header("Location: team_details.php?id=" . $team_id);
    exit();
}

// Find user by email
$user_sql = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
$user_sql->bind_param("s", $player_email);
$user_sql->execute();
$user_result = $user_sql->get_result();

if ($user_result->num_rows == 0) {
    $_SESSION['error'] = "User not found with this email.";
    header("Location: team_details.php?id=" . $team_id);
    exit();
}

$player = $user_result->fetch_assoc();
$player_id = $player['user_id'];

// Check if already in team
$check_member = $conn->prepare("SELECT * FROM team_members WHERE team_id = ? AND user_id = ?");
$check_member->bind_param("ii", $team_id, $player_id);
$check_member->execute();

if ($check_member->get_result()->num_rows > 0) {
    $_SESSION['error'] = "This player is already in the team.";
    header("Location: team_details.php?id=" . $team_id);
    exit();
}

// Add player to team
$add_sql = $conn->prepare("INSERT INTO team_members (team_id, user_id, role, status) VALUES (?, ?, 'member', 'active')");
$add_sql->bind_param("ii", $team_id, $player_id);

if ($add_sql->execute()) {
    $_SESSION['success'] = $player['full_name'] . " has been added to the team!";
    
    // Create notification for the player
    $notify_sql = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    $team_name_sql = $conn->prepare("SELECT team_name FROM teams WHERE team_id = ?");
    $team_name_sql->bind_param("i", $team_id);
    $team_name_sql->execute();
    $team_name = $team_name_sql->get_result()->fetch_assoc()['team_name'];
    
    $title = "Added to Team";
    $message = "You have been added to team: " . $team_name;
    $link = "team_details.php?id=" . $team_id;
    $notify_sql->bind_param("issss", $player_id, $title, $message, 'team', $link);
    $notify_sql->execute();
    
    header("Location: team_details.php?id=" . $team_id);
} else {
    $_SESSION['error'] = "Failed to add player.";
    header("Location: team_details.php?id=" . $team_id);
}
?>