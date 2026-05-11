<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:20px 0; }
        .reg-container { width:100%; max-width:500px; padding:40px; background:white; border:1px solid #e0e0e0; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,.05); }
        .reg-container h1 { font-weight:800; font-size:2rem; margin-bottom:8px; color:#212529; text-align:center; }
        .form-control, .form-select { border:1px solid #ddd; border-radius:8px; padding:12px; margin-bottom:15px; }
        .form-control:focus, .form-select:focus { border-color:#2c7a2c; box-shadow:none; }
        .btn-reg { background:#212529; color:white; width:100%; padding:14px; border:none; border-radius:8px; font-weight:bold; font-size:1.1rem; margin-top:10px; transition:.3s; }
        .btn-reg:hover { background:#000; transform:translateY(-1px); }
    </style>
</head>
<body>
<div class="reg-container">
    <h1>📦 FreshBox</h1>
    <p class="text-center text-muted mb-4">Create your account</p>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="../../index.php?controller=auth&action=registerPost" method="POST">
        <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
        <input type="email" name="user_email" class="form-control" placeholder="Email" required>
        <input type="password" name="user_password" class="form-control" placeholder="Password (min 8 chars)" required>
        <select name="subscription_tier" class="form-select" required>
            <option value="" disabled selected>Choose your plan...</option>
            <option value="standard">Standard — 299 EGP/mo</option>
            <option value="premium">Premium — 499 EGP/mo</option>
            <option value="vip">VIP — 799 EGP/mo</option>
        </select>
        <button type="submit" class="btn-reg">Sign Up</button>
    </form>
    <p class="text-center mt-3" style="font-size:.9rem;">
        Already have an account?
         <a href="../../index.php?controller=auth&action=login" style="color:#2c7a2c; font-weight:bold;">Log in</a> 
         
    </form>
    </p>
</div>
</body>
</html>