<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Filters ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// --- Stats ---
$totalSports = 0;
$activeSports = 0;
$inactiveSports = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM sports");
if ($result) { $row = $result->fetch_assoc(); $totalSports = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM sports WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $activeSports = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM sports WHERE status = 'inactive'");
if ($result) { $row = $result->fetch_assoc(); $inactiveSports = (int)$row['total']; }

// --- Get distinct categories for filter ---
$categories = [];
$catRes = $conn->query("SELECT DISTINCT category FROM sports ORDER BY category");
if ($catRes) { while ($row = $catRes->fetch_assoc()) { $categories[] = $row['category']; } }

// --- Build query ---
$sql = "SELECT * FROM sports WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (sport_name LIKE ? OR category LIKE ? OR description LIKE ?)";
    $sv = "%$search%";
    $params[] = $sv; $params[] = $sv; $params[] = $sv;
    $types .= "sss";
}
if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY sport_name ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) { die("Query error: " . $conn->error); }
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$sports = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Management | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/sports.css">
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <!-- Page Header -->
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-trophy"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Sports Management</h1>
                <p>Manage all sports, categories, and player limits.</p>
            </div>
        </div>
        <a href="add_sport.php" class="add-user-btn">
            <i class="fa-solid fa-plus"></i> Add Sport
        </a>
    </section>

    <!-- Stats -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-trophy"></i></div>
            <div class="stat-content">
                <span>Total Sports</span>
                <strong><?= number_format($totalSports); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #16a34a;">
            <div class="stat-icon" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-check-circle"></i></div>
            <div class="stat-content">
                <span>Active</span>
                <strong><?= number_format($activeSports); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #dc2626;">
            <div class="stat-icon" style="background:#fecaca; color:#dc2626;"><i class="fa-solid fa-ban"></i></div>
            <div class="stat-content">
                <span>Inactive</span>
                <strong><?= number_format($inactiveSports); ?></strong>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="filter-card">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search sports, category, description..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="select-box">
                <i class="fa-solid fa-tag"></i>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat); ?>" <?= $category === $cat ? 'selected' : ''; ?>><?= htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="select-box">
                <i class="fa-solid fa-circle-half-stroke"></i>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="sports.php" class="reset-btn"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </form>
    </section>

    <!-- Sports Table -->
    <section class="users-card">
        <div class="table-header">
            <div>
                <span class="section-label">SPORT DIRECTORY</span>
                <h2>All Sports</h2>
            </div>
            <span class="user-count"><?= count($sports); ?> sport<?= count($sports) != 1 ? 's' : ''; ?></span>
        </div>
        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Sport</th>
                        <th>Category</th>
                        <th>Players (min–max)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($sports)): ?>
                    <?php foreach ($sports as $sport): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="avatar" style="background:var(--orange); color:#fff; font-size:16px;">
                                        <i class="fa-solid fa-<?= !empty($sport['icon']) ? htmlspecialchars($sport['icon']) : 'trophy'; ?>"></i>
                                    </div>
                                    <div class="user-name">
                                        <strong><?= htmlspecialchars($sport['sport_name']); ?></strong>
                                        <small><?= htmlspecialchars(substr($sport['description'] ?? '', 0, 40)) . (strlen($sport['description'] ?? '') > 40 ? '...' : ''); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="sport-category"><?= htmlspecialchars($sport['category']); ?></span></td>
                            <td><?= (int)$sport['min_players']; ?> – <?= (int)$sport['max_players']; ?></td>
                            <td>
                                <span class="fixture-status <?= $sport['status']; ?>"><?= ucfirst($sport['status']); ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_sport.php?id=<?= (int)$sport['sport_id']; ?>" class="action-btn edit-btn" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    <a href="delete_sport.php?id=<?= (int)$sport['sport_id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this sport?');"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-trophy"></i></div>
                        <h3>No Sports Found</h3>
                        <p>Try adjusting your filters or create a new sport.</p>
                        <a href="add_sport.php" class="empty-reset"><i class="fa-solid fa-plus"></i> Create Sport</a>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>