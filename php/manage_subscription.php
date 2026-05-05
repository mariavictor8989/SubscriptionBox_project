<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId = $_SESSION['user_id'];
$msg    = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $subId  = (int) ($_POST['sub_id'] ?? 0);

    if ($action === 'pause') {
        $stmt = $conn->prepare("UPDATE user_subscriptions SET status='paused', pause_start=CURDATE() WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $subId, $userId);
        $stmt->execute();
        $msg = 'Subscription paused successfully.';

    } elseif ($action === 'resume') {
        $stmt = $conn->prepare("UPDATE user_subscriptions SET status='active', pause_end=CURDATE() WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $subId, $userId);
        $stmt->execute();
        $msg = 'Subscription resumed!';

    } elseif ($action === 'cancel') {
        $reason = trim($_POST['cancel_reason'] ?? '');
        // Retention offer logic (UC6)
        $offer = '';
        if (strpos(strtolower($reason), 'expensive') !== false || strpos(strtolower($reason), 'price') !== false) {
            $offer = 'Get 50% off your next box!';
        } elseif (strpos(strtolower($reason), 'quality') !== false) {
            $offer = 'We\'ll add a bonus item to your next box!';
        }
        $stmt = $conn->prepare("UPDATE user_subscriptions SET status='cancelled', cancel_reason=?, retention_offer=? WHERE id=? AND user_id=?");
        $stmt->bind_param("ssii", $reason, $offer, $subId, $userId);
        $stmt->execute();
        $msg = $offer ? "Offer for you: $offer" : 'Subscription cancelled.';
    }
}

// Get all subscriptions
$subs = $conn->prepare("
    SELECT us.*, sp.name AS plan_name, sp.price, sp.theme
    FROM user_subscriptions us
    JOIN subscription_plans sp ON sp.id = us.plan_id
    WHERE us.user_id = ?
    ORDER BY us.created_at DESC
");
$subs->bind_param("i", $userId);
$subs->execute();
$subscriptions = $subs->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subscription - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .sub-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); margin-bottom: 20px; }
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
    <h3>Manage Subscriptions</h3>

    <?php if ($msg): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php foreach ($subscriptions as $sub): ?>
    <div class="sub-card">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5><?= htmlspecialchars($sub['plan_name']) ?></h5>
                <span class="text-muted"><?= htmlspecialchars($sub['theme']) ?></span>
            </div>
            <?php
                $sc = $sub['status'] === 'active' ? 'success' : ($sub['status'] === 'paused' ? 'warning' : 'danger');
            ?>
            <span class="badge bg-<?= $sc ?> fs-6"><?= ucfirst($sub['status']) ?></span>
        </div>

        <div class="row text-center mb-3">
            <div class="col"><div class="text-muted small">Price</div><div class="fw-bold"><?= $sub['price'] ?> EGP/mo</div></div>
            <div class="col"><div class="text-muted small">Next Billing</div><div class="fw-bold"><?= $sub['next_billing'] ?></div></div>
            <div class="col"><div class="text-muted small">Started</div><div class="fw-bold"><?= $sub['start_date'] ?></div></div>
        </div>

        <?php if ($sub['status'] === 'active'): ?>
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" class="d-inline">
                <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                <input type="hidden" name="action" value="pause">
                <button class="btn btn-warning btn-sm">⏸ Pause</button>
            </form>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $sub['id'] ?>">
                ✕ Cancel
            </button>
        </div>

        <!-- Cancel Modal -->
        <div class="modal fade" id="cancelModal<?= $sub['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Cancel Subscription</h5></div>
                    <div class="modal-body">
                        <form method="POST">
                            <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                            <input type="hidden" name="action" value="cancel">
                            <label class="form-label">Why are you cancelling?</label>
                            <select name="cancel_reason" class="form-select mb-3">
                                <option value="too expensive">Too expensive / price</option>
                                <option value="quality issues">Quality issues</option>
                                <option value="not using it">Not using it enough</option>
                                <option value="other">Other</option>
                            </select>
                            <button type="submit" class="btn btn-danger w-100">Confirm Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($sub['status'] === 'paused'): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
            <input type="hidden" name="action" value="resume">
            <button class="btn btn-success btn-sm">▶ Resume</button>
        </form>
        <?php endif; ?>

        <?php if ($sub['retention_offer']): ?>
            <div class="alert alert-warning mt-3">🎁 Special offer: <?= htmlspecialchars($sub['retention_offer']) ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <a href="subscribe.php" class="btn btn-success">+ Add New Subscription</a>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>