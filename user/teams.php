<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

require_once "../db_connect.php";

$user_id = (int) $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

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

// Fetch teams with member count
$sql = $conn->prepare("
    SELECT
        t.team_id,
        t.team_name,
        t.event_id,
        t.captain_id,
        t.status,
        t.max_players,
        t.created_at,
        tm.role,
        tm.joined_at,
        e.event_name,
        e.event_date,
        e.location,
        s.sport_name,
        (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id) as member_count
    FROM team_members tm
    INNER JOIN teams t ON tm.team_id = t.team_id
    LEFT JOIN events e ON t.event_id = e.event_id
    LEFT JOIN sports s ON t.sport_id = s.sport_id
    WHERE tm.user_id = ?
    ORDER BY 
        CASE WHEN t.captain_id = ? THEN 0 ELSE 1 END,
        t.created_at DESC
");
if (!$sql) die("Prepare Error: " . $conn->error);
$sql->bind_param("ii", $user_id, $user_id);
$sql->execute();
$result = $sql->get_result();
$teams = $result->fetch_all(MYSQLI_ASSOC);
$sql->close();

// Handle flash messages
$successMsg = $_SESSION['success'] ?? null;
$errorMsg   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Check if we're viewing a specific team (AJAX for members)
$view_team_id = isset($_GET['view_team']) ? (int)$_GET['view_team'] : 0;
$team_members = [];
$selected_team = null;

if ($view_team_id > 0) {
    // Get team details
    $team_sql = $conn->prepare("
        SELECT t.*, e.event_name, e.event_date, e.location, s.sport_name 
        FROM teams t
        LEFT JOIN events e ON t.event_id = e.event_id
        LEFT JOIN sports s ON t.sport_id = s.sport_id
        WHERE t.team_id = ?
    ");
    $team_sql->bind_param("i", $view_team_id);
    $team_sql->execute();
    $team_result = $team_sql->get_result();
    $selected_team = $team_result->fetch_assoc();
    $team_sql->close();
    
    if ($selected_team) {
        // Get team members
        $members_sql = $conn->prepare("
            SELECT u.user_id, u.full_name, u.email, tm.joined_at, tm.role 
            FROM team_members tm
            JOIN users u ON tm.user_id = u.user_id
            WHERE tm.team_id = ?
            ORDER BY 
                CASE WHEN tm.role = 'captain' THEN 0 ELSE 1 END,
                tm.joined_at ASC
        ");
        $members_sql->bind_param("i", $view_team_id);
        $members_sql->execute();
        $members_result = $members_sql->get_result();
        $team_members = $members_result->fetch_all(MYSQLI_ASSOC);
        $members_sql->close();
        
        // Get available users for adding (only if user is captain)
        if ($selected_team['captain_id'] == $user_id) {
            $available_sql = $conn->prepare("
                SELECT u.user_id, u.full_name, u.email 
                FROM users u
                JOIN registrations r ON u.user_id = r.user_id
                WHERE r.event_id = ? 
                AND u.user_id NOT IN (
                    SELECT user_id FROM team_members WHERE team_id = ?
                )
                ORDER BY u.full_name ASC
            ");
            $available_sql->bind_param("ii", $selected_team['event_id'], $view_team_id);
            $available_sql->execute();
            $available_result = $available_sql->get_result();
            $available_users = $available_result->fetch_all(MYSQLI_ASSOC);
            $available_sql->close();
        } else {
            $available_users = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Teams | NexArena</title>
    
    <!-- Theme CSS - MUST BE FIRST -->
    <link rel="stylesheet" href="assets/theme.css">
    
    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/team.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* ---------- MODAL & ADD-ON STYLES ---------- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(2px);
            padding: 20px;
        }
        [data-theme="dark"] .modal-overlay {
            background: rgba(0, 0, 0, 0.7);
        }
        .modal-box {
            background: var(--bg-card);
            border-radius: 24px;
            max-width: 600px;
            width: 100%;
            padding: 28px 30px 36px;
            box-shadow: var(--shadow-lg);
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--border-primary);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .modal-header h2 {
            font-size: 22px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }
        .modal-close {
            background: transparent;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0 8px;
        }
        .modal-close:hover { color: var(--text-primary); }

        /* Team Management Styles */
        .team-management-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid var(--border-primary);
        }

        .team-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .team-management-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .team-management-header h2 i {
            color: var(--orange);
            margin-right: 8px;
        }

        .close-team-view {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            padding: 8px 20px;
            border-radius: 8px;
            color: var(--text-primary);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
        }

        .close-team-view:hover {
            background: var(--border-primary);
        }

        /* Members Grid */
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .member-card {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid var(--border-primary);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s;
        }

        .member-card:hover {
            border-color: var(--orange);
            transform: translateY(-2px);
        }

        .member-card.captain {
            background: var(--orange-light);
            border-color: var(--orange);
        }

        .member-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--orange);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .member-card.captain .member-avatar {
            background: #f59e0b;
        }

        .member-info {
            flex: 1;
        }

        .member-info .member-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .member-info .member-email {
            font-size: 12px;
            color: var(--text-muted);
            display: block;
        }

        .member-role-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 12px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .member-role-badge.captain {
            background: #f59e0b;
            color: #fff;
        }

        .member-role-badge.player {
            background: var(--orange);
            color: #fff;
        }

        .remove-member-btn {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 18px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .remove-member-btn:hover {
            background: var(--danger-bg);
        }

        /* Add Player Section */
        .add-player-section {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid var(--border-primary);
        }

        .add-player-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .add-player-section .search-container {
            position: relative;
        }

        .add-player-section .search-input-wrapper {
            position: relative;
        }

        .add-player-section .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .add-player-section .search-input-wrapper input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .add-player-section .search-input-wrapper input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
        }

        .search-results {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }

        .search-result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border-primary);
            transition: background 0.2s;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background: var(--bg-secondary);
        }

        .search-result-item .user-info strong {
            font-size: 14px;
            color: var(--text-primary);
        }

        .search-result-item .user-info .user-email {
            font-size: 12px;
            color: var(--text-muted);
            display: block;
        }

        .add-player-btn {
            padding: 4px 16px;
            border-radius: 20px;
            border: 2px solid var(--orange);
            background: transparent;
            color: var(--orange);
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .add-player-btn:hover {
            background: var(--orange);
            color: #fff;
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

        .form-hint {
            display: block;
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .team-stats-mini {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .team-stats-mini span {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .team-stats-mini strong {
            color: var(--text-primary);
        }

        .empty-members {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-muted);
        }

        .empty-members i {
            font-size: 36px;
            display: block;
            margin-bottom: 10px;
            color: var(--border-primary);
        }

        /* Form group for modal */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 15px;
            outline: none;
            transition: border 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255, 122, 47, 0.15);
        }

        .toggle-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 4px;
        }
        .toggle-wrap span {
            font-size: 14px;
            color: var(--text-muted);
        }
        .toggle {
            position: relative;
            width: 48px;
            height: 26px;
            background: var(--border-input);
            border-radius: 40px;
            cursor: pointer;
            border: 1px solid var(--border-primary);
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

        .btn-create-team {
            width: 100%;
            padding: 14px;
            background: var(--orange);
            border: none;
            border-radius: 40px;
            color: #ffffff;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            margin-top: 8px;
        }
        .btn-create-team:hover {
            background: var(--orange-hover);
            transform: scale(1.01);
        }

        /* Existing styles */
        .btn-create-header {
            background: var(--orange);
            border: none;
            color: #fff;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 15px;
        }
        .btn-create-header:hover {
            background: var(--orange-hover);
        }

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

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .orange-button {
            background: var(--orange);
            color: #fff;
            padding: 10px 24px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        .orange-button:hover {
            background: var(--orange-hover);
        }

        /* Team Card Styles */
        .team-card .view-team-btn {
            display: inline-block;
            padding: 8px 20px;
            background: var(--orange);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            margin-top: 12px;
        }

        .team-card .view-team-btn:hover {
            background: var(--orange-hover);
            transform: translateY(-2px);
        }

        .team-card .member-count-badge {
            display: inline-block;
            padding: 2px 10px;
            background: var(--bg-secondary);
            border-radius: 12px;
            font-size: 12px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">

<?php include "sidebar.php"; ?>

<main class="team-main">
    <div class="team-container">

        <!-- Flash Messages -->
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= clean($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?= clean($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Page Header -->
        <section class="page-header">
            <div>
                <span class="page-label">NEXARENA</span>
                <h1>My Teams</h1>
                <p>View the teams you belong to and manage your team participation.</p>
            </div>
            <div class="header-actions">
                <a href="events.php" class="orange-button">Explore Events →</a>
                <button id="openCreateTeamBtn" class="btn-create-header">+ Create Team</button>
            </div>
        </section>

        <!-- Team Count -->
        <section class="team-summary">
            <div class="summary-icon">👥</div>
            <div>
                <span>MY TEAMS</span>
                <strong><?= count($teams) ?></strong>
            </div>
        </section>

        <!-- Teams Grid -->
        <?php if (!empty($teams)): ?>
            <section class="teams-grid">
                <?php foreach ($teams as $team): ?>
                    <article class="team-card">
                        <div class="team-card-header">
                            <div class="team-icon">👥</div>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <span class="member-count-badge">
                                    <i class="fas fa-users"></i> <?= $team['member_count'] ?? 0 ?> members
                                </span>
                                <span class="team-status status-<?= clean(strtolower($team['status'] ?? 'active')) ?>">
                                    <?= ucfirst(clean($team['status'] ?? 'Active')) ?>
                                </span>
                            </div>
                        </div>
                        <div class="team-content">
                            <span class="team-label">TEAM</span>
                            <h2><?= clean($team['team_name']) ?></h2>
                            <div class="team-detail">
                                <span class="detail-icon">🏆</span>
                                <div>
                                    <small>Sport</small>
                                    <strong><?= !empty($team['sport_name']) ? clean($team['sport_name']) : "Not specified" ?></strong>
                                </div>
                            </div>
                            <div class="team-detail">
                                <span class="detail-icon">📅</span>
                                <div>
                                    <small>Event</small>
                                    <strong><?= !empty($team['event_name']) ? clean($team['event_name']) : "Event not available" ?></strong>
                                </div>
                            </div>
                            <div class="team-detail">
                                <span class="detail-icon">👤</span>
                                <div>
                                    <small>Your Role</small>
                                    <strong><?= ucfirst(clean($team['role'] ?? 'Member')) ?></strong>
                                </div>
                            </div>
                            <?php if ($team['role'] == 'captain'): ?>
                                <div style="margin-top: 6px;">
                                    <span style="font-size: 12px; color: var(--orange);">
                                        <i class="fas fa-crown"></i> You are the Captain
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="?view_team=<?= (int)$team['team_id'] ?>" class="view-team-btn">
                            <i class="fas fa-users-cog"></i> Manage Team →
                        </a>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <section class="empty-team">
                <div class="empty-icon">👥</div>
                <h2>You Are Not In Any Team</h2>
                <p>You haven't joined a team yet. Register for an event and join a team when team registration is available.</p>
                <a href="events.php" class="orange-button">Find Events →</a>
            </section>
        <?php endif; ?>

        <!-- Team Management Section (when viewing a specific team) -->
        <?php if ($view_team_id > 0 && $selected_team): ?>
            <section class="team-management-section">
                <div class="team-management-header">
                    <h2>
                        <i class="fas fa-users"></i> 
                        <?= clean($selected_team['team_name']) ?> - Team Management
                    </h2>
                    <a href="my_teams.php" class="close-team-view">
                        <i class="fas fa-times"></i> Close
                    </a>
                </div>

                <!-- Team Stats -->
                <div class="team-stats-mini">
                    <span><strong><?= count($team_members) ?></strong> Total Members</span>
                    <span><strong><?= $selected_team['max_players'] ?? '∞' ?></strong> Max Players</span>
                    <span><i class="fas fa-trophy"></i> <?= clean($selected_team['sport_name'] ?? 'N/A') ?></span>
                    <span><i class="fas fa-calendar"></i> <?= date("d M Y", strtotime($selected_team['event_date'] ?? 'now')) ?></span>
                </div>

                <!-- Members List -->
                <h3 style="margin-bottom: 12px; font-size: 16px; color: var(--text-primary);">
                    <i class="fas fa-user-friends"></i> Team Members
                </h3>

                <?php if (!empty($team_members)): ?>
                    <div class="members-grid">
                        <?php foreach ($team_members as $member): ?>
                            <div class="member-card <?= $member['role'] == 'captain' ? 'captain' : ''; ?>">
                                <div class="member-avatar">
                                    <?= strtoupper(substr($member['full_name'], 0, 1)); ?>
                                </div>
                                <div class="member-info">
                                    <span class="member-name">
                                        <?= clean($member['full_name']); ?>
                                        <?php if ($member['user_id'] == $user_id): ?>
                                            <span style="font-size: 11px; color: var(--orange);">(You)</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="member-email"><?= clean($member['email']); ?></span>
                                    <span class="member-role-badge <?= $member['role']; ?>">
                                        <?= ucfirst($member['role']); ?>
                                    </span>
                                </div>
                                <?php 
                                $is_captain = ($selected_team['captain_id'] == $user_id);
                                if ($is_captain && $member['user_id'] != $user_id && $member['role'] != 'captain'): 
                                ?>
                                    <form method="POST" action="remove_player.php" onsubmit="return confirm('Remove this player from the team?');" style="display:inline;">
                                        <input type="hidden" name="team_id" value="<?= $view_team_id; ?>">
                                        <input type="hidden" name="user_id" value="<?= $member['user_id']; ?>">
                                        <button type="submit" class="remove-member-btn" title="Remove Player">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-members">
                        <i class="fas fa-user-slash"></i>
                        <p>No members in this team yet.</p>
                    </div>
                <?php endif; ?>

                <!-- Add Player Section (Only for Captain) -->
                <?php if ($selected_team['captain_id'] == $user_id): ?>
                    <div class="add-player-section">
                        <h3><i class="fas fa-user-plus"></i> Add Players</h3>
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input 
                                    type="text" 
                                    id="playerSearch" 
                                    placeholder="Search registered users by name or email..."
                                    autocomplete="off"
                                >
                            </div>
                            <div id="searchResults" class="search-results"></div>
                        </div>
                        <small class="form-hint">
                            <i class="fas fa-info-circle"></i> 
                            Search for users who are registered for this event but not yet in your team.
                        </small>

                        <!-- Hidden form for adding players -->
                        <form id="addPlayerForm" action="add_player.php" method="POST" style="display:none;">
                            <input type="hidden" name="team_id" value="<?= $view_team_id; ?>">
                            <input type="hidden" name="user_id" id="addPlayerUserId" value="">
                        </form>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

    </div>
</main>

<!-- Create Team Modal -->
<div id="createTeamModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2><i class="fas fa-users-plus"></i> Create New Team</h2>
            <button class="modal-close" id="closeModalBtn">&times;</button>
        </div>
        <form id="createTeamForm" action="create_team.php" method="POST">
            <div class="form-group">
                <label for="teamName">Team Name *</label>
                <input type="text" id="teamName" name="team_name" required placeholder="e.g. Phoenix Rising">
            </div>
            <div class="form-group">
                <label for="teamDesc">Description</label>
                <textarea id="teamDesc" name="description" rows="3" placeholder="What's your team about?"></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="teamGame">Game</label>
                    <select id="teamGame" name="game">
                        <option value="Valorant">Valorant</option>
                        <option value="Counter-Strike 2">Counter-Strike 2</option>
                        <option value="Dota 2">Dota 2</option>
                        <option value="League of Legends">League of Legends</option>
                        <option value="Apex Legends">Apex Legends</option>
                        <option value="Rocket League">Rocket League</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="teamRegion">Region</label>
                    <select id="teamRegion" name="region">
                        <option value="NA">North America</option>
                        <option value="EU">Europe</option>
                        <option value="AS">Asia</option>
                        <option value="SA">South America</option>
                        <option value="OC">Oceania</option>
                        <option value="AF">Africa</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Visibility</label>
                <div class="toggle-wrap">
                    <span>Public</span>
                    <div class="toggle" id="visibilityToggle">
                        <div class="knob"></div>
                    </div>
                    <span>Private</span>
                </div>
                <input type="hidden" name="is_private" id="is_private" value="0">
                <small style="color: var(--text-muted);">Private teams are invite‑only.</small>
            </div>
            <button type="submit" class="btn-create-team">Create Team</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============================================================
        // CREATE TEAM MODAL
        // ============================================================
        const modal = document.getElementById('createTeamModal');
        const openBtn = document.getElementById('openCreateTeamBtn');
        const closeBtn = document.getElementById('closeModalBtn');

        if (openBtn) {
            openBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (modal) modal.style.display = 'flex';
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                if (modal) modal.style.display = 'none';
            });
        }

        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        const toggle = document.getElementById('visibilityToggle');
        const hiddenInput = document.getElementById('is_private');
        if (toggle && hiddenInput) {
            toggle.addEventListener('click', function() {
                this.classList.toggle('active');
                hiddenInput.value = this.classList.contains('active') ? '1' : '0';
            });
        }

        // ============================================================
        // PLAYER SEARCH (for adding players)
        // ============================================================
        const searchInput = document.getElementById('playerSearch');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    searchPlayers(query);
                }, 300);
            });

            // Close search results on click outside
            document.addEventListener('click', function(e) {
                const container = document.querySelector('.search-container');
                if (container && !container.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
        }

        function searchPlayers(query) {
            const teamId = <?= $view_team_id ?>;
            const eventId = <?= $selected_team['event_id'] ?? 0 ?>;
            
            fetch(`search_users.php?query=${encodeURIComponent(query)}&team_id=${teamId}&event_id=${eventId}`)
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
            
            users.forEach(user => {
                html += `
                    <div class="search-result-item">
                        <div class="user-info">
                            <strong>${escapeHtml(user.full_name)}</strong>
                            <span class="user-email">${escapeHtml(user.email)}</span>
                        </div>
                        <button 
                            type="button" 
                            class="add-player-btn"
                            onclick="addPlayer(${user.user_id})"
                        >
                            + Add Player
                        </button>
                    </div>
                `;
            });
            
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
        }

        // ============================================================
        // ADD PLAYER
        // ============================================================
        window.addPlayer = function(userId) {
            document.getElementById('addPlayerUserId').value = userId;
            document.getElementById('addPlayerForm').submit();
        };

        // ============================================================
        // ESCAPE HELPER
        // ============================================================
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
</script>

<!-- Theme JavaScript - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>
</html>

<?php
// Don't close connection here - already closed at the end
// $conn->close();
?>