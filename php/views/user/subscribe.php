<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscribe - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .plan-card { border:2px solid #e0e0e0; border-radius:14px; padding:25px; cursor:pointer; transition:.25s; background:white; height:100%; position:relative; }
        .plan-card:hover { border-color:#2c7a2c; transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.1); }
        .plan-card.selected { border-color:#2c7a2c; background:#f0faf0; }
        .plan-card.vip-card { border-color:#daa520; }
        .plan-card.vip-card.selected { background:#fffbea; }
        .plan-card.selected .check-icon { display:flex; }
        .check-icon { display:none; width:28px; height:28px; background:#2c7a2c; color:white; border-radius:50%; align-items:center; justify-content:center; font-size:14px; font-weight:bold; position:absolute; top:12px; right:12px; }
        .vip-card .check-icon { background:#daa520; }
        .plan-price { font-size:1.8rem; font-weight:900; color:#2c7a2c; }
        .plan-price.vip { color:#daa520; }
        .plan-feature { font-size:.88rem; color:#555; padding:3px 0; }
        .plan-feature::before { content:'✓ '; color:#2c7a2c; font-weight:bold; }
        .form-select { border:1.5px solid #ddd; border-radius:10px; padding:12px; }
        .form-select:focus { border-color:#2c7a2c; box-shadow:none; }
        .btn-subscribe { background:#2c7a2c; color:white; padding:16px; font-size:1.1rem; font-weight:700; border:none; border-radius:12px; width:100%; transition:.25s; cursor:pointer; }
        .btn-subscribe:hover { background:#1e5c1e; transform:translateY(-2px); }
    </style>
</head>
<body>
<nav class="navbar navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?controller=user&action=dashboard">📦 FreshBox</a>
        <a href="index.php?controller=user&action=dashboard" class="btn btn-outline-light btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="container mt-4" style="max-width:750px">
    <h3 class="mb-1 fw-bold">Choose Your Plan</h3>
    <p class="text-muted mb-4">Select a subscription plan to get started</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger fw-bold"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?controller=user&action=subscribe" id="subscribe-form">
        <input type="hidden" name="plan_id" id="selected-plan-id" value="0">

        <div class="row g-3 mb-4">
        <?php foreach ($plans as $plan): ?>
            <div class="col-md-4">
                <div class="plan-card <?= $plan['tier'] === 'vip' ? 'vip-card' : '' ?>" id="plan-card-<?= $plan['id'] ?>" onclick="selectPlan(<?= $plan['id'] ?>)">
                    <div class="check-icon">✓</div>
                    <input type="radio" name="_plan_id" value="<?= $plan['id'] ?>" class="d-none">
                    <div class="fw-bold fs-6 mb-1"><?= htmlspecialchars($plan['name']) ?></div>
                    <div class="text-muted small mb-3"><?= htmlspecialchars($plan['theme']) ?></div>
                    <div class="plan-price <?= $plan['tier'] === 'vip' ? 'vip' : '' ?> mb-1">
                        <?= number_format($plan['price'], 0) ?><span style="font-size:.9rem;color:#888;font-weight:400;"> EGP/mo</span>
                    </div>
                    <hr>
                    <div class="plan-feature"><?= $plan['max_swaps'] ?> swaps/month</div>
                    <div class="plan-feature"><?= ucfirst($plan['box_size']) ?> box</div>
                    <?php if ($plan['early_swap_access']): ?><div class="plan-feature">Early swap access</div><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <div id="no-plan-warning" class="alert alert-warning d-none fw-bold">⚠️ Please select a plan before subscribing.</div>

        <div class="mb-4">
            <label class="form-label fw-bold">Delivery Address</label>
            <?php if (empty($addresses)): ?>
                <div class="alert alert-warning">No addresses found. <a href="index.php?controller=user&action=addAddress" class="fw-bold">Add one first →</a></div>
            <?php else: ?>
                <select name="address_id" class="form-select" required>
                    <option value="">Select address...</option>
                    <?php foreach ($addresses as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= !$a['is_serviceable'] ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($a['label'] . ' — ' . $a['full_address']) ?>
                            <?= !$a['is_serviceable'] ? ' (Not serviceable)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <button type="button" class="btn-subscribe" onclick="submitSubscribe()">Subscribe Now</button>
    </form>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function selectPlan(planId) {
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('plan-card-' + planId).classList.add('selected');
    document.getElementById('selected-plan-id').value = planId;
    document.getElementById('no-plan-warning').classList.add('d-none');
}
function submitSubscribe() {
    const planId = document.getElementById('selected-plan-id').value;
    if (!planId || planId == 0) {
        document.getElementById('no-plan-warning').classList.remove('d-none');
        window.scrollTo({top:0, behavior:'smooth'});
        return;
    }
    document.getElementById('subscribe-form').submit();
}
</script>
</body>
</html>