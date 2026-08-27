<!-- At the top of sidebar.php, add this to get dark mode setting -->
<?php

// Get pending registrations count for notification dot
$pending_registrations_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id_sidebar = (int) $_SESSION['user_id'];
    $pending_sql = "SELECT COUNT(*) as count FROM registrations WHERE user_id = ? AND status = 'pending'";
    $pending_stmt = $conn->prepare($pending_sql);
    if ($pending_stmt) {
        $pending_stmt->bind_param("i", $user_id_sidebar);
        $pending_stmt->execute();
        $pending_result = $pending_stmt->get_result();
        if ($pending_result) {
            $pending_row = $pending_result->fetch_assoc();
            $pending_registrations_count = (int)($pending_row['count'] ?? 0);
        }
        $pending_stmt->close();
    }
}
// Get dark mode setting for sidebar
$dark_mode_sidebar = 0;
if (isset($_SESSION['user_id'])) {
    $user_id_sidebar = (int) $_SESSION['user_id'];
    $settings_sql_sidebar = "SELECT dark_mode FROM user_settings WHERE user_id = ?";
    $settings_stmt_sidebar = $conn->prepare($settings_sql_sidebar);
    if ($settings_stmt_sidebar) {
        $settings_stmt_sidebar->bind_param("i", $user_id_sidebar);
        $settings_stmt_sidebar->execute();
        $settings_result_sidebar = $settings_stmt_sidebar->get_result();
        if ($settings_result_sidebar->num_rows > 0) {
            $settings_data_sidebar = $settings_result_sidebar->fetch_assoc();
            $dark_mode_sidebar = $settings_data_sidebar['dark_mode'] ?? 0;
        }
        $settings_stmt_sidebar->close();
    }
}
$dark_mode_sidebar_class = ($dark_mode_sidebar == 1) ? 'dark-mode' : '';
?>

<!-- Update the sidebar container to include dark mode class -->
<div class="sidebar <?php echo $dark_mode_sidebar_class; ?>">
    <!-- ... rest of sidebar content ... -->
</div>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexArena Sidebar</title>
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">

    <!-- LOGO - Only Image -->
    <div class="sidebar-logo">
        <div class="logo-mark">
            <img src="../assets/images/logo.png" alt="NexArena" class="logo-image">
        </div>
    </div>

    <!-- USER PROFILE (CLICKABLE) -->
    <div class="sidebar-user" id="userProfileTrigger">
        <div class="user-avatar">
            <?php
            $initial = strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1));
            echo $initial;
            ?>
        </div>
        <div class="user-info">
            <strong>
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
            </strong>
            <small>
                <i class="fas fa-circle" style="font-size: 6px; color: #4ade80; margin-right: 6px;"></i>
                Online
            </small>
        </div>
        <div class="user-badge">
            <i class="fas fa-chevron-right"></i>
        </div>
    </div>

    <!-- MENU -->
    <nav class="sidebar-menu">
        <p class="menu-title">MAIN MENU</p>

        <!-- DASHBOARD -->
        <a href="dashboard.php" class="menu-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-th-large"></i></span>
            <span>Dashboard</span>
            <?php if($current_page === 'dashboard.php'): ?>
                <span class="menu-indicator"></span>
            <?php endif; ?>
        </a>

        <!-- SPORTS -->
        <a href="sports.php" class="menu-item <?php echo $current_page === 'sports.php' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-trophy"></i></span>
            <span>Sports</span>
        </a>

        <!-- EVENTS -->
        <a href="events.php" class="menu-item <?php echo $current_page === 'events.php' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-calendar-alt"></i></span>
            <span>Events</span>
        </a>

        <!-- REGISTRATIONS -->
        <a href="my_registration.php" class="menu-item <?php echo $current_page === 'my_registration.php' ? 'active' : ''; ?>">
    <span class="menu-icon"><i class="fas fa-clipboard-list"></i></span>
    <span>My Registration</span>
    <?php 
    // Show notification dot if there are pending registrations
    if (isset($pending_registrations_count) && $pending_registrations_count > 0): 
    ?>
        <span class="notification-dot"></span>
    <?php endif; ?>
</a>

        <p class="menu-title second">TEAM MANAGEMENT</p>

        <!-- TEAMS -->
        <a href="teams.php" class="menu-item <?php echo $current_page === 'teams.php' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-users"></i></span>
            <span>My Teams</span>
            <span class="notification-dot"></span>
        </a>

        <!-- LEADERBOARD -->
        <a href="leaderboard.php" class="menu-item <?php echo $current_page === 'leaderboard.php' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-ranking-star"></i></span>
            <span>Leaderboard</span>
        </a>

        <!-- FIXTURES -->
        <a href="fixture.php" class="menu-item <?php echo $current_page === 'fixture.php' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-clock"></i></span>
            <span>Fixtures</span>
        </a>

        <!-- NOTIFICATIONS -->
        <a href="notification.php" class="menu-item <?php echo $current_page === 'notification.php' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-bell"></i></span>
            <span>Notifications</span>
            <span class="notification-dot"></span>
        </a>

        <!-- PROFILE -->
        <a href="profile.php" class="menu-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-user"></i></span>
            <span>My Profile</span>
        </a>

        <!-- SETTINGS -->
        <a href="settings.php" class="menu-item <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-cog"></i></span>
            <span>Settings</span>
        </a>
    </nav>

    <!-- BOTTOM -->
    <div class="sidebar-bottom">
        <a href="../logout.php" class="logout-menu">
            <span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span>Logout</span>
        </a>
    </div>

</aside>

<!-- USER PROFILE MODAL -->
<div id="userProfileModal" class="profile-modal-overlay">
    <div class="profile-modal-box">
        <div class="profile-modal-header">
            <div class="profile-modal-avatar">
                <?php 
                $full_name = $_SESSION['full_name'] ?? 'User';
                $initial = strtoupper(substr($full_name, 0, 1));
                ?>
                <span><?php echo $initial; ?></span>
            </div>
            <button class="profile-modal-close" id="closeProfileModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="profile-modal-body">
            <h2>My Profile</h2>
            <p class="profile-subtitle">View and manage your personal information</p>
            
            <form id="profileEditForm" action="update_profile.php" method="POST">
                <div class="profile-form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                </div>
                
                <div class="profile-form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? 'user@example.com'); ?>" required>
                </div>
                
                <div class="profile-form-row">
                    <div class="profile-form-group half">
                        <label><i class="fas fa-phone"></i> Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? '+1 234 567 8900'); ?>">
                    </div>
                    <div class="profile-form-group half">
                        <label><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($_SESSION['location'] ?? 'New York, USA'); ?>">
                    </div>
                </div>
                
                <div class="profile-form-group">
                    <label><i class="fas fa-calendar"></i> Date of Birth</label>
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($_SESSION['dob'] ?? '1995-01-01'); ?>">
                </div>
                
                <div class="profile-form-group">
                    <label><i class="fas fa-gamepad"></i> Favorite Game</label>
                    <select name="favorite_game">
                        <option value="Valorant" <?php echo ($_SESSION['favorite_game'] ?? '') == 'Valorant' ? 'selected' : ''; ?>>Valorant</option>
                        <option value="CS:GO" <?php echo ($_SESSION['favorite_game'] ?? '') == 'CS:GO' ? 'selected' : ''; ?>>CS:GO</option>
                        <option value="Dota 2" <?php echo ($_SESSION['favorite_game'] ?? '') == 'Dota 2' ? 'selected' : ''; ?>>Dota 2</option>
                        <option value="League of Legends" <?php echo ($_SESSION['favorite_game'] ?? '') == 'League of Legends' ? 'selected' : ''; ?>>League of Legends</option>
                        <option value="Apex Legends" <?php echo ($_SESSION['favorite_game'] ?? '') == 'Apex Legends' ? 'selected' : ''; ?>>Apex Legends</option>
                    </select>
                </div>
                
                <div class="profile-form-actions">
                    <button type="button" class="profile-btn-cancel" id="cancelProfileEdit">Cancel</button>
                    <button type="submit" class="profile-btn-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Profile Modal
    const profileModal = document.getElementById('userProfileModal');
    const profileTrigger = document.getElementById('userProfileTrigger');
    const closeModal = document.getElementById('closeProfileModal');
    const cancelBtn = document.getElementById('cancelProfileEdit');

    // Open modal when user clicks on profile
    if (profileTrigger) {
        profileTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            if (profileModal) {
                profileModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        });
    }

    // Close modal functions
    function closeProfileModalFn() {
        if (profileModal) {
            profileModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    if (closeModal) {
        closeModal.addEventListener('click', closeProfileModalFn);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeProfileModalFn);
    }

    // Close on overlay click
    if (profileModal) {
        profileModal.addEventListener('click', function(e) {
            if (e.target === profileModal) {
                closeProfileModalFn();
            }
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && profileModal && profileModal.style.display === 'flex') {
            closeProfileModalFn();
        }
    });
});
</script>
    
</body>
</html>