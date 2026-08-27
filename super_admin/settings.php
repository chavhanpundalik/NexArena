<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// Get admin user data
$adminId = $_SESSION['user_id'];

// Handle form submissions
$message = '';
$messageType = '';

// --- Create settings table if not exists ---
$createTableSQL = "
CREATE TABLE IF NOT EXISTS advanced_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    setting_type VARCHAR(20) DEFAULT 'text',
    is_encrypted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_group (setting_group)
)";

$conn->query($createTableSQL);

// --- Insert default settings if empty ---
$checkSettings = $conn->query("SELECT COUNT(*) as count FROM advanced_settings");
if ($checkSettings) {
    $count = $checkSettings->fetch_assoc()['count'];
    if ($count == 0) {
        $defaultSettings = [
            // Security Settings
            ['security', 'two_factor_auth', '0', 'toggle'],
            ['security', 'session_timeout', '3600', 'number'],
            ['security', 'max_login_attempts', '5', 'number'],
            ['security', 'password_expiry_days', '90', 'number'],
            ['security', 'force_strong_password', '1', 'toggle'],
            
            // Email Settings
            ['email', 'smtp_host', 'smtp.gmail.com', 'text'],
            ['email', 'smtp_port', '587', 'number'],
            ['email', 'smtp_encryption', 'tls', 'select'],
            ['email', 'smtp_username', '', 'text'],
            ['email', 'smtp_password', '', 'password'],
            ['email', 'from_email', 'admin@nexarena.com', 'email'],
            ['email', 'from_name', 'NexArena', 'text'],
            
            // Registration Settings
            ['registration', 'allow_registration', '1', 'toggle'],
            ['registration', 'email_verification_required', '1', 'toggle'],
            ['registration', 'admin_approval_required', '0', 'toggle'],
            ['registration', 'default_role', 'user', 'select'],
            
            // Team Settings
            ['teams', 'allow_team_creation', '1', 'toggle'],
            ['teams', 'max_teams_per_user', '3', 'number'],
            ['teams', 'max_players_per_team', '11', 'number'],
            ['teams', 'team_name_min_length', '3', 'number'],
            ['teams', 'team_name_max_length', '50', 'number'],
            
            // Event Settings
            ['events', 'allow_event_creation', '1', 'toggle'],
            ['events', 'max_events_per_day', '10', 'number'],
            ['events', 'event_registration_deadline', '24', 'number'],
            ['events', 'max_participants_per_event', '100', 'number'],
            
            // Payment Settings
            ['payment', 'payment_gateway', 'stripe', 'select'],
            ['payment', 'stripe_publishable_key', '', 'text'],
            ['payment', 'stripe_secret_key', '', 'password'],
            ['payment', 'paypal_client_id', '', 'text'],
            ['payment', 'paypal_secret', '', 'password'],
            ['payment', 'currency', 'USD', 'select'],
            ['payment', 'tax_rate', '0', 'number'],
            
            // Notification Settings
            ['notifications', 'email_notifications', '1', 'toggle'],
            ['notifications', 'push_notifications', '1', 'toggle'],
            ['notifications', 'sms_notifications', '0', 'toggle'],
            ['notifications', 'notification_email', '', 'email'],
            ['notifications', 'notification_phone', '', 'text'],
            
            // API Settings
            ['api', 'api_enabled', '0', 'toggle'],
            ['api', 'api_rate_limit', '1000', 'number'],
            ['api', 'api_timeout', '30', 'number'],
            
            // Cache Settings
            ['cache', 'cache_enabled', '1', 'toggle'],
            ['cache', 'cache_duration', '3600', 'number'],
            ['cache', 'cache_driver', 'file', 'select'],
            
            // Logging Settings
            ['logging', 'enable_logging', '1', 'toggle'],
            ['logging', 'log_level', 'info', 'select'],
            ['logging', 'log_retention_days', '30', 'number'],
            
            // Backup Settings
            ['backup', 'auto_backup', '1', 'toggle'],
            ['backup', 'backup_frequency', 'daily', 'select'],
            ['backup', 'backup_retention', '7', 'number'],
            
            // Social Media
            ['social', 'facebook_url', '', 'url'],
            ['social', 'twitter_url', '', 'url'],
            ['social', 'instagram_url', '', 'url'],
            ['social', 'youtube_url', '', 'url'],
            
            // SEO Settings
            ['seo', 'meta_title', 'NexArena - Sports Event Management', 'text'],
            ['seo', 'meta_description', 'Manage sports events, teams, and tournaments efficiently', 'textarea'],
            ['seo', 'meta_keywords', 'sports, events, tournaments, teams', 'text'],
            ['seo', 'google_analytics_id', '', 'text'],
            
            // Performance Settings
            ['performance', 'compression_enabled', '1', 'toggle'],
            ['performance', 'image_optimization', '1', 'toggle'],
            ['performance', 'lazy_loading', '1', 'toggle'],
            ['performance', 'cdn_enabled', '0', 'toggle'],
            ['performance', 'cdn_url', '', 'url'],
            
            // Maintenance Settings
            ['maintenance', 'maintenance_mode', '0', 'toggle'],
            ['maintenance', 'maintenance_message', 'Site is under maintenance. Please check back later.', 'textarea'],
            ['maintenance', 'allowed_ips', '', 'text']
        ];
        
        foreach ($defaultSettings as $setting) {
            $stmt = $conn->prepare("INSERT INTO advanced_settings (setting_group, setting_key, setting_value, setting_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $setting[0], $setting[1], $setting[2], $setting[3]);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// --- Get all settings ---
$settings = [];
$result = $conn->query("SELECT * FROM advanced_settings ORDER BY setting_group, setting_key");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_group']][$row['setting_key']] = [
            'value' => $row['setting_value'],
            'type' => $row['setting_type'],
            'setting_id' => $row['setting_id']
        ];
    }
}

// --- Save settings ---
if (isset($_POST['save_settings'])) {
    $setting_updates = $_POST['settings'] ?? [];
    $encrypted_fields = ['smtp_password', 'stripe_secret_key', 'paypal_secret'];
    
    $success = true;
    foreach ($setting_updates as $key => $value) {
        // Handle toggles (checkbox sends 'on' or doesn't send)
        if (is_array($value)) {
            $value = isset($value['on']) ? '1' : '0';
        }
        
        // Sanitize and trim
        $value = trim($value);
        
        // Check if this setting exists
        $stmt = $conn->prepare("SELECT setting_id FROM advanced_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing
            $updateStmt = $conn->prepare("UPDATE advanced_settings SET setting_value = ? WHERE setting_key = ?");
            $updateStmt->bind_param("ss", $value, $key);
            if (!$updateStmt->execute()) {
                $success = false;
            }
            $updateStmt->close();
        } else {
            // Insert new
            $insertStmt = $conn->prepare("INSERT INTO advanced_settings (setting_key, setting_value, setting_group, setting_type) VALUES (?, ?, 'general', 'text')");
            $insertStmt->bind_param("ss", $key, $value);
            if (!$insertStmt->execute()) {
                $success = false;
            }
            $insertStmt->close();
        }
        $stmt->close();
    }
    
    if ($success) {
        $message = "Advanced settings saved successfully!";
        $messageType = "success";
        // Refresh settings
        $result = $conn->query("SELECT * FROM advanced_settings ORDER BY setting_group, setting_key");
        if ($result) {
            $settings = [];
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_group']][$row['setting_key']] = [
                    'value' => $row['setting_value'],
                    'type' => $row['setting_type'],
                    'setting_id' => $row['setting_id']
                ];
            }
        }
    } else {
        $message = "Error saving settings. Please try again.";
        $messageType = "error";
    }
}

// --- Export settings ---
if (isset($_GET['export'])) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="settings_backup_' . date('Y-m-d') . '.json"');
    
    $exportData = [];
    foreach ($settings as $group => $groupSettings) {
        foreach ($groupSettings as $key => $data) {
            $exportData[$group][$key] = $data['value'];
        }
    }
    
    echo json_encode($exportData, JSON_PRETTY_PRINT);
    exit();
}

// --- Import settings ---
if (isset($_POST['import_settings']) && isset($_FILES['settings_file'])) {
    $file = $_FILES['settings_file'];
    if ($file['error'] == 0 && $file['type'] == 'application/json') {
        $json = file_get_contents($file['tmp_name']);
        $importData = json_decode($json, true);
        
        if ($importData) {
            $success = true;
            foreach ($importData as $group => $groupSettings) {
                foreach ($groupSettings as $key => $value) {
                    $stmt = $conn->prepare("UPDATE advanced_settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->bind_param("ss", $value, $key);
                    if (!$stmt->execute()) {
                        $success = false;
                    }
                    $stmt->close();
                }
            }
            
            if ($success) {
                $message = "Settings imported successfully!";
                $messageType = "success";
                // Refresh settings
                $result = $conn->query("SELECT * FROM advanced_settings ORDER BY setting_group, setting_key");
                if ($result) {
                    $settings = [];
                    while ($row = $result->fetch_assoc()) {
                        $settings[$row['setting_group']][$row['setting_key']] = [
                            'value' => $row['setting_value'],
                            'type' => $row['setting_type'],
                            'setting_id' => $row['setting_id']
                        ];
                    }
                }
            } else {
                $message = "Error importing settings.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid JSON file.";
            $messageType = "error";
        }
    } else {
        $message = "Please upload a valid JSON file.";
        $messageType = "error";
    }
}

// --- Reset settings to default ---
if (isset($_GET['reset']) && $_GET['reset'] === 'confirm') {
    $conn->query("TRUNCATE TABLE advanced_settings");
    header("Location: advanced_settings.php?reset=done");
    exit();
}

// --- Get system info ---
$systemInfo = [
    'PHP Version' => phpversion(),
    'MySQL Version' => $conn->server_info,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Server OS' => PHP_OS,
    'Memory Limit' => ini_get('memory_limit'),
    'Max Upload Size' => ini_get('upload_max_filesize'),
    'Max Execution Time' => ini_get('max_execution_time') . 's',
    'Timezone' => date_default_timezone_get(),
    'Current Date/Time' => date('Y-m-d H:i:s'),
    'Database Size' => getDatabaseSize($conn),
    'Total Users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0,
    'Total Teams' => $conn->query("SELECT COUNT(*) as count FROM teams")->fetch_assoc()['count'] ?? 0,
    'Total Events' => $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'] ?? 0
];

function getDatabaseSize($conn) {
    $size = 0;
    $result = $conn->query("SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = DATABASE()");
    if ($result) {
        $row = $result->fetch_assoc();
        $size = $row['size'] ?? 0;
    }
    return formatSize($size);
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

// --- Get logs ---
$logs = [];
if (isset($_GET['view_logs'])) {
    $logFile = '../logs/system.log';
    if (file_exists($logFile)) {
        $logs = array_reverse(file($logFile));
        $logs = array_slice($logs, 0, 100);
    }
}

// --- Database Backup ---
if (isset($_GET['backup_db'])) {
    backupDatabase($conn);
    exit();
}

function backupDatabase($conn) {
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    $output = "-- NexArena Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $createResult = $conn->query("SHOW CREATE TABLE `$table`");
        $createRow = $createResult->fetch_row();
        $output .= $createRow[1] . ";\n\n";
        
        $dataResult = $conn->query("SELECT * FROM `$table`");
        while ($row = $dataResult->fetch_assoc()) {
            $columns = array_keys($row);
            $values = array_map(function($value) use ($conn) {
                if ($value === null) return 'NULL';
                return "'" . $conn->real_escape_string($value) . "'";
            }, $row);
            $output .= "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
        $output .= "\n";
    }
    
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="nexarena_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    echo $output;
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Settings | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        /* ========================================
           COMPLETE ORANGE THEME - White Background, Black Text, Orange Accents
           ======================================== */

        /* ---- Root Variables ---- */
        :root {
            --orange: #f97316;
            --orange-dark: #ea580c;
            --orange-light: #fb923c;
            --orange-bg: #fff7ed;
            --orange-border: #fed7aa;
            --orange-shadow: rgba(249, 115, 22, 0.25);
            --white: #ffffff;
            --black: #000000;
            --gray-dark: #1e293b;
            --gray: #64748b;
            --gray-light: #94a3b8;
            --border: #e2e8f0;
            --border-dark: #cbd5e1;
        }

        /* ---- Global Reset ---- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, 
        .users-main, 
        .settings-container,
        .settings-card,
        .main-content,
        .page-content {
            background: var(--white) !important;
            color: var(--black) !important;
        }

        /* ---- Scrollbar ---- */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--orange);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--orange-dark);
        }

        /* ========================================
           SETTINGS CONTAINER
           ======================================== */
        .settings-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* ========================================
           HEADER
           ======================================== */
        .settings-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 15px;
        }
        .settings-header h1 {
            font-size: 28px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--black) !important;
        }
        .settings-header h1 i {
            color: var(--orange) !important;
        }
        .settings-header p {
            color: var(--gray) !important;
            margin-top: 5px;
        }

        /* ========================================
           TABS
           ======================================== */
        .settings-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 12px;
        }
        .settings-tab {
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: var(--gray) !important;
            transition: all 0.3s ease;
            border: none;
            background: transparent !important;
        }
        .settings-tab:hover {
            background: var(--orange-bg) !important;
            color: var(--orange) !important;
        }
        .settings-tab.active {
            background: var(--orange) !important;
            color: var(--white) !important;
        }
        .settings-tab i {
            margin-right: 6px;
        }

        /* ========================================
           TAB CONTENT
           ======================================== */
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        /* ========================================
           CARDS
           ======================================== */
        .settings-card {
            background: var(--white) !important;
            border-radius: 12px;
            border: 1px solid var(--border) !important;
            padding: 25px 30px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        .settings-card:hover {
            border-color: var(--orange-border) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06) !important;
        }
        .settings-card .card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--black) !important;
        }
        .settings-card .card-title i {
            color: var(--orange) !important;
        }
        .settings-card .card-subtitle {
            color: var(--gray) !important;
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* ========================================
           FORM ELEMENTS
           ======================================== */
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 13px;
            color: var(--black) !important;
        }
        .form-group label .required {
            color: var(--orange) !important;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-dark) !important;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: var(--white) !important;
            color: var(--black) !important;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--orange) !important;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12) !important;
        }
        .form-control::placeholder {
            color: var(--gray-light) !important;
        }
        textarea.form-control {
            min-height: 60px;
            resize: vertical;
        }
        select.form-control {
            appearance: auto;
            cursor: pointer;
        }
        select.form-control option {
            background: var(--white) !important;
            color: var(--black) !important;
        }

        /* ========================================
           FORM ROW
           ======================================== */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* ========================================
           CHECKBOXES
           ======================================== */
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 0;
        }
        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--orange) !important;
        }
        .form-check input[type="checkbox"]:checked {
            accent-color: var(--orange) !important;
        }
        .form-check label {
            font-weight: 400;
            cursor: pointer;
            margin: 0;
            color: var(--black) !important;
        }

        /* ========================================
           TOGGLE SWITCH - ORANGE
           ======================================== */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #cbd5e1;
            transition: all 0.4s ease;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            transition: all 0.4s ease;
            border-radius: 50%;
        }
        .toggle-switch input:checked + .toggle-slider {
            background: var(--orange) !important;
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        .toggle-label {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toggle-label span {
            color: var(--black) !important;
        }

        /* ========================================
           BUTTONS - ORANGE
           ======================================== */
        .btn {
            padding: 9px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn:hover {
            transform: translateY(-2px);
        }

        /* Primary Orange Button */
        .btn-primary {
            background: var(--orange) !important;
            color: var(--white) !important;
            border: none !important;
        }
        .btn-primary:hover {
            background: var(--orange-dark) !important;
            color: var(--white) !important;
            box-shadow: 0 4px 12px var(--orange-shadow) !important;
            transform: translateY(-2px);
        }

        /* Success Button */
        .btn-success {
            background: #22c55e !important;
            color: var(--white) !important;
            border: none !important;
        }
        .btn-success:hover {
            background: #16a34a !important;
            color: var(--white) !important;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3) !important;
            transform: translateY(-2px);
        }

        /* Danger Button */
        .btn-danger {
            background: #ef4444 !important;
            color: var(--white) !important;
            border: none !important;
        }
        .btn-danger:hover {
            background: #dc2626 !important;
            color: var(--white) !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
            transform: translateY(-2px);
        }

        /* Warning Button */
        .btn-warning {
            background: #f59e0b !important;
            color: var(--white) !important;
            border: none !important;
        }
        .btn-warning:hover {
            background: #d97706 !important;
            color: var(--white) !important;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3) !important;
            transform: translateY(-2px);
        }

        /* Secondary Button */
        .btn-secondary {
            background: #f1f5f9 !important;
            color: var(--gray-dark) !important;
            border: 1px solid var(--border) !important;
        }
        .btn-secondary:hover {
            background: #e2e8f0 !important;
            color: var(--black) !important;
            transform: translateY(-2px);
        }

        /* ========================================
           ALERTS
           ======================================== */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #f0fdf4 !important;
            border: 1px solid #86efac !important;
            color: #16a34a !important;
        }
        .alert-error {
            background: #fef2f2 !important;
            border: 1px solid #fca5a5 !important;
            color: #dc2626 !important;
        }
        .alert-info {
            background: #eff6ff !important;
            border: 1px solid #93c5fd !important;
            color: #2563eb !important;
        }

        /* ========================================
           SYSTEM INFO
           ======================================== */
        .system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .system-info-item {
            padding: 12px 16px;
            background: #f8fafc !important;
            border-radius: 8px;
            border: 1px solid var(--border) !important;
        }
        .system-info-item .label {
            font-size: 11px;
            color: var(--gray) !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .system-info-item .value {
            font-size: 15px;
            font-weight: 600;
            color: var(--black) !important;
            margin-top: 2px;
            word-break: break-all;
        }

        /* ========================================
           DANGER ZONE
           ======================================== */
        .danger-zone {
            border: 2px solid #fecaca !important;
            background: #fef2f2 !important;
        }
        .danger-zone .card-title {
            color: #dc2626 !important;
        }
        .danger-zone .card-title i {
            color: #dc2626 !important;
        }

        /* ========================================
           ACTION BUTTONS
           ======================================== */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ========================================
           IMPORT/EXPORT
           ======================================== */
        .import-export {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            padding: 15px;
            background: #f8fafc !important;
            border-radius: 10px;
            border: 1px dashed var(--border-dark) !important;
        }

        /* ========================================
           STICKY SAVE BUTTON
           ======================================== */
        .sticky-save {
            position: sticky;
            bottom: 20px;
            background: var(--white) !important;
            padding: 15px 30px;
            border-radius: 12px;
            border: 1px solid var(--border) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
            text-align: right;
            z-index: 100;
        }

        /* ========================================
           LINKS
           ======================================== */
        a:not(.btn) {
            color: var(--orange) !important;
            text-decoration: none !important;
            transition: color 0.3s ease;
        }
        a:not(.btn):hover {
            color: var(--orange-dark) !important;
            text-decoration: underline !important;
        }

        /* ========================================
           RESPONSIVE
           ======================================== */
        @media (max-width: 768px) {
            .settings-container {
                padding: 0 15px;
            }
            .settings-card {
                padding: 18px 15px !important;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .settings-header {
                flex-direction: column;
            }
            .settings-tabs {
                gap: 4px;
            }
            .settings-tab {
                padding: 6px 14px;
                font-size: 12px;
            }
            .system-info-grid {
                grid-template-columns: 1fr 1fr;
            }
            .sticky-save {
                padding: 12px 16px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .system-info-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
            }
            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }
            .import-export {
                flex-direction: column;
                align-items: stretch;
            }
            .import-export .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <div class="settings-container">
        
        <!-- Settings Header -->
        <div class="settings-header">
            <div>
                <h1><i class="fa-solid fa-sliders"></i> Advanced Settings</h1>
                <p>Configure your NexArena platform with advanced system settings</p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button onclick="window.location.href='<?= $_SERVER['PHP_SELF']; ?>?backup_db=1'" class="btn btn-secondary">
                    <i class="fa-solid fa-database"></i> Backup DB
                </button>
                <button onclick="window.location.href='<?= $_SERVER['PHP_SELF']; ?>?export=1'" class="btn btn-secondary">
                    <i class="fa-solid fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType; ?>">
                <i class="fa-solid fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?= $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['reset']) && $_GET['reset'] == 'done'): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> Settings have been reset to default values.
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="settings-tabs">
            <button class="settings-tab active" data-tab="general"><i class="fa-solid fa-gear"></i> General</button>
            <button class="settings-tab" data-tab="security"><i class="fa-solid fa-shield"></i> Security</button>
            <button class="settings-tab" data-tab="email"><i class="fa-solid fa-envelope"></i> Email</button>
            <button class="settings-tab" data-tab="teams"><i class="fa-solid fa-people-group"></i> Teams</button>
            <button class="settings-tab" data-tab="events"><i class="fa-solid fa-calendar"></i> Events</button>
            <button class="settings-tab" data-tab="payment"><i class="fa-solid fa-credit-card"></i> Payment</button>
            <button class="settings-tab" data-tab="api"><i class="fa-solid fa-code"></i> API</button>
            <button class="settings-tab" data-tab="system"><i class="fa-solid fa-server"></i> System</button>
            <button class="settings-tab" data-tab="danger"><i class="fa-solid fa-triangle-exclamation"></i> Danger</button>
        </div>
        
        <form method="POST">
            <!-- General Tab -->
            <div id="tab-general" class="tab-content active">
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-globe"></i> General Settings</div>
                    <div class="card-subtitle">Basic platform configuration</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Site Name <span class="required">*</span></label>
                            <input type="text" name="settings[site_name]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['general']['site_name']['value'] ?? 'NexArena'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Default Timezone</label>
                            <select name="settings[timezone]" class="form-control">
                                <option value="UTC" <?= ($settings['general']['timezone']['value'] ?? '') == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="America/New_York" <?= ($settings['general']['timezone']['value'] ?? '') == 'America/New_York' ? 'selected' : ''; ?>>America/New York</option>
                                <option value="America/Los_Angeles" <?= ($settings['general']['timezone']['value'] ?? '') == 'America/Los_Angeles' ? 'selected' : ''; ?>>America/Los Angeles</option>
                                <option value="Europe/London" <?= ($settings['general']['timezone']['value'] ?? '') == 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                                <option value="Asia/Kolkata" <?= ($settings['general']['timezone']['value'] ?? '') == 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata</option>
                                <option value="Asia/Dubai" <?= ($settings['general']['timezone']['value'] ?? '') == 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Site Description</label>
                        <textarea name="settings[site_description]" class="form-control" rows="3"><?= htmlspecialchars($settings['general']['site_description']['value'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="toggle-label">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[maintenance_mode]" 
                                           <?= ($settings['maintenance']['maintenance_mode']['value'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>Maintenance Mode</span>
                            </div>
                            <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Only admins can access the site</small>
                        </div>
                        <div class="form-group">
                            <div class="toggle-label">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[registration_enabled]" 
                                           <?= ($settings['registration']['allow_registration']['value'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>Allow Registration</span>
                            </div>
                            <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Allow new users to register</small>
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-paint-brush"></i> Appearance</div>
                    <div class="card-subtitle">Customize the platform look and feel</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Primary Color</label>
                            <input type="color" name="settings[primary_color]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['appearance']['primary_color']['value'] ?? '#f97316'); ?>" style="height:50px;padding:4px;cursor:pointer;">
                        </div>
                        <div class="form-group">
                            <label>Logo URL</label>
                            <input type="url" name="settings[logo_url]" class="form-control" 
                                   placeholder="https://example.com/logo.png"
                                   value="<?= htmlspecialchars($settings['appearance']['logo_url']['value'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Favicon URL</label>
                        <input type="url" name="settings[favicon_url]" class="form-control" 
                               placeholder="https://example.com/favicon.ico"
                               value="<?= htmlspecialchars($settings['appearance']['favicon_url']['value'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Security Tab -->
            <div id="tab-security" class="tab-content">
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-shield-halved"></i> Security Settings</div>
                    <div class="card-subtitle">Protect your platform from unauthorized access</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="toggle-label">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[two_factor_auth]" 
                                           <?= ($settings['security']['two_factor_auth']['value'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>Two-Factor Authentication</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="toggle-label">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[force_strong_password]" 
                                           <?= ($settings['security']['force_strong_password']['value'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>Force Strong Passwords</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Session Timeout (seconds)</label>
                            <input type="number" name="settings[session_timeout]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['security']['session_timeout']['value'] ?? 3600); ?>">
                            <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Default: 3600 seconds (1 hour)</small>
                        </div>
                        <div class="form-group">
                            <label>Max Login Attempts</label>
                            <input type="number" name="settings[max_login_attempts]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['security']['max_login_attempts']['value'] ?? 5); ?>">
                            <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Default: 5 attempts</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password Expiry (days)</label>
                        <input type="number" name="settings[password_expiry_days]" class="form-control" 
                               value="<?= htmlspecialchars($settings['security']['password_expiry_days']['value'] ?? 90); ?>">
                        <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Set to 0 for no expiry</small>
                    </div>
                </div>
            </div>
            
            <!-- Email Tab -->
            <div id="tab-email" class="tab-content">
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-envelope"></i> Email Settings</div>
                    <div class="card-subtitle">Configure email notifications and SMTP</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" name="settings[smtp_host]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['email']['smtp_host']['value'] ?? 'smtp.gmail.com'); ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" name="settings[smtp_port]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['email']['smtp_port']['value'] ?? 587); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Encryption</label>
                            <select name="settings[smtp_encryption]" class="form-control">
                                <option value="none" <?= ($settings['email']['smtp_encryption']['value'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                                <option value="tls" <?= ($settings['email']['smtp_encryption']['value'] ?? '') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?= ($settings['email']['smtp_encryption']['value'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" name="settings[smtp_username]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['email']['smtp_username']['value'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" name="settings[smtp_password]" class="form-control" 
                                   placeholder="Enter SMTP password"
                                   value="<?= htmlspecialchars($settings['email']['smtp_password']['value'] ?? ''); ?>">
                            <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Leave blank to keep current password</small>
                        </div>
                        <div class="form-group">
                            <label>From Email</label>
                            <input type="email" name="settings[from_email]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['email']['from_email']['value'] ?? 'admin@nexarena.com'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>From Name</label>
                        <input type="text" name="settings[from_name]" class="form-control" 
                               value="<?= htmlspecialchars($settings['email']['from_name']['value'] ?? 'NexArena'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <div class="toggle-label">
                            <label class="toggle-switch">
                                <input type="checkbox" name="settings[email_notifications]" 
                                       <?= ($settings['notifications']['email_notifications']['value'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <span>Enable Email Notifications</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Teams Tab -->
            <div id="tab-teams" class="tab-content">
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-people-group"></i> Team Settings</div>
                    <div class="card-subtitle">Configure team creation and management</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="toggle-label">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[allow_team_creation]" 
                                           <?= ($settings['teams']['allow_team_creation']['value'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>Allow Team Creation</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Max Teams Per User</label>
                            <input type="number" name="settings[max_teams_per_user]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['teams']['max_teams_per_user']['value'] ?? 3); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Max Players Per Team</label>
                            <input type="number" name="settings[max_players_per_team]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['teams']['max_players_per_team']['value'] ?? 11); ?>">
                        </div>
                        <div class="form-group">
                            <label>Team Name Min Length</label>
                            <input type="number" name="settings[team_name_min_length]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['teams']['team_name_min_length']['value'] ?? 3); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Team Name Max Length</label>
                        <input type="number" name="settings[team_name_max_length]" class="form-control" 
                               value="<?= htmlspecialchars($settings['teams']['team_name_max_length']['value'] ?? 50); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Events Tab -->
            <div id="tab-events" class="tab-content">
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-calendar"></i> Event Settings</div>
                    <div class="card-subtitle">Configure event management</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="toggle-label">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[allow_event_creation]" 
                                           <?= ($settings['events']['allow_event_creation']['value'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>Allow Event Creation</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Max Events Per Day</label>
                            <input type="number" name="settings[max_events_per_day]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['events']['max_events_per_day']['value'] ?? 10); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Registration Deadline (hours before)</label>
                            <input type="number" name="settings[event_registration_deadline]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['events']['event_registration_deadline']['value'] ?? 24); ?>">
                            <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Hours before event start</small>
                        </div>
                        <div class="form-group">
                            <label>Max Participants Per Event</label>
                            <input type="number" name="settings[max_participants_per_event]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['events']['max_participants_per_event']['value'] ?? 100); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Tab -->
            <div id="tab-payment" class="tab-content">
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-credit-card"></i> Payment Settings</div>
                    <div class="card-subtitle">Configure payment gateways and billing</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Payment Gateway</label>
                            <select name="settings[payment_gateway]" class="form-control">
                                <option value="stripe" <?= ($settings['payment']['payment_gateway']['value'] ?? '') == 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                                <option value="paypal" <?= ($settings['payment']['payment_gateway']['value'] ?? '') == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                <option value="razorpay" <?= ($settings['payment']['payment_gateway']['value'] ?? '') == 'razorpay' ? 'selected' : ''; ?>>Razorpay</option>
                                <option value="manual" <?= ($settings['payment']['payment_gateway']['value'] ?? '') == 'manual' ? 'selected' : ''; ?>>Manual (Offline)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Currency</label>
                            <select name="settings[currency]" class="form-control">
                                <option value="USD" <?= ($settings['payment']['currency']['value'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="EUR" <?= ($settings['payment']['currency']['value'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                <option value="GBP" <?= ($settings['payment']['currency']['value'] ?? '') == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                <option value="INR" <?= ($settings['payment']['currency']['value'] ?? '') == 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Stripe Publishable Key</label>
                            <input type="text" name="settings[stripe_publishable_key]" class="form-control" 
                                   placeholder="pk_live_..."
                                   value="<?= htmlspecialchars($settings['payment']['stripe_publishable_key']['value'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Stripe Secret Key</label>
                            <input type="password" name="settings[stripe_secret_key]" class="form-control" 
                                   placeholder="sk_live_..."
                                   value="<?= htmlspecialchars($settings['payment']['stripe_secret_key']['value'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>PayPal Client ID</label>
                            <input type="text" name="settings[paypal_client_id]" class="form-control" 
                                   placeholder="Enter PayPal Client ID"
                                   value="<?= htmlspecialchars($settings['payment']['paypal_client_id']['value'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>PayPal Secret</label>
                            <input type="password" name="settings[paypal_secret]" class="form-control" 
                                   placeholder="Enter PayPal secret"
                                   value="<?= htmlspecialchars($settings['payment']['paypal_secret']['value'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tax Rate (%)</label>
                            <input type="number" name="settings[tax_rate]" class="form-control" step="0.01"
                                   value="<?= htmlspecialchars($settings['payment']['tax_rate']['value'] ?? 0); ?>">
                            <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Default: 0% (no tax)</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- API Tab -->
            <div id="tab-api" class="tab-content">
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-code"></i> API Settings</div>
                    <div class="card-subtitle">Configure API access and rate limiting</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="toggle-label">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[api_enabled]" 
                                           <?= ($settings['api']['api_enabled']['value'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>Enable API</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Rate Limit (requests per hour)</label>
                            <input type="number" name="settings[api_rate_limit]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['api']['api_rate_limit']['value'] ?? 1000); ?>">
                            <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Default: 1000 requests/hour</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>API Timeout (seconds)</label>
                        <input type="number" name="settings[api_timeout]" class="form-control" 
                               value="<?= htmlspecialchars($settings['api']['api_timeout']['value'] ?? 30); ?>">
                        <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Default: 30 seconds</small>
                    </div>
                </div>
            </div>
            
            <!-- System Tab -->
            <div id="tab-system" class="tab-content">
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-server"></i> System Information</div>
                    <div class="card-subtitle">Platform technical details</div>
                    
                    <div class="system-info-grid">
                        <?php foreach ($systemInfo as $label => $value): ?>
                            <div class="system-info-item">
                                <div class="label"><?= $label; ?></div>
                                <div class="value"><?= htmlspecialchars($value); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-database"></i> Cache Settings</div>
                    <div class="card-subtitle">Configure caching for better performance</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="toggle-label">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[cache_enabled]" 
                                           <?= ($settings['cache']['cache_enabled']['value'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>Enable Cache</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Cache Duration (seconds)</label>
                            <input type="number" name="settings[cache_duration]" class="form-control" 
                                   value="<?= htmlspecialchars($settings['cache']['cache_duration']['value'] ?? 3600); ?>">
                            <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Default: 3600 seconds (1 hour)</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Cache Driver</label>
                        <select name="settings[cache_driver]" class="form-control">
                            <option value="file" <?= ($settings['cache']['cache_driver']['value'] ?? '') == 'file' ? 'selected' : ''; ?>>File</option>
                            <option value="database" <?= ($settings['cache']['cache_driver']['value'] ?? '') == 'database' ? 'selected' : ''; ?>>Database</option>
                            <option value="redis" <?= ($settings['cache']['cache_driver']['value'] ?? '') == 'redis' ? 'selected' : ''; ?>>Redis</option>
                            <option value="memcached" <?= ($settings['cache']['cache_driver']['value'] ?? '') == 'memcached' ? 'selected' : ''; ?>>Memcached</option>
                        </select>
                    </div>
                </div>
                
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Backup Settings</div>
                    <div class="card-subtitle">Configure automatic backups</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="toggle-label">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="settings[auto_backup]" 
                                           <?= ($settings['backup']['auto_backup']['value'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>Enable Auto Backup</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Backup Frequency</label>
                            <select name="settings[backup_frequency]" class="form-control">
                                <option value="hourly" <?= ($settings['backup']['backup_frequency']['value'] ?? '') == 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                                <option value="daily" <?= ($settings['backup']['backup_frequency']['value'] ?? '') == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?= ($settings['backup']['backup_frequency']['value'] ?? '') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?= ($settings['backup']['backup_frequency']['value'] ?? '') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Backup Retention (days)</label>
                        <input type="number" name="settings[backup_retention]" class="form-control" 
                               value="<?= htmlspecialchars($settings['backup']['backup_retention']['value'] ?? 7); ?>">
                        <small style="color:var(--gray);font-size:12px;display:block;margin-top:4px;">Number of days to keep backups</small>
                    </div>
                </div>
            </div>
            
            <!-- Danger Tab -->
            <div id="tab-danger" class="tab-content">
                <div class="settings-card danger-zone">
                    <div class="card-title"><i class="fa-solid fa-triangle-exclamation"></i> Danger Zone</div>
                    <div class="card-subtitle">Irreversible actions - proceed with caution</div>
                    
                    <div class="action-buttons" style="margin-bottom:15px;">
                        <button type="button" onclick="clearCache()" class="btn btn-secondary">
                            <i class="fa-solid fa-rotate"></i> Clear Cache
                        </button>
                        <button type="button" onclick="confirmReset()" class="btn btn-danger">
                            <i class="fa-solid fa-arrow-rotate-left"></i> Reset All Settings
                        </button>
                        <button type="button" onclick="confirmLogoutAll()" class="btn btn-danger">
                            <i class="fa-solid fa-sign-out-alt"></i> Logout All Users
                        </button>
                        <button type="button" onclick="window.location.href='<?= $_SERVER['PHP_SELF']; ?>?backup_db=1'" class="btn btn-success">
                            <i class="fa-solid fa-database"></i> Backup Database
                        </button>
                    </div>
                    
                    <hr style="border-color:#fecaca;margin:15px 0;">
                    
                    <div style="margin-top:15px;">
                        <button type="button" onclick="confirmDeleteAccount()" class="btn btn-danger">
                            <i class="fa-solid fa-trash"></i> Delete Admin Account
                        </button>
                        <small style="color:var(--gray);font-size:12px;display:block;margin-top:8px;">
                            This will permanently delete your admin account and all associated data
                        </small>
                    </div>
                </div>
                
                <!-- Import/Export -->
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-file-import"></i> Import/Export Settings</div>
                    <div class="card-subtitle">Backup or restore your settings</div>
                    
                    <div class="import-export">
                        <div>
                            <a href="<?= $_SERVER['PHP_SELF']; ?>?export=1" class="btn btn-primary">
                                <i class="fa-solid fa-download"></i> Export Settings
                            </a>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                            <form method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <input type="file" name="settings_file" accept=".json" style="display:none;" id="settingsFile">
                                <button type="button" onclick="document.getElementById('settingsFile').click()" class="btn btn-secondary">
                                    <i class="fa-solid fa-folder-open"></i> Choose File
                                </button>
                                <button type="submit" name="import_settings" class="btn btn-success">
                                    <i class="fa-solid fa-upload"></i> Import
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sticky Save Button -->
            <div class="sticky-save">
                <button type="submit" name="save_settings" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>
</main>

<script>
// Tab switching
document.querySelectorAll('.settings-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        // Remove active from all tabs
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        // Add active to clicked tab
        this.classList.add('active');
        
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        // Show the corresponding tab content
        const tabId = this.dataset.tab;
        document.getElementById('tab-' + tabId).classList.add('active');
    });
});

function clearCache() {
    if (confirm('Are you sure you want to clear the system cache?')) {
        fetch('ajax/clear_cache.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Cache cleared successfully!');
                location.reload();
            })
            .catch(error => {
                alert('Error clearing cache: ' + error);
            });
    }
}

function confirmReset() {
    if (confirm('WARNING: This will reset ALL settings to default values. Continue?')) {
        if (confirm('Are you absolutely sure? This action cannot be undone!')) {
            window.location.href = '<?= $_SERVER['PHP_SELF']; ?>?reset=confirm';
        }
    }
}

function confirmLogoutAll() {
    if (confirm('This will logout all users from the system. Continue?')) {
        fetch('ajax/logout_all.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'All users logged out successfully!');
            })
            .catch(error => {
                alert('Error: ' + error);
            });
    }
}

function confirmDeleteAccount() {
    if (confirm('WARNING: This will permanently delete your admin account! Continue?')) {
        if (confirm('Are you absolutely sure? All your data will be lost!')) {
            if (prompt('Type "DELETE" to confirm') === 'DELETE') {
                window.location.href = 'ajax/delete_account.php?confirm=yes';
            }
        }
    }
}

// Auto-dismiss alerts after 5 seconds
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            alert.style.display = 'none';
        }, 500);
    }, 5000);
});
</script>
</body>
</html>