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
    (
        $_SESSION['role'] !== 'admin' &&
        $_SESSION['role'] !== 'super_admin'
    )
) {
    header("Location: ../index.php?error=access_denied");
    exit();
}

// ========================================
// GET EVENT ID
// ========================================

$event_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$event_id || $event_id <= 0) {

    $conn->close();

    header("Location: events.php?error=invalid_event");
    exit();
}

// ========================================
// CHECK WHETHER EVENT EXISTS
// ========================================

$check = $conn->prepare(
    "SELECT event_id, event_name
     FROM events
     WHERE event_id = ?"
);

if (!$check) {

    die("Database Prepare Error: " . $conn->error);
}

$check->bind_param("i", $event_id);

if (!$check->execute()) {

    $check->close();
    $conn->close();

    die("Database Execute Error: " . $check->error);
}

$result = $check->get_result();

if ($result->num_rows !== 1) {

    $check->close();
    $conn->close();

    header("Location: events.php?error=event_not_found");
    exit();
}

$event = $result->fetch_assoc();

$check->close();

// ========================================
// DELETE EVENT
// ========================================

$delete = $conn->prepare(
    "DELETE FROM events
     WHERE event_id = ?"
);

if (!$delete) {

    $conn->close();

    die("Database Prepare Error: " . $conn->error);
}

$delete->bind_param("i", $event_id);

if ($delete->execute()) {

    $delete->close();
    $conn->close();

    header(
        "Location: events.php?success=event_deleted"
    );

    exit();

} else {

    $error = $delete->error;

    $delete->close();
    $conn->close();

    header(
        "Location: events.php?error=delete_failed"
    );

    exit();
}

?>