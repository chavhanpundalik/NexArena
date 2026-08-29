<?php
session_start();
require_once "../db_connect.php";

// Check if admin/super admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
    header("Location: ../login.php");
    exit();
}

$errors = [];
$success = false;
$form_data = [
    'sport_id' => '',
    'event_name' => '',
    'description' => '',
    'event_date' => '',
    'event_time' => '',
    'venue' => '',
    'max_participants' => 0,
    'status' => 'active'
];

// Get all active sports
$sports_sql = "SELECT sport_id, sport_name FROM sports WHERE status = 'active' ORDER BY sport_name";
$sports_result = $conn->query($sports_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sport_id = (int)$_POST['sport_id'];
    $event_name = trim($_POST['event_name']);
    $description = trim($_POST['description']);
    $event_date = trim($_POST['event_date']);
    $event_time = trim($_POST['event_time']);
    $venue = trim($_POST['venue']);
    $max_participants = (int)$_POST['max_participants'];
    $status = $_POST['status'] ?? 'active';
    
    $form_data = compact('sport_id', 'event_name', 'description', 'event_date', 'event_time', 'venue', 'max_participants', 'status');
    
    // Validation
    if ($sport_id <= 0) { $errors[] = "Please select a sport."; }
    if (strlen($event_name) < 3) { $errors[] = "Event name must be at least 3 characters."; }
    if (empty($event_date)) { $errors[] = "Event date is required."; }
    if (empty($event_time)) { $errors[] = "Event time is required."; }
    if (empty($venue)) { $errors[] = "Venue is required."; }
    
    if (empty($errors)) {
        $user_id = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("
            INSERT INTO events (sport_id, event_name, description, event_date, event_time, venue, max_participants, status, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssssisi", $sport_id, $event_name, $description, $event_date, $event_time, $venue, $max_participants, $status, $user_id);
        
        if ($stmt->execute()) {
            $event_id = $stmt->insert_id;
            $success = true;
            $form_data = ['sport_id' => '', 'event_name' => '', 'description' => '', 'event_date' => '', 'event_time' => '', 'venue' => '', 'max_participants' => 0, 'status' => 'active'];
            
            // Send notifications to all users about new event
            $users_sql = "SELECT user_id FROM users WHERE role = 'user' AND status = 'active'";
            $users_result = $conn->query($users_sql);
            
            while ($user = $users_result->fetch_assoc()) {
                $notif_sql = "INSERT INTO notifications (user_id, type, message, link, created_at) VALUES (?, 'event', ?, 'user/event_details.php?id={$event_id}', NOW())";
                $notif_stmt = $conn->prepare($notif_sql);
                $message = "New event '{$event_name}' has been added! Register now.";
                $notif_stmt->bind_param("is", $user['user_id'], $message);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get dark mode
$user_id = (int)$_SESSION['user_id'];
$dark_mode = 0;
$settings_sql = "SELECT dark_mode FROM user_settings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->bind_param("i", $user_id);
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();
if ($settings_result->num_rows > 0) {
    $settings_data = $settings_result->fetch_assoc();
    $dark_mode = $settings_data['dark_mode'] ?? 0;
}
$settings_stmt->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $dark_mode ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event | NexArena Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/theme.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <style>
        .form-container { max-width: 800px; margin: 0 auto; padding: 24px; }
        .form-card { background: var(--card-bg, #fff); border-radius: 16px; padding: 32px; border: 1px solid var(--border-color, #e2e8f0); }
        .form-card h2 { margin: 0 0 8px 0; color: var(--text-primary, #1f2937); }
        .form-card .subtitle { color: var(--text-muted, #64748b); margin: 0 0 24px 0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: var(--text-primary, #1f2937); margin-bottom: 6px; font-size: 14px; }
        .form-group .required { color: #ef4444; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px 14px; border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px; background: var(--bg-input, #f8fafc); color: var(--text-primary, #1f2937);
            font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-actions { display: flex; gap: 12px; margin-top: 24px; }
        .btn-primary { padding: 10px 32px; background: #f97316; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary:hover { background: #ea580c; transform: translateY(-2px); }
        .btn-secondary { padding: 10px 32px; background: var(--bg-muted, #f1f5f9); color: var(--text-primary, #1f2937); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s ease; }
        .btn-secondary:hover { background: var(--border-color, #e2e8f0); }
        .alert { padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
        .alert-danger { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
        .alert ul { margin: 8px 0 0 0; padding-left: 20px; }
        [data-theme="dark"] .form-card { background: #1e293b; border-color: rgba(255,255,255,0.06); }
        [data-theme="dark"] .form-group input, [data-theme="dark"] .form-group select, [data-theme="dark"] .form-group textarea { background: #0f172a; border-color: rgba(255,255,255,0.1); color: #e2e8f0; }
        [data-theme="dark"] .btn-secondary { background: #334155; border-color: rgba(255,255,255,0.1); color: #e2e8f0; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } .form-actions { flex-direction: column; } .btn-primary, .btn-secondary { width: 100%; text-align: center; } }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<div class="form-container">
    <div class="form-card">
        <h2><i class="fa-regular fa-calendar-plus" style="color:#f97316;"></i> Add New Event</h2>
        <p class="subtitle">Create a new event for users to register and participate.</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Event created successfully! <a href="events.php" style="color:#f97316;font-weight:700;">View all events →</a></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Please fix: <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="sport_id">Sport <span class="required">*</span></label>
                <select name="sport_id" id="sport_id" required>
                    <option value="">Select a sport...</option>
                    <?php while ($sport = $sports_result->fetch_assoc()): ?>
                        <option value="<?= $sport['sport_id']; ?>" <?= $form_data['sport_id'] == $sport['sport_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($sport['sport_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="event_name">Event Name <span class="required">*</span></label>
                <input type="text" id="event_name" name="event_name" value="<?= htmlspecialchars($form_data['event_name']); ?>" required placeholder="e.g., Inter-College Cricket Tournament">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars($form_data['description']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="event_date">Event Date <span class="required">*</span></label>
                    <input type="date" id="event_date" name="event_date" value="<?= htmlspecialchars($form_data['event_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="event_time">Event Time <span class="required">*</span></label>
                    <input type="time" id="event_time" name="event_time" value="<?= htmlspecialchars($form_data['event_time']); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="venue">Venue <span class="required">*</span></label>
                <input type="text" id="venue" name="venue" value="<?= htmlspecialchars($form_data['venue']); ?>" required placeholder="e.g., Main Stadium, City Sports Complex">
            </div>
            
            <div class="form-group">
                <label for="max_participants">Max Participants (0 = Unlimited)</label>
                <input type="number" id="max_participants" name="max_participants" min="0" value="<?= (int)$form_data['max_participants']; ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="active" <?= $form_data['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= $form_data['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Create Event</button>
                <a href="events.php" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>
<script src="assets/theme.js"></script>
</body>
</html>