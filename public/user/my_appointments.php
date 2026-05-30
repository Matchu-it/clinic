<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$apptModel = new Appointment();
$uid       = Auth::id();
$message   = '';
$msgType   = 'success';

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    // Verify ownership
    $appt = $apptModel->getById($id);
    if (!$appt || $appt['patient_id'] !== $uid) {
        $message = 'Unauthorized action.';
        $msgType = 'danger';
    } elseif ($action === 'cancel') {
        if (!in_array($appt['status'], ['pending','confirmed'])) {
            $message = 'This appointment cannot be cancelled.';
            $msgType = 'warning';
        } else {
            $apptModel->updateStatus($id, 'cancelled');
            $message = 'Appointment cancelled successfully.';
        }
    }
}

$filterStatus = $_GET['status'] ?? '';
$allAppts     = $apptModel->getByPatient($uid);
$appointments = $filterStatus
    ? array_filter($allAppts, fn($a) => $a['status'] === $filterStatus)
    : $allAppts;

$pageTitle = 'My Appointments';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h2><i class="bi bi-calendar-event me-2 text-primary"></i>My Appointments</h2>
        <p>Track and manage your appointment history</p>
    </div>
    <a href="<?= BASE_URL ?>/user/book.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Book New
    </a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> alert-auto-dismiss">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<!-- Filter tabs -->
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="<?= BASE_URL ?>/user/my_appointments.php" class="btn btn-sm <?= !$filterStatus ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
    <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
    <a href="<?= BASE_URL ?>/user/my_appointments.php?status=<?= $s ?>"
       class="btn btn-sm <?= $filterStatus === $s ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="table-card">
    <div class="table-card-header">
        <h5><i class="bi bi-list-ul me-2"></i>Appointments (<?= count($appointments) ?>)</h5>
        <a href="<?= BASE_URL ?>/user/xml_export.php" class="btn btn-sm btn-outline-success">
            <i class="bi bi-download me-1"></i>Export XML
        </a>
    </div>
    <div class="table-responsive">
        <?php if (empty($appointments)): ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x d-block"></i>
            <h6>No appointments found</h6>
            <a href="<?= BASE_URL ?>/user/book.php" class="btn btn-primary btn-sm mt-2">Book Your First Appointment</a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>Doctor</th><th>Specialty</th><th>Date</th><th>Time</th><th>Status</th><th>Reason</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($a['doctor_name']) ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars($a['specialty']) ?></small></td>
                    <td><?= date('M j, Y', strtotime($a['appointment_date'])) ?></td>
                    <td><?= date('g:i A', strtotime($a['appointment_time'])) ?></td>
                    <td><span class="status-badge status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td><small><?= htmlspecialchars(substr($a['reason'], 0, 40)) ?><?= strlen($a['reason']) > 40 ? '...' : '' ?></small></td>
                    <td>
                        <?php if (in_array($a['status'], ['pending','confirmed'])): ?>
                        <form method="post" onsubmit="return confirm('Cancel this appointment?')">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
