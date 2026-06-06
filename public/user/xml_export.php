<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();

$xmlHandler = new XmlHandler();
$uid        = Auth::id();
$xmlOutput  = '';

// Handle download
if (isset($_GET['download'])) {
    $xml = $xmlHandler->exportAppointments($uid);
    $filename = 'my_appointments_' . date('Ymd_His') . '.xml';
    header('Content-Type: application/xml; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo $xml;
    exit;
}

// Handle preview
if (isset($_GET['preview'])) {
    $xmlOutput = $xmlHandler->exportAppointments($uid);

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xmlOutput);
    $xmlOutput = $dom->saveXML();
}

$pageTitle = 'Export My Records';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-download me-2 text-primary"></i>Export My Records</h2>
    <p>Download your appointment history as an XML file.</p>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="form-card">
            <h5 class="fw-bold mb-2"><i class="bi bi-file-earmark-code me-2 text-success"></i>My Appointments (XML)</h5>
            <p class="text-muted small mb-4">
                Export all your appointment records into a structured XML file.
                This uses PHP's <code>DOMDocument</code> class to generate well-formed XML.
            </p>
            <div class="d-grid gap-2">
                <a href="<?= BASE_URL ?>/user/xml_export.php?download=1" class="btn btn-success">
                    <i class="bi bi-download me-2"></i>Download XML File
                </a>
                <a href="<?= BASE_URL ?>/user/xml_export.php?preview=1" class="btn btn-outline-secondary">
                    <i class="bi bi-eye me-2"></i>Preview XML
                </a>
            </div>

            <?php if ($xmlOutput): ?>
            <div class="mt-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-semibold small">XML Preview</span>
                    <a href="<?= BASE_URL ?>/user/xml_export.php" class="btn btn-sm btn-outline-secondary">Close</a>
                </div>
                <pre class="xml-preview"><?= htmlspecialchars($xmlOutput) ?></pre>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-card">
            <h6 class="fw-bold mb-3"><i class="bi bi-code-slash me-2"></i>XML Structure</h6>
            <p class="text-muted small">Your exported file will follow this structure:</p>
            <div class="xml-preview">&lt;appointments&gt;
                &lt;appointment&gt;
                &lt;id&gt;1&lt;/id&gt;
                &lt;patient_name&gt;Juan Dela Cruz&lt;/patient_name&gt;
                &lt;patient_email&gt;juan@email.com&lt;/patient_email&gt;
                &lt;doctor_name&gt;Dr. Maria Santos&lt;/doctor_name&gt;
                &lt;specialty&gt;General Medicine&lt;/specialty&gt;
                &lt;appointment_date&gt;2024-07-15&lt;/appointment_date&gt;
                &lt;appointment_time&gt;09:00:00&lt;/appointment_time&gt;
                &lt;status&gt;confirmed&lt;/status&gt;
                &lt;reason&gt;Annual checkup&lt;/reason&gt;
                &lt;notes&gt;&lt;/notes&gt;
                &lt;created_at&gt;2024-07-01 10:30:00&lt;/created_at&gt;
                &lt;/appointment&gt;
                &lt;/appointments&gt;</div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>