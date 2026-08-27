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


/* =====================================================
   FETCH ADMIN PROFILE
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
   ADMIN NAME
===================================================== */

$full_name = trim($admin['full_name'] ?? 'Admin');


if ($full_name === '') {
    $full_name = 'Admin';
}


/* =====================================================
   CREATE AVATAR INITIALS
===================================================== */

$name_parts = preg_split(
    '/\s+/',
    $full_name,
    -1,
    PREG_SPLIT_NO_EMPTY
);


if (count($name_parts) >= 2) {

    $initials =
        strtoupper(substr($name_parts[0], 0, 1)) .
        strtoupper(substr($name_parts[1], 0, 1));

} else {

    $initials =
        strtoupper(substr($full_name, 0, 2));
}


/* =====================================================
   PROFILE IMAGE
===================================================== */

$profile_image = trim(
    $admin['profile_image'] ?? ''
);


$has_profile_image = false;


if ($profile_image !== '') {

    /*
       Change this path if your uploaded
       profile images are stored somewhere else.
    */

    $image_path = "../uploads/profile_images/" . $profile_image;

    if (file_exists($image_path)) {
        $has_profile_image = true;
    }
}


/* =====================================================
   SAFE DISPLAY VALUES
===================================================== */

$username = $admin['username'] ?? '';

$email = $admin['email'] ?? '';

$phone = $admin['phone'] ?? '';

$role = $admin['role'] ?? 'admin';

$date_of_birth = $admin['date_of_birth'] ?? '';

$gender = $admin['gender'] ?? '';

$address = $admin['address'] ?? '';

$city = $admin['city'] ?? '';

$state = $admin['state'] ?? '';

$bio = $admin['bio'] ?? '';

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile | NexArena</title>


    <!-- PROFILE CSS -->

    <link
        rel="stylesheet"
        href="assets/profile.css"
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
     MAIN CONTENT
===================================================== -->

<main class="admin-main">


    <div class="profile-page">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="page-header">

            <div>

                <span class="page-label">
                    ACCOUNT
                </span>

                <h1>
                    My Profile
                </h1>

                <p>
                    Manage your NexArena administrator profile.
                </p>

            </div>


            <a
                href="edit_profile.php"
                class="edit-profile-btn"
            >

                <i class="fa-solid fa-pen"></i>

                Edit Profile

            </a>

        </div>
<!-- ===== TOGGLE SCRIPT ===== -->
<script>
const sidebarToggle = document.getElementById("sidebarToggle");
const sidebar = document.querySelector(".admin-sidebar");
const overlay = document.getElementById("sidebarOverlay");

if (sidebarToggle && sidebar && overlay) {
    sidebarToggle.addEventListener("click", function () {
        sidebar.classList.toggle("mobile-open");
        overlay.classList.toggle("active");
        document.body.classList.toggle("sidebar-open");
    });

    overlay.addEventListener("click", function () {
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("active");
        document.body.classList.remove("sidebar-open");
    });
}
</script>


        <!-- =================================================
             PROFILE HERO
        ================================================== -->

        <section class="profile-hero">


            <!-- PROFILE IMAGE -->

            <div class="profile-avatar-wrapper">


                <?php if ($has_profile_image): ?>

                    <img
                        src="<?php echo htmlspecialchars($image_path); ?>"
                        alt="Profile Image"
                        class="profile-avatar-image"
                    >

                <?php else: ?>

                    <div class="profile-avatar">

                        <?php
                        echo htmlspecialchars($initials);
                        ?>

                    </div>

                <?php endif; ?>


                <span class="online-dot"></span>

            </div>


            <!-- PROFILE INTRO -->

            <div class="profile-intro">

                <span class="profile-welcome">
                    Administrator Account
                </span>


                <h2>
                    <?php
                    echo htmlspecialchars($full_name);
                    ?>
                </h2>


                <p class="profile-username">

                    @<?php
                    echo htmlspecialchars($username);
                    ?>

                </p>


                <div class="role-badge">

                    <i class="fa-solid fa-shield-halved"></i>

                    Administrator

                </div>

            </div>


            <!-- PROFILE ID -->

            <div class="profile-id">

                <span>
                    USER ID
                </span>

                <strong>
                    #<?php echo $admin_id; ?>
                </strong>

            </div>


        </section>


        <!-- =================================================
             PROFILE GRID
        ================================================== -->

        <div class="profile-grid">


            <!-- =================================================
                 PERSONAL INFORMATION
            ================================================== -->

            <section class="profile-card">


                <div class="card-header">

                    <div class="card-icon">

                        <i class="fa-solid fa-user"></i>

                    </div>


                    <div>

                        <h3>
                            Personal Information
                        </h3>

                        <p>
                            Your basic account details
                        </p>

                    </div>

                </div>


                <div class="details-grid">


                    <!-- FULL NAME -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Full Name
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $full_name
                            );
                            ?>
                        </strong>

                    </div>


                    <!-- USERNAME -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Username
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $username
                            );
                            ?>
                        </strong>

                    </div>


                    <!-- EMAIL -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Email Address
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $email
                            );
                            ?>
                        </strong>

                    </div>


                    <!-- PHONE -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Phone Number
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $phone
                            );
                            ?>
                        </strong>

                    </div>


                    <!-- ROLE -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Account Role
                        </span>

                        <strong class="orange-text">

                            <i class="fa-solid fa-user-shield"></i>

                            Administrator

                        </strong>

                    </div>


                    <!-- USER ID -->

                    <div class="detail-item">

                        <span class="detail-label">
                            User ID
                        </span>

                        <strong>
                            #<?php echo $admin_id; ?>
                        </strong>

                    </div>


                </div>

            </section>


            <!-- =================================================
                 ADDITIONAL INFORMATION
            ================================================== -->

            <section class="profile-card">


                <div class="card-header">

                    <div class="card-icon">

                        <i class="fa-solid fa-address-card"></i>

                    </div>


                    <div>

                        <h3>
                            Additional Information
                        </h3>

                        <p>
                            Personal profile details
                        </p>

                    </div>

                </div>


                <div class="details-grid">


                    <!-- DATE OF BIRTH -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Date of Birth
                        </span>

                        <strong>

                            <?php

                            echo $date_of_birth !== ''
                                ? htmlspecialchars(
                                    $date_of_birth
                                )
                                : '<span class="empty-value">
                                    Not added
                                   </span>';

                            ?>

                        </strong>

                    </div>


                    <!-- GENDER -->

                    <div class="detail-item">

                        <span class="detail-label">
                            Gender
                        </span>

                        <strong>

                            <?php

                            echo $gender !== ''
                                ? htmlspecialchars(
                                    $gender
                                )
                                : '<span class="empty-value">
                                    Not added
                                   </span>';

                            ?>

                        </strong>

                    </div>


                    <!-- CITY -->

                    <div class="detail-item">

                        <span class="detail-label">
                            City
                        </span>

                        <strong>

                            <?php

                            echo $city !== ''
                                ? htmlspecialchars(
                                    $city
                                )
                                : '<span class="empty-value">
                                    Not added
                                   </span>';

                            ?>

                        </strong>

                    </div>


                    <!-- STATE -->

                    <div class="detail-item">

                        <span class="detail-label">
                            State
                        </span>

                        <strong>

                            <?php

                            echo $state !== ''
                                ? htmlspecialchars(
                                    $state
                                )
                                : '<span class="empty-value">
                                    Not added
                                   </span>';

                            ?>

                        </strong>

                    </div>


                    <!-- ADDRESS -->

                    <div class="detail-item detail-full">

                        <span class="detail-label">
                            Address
                        </span>

                        <strong>

                            <?php

                            echo $address !== ''
                                ? htmlspecialchars(
                                    $address
                                )
                                : '<span class="empty-value">
                                    Not added
                                   </span>';

                            ?>

                        </strong>

                    </div>


                </div>

            </section>


            <!-- =================================================
                 BIO
            ================================================== -->

            <section class="profile-card bio-card">


                <div class="card-header">

                    <div class="card-icon">

                        <i class="fa-solid fa-align-left"></i>

                    </div>


                    <div>

                        <h3>
                            About Me
                        </h3>

                        <p>
                            Your profile description
                        </p>

                    </div>

                </div>


                <div class="bio-content">

                    <?php if ($bio !== ''): ?>

                        <p>
                            <?php
                            echo nl2br(
                                htmlspecialchars($bio)
                            );
                            ?>
                        </p>

                    <?php else: ?>

                        <div class="empty-bio">

                            <i class="fa-regular fa-pen-to-square"></i>

                            <p>
                                You haven't added a bio yet.
                            </p>

                            <a href="edit_profile.php">
                                Add Bio
                            </a>

                        </div>

                    <?php endif; ?>

                </div>


            </section>


        </div>


        <!-- =================================================
             SECURITY INFORMATION
        ================================================== -->

        <section class="security-card">


            <div class="security-icon">

                <i class="fa-solid fa-lock"></i>

            </div>


            <div class="security-content">

                <h3>
                    Account Security
                </h3>

                <p>
                    Your password is protected and is never
                    displayed on your profile.
                </p>

            </div>


            <div class="security-status">

                <i class="fa-solid fa-circle-check"></i>

                Protected

            </div>


        </section>


    </div>


</main>


</body>

</html>