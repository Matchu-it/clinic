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
        if ($action === 'confirm') {
            $apptModel->updateStatus($id, 'confirmed');
            $message = 'Appointment confirmed.';

        } elseif ($action === 'update_status') {
            $apptModel->updateStatus($id, $_POST['status']);
            $message = 'Appointment status updated.';

        } elseif ($action === 'reschedule') {
            $newDate = trim($_POST['new_date'] ?? '');
            $newTime = trim($_POST['new_time'] ?? '');
            if (!$newDate || !$newTime) {
                throw new \InvalidArgumentException('Please provide both a new date and time.');
            }
            if ($newDate < date('Y-m-d')) {
                throw new \InvalidArgumentException('Rescheduled date cannot be in the past.');
            }
            $apptModel->reschedule($id, $newDate, $newTime);
            $message = 'Appointment rescheduled and confirmed.';

        } elseif ($action === 'save_record') {
            $apptModel->saveMedicalRecord($id, [
                'diagnosis'    => $_POST['diagnosis']    ?? '',
                'prescription' => $_POST['prescription'] ?? '',
                'notes'        => $_POST['notes']        ?? '',
            ]);
            $apptModel->updateStatus($id, 'completed');
            $message = 'Medical record saved and appointment marked completed.';

        } elseif ($action === 'create_followup') {
            $fuDoctorId = (int) ($_POST['fu_doctor_id'] ?? 0);
            $fuDate     = trim($_POST['fu_date'] ?? '');
            $fuTime     = trim($_POST['fu_time'] ?? '');
            $fuReason   = trim($_POST['fu_reason'] ?? '');
            if (!$fuDoctorId || !$fuDate || !$fuTime) {
                throw new \InvalidArgumentException('Doctor, date, and time are required for a follow-up.');
            }
            $followUpId = $apptModel->createFollowUp($id, [
                'doctor_id'        => $fuDoctorId,
                'appointment_date' => $fuDate,
                'appointment_time' => $fuTime,
                'reason'           => $fuReason ?: 'Follow-up appointment',
                'notes'            => trim($_POST['fu_notes'] ?? ''),
            ]);
            $message = 'Follow-up appointment scheduled (Appointment #' . $followUpId . ').';

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
} elseif (!empty($_POST['view'])) {
    $viewAppt = $apptModel->getById((int) $_POST['view']);
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

<?php if ($viewAppt):
    $nowTs     = time();
    $apptTs    = strtotime($viewAppt['appointment_date'] . ' ' . $viewAppt['appointment_time']);
    $isPastDue = $apptTs < $nowTs;
    $canAct    = in_array($viewAppt['status'], ['pending', 'confirmed']);
?>
<!-- ===== DETAIL VIEW ===== -->

<!-- Row 1: Details + Medical Record -->
<div class="row g-4 mb-4">

    <!-- Card 1: Appointment Details + Actions -->
    <div class="col-lg-6">
        <div class="form-card h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Appointment Details</h5>

            <?php if (!empty($viewAppt['follow_up_of']) && !empty($viewAppt['parent'])): ?>
            <div class="alert alert-secondary py-2 mb-3">
                <i class="bi bi-link me-1"></i>This is a follow-up of
                <a href="<?= BASE_URL ?>/admin/appointments.php?view=<?= $viewAppt['parent']['id'] ?>">
                    Appointment #<?= $viewAppt['parent']['id'] ?>
                </a>
                (<?= date('M j, Y', strtotime($viewAppt['parent']['appointment_date'])) ?>)
            </div>
            <?php endif; ?>

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
                <dd class="col-7">
                    <?= date('M j, Y', strtotime($viewAppt['appointment_date'])) ?>
                    at <?= date('g:i A', strtotime($viewAppt['appointment_time'])) ?>
                    <?php if ($isPastDue && $canAct): ?>
                    <span class="badge bg-danger ms-1">Past Due</span>
                    <?php endif; ?>
                </dd>
                <dt class="col-5 text-muted">Status</dt>
                <dd class="col-7"><span
                        class="status-badge status-<?= $viewAppt['status'] ?>"><?= ucfirst($viewAppt['status']) ?></span>
                </dd>
                <dt class="col-5 text-muted">Reason</dt>
                <dd class="col-7"><?= htmlspecialchars($viewAppt['reason']) ?></dd>
                <?php if ($viewAppt['notes']): ?>
                <dt class="col-5 text-muted">Notes</dt>
                <dd class="col-7"><?= htmlspecialchars($viewAppt['notes']) ?></dd>
                <?php endif; ?>
            </dl>

            <hr class="my-3">

            <?php if ($viewAppt['status'] === 'pending' && !$isPastDue): ?>
            <!-- Pending + not yet due: offer quick Confirm -->
            <form method="post" class="mb-3">
                <input type="hidden" name="action" value="confirm">
                <input type="hidden" name="id" value="<?= $viewAppt['id'] ?>">
                <input type="hidden" name="view" value="<?= $viewAppt['id'] ?>">
                <button class="btn btn-success w-100">
                    <i class="bi bi-check-circle me-2"></i>Confirm Appointment
                </button>
            </form>
            <?php endif; ?>

            <?php if ($isPastDue && $canAct): ?>
            <!-- Past due: Complete or Reschedule -->
            <div class="alert alert-warning mb-3">
                <div class="fw-semibold mb-2">
                    <i class="bi bi-clock-history me-1"></i>This appointment's scheduled time has passed
                </div>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" value="<?= $viewAppt['id'] ?>">
                        <input type="hidden" name="status" value="completed">
                        <input type="hidden" name="view" value="<?= $viewAppt['id'] ?>">
                        <button class="btn btn-primary btn-sm">
                            <i class="bi bi-clipboard-check me-1"></i>Mark as Completed
                        </button>
                    </form>
                </div>
                <div class="border-top pt-2">
                    <div class="small fw-semibold mb-2">Or reschedule to a new date:</div>
                    <form method="post" class="d-flex gap-2 flex-wrap align-items-end">
                        <input type="hidden" name="action" value="reschedule">
                        <input type="hidden" name="id" value="<?= $viewAppt['id'] ?>">
                        <input type="hidden" name="view" value="<?= $viewAppt['id'] ?>">
                        <div>
                            <label class="form-label small mb-1">New Date</label>
                            <input type="date" name="new_date" class="form-control form-control-sm"
                                min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label class="form-label small mb-1">New Time</label>
                            <input type="time" name="new_time" class="form-control form-control-sm" required>
                        </div>
                        <button class="btn btn-warning btn-sm">
                            <i class="bi bi-calendar-event me-1"></i>Reschedule
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Generic status change -->
            <form method="post" class="d-flex gap-2 flex-wrap align-items-center">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?= $viewAppt['id'] ?>">
                <input type="hidden" name="view" value="<?= $viewAppt['id'] ?>">
                <select name="status" class="form-select form-select-sm" style="width:auto">
                    <?php foreach (['pending','confirmed','cancelled','completed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $viewAppt['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-outline-primary">Update Status</button>
                <a href="<?= BASE_URL ?>/admin/appointments.php" class="btn btn-sm btn-outline-secondary">Back to
                    List</a>
            </form>
        </div>
    </div>

    <!-- Card 2: Medical Record -->
    <div class="col-lg-6">
        <div class="form-card h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-clipboard-pulse me-2"></i>Medical Record</h5>
            <p class="text-muted small">Fill in after the patient's visit. Saving will mark the appointment as
                completed.</p>
            <form method="post">
                <input type="hidden" name="action" value="save_record">
                <input type="hidden" name="id" value="<?= $viewAppt['id'] ?>">
                <input type="hidden" name="view" value="<?= $viewAppt['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Diagnosis</label>
                    <textarea name="diagnosis" class="form-control"
                        rows="2"><?= htmlspecialchars($viewAppt['medical_record']['diagnosis'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prescription</label>
                    <textarea name="prescription" class="form-control"
                        rows="2"><?= htmlspecialchars($viewAppt['medical_record']['prescription'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Doctor's Notes</label>
                    <textarea name="notes" class="form-control"
                        rows="2"><?= htmlspecialchars($viewAppt['medical_record']['notes'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-save me-1"></i>Save Medical Record &amp; Complete
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Row 2: Follow-up Appointment -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="form-card">
            <h5 class="fw-bold mb-3"><i class="bi bi-arrow-repeat me-2 text-info"></i>Follow-up Appointment</h5>

            <?php if (!empty($viewAppt['follow_up'])): ?>
            <!-- A follow-up already exists for this appointment -->
            <div class="alert alert-success mb-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold"><i class="bi bi-check-circle me-1"></i>Follow-up already scheduled
                        </div>
                        <div class="mt-1 small">
                            <i class="bi bi-calendar me-1"></i>
                            <?= date('M j, Y', strtotime($viewAppt['follow_up']['appointment_date'])) ?>
                            at <?= date('g:i A', strtotime($viewAppt['follow_up']['appointment_time'])) ?>
                            &nbsp;—&nbsp;
                            <?= htmlspecialchars($viewAppt['follow_up']['doctor_name']) ?>
                        </div>
                        <div class="mt-1">
                            <span class="status-badge status-<?= $viewAppt['follow_up']['status'] ?>">
                                <?= ucfirst($viewAppt['follow_up']['status']) ?>
                            </span>
                            <small
                                class="text-muted ms-2"><?= htmlspecialchars($viewAppt['follow_up']['reason']) ?></small>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/appointments.php?view=<?= $viewAppt['follow_up']['id'] ?>"
                        class="btn btn-sm btn-outline-success">
                        <i class="bi bi-eye me-1"></i>View Follow-up
                    </a>
                </div>
            </div>

            <?php elseif ($viewAppt['status'] === 'completed'): ?>
            <!-- Completed, no follow-up yet — show scheduling form -->
            <p class="text-muted small mb-3">Schedule a follow-up visit for the patient if further consultation is
                needed.</p>
            <form method="post">
                <input type="hidden" name="action" value="create_followup">
                <input type="hidden" name="id" value="<?= $viewAppt['id'] ?>">
                <input type="hidden" name="view" value="<?= $viewAppt['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Doctor <span class="text-danger">*</span></label>
                        <select name="fu_doctor_id" class="form-select" required>
                            <option value="">Select doctor...</option>
                            <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>"
                                <?= $d['id'] == ($viewAppt['doctor_id'] ?? 0) ? 'selected' : '' ?>>
                                Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>
                                — <?= htmlspecialchars($d['specialty']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="fu_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Time <span class="text-danger">*</span></label>
                        <input type="time" name="fu_time" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="fu_reason" class="form-control"
                            placeholder="e.g. Check recovery progress">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes <small class="text-muted">(optional)</small></label>
                        <textarea name="fu_notes" class="form-control" rows="1"
                            placeholder="Additional instructions for the follow-up..."></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-info text-white">
                            <i class="bi bi-calendar-plus me-1"></i>Schedule Follow-up Appointment
                        </button>
                    </div>
                </div>
            </form>

            <?php else: ?>
            <!-- Not yet completed -->
            <p class="text-muted mb-0">
                <i class="bi bi-lock me-1"></i>
                Complete the appointment and save a medical record first before scheduling a follow-up.
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Filter tabs -->
<div class="mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/admin/appointments.php"
            class="btn btn-sm <?= !$filterStatus ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
        <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
        <a href="<?= BASE_URL ?>/admin/appointments.php?status=<?= $s ?>"
            class="btn btn-sm <?= $filterStatus === $s ? 'btn-primary' : 'btn-outline-secondary' ?>">
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
                <tr>
                    <th>#</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a):
                    $aTs       = strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
                    $aPastDue  = $aTs < time() && in_array($a['status'], ['pending','confirmed']);
                ?>
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
                        <div>
                            <?= date('M j, Y', strtotime($a['appointment_date'])) ?>
                            <?php if ($aPastDue): ?>
                            <span class="badge bg-danger ms-1" style="font-size:.65rem">Past Due</span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted"><?= date('g:i A', strtotime($a['appointment_time'])) ?></small>
                    </td>
                    <td><span class="status-badge status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td><small><?= htmlspecialchars(substr($a['reason'], 0, 40)) ?><?= strlen($a['reason']) > 40 ? '...' : '' ?></small>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="<?= BASE_URL ?>/admin/appointments.php?view=<?= $a['id'] ?>"
                                class="btn btn-sm btn-outline-primary btn-icon" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if ($a['status'] === 'pending'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button class="btn btn-sm btn-outline-success btn-icon" title="Confirm">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="post" onsubmit="return confirm('Delete this appointment?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger btn-icon" title="Delete"><i
                                        class="bi bi-trash"></i></button>
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
                            <option value="<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" class="form-select" required>
                            <option value="">Select doctor...</option>
                            <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>">Dr.
                                <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?> —
                                <?= htmlspecialchars($d['specialty']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>"
                                required>
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