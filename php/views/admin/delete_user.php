<?php
session_start();
include '../../config/DataBase.php'; 

$dbInstance = Database::getInstance();
$conn = $dbInstance->getConnection();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header("Location: ../../index.php?controller=auth&action=login");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../index.php?controller=admin&action=dashboard");
    exit();
}

$id = (int) $_GET['id'];

if ($id === (int) $_SESSION['user_id']) {
    header("Location: ../../index.php?controller=admin&action=dashboard&msg=cannot_delete_self");
    exit();
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $ip  = $_SERVER['REMOTE_ADDR'];
    $log = $conn->prepare("INSERT INTO audit_log (user_id, action, target, target_id, ip_address) VALUES (?, 'delete_user', 'users', ?, ?)");
    $log->bind_param("iis", $_SESSION['user_id'], $id, $ip);
    $log->execute();
    
    header("Location: ../../index.php?controller=admin&action=dashboard&msg=deleted");
} else {
    header("Location: ../../index.php?controller=admin&action=dashboard&msg=error");
}
exit();
?>