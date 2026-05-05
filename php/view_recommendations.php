<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId = $_SESSION['user_id'];
$tier   = $_SESSION['user_tier'];

// Get user preferences
$pref = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$pref->bind_param("i", $userId);
$pref->execute();
$prefs = $pref->get_result()->fetch_assoc();

$userAllergens = json_decode($prefs['allergens'] ?? '[]', true) ?? [];
$preferredTags = json_decode($prefs['preferred_tags'] ?? '[]', true) ?? [];

// Get recommended items — exclude allergens, prioritize preferred tags, exclude VIP if not VIP
$items = $conn->query("
    SELECT i.*, inv.stock_qty
    FROM items i
    JOIN inventory inv ON inv.item_id = i.id
    WHERE i.is_active = 1
      AND inv.stock_qty > 0
      " . ($tier !== 'vip' ? "AND i.is_vip_only = 0" : "") . "
    ORDER BY i.is_limited DESC, inv.stock_qty DESC
    LIMIT 12
");

$recommendations = [];
while ($item = $items->fetch_assoc()) {
    $itemAllergens = json_decode($item['allergens'] ?? '[]', true) ?? [];
    if (!empty(array_intersect($userAllergens, $itemAllergens))) continue; // skip allergens
    $recommendations[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recommendations - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .item-card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,.07); transition: .3s; height: 100%; }
        .item-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,.12); }
        .limited-banner { background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; font-size: 11px; padding: 3px 10px; border-radius: 10px; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">📦 FreshBox</a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="mb-1">✨ Recommended For You</h3>
    <p class="text-muted">Based on your preferences — allergens filtered out automatically.</p>

    <?php if (!empty($preferredTags)): ?>
        <div class="alert alert-info small">
            Your preferred tags: <?= htmlspecialchars(implode(', ', $preferredTags)) ?>
        </div>
    <?php endif; ?>

    <div class="row g-3">
    <?php if (empty($recommendations)): ?>
        <div class="col-12 text-center text-muted py-4">
            <div style="font-size:3rem">🔍</div>
            <p>No recommendations available right now. <a href="manage_preferences.php">Update your preferences</a>.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($recommendations as $item): ?>
        <div class="col-md-3 col-sm-6">
            <div class="card item-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                        <?php if ($item['is_limited']): ?>
                            <span class="limited-banner">Limited</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-2"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-light text-dark"><?= htmlspecialchars($item['theme']) ?></span>
                        <span class="text-muted small">Stock: <?= $item['stock_qty'] ?></span>
                    </div>
                    <div class="mt-3">
                        <a href="customize_box.php" class="btn btn-outline-success btn-sm w-100">Add to Box</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <a href="manage_preferences.php" class="btn btn-outline-success">⚙️ Update My Preferences</a>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>