<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) { header("Location: login.html"); exit(); }

$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_item') {
        $name      = trim($_POST['name']);
        $desc      = trim($_POST['description']);
        $theme     = trim($_POST['theme']);
        $weight    = (float) $_POST['weight_g'];
        $isLimited = isset($_POST['is_limited']) ? 1 : 0;
        $isVip     = isset($_POST['is_vip_only']) ? 1 : 0;
        $stock     = (int) $_POST['stock_qty'];
        $threshold = (int) $_POST['reorder_threshold'];

        $ins = $conn->prepare("INSERT INTO items (name, description, theme, weight_g, is_limited, is_vip_only) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->bind_param("sssdii", $name, $desc, $theme, $weight, $isLimited, $isVip);

        if ($ins->execute()) {
            $itemId = $conn->insert_id;
            $inv    = $conn->prepare("INSERT INTO inventory (item_id, stock_qty, reorder_threshold) VALUES (?, ?, ?)");
            $inv->bind_param("iii", $itemId, $stock, $threshold);
            $inv->execute();
            $msg = 'Item added to inventory!';
        } else {
            $error = 'Failed to add item.';
        }

    } elseif ($action === 'update_stock') {
        $itemId   = (int) $_POST['item_id'];
        $newStock = (int) $_POST['stock_qty'];
        $upd      = $conn->prepare("UPDATE inventory SET stock_qty = ? WHERE item_id = ?");
        $upd->bind_param("ii", $newStock, $itemId);
        $upd->execute();
        $msg = 'Stock updated!';
    }
}

// Get all items with inventory — flag low stock
$items = $conn->query("
    SELECT i.*, inv.stock_qty, inv.reserved_qty, inv.reorder_threshold,
           (inv.stock_qty <= inv.reorder_threshold) AS low_stock
    FROM items i
    JOIN inventory inv ON inv.item_id = i.id
    ORDER BY low_stock DESC, i.name ASC
");
?>
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
        <a class="navbar-brand fw-bold text-success" href="admin_dashboard.php">📦 Admin Control</a>
        <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-5">
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Inventory Table -->
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
                        <form method="POST" class="d-flex gap-1">
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

    <!-- Add Item -->
    <div class="main-card">
        <h5>Add New Item</h5>
        <form method="POST">
            <input type="hidden" name="action" value="add_item">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="name" class="form-control" placeholder="Item Name" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="theme" class="form-control" placeholder="Theme (e.g. Gourmet Meals)" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="weight_g" class="form-control" placeholder="Weight (grams)" step="0.01" required>
                </div>
                <div class="col-md-8">
                    <textarea name="description" class="form-control" placeholder="Description" rows="2"></textarea>
                </div>
                <div class="col-md-2">
                    <input type="number" name="stock_qty" class="form-control" placeholder="Initial Stock" min="0" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="reorder_threshold" class="form-control" placeholder="Reorder At" min="0" value="10">
                </div>
                <div class="col-12 d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_limited" id="isLimited">
                        <label class="form-check-label text-white" for="isLimited">Limited Edition</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_vip_only" id="isVip">
                        <label class="form-check-label text-white" for="isVip">VIP Only</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Add Item</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>