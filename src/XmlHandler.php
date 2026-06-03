<?php
/**
 * XmlHandler.php
 * XML export and import using DOMDocument.
 */
class XmlHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ------------------------------------------------------------------ */
    /*  EXPORT METHODS                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Export all patients as XML.
     */
    public function exportPatients(): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('patients');
        $dom->appendChild($root);

        $users = $this->db->fetchAll(
            "SELECT id, username, email, first_name, last_name, phone, created_at
             FROM users WHERE role='patient' ORDER BY id"
        );

        foreach ($users as $u) {
            $node = $dom->createElement('patient');
            $this->appendChild($dom, $node, 'id',         (string) $u['id']);
            $this->appendChild($dom, $node, 'username',   $u['username']);
            $this->appendChild($dom, $node, 'email',      $u['email']);
            $this->appendChild($dom, $node, 'first_name', $u['first_name']);
            $this->appendChild($dom, $node, 'last_name',  $u['last_name']);
            $this->appendChild($dom, $node, 'phone',      $u['phone'] ?? '');
            $this->appendChild($dom, $node, 'registered', $u['created_at']);
            $root->appendChild($node);
        }

        return $dom->saveXML();
    }

    /**
     * Export all doctors as XML.
     */
    public function exportDoctors(): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('doctors');
        $dom->appendChild($root);

        $doctors = $this->db->fetchAll('SELECT * FROM doctors ORDER BY id');
        foreach ($doctors as $d) {
            $node = $dom->createElement('doctor');
            $this->appendChild($dom, $node, 'id',         (string) $d['id']);
            $this->appendChild($dom, $node, 'first_name', $d['first_name']);
            $this->appendChild($dom, $node, 'last_name',  $d['last_name']);
            $this->appendChild($dom, $node, 'specialty',  $d['specialty']);
            $this->appendChild($dom, $node, 'phone',      $d['phone'] ?? '');
            $this->appendChild($dom, $node, 'email',      $d['email'] ?? '');
            $this->appendChild($dom, $node, 'status',     $d['status']);
            $root->appendChild($node);
        }

        return $dom->saveXML();
    }

    /**
     * Export all appointments as XML.
     */
    public function exportAppointments(?int $patientId = null): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('appointments');
        $dom->appendChild($root);

        $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
                       a.reason, a.notes, a.created_at,
                       CONCAT(u.first_name,' ',u.last_name) AS patient_name,
                       u.email AS patient_email,
                       CONCAT(d.first_name,' ',d.last_name) AS doctor_name,
                       d.specialty
                FROM appointments a
                JOIN users   u ON a.patient_id = u.id
                JOIN doctors d ON a.doctor_id  = d.id";

        $params = [];
        if ($patientId !== null) {
            $sql    .= ' WHERE a.patient_id = ?';
            $params  = [$patientId];
        }
        $sql .= ' ORDER BY a.appointment_date DESC, a.appointment_time DESC';

        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as $r) {
            $node = $dom->createElement('appointment');
            $this->appendChild($dom, $node, 'id',               (string) $r['id']);
            $this->appendChild($dom, $node, 'patient_name',     $r['patient_name']);
            $this->appendChild($dom, $node, 'patient_email',    $r['patient_email']);
            $this->appendChild($dom, $node, 'doctor_name',      'Dr. ' . $r['doctor_name']);
            $this->appendChild($dom, $node, 'specialty',        $r['specialty']);
            $this->appendChild($dom, $node, 'appointment_date', $r['appointment_date']);
            $this->appendChild($dom, $node, 'appointment_time', $r['appointment_time']);
            $this->appendChild($dom, $node, 'status',           $r['status']);
            $this->appendChild($dom, $node, 'reason',           $r['reason']);
            $this->appendChild($dom, $node, 'notes',            $r['notes'] ?? '');
            $this->appendChild($dom, $node, 'created_at',       $r['created_at']);
            $root->appendChild($node);
        }

        return $dom->saveXML();
    }

    /* ------------------------------------------------------------------ */
    /*  IMPORT METHODS                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Import appointments from an uploaded XML file.
     * Expected structure:
     *   <appointments>
     *     <appointment>
     *       <patient_id>1</patient_id>
     *       <doctor_id>1</doctor_id>
     *       <appointment_date>2024-06-15</appointment_date>
     *       <appointment_time>09:00</appointment_time>
     *       <reason>Annual checkup</reason>
     *     </appointment>
     *   </appointments>
     *
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public function importAppointments(string $xmlContent): array
    {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        if (!$dom->loadXML($xmlContent)) {
            $result['errors'][] = 'Invalid XML file.';
            return $result;
        }

        $nodes = $dom->getElementsByTagName('appointment');

        foreach ($nodes as $node) {
            try {
                $patientId = (int) $this->getNodeValue($node, 'patient_id');
                $doctorId  = (int) $this->getNodeValue($node, 'doctor_id');
                $date      = $this->getNodeValue($node, 'appointment_date');
                $time      = $this->getNodeValue($node, 'appointment_time');
                $reason    = $this->getNodeValue($node, 'reason');

                if (!$patientId || !$doctorId || !$date || !$time || !$reason) {
                    $result['skipped']++;
                    $result['errors'][] = "Row skipped: missing required field(s).";
                    continue;
                }

                // Validate patient and doctor exist
                $patientOk = $this->db->fetchOne(
                    "SELECT id FROM users WHERE id=? AND role='patient'", [$patientId]
                );
                $doctorOk  = $this->db->fetchOne(
                    'SELECT id FROM doctors WHERE id=?', [$doctorId]
                );

                if (!$patientOk || !$doctorOk) {
                    $result['skipped']++;
                    $result['errors'][] = "Row skipped: invalid patient_id ($patientId) or doctor_id ($doctorId).";
                    continue;
                }

                $this->db->execute(
                    'INSERT INTO appointments (patient_id, doctor_id, appointment_date,
                     appointment_time, reason, notes, status) VALUES (?,?,?,?,?,?,?)',
                    [$patientId, $doctorId, $date, $time, $reason,
                     $this->getNodeValue($node, 'notes'), 'pending']
                );
                $result['imported']++;

            } catch (\Exception $e) {
                $result['skipped']++;
                $result['errors'][] = 'Row error: ' . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Import patients from an uploaded XML file.
     * Expected structure:
     *   <patients>
     *     <patient>
     *       <username>jdoe</username>
     *       <email>jdoe@email.com</email>
     *       <first_name>John</first_name>
     *       <last_name>Doe</last_name>
     *       <phone>09171234567</phone>      <!-- optional -->
     *       <password>secret123</password>  <!-- optional; auto-generated if omitted -->
     *     </patient>
     *   </patients>
     *
     * @return array{imported: int, skipped: int, errors: string[], temp_passwords: array<string, string>}
     */
    public function importPatients(string $xmlContent): array
    {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => [], 'temp_passwords' => []];

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        if (!$dom->loadXML($xmlContent)) {
            $result['errors'][] = 'Invalid XML file.';
            return $result;
        }

        $nodes = $dom->getElementsByTagName('patient');

        foreach ($nodes as $node) {
            try {
                $username  = $this->getNodeValue($node, 'username');
                $email     = $this->getNodeValue($node, 'email');
                $firstName = $this->getNodeValue($node, 'first_name');
                $lastName  = $this->getNodeValue($node, 'last_name');
                $phone     = $this->getNodeValue($node, 'phone');
                $password  = $this->getNodeValue($node, 'password');

                // Validate required fields
                if (!$username || !$email || !$firstName || !$lastName) {
                    $result['skipped']++;
                    $result['errors'][] = "Row skipped: missing required field(s) — username, email, first_name, and last_name are all required.";
                    continue;
                }

                // Basic email format check
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $result['skipped']++;
                    $result['errors'][] = "Row skipped: invalid email address '$email'.";
                    continue;
                }

                // Reject duplicate username or email
                $existing = $this->db->fetchOne(
                    'SELECT id FROM users WHERE username = ? OR email = ?',
                    [$username, $email]
                );
                if ($existing) {
                    $result['skipped']++;
                    $result['errors'][] = "Row skipped: username '$username' or email '$email' already exists.";
                    continue;
                }

                // Auto-generate a temporary password when none is supplied
                $tempPassword = null;
                if ($password === '') {
                    $tempPassword = $this->generateTempPassword();
                    $password     = $tempPassword;
                }

                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $this->db->execute(
                    'INSERT INTO users (username, email, password, first_name, last_name, phone, role)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$username, $email, $hash, $firstName, $lastName, $phone ?: null, 'patient']
                );
                $result['imported']++;

                if ($tempPassword !== null) {
                    $result['temp_passwords'][$username] = $tempPassword;
                }

            } catch (\Exception $e) {
                $result['skipped']++;
                $result['errors'][] = 'Row error: ' . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Import doctors from an uploaded XML file.
     *
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public function importDoctors(string $xmlContent): array
    {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        if (!$dom->loadXML($xmlContent)) {
            $result['errors'][] = 'Invalid XML file.';
            return $result;
        }

        $nodes = $dom->getElementsByTagName('doctor');
        foreach ($nodes as $node) {
            try {
                $firstName = $this->getNodeValue($node, 'first_name');
                $lastName  = $this->getNodeValue($node, 'last_name');
                $specialty = $this->getNodeValue($node, 'specialty');

                if (!$firstName || !$lastName || !$specialty) {
                    $result['skipped']++;
                    $result['errors'][] = "Row skipped: missing required field(s).";
                    continue;
                }

                $this->db->execute(
                    'INSERT INTO doctors (first_name, last_name, specialty, phone, email, bio, status)
                     VALUES (?,?,?,?,?,?,?)',
                    [
                        $firstName, $lastName, $specialty,
                        $this->getNodeValue($node, 'phone'),
                        $this->getNodeValue($node, 'email'),
                        $this->getNodeValue($node, 'bio'),
                        $this->getNodeValue($node, 'status') ?: 'active',
                    ]
                );
                $result['imported']++;
            } catch (\Exception $e) {
                $result['skipped']++;
                $result['errors'][] = 'Row error: ' . $e->getMessage();
            }
        }

        return $result;
    }

    /* ------------------------------------------------------------------ */
    /*  HELPERS                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Generate a cryptographically random temporary password.
     */
    private function generateTempPassword(int $length = 10): string
    {
        $chars    = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$';
        $password = '';
        $max      = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }

    /**
     * Create and append a text child element.
     */
    private function appendChild(DOMDocument $dom, DOMElement $parent, string $tag, string $value): void
    {
        $el = $dom->createElement($tag);
        $el->appendChild($dom->createTextNode($value));
        $parent->appendChild($el);
    }

    /**
     * Safely read a named child element's text content.
     */
    private function getNodeValue(DOMElement $node, string $tag): string
    {
        $els = $node->getElementsByTagName($tag);
        return $els->length > 0 ? trim($els->item(0)->textContent) : '';
    }
}