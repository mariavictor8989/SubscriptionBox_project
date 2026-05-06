<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 3) { header("Location: login.html"); exit(); }

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId   = (int) $_POST['order_id'];
    $newStatus = $_POST['new_status'];
    $allowed   = ['out_for_delivery', 'delivered'];

    if (in_array($newStatus, $allowed)) {
        // Update shipment
        $upd = $conn->prepare("UPDATE shipments SET status = ? WHERE order_id = ?");
        $upd->bind_param("si", $newStatus, $orderId);
        $upd->execute();

        // Update box if delivered
        if ($newStatus === 'delivered') {
            $upd2 = $conn->prepare("
                UPDATE boxes b JOIN orders o ON o.box_id = b.id
                SET b.status = 'delivered' WHERE o.id = ?
            ");
            $upd2->bind_param("i", $orderId);
            $upd2->execute();
        }

        // Notify user
        $userStmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
        $userStmt->bind_param("i", $orderId);
        $userStmt->execute();
        $userId = $userStmt->get_result()->fetch_assoc()['user_id'];

        $notif    = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'delivery', ?)");
        $notifMsg = $newStatus === 'delivered'
            ? "📦 Your order #$orderId has been delivered! Enjoy your box!"
            : "🚚 Your order #$orderId is out for delivery! Keep an eye out.";
        $notif->bind_param("is", $userId, $notifMsg);
        $notif->execute();

        $msg = "✅ Order #$orderId updated to: " . ucfirst(str_replace('_', ' ', $newStatus));
    }
}

// Get orders for delivery (shipped or out_for_delivery)
$orders = $conn->query("
    SELECT o.id AS order_id, o.created_at, o.amount,
           u.full_name, u.user_email,
           s.status AS ship_status,
           a.full_address, a.city, a.label AS addr_label,
           a.postal_code,
           sp.name AS plan_name,
           b.box_type, b.id AS box_id
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN shipments s ON s.order_id = o.id
    JOIN addresses a ON a.id = o.address_id
    JOIN user_subscriptions us ON us.id = o.subscription_id
    JOIN subscription_plans sp ON sp.id = us.plan_id
    JOIN boxes b ON b.id = o.box_id
    WHERE s.status IN ('shipped', 'out_for_delivery')
    ORDER BY o.created_at ASC
");

// Stats
$totalOrders    = $orders->num_rows;
$outForDelivery = 0;
$shipped        = 0;
$tempOrders     = [];
while ($o = $orders->fetch_assoc()) {
    $tempOrders[] = $o;
    if ($o['ship_status'] === 'out_for_delivery') $outForDelivery++;
    if ($o['ship_status'] === 'shipped') $shipped++;
}

// Group by city
$byCity = [];
foreach ($tempOrders as $o) {
    $byCity[$o['city']][] = $o;
}
ksort($byCity);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Dashboard - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #0f3460; color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .main-card { background: white; color: #333; border-radius: 15px; padding: 25px; box-shadow: 0 8px 30px rgba(0,0,0,.5); margin-bottom: 25px; }
        .main-card h4 { color: #0f3460; font-weight: bold; }
        .stat-card { background: #16213e; border-radius: 12px; padding: 20px; text-align: center; }
        .stat-card .num { font-size: 2rem; font-weight: 900; color: #00b4d8; }
        .order-card { border-radius: 12px; border: none; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .order-card.shipped    { border-left: 5px solid #0dcaf0; }
        .order-card.out        { border-left: 5px solid #f5a623; }
        .city-header { background: #0f3460; color: white; border-radius: 8px; padding: 8px 16px; margin-bottom: 12px; font-weight: bold; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <span class="navbar-brand fw-bold text-info">🚚 Delivery Dashboard</span>
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
        <div class="col-md-4">
            <div class="stat-card">
                <div class="num"><?= $totalOrders ?></div>
                <div class="text-muted small">Total Orders Today</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="num" style="color:#f5a623"><?= $outForDelivery ?></div>
                <div class="text-muted small">Out for Delivery 🚚</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="num" style="color:#0dcaf0"><?= $shipped ?></div>
                <div class="text-muted small">Ready to Deliver 📦</div>
            </div>
        </div>
    </div>

    <?php if (empty($tempOrders)): ?>
    <div class="main-card text-center py-5">
        <div style="font-size:4rem">📭</div>
        <h5 class="mt-3">No orders assigned for delivery.</h5>
        <p class="text-muted">Check back later!</p>
    </div>

    <?php else: ?>
    <div class="main-card">
        <h4>📍 Orders Grouped by Area</h4>
        <p class="text-muted small">Deliver orders in the same area together to save time.</p>
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

                    <!-- Order Info -->
                    <div class="col-md-3">
                        <div class="fw-bold fs-6">Order #<?= $o['order_id'] ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($o['plan_name']) ?></div>
                        <div class="text-muted small"><?= date('d M Y', strtotime($o['created_at'])) ?></div>
                        <div class="fw-bold text-success small"><?= number_format($o['amount'], 2) ?> EGP</div>
                    </div>

                    <!-- Customer -->
                    <div class="col-md-3">
                        <div class="fw-bold"><?= htmlspecialchars($o['full_name']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($o['user_email']) ?></div>
                    </div>

                    <!-- Address -->
                    <div class="col-md-3">
                        <div class="small">
                            <span class="badge bg-light text-dark mb-1"><?= htmlspecialchars($o['addr_label']) ?></span><br>
                            📍 <?= htmlspecialchars($o['full_address']) ?><br>
                            <?= htmlspecialchars($o['city']) ?>
                            <?php if ($o['postal_code']): ?>
                                <span class="text-muted">(<?= $o['postal_code'] ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Status & Update -->
                    <div class="col-md-3">
                        <div class="mb-2">
                            <?php if ($o['ship_status'] === 'shipped'): ?>
                                <span class="badge bg-info">📦 Ready to Deliver</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">🚚 Out for Delivery</span>
                            <?php endif; ?>
                        </div>
                        <form method="POST" class="d-flex gap-1">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <select name="new_status" class="form-select form-select-sm">
                                <option value="out_for_delivery" <?= $o['ship_status']==='out_for_delivery'?'selected':'' ?>>
                                    🚚 Out for Delivery
                                </option>
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