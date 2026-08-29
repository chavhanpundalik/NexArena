<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$sportId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($sportId <= 0) { header("Location: sports.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM sports WHERE sport_id = ?");
$stmt->bind_param("i", $sportId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: sports.php"); exit(); }
$sport = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success = false;
$form_data = $sport;

// Comprehensive sports icons with famous Indian sports
$sports_icons = [
    // 🇮🇳 Most Popular Indian Sports
    'fa-cricket-bat-ball' => 'Cricket 🏏',
    'fa-hockey-puck' => 'Hockey 🏑',
    'fa-badminton' => 'Badminton 🏸',
    'fa-tennis-ball' => 'Tennis 🎾',
    'fa-table-tennis' => 'Table Tennis 🏓',
    'fa-football' => 'Football ⚽',
    'fa-basketball' => 'Basketball 🏀',
    'fa-volleyball' => 'Volleyball 🏐',
    'fa-baseball' => 'Baseball ⚾',
    'fa-rugby-ball' => 'Rugby 🏉',
    'fa-handball' => 'Handball 🤾',
    'fa-futbol' => 'Soccer ⚽',
    
    // 🇮🇳 Traditional Indian Sports
    'fa-chess' => 'Chess ♟️',
    'fa-dice' => 'Pachisi (Chaupar) 🎲',
    'fa-puzzle-piece' => 'Snakes & Ladders 🧩',
    'fa-golf-ball' => 'Golf ⛳',
    'fa-bowling-ball' => 'Bowling 🎳',
    'fa-shield-alt' => 'Kabaddi 🛡️',
    'fa-shield-halved' => 'Kho-Kho 🏃',
    'fa-fist-raised' => 'Malla-Yuddha ✊',
    'fa-running' => 'Athletics 🏃',
    'fa-swimmer' => 'Swimming 🏊',
    'fa-dumbbell' => 'Weightlifting 🏋️',
    'fa-gymnastics' => 'Gymnastics 🤸',
    
    // 🇮🇳 Indian Martial Arts
    'fa-shield' => 'Kalaripayattu ⚔️',
    'fa-fencing' => 'Fencing 🤺',
    'fa-boxing-glove' => 'Boxing 🥊',
    'fa-shield-alt' => 'Silambam 🏏',
    'fa-crosshairs' => 'Archery 🎯',
    'fa-bullseye' => 'Target Shooting 🎯',
    
    // 🇮🇳 Racing & Adventure
    'fa-bicycle' => 'Cycling 🚴',
    'fa-motorcycle' => 'Motor Racing 🏍️',
    'fa-car' => 'Car Racing 🏎️',
    'fa-sailboat' => 'Sailing ⛵',
    'fa-ship' => 'Boating 🚣',
    'fa-horse' => 'Horse Racing 🐎',
    'fa-horse-head' => 'Equestrian 🐴',
    'fa-person-riding' => 'Horseback Riding 🏇',
    
    // 🇮🇳 Winter Sports (for international events)
    'fa-skiing' => 'Skiing ⛷️',
    'fa-snowboarding' => 'Snowboarding 🏂',
    'fa-skating' => 'Skating ⛸️',
    'fa-ice-skate' => 'Ice Skating ⛸️',
    'fa-sleigh' => 'Bobsled 🛷',
    'fa-person-skiing-nordic' => 'Nordic Skiing 🎿',
    
    // 🇮🇳 Water Sports
    'fa-surfing' => 'Surfing 🏄',
    'fa-water' => 'Water Sports 💦',
    'fa-fish' => 'Fishing 🎣',
    'fa-droplet' => 'Aquatics 💧',
    'fa-person-swimming' => 'Competitive Swimming 🏊',
    
    // 🇮🇳 Adventure Sports
    'fa-person-hiking' => 'Hiking 🥾',
    'fa-person-walking' => 'Trekking 🚶',
    'fa-person-biking' => 'Mountain Biking 🚵',
    'fa-mountain' => 'Mountaineering 🏔️',
    'fa-tree' => 'Forest Sports 🌳',
    'fa-campground' => 'Camping ⛺',
    
    // 🇮🇳 Indoor & Board Games
    'fa-chess-king' => 'Chess King ♔',
    'fa-chess-queen' => 'Chess Queen ♕',
    'fa-chess-rook' => 'Chess Rook ♖',
    'fa-chess-bishop' => 'Chess Bishop ♗',
    'fa-chess-knight' => 'Chess Knight ♘',
    'fa-chess-pawn' => 'Chess Pawn ♙',
    'fa-dice-d6' => 'Board Games 🎲',
    'fa-gamepad' => 'E-Sports 🎮',
    'fa-microchip' => 'Tech Sports 💻',
    
    // 🇮🇳 Traditional Indian Games
    'fa-circle' => 'Gilli-Danda 🔵',
    'fa-circle-dot' => 'Pitthu Garam 🔴',
    'fa-target' => 'Lagori 🎯',
    'fa-rock' => 'Kabbadi Stone 🪨',
    'fa-wind' => 'Kite Flying 🪁',
    'fa-crown' => 'Raja Rancho 👑',
    'fa-flag' => 'Flag Race 🚩',
    'fa-trophy' => 'Trophy 🏆',
    'fa-medal' => 'Medal 🥇',
    'fa-star' => 'Star ⭐',
    
    // 🇮🇳 Additional Sports
    'fa-american-football' => 'American Football 🏈',
    'fa-ping-pong' => 'Ping Pong 🏓',
    'fa-squash' => 'Squash 🎾',
    'fa-pickleball' => 'Pickleball 🏓',
    'fa-rugby' => 'Rugby Union 🏉',
    'fa-cricket' => 'Cricket World 🏏',
    'fa-badminton' => 'Badminton World 🏸',
    'fa-hockey' => 'Hockey World 🏑'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sport_name = trim($_POST['sport_name'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $min_players = (int)($_POST['min_players'] ?? 2);
    $max_players = (int)($_POST['max_players'] ?? 11);
    $status = $_POST['status'] ?? 'active';

    $form_data = compact('sport_name', 'category', 'icon', 'description', 'min_players', 'max_players', 'status');

    if (strlen($sport_name) < 2) { $errors[] = "Sport name must be at least 2 characters."; }
    if (strlen($category) < 2) { $errors[] = "Category is required."; }
    if ($min_players < 1) { $errors[] = "Minimum players must be at least 1."; }
    if ($max_players < $min_players) { $errors[] = "Maximum players cannot be less than minimum."; }
    if (!in_array($status, ['active', 'inactive'])) { $errors[] = "Invalid status."; }

    if (empty($errors)) {
        $check = $conn->prepare("SELECT sport_id FROM sports WHERE sport_name = ? AND sport_id != ?");
        $check->bind_param("si", $sport_name, $sportId);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "A sport with this name already exists.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE sports SET sport_name=?, category=?, icon=?, description=?, min_players=?, max_players=?, status=? WHERE sport_id=?");
        $stmt->bind_param("ssssiisi", $sport_name, $category, $icon, $description, $min_players, $max_players, $status, $sportId);
        if ($stmt->execute()) {
            $success = true;
            $sport = array_merge($sport, $form_data);
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sport | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/sports.css">
    <style>
        /* ========== ICON PICKER STYLES ========== */
        
        /* Main Container */
        .icon-picker-wrapper {
            margin-top: 5px;
        }

        /* Selected Icon Preview */
        .selected-icon-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 10px;
            margin-bottom: 12px;
            border: 2px dashed #cbd5e1;
            transition: all 0.3s ease;
        }

        .selected-icon-preview:hover {
            border-color: #f97316;
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
        }

        .selected-icon-preview i {
            font-size: 36px;
            color: #f97316;
            width: 40px;
            text-align: center;
        }

        .selected-icon-preview .icon-info {
            flex: 1;
        }

        .selected-icon-preview #selectedIconName {
            font-weight: 700;
            color: #1f2937;
            font-size: 16px;
        }

        .selected-icon-preview .icon-label {
            color: #64748b;
            font-size: 13px;
            font-weight: normal;
            margin-left: 8px;
        }

        .selected-icon-preview .icon-hint {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 2px;
        }

        .clear-icon-btn {
            padding: 6px 16px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            white-space: nowrap;
        }

        .clear-icon-btn:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        .clear-icon-btn i {
            font-size: 12px;
            color: white !important;
        }

        /* Search Bar */
        .icon-search {
            margin-bottom: 12px;
            position: relative;
        }

        .icon-search input {
            width: 100%;
            padding: 10px 15px 10px 42px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .icon-search input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .icon-search input::placeholder {
            color: #94a3b8;
        }

        .icon-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
        }

        /* Category Filters */
        .icon-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
            padding: 8px 0;
        }

        .category-btn {
            padding: 6px 16px;
            background: #f1f5f9;
            border: 2px solid transparent;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #475569;
            font-weight: 600;
        }

        .category-btn:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .category-btn.active {
            background: #f97316;
            color: white;
            border-color: #f97316;
        }

        .category-btn.active:hover {
            background: #ea580c;
        }

        /* Icon Grid */
        .icon-picker {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            max-height: 350px;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .icon-picker::-webkit-scrollbar {
            width: 8px;
        }

        .icon-picker::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .icon-picker::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .icon-picker::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Individual Icon Option */
        .icon-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 5px;
            border: 2px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.25s ease;
            background: white;
            min-height: 75px;
            position: relative;
        }

        .icon-option:hover {
            border-color: #f97316;
            background: #fff7ed;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.15);
        }

        .icon-option.selected {
            border-color: #f97316;
            background: #fff7ed;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.2), 0 4px 12px rgba(249, 115, 22, 0.1);
        }

        .icon-option i {
            font-size: 28px;
            color: #334155;
            transition: all 0.25s ease;
        }

        .icon-option.selected i {
            color: #f97316;
            transform: scale(1.1);
        }

        .icon-option:hover i {
            color: #f97316;
            transform: scale(1.1);
        }

        .icon-option .icon-name {
            font-size: 9px;
            margin-top: 5px;
            color: #64748b;
            text-align: center;
            line-height: 1.2;
            max-width: 75px;
            word-wrap: break-word;
            font-weight: 500;
        }

        .icon-option.selected .icon-name {
            color: #f97316;
            font-weight: 700;
        }

        .icon-option .icon-emoji {
            position: absolute;
            top: -4px;
            right: -4px;
            font-size: 12px;
        }

        /* Info Badge */
        .icon-info-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 8px 12px;
            background: #f1f5f9;
            border-radius: 6px;
            color: #64748b;
            font-size: 13px;
        }

        .icon-info-badge i {
            color: #f97316;
            font-size: 14px;
        }

        .icon-count {
            margin-left: auto;
            background: #e2e8f0;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
            color: #475569;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .icon-picker {
                grid-template-columns: repeat(auto-fill, minmax(65px, 1fr));
                max-height: 280px;
                padding: 10px;
                gap: 8px;
            }
            
            .selected-icon-preview {
                flex-wrap: wrap;
                padding: 12px 15px;
                gap: 10px;
            }
            
            .selected-icon-preview i {
                font-size: 28px;
                width: 30px;
            }

            .clear-icon-btn {
                padding: 5px 12px;
                font-size: 12px;
                width: 100%;
                justify-content: center;
            }

            .icon-categories {
                gap: 4px;
            }
            
            .category-btn {
                padding: 4px 12px;
                font-size: 11px;
            }
            
            .icon-option {
                min-height: 60px;
                padding: 8px 4px;
            }
            
            .icon-option i {
                font-size: 22px;
            }
            
            .icon-option .icon-name {
                font-size: 8px;
                max-width: 60px;
            }

            .selected-icon-preview #selectedIconName {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .icon-picker {
                grid-template-columns: repeat(auto-fill, minmax(55px, 1fr));
                padding: 8px;
                gap: 6px;
                max-height: 250px;
            }
            
            .icon-option {
                min-height: 50px;
                padding: 6px 3px;
            }
            
            .icon-option i {
                font-size: 18px;
            }
            
            .icon-option .icon-name {
                font-size: 7px;
                max-width: 50px;
            }

            .selected-icon-preview {
                padding: 10px 12px;
            }

            .selected-icon-preview i {
                font-size: 24px;
                width: 25px;
            }

            .category-btn {
                padding: 3px 10px;
                font-size: 10px;
            }

            .icon-search input {
                padding: 8px 12px 8px 35px;
                font-size: 12px;
            }

            .icon-search i {
                left: 10px;
                font-size: 14px;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-option {
            animation: fadeIn 0.3s ease forwards;
        }

        .icon-option:nth-child(1) { animation-delay: 0.02s; }
        .icon-option:nth-child(2) { animation-delay: 0.04s; }
        .icon-option:nth-child(3) { animation-delay: 0.06s; }
        /* ... continues for all icons */
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-pen-to-square"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Edit Sport</h1>
                <p>Update details for <strong><?= htmlspecialchars($sport['sport_name']); ?></strong></p>
            </div>
        </div>
        <a href="sports.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Sports
        </a>
    </section>
    <div class="form-card">
        <h2><i class="fa-regular fa-pen-to-square" style="color:var(--orange);"></i> Edit Sport</h2>
        <p class="subtitle">Modify the sport information below.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Sport updated successfully! <a href="sports.php" style="color:var(--orange);font-weight:700;text-decoration:none;margin-left:10px;">View all sports →</a></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Please fix: <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $sportId; ?>">

            <div class="form-group">
                <label for="sport_name">Sport Name <span class="required">*</span></label>
                <input type="text" id="sport_name" name="sport_name" value="<?= htmlspecialchars($form_data['sport_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Category <span class="required">*</span></label>
                <input type="text" id="category" name="category" value="<?= htmlspecialchars($form_data['category']); ?>" required>
            </div>

            <div class="form-group">
                <label>Select Sport Icon <span style="color: #64748b; font-weight: normal;">(Click to choose)</span></label>
                
                <!-- Hidden input for icon value -->
                <input type="hidden" id="icon" name="icon" value="<?= htmlspecialchars($form_data['icon']); ?>">
                
                <!-- Selected Icon Preview -->
                <div class="selected-icon-preview">
                    <i class="fas <?= htmlspecialchars($form_data['icon'] ?: 'fa-circle'); ?>" id="previewIcon"></i>
                    <div class="icon-info">
                        <span id="selectedIconName"><?= htmlspecialchars($form_data['icon'] ? $sports_icons[$form_data['icon']] ?? 'Custom' : 'No icon selected'); ?></span>
                        <span class="icon-label">(currently selected)</span>
                        <span class="icon-hint">Click an icon below to change</span>
                    </div>
                    <button type="button" class="clear-icon-btn" onclick="clearIcon()">
                        <i class="fa-solid fa-times"></i> Clear
                    </button>
                </div>

                <div class="icon-picker-wrapper">
                    <!-- Search -->
                    <div class="icon-search">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="iconSearch" placeholder="Search sports icons..." onkeyup="filterIcons()">
                    </div>

                    <!-- Category Filters -->
                    <div class="icon-categories">
                        <button type="button" class="category-btn active" onclick="filterCategory('all')">🇮🇳 All</button>
                        <button type="button" class="category-btn" onclick="filterCategory('popular')">⭐ Popular</button>
                        <button type="button" class="category-btn" onclick="filterCategory('traditional')">🏛️ Traditional</button>
                        <button type="button" class="category-btn" onclick="filterCategory('martial')">⚔️ Martial Arts</button>
                        <button type="button" class="category-btn" onclick="filterCategory('water')">🌊 Water</button>
                        <button type="button" class="category-btn" onclick="filterCategory('indoor')">🏠 Indoor</button>
                        <button type="button" class="category-btn" onclick="filterCategory('adventure')">🏔️ Adventure</button>
                    </div>

                    <!-- Icon Grid -->
                    <div class="icon-picker" id="iconPicker">
                        <?php 
                        $current_icon = $form_data['icon'];
                        foreach ($sports_icons as $icon_class => $icon_name): 
                            $selected = ($icon_class === $current_icon) ? 'selected' : '';
                            // Extract emoji from icon name
                            $emoji = '';
                            if (preg_match('/[🇮🇳⭐🏏🏑🏸🎾🏓⚽🏀🏐⚾🏉🤾♟️🎲🧩⛳🎳🛡️🏃🏊🏋️🤸⚔️🤺🥊🎯🚴🏍️🏎️⛵🚣🐎🐴🏇⛷️🏂⛸️🛷🎿🏄💦🎣💧🥾🚶🚵🏔️🌳⛺♔♕♖♗♘♙🎮💻🔵🔴🎯🪨🪁👑🚩🏆🥇⭐]/u', $icon_name, $matches)) {
                                $emoji = $matches[0];
                            }
                        ?>
                            <div class="icon-option <?= $selected; ?>" 
                                 data-icon="<?= $icon_class; ?>" 
                                 data-name="<?= htmlspecialchars(strtolower($icon_name)); ?>"
                                 onclick="selectIcon('<?= $icon_class; ?>', '<?= htmlspecialchars($icon_name); ?>')">
                                <i class="fas <?= $icon_class; ?>"></i>
                                <span class="icon-name"><?= htmlspecialchars(preg_replace('/[🇮🇳⭐🏏🏑🏸🎾🏓⚽🏀🏐⚾🏉🤾♟️🎲🧩⛳🎳🛡️🏃🏊🏋️🤸⚔️🤺🥊🎯🚴🏍️🏎️⛵🚣🐎🐴🏇⛷️🏂⛸️🛷🎿🏄💦🎣💧🥾🚶🚵🏔️🌳⛺♔♕♖♗♘♙🎮💻🔵🔴🎯🪨🪁👑🚩🏆🥇⭐]/u', '', $icon_name)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Info Badge -->
                    <div class="icon-info-badge">
                        <i class="fa-solid fa-info-circle"></i>
                        <span>Browse through <?= count($sports_icons); ?> sports icons</span>
                        <span class="icon-count" id="iconCount"><?= count($sports_icons); ?></span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars($form_data['description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="min_players">Min Players <span class="required">*</span></label>
                    <input type="number" id="min_players" name="min_players" min="1" value="<?= (int)$form_data['min_players']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="max_players">Max Players <span class="required">*</span></label>
                    <input type="number" id="max_players" name="max_players" min="1" value="<?= (int)$form_data['max_players']; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="active" <?= $form_data['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= $form_data['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Update Sport</button>
                <a href="sports.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
    // ========== ICON PICKER JAVASCRIPT ==========
    
    // Select an icon
    function selectIcon(iconClass, iconName) {
        // Update hidden input
        document.getElementById('icon').value = iconClass;
        
        // Update preview
        const previewIcon = document.getElementById('previewIcon');
        previewIcon.className = 'fas ' + iconClass;
        document.getElementById('selectedIconName').textContent = iconName;
        
        // Update selected state in grid
        document.querySelectorAll('.icon-option').forEach(option => {
            option.classList.remove('selected');
            if (option.dataset.icon === iconClass) {
                option.classList.add('selected');
                option.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
        // Clear search if any
        document.getElementById('iconSearch').value = '';
        filterIcons();
    }

    // Clear icon selection
    function clearIcon() {
        document.getElementById('icon').value = '';
        document.getElementById('previewIcon').className = 'fas fa-circle';
        document.getElementById('selectedIconName').textContent = 'No icon selected';
        
        document.querySelectorAll('.icon-option').forEach(option => {
            option.classList.remove('selected');
        });
    }

    // Filter icons by search
    function filterIcons() {
        const searchTerm = document.getElementById('iconSearch').value.toLowerCase().trim();
        const options = document.querySelectorAll('.icon-option');
        let visibleCount = 0;
        
        options.forEach(option => {
            const iconName = option.dataset.name.toLowerCase();
            if (iconName.includes(searchTerm) || searchTerm === '') {
                option.style.display = 'flex';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        });
        
        // Update count
        document.getElementById('iconCount').textContent = visibleCount;
    }

    // Filter icons by category
    function filterCategory(category) {
        // Update active button
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Clear search
        document.getElementById('iconSearch').value = '';
        
        // Show all icons
        const options = document.querySelectorAll('.icon-option');
        let visibleCount = 0;
        
        options.forEach(option => {
            const iconName = option.dataset.name.toLowerCase();
            let show = false;
            
            switch(category) {
                case 'all':
                    show = true;
                    break;
                case 'popular':
                    const popularSports = ['cricket', 'hockey', 'badminton', 'tennis', 'football', 'basketball', 'volleyball', 'kabaddi'];
                    show = popularSports.some(sport => iconName.includes(sport));
                    break;
                case 'traditional':
                    const traditionalSports = ['chess', 'pachisi', 'snakes', 'ladders', 'gilli', 'danda', 'kho-kho', 'kabaddi'];
                    show = traditionalSports.some(sport => iconName.includes(sport));
                    break;
                case 'martial':
                    const martialSports = ['kalaripayattu', 'fencing', 'boxing', 'silambam', 'archery', 'shooting', 'malla'];
                    show = martialSports.some(sport => iconName.includes(sport));
                    break;
                case 'water':
                    const waterSports = ['swimming', 'sailing', 'boating', 'surfing', 'fishing', 'aquatics', 'water'];
                    show = waterSports.some(sport => iconName.includes(sport));
                    break;
                case 'indoor':
                    const indoorSports = ['chess', 'table tennis', 'badminton', 'squash', 'bowling', 'snooker', 'board games', 'e-sports'];
                    show = indoorSports.some(sport => iconName.includes(sport));
                    break;
                case 'adventure':
                    const adventureSports = ['hiking', 'trekking', 'mountain', 'camping', 'biking', 'cycling', 'racing', 'skating', 'skiing'];
                    show = adventureSports.some(sport => iconName.includes(sport));
                    break;
                default:
                    show = true;
            }
            
            if (show) {
                option.style.display = 'flex';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        });
        
        document.getElementById('iconCount').textContent = visibleCount;
    }

    // Auto-select on load
    document.addEventListener('DOMContentLoaded', function() {
        const currentIcon = document.getElementById('icon').value;
        if (currentIcon) {
            document.querySelectorAll('.icon-option').forEach(option => {
                if (option.dataset.icon === currentIcon) {
                    option.classList.add('selected');
                }
            });
        }
    });
</script>
</body>
</html>