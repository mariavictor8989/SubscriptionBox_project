<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId  = $_SESSION['user_id'];
$msg     = '';
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['activation_code']));

    // Check code exists and not activated
    $stmt = $conn->prepare("
        SELECT gs.*, sp.name AS plan_name, sp.price, sp.tier
        FROM gift_subscriptions gs
        JOIN subscription_plans sp ON sp.id = gs.plan_id
        WHERE gs.activation_code = ? AND gs.is_activated = 0
    ");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $gift = $stmt->get_result()->fetch_assoc();

    if (!$gift) {
        $error = 'Invalid or already used activation code. Please check and try again.';
    } else {
        // Activate gift
        $upd = $conn->prepare("UPDATE gift_subscriptions SET is_activated=1, activated_at=NOW() WHERE id=?");
        $upd->bind_param("i", $gift['id']);
        $upd->execute();

        // Create subscription
        $start       = date('Y-m-d');
        $nextBilling = date('Y-m-d', strtotime('+1 month'));
        $ins = $conn->prepare("
            INSERT INTO user_subscriptions (user_id, plan_id, status, start_date, next_billing)
            VALUES (?, ?, 'gift', ?, ?)
        ");
        $ins->bind_param("iiss", $userId, $gift['plan_id'], $start, $nextBilling);
        $ins->execute();

        // Update user tier
        $upd2 = $conn->prepare("UPDATE users SET subscription_tier = ? WHERE id = ?");
        $upd2->bind_param("si", $gift['tier'], $userId);
        $upd2->execute();
        $_SESSION['user_tier'] = $gift['tier'];

        // Send notification
        $notif    = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'subscription', ?)");
        $notifMsg = "🎁 Your gift subscription ({$gift['plan_name']}) has been activated!";
        $notif->bind_param("is", $userId, $notifMsg);
        $notif->execute();

        $success = true;
        $msg     = "🎉 Gift activated! Your <strong>{$gift['plan_name']}</strong> subscription is now active.";
    }
}
?>
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
        <!-- Success -->
        <div class="text-center">
            <div class="success-icon">🎁</div>
            <h4 class="fw-bold mb-2">Gift Activated!</h4>
            <p class="text-muted mb-4"><?= $msg ?></p>
            <a href="dashboard.php" class="btn btn-success w-100 py-2 fw-bold">
                📦 Go to My Dashboard
            </a>
        </div>

    <?php else: ?>
        <!-- Form -->
        <div class="text-center mb-4">
            <div style="font-size:3rem">🎁</div>
            <h4 class="fw-bold mt-2 mb-1">Activate Your Gift</h4>
            <p class="text-muted small">Enter the activation code you received</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
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
<!-- 
        <hr class="my-4">
        <p class="text-center text-muted small">
            Don't have a code? Ask someone to <a href="gift_box.php">send you a gift</a>!
        </p> -->
    <?php endif; ?>

</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>