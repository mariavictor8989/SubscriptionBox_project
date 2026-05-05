<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId  = $_SESSION['user_id'];
$success = '';

// Get or generate referral code
$user = $conn->prepare("SELECT referral_code, wallet_credit, full_name FROM users WHERE id = ?");
$user->bind_param("i", $userId);
$user->execute();
$userData = $user->get_result()->fetch_assoc();

if (empty($userData['referral_code'])) {
    $code = strtoupper(substr(md5($userId . time()), 0, 8));
    $upd  = $conn->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
    $upd->bind_param("si", $code, $userId);
    $upd->execute();
    $userData['referral_code'] = $code;
}

// Handle share — earn 50 EGP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_box'])) {
    $upd = $conn->prepare("UPDATE users SET wallet_credit = wallet_credit + 50 WHERE id = ?");
    $upd->bind_param("i", $userId);
    $upd->execute();

    $notif    = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'reward', ?)");
    $notifMsg = '🎉 You earned 50 EGP credit for sharing your box!';
    $notif->bind_param("is", $userId, $notifMsg);
    $notif->execute();

    $userData['wallet_credit'] += 50;
    $success = '✅ Box shared! +50 EGP added to your wallet.';
}

// Get latest delivered box
$box = $conn->prepare("
    SELECT b.id, b.month, sp.name AS plan_name, COUNT(bi.id) AS item_count
    FROM boxes b
    JOIN user_subscriptions us ON us.id = b.subscription_id
    JOIN subscription_plans sp ON sp.id = us.plan_id
    LEFT JOIN box_items bi ON bi.box_id = b.id
    WHERE b.user_id = ? AND b.status = 'delivered'
    GROUP BY b.id ORDER BY b.created_at DESC LIMIT 1
");
$box->bind_param("i", $userId);
$box->execute();
$latestBox = $box->get_result()->fetch_assoc();

// Referral stats
$refs = $conn->prepare("SELECT COUNT(*) AS total, SUM(reward_given) AS rewarded FROM referrals WHERE referrer_id = ?");
$refs->bind_param("i", $userId);
$refs->execute();
$refStats = $refs->get_result()->fetch_assoc();

$referralLink = 'http://localhost/subscription%20box%20project/php/register.html?ref=' . $userData['referral_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Share My Box - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .share-card { background: white; border-radius: 16px; padding: 28px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); margin-bottom: 20px; }
        .code-box { background: #f0faf0; border: 2px dashed #2c7a2c; border-radius: 12px; padding: 20px; text-align: center; }
        .code-text { font-size: 2rem; font-weight: 900; letter-spacing: 6px; color: #2c7a2c; font-family: monospace; }
        .wallet-badge { background: linear-gradient(135deg, #2c7a2c, #5cb85c); color: white; border-radius: 12px; padding: 15px 25px; text-align: center; }
        .stat-pill { background: #f8f9fa; border-radius: 10px; padding: 12px 20px; text-align: center; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">📦 FreshBox</a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4" style="max-width:650px;">

    <?php if ($success): ?>
        <div class="alert alert-success fw-bold"><?= $success ?></div>
    <?php endif; ?>

    <!-- Wallet -->
    <div class="wallet-badge mb-4">
        <div class="small opacity-75">Your Wallet Balance</div>
        <div style="font-size:2rem; font-weight:900;"><?= number_format($userData['wallet_credit'], 2) ?> EGP</div>
    </div>

    <!-- Share Last Box -->
    <?php if ($latestBox): ?>
    <div class="share-card">
        <h5 class="fw-bold mb-1">📱 Share Your Last Box</h5>
        <p class="text-muted small mb-3">
            Share your <strong><?= htmlspecialchars($latestBox['plan_name']) ?></strong> box and earn <strong>50 EGP</strong>!
        </p>

        <div class="bg-light rounded p-3 mb-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold"><?= htmlspecialchars($latestBox['plan_name']) ?></div>
                <div class="text-muted small"><?= $latestBox['item_count'] ?> items · Delivered</div>
            </div>
            <span class="badge bg-success">Delivered ✓</span>
        </div>

        <!-- WhatsApp Only -->
        <a href="https://wa.me/?text=📦 Just got my FreshBox! Amazing items this month 🎉%0AJoin using my referral link: <?= urlencode($referralLink) ?>"
           target="_blank"
           class="btn btn-success w-100 fw-bold py-2 mb-3">
            💬 Share on WhatsApp
        </a>

        <form method="POST">
            <input type="hidden" name="share_box" value="1">
            <input type="hidden" name="box_id" value="<?= $latestBox['id'] ?>">
            <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                ⭐ Mark as Shared & Earn 50 EGP
            </button>
        </form>
    </div>
    <?php else: ?>
    <div class="share-card text-center text-muted py-4">
        <div style="font-size:3rem">📦</div>
        <p>No delivered boxes yet.<br><a href="order_box.php">Place an order</a> to get started!</p>
    </div>
    <?php endif; ?>

    <!-- Referral -->
    <div class="share-card">
        <h5 class="fw-bold mb-1">🔗 Your Referral Link</h5>
        <p class="text-muted small mb-3">Earn <strong>100 EGP</strong> for every friend who subscribes!</p>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="stat-pill">
                    <div class="fw-bold fs-4"><?= $refStats['total'] ?? 0 ?></div>
                    <div class="text-muted small">Friends Referred</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-pill">
                    <div class="fw-bold fs-4"><?= ($refStats['rewarded'] ?? 0) * 100 ?> EGP</div>
                    <div class="text-muted small">Rewards Earned</div>
                </div>
            </div>
        </div>

        <div class="code-box mb-3">
            <div class="text-muted small mb-1">Your Referral Code</div>
            <div class="code-text"><?= $userData['referral_code'] ?></div>
        </div>

        <div class="input-group mb-3">
            <input type="text" class="form-control" id="refLink" value="<?= $referralLink ?>" readonly style="font-size:.85rem;">
            <button class="btn btn-success" onclick="copyLink()">📋 Copy</button>
        </div>

        <!-- WhatsApp Only -->
        <a href="https://wa.me/?text=🎁 Join FreshBox with my referral!%0ASign up here: <?= urlencode($referralLink) ?>"
           target="_blank"
           class="btn btn-success w-100 fw-bold py-2">
            💬 Share Referral on WhatsApp
        </a>
    </div>

    <!-- Activate Gift -->
    <div class="share-card text-center">
        <p class="text-muted small mb-2">Received a gift code?</p>
        <a href="activate_gift.php" class="btn btn-outline-success fw-bold">🎁 Activate a Gift Code</a>
    </div>

</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function copyLink() {
    const input = document.getElementById('refLink');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => alert('✅ Referral link copied!'));
}
</script>
</body>
</html>