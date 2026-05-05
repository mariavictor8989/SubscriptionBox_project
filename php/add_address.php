<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId  = $_SESSION['user_id'];
$error   = '';
$success = '';

// Serviceable regions
$serviceableRegions = ['Cairo', 'Giza', 'Alexandria', 'Nasr City', 'Heliopolis', 'Maadi', 'Zamalek', '6th October', 'New Cairo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label   = trim($_POST['label']);
    $address = trim($_POST['full_address']);
    $city    = trim($_POST['city']);
    $region  = trim($_POST['region']);
    // $postal  = trim($_POST['postal_code']);

    // UC24 - Validate address (check if serviceable)
    $isServiceable = in_array($city, $serviceableRegions) || in_array($region, $serviceableRegions) ? 1 : 0;

    if (empty($address) || empty($city)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Set as default if first address
        $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM addresses WHERE user_id = ?");
        $countStmt->bind_param("i", $userId);
        $countStmt->execute();
        $count     = $countStmt->get_result()->fetch_assoc()['cnt'];
        $isDefault = $count == 0 ? 1 : 0;

        $ins = $conn->prepare("INSERT INTO addresses (user_id, label, full_address, city, region, postal_code, is_serviceable, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("isssssii", $userId, $label, $address, $city, $region, $postal, $isServiceable, $isDefault);

        if ($ins->execute()) {
            if ($isServiceable) {
                $success = 'Address added successfully! ✅ This area is serviceable.';
            } else {
                $error = '⚠️ Address saved but this area is outside our serviceable zone. You cannot subscribe with this address.';
            }
        }
    }
}

// Get existing addresses
$addrs = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$addrs->bind_param("i", $userId);
$addrs->execute();
$addresses = $addrs->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Addresses - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .addr-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); margin-bottom: 15px; }
        .form-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); }
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
    <div class="row">
        <div class="col-md-6">
            <h4>My Addresses</h4>
            <?php if (empty($addresses)): ?>
                <p class="text-muted">No addresses yet.</p>
            <?php endif; ?>
            <?php foreach ($addresses as $a): ?>
            <div class="addr-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <span class="fw-bold"><?= htmlspecialchars($a['label']) ?></span>
                        <?php if ($a['is_default']): ?>
                            <span class="badge bg-success ms-2">Default</span>
                        <?php endif; ?>
                        <?php if ($a['is_serviceable']): ?>
                            <span class="badge bg-primary ms-1">✓ Serviceable</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-1">✗ Not Serviceable</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <?= htmlspecialchars($a['full_address']) ?>, <?= htmlspecialchars($a['city']) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="col-md-6">
            <div class="form-card">
                <h5 class="mb-3">Add New Address</h5>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Label</label>
                        <input type="text" name="label" class="form-control" placeholder="Home / Work / Other" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Address</label>
                        <input type="text" name="full_address" class="form-control" placeholder="Street, Building, Floor..." required>
                    </div>
                    <div class="row">
                        <div class="col">
                            <label class="form-label fw-bold">City</label>
                            <input type="text" name="city" class="form-control" placeholder="Cairo" required>
                        </div>
                        <div class="col">
                            <label class="form-label fw-bold">Region / District</label>
                            <input type="text" name="region" class="form-control" placeholder="Nasr City">
                        </div>
                    </div>
                    <!-- <div class="mb-3 mt-3">
                        <label class="form-label fw-bold">Postal Code (optional)</label>
                        <input type="text" name="postal_code" class="form-control" placeholder="11511">
                    </div> -->
                    <div class="alert alert-info small">
                        ✅ Serviceable areas: <?= implode(', ', $serviceableRegions) ?>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Add Address</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>