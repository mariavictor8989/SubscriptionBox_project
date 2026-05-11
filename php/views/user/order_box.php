<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Box - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .order-card { background:white; border-radius:16px; padding:35px; box-shadow:0 4px 15px rgba(0,0,0,.07); max-width:600px; margin:0 auto; }
        .price-box { background:#f8f9fa; border-radius:12px; padding:18px; }
        .price-box .total-row { font-size:1.2rem; font-weight:800; color:#2c7a2c; }
        .addon-item { display:flex; justify-content:space-between; font-size:.88rem; color:#555; padding:3px 0; }
        .success-card { background:#f0faf0; border:2px solid #2c7a2c; border-radius:16px; padding:30px; text-align:center; }
        .form-select { border:1.5px solid #ddd; border-radius:10px; padding:12px; }
        .form-select:focus { border-color:#2c7a2c; box-shadow:none; }
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
    <div class="order-card">
        <?php if ($success): ?>
        <div class="success-card">
            <div style="font-size:3rem">✅</div>
            <h4 class="fw-bold mb-2 mt-2">Order Placed!</h4>
            <p class="text-muted mb-1">Order ID: <strong>#<?= $orderId ?></strong></p>
            <p class="mb-3">Total: <strong class="text-success fs-5"><?= number_format($amount, 2) ?> EGP</strong> <span class="text-muted small">(incl. 14% VAT)</span></p>
            <div class="alert alert-info small text-start">📦 Your box is ready! Customize it before the lock time.</div>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="index.php?controller=user&action=customizeBox" class="btn btn-success fw-bold">✏️ Customize My Box</a>
                <a href="index.php?controller=user&action=dashboard" class="btn btn-outline-success">← Dashboard</a>
            </div>
        </div>

        <?php else: ?>
        <h4 class="mb-1 fw-bold">🛒 Order My Box</h4>
        <p class="text-muted small mb-4">Place your monthly box order</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($subscriptions)): ?>
            <div class="alert alert-warning">No active subscriptions. <a href="index.php?controller=user&action=subscribe" class="fw-bold">Subscribe first →</a></div>
        <?php elseif (empty($addresses)): ?>
            <div class="alert alert-warning">No serviceable addresses. <a href="index.php?controller=user&action=addAddress" class="fw-bold">Add one →</a></div>
        <?php else: ?>
        <form method="POST" action="index.php?controller=user&action=orderBox">
            <?php if ($activeBoxId): ?>
                <input type="hidden" name="existing_box_id" value="<?= $activeBoxId ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label fw-bold">Subscription Plan</label>
                <select name="subscription_id" class="form-select" required onchange="updatePrice(this)">
                    <option value="">Select your subscription...</option>
                    <?php foreach ($subscriptions as $s): ?>
                        <option value="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>">
                            <?= htmlspecialchars($s['name']) ?> — <?= number_format($s['price'], 2) ?> EGP/mo
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Delivery Address</label>
                <select name="address_id" class="form-select" required>
                    <option value="">Select address...</option>
                    <?php foreach ($addresses as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['label'] . ' — ' . $a['full_address'] . ', ' . $a['city']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text"><a href="index.php?controller=user&action=addAddress">+ Add new address</a></div>
            </div>

            <div class="price-box mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Plan Price</span>
                    <span id="plan-price" class="fw-bold">—</span>
                </div>
                <?php if (!empty($addonItems)): ?>
                <div class="mb-2">
                    <div class="text-muted small mb-1">Add-on Items:</div>
                    <?php foreach ($addonItems as $a): ?>
                    <div class="addon-item"><span>+ <?= htmlspecialchars($a['name']) ?></span><span><?= number_format($a['price'], 2) ?> EGP</span></div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between mt-1 pt-1 border-top">
                        <span class="text-muted small">Add-ons Total</span>
                        <span class="small fw-bold"><?= number_format($addonTotal, 2) ?> EGP</span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span id="subtotal">—</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">VAT (14%)</span><span id="vat" class="text-muted">—</span></div>
                <hr class="my-2">
                <div class="d-flex justify-content-between total-row"><span>Total</span><span id="total">—</span></div>
            </div>
            <div class="mt-4 p-3 border rounded shadow-sm bg-light">
    <h6 class="fw-bold mb-3">💳 Payment Information</h6>
    <div class="mb-3">
        <label class="form-label small fw-bold">Card Number</label>
        <input type="text" name="card_number" class="form-control" placeholder="1234 5678 9101 1121" maxlength="16">
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label small fw-bold">Expiry Date</label>
            <input type="text" name="expiry" class="form-control" placeholder="MM/YY">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label small fw-bold">CVV</label>
            <input type="password" name="cvv" class="form-control" placeholder="123" maxlength="3">
        </div>
    </div>
</div>
<br>

            <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5">🛒 Place Order</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
const addonTotal = <?= $addonTotal ?? 0 ?>;
function updatePrice(sel) {
    const price = parseFloat(sel.options[sel.selectedIndex].dataset.price) || 0;
    if (!price) { ['plan-price','subtotal','vat','total'].forEach(id => document.getElementById(id).textContent = '—'); return; }
    const subtotal = price + addonTotal;
    const vat = subtotal * 0.14;
    const total = subtotal + vat;
    document.getElementById('plan-price').textContent = price.toFixed(2) + ' EGP';
    document.getElementById('subtotal').textContent   = subtotal.toFixed(2) + ' EGP';
    document.getElementById('vat').textContent        = vat.toFixed(2) + ' EGP';
    document.getElementById('total').textContent      = total.toFixed(2) + ' EGP';
}
</script>
</body>
</html>