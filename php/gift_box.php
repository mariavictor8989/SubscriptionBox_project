<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId  = $_SESSION['user_id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientEmail = trim($_POST['recipient_email']);
    $planId         = (int) $_POST['plan_id'];

    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid recipient email.';
    } else {
        $plan = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
        $plan->bind_param("i", $planId);
        $plan->execute();
        $planData = $plan->get_result()->fetch_assoc();

        if (!$planData) {
            $error = 'Invalid plan selected.';
        } else {
            $activationCode = strtoupper(bin2hex(random_bytes(8)));
            $ins = $conn->prepare("INSERT INTO gift_subscriptions (payer_id, recipient_email, plan_id, activation_code) VALUES (?, ?, ?, ?)");
            $ins->bind_param("iiss", $userId, $recipientEmail, $planId, $activationCode);

            if ($ins->execute()) {
                $success = "Gift subscription created! Activation code: <strong>$activationCode</strong> — Share this with {$recipientEmail}";
            } else {
                $error = 'Could not create gift. Please try again.';
            }
        }
    }
}

$plans = $conn->query("SELECT * FROM subscription_plans WHERE is_active = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gift a Box - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .gift-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); max-width: 550px; margin: 0 auto; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">📦 FreshBox</a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="gift-card">
        <h4 class="mb-1">🎁 Gift a Box</h4>
        <p class="text-muted small mb-4">Send a FreshBox subscription as a gift to someone you love!</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Recipient Email</label>
                <input type="email" name="recipient_email" class="form-control" placeholder="friend@example.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Choose Plan</label>
                <select name="plan_id" class="form-select" required>
                    <option value="">Select a plan...</option>
                    <?php while ($p = $plans->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> — <?= $p['price'] ?> EGP/mo</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">🎁 Send Gift</button>
        </form>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>