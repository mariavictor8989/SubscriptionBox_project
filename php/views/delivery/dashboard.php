<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Dashboard - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#0f3460; color:#f8f9fa; font-family:'Segoe UI',sans-serif; }
        .main-card { background:white; color:#333; border-radius:15px; padding:25px; box-shadow:0 8px 30px rgba(0,0,0,.5); margin-bottom:25px; }
        .main-card h4 { color:#0f3460; font-weight:bold; }
        .stat-card { background:#16213e; border-radius:12px; padding:20px; text-align:center; }
        .stat-card .num { font-size:2rem; font-weight:900; color:#00b4d8; }
        .stat-card .label { color:#ffffff; font-size:.85rem; font-weight:600; margin-top:5px; }
        .order-card { border-radius:12px; border:none; margin-bottom:12px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .order-card.shipped { border-left:5px solid #0dcaf0; }
        .order-card.out { border-left:5px solid #f5a623; }
        .city-header { background:#0f3460; color:white; border-radius:8px; padding:8px 16px; margin-bottom:12px; font-weight:bold; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <span class="navbar-brand fw-bold text-info">🚚 Delivery Dashboard</span>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-white small">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="index.php?controller=auth&action=logout" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat-card"><div class="num"><?= count($orders) ?></div><div class="label">My Orders Today</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="num" style="color:#f5a623"><?= $outForDelivery ?></div><div class="label">Out for Delivery 🚚</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="num" style="color:#0dcaf0"><?= $shipped ?></div><div class="label">Ready to Deliver 📦</div></div></div>
    </div>

    <?php if (empty($orders)): ?>
    <div class="main-card text-center py-5">
        <div style="font-size:4rem">📭</div>
        <h5 class="mt-3">No orders assigned to you yet.</h5>
        <p class="text-muted">The warehouse will assign orders to you soon!</p>
    </div>
    <?php else: ?>
    <div class="main-card">
        <h4>📍 My Orders — Grouped by Area</h4>
        <p class="text-muted small">Deliver orders in the same area together.</p>
        <hr>
        <?php foreach ($byCity as $city => $cityOrders): ?>
        <div class="city-header mb-3">
            📍 <?= htmlspecialchars($city) ?>
            <span class="badge bg-warning text-dark ms-2"><?= count($cityOrders) ?> orders</span>
        </div>
        <?php foreach ($cityOrders as $o): ?>
        <div class="card order-card <?= $o['ship_status'] === 'out_for_delivery' ? 'out' : 'shipped' ?>">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="fw-bold">Order #<?= $o['order_id'] ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($o['plan_name']) ?></div>
                        <div class="text-muted small"><?= date('d M Y', strtotime($o['created_at'])) ?></div>
                        <div class="fw-bold text-success small"><?= number_format($o['amount'], 2) ?> EGP</div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold"><?= htmlspecialchars($o['full_name']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($o['user_email']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="small">
                            <span class="badge bg-light text-dark mb-1"><?= htmlspecialchars($o['addr_label']) ?></span><br>
                            📍 <?= htmlspecialchars($o['full_address']) ?><br>
                            <?= htmlspecialchars($o['city']) ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <?php if ($o['ship_status'] === 'shipped'): ?>
                                <span class="badge bg-info">📦 Ready to Deliver</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">🚚 Out for Delivery</span>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="index.php?controller=delivery&action=dashboard" class="d-flex gap-1">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <select name="new_status" class="form-select form-select-sm">
                                <option value="out_for_delivery" <?= $o['ship_status']==='out_for_delivery'?'selected':'' ?>>🚚 Out for Delivery</option>
                                <option value="delivered">✅ Delivered</option>
                            </select>
                            <button class="btn btn-sm btn-success fw-bold">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="mb-4"></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>