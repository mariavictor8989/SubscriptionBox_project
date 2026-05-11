<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activate Gift - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif;
               display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .activate-card { background: white; border-radius: 16px; padding: 40px;
                         box-shadow: 0 10px 30px rgba(0,0,0,0.08); max-width: 450px; width: 100%; }
        .code-input { font-size: 1.5rem; letter-spacing: 5px; text-align: center;
                      text-transform: uppercase; font-weight: bold; border-radius: 10px;
                      border: 2px solid #ddd; padding: 15px; }
        .code-input:focus { border-color: #2c7a2c; box-shadow: none; outline: none; }
        .success-icon { width: 70px; height: 70px; background: #f0faf0; border-radius: 50%;
                        display: flex; align-items: center; justify-content: center;
                        font-size: 2rem; margin: 0 auto 20px; }
    </style>
</head>
<body>
<div class="activate-card">

    <?php if ($success): ?>
        <div class="text-center">
            <div class="success-icon">🎁</div>
            <h4 class="fw-bold mb-2">Gift Activated!</h4>
            <p class="text-muted mb-4"><?= $msg ?></p>
            <a href="index.php?controller=user&action=dashboard" class="btn btn-success w-100 py-2 fw-bold">
                📦 Go to My Dashboard
            </a>
        </div>

    <?php else: ?>
        <div class="text-center mb-4">
            <div style="font-size:3rem">🎁</div>
            <h4 class="fw-bold mt-2 mb-1">Activate Your Gift</h4>
            <p class="text-muted small">Enter the activation code you received</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?controller=user&action=activateGift">
            <div class="mb-4">
                <input type="text" name="activation_code"
                       class="form-control code-input"
                       placeholder="XXXXXXXX"
                       maxlength="8" required
                       value="<?= htmlspecialchars($_POST['activation_code'] ?? '') ?>"
                       oninput="this.value = this.value.toUpperCase()">
                <div class="form-text text-center mt-2">Enter the 8-character code from your gift sender</div>
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 fw-bold fs-5">
                🎁 Activate Gift
            </button>
        </form>
    <?php endif; ?>

</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>