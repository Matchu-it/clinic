<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireAdmin();

$apptModel   = new Appointment();
$doctorModel = new Doctor();
$userModel   = new User();
$message     = '';
$msgType     = 'success';
$viewAppt    = null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    try {
        if ($action === 'update_status') {
            $apptModel->updateStatus($id, $_POST['status']);
            $message = 'Appointment status updated.';

        } elseif ($action === 'save_record') {
            $apptModel->saveMedicalRecord($id, [
                'diagnosis'    => $_POST['diagnosis']    ?? '',
                'prescription' => $_POST['prescription'] ?? '',
                'notes'        => $_POST['notes']        ?? '',
            ]);
            $apptModel->updateStatus($id, 'completed');
            $message = 'Medical record saved and appointment marked completed.';

        } elseif ($action === 'delete') {
            $apptModel->delete($id);
            $message = 'Appointment deleted.';

        } elseif ($action === 'create') {
            $apptModel->create([
                'patient_id'       => (int) $_POST['patient_id'],
                'doctor_id'        => (int) $_POST['doctor_id'],
                'appointment_date' => $_POST['appointment_date'],
                'appointment_time' => $_POST['appointment_time'],
                'reason'           => trim($_POST['reason']),
                'notes'            => trim($_POST['notes'] ?? ''),
                'status'           => 'confirmed',
            ]);
            $message = 'Appointment created successfully.';
        }
    } catch (\Exception $e) {
        $message = $e->getMessage();
        $msgType = 'danger';
    }
}

if (isset($_GET['view'])) {
    $viewAppt = $apptModel->getById((int) $_GET['view']);
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$allAppts     = $apptModel->getAll();
$appointments = $filterStatus
    ? array_filter($allAppts, fn($a) => $a['status'] === $filterStatus)
    : $allAppts;

$doctors  = $doctorModel->getActive();
$patients = $userModel->getPatients();

$pageTitle = 'Manage Appointments';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h2><i class="bi bi-calendar-check me-2 text-primary"></i>Appointments</h2>
        <p>View and manage all clinic appointments</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>New Appointment
    </button>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> alert-auto-dismiss">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($viewAppt): ?>
<!-- Detail View -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="form-card">
            <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Appointment Details</h5>
            <dl class="row mb-0">
                <dt class="col-5 text-muted">Patient</dt>
                <dd class="col-7"><?= htmlspecialchars($viewAppt['patient_name']) ?></dd>
                <dt class="col-5 text-muted">Email</dt>
                <dd class="col-7"><?= htmlspecialchars($viewAppt['patient_email']) ?></dd>
                <dt class="col-5 text-muted">Phone</dt>
                <dd class="col-7"><?= htmlspecialchars($viewAppt['patient_phone'] ?? '—') ?></dd>
                <dt class="col-5 text-muted">Doctor</dt>
                <dd class="col-7"><?= htmlspecialchars($viewAppt['doctor_name']) ?></dd>
                <dt class="col-5 text-muted">Specialty</dt>
                <dd class="col-7"><?= htmlspecialchars($viewAppt['specialty']) ?></dd>
                <dt class="col-5 text-muted">Date &amp; Time</dt>
                <dd class="col-7"><?= date('M j, Y', strtotime($viewAppt['appointment_date'])) ?> at <?= date('g:i A', strtotime($viewAppt['appointment_time'])) ?></dd>
                <dt class="col-5 text-muted">Status</dt>
                <dd class="col-7"><span class="status-badge status-<?= $viewAppt['status'] ?>"><?= ucfirst($viewAppt['status']) ?></span></dd>
                <dt class="col-5 text-muted">Reason</dt>
                <dd class="col-7"><?= htmlspecialchars($viewAppt['reason']) ?></dd>
                <?php if ($viewAppt['notes']): ?>
                <dt class="col-5 text-muted">Notes</dt>
                <dd class="col-7"><?= htmlspecialchars($viewAppt['notes']) ?></dd>
                <?php endif; ?>
            </dl>

            <!-- Status change -->
            <form method="post" class="mt-3 d-flex gap-2 flex-wrap">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?= $viewAppt['id'] ?>">
                <select name="status" class="form-select form-select-sm" style="width:auto">
                    <?php foreach (['pending','confirmed','cancelled','completed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $viewAppt['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-outline-primary">Update Status</button>
                <a href="<?= BASE_URL ?>/admin/appointments.php" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-card">
            <h5 class="fw-bold mb-3"><i class="bi bi-clipboard-pulse me-2"></i>Medical Record</h5>
            <form method="post">
                <input type="hidden" name="action" value="save_record">
                <input type="hidden" name="id" value="<?= $viewAppt['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Diagnosis</label>
                    <textarea name="diagnosis" class="form-control" rows="2"><?= htmlspecialchars($viewAppt['medical_record']['diagnosis'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prescription</label>
                    <textarea name="prescription" class="form-control" rows="2"><?= htmlspecialchars($viewAppt['medical_record']['prescription'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Doctor's Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($viewAppt['medical_record']['notes'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-save me-1"></i>Save Medical Record
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filter tabs -->
<div class="mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/admin/appointments.php" class="btn btn-sm <?= !$filterStatus ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
        <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
        <a href="<?= BASE_URL ?>/admin/appointments.php?status=<?= $s ?>" class="btn btn-sm <?= $filterStatus === $s ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Appointments Table -->
<div class="table-card">
    <div class="table-card-header">
        <h5><i class="bi bi-list-ul me-2"></i>Appointments (<?= count($appointments) ?>)</h5>
    </div>
    <div class="table-responsive">
        <?php if (empty($appointments)): ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x d-block"></i>
            <h6>No appointments found</h6>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Date &amp; Time</th><th>Status</th><th>Reason</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td class="text-muted"><?= $a['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($a['patient_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($a['patient_email']) ?></small>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($a['doctor_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($a['specialty']) ?></small>
                    </td>
                    <td>
                        <div><?= date('M j, Y', strtotime($a['appointment_date'])) ?></div>
                        <small class="text-muted"><?= date('g:i A', strtotime($a['appointment_time'])) ?></small>
                    </td>
                    <td><span class="status-badge status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td><small><?= htmlspecialchars(substr($a['reason'], 0, 40)) ?><?= strlen($a['reason']) > 40 ? '...' : '' ?></small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/admin/appointments.php?view=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="post" onsubmit="return confirm('Delete this appointment?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Appointment Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>New Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Select patient...</option>
                            <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" class="form-select" required>
                            <option value="">Select doctor...</option>
                            <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>">Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?> — <?= htmlspecialchars($d['specialty']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="appointment_date" class="form-control"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Time</label>
                            <input type="time" name="appointment_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Visit</label>
                        <textarea name="reason" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
