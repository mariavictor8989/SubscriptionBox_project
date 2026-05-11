<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Preferences - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color:#fdfcf7; font-family:'Segoe UI',sans-serif; }
        .navbar { background-color:#2c7a2c !important; }
        .pref-card { background:white; border-radius:12px; padding:30px; box-shadow:0 4px 15px rgba(0,0,0,.07); max-width:600px; margin:0 auto; }
        .allergen-btn { border:2px solid #dee2e6; border-radius:20px; padding:6px 16px; cursor:pointer; transition:.2s; display:inline-block; margin:4px; }
        .allergen-btn.active { border-color:#dc3545; background:#dc3545; color:white; }
        .form-control { border:1px solid #ddd; border-radius:8px; padding:10px; }
        .form-control:focus { border-color:#2c7a2c; box-shadow:none; }
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
    <div class="pref-card">
        <h4 class="mb-1">🥗 My Preferences</h4>
        <p class="text-muted small mb-4">We'll use this to personalize your box and filter allergens.</p>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php
        $currentAllergens = json_decode($prefs['allergens'] ?? '[]', true) ?? [];
        $currentDisliked  = implode(', ', json_decode($prefs['disliked_tags'] ?? '[]', true) ?? []);
        $currentPreferred = implode(', ', json_decode($prefs['preferred_tags'] ?? '[]', true) ?? []);
        $allergenOptions  = ['Gluten','Dairy','Nuts','Eggs','Soy','Shellfish','Fish','Sesame'];
        ?>

        <form method="POST" action="index.php?controller=user&action=preferences">
            <div class="mb-4">
                <label class="form-label fw-bold">Allergens to avoid</label>
                <div>
                    <?php foreach ($allergenOptions as $a): ?>
                        <label class="allergen-btn <?= in_array($a, $currentAllergens) ? 'active' : '' ?>">
                            <input type="checkbox" name="allergens[]" value="<?= $a ?>"
                                   <?= in_array($a, $currentAllergens) ? 'checked' : '' ?>
                                   class="d-none" onchange="this.parentElement.classList.toggle('active', this.checked)">
                            <?= $a ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Disliked items</label>
                <input type="text" name="disliked_tags" class="form-control"
                       placeholder="e.g. spicy, mushrooms" value="<?= htmlspecialchars($currentDisliked) ?>">
                <div class="form-text">Separate with commas</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Preferred items</label>
                <input type="text" name="preferred_tags" class="form-control"
                       placeholder="e.g. organic, vegan" value="<?= htmlspecialchars($currentPreferred) ?>">
                <div class="form-text">Separate with commas</div>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Save Preferences</button>
        </form>
    </div>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>