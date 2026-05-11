<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/Database.php';

class AuthController extends Controller {

    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['user_role']);
        }
        $this->render('auth/login', ['error' => '']);
    }

    public function loginPost(): void {
        $email    = trim($_POST['user_email'] ?? '');
        $password = $_POST['user_password'] ?? '';

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['user_password'])) {
            $this->render('auth/login', ['error' => 'Invalid email or password.']);
            return;
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_tier'] = $user['subscription_tier'];
        $_SESSION['user_role'] = $user['role'];

        $this->redirectByRole($user['role']);
    }

    public function register(): void {
        $this->render('auth/register', ['error' => '']);
    }

    public function registerPost(): void {
        $name     = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['user_email'] ?? '');
        $password = $_POST['user_password'] ?? '';
        $tier     = $_POST['subscription_tier'] ?? 'standard';

        $allowedTiers = ['standard', 'premium', 'vip'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('auth/register', ['error' => 'Invalid email address.']);
            return;
        }
        if (strlen($password) < 8) {
            $this->render('auth/register', ['error' => 'Password must be at least 8 characters.']);
            return;
        }
        if (!in_array($tier, $allowedTiers)) {
            $this->render('auth/register', ['error' => 'Invalid plan selected.']);
            return;
        }
        if ($this->userModel->emailExists($email)) {
            $this->render('auth/register', ['error' => 'Email already exists.']);
            return;
        }

        if ($this->userModel->create($name, $email, $password, $tier)) {
            $this->redirect('index.php?controller=auth&action=login&msg=registered');
        } else {
            $this->render('auth/register', ['error' => 'Registration failed. Please try again.']);
        }
    }

    public function logout(): void {
        session_start();
        session_unset();
        session_destroy();
        $this->redirect('index.php?controller=auth&action=login');
    }

    private function redirectByRole(int $role): void {
        switch ($role) {
            case 1: $this->redirect('index.php?controller=admin&action=dashboard');     break;
            case 2: $this->redirect('index.php?controller=warehouse&action=dashboard'); break;
            case 3: $this->redirect('index.php?controller=delivery&action=dashboard');  break;
            default: $this->redirect('index.php?controller=user&action=dashboard');
        }
    }
}