<?php

session_start();

include 'db_connect.php';


// Check whether user is logged in

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();

}


// Get logged-in user's ID

$user_id = $_SESSION['user_id'];


// Get feedback data

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$subject = trim($_POST['subject']);
$message = trim($_POST['message']);


// Check required fields

if (
    empty($name) ||
    empty($email) ||
    empty($subject) ||
    empty($message)
) {

    die("Please fill all fields.");

}


// Validate email

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    die("Invalid email address.");

}


// Insert feedback

$sql = $conn->prepare(
    "INSERT INTO feedback
    (user_id, name, email, subject, message)
    VALUES (?, ?, ?, ?, ?)"
);

$sql->bind_param(
    "issss",
    $user_id,
    $name,
    $email,
    $subject,
    $message
);


if ($sql->execute()) {

    echo "Feedback submitted successfully!";

} else {

    echo "Feedback submission failed.";

}


$sql->close();
$conn->close();

?>