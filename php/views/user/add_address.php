<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Addresses - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .addr-card { background:white; border-radius:12px; padding:20px; box-shadow:0 4px 15px rgba(0,0,0,.07); margin-bottom:15px; }
        .form-card { background:white; border-radius:12px; padding:25px; box-shadow:0 4px 15px rgba(0,0,0,.07); }
        .form-control { border:1px solid #ddd; border-radius:8px; padding:10px; }
        .form-control:focus { border-color:#2c7a2c; box-shadow:none; }
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
    <div class="row">
        <div class="col-md-6">
            <h4>My Addresses</h4>
            <?php if (empty($addresses)): ?>
                <p class="text-muted">No addresses yet.</p>
            <?php endif; ?>
            <?php foreach ($addresses as $a): ?>
            <div class="addr-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <span class="fw-bold"><?= htmlspecialchars($a['label']) ?></span>
                        <?php if ($a['is_default']): ?><span class="badge bg-success ms-2">Default</span><?php endif; ?>
                        <?php if ($a['is_serviceable']): ?>
                            <span class="badge bg-primary ms-1">✓ Serviceable</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-1">✗ Not Serviceable</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0"><?= htmlspecialchars($a['full_address']) ?>, <?= htmlspecialchars($a['city']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="col-md-6">
            <div class="form-card">
                <h5 class="mb-3">Add New Address</h5>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php?controller=user&action=addAddress">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Label</label>
                        <input type="text" name="label" class="form-control" placeholder="Home / Work / Other" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Address</label>
                        <input type="text" name="full_address" class="form-control" placeholder="Street, Building, Floor..." required>
                    </div>
                    <div class="row">
                        <div class="col mb-3">
                            <label class="form-label fw-bold">City</label>
                            <input type="text" name="city" class="form-control" placeholder="Cairo" required>
                        </div>
                        <div class="col mb-3">
                            <label class="form-label fw-bold">Region</label>
                            <input type="text" name="region" class="form-control" placeholder="Nasr City">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Postal Code (optional)</label>
                        <input type="text" name="postal_code" class="form-control" placeholder="11511">
                    </div>
                    <div class="alert alert-info small">✅ Serviceable: <?= implode(', ', $serviceableRegions) ?></div>
                    <button type="submit" class="btn btn-success w-100">Add Address</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>