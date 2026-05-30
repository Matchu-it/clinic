<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Already logged in → redirect
if (Auth::check()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Registration successful! Please log in.';
}
if (isset($_GET['error']) && $_GET['error'] === 'login_required') {
    $error = 'Please log in to access that page.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please enter your username/email and password.';
    } else {
        $auth = new Auth();
        $user = $auth->login($username, $password);
        if ($user) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials. Please try again.';
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-card">
    <div class="auth-header">
        <i class="bi bi-heart-pulse-fill logo-icon"></i>
        <h1>ClinicCare</h1>
        <p>Appointment Reservation System</p>
    </div>
    <div class="auth-body">
        <h5 class="mb-4 fw-700">Welcome back</h5>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-auto-dismiss">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success alert-auto-dismiss">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label" for="username">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-person text-muted"></i>
                    </span>
                    <input type="text" id="username" name="username" class="form-control border-start-0"
                           placeholder="Enter username or email"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autocomplete="username" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input type="password" id="password" name="password" class="form-control border-start-0"
                           placeholder="Enter password"
                           autocomplete="current-password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">Demo admin: <strong>admin</strong> / <strong>Admin@1234</strong></small>
        </div>
    </div>
    <div class="auth-footer">
        Don't have an account? <a href="<?= BASE_URL ?>/register.php" class="text-primary fw-semibold">Register here</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
