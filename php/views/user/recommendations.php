<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recommendations - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .item-card { border:none; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,.07); transition:.3s; height:100%; }
        .item-card:hover { transform:translateY(-4px); box-shadow:0 8px 25px rgba(0,0,0,.12); }
        .limited-banner { background:linear-gradient(135deg,#ff6b6b,#ee5a24); color:white; font-size:11px; padding:3px 10px; border-radius:10px; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?controller=user&action=dashboard">📦 FreshBox</a>
        <a href="index.php?controller=user&action=dashboard" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="mb-1">✨ Recommended For You</h3>
    <p class="text-muted">Based on your preferences — allergens filtered out automatically.</p>

    <div class="row g-3">
    <?php if (empty($recommendations)): ?>
        <div class="col-12 text-center text-muted py-4">
            <div style="font-size:3rem">🔍</div>
            <p>No recommendations right now. <a href="index.php?controller=user&action=preferences">Update your preferences</a>.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($recommendations as $item): ?>
        <div class="col-md-3 col-sm-6">
            <div class="card item-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                        <?php if ($item['is_limited']): ?>
                            <span class="limited-banner">Limited</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-2"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-light text-dark"><?= htmlspecialchars($item['theme']) ?></span>
                        <span class="text-muted small">Stock: <?= $item['stock_qty'] ?></span>
                    </div>
                    <a href="index.php?controller=user&action=customizeBox" class="btn btn-outline-success btn-sm w-100">Add to Box</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <a href="index.php?controller=user&action=preferences" class="btn btn-outline-success">⚙️ Update My Preferences</a>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>