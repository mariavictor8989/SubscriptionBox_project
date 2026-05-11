<?php

abstract class Controller {

    protected function render(string $view, array $data = []): void {
        // Extract data array as variables
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "View not found: $view";
        }
    }

    
protected function redirect(string $url): void {
    if (!str_starts_with($url, 'http') && !str_starts_with($url, '/')) {
        $base = dirname($_SERVER['SCRIPT_NAME']);
        $base = ($base === '/' || $base === '\\') ? '/' : rtrim($base, '/') . '/';
        $url  = $base . $url;
    }
    header("Location: " . $url);
    exit();
}


    protected function requireLogin(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('index.php?controller=auth&action=login');
        }
    }

    protected function requireAdmin(): void {
        $this->requireLogin();
        if ($_SESSION['user_role'] != 1) {
            $this->redirect('index.php?controller=auth&action=login');
        }
    }

    protected function requireWarehouse(): void {
        $this->requireLogin();
        if ($_SESSION['user_role'] != 2) {
            $this->redirect('index.php?controller=auth&action=login');
        }
    }

    protected function requireDelivery(): void {
        $this->requireLogin();
        if ($_SESSION['user_role'] != 3) {
            $this->redirect('index.php?controller=auth&action=login');
        }
    }

    protected function addNotification(int $userId, string $type, string $message): void {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $type, $message);
        $stmt->execute();
    }

    protected function logAction(string $action, string $target, int $targetId): void {
        if (!isset($_SESSION['user_id'])) return;
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO audit_log (user_id, action, target, target_id, ip_address) VALUES (?, ?, ?, ?, ?)");
        $ip   = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("issis", $_SESSION['user_id'], $action, $target, $targetId, $ip);
        $stmt->execute();
    }
}