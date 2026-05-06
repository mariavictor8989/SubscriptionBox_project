<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 2) { header("Location: login.html"); exit(); }

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update order status
    if ($action === 'update_status') {
        $orderId   = (int) $_POST['order_id'];
        $newStatus = $_POST['new_status'];
        $allowed   = ['picking', 'packed', 'shipped'];

        if (in_array($newStatus, $allowed)) {
            // Update box status
            $upd = $conn->prepare("
                UPDATE boxes b JOIN orders o ON o.box_id = b.id
                SET b.status = ? WHERE o.id = ?
            ");
            $upd->bind_param("si", $newStatus, $orderId);
            $upd->execute();

            // Update shipment
            $upd2 = $conn->prepare("UPDATE shipments SET status = ? WHERE order_id = ?");
            $upd2->bind_param("si", $newStatus, $orderId);
            $upd2->execute();

            // Notify user
            $userStmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
            $userStmt->bind_param("i", $orderId);
            $userStmt->execute();
            $userId = $userStmt->get_result()->fetch_assoc()['user_id'];

            $notif    = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'delivery', ?)");
            $notifMsg = "Your order #$orderId status updated to: " . ucfirst($newStatus);
            $notif->bind_param("is", $userId, $notifMsg);
            $notif->execute();

            $msg = "✅ Order #$orderId updated to: " . ucfirst($newStatus);
        }

    // Handle return
    } elseif ($action === 'handle_return') {
        $returnId  = (int) $_POST['return_id'];
        $newStatus = $_POST['return_status'];
        $allowed   = ['in_transit', 'received', 'refunded'];

        if (in_array($newStatus, $allowed)) {
            $upd = $conn->prepare("UPDATE returns SET status = ? WHERE id = ?");
            $upd->bind_param("si", $newStatus, $returnId);
            $upd->execute();
            $msg = "✅ Return #$returnId updated to: " . ucfirst($newStatus);
        }

    // Manage stock
    } elseif ($action === 'update_stock') {
        $itemId   = (int) $_POST['item_id'];
        $newStock = (int) $_POST['stock_qty'];
        $upd = $conn->prepare("UPDATE inventory SET stock_qty = ? WHERE item_id = ?");
        $upd->bind_param("ii", $newStock, $itemId);
        $upd->execute();
        $msg = "✅ Stock updated!";
    }
}

// Get orders to prepare (pending, picking, packed)
$orders = $conn->query("
    SELECT o.id AS order_id, o.amount, o.created_at,
           u.full_name, u.user_email,
           b.status AS box_status, b.id AS box_id,
           b.box_type, b.lock_at,
           sp.name AS plan_name,
           a.full_address, a.city,
           COUNT(bi.id) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN boxes b ON b.id = o.box_id
    JOIN user_subscriptions us ON us.id = o.subscription_id
    JOIN subscription_plans sp ON sp.id = us.plan_id
    JOIN addresses a ON a.id = o.address_id
    LEFT JOIN box_items bi ON bi.box_id = b.id
    WHERE b.status IN ('pending','customizing','picking','packed')
    GROUP BY o.id
    ORDER BY o.created_at ASC
");

// Get low stock items
$lowStock = $conn->query("
    SELECT i.id, i.name, i.theme, inv.stock_qty, inv.reorder_threshold
    FROM items i
    JOIN inventory inv ON inv.item_id = i.id
    WHERE inv.stock_qty <= inv.reorder_threshold AND i.is_active = 1
    ORDER BY inv.stock_qty ASC
");

// Get returns
$returns = $conn->query("
    SELECT r.*, u.full_name, o.id AS order_id
    FROM returns r
    JOIN users u ON u.id = r.user_id
    JOIN orders o ON o.id = r.order_id
    WHERE r.status IN ('requested','in_transit')
    ORDER BY r.created_at DESC
");

// Get all inventory for stock management
$inventory = $conn->query("
    SELECT i.id, i.name, i.theme, inv.stock_qty, inv.reorder_threshold,
           (inv.stock_qty <= inv.reorder_threshold) AS low_stock
    FROM items i
    JOIN inventory inv ON inv.item_id = i.id
    WHERE i.is_active = 1
    ORDER BY low_stock DESC, i.name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warehouse Dashboard - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #1a1a2e; color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .main-card { background: white; color: #333; border-radius: 15px; padding: 25px; box-shadow: 0 8px 30px rgba(0,0,0,.5); margin-bottom: 25px; }
        .main-card h4 { color: #e67e22; font-weight: bold; }
        .low-stock-row { background: #fff3cd !important; }
        .stat-card { background: #2d2d44; border-radius: 12px; padding: 20px; text-align: center; }
        .stat-card .num { font-size: 2rem; font-weight: 900; color: #e67e22; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <span class="navbar-brand fw-bold text-warning">🏭 Warehouse</span>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-white small">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="num"><?= $orders->num_rows ?></div>
                <div class="text-muted small">Orders to Prepare</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="num"><?= $lowStock->num_rows ?></div>
                <div class="text-muted small" style="color:#e74c3c!important">Low Stock Items ⚠️</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="num"><?= $returns->num_rows ?></div>
                <div class="text-muted small">Pending Returns</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="num"><?= date('d M') ?></div>
                <div class="text-muted small">Today</div>
            </div>
        </div>
    </div>

    <!-- Orders Pick List -->
    <div class="main-card">
        <h4>📦 Orders — Pick List</h4>
        <p class="text-muted small">Prepare, pack, and ship orders below.</p>
        <hr>

        <?php if ($orders->num_rows === 0): ?>
            <div class="text-center text-muted py-4">
                <div style="font-size:3rem">✅</div>
                <p>All orders are processed!</p>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Plan</th>
                    <th>Address</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $orders->data_seek(0);
            while ($o = $orders->fetch_assoc()):
                $sc = $o['box_status'] === 'pending' ? 'secondary' :
                     ($o['box_status'] === 'picking' ? 'warning' :
                     ($o['box_status'] === 'packed'  ? 'info' : 'success'));
            ?>
                <tr>
                    <td class="fw-bold">#<?= $o['order_id'] ?></td>
                    <td>
                        <?= htmlspecialchars($o['full_name']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($o['user_email']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($o['plan_name']) ?><br>
                        <small class="badge bg-light text-dark"><?= ucfirst($o['box_type']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($o['full_address']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($o['city']) ?></small>
                    </td>
                    <td class="text-center fw-bold"><?= $o['item_count'] ?></td>
                    <td><?= number_format($o['amount'], 2) ?> EGP</td>
                    <td><span class="badge bg-<?= $sc ?>"><?= ucfirst($o['box_status']) ?></span></td>
                    <td>
                        <form method="POST" class="d-flex gap-1">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <select name="new_status" class="form-select form-select-sm" style="width:110px">
                                <option value="picking" <?= $o['box_status']==='picking' ? 'selected':'' ?>>Picking</option>
                                <option value="packed"  <?= $o['box_status']==='packed'  ? 'selected':'' ?>>Packed</option>
                                <option value="shipped" <?= $o['box_status']==='shipped' ? 'selected':'' ?>>Shipped</option>
                            </select>
                            <button class="btn btn-sm btn-warning">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Stock Management -->
    <div class="main-card">
        <h4>📊 Manage Stock</h4>
        <p class="text-muted small">⚠️ Yellow rows = below reorder threshold.</p>
        <hr>
        <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr><th>Item</th><th>Theme</th><th>Stock</th><th>Threshold</th><th>Update Stock</th></tr>
            </thead>
            <tbody>
            <?php while ($item = $inventory->fetch_assoc()): ?>
                <tr class="<?= $item['low_stock'] ? 'low-stock-row' : '' ?>">
                    <td>
                        <?= htmlspecialchars($item['name']) ?>
                        <?php if ($item['low_stock']): ?><span class="badge bg-danger ms-1">Low ⚠️</span><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['theme']) ?></td>
                    <td class="fw-bold <?= $item['low_stock'] ? 'text-danger' : 'text-success' ?>">
                        <?= $item['stock_qty'] ?>
                    </td>
                    <td><?= $item['reorder_threshold'] ?></td>
                    <td>
                        <form method="POST" class="d-flex gap-1">
                            <input type="hidden" name="action" value="update_stock">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="number" name="stock_qty" class="form-control form-control-sm"
                                   style="width:80px" value="<?= $item['stock_qty'] ?>" min="0">
                            <button class="btn btn-sm btn-success">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Returns -->
    <div class="main-card">
        <h4>↩️ Handle Returns</h4>
        <hr>
        <?php if ($returns->num_rows === 0): ?>
            <div class="text-center text-muted py-3">
                <div style="font-size:2rem">✅</div>
                <p>No pending returns.</p>
            </div>
        <?php else: ?>
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr><th>Return #</th><th>Order #</th><th>Customer</th><th>Reason</th><th>Status</th><th>Update</th></tr>
            </thead>
            <tbody>
            <?php while ($r = $returns->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $r['id'] ?></td>
                    <td>#<?= $r['order_id'] ?></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><?= htmlspecialchars($r['reason']) ?></td>
                    <td><span class="badge bg-warning text-dark"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <form method="POST" class="d-flex gap-1">
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
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>