<?php
/* =========================================================
   NEXARENA ADMIN SIDEBAR
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
   CURRENT PAGE
========================================================= */

$current_page = basename($_SERVER['PHP_SELF']);

/* =========================================================
   ADMIN USER INFORMATION
========================================================= */

$admin_name = $_SESSION['username'] ?? 'Admin';
$admin_role = $_SESSION['role'] ?? 'admin';

/* =========================================================
   ADMIN INITIAL
========================================================= */

$admin_initial = strtoupper(substr($admin_name, 0, 1));

?>

<link rel="stylesheet" href="assets/sidebar.css">
<!-- Font Awesome 6 (Free) for high-quality icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- TOGGLE BUTTON (hamburger) - only visible on mobile -->
<button class="toggle-btn" id="toggleBtn" aria-label="Toggle sidebar">
    <i class="fas fa-bars"></i>
</button>

<aside class="sidebar" id="sidebar">

    <!-- =====================================================
         LOGO
    ====================================================== -->

    <div class="sidebar-logo">
        <img
            src="../assets/images/logo.png"
            alt="NexArena Logo"
            class="sidebar-logo-img"
        >
    </div>

    <!-- =====================================================
         ADMIN PROFILE
    ====================================================== -->

    <div class="sidebar-user">

        <div class="user-avatar">
            <?= htmlspecialchars($admin_initial) ?>
        </div>

        <div class="user-info">

            <strong>
                <?= htmlspecialchars($admin_name) ?>
            </strong>

            <small>
                <?= htmlspecialchars(str_replace('_', ' ', $admin_role)) ?>
            </small>

        </div>

    </div>

    <!-- =====================================================
         MENU
    ====================================================== -->

    <nav class="sidebar-menu">

        <!-- ===============================
             MAIN
        ================================ -->

        <span class="menu-title">
            Main
        </span>

        <!-- Dashboard -->

        <a
            href="dashboard.php"
            class="menu-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
        >

            <span class="menu-icon">
                <i class="fas fa-house-chimney"></i>
            </span>

            <span>
                Dashboard
            </span>

        </a>

        <!-- ===============================
             MANAGEMENT
        ================================ -->

        <span class="menu-title second">
            Management
        </span>

        <!-- Sports -->

        <a
            href="sports.php"
            class="menu-item <?= $current_page === 'sports.php' ? 'active' : '' ?>"
        >

            <span class="menu-icon">
                <i class="fas fa-futbol"></i>
            </span>

            <span>
                Sports
            </span>

        </a>

        <!-- Events -->

        <a
            href="events.php"
            class="menu-item <?= $current_page === 'events.php' ? 'active' : '' ?>"
        >

            <span class="menu-icon">
                <i class="fas fa-calendar-days"></i>
            </span>

            <span>
                Events
            </span>

        </a>

        <!-- Registrations -->

        <a
            href="registrations.php"
            class="menu-item <?= $current_page === 'registrations.php' ? 'active' : '' ?>"
        >

            <span class="menu-icon">
                <i class="fas fa-clipboard-check"></i>
            </span>

            <span>
                Registrations
            </span>

        </a>

        <!-- Teams -->

        <a
            href="add_team.php"
            class="menu-item <?= $current_page === 'teams.php' ? 'active' : '' ?>"
        >

            <span class="menu-icon">
                <i class="fas fa-chess-queen"></i>
            </span>

            <span>
                Teams
            </span>

        </a>

        <!-- ===============================
             USERS
        ================================ -->

        <span class="menu-title second">
            Users Management
        </span>

        <!-- Users -->

        <a
            href="users.php"
            class="menu-item <?= $current_page === 'users.php' ? 'active' : '' ?>"
        >

            <span class="menu-icon">
                <i class="fas fa-users"></i>
            </span>

            <span>
                Users
            </span>

        </a>

        <!-- Notifications -->

        <a
            href="notifications.php"
            class="menu-item <?= $current_page === 'notifications.php' ? 'active' : '' ?>"
        >

            <span class="menu-icon">
                <i class="fas fa-bell"></i>
            </span>

            <span>
                Notifications
            </span>

            <span class="notification-dot"></span>

        </a>

        <!-- ===============================
             ACCOUNT
        ================================ -->

        <span class="menu-title second">
            Account
        </span>

        <!-- Profile -->

        <a
            href="profile.php"
            class="menu-item <?= $current_page === 'profile.php' ? 'active' : '' ?>"
        >

            <span class="menu-icon">
                <i class="fas fa-user-circle"></i>
            </span>

            <span>
                My Profile
            </span>

        </a>

    </nav>

    <!-- =====================================================
         SIDEBAR BOTTOM
    ====================================================== -->

    <div class="sidebar-bottom">

        <!-- Logout -->

        <a
            href="../logout.php"
            class="logout-menu"
        >

            <span class="menu-icon">
                <i class="fas fa-right-from-bracket"></i>
            </span>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>

<!-- =========================================================
   JAVASCRIPT FOR TOGGLE FUNCTIONALITY
========================================================= -->
<script>
    (function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const overlay = document.getElementById('sidebarOverlay');
        const mainContent = document.querySelector('.main-content');

        // Check if we're on mobile
        function isMobile() {
            return window.innerWidth <= 700;
        }

        // Initialize sidebar state
        function initSidebar() {
            if (isMobile()) {
                // On mobile: sidebar is closed by default
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('active');
                toggleBtn.classList.remove('closed');
            } else {
                // On desktop: sidebar is always open
                sidebar.classList.remove('closed');
                sidebar.classList.add('open');
                if (overlay) overlay.classList.remove('active');
                toggleBtn.classList.remove('closed');
            }
        }

        // Toggle sidebar (only works on mobile)
        function toggleSidebar() {
            if (!isMobile()) return; // Only toggle on mobile

            const isOpen = sidebar.classList.contains('open');
            if (isOpen) {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('active');
                toggleBtn.classList.remove('closed');
            } else {
                sidebar.classList.add('open');
                if (overlay) overlay.classList.add('active');
                toggleBtn.classList.add('closed');
            }
        }

        // Close sidebar (mobile only)
        function closeSidebar() {
            if (isMobile() && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('active');
                toggleBtn.classList.remove('closed');
            }
        }

        // Event listeners
        toggleBtn.addEventListener('click', toggleSidebar);

        // Click overlay to close
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });

        // Handle window resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (isMobile()) {
                    // On mobile: ensure sidebar is closed if it was open and we resized
                    // But only if we're actually on mobile
                    if (sidebar.classList.contains('open')) {
                        // Keep it open if it was open, but ensure overlay is synced
                        if (overlay) overlay.classList.add('active');
                        toggleBtn.classList.add('closed');
                    } else {
                        if (overlay) overlay.classList.remove('active');
                        toggleBtn.classList.remove('closed');
                    }
                } else {
                    // On desktop: force sidebar open, remove overlay
                    sidebar.classList.add('open');
                    sidebar.classList.remove('closed');
                    if (overlay) overlay.classList.remove('active');
                    toggleBtn.classList.remove('closed');
                }
            }, 200);
        });

        // Initialize
        initSidebar();

        // Prevent anchor clicks from navigating (if href="#")
        document.querySelectorAll('.menu-item, .logout-menu').forEach(el => {
            el.addEventListener('click', function(e) {
                if (this.getAttribute('href') === '#') e.preventDefault();
                
                // Close sidebar on mobile when a menu item is clicked
                if (isMobile() && this.classList.contains('menu-item')) {
                    closeSidebar();
                }
                
                // Update active state
                document.querySelectorAll('.menu-item').forEach(item => item.classList.remove('active'));
                if (this.classList.contains('menu-item')) {
                    this.classList.add('active');
                }
            });
        });
    })();
</script>