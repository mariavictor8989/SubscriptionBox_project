<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId  = $_SESSION['user_id'];
$success = '';

// Get user referral code
$user = $conn->prepare("SELECT referral_code, wallet_credit FROM users WHERE id = ?");
$user->bind_param("i", $userId);
$user->execute();
$userData = $user->get_result()->fetch_assoc();

// Generate referral code if missing
if (empty($userData['referral_code'])) {
    $code = strtoupper(substr(md5($userId . time()), 0, 8));
    $upd  = $conn->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
    $upd->bind_param("si", $code, $userId);
    $upd->execute();
    $userData['referral_code'] = $code;
}

// Handle share action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $boxId = (int) ($_POST['box_id'] ?? 0);
    // Log share (earn points)
    $notif = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'reward', ?)");
    $msg   = 'You earned 50 EGP credit for sharing your box!';
    $notif->bind_param("is", $userId, $msg);
    $notif->execute();

    // Add wallet credit
    $conn->prepare("UPDATE users SET wallet_credit = wallet_credit + 50 WHERE id = ?")->execute() || null;
    $upd2 = $conn->prepare("UPDATE users SET wallet_credit = wallet_credit + 50 WHERE id = ?");
    $upd2->bind_param("i", $userId);
    $upd2->execute();

    $success = 'Box shared! +50 EGP added to your wallet 🎉';
}

// Get latest delivered box
$box = $conn->prepare("
    SELECT b.id, b.month, b.status, sp.name AS plan_name
    FROM boxes b
    JOIN user_subscriptions us ON us.id = b.subscription_id
    JOIN subscription_plans sp ON sp.id = us.plan_id
    WHERE b.user_id = ? AND b.status = 'delivered'
    ORDER BY b.created_at DESC LIMIT 1
");
$box->bind_param("i", $userId);
$box->execute();
$latestBox = $box->get_result()->fetch_assoc();

$referralLink = "http://localhost/maria/php/register.html?ref=" . $userData['referral_code'];
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
        .share-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); }
        .referral-box { background: #f0faf0; border: 2px dashed #2c7a2c; border-radius: 10px; padding: 20px; text-align: center; }
        .code { font-size: 2rem; font-weight: 900; letter-spacing: 5px; color: #2c7a2c; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">📦 FreshBox</a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4" style="max-width:650px">
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Share Box -->
    <?php if ($latestBox): ?>
    <div class="share-card mb-4">
        <h5>📱 Share Your Last Box</h5>
        <p class="text-muted">Share your <?= htmlspecialchars($latestBox['plan_name']) ?> box to earn 50 EGP!</p>
        <form method="POST">
            <input type="hidden" name="box_id" value="<?= $latestBox['id'] ?>">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">Share & Earn 50 EGP</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Referral -->
    <div class="share-card">
        <h5>🔗 Your Referral Link</h5>
        <p class="text-muted">Invite friends and earn rewards for every signup!</p>
        <div class="referral-box mb-3">
            <div class="code"><?= $userData['referral_code'] ?></div>
            <div class="small text-muted mt-2">Your referral code</div>
        </div>
        <div class="input-group mb-3">
            <input type="text" class="form-control" id="refLink" value="<?= $referralLink ?>" readonly>
            <button class="btn btn-success" onclick="copyLink()">Copy Link</button>
        </div>
        <div class="alert alert-info small">
            💰 Earn <strong>100 EGP credit</strong> for every friend who subscribes using your link!
        </div>
        <div class="fw-bold">Your wallet: <?= number_format($userData['wallet_credit'], 2) ?> EGP</div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function copyLink() {
    document.getElementById('refLink').select();
    document.execCommand('copy');
    alert('Link copied!');
}
</script>
</body>
</html>