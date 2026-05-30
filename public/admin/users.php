<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireAdmin();

$userModel = new User();
$message   = '';
$msgType   = 'success';
$editUser  = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $userModel->delete((int) $_POST['id']);
            $message = 'Patient deleted successfully.';

        } elseif ($action === 'update') {
            $userModel->update((int) $_POST['id'], [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name'  => trim($_POST['last_name']  ?? ''),
                'email'      => trim($_POST['email']      ?? ''),
                'phone'      => trim($_POST['phone']      ?? ''),
                'role'       => $_POST['role'] ?? 'patient',
                'password'   => $_POST['password'] ?? '',
            ]);
            $message = 'Patient updated successfully.';
        }
    } catch (\Exception $e) {
        $message = $e->getMessage();
        $msgType = 'danger';
    }
}

// Load edit target
if (isset($_GET['edit'])) {
    $editUser = $userModel->getById((int) $_GET['edit']);
}

$patients = $userModel->getAll();
$pageTitle = 'Manage Patients';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h2><i class="bi bi-people me-2 text-primary"></i>Patients</h2>
        <p>Manage registered patient accounts</p>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> alert-auto-dismiss">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($editUser): ?>
<!-- Edit Form -->
<div class="form-card mb-4">
    <h5 class="fw-bold mb-4"><i class="bi bi-pencil me-2"></i>Edit Patient</h5>
    <form method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control"
                       value="<?= htmlspecialchars($editUser['first_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control"
                       value="<?= htmlspecialchars($editUser['last_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($editUser['email']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control"
                       value="<?= htmlspecialchars($editUser['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="patient" <?= $editUser['role'] === 'patient' ? 'selected' : '' ?>>Patient</option>
                    <option value="admin"   <?= $editUser['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                <input type="password" name="password" class="form-control" placeholder="New password">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Save Changes
                </button>
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="table-card">
    <div class="table-card-header">
        <h5><i class="bi bi-list-ul me-2"></i>All Users (<?= count($patients) ?>)</h5>
    </div>
    <div class="table-responsive">
        <?php if (empty($patients)): ?>
        <div class="empty-state">
            <i class="bi bi-people d-block"></i>
            <h6>No users registered yet</h6>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $u): ?>
                <tr>
                    <td class="text-muted"><?= $u['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar" style="width:30px;height:30px;font-size:.75rem">
                                <?= strtoupper(substr($u['first_name'], 0, 1)) ?>
                            </div>
                            <span class="fw-semibold"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></span>
                        </div>
                    </td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td>
                        <span class="status-badge status-<?= $u['role'] === 'admin' ? 'confirmed' : 'active' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td><small class="text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/admin/users.php?edit=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($u['id'] !== Auth::id()): ?>
                            <form method="post" onsubmit="return confirm('Delete this user? This will also remove all their appointments.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-icon">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
