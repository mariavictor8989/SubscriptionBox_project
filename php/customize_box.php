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

    // ── SWAP item (UC5) ───────────────────────────────────
    // يشيل item قديم ويضيف item جديد
    } elseif ($action === 'swap' && !$isLocked) {
        $newItemId    = (int) ($_POST['item_id'] ?? 0);
        $oldBoxItemId = (int) ($_POST['old_box_item_id'] ?? 0);

        // Step 1: Validate request — هل فيه item قديم محدد؟
        if ($oldBoxItemId === 0) {
            $errorMsg = '⚠️ Please select an item to swap from your box first.';
        } else {
            // Step 2: Search for new item in database
            $itemStmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND is_active = 1");
            $itemStmt->bind_param("i", $newItemId);
            $itemStmt->execute();
            $item = $itemStmt->get_result()->fetch_assoc();

            if (!$item) {
                $errorMsg = '❌ Swap rejected: Item not found or unavailable.';
            } else {
                // Step 3: Check swap conditions
                // Check stock
                $stockStmt = $conn->prepare("SELECT stock_qty FROM inventory WHERE item_id = ?");
                $stockStmt->bind_param("i", $newItemId);
                $stockStmt->execute();
                $stock = $stockStmt->get_result()->fetch_assoc();

                // Check allergens
                $prefStmt = $conn->prepare("SELECT allergens FROM user_preferences WHERE user_id = ?");
                $prefStmt->bind_param("i", $userId);
                $prefStmt->execute();
                $pref = $prefStmt->get_result()->fetch_assoc();

                $allergenConflict = false;
                if ($pref && $pref['allergens'] && $item['allergens']) {
                    $userAllergens    = json_decode($pref['allergens'], true);
                    $itemAllergens    = json_decode($item['allergens'], true);
                    $allergenConflict = !empty(array_intersect($userAllergens, $itemAllergens));
                }

                // Check VIP
                $vipConflict = ($_SESSION['user_tier'] !== 'vip' && $item['is_vip_only']);

                // Check lock (already checked but double check)
                if ($isLocked) {
                    $errorMsg = '❌ Swap rejected: Box is locked for shipping.';
                } elseif (($stock['stock_qty'] ?? 0) <= 0) {
                    $errorMsg = '❌ Swap rejected: Item is out of stock.';
                } elseif ($allergenConflict) {
                    $errorMsg = '❌ Swap rejected: Item contains allergens from your profile.';
                } elseif ($vipConflict) {
                    $errorMsg = '❌ Swap rejected: This item is for VIP members only.';
                } else {
                    // ── Swap approved ──
                    // Update old item status (mark as swapped)
                    $upd = $conn->prepare("UPDATE box_items SET is_swap = 0 WHERE id = ? AND box_id = ?");
                    $upd->bind_param("ii", $oldBoxItemId, $box['id']);
                    $upd->execute();

                    // Remove old item from box
                    $del = $conn->prepare("DELETE FROM box_items WHERE id = ? AND box_id = ?");
                    $del->bind_param("ii", $oldBoxItemId, $box['id']);
                    $del->execute();

                    // Add new item to box as swap
                    $ins = $conn->prepare("INSERT INTO box_items (box_id, item_id, is_swap, is_addon) VALUES (?, ?, 1, 0)");
                    $ins->bind_param("ii", $box['id'], $newItemId);
                    $ins->execute();

                    // Update inventory (reserve 1 unit)
                    $invUpd = $conn->prepare("UPDATE inventory SET reserved_qty = reserved_qty + 1 WHERE item_id = ?");
                    $invUpd->bind_param("i", $newItemId);
                    $invUpd->execute();

                    // Update box status
                    $updBox = $conn->prepare("UPDATE boxes SET status='customizing' WHERE id=?");
                    $updBox->bind_param("i", $box['id']);
                    $updBox->execute();

                    $successMsg = '✅ Swap completed successfully! ' . htmlspecialchars($item['name']) . ' added to your box.';
                }
            }
        }

    // ── Add-on item (UC8) ─────────────────────────────────
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
            $ins = $conn->prepare("INSERT INTO box_items (box_id, item_id, is_swap, is_addon) VALUES (?, ?, 0, 1)");
            $ins->bind_param("ii", $box['id'], $itemId);
            $ins->execute();

            $updBox = $conn->prepare("UPDATE boxes SET status='customizing' WHERE id=?");
            $updBox->bind_param("i", $box['id']);
            $updBox->execute();

            $successMsg = '✅ ' . htmlspecialchars($item['name']) . ' added as add-on!';
        }

    // ── Remove item ───────────────────────────────────────
    } elseif ($action === 'remove' && !$isLocked) {
        $boxItemId = (int) ($_POST['box_item_id'] ?? 0);
        $del = $conn->prepare("DELETE FROM box_items WHERE id = ? AND box_id = ?");
        $del->bind_param("ii", $boxItemId, $box['id']);
        $del->execute();
        $successMsg = 'Item removed from your box.';
    }

    // Reload box
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
        .swap-mode { border: 2px solid #0dcaf0 !important; background: #e8f9ff !important; }
        .swap-banner { background: #0dcaf0; color: #000; padding: 10px 16px; border-radius: 8px; margin-bottom: 12px; font-weight: 600; }
        .box-item-selectable { cursor: pointer; transition: .15s; }
        .box-item-selectable:hover { background: #e8f9ff; border-radius: 6px; }
        .box-item-selectable.selected { background: #cff4fc; border-radius: 6px; border: 1px solid #0dcaf0; }
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

            <!-- Box Type Toggle -->
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
                        <small class="text-muted">We pick the best items for you. You can still swap items you don't like.</small>
                    </div>
                <?php else: ?>
                    <div class="custom-info">
                        <strong>✏️ Fully Custom Box</strong><br>
                        <small class="text-muted">You choose everything! Browse items below and add what you want.</small>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Swap mode banner -->
            <div id="swap-banner" class="swap-banner d-none">
                🔄 Swap mode: Select the new item you want to replace <strong id="swap-item-name"></strong> with.
                <button type="button" class="btn btn-sm btn-outline-dark ms-2" onclick="cancelSwap()">Cancel</button>
            </div>

            <!-- Items Grid -->
            <div class="row g-3" id="items-grid">
            <?php
            $items->data_seek(0);
            while ($item = $items->fetch_assoc()):
                if ($item['is_vip_only'] && $tier !== 'vip') continue;
            ?>
                <div class="col-md-6">
                    <div class="card item-card" id="item-card-<?= $item['id'] ?>">
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
                                <!-- Swap button — needs old item selection -->
                                <button class="btn btn-outline-info btn-sm"
                                        onclick="selectNewItem(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')">
                                    🔄 Swap
                                </button>

                                <!-- Add-on button -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
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

        <!-- Box Summary -->
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
                    <p class="text-muted small mb-2">Click an item to select it for swap 👇</p>
                    <?php foreach ($currentItems as $ci): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom box-item-selectable"
                         id="box-item-<?= $ci['id'] ?>"
                         onclick="selectOldItem(<?= $ci['id'] ?>, '<?= addslashes($ci['name']) ?>')">
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
                        <form method="POST" onclick="event.stopPropagation()">
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
                    <div class="small text-muted mb-1">🔒 Locks at: <strong><?= $box['lock_at'] ?></strong></div>
                    <div class="small text-muted">
                        Status: <span class="badge bg-<?= $box['status'] === 'customizing' ? 'warning text-dark' : 'success' ?>">
                            <?= ucfirst($box['status']) ?>
                        </span>
                    </div>
                </div>

                <?php if (!$isLocked && !empty($currentItems)): ?>
                <a href="order_box.php" class="btn btn-success w-100 mt-3 fw-bold">🛒 Proceed to Order</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hidden swap form -->
    <form method="POST" id="swap-form" style="display:none;">
        <input type="hidden" name="action" value="swap">
        <input type="hidden" name="item_id" id="new-item-id">
        <input type="hidden" name="old_box_item_id" id="old-box-item-id">
    </form>

    <?php endif; ?>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
let selectedOldItemId   = null;
let selectedOldItemName = '';
let selectedNewItemId   = null;

// Step 1: User clicks item in box → selects it as "old item to swap"
function selectOldItem(boxItemId, itemName) {
    // Deselect all
    document.querySelectorAll('.box-item-selectable').forEach(el => el.classList.remove('selected'));
    // Select this one
    document.getElementById('box-item-' + boxItemId).classList.add('selected');

    selectedOldItemId   = boxItemId;
    selectedOldItemName = itemName;

    // If new item already selected → submit swap
    if (selectedNewItemId) {
        submitSwap();
    } else {
        document.getElementById('swap-banner').classList.remove('d-none');
        document.getElementById('swap-item-name').textContent = itemName;
    }
}

// Step 2: User clicks Swap on new item → selects it as "new item"
function selectNewItem(itemId, itemName) {
    selectedNewItemId = itemId;

    // Highlight selected card
    document.querySelectorAll('.item-card').forEach(el => el.classList.remove('swap-mode'));
    document.getElementById('item-card-' + itemId).classList.add('swap-mode');

    if (selectedOldItemId) {
        // Both selected → submit swap
        submitSwap();
    } else {
        // Ask user to pick old item
        document.getElementById('swap-banner').classList.remove('d-none');
        document.getElementById('swap-item-name').textContent = 'your selected item';
        alert('Now click on the item in "Your Box" that you want to replace with ' + itemName);
    }
}

// Submit the swap form
function submitSwap() {
    document.getElementById('new-item-id').value     = selectedNewItemId;
    document.getElementById('old-box-item-id').value = selectedOldItemId;
    document.getElementById('swap-form').submit();
}

function cancelSwap() {
    selectedOldItemId   = null;
    selectedNewItemId   = null;
    selectedOldItemName = '';
    document.querySelectorAll('.box-item-selectable').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('.item-card').forEach(el => el.classList.remove('swap-mode'));
    document.getElementById('swap-banner').classList.add('d-none');
}
</script>
</body>
</html>