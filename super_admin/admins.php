<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$loggedInId = (int)$_SESSION['user_id'];

// --- Stats ---
$totalAdmins = 0;
$totalSuperAdmins = 0;
$activeAdmins = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role IN ('admin', 'super_admin')");
if ($result) { $row = $result->fetch_assoc(); $totalAdmins = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'super_admin'");
if ($result) { $row = $result->fetch_assoc(); $totalSuperAdmins = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role IN ('admin', 'super_admin') AND status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $activeAdmins = (int)$row['total']; }

// --- Fetch all admins ---
$admins = [];
$sql = "SELECT user_id, username, email, phone, role, status FROM users WHERE role IN ('admin', 'super_admin') ORDER BY user_id DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrators | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        /* small extra tweaks for admin page */
        .promote-btn, .demote-btn {
            background: #f4f4f5;
            color: var(--black);
            border: none;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .promote-btn:hover {
            background: var(--orange);
            color: #fff;
        }
        .demote-btn:hover {
            background: var(--danger);
            color: #fff;
        }
        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="users-main">

    <!-- PAGE HEADER -->
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-user-shield"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Administrators</h1>
                <p>Manage all admin and super admin accounts.</p>
            </div>
        </div>
        <a href="add_user.php?role=admin" class="add-user-btn">
            <i class="fa-solid fa-user-plus"></i> Add Admin
        </a>
    </section>

    <!-- STATS -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-user-shield"></i></div>
            <div class="stat-content">
                <span>Total Admins</span>
                <strong><?= number_format($totalAdmins); ?></strong>
            </div>
        </div>
        <div class="stat-card admin-card">
            <div class="stat-icon"><i class="fa-solid fa-crown"></i></div>
            <div class="stat-content">
                <span>Super Admins</span>
                <strong><?= number_format($totalSuperAdmins); ?></strong>
            </div>
        </div>
        <div class="stat-card active-card">
            <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
            <div class="stat-content">
                <span>Active Admins</span>
                <strong><?= number_format($activeAdmins); ?></strong>
            </div>
        </div>
    </section>

    <!-- ADMIN TABLE -->
    <section class="users-card">
        <div class="table-header">
            <div>
                <span class="section-label">ADMIN DIRECTORY</span>
                <h2>All Administrators</h2>
            </div>
            <span class="user-count"><?= count($admins); ?> admin<?= count($admins) != 1 ? 's' : ''; ?></span>
        </div>

        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($admins)): ?>
                    <?php foreach ($admins as $admin): ?>
                        <?php
                        $username = $admin['username'] ?? 'Unknown';
                        $email = $admin['email'] ?? '';
                        $phone = $admin['phone'] ?? '';
                        $role = $admin['role'] ?? 'admin';
                        $status = $admin['status'] ?? 'inactive';
                        $avatarLetter = strtoupper(substr($username, 0, 1));
                        $isSuper = ($role === 'super_admin');
                        $isSelf = ((int)$admin['user_id'] === $loggedInId);
                        ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="avatar"><?= htmlspecialchars($avatarLetter); ?></div>
                                    <div class="user-name">
                                        <strong><?= htmlspecialchars($username); ?></strong>
                                        <small>@<?= htmlspecialchars($username); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <span><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($email); ?></span>
                                    <?php if ($phone): ?>
                                        <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($phone); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($isSuper): ?>
                                    <span class="role-badge super-admin"><i class="fa-solid fa-crown"></i> Super Admin</span>
                                <?php else: ?>
                                    <span class="role-badge admin"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($status === 'active'): ?>
                                    <span class="status-badge active"><span></span> Active</span>
                                <?php elseif ($status === 'blocked'): ?>
                                    <span class="status-badge blocked"><span></span> Blocked</span>
                                <?php else: ?>
                                    <span class="status-badge inactive"><span></span> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="user-id">#<?= (int)$admin['user_id']; ?></span></td>
                            <td>
                                <div class="action-group">
                                    <a href="view_user.php?id=<?= (int)$admin['user_id']; ?>" class="action-btn view-btn" title="View"><i class="fa-solid fa-eye"></i></a>

                                    <?php if (!$isSelf): ?>
                                        <a href="edit_user.php?id=<?= (int)$admin['user_id']; ?>" class="action-btn edit-btn" title="Edit"><i class="fa-solid fa-pen"></i></a>

                                        <!-- Demote only if not super_admin and not self -->
                                        <?php if (!$isSuper): ?>
                                            <a href="demote_user.php?id=<?= (int)$admin['user_id']; ?>" class="demote-btn" onclick="return confirm('Demote this admin to regular user?');">
                                                <i class="fa-solid fa-arrow-down"></i> Demote
                                            </a>
                                        <?php endif; ?>

                                        <a href="delete_user.php?id=<?= (int)$admin['user_id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this admin account?');"><i class="fa-solid fa-trash"></i></a>
                                    <?php else: ?>
                                        <span class="protected-badge" title="Your own account"><i class="fa-solid fa-lock"></i></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="empty-state"><div class="empty-icon"><i class="fa-solid fa-users-slash"></i></div><h3>No Admins Found</h3><p>There are no administrator accounts yet.</p></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

</body>
</html>