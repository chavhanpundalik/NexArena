<?php

session_start();

include 'db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// ========================================
// PHPMailer FILES
// ========================================

require_once __DIR__ . '/PHPMailer-FE_v4.11/src/Exception.php';
require_once __DIR__ . '/PHPMailer-FE_v4.11/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-FE_v4.11/src/SMTP.php';


/* =========================================================
   ONLY POST REQUESTS
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: forgot_password.php');
    exit;

}


/* =========================================================
   GET EMAIL
========================================================= */

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $_SESSION['forgot_message'] =
        'Please enter a valid email address.';

    $_SESSION['forgot_message_type'] = 'error';

    header('Location: forgot_password.php');
    exit;

}


/* =========================================================
   FIND USER
========================================================= */

$stmt = $conn->prepare("
    SELECT user_id, full_name, email
    FROM users
    WHERE email = ?
    LIMIT 1
");

if (!$stmt) {

    $_SESSION['forgot_message'] =
        'Something went wrong. Please try again later.';

    $_SESSION['forgot_message_type'] = 'error';

    header('Location: forgot_password.php');
    exit;

}

$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


/*
 * Do not reveal whether an email exists.
 */

if (!$user) {

    $_SESSION['forgot_message'] =
        'If an account exists for this email, a reset link has been sent.';

    $_SESSION['forgot_message_type'] = 'success';

    header('Location: forgot_password.php');
    exit;

}


/* =========================================================
   DELETE OLD RESET TOKENS
========================================================= */

$delete_stmt = $conn->prepare("
    DELETE FROM password_resets
    WHERE user_id = ?
       OR expires_at < NOW()
");

$delete_stmt->bind_param(
    "i",
    $user['user_id']
);

$delete_stmt->execute();

$delete_stmt->close();


/* =========================================================
   GENERATE SECURE TOKEN
========================================================= */

$token = bin2hex(random_bytes(32));

$token_hash = hash(
    'sha256',
    $token
);


/* =========================================================
   TOKEN EXPIRY
   30 MINUTES
========================================================= */

$expires_at = date(
    'Y-m-d H:i:s',
    time() + (30 * 60)
);


/* =========================================================
   STORE TOKEN HASH
========================================================= */

$insert_stmt = $conn->prepare("
    INSERT INTO password_resets
    (
        user_id,
        token_hash,
        expires_at
    )
    VALUES (?, ?, ?)
");

$insert_stmt->bind_param(
    "iss",
    $user['user_id'],
    $token_hash,
    $expires_at
);

$insert_stmt->execute();

$insert_stmt->close();


/* =========================================================
   CREATE RESET LINK
========================================================= */

/*
 * CHANGE THIS to your actual NexArena URL.
 */

$reset_link =
    "http://localhost/nexarena/reset_password.php?token="
    . urlencode($token);


/* =========================================================
   SEND EMAIL
========================================================= */

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();

    /*
     * CHANGE THESE SMTP SETTINGS
     * according to your email provider.
     */

    $mail->Host = 'smtp.gmail.com';

    $mail->SMTPAuth = true;

    $mail->Username = 'pundlikc199@gmail.com';

    $mail->Password = 'mtagfighczgwmiev';

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;


    /* Sender */

    $mail->setFrom(
        'pundlikc199@gmail.com',
        'NexArena'
    );


    /* Receiver */

    $mail->addAddress(
        $user['email'],
        $user['full_name']
    );


    /* Email */

    $mail->isHTML(true);

    $mail->Subject =
        'NexArena Password Reset';


    $safe_name =
        htmlspecialchars(
            $user['full_name'],
            ENT_QUOTES,
            'UTF-8'
        );


    $safe_link =
        htmlspecialchars(
            $reset_link,
            ENT_QUOTES,
            'UTF-8'
        );


    $mail->Body = "

    <div style='
        font-family: Arial, sans-serif;
        background:#f7f7f7;
        padding:40px 20px;
    '>

        <div style='
            max-width:600px;
            margin:auto;
            background:#ffffff;
            padding:40px;
            border-radius:16px;
            border:1px solid #eeeeee;
        '>

            <h2 style='
                color:#111111;
                margin-bottom:10px;
            '>
                Password Reset
            </h2>

            <p style='
                color:#555555;
                line-height:1.7;
            '>
                Hello {$safe_name},
            </p>

            <p style='
                color:#555555;
                line-height:1.7;
            '>
                We received a request to reset your
                NexArena account password.
            </p>

            <p style='
                color:#555555;
                line-height:1.7;
            '>
                Click the button below to create a new password.
            </p>

            <div style='
                margin:30px 0;
                text-align:center;
            '>

                <a
                    href='{$safe_link}'
                    style='
                        display:inline-block;
                        padding:14px 25px;
                        background:#f97316;
                        color:#ffffff;
                        text-decoration:none;
                        border-radius:8px;
                        font-weight:bold;
                    '
                >
                    Reset Password
                </a>

            </div>

            <p style='
                color:#777777;
                font-size:14px;
                line-height:1.6;
            '>
                This link will expire in
                <strong>30 minutes</strong>.
            </p>

            <p style='
                color:#777777;
                font-size:14px;
                line-height:1.6;
            '>
                If you did not request this password reset,
                you can safely ignore this email.
            </p>

            <hr style='
                border:none;
                border-top:1px solid #eeeeee;
                margin:30px 0;
            '>

            <p style='
                color:#999999;
                font-size:12px;
                text-align:center;
            '>
                © NexArena
            </p>

        </div>

    </div>

    ";


    $mail->AltBody =
        "Reset your NexArena password using this link: "
        . $reset_link;


    $mail->send();


} catch (Exception $e) {

    /*
     * Do not expose SMTP errors to users.
     */

    error_log(
        'Password reset email error: '
        . $mail->ErrorInfo
    );

}


/* =========================================================
   FINAL MESSAGE
========================================================= */

$_SESSION['forgot_message'] =
    'If an account exists for this email, a reset link has been sent.';

$_SESSION['forgot_message_type'] =
    'success';

header('Location: forgot_password.php');

exit;