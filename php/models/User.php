<?php
require_once __DIR__ . '/Model.php';

class User extends Model {

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }

    public function getAll(string $search = ''): array {
        if ($search) {
            $stmt = $this->db->prepare("
                SELECT id, full_name, user_email, subscription_tier, role, status 
                FROM users WHERE full_name LIKE ? OR user_email LIKE ?
            ");
            $like = "%$search%";
            $stmt->bind_param("ss", $like, $like);
        } else {
            $stmt = $this->db->prepare("
                SELECT id, full_name, user_email, subscription_tier, role, status 
                FROM users
            ");
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create(string $name, string $email, string $password, string $tier, int $role = 0): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            INSERT INTO users (full_name, user_email, user_password, subscription_tier, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", $name, $email, $hash, $tier, $role);
        return $stmt->execute();
    }

    public function update(int $id, string $name, string $tier, int $role, string $status): bool {
        $stmt = $this->db->prepare("
            UPDATE users SET full_name=?, subscription_tier=?, role=?, status=? WHERE id=?
        ");
        $stmt->bind_param("ssisi", $name, $tier, $role, $status, $id);
        return $stmt->execute();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function verifyPassword(string $plain, string $hashed): bool {
        return password_verify($plain, $hashed);
    }

    public function updateTier(int $id, string $tier): bool {
        $stmt = $this->db->prepare("UPDATE users SET subscription_tier = ? WHERE id = ?");
        $stmt->bind_param("si", $tier, $id);
        return $stmt->execute();
    }

    public function getLastId(): int {
        return $this->db->insert_id;
    }

    public function emailExists(string $email): bool {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
}