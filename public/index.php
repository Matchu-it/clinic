<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (Auth::check()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

header('Location: ' . BASE_URL . '/loggin.php');
exit;
