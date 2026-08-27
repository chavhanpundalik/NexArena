<?php
session_start();

require_once "../db_connect.php";

/* =========================
   ADMIN AUTHENTICATION
========================= */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit();
}


/* =========================
   GET USER ID
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = (int) $_GET['id'];


/* =========================
   GET USER + PROFILE
========================= */

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
    die("USER QUERY ERROR: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: users.php");
    exit();
}

$user = $result->fetch_assoc();


/* =========================
   SUPER ADMIN PROTECTION
========================= */

$isSuperAdmin = ($user['role'] === 'super_admin');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>View User | NexArena</title>

    <link rel="stylesheet" href="users.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        .view-page {
            max-width: 1100px;
            margin: auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #555;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .back-link:hover {
            color: #f36b21;
        }

        .profile-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #eee;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
            overflow: hidden;
        }

        .profile-header {
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid #eee;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fff1e8;
            color: #f36b21;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: 700;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-header h1 {
            font-size: 24px;
            margin-bottom: 6px;
        }

        .profile-header p {
            color: #888;
            font-size: 14px;
        }

        .profile-body {
            padding: 30px;
        }

        .section-title {
            font-size: 17px;
            margin-bottom: 18px;
            color: #222;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .detail-box {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
            background: #fafafa;
        }

        .detail-box label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .detail-box span {
            color: #333;
            font-size: 14px;
            word-break: break-word;
        }

        .bio-box {
            margin-top: 18px;
        }

        .bio-box p {
            color: #555;
            line-height: 1.6;
            font-size: 14px;
        }

        .protected-box {
            margin-top: 25px;
            padding: 18px;
            background: #fff8f3;
            border: 1px solid #ffd9c2;
            border-radius: 10px;
            color: #a84c16;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-actions {
            padding: 20px 30px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        .profile-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 17px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .edit-action {
            background: #f36b21;
            color: #fff;
        }

        .edit-action:hover {
            background: #d95713;
        }

        .back-action {
            background: #eee;
            color: #444;
        }

        @media(max-width:700px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

            .profile-header {
                padding: 22px;
            }

            .profile-body {
                padding: 22px;
            }

            .profile-actions {
                padding: 18px 22px;
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<?php include "sidebar.php"; ?>


<main class="admin-main">

    <div class="view-page">

        <a href="users.php" class="back-link">

            <i class="fa-solid fa-arrow-left"></i>

            Back to User Management

        </a>


        <div class="profile-card">

            <!-- HEADER -->

            <div class="profile-header">

                <div class="profile-avatar">

                    <?php if (
                        !$isSuperAdmin &&
                        !empty($user['profile_image'])
                    ): ?>

                        <img
                            src="../uploads/profile/<?php
                                echo htmlspecialchars($user['profile_image']);
                            ?>"
                            alt="Profile"
                        >

                    <?php else: ?>

                        <i class="fa-solid fa-user"></i>

                    <?php endif; ?>

                </div>


                <div>

                    <?php if ($isSuperAdmin): ?>

                        <h1>Protected Account</h1>

                        <p>
                            Super Admin account
                        </p>

                    <?php else: ?>

                        <h1>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </h1>

                        <p>
                            @<?php echo htmlspecialchars($user['username']); ?>
                        </p>

                    <?php endif; ?>

                </div>

            </div>


            <!-- BODY -->

            <div class="profile-body">

                <?php if ($isSuperAdmin): ?>

                    <div class="protected-box">

                        <i class="fa-solid fa-shield-halved"></i>

                        <div>

                            <strong>Super Admin Protected</strong>

                            <p>
                                Personal information for this account
                                is protected from normal administrators.
                            </p>

                        </div>

                    </div>


                    <h2 class="section-title" style="margin-top:30px;">
                        Account Information
                    </h2>

                    <div class="details-grid">

                        <div class="detail-box">

                            <label>User ID</label>

                            <span>
                                #<?php echo $user['user_id']; ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>Username</label>

                            <span>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>Role</label>

                            <span>
                                Super Admin
                            </span>

                        </div>

                    </div>

                <?php else: ?>


                    <h2 class="section-title">
                        Account Information
                    </h2>


                    <div class="details-grid">

                        <div class="detail-box">

                            <label>User ID</label>

                            <span>
                                #<?php echo $user['user_id']; ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>Full Name</label>

                            <span>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>Username</label>

                            <span>
                                @<?php echo htmlspecialchars($user['username']); ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>Email</label>

                            <span>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>Phone</label>

                            <span>
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>Role</label>

                            <span>
                                <?php echo ucfirst($user['role']); ?>
                            </span>

                        </div>

                    </div>


                    <h2 class="section-title" style="margin-top:30px;">
                        Profile Information
                    </h2>


                    <div class="details-grid">

                        <div class="detail-box">

                            <label>Date of Birth</label>

                            <span>
                                <?php
                                echo !empty($user['date_of_birth'])
                                    ? htmlspecialchars($user['date_of_birth'])
                                    : 'Not provided';
                                ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>Gender</label>

                            <span>
                                <?php
                                echo !empty($user['gender'])
                                    ? htmlspecialchars($user['gender'])
                                    : 'Not provided';
                                ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>Address</label>

                            <span>
                                <?php
                                echo !empty($user['address'])
                                    ? htmlspecialchars($user['address'])
                                    : 'Not provided';
                                ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>City</label>

                            <span>
                                <?php
                                echo !empty($user['city'])
                                    ? htmlspecialchars($user['city'])
                                    : 'Not provided';
                                ?>
                            </span>

                        </div>


                        <div class="detail-box">

                            <label>State</label>

                            <span>
                                <?php
                                echo !empty($user['state'])
                                    ? htmlspecialchars($user['state'])
                                    : 'Not provided';
                                ?>
                            </span>

                        </div>

                    </div>


                    <div class="detail-box bio-box">

                        <label>Bio</label>

                        <p>

                            <?php
                            echo !empty($user['bio'])
                                ? nl2br(htmlspecialchars($user['bio']))
                                : 'No bio available.';
                            ?>

                        </p>

                    </div>


                <?php endif; ?>

            </div>


            <!-- ACTIONS -->

            <div class="profile-actions">

                <a
                    href="users.php"
                    class="profile-action back-action"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Back

                </a>


                <?php if (!$isSuperAdmin): ?>

                    <a
                        href="edit_user.php?id=<?php echo $user['user_id']; ?>"
                        class="profile-action edit-action"
                    >

                        <i class="fa-solid fa-pen"></i>

                        Edit User

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>

</body>
</html>