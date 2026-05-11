<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customize My Box - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .item-card { border:none; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,.07); transition:.3s; height:100%; }
        .item-card:hover { transform:translateY(-3px); box-shadow:0 8px 25px rgba(0,0,0,.12); }
        .vip-badge { background:#ffd700; color:#333; font-size:10px; padding:2px 8px; border-radius:10px; }
        .locked-banner { background:#dc3545; color:white; padding:15px; border-radius:10px; text-align:center; }
        .box-summary { background:white; border-radius:12px; padding:20px; box-shadow:0 4px 15px rgba(0,0,0,.07); position:sticky; top:20px; }
        .type-btn { border-radius:10px; padding:10px 20px; font-weight:600; transition:.2s; }
        .curated-info { background:#f0faf0; border-left:4px solid #2c7a2c; border-radius:8px; padding:12px 16px; margin-bottom:16px; }
        .custom-info { background:#fff8e1; border-left:4px solid #ffc107; border-radius:8px; padding:12px 16px; margin-bottom:16px; }
        .swap-banner { background:#0dcaf0; color:#000; padding:10px 16px; border-radius:8px; margin-bottom:12px; font-weight:600; }
        .box-item-selectable { cursor:pointer; transition:.15s; padding:6px; border-radius:6px; }
        .box-item-selectable:hover { background:#e8f9ff; }
        .box-item-selectable.selected { background:#cff4fc; border:1px solid #0dcaf0; }
        .item-card.swap-mode { border:2px solid #0dcaf0 !important; background:#e8f9ff !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?controller=user&action=dashboard">📦 FreshBox</a>
        <a href="index.php?controller=user&action=dashboard" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="mb-1">Customize My Box</h3>
    <p class="text-muted">Add, swap, or remove items from your next delivery</p>

    <?php if (!$box): ?>
        <div class="alert alert-warning">No active box found. <a href="index.php?controller=user&action=orderBox">Place an order first</a>.</div>
    <?php else: ?>

    <?php if ($isLocked): ?>
        <div class="locked-banner mb-4">🔒 Your box is locked! Customization closed 48 hours before shipping.</div>
    <?php endif; ?>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success alert-dismissible"><?= $successMsg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger alert-dismissible"><?= $errorMsg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Box Type Toggle -->
            <?php if (!$isLocked): ?>
            <div class="mb-4">
                <div class="d-flex gap-2 mb-2">
                    <form method="POST" action="index.php?controller=user&action=customizeBox" class="d-inline">
                        <input type="hidden" name="action" value="set_type">
                        <button type="submit" name="box_type" value="curated" class="type-btn btn <?= $boxType === 'curated' ? 'btn-success' : 'btn-outline-success' ?>">🎁 Pre-set Curated Box</button>
                    </form>
                    <form method="POST" action="index.php?controller=user&action=customizeBox" class="d-inline">
                        <input type="hidden" name="action" value="set_type">
                        <button type="submit" name="box_type" value="custom" class="type-btn btn <?= $boxType === 'custom' ? 'btn-warning' : 'btn-outline-warning' ?>">✏️ Fully Custom Box</button>
                    </form>
                </div>
                <?php if ($boxType === 'curated'): ?>
                    <div class="curated-info"><strong>🎁 Pre-set Curated Box</strong><br><small class="text-muted">We pick the best items for you. You can still swap items you don't like.</small></div>
                <?php else: ?>
                    <div class="custom-info"><strong>✏️ Fully Custom Box</strong><br><small class="text-muted">You choose everything! Browse items below.</small></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div id="swap-banner" class="swap-banner d-none">
                🔄 Swap mode: Now click the item in "Your Box" you want to replace.
                <button type="button" class="btn btn-sm btn-outline-dark ms-2" onclick="cancelSwap()">Cancel</button>
            </div>

            <div class="row g-3">
            <?php foreach ($items as $item):
                if ($item['is_vip_only'] && $tier !== 'vip') continue;
            ?>
                <div class="col-md-6">
                    <div class="card item-card" id="item-card-<?= $item['id'] ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                <div class="d-flex gap-1">
                                    <?php if ($item['is_vip_only']): ?><span class="vip-badge">VIP</span><?php endif; ?>
                                    <?php if ($item['is_limited']): ?><span class="badge bg-danger" style="font-size:10px">Limited</span><?php endif; ?>
                                </div>
                            </div>
                            <p class="card-text text-muted small mb-1"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                            <p class="text-muted small mb-3">⚖️ <?= $item['weight_g'] ?>g &nbsp;|&nbsp; 📦 Stock: <?= $item['stock_qty'] ?></p>
                            <?php if ($box && !$isLocked): ?>
                            <div class="d-flex gap-1 flex-wrap">
                                <button class="btn btn-outline-info btn-sm" onclick="selectNewItem(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')">🔄 Swap</button>
                                <form method="POST" action="index.php?controller=user&action=customizeBox" class="d-inline">
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
            <?php endforeach; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box-summary">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">📦 Your Box</h6>
                    <span class="badge <?= $boxType === 'curated' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $boxType === 'curated' ? 'Curated' : 'Custom' ?></span>
                </div>

                <?php if (empty($currentItems)): ?>
                    <p class="text-muted small">No items added yet.</p>
                <?php else: ?>
                    <p class="text-muted small mb-2">Click item to select for swap 👇</p>
                    <?php foreach ($currentItems as $ci): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom box-item-selectable"
                         id="box-item-<?= $ci['id'] ?>"
                         onclick="selectOldItem(<?= $ci['id'] ?>, '<?= addslashes($ci['name']) ?>')">
                        <div>
                            <div class="small fw-bold"><?= htmlspecialchars($ci['name']) ?></div>
                            <div class="d-flex gap-1 mt-1">
                                <?php if ($ci['is_swap']): ?><span class="badge bg-info" style="font-size:9px">Swap</span>
                                <?php elseif ($ci['is_addon']): ?><span class="badge bg-warning text-dark" style="font-size:9px">Add-on</span>
                                <?php else: ?><span class="badge bg-success" style="font-size:9px">Included</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$isLocked): ?>
                        <form method="POST" action="index.php?controller=user&action=customizeBox" onclick="event.stopPropagation()">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="box_item_id" value="<?= $ci['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" style="font-size:10px;padding:2px 8px;">✕</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <div class="small text-muted mt-2">Total items: <strong><?= count($currentItems) ?></strong></div>
                <?php endif; ?>

                <div class="mt-3 pt-3 border-top">
                    <div class="small text-muted mb-1">🔒 Locks at: <strong><?= $box['lock_at'] ?></strong></div>
                    <div class="small text-muted">Status: <span class="badge bg-<?= $box['status'] === 'customizing' ? 'warning text-dark' : 'success' ?>"><?= ucfirst($box['status']) ?></span></div>
                </div>

                <?php if (!$isLocked && !empty($currentItems)): ?>
                    <a href="index.php?controller=user&action=orderBox" class="btn btn-success w-100 mt-3 fw-bold">🛒 Proceed to Order</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <form method="POST" action="index.php?controller=user&action=customizeBox" id="swap-form" style="display:none;">
        <input type="hidden" name="action" value="swap">
        <input type="hidden" name="item_id" id="new-item-id">
        <input type="hidden" name="old_box_item_id" id="old-box-item-id">
    </form>

    <?php endif; ?>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
let selectedOldItemId = null;
let selectedNewItemId = null;

function selectOldItem(boxItemId, itemName) {
    document.querySelectorAll('.box-item-selectable').forEach(el => el.classList.remove('selected'));
    document.getElementById('box-item-' + boxItemId).classList.add('selected');
    selectedOldItemId = boxItemId;
    if (selectedNewItemId) submitSwap();
    else {
        document.getElementById('swap-banner').classList.remove('d-none');
    }
}

function selectNewItem(itemId, itemName) {
    selectedNewItemId = itemId;
    document.querySelectorAll('.item-card').forEach(el => el.classList.remove('swap-mode'));
    document.getElementById('item-card-' + itemId).classList.add('swap-mode');
    if (selectedOldItemId) submitSwap();
    else {
        document.getElementById('swap-banner').classList.remove('d-none');
        alert('Now click the item in "Your Box" you want to replace with ' + itemName);
    }
}

function submitSwap() {
    document.getElementById('new-item-id').value     = selectedNewItemId;
    document.getElementById('old-box-item-id').value = selectedOldItemId;
    document.getElementById('swap-form').submit();
}

function cancelSwap() {
    selectedOldItemId = null;
    selectedNewItemId = null;
    document.querySelectorAll('.box-item-selectable').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('.item-card').forEach(el => el.classList.remove('swap-mode'));
    document.getElementById('swap-banner').classList.add('d-none');
}
</script>
</body>
</html>