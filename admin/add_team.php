<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Get all users for captain dropdown ---
$users = [];
$userResult = $conn->query("SELECT user_id, username, email FROM users ORDER BY username");
if ($userResult) {
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// --- Get all sports for dropdown ---
$sports = [];
$sportResult = $conn->query("SELECT sport_id, sport_name FROM sports ORDER BY sport_name");
if ($sportResult && $sportResult->num_rows > 0) {
    while ($row = $sportResult->fetch_assoc()) {
        $sports[] = $row;
    }
} else {
    // If no sports table exists, use default sports
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

// --- CSRF token ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- Handle form submission ---
$message = '';
$messageType = '';
$form_data = [
    'team_name' => '',
    'sport_id' => '',
    'game' => '',
    'captain_id' => '',
    'status' => 'active',
    'max_players' => 11,
    'description' => '',
    'region' => '',
    'is_private' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid security token. Please try again.";
        $messageType = "error";
    } else {
        $team_name = trim($_POST['team_name'] ?? '');
        $sport_id = !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : 0;
        $game = trim($_POST['game'] ?? '');
        $captain_id = !empty($_POST['captain_id']) ? (int)$_POST['captain_id'] : null;
        $status = $_POST['status'] ?? 'active';
        $max_players = (int)($_POST['max_players'] ?? 11);
        $description = trim($_POST['description'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $is_private = isset($_POST['is_private']) ? 1 : 0;

        $form_data = compact('team_name', 'sport_id', 'game', 'captain_id', 'status', 'max_players', 'description', 'region', 'is_private');

        // --- Validation ---
        $errors = [];
        if (empty($team_name)) {
            $errors[] = "Team name is required";
        }
        if (empty($sport_id) || $sport_id <= 0) {
            $errors[] = "Sport is required";
        }
        if (strlen($team_name) < 3) {
            $errors[] = "Team name must be at least 3 characters";
        }

        // --- Insert team ---
        if (empty($errors)) {
            $insertSql = "INSERT INTO teams (team_name, sport_id, game, captain_id, status, max_players, description, region, is_private, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($insertSql);
            if ($stmt) {
                $stmt->bind_param("sisisissi", 
                    $team_name,    
                    $sport_id,     
                    $game,         
                    $captain_id,   
                    $status,       
                    $max_players,  
                    $description,  
                    $region,       
                    $is_private    
                );

                if ($stmt->execute()) {
                    $teamId = $stmt->insert_id;
                    $message = "Team created successfully! Team ID: #" . $teamId;
                    $messageType = "success";
                    
                    // Reset form data
                    $form_data = [
                        'team_name' => '',
                        'sport_id' => '',
                        'game' => '',
                        'captain_id' => '',
                        'status' => 'active',
                        'max_players' => 11,
                        'description' => '',
                        'region' => '',
                        'is_private' => 0
                    ];
                    
                    // Generate new CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $csrf_token = $_SESSION['csrf_token'];
                } else {
                    $message = "Error creating team: " . $conn->error;
                    $messageType = "error";
                }
                $stmt->close();
            } else {
                $message = "Database error: " . $conn->error;
                $messageType = "error";
            }
        } else {
            $message = implode("<br>", $errors);
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Team | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        .form-container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
        }
        .form-header {
            margin-bottom: 30px;
        }
        .form-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .form-header p {
            color: #71717a;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 700;
            font-size: 13px;
            color: #1f2937;
            margin-bottom: 6px;
        }
        .form-group label .required {
            color: #dc2626;
            margin-left: 2px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: #fafafa;
            transition: 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #8b5cf6;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(139,92,246,0.1);
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
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        .form-actions {
            display: flex;
            gap: 14px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .btn-submit {
            background: #8b5cf6;
            color: #fff;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: 0.25s;
            box-shadow: 0 7px 18px rgba(139,92,246,0.25);
        }
        .btn-submit:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }
        .btn-cancel {
            background: #f4f4f5;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.25s;
        }
        .btn-cancel:hover {
            background: #e4e4e7;
        }
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .alert-success a {
            color: #16a34a;
            font-weight: 700;
            text-decoration: none;
            margin-left: 10px;
        }
        .alert-success a:hover {
            text-decoration: underline;
        }
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert ul {
            margin: 4px 0 0 18px;
        }
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .form-container {
                padding: 20px;
                margin: 20px;
            }
            .form-actions {
                flex-direction: column;
            }
            .form-actions .btn-submit,
            .form-actions .btn-cancel {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-plus-circle"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Add New Team</h1>
                <p>Create a new team for your platform.</p>
            </div>
        </div>
        <a href="teams.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Teams
        </a>
    </section>

    <div class="form-container">
        <div class="form-header">
            <h2><i class="fa-regular fa-people-group" style="color:#8b5cf6;"></i> Team Details</h2>
            <p>Fill in the information below to create a new team.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType; ?>">
                <?= $message; ?>
                <?php if ($messageType === 'success'): ?>
                    <a href="teams.php">View all teams →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="team_name">Team Name <span class="required">*</span></label>
                    <input type="text" id="team_name" name="team_name" class="form-control" 
                           value="<?= htmlspecialchars($form_data['team_name']); ?>" required 
                           placeholder="e.g., Eagles FC">
                </div>
                <div class="form-group">
                    <label for="sport_id">Sport <span class="required">*</span></label>
                    <select id="sport_id" name="sport_id" class="form-control" required>
                        <option value="">Select Sport</option>
                        <?php foreach ($sports as $sport): ?>
                            <option value="<?= $sport['sport_id']; ?>" <?= ($form_data['sport_id'] == $sport['sport_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($sport['sport_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="game">Game Type</label>
                    <input type="text" id="game" name="game" class="form-control" 
                           value="<?= htmlspecialchars($form_data['game']); ?>" 
                           placeholder="e.g., 5v5, 11v11, Singles">
                </div>
                <div class="form-group">
                    <label for="region">Region</label>
                    <input type="text" id="region" name="region" class="form-control" 
                           value="<?= htmlspecialchars($form_data['region']); ?>" 
                           placeholder="e.g., North, South, City">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="captain_id">Captain</label>
                    <select id="captain_id" name="captain_id" class="form-control">
                        <option value="">Select Captain</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['user_id']; ?>" <?= ($form_data['captain_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($user['username']); ?> (<?= htmlspecialchars($user['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active" <?= $form_data['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $form_data['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="full" <?= $form_data['status'] === 'full' ? 'selected' : ''; ?>>Full</option>
                        <option value="disbanded" <?= $form_data['status'] === 'disbanded' ? 'selected' : ''; ?>>Disbanded</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="max_players">Max Players</label>
                    <input type="number" id="max_players" name="max_players" class="form-control" 
                           value="<?= $form_data['max_players']; ?>" min="1" max="50">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="form-check">
                        <input type="checkbox" name="is_private" id="is_private" <?= ($form_data['is_private']) ? 'checked' : ''; ?>>
                        <label for="is_private" style="font-weight:400;cursor:pointer;">Private Team (only visible to members)</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" 
                          placeholder="Enter team description, goals, achievements, etc..."><?= htmlspecialchars($form_data['description']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit"><i class="fa-solid fa-save"></i> Create Team</button>
                <a href="teams.php" class="btn-cancel"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>

</main>

</body>
</html>