<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) { header("Location: login.html"); exit(); }

$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name    = trim($_POST['name']);
        $theme   = trim($_POST['theme']);
        $tier    = $_POST['tier'];
        $price   = (float) $_POST['price'];
        $cycle   = $_POST['billing_cycle'];
        $size    = $_POST['box_size'];
        $swaps   = (int) $_POST['max_swaps'];
        $early   = isset($_POST['early_swap_access']) ? 1 : 0;

        $ins = $conn->prepare("INSERT INTO subscription_plans (name, theme, tier, price, billing_cycle, box_size, max_swaps, early_swap_access) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("sssdssis", $name, $theme, $tier, $price, $cycle, $size, $swaps, $early);
        $ins->execute() ? $msg = 'Plan added!' : $error = 'Failed to add plan.';

    } elseif ($action === 'toggle') {
        $planId = (int) $_POST['plan_id'];
        $conn->prepare("UPDATE subscription_plans SET is_active = NOT is_active WHERE id = ?")->bind_param("i", $planId);
        $stmt = $conn->prepare("UPDATE subscription_plans SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $planId);
        $stmt->execute();
        $msg = 'Plan status updated.';
    }
}

$plans = $conn->query("SELECT * FROM subscription_plans ORDER BY price ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Plans - FreshBox Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #212529; color: #f8f9fa; }
        .main-card { background: white; color: #333; border-radius: 15px; padding: 30px; box-shadow: 0 8px 30px rgba(0,0,0,.5); }
        .main-card h2 { color: #2c7a2c; font-weight: bold; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="admin_dashboard.php">📦 Admin Control</a>
        <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-5">
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="main-card mb-4">
        <h2>Subscription Plans</h2>
        <hr>
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr><th>Name</th><th>Theme</th><th>Tier</th><th>Price</th><th>Cycle</th><th>Swaps</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php while ($p = $plans->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['theme']) ?></td>
                    <td><span class="badge bg-primary"><?= $p['tier'] ?></span></td>
                    <td><?= $p['price'] ?> EGP</td>
                    <td><?= $p['billing_cycle'] ?></td>
                    <td><?= $p['max_swaps'] ?></td>
                    <td><span class="badge bg-<?= $p['is_active'] ? 'success' : 'danger' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-warning"><?= $p['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="main-card">
        <h5>Add New Plan</h5>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row g-3">
                <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Plan Name" required></div>
                <div class="col-md-4"><input type="text" name="theme" class="form-control" placeholder="Theme (e.g. Gourmet Meals)" required></div>
                <div class="col-md-4">
                    <select name="tier" class="form-select">
                        <option value="standard">Standard</option>
                        <option value="premium">Premium</option>
                        <option value="vip">VIP</option>
                    </select>
                </div>
                <div class="col-md-3"><input type="number" name="price" class="form-control" placeholder="Price (EGP)" step="0.01" required></div>
                <div class="col-md-3">
                    <select name="billing_cycle" class="form-select">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="box_size" class="form-select">
                        <option value="small">Small</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>
                    </select>
                </div>
                <div class="col-md-3"><input type="number" name="max_swaps" class="form-control" placeholder="Max Swaps" value="2" min="0"></div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="early_swap_access" id="earlySwap">
                        <label class="form-check-label" for="earlySwap">Early Swap Access (VIP perk)</label>
                    </div>
                </div>
                <div class="col-12"><button type="submit" class="btn btn-success">Add Plan</button></div>
            </div>
        </form>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>