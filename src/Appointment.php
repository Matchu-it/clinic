<?php
/**
 * Appointment.php
 * CRUD operations for appointments and medical records.
 */
class Appointment
{
    private Database $db;
    private array $medicalRecordColumns = [];
    private array $appointmentsColumns = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Return all appointments with patient and doctor info.
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT a.*,
                    CONCAT(u.first_name,' ',u.last_name) AS patient_name,
                    u.email AS patient_email,
                    CONCAT('Dr. ',d.first_name,' ',d.last_name) AS doctor_name,
                    d.specialty
             FROM appointments a
             JOIN users   u ON a.patient_id = u.id
             JOIN doctors d ON a.doctor_id  = d.id
             ORDER BY a.appointment_date DESC, a.appointment_time DESC"
        );
    }

    /**
     * Return appointments for a specific patient.
     */
    public function getByPatient(int $patientId): array
    {
        return $this->db->fetchAll(
            "SELECT a.*,
                    CONCAT('Dr. ',d.first_name,' ',d.last_name) AS doctor_name,
                    d.specialty
             FROM appointments a
             JOIN doctors d ON a.doctor_id = d.id
             WHERE a.patient_id = ?
             ORDER BY a.appointment_date DESC, a.appointment_time DESC",
            [$patientId]
        );
    }

    /**
     * Return a single appointment by ID, with medical record, follow-up, and parent info.
     */
    public function getById(int $id): ?array
    {
        $appt = $this->db->fetchOne(
            "SELECT a.*,
                    CONCAT(u.first_name,' ',u.last_name) AS patient_name,
                    u.email AS patient_email, u.phone AS patient_phone,
                    CONCAT('Dr. ',d.first_name,' ',d.last_name) AS doctor_name,
                    d.specialty
             FROM appointments a
             JOIN users   u ON a.patient_id = u.id
             JOIN doctors d ON a.doctor_id  = d.id
             WHERE a.id = ?",
            [$id]
        );

        if ($appt) {
            $appt['id']         = (int) $appt['id'];
            $appt['patient_id'] = (int) $appt['patient_id'];
            $appt['doctor_id']  = (int) $appt['doctor_id'];

            $appt['medical_record'] = $this->db->fetchOne(
                'SELECT * FROM medical_records WHERE appointment_id = ?', [$id]
            );

            // Follow-up support is optional; only query if the field exists.
            if ($this->appointmentHasColumn('follow_up_of') && array_key_exists('follow_up_of', $appt)) {
                $appt['follow_up_of'] = $appt['follow_up_of'] !== null ? (int) $appt['follow_up_of'] : null;

                $appt['follow_up'] = $this->db->fetchOne(
                    "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.reason,
                            CONCAT('Dr. ',d.first_name,' ',d.last_name) AS doctor_name
                     FROM appointments a
                     JOIN doctors d ON a.doctor_id = d.id
                     WHERE a.follow_up_of = ?",
                    [$id]
                );

                if (!empty($appt['follow_up_of'])) {
                    $appt['parent'] = $this->db->fetchOne(
                        "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
                                CONCAT('Dr. ',d.first_name,' ',d.last_name) AS doctor_name
                         FROM appointments a
                         JOIN doctors d ON a.doctor_id = d.id
                         WHERE a.id = ?",
                        [(int) $appt['follow_up_of']]
                    );
                }
            }
        }

        return $appt;
    }

    /**
     * Create a new appointment.
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO appointments (patient_id, doctor_id, appointment_date,
             appointment_time, reason, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['patient_id'],
                $data['doctor_id'],
                $data['appointment_date'],
                $data['appointment_time'],
                $data['reason'],
                $data['notes'] ?? null,
                $data['status'] ?? 'pending',
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Return medical_records column names for schema compatibility.
     */
    private function getMedicalRecordColumns(): array
    {
        if ($this->medicalRecordColumns === []) {
            $rows = $this->db->fetchAll('SHOW COLUMNS FROM medical_records');
            foreach ($rows as $row) {
                if (!empty($row['Field'])) {
                    $this->medicalRecordColumns[] = $row['Field'];
                }
            }
        }
        return $this->medicalRecordColumns;
    }

    private function medicalRecordHasColumn(string $column): bool
    {
        return in_array($column, $this->getMedicalRecordColumns(), true);
    }

    private function getAppointmentColumns(): array
    {
        if ($this->appointmentsColumns === []) {
            $rows = $this->db->fetchAll('SHOW COLUMNS FROM appointments');
            foreach ($rows as $row) {
                if (!empty($row['Field'])) {
                    $this->appointmentsColumns[] = $row['Field'];
                }
            }
        }
        return $this->appointmentsColumns;
    }

    private function appointmentHasColumn(string $column): bool
    {
        return in_array($column, $this->getAppointmentColumns(), true);
    }

    private function ensureTableColumn(string $table, string $column, string $definition): void
    {
        $exists = false;
        if ($table === 'appointments') {
            $exists = $this->appointmentHasColumn($column);
        } elseif ($table === 'medical_records') {
            $exists = $this->medicalRecordHasColumn($column);
        }

        if (!$exists) {
            $this->db->execute("ALTER TABLE $table ADD COLUMN $column $definition");
            if ($table === 'appointments') {
                $this->appointmentsColumns = [];
            } elseif ($table === 'medical_records') {
                $this->medicalRecordColumns = [];
            }
        }
    }

    /**
     * Update appointment status or notes.
     */
    public function update(int $id, array $data): bool
    {
        $this->db->execute(
            'UPDATE appointments SET doctor_id=?, appointment_date=?, appointment_time=?,
             status=?, reason=?, notes=? WHERE id=?',
            [
                $data['doctor_id'],
                $data['appointment_date'],
                $data['appointment_time'],
                $data['status'],
                $data['reason'],
                $data['notes'] ?? null,
                $id,
            ]
        );
        return true;
    }

    /**
     * Update only the status of an appointment.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['pending','confirmed','cancelled','completed'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid status: $status");
        }
        $this->db->execute(
            'UPDATE appointments SET status=? WHERE id=?', [$status, $id]
        );
        return true;
    }

    /**
     * Reschedule an appointment to a new date/time and confirm it.
     */
    public function reschedule(int $id, string $date, string $time): bool
    {
        $this->db->execute(
            'UPDATE appointments SET appointment_date=?, appointment_time=?, status=? WHERE id=?',
            [$date, $time, 'confirmed', $id]
        );
        return true;
    }

    /**
     * Create a follow-up appointment linked to a parent appointment.
     * Inherits the patient from the parent; doctor can be changed.
     *
     * @return int New appointment ID
     */
    public function createFollowUp(int $parentId, array $data): int
    {
        if (!$this->appointmentHasColumn('follow_up_of')) {
            $this->ensureTableColumn('appointments', 'follow_up_of', 'int(11) DEFAULT NULL');
        }

        $parent = $this->db->fetchOne('SELECT * FROM appointments WHERE id=?', [$parentId]);
        if (!$parent) {
            throw new \InvalidArgumentException('Parent appointment not found.');
        }

        $this->db->execute(
            'INSERT INTO appointments (patient_id, doctor_id, appointment_date,
             appointment_time, reason, notes, status, follow_up_of)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $parent['patient_id'],
                (int) ($data['doctor_id'] ?? $parent['doctor_id']),
                $data['appointment_date'],
                $data['appointment_time'],
                $data['reason'] ?: 'Follow-up appointment',
                $data['notes'] ?? null,
                'confirmed',
                $parentId,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Delete an appointment.
     */
    public function delete(int $id): bool
    {
        $this->db->execute('DELETE FROM appointments WHERE id=?', [$id]);
        return true;
    }

    /**
     * Save or update a medical record (text fields) for an appointment.
     * Optionally includes pdf_path / pdf_original_name.
     */
    public function saveMedicalRecord(int $appointmentId, array $data): void
    {
        $hasPdfPath = $this->medicalRecordHasColumn('pdf_path');
        $hasPdfName = $this->medicalRecordHasColumn('pdf_original_name');

        $existing = $this->db->fetchOne(
            'SELECT id FROM medical_records WHERE appointment_id=?', [$appointmentId]
        );

        if ($existing) {
            $setParts = ['diagnosis=?', 'prescription=?', 'notes=?'];
            $values   = [
                $data['diagnosis']    ?? null,
                $data['prescription'] ?? null,
                $data['notes']        ?? null,
            ];

            if ($hasPdfPath && array_key_exists('pdf_path', $data)) {
                $setParts[] = 'pdf_path=?';
                $values[]   = $data['pdf_path'];
            }
            if ($hasPdfName && array_key_exists('pdf_original_name', $data)) {
                $setParts[] = 'pdf_original_name=?';
                $values[]   = $data['pdf_original_name'];
            }

            $values[] = $appointmentId;
            $this->db->execute(
                'UPDATE medical_records SET ' . implode(', ', $setParts) . ' WHERE appointment_id=?',
                $values
            );
        } else {
            $columns      = ['appointment_id', 'diagnosis', 'prescription', 'notes'];
            $placeholders = ['?', '?', '?', '?'];
            $values       = [
                $appointmentId,
                $data['diagnosis']    ?? null,
                $data['prescription'] ?? null,
                $data['notes']        ?? null,
            ];

            if ($hasPdfPath) {
                $columns[] = 'pdf_path';
                $placeholders[] = '?';
                $values[] = $data['pdf_path'] ?? null;
            }
            if ($hasPdfName) {
                $columns[] = 'pdf_original_name';
                $placeholders[] = '?';
                $values[] = $data['pdf_original_name'] ?? null;
            }

            $this->db->execute(
                'INSERT INTO medical_records (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
                $values
            );
        }
    }

    /**
     * Upload or replace a PDF medical record file for an appointment.
     * Creates the medical_records row if it does not exist yet.
     */
    public function updateMedicalRecordPdf(int $appointmentId, string $pdfPath, string $pdfOriginalName): void
    {
        $hasPdfPath = $this->medicalRecordHasColumn('pdf_path');
        $hasPdfName = $this->medicalRecordHasColumn('pdf_original_name');
        if (!$hasPdfPath) {
            $this->ensureTableColumn('medical_records', 'pdf_path', 'varchar(255) DEFAULT NULL');
            $hasPdfPath = true;
        }
        if (!$hasPdfName) {
            $this->ensureTableColumn('medical_records', 'pdf_original_name', 'varchar(255) DEFAULT NULL');
            $hasPdfName = true;
        }

        $existing = $this->db->fetchOne(
            'SELECT id FROM medical_records WHERE appointment_id=?', [$appointmentId]
        );
        if ($existing) {
            $this->db->execute(
                'UPDATE medical_records SET pdf_path=?, pdf_original_name=? WHERE appointment_id=?',
                [$pdfPath, $pdfOriginalName, $appointmentId]
            );
        } else {
            $this->db->execute(
                'INSERT INTO medical_records (appointment_id, pdf_path, pdf_original_name)
                 VALUES (?, ?, ?)',
                [$appointmentId, $pdfPath, $pdfOriginalName]
            );
        }
    }

    /**
     * Counts by status for the dashboard.
     */
    public function countByStatus(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT status, COUNT(*) AS cnt FROM appointments GROUP BY status'
        );
        $map = ['pending'=>0,'confirmed'=>0,'cancelled'=>0,'completed'=>0];
        foreach ($rows as $r) {
            $map[$r['status']] = (int)$r['cnt'];
        }
        return $map;
    }

    /**
     * Total appointments for a patient.
     */
    public function countByPatient(int $patientId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM appointments WHERE patient_id=?', [$patientId]
        );
        return (int)($row['cnt']??0);
    }
}