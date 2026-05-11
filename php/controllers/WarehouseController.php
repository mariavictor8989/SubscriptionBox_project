<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../config/Database.php';

class WarehouseController extends Controller {

    private Order $orderModel;

    public function __construct() {
        $this->orderModel = new Order();
    }

    public function dashboard(): void {
        $this->requireWarehouse();
        $msg = '';
        $db  = Database::getInstance()->getConnection();

        $deliveryUsers = $db->query("SELECT id, full_name FROM users WHERE role = 3")->fetch_all(MYSQLI_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_status') {
                $orderId   = (int) $_POST['order_id'];
                $newStatus = $_POST['new_status'];
                $allowed   = ['picking', 'packed', 'shipped'];

                if (in_array($newStatus, $allowed)) {
                    $upd = $db->prepare("UPDATE boxes b JOIN orders o ON o.box_id = b.id SET b.status = ? WHERE o.id = ?");
                    $upd->bind_param("si", $newStatus, $orderId);
                    $upd->execute();

                    $delivId = (int) ($_POST['delivery_user_id'] ?? 0);
                    if ($newStatus === 'shipped' && $delivId > 0) {
                        $this->orderModel->updateShipmentStatus($orderId, $newStatus, $delivId);
                    } else {
                        $this->orderModel->updateShipmentStatus($orderId, $newStatus);
                    }

                    $customerId = $this->orderModel->getUserId($orderId);
                    if ($customerId) {
                        $this->addNotification($customerId, 'delivery', "Your order #$orderId updated to: " . ucfirst($newStatus));
                    }

                    $this->logAction('update_order_status', 'orders', $orderId);
                    $msg = "✅ Order #$orderId updated to: " . ucfirst($newStatus);
                }

            } elseif ($action === 'update_stock') {
                $itemId   = (int) $_POST['item_id'];
                $newStock = (int) $_POST['stock_qty'];
                $upd      = $db->prepare("UPDATE inventory SET stock_qty = ? WHERE item_id = ?");
                $upd->bind_param("ii", $newStock, $itemId);
                $upd->execute();
                $msg = "✅ Stock updated!";

            } elseif ($action === 'handle_return') {
                $returnId  = (int) $_POST['return_id'];
                $newStatus = $_POST['return_status'];
                $allowed   = ['in_transit', 'received', 'refunded'];

                if (in_array($newStatus, $allowed)) {
                    $upd = $db->prepare("UPDATE returns SET status = ? WHERE id = ?");
                    $upd->bind_param("si", $newStatus, $returnId);
                    $upd->execute();
                    $msg = "✅ Return #$returnId updated to: " . ucfirst($newStatus);
                }
            }
        }

        $orders = $this->orderModel->getWarehouseOrders();

        $lowStock = $db->query("
            SELECT i.id, i.name, i.theme, inv.stock_qty, inv.reorder_threshold
            FROM items i JOIN inventory inv ON inv.item_id = i.id
            WHERE inv.stock_qty <= inv.reorder_threshold AND i.is_active = 1
            ORDER BY inv.stock_qty ASC
        ")->fetch_all(MYSQLI_ASSOC);

        $returns = $db->query("
            SELECT r.*, u.full_name, o.id AS order_id
            FROM returns r
            JOIN users u ON u.id = r.user_id
            JOIN orders o ON o.id = r.order_id
            WHERE r.status IN ('requested','in_transit')
            ORDER BY r.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);

        $inventory = $db->query("
            SELECT i.id, i.name, i.theme, inv.stock_qty, inv.reorder_threshold,
                   (inv.stock_qty <= inv.reorder_threshold) AS low_stock
            FROM items i JOIN inventory inv ON inv.item_id = i.id
            WHERE i.is_active = 1
            ORDER BY low_stock DESC, i.name ASC
        ")->fetch_all(MYSQLI_ASSOC);

        $this->render('warehouse/dashboard', [
            'orders'        => $orders,
            'lowStock'      => $lowStock,
            'returns'       => $returns,
            'inventory'     => $inventory,
            'deliveryUsers' => $deliveryUsers,
            'msg'           => $msg,
        ]);
    }
}