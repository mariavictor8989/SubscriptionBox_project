<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId         = $_SESSION['user_id'];
$error          = '';
$success        = '';
$activationCode = '';

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
            $activationCode = strtoupper(bin2hex(random_bytes(4)));
            $ins = $conn->prepare("INSERT INTO gift_subscriptions (payer_id, recipient_email, plan_id, activation_code) VALUES (?, ?, ?, ?)");
            $ins->bind_param("iiss", $userId, $recipientEmail, $planId, $activationCode);

            if ($ins->execute()) {
                $success = "Gift created successfully!";
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
        .gift-card { background: white; border-radius: 16px; padding: 35px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); max-width: 550px; margin: 0 auto; }
        .plan-option { border: 2px solid #e0e0e0; border-radius: 10px; padding: 15px; cursor: pointer; transition: .2s; margin-bottom: 10px; }
        .plan-option:hover { border-color: #2c7a2c; background: #f0faf0; }
        .code-box { background: #f0faf0; border: 2px dashed #2c7a2c; border-radius: 12px; padding: 25px; text-align: center; }
        .code-text { font-size: 2.5rem; font-weight: 900; letter-spacing: 8px; color: #2c7a2c; font-family: monospace; }
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
        <h4 class="fw-bold mb-1 text-center">🎁 Gift a FreshBox</h4>
        <p class="text-muted text-center small mb-4">Send a subscription as a gift!</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success && $activationCode): ?>
            <div class="alert alert-success text-center fw-bold"><?= $success ?></div>

            <div class="code-box mb-4">
                <div class="text-muted small mb-2">🎁 Activation Code</div>
                <div class="code-text" id="giftCode"><?= $activationCode ?></div>
                <button onclick="copyCode()" class="btn btn-outline-success btn-sm mt-3">📋 Copy Code</button>
            </div>

            <p class="text-muted small text-center mb-3">
                Share this code with <strong><?= htmlspecialchars($_POST['recipient_email']) ?></strong>
            </p>

            <!-- WhatsApp Only -->
            <a href="https://wa.me/?text=🎁 I sent you a FreshBox gift subscription!%0AUse this activation code: *<?= $activationCode ?>*%0AActivate here: http://localhost/subscription%20box%20project/php/activate_gift.php"
               target="_blank"
               class="btn btn-success w-100 py-2 fw-bold mb-3">
                💬 Share via WhatsApp
            </a>

            <hr>
            <a href="gift_box.php" class="btn btn-outline-success w-100">🎁 Send Another Gift</a>

        <?php else: ?>
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label fw-bold">Recipient's Email</label>
                    <input type="email" name="recipient_email" class="form-control"
                           placeholder="friend@example.com" required style="padding:12px; border-radius:8px;">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Choose a Plan</label>
                    <?php while ($p = $plans->fetch_assoc()): ?>
                    <div class="plan-option">
                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                   name="plan_id" value="<?= $p['id'] ?>"
                                   id="plan_<?= $p['id'] ?>" required>
                            <label class="form-check-label w-100" for="plan_<?= $p['id'] ?>">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold"><?= htmlspecialchars($p['name']) ?></span>
                                    <span class="text-success fw-bold"><?= $p['price'] ?> EGP/mo</span>
                                </div>
                                <div class="text-muted small">
                                    <?= ucfirst($p['tier']) ?> · <?= $p['max_swaps'] ?> swaps · <?= ucfirst($p['box_size']) ?> box
                                </div>
                            </label>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5">🎁 Create Gift</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function copyCode() {
    const code = document.getElementById('giftCode').textContent.trim();
    navigator.clipboard.writeText(code).then(() => alert('✅ Code copied: ' + code));
}
</script>
</body>
</html>