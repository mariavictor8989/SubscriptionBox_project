<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.html"); exit(); }

$userId  = $_SESSION['user_id'];
$success = '';

// Get current preferences
$pref = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$pref->bind_param("i", $userId);
$pref->execute();
$prefs = $pref->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allergens    = json_encode($_POST['allergens'] ?? []);
    $disliked     = json_encode(array_filter(array_map('trim', explode(',', $_POST['disliked_tags'] ?? ''))));
    $preferred    = json_encode(array_filter(array_map('trim', explode(',', $_POST['preferred_tags'] ?? ''))));

    if ($prefs) {
        $upd = $conn->prepare("UPDATE user_preferences SET allergens=?, disliked_tags=?, preferred_tags=? WHERE user_id=?");
        $upd->bind_param("sssi", $allergens, $disliked, $preferred, $userId);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO user_preferences (user_id, allergens, disliked_tags, preferred_tags) VALUES (?, ?, ?, ?)");
        $ins->bind_param("isss", $userId, $allergens, $disliked, $preferred);
        $ins->execute();
    }
    $success = 'Preferences saved!';
    $prefs = ['allergens' => $allergens, 'disliked_tags' => $disliked, 'preferred_tags' => $preferred];
}

$currentAllergens = json_decode($prefs['allergens'] ?? '[]', true) ?? [];
$currentDisliked  = implode(', ', json_decode($prefs['disliked_tags'] ?? '[]', true) ?? []);
$currentPreferred = implode(', ', json_decode($prefs['preferred_tags'] ?? '[]', true) ?? []);

$allergenOptions = ['Gluten', 'Dairy', 'Nuts', 'Eggs', 'Soy', 'Shellfish', 'Fish', 'Sesame'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Preferences - FreshBox</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background-color: #fdfcf7; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2c7a2c !important; }
        .pref-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); max-width: 600px; margin: 0 auto; }
        .allergen-btn { border: 2px solid #dee2e6; border-radius: 20px; padding: 6px 16px; cursor: pointer; transition: .2s; }
        .allergen-btn.active { border-color: #dc3545; background: #dc3545; color: white; }
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
    <div class="pref-card">
        <h4 class="mb-1">🥗 My Preferences</h4>
        <p class="text-muted small mb-4">We'll use this to personalize your box and filter out allergens.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="form-label fw-bold">Allergens to avoid</label>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($allergenOptions as $a): ?>
                        <label class="allergen-btn <?= in_array($a, $currentAllergens) ? 'active' : '' ?>">
                            <input type="checkbox" name="allergens[]" value="<?= $a ?>"
                                   <?= in_array($a, $currentAllergens) ? 'checked' : '' ?>
                                   class="d-none" onchange="toggleBtn(this)">
                            <?= $a ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Disliked items / tags</label>
                <input type="text" name="disliked_tags" class="form-control"
                       placeholder="e.g. spicy, mushrooms, coriander"
                       value="<?= htmlspecialchars($currentDisliked) ?>">
                <div class="form-text">Separate with commas</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Preferred items / tags</label>
                <input type="text" name="preferred_tags" class="form-control"
                       placeholder="e.g. organic, vegan, pasta"
                       value="<?= htmlspecialchars($currentPreferred) ?>">
                <div class="form-text">Separate with commas</div>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Save Preferences</button>
        </form>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function toggleBtn(input) {
    input.parentElement.classList.toggle('active', input.checked);
}
</script>
</body>
</html>