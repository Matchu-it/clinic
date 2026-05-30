<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireAdmin();

$appt  = new Appointment();
$user  = new User();
$doc   = new Doctor();

$counts      = $appt->countByStatus();
$totalPat    = $user->countByRole('patient');
$totalDocs   = $doc->countActive();
$totalAppts  = array_sum($counts);
$recentAppts = array_slice($appt->getAll(), 0, 8);

$pageTitle = 'Admin Dashboard';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</h2>
    <p>Overview of clinic operations — <?= date('l, F j, Y') ?></p>
</div>

<!-- Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalPat ?></div>
                <div class="stat-label">Registered Patients</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-person-badge"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalDocs ?></div>
                <div class="stat-label">Active Doctors</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalAppts ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $counts['pending'] ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
        </div>
    </div>
</div>

<!-- Appointment Status Breakdown + Recent -->
<div class="row g-4">
    <!-- Status Breakdown -->
    <div class="col-lg-4">
        <div class="table-card h-100">
            <div class="table-card-header">
                <h5><i class="bi bi-bar-chart me-2"></i>Appointment Status</h5>
            </div>
            <div class="p-4">
                <?php
                $statuses = [
                    ['key'=>'pending',   'label'=>'Pending',   'class'=>'warning', 'icon'=>'hourglass-split'],
                    ['key'=>'confirmed', 'label'=>'Confirmed', 'class'=>'success', 'icon'=>'check-circle'],
                    ['key'=>'completed', 'label'=>'Completed', 'class'=>'primary', 'icon'=>'clipboard-check'],
                    ['key'=>'cancelled', 'label'=>'Cancelled', 'class'=>'danger',  'icon'=>'x-circle'],
                ];
                foreach ($statuses as $s):
                    $count = $counts[$s['key']];
                    $pct   = $totalAppts > 0 ? round($count / $totalAppts * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold">
                            <i class="bi bi-<?= $s['icon'] ?> me-1 text-<?= $s['class'] ?>"></i>
                            <?= $s['label'] ?>
                        </span>
                        <span class="small text-muted"><?= $count ?></span>
                    </div>
                    <div class="progress" style="height:6px;border-radius:4px">
                        <div class="progress-bar bg-<?= $s['class'] ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Appointments -->
    <div class="col-lg-8">
        <div class="table-card">
            <div class="table-card-header">
                <h5><i class="bi bi-clock-history me-2"></i>Recent Appointments</h5>
                <a href="<?= BASE_URL ?>/admin/appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <?php if (empty($recentAppts)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x d-block"></i>
                    <h6>No appointments yet</h6>
                </div>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAppts as $a): ?>
                        <tr>
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
                            <td>
                                <span class="status-badge status-<?= $a['status'] ?>">
                                    <?= ucfirst($a['status']) ?>
                                </span>
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
