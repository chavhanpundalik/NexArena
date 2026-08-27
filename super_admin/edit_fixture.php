<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$fixtureId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($fixtureId <= 0) { header("Location: fixtures.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM fixtures WHERE fixture_id = ?");
$stmt->bind_param("i", $fixtureId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: fixtures.php"); exit(); }
$fixture = $result->fetch_assoc();
$stmt->close();

// --- Fetch dropdowns ---
$events = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC")->fetch_all(MYSQLI_ASSOC);
$sports = $conn->query("SELECT sport_id, sport_name FROM sports ORDER BY sport_name")->fetch_all(MYSQLI_ASSOC);
$teams = $conn->query("SELECT team_id, team_name, event_id FROM teams WHERE status = 'active' ORDER BY team_name")->fetch_all(MYSQLI_ASSOC);

$errors = [];
$success = false;
$form_data = $fixture;

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
    $score_team_one = (int)$_POST['score_team_one'] ?? 0;
    $score_team_two = (int)$_POST['score_team_two'] ?? 0;
    $winner_team_id = (int)$_POST['winner_team_id'] ?? 0;

    $form_data = compact('event_id', 'sport_id', 'team_one', 'team_two', 'team_one_id', 'team_two_id', 'fixture_date', 'fixture_time', 'venue', 'status', 'round', 'score_team_one', 'score_team_two', 'winner_team_id');

    if ($event_id <= 0) { $errors[] = "Please select an event."; }
    if ($sport_id <= 0) { $errors[] = "Please select a sport."; }
    if (strlen($team_one) < 2) { $errors[] = "Team One name is required."; }
    if (strlen($team_two) < 2) { $errors[] = "Team Two name is required."; }
    if (empty($fixture_date)) { $errors[] = "Fixture date is required."; }
    if (empty($fixture_time)) { $errors[] = "Fixture time is required."; }
    if (strlen($venue) < 2) { $errors[] = "Venue is required."; }
    if ($team_one === $team_two) { $errors[] = "A team cannot play against itself."; }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE fixtures SET event_id=?, sport_id=?, team_one=?, team_two=?, team_one_id=?, team_two_id=?, fixture_date=?, fixture_time=?, venue=?, status=?, round=?, score_team_one=?, score_team_two=?, winner_team_id=? WHERE fixture_id=?");
        $stmt->bind_param("iissiisssssiiii", $event_id, $sport_id, $team_one, $team_two, $team_one_id, $team_two_id, $fixture_date, $fixture_time, $venue, $status, $round, $score_team_one, $score_team_two, $winner_team_id, $fixtureId);
        if ($stmt->execute()) {
            $success = true;
            $fixture = array_merge($fixture, $form_data);

            // ============================================================
            // AUTO-UPDATE LEADERBOARD (only if fixture is completed)
            // ============================================================
            if ($status === 'completed' && $winner_team_id > 0) {
                // 1. Update winner
                $updateWinner = "INSERT INTO leaderboard (team_id, event_id, sport_id, wins, losses, draws, matches_played)
                                 VALUES (?, ?, ?, 1, 0, 0, 1)
                                 ON DUPLICATE KEY UPDATE 
                                     wins = wins + 1,
                                     matches_played = matches_played + 1";
                $stmtW = $conn->prepare($updateWinner);
                $stmtW->bind_param("iii", $winner_team_id, $event_id, $sport_id);
                $stmtW->execute();
                $stmtW->close();

                // 2. Update loser
                $loser_id = ($winner_team_id == $team_one_id) ? $team_two_id : $team_one_id;
                if ($loser_id > 0) {
                    $updateLoser = "INSERT INTO leaderboard (team_id, event_id, sport_id, wins, losses, draws, matches_played)
                                    VALUES (?, ?, ?, 0, 1, 0, 1)
                                    ON DUPLICATE KEY UPDATE 
                                        losses = losses + 1,
                                        matches_played = matches_played + 1";
                    $stmtL = $conn->prepare($updateLoser);
                    $stmtL->bind_param("iii", $loser_id, $event_id, $sport_id);
                    $stmtL->execute();
                    $stmtL->close();
                }
            }
            // ============================================================

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
    <title>Edit Fixture | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/fixtures.css">
    <style>/* same as add_fixture */</style>
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-pen-to-square"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Edit Fixture</h1>
                <p>Update match details and scores.</p>
            </div>
        </div>
        <a href="fixtures.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Fixtures
        </a>
    </section>
    <div class="form-card">
        <h2><i class="fa-regular fa-pen-to-square" style="color:var(--orange);"></i> Edit Fixture</h2>
        <p class="subtitle">Update the match details below.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Fixture updated successfully! <a href="fixtures.php" style="color:var(--orange);font-weight:700;text-decoration:none;margin-left:10px;">View all fixtures →</a></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Please fix: <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $fixtureId; ?>">

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

            <!-- Score section -->
            <div class="form-row">
                <div class="form-group">
                    <label for="score_team_one">Score - Team One</label>
                    <input type="number" id="score_team_one" name="score_team_one" min="0" value="<?= (int)$form_data['score_team_one']; ?>">
                </div>
                <div class="form-group">
                    <label for="score_team_two">Score - Team Two</label>
                    <input type="number" id="score_team_two" name="score_team_two" min="0" value="<?= (int)$form_data['score_team_two']; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="winner_team_id">Winner Team</label>
                <select name="winner_team_id" id="winner_team_id">
                    <option value="0">-- No winner yet --</option>
                    <?php foreach ($teams as $t): ?>
                        <option value="<?= $t['team_id']; ?>" <?= $form_data['winner_team_id'] == $t['team_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($t['team_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Update Fixture</button>
                <a href="fixtures.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>