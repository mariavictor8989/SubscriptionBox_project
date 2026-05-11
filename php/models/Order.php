<?php
require_once __DIR__ . '/Model.php';

class Order extends Model {

    public function create(int $userId, int $boxId, int $subId, int $addressId, float $amount, float $tax): int {
        $stmt = $this->db->prepare("
            INSERT INTO orders (user_id, box_id, subscription_id, address_id, amount, tax, status)
            VALUES (?, ?, ?, ?, ?, ?, 'paid')
        ");
        $stmt->bind_param("iiiidd", $userId, $boxId, $subId, $addressId, $amount, $tax);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function createShipment(int $orderId): bool {
        $stmt = $this->db->prepare("INSERT INTO shipments (order_id, status) VALUES (?, 'pending')");
        $stmt->bind_param("i", $orderId);
        return $stmt->execute();
    }

    public function getWarehouseOrders(): array {
        $result = $this->db->query("
            SELECT o.id AS order_id, o.amount, o.created_at,
                   u.full_name, u.user_email,
                   b.status AS box_status, b.box_type,
                   sp.name AS plan_name,
                   a.full_address, a.city,
                   s.delivery_user_id,
                   COUNT(bi.id) AS item_count
            FROM orders o
            JOIN users u ON u.id = o.user_id
            JOIN boxes b ON b.id = o.box_id
            JOIN user_subscriptions us ON us.id = o.subscription_id
            JOIN subscription_plans sp ON sp.id = us.plan_id
            JOIN addresses a ON a.id = o.address_id
            JOIN shipments s ON s.order_id = o.id
            LEFT JOIN box_items bi ON bi.box_id = b.id
            WHERE b.status IN ('pending','customizing','picking','packed')
            GROUP BY o.id
            ORDER BY o.created_at ASC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getDeliveryOrders(int $deliveryUserId): array {
        $stmt = $this->db->prepare("
            SELECT o.id AS order_id, o.created_at, o.amount,
                   u.full_name, u.user_email,
                   s.status AS ship_status,
                   a.full_address, a.city, a.label AS addr_label, a.postal_code,
                   sp.name AS plan_name, b.box_type
            FROM orders o
            JOIN users u ON u.id = o.user_id
            JOIN shipments s ON s.order_id = o.id
            JOIN addresses a ON a.id = o.address_id
            JOIN user_subscriptions us ON us.id = o.subscription_id
            JOIN subscription_plans sp ON sp.id = us.plan_id
            JOIN boxes b ON b.id = o.box_id
            WHERE s.status IN ('shipped','out_for_delivery')
            AND s.delivery_user_id = ?
            ORDER BY o.created_at ASC
        ");
        $stmt->bind_param("i", $deliveryUserId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateShipmentStatus(int $orderId, string $status, ?int $deliveryUserId = null): bool {
        if ($deliveryUserId) {
            $stmt = $this->db->prepare("UPDATE shipments SET status=?, delivery_user_id=? WHERE order_id=?");
            $stmt->bind_param("sii", $status, $deliveryUserId, $orderId);
        } else {
            $stmt = $this->db->prepare("UPDATE shipments SET status=? WHERE order_id=?");
            $stmt->bind_param("si", $status, $orderId);
        }
        return $stmt->execute();
    }

    public function getUserId(int $orderId): ?int {
        $stmt = $this->db->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int) $row['user_id'] : null;
    }
}