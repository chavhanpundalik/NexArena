<?php
session_start();

/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

/* =========================================================
   USER DATA
========================================================= */

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';
$role      = $_SESSION['role'] ?? 'user';

/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "../db_connect.php";

/* =========================================================
   GET DARK MODE SETTING
========================================================= */

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

$dark_mode_class = ($dark_mode == 1) ? 'dark-mode' : '';
$data_theme = $dark_mode ? 'dark' : 'light';

/* =========================================================
   FETCH UPCOMING EVENTS
========================================================= */

$sql = "
    SELECT
        e.event_id,
        e.event_name,
        e.sport_id,
        e.sport_description,
        e.event_date,
        e.registration_start,
        e.registration_end,
        e.location,
        e.status,
        e.created_by,
        e.created_at,
        s.sport_name
    FROM events e
    LEFT JOIN sports s
        ON e.sport_id = s.sport_id
    WHERE
        e.event_date >= CURDATE()
    ORDER BY
        e.event_date ASC,
        e.registration_end ASC
";

$result = $conn->query($sql);

/* =========================================================
   DATABASE ERROR
========================================================= */

if (!$result) {
    die("Database Error: " . $conn->error);
}

/* =========================================================
   GET USER'S REGISTERED EVENTS
========================================================= */

$user_registered_events = [];
$registered_sql = "SELECT event_id FROM registrations WHERE user_id = ? AND status = 'confirmed'";
$registered_stmt = $conn->prepare($registered_sql);
$registered_stmt->bind_param("i", $user_id);
$registered_stmt->execute();
$registered_result = $registered_stmt->get_result();

while ($row = $registered_result->fetch_assoc()) {
    $user_registered_events[] = $row['event_id'];
}
$registered_stmt->close();

/* =========================================================
   GET PENDING REGISTRATIONS COUNT FOR SIDEBAR
========================================================= */

$pending_registrations_count = 0;
$pending_sql = "SELECT COUNT(*) as count FROM registrations WHERE user_id = ? AND status = 'pending'";
$pending_stmt = $conn->prepare($pending_sql);
if ($pending_stmt) {
    $pending_stmt->bind_param("i", $user_id);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    if ($pending_result) {
        $pending_row = $pending_result->fetch_assoc();
        $pending_registrations_count = (int)($pending_row['count'] ?? 0);
    }
    $pending_stmt->close();
}

/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function clean($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDate($date)
{
    if (empty($date)) {
        return 'Date not available';
    }
    return date("d M Y", strtotime($date));
}

function formatDateTime($date)
{
    if (empty($date)) {
        return 'Not available';
    }
    return date("d M Y, h:i A", strtotime($date));
}

function getEventStatus($event_status, $registration_start, $registration_end)
{
    $now = time();
    $start = !empty($registration_start) ? strtotime($registration_start) : null;
    $end = !empty($registration_end) ? strtotime($registration_end) : null;

    if (strtolower($event_status) === 'cancelled') {
        return "Cancelled";
    }

    if (strtolower($event_status) === 'completed' || strtolower($event_status) === 'closed') {
        return "Closed";
    }

    if ($end !== null && $now > $end) {
        return "Registration Closed";
    }

    if ($start !== null && $now < $start) {
        return "Registration Not Open";
    }

    if ($start !== null && $end !== null && $now >= $start && $now <= $end) {
        return "Open";
    }

    if ($start === null && $end === null) {
        return "Open";
    }

    return "Open";
}

// Get current page for sidebar active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $data_theme; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Upcoming Events | NexArena</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* =========================================================
           GLOBAL RESET
        ========================================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* =========================================================
           ROOT VARIABLES - LIGHT MODE (DEFAULT)
        ========================================================= */
        :root {
            --sidebar-width: 280px;
            --orange: #f97316;
            --orange-dark: #ea580c;
            --orange-light: #fb923c;
            --orange-bg: #fff7ed;
            --orange-border: #fed7aa;
            --orange-shadow: rgba(249, 115, 22, 0.25);
            
            /* Backgrounds */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #fafafa;
            
            /* Text Colors */
            --text-primary: #000000;
            --text-secondary: #1e293b;
            --text-muted: #64748b;
            --text-card: #000000;
            
            /* Borders */
            --border-color: #e2e8f0;
            --border-card: #e2e8f0;
            
            /* Shadows */
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 10px 30px rgba(0, 0, 0, 0.08);
            
            /* Badges */
            --badge-bg: #fff7ed;
            --badge-text: #f97316;
            
            /* Radius */
            --radius: 12px;
            
            /* Buttons */
            --btn-bg: #f8fafc;
            --btn-border: #e2e8f0;
            --btn-text: #000000;
        }

        /* =========================================================
           ROOT VARIABLES - DARK MODE
        ========================================================= */
        [data-theme="dark"] {
            /* Backgrounds */
            --bg-primary: #0f0f1a;
            --bg-secondary: #1a1a2e;
            --bg-card: #1a1a2e;
            --bg-card-hover: #242442;
            
            /* Text Colors */
            --text-primary: #ffffff;
            --text-secondary: #e2e8f0;
            --text-muted: #94a3b8;
            --text-card: #ffffff;
            
            /* Borders */
            --border-color: rgba(255, 255, 255, 0.06);
            --border-card: rgba(255, 255, 255, 0.08);
            
            /* Shadows */
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 10px 30px rgba(0, 0, 0, 0.5);
            
            /* Badges */
            --badge-bg: rgba(249, 115, 22, 0.15);
            --badge-text: #fb923c;
            
            /* Buttons */
            --btn-bg: #2a2a4a;
            --btn-border: rgba(255, 255, 255, 0.06);
            --btn-text: #ffffff;
        }

        /* =========================================================
           BODY STYLES
        ========================================================= */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            display: flex;
        }

        /* =========================================================
           SIDEBAR WRAPPER
        ========================================================= */
        .sidebar-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            flex-shrink: 0;
        }

        /* =========================================================
           MAIN CONTENT WRAPPER
        ========================================================= */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 0;
            background: var(--bg-primary);
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s ease;
            width: calc(100% - var(--sidebar-width));
            max-width: calc(100% - var(--sidebar-width));
            overflow-x: hidden;
        }

        .events-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 24px 60px;
            width: 100%;
        }

        /* =========================================================
           PAGE HEADER
        ========================================================= */
        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            flex-wrap: wrap;
            gap: 16px;
            transition: border-color 0.3s ease;
        }

        .header-content .header-label {
            display: inline-block;
            background: var(--badge-bg);
            color: var(--badge-text);
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .header-content h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        .header-content h1 i {
            color: var(--orange);
        }

        .header-content p {
            color: var(--text-muted);
            font-size: 15px;
            margin-top: 2px;
            transition: color 0.3s ease;
        }

        .header-icon {
            font-size: 48px;
            flex-shrink: 0;
        }

        /* =========================================================
           TOP BAR
        ========================================================= */
        .events-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .topbar-label {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: color 0.3s ease;
        }

        .events-topbar h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        .events-topbar h2 span {
            color: var(--orange);
        }

        .back-button {
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
        }

        .back-button:hover {
            border-color: var(--orange);
            color: var(--orange);
            transform: translateY(-2px);
        }

        /* =========================================================
           EVENTS GRID
        ========================================================= */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            width: 100%;
        }

        /* =========================================================
           EVENT CARD - FIXED DARK MODE
        ========================================================= */
        .event-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius);
            padding: 24px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            position: relative;
            width: 100%;
        }

        .event-card:hover {
            border-color: var(--orange-border);
            box-shadow: var(--shadow-hover);
            transform: translateY(-4px);
            background: var(--bg-card-hover);
        }

        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--orange);
            border-radius: var(--radius) var(--radius) 0 0;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .event-card:hover::before {
            opacity: 1;
        }

        .event-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .sport-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            background: var(--badge-bg);
            color: var(--badge-text);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .sport-badge .sport-icon {
            font-size: 16px;
        }

        .sport-badge .sport-name {
            transition: color 0.3s ease;
        }

        .event-status {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .event-status.open {
            background: #dcfce7;
            color: #16a34a;
        }

        .event-status.registration-closed {
            background: #fef2f2;
            color: #dc2626;
        }

        .event-status.registration-not-open {
            background: #fef3c7;
            color: #d97706;
        }

        .event-status.cancelled {
            background: #fef2f2;
            color: #dc2626;
        }

        .event-status.closed {
            background: #f1f5f9;
            color: #64748b;
        }

        /* Dark Mode Event Status Overrides */
        [data-theme="dark"] .event-status.open {
            background: rgba(34, 197, 94, 0.15);
            color: #34d399;
        }

        [data-theme="dark"] .event-status.registration-closed {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        [data-theme="dark"] .event-status.registration-not-open {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }

        [data-theme="dark"] .event-status.cancelled {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        [data-theme="dark"] .event-status.closed {
            background: rgba(100, 116, 139, 0.15);
            color: #94a3b8;
        }

        .event-title h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-card);
            margin-bottom: 8px;
            word-wrap: break-word;
            transition: color 0.3s ease;
        }

        .event-description {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 16px;
            flex: 1;
            word-wrap: break-word;
            transition: color 0.3s ease;
        }

        .event-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            min-width: 0;
        }

        .detail-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .detail-item div {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .detail-item small {
            color: var(--text-muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .detail-item strong {
            color: var(--text-card);
            font-size: 14px;
            font-weight: 600;
            word-wrap: break-word;
            transition: color 0.3s ease;
        }

        .event-action {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .details-button,
        .register-button,
        .closed-button,
        .not-open-button {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
            flex: 1;
            min-width: 120px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .details-button {
            background: var(--btn-bg);
            border: 1px solid var(--btn-border);
            color: var(--btn-text);
            transition: all 0.3s ease;
        }

        .details-button:hover {
            border-color: var(--orange);
            color: var(--orange);
        }

        .register-button {
            background: var(--orange);
            border: 1px solid var(--orange);
            color: #ffffff;
        }

        .register-button:hover {
            background: var(--orange-dark);
            border-color: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--orange-shadow);
        }

        .closed-button {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #dc2626;
            cursor: not-allowed;
        }

        .not-open-button {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #d97706;
            cursor: not-allowed;
        }

        /* Dark Mode Button Overrides */
        [data-theme="dark"] .closed-button {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        [data-theme="dark"] .not-open-button {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }

        .already-registered-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .already-registered-badge {
            background: rgba(34, 197, 94, 0.15);
            color: #34d399;
        }

        /* =========================================================
           NO EVENTS
        ========================================================= */
        .no-events {
            text-align: center;
            padding: 60px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius);
            transition: all 0.3s ease;
        }

        .no-events-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .no-events h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }

        .no-events p {
            color: var(--text-muted);
            font-size: 16px;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }

        /* =========================================================
           RESPONSIVE STYLES
        ========================================================= */

        /* Tablets & Small Laptops */
        @media (max-width: 1024px) {
            :root {
                --sidebar-width: 240px;
            }

            .events-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 20px;
            }
            
            .events-main {
                padding: 24px 20px 40px;
            }

            .header-content h1 {
                font-size: 28px;
            }
        }

        /* Tablets */
        @media (max-width: 820px) {
            :root {
                --sidebar-width: 220px;
            }

            .events-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 16px;
            }

            .event-card {
                padding: 18px;
            }

            .event-title h3 {
                font-size: 18px;
            }

            .event-details {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .detail-item strong {
                font-size: 13px;
            }

            .events-main {
                padding: 20px 16px 30px;
            }

            .header-content h1 {
                font-size: 24px;
            }

            .header-icon {
                font-size: 36px;
            }
        }

        /* Small Tablets & Large Phones */
        @media (max-width: 650px) {
            :root {
                --sidebar-width: 200px;
            }

            .sidebar-wrapper {
                width: var(--sidebar-width);
            }

            .main-content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
                max-width: calc(100% - var(--sidebar-width));
            }

            .events-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .event-card {
                padding: 16px;
            }

            .event-details {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .event-action {
                flex-direction: column;
            }

            .details-button,
            .register-button,
            .closed-button,
            .not-open-button {
                flex: none;
                width: 100%;
                min-width: unset;
                padding: 10px 14px;
                font-size: 13px;
            }

            .events-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .header-content h1 {
                font-size: 22px;
            }

            .header-content p {
                font-size: 13px;
            }

            .header-icon {
                font-size: 30px;
            }

            .events-topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .events-topbar h2 {
                font-size: 18px;
            }

            .back-button {
                padding: 6px 14px;
                font-size: 12px;
            }

            .events-main {
                padding: 16px 12px 24px;
            }

            .sport-badge {
                font-size: 11px;
                padding: 3px 12px;
            }

            .event-status {
                font-size: 10px;
                padding: 3px 12px;
            }
        }

        /* Mobile Phones */
        @media (max-width: 480px) {
            :root {
                --sidebar-width: 180px;
            }

            .sidebar-wrapper {
                width: var(--sidebar-width);
            }

            .main-content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
                max-width: calc(100% - var(--sidebar-width));
            }

            .events-main {
                padding: 12px 10px 20px;
            }

            .header-content h1 {
                font-size: 20px;
            }

            .header-content p {
                font-size: 12px;
            }

            .header-label {
                font-size: 9px;
                padding: 3px 10px;
            }

            .event-card {
                padding: 14px;
            }

            .event-title h3 {
                font-size: 17px;
            }

            .event-description {
                font-size: 13px;
            }

            .event-details {
                grid-template-columns: 1fr 1fr;
                gap: 6px;
            }

            .detail-item strong {
                font-size: 12px;
            }

            .detail-item small {
                font-size: 9px;
            }

            .detail-icon {
                font-size: 16px;
            }

            .events-topbar h2 {
                font-size: 17px;
            }

            .topbar-label {
                font-size: 9px;
            }

            .details-button,
            .register-button,
            .closed-button,
            .not-open-button {
                padding: 8px 12px;
                font-size: 12px;
            }

            .event-card-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }

        /* Very Small Phones */
        @media (max-width: 380px) {
            :root {
                --sidebar-width: 160px;
            }

            .sidebar-wrapper {
                width: var(--sidebar-width);
            }

            .main-content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
                max-width: calc(100% - var(--sidebar-width));
            }

            .events-main {
                padding: 10px 8px 16px;
            }

            .header-content h1 {
                font-size: 18px;
            }

            .event-card {
                padding: 12px;
            }

            .event-title h3 {
                font-size: 15px;
            }

            .event-details {
                grid-template-columns: 1fr;
                gap: 4px;
            }

            .detail-item strong {
                font-size: 11px;
            }

            .events-topbar h2 {
                font-size: 15px;
            }

            .back-button {
                padding: 5px 10px;
                font-size: 11px;
            }

            .details-button,
            .register-button,
            .closed-button,
            .not-open-button {
                padding: 6px 10px;
                font-size: 11px;
            }
        }

        /* Landscape phones */
        @media (max-height: 500px) and (orientation: landscape) {
            :root {
                --sidebar-width: 180px;
            }

            .sidebar-wrapper {
                width: var(--sidebar-width);
            }

            .main-content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
                max-width: calc(100% - var(--sidebar-width));
            }

            .events-main {
                padding: 12px 16px 20px;
            }

            .events-header {
                margin-bottom: 16px;
                padding-bottom: 12px;
            }

            .header-content h1 {
                font-size: 20px;
            }

            .header-icon {
                font-size: 28px;
            }

            .event-card {
                padding: 14px;
            }

            .events-grid {
                gap: 12px;
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Sidebar scrollbar styling */
        .sidebar-wrapper::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-wrapper::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        .sidebar-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--orange);
        }

        [data-theme="dark"] .sidebar-wrapper::-webkit-scrollbar-thumb {
            background: #4a4a6a;
        }

        [data-theme="dark"] .sidebar-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--orange);
        }
    </style>

</head>

<body>

    <!-- =========================================================
         SIDEBAR WRAPPER
    ========================================================= -->
    <div class="sidebar-wrapper">
        <?php include "sidebar.php"; ?>
    </div>

    <!-- =========================================================
         MAIN CONTENT
    ========================================================= -->
    <main class="main-content">

        <div class="events-main">

            <!-- PAGE HEADER -->
            <section class="events-header">
                <div class="header-content">
                    <span class="header-label">
                        <i class="fas fa-calendar-alt"></i> NEXARENA EVENTS
                    </span>
                    <h1>
                        <i class="fas fa-trophy"></i> Upcoming Events
                    </h1>
                    <p>
                        Discover upcoming sports events,
                        check event details and register to participate.
                    </p>
                </div>
                <div class="header-icon">
                    🏆
                </div>
            </section>

            <!-- EVENT COUNT -->
            <div class="events-topbar">
                <div>
                    <span class="topbar-label">
                        <i class="fas fa-list"></i> AVAILABLE EVENTS
                    </span>
                    <h2>
                        <span><?php echo $result->num_rows; ?></span>
                        Upcoming Event<?php echo ($result->num_rows != 1) ? 's' : ''; ?>
                    </h2>
                </div>
                <a href="dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>

            <!-- EVENTS -->
            <?php if ($result->num_rows > 0): ?>

                <section class="events-grid">

                    <?php while ($event = $result->fetch_assoc()): ?>

                        <?php
                        $eventStatus = getEventStatus(
                            $event['status'],
                            $event['registration_start'],
                            $event['registration_end']
                        );

                        $now = time();
                        $start = !empty($event['registration_start']) ? strtotime($event['registration_start']) : null;
                        $end = !empty($event['registration_end']) ? strtotime($event['registration_end']) : null;
                        
                        $registrationOpen = (
                            $eventStatus === "Open" &&
                            ($start === null || $now >= $start) &&
                            ($end === null || $now <= $end)
                        );

                        if ($start === null && $end === null && $eventStatus === "Open") {
                            $registrationOpen = true;
                        }

                        $statusClass = strtolower(
                            str_replace(' ', '-', $eventStatus)
                        );

                        $isRegistered = in_array($event['event_id'], $user_registered_events);
                        ?>

                        <article class="event-card">

                            <div class="event-card-top">
                                <div class="sport-badge">
                                    <span class="sport-icon">⚽</span>
                                    <span class="sport-name">
                                        <?php
                                        echo !empty($event['sport_name'])
                                            ? clean($event['sport_name'])
                                            : 'Sports';
                                        ?>
                                    </span>
                                </div>
                                <?php if ($isRegistered): ?>
                                    <span class="already-registered-badge">
                                        <i class="fas fa-check-circle"></i> Registered
                                    </span>
                                <?php else: ?>
                                    <span class="event-status <?php echo clean($statusClass); ?>">
                                        <?php echo clean($eventStatus); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="event-title">
                                <h3><?php echo clean($event['event_name']); ?></h3>
                            </div>

                            <p class="event-description">
                                <?php
                                if (!empty($event['sport_description'])) {
                                    echo clean($event['sport_description']);
                                } else {
                                    echo "Join this NexArena sports event and compete with other players.";
                                }
                                ?>
                            </p>

                            <div class="event-details">
                                <div class="detail-item">
                                    <span class="detail-icon">📅</span>
                                    <div>
                                        <small>Event Date</small>
                                        <strong><?php echo formatDate($event['event_date']); ?></strong>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">📍</span>
                                    <div>
                                        <small>Location</small>
                                        <strong>
                                            <?php
                                            echo !empty($event['location'])
                                                ? clean($event['location'])
                                                : 'Not available';
                                            ?>
                                        </strong>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">⏱</span>
                                    <div>
                                        <small>Registration</small>
                                        <strong>
                                            <?php
                                            if (!empty($event['registration_start']) && !empty($event['registration_end'])) {
                                                echo formatDateTime($event['registration_start']) . ' - ' . formatDateTime($event['registration_end']);
                                            } elseif (!empty($event['registration_start'])) {
                                                echo 'From: ' . formatDateTime($event['registration_start']);
                                            } elseif (!empty($event['registration_end'])) {
                                                echo 'Until: ' . formatDateTime($event['registration_end']);
                                            } else {
                                                echo 'Always Open';
                                            }
                                            ?>
                                        </strong>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">📊</span>
                                    <div>
                                        <small>Status</small>
                                        <strong><?php echo clean($eventStatus); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="event-action">
                                <a href="event_details.php?id=<?php echo (int)$event['event_id']; ?>" class="details-button">
                                    <i class="fas fa-eye"></i> View Details
                                </a>

                                <?php if ($isRegistered): ?>
                                    <span class="closed-button" style="background:#dcfce7;color:#16a34a;border-color:#86efac;cursor:default;">
                                        <i class="fas fa-check-circle"></i> Already Registered
                                    </span>
                                <?php elseif ($registrationOpen && $eventStatus !== "Cancelled" && $eventStatus !== "Closed"): ?>
                                    <a href="register_event.php?id=<?php echo (int)$event['event_id']; ?>" class="register-button">
                                        <i class="fas fa-check-circle"></i> Register Now
                                    </a>
                                <?php elseif ($eventStatus === "Registration Closed"): ?>
                                    <span class="closed-button">
                                        <i class="fas fa-times-circle"></i> Registration Closed
                                    </span>
                                <?php elseif ($eventStatus === "Registration Not Open"): ?>
                                    <span class="not-open-button">
                                        <i class="fas fa-clock"></i> Not Open Yet
                                    </span>
                                <?php elseif ($eventStatus === "Cancelled"): ?>
                                    <span class="closed-button">
                                        <i class="fas fa-ban"></i> Event Cancelled
                                    </span>
                                <?php else: ?>
                                    <span class="not-open-button">
                                        <i class="fas fa-clock"></i> Not Available
                                    </span>
                                <?php endif; ?>
                            </div>

                        </article>

                    <?php endwhile; ?>

                </section>

            <?php else: ?>

                <section class="no-events">
                    <div class="no-events-icon">🏟️</div>
                    <h2>No Upcoming Events</h2>
                    <p>There are currently no upcoming events. Please check again later.</p>
                    <a href="dashboard.php" class="back-button" style="display:inline-flex;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </section>

            <?php endif; ?>

        </div>

    </main>

</body>

</html>