<?php

// Start session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Redirect directly to login page
header("Location: login.php");
exit();

?>