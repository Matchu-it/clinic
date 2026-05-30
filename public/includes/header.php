<?php
// $pageTitle should be set before including this file
$pageTitle = $pageTitle ?? 'ClinicCare';
$isLoggedIn = Auth::check();
$userRole   = Auth::role();
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — ClinicCare</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>

<body>

    <?php if ($isLoggedIn): ?>
    <!-- Sidebar Layout -->
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="bi bi-heart-pulse-fill"></i>
                    <span>ClinicCare</span>
                </div>
            </div>

            <div class="sidebar-user">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                    <div class="user-role badge bg-<?= $userRole === 'admin' ? 'danger' : 'primary' ?>">
                        <?= ucfirst($userRole) ?></div>
                </div>
            </div>

            <ul class="sidebar-nav">
                <?php if ($userRole === 'admin'): ?>
                <li class="nav-section">Administration</li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/dashboardAdmin.php"
                        class="<?= str_contains($currentUri, '/admin/dashboardAdmin') || $currentUri === '/admin/' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/users.php"
                        class="<?= str_contains($currentUri, '/admin/users') ? 'active' : '' ?>">
                        <i class="bi bi-people"></i> Patients
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/doctors.php"
                        class="<?= str_contains($currentUri, '/admin/doctors') ? 'active' : '' ?>">
                        <i class="bi bi-person-badge"></i> Doctors
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/appointments.php"
                        class="<?= str_contains($currentUri, '/admin/appointments') ? 'active' : '' ?>">
                        <i class="bi bi-calendar-check"></i> Appointments
                    </a>
                </li>
                <li class="nav-section">Data Tools</li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/xml_tools.php"
                        class="<?= str_contains($currentUri, '/admin/xml') ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-code"></i> XML Tools
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-section">My Account</li>
                <li>
                    <a href="<?= BASE_URL ?>/user/index.php"
                        class="<?= str_contains($currentUri, '/user/index') || $currentUri === '/user/' ? 'active' : '' ?>">
                        <i class="bi bi-house"></i> My Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/user/book.php"
                        class="<?= str_contains($currentUri, '/user/book') ? 'active' : '' ?>">
                        <i class="bi bi-calendar-plus"></i> Book Appointment
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/user/my_appointments.php"
                        class="<?= str_contains($currentUri, '/user/my_appointments') ? 'active' : '' ?>">
                        <i class="bi bi-calendar-event"></i> My Appointments
                    </a>
                </li>
                <li class="nav-section">Data Tools</li>
                <li>
                    <a href="<?= BASE_URL ?>/user/xml_export.php"
                        class="<?= str_contains($currentUri, '/user/xml_export') ? 'active' : '' ?>">
                        <i class="bi bi-download"></i> Export My Data
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="sidebar-footer">
                <a href="<?= BASE_URL ?>/logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-left"></i> Log Out
                </a>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="content-wrapper">
            <!-- Top bar -->
            <header class="topbar">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <nav aria-label="breadcrumb" class="ms-3 d-none d-md-flex align-items-center">
                    <span class="topbar-title"><?= htmlspecialchars($pageTitle) ?></span>
                </nav>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="d-none d-sm-inline text-muted small">
                        <i class="bi bi-clock me-1"></i><?= date('l, F j, Y') ?>
                    </span>
                </div>
            </header>

            <!-- Main content area -->
            <main class="main-content">

                <?php else: ?>
                <!-- Guest layout (login/register) -->
                <div class="auth-wrapper">
                    <?php endif; ?>