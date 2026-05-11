<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - FreshBox Admin</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .container-form { width:100%; max-width:480px; padding:40px; background:white; border:1px solid #e0e0e0; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.05); }
        .form-control, .form-select { border:1px solid #ddd; border-radius:8px; padding:12px; margin-bottom:15px; }
        .form-control:focus, .form-select:focus { border-color:#2c7a2c; box-shadow:none; }
        .btn-update { background:#212529; color:white; width:100%; padding:14px; border:none; border-radius:8px; font-weight:bold; font-size:1.1rem; transition:.3s; }
        .btn-update:hover { background:#000; transform:translateY(-2px); }
        .btn-back { display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none; }
        .btn-back:hover { color:#2c7a2c; }
    </style>
</head>
<body>
<div class="container-form">
    <h4 class="fw-bold text-center mb-1">Update User Info</h4>
    <p class="text-muted text-center small mb-4">Editing: <strong><?= htmlspecialchars($user['user_email']) ?></strong></p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?controller=admin&action=editUser&id=<?= $user['id'] ?>">
        <label class="form-label fw-bold small">Full Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>

        <label class="form-label fw-bold small">System Role</label>
        <select name="role" class="form-select" id="roleSelect" onchange="toggleTier(this.value)">
            <option value="0" <?= $user['role'] == 0 ? 'selected' : '' ?>>Regular User</option>
            <option value="1" <?= $user['role'] == 1 ? 'selected' : '' ?>>Administrator</option>
            <option value="2" <?= $user['role'] == 2 ? 'selected' : '' ?>>Warehouse Staff</option>
            <option value="3" <?= $user['role'] == 3 ? 'selected' : '' ?>>Delivery</option>
        </select>

        <div id="tier-section">
            <label class="form-label fw-bold small">Subscription Tier</label>
            <select name="tier" class="form-select">
                <?php foreach (['standard','premium','vip'] as $t): ?>
                    <option value="<?= $t ?>" <?= $user['subscription_tier'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="tier-notice" class="alert alert-secondary small" style="display:none;">⚠️ Tier not applicable for staff accounts.</div>

        <label class="form-label fw-bold small">Account Status</label>
        <select name="status" class="form-select">
            <?php foreach (['active','paused','suspended','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($user['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>

        <button name="update" type="submit" class="btn-update mt-2">Save Changes</button>
        <a href="index.php?controller=admin&action=dashboard" class="btn-back">← Back to Dashboard</a>
    </form>
</div>
<script>
function toggleTier(role) {
    document.getElementById('tier-section').style.display = role == 0 ? 'block' : 'none';
    document.getElementById('tier-notice').style.display  = role == 0 ? 'none'  : 'block';
}
toggleTier(<?= $user['role'] ?>);
</script>
</body>
</html>