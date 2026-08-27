<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

require_once "../db_connect.php";

$user_id = (int) $_SESSION['user_id'];

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

// Fetch teams
$sql = $conn->prepare("
    SELECT
        t.team_id,
        t.team_name,
        t.event_id,
        t.captain_id,
        t.status,
        t.created_at,
        tm.role,
        tm.joined_at,
        e.event_name,
        e.event_date,
        e.location
    FROM team_members tm
    INNER JOIN teams t ON tm.team_id = t.team_id
    LEFT JOIN events e ON t.event_id = e.event_id
    WHERE tm.user_id = ?
    ORDER BY t.created_at DESC
");
if (!$sql) die("Prepare Error: " . $conn->error);
$sql->bind_param("i", $user_id);
$sql->execute();
$result = $sql->get_result();

// Handle flash messages
$successMsg = $_SESSION['success'] ?? null;
$errorMsg   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Don't close connection here - sidebar needs it
// $conn->close();
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
            max-width: 540px;
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
        [data-theme="dark"] .form-group input:focus,
        [data-theme="dark"] .form-group textarea:focus,
        [data-theme="dark"] .form-group select:focus {
            box-shadow: 0 0 0 3px rgba(255, 122, 47, 0.3);
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
        
        [data-theme="dark"] .orange-button {
            color: #ffffff;
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
                <strong><?= $result->num_rows ?></strong>
            </div>
        </section>

        <!-- Teams Grid -->
        <?php if ($result->num_rows > 0): ?>
            <section class="teams-grid">
                <?php while ($team = $result->fetch_assoc()): ?>
                    <article class="team-card">
                        <div class="team-card-header">
                            <div class="team-icon">👥</div>
                            <span class="team-status status-<?= clean(strtolower($team['status'] ?? 'active')) ?>">
                                <?= ucfirst(clean($team['status'] ?? 'Active')) ?>
                            </span>
                        </div>
                        <div class="team-content">
                            <span class="team-label">TEAM</span>
                            <h2><?= clean($team['team_name']) ?></h2>
                            <div class="team-detail">
                                <span class="detail-icon">🏆</span>
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
                            <div class="team-detail">
                                <span class="detail-icon">📅</span>
                                <div>
                                    <small>Joined</small>
                                    <strong><?= !empty($team['joined_at']) ? date("d M Y", strtotime($team['joined_at'])) : "Not available" ?></strong>
                                </div>
                            </div>
                        </div>
                        <a href="team_details.php?id=<?= (int)$team['team_id'] ?>" class="view-team-btn">View Team →</a>
                    </article>
                <?php endwhile; ?>
            </section>
        <?php else: ?>
            <section class="empty-team">
                <div class="empty-icon">👥</div>
                <h2>You Are Not In Any Team</h2>
                <p>You haven't joined a team yet. Register for an event and join a team when team registration is available.</p>
                <a href="events.php" class="orange-button">Find Events →</a>
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
    });
</script>

<!-- Theme JavaScript - MUST BE LAST -->
<script src="assets/theme.js"></script>

</body>
</html>

<?php
$sql->close();
// Don't close connection here - already closed at the end
// $conn->close();
?>