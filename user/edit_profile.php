<?php

session_start();

require_once "../db_connect.php";


/* ========================================
   CHECK LOGIN
======================================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php?error=login_required");
    exit();

}


/* ========================================
   CHECK USER ROLE
======================================== */

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {

    header("Location: ../login.php?error=access_denied");
    exit();

}


$user_id = (int) $_SESSION['user_id'];


/* ========================================
   GET USER DATA
======================================== */

$user_sql = "
    SELECT
        u.full_name,
        u.username,
        u.email,
        u.phone,

        up.profile_id,
        up.profile_image,
        up.date_of_birth,
        up.gender,
        up.address,
        up.city,
        up.state,
        up.bio

    FROM users u

    LEFT JOIN user_profiles up
        ON u.user_id = up.user_id

    WHERE u.user_id = ?

    LIMIT 1
";


$user_stmt = $conn->prepare($user_sql);


if (!$user_stmt) {

    die(
        "PROFILE QUERY ERROR: " .
        $conn->error
    );

}


$user_stmt->bind_param(
    "i",
    $user_id
);

$user_stmt->execute();

$user_result = $user_stmt->get_result();


if ($user_result->num_rows === 0) {

    $user_stmt->close();

    die("User not found.");

}


$user = $user_result->fetch_assoc();

$user_stmt->close();


/* ========================================
   DEFAULT VALUES
======================================== */

$profile_image = $user['profile_image'] ?? '';

$date_of_birth = $user['date_of_birth'] ?? '';

$gender = $user['gender'] ?? '';

$address = $user['address'] ?? '';

$city = $user['city'] ?? '';

$state = $user['state'] ?? '';

$bio = $user['bio'] ?? '';


/* ========================================
   UPDATE PROFILE
======================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $date_of_birth = trim(
        $_POST['date_of_birth'] ?? ''
    );


    $gender = trim(
        $_POST['gender'] ?? ''
    );


    $address = trim(
        $_POST['address'] ?? ''
    );


    $city = trim(
        $_POST['city'] ?? ''
    );


    $state = trim(
        $_POST['state'] ?? ''
    );


    $bio = trim(
        $_POST['bio'] ?? ''
    );


    /* ========================================
       UPDATE USER PROFILE
    ======================================== */

    $update_sql = "
        UPDATE user_profiles

        SET
            date_of_birth = ?,
            gender = ?,
            address = ?,
            city = ?,
            state = ?,
            bio = ?

        WHERE user_id = ?
    ";


    $update_stmt = $conn->prepare(
        $update_sql
    );


    if (!$update_stmt) {

        die(
            "UPDATE PREPARE ERROR: " .
            $conn->error
        );

    }


    $update_stmt->bind_param(
        "ssssssi",
        $date_of_birth,
        $gender,
        $address,
        $city,
        $state,
        $bio,
        $user_id
    );


    if (!$update_stmt->execute()) {

        die(
            "PROFILE UPDATE ERROR: " .
            $update_stmt->error
        );

    }


    $update_stmt->close();


    /* ========================================
       PROFILE IMAGE
    ======================================== */

    if (
        isset($_FILES['profile_image']) &&
        $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {


        if (
            $_FILES['profile_image']['error']
            !== UPLOAD_ERR_OK
        ) {

            die("Profile image upload failed.");

        }


        $allowed_types = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];


        $file_type =
            $_FILES['profile_image']['type'];


        if (!in_array(
            $file_type,
            $allowed_types,
            true
        )) {

            die(
                "Only JPG, PNG and WEBP images are allowed."
            );

        }


        /* Maximum 2 MB */

        if (
            $_FILES['profile_image']['size']
            > 2 * 1024 * 1024
        ) {

            die(
                "Profile image must be less than 2 MB."
            );

        }


        /* ========================================
           CREATE UPLOAD DIRECTORY
        ======================================== */

        $upload_dir =
            __DIR__ . "/uploads/profile_images/";


        if (!is_dir($upload_dir)) {

            mkdir(
                $upload_dir,
                0755,
                true
            );

        }


        /* ========================================
           FILE EXTENSION
        ======================================== */

        $extension =
            strtolower(
                pathinfo(
                    $_FILES['profile_image']['name'],
                    PATHINFO_EXTENSION
                )
            );


        /* ========================================
           UNIQUE FILE NAME
        ======================================== */

        $new_filename =
            "user_" .
            $user_id .
            "_" .
            time() .
            "." .
            $extension;


        $target_file =
            $upload_dir .
            $new_filename;


        /* ========================================
           MOVE IMAGE
        ======================================== */

        if (!move_uploaded_file(
            $_FILES['profile_image']['tmp_name'],
            $target_file
        )) {

            die(
                "Unable to upload profile image."
            );

        }


        /* ========================================
           DATABASE IMAGE PATH
        ======================================== */

        $image_path =
            "uploads/profile_images/" .
            $new_filename;


        $image_sql = "
            UPDATE user_profiles

            SET profile_image = ?

            WHERE user_id = ?
        ";


        $image_stmt =
            $conn->prepare($image_sql);


        if (!$image_stmt) {

            die(
                "IMAGE UPDATE ERROR: " .
                $conn->error
            );

        }


        $image_stmt->bind_param(
            "si",
            $image_path,
            $user_id
        );


        if (!$image_stmt->execute()) {

            die(
                "IMAGE DATABASE ERROR: " .
                $image_stmt->error
            );

        }


        $image_stmt->close();

    }


    /* ========================================
       SUCCESS
    ======================================== */

    $conn->close();


    header(
        "Location: profile.php?updated=success"
    );

    exit();

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

    <title>
        Edit Profile | NexArena
    </title>


    <link
        rel="stylesheet"
        href="assets/edit_profile.css"
    >

</head>


<body>


<?php include "sidebar.php"; ?>


<!-- ========================================
     MAIN CONTENT
======================================== -->

<div class="edit-profile-main">


    <main class="edit-profile-container">


        <!-- ========================================
             PAGE HEADER
        ======================================== -->

        <section class="page-header">

            <div>

                <span>
                    ACCOUNT SETTINGS
                </span>

                <h1>
                    Edit Profile
                </h1>

                <p>
                    Update your personal information
                    and NexArena player profile.
                </p>

            </div>


            <a
                href="profile.php"
                class="back-btn"
            >
                ← Back to Profile
            </a>

        </section>



        <!-- ========================================
             FORM
        ======================================== -->

        <form
            method="POST"
            enctype="multipart/form-data"
            class="profile-form"
        >


            <!-- ========================================
                 PROFILE IMAGE
            ======================================== -->

            <section class="form-section">


                <div class="section-title">

                    <span>
                        PROFILE
                    </span>

                    <h2>
                        Profile Picture
                    </h2>

                </div>


                <div class="image-area">


                    <div class="current-avatar">

                        <?php if (!empty($profile_image)): ?>

                            <img
                                src="<?php
                                    echo htmlspecialchars(
                                        $profile_image,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>"
                                alt="Profile Image"
                            >

                        <?php else: ?>

                            <span>
                                <?php
                                echo strtoupper(
                                    substr(
                                        $user['full_name'] ?? 'U',
                                        0,
                                        2
                                    )
                                );
                                ?>
                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="image-content">

                        <h3>
                            Profile Photo
                        </h3>

                        <p>
                            Upload a JPG, PNG or WEBP image.
                            Maximum size: 2 MB.
                        </p>


                        <label
                            for="profile_image"
                            class="upload-btn"
                        >
                            Choose Image
                        </label>


                        <input
                            type="file"
                            id="profile_image"
                            name="profile_image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                    </div>


                </div>


            </section>



            <!-- ========================================
                 ACCOUNT INFORMATION
            ======================================== -->

            <section class="form-section">


                <div class="section-title">

                    <span>
                        ACCOUNT
                    </span>

                    <h2>
                        Account Information
                    </h2>

                </div>


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Full Name
                        </label>

                        <input
                            type="text"
                            value="<?php
                                echo htmlspecialchars(
                                    $user['full_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            readonly
                        >

                        <small>
                            Contact an administrator if
                            you need to change your name.
                        </small>

                    </div>


                    <div class="form-group">

                        <label>
                            Username
                        </label>

                        <input
                            type="text"
                            value="<?php
                                echo htmlspecialchars(
                                    $user['username'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            value="<?php
                                echo htmlspecialchars(
                                    $user['email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Phone
                        </label>

                        <input
                            type="text"
                            value="<?php
                                echo htmlspecialchars(
                                    $user['phone'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            readonly
                        >

                    </div>


                </div>


            </section>



            <!-- ========================================
                 PERSONAL INFORMATION
            ======================================== -->

            <section class="form-section">


                <div class="section-title">

                    <span>
                        PERSONAL
                    </span>

                    <h2>
                        Personal Information
                    </h2>

                </div>


                <div class="form-grid">


                    <div class="form-group">

                        <label for="date_of_birth">
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            value="<?php
                                echo htmlspecialchars(
                                    $date_of_birth,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label for="gender">
                            Gender
                        </label>

                        <select
                            id="gender"
                            name="gender"
                        >

                            <option value="">
                                Select Gender
                            </option>

                            <option
                                value="Male"
                                <?php
                                echo (
                                    $gender === 'Male'
                                    ? 'selected'
                                    : ''
                                );
                                ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?php
                                echo (
                                    $gender === 'Female'
                                    ? 'selected'
                                    : ''
                                );
                                ?>
                            >
                                Female
                            </option>

                            <option
                                value="Other"
                                <?php
                                echo (
                                    $gender === 'Other'
                                    ? 'selected'
                                    : ''
                                );
                                ?>
                            >
                                Other
                            </option>

                        </select>

                    </div>


                </div>


            </section>



            <!-- ========================================
                 ADDRESS
            ======================================== -->

            <section class="form-section">


                <div class="section-title">

                    <span>
                        LOCATION
                    </span>

                    <h2>
                        Address Information
                    </h2>

                </div>


                <div class="form-group">

                    <label for="address">
                        Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        placeholder="Enter your address"
                    ><?php
                        echo htmlspecialchars(
                            $address,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?></textarea>

                </div>


                <div class="form-grid">


                    <div class="form-group">

                        <label for="city">
                            City
                        </label>

                        <input
                            type="text"
                            id="city"
                            name="city"
                            value="<?php
                                echo htmlspecialchars(
                                    $city,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Enter city"
                        >

                    </div>


                    <div class="form-group">

                        <label for="state">
                            State
                        </label>

                        <input
                            type="text"
                            id="state"
                            name="state"
                            value="<?php
                                echo htmlspecialchars(
                                    $state,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="Enter state"
                        >

                    </div>


                </div>


            </section>



            <!-- ========================================
                 BIO
            ======================================== -->

            <section class="form-section">


                <div class="section-title">

                    <span>
                        ABOUT
                    </span>

                    <h2>
                        About Me
                    </h2>

                </div>


                <div class="form-group">

                    <label for="bio">
                        Bio
                    </label>

                    <textarea
                        id="bio"
                        name="bio"
                        rows="5"
                        maxlength="500"
                        placeholder="Tell other NexArena players a little about yourself..."
                    ><?php
                        echo htmlspecialchars(
                            $bio,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?></textarea>

                    <small>
                        Maximum 500 characters.
                    </small>

                </div>


            </section>



            <!-- ========================================
                 ACTIONS
            ======================================== -->

            <div class="form-actions">

                <a
                    href="profile.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Changes
                </button>

            </div>


        </form>


    </main>


    <!-- ========================================
         FOOTER
    ======================================== -->

    <footer>

        <div class="footer-logo">
            <span>Nex</span>Arena
        </div>

        <p>
            © <?php echo date("Y"); ?>
            NexArena. All Rights Reserved.
        </p>

    </footer>


</div>


</body>

</html>