<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
require_once "../db_connect.php";

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if (empty($query) || $event_id <= 0) {
    echo json_encode(['success' => true, 'users' => []]);
    exit();
}

// Search for users registered for this event
$search_sql = $conn->prepare("
    SELECT DISTINCT u.user_id, u.full_name, u.email 
    FROM users u
    JOIN registrations r ON u.user_id = r.user_id
    LEFT JOIN team_members tm ON u.user_id = tm.user_id
    LEFT JOIN teams t ON tm.team_id = t.team_id AND t.event_id = ?
    WHERE r.event_id = ? 
        AND r.status = 'confirmed'
        AND u.user_id != ?
        AND (u.full_name LIKE ? OR u.email LIKE ?)
        AND (t.team_id IS NULL OR tm.status != 'approved')
    ORDER BY u.full_name ASC
    LIMIT 10
");

$search_term = "%$query%";
$search_sql->bind_param("iiiss", $event_id, $event_id, $user_id, $search_term, $search_term);
$search_sql->execute();
$result = $search_sql->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$search_sql->close();
$conn->close();

echo json_encode(['success' => true, 'users' => $users]);
?>