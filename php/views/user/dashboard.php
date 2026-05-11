<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .card { border:none; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,.07); }
        .quick-action { background:white; border-radius:12px; padding:20px; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,.07); transition:.3s; text-decoration:none; color:#333; display:block; }
        .quick-action:hover { transform:translateY(-3px); color:#2c7a2c; }
        .quick-action .icon { font-size:2rem; margin-bottom:8px; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?controller=user&action=dashboard">📦 FreshBox</a>
        <div class="d-flex align-items-center gap-3">
            <a href="index.php?controller=user&action=notifications" class="text-white text-decoration-none">
                🔔 <?php if ($notifCount > 0): ?><span class="badge bg-danger"><?= $notifCount ?></span><?php endif; ?>
            </a>
            <span class="text-white">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
            <a href="index.php?controller=auth&action=logout" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'subscribed'): ?>
        <div class="alert alert-success">🎉 Subscription activated!</div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8">
            <?php if ($subscription): ?>
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1"><?= htmlspecialchars($subscription['plan_name']) ?></h5>
                        <span class="badge bg-success">Active</span>
                    </div>
                    <span class="badge bg-primary"><?= strtoupper($subscription['tier']) ?></span>
                </div>
                <div class="row text-center mb-3">
                    <div class="col"><div class="text-muted small">Next Billing</div><div class="fw-bold"><?= $subscription['next_billing'] ?></div></div>
                    <div class="col"><div class="text-muted small">Price</div><div class="fw-bold"><?= $subscription['price'] ?> EGP/mo</div></div>
                    <div class="col"><div class="text-muted small">Swaps</div><div class="fw-bold"><?= $subscription['max_swaps'] ?></div></div>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?controller=user&action=manageSubscription" class="btn btn-outline-success btn-sm">Manage</a>
                    <a href="index.php?controller=user&action=customizeBox" class="btn btn-success btn-sm">Customize Box</a>
                </div>
            </div>
            <?php else: ?>
            <div class="card p-4 text-center">
                <h5>No Active Subscription</h5>
                <a href="index.php?controller=user&action=subscribe" class="btn btn-success mt-2">Subscribe Now</a>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <div class="card p-4 text-center">
                <div style="font-size:2rem">💰</div>
                <div class="text-muted small mt-2">Wallet Credit</div>
                <h4 class="text-success fw-bold"><?= number_format($wallet, 2) ?> EGP</h4>
            </div>
        </div>
    </div>

    <h6 class="text-muted text-uppercase mb-3">Quick Actions</h6>
    <div class="row g-3">
        <div class="col-6 col-md-3"><a href="index.php?controller=user&action=customizeBox" class="quick-action"><div class="icon">📦</div><div class="fw-bold">Customize Box</div></a></div>
        <div class="col-6 col-md-3"><a href="index.php?controller=user&action=orderBox" class="quick-action"><div class="icon">🛒</div><div class="fw-bold">Order Box</div></a></div>
        <div class="col-6 col-md-3"><a href="index.php?controller=user&action=preferences" class="quick-action"><div class="icon">🥗</div><div class="fw-bold">Preferences</div></a></div>
        <div class="col-6 col-md-3"><a href="index.php?controller=user&action=addAddress" class="quick-action"><div class="icon">📍</div><div class="fw-bold">Addresses</div></a></div>
        <div class="col-6 col-md-3"><a href="index.php?controller=user&action=giftBox" class="quick-action"><div class="icon">🎁</div><div class="fw-bold">Gift a Box</div></a></div>
        <div class="col-6 col-md-3"><a href="index.php?controller=user&action=shareBox" class="quick-action"><div class="icon">📱</div><div class="fw-bold">Share Box</div></a></div>
        <div class="col-6 col-md-3"><a href="index.php?controller=user&action=notifications" class="quick-action"><div class="icon">🔔</div><div class="fw-bold">Notifications</div></a></div>
        <div class="col-6 col-md-3"><a href="index.php?controller=user&action=recommendations" class="quick-action"><div class="icon">✨</div><div class="fw-bold">For You</div></a></div>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>