<?php
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=login_required");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';

require_once "../db_connect.php";

// Get event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    header("Location: events.php?error=invalid_event");
    exit();
}

// Check if user is registered for this event
$check_registration = $conn->prepare("
    SELECT r.*, e.event_name, e.sport_id, e.event_date, e.location, s.sport_name 
    FROM registrations r
    JOIN events e ON r.event_id = e.event_id
    LEFT JOIN sports s ON e.sport_id = s.sport_id
    WHERE r.user_id = ? AND r.event_id = ? AND r.status = 'confirmed'
");
$check_registration->bind_param("ii", $user_id, $event_id);
$check_registration->execute();
$reg_result = $check_registration->get_result();

if ($reg_result->num_rows == 0) {
    header("Location: events.php?error=not_registered");
    exit();
}

$event_data = $reg_result->fetch_assoc();
$check_registration->close();

// Check if user already has a team for this event
$check_team = $conn->prepare("
    SELECT t.*, 
           COUNT(tm.member_id) as member_count 
    FROM teams t
    LEFT JOIN team_members tm ON t.team_id = tm.team_id
    WHERE t.event_id = ? AND t.captain_id = ?
    GROUP BY t.team_id
");
$check_team->bind_param("ii", $event_id, $user_id);
$check_team->execute();
$team_result = $check_team->get_result();

if ($team_result->num_rows > 0) {
    $existing_team = $team_result->fetch_assoc();
    header("Location: manage_team.php?team_id=" . $existing_team['team_id']);
    exit();
}
$check_team->close();

// Get dark mode setting
$dark_mode = 0;
$table_check = $conn->query("SHOW TABLES LIKE 'user_settings'");
if ($table_check && $table_check->num_rows > 0) {
    $settings_sql = "SELECT dark_mode FROM user_settings WHERE user_id = ?";
    $settings_stmt = $conn->prepare($settings_sql);
    if ($settings_stmt) {
        $settings_stmt->bind_param("i", $user_id);
        $settings_stmt->execute();
        $settings_result = $settings_stmt->get_result();
        if ($settings_result->num_rows > 0) {
            $settings_data = $settings_result->fetch_assoc();
            $dark_mode = $settings_data['dark_mode'] ?? 0;
        }
        $settings_stmt->close();
    }
}

$dark_mode_class = ($dark_mode == 1) ? 'dark-mode' : '';
$data_theme = $dark_mode ? 'dark' : 'light';

// Get registered users for this event (to add as members)
$registered_users = [];
$users_sql = $conn->prepare("
    SELECT u.user_id, u.full_name, u.email 
    FROM users u
    JOIN registrations r ON u.user_id = r.user_id
    WHERE r.event_id = ? AND r.status = 'confirmed' AND u.user_id != ?
    ORDER BY u.full_name ASC
");
$users_sql->bind_param("ii", $event_id, $user_id);
$users_sql->execute();
$users_result = $users_sql->get_result();

while ($user_row = $users_result->fetch_assoc()) {
    $registered_users[] = $user_row;
}
$users_sql->close();

// Current page for sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $data_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Team | NexArena</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/theme.css">
    <link rel="stylesheet" href="assets/create_team.css">
</head>
<body>

    <div class="sidebar-wrapper">
        <?php include "sidebar.php"; ?>
    </div>

    <main class="main-content">
        <div class="create-team-container">

            <!-- Page Header -->
            <div class="create-team-header">
                <div class="header-left">
                    <a href="event_details.php?id=<?php echo $event_id; ?>" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <h1><i class="fas fa-users"></i> Create Team</h1>
                </div>
            </div>

            <!-- Event Info -->
            <div class="event-info-card">
                <div class="event-badge">
                    <span class="sport-icon">🏏</span>
                    <span><?php echo htmlspecialchars($event_data['sport_name'] ?? 'Sports'); ?></span>
                </div>
                <h2><?php echo htmlspecialchars($event_data['event_name']); ?></h2>
                <div class="event-meta">
                    <span><i class="fas fa-calendar"></i> <?php echo date("d M Y", strtotime($event_data['event_date'])); ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event_data['location'] ?? 'TBA'); ?></span>
                </div>
            </div>

            <!-- Messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Create Team Form -->
            <form action="process_create_team.php" method="POST" class="create-team-form" id="createTeamForm">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

                <!-- Team Name -->
                <div class="form-group">
                    <label for="team_name">Team Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="team_name" 
                        name="team_name" 
                        class="form-control" 
                        placeholder="Enter team name (e.g., The Titans)" 
                        required 
                        maxlength="50"
                        autofocus
                    >
                    <small class="char-count">0/50</small>
                </div>

                <!-- Search Members -->
                <div class="form-group">
                    <label>Add Team Members</label>
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
                        <div id="searchResults" class="search-results" style="display:none;"></div>
                    </div>
                    <small class="form-hint">Search for users who are already registered for this event</small>
                </div>

                <!-- Selected Members -->
                <div class="form-group">
                    <div class="selected-header">
                        <label>Selected Members</label>
                        <span class="member-count" id="memberCount">1</span>
                    </div>
                    <div class="members-list" id="membersList">
                        <div class="member-item captain" data-user-id="<?php echo $user_id; ?>">
                            <div class="member-info">
                                <span class="member-avatar">👑</span>
                                <div>
                                    <span class="member-name"><?php echo htmlspecialchars($full_name); ?></span>
                                    <span class="member-role-badge captain-badge">Captain</span>
                                </div>
                            </div>
                            <span class="member-status"><i class="fas fa-check-circle" style="color:#4CAF50;"></i></span>
                        </div>
                    </div>
                    <input type="hidden" name="members" id="selectedMembers" value="">
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <a href="event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">
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
        // Character counter
        const teamNameInput = document.getElementById('team_name');
        const charCount = document.querySelector('.char-count');
        
        teamNameInput.addEventListener('input', function() {
            charCount.textContent = this.value.length + '/50';
        });

        // Member search
        const searchInput = document.getElementById('memberSearch');
        const searchResults = document.getElementById('searchResults');
        let selectedMembers = [];
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                searchUsers(query);
            }, 300);
        });

        function searchUsers(query) {
            const eventId = <?php echo $event_id; ?>;
            
            fetch(`ajax_search_users.php?query=${encodeURIComponent(query)}&event_id=${eventId}`)
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
            
            users.forEach(user => {
                const isSelected = selectedIds.includes(user.user_id);
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

        // Add member function
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

        // Remove member function
        window.removeMember = function(userId) {
            selectedMembers = selectedMembers.filter(m => m.user_id !== userId);
            updateMembersList();
            updateHiddenInput();
            updateMemberCount();
        };

        function updateMembersList() {
            const list = document.getElementById('membersList');
            // Keep the captain
            list.innerHTML = `
                <div class="member-item captain" data-user-id="<?php echo $user_id; ?>">
                    <div class="member-info">
                        <span class="member-avatar">👑</span>
                        <div>
                            <span class="member-name"><?php echo htmlspecialchars($full_name); ?></span>
                            <span class="member-role-badge captain-badge">Captain</span>
                        </div>
                    </div>
                    <span class="member-status"><i class="fas fa-check-circle" style="color:#4CAF50;"></i></span>
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

        // Escape helpers
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function escapeJs(text) {
            return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        }

        // Form validation
        document.getElementById('createTeamForm').addEventListener('submit', function(e) {
            const teamName = document.getElementById('team_name').value.trim();
            
            if (!teamName) {
                e.preventDefault();
                alert('Please enter a team name.');
                document.getElementById('team_name').focus();
                return;
            }

            if (selectedMembers.length === 0) {
                e.preventDefault();
                alert('Please add at least one team member.');
                return;
            }

            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        });

        // Close search results on click outside
        document.addEventListener('click', function(e) {
            if (!document.querySelector('.search-container').contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    });
    </script>

    <script src="assets/theme.js"></script>
</body>
</html>