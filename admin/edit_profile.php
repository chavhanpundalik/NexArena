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


$admin_id = (int) $_SESSION['user_id'];

$success = "";
$error = "";


/* =====================================================
   PROFILE IMAGE DIRECTORY
===================================================== */

$upload_dir = "../uploads/profile_images/";


if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


/* =====================================================
   FETCH CURRENT PROFILE
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
            up.bio

        FROM users u

        LEFT JOIN user_profiles up
            ON u.user_id = up.user_id

        WHERE u.user_id = ?

        LIMIT 1";


$stmt = $conn->prepare($sql);


if (!$stmt) {
    die("PROFILE QUERY ERROR: " . $conn->error);
}


$stmt->bind_param("i", $admin_id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    session_destroy();

    header("Location: ../login.php");

    exit();
}


$admin = $result->fetch_assoc();


/* =====================================================
   CURRENT PROFILE IMAGE
===================================================== */

$current_image = trim(
    $admin['profile_image'] ?? ''
);


$has_image = false;


if ($current_image !== '') {

    $current_image_path =
        $upload_dir . $current_image;

    if (file_exists($current_image_path)) {
        $has_image = true;
    }
}


/* =====================================================
   CREATE AVATAR INITIALS
===================================================== */

function getInitials($name)
{
    $name = trim($name);

    if ($name === '') {
        return "A";
    }

    $parts = preg_split(
        '/\s+/',
        $name,
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    if (count($parts) >= 2) {

        return strtoupper(
            substr($parts[0], 0, 1) .
            substr($parts[1], 0, 1)
        );

    }

    return strtoupper(
        substr($name, 0, 2)
    );
}


$initials = getInitials(
    $admin['full_name'] ?? 'Admin'
);


/* =====================================================
   FORM SUBMISSION
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /* =================================================
       GET FORM DATA
    ================================================= */

    $full_name = trim(
        $_POST['full_name'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );

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


    /* =================================================
       VALIDATION
    ================================================= */

    if ($full_name === '') {

        $error = "Full name is required.";

    }


    /* =================================================
       PHONE VALIDATION
    ================================================= */

    elseif ($phone === '') {

        $error = "Phone number is required.";

    }


    /* =================================================
       IMAGE VALIDATION
    ================================================= */

    $new_image_name = $current_image;

    $image_uploaded = false;


    if (
        $error === '' &&
        isset($_FILES['profile_image']) &&
        $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {


        if (
            $_FILES['profile_image']['error']
            !== UPLOAD_ERR_OK
        ) {

            $error = "There was a problem uploading the image.";

        } else {


            $file_tmp =
                $_FILES['profile_image']['tmp_name'];

            $file_name =
                $_FILES['profile_image']['name'];

            $file_size =
                $_FILES['profile_image']['size'];


            /* =========================================
               FILE EXTENSION
            ========================================== */

            $extension = strtolower(
                pathinfo(
                    $file_name,
                    PATHINFO_EXTENSION
                )
            );


            /* =========================================
               ALLOWED EXTENSIONS
            ========================================== */

            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];


            if (
                !in_array(
                    $extension,
                    $allowed_extensions,
                    true
                )
            ) {

                $error =
                    "Only JPG, JPEG, PNG and WEBP images are allowed.";

            }


            /* =========================================
               FILE SIZE
            ========================================== */

            elseif ($file_size > 2 * 1024 * 1024) {

                $error =
                    "Profile image must be smaller than 2MB.";

            }


            /* =========================================
               VERIFY REAL IMAGE
            ========================================== */

            elseif (
                getimagesize($file_tmp) === false
            ) {

                $error =
                    "The uploaded file is not a valid image.";

            }


            /* =========================================
               GENERATE UNIQUE FILE NAME
            ========================================== */

            else {

                $new_image_name =
                    'admin_' .
                    $admin_id .
                    '_' .
                    time() .
                    '_' .
                    bin2hex(random_bytes(4)) .
                    '.' .
                    $extension;

                $destination =
                    $upload_dir .
                    $new_image_name;


                if (
                    !move_uploaded_file(
                        $file_tmp,
                        $destination
                    )
                ) {

                    $error =
                        "Unable to save the uploaded image.";

                } else {

                    $image_uploaded = true;
                }
            }
        }
    }


    /* =================================================
       UPDATE DATABASE
    ================================================= */

    if ($error === '') {


        $conn->begin_transaction();


        try {


            /* =========================================
               UPDATE USERS TABLE
            ========================================== */

            $update_user = $conn->prepare(
                "UPDATE users
                 SET
                    full_name = ?,
                    phone = ?
                 WHERE user_id = ?"
            );


            if (!$update_user) {

                throw new Exception(
                    "User update query failed."
                );
            }


            $update_user->bind_param(
                "ssi",
                $full_name,
                $phone,
                $admin_id
            );


            if (!$update_user->execute()) {

                throw new Exception(
                    "Unable to update account information."
                );
            }


            /* =========================================
               CHECK PROFILE
            ========================================== */

            $check_profile = $conn->prepare(
                "SELECT profile_id
                 FROM user_profiles
                 WHERE user_id = ?
                 LIMIT 1"
            );


            if (!$check_profile) {

                throw new Exception(
                    "Unable to check profile."
                );
            }


            $check_profile->bind_param(
                "i",
                $admin_id
            );

            $check_profile->execute();

            $profile_result =
                $check_profile->get_result();


            /* =========================================
               PROFILE EXISTS
            ========================================== */

            if ($profile_result->num_rows > 0) {


                $update_profile = $conn->prepare(
                    "UPDATE user_profiles
                     SET
                        profile_image = NULLIF(?, ''),
                        date_of_birth = NULLIF(?, ''),
                        gender = NULLIF(?, ''),
                        address = NULLIF(?, ''),
                        city = NULLIF(?, ''),
                        state = NULLIF(?, ''),
                        bio = NULLIF(?, '')
                     WHERE user_id = ?"
                );


                if (!$update_profile) {

                    throw new Exception(
                        "Profile update query failed."
                    );
                }


                $update_profile->bind_param(
                    "sssssssi",
                    $new_image_name,
                    $date_of_birth,
                    $gender,
                    $address,
                    $city,
                    $state,
                    $bio,
                    $admin_id
                );


                if (!$update_profile->execute()) {

                    throw new Exception(
                        "Unable to update profile information."
                    );
                }


            }


            /* =========================================
               PROFILE DOES NOT EXIST
            ========================================== */

            else {


                $insert_profile = $conn->prepare(
                    "INSERT INTO user_profiles
                    (
                        user_id,
                        profile_image,
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
                        NULLIF(?, ''),
                        NULLIF(?, '')
                    )"
                );


                if (!$insert_profile) {

                    throw new Exception(
                        "Profile insert query failed."
                    );
                }


                $insert_profile->bind_param(
                    "isssssss",
                    $admin_id,
                    $new_image_name,
                    $date_of_birth,
                    $gender,
                    $address,
                    $city,
                    $state,
                    $bio
                );


                if (!$insert_profile->execute()) {

                    throw new Exception(
                        "Unable to create profile."
                    );
                }
            }


            /* =========================================
               COMMIT
            ========================================== */

            $conn->commit();


            /* =========================================
               DELETE OLD IMAGE
            ========================================== */

            if (
                $image_uploaded &&
                $current_image !== '' &&
                file_exists(
                    $upload_dir . $current_image
                )
            ) {

                unlink(
                    $upload_dir . $current_image
                );
            }


            $success =
                "Your profile has been updated successfully.";


            /* =========================================
               REFRESH PAGE DATA
            ========================================== */

            $stmt->execute();

            $result = $stmt->get_result();

            $admin = $result->fetch_assoc();


            $current_image =
                trim(
                    $admin['profile_image'] ?? ''
                );


            $has_image = false;


            if ($current_image !== '') {

                $current_image_path =
                    $upload_dir . $current_image;

                if (
                    file_exists(
                        $current_image_path
                    )
                ) {

                    $has_image = true;
                }
            }


            $initials = getInitials(
                $admin['full_name'] ?? 'Admin'
            );


        } catch (Exception $e) {


            $conn->rollback();


            /* =========================================
               DELETE NEW IMAGE IF DATABASE FAILED
            ========================================== */

            if (
                $image_uploaded &&
                $new_image_name !== '' &&
                file_exists(
                    $upload_dir . $new_image_name
                )
            ) {

                unlink(
                    $upload_dir . $new_image_name
                );
            }


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

    <title>Edit Profile | NexArena</title>


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="assets/edit_profile.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<?php include "sidebar.php"; ?>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="admin-main">


    <div class="edit-profile-page">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="page-header">


            <div>

                <span class="page-label">
                    ACCOUNT SETTINGS
                </span>

                <h1>
                    Edit Profile
                </h1>

                <p>
                    Update your personal NexArena administrator information.
                </p>

            </div>


            <a
                href="profile.php"
                class="back-profile-btn"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Profile

            </a>


        </div>


        <!-- =================================================
             SUCCESS
        ================================================== -->

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


        <!-- =================================================
             ERROR
        ================================================== -->

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


        <!-- =================================================
             FORM
        ================================================== -->

        <form
            method="POST"
            enctype="multipart/form-data"
            class="edit-profile-form"
        >


            <!-- =================================================
                 PROFILE IMAGE CARD
            ================================================== -->

            <section class="edit-card profile-photo-card">


                <div class="card-heading">

                    <div class="heading-icon">

                        <i class="fa-solid fa-camera"></i>

                    </div>

                    <div>

                        <h2>
                            Profile Photo
                        </h2>

                        <p>
                            Upload a professional profile image.
                        </p>

                    </div>

                </div>


                <div class="photo-area">


                    <!-- AVATAR -->

                    <div class="photo-preview">


                        <?php if ($has_image): ?>

                            <img
                                src="<?php
                                    echo htmlspecialchars(
                                        $upload_dir .
                                        $current_image
                                    );
                                ?>"
                                alt="Profile Image"
                                id="profilePreview"
                            >

                        <?php else: ?>

                            <div
                                class="avatar-preview"
                                id="avatarPreview"
                            >
                                <?php
                                echo htmlspecialchars(
                                    $initials
                                );
                                ?>
                            </div>

                            <img
                                src=""
                                alt="Profile Preview"
                                id="profilePreview"
                                class="hidden-preview"
                            >

                        <?php endif; ?>


                    </div>


                    <!-- UPLOAD -->

                    <div class="photo-info">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $admin['full_name']
                            );
                            ?>
                        </h3>

                        <p>
                            JPG, JPEG, PNG or WEBP
                        </p>

                        <p>
                            Maximum file size: 2MB
                        </p>


                        <label
                            for="profile_image"
                            class="upload-btn"
                        >

                            <i class="fa-solid fa-upload"></i>

                            Choose Image

                        </label>


                        <input
                            type="file"
                            id="profile_image"
                            name="profile_image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            hidden
                        >


                        <span
                            id="fileName"
                            class="file-name"
                        >
                        </span>

                    </div>


                </div>


            </section>


            <!-- =================================================
                 PERSONAL INFORMATION
            ================================================== -->

            <section class="edit-card">


                <div class="card-heading">

                    <div class="heading-icon">

                        <i class="fa-solid fa-user"></i>

                    </div>


                    <div>

                        <h2>
                            Personal Information
                        </h2>

                        <p>
                            Update your basic account information.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- FULL NAME -->

                    <div class="form-group">

                        <label for="full_name">

                            Full Name

                            <span>*</span>

                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-user"></i>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['full_name'] ?? ''
                                    );
                                ?>"
                                placeholder="Enter your full name"
                                required
                            >

                        </div>

                    </div>


                    <!-- USERNAME -->

                    <div class="form-group">

                        <label>
                            Username
                        </label>

                        <div class="input-wrapper readonly">

                            <i class="fa-solid fa-at"></i>

                            <input
                                type="text"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['username'] ?? ''
                                    );
                                ?>"
                                readonly
                            >

                        </div>

                        <small>
                            Username cannot be changed here.
                        </small>

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <div class="input-wrapper readonly">

                            <i class="fa-solid fa-envelope"></i>

                            <input
                                type="email"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['email'] ?? ''
                                    );
                                ?>"
                                readonly
                            >

                        </div>

                        <small>
                            Contact the system administrator to change your email.
                        </small>

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label for="phone">

                            Phone Number

                            <span>*</span>

                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-phone"></i>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['phone'] ?? ''
                                    );
                                ?>"
                                placeholder="Enter phone number"
                                required
                            >

                        </div>

                    </div>


                </div>


            </section>


            <!-- =================================================
                 ADDITIONAL INFORMATION
            ================================================== -->

            <section class="edit-card">


                <div class="card-heading">

                    <div class="heading-icon">

                        <i class="fa-solid fa-address-card"></i>

                    </div>


                    <div>

                        <h2>
                            Additional Information
                        </h2>

                        <p>
                            Complete your personal profile.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- DATE OF BIRTH -->

                    <div class="form-group">

                        <label for="date_of_birth">
                            Date of Birth
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-calendar"></i>

                            <input
                                type="date"
                                id="date_of_birth"
                                name="date_of_birth"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['date_of_birth'] ?? ''
                                    );
                                ?>"
                            >

                        </div>

                    </div>


                    <!-- GENDER -->

                    <div class="form-group">

                        <label for="gender">
                            Gender
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-venus-mars"></i>

                            <select
                                id="gender"
                                name="gender"
                            >

                                <option
                                    value=""
                                >
                                    Select Gender
                                </option>

                                <option
                                    value="Male"
                                    <?php
                                    echo (
                                        ($admin['gender'] ?? '')
                                        === 'Male'
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Male
                                </option>

                                <option
                                    value="Female"
                                    <?php
                                    echo (
                                        ($admin['gender'] ?? '')
                                        === 'Female'
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Female
                                </option>

                                <option
                                    value="Other"
                                    <?php
                                    echo (
                                        ($admin['gender'] ?? '')
                                        === 'Other'
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    Other
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- CITY -->

                    <div class="form-group">

                        <label for="city">
                            City
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-city"></i>

                            <input
                                type="text"
                                id="city"
                                name="city"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['city'] ?? ''
                                    );
                                ?>"
                                placeholder="Enter city"
                            >

                        </div>

                    </div>


                    <!-- STATE -->

                    <div class="form-group">

                        <label for="state">
                            State
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-map"></i>

                            <input
                                type="text"
                                id="state"
                                name="state"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['state'] ?? ''
                                    );
                                ?>"
                                placeholder="Enter state"
                            >

                        </div>

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group full">

                        <label for="address">
                            Address
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-location-dot"></i>

                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="<?php
                                    echo htmlspecialchars(
                                        $admin['address'] ?? ''
                                    );
                                ?>"
                                placeholder="Enter your address"
                            >

                        </div>

                    </div>


                    <!-- BIO -->

                    <div class="form-group full">

                        <label for="bio">
                            Bio
                        </label>

                        <div class="textarea-wrapper">

                            <textarea
                                id="bio"
                                name="bio"
                                maxlength="500"
                                placeholder="Tell something about yourself..."
                            ><?php
                            echo htmlspecialchars(
                                $admin['bio'] ?? ''
                            );
                            ?></textarea>

                            <span
                                id="bioCount"
                                class="character-count"
                            >
                                0 / 500
                            </span>

                        </div>

                    </div>


                </div>


            </section>


            <!-- =================================================
                 SECURITY
            ================================================== -->

            <section class="security-info">


                <div class="security-icon">

                    <i class="fa-solid fa-shield-halved"></i>

                </div>


                <div>

                    <h3>
                        Protected Account Information
                    </h3>

                    <p>
                        Username, email, password and administrator
                        role are protected and cannot be changed
                        from this page.
                    </p>

                </div>


            </section>


            <!-- =================================================
                 FORM ACTIONS
            ================================================== -->

            <div class="form-actions">


                <a
                    href="profile.php"
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


</main>


<!-- =====================================================
     SMALL JAVASCRIPT
===================================================== -->

<script>


/* =====================================================
   IMAGE PREVIEW
===================================================== */

const imageInput =
    document.getElementById("profile_image");

const profilePreview =
    document.getElementById("profilePreview");

const avatarPreview =
    document.getElementById("avatarPreview");

const fileName =
    document.getElementById("fileName");


if (imageInput) {

    imageInput.addEventListener(
        "change",
        function () {

            const file = this.files[0];


            if (!file) {
                return;
            }


            fileName.textContent =
                file.name;


            const reader =
                new FileReader();


            reader.onload = function (event) {


                if (profilePreview) {

                    profilePreview.src =
                        event.target.result;

                    profilePreview.classList.remove(
                        "hidden-preview"
                    );

                    profilePreview.classList.add(
                        "active-preview"
                    );
                }


                if (avatarPreview) {

                    avatarPreview.style.display =
                        "none";
                }

            };


            reader.readAsDataURL(file);

        }
    );

}


/* =====================================================
   BIO CHARACTER COUNT
===================================================== */

const bio =
    document.getElementById("bio");

const bioCount =
    document.getElementById("bioCount");


function updateBioCount() {

    if (!bio || !bioCount) {
        return;
    }

    bioCount.textContent =
        bio.value.length + " / 500";
}


if (bio) {

    bio.addEventListener(
        "input",
        updateBioCount
    );

    updateBioCount();
}


</script>


</body>

</html>