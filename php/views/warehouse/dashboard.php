<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warehouse Dashboard - FreshBox</title>
    <link rel="stylesheet" href=".././css/bootstrap.min.css">
    <style>
        body { background-color:#f4f6f9; font-family:'Segoe UI',sans-serif; color:#212529; }
        .navbar { background-color:#e67e22 !important; }
        .navbar-brand { color:white !important; font-weight:900; }
        .stat-card { background:white; border-radius:14px; padding:20px 25px; box-shadow:0 2px 12px rgba(0,0,0,.07); border-left:5px solid #e67e22; }
        .stat-card .num { font-size:2.2rem; font-weight:900; color:#e67e22; }
        .stat-card .label { font-size:.85rem; color:#666; font-weight:600; text-transform:uppercase; }
        .stat-card.danger { border-left-color:#dc3545; } .stat-card.danger .num { color:#dc3545; }
        .stat-card.info { border-left-color:#0dcaf0; } .stat-card.info .num { color:#0984a8; }
        .stat-card.success { border-left-color:#2c7a2c; } .stat-card.success .num { color:#2c7a2c; }
        .main-card { background:white; border-radius:14px; padding:25px; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:25px; }
        .main-card h4 { color:#e67e22; font-weight:800; }
        .table thead { background:#212529; color:white; }
        .table thead th { border:none; padding:12px; }
        .low-stock-row { background:#fff3cd !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <span class="navbar-brand">🏭 Warehouse Dashboard</span>
        <div class="d-flex gap-3 align-items-center">
            <span style="color:white;font-weight:600;">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> 👋</span>
            <a href="index.php?controller=auth&action=logout" class="btn btn-light btn-sm fw-bold">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="num"><?= count($orders) ?></div><div class="label">📦 Orders to Prepare</div></div></div>
        <div class="col-md-3"><div class="stat-card danger"><div class="num"><?= count($lowStock) ?></div><div class="label">⚠️ Low Stock</div></div></div>
        <div class="col-md-3"><div class="stat-card info"><div class="num"><?= count($returns) ?></div><div class="label">↩️ Returns</div></div></div>
        <div class="col-md-3"><div class="stat-card success"><div class="num"><?= count($deliveryUsers) ?></div><div class="label">🚚 Delivery Staff</div></div></div>
    </div>

    <!-- Orders -->
    <div class="main-card">
        <h4>📦 Orders — Pick List</h4>
        <p class="text-muted small mb-3">Prepare, pack, and assign to delivery staff.</p>
        <hr>
        <?php if (empty($orders)): ?>
            <div class="text-center py-4 text-muted"><div style="font-size:3rem">✅</div><p>All orders processed!</p></div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead><tr><th>Order #</th><th>Customer</th><th>Plan</th><th>Address</th><th>Items</th><th>Amount</th><th>Status</th><th>Assign & Update</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o):
                $sc = $o['box_status'] === 'pending' ? 'secondary' : ($o['box_status'] === 'picking' ? 'warning' : ($o['box_status'] === 'packed' ? 'info' : 'success'));
            ?>
                <tr>
                    <td class="fw-bold">#<?= $o['order_id'] ?></td>
                    <td><?= htmlspecialchars($o['full_name']) ?><br><small class="text-muted"><?= htmlspecialchars($o['user_email']) ?></small></td>
                    <td><?= htmlspecialchars($o['plan_name']) ?><br><span class="badge bg-light text-dark"><?= ucfirst($o['box_type']) ?></span></td>
                    <td><?= htmlspecialchars($o['full_address']) ?><br><small class="text-muted"><?= htmlspecialchars($o['city']) ?></small></td>
                    <td class="text-center fw-bold"><?= $o['item_count'] ?></td>
                    <td><?= number_format($o['amount'], 2) ?> EGP</td>
                    <td><span class="badge bg-<?= $sc ?>"><?= ucfirst($o['box_status']) ?></span></td>
                    <td>
                        <form method="POST" action="index.php?controller=warehouse&action=dashboard">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <select name="delivery_user_id" class="form-select form-select-sm mb-1">
                                <option value="">🚚 Assign delivery...</option>
                                <?php foreach ($deliveryUsers as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($o['delivery_user_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="d-flex gap-1">
                                <select name="new_status" class="form-select form-select-sm">
                                    <option value="picking" <?= $o['box_status']==='picking'?'selected':'' ?>>Picking</option>
                                    <option value="packed"  <?= $o['box_status']==='packed' ?'selected':'' ?>>Packed</option>
                                    <option value="shipped" <?= $o['box_status']==='shipped'?'selected':'' ?>>Shipped</option>
                                </select>
                                <button class="btn btn-sm btn-warning fw-bold">Update</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stock -->
    <div class="main-card">
        <h4>📊 Manage Stock</h4>
        <p class="text-muted small mb-3">⚠️ Yellow = below threshold.</p>
        <hr>
        <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead><tr><th>Item</th><th>Theme</th><th>Stock</th><th>Threshold</th><th>Update</th></tr></thead>
            <tbody>
            <?php foreach ($inventory as $item): ?>
                <tr class="<?= $item['low_stock'] ? 'low-stock-row' : '' ?>">
                    <td><?= htmlspecialchars($item['name']) ?><?php if ($item['low_stock']): ?> <span class="badge bg-danger">Low ⚠️</span><?php endif; ?></td>
                    <td><?= htmlspecialchars($item['theme']) ?></td>
                    <td class="fw-bold <?= $item['low_stock'] ? 'text-danger' : 'text-success' ?>"><?= $item['stock_qty'] ?></td>
                    <td><?= $item['reorder_threshold'] ?></td>
                    <td>
                        <form method="POST" action="index.php?controller=warehouse&action=dashboard" class="d-flex gap-1">
                            <input type="hidden" name="action" value="update_stock">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="number" name="stock_qty" class="form-control form-control-sm" style="width:80px" value="<?= $item['stock_qty'] ?>" min="0">
                            <button class="btn btn-sm btn-success">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Returns -->
    <div class="main-card">
        <h4>↩️ Handle Returns</h4>
        <hr>
        <?php if (empty($returns)): ?>
            <div class="text-center text-muted py-3"><div style="font-size:2rem">✅</div><p>No pending returns.</p></div>
        <?php else: ?>
        <table class="table table-bordered align-middle">
            <thead><tr><th>Return #</th><th>Order #</th><th>Customer</th><th>Reason</th><th>Status</th><th>Update</th></tr></thead>
            <tbody>
            <?php foreach ($returns as $r): ?>
                <tr>
                    <td>#<?= $r['id'] ?></td>
                    <td>#<?= $r['order_id'] ?></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><?= htmlspecialchars($r['reason']) ?></td>
                    <td><span class="badge bg-warning text-dark"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <form method="POST" action="index.php?controller=warehouse&action=dashboard" class="d-flex gap-1">
                            <input type="hidden" name="action" value="handle_return">
                            <input type="hidden" name="return_id" value="<?= $r['id'] ?>">
                            <select name="return_status" class="form-select form-select-sm" style="width:130px">
                                <option value="in_transit">In Transit</option>
                                <option value="received">Received</option>
                                <option value="refunded">Refunded</option>
                            </select>
                            <button class="btn btn-sm btn-warning">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<script src="../../js/bootstrap.bundle.min.js"></script>
</body>
</html>