<?php
/**
 * User.php
 * CRUD operations for the `users` table.
 */
class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Return all users, newest first.
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            'SELECT id, username, email, first_name, last_name, phone, role, created_at
             FROM users ORDER BY created_at DESC'
        );
    }

    /**
     * Return a single user by ID.
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT id, username, email, first_name, last_name, phone, role, created_at
             FROM users WHERE id = ?',
            [$id]
        );
    }

    /**
     * Return all patient-role users.
     */
    public function getPatients(): array
    {
        return $this->db->fetchAll(
            "SELECT id, username, email, first_name, last_name, phone, created_at
             FROM users WHERE role = 'patient' ORDER BY last_name, first_name"
        );
    }

    /**
     * Update a user's profile fields (admin operation).
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowed = ['first_name','last_name','email','phone','role'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[]  = $data[$field];
            }
        }

        // Optional password change
        if (!empty($data['password'])) {
            $fields[] = 'password = ?';
            $params[]  = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $this->db->execute(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $params
        );
        return true;
    }

    /**
     * Delete a user by ID.
     */
    public function delete(int $id): bool
    {
        // Prevent self-deletion
        if ($id === Auth::id()) {
            throw new \RuntimeException('You cannot delete your own account.');
        }
        $this->db->execute('DELETE FROM users WHERE id = ?', [$id]);
        return true;
    }

    /**
     * Count users by role.
     */
    public function countByRole(string $role): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM users WHERE role = ?', [$role]
        );
        return (int) ($row['cnt'] ?? 0);
    }
}
