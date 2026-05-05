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

$isLocked   = $box && strtotime($box['lock_at']) <= time();
$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $box) {
    $action = $_POST['action'] ?? '';

    // ── Set box type (UC11) ──────────────────────────────
    if ($action === 'set_type') {
        $type = in_array($_POST['box_type'], ['curated','custom']) ? $_POST['box_type'] : 'curated';
        $upd  = $conn->prepare("UPDATE boxes SET box_type = ? WHERE id = ?");
        $upd->bind_param("si", $type, $box['id']);
        $upd->execute();
        header("Location: customize_box.php");
        exit();

    // ── Add / Swap / Addon (UC5, UC6, UC8) ───────────────
    } elseif ($action === 'add' && !$isLocked) {
        $itemId = (int) ($_POST['item_id'] ?? 0);

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
            $userAllergens    = json_decode($pref['allergens'], true);
            $itemAllergens    = json_decode($item['allergens'], true);
            $allergenConflict = !empty(array_intersect($userAllergens, $itemAllergens));
        }

        if ($allergenConflict) {
            $errorMsg = '⚠️ This item contains allergens from your profile!';
        } elseif ($item && $_SESSION['user_tier'] !== 'vip' && $item['is_vip_only']) {
            $errorMsg = '⭐ This item is for VIP members only!';
        } elseif ($item) {
            $isAddon = isset($_POST['is_addon']) ? 1 : 0;
            $isSwap  = isset($_POST['is_swap'])  ? 1 : 0;
            $ins = $conn->prepare("INSERT INTO box_items (box_id, item_id, is_swap, is_addon) VALUES (?, ?, ?, ?)");
            $ins->bind_param("iiii", $box['id'], $itemId, $isSwap, $isAddon);
            $ins->execute();

            // Update box status to customizing
            $conn->prepare("UPDATE boxes SET status='customizing' WHERE id=?")->bind_param("i", $box['id']);
            $upd2 = $conn->prepare("UPDATE boxes SET status='customizing' WHERE id=?");
            $upd2->bind_param("i", $box['id']);
            $upd2->execute();

            $successMsg = '✅ Item added to your box!';
        }

    // ── Remove item ───────────────────────────────────────
    } elseif ($action === 'remove' && !$isLocked) {
        $boxItemId = (int) ($_POST['box_item_id'] ?? 0);
        $del = $conn->prepare("DELETE FROM box_items WHERE id = ? AND box_id = ?");
        $del->bind_param("ii", $boxItemId, $box['id']);
        $del->execute();
        $successMsg = 'Item removed.';
    }

    // Reload box after update
    $boxStmt->bind_param("i", $userId);
    $boxStmt->execute();
    $box = $boxStmt->get_result()->fetch_assoc();
}

// Get available items
$tier  = $_SESSION['user_tier'];
$items = $conn->query("
    SELECT i.*, inv.stock_qty 
    FROM items i 
    JOIN inventory inv ON inv.item_id = i.id 
    WHERE i.is_active = 1 AND inv.stock_qty > 0
    ORDER BY i.is_limited DESC, i.name ASC
");

// Get current box items
$currentItems = [];
if ($box) {
    $ci = $conn->prepare("
        SELECT bi.*, i.name, i.description, i.weight_g 
        FROM box_items bi 
        JOIN items i ON i.id = bi.item_id 
        WHERE bi.box_id = ?
    ");
    $ci->bind_param("i", $box['id']);
    $ci->execute();
    $currentItems = $ci->get_result()->fetch_all(MYSQLI_ASSOC);
}

$boxType = $box['box_type'] ?? 'curated';
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
        .item-card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); transition: .3s; height: 100%; }
        .item-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
        .vip-badge { background: #ffd700; color: #333; font-size: 10px; padding: 2px 8px; border-radius: 10px; }
        .locked-banner { background: #dc3545; color: white; padding: 15px; border-radius: 10px; text-align: center; }
        .box-summary { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); position: sticky; top: 20px; }
        .type-btn { border-radius: 10px; padding: 10px 20px; font-weight: 600; transition: .2s; }
        .curated-info { background: #f0faf0; border-left: 4px solid #2c7a2c; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
        .custom-info  { background: #fff8e1; border-left: 4px solid #ffc107; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
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

    <?php if (!$box): ?>
        <div class="alert alert-warning">
            No active box found. <a href="order_box.php">Place an order first</a>.
        </div>
    <?php else: ?>

    <?php if ($isLocked): ?>
        <div class="locked-banner mb-4">
            🔒 Your box is locked! Customization closed 48 hours before shipping.
        </div>
    <?php endif; ?>

    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible"><?= $successMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible"><?= $errorMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">

            <!-- ── Box Type Toggle (UC11) ── -->
            <?php if (!$isLocked): ?>
            <div class="mb-4">
                <div class="d-flex gap-2 mb-2">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="set_type">
                        <button type="submit" name="box_type" value="curated"
                                class="type-btn btn <?= $boxType === 'curated' ? 'btn-success' : 'btn-outline-success' ?>">
                            🎁 Pre-set Curated Box
                        </button>
                    </form>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="set_type">
                        <button type="submit" name="box_type" value="custom"
                                class="type-btn btn <?= $boxType === 'custom' ? 'btn-warning' : 'btn-outline-warning' ?>">
                            ✏️ Fully Custom Box
                        </button>
                    </form>
                </div>

                <?php if ($boxType === 'curated'): ?>
                    <div class="curated-info">
                        <strong>🎁 Pre-set Curated Box</strong><br>
                        <small class="text-muted">We pick the best items for you based on your preferences. You can still swap items you don't like.</small>
                    </div>
                <?php else: ?>
                    <div class="custom-info">
                        <strong>✏️ Fully Custom Box</strong><br>
                        <small class="text-muted">You choose everything! Browse items below and add what you want to your box.</small>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ── Items Grid ── -->
            <div class="row g-3">
            <?php
            $items->data_seek(0);
            while ($item = $items->fetch_assoc()):
                if ($item['is_vip_only'] && $tier !== 'vip') continue;
            ?>
                <div class="col-md-6">
                    <div class="card item-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                <div class="d-flex gap-1">
                                    <?php if ($item['is_vip_only']): ?>
                                        <span class="vip-badge">VIP</span>
                                    <?php endif; ?>
                                    <?php if ($item['is_limited']): ?>
                                        <span class="badge bg-danger" style="font-size:10px">Limited</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="card-text text-muted small mb-1"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                            <p class="text-muted small mb-3">
                                ⚖️ <?= $item['weight_g'] ?>g &nbsp;|&nbsp; 
                                📦 Stock: <?= $item['stock_qty'] ?>
                            </p>

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
                            <?php elseif ($isLocked): ?>
                                <span class="text-muted small">🔒 Locked</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>

        </div>

        <!-- ── Box Summary ── -->
        <div class="col-md-4">
            <div class="box-summary">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">📦 Your Box</h6>
                    <span class="badge <?= $boxType === 'curated' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= $boxType === 'curated' ? 'Curated' : 'Custom' ?>
                    </span>
                </div>

                <?php if (empty($currentItems)): ?>
                    <p class="text-muted small">No items added yet.</p>
                <?php else: ?>
                    <?php foreach ($currentItems as $ci): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div>
                            <div class="small fw-bold"><?= htmlspecialchars($ci['name']) ?></div>
                            <div class="d-flex gap-1 mt-1">
                                <?php if ($ci['is_swap']): ?>
                                    <span class="badge bg-info" style="font-size:9px">Swap</span>
                                <?php elseif ($ci['is_addon']): ?>
                                    <span class="badge bg-warning text-dark" style="font-size:9px">Add-on</span>
                                <?php else: ?>
                                    <span class="badge bg-success" style="font-size:9px">Included</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$isLocked): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="box_item_id" value="<?= $ci['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" style="font-size:10px; padding:2px 8px;">✕</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <div class="small text-muted mt-2">
                        Total items: <strong><?= count($currentItems) ?></strong>
                    </div>
                <?php endif; ?>

                <div class="mt-3 pt-3 border-top">
                    <div class="small text-muted mb-1">
                        🔒 Locks at: <strong><?= $box['lock_at'] ?></strong>
                    </div>
                    <div class="small text-muted">
                        Status: <span class="badge bg-<?= $box['status'] === 'customizing' ? 'warning text-dark' : 'success' ?>">
                            <?= ucfirst($box['status']) ?>
                        </span>
                    </div>
                </div>

                <?php if (!$isLocked && !empty($currentItems)): ?>
                <a href="order_box.php" class="btn btn-success w-100 mt-3 fw-bold">
                    🛒 Proceed to Order
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>