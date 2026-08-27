<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// ========================================
// PHPMailer FILES
// ========================================

require_once __DIR__ . '/PHPMailer-FE_v4.11/src/Exception.php';
require_once __DIR__ . '/PHPMailer-FE_v4.11/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-FE_v4.11/src/SMTP.php';


// ========================================
// SEND WELCOME EMAIL
// ========================================

function sendWelcomeEmail($email, $fullname, $username)
{
    $mail = new PHPMailer(true);

    try {

        // ========================================
        // SMTP SETTINGS
        // ========================================

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';

        $mail->SMTPAuth = true;

        $mail->Username = 'pundlikc199@gmail.com';

        /*
        IMPORTANT:
        Use your NEW Gmail App Password here.

        DO NOT use your normal Gmail password.

        Example:
        $mail->Password = 'abcd efgh ijkl mnop';

        You can also remove spaces if required:
        $mail->Password = 'abcdefghijklmnop';
        */

        $mail->Password = 'mtagfighczgwmiev';


        // ========================================
        // ENCRYPTION
        // ========================================

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;


        // ========================================
        // SENDER
        // ========================================

        $mail->setFrom(
            'pundlikc199@gmail.com',
            'NexArena'
        );


        // ========================================
        // RECEIVER
        // ========================================

        $mail->addAddress(
            $email,
            $fullname
        );


        // ========================================
        // EMAIL FORMAT
        // ========================================

        $mail->isHTML(true);


        // ========================================
        // SUBJECT
        // ========================================

        $mail->Subject =
            'Welcome to NexArena - Registration Successful';


        // ========================================
        // SAFE DISPLAY VALUES
        // ========================================

        $safeFullname =
            htmlspecialchars(
                $fullname,
                ENT_QUOTES,
                'UTF-8'
            );

        $safeUsername =
            htmlspecialchars(
                $username,
                ENT_QUOTES,
                'UTF-8'
            );

        $safeEmail =
            htmlspecialchars(
                $email,
                ENT_QUOTES,
                'UTF-8'
            );


        // ========================================
        // HTML EMAIL
        // ========================================

        $mail->Body = "

        <div style='
            font-family: Arial, Helvetica, sans-serif;
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            background: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 12px;
            color: #111111;
        '>

            <h2 style='
                color: #ff6600;
                margin-bottom: 20px;
            '>
                Welcome to NexArena! 🏆
            </h2>


            <p>
                Hello <strong>{$safeFullname}</strong>,
            </p>


            <p>
                Your NexArena account has been
                created successfully.
            </p>


            <hr style='
                border: 0;
                border-top: 1px solid #eeeeee;
                margin: 25px 0;
            '>


            <p>
                <strong>Full Name:</strong>
                {$safeFullname}
            </p>


            <p>
                <strong>Username:</strong>
                {$safeUsername}
            </p>


            <p>
                <strong>Email:</strong>
                {$safeEmail}
            </p>


            <br>


            <p>
                Thank you for joining NexArena!
            </p>


            <p>
                <strong>NexArena Team</strong>
            </p>

        </div>

        ";


        // ========================================
        // PLAIN TEXT VERSION
        // ========================================

        $mail->AltBody =
            "Welcome to NexArena!\n\n" .

            "Hello $fullname,\n\n" .

            "Your account has been created successfully.\n\n" .

            "Full Name: $fullname\n" .

            "Username: $username\n" .

            "Email: $email\n\n" .

            "Thank you for joining NexArena!\n\n" .

            "NexArena Team";


        // ========================================
        // SEND EMAIL
        // ========================================

        $mail->send();


        return true;


    } catch (Exception $e) {

        // ========================================
        // ERROR LOG
        // ========================================

        error_log(
            "PHPMailer Error: " .
            $mail->ErrorInfo
        );


        return false;
    }
}

?>