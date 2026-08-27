<?php
/* =========================================================
   NEXARENA SUPER ADMIN SIDEBAR
   MySQLi Compatible - High-Quality Icons
========================================================= */

/* =========================================================
   CURRENT PAGE
========================================================= */

$current_page = basename($_SERVER['PHP_SELF']);

/* =========================================================
   SUPER ADMIN INFORMATION
========================================================= */

$admin_name = $_SESSION['full_name'] ?? 'Super Admin';
$admin_role = $_SESSION['role'] ?? 'Super Admin';
$avatar_letter = strtoupper(substr(trim($admin_name), 0, 1));

/* =========================================================
   DATABASE COUNTS - MySQLi
========================================================= */

$total_users = 0;
$total_events = 0;
$total_teams = 0;

if (isset($conn) && $conn instanceof mysqli) {

    $result = $conn->query("SELECT COUNT(*) AS total FROM users");
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        $total_users = (int)($row['total'] ?? 0);
        $result->free();
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM events");
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        $total_events = (int)($row['total'] ?? 0);
        $result->free();
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM teams");
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        $total_teams = (int)($row['total'] ?? 0);
        $result->free();
    }
}

// --- Fetch profile image for the logged-in admin ---
$profile_image = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT profile_image FROM user_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $profile_image = $row['profile_image'];
    }
    $stmt->close();
}
?>

<!-- =====================================================
     SIDEBAR TOGGLE (for mobile)
====================================================== -->
<button class="sidebar-toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
    <i class="fas fa-bars"></i>
</button>

<!-- =====================================================
     OVERLAY (for mobile)
====================================================== -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- =====================================================
     SUPER ADMIN SIDEBAR
====================================================== -->
<aside class="super-sidebar" id="superSidebar">

    <!-- =================================================
         BRAND
    ================================================== -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-crown"></i>
        </div>
        <div class="brand-text">
            <div class="brand-name">
                Nex<span class="brand-name-highlight">Arena</span>
            </div>
            <div class="brand-badge">
                <i class="fas fa-shield-alt"></i> Super Admin
            </div>
        </div>
    </div>

    <!-- =================================================
         SUPER ADMIN PROFILE (with profile image)
    ================================================== -->
    <div class="sidebar-user">
        <div class="user-avatar-wrapper">
            <div class="user-avatar">
                <?php if (!empty($profile_image) && file_exists('uploads/profiles/' . $profile_image)): ?>
                    <img src="uploads/profiles/<?= htmlspecialchars($profile_image); ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <?= htmlspecialchars($avatar_letter); ?>
                <?php endif; ?>
            </div>
            <span class="online-status"><i class="fas fa-circle"></i></span>
        </div>
        <div class="user-info">
            <strong><?= htmlspecialchars($admin_name); ?></strong>
            <small><i class="fas fa-user-cog"></i> Super Admin</small>
        </div>
    </div>

    <!-- =================================================
         SIDEBAR STATISTICS
    ================================================== -->
    <div class="sidebar-stats">
        <div class="stat-item">
            <span class="stat-number"><?= $total_users; ?></span>
            <span class="stat-label"><i class="fas fa-users"></i> Users</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-number"><?= $total_events; ?></span>
            <span class="stat-label"><i class="fas fa-calendar-alt"></i> Events</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-number"><?= $total_teams; ?></span>
            <span class="stat-label"><i class="fas fa-users-cog"></i> Teams</span>
        </div>
    </div>

    <!-- =================================================
         NAVIGATION
    ================================================== -->
    <nav class="sidebar-nav">

        <!-- OVERVIEW -->
        <div class="nav-section">
            <span class="nav-section-title"><i class="fas fa-th-large"></i> Overview</span>
            <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                <span class="nav-text">Dashboard</span>
            </a>
        </div>

        <!-- USER MANAGEMENT -->
        <div class="nav-section">
            <span class="nav-section-title"><i class="fas fa-user-shield"></i> User Management</span>
            <a href="users.php" class="nav-item <?= $current_page === 'users.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                <span class="nav-text">All Users</span>
                <span class="nav-count"><?= $total_users; ?></span>
            </a>
            <a href="admins.php" class="nav-item <?= $current_page === 'admins.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-user-tie"></i></span>
                <span class="nav-text">Administrators</span>
            </a>
            <a href="add_user.php" class="nav-item <?= $current_page === 'add_user.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-user-plus"></i></span>
                <span class="nav-text">Add User</span>
                <span class="nav-badge"><i class="fas fa-star"></i> New</span>
            </a>
        </div>

        <!-- SPORTS & EVENTS -->
        <div class="nav-section">
            <span class="nav-section-title"><i class="fas fa-sports-ball"></i> Sports & Events</span>
            <a href="sports.php" class="nav-item <?= $current_page === 'sports.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-trophy"></i></span>
                <span class="nav-text">Sports</span>
            </a>
            <a href="events.php" class="nav-item <?= $current_page === 'events.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
                <span class="nav-text">Events</span>
                <span class="nav-count"><?= $total_events; ?></span>
            </a>
            <a href="registrations.php" class="nav-item <?= $current_page === 'registrations.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
                <span class="nav-text">Registrations</span>
            </a>
        </div>

        <!-- COMPETITION -->
        <div class="nav-section">
            <span class="nav-section-title"><i class="fas fa-flag-checkered"></i> Competition</span>
            <a href="teams.php" class="nav-item <?= $current_page === 'teams.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                <span class="nav-text">Teams</span>
                <span class="nav-count"><?= $total_teams; ?></span>
            </a>
            <a href="fixtures.php" class="nav-item <?= $current_page === 'fixtures.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-calendar-alt"></i></span>
                <span class="nav-text">Fixtures</span>
            </a>
            <a href="leaderboard.php" class="nav-item <?= $current_page === 'leaderboard.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-list-ol"></i></span>
                <span class="nav-text">Leaderboard</span>
            </a>
        </div>

        <!-- SYSTEM -->
        <div class="nav-section">
            <span class="nav-section-title"><i class="fas fa-cogs"></i> System</span>
            <a href="notifications.php" class="nav-item <?= $current_page === 'notifications.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-bell"></i></span>
                <span class="nav-text">Notifications</span>
                <span class="notification-dot pulse"></span>
            </a>
            <a href="reports.php" class="nav-item <?= $current_page === 'reports.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                <span class="nav-text">Reports</span>
            </a>
            <a href="system_activity.php" class="nav-item <?= $current_page === 'system_activity.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-history"></i></span>
                <span class="nav-text">System Activity</span>
            </a>
        </div>

        <!-- ACCOUNT -->
        <div class="nav-section">
            <span class="nav-section-title"><i class="fas fa-user-circle"></i> Account</span>
            <a href="profile.php" class="nav-item <?= $current_page === 'profile.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-id-badge"></i></span>
                <span class="nav-text">My Profile</span>
            </a>
            <a href="settings.php" class="nav-item <?= $current_page === 'settings.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-sliders-h"></i></span>
                <span class="nav-text">Settings</span>
            </a>
        </div>

    </nav>

    <!-- =================================================
         SIDEBAR FOOTER
    ================================================== -->
    <div class="sidebar-footer">

        <div class="system-status">
            <div class="status-indicator online">
                <span class="status-dot"><i class="fas fa-circle"></i></span>
                <span><i class="fas fa-server"></i> System Online</span>
            </div>
            <span class="status-version"><i class="fas fa-code-branch"></i> v1.0</span>
        </div>

        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
            <span class="logout-shortcut"><i class="fas fa-key"></i> ESC</span>
        </a>

    </div>

</aside>

<!-- =====================================================
     SIDEBAR TOGGLE SCRIPT
====================================================== -->
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('superSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
}

// Close sidebar on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('superSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
    }
});

// Close sidebar on window resize (if going from mobile to desktop)
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        const sidebar = document.getElementById('superSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    }
});
</script>