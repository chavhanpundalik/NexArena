<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../db_connect.php";

$user_id = $_SESSION['user_id'];
$team_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

// Check if user is captain
$check_sql = $conn->prepare("SELECT role FROM team_members WHERE team_id = ? AND user_id = ? AND role = 'captain'");
$check_sql->bind_param("ii", $team_id, $user_id);
$check_sql->execute();
$check_result = $check_sql->get_result();

if ($check_result->num_rows == 0) {
    $_SESSION['error'] = "You don't have permission to edit this team.";
    header("Location: teams.php");
    exit();
}

// Get team details
$team_sql = $conn->prepare("SELECT * FROM teams WHERE team_id = ?");
$team_sql->bind_param("i", $team_id);
$team_sql->execute();
$team = $team_sql->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $game = trim($_POST['game'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    $max_players = (int)$_POST['max_players'] ?? 11;
    
    if (empty($team_name)) {
        $_SESSION['error'] = "Team name is required.";
    } else {
        $update_sql = $conn->prepare("UPDATE teams SET team_name = ?, description = ?, game = ?, region = ?, is_private = ?, max_players = ? WHERE team_id = ?");
        $update_sql->bind_param("ssssiii", $team_name, $description, $game, $region, $is_private, $max_players, $team_id);
        
        if ($update_sql->execute()) {
            $_SESSION['success'] = "Team settings updated successfully!";
            header("Location: team_details.php?id=" . $team_id);
            exit();
        } else {
            $_SESSION['error'] = "Failed to update team settings.";
        }
    }
}

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

function clean($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Don't close connection here - sidebar needs it
// $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Settings | NexArena</title>
    
    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">
    
    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/team.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        .settings-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            margin-left: 290px;
            background: var(--bg-card);
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-primary);
        }
        [data-theme="dark"] .settings-container {
            box-shadow: var(--shadow-md);
        }
        .settings-container h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        .settings-container .subtitle {
            color: var(--text-muted);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-input);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s;
            box-sizing: border-box;
            background: var(--bg-input);
            color: var(--text-primary);
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--orange);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,107,53,0.1);
        }
        [data-theme="dark"] .form-group input:focus,
        [data-theme="dark"] .form-group textarea:focus,
        [data-theme="dark"] .form-group select:focus {
            box-shadow: 0 0 0 3px rgba(255,107,53,0.2);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn-save {
            background: var(--orange);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-save:hover {
            background: var(--orange-hover);
            transform: translateY(-2px);
        }
        .btn-cancel {
            background: var(--bg-input);
            color: var(--text-primary);
            border: 1px solid var(--border-input);
            padding: 14px 40px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-cancel:hover {
            background: var(--border-hover);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid var(--success-border);
        }
        .alert-error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
        }
        [data-theme="dark"] .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }
        @media (max-width: 768px) {
            .settings-container {
                margin-left: 80px;
                padding: 20px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
            }
            .btn-save,
            .btn-cancel {
                width: 100%;
                text-align: center;
            }
        }
        @media (max-width: 400px) {
            .settings-container {
                margin-left: 68px;
                padding: 15px;
            }
        }
    </style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">
    <?php include "sidebar.php"; ?>
    
    <div class="settings-container">
        <h1><i class="fas fa-cog"></i> Team Settings</h1>
        <p class="subtitle">Update your team information and preferences</p>
        
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= clean($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?= clean($errorMsg) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Team Name *</label>
                <input type="text" name="team_name" value="<?= clean($team['team_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?= clean($team['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Game</label>
                    <select name="game">
                        <option value="Valorant" <?= ($team['game'] ?? '') == 'Valorant' ? 'selected' : '' ?>>Valorant</option>
                        <option value="Counter-Strike 2" <?= ($team['game'] ?? '') == 'Counter-Strike 2' ? 'selected' : '' ?>>Counter-Strike 2</option>
                        <option value="Dota 2" <?= ($team['game'] ?? '') == 'Dota 2' ? 'selected' : '' ?>>Dota 2</option>
                        <option value="League of Legends" <?= ($team['game'] ?? '') == 'League of Legends' ? 'selected' : '' ?>>League of Legends</option>
                        <option value="Apex Legends" <?= ($team['game'] ?? '') == 'Apex Legends' ? 'selected' : '' ?>>Apex Legends</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Region</label>
                    <select name="region">
                        <option value="NA" <?= ($team['region'] ?? '') == 'NA' ? 'selected' : '' ?>>North America</option>
                        <option value="EU" <?= ($team['region'] ?? '') == 'EU' ? 'selected' : '' ?>>Europe</option>
                        <option value="AS" <?= ($team['region'] ?? '') == 'AS' ? 'selected' : '' ?>>Asia</option>
                        <option value="SA" <?= ($team['region'] ?? '') == 'SA' ? 'selected' : '' ?>>South America</option>
                        <option value="OC" <?= ($team['region'] ?? '') == 'OC' ? 'selected' : '' ?>>Oceania</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Max Players</label>
                    <input type="number" name="max_players" value="<?= $team['max_players'] ?? 11 ?>" min="1" max="20">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_private" value="1" <?= ($team['is_private'] ?? 0) ? 'checked' : '' ?>>
                        Private Team
                    </label>
                    <small style="display:block;color:var(--text-muted);margin-top:5px;">Private teams are invite-only</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                <a href="team_details.php?id=<?= $team_id ?>" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
    
    <!-- Theme JavaScript - MUST BE LAST -->
    <script src="assets/theme.js"></script>
</body>
</html>

<?php
// Don't close connection here - sidebar needs it
// $conn->close();
?>