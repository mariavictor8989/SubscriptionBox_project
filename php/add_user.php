<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header("Location: login.html");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name']);
    $email = trim($_POST['user_email']);
    $tier  = $_POST['subscription_tier'];
    $role  = (int) $_POST['role'];
    $pass  = $_POST['user_password'];

    $allowed_tiers = ['standard', 'premium', 'vip'];
    $allowed_roles = [0, 1, 2, 3];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!in_array($tier, $allowed_tiers) || !in_array($role, $allowed_roles)) {
        $error = 'Invalid tier or role.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE user_email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = 'Email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (full_name, user_email, user_password, subscription_tier, role) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("ssssi", $name, $email, $hash, $tier, $role);

            if ($ins->execute()) {
                $newId = $conn->insert_id;
                $ip    = $_SERVER['REMOTE_ADDR'];
                $log   = $conn->prepare("INSERT INTO audit_log (user_id, action, target, target_id, ip_address) VALUES (?, 'add_user', 'users', ?, ?)");
                $log->bind_param("iis", $_SESSION['user_id'], $newId, $ip);
                $log->execute();
                header("Location: admin_dashboard.php?msg=added");
                exit();
            } else {
                $error = 'Could not add user. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User - FreshBox Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body {
            background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 20px 0;
        }
        .add-container {
            width: 100%; max-width: 500px; padding: 40px;
            background: white; border: 1px solid #e0e0e0;
            border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.05);
        }
        .add-container h2 { font-weight: 800; color: #212529; text-align: center; margin-bottom: 6px; }
        .add-container p  { color: #666; text-align: center; font-size: .9rem; margin-bottom: 25px; }
        .form-label { font-weight: 600; color: #444; font-size: .9rem; }
        .form-control, .form-select {
            border: 1px solid #ddd; border-radius: 8px;
            padding: 12px; margin-bottom: 15px; font-size: 1rem;
        }
        .form-control:focus, .form-select:focus { border-color: #2c7a2c; box-shadow: none; }
        .btn-add {
            background: #2c7a2c; color: white; width: 100%;
            padding: 14px; border: none; border-radius: 8px;
            font-weight: bold; font-size: 1.1rem; transition: .3s;
        }
        .btn-add:hover { background: #1e5c1e; transform: translateY(-2px); }
        .btn-back { display: block; text-align: center; margin-top: 18px; color: #666; text-decoration: none; }
        .btn-back:hover { color: #2c7a2c; }
    </style>
</head>
<body>
<div class="add-container">
    <h2>Add New User</h2>
    <p>Create a new account on the platform</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>

        <label class="form-label">Email Address</label>
        <input type="email" name="user_email" class="form-control" placeholder="Email" required>

        <label class="form-label">Password</label>
        <input type="password" name="user_password" class="form-control" placeholder="Min. 8 characters" required>

        <label class="form-label">Subscription Tier</label>
        <select name="subscription_tier" class="form-select">
            <option value="standard">Standard</option>
            <option value="premium">Premium</option>
            <option value="vip">VIP</option>
        </select>

        <label class="form-label">System Role</label>
        <select name="role" class="form-select">
            <option value="0">Regular User</option>
            <option value="1">Administrator</option>
            <option value="2">Warehouse Staff</option>
            <option value="3">Delivery</option>
        </select>

        <button type="submit" class="btn-add shadow-sm mt-2">Add User</button>
        <a href="admin_dashboard.php" class="btn-back">← Back to Dashboard</a>
    </form>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>