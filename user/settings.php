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


$user_id = (int) $_SESSION['user_id'];


/* ========================================
   HANDLE SETTINGS UPDATE
======================================== */

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form data
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $event_reminders = isset($_POST['event_reminders']) ? 1 : 0;
    $team_updates = isset($_POST['team_updates']) ? 1 : 0;
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
    $language = isset($_POST['language']) ? $_POST['language'] : 'en';
    $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC';
    $date_format = isset($_POST['date_format']) ? $_POST['date_format'] : 'd M Y';
    $time_format = isset($_POST['time_format']) ? $_POST['time_format'] : '12';

    // Check if settings exist for user
    $check_sql = "SELECT setting_id FROM user_settings WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $settings_exist = $check_result->num_rows > 0;
    $check_stmt->close();

    if ($settings_exist) {
        // Update existing settings
        $update_sql = "
            UPDATE user_settings 
            SET 
                notifications_enabled = ?,
                email_notifications = ?,
                event_reminders = ?,
                team_updates = ?,
                dark_mode = ?,
                language = ?,
                timezone = ?,
                date_format = ?,
                time_format = ?
            WHERE user_id = ?
        ";

        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param(
            "iiiiissssi",
            $notifications_enabled,
            $email_notifications,
            $event_reminders,
            $team_updates,
            $dark_mode,
            $language,
            $timezone,
            $date_format,
            $time_format,
            $user_id
        );

        if ($update_stmt->execute()) {
            $message = "Settings updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating settings: " . $conn->error;
            $message_type = "error";
        }
        $update_stmt->close();
    } else {
        // Insert new settings
        $insert_sql = "
            INSERT INTO user_settings (
                user_id,
                notifications_enabled,
                email_notifications,
                event_reminders,
                team_updates,
                dark_mode,
                language,
                timezone,
                date_format,
                time_format
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            "iiiiissssi",
            $user_id,
            $notifications_enabled,
            $email_notifications,
            $event_reminders,
            $team_updates,
            $dark_mode,
            $language,
            $timezone,
            $date_format,
            $time_format
        );

        if ($insert_stmt->execute()) {
            $message = "Settings saved successfully!";
            $message_type = "success";
        } else {
            $message = "Error saving settings: " . $conn->error;
            $message_type = "error";
        }
        $insert_stmt->close();
    }
}


/* ========================================
   GET CURRENT SETTINGS
======================================== */

$settings_sql = "
    SELECT 
        notifications_enabled,
        email_notifications,
        event_reminders,
        team_updates,
        dark_mode,
        language,
        timezone,
        date_format,
        time_format
    FROM user_settings 
    WHERE user_id = ?
";

$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->bind_param("i", $user_id);
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();

// Default settings
$settings = [
    'notifications_enabled' => 1,
    'email_notifications' => 1,
    'event_reminders' => 1,
    'team_updates' => 1,
    'dark_mode' => 0,
    'language' => 'en',
    'timezone' => 'UTC',
    'date_format' => 'd M Y',
    'time_format' => '12'
];

if ($settings_result->num_rows > 0) {
    $settings = array_merge($settings, $settings_result->fetch_assoc());
}
$settings_stmt->close();


/* ========================================
   GET USER INFO FOR DISPLAY
======================================== */

$user_sql = "SELECT full_name, email FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();


/* ========================================
   SET DARK MODE CLASS
======================================== */

// This is the important part - check if dark mode is enabled
$dark_mode_class = ($settings['dark_mode'] == 1) ? 'dark-mode' : '';


// $conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | NexArena</title>
    <link rel="stylesheet" href="assets/settings.css">
    <!-- Theme CSS (must be loaded first) -->
<link rel="stylesheet" href="assets/theme.css">
</head>

<body class="<?php echo $dark_mode_class; ?>">
    <?php include "sidebar.php"; ?>

    <!-- ========================================
         MAIN CONTENT
    ======================================== -->

    <div class="settings-main">

        <main class="settings-container">

            <!-- ========================================
                 HEADER
            ======================================== -->

            <section class="settings-header">

                <div class="header-content">

                    <span class="page-label">
                        NEXARENA SETTINGS
                    </span>

                    <h1>
                        Settings
                    </h1>

                    <p>
                        Manage your account preferences,
                        notifications, and appearance settings.
                    </p>

                </div>

            </section>

            <!-- ========================================
                 MESSAGE
            ======================================== -->

            <?php if ($message): ?>

                <div class="settings-message <?php echo $message_type; ?>">

                    <span class="message-icon">
                        <?php echo $message_type === 'success' ? '✅' : '❌'; ?>
                    </span>

                    <p>
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                </div>

            <?php endif; ?>

            <!-- ========================================
                 SETTINGS FORM
            ======================================== -->

            <form method="POST" action="settings.php" class="settings-form">

                <!-- ========================================
                     PROFILE SECTION
                ======================================== -->

                <section class="settings-section">

                    <div class="section-heading">

                        <div>

                            <span>
                                ACCOUNT
                            </span>

                            <h2>
                                Profile Information
                            </h2>

                            <p class="section-description">
                                Your account details and personal information.
                            </p>

                        </div>

                    </div>

                    <div class="settings-grid two-col">

                        <div class="settings-card">

                            <div class="settings-card-header">

                                <span class="card-icon">👤</span>

                                <div>

                                    <h3>Personal Info</h3>

                                    <p>
                                        Your basic account information
                                    </p>

                                </div>

                            </div>

                            <div class="settings-card-body">

                                <div class="info-display">

                                    <div class="info-item">

                                        <span class="info-label">
                                            Full Name
                                        </span>

                                        <strong>
                                            <?php echo htmlspecialchars($user_data['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?>
                                        </strong>

                                    </div>

                                    <div class="info-item">

                                        <span class="info-label">
                                            Email
                                        </span>

                                        <strong>
                                            <?php echo htmlspecialchars($user_data['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </strong>

                                    </div>

                                    <div class="info-item">

                                        <span class="info-label">
                                            User ID
                                        </span>

                                        <strong>
                                            #<?php echo $user_id; ?>
                                        </strong>

                                    </div>

                                </div>

                                <a href="edit_profile.php" class="settings-link">
                                    Edit Profile →
                                </a>

                            </div>

                        </div>

                        <div class="settings-card">

                            <div class="settings-card-header">

                                <span class="card-icon">🔒</span>

                                <div>

                                    <h3>Security</h3>

                                    <p>
                                        Manage your password and security
                                    </p>

                                </div>

                            </div>

                            <div class="settings-card-body">

                                <a href="change_password.php" class="settings-link">
                                    Change Password →
                                </a>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- ========================================
                     PREFERENCES SECTION
                ======================================== -->

                <section class="settings-section">

                    <div class="section-heading">

                        <div>

                            <span>
                                PREFERENCES
                            </span>

                            <h2>
                                App Preferences
                            </h2>

                            <p class="section-description">
                                Customize your NexArena experience.
                            </p>

                        </div>

                    </div>

                    <div class="settings-grid two-col">

                        <!-- Language -->
                        <div class="settings-card">

                            <div class="settings-card-header">

                                <span class="card-icon">🌍</span>

                                <div>

                                    <h3>Language</h3>

                                    <p>
                                        Choose your preferred language
                                    </p>

                                </div>

                            </div>

                            <div class="settings-card-body">

                                <div class="form-group">

                                    <label for="language">
                                        Select Language
                                    </label>

                                    <select
                                        id="language"
                                        name="language"
                                        class="settings-select"
                                    >

                                        <option value="en" <?php echo $settings['language'] === 'en' ? 'selected' : ''; ?>>
                                            English
                                        </option>

                                        <option value="es" <?php echo $settings['language'] === 'es' ? 'selected' : ''; ?>>
                                            Español
                                        </option>

                                        <option value="fr" <?php echo $settings['language'] === 'fr' ? 'selected' : ''; ?>>
                                            Français
                                        </option>

                                        <option value="de" <?php echo $settings['language'] === 'de' ? 'selected' : ''; ?>>
                                            Deutsch
                                        </option>

                                        <option value="hi" <?php echo $settings['language'] === 'hi' ? 'selected' : ''; ?>>
                                            हिन्दी
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <!-- Timezone -->
                        <div class="settings-card">

                            <div class="settings-card-header">

                                <span class="card-icon">🕐</span>

                                <div>

                                    <h3>Time & Date</h3>

                                    <p>
                                        Set your timezone and date format
                                    </p>

                                </div>

                            </div>

                            <div class="settings-card-body">

                                <div class="form-group">

                                    <label for="timezone">
                                        Timezone
                                    </label>

                                    <select
                                        id="timezone"
                                        name="timezone"
                                        class="settings-select"
                                    >

                                        <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>
                                            UTC
                                        </option>

                                        <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>
                                            Eastern Time (ET)
                                        </option>

                                        <option value="America/Chicago" <?php echo $settings['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>
                                            Central Time (CT)
                                        </option>

                                        <option value="America/Denver" <?php echo $settings['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>
                                            Mountain Time (MT)
                                        </option>

                                        <option value="America/Los_Angeles" <?php echo $settings['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>
                                            Pacific Time (PT)
                                        </option>

                                        <option value="Europe/London" <?php echo $settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>
                                            GMT (London)
                                        </option>

                                        <option value="Asia/Kolkata" <?php echo $settings['timezone'] === 'Asia/Kolkata' ? 'selected' : ''; ?>>
                                            IST (India)
                                        </option>

                                        <option value="Asia/Dubai" <?php echo $settings['timezone'] === 'Asia/Dubai' ? 'selected' : ''; ?>>
                                            GST (Dubai)
                                        </option>

                                        <option value="Australia/Sydney" <?php echo $settings['timezone'] === 'Australia/Sydney' ? 'selected' : ''; ?>>
                                            AEDT (Sydney)
                                        </option>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label for="date_format">
                                        Date Format
                                    </label>

                                    <select
                                        id="date_format"
                                        name="date_format"
                                        class="settings-select"
                                    >

                                        <option value="d M Y" <?php echo $settings['date_format'] === 'd M Y' ? 'selected' : ''; ?>>
                                            25 Aug 2026
                                        </option>

                                        <option value="M d, Y" <?php echo $settings['date_format'] === 'M d, Y' ? 'selected' : ''; ?>>
                                            Aug 25, 2026
                                        </option>

                                        <option value="Y-m-d" <?php echo $settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>
                                            2026-08-25
                                        </option>

                                        <option value="d/m/Y" <?php echo $settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>
                                            25/08/2026
                                        </option>

                                        <option value="m/d/Y" <?php echo $settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>
                                            08/25/2026
                                        </option>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label for="time_format">
                                        Time Format
                                    </label>

                                    <select
                                        id="time_format"
                                        name="time_format"
                                        class="settings-select"
                                    >

                                        <option value="12" <?php echo $settings['time_format'] === '12' ? 'selected' : ''; ?>>
                                            12-hour (2:30 PM)
                                        </option>

                                        <option value="24" <?php echo $settings['time_format'] === '24' ? 'selected' : ''; ?>>
                                            24-hour (14:30)
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- ========================================
                     NOTIFICATIONS SECTION
                ======================================== -->

                <section class="settings-section">

                    <div class="section-heading">

                        <div>

                            <span>
                                NOTIFICATIONS
                            </span>

                            <h2>
                                Notification Preferences
                            </h2>

                            <p class="section-description">
                                Choose how and when you want to be notified.
                            </p>

                        </div>

                    </div>

                    <div class="settings-grid one-col">

                        <div class="settings-card">

                            <div class="settings-card-header">

                                <span class="card-icon">🔔</span>

                                <div>

                                    <h3>Notification Settings</h3>

                                    <p>
                                        Control your notification preferences
                                    </p>

                                </div>

                            </div>

                            <div class="settings-card-body">

                                <div class="toggle-group">

                                    <div class="toggle-item">

                                        <div class="toggle-info">

                                            <strong>
                                                Enable Notifications
                                            </strong>

                                            <p>
                                                Turn all notifications on or off
                                            </p>

                                        </div>

                                        <label class="toggle-switch">

                                            <input
                                                type="checkbox"
                                                name="notifications_enabled"
                                                <?php echo $settings['notifications_enabled'] ? 'checked' : ''; ?>
                                            >

                                            <span class="toggle-slider"></span>

                                        </label>

                                    </div>

                                    <div class="toggle-item">

                                        <div class="toggle-info">

                                            <strong>
                                                Email Notifications
                                            </strong>

                                            <p>
                                                Receive notifications via email
                                            </p>

                                        </div>

                                        <label class="toggle-switch">

                                            <input
                                                type="checkbox"
                                                name="email_notifications"
                                                <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>
                                            >

                                            <span class="toggle-slider"></span>

                                        </label>

                                    </div>

                                    <div class="toggle-item">

                                        <div class="toggle-info">

                                            <strong>
                                                Event Reminders
                                            </strong>

                                            <p>
                                                Get reminders for upcoming events
                                            </p>

                                        </div>

                                        <label class="toggle-switch">

                                            <input
                                                type="checkbox"
                                                name="event_reminders"
                                                <?php echo $settings['event_reminders'] ? 'checked' : ''; ?>
                                            >

                                            <span class="toggle-slider"></span>

                                        </label>

                                    </div>

                                    <div class="toggle-item">

                                        <div class="toggle-info">

                                            <strong>
                                                Team Updates
                                            </strong>

                                            <p>
                                                Updates about your teams
                                            </p>

                                        </div>

                                        <label class="toggle-switch">

                                            <input
                                                type="checkbox"
                                                name="team_updates"
                                                <?php echo $settings['team_updates'] ? 'checked' : ''; ?>
                                            >

                                            <span class="toggle-slider"></span>

                                        </label>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- ========================================
     APPEARANCE SECTION
======================================== -->

<section class="settings-section">

    <div class="section-heading">

        <div>

            <span>
                APPEARANCE
            </span>

            <h2>
                Appearance
            </h2>

            <p class="section-description">
                Customize the look and feel of NexArena.
            </p>

        </div>

    </div>

    <div class="settings-grid one-col">

        <div class="settings-card">

            <div class="settings-card-header">

                <span class="card-icon">🎨</span>

                <div>

                    <h3>Theme Settings</h3>

                    <p>
                        Choose your preferred theme
                    </p>

                </div>

            </div>

            <div class="settings-card-body">

                <div class="toggle-item">

                    <div class="toggle-info">

                        <strong>
                            Dark Mode
                        </strong>

                        <p>
                            Switch between light and dark theme
                        </p>

                    </div>

                    <label class="toggle-switch">

                        <input 
                            type="checkbox" 
                            name="dark_mode" 
                            id="dark_mode"
                            data-theme-toggle
                            <?php echo $settings['dark_mode'] ? 'checked' : ''; ?>
                        >

                        <span class="toggle-slider"></span>

                    </label>

                </div>

                <div class="theme-preview" style="margin-top: 15px;">

                    <p style="font-size: 11px; color: #999999; margin-bottom: 8px;">
                        Current Theme:
                    </p>

                    <div class="preview-boxes">

                        <div class="preview-box light-preview">
                            ☀️ Light
                        </div>

                        <div class="preview-box dark-preview" data-theme-indicator>
                            <?php echo $settings['dark_mode'] ? '🌙 Dark' : '☀️ Light'; ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>
                <!-- ========================================
                     DANGER ZONE
                ======================================== -->

                <section class="settings-section danger-zone">

                    <div class="section-heading">

                        <div>

                            <span style="color: #dc2626;">
                                DANGER ZONE
                            </span>

                            <h2 style="color: #dc2626;">
                                Account Actions
                            </h2>

                            <p class="section-description" style="color: #dc2626;">
                                These actions are irreversible. Please proceed with caution.
                            </p>

                        </div>

                    </div>

                    <div class="settings-grid one-col">

                        <div class="settings-card danger-card">

                            <div class="settings-card-header">

                                <span class="card-icon" style="background: #fee2e2; border-color: #fecaca;">
                                    ⚠️
                                </span>

                                <div>

                                    <h3 style="color: #dc2626;">
                                        Delete Account
                                    </h3>

                                    <p>
                                        Permanently delete your account and all associated data
                                    </p>

                                </div>

                            </div>

                            <div class="settings-card-body">

                                <button
                                    type="button"
                                    class="danger-btn"
                                    onclick="confirmDelete()"
                                >
                                    Delete My Account
                                </button>

                                <p style="font-size: 11px; color: #999999; margin-top: 10px;">
                                    This action cannot be undone.
                                </p>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- ========================================
                     SAVE BUTTONS
                ======================================== -->

                <div class="settings-actions">

                    <button type="submit" class="save-settings-btn">
                        💾 Save Settings
                    </button>

                    <a href="dashboard.php" class="cancel-btn">
                        Cancel
                    </a>

                </div>

            </form>

        </main>

        <!-- ========================================
             FOOTER
        ======================================== -->

        <footer>

            <div class="footer-logo">

                <span>Nex</span>Arena

            </div>

            <p>
                © <?php echo date("Y"); ?> NexArena. All Rights Reserved.
            </p>

        </footer>

    </div>

    <!-- ========================================
         DELETE ACCOUNT MODAL
    ======================================== -->

    <div class="modal-overlay" id="deleteModal">

        <div class="modal-box">

            <div class="modal-header">

                <h2>⚠️ Delete Account</h2>

                <button class="modal-close" onclick="closeModal()">
                    ✕
                </button>

            </div>

            <div class="modal-body">

                <p style="color: #dc2626; font-weight: 700; margin-bottom: 15px;">
                    Are you sure you want to delete your account?
                </p>

                <p style="color: #666666; font-size: 14px; line-height: 1.7;">
                    This action is <strong>permanent</strong> and cannot be undone. 
                    All your data including profile information, registrations, 
                    team memberships, and notifications will be permanently removed.
                </p>

                <div style="background: #fef2f2; padding: 15px; border-radius: 10px; margin: 15px 0; border: 1px solid #fecaca;">
                    <p style="color: #dc2626; font-size: 13px; margin: 0;">
                        Please type <strong>"DELETE"</strong> to confirm.
                    </p>
                </div>

                <input
                    type="text"
                    id="confirmDeleteInput"
                    placeholder="Type DELETE to confirm"
                    class="modal-input"
                    style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px;"
                >

            </div>

            <div class="modal-footer">

                <button class="modal-cancel-btn" onclick="closeModal()">
                    Cancel
                </button>

                <button class="modal-delete-btn" onclick="deleteAccount()">
                    Delete Account
                </button>

            </div>

        </div>

    </div>

    <script>

        function confirmDelete() {
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('confirmDeleteInput').value = '';
            document.getElementById('confirmDeleteInput').focus();
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function deleteAccount() {
            const input = document.getElementById('confirmDeleteInput');
            if (input.value === 'DELETE') {
                if (confirm('Are you absolutely sure you want to delete your account?')) {
                    window.location.href = 'delete_account.php?confirm=yes';
                }
            } else {
                alert('Please type "DELETE" to confirm account deletion.');
                input.focus();
            }
        }

        // Close modal on click outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Enter key to submit delete
        document.getElementById('confirmDeleteInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                deleteAccount();
            }
        });

    </script>
<!-- Theme JavaScript -->
<script src="assets/theme.js"></script>1
</body>

</html>