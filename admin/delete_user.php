<?php
session_start();

require_once "../db_connect.php";

/* =========================
   ADMIN AUTHENTICATION
========================= */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit();
}


/* =========================
   GET USER ID
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = (int) $_GET['id'];


/* =========================
   PREVENT SELF DELETE
========================= */

if ($user_id === (int) $_SESSION['user_id']) {

    header("Location: users.php?error=self_delete");
    exit();
}


/* =========================
   GET TARGET USER
========================= */

$stmt = $conn->prepare(
    "SELECT user_id, full_name, role
     FROM users
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    header("Location: users.php?error=user_not_found");
    exit();
}

$user = $result->fetch_assoc();


/* =========================
   PROTECT SUPER ADMIN
========================= */

if ($user['role'] === 'super_admin') {

    header("Location: users.php?error=protected_user");
    exit();
}


/* =========================
   DELETE
========================= */

$conn->begin_transaction();

try {


    /* =========================
       DELETE NOTIFICATIONS
    ========================= */

    $stmt = $conn->prepare(
        "DELETE FROM notifications
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();


    /* =========================
       DELETE TEAM MEMBERSHIP
    ========================= */

    $stmt = $conn->prepare(
        "DELETE FROM team_members
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();


    /* =========================
       DELETE EVENT REGISTRATIONS
    ========================= */

    $stmt = $conn->prepare(
        "DELETE FROM event_registrations
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();


    /* =========================
       DELETE USER PROFILE
    ========================= */

    $stmt = $conn->prepare(
        "DELETE FROM user_profiles
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();


    /* =========================
       DELETE USER
    ========================= */

    $stmt = $conn->prepare(
        "DELETE FROM users
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();


    /* =========================
       COMMIT
    ========================= */

    $conn->commit();


    header("Location: users.php?success=user_deleted");
    exit();


} catch (Exception $e) {

    $conn->rollback();

    header("Location: users.php?error=delete_failed");
    exit();

}