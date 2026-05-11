<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Inventory - FreshBox Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #212529; color: #f8f9fa; }
        .main-card { background: white; color: #333; border-radius: 15px; padding: 30px; box-shadow: 0 8px 30px rgba(0,0,0,.5); margin-bottom: 30px; }
        .main-card h2 { color: #2c7a2c; font-weight: bold; }
        .low-stock { background: #fff3cd !important; }
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
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="main-card">
        <h2>Inventory Management</h2>
        <p class="text-muted">⚠️ Items highlighted in yellow are below reorder threshold.</p>
        <hr>
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th><th>Name</th><th>Theme</th><th>Weight</th>
                    <th>Stock</th><th>Reserved</th><th>Threshold</th>
                    <th>Flags</th><th>Update Stock</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($item = $items->fetch_assoc()): ?>
                <tr class="<?= $item['low_stock'] ? 'low-stock' : '' ?>">
                    <td><?= $item['id'] ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['theme']) ?></td>
                    <td><?= $item['weight_g'] ?>g</td>
                    <td class="fw-bold <?= $item['low_stock'] ? 'text-danger' : 'text-success' ?>">
                        <?= $item['stock_qty'] ?>
                        <?php if ($item['low_stock']): ?> ⚠️<?php endif; ?>
                    </td>
                    <td><?= $item['reserved_qty'] ?></td>
                    <td><?= $item['reorder_threshold'] ?></td>
                    <td>
                        <?php if ($item['is_limited']): ?><span class="badge bg-danger">Limited</span><?php endif; ?>
                        <?php if ($item['is_vip_only']): ?><span class="badge bg-warning text-dark">VIP</span><?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="index.php?controller=admin&action=manageInventory" class="d-flex gap-1">
                            <input type="hidden" name="action" value="update_stock">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="number" name="stock_qty" class="form-control form-control-sm" style="width:80px" value="<?= $item['stock_qty'] ?>" min="0">
                            <button class="btn btn-sm btn-outline-success">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="main-card">
        <h5>Add New Item</h5>
        <form method="POST" action="index.php?controller=admin&action=manageInventory">
            <input type="hidden" name="action" value="add_item">
            <div class="row g-3">
                <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Item Name" required></div>
                <div class="col-md-4"><input type="text" name="theme" class="form-control" placeholder="Theme" required></div>
                <div class="col-md-4"><input type="number" name="weight_g" class="form-control" placeholder="Weight (grams)" step="0.01" required></div>
                <div class="col-md-8"><textarea name="description" class="form-control" placeholder="Description" rows="2"></textarea></div>
                <div class="col-md-2"><input type="number" name="stock_qty" class="form-control" placeholder="Initial Stock" min="0" required></div>
                <div class="col-md-2"><input type="number" name="reorder_threshold" class="form-control" placeholder="Reorder At" min="0" value="10"></div>
                <div class="col-12 d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_limited" id="isLimited">
                        <label class="form-check-label" for="isLimited" style="color: #333;">Limited Edition</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_vip_only" id="isVip">
                        <label class="form-check-label" for="isVip" style="color: #333;">VIP Only</label>
                    </div>
                </div>
                <div class="col-12"><button type="submit" class="btn btn-success">Add Item</button></div>
            </div>
        </form>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>