<?php
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireLogin();

if (Auth::role() === 'admin') {
    header('Location: ' . BASE_URL . '/admin/dashboardAdmin.php');
} else {
    header('Location: ' . BASE_URL . '/user/index.php');
}
exit;
