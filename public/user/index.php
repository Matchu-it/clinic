<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$apptModel = new Appointment();
$uid       = Auth::id();
$myAppts   = $apptModel->getByPatient($uid);
$counts    = ['pending'=>0,'confirmed'=>0,'completed'=>0,'cancelled'=>0];
foreach ($myAppts as $a) {
    if (isset($counts[$a['status']])) $counts[$a['status']]++;
}
$recentAppts = array_slice($myAppts, 0, 5);

$pageTitle = 'My Dashboard';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <h2>Welcome back, <span class="text-primary"><?= htmlspecialchars($_SESSION['full_name']) ?></span></h2>
    <p>Here's a summary of your appointments.</p>
</div>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= count($myAppts) ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $counts['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $counts['confirmed'] ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-clipboard-check"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $counts['completed'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Quick actions -->
    <div class="col-lg-4">
        <div class="table-card">
            <div class="table-card-header">
                <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="p-4 d-grid gap-2">
                <a href="<?= BASE_URL ?>/user/book.php" class="btn btn-primary">
                    <i class="bi bi-calendar-plus me-2"></i>Book New Appointment
                </a>
                <a href="<?= BASE_URL ?>/user/my_appointments.php" class="btn btn-outline-primary">
                    <i class="bi bi-calendar-event me-2"></i>View All My Appointments
                </a>
                <a href="<?= BASE_URL ?>/user/xml_export.php" class="btn btn-outline-secondary">
                    <i class="bi bi-download me-2"></i>Export My Records (XML)
                </a>
            </div>
        </div>
    </div>

    <!-- Recent appointments -->
    <div class="col-lg-8">
        <div class="table-card">
            <div class="table-card-header">
                <h5><i class="bi bi-clock-history me-2"></i>Recent Appointments</h5>
                <a href="<?= BASE_URL ?>/user/my_appointments.php" class="btn btn-sm btn-outline-primary">See All</a>
            </div>
            <div class="table-responsive">
                <?php if (empty($recentAppts)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x d-block"></i>
                    <h6>No appointments yet</h6>
                    <p class="small">Book your first appointment to get started.</p>
                    <a href="<?= BASE_URL ?>/user/book.php" class="btn btn-primary btn-sm">Book Now</a>
                </div>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAppts as $a): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($a['doctor_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($a['specialty']) ?></small>
                            </td>
                            <td>
                                <div><?= date('M j, Y', strtotime($a['appointment_date'])) ?></div>
                                <small
                                    class="text-muted"><?= date('g:i A', strtotime($a['appointment_time'])) ?></small>
                            </td>
                            <td><span
                                    class="status-badge status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>