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
    if (!$appt || (int) $appt['patient_id'] !== $uid) {
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

// Load medical record view if requested
$viewRecord = null;
if (isset($_GET['record'])) {
    $rid = (int) $_GET['record'];
    $rec = $apptModel->getById($rid);
    if ($rec && (int) $rec['patient_id'] === $uid) {
        $viewRecord = $rec;
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

<?php if ($viewRecord): ?>
<!-- ══════════════════════════════════════════════════════════════
     MEDICAL RECORD VIEW
═════════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- Medical Record Card -->
    <div class="col-lg-6">
        <div class="form-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-clipboard-pulse me-2 text-primary"></i>Medical Record
                </h5>
                <a href="<?= BASE_URL ?>/user/my_appointments.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <div class="mb-3 p-3 bg-light rounded">
                <div class="small text-muted">Appointment with</div>
                <div class="fw-semibold"><?= htmlspecialchars($viewRecord['doctor_name']) ?></div>
                <div class="small text-muted">
                    <?= htmlspecialchars($viewRecord['specialty']) ?> &nbsp;·&nbsp;
                    <?= date('M j, Y', strtotime($viewRecord['appointment_date'])) ?>
                    at <?= date('g:i A', strtotime($viewRecord['appointment_time'])) ?>
                </div>
                <div class="mt-1">
                    <span class="status-badge status-<?= $viewRecord['status'] ?>">
                        <?= ucfirst($viewRecord['status']) ?>
                    </span>
                </div>
            </div>

            <?php if ($viewRecord['medical_record']): $mr = $viewRecord['medical_record']; ?>

            <!-- ── PDF Download Banner ─────────────────────────────── -->
            <?php if (!empty($mr['pdf_path'])): ?>
            <div class="alert alert-primary d-flex align-items-center justify-content-between gap-2 mb-3 py-2">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-5 flex-shrink-0"></i>
                    <div class="overflow-hidden">
                        <div class="fw-semibold small">PDF Report Available</div>
                        <div class="text-muted text-truncate" style="max-width:180px;font-size:.78rem">
                            <?= htmlspecialchars($mr['pdf_original_name'] ?? 'medical_record.pdf') ?>
                        </div>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/serve_record.php?appt=<?= $viewRecord['id'] ?>"
                    class="btn btn-sm btn-primary flex-shrink-0" target="_blank">
                    <i class="bi bi-download me-1"></i>Download
                </a>
            </div>
            <?php endif; ?>

            <?php if ($mr['diagnosis']): ?>
            <div class="mb-3">
                <div class="small fw-semibold text-uppercase text-muted mb-1">
                    <i class="bi bi-search me-1"></i>Diagnosis
                </div>
                <div class="p-2 border rounded"><?= nl2br(htmlspecialchars($mr['diagnosis'])) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($mr['prescription']): ?>
            <div class="mb-3">
                <div class="small fw-semibold text-uppercase text-muted mb-1">
                    <i class="bi bi-capsule me-1"></i>Prescription
                </div>
                <div class="p-2 border rounded"><?= nl2br(htmlspecialchars($mr['prescription'])) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($mr['notes']): ?>
            <div class="mb-0">
                <div class="small fw-semibold text-uppercase text-muted mb-1">
                    <i class="bi bi-journal-text me-1"></i>Doctor's Notes
                </div>
                <div class="p-2 border rounded"><?= nl2br(htmlspecialchars($mr['notes'])) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!$mr['diagnosis'] && !$mr['prescription'] && !$mr['notes'] && empty($mr['pdf_path'])): ?>
            <p class="text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>Your medical record has been created but details haven't been
                filled in yet. Please check back later.
            </p>
            <?php endif; ?>

            <?php else: ?>
            <p class="text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>No medical record has been issued for this appointment yet.
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Follow-up Info Card -->
    <div class="col-lg-6">
        <div class="form-card h-100">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-arrow-repeat me-2 text-info"></i>Follow-up Appointment
            </h5>

            <?php if (!empty($viewRecord['follow_up'])): $fu = $viewRecord['follow_up']; ?>
            <div class="alert alert-info">
                <div class="fw-semibold mb-2">
                    <i class="bi bi-calendar-check me-1"></i>A follow-up has been scheduled for you
                </div>
                <div class="mb-1">
                    <i class="bi bi-calendar-event me-1 text-muted"></i>
                    <strong><?= date('M j, Y', strtotime($fu['appointment_date'])) ?></strong>
                    at <?= date('g:i A', strtotime($fu['appointment_time'])) ?>
                </div>
                <div class="mb-1">
                    <i class="bi bi-person-badge me-1 text-muted"></i>
                    <?= htmlspecialchars($fu['doctor_name']) ?>
                </div>
                <div class="mb-2">
                    <i class="bi bi-chat-text me-1 text-muted"></i>
                    <?= htmlspecialchars($fu['reason']) ?>
                </div>
                <span class="status-badge status-<?= $fu['status'] ?>"><?= ucfirst($fu['status']) ?></span>
            </div>
            <a href="<?= BASE_URL ?>/user/my_appointments.php?record=<?= $fu['id'] ?>"
                class="btn btn-sm btn-outline-info">
                <i class="bi bi-eye me-1"></i>View Follow-up Record
            </a>

            <?php elseif (!empty($viewRecord['follow_up_of'])): ?>
            <div class="alert alert-secondary mb-3">
                <div class="fw-semibold mb-1">
                    <i class="bi bi-link-45deg me-1"></i>This is a follow-up appointment
                </div>
                <?php if (!empty($viewRecord['parent'])): $p = $viewRecord['parent']; ?>
                <div class="small">
                    Original visit: <?= date('M j, Y', strtotime($p['appointment_date'])) ?>
                    with <?= htmlspecialchars($p['doctor_name']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($viewRecord['parent'])): ?>
            <a href="<?= BASE_URL ?>/user/my_appointments.php?record=<?= $viewRecord['parent']['id'] ?>"
                class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>View Original Visit Record
            </a>
            <?php endif; ?>

            <?php else: ?>
            <p class="text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>No follow-up appointment has been scheduled for this visit.
                If you need further consultation, please book a new appointment or contact the clinic.
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filter tabs -->
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="<?= BASE_URL ?>/user/my_appointments.php"
        class="btn btn-sm <?= !$filterStatus ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
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
            <a href="<?= BASE_URL ?>/user/book.php" class="btn btn-primary btn-sm mt-2">
                Book Your First Appointment
            </a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Specialty</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($a['doctor_name']) ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars($a['specialty']) ?></small></td>
                    <td><?= date('M j, Y', strtotime($a['appointment_date'])) ?></td>
                    <td><?= date('g:i A', strtotime($a['appointment_time'])) ?></td>
                    <td>
                        <span class="status-badge status-<?= $a['status'] ?>">
                            <?= ucfirst($a['status']) ?>
                        </span>
                    </td>
                    <td>
                        <small>
                            <?= htmlspecialchars(substr($a['reason'], 0, 40)) ?>
                            <?= strlen($a['reason']) > 40 ? '…' : '' ?>
                        </small>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <?php if ($a['status'] === 'completed'): ?>
                            <a href="<?= BASE_URL ?>/user/my_appointments.php?record=<?= $a['id'] ?>"
                                class="btn btn-sm btn-outline-info" title="View Medical Record">
                                <i class="bi bi-clipboard-pulse me-1"></i>Record
                            </a>
                            <?php endif; ?>
                            <?php if (in_array($a['status'], ['pending','confirmed'])): ?>
                            <form method="post" onsubmit="return confirm('Cancel this appointment?')">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($a['status'] === 'cancelled'): ?>
                            <span class="text-muted small">—</span>
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