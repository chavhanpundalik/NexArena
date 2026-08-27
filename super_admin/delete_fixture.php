<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$fixtureId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($fixtureId <= 0) { header("Location: fixtures.php"); exit(); }

$stmt = $conn->prepare("SELECT team_one, team_two, fixture_date FROM fixtures WHERE fixture_id = ?");
$stmt->bind_param("i", $fixtureId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: fixtures.php"); exit(); }
$fixture = $result->fetch_assoc();
$stmt->close();

if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $del = $conn->prepare("DELETE FROM fixtures WHERE fixture_id = ?");
    $del->bind_param("i", $fixtureId);
    $del->execute();
    $del->close();
    header("Location: fixtures.php?deleted=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Delete | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/fixtures.css">
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <div class="confirm-container">
        <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h2>Delete Fixture?</h2>
        <p>You are about to delete the fixture: <strong><?= htmlspecialchars($fixture['team_one'] . ' vs ' . $fixture['team_two']); ?></strong> on <?= date('M d, Y', strtotime($fixture['fixture_date'])); ?>. This cannot be undone.</p>
        <div class="confirm-actions">
            <a href="delete_fixture.php?id=<?= $fixtureId; ?>&confirm=yes" class="btn-danger"><i class="fa-solid fa-trash"></i> Yes, Delete</a>
            <a href="fixtures.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
        </div>
    </div>
</main>
</body>
</html>