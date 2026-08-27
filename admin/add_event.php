<?php

session_start();

require_once "../db_connect.php";

// ========================================
// CHECK LOGIN
// ========================================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

// ========================================
// CHECK ADMIN ROLE
// ========================================

if (
    !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')
) {
    header("Location: ../index.php?error=access_denied");
    exit();
}

$success = "";
$error = "";

// Default values
$event_name = "";
$sport_id = 0;
$description = "";
$event_date = "";
$registration_start = "";
$registration_end = "";
$location = "";
$status = "active";

// ========================================
// FETCH SPORTS
// ========================================

$sports = [];

$sportQuery = $conn->prepare(
    "SELECT sport_id, sport_name
     FROM sports
     WHERE status = 'active'
     ORDER BY sport_name ASC"
);

if ($sportQuery) {

    $sportQuery->execute();

    $sportResult = $sportQuery->get_result();

    while ($row = $sportResult->fetch_assoc()) {
        $sports[] = $row;
    }

    $sportQuery->close();

} else {
    $error = "Unable to load sports.";
}

// ========================================
// HANDLE FORM
// ========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $event_name = trim($_POST['event_name'] ?? '');
    $sport_id = intval($_POST['sport_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $registration_start = $_POST['registration_start'] ?? '';
    $registration_end = $_POST['registration_end'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $status = $_POST['status'] ?? 'active';

    $created_by = $_SESSION['user_id'];

    // ========================================
    // VALIDATION
    // ========================================

    if ($event_name === '') {

        $error = "Please enter the event name.";

    } elseif ($sport_id <= 0) {

        $error = "Please select a sport.";

    } elseif ($event_date === '') {

        $error = "Please select the event date.";

    } elseif ($registration_start === '') {

        $error = "Please select the registration start date.";

    } elseif ($registration_end === '') {

        $error = "Please select the registration end date.";

    } elseif ($location === '') {

        $error = "Please enter the event location.";

    } elseif (!in_array($status, ['active', 'inactive'], true)) {

        $error = "Invalid event status.";

    } elseif ($registration_start > $registration_end) {

        $error = "Registration start date cannot be after registration end date.";

    } elseif ($registration_end > $event_date) {

        $error = "Registration end date cannot be after the event date.";

    }

    // ========================================
    // CHECK SPORT
    // ========================================

    if ($error === "") {

        $checkSport = $conn->prepare(
            "SELECT sport_id, sport_name
             FROM sports
             WHERE sport_id = ?
             AND status = 'active'"
        );

        if (!$checkSport) {

            $error = "Database error while checking sport.";

        } else {

            $checkSport->bind_param("i", $sport_id);
            $checkSport->execute();

            $sportResult = $checkSport->get_result();

            if ($sportResult->num_rows !== 1) {

                $error = "Selected sport does not exist or is inactive.";

            } else {

                $sportData = $sportResult->fetch_assoc();
                $sport_name = $sportData['sport_name'];

            }

            $checkSport->close();
        }
    }

    // ========================================
    // INSERT EVENT
    // ========================================

    if ($error === "") {

        $insert = $conn->prepare(
            "INSERT INTO events
            (
                event_name,
                sport_id,
                sport,
                description,
                event_date,
                registration_start,
                registration_end,
                location,
                status,
                created_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$insert) {

            $error = "Database prepare error: " . $conn->error;

        } else {

            $insert->bind_param(
                "sisssssssi",
                $event_name,
                $sport_id,
                $sport_name,
                $description,
                $event_date,
                $registration_start,
                $registration_end,
                $location,
                $status,
                $created_by
            );

            if ($insert->execute()) {

                $success = "Event created successfully.";

                $event_name = "";
                $sport_id = 0;
                $description = "";
                $event_date = "";
                $registration_start = "";
                $registration_end = "";
                $location = "";
                $status = "active";

            } else {

                $error = "Failed to create event: " . $insert->error;
            }

            $insert->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Event | NexArena</title>

    <link
        rel="stylesheet"
        href="assets/add_event.css"
    >

</head>

<body>
<?php include "sidebar.php"; ?>

    <!-- MAIN -->

    <main class="container">

        <div class="page-header">

            <span>EVENT MANAGEMENT</span>

            <h1>Create New Event</h1>

            <p>
                Add a new sports event to NexArena.
            </p>

        </div>


        <div class="form-card">

            <?php if ($success !== ""): ?>

                <div class="message success">
                    <?php echo htmlspecialchars($success); ?>
                </div>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <div class="message error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="">

                <div class="form-grid">

                    <!-- EVENT NAME -->

                    <div class="form-group full-width">

                        <label for="event_name">
                            Event Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="event_name"
                            name="event_name"
                            placeholder="Enter event name"
                            value="<?php echo htmlspecialchars($event_name); ?>"
                            required
                        >

                    </div>


                    <!-- SPORT -->

                    <div class="form-group">

                        <label for="sport_id">
                            Sport <span>*</span>
                        </label>

                        <select
                            id="sport_id"
                            name="sport_id"
                            required
                        >

                            <option value="">
                                Select Sport
                            </option>

                            <?php foreach ($sports as $sport): ?>

                                <option
                                    value="<?php echo (int)$sport['sport_id']; ?>"
                                    <?php
                                    echo (
                                        (int)$sport_id ===
                                        (int)$sport['sport_id']
                                    )
                                    ? 'selected'
                                    : '';
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $sport['sport_name']
                                    );
                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label for="status">
                            Status <span>*</span>
                        </label>

                        <select
                            id="status"
                            name="status"
                            required
                        >

                            <option
                                value="active"
                                <?php
                                echo $status === 'active'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="inactive"
                                <?php
                                echo $status === 'inactive'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>


                    <!-- EVENT DATE -->

                    <div class="form-group">

                        <label for="event_date">
                            Event Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            id="event_date"
                            name="event_date"
                            value="<?php echo htmlspecialchars($event_date); ?>"
                            required
                        >

                    </div>


                    <!-- LOCATION -->

                    <div class="form-group">

                        <label for="location">
                            Location <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="location"
                            name="location"
                            placeholder="Enter event location"
                            value="<?php echo htmlspecialchars($location); ?>"
                            required
                        >

                    </div>


                    <!-- REGISTRATION START -->

                    <div class="form-group">

                        <label for="registration_start">
                            Registration Start <span>*</span>
                        </label>

                        <input
                            type="date"
                            id="registration_start"
                            name="registration_start"
                            value="<?php echo htmlspecialchars($registration_start); ?>"
                            required
                        >

                    </div>


                    <!-- REGISTRATION END -->

                    <div class="form-group">

                        <label for="registration_end">
                            Registration End <span>*</span>
                        </label>

                        <input
                            type="date"
                            id="registration_end"
                            name="registration_end"
                            value="<?php echo htmlspecialchars($registration_end); ?>"
                            required
                        >

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group full-width">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            placeholder="Enter event description..."
                        ><?php echo htmlspecialchars($description); ?></textarea>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="actions">

                    <a
                        href="dashboard.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Create Event
                    </button>

                </div>

            </form>

        </div>

    </main>

</body>

</html>

<?php

$conn->close();

?>