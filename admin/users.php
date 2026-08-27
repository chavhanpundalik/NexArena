<?php
session_start();

require_once __DIR__ . '/../db_connect.php';
// Admin protection
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* =========================
   SEARCH & FILTER
========================= */

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role   = isset($_GET['role']) ? trim($_GET['role']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

/* =========================
   BUILD QUERY
========================= */

$sql = "SELECT 
            user_id,
            username,
            email,
            phone,
            full_name,
            role
        FROM users
        WHERE 1=1";

$params = [];
$types = "";

/* Search */
if ($search !== '') {
    $sql .= " AND (
                username LIKE ?
                OR email LIKE ?
                OR phone LIKE ?
                OR full_name LIKE ?
              )";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssss";
}

/* Role filter */
if ($role !== '') {
    $sql .= " AND role = ?";
    $params[] = $role;
    $types .= "s";
}

/*
   Status filter is only applied if your users table
   contains a status column.

   Currently commented out because your existing
   users schema may not have it.
*/

/*
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}
*/

$sql .= " ORDER BY user_id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("USER QUERY ERROR: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

/* =========================
   USER STATISTICS
========================= */

$totalUsers = 0;
$totalAdmins = 0;
$totalNormalUsers = 0;

$countQuery = "SELECT 
                COUNT(*) AS total_users,
                SUM(role = 'admin') AS total_admins,
                SUM(role = 'user') AS total_normal_users
               FROM users";

$countResult = $conn->query($countQuery);

if ($countResult) {
    $countData = $countResult->fetch_assoc();

    $totalUsers = (int)$countData['total_users'];
    $totalAdmins = (int)$countData['total_admins'];
    $totalNormalUsers = (int)$countData['total_normal_users'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Management | NexArena</title>

    <link rel="stylesheet" href="assets/users.css">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body>

    <!-- =========================
         SIDEBAR
    ========================== -->

    <?php include "sidebar.php"; ?>


    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="admin-main">

        <!-- Page Header -->

        <div class="page-header">

            <div>
                <h1>User Management</h1>

                <p>
                    Manage NexArena users, roles and accounts.
                </p>
            </div>

        </div>


        <!-- =========================
             STATISTICS
        ========================== -->

        <section class="stats-grid">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div class="stat-info">

                    <span>Total Users</span>

                    <h2>
                        <?php echo $totalUsers; ?>
                    </h2>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="stat-info">

                    <span>Normal Users</span>

                    <h2>
                        <?php echo $totalNormalUsers; ?>
                    </h2>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-user-shield"></i>
                </div>

                <div class="stat-info">

                    <span>Admins</span>

                    <h2>
                        <?php echo $totalAdmins; ?>
                    </h2>

                </div>

            </div>

        </section>


        <!-- =========================
             USER MANAGEMENT CARD
        ========================== -->

        <section class="users-card">


            <!-- Card Header -->

            <div class="users-card-header">

                <div>

                    <h2>All Users</h2>

                    <p>
                        View and manage registered users.
                    </p>

                </div>

            </div>


            <!-- =========================
                 SEARCH & FILTER
            ========================== -->

            <form method="GET" class="filter-bar">

                <div class="search-box">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search name, username, email or phone..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <div class="filter-group">

                    <select name="role">

                        <option value="">All Roles</option>

                        <option
                            value="user"
                            <?php echo ($role === 'user') ? 'selected' : ''; ?>
                        >
                            User
                        </option>

                        <option
                            value="admin"
                            <?php echo ($role === 'admin') ? 'selected' : ''; ?>
                        >
                            Admin
                        </option>

                    </select>


                    <button type="submit" class="filter-btn">

                        <i class="fa-solid fa-filter"></i>

                        Filter

                    </button>


                    <?php if ($search !== '' || $role !== ''): ?>

                        <a href="users.php" class="reset-btn">

                            <i class="fa-solid fa-rotate-left"></i>

                            Reset

                        </a>

                    <?php endif; ?>

                </div>

            </form>


            <!-- =========================
                 USER TABLE
            ========================== -->

            <div class="table-container">

                <table class="users-table">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>User</th>

                            <th>Email</th>

                            <th>Phone</th>

                            <th>Role</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if ($result->num_rows > 0): ?>

                        <?php while ($user = $result->fetch_assoc()): ?>

                            <tr>

                                <!-- ID -->

                                <td>

                                    <span class="user-id">
                                        #<?php echo $user['user_id']; ?>
                                    </span>

                                </td>


                                <!-- USER -->

                                <td>

                                    <div class="user-info">

                                        <div class="avatar">

                                            <?php
                                            $name = !empty($user['full_name'])
                                                ? $user['full_name']
                                                : $user['username'];

                                            echo strtoupper(substr($name, 0, 1));
                                            ?>

                                        </div>

                                        <div>

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    !empty($user['full_name'])
                                                        ? $user['full_name']
                                                        : $user['username']
                                                );
                                                ?>
                                            </strong>

                                            <small>
                                                @<?php echo htmlspecialchars($user['username']); ?>
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <span class="email-text">

                                        <?php echo htmlspecialchars($user['email']); ?>

                                    </span>

                                </td>


                                <!-- PHONE -->

                                <td>

                                    <?php if (!empty($user['phone'])): ?>

                                        <?php echo htmlspecialchars($user['phone']); ?>

                                    <?php else: ?>

                                        <span class="not-available">
                                            Not provided
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ROLE -->

                                <td>

                                    <?php if ($user['role'] === 'admin'): ?>

                                        <span class="role-badge admin-role">

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


                                <!-- ACTIONS -->

                                <td>

                                    <div class="action-buttons">

                                        <a
                                            href="view_user.php?id=<?php echo $user['user_id']; ?>"
                                            class="action-btn view-btn"
                                            title="View User"
                                        >

                                            <i class="fa-solid fa-eye"></i>

                                        </a>


                                        <a
                                            href="edit_user.php?id=<?php echo $user['user_id']; ?>"
                                            class="action-btn edit-btn"
                                            title="Edit User"
                                        >

                                            <i class="fa-solid fa-pen"></i>

                                        </a>


                                        <a
                                            href="delete_user.php?id=<?php echo $user['user_id']; ?>"
                                            class="action-btn delete-btn"
                                            title="Delete User"
                                            onclick="return confirm('Are you sure you want to delete this user?');"
                                        >

                                            <i class="fa-solid fa-trash"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6">

                                <div class="empty-state">

                                    <i class="fa-solid fa-users-slash"></i>

                                    <h3>No Users Found</h3>

                                    <p>
                                        No users match your search or filter.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>


</body>

</html>