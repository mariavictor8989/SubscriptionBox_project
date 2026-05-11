<?php
require_once __DIR__ . '/Model.php';

class Box extends Model {

    public function getActiveBox(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT b.*, us.plan_id
            FROM boxes b
            JOIN user_subscriptions us ON us.id = b.subscription_id
            WHERE b.user_id = ? AND b.status IN ('pending','customizing')
            ORDER BY b.created_at DESC LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(int $subId, int $userId): int {
        $lockAt = date('Y-m-d H:i:s', strtotime('+5 days'));
        $month  = date('Y-m-01');
        $stmt = $this->db->prepare("
            INSERT INTO boxes (subscription_id, user_id, month, status, lock_at) 
            VALUES (?, ?, ?, 'pending', ?)
        ");
        $stmt->bind_param("iiss", $subId, $userId, $month, $lockAt);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function getItems(int $boxId): array {
        $stmt = $this->db->prepare("
            SELECT bi.*, i.name, i.description, i.weight_g, i.price
            FROM box_items bi
            JOIN items i ON i.id = bi.item_id
            WHERE bi.box_id = ?
        ");
        $stmt->bind_param("i", $boxId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addItem(int $boxId, int $itemId, bool $isSwap, bool $isAddon): bool {
        $swap  = $isSwap  ? 1 : 0;
        $addon = $isAddon ? 1 : 0;
        $stmt  = $this->db->prepare("INSERT INTO box_items (box_id, item_id, is_swap, is_addon) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $boxId, $itemId, $swap, $addon);
        return $stmt->execute();
    }

    public function removeItem(int $boxItemId, int $boxId): bool {
        $stmt = $this->db->prepare("DELETE FROM box_items WHERE id = ? AND box_id = ?");
        $stmt->bind_param("ii", $boxItemId, $boxId);
        return $stmt->execute();
    }

    // Swap item
    public function swapItem(int $oldBoxItemId, int $boxId, int $newItemId): bool {
        $del = $this->db->prepare("DELETE FROM box_items WHERE id = ? AND box_id = ?");
        $del->bind_param("ii", $oldBoxItemId, $boxId);
        $del->execute();

        $ins = $this->db->prepare("INSERT INTO box_items (box_id, item_id, is_swap, is_addon) VALUES (?, ?, 1, 0)");
        $ins->bind_param("ii", $boxId, $newItemId);
        return $ins->execute();
    }

    public function updateStatus(int $boxId, string $status): bool {
        $stmt = $this->db->prepare("UPDATE boxes SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $boxId);
        return $stmt->execute();
    }

    public function updateType(int $boxId, string $type): bool {
        $stmt = $this->db->prepare("UPDATE boxes SET box_type = ? WHERE id = ?");
        $stmt->bind_param("si", $type, $boxId);
        return $stmt->execute();
    }

    public function isLocked(array $box): bool {
        return strtotime($box['lock_at']) <= time();
    }

    public function getAddonTotal(int $boxId): float {
        $stmt = $this->db->prepare("
            SELECT SUM(i.price) AS total
            FROM box_items bi
            JOIN items i ON i.id = bi.item_id
            WHERE bi.box_id = ? AND bi.is_addon = 1
        ");
        $stmt->bind_param("i", $boxId);
        $stmt->execute();
        return (float) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    }
}