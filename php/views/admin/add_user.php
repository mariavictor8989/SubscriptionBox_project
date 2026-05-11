<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User - FreshBox Admin</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .container-form { width:100%; max-width:500px; padding:40px; background:white; border:1px solid #e0e0e0; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.05); }
        .form-control, .form-select { border:1px solid #ddd; border-radius:8px; padding:12px; margin-bottom:15px; }
        .form-control:focus, .form-select:focus { border-color:#2c7a2c; box-shadow:none; }
        .btn-add { background:#2c7a2c; color:white; width:100%; padding:14px; border:none; border-radius:8px; font-weight:bold; font-size:1.1rem; transition:.3s; }
        .btn-add:hover { background:#1e5c1e; transform:translateY(-2px); }
        .btn-back { display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none; }
        .btn-back:hover { color:#2c7a2c; }
    </style>
</head>
<body>
<div class="container-form">
    <h4 class="fw-bold text-center mb-1">Add New User</h4>
    <p class="text-muted text-center small mb-4">Create a new account on the platform</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?controller=admin&action=addUser">
        <label class="form-label fw-bold small">Full Name</label>
        <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>

        <label class="form-label fw-bold small">Email</label>
        <input type="email" name="user_email" class="form-control" placeholder="Email" required>

        <label class="form-label fw-bold small">Password</label>
        <input type="password" name="user_password" class="form-control" placeholder="Min. 8 characters" required>

        <label class="form-label fw-bold small">Subscription Tier</label>
        <select name="subscription_tier" class="form-select">
            <option value="standard">Standard</option>
            <option value="premium">Premium</option>
            <option value="vip">VIP</option>
        </select>

        <label class="form-label fw-bold small">System Role</label>
        <select name="role" class="form-select" onchange="toggleTier(this.value)">
            <option value="0">Regular User</option>
            <option value="1">Administrator</option>
            <option value="2">Warehouse Staff</option>
            <option value="3">Delivery</option>
        </select>

        <button type="submit" class="btn-add mt-2">Add User</button>
        <a href="index.php?controller=admin&action=dashboard" class="btn-back">← Back to Dashboard</a>
    </form>
</div>
<script>
function toggleTier(role) {
    const tierDiv = document.querySelector('select[name="subscription_tier"]').parentElement;
    tierDiv.style.opacity = role == 0 ? '1' : '0.4';
}
</script>
</body>
</html>