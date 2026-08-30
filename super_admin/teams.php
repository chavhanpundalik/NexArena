<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Search & Filter ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sport = isset($_GET['sport']) ? trim($_GET['sport']) : '';

// --- Stats ---
$totalTeams = 0;
$sportsList = [];

$result = $conn->query("SELECT COUNT(*) AS total FROM teams");
if ($result) { 
    $row = $result->fetch_assoc(); 
    $totalTeams = (int)$row['total']; 
}

// Get distinct sports from sports table
$result = $conn->query("SELECT DISTINCT sport_name FROM sports ORDER BY sport_name");
if ($result) { 
    while ($row = $result->fetch_assoc()) { 
        $sportsList[] = $row['sport_name']; 
    }
}

// --- Build query with correct columns ---
$sql = "SELECT 
            t.team_id, 
            t.team_name, 
            t.sport_id,
            t.game,
            t.description, 
            t.captain_id,
            t.status,
            t.max_players,
            t.created_at,
            t.region,
            t.is_private,
            u.username AS captain_name,
            s.sport_name
        FROM teams t
        LEFT JOIN users u ON t.captain_id = u.user_id
        LEFT JOIN sports s ON t.sport_id = s.sport_id
        WHERE 1=1";

$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (t.team_name LIKE ? OR t.description LIKE ? OR u.username LIKE ?)";
    $searchValue = "%$search%";
    $params[] = $searchValue; 
    $params[] = $searchValue; 
    $params[] = $searchValue;
    $types .= "sss";
}
if ($sport !== '') {
    $sql .= " AND s.sport_name = ?";
    $params[] = $sport;
    $types .= "s";
}

$sql .= " ORDER BY t.team_name ASC";

$stmt = $conn->prepare($sql);
if ($stmt === false) { 
    die("Query error: " . $conn->error); 
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$teamsResult = $stmt->get_result();
$teams = $teamsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams Management | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/teams.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active { background: #dcfce7; color: #16a34a; }
        .status-inactive { background: #fef2f2; color: #dc2626; }
        .status-full { background: #fef3c7; color: #d97706; }
        .status-disbanded { background: #f3f4f6; color: #6b7280; }
        .game-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            background: #e0e7ff;
            color: #4f46e5;
        }
        .sport-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            background: #ede9fe;
            color: #7c3aed;
        }
        .sport-badge i {
            margin-right: 4px;
        }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <!-- Page Header -->
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-people-group"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Teams Management</h1>
                <p>Organize and oversee all teams on the platform.</p>
            </div>
        </div>
        <a href="add_team.php" class="add-user-btn">
            <i class="fa-solid fa-plus"></i> Create Team
        </a>
    </section>

    <!-- Stats -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-people-group"></i></div>
            <div class="stat-content">
                <span>Total Teams</span>
                <strong><?= number_format($totalTeams); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
            <div class="stat-icon" style="background:#ede9fe; color:#8b5cf6;"><i class="fa-regular fa-futbol"></i></div>
            <div class="stat-content">
                <span>Total Sports</span>
                <strong><?= count($sportsList); ?></strong>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="filter-card">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search teams by name, description, or captain..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="select-box">
                <i class="fa-solid fa-tag"></i>
                <select name="sport">
                    <option value="">All Sports</option>
                    <?php foreach ($sportsList as $s): ?>
                        <option value="<?= htmlspecialchars($s); ?>" <?= $sport === $s ? 'selected' : ''; ?>><?= htmlspecialchars($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="teams.php" class="reset-btn"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </form>
    </section>

    <!-- Teams Table -->
    <section class="users-card">
        <div class="table-header">
            <div>
                <span class="section-label">TEAM DIRECTORY</span>
                <h2>All Teams</h2>
            </div>
            <span class="user-count"><?= count($teams); ?> team<?= count($teams) != 1 ? 's' : ''; ?></span>
        </div>
        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Team</th>
                        <th>Sport</th>
                        <th>Game</th>
                        <th>Captain</th>
                        <th>Status</th>
                        <th>Players</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($teams)): ?>
                    <?php foreach ($teams as $team): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="avatar" style="background:var(--orange); color:#fff; font-size:14px;">
                                        <?php if (!empty($team['logo'])): ?>
                                            <img src="../<?= htmlspecialchars($team['logo']); ?>" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                        <?php else: ?>
                                            <i class="fa-regular fa-people-group"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-name">
                                        <strong><?= htmlspecialchars($team['team_name']); ?></strong>
                                        <small>ID: #<?= (int)$team['team_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="sport-badge">
                                    <i class="fa-regular fa-futbol"></i> 
                                    <?= htmlspecialchars($team['sport_name'] ?? 'Not specified'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($team['game'])): ?>
                                    <span class="game-badge"><?= htmlspecialchars($team['game']); ?></span>
                                <?php else: ?>
                                    <span style="color:#999;font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($team['captain_name'])): ?>
                                    <i class="fa-solid fa-crown" style="color:#f59e0b;"></i>
                                    <?= htmlspecialchars($team['captain_name']); ?>
                                <?php else: ?>
                                    <span style="color:#999;font-size:12px;">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $team['status'] ?? 'inactive'; ?>">
                                    <?= $team['status'] ?? 'Inactive'; ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <?= $team['max_players'] ?? '11'; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($team['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_team.php?id=<?= (int)$team['team_id']; ?>" class="action-btn view-btn" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <a href="edit_team.php?id=<?= (int)$team['team_id']; ?>" class="action-btn edit-btn" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    <a href="delete_team.php?id=<?= (int)$team['team_id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this team?');"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-people-group"></i></div>
                        <h3>No Teams Found</h3>
                        <p>Try adjusting your filters or create a new team.</p>
                        <a href="add_team.php" class="empty-reset"><i class="fa-solid fa-plus"></i> Create Team</a>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>