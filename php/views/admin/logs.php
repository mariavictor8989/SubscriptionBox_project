<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Logs - FreshBox Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#212529; color:#f8f9fa; }
        .logs-card { background:white; color:#333; border-radius:15px; padding:30px; box-shadow:0 8px 30px rgba(0,0,0,.5); }
        .logs-card h2 { color:#2c7a2c; font-weight:bold; }
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
    <div class="logs-card">
        <h2>System Audit Log</h2>
        <p class="text-muted">Last 200 actions on the platform.</p>
        <hr>
        <table class="table table-hover table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr><th>ID</th><th>User</th><th>Action</th><th>Target</th><th>Target ID</th><th>IP</th><th>Time</th></tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td><?= htmlspecialchars($log['full_name'] ?? 'System') ?><br><small class="text-muted"><?= htmlspecialchars($log['user_email'] ?? '') ?></small></td>
                    <td><span class="badge bg-dark"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td><?= htmlspecialchars($log['target'] ?? '') ?></td>
                    <td><?= $log['target_id'] ?></td>
                    <td><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                    <td><?= $log['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>