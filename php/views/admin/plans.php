<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Plans - FreshBox Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#212529; color:#f8f9fa; }
        .main-card { background:white; color:#333; border-radius:15px; padding:30px; box-shadow:0 8px 30px rgba(0,0,0,.5); margin-bottom:25px; }
        .main-card h2 { color:#2c7a2c; font-weight:bold; }
        .form-control, .form-select { border:1px solid #ddd; border-radius:8px; padding:10px; }
        .form-control:focus, .form-select:focus { border-color:#2c7a2c; box-shadow:none; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="index.php?controller=admin&action=dashboard">📦 Admin Control</a>
        <a href="index.php?controller=admin&action=dashboard" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-5">
    <?php if (!empty($msg)): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="main-card mb-4">
        <h2>Subscription Plans</h2>
        <hr>
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr><th>Name</th><th>Theme</th><th>Tier</th><th>Price</th><th>Cycle</th><th>Swaps</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php foreach ($plans as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['theme']) ?></td>
                    <td><span class="badge bg-primary"><?= $p['tier'] ?></span></td>
                    <td><?= $p['price'] ?> EGP</td>
                    <td><?= $p['billing_cycle'] ?></td>
                    <td><?= $p['max_swaps'] ?></td>
                    <td><span class="badge bg-<?= $p['is_active'] ? 'success' : 'danger' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td>
                        <form method="POST" action="index.php?controller=admin&action=plans" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-warning"><?= $p['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="main-card">
        <h5>Add New Plan</h5>
        <form method="POST" action="index.php?controller=admin&action=plans">
            <input type="hidden" name="action" value="add">
            <div class="row g-3">
                <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Plan Name" required></div>
                <div class="col-md-4"><input type="text" name="theme" class="form-control" placeholder="Theme" required></div>
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
                        <label class="form-check-label text-white" for="earlySwap">Early Swap Access (VIP perk)</label>
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