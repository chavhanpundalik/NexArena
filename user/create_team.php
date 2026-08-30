<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

require_once "../db_connect.php";

$user_id = (int) $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';
$user_email = $_SESSION['email'] ?? '';

// Get dark mode setting
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

function clean($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Get all sports for dropdown
$sports = [];
$sport_result = $conn->query("SELECT sport_id, sport_name FROM sports ORDER BY sport_name");
if ($sport_result && $sport_result->num_rows > 0) {
    while ($row = $sport_result->fetch_assoc()) {
        $sports[] = $row;
    }
} else {
    // Default sports if table doesn't exist
    $sports = [
        ['sport_id' => 1, 'sport_name' => 'Football'],
        ['sport_id' => 2, 'sport_name' => 'Basketball'],
        ['sport_id' => 3, 'sport_name' => 'Cricket'],
        ['sport_id' => 4, 'sport_name' => 'Volleyball'],
        ['sport_id' => 5, 'sport_name' => 'Tennis'],
        ['sport_id' => 6, 'sport_name' => 'Badminton'],
        ['sport_id' => 7, 'sport_name' => 'Hockey'],
        ['sport_id' => 8, 'sport_name' => 'Rugby']
    ];
}

// Get all events for dropdown
$events = [];
$event_result = $conn->query("SELECT event_id, event_name, event_date FROM events WHERE status = 'active' ORDER BY event_date ASC");
if ($event_result && $event_result->num_rows > 0) {
    while ($row = $event_result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Get registered users for the selected event (AJAX will use this)
$available_users = [];

// Handle form submission
$message = '';
$messageType = '';
$form_data = [
    'team_name' => '',
    'sport_id' => '',
    'event_id' => '',
    'game' => '',
    'region' => '',
    'description' => '',
    'max_players' => 11,
    'is_private' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name'] ?? '');
    $sport_id = !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : 0;
    $event_id = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    $game = trim($_POST['game'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $max_players = (int)($_POST['max_players'] ?? 11);
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    $members = isset($_POST['members']) ? $_POST['members'] : '';
    
    $form_data = compact('team_name', 'sport_id', 'event_id', 'game', 'region', 'description', 'max_players', 'is_private');
    
    // Validation
    $errors = [];
    if (empty($team_name)) {
        $errors[] = "Team name is required";
    } elseif (strlen($team_name) < 3) {
        $errors[] = "Team name must be at least 3 characters";
    }
    if (empty($sport_id) || $sport_id <= 0) {
        $errors[] = "Please select a sport";
    }
    if (empty($event_id) || $event_id <= 0) {
        $errors[] = "Please select an event";
    }
    
    if (empty($errors)) {
        // Insert team
        $insert_sql = $conn->prepare("
            INSERT INTO teams (team_name, sport_id, event_id, game, region, description, max_players, is_private, captain_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        $insert_sql->bind_param("siisssiii", $team_name, $sport_id, $event_id, $game, $region, $description, $max_players, $is_private, $user_id);
        
        if ($insert_sql->execute()) {
            $team_id = $insert_sql->insert_id;
            $insert_sql->close();
            
            // Add captain as team member
            $add_captain = $conn->prepare("
                INSERT INTO team_members (team_id, user_id, role, joined_at) 
                VALUES (?, ?, 'captain', NOW())
            ");
            $add_captain->bind_param("ii", $team_id, $user_id);
            $add_captain->execute();
            $add_captain->close();
            
            // Add selected members
            if (!empty($members)) {
                $member_ids = array_map('intval', explode(',', $members));
                $add_member = $conn->prepare("
                    INSERT INTO team_members (team_id, user_id, role, joined_at) 
                    VALUES (?, ?, 'player', NOW())
                ");
                
                foreach ($member_ids as $member_id) {
                    if ($member_id != $user_id) {
                        $add_member->bind_param("ii", $team_id, $member_id);
                        $add_member->execute();
                    }
                }
                $add_member->close();
            }
            
            $_SESSION['success'] = "Team created successfully!";
            header("Location: my_teams.php");
            exit();
        } else {
            $message = "Error creating team: " . $conn->error;
            $messageType = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}

// Get users registered for events (for AJAX)
// This will be used by the search function
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Team | NexArena</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================================
           ROOT VARIABLES
           ============================================================ */
        :root {
            --bg-body: #f1f5f9;
            --bg-container: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --bg-input: #f8fafc;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --border-color: #e5e7eb;
            --border-light: #f1f5f9;
            --border-input: #e5e7eb;
            --orange: #f97316;
            --orange-dark: #ea580c;
            --orange-hover: #c2410c;
            --orange-light: #ffedd5;
            --orange-gradient: linear-gradient(135deg, #f97316, #ea580c);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.07);
            --shadow-orange: 0 8px 20px rgba(249, 115, 22, 0.3);
            --success: #22c55e;
            --success-bg: #dcfce7;
            --success-border: #86efac;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --danger-border: #fca5a5;
            --sidebar-width: 80px;
            --transition: 0.3s ease;
        }

        body.dark-mode {
            --bg-body: #0f0f0f;
            --bg-container: #1a1a2e;
            --bg-secondary: #16213e;
            --bg-card: #1a1a2e;
            --bg-input: #1a1a2e;
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border-color: #2d3748;
            --border-light: #1e293b;
            --border-input: #2d3748;
            --orange-light: #2d1f0e;
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.5);
            --shadow-orange: 0 8px 20px rgba(249, 115, 22, 0.2);
            --success-bg: #064e3b;
            --success-border: #065f46;
            --danger-bg: #4c0519;
            --danger-border: #7f1d1d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            transition: background var(--transition), color var(--transition);
            min-height: 100vh;
        }

        /* ============================================================
           LAYOUT
           ============================================================ */
        .sidebar-wrapper {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            z-index: 1000;
        }

        .main-content {
            margin-left: calc(var(--sidebar-width) + 30px);
            padding: 30px 40px;
            min-height: 100vh;
            max-width: 900px;
            transition: all var(--transition);
        }

        .create-team-container {
            background: var(--bg-container);
            border-radius: 18px;
            padding: 40px 45px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: all var(--transition);
        }

        /* ============================================================
           HEADER
           ============================================================ */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            transition: color var(--transition);
        }

        .page-header h1 i {
            color: var(--orange);
            margin-right: 10px;
        }

        .page-header p {
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--orange);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 8px;
            background: var(--orange-light);
            transition: all var(--transition);
        }

        .back-btn:hover {
            background: var(--orange);
            color: #fff;
        }

        /* ============================================================
           ALERTS
           ============================================================ */
        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            border: 1px solid;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border-color: var(--success-border);
        }

        .alert-error {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: var(--danger-border);
        }

        /* ============================================================
           FORM
           ============================================================ */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            transition: color var(--transition);
        }

        .form-group .required {
            color: var(--danger);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 15px;
            outline: none;
            transition: all var(--transition);
            font-family: inherit;
        }

        .form-control:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        select.form-control {
            appearance: auto;
            cursor: pointer;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-hint {
            display: block;
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .char-count {
            display: block;
            text-align: right;
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* ============================================================
           TOGGLE SWITCH
           ============================================================ */
        .toggle-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 4px;
        }

        .toggle-wrap span {
            font-size: 14px;
            color: var(--text-muted);
            transition: color var(--transition);
        }

        .toggle {
            position: relative;
            width: 48px;
            height: 26px;
            background: var(--border-input);
            border-radius: 40px;
            cursor: pointer;
            border: 1px solid var(--border-color);
            flex-shrink: 0;
            transition: background 0.25s;
        }

        .toggle.active {
            background: var(--orange);
            border-color: var(--orange);
        }

        .toggle .knob {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: #ffffff;
            border-radius: 50%;
            transition: transform 0.25s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }

        .toggle.active .knob {
            transform: translateX(22px);
        }

        /* ============================================================
           SEARCH & MEMBERS
           ============================================================ */
        .search-container {
            position: relative;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .search-input-wrapper .form-control {
            padding-left: 40px;
        }

        .search-results {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: var(--bg-container);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            max-height: 250px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            transition: all var(--transition);
        }

        .search-result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border-light);
            transition: background var(--transition);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background: var(--bg-secondary);
        }

        .search-result-item .user-info {
            display: flex;
            flex-direction: column;
        }

        .search-result-item .user-info strong {
            font-size: 14px;
            color: var(--text-primary);
            transition: color var(--transition);
        }

        .search-result-item .user-info .user-email {
            font-size: 12px;
            color: var(--text-muted);
        }

        .search-result-empty {
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
        }

        .search-result-empty i {
            font-size: 24px;
            display: block;
            margin-bottom: 8px;
        }

        .add-member-btn {
            padding: 4px 14px;
            border-radius: 20px;
            border: 2px solid var(--orange);
            background: transparent;
            color: var(--orange);
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all var(--transition);
        }

        .add-member-btn:hover:not(:disabled) {
            background: var(--orange);
            color: #fff;
        }

        .add-member-btn.added {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
            cursor: default;
        }

        .add-member-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* ============================================================
           SELECTED MEMBERS
           ============================================================ */
        .selected-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .selected-header label {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            transition: color var(--transition);
        }

        .member-count {
            background: var(--orange);
            color: #fff;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .members-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .members-list::-webkit-scrollbar {
            width: 4px;
        }

        .members-list::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 10px;
        }

        .members-list::-webkit-scrollbar-thumb {
            background: var(--orange);
            border-radius: 10px;
        }

        .member-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            background: var(--bg-secondary);
            border-radius: 10px;
            border: 1px solid var(--border-light);
            transition: all var(--transition);
        }

        .member-item.captain {
            background: var(--orange-light);
            border-color: var(--orange);
        }

        .member-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .member-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--orange);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .member-item.captain .member-avatar {
            background: #f59e0b;
        }

        .member-name {
            font-weight: 600;
            color: var(--text-primary);
            transition: color var(--transition);
        }

        .member-role-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .captain-badge {
            background: #f59e0b;
            color: #fff;
        }

        .player-badge {
            background: var(--orange);
            color: #fff;
        }

        .remove-btn {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 16px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all var(--transition);
        }

        .remove-btn:hover {
            background: var(--danger-bg);
        }

        .member-status {
            color: var(--success);
        }

        /* ============================================================
           BUTTONS
           ============================================================ */
        .form-actions {
            display: flex;
            gap: 14px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all var(--transition);
        }

        .btn-primary {
            background: var(--orange-gradient);
            color: #fff;
            box-shadow: var(--shadow-orange);
            flex: 1;
            justify-content: center;
        }

        .btn-primary:hover {
            background: var(--orange-hover);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(249, 115, 22, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-light);
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: calc(var(--sidebar-width) + 20px);
                padding: 20px 25px;
            }
        }

        @media (max-width: 768px) {
            .sidebar-wrapper {
                width: 60px;
            }
            
            .main-content {
                margin-left: 80px;
                padding: 15px 20px;
            }

            .create-team-container {
                padding: 25px 22px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                margin-left: 70px;
                padding: 12px 12px;
            }

            .create-team-container {
                padding: 18px 16px;
                border-radius: 12px;
            }

            .page-header h1 {
                font-size: 19px;
            }

            .member-item {
                padding: 8px 12px;
            }

            .member-avatar {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
        }

        @media (max-width: 375px) {
            .main-content {
                margin-left: 60px;
                padding: 8px 8px;
            }

            .create-team-container {
                padding: 14px 12px;
            }
        }

        /* ============================================================
           DARK MODE OVERRIDES
           ============================================================ */
        body.dark-mode .member-item.captain {
            background: #2d1f0e;
            border-color: var(--orange);
        }

        body.dark-mode .back-btn {
            background: #2d1f0e;
        }

        body.dark-mode .back-btn:hover {
            background: var(--orange);
        }

        body.dark-mode .search-results {
            background: var(--bg-container);
        }
    </style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

    <div class="sidebar-wrapper">
        <?php include "sidebar.php"; ?>
    </div>

    <main class="main-content">
        <div class="create-team-container">

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <a href="my_teams.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to My Teams
                    </a>
                    <h1><i class="fas fa-users-plus"></i> Create New Team</h1>
                    <p>Fill in the details below to create your team and add players.</p>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType; ?>">
                    <?= $message; ?>
                </div>
            <?php endif; ?>

            <!-- Create Team Form -->
            <form action="" method="POST" id="createTeamForm">
                <!-- Team Name -->
                <div class="form-group">
                    <label for="team_name">Team Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="team_name" 
                        name="team_name" 
                        class="form-control" 
                        placeholder="Enter team name (e.g., Phoenix Rising)" 
                        value="<?= clean($form_data['team_name']); ?>"
                        required 
                        maxlength="50"
                        autofocus
                    >
                    <small class="char-count">0/50</small>
                </div>

                <!-- Sport & Event -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="sport_id">Sport <span class="required">*</span></label>
                        <select id="sport_id" name="sport_id" class="form-control" required>
                            <option value="">Select Sport</option>
                            <?php foreach ($sports as $sport): ?>
                                <option value="<?= $sport['sport_id']; ?>" <?= ($form_data['sport_id'] == $sport['sport_id']) ? 'selected' : ''; ?>>
                                    <?= clean($sport['sport_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="event_id">Event <span class="required">*</span></label>
                        <select id="event_id" name="event_id" class="form-control" required>
                            <option value="">Select Event</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= $event['event_id']; ?>" <?= ($form_data['event_id'] == $event['event_id']) ? 'selected' : ''; ?>>
                                    <?= clean($event['event_name']); ?> (<?= date("d M Y", strtotime($event['event_date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Game & Region -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="game">Game Type</label>
                        <input 
                            type="text" 
                            id="game" 
                            name="game" 
                            class="form-control" 
                            placeholder="e.g., 5v5, 11v11, Singles" 
                            value="<?= clean($form_data['game']); ?>"
                        >
                    </div>
                    <div class="form-group">
                        <label for="region">Region</label>
                        <input 
                            type="text" 
                            id="region" 
                            name="region" 
                            class="form-control" 
                            placeholder="e.g., North America, Europe" 
                            value="<?= clean($form_data['region']); ?>"
                        >
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-control" 
                        rows="3" 
                        placeholder="Describe your team, goals, achievements, etc."
                    ><?= clean($form_data['description']); ?></textarea>
                </div>

                <!-- Max Players & Privacy -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_players">Max Players</label>
                        <input 
                            type="number" 
                            id="max_players" 
                            name="max_players" 
                            class="form-control" 
                            value="<?= $form_data['max_players']; ?>" 
                            min="2" 
                            max="50"
                        >
                        <small class="form-hint">Minimum 2 (you + at least 1 player)</small>
                    </div>
                    <div class="form-group">
                        <label>Visibility</label>
                        <div class="toggle-wrap">
                            <span>Public</span>
                            <div class="toggle <?= ($form_data['is_private']) ? 'active' : ''; ?>" id="visibilityToggle">
                                <div class="knob"></div>
                            </div>
                            <span>Private</span>
                        </div>
                        <input type="hidden" name="is_private" id="is_private" value="<?= $form_data['is_private']; ?>">
                        <small class="form-hint">Private teams are invite-only and hidden from others.</small>
                    </div>
                </div>

                <!-- Add Members Section -->
                <div class="form-group" style="margin-top: 10px; padding-top: 20px; border-top: 2px solid var(--border-light);">
                    <label style="font-size: 18px;">
                        <i class="fas fa-user-plus" style="color: var(--orange);"></i> Add Team Members
                    </label>
                    <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 12px;">
                        Search for users registered for the selected event and add them to your team.
                    </p>

                    <div class="search-container">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input 
                                type="text" 
                                id="memberSearch" 
                                class="form-control" 
                                placeholder="Search registered users by name or email..."
                                autocomplete="off"
                            >
                        </div>
                        <div id="searchResults" class="search-results"></div>
                    </div>
                    <small class="form-hint">
                        <i class="fas fa-info-circle"></i> 
                        Users must be registered for the selected event to be added.
                    </small>
                </div>

                <!-- Selected Members -->
                <div class="form-group">
                    <div class="selected-header">
                        <label>Selected Members</label>
                        <span class="member-count" id="memberCount">1</span>
                    </div>
                    <div class="members-list" id="membersList">
                        <!-- Captain is automatically added -->
                        <div class="member-item captain" data-user-id="<?= $user_id; ?>">
                            <div class="member-info">
                                <span class="member-avatar">👑</span>
                                <div>
                                    <span class="member-name"><?= clean($full_name); ?> (You)</span>
                                    <span class="member-role-badge captain-badge">Captain</span>
                                </div>
                            </div>
                            <span class="member-status"><i class="fas fa-check-circle"></i></span>
                        </div>
                    </div>
                    <input type="hidden" name="members" id="selectedMembers" value="">
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <a href="my_teams.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Create Team
                    </button>
                </div>
            </form>

        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============================================================
        // CHARACTER COUNTER
        // ============================================================
        const teamNameInput = document.getElementById('team_name');
        const charCount = document.querySelector('.char-count');
        
        teamNameInput.addEventListener('input', function() {
            charCount.textContent = this.value.length + '/50';
        });

        // ============================================================
        // VISIBILITY TOGGLE
        // ============================================================
        const toggle = document.getElementById('visibilityToggle');
        const hiddenInput = document.getElementById('is_private');
        if (toggle && hiddenInput) {
            toggle.addEventListener('click', function() {
                this.classList.toggle('active');
                hiddenInput.value = this.classList.contains('active') ? '1' : '0';
            });
        }

        // ============================================================
        // MEMBER SEARCH
        // ============================================================
        const searchInput = document.getElementById('memberSearch');
        const searchResults = document.getElementById('searchResults');
        let selectedMembers = [];
        let searchTimeout;

        function getSelectedEventId() {
            return document.getElementById('event_id').value;
        }

        // When event changes, update search
        document.getElementById('event_id').addEventListener('change', function() {
            // Clear search results
            searchResults.style.display = 'none';
            searchInput.value = '';
            // Reset selected members (keep captain)
            selectedMembers = [];
            updateMembersList();
            updateHiddenInput();
            updateMemberCount();
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            const eventId = getSelectedEventId();
            
            if (!eventId) {
                searchResults.innerHTML = `
                    <div class="search-result-empty">
                        <i class="fas fa-exclamation-circle"></i> Please select an event first
                    </div>
                `;
                searchResults.style.display = 'block';
                return;
            }
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                searchUsers(query, eventId);
            }, 300);
        });

        function searchUsers(query, eventId) {
            fetch(`ajax_search_users.php?query=${encodeURIComponent(query)}&event_id=${eventId}&team_id=0`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users.length > 0) {
                        displaySearchResults(data.users);
                    } else {
                        searchResults.innerHTML = `
                            <div class="search-result-empty">
                                <i class="fas fa-user-slash"></i> No users found
                            </div>
                        `;
                        searchResults.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function displaySearchResults(users) {
            let html = '';
            const selectedIds = selectedMembers.map(m => m.user_id);
            const currentUserId = <?= $user_id; ?>;
            
            users.forEach(user => {
                const isSelected = selectedIds.includes(user.user_id);
                // Don't show the current user (they're already captain)
                if (user.user_id === currentUserId) return;
                
                html += `
                    <div class="search-result-item" data-user-id="${user.user_id}">
                        <div class="user-info">
                            <strong>${escapeHtml(user.full_name)}</strong>
                            <span class="user-email">${escapeHtml(user.email)}</span>
                        </div>
                        <button 
                            type="button" 
                            class="add-member-btn ${isSelected ? 'added' : ''}"
                            onclick="addMember(${user.user_id}, '${escapeJs(user.full_name)}', '${escapeJs(user.email)}')"
                            ${isSelected ? 'disabled' : ''}
                        >
                            ${isSelected ? 'Added' : '+ Add'}
                        </button>
                    </div>
                `;
            });
            
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
        }

        // ============================================================
        // ADD / REMOVE MEMBERS
        // ============================================================
        window.addMember = function(userId, userName, userEmail) {
            // Check if already selected
            if (selectedMembers.some(m => m.user_id === userId)) {
                return;
            }

            selectedMembers.push({
                user_id: userId,
                full_name: userName,
                email: userEmail
            });

            updateMembersList();
            updateHiddenInput();
            updateMemberCount();
            searchResults.style.display = 'none';
            searchInput.value = '';
        };

        window.removeMember = function(userId) {
            selectedMembers = selectedMembers.filter(m => m.user_id !== userId);
            updateMembersList();
            updateHiddenInput();
            updateMemberCount();
        };

        function updateMembersList() {
            const list = document.getElementById('membersList');
            const currentUserId = <?= $user_id; ?>;
            
            // Keep the captain
            list.innerHTML = `
                <div class="member-item captain" data-user-id="${currentUserId}">
                    <div class="member-info">
                        <span class="member-avatar">👑</span>
                        <div>
                            <span class="member-name"><?= clean($full_name); ?> (You)</span>
                            <span class="member-role-badge captain-badge">Captain</span>
                        </div>
                    </div>
                    <span class="member-status"><i class="fas fa-check-circle"></i></span>
                </div>
            `;

            // Add selected members
            selectedMembers.forEach(member => {
                const div = document.createElement('div');
                div.className = 'member-item';
                div.dataset.userId = member.user_id;
                div.innerHTML = `
                    <div class="member-info">
                        <span class="member-avatar">${member.full_name.charAt(0).toUpperCase()}</span>
                        <div>
                            <span class="member-name">${escapeHtml(member.full_name)}</span>
                            <span class="member-role-badge player-badge">Player</span>
                        </div>
                    </div>
                    <button type="button" class="remove-btn" onclick="removeMember(${member.user_id})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                list.appendChild(div);
            });
        }

        function updateHiddenInput() {
            const ids = selectedMembers.map(m => m.user_id);
            document.getElementById('selectedMembers').value = ids.join(',');
        }

        function updateMemberCount() {
            const count = selectedMembers.length + 1; // +1 for captain
            document.getElementById('memberCount').textContent = count;
        }

        // ============================================================
        // ESCAPE HELPERS
        // ============================================================
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function escapeJs(text) {
            return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        }

        // ============================================================
        // FORM VALIDATION
        // ============================================================
        document.getElementById('createTeamForm').addEventListener('submit', function(e) {
            const teamName = document.getElementById('team_name').value.trim();
            const sportId = document.getElementById('sport_id').value;
            const eventId = document.getElementById('event_id').value;
            
            if (!teamName) {
                e.preventDefault();
                alert('Please enter a team name.');
                document.getElementById('team_name').focus();
                return;
            }

            if (!sportId) {
                e.preventDefault();
                alert('Please select a sport.');
                document.getElementById('sport_id').focus();
                return;
            }

            if (!eventId) {
                e.preventDefault();
                alert('Please select an event.');
                document.getElementById('event_id').focus();
                return;
            }

            // At least 1 player (captain is already included)
            if (selectedMembers.length === 0) {
                if (!confirm('You haven\'t added any players. Do you want to create the team with just you as the captain?')) {
                    e.preventDefault();
                    return;
                }
            }

            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        });

        // ============================================================
        // CLOSE SEARCH RESULTS ON CLICK OUTSIDE
        // ============================================================
        document.addEventListener('click', function(e) {
            const container = document.querySelector('.search-container');
            if (container && !container.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    });
    </script>

</body>
</html>