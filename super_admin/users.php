<?php
session_start();

require_once "../db_connect.php";

/* =========================================================
   SUPER ADMIN ACCESS
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

/*
   Optional role protection.
   Adjust if your session uses a different role variable.
*/
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

/* =========================================================
   SEARCH & FILTER
========================================================= */

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role   = isset($_GET['role']) ? trim($_GET['role']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

/* =========================================================
   STATISTICS
========================================================= */

$totalUsers = 0;
$activeUsers = 0;
$adminUsers = 0;
$inactiveUsers = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    $totalUsers = (int)$row['total'];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE status = 'active'
");

if ($result) {
    $row = $result->fetch_assoc();
    $activeUsers = (int)$row['total'];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role IN ('admin', 'super_admin')
");

if ($result) {
    $row = $result->fetch_assoc();
    $adminUsers = (int)$row['total'];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE status != 'active' OR status IS NULL
");

if ($result) {
    $row = $result->fetch_assoc();
    $inactiveUsers = (int)$row['total'];
}

/* =========================================================
   USER QUERY
========================================================= */

$sql = "
    SELECT
        user_id,
        username,
        email,
        phone,
        role,
        status
    FROM users
    WHERE 1=1
";

$params = [];
$types = '';

/* Search */
if ($search !== '') {
    $sql .= "
        AND (
            username LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $searchValue = "%{$search}%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}

/* Role */
if ($role !== '') {
    $sql .= " AND role = ?";
    $params[] = $role;
    $types .= "s";
}

/* Status */
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY user_id DESC";

/* =========================================================
   PREPARE QUERY
========================================================= */

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("User query error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$usersResult = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Management | NexArena Super Admin</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    ><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">

    <!-- ====== IMPORTANT: Load sidebar CSS ====== -->
    <link
        rel="stylesheet"
        href="assets/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="assets/users.css"
    >

</head>

<body>

<?php include "sidebar.php"; ?>


<main class="users-main">
    
<?php if (isset($_SESSION['delete_success'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;">
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['delete_success']); ?>
    </div>
    <?php unset($_SESSION['delete_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['delete_error'])): ?>
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['delete_error']); ?>
    </div>
    <?php unset($_SESSION['delete_error']); ?>
<?php endif; ?>
    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <section class="page-header">

        <div class="header-left">

            <div class="header-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <div>
                <span class="page-label">SUPER ADMIN</span>

                <h1>User Management</h1>

                <p>
                    Manage NexArena users, roles and account status.
                </p>
            </div>

        </div>

        <a href="add_user.php" class="add-user-btn">
            <i class="fa-solid fa-user-plus"></i>
            Add User
        </a>

    </section>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <div class="stat-content">

                <span>Total Users</span>

                <strong>
                    <?= number_format($totalUsers); ?>
                </strong>

            </div>

        </div>


        <div class="stat-card active-card">

            <div class="stat-icon">
                <i class="fa-solid fa-user-check"></i>
            </div>

            <div class="stat-content">

                <span>Active Users</span>

                <strong>
                    <?= number_format($activeUsers); ?>
                </strong>

            </div>

        </div>


        <div class="stat-card admin-card">

            <div class="stat-icon">
                <i class="fa-solid fa-user-shield"></i>
            </div>

            <div class="stat-content">

                <span>Administrators</span>

                <strong>
                    <?= number_format($adminUsers); ?>
                </strong>

            </div>

        </div>


        <div class="stat-card inactive-card">

            <div class="stat-icon">
                <i class="fa-solid fa-user-slash"></i>
            </div>

            <div class="stat-content">

                <span>Inactive Users</span>

                <strong>
                    <?= number_format($inactiveUsers); ?>
                </strong>

            </div>

        </div>

    </section>


    <!-- =====================================================
         SEARCH & FILTER
    ====================================================== -->

    <section class="filter-card">

        <form method="GET" class="filter-form">

            <div class="search-box">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    placeholder="Search username, email or phone..."
                    value="<?= htmlspecialchars($search); ?>"
                >

            </div>


            <div class="select-box">

                <i class="fa-solid fa-user-tag"></i>

                <select name="role">

                    <option value="">All Roles</option>

                    <option
                        value="user"
                        <?= $role === 'user' ? 'selected' : ''; ?>
                    >
                        User
                    </option>

                    <option
                        value="admin"
                        <?= $role === 'admin' ? 'selected' : ''; ?>
                    >
                        Admin
                    </option>

                    <option
                        value="super_admin"
                        <?= $role === 'super_admin' ? 'selected' : ''; ?>
                    >
                        Super Admin
                    </option>

                </select>

            </div>


            <div class="select-box">

                <i class="fa-solid fa-circle-half-stroke"></i>

                <select name="status">

                    <option value="">All Status</option>

                    <option
                        value="active"
                        <?= $status === 'active' ? 'selected' : ''; ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= $status === 'inactive' ? 'selected' : ''; ?>
                    >
                        Inactive
                    </option>

                    <option
                        value="blocked"
                        <?= $status === 'blocked' ? 'selected' : ''; ?>
                    >
                        Blocked
                    </option>

                </select>

            </div>


            <button type="submit" class="filter-btn">
                <i class="fa-solid fa-filter"></i>
                Filter
            </button>


            <a href="users.php" class="reset-btn">
                <i class="fa-solid fa-rotate-left"></i>
                Reset
            </a>

        </form>

    </section>


    <!-- =====================================================
         USER TABLE
    ====================================================== -->

    <section class="users-card">

        <div class="table-header">

            <div>

                <span class="section-label">
                    USER DIRECTORY
                </span>

                <h2>All Users</h2>

            </div>

            <span class="user-count">

                <?= $usersResult->num_rows; ?>

                result<?= $usersResult->num_rows != 1 ? 's' : ''; ?>

            </span>

        </div>


        <div class="table-wrapper">

            <table class="users-table">

                <thead>

                    <tr>

                        <th>User</th>

                        <th>Contact</th>

                        <th>Role</th>

                        <th>Status</th>

                        <th>User ID</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($usersResult->num_rows > 0): ?>

                    <?php while ($user = $usersResult->fetch_assoc()): ?>

                        <?php

                        $username = $user['username'] ?? 'Unknown';

                        $email = $user['email'] ?? '';

                        $phone = $user['phone'] ?? '';

                        $userRole = $user['role'] ?? 'user';

                        $userStatus = $user['status'] ?? 'inactive';

                        $avatarLetter = strtoupper(
                            substr($username, 0, 1)
                        );

                        ?>

                        <tr>

                            <!-- USER -->

                            <td>

                                <div class="user-info">

                                    <div class="avatar">

                                        <?= htmlspecialchars($avatarLetter); ?>

                                    </div>

                                    <div class="user-name">

                                        <strong>
                                            <?= htmlspecialchars($username); ?>
                                        </strong>

                                        <small>
                                            @<?= htmlspecialchars($username); ?>
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <!-- CONTACT -->

                            <td>

                                <div class="contact-info">

                                    <span>
                                        <i class="fa-solid fa-envelope"></i>

                                        <?= htmlspecialchars($email); ?>
                                    </span>

                                    <?php if (!empty($phone)): ?>

                                        <span>
                                            <i class="fa-solid fa-phone"></i>

                                            <?= htmlspecialchars($phone); ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- ROLE -->

                            <td>

                                <?php if ($userRole === 'super_admin'): ?>

                                    <span class="role-badge super-admin">
                                        <i class="fa-solid fa-crown"></i>
                                        Super Admin
                                    </span>

                                <?php elseif ($userRole === 'admin'): ?>

                                    <span class="role-badge admin">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        Admin
                                    </span>

                                <?php else: ?>

                                    <span class="role-badge user-role">
                                        <i class="fa-solid fa-user"></i>
                                        User
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php if ($userStatus === 'active'): ?>

                                    <span class="status-badge active">
                                        <span></span>
                                        Active
                                    </span>

                                <?php elseif ($userStatus === 'blocked'): ?>

                                    <span class="status-badge blocked">
                                        <span></span>
                                        Blocked
                                    </span>

                                <?php else: ?>

                                    <span class="status-badge inactive">
                                        <span></span>
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ID -->

                            <td>

                                <span class="user-id">
                                    #<?= (int)$user['user_id']; ?>
                                </span>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div class="action-buttons">

                                    <a
                                        href="view_user.php?id=<?= (int)$user['user_id']; ?>"
                                        class="action-btn view-btn"
                                        title="View User"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                    </a>


                                    <?php
                                    /*
                                      Prevent editing Super Admin accounts
                                      from normal User Management.
                                    */

                                    if ($userRole !== 'super_admin'):
                                    ?>

                                        <a
                                            href="edit_user.php?id=<?= (int)$user['user_id']; ?>"
                                            class="action-btn edit-btn"
                                            title="Edit User"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </a>


                                        <a
                                            href="delete_user.php?id=<?= (int)$user['user_id']; ?>"
                                            class="action-btn delete-btn"
                                            title="Delete User"
                                            onclick="return confirm('Are you sure you want to delete this user?');"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </a>

                                    <?php else: ?>

                                        <span
                                            class="protected-badge"
                                            title="Protected account"
                                        >
                                            <i class="fa-solid fa-lock"></i>
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="6"
                            class="empty-state"
                        >

                            <div class="empty-icon">
                                <i class="fa-solid fa-users-slash"></i>
                            </div>

                            <h3>No Users Found</h3>

                            <p>
                                No users match your current search or filters.
                            </p>

                            <a
                                href="users.php"
                                class="empty-reset"
                            >
                                Clear Filters
                            </a>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<!-- =====================================================
     SIDEBAR TOGGLE SCRIPT (already in sidebar.php)
====================================================== -->
<!-- No need to duplicate – it's already in sidebar.php -->

</body>
</html>