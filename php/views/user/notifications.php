<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .notif-item { background:white; border-radius:10px; padding:16px 20px; margin-bottom:10px; box-shadow:0 2px 8px rgba(0,0,0,.06); transition:.2s; }
        .notif-item.unread { border-left:4px solid #2c7a2c; }
        .notif-item:hover { box-shadow:0 4px 15px rgba(0,0,0,.1); }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?controller=user&action=dashboard">📦 FreshBox</a>
        <a href="index.php?controller=user&action=dashboard" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4" style="max-width:700px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>🔔 Notifications</h4>
        <a href="index.php?controller=user&action=notifications&mark_read=1" class="btn btn-outline-success btn-sm">Mark all as read</a>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="text-center text-muted py-5">
            <div style="font-size:3rem">🔔</div>
            <p>No notifications yet.</p>
        </div>
    <?php endif; ?>

    <?php
    $icons = ['subscription'=>'📦','reward'=>'💰','delivery'=>'🚚','payment'=>'💳','swap'=>'🔄','alert'=>'⚠️'];
    foreach ($notifications as $n):
    ?>
    <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
        <div class="d-flex align-items-start gap-3">
            <div style="font-size:1.5rem"><?= $icons[$n['type']] ?? '📌' ?></div>
            <div class="flex-grow-1">
                <div><?= htmlspecialchars($n['message']) ?></div>
                <div class="text-muted small mt-1"><?= $n['created_at'] ?></div>
            </div>
            <?php if (!$n['is_read']): ?><span class="badge bg-success">New</span><?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>