<?php

session_start();

/* =========================================================
   DATABASE - MOVED TO THE TOP (BEFORE ANY DB OPERATIONS)
========================================================= */

require_once "../db_connect.php";

/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================================================
   GET DARK MODE SETTING
========================================================= */

$dark_mode = 0;
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

$dark_mode_class = ($dark_mode == 1) ? 'dark-mode' : '';

/* =========================================================
   GET EVENT ID
========================================================= */

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    header("Location: events.php?error=invalid_event");
    exit();
}

/* =========================================================
   FETCH EVENT
========================================================= */

$sql = $conn->prepare("
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
        s.sport_name
    FROM events e
    LEFT JOIN sports s
        ON e.sport_id = s.sport_id
    WHERE e.event_id = ?
    LIMIT 1
");

if (!$sql) {
    die("Database Prepare Error: " . $conn->error);
}

$sql->bind_param("i", $event_id);

if (!$sql->execute()) {
    die("Database Execute Error: " . $sql->error);
}

$result = $sql->get_result();

if ($result->num_rows !== 1) {
    $sql->close();
    header("Location: events.php?error=event_not_found");
    exit();
}

$event = $result->fetch_assoc();
$sql->close();

/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function clean($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatDate($date)
{
    if (empty($date)) {
        return "Not available";
    }
    return date("d M Y", strtotime($date));
}

function formatDateTime($date)
{
    if (empty($date)) {
        return "Not available";
    }
    return date("d M Y, h:i A", strtotime($date));
}

/* =========================================================
   EVENT STATUS
========================================================= */

$event_status = strtolower(
    trim($event['status'] ?? '')
);

$registration_start = !empty($event['registration_start'])
    ? strtotime($event['registration_start'])
    : null;

$registration_end = !empty($event['registration_end'])
    ? strtotime($event['registration_end'])
    : null;

/* =========================================================
   DETERMINE REGISTRATION STATUS
========================================================= */

if ($event_status === 'cancelled') {
    $registration_status = "Cancelled";
} elseif (
    $registration_end !== null &&
    time() > $registration_end
) {
    $registration_status = "Registration Closed";
} elseif (
    $registration_start !== null &&
    time() < $registration_start
) {
    $registration_status = "Registration Not Open";
} elseif (
    $registration_start !== null &&
    $registration_end !== null &&
    time() >= $registration_start &&
    time() <= $registration_end
) {
    $registration_status = "Open";
} else {
    $registration_status = "Open";
}

/* =========================================================
   STATUS CLASS
========================================================= */

$status_class = strtolower(
    str_replace(
        ' ',
        '-',
        $registration_status
    )
);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($event['event_name']); ?> | NexArena</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ============================================================
           ROOT VARIABLES - LIGHT MODE (DEFAULT)
           ============================================================ */
        :root {
            /* Backgrounds */
            --bg-body: #f1f5f9;
            --bg-container: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --bg-hero-right: #f8fafc;
            --bg-description: #f8fafc;
            --bg-registration: #ffedd5;
            --bg-registration-border: rgba(249, 115, 22, 0.2);
            
            /* Text */
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --text-white: #ffffff;
            
            /* Borders */
            --border-color: #e5e7eb;
            --border-light: #f1f5f9;
            
            /* Orange Theme */
            --orange: #f97316;
            --orange-dark: #ea580c;
            --orange-hover: #c2410c;
            --orange-light: #ffedd5;
            --orange-gradient: linear-gradient(135deg, #f97316, #ea580c);
            
            /* Purple Accent */
            --purple: #8b5cf6;
            --purple-light: #ede9fe;
            
            /* Status Colors */
            --success: #22c55e;
            --success-bg: #dcfce7;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --warning: #f59e0b;
            --warning-bg: #fef3c7;
            --gray: #6b7280;
            --gray-bg: #f3f4f6;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.07);
            --shadow-xl: 0 20px 50px rgba(0, 0, 0, 0.1);
            --shadow-orange: 0 8px 20px rgba(249, 115, 22, 0.3);
            --shadow-orange-hover: 0 12px 30px rgba(249, 115, 22, 0.4);
            
            /* Sidebar Width */
            --sidebar-width: 80px;
            
            /* Transitions */
            --transition: 0.3s ease;
        }

        /* ============================================================
           DARK MODE VARIABLES
           ============================================================ */
        body.dark-mode {
            --bg-body: #0f0f0f;
            --bg-container: #1a1a2e;
            --bg-secondary: #16213e;
            --bg-card: #1a1a2e;
            --bg-hero-right: #16213e;
            --bg-description: #16213e;
            --bg-registration: #2d1f0e;
            --bg-registration-border: rgba(249, 115, 22, 0.15);
            
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --text-white: #ffffff;
            
            --border-color: #2d3748;
            --border-light: #1e293b;
            
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.5);
            --shadow-orange: 0 8px 20px rgba(249, 115, 22, 0.2);
            --shadow-orange-hover: 0 12px 30px rgba(249, 115, 22, 0.3);
            
            --orange-light: #2d1f0e;
            
            --success-bg: #064e3b;
            --danger-bg: #4c0519;
            --warning-bg: #451a03;
            --gray-bg: #1e293b;
        }

        /* ============================================================
           RESET & BASE
           ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            transition: background var(--transition), color var(--transition);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* ============================================================
           MAIN LAYOUT WITH SIDEBAR MARGIN
           ============================================================ */
        .event-details-main {
            padding: 30px 40px;
            max-width: 1200px;
            margin: 0 auto;
            margin-left: calc(var(--sidebar-width) + 30px);
            min-height: 100vh;
            transition: all var(--transition);
        }

        .event-details-container {
            background: var(--bg-container);
            border-radius: 18px;
            padding: 40px 45px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: all var(--transition);
        }

        /* ============================================================
           PAGE NAVIGATION
           ============================================================ */
        .page-navigation {
            margin-bottom: 30px;
        }

        .back-link {
            color: var(--orange);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--orange-dark);
            text-decoration: underline;
        }

        /* ============================================================
           EVENT HERO
           ============================================================ */
        .event-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 30px 0 35px;
            border-bottom: 2px solid var(--border-light);
            margin-bottom: 40px;
            gap: 30px;
            transition: border-color var(--transition);
        }

        .hero-left {
            flex: 1;
        }

        .hero-left h1 {
            font-size: 36px;
            font-weight: 800;
            margin: 12px 0 12px;
            color: var(--text-primary);
            line-height: 1.2;
            transition: color var(--transition);
        }

        .hero-left p {
            color: var(--text-secondary);
            font-size: 16px;
            max-width: 600px;
            line-height: 1.7;
            transition: color var(--transition);
        }

        .sport-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: var(--orange-light);
            color: var(--orange);
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            transition: background var(--transition);
        }

        .sport-badge .sport-icon {
            font-size: 16px;
        }

        .hero-right {
            text-align: center;
            padding: 24px 30px;
            background: var(--bg-hero-right);
            border-radius: 16px;
            min-width: 160px;
            border: 1px solid var(--border-light);
            flex-shrink: 0;
            transition: all var(--transition);
        }

        .hero-icon {
            font-size: 52px;
            display: block;
            margin-bottom: 6px;
        }

        .hero-right span {
            display: block;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            transition: color var(--transition);
        }

        /* ============================================================
           SECTION HEADINGS
           ============================================================ */
        .section-heading {
            margin-bottom: 25px;
        }

        .section-heading span {
            font-size: 12px;
            font-weight: 700;
            color: var(--orange);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .section-heading h2 {
            font-size: 22px;
            font-weight: 700;
            margin: 4px 0 0;
            color: var(--text-primary);
            transition: color var(--transition);
        }

        /* ============================================================
           INFORMATION GRID
           ============================================================ */
        .information-section {
            margin-bottom: 40px;
        }

        .information-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
        }

        .info-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 22px;
            background: var(--bg-secondary);
            border-radius: 12px;
            border: 1px solid var(--border-light);
            transition: all var(--transition);
        }

        .info-card:hover {
            border-color: var(--orange);
            box-shadow: var(--shadow-sm);
        }

        .info-icon {
            font-size: 26px;
            flex-shrink: 0;
        }

        .info-card small {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            transition: color var(--transition);
        }

        .info-card strong {
            font-size: 15px;
            color: var(--text-primary);
            font-weight: 600;
            transition: color var(--transition);
        }

        .info-card .status-text {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-text.open {
            background: var(--success-bg);
            color: var(--success);
        }

        .status-text.registration-not-open {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .status-text.registration-closed {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .status-text.cancelled {
            background: var(--gray-bg);
            color: var(--gray);
        }

        /* ============================================================
           DESCRIPTION
           ============================================================ */
        .description-section {
            margin-bottom: 40px;
        }

        .description-box {
            padding: 28px 32px;
            background: var(--bg-description);
            border-radius: 12px;
            border: 1px solid var(--border-light);
            line-height: 1.9;
            color: var(--text-primary);
            font-size: 15px;
            transition: all var(--transition);
        }

        /* ============================================================
           REGISTRATION CTA
           ============================================================ */
        .registration-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 35px 40px;
            background: var(--bg-registration);
            border-radius: 16px;
            border: 1px solid var(--bg-registration-border);
            margin-top: 10px;
            gap: 30px;
            transition: all var(--transition);
        }

        .registration-text span {
            font-size: 12px;
            font-weight: 700;
            color: var(--orange);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .registration-text h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 4px 0 8px;
            color: var(--text-primary);
            transition: color var(--transition);
        }

        .registration-text p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 15px;
            transition: color var(--transition);
        }

        .registration-action {
            flex-shrink: 0;
        }

        .register-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 36px;
            background: var(--orange-gradient);
            color: #ffffff;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            transition: all var(--transition);
            box-shadow: var(--shadow-orange);
            border: none;
            cursor: pointer;
        }

        .register-button:hover {
            background: var(--orange-hover);
            transform: translateY(-3px);
            box-shadow: var(--shadow-orange-hover);
        }

        .register-button:active {
            transform: translateY(0px);
        }

        .register-button i {
            font-size: 14px;
        }

        .disabled-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 36px;
            background: var(--gray-bg);
            color: var(--text-muted);
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: not-allowed;
            border: 1px solid var(--border-color);
            transition: all var(--transition);
        }

        .disabled-button i {
            font-size: 14px;
        }

        /* ============================================================
           RESPONSIVE BREAKPOINTS
           ============================================================ */

        /* Large Screens (1200px - 1440px) */
        @media (max-width: 1200px) {
            .event-details-main {
                padding: 25px 30px;
                max-width: 1100px;
            }
            
            .event-details-container {
                padding: 35px 35px;
            }
            
            .hero-left h1 {
                font-size: 32px;
            }
            
            .information-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }
        }

        /* Medium Screens (992px - 1199px) */
        @media (max-width: 992px) {
            .event-details-main {
                padding: 20px 25px;
                margin-left: calc(var(--sidebar-width) + 20px);
                max-width: 100%;
            }
            
            .event-details-container {
                padding: 30px 30px;
            }
            
            .hero-left h1 {
                font-size: 28px;
            }
            
            .hero-right {
                min-width: 130px;
                padding: 20px 25px;
            }
            
            .hero-icon {
                font-size: 44px;
            }
            
            .information-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .registration-section {
                padding: 30px 30px;
                flex-direction: column;
                text-align: center;
            }
            
            .registration-action {
                width: 100%;
            }
            
            .register-button,
            .disabled-button {
                width: 100%;
                justify-content: center;
            }
        }

        /* Tablet Screens (768px - 991px) */
        @media (max-width: 768px) {
            .event-details-main {
                padding: 15px 20px;
                margin-left: calc(var(--sidebar-width) + 15px);
            }
            
            .event-details-container {
                padding: 25px 22px;
                border-radius: 14px;
            }
            
            .event-hero {
                flex-direction: column;
                padding: 20px 0 25px;
                gap: 20px;
            }
            
            .hero-left h1 {
                font-size: 26px;
            }
            
            .hero-left p {
                font-size: 15px;
            }
            
            .hero-right {
                width: 100%;
                min-width: unset;
                padding: 18px 20px;
            }
            
            .hero-icon {
                font-size: 40px;
            }
            
            .information-grid {
                grid-template-columns: 1fr 1fr;
                gap: 14px;
            }
            
            .info-card {
                padding: 14px 18px;
            }
            
            .info-icon {
                font-size: 22px;
            }
            
            .info-card strong {
                font-size: 14px;
            }
            
            .description-box {
                padding: 20px 22px;
                font-size: 14px;
            }
            
            .registration-section {
                padding: 25px 22px;
            }
            
            .registration-text h2 {
                font-size: 22px;
            }
            
            .register-button,
            .disabled-button {
                padding: 14px 28px;
                font-size: 15px;
            }
            
            .section-heading h2 {
                font-size: 20px;
            }
        }

        /* Mobile Screens (576px - 767px) */
        @media (max-width: 576px) {
            .event-details-main {
                padding: 12px 12px;
                margin-left: calc(var(--sidebar-width) + 10px);
            }
            
            .event-details-container {
                padding: 18px 16px;
                border-radius: 12px;
            }
            
            .page-navigation {
                margin-bottom: 20px;
            }
            
            .back-link {
                font-size: 13px;
            }
            
            .event-hero {
                padding: 15px 0 20px;
                gap: 16px;
            }
            
            .hero-left h1 {
                font-size: 22px;
                margin: 8px 0 10px;
            }
            
            .hero-left p {
                font-size: 14px;
                line-height: 1.6;
            }
            
            .sport-badge {
                font-size: 12px;
                padding: 4px 12px;
            }
            
            .hero-right {
                padding: 14px 16px;
            }
            
            .hero-icon {
                font-size: 34px;
            }
            
            .hero-right span {
                font-size: 12px;
            }
            
            .section-heading {
                margin-bottom: 18px;
            }
            
            .section-heading span {
                font-size: 10px;
            }
            
            .section-heading h2 {
                font-size: 18px;
            }
            
            .information-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .info-card {
                padding: 12px 14px;
                gap: 12px;
            }
            
            .info-icon {
                font-size: 20px;
            }
            
            .info-card small {
                font-size: 10px;
            }
            
            .info-card strong {
                font-size: 13px;
            }
            
            .info-card .status-text {
                font-size: 12px;
                padding: 3px 10px;
            }
            
            .description-box {
                padding: 16px 18px;
                font-size: 13px;
                line-height: 1.7;
            }
            
            .registration-section {
                padding: 20px 16px;
                gap: 16px;
                flex-direction: column;
                text-align: center;
            }
            
            .registration-text span {
                font-size: 10px;
            }
            
            .registration-text h2 {
                font-size: 20px;
                margin: 2px 0 6px;
            }
            
            .registration-text p {
                font-size: 14px;
            }
            
            .registration-action {
                width: 100%;
            }
            
            .register-button,
            .disabled-button {
                width: 100%;
                justify-content: center;
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .register-button i,
            .disabled-button i {
                font-size: 13px;
            }
        }

        /* Small Mobile Screens (up to 375px) */
        @media (max-width: 375px) {
            .event-details-main {
                padding: 8px 8px;
                margin-left: calc(var(--sidebar-width) + 8px);
            }
            
            .event-details-container {
                padding: 14px 12px;
                border-radius: 10px;
            }
            
            .hero-left h1 {
                font-size: 19px;
            }
            
            .hero-left p {
                font-size: 13px;
            }
            
            .information-grid {
                gap: 10px;
            }
            
            .info-card {
                padding: 10px 12px;
                gap: 10px;
            }
            
            .info-icon {
                font-size: 18px;
            }
            
            .info-card strong {
                font-size: 12px;
            }
            
            .description-box {
                padding: 14px 14px;
                font-size: 12px;
            }
            
            .registration-text h2 {
                font-size: 18px;
            }
            
            .register-button,
            .disabled-button {
                padding: 10px 16px;
                font-size: 13px;
            }
        }

        /* ============================================================
           SIDEBAR COMPATIBILITY
           ============================================================ */
        /* Make sure sidebar works with dark mode and responsive */
        .sidebar {
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            transition: all var(--transition);
            width: var(--sidebar-width);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar a {
            color: var(--text-secondary);
            transition: color var(--transition);
        }

        .sidebar a:hover,
        .sidebar a.active {
            color: var(--orange);
        }

        /* Theme toggle button */
        .theme-toggle {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            transition: all var(--transition);
        }

        .theme-toggle:hover {
            border-color: var(--orange);
        }

        /* ============================================================
           PRINT STYLES
           ============================================================ */
        @media print {
            .sidebar {
                display: none !important;
            }
            
            .event-details-main {
                margin-left: 0 !important;
                padding: 20px !important;
            }
            
            .event-details-container {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            
            .register-button,
            .disabled-button {
                box-shadow: none !important;
            }
        }

        /* ============================================================
           ACCESSIBILITY - REDUCED MOTION
           ============================================================ */
        @media (prefers-reduced-motion: reduce) {
            * {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }
    </style>

</head>

<body class="<?php echo $dark_mode_class; ?>">
    
<?php include "sidebar.php"; ?>

<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="event-details-main">

    <div class="event-details-container">

        <!-- =================================================
             TOP NAVIGATION
        ================================================== -->

        <div class="page-navigation">
            <a href="events.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Events
            </a>
        </div>

        <!-- =================================================
             EVENT HERO
        ================================================== -->

        <section class="event-hero">

            <div class="hero-left">

                <div class="sport-badge">
                    <span class="sport-icon">🏆</span>
                    <span>
                        <?php
                        echo !empty($event['sport_name'])
                            ? clean($event['sport_name'])
                            : "Sports";
                        ?>
                    </span>
                </div>

                <h1>
                    <?php echo clean($event['event_name']); ?>
                </h1>

                <p>
                    <?php
                    if (!empty($event['sport_description'])) {
                        echo clean($event['sport_description']);
                    } else {
                        echo "Participate in this NexArena sports event and compete with other players.";
                    }
                    ?>
                </p>

            </div>

            <div class="hero-right">
                <span class="hero-icon">🏆</span>
                <span>NexArena Event</span>
            </div>

        </section>

        <!-- =================================================
             EVENT INFORMATION
        ================================================== -->

        <section class="information-section">

            <div class="section-heading">
                <span>EVENT INFORMATION</span>
                <h2>Event Details</h2>
            </div>

            <div class="information-grid">

                <!-- EVENT DATE -->
                <div class="info-card">
                    <div class="info-icon">📅</div>
                    <div>
                        <small>Event Date</small>
                        <strong>
                            <?php echo formatDate($event['event_date']); ?>
                        </strong>
                    </div>
                </div>

                <!-- LOCATION -->
                <div class="info-card">
                    <div class="info-icon">📍</div>
                    <div>
                        <small>Location</small>
                        <strong>
                            <?php
                            echo !empty($event['location'])
                                ? clean($event['location'])
                                : "Location not available";
                            ?>
                        </strong>
                    </div>
                </div>

                <!-- REGISTRATION START -->
                <div class="info-card">
                    <div class="info-icon">▶</div>
                    <div>
                        <small>Registration Starts</small>
                        <strong>
                            <?php echo formatDateTime($event['registration_start']); ?>
                        </strong>
                    </div>
                </div>

                <!-- REGISTRATION END -->
                <div class="info-card">
                    <div class="info-icon">⏰</div>
                    <div>
                        <small>Registration Ends</small>
                        <strong>
                            <?php echo formatDateTime($event['registration_end']); ?>
                        </strong>
                    </div>
                </div>

                <!-- SPORT -->
                <div class="info-card">
                    <div class="info-icon">⚽</div>
                    <div>
                        <small>Sport</small>
                        <strong>
                            <?php
                            echo !empty($event['sport_name'])
                                ? clean($event['sport_name'])
                                : "Not specified";
                            ?>
                        </strong>
                    </div>
                </div>

                <!-- STATUS -->
                <div class="info-card">
                    <div class="info-icon">●</div>
                    <div>
                        <small>Registration Status</small>
                        <strong class="status-text <?php echo clean($status_class); ?>">
                            <?php echo clean($registration_status); ?>
                        </strong>
                    </div>
                </div>

            </div>

        </section>

        <!-- =================================================
             DESCRIPTION
        ================================================== -->

        <section class="description-section">

            <div class="section-heading">
                <span>ABOUT THE EVENT</span>
                <h2>Description</h2>
            </div>

            <div class="description-box">
                <?php
                if (!empty($event['sport_description'])) {
                    echo nl2br(clean($event['sport_description']));
                } else {
                    echo "No additional description has been provided for this event.";
                }
                ?>
            </div>

        </section>

        <!-- =================================================
             REGISTRATION CTA
        ================================================== -->

        <section class="registration-section">

            <div class="registration-text">
                <span>READY TO COMPETE?</span>
                <h2>Join This Event</h2>
                <p>Register for this event and take part in the NexArena competition.</p>
            </div>

            <div class="registration-action">

                <?php if ($registration_status === "Open"): ?>
                    <a href="register_event.php?id=<?php echo (int)$event['event_id']; ?>" class="register-button">
                        Register Now <i class="fas fa-arrow-right"></i>
                    </a>

                <?php elseif ($registration_status === "Registration Not Open"): ?>
                    <span class="disabled-button">
                        <i class="fas fa-clock"></i> Registration Not Open
                    </span>

                <?php elseif ($registration_status === "Registration Closed"): ?>
                    <span class="disabled-button">
                        <i class="fas fa-times-circle"></i> Registration Closed
                    </span>

                <?php else: ?>
                    <span class="disabled-button">
                        <i class="fas fa-ban"></i> Event Cancelled
                    </span>
                <?php endif; ?>

            </div>

        </section>

    </div>

</main>

</body>
</html>