<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Search & Filter ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// --- Stats ---
$totalEvents = 0;
$upcomingEvents = 0;
$ongoingEvents = 0;
$completedEvents = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($result) { $row = $result->fetch_assoc(); $totalEvents = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM events WHERE status = 'upcoming'");
if ($result) { $row = $result->fetch_assoc(); $upcomingEvents = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM events WHERE status = 'ongoing'");
if ($result) { $row = $result->fetch_assoc(); $ongoingEvents = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM events WHERE status = 'completed'");
if ($result) { $row = $result->fetch_assoc(); $completedEvents = (int)$row['total']; }

// --- Build query ---
$sql = "SELECT event_id, event_name, description, event_date, location, status, created_at FROM events WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (event_name LIKE ? OR description LIKE ? OR location LIKE ?)";
    $searchValue = "%$search%";
    $params[] = $searchValue; $params[] = $searchValue; $params[] = $searchValue;
    $types .= "sss";
}
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}
if ($date_from !== '') {
    $sql .= " AND event_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if ($date_to !== '') {
    $sql .= " AND event_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY event_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) { die("Query error: " . $conn->error); }
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$eventsResult = $stmt->get_result();
$events = $eventsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Management | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        .event-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
        }
        .event-status.upcoming { background: #fef3c7; color: #d97706; }
        .event-status.ongoing { background: #dbeafe; color: #2563eb; }
        .event-status.completed { background: #d1fae5; color: #059669; }
        .event-status.cancelled { background: #fecaca; color: #dc2626; }
        .event-date { font-size: 13px; color: #71717a; }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <!-- Page Header -->
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-calendar-days"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Events Management</h1>
                <p>Organize and oversee all events on the platform.</p>
            </div>
        </div>
        <a href="add_event.php" class="add-user-btn">
            <i class="fa-solid fa-calendar-plus"></i> Create Event
        </a>
    </section>

    <!-- Stats -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-calendar"></i></div>
            <div class="stat-content">
                <span>Total Events</span>
                <strong><?= number_format($totalEvents); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #d97706;">
            <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-regular fa-clock"></i></div>
            <div class="stat-content">
                <span>Upcoming</span>
                <strong><?= number_format($upcomingEvents); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #2563eb;">
            <div class="stat-icon" style="background:#dbeafe; color:#2563eb;"><i class="fa-solid fa-play"></i></div>
            <div class="stat-content">
                <span>Ongoing</span>
                <strong><?= number_format($ongoingEvents); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #059669;">
            <div class="stat-icon" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-check"></i></div>
            <div class="stat-content">
                <span>Completed</span>
                <strong><?= number_format($completedEvents); ?></strong>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="filter-card">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search events by name, description, or location..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="select-box">
                <i class="fa-solid fa-circle-half-stroke"></i>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="upcoming" <?= $status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
            <a href="events.php" class="reset-btn"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </form>
    </section>

    <!-- Events Table -->
    <section class="users-card">
        <div class="table-header">
            <div>
                <span class="section-label">EVENT DIRECTORY</span>
                <h2>All Events</h2>
            </div>
            <span class="user-count"><?= count($events); ?> event<?= count($events) != 1 ? 's' : ''; ?></span>
        </div>
        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="avatar" style="background:var(--orange); color:#fff; font-size:14px;">
                                        <i class="fa-regular fa-calendar"></i>
                                    </div>
                                    <div class="user-name">
                                        <strong><?= htmlspecialchars($event['event_name']); ?></strong>
                                        <small>ID: #<?= (int)$event['event_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?= htmlspecialchars(substr($event['description'] ?? '', 0, 60)) . (strlen($event['description'] ?? '') > 60 ? '...' : ''); ?>
                            </td>
                            <td>
                                <span class="event-date"><?= date('M d, Y', strtotime($event['event_date'])); ?></span>
                            </td>
                            <td><?= htmlspecialchars($event['location']); ?></td>
                            <td>
                                <span class="event-status <?= $event['status']; ?>">
                                    <?= ucfirst($event['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_event.php?id=<?= (int)$event['event_id']; ?>" class="action-btn view-btn" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <a href="edit_event.php?id=<?= (int)$event['event_id']; ?>" class="action-btn edit-btn" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    <a href="delete_event.php?id=<?= (int)$event['event_id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this event?');"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-calendar-xmark"></i></div>
                        <h3>No Events Found</h3>
                        <p>Try adjusting your filters or create a new event.</p>
                        <a href="add_event.php" class="empty-reset"><i class="fa-solid fa-plus"></i> Create Event</a>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>