<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$regId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($regId <= 0) { header("Location: registrations.php"); exit(); }

$sql = "SELECT er.*, e.event_name, e.event_date, e.location, 
               u.full_name AS user_name, u.email, u.phone,
               t.team_name
        FROM event_registrations er
        LEFT JOIN events e ON er.event_id = e.event_id
        LEFT JOIN users u ON er.user_id = u.user_id
        LEFT JOIN teams t ON er.team_id = t.team_id
        WHERE er.registration_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $regId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: registrations.php"); exit(); }
$reg = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Details | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/registrations.css">
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-regular fa-file-lines"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Registration Details</h1>
                <p>View full registration information.</p>
            </div>
        </div>
        <a href="registrations.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </section>

    <div class="detail-card">
        <div class="detail-section">
            <h3><i class="fa-regular fa-circle-info" style="color:var(--orange);"></i> Registration Info</h3>
            <table class="detail-table">
                <tr><th>Registration ID</th><td>#<?= (int)$reg['registration_id']; ?></td></tr>
                <tr><th>Event</th><td><?= htmlspecialchars($reg['event_name']); ?></td></tr>
                <tr><th>Event Date</th><td><?= date('M d, Y', strtotime($reg['event_date'])); ?></td></tr>
                <tr><th>Location</th><td><?= htmlspecialchars($reg['location']); ?></td></tr>
                <tr><th>User</th><td><?= htmlspecialchars($reg['user_name']); ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($reg['email']); ?></td></tr>
                <tr><th>Phone</th><td><?= htmlspecialchars($reg['phone']); ?></td></tr>
                <tr><th>Team</th><td><?= htmlspecialchars($reg['team_name'] ?? '—'); ?></td></tr>
                <tr><th>Status</th><td><span class="reg-status <?= $reg['status']; ?>"><?= ucfirst($reg['status']); ?></span></td></tr>
                <tr><th>Payment Status</th><td><span class="payment-status <?= $reg['payment_status']; ?>"><?= ucfirst($reg['payment_status']); ?></span></td></tr>
                <tr><th>Registered At</th><td><?= date('M d, Y H:i', strtotime($reg['registered_at'])); ?></td></tr>
                <?php if ($reg['approved_at']): ?>
                <tr><th>Approved At</th><td><?= date('M d, Y H:i', strtotime($reg['approved_at'])); ?></td></tr>
                <?php endif; ?>
                <?php if ($reg['notes']): ?>
                <tr><th>Notes</th><td><?= nl2br(htmlspecialchars($reg['notes'])); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="detail-actions">
            <?php if ($reg['status'] === 'pending'): ?>
                <a href="update_registration.php?id=<?= (int)$reg['registration_id']; ?>&action=approve" class="btn-primary" onclick="return confirm('Approve this registration?');"><i class="fa-solid fa-check"></i> Approve</a>
                <a href="update_registration.php?id=<?= (int)$reg['registration_id']; ?>&action=reject" class="btn-danger" onclick="return confirm('Reject this registration?');"><i class="fa-solid fa-times"></i> Reject</a>
            <?php endif; ?>
            <a href="registrations.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
        </div>
    </div>

</main>
</body>
</html>