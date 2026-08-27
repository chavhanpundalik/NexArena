<?php 
 
include 'db_connect.php'; 
 
 
// ======================================== 
// CHECK REQUEST 
// ======================================== 
 
if ($_SERVER["REQUEST_METHOD"] !== "POST") { 
    header("Location: register.php"); 
    exit(); 
} 
 
 
// ======================================== 
// GET FORM DATA 
// ======================================== 
 
$fullname = trim($_POST['fullname'] ?? ''); 
$username = trim($_POST['username'] ?? ''); 
$email = trim($_POST['email'] ?? ''); 
$phone = trim($_POST['phone'] ?? ''); 
 
$password = $_POST['password'] ?? ''; 
$confirm_password = $_POST['confirm_password'] ?? ''; 
 
 
// ======================================== 
// CHECK EMPTY FIELDS 
// ======================================== 
 
if ( 
    $fullname === '' || 
    $username === '' || 
    $email === '' || 
    $phone === '' || 
    $password === '' || 
    $confirm_password === '' 
) { 
 
    die("ERROR: All fields are required."); 
} 
 
 
// ======================================== 
// CHECK EMAIL 
// ======================================== 
 
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
 
    die("ERROR: Invalid email address."); 
} 
 
 
// ======================================== 
// CHECK PASSWORD 
// ======================================== 
 
if ($password !== $confirm_password) { 
 
    die("ERROR: Passwords do not match."); 
} 
 
 
// ======================================== 
// CHECK USERNAME 
// ======================================== 
 
$check_username = $conn->prepare( 
    "SELECT user_id FROM users WHERE username = ?" 
); 
 
if (!$check_username) { 
    die( 
        "Username prepare error: " . 
        $conn->error 
    ); 
} 
 
$check_username->bind_param( 
    "s", 
    $username 
); 
 
if (!$check_username->execute()) { 
    die( 
        "Username execute error: " . 
        $check_username->error 
    ); 
} 
 
$check_username->store_result(); 
 
if ($check_username->num_rows > 0) { 
 
    $check_username->close(); 
 
    die("ERROR: Username already exists."); 
} 
 
$check_username->close(); 
 
 
// ======================================== 
// CHECK EMAIL 
// ======================================== 
 
$check_email = $conn->prepare( 
    "SELECT user_id FROM users WHERE email = ?" 
); 
 
if (!$check_email) { 
    die( 
        "Email prepare error: " . 
        $conn->error 
    ); 
} 
 
$check_email->bind_param( 
    "s", 
    $email 
); 
 
if (!$check_email->execute()) { 
    die( 
        "Email execute error: " . 
        $check_email->error 
    ); 
} 
 
$check_email->store_result(); 
 
if ($check_email->num_rows > 0) { 
 
    $check_email->close(); 
 
    die("ERROR: Email already exists."); 
} 
 
$check_email->close(); 
 
 
// ======================================== 
// HASH PASSWORD 
// ======================================== 
 
$hashed_password = password_hash( 
    $password, 
    PASSWORD_DEFAULT 
); 
 
if ($hashed_password === false) { 
    die("ERROR: Password hashing failed."); 
} 
 
 
// ======================================== 
// INSERT USER 
// ======================================== 
 
$sql = $conn->prepare( 
    "INSERT INTO users 
    (full_name, username, email, phone, password) 
    VALUES (?, ?, ?, ?, ?)" 
); 
 
if (!$sql) { 
 
    die( 
        "INSERT PREPARE ERROR: " . 
        $conn->error 
    ); 
} 
 
 
// ======================================== 
// BIND VALUES 
// ======================================== 
 
$sql->bind_param( 
    "sssss", 
    $fullname, 
    $username, 
    $email, 
    $phone, 
    $hashed_password 
); 
 
 
// ======================================== 
// EXECUTE INSERT 
// ======================================== 
 
if (!$sql->execute()) { 
 
    die( 
        "DATABASE INSERT ERROR: " . 
        $sql->error 
    ); 
} 
 
 
// ======================================== 
// GET NEW USER ID 
// ======================================== 
 
$user_id = $sql->insert_id; 
 
$sql->close(); 


// ========================================
// CREATE USER PROFILE
// ========================================
// This creates an empty profile automatically
// for the newly registered user.

$profile_sql = "
    INSERT INTO user_profiles (user_id)
    VALUES (?)
";

$profile_stmt = $conn->prepare($profile_sql);

if (!$profile_stmt) {

    die(
        "PROFILE PREPARE ERROR: " .
        $conn->error
    );

}

$profile_stmt->bind_param(
    "i",
    $user_id
);

if (!$profile_stmt->execute()) {

    die(
        "PROFILE INSERT ERROR: " .
        $profile_stmt->error
    );

}

$profile_stmt->close();

 
// ======================================== 
// NOW LOAD MAIL CONFIG 
// ======================================== 
 
require_once __DIR__ . '/mail_config.php'; 
 
 
// ======================================== 
// SEND EMAIL 
// ======================================== 
 
$email_sent = sendWelcomeEmail( 
    $email, 
    $fullname, 
    $username 
); 
 
 
// ======================================== 
// CLOSE DATABASE 
// ======================================== 
 
$conn->close(); 
 
 
// ======================================== 
// REDIRECT 
// ======================================== 
 
if ($email_sent) { 
 
    header( 
        "Location: login.php?registered=success" 
    ); 
 
    exit(); 
 
} 
 
 
// ======================================== 
// USER SAVED BUT EMAIL FAILED 
// ======================================== 
 
header( 
    "Location: login.php?registered=success&mail=failed" 
); 
 
exit(); 
 
?>