<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Subscription.php';
require_once __DIR__ . '/../config/Database.php';

class AdminController extends Controller {

    private User         $userModel;
    private Subscription $subModel;

    public function __construct() {
        $this->userModel = new User();
        $this->subModel  = new Subscription();
    }

    // Admin dashboard — manage users
    public function dashboard(): void {
        $this->requireAdmin();
        $search = trim($_GET['search'] ?? '');
        $msg    = $_GET['msg'] ?? '';
        $users  = $this->userModel->getAll($search);

        $this->render('admin/dashboard', [
            'users'  => $users,
            'search' => $search,
            'msg'    => $msg,
            'total'  => count($users),
        ]);
    }

    // Add user
    public function addUser(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name  = trim($_POST['full_name']);
            $email = trim($_POST['user_email']);
            $pass  = $_POST['user_password'];
            $tier  = $_POST['subscription_tier'];
            $role  = (int) $_POST['role'];

            $allowedTiers = ['standard','premium','vip'];
            $allowedRoles = [0,1,2,3];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email.';
            } elseif (strlen($pass) < 8) {
                $error = 'Password too short.';
            } elseif (!in_array($tier, $allowedTiers) || !in_array($role, $allowedRoles)) {
                $error = 'Invalid tier or role.';
            } elseif ($this->userModel->emailExists($email)) {
                $error = 'Email already exists.';
            } else {
                if ($this->userModel->create($name, $email, $pass, $tier, $role)) {
                    $newId = $this->userModel->getLastId();
                    $this->logAction('add_user', 'users', $newId);
                    $this->redirect('index.php?controller=admin&action=dashboard&msg=added');
                }
                $error = 'Could not add user.';
            }
            $this->render('admin/add_user', ['error' => $error]);
            return;
        }
        $this->render('admin/add_user', ['error' => '']);
    }

    // Edit user
    public function editUser(): void {
        $this->requireAdmin();
        $id   = (int) ($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $this->redirect('index.php?controller=admin&action=dashboard');
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name   = trim($_POST['name']);
            $tier   = $_POST['tier'];
            $role   = (int) $_POST['role'];
            $status = $_POST['status'];

            $allowedTiers   = ['standard','premium','vip'];
            $allowedRoles   = [0,1,2,3];
            $allowedStatus  = ['active','paused','suspended','cancelled'];

            if (!in_array($tier, $allowedTiers) || !in_array($role, $allowedRoles) || !in_array($status, $allowedStatus)) {
                $error = 'Invalid input.';
            } else {
                if ($this->userModel->update($id, $name, $tier, $role, $status)) {
                    $this->logAction('edit_user', 'users', $id);
                    $this->redirect('index.php?controller=admin&action=dashboard&msg=updated');
                }
                $error = 'Update failed.';
            }
        }
        $this->render('admin/edit_user', ['user' => $user, 'error' => $error]);
    }

    public function deleteUser(): void {
        $this->requireAdmin();
        $id = (int) ($_GET['id'] ?? 0);

        if ($id === (int) $_SESSION['user_id']) {
            $this->redirect('index.php?controller=admin&action=dashboard&msg=cannot_delete_self');
        }

        if ($this->userModel->delete($id)) {
            $this->logAction('delete_user', 'users', $id);
            $this->redirect('index.php?controller=admin&action=dashboard&msg=deleted');
        }
        $this->redirect('index.php?controller=admin&action=dashboard&msg=error');
    }

    public function plans(): void {
        $this->requireAdmin();
        $msg = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'add') {
                $this->subModel->addPlan($_POST);
                $msg = 'Plan added!';
            } elseif ($action === 'toggle') {
                $this->subModel->togglePlan((int) $_POST['plan_id']);
                $msg = 'Plan status updated.';
            }
        }

        $plans = $this->subModel->getPlans();
        $this->render('admin/plans', ['plans' => $plans, 'msg' => $msg]);
    }
    
public function manageInventory() {
    $dbInstance = Database::getInstance();
    $conn = $dbInstance->getConnection();

    $msg   = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_item') {
            $name      = trim($_POST['name']);
            $desc      = trim($_POST['description']);
            $theme     = trim($_POST['theme']);
            $weight    = (float) $_POST['weight_g'];
            $isLimited = isset($_POST['is_limited']) ? 1 : 0;
            $isVip     = isset($_POST['is_vip_only']) ? 1 : 0;
            $stock     = (int) $_POST['stock_qty'];
            $threshold = (int) $_POST['reorder_threshold'];

            $ins = $conn->prepare("INSERT INTO items (name, description, theme, weight_g, is_limited, is_vip_only) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("sssdii", $name, $desc, $theme, $weight, $isLimited, $isVip);

            if ($ins->execute()) {
                $itemId = $conn->insert_id;
                $inv    = $conn->prepare("INSERT INTO inventory (item_id, stock_qty, reorder_threshold) VALUES (?, ?, ?)");
                $inv->bind_param("iii", $itemId, $stock, $threshold);
                $inv->execute();
                $msg = 'Item added to inventory!';
            } else {
                $error = 'Failed to add item.';
            }

        } elseif ($action === 'update_stock') {
            $itemId   = (int) $_POST['item_id'];
            $newStock = (int) $_POST['stock_qty'];
            $upd      = $conn->prepare("UPDATE inventory SET stock_qty = ? WHERE item_id = ?");
            $upd->bind_param("ii", $newStock, $itemId);
            $upd->execute();
            $msg = 'Stock updated!';
        }
    }

    $items = $conn->query("
        SELECT i.*, inv.stock_qty, inv.reserved_qty, inv.reorder_threshold,
               (inv.stock_qty <= inv.reorder_threshold) AS low_stock
        FROM items i
        JOIN inventory inv ON inv.item_id = i.id
        ORDER BY low_stock DESC, i.name ASC
    ");
    $this->render('admin/manage_inventory', [
    'items' => $items->fetch_all(MYSQLI_ASSOC),
    'msg'   => $msg,
    'error' => $error,
]);

}
    public function logs(): void {
        $this->requireAdmin();
        $db   = Database::getInstance()->getConnection();
        $logs = $db->query("
            SELECT al.*, u.full_name, u.user_email
            FROM audit_log al
            LEFT JOIN users u ON u.id = al.user_id
            ORDER BY al.created_at DESC LIMIT 200
        ")->fetch_all(MYSQLI_ASSOC);

        $this->render('admin/logs', ['logs' => $logs]);
    }
}