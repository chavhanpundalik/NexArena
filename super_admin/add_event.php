<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$errors = [];
$success = false;
$form_data = ['event_name' => '', 'description' => '', 'event_date' => date('Y-m-d'), 'location' => '', 'status' => 'upcoming'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name   = trim($_POST['event_name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $event_date   = $_POST['event_date'] ?? '';
    $location     = trim($_POST['location'] ?? '');
    $status       = $_POST['status'] ?? 'upcoming';

    $form_data = compact('event_name', 'description', 'event_date', 'location', 'status');

    if (strlen($event_name) < 3) { $errors[] = "Event name must be at least 3 characters."; }
    if (empty($event_date)) { $errors[] = "Event date is required."; }
    if (strlen($location) < 2) { $errors[] = "Location is required."; }
    if (!in_array($status, ['upcoming','ongoing','completed','cancelled'])) { $errors[] = "Invalid status."; }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO events (event_name, description, event_date, location, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $event_name, $description, $event_date, $location, $status);
        if ($stmt->execute()) {
            $success = true;
            $form_data = ['event_name' => '', 'description' => '', 'event_date' => date('Y-m-d'), 'location' => '', 'status' => 'upcoming'];
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        .form-card {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
        }
        .form-card h2 { font-size: 22px; margin-bottom: 6px; }
        .form-card .subtitle { color: #71717a; margin-bottom: 28px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 700; font-size: 13px; color: #1f2937; margin-bottom: 6px; }
        .form-group label .required { color: #dc2626; margin-left: 2px; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: #fafafa;
            transition: 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: var(--orange);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-actions { display: flex; gap: 14px; margin-top: 10px; flex-wrap: wrap; }
        .btn-primary {
            background: var(--orange); color: #fff; border: none; padding: 14px 32px; border-radius: 10px;
            font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.25s;
            box-shadow: 0 7px 18px rgba(249,115,22,0.25);
        }
        .btn-primary:hover { background: var(--orange-dark); transform: translateY(-2px); }
        .btn-secondary {
            background: #f4f4f5; color: #1f2937; border: 1px solid #e5e7eb;
            padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 15px;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.25s;
        }
        .btn-secondary:hover { background: #e4e4e7; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert ul { margin: 4px 0 0 18px; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } .form-card { padding: 20px; } }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-calendar-plus"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Create Event</h1>
                <p>Add a new event to the platform.</p>
            </div>
        </div>
        <a href="events.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Events
        </a>
    </section>
    <div class="form-card">
        <h2><i class="fa-regular fa-pen-to-square" style="color:var(--orange);"></i> Event Details</h2>
        <p class="subtitle">Fill in all details for the new event.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Event created successfully! <a href="events.php" style="color:var(--orange);font-weight:700;text-decoration:none;margin-left:10px;">View all events →</a></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Please fix: <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="event_name">Event Name <span class="required">*</span></label>
                <input type="text" id="event_name" name="event_name" value="<?= htmlspecialchars($form_data['event_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($form_data['description']); ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="event_date">Event Date <span class="required">*</span></label>
                    <input type="date" id="event_date" name="event_date" value="<?= htmlspecialchars($form_data['event_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="location">Location <span class="required">*</span></label>
                    <input type="text" id="location" name="location" value="<?= htmlspecialchars($form_data['location']); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="upcoming" <?= $form_data['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="ongoing" <?= $form_data['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="completed" <?= $form_data['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?= $form_data['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Create Event</button>
                <a href="events.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>