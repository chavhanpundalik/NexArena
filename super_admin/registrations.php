<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Filters ---
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// --- Stats ---
$totalRegistrations = 0;
$pendingRegistrations = 0;
$confirmedRegistrations = 0;
$cancelledRegistrations = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM registrations");
if ($result) { $row = $result->fetch_assoc(); $totalRegistrations = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM registrations WHERE status = 'pending'");
if ($result) { $row = $result->fetch_assoc(); $pendingRegistrations = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM registrations WHERE status = 'confirmed'");
if ($result) { $row = $result->fetch_assoc(); $confirmedRegistrations = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM registrations WHERE status = 'cancelled'");
if ($result) { $row = $result->fetch_assoc(); $cancelledRegistrations = (int)$row['total']; }

// --- Get all events for filter dropdown ---
$events = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC");
$eventsList = [];
if ($events) {
    while ($row = $events->fetch_assoc()) {
        $eventsList[] = $row;
    }
}

// --- Build query using correct table names ---
$sql = "SELECT 
            r.registration_id,
            r.event_id,
            r.user_id,
            r.status,
            r.registered_at,
            e.event_name,
            e.event_date,
            e.location,
            u.full_name AS user_name,
            u.username,
            u.email,
            s.sport_name
        FROM registrations r
        LEFT JOIN events e ON r.event_id = e.event_id
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN sports s ON e.sport_id = s.sport_id
        WHERE 1=1";

$params = [];
$types = '';

if ($event_id > 0) {
    $sql .= " AND r.event_id = ?";
    $params[] = $event_id;
    $types .= "i";
}
if ($status !== '') {
    $sql .= " AND r.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY r.registered_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) { die("Query error: " . $conn->error); }
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$registrations = [];
while ($row = $result->fetch_assoc()) {
    $registrations[] = $row;
}
$stmt->close();

// --- Update registration status ---
if (isset($_POST['update_status']) && isset($_POST['registration_id']) && isset($_POST['new_status'])) {
    $reg_id = (int)$_POST['registration_id'];
    $new_status = $_POST['new_status'];
    
    $update_sql = "UPDATE registrations SET status = ? WHERE registration_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $reg_id);
    
    if ($update_stmt->execute()) {
        // Refresh the page to show updated data
        header("Location: registrations.php?updated=1");
        exit();
    }
    $update_stmt->close();
}

// --- Delete registration ---
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $reg_id = (int)$_GET['id'];
    $delete_sql = "DELETE FROM registrations WHERE registration_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $reg_id);
    
    if ($delete_stmt->execute()) {
        header("Location: registrations.php?deleted=1");
        exit();
    }
    $delete_stmt->close();
}

// DO NOT CLOSE THE CONNECTION HERE - Sidebar needs it
// $conn->close();

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrations | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        /* =========================================================
           ORANGE THEME - White Background, Black Text, Orange Accents
        ========================================================= */
        :root {
            --orange: #f97316;
            --orange-dark: #ea580c;
            --orange-light: #fb923c;
            --orange-bg: #fff7ed;
            --orange-border: #fed7aa;
            --orange-shadow: rgba(249, 115, 22, 0.25);
            --white: #ffffff;
            --black: #000000;
            --gray-dark: #1e293b;
            --gray: #64748b;
            --gray-light: #94a3b8;
            --border: #e2e8f0;
            --border-dark: #cbd5e1;
        }

        body {
            background: var(--white) !important;
            color: var(--black) !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .users-main {
            padding: 24px 32px;
            background: var(--white) !important;
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            background: var(--orange-bg);
            color: var(--orange);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .page-label {
            color: var(--gray);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .page-header h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--black);
            margin: 0;
        }

        .page-header p {
            color: var(--gray);
            font-size: 14px;
            margin: 0;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--orange-border);
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: var(--orange-bg);
            color: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .stat-content span {
            color: var(--gray);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .stat-content strong {
            font-size: 24px;
            font-weight: 800;
            color: var(--black);
        }

        /* Filters */
        .filter-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 24px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .select-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            padding: 0 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .select-box i {
            color: var(--gray);
            font-size: 14px;
        }

        .select-box select {
            padding: 8px 12px 8px 0;
            border: none;
            background: transparent;
            color: var(--black);
            font-size: 14px;
            cursor: pointer;
            min-width: 120px;
        }

        .select-box select:focus {
            outline: none;
        }

        .filter-btn {
            background: var(--orange);
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .filter-btn:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--orange-shadow);
        }

        .reset-btn {
            background: #f1f5f9;
            color: var(--gray-dark);
            border: 1px solid var(--border);
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .reset-btn:hover {
            background: #e2e8f0;
        }

        /* Table */
        .users-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .table-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-label {
            color: var(--gray);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--black);
            margin: 0;
        }

        .user-count {
            color: var(--gray);
            font-size: 14px;
            padding: 4px 14px;
            background: var(--orange-bg);
            border-radius: 20px;
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
            border-bottom: 2px solid var(--border);
        }

        th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray);
        }

        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--black);
            font-size: 14px;
            vertical-align: middle;
        }

        tr:hover td {
            background: var(--orange-bg);
        }

        /* Status Badges */
        .reg-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .reg-status.confirmed {
            background: #dcfce7;
            color: #16a34a;
        }

        .reg-status.pending {
            background: #fef3c7;
            color: #d97706;
        }

        .reg-status.cancelled {
            background: #fef2f2;
            color: #dc2626;
        }

        .reg-status.approved {
            background: #dcfce7;
            color: #16a34a;
        }

        .reg-status.rejected {
            background: #fef2f2;
            color: #dc2626;
        }

        .event-name {
            font-weight: 600;
            color: var(--black);
        }

        .event-meta {
            color: var(--gray);
            font-size: 12px;
            display: block;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--orange-bg);
            color: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            flex-shrink: 0;
        }

        .user-name {
            font-weight: 600;
            color: var(--black);
        }

        .user-email {
            color: var(--gray);
            font-size: 11px;
            display: block;
        }

        /* Actions */
        .action-buttons {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .view-btn:hover {
            border-color: var(--orange);
            color: var(--orange);
            background: var(--orange-bg);
        }

        .edit-btn:hover {
            border-color: #22c55e;
            color: #22c55e;
            background: #dcfce7;
        }

        .delete-btn:hover {
            border-color: #dc2626;
            color: #dc2626;
            background: #fef2f2;
        }

        /* Status Select */
        .status-select {
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--black);
            font-size: 13px;
            cursor: pointer;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--orange);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-icon {
            font-size: 56px;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            color: var(--black);
            font-size: 20px;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--gray);
        }

        /* Alert */
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #16a34a;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #dc2626;
        }

        .alert-info {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            color: #2563eb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .users-main {
                padding: 16px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .select-box {
                width: 100%;
            }

            .select-box select {
                width: 100%;
            }

            .filter-btn, .reset-btn {
                justify-content: center;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }

            td, th {
                padding: 8px 12px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Registration status updated successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Registration deleted successfully!
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-regular fa-clipboard-list"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Event Registrations</h1>
                <p>Manage and approve user registrations for events.</p>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-regular fa-clipboard"></i></div>
            <div class="stat-content">
                <span>Total</span>
                <strong><?= number_format($totalRegistrations); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #d97706;">
            <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-regular fa-clock"></i></div>
            <div class="stat-content">
                <span>Pending</span>
                <strong><?= number_format($pendingRegistrations); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #16a34a;">
            <div class="stat-icon" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-check-circle"></i></div>
            <div class="stat-content">
                <span>Confirmed</span>
                <strong><?= number_format($confirmedRegistrations); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #dc2626;">
            <div class="stat-icon" style="background:#fecaca; color:#dc2626;"><i class="fa-solid fa-times-circle"></i></div>
            <div class="stat-content">
                <span>Cancelled</span>
                <strong><?= number_format($cancelledRegistrations); ?></strong>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="filter-card">
        <form method="GET" class="filter-form">
            <div class="select-box">
                <i class="fa-regular fa-calendar"></i>
                <select name="event_id">
                    <option value="0">All Events</option>
                    <?php foreach ($eventsList as $ev): ?>
                        <option value="<?= $ev['event_id']; ?>" <?= $event_id == $ev['event_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($ev['event_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="select-box">
                <i class="fa-solid fa-circle-half-stroke"></i>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="registrations.php" class="reset-btn"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </form>
    </section>

    <!-- Registrations Table -->
    <section class="users-card">
        <div class="table-header">
            <div>
                <span class="section-label">REGISTRATION DIRECTORY</span>
                <h2>All Registrations</h2>
            </div>
            <span class="user-count"><?= count($registrations); ?> registration<?= count($registrations) != 1 ? 's' : ''; ?></span>
        </div>
        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Event</th>
                        <th>Sport</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($registrations)): ?>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($reg['full_name'] ?? $reg['username'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="user-name"><?= htmlspecialchars($reg['full_name'] ?? $reg['username'] ?? 'Unknown'); ?></div>
                                        <span class="user-email"><?= htmlspecialchars($reg['email'] ?? ''); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="event-name"><?= htmlspecialchars($reg['event_name'] ?? 'Unknown Event'); ?></div>
                                <?php if (!empty($reg['location'])): ?>
                                    <span class="event-meta"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($reg['location']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;background:var(--orange-bg);color:var(--orange);border-radius:12px;font-size:12px;font-weight:600;">
                                    <i class="fas fa-running"></i>
                                    <?= htmlspecialchars($reg['sport_name'] ?? 'General'); ?>
                                </span>
                            </td>
                            <td>
                                <?= date('d M Y', strtotime($reg['registered_at'] ?? 'now')); ?>
                                <br><small style="color:var(--gray);font-size:11px;"><?= date('h:i A', strtotime($reg['registered_at'] ?? 'now')); ?></small>
                            </td>
                            <td>
                                <span class="reg-status <?= $reg['status'] ?? 'pending'; ?>">
                                    <i class="fas fa-<?= 
                                        ($reg['status'] ?? 'pending') == 'confirmed' ? 'check-circle' : 
                                        (($reg['status'] ?? 'pending') == 'pending' ? 'clock' : 'times-circle'); 
                                    ?>"></i>
                                    <?= ucfirst($reg['status'] ?? 'Pending'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <!-- Update Status Form -->
                                    <form method="POST" style="display:inline-flex;gap:4px;align-items:center;">
                                        <input type="hidden" name="registration_id" value="<?= $reg['registration_id']; ?>">
                                        <select name="new_status" class="status-select" onchange="this.form.submit()">
                                            <option value="pending" <?= ($reg['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?= ($reg['status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="cancelled" <?= ($reg['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="action-btn view-btn" title="Update Status">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                    
                                    <a href="view_registration.php?id=<?= $reg['registration_id']; ?>" class="action-btn view-btn" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <a href="registrations.php?delete=1&id=<?= $reg['registration_id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this registration?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-clipboard"></i></div>
                        <h3>No Registrations Found</h3>
                        <p>Try adjusting your filters or wait for users to register.</p>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>