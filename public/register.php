<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (Auth::check()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name'  => trim($_POST['last_name']  ?? ''),
        'username'   => trim($_POST['username']   ?? ''),
        'email'      => trim($_POST['email']      ?? ''),
        'phone'      => trim($_POST['phone']      ?? ''),
        'password'   => $_POST['password']        ?? '',
        'confirm'    => $_POST['confirm_password'] ?? '',
    ];

    // Validation
    if (strlen($data['first_name']) < 2) $errors[] = 'First name must be at least 2 characters.';
    if (strlen($data['last_name'])  < 2) $errors[] = 'Last name must be at least 2 characters.';
    if (strlen($data['username'])   < 4) $errors[] = 'Username must be at least 4 characters.';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) $errors[] = 'Username may only contain letters, numbers, and underscores.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($data['password']) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($data['password'] !== $data['confirm']) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        try {
            $auth = new Auth();
            $auth->register($data);
            header('Location: ' . BASE_URL . '/index.php?registered=1');
            exit;
        } catch (\RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-card" style="max-width:500px">
    <div class="auth-header">
        <i class="bi bi-heart-pulse-fill logo-icon"></i>
        <h1>Create Account</h1>
        <p>Join ClinicCare as a patient</p>
    </div>
    <div class="auth-body">

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Please fix the following:</strong>
            <ul class="mb-0 mt-1 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control"
                           value="<?= htmlspecialchars($data['first_name'] ?? '') ?>"
                           placeholder="Juan" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control"
                           value="<?= htmlspecialchars($data['last_name'] ?? '') ?>"
                           placeholder="Dela Cruz" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control"
                           value="<?= htmlspecialchars($data['username'] ?? '') ?>"
                           placeholder="juandc123" required autocomplete="username">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?= htmlspecialchars($data['phone'] ?? '') ?>"
                           placeholder="09171234567">
                </div>
                <div class="col-12">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                           placeholder="juan@email.com" required autocomplete="email">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Min. 8 characters" required autocomplete="new-password">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control"
                           placeholder="Repeat password" required>
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-person-check-fill me-2"></i>Create Account
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div class="auth-footer">
        Already have an account? <a href="<?= BASE_URL ?>/index.php" class="text-primary fw-semibold">Sign in</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
