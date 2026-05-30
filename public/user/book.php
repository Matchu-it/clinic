<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$doctorModel = new Doctor();
$apptModel   = new Appointment();
$doctors     = $doctorModel->getActive();
$message     = '';
$msgType     = 'success';
$formData    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'doctor_id'        => (int) ($_POST['doctor_id'] ?? 0),
        'appointment_date' => trim($_POST['appointment_date'] ?? ''),
        'appointment_time' => trim($_POST['appointment_time'] ?? ''),
        'reason'           => trim($_POST['reason'] ?? ''),
        'notes'            => trim($_POST['notes'] ?? ''),
    ];

    $errors = [];
    if (!$formData['doctor_id'])        $errors[] = 'Please select a doctor.';
    if (!$formData['appointment_date']) $errors[] = 'Please choose a date.';
    if (!$formData['appointment_time']) $errors[] = 'Please choose a time.';
    if (strlen($formData['reason']) < 5) $errors[] = 'Please describe your reason for the visit (min. 5 characters).';
    if ($formData['appointment_date'] < date('Y-m-d')) $errors[] = 'Appointment date cannot be in the past.';

    if (empty($errors)) {
        try {
            $apptModel->create([
                'patient_id'       => Auth::id(),
                'doctor_id'        => $formData['doctor_id'],
                'appointment_date' => $formData['appointment_date'],
                'appointment_time' => $formData['appointment_time'],
                'reason'           => $formData['reason'],
                'notes'            => $formData['notes'],
                'status'           => 'pending',
            ]);
            $message  = 'Your appointment request has been submitted! We will confirm it shortly.';
            $formData = [];
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $msgType = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $msgType = 'danger';
    }
}

// Build doctor-schedule map for JS
$scheduleMap = [];
foreach ($doctors as $d) {
    $full = $doctorModel->getById($d['id']);
    $scheduleMap[$d['id']] = $full['schedules'] ?? [];
}

$pageTitle = 'Book Appointment';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-calendar-plus me-2 text-primary"></i>Book an Appointment</h2>
    <p>Fill in the form below to request a clinic appointment.</p>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> <?= $msgType === 'success' ? 'alert-auto-dismiss' : '' ?>">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
    <?= $message ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="form-card">
            <h5 class="fw-bold mb-4"><i class="bi bi-pencil-square me-2"></i>Appointment Details</h5>
            <form method="post" novalidate>
                <div class="mb-3">
                    <label class="form-label">Select Doctor <span class="text-danger">*</span></label>
                    <select name="doctor_id" id="doctor_id" class="form-select" required onchange="showSchedule(this.value)">
                        <option value="">Choose a doctor...</option>
                        <?php foreach ($doctors as $d): ?>
                        <option value="<?= $d['id'] ?>"
                                <?= ($formData['doctor_id'] ?? 0) == $d['id'] ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>
                            — <?= htmlspecialchars($d['specialty']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Doctor schedule hint -->
                <div id="scheduleHint" class="alert alert-info d-none mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Available days:</strong> <span id="scheduleText"></span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
                        <input type="date" name="appointment_date" class="form-control"
                               value="<?= htmlspecialchars($formData['appointment_date'] ?? '') ?>"
                               min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Preferred Time <span class="text-danger">*</span></label>
                        <input type="time" name="appointment_time" class="form-control"
                               value="<?= htmlspecialchars($formData['appointment_time'] ?? '') ?>"
                               min="07:00" max="19:00" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reason for Visit <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3"
                              placeholder="Describe your symptoms or reason for visit..."
                              required><?= htmlspecialchars($formData['reason'] ?? '') ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Additional Notes <small class="text-muted">(optional)</small></label>
                    <textarea name="notes" class="form-control" rows="2"
                              placeholder="Any other information the doctor should know..."><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary px-5">
                    <i class="bi bi-send me-2"></i>Submit Request
                </button>
            </form>
        </div>
    </div>

    <!-- Info panel -->
    <div class="col-lg-5">
        <div class="form-card mb-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>How It Works</h6>
            <ol class="ps-3 small text-muted">
                <li class="mb-2">Select your preferred doctor and specialty.</li>
                <li class="mb-2">Choose a date and time that works for you.</li>
                <li class="mb-2">Submit your request — status will be <strong>Pending</strong> initially.</li>
                <li class="mb-2">Our staff will <strong>confirm</strong> your appointment.</li>
                <li>Visit the clinic on your scheduled date.</li>
            </ol>
        </div>

        <!-- Doctor cards -->
        <div class="table-card">
            <div class="table-card-header"><h5><i class="bi bi-person-badge me-2"></i>Our Doctors</h5></div>
            <div style="max-height:340px;overflow-y:auto">
                <?php foreach ($doctors as $d): ?>
                <div class="p-3 border-bottom">
                    <div class="fw-semibold">Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></div>
                    <div class="small text-primary"><?= htmlspecialchars($d['specialty']) ?></div>
                    <?php if ($d['bio']): ?>
                    <div class="small text-muted mt-1"><?= htmlspecialchars(substr($d['bio'], 0, 80)) ?>...</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
const scheduleMap = <?= json_encode($scheduleMap) ?>;

function showSchedule(doctorId) {
    const hint = document.getElementById('scheduleHint');
    const txt  = document.getElementById('scheduleText');
    if (!doctorId || !scheduleMap[doctorId] || !scheduleMap[doctorId].length) {
        hint.classList.add('d-none');
        return;
    }
    const days = scheduleMap[doctorId].map(s => `${s.day_of_week} (${s.start_time}–${s.end_time})`);
    txt.textContent = days.join(', ');
    hint.classList.remove('d-none');
}
// Show on page load if doctor pre-selected
const sel = document.getElementById('doctor_id');
if (sel.value) showSchedule(sel.value);
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
