<?php

session_start();

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
   DATABASE
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
    $conn->close();

    header("Location: events.php?error=event_not_found");
    exit();
}

$event = $result->fetch_assoc();

$sql->close();


/* =========================================================
   HELPER
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

$today = strtotime(date("Y-m-d"));

$event_date = !empty($event['event_date'])
    ? strtotime($event['event_date'])
    : null;

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
        <?php echo clean($event['event_name']); ?> | NexArena
    </title>

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/event_details.css">

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

            <a
                href="events.php"
                class="back-link"
            >
                ← Back to Events
            </a>

        </div>


        <!-- =================================================
             EVENT HERO
        ================================================== -->

        <section class="event-hero">

            <div class="hero-left">

                <div class="sport-badge">

                    <span class="sport-icon">
                        🏆
                    </span>

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

                        echo clean(
                            $event['sport_description']
                        );

                    } else {

                        echo "Participate in this NexArena sports event and compete with other players.";

                    }

                    ?>
                </p>

            </div>


            <div class="hero-right">

                <div class="hero-icon">
                    🏆
                </div>

                <span>
                    NexArena Event
                </span>

            </div>

        </section>



        <!-- =================================================
             EVENT INFORMATION
        ================================================== -->

        <section class="information-section">

            <div class="section-heading">

                <span>
                    EVENT INFORMATION
                </span>

                <h2>
                    Event Details
                </h2>

            </div>


            <div class="information-grid">


                <!-- EVENT DATE -->

                <div class="info-card">

                    <div class="info-icon">
                        📅
                    </div>

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


                <!-- LOCATION -->

                <div class="info-card">

                    <div class="info-icon">
                        📍
                    </div>

                    <div>

                        <small>
                            Location
                        </small>

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

                    <div class="info-icon">
                        ▶
                    </div>

                    <div>

                        <small>
                            Registration Starts
                        </small>

                        <strong>
                            <?php
                            echo formatDateTime(
                                $event['registration_start']
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <!-- REGISTRATION END -->

                <div class="info-card">

                    <div class="info-icon">
                        ⏰
                    </div>

                    <div>

                        <small>
                            Registration Ends
                        </small>

                        <strong>
                            <?php
                            echo formatDateTime(
                                $event['registration_end']
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <!-- SPORT -->

                <div class="info-card">

                    <div class="info-icon">
                        ⚽
                    </div>

                    <div>

                        <small>
                            Sport
                        </small>

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

                    <div class="info-icon">
                        ●
                    </div>

                    <div>

                        <small>
                            Registration Status
                        </small>

                        <strong
                            class="status-text <?php echo clean($status_class); ?>"
                        >
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

                <span>
                    ABOUT THE EVENT
                </span>

                <h2>
                    Description
                </h2>

            </div>


            <div class="description-box">

                <?php

                if (!empty($event['sport_description'])) {

                    echo nl2br(
                        clean(
                            $event['sport_description']
                        )
                    );

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

                <span>
                    READY TO COMPETE?
                </span>

                <h2>
                    Join This Event
                </h2>

                <p>
                    Register for this event and take part
                    in the NexArena competition.
                </p>

            </div>


            <div class="registration-action">


                <?php if ($registration_status === "Open"): ?>

                    <a
                        href="register_event.php?id=<?php echo (int)$event['event_id']; ?>"
                        class="register-button"
                    >
                        Register Now →
                    </a>


                <?php elseif ($registration_status === "Registration Not Open"): ?>

                    <span class="disabled-button">
                        Registration Not Open
                    </span>


                <?php elseif ($registration_status === "Registration Closed"): ?>

                    <span class="disabled-button">
                        Registration Closed
                    </span>


                <?php else: ?>

                    <span class="disabled-button">
                        Event Cancelled
                    </span>

                <?php endif; ?>


            </div>

        </section>


    </div>

</main>

<!-- Theme JavaScript - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>
</html>