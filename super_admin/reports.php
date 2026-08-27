<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Get date filters from GET ---
$report_type = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/reports.css">
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <!-- Page Header -->
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-file-lines"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Reports &amp; Exports</h1>
                <p>Generate and export data for analysis.</p>
            </div>
        </div>
    </section>

    <!-- Date Filter Form -->
    <section class="filter-card">
        <form method="GET" class="filter-form" action="export_csv.php">
            <input type="hidden" name="action" value="export">
            <div class="select-box" style="flex:1;">
                <i class="fa-solid fa-table"></i>
                <select name="report_type" required>
                    <option value="">-- Select Report --</option>
                    <option value="users" <?= $report_type === 'users' ? 'selected' : ''; ?>>Users</option>
                    <option value="events" <?= $report_type === 'events' ? 'selected' : ''; ?>>Events</option>
                    <option value="registrations" <?= $report_type === 'registrations' ? 'selected' : ''; ?>>Registrations</option>
                    <option value="fixtures" <?= $report_type === 'fixtures' ? 'selected' : ''; ?>>Fixtures</option>
                    <option value="leaderboard" <?= $report_type === 'leaderboard' ? 'selected' : ''; ?>>Leaderboard</option>
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
            <button type="submit" class="filter-btn" style="background:var(--orange);color:#fff;">
                <i class="fa-solid fa-download"></i> Export CSV
            </button>
            <a href="reports.php" class="reset-btn"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </form>
    </section>

    <!-- Quick Export Cards -->
    <section class="report-cards">
        <div class="report-card" onclick="window.location.href='export_csv.php?action=export&report_type=users'">
            <div class="report-icon"><i class="fa-solid fa-users"></i></div>
            <div class="report-info">
                <h3>Users</h3>
                <p>Export all user data</p>
            </div>
            <div class="report-action"><i class="fa-solid fa-arrow-right"></i></div>
        </div>
        <div class="report-card" onclick="window.location.href='export_csv.php?action=export&report_type=events'">
            <div class="report-icon"><i class="fa-regular fa-calendar"></i></div>
            <div class="report-info">
                <h3>Events</h3>
                <p>Export event details</p>
            </div>
            <div class="report-action"><i class="fa-solid fa-arrow-right"></i></div>
        </div>
        <div class="report-card" onclick="window.location.href='export_csv.php?action=export&report_type=registrations'">
            <div class="report-icon"><i class="fa-regular fa-clipboard"></i></div>
            <div class="report-info">
                <h3>Registrations</h3>
                <p>Export registration data</p>
            </div>
            <div class="report-action"><i class="fa-solid fa-arrow-right"></i></div>
        </div>
        <div class="report-card" onclick="window.location.href='export_csv.php?action=export&report_type=fixtures'">
            <div class="report-icon"><i class="fa-regular fa-calendar-check"></i></div>
            <div class="report-info">
                <h3>Fixtures</h3>
                <p>Export fixture schedules</p>
            </div>
            <div class="report-action"><i class="fa-solid fa-arrow-right"></i></div>
        </div>
        <div class="report-card" onclick="window.location.href='export_csv.php?action=export&report_type=leaderboard'">
            <div class="report-icon"><i class="fa-solid fa-trophy"></i></div>
            <div class="report-info">
                <h3>Leaderboard</h3>
                <p>Export team rankings</p>
            </div>
            <div class="report-action"><i class="fa-solid fa-arrow-right"></i></div>
        </div>
    </section>

</main>
</body>
</html>