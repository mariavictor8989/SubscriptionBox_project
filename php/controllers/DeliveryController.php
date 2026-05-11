<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../config/Database.php';

class DeliveryController extends Controller {

    private Order $orderModel;

    public function __construct() {
        $this->orderModel = new Order();
    }

    public function dashboard(): void {
        $this->requireDelivery();
        $userId = (int) $_SESSION['user_id'];
        $msg    = '';
        $db     = Database::getInstance()->getConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId   = (int) $_POST['order_id'];
            $newStatus = $_POST['new_status'];
            $allowed   = ['out_for_delivery', 'delivered'];

            if (in_array($newStatus, $allowed)) {
                $upd = $db->prepare("UPDATE shipments SET status = ? WHERE order_id = ? AND delivery_user_id = ?");
                $upd->bind_param("sii", $newStatus, $orderId, $userId);
                $upd->execute();

                if ($newStatus === 'delivered') {
                    $upd2 = $db->prepare("UPDATE boxes b JOIN orders o ON o.box_id = b.id SET b.status = 'delivered' WHERE o.id = ?");
                    $upd2->bind_param("i", $orderId);
                    $upd2->execute();
                }

                $customerId = $this->orderModel->getUserId($orderId);
                if ($customerId) {
                    $notifMsg = $newStatus === 'delivered'
                        ? "📦 Your order #$orderId has been delivered! Enjoy your box!"
                        : "🚚 Your order #$orderId is out for delivery!";
                    $this->addNotification($customerId, 'delivery', $notifMsg);
                }

                $msg = "✅ Order #$orderId updated to: " . ucfirst(str_replace('_', ' ', $newStatus));
            }
        }

        $orders         = $this->orderModel->getDeliveryOrders($userId);
        $outForDelivery = count(array_filter($orders, fn($o) => $o['ship_status'] === 'out_for_delivery'));
        $shipped        = count(array_filter($orders, fn($o) => $o['ship_status'] === 'shipped'));

        $byCity = [];
        foreach ($orders as $o) {
            $byCity[$o['city']][] = $o;
        }
        ksort($byCity);

        $this->render('delivery/dashboard', [
            'orders'         => $orders,
            'byCity'         => $byCity,
            'outForDelivery' => $outForDelivery,
            'shipped'        => $shipped,
            'msg'            => $msg,
        ]);
    }
}