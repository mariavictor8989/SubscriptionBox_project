<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header("Location: login.html");
    exit();
}

$search = '';
$params = [];
$types  = '';
$where  = '';

if (!empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $where  = " WHERE full_name LIKE ? OR user_email LIKE ?";
    $like   = "%$search%";
    $params = [$like, $like];
    $types  = 'ss';
}

$stmt = $conn->prepare("SELECT id, full_name, user_email, subscription_tier, role, status FROM users" . $where);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result     = $stmt->get_result();
$totalUsers = $result->num_rows;

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #212529; color: #f8f9fa; }
        .stat-card { background: #f8f9fa; border: none; border-radius: 12px; transition: .3s; }
        .stat-card:hover { transform: translateY(-3px); }
        .user-management-card {
            background-color: white; color: #333;
            border-radius: 15px; padding: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,.5);
        }
        .user-management-card h2 { color: #2c7a2c; font-weight: bold; }
        .user-management-card .table { color: #333; margin-top: 20px; }
        .badge-vip      { background-color: #ffd700; color: #333; }
        .badge-premium  { background-color: #3e9f3e; color: #fff; }
        .badge-standard { background-color: #6c757d; color: #fff; }
        .badge-admin    { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <span class="navbar-brand fw-bold text-success">📦 Admin Control</span>
        <div class="d-flex gap-2">
            <a href="manage_plans.php" class="btn btn-outline-success btn-sm">Plans</a>
            <a href="manage_inventory.php" class="btn btn-outline-warning btn-sm">Inventory</a>
            <a href="system_logs.php" class="btn btn-outline-info btn-sm">Logs</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">

    <?php if ($msg === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible">User deleted successfully. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success alert-dismissible">User updated successfully. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($msg === 'added'): ?>
        <div class="alert alert-success alert-dismissible">User added successfully. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card stat-card p-4 shadow-sm">
                <h6 class="text-uppercase text-muted">Total Users</h6>
                <h2 class="text-primary mb-0"><?= $totalUsers ?></h2>
            </div>
        </div>
    </div>

    <div class="card user-management-card shadow-lg">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>User Management</h2>
                <div class="d-flex gap-2 align-items-center">
                    <span class="small text-muted"><?= date("F j, Y") ?></span>
                    <a href="add_user.php" class="btn btn-success btn-sm">+ Add User</a>
                </div>
            </div>

            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by name or email..."
                           value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-success" type="submit">Search</button>
                    <?php if ($search): ?>
                        <a href="admin_dashboard.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <hr>

            <table class="table table-hover table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th>
                        <th>Plan</th><th>Role</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-bold"><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['user_email']) ?></td>
                        <td>
                            <?php if ($row['role'] == 0): ?>
                                <?php
                                    $tier = strtolower($row['subscription_tier']);
                                    $bc   = $tier === 'vip' ? 'badge-vip' : ($tier === 'premium' ? 'badge-premium' : 'badge-standard');
                                ?>
                                <span class="badge <?= $bc ?> text-uppercase rounded-pill">
                                    <?= htmlspecialchars($row['subscription_tier']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['role'] == 1): ?>
                                <span class="badge badge-admin rounded-pill">Admin</span>
                            <?php elseif ($row['role'] == 2): ?>
                                <span class="badge bg-info rounded-pill">Warehouse</span>
                            <?php elseif ($row['role'] == 3): ?>
                                <span class="badge bg-warning text-dark rounded-pill">Delivery</span>
                            <?php else: ?>
                                <span class="badge bg-secondary rounded-pill">User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $s  = $row['status'] ?? 'active';
                                $sc = $s === 'active' ? 'success' : ($s === 'paused' ? 'warning' : 'danger');
                            ?>
                            <span class="badge bg-<?= $sc ?>"><?= ucfirst($s) ?></span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                                <a href="delete_user.php?id=<?= $row['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this user?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>