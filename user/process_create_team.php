<?php
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

require_once "../db_connect.php";

// Get form data
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$team_name = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';
$members_str = isset($_POST['members']) ? $_POST['members'] : '';
$members = !empty($members_str) ? explode(',', $members_str) : [];

// Validate
if ($event_id <= 0 || empty($team_name)) {
    header("Location: create_team.php?id=$event_id&error=Invalid input");
    exit();
}

// Check if team name already exists for this event
$check_name = $conn->prepare("
    SELECT team_id FROM teams WHERE event_id = ? AND team_name = ?
");
$check_name->bind_param("is", $event_id, $team_name);
$check_name->execute();
$name_result = $check_name->get_result();

if ($name_result->num_rows > 0) {
    $check_name->close();
    header("Location: create_team.php?id=$event_id&error=Team name already taken");
    exit();
}
$check_name->close();

// Check if user already has a team
$check_user_team = $conn->prepare("
    SELECT team_id FROM teams WHERE event_id = ? AND captain_id = ?
");
$check_user_team->bind_param("ii", $event_id, $user_id);
$check_user_team->execute();
$user_team_result = $check_user_team->get_result();

if ($user_team_result->num_rows > 0) {
    $check_user_team->close();
    header("Location: create_team.php?id=$event_id&error=You already have a team for this event");
    exit();
}
$check_user_team->close();

// Start transaction
$conn->begin_transaction();

try {
    // 1. Create team
    $players_count = 1 + count($members); // Captain + members
    
    $insert_team = $conn->prepare("
        INSERT INTO teams (event_id, captain_id, team_name, players_count, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $insert_team->bind_param("iisi", $event_id, $user_id, $team_name, $players_count);
    
    if (!$insert_team->execute()) {
        throw new Exception("Failed to create team: " . $conn->error);
    }
    
    $team_id = $conn->insert_id;
    $insert_team->close();

    // 2. Add captain as team member
    $insert_member = $conn->prepare("
        INSERT INTO team_members (team_id, user_id, role, status)
        VALUES (?, ?, 'captain', 'approved')
    ");
    $insert_member->bind_param("ii", $team_id, $user_id);
    
    if (!$insert_member->execute()) {
        throw new Exception("Failed to add captain: " . $conn->error);
    }
    $insert_member->close();

    // 3. Add other members
    if (!empty($members)) {
        $insert_member = $conn->prepare("
            INSERT INTO team_members (team_id, user_id, role, status)
            VALUES (?, ?, 'player', 'pending')
        ");
        
        foreach ($members as $member_id) {
            $member_id = (int)$member_id;
            
            // Check if member is registered for this event
            $check_reg = $conn->prepare("
                SELECT registration_id FROM registrations 
                WHERE user_id = ? AND event_id = ? AND status = 'confirmed'
            ");
            $check_reg->bind_param("ii", $member_id, $event_id);
            $check_reg->execute();
            $reg_result = $check_reg->get_result();
            
            if ($reg_result->num_rows == 0) {
                throw new Exception("User ID $member_id is not registered for this event");
            }
            $check_reg->close();
            
            // Check if member is already in another team for this event
            $check_other_team = $conn->prepare("
                SELECT tm.team_id FROM team_members tm
                JOIN teams t ON tm.team_id = t.team_id
                WHERE tm.user_id = ? AND t.event_id = ? AND tm.status = 'approved'
            ");
            $check_other_team->bind_param("ii", $member_id, $event_id);
            $check_other_team->execute();
            $other_result = $check_other_team->get_result();
            
            if ($other_result->num_rows > 0) {
                throw new Exception("User is already in another team for this event");
            }
            $check_other_team->close();
            
            $insert_member->bind_param("ii", $team_id, $member_id);
            
            if (!$insert_member->execute()) {
                throw new Exception("Failed to add member: " . $conn->error);
            }
        }
        $insert_member->close();
    }

    // 4. Update registration with team_id
    $update_reg = $conn->prepare("
        UPDATE registrations SET team_id = ? WHERE user_id = ? AND event_id = ?
    ");
    $update_reg->bind_param("iii", $team_id, $user_id, $event_id);
    $update_reg->execute();
    $update_reg->close();

    // Commit transaction
    $conn->commit();

    // Success - redirect to team management
    header("Location: manage_team.php?team_id=$team_id&success=Team created successfully");
    exit();

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    header("Location: create_team.php?id=$event_id&error=" . urlencode($e->getMessage()));
    exit();
}

$conn->close();
?>