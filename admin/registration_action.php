<?php

session_start();


/* =========================================================
   ADMIN ACCESS
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (
    !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'admin' &&
     $_SESSION['role'] !== 'super_admin')
) {
    header("Location: ../login.php");
    exit();
}


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/../db_connect.php';


/* =========================================================
   POST CHECK
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: registrations.php");
    exit();

}


/* =========================================================
   GET DATA
========================================================= */

$registration_id =
    isset($_POST['registration_id'])
    ? (int) $_POST['registration_id']
    : 0;

$action =
    isset($_POST['action'])
    ? $_POST['action']
    : '';


/* =========================================================
   VALIDATION
========================================================= */

if ($registration_id <= 0) {

    header(
        "Location: registrations.php?error=invalid_registration"
    );

    exit();

}


if (
    $action !== 'approve' &&
    $action !== 'reject'
) {

    header(
        "Location: registrations.php?error=invalid_action"
    );

    exit();

}


/* =========================================================
   STATUS
========================================================= */

$newStatus =
    ($action === 'approve')
    ? 'approved'
    : 'rejected';


/* =========================================================
   UPDATE
========================================================= */

$sql = "
    UPDATE event_registrations
    SET status = ?
    WHERE registration_id = ?
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!$stmt) {

    header(
        "Location: registrations.php?error=database"
    );

    exit();

}


mysqli_stmt_bind_param(
    $stmt,
    "si",
    $newStatus,
    $registration_id
);


if (mysqli_stmt_execute($stmt)) {

    if ($action === 'approve') {

        header(
            "Location: registrations.php?success=approved"
        );

    } else {

        header(
            "Location: registrations.php?success=rejected"
        );

    }

    exit();

}


header(
    "Location: registrations.php?error=update_failed"
);

exit();

?>