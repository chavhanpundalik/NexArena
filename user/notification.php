<?php

session_start();

require_once "../db_connect.php";


/* ========================================
   CHECK LOGIN
======================================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php?error=login_required");
    exit();

}


/* ========================================
   CHECK USER ROLE
======================================== */

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'user'
) {

    header("Location: ../login.php?error=access_denied");
    exit();

}


$user_id = (int) $_SESSION['user_id'];


/* ========================================
   GET DARK MODE SETTING
======================================== */

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


/* ========================================
   MARK SINGLE NOTIFICATION AS READ
======================================== */

if (
    isset($_GET['read']) &&
    is_numeric($_GET['read'])
) {

    $notification_id = (int) $_GET['read'];

    $read_sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ?
        AND user_id = ?
    ";

    $read_stmt = $conn->prepare($read_sql);

    if ($read_stmt) {

        $read_stmt->bind_param(
            "ii",
            $notification_id,
            $user_id
        );

        $read_stmt->execute();
        $read_stmt->close();

    }

    header("Location: notifications.php");
    exit();
}


/* ========================================
   MARK ALL AS READ
======================================== */

if (
    isset($_GET['mark_all']) &&
    $_GET['mark_all'] === 'read'
) {

    $all_read_sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
    ";

    $all_read_stmt =
        $conn->prepare($all_read_sql);

    if ($all_read_stmt) {

        $all_read_stmt->bind_param(
            "i",
            $user_id
        );

        $all_read_stmt->execute();
        $all_read_stmt->close();

    }

    header("Location: notifications.php");
    exit();
}


/* ========================================
   GET UNREAD COUNT
======================================== */

$count_sql = "
    SELECT COUNT(*) AS unread_count
    FROM notifications
    WHERE user_id = ?
    AND is_read = 0
";

$count_stmt = $conn->prepare($count_sql);

$unread_count = 0;

if ($count_stmt) {

    $count_stmt->bind_param(
        "i",
        $user_id
    );

    $count_stmt->execute();

    $count_result =
        $count_stmt->get_result();

    if ($count_row = $count_result->fetch_assoc()) {

        $unread_count =
            (int) $count_row['unread_count'];

    }

    $count_stmt->close();
}


/* ========================================
   GET USER NOTIFICATIONS
======================================== */

$notification_sql = "
    SELECT
        notification_id,
        title,
        message,
        type,
        is_read,
        created_at

    FROM notifications

    WHERE user_id = ?

    ORDER BY created_at DESC
";

$notification_stmt =
    $conn->prepare($notification_sql);

$notifications = [];

if ($notification_stmt) {

    $notification_stmt->bind_param(
        "i",
        $user_id
    );

    $notification_stmt->execute();

    $notification_result =
        $notification_stmt->get_result();

    while (
        $notification =
        $notification_result->fetch_assoc()
    ) {

        $notifications[] =
            $notification;

    }

    $notification_stmt->close();
}


/* ========================================
   NOTIFICATION ICON
======================================== */

function getNotificationIcon($type)
{

    switch ($type) {

        case 'registration':
            return '📝';

        case 'event':
            return '🏆';

        case 'team':
            return '👥';

        case 'system':
        default:
            return '🔔';

    }

}


/* ========================================
   NOTIFICATION LABEL
======================================== */

function getNotificationType($type)
{

    switch ($type) {

        case 'registration':
            return 'Registration';

        case 'event':
            return 'Event';

        case 'team':
            return 'Team';

        case 'system':
        default:
            return 'System';

    }

}


/* ========================================
   TIME FORMAT
======================================== */

function notificationTime($date)
{

    if (empty($date)) {
        return 'Recently';
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return 'Recently';
    }

    $difference = time() - $timestamp;


    if ($difference < 60) {

        return 'Just now';

    }


    if ($difference < 3600) {

        $minutes =
            floor($difference / 60);

        return $minutes . ' min ago';

    }


    if ($difference < 86400) {

        $hours =
            floor($difference / 3600);

        return $hours .
            ' hour' .
            ($hours > 1 ? 's' : '') .
            ' ago';

    }


    if ($difference < 604800) {

        $days =
            floor($difference / 86400);

        return $days .
            ' day' .
            ($days > 1 ? 's' : '') .
            ' ago';

    }


    return date(
        "d M Y",
        $timestamp
    );

}

// Don't close connection here - sidebar needs it
// $conn->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Notifications | NexArena
    </title>

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/notification.css">

</head>


<body class="<?php echo $dark_mode_class; ?>">

<?php include "sidebar.php"; ?>


<!-- ========================================
     MAIN CONTENT
======================================== -->

<div class="notifications-main">


    <main class="notifications-container">


        <!-- ========================================
             HEADER
        ======================================== -->

        <section class="notifications-header">


            <div class="header-content">

                <span class="page-label">
                    NEXARENA UPDATES
                </span>

                <h1>
                    Notifications
                </h1>

                <p>
                    Stay updated with your events,
                    registrations, teams and NexArena activities.
                </p>

            </div>


            <?php if ($unread_count > 0): ?>

                <a
                    href="notifications.php?mark_all=read"
                    class="mark-all-btn"
                >
                    Mark All as Read
                </a>

            <?php endif; ?>


        </section>



        <!-- ========================================
             SUMMARY
        ======================================== -->

        <section class="notification-summary">


            <div class="summary-icon">
                🔔
            </div>


            <div class="summary-content">

                <span>
                    YOUR NOTIFICATIONS
                </span>

                <h2>

                    <?php
                    echo count($notifications);
                    ?>

                    Notifications

                </h2>


                <p>

                    <?php

                    if ($unread_count > 0) {

                        echo $unread_count .
                            " unread notification" .
                            ($unread_count > 1 ? "s" : "");

                    } else {

                        echo "You're all caught up!";

                    }

                    ?>

                </p>

            </div>


        </section>



        <!-- ========================================
             NOTIFICATION SECTION
        ======================================== -->

        <section class="notifications-section">


            <div class="section-heading">


                <div>

                    <span>
                        ACTIVITY
                    </span>

                    <h2>
                        Recent Notifications
                    </h2>

                </div>


                <?php if ($unread_count > 0): ?>

                    <div class="unread-label">

                        <?php
                        echo $unread_count;
                        ?>

                        Unread

                    </div>

                <?php endif; ?>


            </div>



            <?php if (!empty($notifications)): ?>


                <div class="notification-list">


                    <?php foreach (
                        $notifications
                        as $notification
                    ): ?>


                        <?php

                        $is_unread =
                            ((int)$notification['is_read'] === 0);

                        $type =
                            $notification['type'];

                        ?>


                        <article
                            class="
                                notification-card
                                <?php
                                echo $is_unread
                                    ? 'unread'
                                    : 'read';
                                ?>
                            "
                        >


                            <!-- ICON -->

                            <div
                                class="
                                    notification-icon
                                    type-<?php
                                    echo htmlspecialchars(
                                        $type,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                "
                            >

                                <?php
                                echo getNotificationIcon(
                                    $type
                                );
                                ?>

                            </div>



                            <!-- CONTENT -->

                            <div class="notification-content">


                                <div class="notification-top">


                                    <span class="notification-type">

                                        <?php
                                        echo htmlspecialchars(
                                            getNotificationType(
                                                $type
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </span>


                                    <?php if ($is_unread): ?>

                                        <span class="new-label">
                                            NEW
                                        </span>

                                    <?php endif; ?>


                                </div>


                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $notification['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <?php
                                    echo htmlspecialchars(
                                        $notification['message'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </p>


                                <div class="notification-bottom">


                                    <span class="notification-time">

                                        🕒

                                        <?php
                                        echo notificationTime(
                                            $notification['created_at']
                                        );
                                        ?>

                                    </span>


                                    <?php if ($is_unread): ?>

                                        <a
                                            href="notifications.php?read=<?php
                                                echo (int)
                                                    $notification[
                                                        'notification_id'
                                                    ];
                                            ?>"
                                            class="read-btn"
                                        >
                                            Mark as Read
                                        </a>

                                    <?php else: ?>

                                        <span class="read-status">
                                            ✓ Read
                                        </span>

                                    <?php endif; ?>


                                </div>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <!-- ========================================
                     EMPTY STATE
                ======================================== -->

                <div class="empty-notifications">


                    <div class="empty-icon">
                        🔔
                    </div>


                    <h2>
                        You're All Caught Up
                    </h2>


                    <p>
                        You don't have any notifications yet.
                        New updates about events, registrations
                        and teams will appear here.
                    </p>


                    <a
                        href="dashboard.php"
                        class="back-dashboard-btn"
                    >
                        ← Back to Dashboard
                    </a>


                </div>


            <?php endif; ?>


        </section>


    </main>



    <!-- ========================================
         FOOTER
    ======================================== -->

    <footer>

        <div class="footer-logo">

            <span>Nex</span>Arena

        </div>


        <p>

            © <?php echo date("Y"); ?>

            NexArena. All Rights Reserved.

        </p>

    </footer>


</div>

<!-- Theme JavaScript - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>

</html>