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

// Don't close connection here - sidebar needs it
// $conn->close();

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

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">
    
    <!-- External CSS -->
    <link rel="stylesheet" href="assets/events.css">
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
                                    <span class="closed-button" style="cursor:default;">
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

    <!-- Theme JavaScript -->
    <script src="assets/theme.js"></script>

</body>

</html>