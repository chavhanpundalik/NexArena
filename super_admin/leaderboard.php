<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// Get leaderboard data with proper joins
$sql = "SELECT 
            lb.leaderboard_id,
            lb.user_id,
            lb.team_id,
            lb.event_id,
            lb.points,
            lb.wins,
            lb.losses,
            lb.draws,
            lb.matches_played,
            lb.rank_position,
            lb.created_at,
            lb.updated_at,
            u.username AS user_name,
            t.team_name,
            e.event_name
        FROM leaderboard lb
        LEFT JOIN users u ON lb.user_id = u.user_id
        LEFT JOIN teams t ON lb.team_id = t.team_id
        LEFT JOIN events e ON lb.event_id = e.event_id
        ORDER BY lb.points DESC, lb.wins DESC, lb.rank_position ASC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // Fallback query without joins
    $sql = "SELECT * FROM leaderboard ORDER BY points DESC, wins DESC";
    $stmt = $conn->prepare($sql);
}

$leaderboardData = [];
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $leaderboardData = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get events for filter
$events = [];
$eventResult = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_name");
if ($eventResult) {
    while ($row = $eventResult->fetch_assoc()) {
        $events[] = $row;
    }
}

// Calculate statistics
$totalEntries = count($leaderboardData);
$topEntry = !empty($leaderboardData) ? $leaderboardData[0] : null;
$totalPoints = 0;
$totalWins = 0;

foreach ($leaderboardData as $entry) {
    $totalPoints += (int)($entry['points'] ?? 0);
    $totalWins += (int)($entry['wins'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        .leaderboard-container { max-width:1100px; margin:40px auto; }
        
        .page-header {
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-size: 28px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-header h1 i { color: #f59e0b; }
        .page-header p { color: #71717a; margin-top: 5px; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background: #ede9fe;
            color: #8b5cf6;
            flex-shrink: 0;
        }
        .stat-card .stat-content { flex: 1; }
        .stat-card .stat-content span { font-size: 13px; color: #71717a; display: block; }
        .stat-card .stat-content strong { font-size: 24px; display: block; }
        
        .leaderboard-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .leaderboard-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .leaderboard-header h2 {
            font-size: 20px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .leaderboard-header h2 i { color: #f59e0b; }
        .leaderboard-header .count {
            font-size: 14px;
            color: #71717a;
            background: #f3f4f6;
            padding: 4px 14px;
            border-radius: 20px;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        .leaderboard-table th {
            text-align: left;
            padding: 14px 20px;
            background: #f8fafc;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #71717a;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        .leaderboard-table td {
            padding: 14px 20px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .leaderboard-table tr:last-child td { border-bottom: none; }
        .leaderboard-table tr:hover td { background: #fafafa; }
        
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 14px;
        }
        .rank-1 { background: #fef3c7; color: #d97706; }
        .rank-2 { background: #e5e7eb; color: #6b7280; }
        .rank-3 { background: #fde68a; color: #92400e; }
        .rank-other { background: #f3f4f6; color: #4b5563; }
        
        .entity-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .entity-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        .entity-name { font-weight: 600; }
        .entity-sub {
            font-size: 12px;
            color: #71717a;
            display: block;
        }
        
        .points-badge {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 16px;
            background: #ede9fe;
            color: #7c3aed;
            text-align: center;
            min-width: 50px;
        }
        
        .stat-number {
            font-weight: 600;
            text-align: center;
        }
        .stat-win { color: #16a34a; }
        .stat-loss { color: #dc2626; }
        .stat-draw { color: #d97706; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #71717a;
        }
        .empty-state i {
            font-size: 60px;
            color: #d1d5db;
            margin-bottom: 20px;
            display: block;
        }
        .empty-state h3 { font-size: 20px; margin-bottom: 10px; color: #1f2937; }
        
        .medal-icon { font-size: 18px; }
        .event-tag {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            background: #e0e7ff;
            color: #4f46e5;
        }
        
        @media (max-width: 768px) {
            .leaderboard-table { font-size: 13px; min-width: 600px; }
            .leaderboard-table th, .leaderboard-table td { padding: 10px 12px; }
            .entity-avatar { width: 32px; height: 32px; font-size: 12px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .leaderboard-header { padding: 15px 20px; }
        }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <div class="leaderboard-container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fa-solid fa-trophy"></i> Leaderboard</h1>
            <p>Track performance rankings across all events</p>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-list-ol"></i></div>
                <div class="stat-content">
                    <span>Total Entries</span>
                    <strong><?= $totalEntries; ?></strong>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fa-solid fa-trophy"></i></div>
                <div class="stat-content">
                    <span>Top Performer</span>
                    <strong><?= $topEntry ? htmlspecialchars($topEntry['user_name'] ?? $topEntry['user_id'] ?? 'N/A') : 'N/A'; ?></strong>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fa-solid fa-star"></i></div>
                <div class="stat-content">
                    <span>Total Points</span>
                    <strong><?= number_format($totalPoints); ?></strong>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa-solid fa-medal"></i></div>
                <div class="stat-content">
                    <span>Total Wins</span>
                    <strong><?= number_format($totalWins); ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Leaderboard Table -->
        <div class="leaderboard-card">
            <div class="leaderboard-header">
                <h2><i class="fa-solid fa-ranking-star"></i> Rankings</h2>
                <span class="count"><?= $totalEntries; ?> entries</span>
            </div>
            
            <?php if (!empty($leaderboardData)): ?>
                <div class="table-wrapper">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">Rank</th>
                                <th>Player / Team</th>
                                <th style="text-align:center;">Event</th>
                                <th style="text-align:center;">Played</th>
                                <th style="text-align:center;">Wins</th>
                                <th style="text-align:center;">Losses</th>
                                <th style="text-align:center;">Draws</th>
                                <th style="text-align:center;">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaderboardData as $index => $entry): ?>
                                <?php 
                                $rank = $index + 1;
                                $rankClass = $rank <= 3 ? 'rank-' . $rank : 'rank-other';
                                $medal = '';
                                if ($rank == 1) $medal = '🥇';
                                elseif ($rank == 2) $medal = '🥈';
                                elseif ($rank == 3) $medal = '🥉';
                                
                                // Determine display name
                                $displayName = '';
                                if (!empty($entry['team_name'])) {
                                    $displayName = $entry['team_name'];
                                } elseif (!empty($entry['user_name'])) {
                                    $displayName = $entry['user_name'];
                                } else {
                                    $displayName = 'User #' . $entry['user_id'];
                                }
                                ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge <?= $rankClass; ?>">
                                            <?= $medal ?: $rank; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="entity-info">
                                            <div class="entity-avatar">
                                                <?= strtoupper(substr($displayName, 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="entity-name">
                                                    <?= htmlspecialchars($displayName); ?>
                                                </div>
                                                <?php if (!empty($entry['team_name']) && !empty($entry['user_name'])): ?>
                                                    <span class="entity-sub">
                                                        <i class="fa-solid fa-user"></i> <?= htmlspecialchars($entry['user_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if (!empty($entry['event_name'])): ?>
                                            <span class="event-tag">
                                                <?= htmlspecialchars($entry['event_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#999;font-size:12px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="stat-number"><?= (int)($entry['matches_played'] ?? 0); ?></td>
                                    <td class="stat-number stat-win"><?= (int)($entry['wins'] ?? 0); ?></td>
                                    <td class="stat-number stat-loss"><?= (int)($entry['losses'] ?? 0); ?></td>
                                    <td class="stat-number stat-draw"><?= (int)($entry['draws'] ?? 0); ?></td>
                                    <td style="text-align:center;">
                                        <span class="points-badge">
                                            <?= (int)($entry['points'] ?? 0); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-trophy"></i>
                    <h3>No Data Available</h3>
                    <p>The leaderboard is empty. Start adding matches to see rankings here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>