<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId = $_SESSION['user_id'];

// Get active box
$boxStmt = $conn->prepare("
    SELECT b.*, us.plan_id
    FROM boxes b
    JOIN user_subscriptions us ON us.id = b.subscription_id
    WHERE b.user_id = ? AND b.status IN ('pending','customizing')
    ORDER BY b.created_at DESC LIMIT 1
");
$boxStmt->bind_param("i", $userId);
$boxStmt->execute();
$box = $boxStmt->get_result()->fetch_assoc();

// Check if box is locked
$isLocked = $box && strtotime($box['lock_at']) <= time();

// Handle swap/add
$successMsg = '';
$errorMsg   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked && $box) {
    $action = $_POST['action'] ?? '';
    $itemId = (int) ($_POST['item_id'] ?? 0);

    if ($action === 'add' && $itemId > 0) {
        // Check allergens
        $prefStmt = $conn->prepare("SELECT allergens FROM user_preferences WHERE user_id = ?");
        $prefStmt->bind_param("i", $userId);
        $prefStmt->execute();
        $pref = $prefStmt->get_result()->fetch_assoc();

        $itemStmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND is_active = 1");
        $itemStmt->bind_param("i", $itemId);
        $itemStmt->execute();
        $item = $itemStmt->get_result()->fetch_assoc();

        $allergenConflict = false;
        if ($pref && $pref['allergens'] && $item && $item['allergens']) {
            $userAllergens = json_decode($pref['allergens'], true);
            $itemAllergens = json_decode($item['allergens'], true);
            $allergenConflict = !empty(array_intersect($userAllergens, $itemAllergens));
        }

        if ($allergenConflict) {
            $errorMsg = 'This item contains allergens from your profile!';
        } elseif ($item && $_SESSION['user_tier'] !== 'vip' && $item['is_vip_only']) {
            $errorMsg = 'This item is for VIP members only!';
        } elseif ($item) {
            $isAddon = isset($_POST['is_addon']) ? 1 : 0;
            $isSwap  = isset($_POST['is_swap'])  ? 1 : 0;
            $ins = $conn->prepare("INSERT INTO box_items (box_id, item_id, is_swap, is_addon) VALUES (?, ?, ?, ?)");
            $ins->bind_param("iiii", $box['id'], $itemId, $isSwap, $isAddon);
            $ins->execute();
            $successMsg = 'Item added to your box!';
        }
    }
}

// Get available items
$tier = $_SESSION['user_tier'];
$items = $conn->query("SELECT i.*, inv.stock_qty FROM items i JOIN inventory inv ON inv.item_id = i.id WHERE i.is_active = 1 AND inv.stock_qty > 0");

// Get current box items
$currentItems = [];
if ($box) {
    $ci = $conn->prepare("SELECT bi.*, i.name, i.description FROM box_items bi JOIN items i ON i.id = bi.item_id WHERE bi.box_id = ?");
    $ci->bind_param("i", $box['id']);
    $ci->execute();
    $currentItems = $ci->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customize My Box - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .item-card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); transition: .3s; }
        .item-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
        .vip-badge { background: #ffd700; color: #333; font-size: 10px; padding: 2px 8px; border-radius: 10px; }
        .locked-banner { background: #dc3545; color: white; padding: 15px; border-radius: 10px; text-align: center; }
        .box-summary { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); }
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
    <h3 class="mb-1">Customize My Box</h3>
    <p class="text-muted">Add, swap, or remove items from your next delivery</p>

    <?php if ($isLocked): ?>
        <div class="locked-banner mb-4">
            🔒 Your box is locked! Customization closed 48 hours before shipping.
        </div>
    <?php endif; ?>

    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?= $successMsg ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?= $errorMsg ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Items Grid -->
        <div class="col-md-8">
            <!-- Box Type Toggle (UC11) -->
            <?php if ($box && !$isLocked): ?>
            <div class="mb-3 d-flex gap-2">
                <form method="POST">
                    <input type="hidden" name="action" value="set_type">
                    <button name="box_type" value="curated" class="btn <?= ($box['box_type'] === 'curated') ? 'btn-success' : 'btn-outline-success' ?>">
                        🎁 Pre-set Curated Box
                    </button>
                    <button name="box_type" value="custom" class="btn <?= ($box['box_type'] === 'custom') ? 'btn-success' : 'btn-outline-success' ?>">
                        ✏️ Fully Custom Box
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <div class="row g-3">
            <?php while ($item = $items->fetch_assoc()): ?>
                <?php if ($item['is_vip_only'] && $tier !== 'vip') continue; ?>
                <div class="col-md-6">
                    <div class="card item-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                <?php if ($item['is_vip_only']): ?>
                                    <span class="vip-badge">VIP</span>
                                <?php endif; ?>
                                <?php if ($item['is_limited']): ?>
                                    <span class="badge bg-danger" style="font-size:10px">Limited</span>
                                <?php endif; ?>
                            </div>
                            <p class="card-text text-muted small"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                            <p class="text-muted small mb-2">Stock: <?= $item['stock_qty'] ?></p>

                            <?php if ($box && !$isLocked): ?>
                            <div class="d-flex gap-1 flex-wrap">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="is_swap" value="1">
                                    <button class="btn btn-outline-success btn-sm">🔄 Swap</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="is_addon" value="1">
                                    <button class="btn btn-success btn-sm">+ Add-on</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
        </div>

        <!-- Box Summary -->
        <div class="col-md-4">
            <div class="box-summary">
                <h6 class="fw-bold mb-3">📦 Your Box</h6>
                <?php if (empty($currentItems)): ?>
                    <p class="text-muted small">No items added yet.</p>
                <?php else: ?>
                    <?php foreach ($currentItems as $ci): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div>
                            <div class="small fw-bold"><?= htmlspecialchars($ci['name']) ?></div>
                            <?php if ($ci['is_swap']): ?>
                                <span class="badge bg-info" style="font-size:9px">Swap</span>
                            <?php elseif ($ci['is_addon']): ?>
                                <span class="badge bg-warning text-dark" style="font-size:9px">Add-on</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$isLocked): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="box_item_id" value="<?= $ci['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" style="font-size:10px">✕</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($box): ?>
                <div class="mt-3 pt-2 border-top">
                    <div class="small text-muted">Lock time: <?= $box['lock_at'] ?></div>
                    <div class="small text-muted">Status: <span class="badge bg-success"><?= ucfirst($box['status']) ?></span></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>