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
            ? "Your order #$orderId has been delivered! Enjoy your box 📦"
            : "Your order #$orderId is out for delivery! 🚚";
        $notif->bind_param("is", $userId, $notifMsg);
        $notif->execute();

        $msg = "Order #$orderId updated to: " . ucfirst(str_replace('_', ' ', $newStatus));
    }
}

// Get orders assigned to delivery
$orders = $conn->query("
    SELECT o.id AS order_id, o.created_at,
           u.full_name, u.user_email,
           s.status AS ship_status, s.carrier_name, s.tracking_number,
           a.full_address, a.city, a.label AS addr_label,
           sp.name AS plan_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN shipments s ON s.order_id = o.id
    JOIN addresses a ON a.id = o.address_id
    JOIN user_subscriptions us ON us.id = o.subscription_id
    JOIN subscription_plans sp ON sp.id = us.plan_id
    WHERE s.status IN ('shipped', 'out_for_delivery')
    ORDER BY o.created_at ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Dashboard - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #0f3460; color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .main-card { background: white; color: #333; border-radius: 15px; padding: 25px; box-shadow: 0 8px 30px rgba(0,0,0,.5); }
        .main-card h4 { color: #0f3460; font-weight: bold; }
        .order-row { border-left: 4px solid #0f3460; }
        .order-row.out { border-left-color: #f5a623; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <span class="navbar-brand fw-bold text-info">🚚 Delivery</span>
        <div class="d-flex gap-2">
            <span class="text-white small">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="main-card">
        <h4>🚚 My Delivery Orders</h4>
        <p class="text-muted small">Orders ready for delivery — grouped by area.</p>
        <hr>

        <?php if ($orders->num_rows === 0): ?>
            <div class="text-center text-muted py-4">
                <div style="font-size:3rem">📭</div>
                <p>No orders assigned for delivery.</p>
            </div>
        <?php endif; ?>

        <?php while ($o = $orders->fetch_assoc()): ?>
        <div class="card mb-3 order-row <?= $o['ship_status'] === 'out_for_delivery' ? 'out' : '' ?>">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="fw-bold">Order #<?= $o['order_id'] ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($o['plan_name']) ?></div>
                        <div class="text-muted small"><?= $o['created_at'] ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="fw-bold"><?= htmlspecialchars($o['full_name']) ?></div>
                        <div class="small">📍 <?= htmlspecialchars($o['full_address']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($o['city']) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-2">
                            <?php
                                $sc = $o['ship_status'] === 'shipped' ? 'info' : 'warning';
                            ?>
                            <span class="badge bg-<?= $sc ?>"><?= ucfirst(str_replace('_', ' ', $o['ship_status'])) ?></span>
                        </div>
                        <form method="POST" class="d-flex gap-1">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <select name="new_status" class="form-select form-select-sm">
                                <option value="out_for_delivery">Out for Delivery</option>
                                <option value="delivered">Delivered ✓</option>
                            </select>
                            <button class="btn btn-sm btn-success">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>