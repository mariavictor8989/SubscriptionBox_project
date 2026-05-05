<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['user_email']);
    $password = $_POST['user_password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['user_password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_tier'] = $user['subscription_tier'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] == 1) {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] == 2) {
                header("Location: warehouse_dashboard.php");
            } elseif ($user['role'] == 3) {
                header("Location: delivery_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            echo "<script>alert('Invalid Password!'); window.location='login.html';</script>";
        }
    } else {
        echo "<script>alert('User not found!'); window.location='login.html';</script>";
    }
}
?>