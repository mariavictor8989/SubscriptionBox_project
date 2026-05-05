<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId = $_SESSION['user_id'];

// Get active subscription
$sub = $conn->prepare("
    SELECT us.*, sp.name AS plan_name, sp.theme, sp.price, sp.max_swaps
    FROM user_subscriptions us
    JOIN subscription_plans sp ON sp.id = us.plan_id
    WHERE us.user_id = ? AND us.status = 'active'
    LIMIT 1
");
$sub->bind_param("i", $userId);
$sub->execute();
$subscription = $sub->get_result()->fetch_assoc();

// Get notifications count
$notif = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
$notif->bind_param("i", $userId);
$notif->execute();
$notifCount = $notif->get_result()->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .tier-badge { font-size: 0.9rem; padding: 5px 15px; border-radius: 20px; }
        .vip-theme { border-left: 5px solid #ffd700; }
        .premium-theme { border-left: 5px solid #3e9f3e; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.07); }
        .nav-link-custom { color: rgba(255,255,255,0.85) !important; font-weight: 500; }
        .nav-link-custom:hover { color: white !important; }
        .quick-action { background: white; border-radius: 12px; padding: 20px; text-align: center;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.07); transition: 0.3s; text-decoration: none; color: #333; display: block; }
        .quick-action:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); color: #2c7a2c; }
        .quick-action .icon { font-size: 2rem; margin-bottom: 10px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">📦 FreshBox</a>
        <div class="d-flex align-items-center gap-3">
            <a href="notifications.php" class="nav-link-custom position-relative">
                🔔
                <?php if ($notifCount > 0): ?>
                    <span class="badge bg-danger position-absolute" style="top:-5px;right:-8px;font-size:10px"><?= $notifCount ?></span>
                <?php endif; ?>
            </a>
            <span class="text-white">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <!-- Subscription Card -->
    <div class="row mb-4">
        <div class="col-md-8">
            <?php if ($subscription): ?>
            <div class="card p-4 <?= $_SESSION['user_tier'] === 'vip' ? 'vip-theme' : ($_SESSION['user_tier'] === 'premium' ? 'premium-theme' : '') ?>">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1"><?= htmlspecialchars($subscription['plan_name']) ?></h5>
                        <span class="badge bg-success">Active</span>
                    </div>
                    <span class="badge bg-primary tier-badge"><?= strtoupper($_SESSION['user_tier']) ?></span>
                </div>
                <div class="row text-center">
                    <div class="col">
                        <div class="text-muted small">Next Billing</div>
                        <div class="fw-bold"><?= $subscription['next_billing'] ?></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Price</div>
                        <div class="fw-bold"><?= $subscription['price'] ?> EGP/mo</div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Swaps Left</div>
                        <div class="fw-bold"><?= $subscription['max_swaps'] ?></div>
                    </div>
                </div>
                <hr>
                <div class="d-flex gap-2">
                    <a href="manage_subscription.php" class="btn btn-outline-success btn-sm">Manage Subscription</a>
                    <a href="customize_box.php" class="btn btn-success btn-sm">Customize My Box</a>
                </div>
            </div>
            <?php else: ?>
            <div class="card p-4 text-center">
                <h5>No Active Subscription</h5>
                <p class="text-muted">Subscribe to start receiving your monthly box!</p>
                <a href="subscribe.php" class="btn btn-success">Subscribe Now</a>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card p-4 text-center">
                <div style="font-size:2rem">💰</div>
                <div class="text-muted small mt-2">Wallet Credit</div>
                <h4 class="text-success fw-bold"><?= number_format($_SESSION['user_wallet'] ?? 0, 2) ?> EGP</h4>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h6 class="text-muted text-uppercase mb-3" style="letter-spacing:1px">Quick Actions</h6>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="customize_box.php" class="quick-action">
                <div class="icon">📦</div>
                <div class="fw-bold">Customize Box</div>
                <div class="small text-muted">Swap & add items</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="manage_subscription.php" class="quick-action">
                <div class="icon">⚙️</div>
                <div class="fw-bold">Manage Plan</div>
                <div class="small text-muted">Pause or upgrade</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="manage_preferences.php" class="quick-action">
                <div class="icon">🥗</div>
                <div class="fw-bold">Preferences</div>
                <div class="small text-muted">Allergies & likes</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="gift_box.php" class="quick-action">
                <div class="icon">🎁</div>
                <div class="fw-bold">Gift a Box</div>
                <div class="small text-muted">Send to a friend</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="add_address.php" class="quick-action">
                <div class="icon">📍</div>
                <div class="fw-bold">Addresses</div>
                <div class="small text-muted">Manage delivery</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="order_box.php" class="quick-action">
                <div class="icon">🛒</div>
                <div class="fw-bold">Order Box</div>
                <div class="small text-muted">Place an order</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="share_box.php" class="quick-action">
                <div class="icon">📱</div>
                <div class="fw-bold">Share Box</div>
                <div class="small text-muted">Earn points</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="notifications.php" class="quick-action">
                <div class="icon">🔔</div>
                <div class="fw-bold">Notifications</div>
                <div class="small text-muted"><?= $notifCount ?> unread</div>
            </a>
        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>