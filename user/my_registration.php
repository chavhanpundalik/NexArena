<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

require_once "../db_connect.php";

$user_id = (int) $_SESSION['user_id'];

// Get dark mode setting
$dark_mode = 0;

// Check if user_settings table exists
$table_check = $conn->query("SHOW TABLES LIKE 'user_settings'");
$table_exists = ($table_check && $table_check->num_rows > 0);

if ($table_exists) {
    $settings_sql = "SELECT dark_mode FROM user_settings WHERE user_id = ?";
    $settings_stmt = $conn->prepare($settings_sql);
    
    if ($settings_stmt) {
        $settings_stmt->bind_param("i", $user_id);
        $settings_stmt->execute();
        $settings_result = $settings_stmt->get_result();
        
        if ($settings_result->num_rows > 0) {
            $settings_data = $settings_result->fetch_assoc();
            $dark_mode = $settings_data['dark_mode'] ?? 0;
        }
        $settings_stmt->close();
    }
}

// Get user's registrations
$sql = "
    SELECT 
        r.registration_id,
        r.event_id,
        r.status AS registration_status,
        r.registered_at,
        e.event_name,
        e.event_date,
        e.location,
        e.status AS event_status,
        s.sport_name
    FROM registrations r
    LEFT JOIN events e ON r.event_id = e.event_id
    LEFT JOIN sports s ON e.sport_id = s.sport_id
    WHERE r.user_id = ?
    ORDER BY r.registered_at DESC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $registrations = [];

    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    $stmt->close();
} else {
    $registrations = [];
    error_log("Registrations query failed: " . $conn->error);
}

// Get statistics
$totalRegistrations = count($registrations);
$pendingCount = 0;
$confirmedCount = 0;
$cancelledCount = 0;

foreach ($registrations as $reg) {
    $status = strtolower($reg['registration_status'] ?? '');
    if ($status === 'confirmed' || $status === 'approved') {
        $confirmedCount++;
    } elseif ($status === 'pending') {
        $pendingCount++;
    } elseif ($status === 'cancelled') {
        $cancelledCount++;
    }
}

// Cancel registration
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $reg_id = (int)$_GET['id'];
    $cancel_sql = "UPDATE registrations SET status = 'cancelled' WHERE registration_id = ? AND user_id = ?";
    $cancel_stmt = $conn->prepare($cancel_sql);
    
    if ($cancel_stmt) {
        $cancel_stmt->bind_param("ii", $reg_id, $user_id);
        
        if ($cancel_stmt->execute()) {
            header("Location: my_registration.php?cancelled=1");
            exit();
        }
        $cancel_stmt->close();
    }
}

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    if (empty($date)) return 'Not available';
    return date("d M Y", strtotime($date));
}

function getStatusBadge($status) {
    $status = strtolower($status ?? 'pending');
    $icons = [
        'confirmed' => 'fa-check-circle',
        'approved' => 'fa-check-circle',
        'pending' => 'fa-clock',
        'cancelled' => 'fa-times-circle'
    ];
    $icon = $icons[$status] ?? 'fa-clock';
    return '<span class="status-badge ' . $status . '"><i class="fas ' . $icon . '"></i> ' . ucfirst($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $dark_mode ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registrations | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/theme.css">
    <style>
        /* =========================================================
           RESET & BASE
        ========================================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* =========================================================
           CSS VARIABLES
        ========================================================= */
        :root {
            --orange: #f97316;
            --orange-dark: #ea580c;
            --orange-light: #fb923c;
            --orange-bg: #fff7ed;
            --orange-border: #fed7aa;
            --orange-shadow: rgba(249, 115, 22, 0.25);
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #000000;
            --text-secondary: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 10px 30px rgba(0, 0, 0, 0.08);
            --radius: 12px;
        }

        [data-theme="dark"] {
            --bg-primary: #0f0f1a;
            --bg-secondary: #1a1a2e;
            --bg-card: #1a1a2e;
            --text-primary: #ffffff;
            --text-secondary: #e2e8f0;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.06);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        /* =========================================================
           BODY - 80px LEFT MARGIN FOR ALL SCREENS
        ========================================================= */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-primary) !important;
            color: var(--text-primary) !important;
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
            padding-left: 80px;
        }

        /* =========================================================
           MAIN CONTENT WRAPPER
        ========================================================= */
        .registrations-main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 30px 60px 30px;
            width: 100%;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .registrations-main {
                padding: 20px 16px 60px;
            }
        }

        /* =========================================================
           PAGE HEADER
        ========================================================= */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: var(--orange);
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 2px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .back-link:hover {
            border-color: var(--orange);
            color: var(--orange);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .back-link {
                white-space: normal;
            }
        }

        /* =========================================================
           STATS GRID
        ========================================================= */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 16px 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--orange-border);
            box-shadow: var(--shadow-hover);
        }

        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .stat-card .stat-number.orange { color: var(--orange); }
        .stat-card .stat-number.green { color: #22c55e; }
        .stat-card .stat-number.yellow { color: #f59e0b; }
        .stat-card .stat-number.red { color: #ef4444; }

        .stat-card .stat-label {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .stat-card {
                padding: 12px 14px;
            }

            .stat-card .stat-number {
                font-size: 22px;
            }
        }

        /* =========================================================
           ALERT
        ========================================================= */
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

        [data-theme="dark"] .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
            color: #34d399;
        }

        /* =========================================================
           REGISTRATIONS GRID
        ========================================================= */
        .registrations-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .registration-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px 24px;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .registration-card:hover {
            border-color: var(--orange-border);
            box-shadow: var(--shadow-hover);
        }

        .registration-card .event-info {
            flex: 1;
            min-width: 200px;
        }

        .registration-card .event-info .event-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .registration-card .event-info .event-name i {
            color: var(--orange);
        }

        .registration-card .event-info .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 20px;
            color: var(--text-muted);
            font-size: 13px;
        }

        .registration-card .event-info .event-meta i {
            color: var(--orange);
            width: 16px;
        }

        .registration-card .event-status-info {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        /* =========================================================
           STATUS BADGE
        ========================================================= */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-badge.confirmed {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.approved {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-badge.cancelled {
            background: #fef2f2;
            color: #dc2626;
        }

        [data-theme="dark"] .status-badge.confirmed {
            background: rgba(34, 197, 94, 0.15);
            color: #34d399;
        }

        [data-theme="dark"] .status-badge.approved {
            background: rgba(34, 197, 94, 0.15);
            color: #34d399;
        }

        [data-theme="dark"] .status-badge.pending {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }

        [data-theme="dark"] .status-badge.cancelled {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        /* =========================================================
           ACTION BUTTONS
        ========================================================= */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 14px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .action-btn.view {
            border-color: var(--orange);
            color: var(--orange);
        }

        .action-btn.view:hover {
            background: var(--orange);
            color: #fff;
        }

        .action-btn.cancel {
            border-color: #ef4444;
            color: #ef4444;
        }

        .action-btn.cancel:hover {
            background: #ef4444;
            color: #fff;
        }

        .action-btn i {
            font-size: 13px;
        }

        .registered-date {
            color: var(--text-muted);
            font-size: 12px;
        }

        .registered-date i {
            color: var(--orange);
        }

        /* =========================================================
           EMPTY STATE
        ========================================================= */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
        }

        .empty-state i {
            font-size: 56px;
            color: #d1d5db;
            margin-bottom: 16px;
            display: block;
        }

        .empty-state h3 {
            color: var(--text-primary);
            font-size: 20px;
            margin-bottom: 8px;
        }

        .empty-state .btn-orange {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-top: 12px;
        }

        .empty-state .btn-orange:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--orange-shadow);
        }

        /* =========================================================
           RESPONSIVE - TABLETS & MOBILE
        ========================================================= */
        @media (max-width: 1024px) {
            .registration-card {
                padding: 16px 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            body {
                padding-left: 80px; /* Keep 80px on mobile too */
            }

            .registration-card {
                flex-direction: column;
                align-items: flex-start;
                padding: 16px;
            }

            .registration-card .event-info {
                width: 100%;
                min-width: unset;
            }

            .registration-card .event-info .event-name {
                font-size: 16px;
            }

            .registration-card .event-info .event-meta {
                flex-direction: column;
                gap: 6px;
            }

            .registration-card .event-status-info {
                width: 100%;
                flex-wrap: wrap;
                gap: 12px;
                justify-content: space-between;
            }

            .action-buttons {
                flex: 1;
                justify-content: flex-end;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .stat-card .stat-number {
                font-size: 22px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding-left: 80px; /* Keep 80px on mobile too */
            }

            .registrations-main {
                padding: 16px 12px 60px 12px;
            }

            .registration-card {
                padding: 14px;
            }

            .registration-card .event-info .event-meta {
                font-size: 12px;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
                gap: 6px;
            }

            .action-btn {
                justify-content: center;
                width: 100%;
                padding: 8px 14px;
            }

            .status-badge {
                font-size: 12px;
                padding: 4px 12px;
            }

            .registered-date {
                font-size: 11px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .stat-card {
                padding: 10px 12px;
            }

            .stat-card .stat-number {
                font-size: 20px;
            }

            .stat-card .stat-label {
                font-size: 10px;
            }
        }
    </style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

<?php include "sidebar.php"; ?>

<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="registrations-main">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1><i class="fas fa-clipboard-list"></i> My Registrations</h1>
            <p>Manage all your event registrations</p>
        </div>
        <a href="events.php" class="back-link">
            <i class="fas fa-calendar-alt"></i> Browse Events
        </a>
    </div>

    <?php if (isset($_GET['cancelled']) && $_GET['cancelled'] == 1): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Registration cancelled successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'registered'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> You have successfully registered for the event!
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number orange"><?= number_format($totalRegistrations); ?></div>
            <div class="stat-label">Total Registrations</div>
        </div>
        <div class="stat-card">
            <div class="stat-number green"><?= number_format($confirmedCount); ?></div>
            <div class="stat-label">Confirmed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number yellow"><?= number_format($pendingCount); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-number red"><?= number_format($cancelledCount); ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>

    <!-- Registrations List -->
    <?php if (!empty($registrations)): ?>
        <div class="registrations-grid">
            <?php foreach ($registrations as $reg): ?>
                <div class="registration-card">
                    <div class="event-info">
                        <div class="event-name">
                            <i class="fas fa-calendar-alt"></i> <?= clean($reg['event_name'] ?? 'Unknown Event'); ?>
                        </div>
                        <div class="event-meta">
                            <span><i class="fas fa-tag"></i> <?= clean($reg['sport_name'] ?? 'General'); ?></span>
                            <span><i class="fas fa-calendar-day"></i> <?= formatDate($reg['event_date']); ?></span>
                            <?php if (!empty($reg['location'])): ?>
                                <span><i class="fas fa-map-marker-alt"></i> <?= clean($reg['location']); ?></span>
                            <?php endif; ?>
                            <span class="registered-date">
                                <i class="fas fa-clock"></i> Registered: <?= date('d M Y, h:i A', strtotime($reg['registered_at'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="event-status-info">
                        <?= getStatusBadge($reg['registration_status'] ?? 'pending'); ?>
                        <div class="action-buttons">
                            <a href="event_details.php?id=<?= $reg['event_id']; ?>" class="action-btn view" title="View Event">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <?php if (strtolower($reg['registration_status'] ?? '') !== 'cancelled'): ?>
                                <a href="my_registration.php?cancel=1&id=<?= $reg['registration_id']; ?>" 
                                   class="action-btn cancel" 
                                   onclick="return confirm('Are you sure you want to cancel this registration?');" 
                                   title="Cancel Registration">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>No Registrations Yet</h3>
            <p>You haven't registered for any events yet. Browse events and register now!</p>
            <a href="events.php" class="btn-orange">
                <i class="fas fa-calendar-alt"></i> Browse Events
            </a>
        </div>
    <?php endif; ?>

</main>

<!-- Theme JavaScript -->
<script src="assets/theme.js"></script>

</body>
</html>