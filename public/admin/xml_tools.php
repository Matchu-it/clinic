<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireAdmin();

$xmlHandler = new XmlHandler();
$message    = '';
$msgType    = 'success';
$xmlOutput  = '';
$importResult = null;

// Handle exports
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $filename = "clinic_{$type}_" . date('Ymd_His') . '.xml';

    $xml = match($type) {
        'patients'     => $xmlHandler->exportPatients(),
        'doctors'      => $xmlHandler->exportDoctors(),
        'appointments' => $xmlHandler->exportAppointments(),
        default        => null,
    };

    if ($xml) {
        header('Content-Type: application/xml; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        echo $xml;
        exit;
    }
}

// Handle preview
if (isset($_GET['preview'])) {
    $type = $_GET['preview'];
    $xmlOutput = match($type) {
        'patients'     => $xmlHandler->exportPatients(),
        'doctors'      => $xmlHandler->exportDoctors(),
        'appointments' => $xmlHandler->exportAppointments(),
        default        => '',
    };
}

// Handle import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml_file'])) {
    $type = $_POST['import_type'] ?? 'appointments';

    if ($_FILES['xml_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'File upload error. Please try again.';
        $msgType = 'danger';
    } else {
        $xmlContent = file_get_contents($_FILES['xml_file']['tmp_name']);
        $importResult = match($type) {
            'appointments' => $xmlHandler->importAppointments($xmlContent),
            'doctors'      => $xmlHandler->importDoctors($xmlContent),
            default        => ['imported'=>0,'skipped'=>0,'errors'=>['Unknown type.']],
        };
        if ($importResult['imported'] > 0) {
            $message = "{$importResult['imported']} record(s) imported successfully.";
        } else {
            $message = 'No records imported.';
            $msgType = 'warning';
        }
    }
}

$pageTitle = 'XML Tools';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-file-earmark-code me-2 text-primary"></i>XML Tools</h2>
    <p>Export database records to XML or import XML data into the database</p>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> alert-auto-dismiss">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : ($msgType === 'warning' ? 'exclamation-circle' : 'x-circle') ?>-fill me-2"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($importResult): ?>
<div class="alert alert-info">
    <strong>Import Summary:</strong>
    Imported: <strong><?= $importResult['imported'] ?></strong> |
    Skipped: <strong><?= $importResult['skipped'] ?></strong>
    <?php if (!empty($importResult['errors'])): ?>
    <ul class="mb-0 mt-2 ps-3">
        <?php foreach ($importResult['errors'] as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- EXPORT -->
    <div class="col-lg-6">
        <div class="form-card h-100">
            <h5 class="fw-bold mb-1"><i class="bi bi-upload me-2 text-success"></i>Export to XML</h5>
            <p class="text-muted small mb-4">Export database records to an XML file using DOMDocument.</p>

            <div class="d-grid gap-2">
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/admin/xml_tools.php?export=patients" class="btn btn-outline-success flex-fill">
                        <i class="bi bi-download me-2"></i>Download Patients XML
                    </a>
                    <a href="<?= BASE_URL ?>/admin/xml_tools.php?preview=patients" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/admin/xml_tools.php?export=doctors" class="btn btn-outline-success flex-fill">
                        <i class="bi bi-download me-2"></i>Download Doctors XML
                    </a>
                    <a href="<?= BASE_URL ?>/admin/xml_tools.php?preview=doctors" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/admin/xml_tools.php?export=appointments" class="btn btn-outline-success flex-fill">
                        <i class="bi bi-download me-2"></i>Download Appointments XML
                    </a>
                    <a href="<?= BASE_URL ?>/admin/xml_tools.php?preview=appointments" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
            </div>

            <?php if ($xmlOutput): ?>
            <div class="mt-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold small">XML Preview</span>
                    <a href="<?= BASE_URL ?>/admin/xml_tools.php" class="btn btn-sm btn-outline-secondary">Close</a>
                </div>
                <div class="xml-preview"><?= htmlspecialchars($xmlOutput) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- IMPORT -->
    <div class="col-lg-6">
        <div class="form-card h-100">
            <h5 class="fw-bold mb-1"><i class="bi bi-cloud-upload me-2 text-primary"></i>Import from XML</h5>
            <p class="text-muted small mb-4">Upload an XML file to import records. Uses DOMDocument for parsing.</p>

            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Import Type</label>
                    <select name="import_type" class="form-select">
                        <option value="appointments">Appointments</option>
                        <option value="doctors">Doctors</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">XML File</label>
                    <input type="file" name="xml_file" class="form-control" accept=".xml" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-cloud-upload me-2"></i>Import XML Data
                </button>
            </form>

            <hr class="my-4">

            <!-- Expected format reference -->
            <div>
                <p class="fw-semibold small mb-2"><i class="bi bi-code-slash me-1"></i>Expected XML Format</p>
                <ul class="nav nav-tabs nav-sm mb-2" id="sampleTabs">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sampleAppt">Appointments</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#sampleDoc">Doctors</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="sampleAppt">
<div class="xml-preview" style="max-height:180px">&lt;appointments&gt;
  &lt;appointment&gt;
    &lt;patient_id&gt;1&lt;/patient_id&gt;
    &lt;doctor_id&gt;1&lt;/doctor_id&gt;
    &lt;appointment_date&gt;2024-07-15&lt;/appointment_date&gt;
    &lt;appointment_time&gt;09:00&lt;/appointment_time&gt;
    &lt;reason&gt;Annual checkup&lt;/reason&gt;
    &lt;notes&gt;Optional notes&lt;/notes&gt;
  &lt;/appointment&gt;
&lt;/appointments&gt;</div>
                    </div>
                    <div class="tab-pane fade" id="sampleDoc">
<div class="xml-preview" style="max-height:180px">&lt;doctors&gt;
  &lt;doctor&gt;
    &lt;first_name&gt;Pedro&lt;/first_name&gt;
    &lt;last_name&gt;Reyes&lt;/last_name&gt;
    &lt;specialty&gt;Dermatology&lt;/specialty&gt;
    &lt;phone&gt;09121234567&lt;/phone&gt;
    &lt;email&gt;p.reyes@clinic.com&lt;/email&gt;
    &lt;status&gt;active&lt;/status&gt;
  &lt;/doctor&gt;
&lt;/doctors&gt;</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
