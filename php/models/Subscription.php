<?php
require_once __DIR__ . '/Model.php';

class Subscription extends Model {

    public function getPlans(): array {
        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPlanById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getUserActiveSubscription(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT us.*, sp.name AS plan_name, sp.theme, sp.price, sp.max_swaps, sp.tier
            FROM user_subscriptions us
            JOIN subscription_plans sp ON sp.id = us.plan_id
            WHERE us.user_id = ? AND us.status = 'active'
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getUserSubscriptions(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT us.*, sp.name AS plan_name, sp.price, sp.theme
            FROM user_subscriptions us
            JOIN subscription_plans sp ON sp.id = us.plan_id
            WHERE us.user_id = ?
            ORDER BY us.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create(int $userId, int $planId): bool {
        $start       = date('Y-m-d');
        $nextBilling = date('Y-m-d', strtotime('+1 month'));
        $stmt = $this->db->prepare("
            INSERT INTO user_subscriptions (user_id, plan_id, status, start_date, next_billing)
            VALUES (?, ?, 'active', ?, ?)
        ");
        $stmt->bind_param("iiss", $userId, $planId, $start, $nextBilling);
        return $stmt->execute();
    }

    public function pause(int $subId, int $userId): bool {
        $stmt = $this->db->prepare("
            UPDATE user_subscriptions SET status='paused', pause_start=CURDATE() 
            WHERE id=? AND user_id=?
        ");
        $stmt->bind_param("ii", $subId, $userId);
        return $stmt->execute();
    }

    public function resume(int $subId, int $userId): bool {
        $stmt = $this->db->prepare("
            UPDATE user_subscriptions SET status='active', pause_end=CURDATE() 
            WHERE id=? AND user_id=?
        ");
        $stmt->bind_param("ii", $subId, $userId);
        return $stmt->execute();
    }

    public function cancel(int $subId, int $userId, string $reason): bool {
        $offer = '';
        if (stripos($reason, 'expensive') !== false || stripos($reason, 'price') !== false) {
            $offer = 'Get 50% off your next box!';
        } elseif (stripos($reason, 'quality') !== false) {
            $offer = "We'll add a bonus item to your next box!";
        }
        $stmt = $this->db->prepare("
            UPDATE user_subscriptions SET status='cancelled', cancel_reason=?, retention_offer=? 
            WHERE id=? AND user_id=?
        ");
        $stmt->bind_param("ssii", $reason, $offer, $subId, $userId);
        return $stmt->execute();
    }

    public function getLastId(): int {
        return $this->db->insert_id;
    }

    public function addPlan(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO subscription_plans (name, theme, tier, price, billing_cycle, box_size, max_swaps, early_swap_access)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $early = $data['early_swap_access'] ? 1 : 0;
        $stmt->bind_param(
            "sssdssis",
            $data['name'], $data['theme'], $data['tier'],
            $data['price'], $data['billing_cycle'],
            $data['box_size'], $data['max_swaps'], $early
        );
        return $stmt->execute();
    }

    public function togglePlan(int $planId): bool {
        $stmt = $this->db->prepare("UPDATE subscription_plans SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $planId);
        return $stmt->execute();
    }
}