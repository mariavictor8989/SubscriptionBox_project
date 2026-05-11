<?php
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/controllers/Controller.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/WarehouseController.php';
require_once __DIR__ . '/controllers/DeliveryController.php';

if (!isset($_GET['controller'])) {
    require __DIR__ . '/home.php';
    exit();
}

$controller = $_GET['controller'] ?? 'auth';
$action     = $_GET['action']     ?? 'login';

switch ($controller) {
    case 'auth':      $c = new AuthController();      break;
    case 'user':      $c = new UserController();      break;
    case 'admin':     $c = new AdminController();     break;
    case 'warehouse': $c = new WarehouseController(); break;
    case 'delivery':  $c = new DeliveryController();  break;
    default:          $c = new AuthController();
}

if (method_exists($c, $action)) {
    $c->$action();
} else {
    echo "Action not found: $action";
}