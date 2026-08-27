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

$event_id = isset($_GET['event_id'])
    ? (int) $_GET['event_id']
    : 0;


/* =========================================================
   VALIDATE EVENT ID
========================================================= */

if ($event_id <= 0) {
    header("Location: events.php?error=invalid_event");
    exit();
}


/* =========================================================
   GET EVENT
========================================================= */

$stmt = $conn->prepare("
    SELECT
        e.event_id,
        e.event_name,
        e.sport_id,
        e.sport_description,
        e.description,
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

if (!$stmt) {
    die("Database Prepare Error: " . $conn->error);
}

$stmt->bind_param("i", $event_id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    $stmt->close();
    $conn->close();

    header("Location: events.php?error=event_not_found");
    exit();
}


$event = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   CHECK REGISTRATION PERIOD
========================================================= */

$now = time();

$registration_start = !empty($event['registration_start'])
    ? strtotime($event['registration_start'])
    : null;

$registration_end = !empty($event['registration_end'])
    ? strtotime($event['registration_end'])
    : null;

$event_status = strtolower(
    trim($event['status'] ?? '')
);


/*
   Registration is available unless:
   - event is cancelled
   - registration hasn't started
   - registration has ended
*/

$registration_open = true;


if ($event_status === 'cancelled') {
    $registration_open = false;
}


if (
    $registration_start !== null &&
    $now < $registration_start
) {
    $registration_open = false;
}


if (
    $registration_end !== null &&
    $now > $registration_end
) {
    $registration_open = false;
}


/* =========================================================
   CHECK EXISTING REGISTRATION
========================================================= */

$check = $conn->prepare("
    SELECT registration_id
    FROM registrations
    WHERE event_id = ?
      AND user_id = ?
    LIMIT 1
");

if (!$check) {
    die("Registration Check Error: " . $conn->error);
}

$check->bind_param(
    "ii",
    $event_id,
    $user_id
);

$check->execute();

$check_result = $check->get_result();

$already_registered = (
    $check_result->num_rows > 0
);

$check->close();


/* =========================================================
   PROCESS REGISTRATION
========================================================= */

$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* -----------------------------------------
       CHECK REGISTRATION OPEN
    ----------------------------------------- */

    if (!$registration_open) {

        $error =
            "Registration is currently closed for this event.";

    }


    /* -----------------------------------------
       CHECK ALREADY REGISTERED
    ----------------------------------------- */

    elseif ($already_registered) {

        $error =
            "You are already registered for this event.";

    }


    /* -----------------------------------------
       INSERT
    ----------------------------------------- */

    else {

        $insert = $conn->prepare("
            INSERT INTO registrations
            (
                user_id,
                event_id,
                registered_at,
                status
            )
            VALUES
            (
                ?,
                ?,
                NOW(),
                'pending'
            )
        ");


        if (!$insert) {

            $error =
                "Database Prepare Error: "
                . $conn->error;

        } else {

            $insert->bind_param(
                "ii",
                $user_id,
                $event_id
            );


            if ($insert->execute()) {

                $insert->close();

                $conn->close();


                header(
                    "Location: my_registration.php?success=registered"
                );

                exit();


            } else {

                if ($insert->errno == 1062) {

                    $error =
                        "You are already registered for this event.";

                } else {

                    $error =
                        "Registration failed: "
                        . $insert->error;

                }

                $insert->close();
            }
        }
    }
}


/* =========================================================
   FUNCTIONS
========================================================= */

function clean($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatDate($date)
{
    if (empty($date)) {
        return "Not available";
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return "Not available";
    }

    return date("d M Y", $timestamp);
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
        Register |
        <?php echo clean($event['event_name']); ?>
        | NexArena
    </title>

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/registration.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

    <style>
        [data-theme="dark"] .registration-page {
            background: var(--bg-primary);
        }
        [data-theme="dark"] .event-panel,
        [data-theme="dark"] .registration-card {
            background: var(--bg-card);
            border-color: var(--border-primary);
        }
        [data-theme="dark"] .event-panel h1,
        [data-theme="dark"] .form-heading h2 {
            color: var(--text-primary);
        }
        [data-theme="dark"] .event-description,
        [data-theme="dark"] .form-heading p {
            color: var(--text-muted);
        }
        [data-theme="dark"] .event-detail small,
        [data-theme="dark"] .event-detail strong {
            color: var(--text-primary);
        }
        [data-theme="dark"] .user-box {
            background: var(--bg-tertiary);
            border-color: var(--border-light);
        }
        [data-theme="dark"] .user-box small,
        [data-theme="dark"] .user-box strong,
        [data-theme="dark"] .user-box span {
            color: var(--text-primary);
        }
        [data-theme="dark"] .selected-event {
            background: var(--orange-light);
            border-color: var(--orange-border);
        }
        [data-theme="dark"] .selected-event span,
        [data-theme="dark"] .selected-event strong {
            color: var(--text-primary);
        }
        [data-theme="dark"] .confirm-box {
            color: var(--text-primary);
        }
        [data-theme="dark"] .message.error {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: var(--danger-border);
        }
        [data-theme="dark"] .already-registered,
        [data-theme="dark"] .registration-closed {
            background: var(--bg-tertiary);
            border-color: var(--border-light);
        }
        [data-theme="dark"] .already-registered h3,
        [data-theme="dark"] .registration-closed h3 {
            color: var(--text-primary);
        }
        [data-theme="dark"] .already-registered p,
        [data-theme="dark"] .registration-closed p {
            color: var(--text-muted);
        }
        [data-theme="dark"] .form-note {
            color: var(--text-muted);
        }
        [data-theme="dark"] .back-link {
            color: var(--text-muted);
        }
        [data-theme="dark"] .back-link:hover {
            color: var(--orange);
        }
    </style>

</head>

<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">


<?php include "sidebar.php"; ?>


<!-- =====================================================
     PAGE
====================================================== -->

<main class="registration-page">


    <!-- BACK -->

    <a
        href="events.php"
        class="back-link"
    >

        <i class="fas fa-arrow-left"></i>

        Back to Events

    </a>


    <div class="registration-layout">


        <!-- =================================================
             EVENT INFORMATION
        ================================================== -->

        <section class="event-panel">


            <div class="event-top">

                <span class="event-label">
                    NEXARENA EVENT
                </span>

                <div class="event-icon">
                    🏆
                </div>

            </div>


            <h1>

                <?php
                echo clean($event['event_name']);
                ?>

            </h1>


            <?php if (!empty($event['description'])): ?>

                <p class="event-description">

                    <?php
                    echo clean($event['description']);
                    ?>

                </p>

            <?php elseif (!empty($event['sport_description'])): ?>

                <p class="event-description">

                    <?php
                    echo clean(
                        $event['sport_description']
                    );
                    ?>

                </p>

            <?php else: ?>

                <p class="event-description">

                    Register now and participate
                    in this NexArena event.

                </p>

            <?php endif; ?>


            <div class="event-details">


                <div class="event-detail">

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


                <div class="event-detail">

                    <span class="detail-icon">
                        📍
                    </span>

                    <div>

                        <small>
                            Location
                        </small>

                        <strong>

                            <?php

                            echo !empty(
                                $event['location']
                            )
                                ? clean(
                                    $event['location']
                                )
                                : "Not available";

                            ?>

                        </strong>

                    </div>

                </div>


                <div class="event-detail">

                    <span class="detail-icon">
                        📝
                    </span>

                    <div>

                        <small>
                            Registration Ends
                        </small>

                        <strong>

                            <?php
                            echo formatDate(
                                $event['registration_end']
                            );
                            ?>

                        </strong>

                    </div>

                </div>


            </div>

        </section>


        <!-- =================================================
             REGISTRATION CARD
        ================================================== -->

        <section class="registration-card">


            <div class="form-heading">

                <span>
                    EVENT REGISTRATION
                </span>

                <h2>
                    Confirm Registration
                </h2>

                <p>
                    Review the event information
                    and confirm your registration.
                </p>

            </div>


            <!-- ERROR -->

            <?php if (!empty($error)): ?>

                <div class="message error">

                    <?php
                    echo clean($error);
                    ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <?php if ($already_registered): ?>

                <div class="already-registered">

                    <div class="already-icon">
                        ✓
                    </div>

                    <h3>
                        Already Registered
                    </h3>

                    <p>
                        You have already registered
                        for this event.
                    </p>

                    <a
                        href="my_registration.php"
                        class="register-button"
                    >
                        View My Registration →
                    </a>

                </div>


            <?php elseif (!$registration_open): ?>

                <div class="registration-closed">

                    <div class="closed-icon">
                        !
                    </div>

                    <h3>
                        Registration Closed
                    </h3>

                    <p>
                        Registration is not currently
                        available for this event.
                    </p>

                </div>


            <?php else: ?>


                <form
                    method="POST"
                    action=""
                >


                    <!-- USER -->

                    <div class="user-box">

                        <div class="user-avatar">

                            <?php

                            $name =
                                $_SESSION['full_name']
                                ??
                                $_SESSION['username']
                                ??
                                'U';

                            echo clean(
                                strtoupper(
                                    substr(
                                        $name,
                                        0,
                                        1
                                    )
                                )
                            );

                            ?>

                        </div>


                        <div>

                            <small>
                                REGISTERING AS
                            </small>

                            <strong>

                                <?php

                                echo clean(
                                    $_SESSION['full_name']
                                    ??
                                    $_SESSION['username']
                                    ??
                                    'User'
                                );

                                ?>

                            </strong>

                            <span>

                                <?php

                                echo clean(
                                    $_SESSION['email']
                                    ?? ''
                                );

                                ?>

                            </span>

                        </div>

                    </div>


                    <!-- SELECTED EVENT -->

                    <div class="selected-event">

                        <span>
                            SELECTED EVENT
                        </span>

                        <strong>

                            <?php
                            echo clean(
                                $event['event_name']
                            );
                            ?>

                        </strong>

                    </div>


                    <!-- CONFIRM -->

                    <label class="confirm-box">

                        <input
                            type="checkbox"
                            name="confirm_registration"
                            value="1"
                            required
                        >

                        <span>
                            I confirm that I want to
                            register for this event.
                        </span>

                    </label>


                    <!-- BUTTON -->

                    <button
                        type="submit"
                        class="register-button"
                    >

                        <i class="fas fa-check-circle"></i>

                        Confirm Registration →

                    </button>


                    <p class="form-note">

                        By registering, your registration
                        will be added to your NexArena account.

                    </p>


                </form>


            <?php endif; ?>


        </section>


    </div>


</main>

<!-- Theme JavaScript - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>

</html>


<?php

// Don't close connection here - sidebar needs it
// $conn->close();

?>