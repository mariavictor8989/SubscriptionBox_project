<?php
include 'db_connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = trim($_POST['full_name']);
    $email = trim($_POST['user_email']);
    $tier  = $_POST['subscription_tier'];
    $pass  = $_POST['user_password'];

    $allowed_tiers = ['standard', 'premium', 'vip'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email!'); window.location='register.html';</script>";
        exit();
    }
    if (strlen($pass) < 8) {
        echo "<script>alert('Password must be at least 8 characters!'); window.location='register.html';</script>";
        exit();
    }
    if (!in_array($tier, $allowed_tiers)) {
        echo "<script>alert('Invalid plan!'); window.location='register.html';</script>";
        exit();
    }

    $chk = $conn->prepare("SELECT id FROM users WHERE user_email = ?");
    $chk->bind_param("s", $email);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows > 0) {
        echo "<script>alert('Email already exists! Try another.'); window.location='register.html';</script>";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $ins  = $conn->prepare("INSERT INTO users (full_name, user_email, user_password, subscription_tier, role) VALUES (?, ?, ?, ?, 0)");
        $ins->bind_param("ssss", $name, $email, $hash, $tier);

        if ($ins->execute()) {
            echo "<script>alert('Account created! Please log in.'); window.location='login.html';</script>";
        } else {
            echo "<script>alert('Error! Please try again.'); window.location='register.html';</script>";
        }
    }
}
?>