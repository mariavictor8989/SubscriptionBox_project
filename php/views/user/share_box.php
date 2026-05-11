<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Share My Box - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .share-card { background:white; border-radius:16px; padding:28px; box-shadow:0 4px 15px rgba(0,0,0,.07); margin-bottom:20px; }
        .code-box { background:#f0faf0; border:2px dashed #2c7a2c; border-radius:12px; padding:20px; text-align:center; }
        .code-text { font-size:2rem; font-weight:900; letter-spacing:6px; color:#2c7a2c; font-family:monospace; }
        .wallet-badge { background:linear-gradient(135deg,#2c7a2c,#5cb85c); color:white; border-radius:12px; padding:15px 25px; text-align:center; }
        .stat-pill { background:#f8f9fa; border-radius:10px; padding:12px 20px; text-align:center; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?controller=user&action=dashboard">📦 FreshBox</a>
        <a href="index.php?controller=user&action=dashboard" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4" style="max-width:650px">
    <?php if (!empty($success)): ?>
        <div class="alert alert-success fw-bold"><?= $success ?></div>
    <?php endif; ?>

    <div class="wallet-badge mb-4">
        <div class="small opacity-75">Your Wallet Balance</div>
        <div style="font-size:2rem;font-weight:900;"><?= number_format($userData['wallet_credit'], 2) ?> EGP</div>
    </div>

    <!-- Share Last Box -->
    <?php if ($latestBox): ?>
    <div class="share-card">
        <h5 class="fw-bold mb-1">📱 Share Your Last Box</h5>
        <p class="text-muted small mb-3">Share your <strong><?= htmlspecialchars($latestBox['plan_name']) ?></strong> box and earn <strong>50 EGP</strong>!</p>
        <div class="bg-light rounded p-3 mb-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold"><?= htmlspecialchars($latestBox['plan_name']) ?></div>
                <div class="text-muted small"><?= $latestBox['item_count'] ?> items · Delivered</div>
            </div>
            <span class="badge bg-success">Delivered ✓</span>
        </div>
        <?php
        $referralLink = 'http://localhost/subscription%20box%20project/php/index.php?controller=auth&action=register&ref=' . $userData['referral_code'];
        ?>
        <a href="https://wa.me/?text=📦 Just got my FreshBox! Amazing items 🎉 Join here: <?= urlencode($referralLink) ?>"
           target="_blank" class="btn btn-success w-100 fw-bold py-2 mb-3">💬 Share on WhatsApp</a>
        <form method="POST" action="index.php?controller=user&action=shareBox">
            <input type="hidden" name="share_box" value="1">
            <input type="hidden" name="box_id" value="<?= $latestBox['id'] ?>">
            <button type="submit" class="btn btn-warning w-100 fw-bold py-2">⭐ Mark as Shared & Earn 50 EGP</button>
        </form>
    </div>
    <?php else: ?>
    <div class="share-card text-center text-muted py-4">
        <div style="font-size:3rem">📦</div>
        <p>No delivered boxes yet.<br><a href="index.php?controller=user&action=orderBox">Place an order</a> to get started!</p>
    </div>
    <?php endif; ?>

    <!-- Referral -->
    <div class="share-card">
        <h5 class="fw-bold mb-1">🔗 Your Referral Link</h5>
        <p class="text-muted small mb-3">Earn <strong>100 EGP</strong> for every friend who subscribes!</p>

        <div class="code-box mb-3">
            <div class="text-muted small mb-1">Your Referral Code</div>
            <div class="code-text"><?= $userData['referral_code'] ?></div>
        </div>

        <div class="input-group mb-3">
            <input type="text" class="form-control" id="refLink" value="<?= $referralLink ?>" readonly style="font-size:.85rem;">
            <button class="btn btn-success" onclick="copyLink()">📋 Copy</button>
        </div>

        <a href="https://wa.me/?text=🎁 Join FreshBox with my referral! Sign up: <?= urlencode($referralLink) ?>"
           target="_blank" class="btn btn-success w-100 fw-bold py-2">💬 Share Referral on WhatsApp</a>
    </div>

    <div class="share-card text-center">
        <p class="text-muted small mb-2">Received a gift code?</p>
        <a href="index.php?controller=user&action=activateGift" class="btn btn-outline-success fw-bold">🎁 Activate a Gift Code</a>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function copyLink() {
    const input = document.getElementById('refLink');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => alert('✅ Link copied!'));
}
</script>
</body>
</html>