<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Filters ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$sport_id = isset($_GET['sport_id']) ? (int)$_GET['sport_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// --- Stats ---
$totalFixtures = 0;
$upcomingFixtures = 0;
$liveFixtures = 0;
$completedFixtures = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM fixtures");
if ($result) { $row = $result->fetch_assoc(); $totalFixtures = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM fixtures WHERE status = 'upcoming'");
if ($result) { $row = $result->fetch_assoc(); $upcomingFixtures = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM fixtures WHERE status = 'live'");
if ($result) { $row = $result->fetch_assoc(); $liveFixtures = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM fixtures WHERE status = 'completed'");
if ($result) { $row = $result->fetch_assoc(); $completedFixtures = (int)$row['total']; }

// --- Build query ---
$sql = "SELECT f.*, e.event_name AS event_name, s.sport_name AS sport_name 
        FROM fixtures f
        LEFT JOIN events e ON f.event_id = e.event_id
        LEFT JOIN sports s ON f.sport_id = s.sport_id
        WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (f.team_one LIKE ? OR f.team_two LIKE ? OR f.venue LIKE ?)";
    $sv = "%$search%";
    $params[] = $sv; $params[] = $sv; $params[] = $sv;
    $types .= "sss";
}
if ($event_id > 0) {
    $sql .= " AND f.event_id = ?";
    $params[] = $event_id;
    $types .= "i";
}
if ($sport_id > 0) {
    $sql .= " AND f.sport_id = ?";
    $params[] = $sport_id;
    $types .= "i";
}
if ($status !== '') {
    $sql .= " AND f.status = ?";
    $params[] = $status;
    $types .= "s";
}
if ($date_from !== '') {
    $sql .= " AND f.fixture_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if ($date_to !== '') {
    $sql .= " AND f.fixture_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY f.fixture_date ASC, f.fixture_time ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) { die("Query error: " . $conn->error); }
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$fixtures = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Get events & sports for filter dropdowns ---
$events = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC")->fetch_all(MYSQLI_ASSOC);
$sports = $conn->query("SELECT sport_id, sport_name FROM sports ORDER BY sport_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixtures Management | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/fixtures.css">
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <!-- Page Header -->
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Fixtures Management</h1>
                <p>Schedule and manage matches between teams.</p>
            </div>
        </div>
        <a href="add_fixture.php" class="add-user-btn">
            <i class="fa-solid fa-plus"></i> Add Fixture
        </a>
    </section>

    <!-- Stats -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-calendar"></i></div>
            <div class="stat-content">
                <span>Total Fixtures</span>
                <strong><?= number_format($totalFixtures); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #d97706;">
            <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-regular fa-clock"></i></div>
            <div class="stat-content">
                <span>Upcoming</span>
                <strong><?= number_format($upcomingFixtures); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #2563eb;">
            <div class="stat-icon" style="background:#dbeafe; color:#2563eb;"><i class="fa-solid fa-play"></i></div>
            <div class="stat-content">
                <span>Live</span>
                <strong><?= number_format($liveFixtures); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #059669;">
            <div class="stat-icon" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-check"></i></div>
            <div class="stat-content">
                <span>Completed</span>
                <strong><?= number_format($completedFixtures); ?></strong>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="filter-card">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search teams or venue..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="select-box">
                <i class="fa-regular fa-calendar"></i>
                <select name="event_id">
                    <option value="0">All Events</option>
                    <?php foreach ($events as $e): ?>
                        <option value="<?= $e['event_id']; ?>" <?= $event_id == $e['event_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($e['event_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="select-box">
                <i class="fa-solid fa-tag"></i>
                <select name="sport_id">
                    <option value="0">All Sports</option>
                    <?php foreach ($sports as $s): ?>
                        <option value="<?= $s['sport_id']; ?>" <?= $sport_id == $s['sport_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($s['sport_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="select-box">
                <i class="fa-solid fa-circle-half-stroke"></i>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="upcoming" <?= $status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="live" <?= $status === 'live' ? 'selected' : ''; ?>>Live</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="postponed" <?= $status === 'postponed' ? 'selected' : ''; ?>>Postponed</option>
                </select>
            </div>
            <div class="search-box" style="min-width:130px;">
                <i class="fa-regular fa-calendar"></i>
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from); ?>" placeholder="From">
            </div>
            <div class="search-box" style="min-width:130px;">
                <i class="fa-regular fa-calendar"></i>
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to); ?>" placeholder="To">
            </div>
            <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="fixtures.php" class="reset-btn"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </form>
    </section>

    <!-- Fixtures Table -->
    <section class="users-card">
        <div class="table-header">
            <div>
                <span class="section-label">FIXTURE DIRECTORY</span>
                <h2>All Fixtures</h2>
            </div>
            <span class="user-count"><?= count($fixtures); ?> fixture<?= count($fixtures) != 1 ? 's' : ''; ?></span>
        </div>
        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Match</th>
                        <th>Event / Sport</th>
                        <th>Date & Time</th>
                        <th>Venue</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($fixtures)): ?>
                    <?php foreach ($fixtures as $fixture): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="avatar" style="background:var(--orange); color:#fff; font-size:14px;">
                                        <i class="fa-regular fa-futbol"></i>
                                    </div>
                                    <div class="user-name">
                                        <strong><?= htmlspecialchars($fixture['team_one']); ?> vs <?= htmlspecialchars($fixture['team_two']); ?></strong>
                                        <small><?= htmlspecialchars($fixture['event_name'] ?? 'No Event'); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fixture-sport"><?= htmlspecialchars($fixture['sport_name'] ?? 'Unknown Sport'); ?></span>
                            </td>
                            <td>
                                <div class="fixture-datetime">
                                    <span><?= date('M d, Y', strtotime($fixture['fixture_date'])); ?></span>
                                    <small><?= date('h:i A', strtotime($fixture['fixture_time'])); ?></small>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($fixture['venue']); ?></td>
                            <td>
                                <?php if ($fixture['status'] === 'completed'): ?>
                                    <span class="fixture-score"><?= $fixture['score_team_one']; ?> – <?= $fixture['score_team_two']; ?></span>
                                <?php else: ?>
                                    <span class="fixture-score-pending">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fixture-status <?= $fixture['status']; ?>">
                                    <?= ucfirst($fixture['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_fixture.php?id=<?= (int)$fixture['fixture_id']; ?>" class="action-btn view-btn" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <a href="edit_fixture.php?id=<?= (int)$fixture['fixture_id']; ?>" class="action-btn edit-btn" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    <a href="delete_fixture.php?id=<?= (int)$fixture['fixture_id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this fixture?');"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-calendar-xmark"></i></div>
                        <h3>No Fixtures Found</h3>
                        <p>Try adjusting your filters or create a new fixture.</p>
                        <a href="add_fixture.php" class="empty-reset"><i class="fa-solid fa-plus"></i> Create Fixture</a>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>