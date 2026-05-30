<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireAdmin();

$doctorModel = new Doctor();
$message = '';
$msgType = 'success';
$editDoc = null;
$mode    = 'list'; // list | add | edit

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $doctorModel->create($_POST);
            $message = 'Doctor added successfully.';

        } elseif ($action === 'update') {
            $id = (int) $_POST['id'];
            $doctorModel->update($id, $_POST);

            // Handle schedule
            $schedules = [];
            if (!empty($_POST['days'])) {
                foreach ($_POST['days'] as $i => $day) {
                    $schedules[] = [
                        'day_of_week' => $day,
                        'start_time'  => $_POST['start_times'][$i] ?? '08:00',
                        'end_time'    => $_POST['end_times'][$i]   ?? '17:00',
                    ];
                }
            }
            $doctorModel->updateSchedule($id, $schedules);
            $message = 'Doctor updated successfully.';

        } elseif ($action === 'delete') {
            $doctorModel->delete((int) $_POST['id']);
            $message = 'Doctor deleted successfully.';
        }
    } catch (\Exception $e) {
        $message = $e->getMessage();
        $msgType = 'danger';
    }
}

if (isset($_GET['edit'])) {
    $editDoc = $doctorModel->getById((int) $_GET['edit']);
    $mode = 'edit';
} elseif (isset($_GET['add'])) {
    $mode = 'add';
}

$doctors = $doctorModel->getAll();
$days    = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$pageTitle = 'Manage Doctors';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h2><i class="bi bi-person-badge me-2 text-primary"></i>Doctors</h2>
        <p>Manage clinic doctors and their schedules</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/doctors.php?add=1" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Add Doctor
    </a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> alert-auto-dismiss">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($mode === 'add' || $mode === 'edit'): ?>
<div class="form-card mb-4">
    <h5 class="fw-bold mb-4">
        <i class="bi bi-<?= $mode === 'add' ? 'plus-circle' : 'pencil' ?> me-2"></i>
        <?= $mode === 'add' ? 'Add New Doctor' : 'Edit Doctor' ?>
    </h5>
    <form method="post">
        <input type="hidden" name="action" value="<?= $mode === 'add' ? 'create' : 'update' ?>">
        <?php if ($mode === 'edit'): ?>
        <input type="hidden" name="id" value="<?= $editDoc['id'] ?>">
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control"
                       value="<?= htmlspecialchars($editDoc['first_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control"
                       value="<?= htmlspecialchars($editDoc['last_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Specialty</label>
                <input type="text" name="specialty" class="form-control"
                       value="<?= htmlspecialchars($editDoc['specialty'] ?? '') ?>"
                       placeholder="e.g. Cardiology" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control"
                       value="<?= htmlspecialchars($editDoc['phone'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($editDoc['email'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active"   <?= ($editDoc['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($editDoc['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Bio / Notes</label>
                <textarea name="bio" class="form-control" rows="2"><?= htmlspecialchars($editDoc['bio'] ?? '') ?></textarea>
            </div>
        </div>

        <?php if ($mode === 'edit'): ?>
        <!-- Schedule Editor -->
        <h6 class="fw-bold mb-3"><i class="bi bi-calendar-week me-2"></i>Weekly Schedule</h6>
        <div id="scheduleRows">
            <?php if (!empty($editDoc['schedules'])): ?>
            <?php foreach ($editDoc['schedules'] as $sched): ?>
            <div class="row g-2 mb-2 schedule-row">
                <div class="col-md-4">
                    <select name="days[]" class="form-select form-select-sm">
                        <?php foreach ($days as $d): ?>
                        <option value="<?= $d ?>" <?= $sched['day_of_week'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="time" name="start_times[]" class="form-control form-control-sm"
                           value="<?= $sched['start_time'] ?>">
                </div>
                <div class="col-md-3">
                    <input type="time" name="end_times[]" class="form-control form-control-sm"
                           value="<?= $sched['end_time'] ?>">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.schedule-row').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="addSchedule">
            <i class="bi bi-plus me-1"></i>Add Schedule Row
        </button>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Save
            </button>
            <a href="<?= BASE_URL ?>/admin/doctors.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
const days = <?= json_encode($days) ?>;
document.getElementById('addSchedule')?.addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 schedule-row';
    row.innerHTML = `
        <div class="col-md-4"><select name="days[]" class="form-select form-select-sm">
            ${days.map(d => `<option>${d}</option>`).join('')}
        </select></div>
        <div class="col-md-3"><input type="time" name="start_times[]" class="form-control form-control-sm" value="08:00"></div>
        <div class="col-md-3"><input type="time" name="end_times[]" class="form-control form-control-sm" value="17:00"></div>
        <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.schedule-row').remove()"><i class="bi bi-trash"></i></button></div>`;
    document.getElementById('scheduleRows').appendChild(row);
});
</script>
<?php endif; ?>

<!-- Doctors Table -->
<div class="table-card">
    <div class="table-card-header">
        <h5><i class="bi bi-list-ul me-2"></i>All Doctors (<?= count($doctors) ?>)</h5>
    </div>
    <div class="table-responsive">
        <?php if (empty($doctors)): ?>
        <div class="empty-state">
            <i class="bi bi-person-badge d-block"></i>
            <h6>No doctors added yet</h6>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>#</th><th>Name</th><th>Specialty</th><th>Phone</th><th>Email</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $d): ?>
                <tr>
                    <td class="text-muted"><?= $d['id'] ?></td>
                    <td class="fw-semibold">Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></td>
                    <td><?= htmlspecialchars($d['specialty']) ?></td>
                    <td><?= htmlspecialchars($d['phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($d['email'] ?? '—') ?></td>
                    <td>
                        <span class="status-badge status-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/admin/doctors.php?edit=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" onsubmit="return confirm('Delete Dr. <?= addslashes($d['first_name']) ?> <?= addslashes($d['last_name']) ?>?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
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

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
