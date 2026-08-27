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

    // Check if event is cancelled
    if (strtolower($event_status) === 'cancelled') {
        return "Cancelled";
    }

    // Check if event is completed or closed
    if (strtolower($event_status) === 'completed' || strtolower($event_status) === 'closed') {
        return "Closed";
    }

    // Check registration status
    if ($end !== null && $now > $end) {
        return "Registration Closed";
    }

    if ($start !== null && $now < $start) {
        return "Registration Not Open";
    }

    if ($start !== null && $end !== null && $now >= $start && $now <= $end) {
        return "Open";
    }

    // If no registration dates set, allow registration
    if ($start === null && $end === null) {
        return "Open";
    }

    return "Open";
}

// Don't close connection here - sidebar needs it
// $conn->close();

?>

<!DOCTYPE html>
<html lang="en" data-theme="<?= $dark_mode ? 'dark' : 'light' ?>">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Upcoming Events | NexArena</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">

    <style>
        /* =========================================================
           EVENTS PAGE - DARK MODE SUPPORT
        ========================================================= */

        /* ---- Root Variables ---- */
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

        /* Dark Mode Variables */
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

        /* ---- Global Reset ---- */
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

        /* =========================================================
           MAIN CONTENT
        ========================================================= */
        .events-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 24px 60px;
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
        }

        .header-content .header-label {
            display: inline-block;
            background: var(--orange-bg);
            color: var(--orange);
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .header-content h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .header-content h1 i {
            color: var(--orange);
        }

        .header-content p {
            color: var(--text-muted);
            font-size: 15px;
            margin-top: 2px;
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
        }

        .events-topbar h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
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
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
        }

        /* =========================================================
           EVENT CARD
        ========================================================= */
        .event-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .event-card:hover {
            border-color: var(--orange-border);
            box-shadow: var(--shadow-hover);
            transform: translateY(-4px);
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
            background: var(--orange-bg);
            color: var(--orange);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .sport-badge .sport-icon {
            font-size: 16px;
        }

        .event-status {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .event-description {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 16px;
            flex: 1;
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
        }

        .detail-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .detail-item div {
            display: flex;
            flex-direction: column;
        }

        .detail-item small {
            color: var(--text-muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .detail-item strong {
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 600;
        }

        .registration-info {
            padding: 12px 16px;
            background: var(--bg-secondary);
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .registration-info span {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .registration-info strong {
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 600;
        }

        /* =========================================================
           EVENT ACTION BUTTONS
        ========================================================= */
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
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
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
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
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
        }

        .no-events p {
            color: var(--text-muted);
            font-size: 16px;
            margin-bottom: 20px;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */
        @media (max-width: 768px) {
            .events-main {
                padding: 20px 16px;
            }

            .header-content h1 {
                font-size: 24px;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }

            .event-details {
                grid-template-columns: 1fr;
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
            }

            .events-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-icon {
                font-size: 36px;
            }

            .registration-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .events-topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .event-card {
                padding: 18px;
            }

            .event-title h3 {
                font-size: 18px;
            }

            .detail-item strong {
                font-size: 13px;
            }
        }
    </style>

</head>

<body class="<?php echo $dark_mode_class; ?>">

<?php include "sidebar.php"; ?>

<!-- ======================================================
     MAIN CONTENT
====================================================== -->

<main class="events-main">

    <!-- ==================================================
         PAGE HEADER
    ================================================== -->

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

    <!-- ==================================================
         EVENT COUNT
    ================================================== -->

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

        <a
            href="dashboard.php"
            class="back-button"
        >
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>

    </div>

    <!-- ==================================================
         EVENTS
    ================================================== -->

    <?php if ($result->num_rows > 0): ?>

        <section class="events-grid">

            <?php while ($event = $result->fetch_assoc()): ?>

                <?php

                // Get event status using the improved function
                $eventStatus = getEventStatus(
                    $event['status'],
                    $event['registration_start'],
                    $event['registration_end']
                );

                // Check if registration is open
                $now = time();
                $start = !empty($event['registration_start']) ? strtotime($event['registration_start']) : null;
                $end = !empty($event['registration_end']) ? strtotime($event['registration_end']) : null;
                
                // Registration is open if:
                // 1. Event status is "Open"
                // 2. (No start date set OR current time >= start)
                // 3. (No end date set OR current time <= end)
                $registrationOpen = (
                    $eventStatus === "Open" &&
                    ($start === null || $now >= $start) &&
                    ($end === null || $now <= $end)
                );

                // Special case: If no registration dates are set, allow registration
                if ($start === null && $end === null && $eventStatus === "Open") {
                    $registrationOpen = true;
                }

                $statusClass = strtolower(
                    str_replace(' ', '-', $eventStatus)
                );

                $isRegistered = in_array($event['event_id'], $user_registered_events);

                ?>

                <article class="event-card">

                    <!-- EVENT TOP -->

                    <div class="event-card-top">

                        <div class="sport-badge">

                            <span class="sport-icon">
                                ⚽
                            </span>

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
                            <span
                                class="event-status <?php echo clean($statusClass); ?>"
                            >
                                <?php echo clean($eventStatus); ?>
                            </span>
                        <?php endif; ?>

                    </div>

                    <!-- EVENT TITLE -->

                    <div class="event-title">

                        <h3>
                            <?php echo clean($event['event_name']); ?>
                        </h3>

                    </div>

                    <!-- EVENT DESCRIPTION -->

                    <p class="event-description">

                        <?php

                        if (!empty($event['sport_description'])) {

                            echo clean(
                                $event['sport_description']
                            );

                        } else {

                            echo "Join this NexArena sports event and compete with other players.";

                        }

                        ?>

                    </p>

                    <!-- EVENT DETAILS -->

                    <div class="event-details">

                        <div class="detail-item">

                            <span class="detail-icon">
                                📅
                            </span>

                            <div>

                                <small>
                                    Event Date
                                </small>

                                <strong>
                                    <?php
                                    echo formatDate(
                                        $event['event_date']
                                    );
                                    ?>
                                </strong>

                            </div>

                        </div>

                        <div class="detail-item">

                            <span class="detail-icon">
                                📍
                            </span>

                            <div>

                                <small>
                                    Location
                                </small>

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

                            <span class="detail-icon">
                                ⏱
                            </span>

                            <div>

                                <small>
                                    Registration
                                </small>

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

                            <span class="detail-icon">
                                📊
                            </span>

                            <div>

                                <small>
                                    Status
                                </small>

                                <strong>
                                    <?php echo clean($eventStatus); ?>
                                </strong>

                            </div>

                        </div>

                    </div>

                    <!-- ACTION -->

                    <div class="event-action">

                        <a
                            href="event_details.php?id=<?php echo (int)$event['event_id']; ?>"
                            class="details-button"
                        >
                            <i class="fas fa-eye"></i> View Details
                        </a>

                        <?php if ($isRegistered): ?>

                            <span class="closed-button" style="background:#dcfce7;color:#16a34a;border-color:#86efac;cursor:default;">
                                <i class="fas fa-check-circle"></i> Already Registered
                            </span>

                        <?php elseif ($registrationOpen && $eventStatus !== "Cancelled" && $eventStatus !== "Closed"): ?>

                            <a
                                href="register_event.php?id=<?php echo (int)$event['event_id']; ?>"
                                class="register-button"
                            >
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

        <!-- ==================================================
             NO EVENTS
        ================================================== -->

        <section class="no-events">

            <div class="no-events-icon">
                🏟️
            </div>

            <h2>
                No Upcoming Events
            </h2>

            <p>
                There are currently no upcoming events.
                Please check again later.
            </p>

            <a
                href="dashboard.php"
                class="back-button"
                style="display:inline-flex;"
            >
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

        </section>

    <?php endif; ?>

</main>

<!-- Theme JavaScript - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>

</html>