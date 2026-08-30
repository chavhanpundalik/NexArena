<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$teamId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($teamId <= 0) { 
    header("Location: teams.php"); 
    exit(); 
}

// Get team details
$stmt = $conn->prepare("SELECT * FROM teams WHERE team_id = ?");
$stmt->bind_param("i", $teamId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { 
    header("Location: teams.php"); 
    exit(); 
}
$team = $result->fetch_assoc();
$stmt->close();

// Get all users for captain dropdown
$users = [];
$userResult = $conn->query("SELECT user_id, username, email FROM users ORDER BY username");
if ($userResult) {
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get all sports for dropdown
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

// Handle form submission
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name'] ?? '');
    $sport_id = !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : 0;
    $game = trim($_POST['game'] ?? '');
    $captain_id = !empty($_POST['captain_id']) ? (int)$_POST['captain_id'] : null;
    $status = $_POST['status'] ?? 'active';
    $max_players = (int)($_POST['max_players'] ?? 11);
    $description = trim($_POST['description'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    
    // Validate
    $errors = [];
    if (empty($team_name)) { 
        $errors[] = "Team name is required"; 
    }
    if (empty($sport_id) || $sport_id <= 0) { 
        $errors[] = "Sport is required"; 
    }
    
    if (empty($errors)) {
        $updateSql = "UPDATE teams SET 
                        team_name = ?, 
                        sport_id = ?, 
                        game = ?, 
                        captain_id = ?, 
                        status = ?, 
                        max_players = ?, 
                        description = ?, 
                        region = ?, 
                        is_private = ? 
                      WHERE team_id = ?";
        
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("sisisissii", 
            $team_name,    
            $sport_id,     
            $game,         
            $captain_id,   
            $status,       
            $max_players,  
            $description,  
            $region,       
            $is_private,   
            $teamId        
        );
        
        if ($stmt->execute()) {
            $message = "Team updated successfully!";
            $messageType = "success";
            $stmt->close(); // Close the update statement
            
            // Refresh team data with a NEW statement
            $refreshStmt = $conn->prepare("SELECT * FROM teams WHERE team_id = ?");
            $refreshStmt->bind_param("i", $teamId);
            $refreshStmt->execute();
            $result = $refreshStmt->get_result();
            $team = $result->fetch_assoc();
            $refreshStmt->close(); // Close the refresh statement
        } else {
            $message = "Error updating team: " . $conn->error;
            $messageType = "error";
            $stmt->close();
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        .form-container { max-width:700px; margin:40px auto; background:#fff; padding:40px; border-radius:18px; border:1px solid #e5e7eb; }
        .form-header { margin-bottom:30px; }
        .form-header h2 { font-size:24px; margin-bottom:5px; }
        .form-header p { color:#71717a; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; font-weight:600; margin-bottom:6px; font-size:14px; color:#1f2937; }
        .form-group .required { color:#dc2626; }
        .form-control { width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; transition:0.2s; }
        .form-control:focus { outline:none; border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,0.1); }
        textarea.form-control { min-height:100px; resize:vertical; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-check { display:flex; align-items:center; gap:10px; }
        .form-check input[type="checkbox"] { width:18px; height:18px; }
        .btn-submit { background:#8b5cf6; color:#fff; border:none; padding:12px 30px; border-radius:10px; font-weight:700; font-size:15px; cursor:pointer; transition:0.25s; }
        .btn-submit:hover { background:#7c3aed; transform:translateY(-2px); }
        .btn-cancel { background:#f4f4f5; color:#1f2937; border:1px solid #e5e7eb; padding:12px 30px; border-radius:10px; font-weight:700; font-size:15px; text-decoration:none; display:inline-block; transition:0.25s; }
        .btn-cancel:hover { background:#e4e4e7; }
        .form-actions { display:flex; gap:14px; margin-top:10px; }
        .alert { padding:12px 16px; border-radius:8px; margin-bottom:20px; }
        .alert-success { background:#dcfce7; border:1px solid #86efac; color:#16a34a; }
        .alert-error { background:#fef2f2; border:1px solid #fca5a5; color:#dc2626; }
        @media (max-width:600px) { .form-row { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fa-solid fa-pen-to-square" style="color:#8b5cf6;"></i> Edit Team</h2>
            <p>Update team information</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType; ?>">
                <?= $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Team Name <span class="required">*</span></label>
                    <input type="text" name="team_name" class="form-control" value="<?= htmlspecialchars($team['team_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Sport <span class="required">*</span></label>
                    <select name="sport_id" class="form-control" required>
                        <option value="">Select Sport</option>
                        <?php foreach ($sports as $sport): ?>
                            <option value="<?= $sport['sport_id']; ?>" <?= ($team['sport_id'] == $sport['sport_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($sport['sport_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Game Type</label>
                    <input type="text" name="game" class="form-control" value="<?= htmlspecialchars($team['game'] ?? ''); ?>" placeholder="e.g., 5v5, 11v11, Singles">
                </div>
                <div class="form-group">
                    <label>Region</label>
                    <input type="text" name="region" class="form-control" value="<?= htmlspecialchars($team['region'] ?? ''); ?>" placeholder="e.g., North, South, City">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Captain</label>
                    <select name="captain_id" class="form-control">
                        <option value="">Select Captain</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['user_id']; ?>" <?= ($team['captain_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($user['username']); ?> (<?= htmlspecialchars($user['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($team['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= ($team['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="full" <?= ($team['status'] == 'full') ? 'selected' : ''; ?>>Full</option>
                        <option value="disbanded" <?= ($team['status'] == 'disbanded') ? 'selected' : ''; ?>>Disbanded</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Max Players</label>
                    <input type="number" name="max_players" class="form-control" value="<?= $team['max_players'] ?? 11; ?>" min="1" max="50">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="form-check">
                        <input type="checkbox" name="is_private" id="is_private" <?= ($team['is_private'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="is_private" style="font-weight:400;cursor:pointer;">Private Team (only visible to members)</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($team['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit"><i class="fa-solid fa-save"></i> Update Team</button>
                <a href="teams.php" class="btn-cancel"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>