<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#212529; color:#f8f9fa; }
        .stat-card { background:#f8f9fa; border:none; border-radius:12px; transition:.3s; }
        .stat-card:hover { transform:translateY(-3px); }
        .user-management-card { background:white; color:#333; border-radius:15px; padding:30px; box-shadow:0 8px 30px rgba(0,0,0,.5); }
        .user-management-card h2 { color:#2c7a2c; font-weight:bold; }
        .badge-vip { background:#ffd700; color:#333; }
        .badge-premium { background:#3e9f3e; color:#fff; }
        .badge-standard { background:#6c757d; color:#fff; }
        .badge-admin { background:#dc3545; color:#fff; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <span class="navbar-brand fw-bold text-success">📦 Admin Control</span>
        <div class="d-flex gap-2">
            <a href="index.php?controller=admin&action=plans" class="btn btn-outline-success btn-sm">Plans</a>
            <a href="index.php?controller=admin&action=logs" class="btn btn-outline-info btn-sm">Logs</a>
            <a href="index.php?controller=auth&action=logout" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <?php if ($msg === 'deleted'): ?><div class="alert alert-success">User deleted.</div>
    <?php elseif ($msg === 'updated'): ?><div class="alert alert-success">User updated.</div>
    <?php elseif ($msg === 'added'): ?><div class="alert alert-success">User added.</div>
    <?php endif; ?>

    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card stat-card p-4 shadow-sm">
                <h6 class="text-uppercase text-muted">Total Users</h6>
                <h2 class="text-primary mb-0"><?= $total ?></h2>
            </div>
        </div>
    </div>

    <div class="card user-management-card shadow-lg">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>User Management</h2>
                <div class="d-flex gap-2">
                    <span class="small text-muted"><?= date("F j, Y") ?></span>
                    <a href="index.php?controller=admin&action=addUser" class="btn btn-success btn-sm">+ Add User</a>
                </div>
            </div>

            <form method="GET" action="index.php" class="mb-3">
                <input type="hidden" name="controller" value="admin">
                <input type="hidden" name="action" value="dashboard">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-success">Search</button>
                    <?php if ($search): ?><a href="index.php?controller=admin&action=dashboard" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
                </div>
            </form>
            <hr>

            <table class="table table-hover table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Plan</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td class="fw-bold"><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['user_email']) ?></td>
                        <td>
                            <?php if ($row['role'] == 0): ?>
                                <?php $bc = $row['subscription_tier'] === 'vip' ? 'badge-vip' : ($row['subscription_tier'] === 'premium' ? 'badge-premium' : 'badge-standard'); ?>
                                <span class="badge <?= $bc ?> rounded-pill"><?= strtoupper($row['subscription_tier']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['role'] == 1): ?><span class="badge badge-admin rounded-pill">Admin</span>
                            <?php elseif ($row['role'] == 2): ?><span class="badge bg-info rounded-pill">Warehouse</span>
                            <?php elseif ($row['role'] == 3): ?><span class="badge bg-warning text-dark rounded-pill">Delivery</span>
                            <?php else: ?><span class="badge bg-secondary rounded-pill">User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $sc = $row['status'] === 'active' ? 'success' : ($row['status'] === 'paused' ? 'warning' : 'danger'); ?>
                            <span class="badge bg-<?= $sc ?>"><?= ucfirst($row['status']) ?></span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="index.php?controller=admin&action=editUser&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                                <a href="index.php?controller=admin&action=deleteUser&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>