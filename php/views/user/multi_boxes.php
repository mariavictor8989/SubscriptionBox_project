<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Boxes - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .box-card { background:white; border-radius:14px; padding:25px; box-shadow:0 4px 15px rgba(0,0,0,.07); margin-bottom:20px; border-left:5px solid #2c7a2c; }
        .box-card.paused { border-left-color:#ffc107; }
        .box-card.cancelled { border-left-color:#dc3545; opacity:.7; }
        .tier-pill { padding:4px 14px; border-radius:20px; font-size:.8rem; font-weight:bold; }
        .tier-vip { background:#ffd700; color:#333; }
        .tier-premium { background:#3e9f3e; color:white; }
        .tier-standard { background:#6c757d; color:white; }
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">My Boxes</h3>
            <p class="text-muted small">Manage all your subscriptions in one place</p>
        </div>
        <a href="index.php?controller=user&action=subscribe" class="btn btn-success">+ Add New Box</a>
    </div>

    <?php if (empty($subscriptions)): ?>
        <div class="text-center py-5">
            <div style="font-size:3rem">📦</div>
            <p class="text-muted">No subscriptions yet.</p>
            <a href="index.php?controller=user&action=subscribe" class="btn btn-success">Subscribe Now</a>
        </div>
    <?php endif; ?>

    <?php foreach ($subscriptions as $sub): ?>
    <div class="box-card <?= $sub['status'] ?>">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="mb-1"><?= htmlspecialchars($sub['plan_name']) ?></h5>
                <span class="text-muted small"><?= htmlspecialchars($sub['theme']) ?></span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="tier-pill tier-<?= $sub['tier'] ?? 'standard' ?>"><?= strtoupper($sub['tier'] ?? 'STANDARD') ?></span>
                <?php $sc = $sub['status'] === 'active' ? 'success' : ($sub['status'] === 'paused' ? 'warning' : 'danger'); ?>
                <span class="badge bg-<?= $sc ?>"><?= ucfirst($sub['status']) ?></span>
            </div>
        </div>

        <div class="row text-center mb-3">
            <div class="col"><div class="text-muted small">Price</div><div class="fw-bold"><?= $sub['price'] ?> EGP/mo</div></div>
            <div class="col"><div class="text-muted small">Next Billing</div><div class="fw-bold"><?= $sub['next_billing'] ?></div></div>
            <div class="col"><div class="text-muted small">Started</div><div class="fw-bold"><?= $sub['start_date'] ?></div></div>
        </div>

        <?php if ($sub['status'] === 'active'): ?>
        <div class="d-flex gap-2 flex-wrap">
            <a href="index.php?controller=user&action=customizeBox" class="btn btn-success btn-sm">✏️ Customize</a>
            <a href="index.php?controller=user&action=manageSubscription" class="btn btn-outline-warning btn-sm">⏸ Pause</a>
            <a href="index.php?controller=user&action=orderBox" class="btn btn-outline-primary btn-sm">🛒 Order</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>