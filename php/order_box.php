<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId  = $_SESSION['user_id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subId     = (int) $_POST['subscription_id'];
    $addressId = (int) $_POST['address_id'];

    // Validate subscription
    $sub = $conn->prepare("SELECT us.*, sp.price, sp.name FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.id = ? AND us.user_id = ? AND us.status = 'active'");
    $sub->bind_param("ii", $subId, $userId);
    $sub->execute();
    $subData = $sub->get_result()->fetch_assoc();

    // Validate address
    $addr = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ? AND is_serviceable = 1");
    $addr->bind_param("ii", $addressId, $userId);
    $addr->execute();
    $addrData = $addr->get_result()->fetch_assoc();

    if (!$subData) { $error = 'Invalid or inactive subscription.'; }
    elseif (!$addrData) { $error = 'Please select a valid serviceable address.'; }
    else {
        $lockAt = date('Y-m-d H:i:s', strtotime('+5 days'));
        $month  = date('Y-m-01');

        // Create box
        $box = $conn->prepare("INSERT INTO boxes (subscription_id, user_id, month, status, lock_at) VALUES (?, ?, ?, 'pending', ?)");
        $box->bind_param("iiss", $subId, $userId, $month, $lockAt);
        $box->execute();
        $boxId = $conn->insert_id;

        // Create order
        $tax    = $subData['price'] * 0.14;
        $amount = $subData['price'] + $tax;
        $ord = $conn->prepare("INSERT INTO orders (user_id, box_id, subscription_id, address_id, amount, tax, status) VALUES (?, ?, ?, ?, ?, ?, 'paid')");
        $ord->bind_param("iiiid", $userId, $boxId, $subId, $addressId, $amount, $tax);

        // Rewrite with all 6 params properly
        $ord2 = $conn->prepare("INSERT INTO orders (user_id, box_id, subscription_id, address_id, amount, tax, status) VALUES (?, ?, ?, ?, ?, ?, 'paid')");
        $ord2->bind_param("iiiidd", $userId, $boxId, $subId, $addressId, $amount, $tax);
        $ord2->execute();
        $orderId = $conn->insert_id;

        // Create shipment
        $ship = $conn->prepare("INSERT INTO shipments (order_id, status) VALUES (?, 'pending')");
        $ship->bind_param("i", $orderId);
        $ship->execute();

        // Notify
        $notif = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'subscription', ?)");
        $notifMsg = "Your order #{$orderId} has been placed! Total: " . number_format($amount, 2) . " EGP";
        $notif->bind_param("is", $userId, $notifMsg);
        $notif->execute();

        $success = "✅ Order #{$orderId} placed! Total: " . number_format($amount, 2) . " EGP (incl. 14% VAT)";
    }
}

// Get subscriptions
$subs = $conn->prepare("SELECT us.*, sp.name, sp.price FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.user_id = ? AND us.status = 'active'");
$subs->bind_param("i", $userId);
$subs->execute();
$subscriptions = $subs->get_result()->fetch_all(MYSQLI_ASSOC);

// Get addresses
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
        .order-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); max-width: 600px; margin: 0 auto; }
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
        <h4 class="mb-1">🛒 Order My Box</h4>
        <p class="text-muted small mb-4">Place your monthly box order</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($subscriptions)): ?>
            <div class="alert alert-warning">No active subscriptions. <a href="subscribe.php">Subscribe first</a>.</div>
        <?php elseif (empty($addresses)): ?>
            <div class="alert alert-warning">No serviceable addresses. <a href="add_address.php">Add one first</a>.</div>
        <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Subscription</label>
                <select name="subscription_id" class="form-select" required onchange="updatePrice(this)">
                    <option value="">Select subscription...</option>
                    <?php foreach ($subscriptions as $s): ?>
                        <option value="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>">
                            <?= htmlspecialchars($s['name']) ?> — <?= $s['price'] ?> EGP/mo
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Delivery Address</label>
                <select name="address_id" class="form-select" required>
                    <option value="">Select address...</option>
                    <?php foreach ($addresses as $a): ?>
                        <option value="<?= $a['id'] ?>">
                            <?= htmlspecialchars($a['label'] . ' — ' . $a['full_address']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bg-light rounded p-3 mb-3">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span id="subtotal">—</span></div>
                <div class="d-flex justify-content-between text-muted"><span>VAT (14%)</span><span id="vat">—</span></div>
                <hr>
                <div class="d-flex justify-content-between fw-bold"><span>Total</span><span id="total">—</span></div>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Place Order</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function updatePrice(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = parseFloat(opt.dataset.price) || 0;
    const vat   = price * 0.14;
    const total = price + vat;
    document.getElementById('subtotal').textContent = price.toFixed(2) + ' EGP';
    document.getElementById('vat').textContent      = vat.toFixed(2) + ' EGP';
    document.getElementById('total').textContent    = total.toFixed(2) + ' EGP';
}
</script>
</body>
</html>