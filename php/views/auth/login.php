<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log in - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
        .login-container { width:100%; max-width:450px; padding:40px; background:white; border:1px solid #e0e0e0; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,.05); }
        .login-container h1 { font-weight:800; font-size:2rem; margin-bottom:25px; color:#212529; text-align:center; }
        .form-control { border:1px solid #ddd; border-radius:8px; padding:12px 15px; margin-bottom:18px; font-size:1rem; }
        .form-control:focus { border-color:#2c7a2c; box-shadow:none; }
        .btn-login { background:#212529; color:white; width:100%; padding:14px; border:none; border-radius:8px; font-weight:bold; font-size:1.1rem; transition:.3s; }
        .btn-login:hover { background:#000; transform:translateY(-1px); }
    </style>
</head>
<body>
<div class="login-container">
    <h1>📦 FreshBox</h1>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
        <div class="alert alert-success">Account created! Please log in.</div>
    <?php endif; ?>
    <form action="../../index.php?controller=auth&action=loginPost" method="POST">
        <input type="email" name="user_email" class="form-control" placeholder="Email" required>
        <input type="password" name="user_password" class="form-control" placeholder="Password" required>
        <button type="submit" class="btn-login">Log In</button>
    </form>

    <p class="text-center mt-3" style="font-size:.9rem;">
        Don't have an account?
        <a href="../../index.php?controller=auth&action=register" style="color:#2c7a2c; font-weight:bold;">Register</a>
    </p>
</div>
</body>
</html>