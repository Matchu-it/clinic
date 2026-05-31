<?php
/**
 * serve_record.php
 * Securely streams a medical record PDF to the authenticated user.
 * - Admins can download any record.
 * - Patients can only download their own records.
 */
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireLogin();

$apptId = (int) ($_GET['appt'] ?? 0);
if (!$apptId) {
    http_response_code(400);
    die('Invalid request.');
}

$apptModel = new Appointment();
$appt      = $apptModel->getById($apptId);

if (!$appt) {
    http_response_code(404);
    die('Appointment not found.');
}

// Access control
$isAdmin = Auth::role() === 'admin';
$isOwner = (int) $appt['patient_id'] === Auth::id();

if (!$isAdmin && !$isOwner) {
    http_response_code(403);
    die('Access denied.');
}

$mr = $appt['medical_record'] ?? null;
if (empty($mr['pdf_path'])) {
    http_response_code(404);
    die('No PDF record found for this appointment.');
}

$storageDir = dirname(__DIR__) . '/storage/medical_records/';
$filePath   = $storageDir . basename($mr['pdf_path']); // basename prevents path traversal

if (!file_exists($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    die('File not found on server.');
}

$originalName = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $mr['pdf_original_name'] ?? 'medical_record.pdf');
if (!str_ends_with(strtolower($originalName), '.pdf')) {
    $originalName .= '.pdf';
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $originalName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

readfile($filePath);
exit;
