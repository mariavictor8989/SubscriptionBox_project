<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$id   = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) { header("Location: admin_dashboard.php"); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name   = trim($_POST['name']);
    $role   = (int) $_POST['role'];
    $status = $_POST['status'];
    $tier   = $role === 0 ? $_POST['tier'] : 'standard';

    $allowed_tiers  = ['standard', 'premium', 'vip'];
    $allowed_roles  = [0, 1, 2, 3];
    $allowed_status = ['active', 'paused', 'suspended', 'cancelled'];

    if (!in_array($tier, $allowed_tiers) || !in_array($role, $allowed_roles) || !in_array($status, $allowed_status)) {
        $error = 'Invalid input values.';
    } else {
        $upd = $conn->prepare("UPDATE users SET full_name=?, subscription_tier=?, role=?, status=? WHERE id=?");
        $upd->bind_param("ssisi", $name, $tier, $role, $status, $id);

        if ($upd->execute()) {
            $ip  = $_SERVER['REMOTE_ADDR'];
            $log = $conn->prepare("INSERT INTO audit_log (user_id, action, target, target_id, ip_address) VALUES (?, 'edit_user', 'users', ?, ?)");
            $log->bind_param("iis", $_SESSION['user_id'], $id, $ip);
            $log->execute();
            header("Location: admin_dashboard.php?msg=updated");
            exit();
        } else {
            $error = 'Update failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - FreshBox Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body {
            background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 20px 0;
        }
        .edit-container {
            width: 100%; max-width: 480px; padding: 40px;
            background: white; border: 1px solid #e0e0e0;
            border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.05);
        }
        .edit-container h2 { font-weight: 800; color: #212529; text-align: center; margin-bottom: 6px; }
        .edit-container p  { color: #666; text-align: center; font-size: .9rem; margin-bottom: 25px; }
        .form-label { font-weight: 600; color: #444; font-size: .9rem; }
        .form-control, .form-select {
            border: 1px solid #ddd; border-radius: 8px;
            padding: 12px; margin-bottom: 18px; font-size: 1rem;
        }
        .form-control:focus, .form-select:focus { border-color: #2c7a2c; box-shadow: none; }
        .btn-update {
            background: #212529; color: white; width: 100%;
            padding: 14px; border: none; border-radius: 8px;
            font-weight: bold; font-size: 1.1rem; transition: .3s;
        }
        .btn-update:hover { background: #000; transform: translateY(-2px); }
        .btn-back { display: block; text-align: center; margin-top: 18px; color: #666; text-decoration: none; }
        .btn-back:hover { color: #2c7a2c; }
    </style>
</head>
<body>
<div class="edit-container">
    <h2>Update User Info</h2>
    <p>Editing: <strong><?= htmlspecialchars($user['user_email']) ?></strong></p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control"
               value="<?= htmlspecialchars($user['full_name']) ?>" required>

        <label class="form-label">System Role</label>
        <select name="role" class="form-select" id="roleSelect" onchange="toggleTier(this.value)">
            <option value="0" <?= $user['role'] == 0 ? 'selected' : '' ?>>Regular User</option>
            <option value="1" <?= $user['role'] == 1 ? 'selected' : '' ?>>Administrator</option>
            <option value="2" <?= $user['role'] == 2 ? 'selected' : '' ?>>Warehouse Staff</option>
            <option value="3" <?= $user['role'] == 3 ? 'selected' : '' ?>>Delivery</option>
        </select>

        <!-- Tier — بيظهر بس لو Regular User -->
        <div id="tier-section">
            <label class="form-label">Subscription Tier</label>
            <select name="tier" class="form-select">
                <?php foreach (['standard', 'premium', 'vip'] as $t): ?>
                    <option value="<?= $t ?>" <?= $user['subscription_tier'] === $t ? 'selected' : '' ?>>
                        <?= ucfirst($t) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- رسالة بتظهر لو Staff -->
        <div id="tier-notice" class="alert alert-secondary py-2 small" style="display:none;">
            ⚠️ Subscription tier not applicable for staff accounts.
        </div>

        <label class="form-label">Account Status</label>
        <select name="status" class="form-select">
            <?php foreach (['active', 'paused', 'suspended', 'cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($user['status'] ?? 'active') === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button name="update" type="submit" class="btn-update shadow-sm">Save Changes</button>
        <a href="admin_dashboard.php" class="btn-back">← Back to Dashboard</a>
    </form>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function toggleTier(role) {
    const tierSection = document.getElementById('tier-section');
    const tierNotice  = document.getElementById('tier-notice');
    if (role == 0) {
        tierSection.style.display = 'block';
        tierNotice.style.display  = 'none';
    } else {
        tierSection.style.display = 'none';
        tierNotice.style.display  = 'block';
    }
}
// شغل على load الصفحة
toggleTier(<?= $user['role'] ?>);
</script>
</body>
</html>
