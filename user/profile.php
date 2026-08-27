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


/* ========================================
   GET USER ID
======================================== */

$user_id = (int) $_SESSION['user_id'];


/* ========================================
   GET DARK MODE SETTING
======================================== */

$dark_mode = 0;
$settings_sql = "SELECT dark_mode FROM user_settings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->bind_param("i", $user_id);
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();

if ($settings_result->num_rows > 0) {
    $settings_data = $settings_result->fetch_assoc();
    $dark_mode = $settings_data['dark_mode'] ?? 0;
}
$settings_stmt->close();

$dark_mode_class = ($dark_mode == 1) ? 'dark-mode' : '';


/* ========================================
   GET USER + PROFILE INFORMATION
======================================== */

$profile_sql = "
    SELECT
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

    LIMIT 1
";


$profile_stmt = $conn->prepare($profile_sql);


if (!$profile_stmt) {

    die("PROFILE QUERY ERROR: " .
        $conn->error);
}


$profile_stmt->bind_param(
    "i",
    $user_id
);


$profile_stmt->execute();


$profile_result = $profile_stmt->get_result();


if ($profile_result->num_rows === 0) {

    $profile_stmt->close();

    die("User profile not found.");
}


$user = $profile_result->fetch_assoc();


$profile_stmt->close();


/* ========================================
   SAFE DISPLAY VALUES
======================================== */

$full_name = htmlspecialchars(
    $user['full_name'] ?? 'User',
    ENT_QUOTES,
    'UTF-8'
);


$username = htmlspecialchars(
    $user['username'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$email = htmlspecialchars(
    $user['email'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$phone = htmlspecialchars(
    $user['phone'] ?? 'Not available',
    ENT_QUOTES,
    'UTF-8'
);


$role = htmlspecialchars(
    ucfirst($user['role'] ?? 'user'),
    ENT_QUOTES,
    'UTF-8'
);


$favorite_sport = htmlspecialchars(
    $user['sport_name'] ?? 'Not selected',
    ENT_QUOTES,
    'UTF-8'
);


$sport_category = htmlspecialchars(
    $user['category'] ?? 'Not specified',
    ENT_QUOTES,
    'UTF-8'
);


$position = htmlspecialchars(
    $user['preferred_position'] ?? 'Not specified',
    ENT_QUOTES,
    'UTF-8'
);


$bio = htmlspecialchars(
    $user['bio'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);


/* ========================================
   PROFILE INITIALS
======================================== */

$name_parts = preg_split(
    '/\s+/',
    trim($user['full_name'] ?? 'User')
);


$initials = 'U';


if (count($name_parts) >= 2) {

    $initials =
        strtoupper(
            substr($name_parts[0], 0, 1) .
                substr($name_parts[1], 0, 1)
        );
} elseif (!empty($name_parts[0])) {

    $initials =
        strtoupper(
            substr($name_parts[0], 0, 2)
        );
}


/* ========================================
   PROFILE IMAGE
======================================== */

$profile_image = $user['profile_image'] ?? '';



/* ========================================
   ACTIVITY STATISTICS
======================================== */

/* Registrations */

$registration_count = 0;

$registration_sql = "
    SELECT COUNT(*) AS total
    FROM event_registrations
    WHERE user_id = ?
";

$registration_stmt = $conn->prepare(
    $registration_sql
);

if ($registration_stmt) {

    $registration_stmt->bind_param(
        "i",
        $user_id
    );

    $registration_stmt->execute();

    $registration_data =
        $registration_stmt->get_result()
        ->fetch_assoc();

    $registration_count =
        (int) ($registration_data['total'] ?? 0);

    $registration_stmt->close();
}


/* Teams */

$team_count = 0;

$team_sql = "
    SELECT COUNT(DISTINCT team_id) AS total
    FROM team_members
    WHERE user_id = ?
";

$team_stmt = $conn->prepare($team_sql);

if ($team_stmt) {

    $team_stmt->bind_param(
        "i",
        $user_id
    );

    $team_stmt->execute();

    $team_data =
        $team_stmt->get_result()
        ->fetch_assoc();

    $team_count =
        (int) ($team_data['total'] ?? 0);

    $team_stmt->close();
}


/* Notifications */

$notification_count = 0;

$notification_sql = "
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ?
";

$notification_stmt = $conn->prepare(
    $notification_sql
);

if ($notification_stmt) {

    $notification_stmt->bind_param(
        "i",
        $user_id
    );

    $notification_stmt->execute();

    $notification_data =
        $notification_stmt->get_result()
        ->fetch_assoc();

    $notification_count =
        (int) ($notification_data['total'] ?? 0);

    $notification_stmt->close();
}

// Don't close connection here - sidebar needs it
// $conn->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        My Profile | NexArena
    </title>

    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/profile.css">

</head>

<body class="<?php echo $dark_mode_class; ?>">

    <?php include "sidebar.php"; ?>


    <!-- ========================================
     MAIN CONTENT
======================================== -->

    <div class="profile-main">


        <main class="profile-container">


            <!-- ========================================
             PROFILE HEADER
        ======================================== -->

            <section class="profile-hero">


                <div class="profile-identity">


                    <div class="profile-avatar">

                        <?php if (!empty($profile_image)): ?>

                            <img
                                src="<?php echo htmlspecialchars(
                                            $profile_image,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                alt="Profile Image">

                        <?php else: ?>

                            <span>
                                <?php echo $initials; ?>
                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="profile-name">


                        <span class="profile-label">
                            NEXARENA PLAYER
                        </span>


                        <h1>
                            <?php echo $full_name; ?>
                        </h1>


                        <p>
                            @<?php echo $username; ?>
                        </p>


                        <span class="player-badge">
                            <?php echo $role; ?>
                        </span>


                    </div>


                </div>


                <a
                    href="edit_profile.php"
                    class="edit-profile-btn">
                    Edit Profile
                </a>


            </section>



            <!-- ========================================
             PERSONAL INFORMATION
        ======================================== -->

            <section class="profile-section">


                <div class="section-heading">

                    <div>

                        <span>
                            ACCOUNT
                        </span>

                        <h2>
                            Personal Information
                        </h2>

                    </div>

                </div>


                <div class="information-grid">


                    <div class="information-card">

                        <span>
                            FULL NAME
                        </span>

                        <strong>
                            <?php echo $full_name; ?>
                        </strong>

                    </div>


                    <div class="information-card">

                        <span>
                            USERNAME
                        </span>

                        <strong>
                            @<?php echo $username; ?>
                        </strong>

                    </div>


                    <div class="information-card">

                        <span>
                            EMAIL
                        </span>

                        <strong>
                            <?php echo $email; ?>
                        </strong>

                    </div>


                    <div class="information-card">

                        <span>
                            PHONE
                        </span>

                        <strong>
                            <?php echo $phone; ?>
                        </strong>

                    </div>


                    <div class="information-card">

                        <span>
                            ACCOUNT TYPE
                        </span>

                        <strong class="orange-text">
                            <?php echo $role; ?>
                        </strong>

                    </div>


                    <div class="information-card">

                        <span>
                            USER ID
                        </span>

                        <strong>
                            #<?php echo $user_id; ?>
                        </strong>

                    </div>


                </div>


            </section>



            <!-- ========================================
             SPORTS PROFILE
        ======================================== -->

            <section class="profile-section">


                <div class="section-heading">

                    <div>

                        <span>
                            SPORTS
                        </span>

                        <h2>
                            Sports Profile
                        </h2>

                    </div>

                </div>


                <div class="sports-profile-grid">


                    <div class="sports-profile-card">


                        <div class="sports-icon">
                            🏆
                        </div>


                        <div>

                            <span>
                                FAVORITE SPORT
                            </span>

                            <strong>
                                <?php echo $favorite_sport; ?>
                            </strong>

                        </div>


                    </div>


                    <div class="sports-profile-card">


                        <div class="sports-icon">
                            🏅
                        </div>


                        <div>

                            <span>
                                CATEGORY
                            </span>

                            <strong>
                                <?php echo $sport_category; ?>
                            </strong>

                        </div>


                    </div>


                    <div class="sports-profile-card">


                        <div class="sports-icon">
                            ⚡
                        </div>


                        <div>

                            <span>
                                PREFERRED POSITION
                            </span>

                            <strong>
                                <?php echo $position; ?>
                            </strong>

                        </div>


                    </div>


                </div>


                <div class="bio-card">


                    <span>
                        ABOUT ME
                    </span>


                    <?php if ($bio !== ''): ?>

                        <p>
                            <?php echo nl2br($bio); ?>
                        </p>

                    <?php else: ?>

                        <p class="empty-bio">
                            No bio added yet.
                            Add a short introduction about yourself
                            from Edit Profile.
                        </p>

                    <?php endif; ?>


                </div>


            </section>



            <!-- ========================================
             ACTIVITY
        ======================================== -->

            <section class="profile-section">


                <div class="section-heading">

                    <div>

                        <span>
                            ACTIVITY
                        </span>

                        <h2>
                            My NexArena Activity
                        </h2>

                    </div>

                </div>


                <div class="activity-grid">


                    <div class="activity-card">

                        <div class="activity-icon orange">
                            🏆
                        </div>

                        <strong>
                            <?php echo $registration_count; ?>
                        </strong>

                        <span>
                            Registrations
                        </span>

                    </div>


                    <div class="activity-card">

                        <div class="activity-icon black">
                            👥
                        </div>

                        <strong>
                            <?php echo $team_count; ?>
                        </strong>

                        <span>
                            Teams
                        </span>

                    </div>


                    <div class="activity-card">

                        <div class="activity-icon orange">
                            🔔
                        </div>

                        <strong>
                            <?php echo $notification_count; ?>
                        </strong>

                        <span>
                            Notifications
                        </span>

                    </div>


                </div>


            </section>



            <!-- ========================================
             ACCOUNT ACTIONS
        ======================================== -->

            <section class="profile-section security-section">


                <div class="section-heading">

                    <div>

                        <span>
                            ACCOUNT
                        </span>

                        <h2>
                            Account Actions
                        </h2>

                    </div>

                </div>


                <div class="account-actions">


                    <a
                        href="edit_profile.php"
                        class="action-btn orange-btn">
                        Edit My Profile
                    </a>


                    <a
                        href="change_password.php"
                        class="action-btn black-btn">
                        Change Password
                    </a>


                </div>


            </section>


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

    <!-- Theme JavaScript - MUST BE LAST -->
    <script src="assets/theme.js"></script>

</body>

</html>