<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gift a Box - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .gift-card { background:white; border-radius:16px; padding:35px; box-shadow:0 4px 15px rgba(0,0,0,.07); max-width:550px; margin:0 auto; }
        .plan-option { border:2px solid #e0e0e0; border-radius:10px; padding:15px; cursor:pointer; transition:.2s; margin-bottom:10px; }
        .plan-option:hover { border-color:#2c7a2c; background:#f0faf0; }
        .code-box { background:#f0faf0; border:2px dashed #2c7a2c; border-radius:12px; padding:25px; text-align:center; }
        .code-text { font-size:2.5rem; font-weight:900; letter-spacing:8px; color:#2c7a2c; font-family:monospace; }
        .form-control { border:1px solid #ddd; border-radius:8px; padding:12px; }
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
    <div class="gift-card">
        <h4 class="fw-bold mb-1 text-center">🎁 Gift a FreshBox</h4>
        <p class="text-muted text-center small mb-4">Send a subscription as a gift!</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success) && !empty($activationCode)): ?>
            <div class="alert alert-success text-center fw-bold"><?= $success ?></div>
            <div class="code-box mb-4">
                <div class="text-muted small mb-2">🎁 Activation Code</div>
                <div class="code-text" id="giftCode"><?= $activationCode ?></div>
                <button onclick="copyCode()" class="btn btn-outline-success btn-sm mt-3">📋 Copy Code</button>
            </div>
            <p class="text-muted small text-center mb-3">Share this code with <strong><?= htmlspecialchars($recipientEmail) ?></strong></p>
            <a href="https://wa.me/?text=🎁 I sent you a FreshBox gift! Activation code: *<?= $activationCode ?>*"
               target="_blank" class="btn btn-success w-100 fw-bold mb-3">💬 Share via WhatsApp</a>
            <hr>
            <a href="index.php?controller=user&action=giftBox" class="btn btn-outline-success w-100">🎁 Send Another Gift</a>

        <?php else: ?>
            <form method="POST" action="index.php?controller=user&action=giftBox">
                <div class="mb-4">
                    <label class="form-label fw-bold">Recipient's Email</label>
                    <input type="email" name="recipient_email" class="form-control" placeholder="friend@example.com" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Choose a Plan</label>
                    <?php foreach ($plans as $p): ?>
                    <div class="plan-option">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="plan_id" value="<?= $p['id'] ?>" id="plan_<?= $p['id'] ?>" required>
                            <label class="form-check-label w-100" for="plan_<?= $p['id'] ?>">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold"><?= htmlspecialchars($p['name']) ?></span>
                                    <span class="text-success fw-bold"><?= $p['price'] ?> EGP/mo</span>
                                </div>
                                <div class="text-muted small"><?= ucfirst($p['tier']) ?> · <?= $p['max_swaps'] ?> swaps · <?= ucfirst($p['box_size']) ?> box</div>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5">🎁 Create Gift</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function copyCode() {
    navigator.clipboard.writeText(document.getElementById('giftCode').textContent.trim())
        .then(() => alert('✅ Code copied!'));
}
</script>
</body>
</html>