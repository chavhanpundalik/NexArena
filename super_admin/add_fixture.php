<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Fetch events, sports, and teams for dropdowns ---
$events = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC")->fetch_all(MYSQLI_ASSOC);
$sports = $conn->query("SELECT sport_id, sport_name FROM sports ORDER BY sport_name")->fetch_all(MYSQLI_ASSOC);

// Fetch teams with event association for better UX
$teams = $conn->query("SELECT team_id, team_name, event_id FROM teams WHERE status = 'active' ORDER BY team_name")->fetch_all(MYSQLI_ASSOC);

$errors = [];
$success = false;
$form_data = [
    'event_id' => '',
    'sport_id' => '',
    'team_one' => '',
    'team_two' => '',
    'team_one_id' => '',
    'team_two_id' => '',
    'fixture_date' => date('Y-m-d'),
    'fixture_time' => '10:00',
    'venue' => '',
    'status' => 'upcoming',
    'round' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = (int)$_POST['event_id'] ?? 0;
    $sport_id = (int)$_POST['sport_id'] ?? 0;
    $team_one = trim($_POST['team_one'] ?? '');
    $team_two = trim($_POST['team_two'] ?? '');
    $team_one_id = (int)$_POST['team_one_id'] ?? 0;
    $team_two_id = (int)$_POST['team_two_id'] ?? 0;
    $fixture_date = $_POST['fixture_date'] ?? '';
    $fixture_time = $_POST['fixture_time'] ?? '10:00';
    $venue = trim($_POST['venue'] ?? '');
    $status = $_POST['status'] ?? 'upcoming';
    $round = trim($_POST['round'] ?? '');

    $form_data = compact('event_id', 'sport_id', 'team_one', 'team_two', 'team_one_id', 'team_two_id', 'fixture_date', 'fixture_time', 'venue', 'status', 'round');

    if ($event_id <= 0) { $errors[] = "Please select an event."; }
    if ($sport_id <= 0) { $errors[] = "Please select a sport."; }
    if (strlen($team_one) < 2) { $errors[] = "Team One name is required."; }
    if (strlen($team_two) < 2) { $errors[] = "Team Two name is required."; }
    if (empty($fixture_date)) { $errors[] = "Fixture date is required."; }
    if (empty($fixture_time)) { $errors[] = "Fixture time is required."; }
    if (strlen($venue) < 2) { $errors[] = "Venue is required."; }
    if (!in_array($status, ['upcoming','live','completed','cancelled','postponed'])) { $errors[] = "Invalid status."; }

    // Prevent same team against itself
    if ($team_one === $team_two) {
        $errors[] = "A team cannot play against itself.";
    }

    if (empty($errors)) {
        // Check if team names are used already (optional)
        $stmt = $conn->prepare("INSERT INTO fixtures (event_id, sport_id, team_one, team_two, team_one_id, team_two_id, fixture_date, fixture_time, venue, status, round) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissiisssss", $event_id, $sport_id, $team_one, $team_two, $team_one_id, $team_two_id, $fixture_date, $fixture_time, $venue, $status, $round);
        if ($stmt->execute()) {
            $success = true;
            $form_data = [
                'event_id' => '',
                'sport_id' => '',
                'team_one' => '',
                'team_two' => '',
                'team_one_id' => '',
                'team_two_id' => '',
                'fixture_date' => date('Y-m-d'),
                'fixture_time' => '10:00',
                'venue' => '',
                'status' => 'upcoming',
                'round' => ''
            ];
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
    <title>Add Fixture | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/fixtures.css">
    <style>
        .form-card { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 18px; border: 1px solid #e5e7eb; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.07); }
        .form-card h2 { font-size: 22px; margin-bottom: 6px; }
        .form-card .subtitle { color: #71717a; margin-bottom: 28px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 700; font-size: 13px; color: #1f2937; margin-bottom: 6px; }
        .form-group label .required { color: #dc2626; margin-left: 2px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: #fafafa; transition: 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--orange); background: #fff; box-shadow: 0 0 0 3px rgba(249,115,22,0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-actions { display: flex; gap: 14px; margin-top: 10px; flex-wrap: wrap; }
        .btn-primary { background: var(--orange); color: #fff; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.25s; box-shadow: 0 7px 18px rgba(249,115,22,0.25); }
        .btn-primary:hover { background: var(--orange-dark); transform: translateY(-2px); }
        .btn-secondary { background: #f4f4f5; color: #1f2937; border: 1px solid #e5e7eb; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 15px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.25s; }
        .btn-secondary:hover { background: #e4e4e7; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert ul { margin: 4px 0 0 18px; }
        .team-select-group { display: flex; gap: 10px; align-items: center; }
        .team-select-group input { flex: 1; }
        .team-select-group select { flex: 1; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } .form-card { padding: 20px; } .team-select-group { flex-direction: column; } }
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
                <h1>Add Fixture</h1>
                <p>Schedule a new match between teams.</p>
            </div>
        </div>
        <a href="fixtures.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Fixtures
        </a>
    </section>
    <div class="form-card">
        <h2><i class="fa-regular fa-pen-to-square" style="color:var(--orange);"></i> Fixture Details</h2>
        <p class="subtitle">Fill in the match schedule and teams.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Fixture created successfully! <a href="fixtures.php" style="color:var(--orange);font-weight:700;text-decoration:none;margin-left:10px;">View all fixtures →</a></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Please fix: <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="event_id">Event <span class="required">*</span></label>
                    <select name="event_id" id="event_id" required>
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $e): ?>
                            <option value="<?= $e['event_id']; ?>" <?= $form_data['event_id'] == $e['event_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($e['event_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sport_id">Sport <span class="required">*</span></label>
                    <select name="sport_id" id="sport_id" required>
                        <option value="">-- Select Sport --</option>
                        <?php foreach ($sports as $s): ?>
                            <option value="<?= $s['sport_id']; ?>" <?= $form_data['sport_id'] == $s['sport_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($s['sport_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="team_one">Team One <span class="required">*</span></label>
                    <div class="team-select-group">
                        <input type="text" id="team_one" name="team_one" placeholder="Team name" value="<?= htmlspecialchars($form_data['team_one']); ?>" required>
                        <select name="team_one_id" id="team_one_id">
                            <option value="">-- or select existing --</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['team_id']; ?>" <?= $form_data['team_one_id'] == $t['team_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($t['team_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="team_two">Team Two <span class="required">*</span></label>
                    <div class="team-select-group">
                        <input type="text" id="team_two" name="team_two" placeholder="Team name" value="<?= htmlspecialchars($form_data['team_two']); ?>" required>
                        <select name="team_two_id" id="team_two_id">
                            <option value="">-- or select existing --</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['team_id']; ?>" <?= $form_data['team_two_id'] == $t['team_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($t['team_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fixture_date">Date <span class="required">*</span></label>
                    <input type="date" id="fixture_date" name="fixture_date" value="<?= htmlspecialchars($form_data['fixture_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="fixture_time">Time <span class="required">*</span></label>
                    <input type="time" id="fixture_time" name="fixture_time" value="<?= htmlspecialchars($form_data['fixture_time']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="venue">Venue <span class="required">*</span></label>
                    <input type="text" id="venue" name="venue" placeholder="Stadium/ground name" value="<?= htmlspecialchars($form_data['venue']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="upcoming" <?= $form_data['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="live" <?= $form_data['status'] === 'live' ? 'selected' : ''; ?>>Live</option>
                        <option value="completed" <?= $form_data['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?= $form_data['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="postponed" <?= $form_data['status'] === 'postponed' ? 'selected' : ''; ?>>Postponed</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="round">Round / Stage</label>
                <input type="text" id="round" name="round" placeholder="e.g., Quarter Final, Semi Final, Group A" value="<?= htmlspecialchars($form_data['round']); ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Create Fixture</button>
                <a href="fixtures.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>