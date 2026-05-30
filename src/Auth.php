<?php
/**
 * Auth.php
 * Handles authentication: login, registration, session helpers, and role guards.
 */
class Auth
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Attempt to log in a user.
     *
     * @param string $username  Username or email
     * @param string $password  Plain-text password
     * @return array|null       User row on success, null on failure
     */
    public function login(string $username, string $password): ?array
    {
        $user = $this->db->fetchOne(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $username]
        );

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role']      = $user['role'];
            return $user;
        }

        return null;
    }

    /**
     * Register a new patient account.
     *
     * @param array $data Validated form fields
     * @return int        New user ID
     * @throws RuntimeException on duplicate email/username
     */
    public function register(array $data): int
    {
        // Check uniqueness
        $existing = $this->db->fetchOne(
            'SELECT id FROM users WHERE username = ? OR email = ?',
            [$data['username'], $data['email']]
        );
        if ($existing) {
            throw new \RuntimeException('Username or email is already taken.');
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $this->db->execute(
            'INSERT INTO users (username, email, password, first_name, last_name, phone, role)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['username'],
                $data['email'],
                $hash,
                $data['first_name'],
                $data['last_name'],
                $data['phone'] ?? null,
                'patient',
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Destroy the current session (logout).
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /**
     * Check if the current visitor is logged in.
     */
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Require login; redirect to login page otherwise.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/index.php?error=login_required');
            exit;
        }
    }

    /**
     * Require admin role; redirect to dashboard otherwise.
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if ($_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
    }

    /**
     * Return the currently logged-in user's ID.
     */
    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /**
     * Return the currently logged-in user's role.
     */
    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }
}
