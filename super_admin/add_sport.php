<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$errors = [];
$success = false;
$form_data = [
    'sport_name' => '',
    'category' => 'Other',
    'icon' => '',
    'description' => '',
    'min_players' => 2,
    'max_players' => 11,
    'status' => 'active'
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

    // Check duplicate sport name
    if (empty($errors)) {
        $check = $conn->prepare("SELECT sport_id FROM sports WHERE sport_name = ?");
        $check->bind_param("s", $sport_name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "A sport with this name already exists.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO sports (sport_name, category, icon, description, min_players, max_players, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiis", $sport_name, $category, $icon, $description, $min_players, $max_players, $status);
        if ($stmt->execute()) {
            $success = true;
            $form_data = ['sport_name' => '', 'category' => 'Other', 'icon' => '', 'description' => '', 'min_players' => 2, 'max_players' => 11, 'status' => 'active'];
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
    <title>Add Sport | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/sports.css">
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-plus-circle"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Add Sport</h1>
                <p>Create a new sport for events and fixtures.</p>
            </div>
        </div>
        <a href="sports.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Sports
        </a>
    </section>
    <div class="form-card">
        <h2><i class="fa-regular fa-pen-to-square" style="color:var(--orange);"></i> Sport Details</h2>
        <p class="subtitle">Fill in all information for the sport.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Sport created successfully! <a href="sports.php" style="color:var(--orange);font-weight:700;text-decoration:none;margin-left:10px;">View all sports →</a></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Please fix: <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="sport_name">Sport Name <span class="required">*</span></label>
                <input type="text" id="sport_name" name="sport_name" value="<?= htmlspecialchars($form_data['sport_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Category <span class="required">*</span></label>
                <input type="text" id="category" name="category" value="<?= htmlspecialchars($form_data['category']); ?>" placeholder="e.g., Team Sports, Esports, Individual" required>
            </div>

            <div class="form-group">
                <label for="icon">Icon (Font Awesome class)</label>
                <input type="text" id="icon" name="icon" placeholder="e.g., fa-futbol, fa-basketball" value="<?= htmlspecialchars($form_data['icon']); ?>">
                <small style="color:#71717a;">e.g., <code>fa-futbol</code>, <code>fa-basketball</code>, <code>fa-gamepad</code>. Leave blank for default.</small>
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
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Create Sport</button>
                <a href="sports.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>