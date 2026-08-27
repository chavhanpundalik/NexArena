<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

require_once "../db_connect.php";

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $game = trim($_POST['game'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $is_private = isset($_POST['is_private']) ? (int)$_POST['is_private'] : 0;
    
    // Validate team name
    if (empty($team_name)) {
        $_SESSION['error'] = "Team name is required.";
        header("Location: teams.php");
        exit();
    }
    
    // Check if team name already exists
    $check_sql = $conn->prepare("SELECT team_id FROM teams WHERE team_name = ?");
    $check_sql->bind_param("s", $team_name);
    $check_sql->execute();
    $check_result = $check_sql->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Team name already exists. Please choose a different name.";
        header("Location: teams.php");
        exit();
    }
    
    // Insert team
    $sql = $conn->prepare("
        INSERT INTO teams (team_name, description, game, region, is_private, captain_id, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ");
    $sql->bind_param("ssssii", $team_name, $description, $game, $region, $is_private, $user_id);
    
    if ($sql->execute()) {
        $team_id = $conn->insert_id;
        
        // Add captain as team member
        $member_sql = $conn->prepare("
            INSERT INTO team_members (team_id, user_id, role, status) 
            VALUES (?, ?, 'captain', 'active')
        ");
        $member_sql->bind_param("ii", $team_id, $user_id);
        $member_sql->execute();
        
        $_SESSION['success'] = "Team created successfully! You can now add players to your team.";
        $_SESSION['team_id'] = $team_id;
        header("Location: manage_team.php?id=" . $team_id);
        exit();
    } else {
        $_SESSION['error'] = "Failed to create team. Please try again.";
        header("Location: teams.php");
        exit();
    }
} else {
    header("Location: teams.php");
    exit();
}
?>