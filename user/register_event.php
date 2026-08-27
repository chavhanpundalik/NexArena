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
   DATABASE CONNECTION
========================================================= */

require_once "../db_connect.php";

/* =========================================================
   GET EVENT ID
========================================================= */

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    header("Location: events.php?error=invalid_event");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

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

/* =========================================================
   GET EVENT DETAILS
========================================================= */

$sql = "
    SELECT 
        e.*,
        s.sport_name,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND status = 'confirmed') AS registered_count
    FROM events e
    LEFT JOIN sports s ON e.sport_id = s.sport_id
    WHERE e.event_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: events.php?error=event_not_found");
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

/* =========================================================
   CHECK IF USER IS ALREADY REGISTERED
========================================================= */

$check_sql = "SELECT registration_id FROM registrations WHERE event_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $event_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$already_registered = $check_result->num_rows > 0;
$check_stmt->close();

/* =========================================================
   CHECK REGISTRATION DEADLINE
========================================================= */

$now = time();
$registration_end = !empty($event['registration_end']) ? strtotime($event['registration_end']) : null;
$registration_start = !empty($event['registration_start']) ? strtotime($event['registration_start']) : null;

$is_registration_open = false;
$registration_status = '';

// Check if registration is open
if ($registration_start === null && $registration_end === null) {
    // No dates set - always open
    $is_registration_open = true;
    $registration_status = 'Open';
} elseif ($registration_start !== null && $now < $registration_start) {
    $registration_status = 'Registration not yet open';
} elseif ($registration_end !== null && $now > $registration_end) {
    $registration_status = 'Registration closed';
} else {
    $is_registration_open = true;
    $registration_status = 'Open';
}

// Check if event is cancelled
if (strtolower($event['status'] ?? '') === 'cancelled') {
    $is_registration_open = false;
    $registration_status = 'Event Cancelled';
}

/* =========================================================
   PROCESS REGISTRATION
========================================================= */

$message = '';
$message_type = '';

if (isset($_POST['register']) && $is_registration_open && !$already_registered) {
    // Insert registration
    $insert_sql = "INSERT INTO registrations (event_id, user_id, status, registered_at) VALUES (?, ?, 'confirmed', NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $event_id, $user_id);
    
    if ($insert_stmt->execute()) {
        $message = "You have successfully registered for this event!";
        $message_type = "success";
        $already_registered = true;
    } else {
        $message = "Error registering: " . $conn->error;
        $message_type = "error";
    }
    $insert_stmt->close();
}

// DO NOT CLOSE THE CONNECTION HERE - Sidebar needs it
// $conn->close();

?>

<!DOCTYPE html>
<html lang="en" data-theme="<?= $dark_mode ? 'dark' : 'light' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Event | NexArena</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Theme CSS -->
    <link rel="stylesheet" href="assets/theme.css">

    <style>
        /* =========================================================
           REGISTER EVENT PAGE - DARK MODE SUPPORT
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-primary) !important;
            color: var(--text-primary) !important;
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .register-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 24px 60px;
        }

        /* Header */
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .page-header .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }

        .page-header .back-link:hover {
            color: var(--orange);
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .page-header h1 i {
            color: var(--orange);
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 16px;
            margin-top: 4px;
        }

        /* Event Card */
        .event-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .event-card:hover {
            border-color: var(--orange-border);
            box-shadow: var(--shadow-hover);
        }

        .sport-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: var(--orange-bg);
            color: var(--orange);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .event-card h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px 24px;
            margin: 16px 0;
        }

        .event-meta .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .event-meta .meta-item i {
            color: var(--orange);
            width: 18px;
        }

        .event-description {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.7;
            margin: 12px 0 16px;
        }

        .registration-status {
            display: inline-block;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .registration-status.open {
            background: #dcfce7;
            color: #16a34a;
        }

        .registration-status.closed {
            background: #fef2f2;
            color: #dc2626;
        }

        .registration-status.not-open {
            background: #fef3c7;
            color: #d97706;
        }

        /* Registration Form */
        .registration-form {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px 30px;
            margin-top: 20px;
        }

        .registration-form h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .registration-form .form-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 16px;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: var(--orange);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-register:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--orange-shadow);
        }

        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn-register i {
            font-size: 18px;
        }

        .already-registered-msg {
            text-align: center;
            padding: 16px;
            background: #dcfce7;
            border-radius: 10px;
            color: #16a34a;
            font-weight: 600;
        }

        .already-registered-msg i {
            margin-right: 8px;
        }

        /* Alerts */
        .alert {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
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
            .register-container {
                padding: 20px 16px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .event-card {
                padding: 20px;
            }

            .event-meta {
                flex-direction: column;
                gap: 10px;
            }

            .registration-form {
                padding: 18px 16px;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: 20px;
            }

            .event-card h2 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body class="<?php echo $dark_mode_class; ?>">

<?php include "sidebar.php"; ?>

<!-- ======================================================
     MAIN CONTENT
====================================================== -->

<main class="register-container">

    <!-- ==================================================
         PAGE HEADER
    ================================================== -->

    <div class="page-header">
        <a href="events.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>
        <h1><i class="fas fa-calendar-plus"></i> Register for Event</h1>
        <p>Confirm your registration for the selected event</p>
    </div>

    <!-- ==================================================
         MESSAGES
    ================================================== -->

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- ==================================================
         EVENT DETAILS
    ================================================== -->

    <div class="event-card">

        <div class="sport-badge">
            <i class="fas fa-running"></i>
            <?php echo htmlspecialchars($event['sport_name'] ?? 'Sports'); ?>
        </div>

        <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>

        <div class="event-meta">
            <div class="meta-item">
                <i class="fas fa-calendar-day"></i>
                <?php echo date('l, d M Y', strtotime($event['event_date'])); ?>
            </div>
            <div class="meta-item">
                <i class="fas fa-clock"></i>
                <?php echo date('h:i A', strtotime($event['event_date'])); ?>
            </div>
            <?php if (!empty($event['location'])): ?>
                <div class="meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo htmlspecialchars($event['location']); ?>
                </div>
            <?php endif; ?>
            <div class="meta-item">
                <i class="fas fa-users"></i>
                <?php echo (int)$event['registered_count']; ?> registered
            </div>
        </div>

        <?php if (!empty($event['sport_description'])): ?>
            <p class="event-description">
                <?php echo htmlspecialchars($event['sport_description']); ?>
            </p>
        <?php endif; ?>

        <div style="margin-top:12px;">
            <span class="registration-status <?php echo $is_registration_open ? 'open' : ($now > ($registration_end ?? 0) ? 'closed' : 'not-open'); ?>">
                <i class="fas fa-<?php echo $is_registration_open ? 'check-circle' : ($now > ($registration_end ?? 0) ? 'times-circle' : 'clock'); ?>"></i>
                <?php echo $registration_status; ?>
            </span>
        </div>

    </div>

    <!-- ==================================================
         REGISTRATION FORM
    ================================================== -->

    <div class="registration-form">

        <?php if ($already_registered): ?>

            <div class="already-registered-msg">
                <i class="fas fa-check-circle"></i>
                You are already registered for this event!
            </div>

            <div style="text-align:center;margin-top:12px;">
                <a href="events.php" class="btn-register" style="display:inline-flex;background:#64748b;width:auto;padding:10px 24px;">
                    <i class="fas fa-arrow-left"></i> Back to Events
                </a>
            </div>

        <?php elseif ($is_registration_open): ?>

            <h3>Confirm Registration</h3>
            <p class="form-subtitle">
                <i class="fas fa-info-circle" style="color:var(--orange);"></i>
                By registering, you agree to participate in this event.
            </p>

            <form method="POST">
                <button type="submit" name="register" class="btn-register">
                    <i class="fas fa-check-circle"></i>
                    Confirm Registration
                </button>
            </form>

        <?php elseif ($registration_status === 'Registration not yet open'): ?>

            <div class="alert alert-info">
                <i class="fas fa-clock"></i>
                Registration opens on <?php echo date('d M Y, h:i A', $registration_start); ?>
            </div>

        <?php elseif ($registration_status === 'Registration closed'): ?>

            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i>
                Registration closed on <?php echo date('d M Y, h:i A', $registration_end); ?>
            </div>

        <?php elseif ($registration_status === 'Event Cancelled'): ?>

            <div class="alert alert-error">
                <i class="fas fa-ban"></i>
                This event has been cancelled.
            </div>

        <?php else: ?>

            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                Registration is currently not available for this event.
            </div>

        <?php endif; ?>

    </div>

</main>

<!-- Theme JavaScript -->
<script src="assets/theme.js"></script>

</body>
</html>