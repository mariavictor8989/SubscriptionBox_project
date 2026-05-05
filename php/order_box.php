<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId  = $_SESSION['user_id'];
$error   = '';
$success = false;
$orderId = null;
$amount  = 0;

// Get active box FIRST (before POST handling)
$activeBox = $conn->prepare("
    SELECT b.id, b.subscription_id FROM boxes b
    JOIN user_subscriptions us ON us.id = b.subscription_id
    WHERE b.user_id = ? AND b.status IN ('pending','customizing')
    ORDER BY b.created_at DESC LIMIT 1
");
$activeBox->bind_param("i", $userId);
$activeBox->execute();
$activeBoxData = $activeBox->get_result()->fetch_assoc();
$activeBoxId   = $activeBoxData['id'] ?? null;

// Get addon items in current box
$addonItems = [];
$addonTotal = 0;
if ($activeBoxId) {
    $addons = $conn->prepare("
        SELECT i.name, i.price
        FROM box_items bi
        JOIN items i ON i.id = bi.item_id
        WHERE bi.box_id = ? AND bi.is_addon = 1
    ");
    $addons->bind_param("i", $activeBoxId);
    $addons->execute();
    $addonItems = $addons->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($addonItems as $a) $addonTotal += $a['price'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subId        = (int) $_POST['subscription_id'];
    $addressId    = (int) $_POST['address_id'];
    $existingBoxId = (int) ($_POST['existing_box_id'] ?? 0);

    $sub = $conn->prepare("
        SELECT us.*, sp.price, sp.name 
        FROM user_subscriptions us 
        JOIN subscription_plans sp ON sp.id = us.plan_id 
        WHERE us.id = ? AND us.user_id = ? AND us.status = 'active'
    ");
    $sub->bind_param("ii", $subId, $userId);
    $sub->execute();
    $subData = $sub->get_result()->fetch_assoc();

    $addr = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ? AND is_serviceable = 1");
    $addr->bind_param("ii", $addressId, $userId);
    $addr->execute();
    $addrData = $addr->get_result()->fetch_assoc();

    if (!$subData) {
        $error = 'Invalid or inactive subscription.';
    } elseif (!$addrData) {
        $error = 'Please select a valid serviceable address.';
    } else {
        // Use existing box if available, otherwise create new
        if ($existingBoxId > 0) {
            $boxId = $existingBoxId;
        } else {
            $lockAt  = date('Y-m-d H:i:s', strtotime('+5 days'));
            $month   = date('Y-m-01');
            $boxStmt = $conn->prepare("INSERT INTO boxes (subscription_id, user_id, month, status, lock_at) VALUES (?, ?, ?, 'pending', ?)");
            $boxStmt->bind_param("iiss", $subId, $userId, $month, $lockAt);
            $boxStmt->execute();
            $boxId = $conn->insert_id;
        }

        // Get addon price from THIS box
        $addonStmt = $conn->prepare("
            SELECT SUM(i.price) AS addon_total
            FROM box_items bi
            JOIN items i ON i.id = bi.item_id
            WHERE bi.box_id = ? AND bi.is_addon = 1
        ");
        $addonStmt->bind_param("i", $boxId);
        $addonStmt->execute();
        $addonPrice = (float) ($addonStmt->get_result()->fetch_assoc()['addon_total'] ?? 0);

        $subtotal = $subData['price'] + $addonPrice;
        $tax      = round($subtotal * 0.14, 2);
        $amount   = round($subtotal + $tax, 2);

        $ordStmt = $conn->prepare("INSERT INTO orders (user_id, box_id, subscription_id, address_id, amount, tax, status) VALUES (?, ?, ?, ?, ?, ?, 'paid')");
        $ordStmt->bind_param("iiiidd", $userId, $boxId, $subId, $addressId, $amount, $tax);
        $ordStmt->execute();
        $orderId = $conn->insert_id;

        // Update box status to picked
        $conn->prepare("UPDATE boxes SET status='picking' WHERE id=?")->bind_param("i", $boxId);
        $upd = $conn->prepare("UPDATE boxes SET status='picking' WHERE id=?");
        $upd->bind_param("i", $boxId);
        $upd->execute();

        $ship = $conn->prepare("INSERT INTO shipments (order_id, status) VALUES (?, 'pending')");
        $ship->bind_param("i", $orderId);
        $ship->execute();

        $notif    = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'subscription', ?)");
        $notifMsg = "Your order #{$orderId} has been placed! Total: " . number_format($amount, 2) . " EGP";
        $notif->bind_param("is", $userId, $notifMsg);
        $notif->execute();

        $success = true;
    }
}

$subs = $conn->prepare("SELECT us.*, sp.name, sp.price FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.user_id = ? AND us.status = 'active'");
$subs->bind_param("i", $userId);
$subs->execute();
$subscriptions = $subs->get_result()->fetch_all(MYSQLI_ASSOC);

$addrs = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? AND is_serviceable = 1");
$addrs->bind_param("i", $userId);
$addrs->execute();
$addresses = $addrs->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Box - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .order-card { background: white; border-radius: 16px; padding: 35px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); max-width: 600px; margin: 0 auto; }
        .price-box { background: #f8f9fa; border-radius: 12px; padding: 18px; }
        .price-box .total-row { font-size: 1.2rem; font-weight: 800; color: #2c7a2c; }
        .addon-item { display: flex; justify-content: space-between; font-size: .88rem; color: #555; padding: 3px 0; }
        .success-card { background: #f0faf0; border: 2px solid #2c7a2c; border-radius: 16px; padding: 30px; text-align: center; }
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
    <div class="order-card">

        <?php if ($success): ?>
        <div class="success-card">
            <div style="font-size:3rem">✅</div>
            <h4 class="fw-bold mb-2 mt-2">Order Placed!</h4>
            <p class="text-muted mb-1">Order ID: <strong>#<?= $orderId ?></strong></p>
            <p class="mb-3">Total: <strong class="text-success fs-5"><?= number_format($amount, 2) ?> EGP</strong>
                <span class="text-muted small">(incl. 14% VAT)</span></p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="dashboard.php" class="btn btn-success fw-bold">← Dashboard</a>
            </div>
        </div>

        <?php else: ?>
        <h4 class="mb-1 fw-bold">🛒 Order My Box</h4>
        <p class="text-muted small mb-4">Place your monthly box order</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($subscriptions)): ?>
            <div class="alert alert-warning">No active subscriptions. <a href="subscribe.php">Subscribe first →</a></div>
        <?php elseif (empty($addresses)): ?>
            <div class="alert alert-warning">No serviceable addresses. <a href="add_address.php">Add one first →</a></div>
        <?php else: ?>
        <form method="POST">
            <!-- Pass existing box ID -->
            <?php if ($activeBoxId): ?>
                <input type="hidden" name="existing_box_id" value="<?= $activeBoxId ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label fw-bold">Subscription Plan</label>
                <select name="subscription_id" class="form-select" required
                        onchange="updatePrice(this)" style="padding:12px; border-radius:8px;">
                    <option value="">Select your subscription...</option>
                    <?php foreach ($subscriptions as $s): ?>
                        <option value="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>">
                            <?= htmlspecialchars($s['name']) ?> — <?= number_format($s['price'], 2) ?> EGP/mo
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Delivery Address</label>
                <select name="address_id" class="form-select" required style="padding:12px; border-radius:8px;">
                    <option value="">Select address...</option>
                    <?php foreach ($addresses as $a): ?>
                        <option value="<?= $a['id'] ?>">
                            <?= htmlspecialchars($a['label']) ?> —
                            <?= htmlspecialchars($a['full_address']) ?>,
                            <?= htmlspecialchars($a['city']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text"><a href="add_address.php">+ Add new address</a></div>
            </div>

            <!-- Price Summary -->
            <div class="price-box mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Plan Price</span>
                    <span id="plan-price" class="fw-bold">—</span>
                </div>

                <?php if (!empty($addonItems)): ?>
                <div class="mb-2">
                    <div class="text-muted small mb-1">Add-on Items:</div>
                    <?php foreach ($addonItems as $a): ?>
                    <div class="addon-item">
                        <span>+ <?= htmlspecialchars($a['name']) ?></span>
                        <span><?= number_format($a['price'], 2) ?> EGP</span>
                    </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between mt-1 pt-1 border-top">
                        <span class="text-muted small">Add-ons Total</span>
                        <span class="small fw-bold"><?= number_format($addonTotal, 2) ?> EGP</span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span id="subtotal">—</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">VAT (14%)</span>
                    <span id="vat" class="text-muted">—</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between total-row">
                    <span>Total</span>
                    <span id="total">—</span>
                </div>
            </div>

            <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5">🛒 Place Order</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
const addonTotal = <?= $addonTotal ?>;

function updatePrice(sel) {
    const opt   = sel.options[sel.selectedIndex];
    const price = parseFloat(opt.dataset.price) || 0;
    if (!price) {
        ['plan-price','subtotal','vat','total'].forEach(id => document.getElementById(id).textContent = '—');
        return;
    }
    const subtotal = price + addonTotal;
    const vat      = subtotal * 0.14;
    const total    = subtotal + vat;
    document.getElementById('plan-price').textContent = price.toFixed(2) + ' EGP';
    document.getElementById('subtotal').textContent   = subtotal.toFixed(2) + ' EGP';
    document.getElementById('vat').textContent        = vat.toFixed(2) + ' EGP';
    document.getElementById('total').textContent      = total.toFixed(2) + ' EGP';
}
</script>
</body>
</html>