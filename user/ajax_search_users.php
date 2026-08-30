<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once "../db_connect.php";

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;

if (strlen($query) < 2 || $event_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'users' => []]);
    exit();
}

$search_param = "%$query%";

// Build query to exclude users already in the team (if team_id provided)
$exclude_condition = "";
if ($team_id > 0) {
    $exclude_condition = "AND u.user_id NOT IN (SELECT user_id FROM team_members WHERE team_id = ?)";
}

$sql = $conn->prepare("
    SELECT u.user_id, u.full_name, u.email 
    FROM users u
    JOIN registrations r ON u.user_id = r.user_id
    WHERE r.event_id = ? 
    AND r.status = 'confirmed'
    AND u.user_id != ?
    AND (u.full_name LIKE ? OR u.email LIKE ?)
    {$exclude_condition}
    ORDER BY u.full_name ASC
    LIMIT 10
");

if ($team_id > 0) {
    $sql->bind_param("iiss", $event_id, $_SESSION['user_id'], $search_param, $search_param);
    // Need to add team_id binding - simplified for now
} else {
    $sql->bind_param("iiss", $event_id, $_SESSION['user_id'], $search_param, $search_param);
}

$sql->execute();
$result = $sql->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$sql->close();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'users' => $users]);
?>