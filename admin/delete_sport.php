<?php
session_start();

require_once "../db_connect.php";

/* =========================================================
   GET SPORT ID
========================================================= */

$sport_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($sport_id <= 0) {
    header("Location: sports.php?error=invalid_sport");
    exit();
}


/* =========================================================
   CHECK SPORT EXISTS
========================================================= */

$check_sport_sql = "
    SELECT sport_id, sport_name
    FROM sports
    WHERE sport_id = ?
    LIMIT 1
";

$check_sport_stmt = mysqli_prepare($conn, $check_sport_sql);

if (!$check_sport_stmt) {
    die("DATABASE ERROR: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $check_sport_stmt,
    "i",
    $sport_id
);

mysqli_stmt_execute($check_sport_stmt);

$sport_result = mysqli_stmt_get_result($check_sport_stmt);

$sport = mysqli_fetch_assoc($sport_result);

mysqli_stmt_close($check_sport_stmt);


/* =========================================================
   SPORT NOT FOUND
========================================================= */

if (!$sport) {
    header("Location: sports.php?error=sport_not_found");
    exit();
}


/* =========================================================
   CHECK WHETHER SPORT IS USED BY EVENTS
========================================================= */

$event_check_sql = "
    SELECT COUNT(*) AS total_events
    FROM events
    WHERE sport_id = ?
";

$event_check_stmt = mysqli_prepare(
    $conn,
    $event_check_sql
);

if (!$event_check_stmt) {
    die("EVENT CHECK ERROR: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $event_check_stmt,
    "i",
    $sport_id
);

mysqli_stmt_execute($event_check_stmt);

$event_result =
    mysqli_stmt_get_result($event_check_stmt);

$event_data =
    mysqli_fetch_assoc($event_result);

$total_events =
    (int) $event_data['total_events'];

mysqli_stmt_close($event_check_stmt);


/* =========================================================
   SPORT IS USED BY EVENTS
========================================================= */

if ($total_events > 0) {

    header(
        "Location: sports.php?error=sport_in_use"
    );

    exit();
}


/* =========================================================
   DELETE SPORT
========================================================= */

$delete_sql = "
    DELETE FROM sports
    WHERE sport_id = ?
    LIMIT 1
";

$delete_stmt = mysqli_prepare(
    $conn,
    $delete_sql
);

if (!$delete_stmt) {
    die("DELETE ERROR: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $delete_stmt,
    "i",
    $sport_id
);


if (mysqli_stmt_execute($delete_stmt)) {

    mysqli_stmt_close($delete_stmt);

    header(
        "Location: sports.php?success=sport_deleted"
    );

    exit();

}


/* =========================================================
   DELETE FAILED
========================================================= */

$error = mysqli_stmt_error($delete_stmt);

mysqli_stmt_close($delete_stmt);

header(
    "Location: sports.php?error=delete_failed"
);

exit();
?>