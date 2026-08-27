<?php

session_start();

require_once "../db_connect.php";



/* =====================================================
   ADMIN ACCESS CHECK
===================================================== */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit();
}


/* =====================================================
   GET USER ID
===================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = (int) $_GET['id'];


/* =====================================================
   FETCH USER + PROFILE
===================================================== */

$sql = "SELECT
            u.user_id,
            u.full_name,
            u.username,
            u.email,
            u.phone,
            u.role,

            up.profile_id,
            up.profile_image,
            up.date_of_birth,
            up.gender,
            up.address,
            up.city,
            up.state,
            up.bio,
            up.updated_at

        FROM users u

        LEFT JOIN user_profiles up
            ON u.user_id = up.user_id

        WHERE u.user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("DATABASE ERROR: " . $conn->error);
}

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();


/* =====================================================
   USER NOT FOUND
===================================================== */

if ($result->num_rows === 0) {

    header("Location: users.php?error=user_not_found");

    exit();
}

$user = $result->fetch_assoc();


/* =====================================================
   SUPER ADMIN PROTECTION
===================================================== */

if ($user['role'] === 'super_admin') {

    header("Location: view_user.php?id=" . $user_id);

    exit();
}


/* =====================================================
   VARIABLES
===================================================== */

$success = "";
$error = "";


/* =====================================================
   FORM SUBMISSION
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /* =================================================
       GET FORM DATA
    ================================================= */

    $full_name = trim($_POST['full_name'] ?? '');

    $username = trim($_POST['username'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $phone = trim($_POST['phone'] ?? '');

    $role = trim($_POST['role'] ?? 'user');

    $date_of_birth = trim($_POST['date_of_birth'] ?? '');

    $gender = trim($_POST['gender'] ?? '');

    $address = trim($_POST['address'] ?? '');

    $city = trim($_POST['city'] ?? '');

    $state = trim($_POST['state'] ?? '');

    $bio = trim($_POST['bio'] ?? '');


    /* =================================================
       VALIDATION
    ================================================= */

    if (
        $full_name === '' ||
        $username === '' ||
        $email === '' ||
        $phone === ''
    ) {

        $error = "Please fill all required fields.";

    }


    /* =================================================
       VALID ROLE
    ================================================= */

    elseif (!in_array($role, ['user', 'admin'], true)) {

        $error = "Invalid role selected.";

    }


    /* =================================================
       VALID EMAIL
    ================================================= */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }


    /* =================================================
       CHECK USERNAME DUPLICATE
    ================================================= */

    else {

        $checkUsername = $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE username = ?
             AND user_id != ?
             LIMIT 1"
        );

        $checkUsername->bind_param(
            "si",
            $username,
            $user_id
        );

        $checkUsername->execute();

        $usernameResult = $checkUsername->get_result();


        if ($usernameResult->num_rows > 0) {

            $error = "This username is already being used.";

        }
    }


    /* =================================================
       CHECK EMAIL DUPLICATE
    ================================================= */

    if ($error === '') {

        $checkEmail = $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE email = ?
             AND user_id != ?
             LIMIT 1"
        );

        $checkEmail->bind_param(
            "si",
            $email,
            $user_id
        );

        $checkEmail->execute();

        $emailResult = $checkEmail->get_result();


        if ($emailResult->num_rows > 0) {

            $error = "This email address is already being used.";

        }
    }


    /* =================================================
       UPDATE DATABASE
    ================================================= */

    if ($error === '') {


        /* =============================================
           START TRANSACTION
        ============================================= */

        $conn->begin_transaction();


        try {


            /* =========================================
               UPDATE USERS TABLE
            ========================================= */

            $updateUser = $conn->prepare(
                "UPDATE users

                 SET
                    full_name = ?,
                    username = ?,
                    email = ?,
                    phone = ?,
                    role = ?

                 WHERE user_id = ?"
            );


            if (!$updateUser) {

                throw new Exception(
                    "User update query failed."
                );
            }


            $updateUser->bind_param(
                "sssssi",
                $full_name,
                $username,
                $email,
                $phone,
                $role,
                $user_id
            );


            if (!$updateUser->execute()) {

                throw new Exception(
                    "Unable to update user account."
                );
            }


            /* =========================================
               CHECK USER PROFILE
            ========================================= */

            $checkProfile = $conn->prepare(
                "SELECT profile_id
                 FROM user_profiles
                 WHERE user_id = ?
                 LIMIT 1"
            );


            if (!$checkProfile) {

                throw new Exception(
                    "Profile check failed."
                );
            }


            $checkProfile->bind_param(
                "i",
                $user_id
            );

            $checkProfile->execute();

            $profileResult = $checkProfile->get_result();


            /* =========================================
               PROFILE EXISTS → UPDATE
            ========================================= */

            if ($profileResult->num_rows > 0) {


                $updateProfile = $conn->prepare(
                    "UPDATE user_profiles

                     SET
                        date_of_birth = NULLIF(?, ''),
                        gender = NULLIF(?, ''),
                        address = NULLIF(?, ''),
                        city = NULLIF(?, ''),
                        state = NULLIF(?, ''),
                        bio = NULLIF(?, '')

                     WHERE user_id = ?"
                );


                if (!$updateProfile) {

                    throw new Exception(
                        "Profile update query failed."
                    );
                }


                $updateProfile->bind_param(
                    "ssssssi",
                    $date_of_birth,
                    $gender,
                    $address,
                    $city,
                    $state,
                    $bio,
                    $user_id
                );


                if (!$updateProfile->execute()) {

                    throw new Exception(
                        "Unable to update profile."
                    );
                }

            }


            /* =========================================
               PROFILE DOES NOT EXIST → CREATE
            ========================================= */

            else {


                $insertProfile = $conn->prepare(
                    "INSERT INTO user_profiles
                    (
                        user_id,
                        date_of_birth,
                        gender,
                        address,
                        city,
                        state,
                        bio
                    )

                    VALUES
                    (
                        ?,
                        NULLIF(?, ''),
                        NULLIF(?, ''),
                        NULLIF(?, ''),
                        NULLIF(?, ''),
                        NULLIF(?, ''),
                        NULLIF(?, '')
                    )"
                );


                if (!$insertProfile) {

                    throw new Exception(
                        "Profile insert query failed."
                    );
                }


                $insertProfile->bind_param(
                    "issssss",
                    $user_id,
                    $date_of_birth,
                    $gender,
                    $address,
                    $city,
                    $state,
                    $bio
                );


                if (!$insertProfile->execute()) {

                    throw new Exception(
                        "Unable to create user profile."
                    );
                }
            }


            /* =========================================
               COMMIT
            ========================================= */

            $conn->commit();


            $success = "User information updated successfully.";


            /* =========================================
               REFRESH USER DATA
            ========================================= */

            $stmt->execute();

            $result = $stmt->get_result();

            $user = $result->fetch_assoc();


        } catch (Exception $e) {


            /* =========================================
               ROLLBACK
            ========================================= */

            $conn->rollback();


            $error = $e->getMessage();
        }
    }
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

    <title>Edit User | NexArena</title>


    <!-- EDIT USER CSS -->

    <link
        rel="stylesheet"
        href="assets/edit_user.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body>


<?php include "sidebar.php"; ?>


<main class="admin-main">


    <div class="edit-page">


        <!-- =========================================
             BACK
        ========================================== -->

        <a
            href="users.php"
            class="back-link"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to User Management

        </a>


        <!-- =========================================
             EDIT CARD
        ========================================== -->

        <div class="edit-card">


            <!-- HEADER -->

            <div class="edit-header">

                <h1>
                    Edit User
                </h1>

                <p>
                    Update account and profile information.
                </p>

            </div>


            <!-- BODY -->

            <div class="edit-body">


                <!-- =================================
                     SUCCESS MESSAGE
                ================================== -->

                <?php if ($success !== ''): ?>

                    <div class="alert alert-success">

                        <i class="fa-solid fa-circle-check"></i>

                        <span>
                            <?php
                            echo htmlspecialchars($success);
                            ?>
                        </span>

                    </div>

                <?php endif; ?>


                <!-- =================================
                     ERROR MESSAGE
                ================================== -->

                <?php if ($error !== ''): ?>

                    <div class="alert alert-error">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <span>
                            <?php
                            echo htmlspecialchars($error);
                            ?>
                        </span>

                    </div>

                <?php endif; ?>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    method="POST"
                    action=""
                >


                    <!-- =================================
                         ACCOUNT INFORMATION
                    ================================== -->

                    <div class="form-section">


                        <h2>

                            <i class="fa-solid fa-user"></i>

                            Account Information

                        </h2>


                        <div class="form-grid">


                            <!-- FULL NAME -->

                            <div class="form-group">

                                <label for="full_name">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $user['full_name']
                                        );
                                    ?>"
                                    required
                                >

                            </div>


                            <!-- USERNAME -->

                            <div class="form-group">

                                <label for="username">
                                    Username
                                </label>

                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $user['username']
                                        );
                                    ?>"
                                    required
                                >

                            </div>


                            <!-- EMAIL -->

                            <div class="form-group">

                                <label for="email">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $user['email']
                                        );
                                    ?>"
                                    required
                                >

                            </div>


                            <!-- PHONE -->

                            <div class="form-group">

                                <label for="phone">
                                    Phone
                                </label>

                                <input
                                    type="text"
                                    id="phone"
                                    name="phone"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $user['phone']
                                        );
                                    ?>"
                                    required
                                >

                            </div>


                            <!-- ROLE -->

                            <div class="form-group">

                                <label for="role">
                                    Role
                                </label>

                                <select
                                    id="role"
                                    name="role"
                                    required
                                >

                                    <option
                                        value="user"
                                        <?php
                                        echo (
                                            $user['role'] === 'user'
                                        )
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        User
                                    </option>


                                    <option
                                        value="admin"
                                        <?php
                                        echo (
                                            $user['role'] === 'admin'
                                        )
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        Admin
                                    </option>

                                </select>

                            </div>


                        </div>


                        <!-- PASSWORD NOTE -->

                        <div class="password-note">

                            <i class="fa-solid fa-lock"></i>

                            <span>
                                Password is protected and cannot be viewed
                                or edited from User Management.
                            </span>

                        </div>


                    </div>


                    <!-- =================================
                         PROFILE INFORMATION
                    ================================== -->

                    <div class="form-section">


                        <h2>

                            <i class="fa-solid fa-id-card"></i>

                            Profile Information

                        </h2>


                        <div class="form-grid">


                            <!-- DATE OF BIRTH -->

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
                                            $user['date_of_birth'] ?? ''
                                        );
                                    ?>"
                                >

                            </div>


                            <!-- GENDER -->

                            <div class="form-group">

                                <label for="gender">
                                    Gender
                                </label>

                                <input
                                    type="text"
                                    id="gender"
                                    name="gender"
                                    placeholder="Enter gender"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $user['gender'] ?? ''
                                        );
                                    ?>"
                                >

                            </div>


                            <!-- CITY -->

                            <div class="form-group">

                                <label for="city">
                                    City
                                </label>

                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    placeholder="Enter city"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $user['city'] ?? ''
                                        );
                                    ?>"
                                >

                            </div>


                            <!-- STATE -->

                            <div class="form-group">

                                <label for="state">
                                    State
                                </label>

                                <input
                                    type="text"
                                    id="state"
                                    name="state"
                                    placeholder="Enter state"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $user['state'] ?? ''
                                        );
                                    ?>"
                                >

                            </div>


                            <!-- ADDRESS -->

                            <div class="form-group full">

                                <label for="address">
                                    Address
                                </label>

                                <input
                                    type="text"
                                    id="address"
                                    name="address"
                                    placeholder="Enter address"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $user['address'] ?? ''
                                        );
                                    ?>"
                                >

                            </div>


                            <!-- BIO -->

                            <div class="form-group full">

                                <label for="bio">
                                    Bio
                                </label>

                                <textarea
                                    id="bio"
                                    name="bio"
                                    placeholder="Enter user bio..."
                                ><?php
                                    echo htmlspecialchars(
                                        $user['bio'] ?? ''
                                    );
                                ?></textarea>

                            </div>


                        </div>

                    </div>


                    <!-- =================================
                         FOOTER
                    ================================== -->

                    <div class="edit-footer">


                        <a
                            href="view_user.php?id=<?php echo $user_id; ?>"
                            class="btn btn-cancel"
                        >

                            <i class="fa-solid fa-xmark"></i>

                            Cancel

                        </a>


                        <button
                            type="submit"
                            class="btn btn-save"
                        >

                            <i class="fa-solid fa-floppy-disk"></i>

                            Save Changes

                        </button>


                    </div>


                </form>


            </div>

        </div>

    </div>

</main>


</body>

</html>