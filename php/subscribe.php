<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId = $_SESSION['user_id'];
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId    = (int) $_POST['plan_id'];
    $addressId = (int) $_POST['address_id'];

    // Validate plan
    $plan = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
    $plan->bind_param("i", $planId);
    $plan->execute();
    $planData = $plan->get_result()->fetch_assoc();

    // Validate address is serviceable
    $addr = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ? AND is_serviceable = 1");
    $addr->bind_param("ii", $addressId, $userId);
    $addr->execute();
    $addrData = $addr->get_result()->fetch_assoc();

    if (!$planData) {
        $error = 'Invalid plan selected.';
    } elseif (!$addrData) {
        $error = 'Please select a valid serviceable address.';
    } else {
        $startDate   = date('Y-m-d');
        $nextBilling = date('Y-m-d', strtotime('+1 month'));

        $ins = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_id, status, start_date, next_billing) VALUES (?, ?, 'active', ?, ?)");
        $ins->bind_param("iiss", $userId, $planId, $startDate, $nextBilling);

        if ($ins->execute()) {
            $subId = $conn->insert_id;
            // Update user tier
            $upd = $conn->prepare("UPDATE users SET subscription_tier = ? WHERE id = ?");
            $upd->bind_param("si", $planData['tier'], $userId);
            $upd->execute();
            $_SESSION['user_tier'] = $planData['tier'];

            // Add notification
            $notif = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'subscription', ?)");
            $notifMsg = "Welcome! Your {$planData['name']} subscription is now active.";
            $notif->bind_param("is", $userId, $notifMsg);
            $notif->execute();

            header("Location: dashboard.php?msg=subscribed");
            exit();
        } else {
            $error = 'Subscription failed. Please try again.';
        }
    }
}

// Get plans
$plans = $conn->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");

// Get addresses
$addrs = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
$addrs->bind_param("i", $userId);
$addrs->execute();
$addresses = $addrs->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscribe - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .plan-card { border: 2px solid #e0e0e0; border-radius: 12px; padding: 25px; cursor: pointer; transition: .3s; }
        .plan-card:hover, .plan-card.selected { border-color: #2c7a2c; background: #f0faf0; }
        .plan-card.vip { border-color: #ffd700; }
        .plan-card.vip.selected { background: #fffbea; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">📦 FreshBox</a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4" style="max-width:700px">
    <h3 class="mb-1">Choose Your Plan</h3>
    <p class="text-muted">Select a subscription plan to get started</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-3 mb-4">
        <?php while ($plan = $plans->fetch_assoc()): ?>
            <div class="col-md-4">
                <label class="plan-card <?= $plan['tier'] ?> d-block" onclick="selectPlan(this, <?= $plan['id'] ?>)">
                    <input type="radio" name="plan_id" value="<?= $plan['id'] ?>" class="d-none" required>
                    <div class="fw-bold fs-5"><?= htmlspecialchars($plan['name']) ?></div>
                    <div class="text-muted small mb-2"><?= htmlspecialchars($plan['theme']) ?></div>
                    <div class="fs-4 fw-bold text-success"><?= $plan['price'] ?> EGP<span class="fs-6 text-muted">/mo</span></div>
                    <hr>
                    <div class="small">✓ <?= $plan['max_swaps'] ?> swaps/month</div>
                    <div class="small">✓ <?= ucfirst($plan['box_size']) ?> box</div>
                    <?php if ($plan['early_swap_access']): ?>
                        <div class="small">⭐ Early swap access</div>
                    <?php endif; ?>
                </label>
            </div>
        <?php endwhile; ?>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Delivery Address</label>
            <?php if (empty($addresses)): ?>
                <div class="alert alert-warning">No addresses found. <a href="add_address.php">Add one first</a>.</div>
            <?php else: ?>
                <select name="address_id" class="form-select" required>
                    <option value="">Select address...</option>
                    <?php foreach ($addresses as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= !$a['is_serviceable'] ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($a['label'] . ' — ' . $a['full_address']) ?>
                            <?= !$a['is_serviceable'] ? ' (Not serviceable)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5">Subscribe Now</button>
    </form>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function selectPlan(el, id) {
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input').checked = true;
}
</script>
</body>
</html>