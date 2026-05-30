<?php
/**
 * Doctor.php
 * CRUD operations for the `doctors` and `schedules` tables.
 */
class Doctor
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Return all doctors.
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM doctors ORDER BY last_name, first_name"
        );
    }

    /**
     * Return only active doctors.
     */
    public function getActive(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM doctors WHERE status = 'active' ORDER BY last_name, first_name"
        );
    }

    /**
     * Return a single doctor by ID, with schedules.
     */
    public function getById(int $id): ?array
    {
        $doctor = $this->db->fetchOne('SELECT * FROM doctors WHERE id = ?', [$id]);
        if ($doctor) {
            $doctor['schedules'] = $this->db->fetchAll(
                'SELECT * FROM schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week,
                 "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday")',
                [$id]
            );
        }
        return $doctor;
    }

    /**
     * Create a new doctor record.
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO doctors (first_name, last_name, specialty, phone, email, bio, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['first_name'],
                $data['last_name'],
                $data['specialty'],
                $data['phone']   ?? null,
                $data['email']   ?? null,
                $data['bio']     ?? null,
                $data['status']  ?? 'active',
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing doctor record.
     */
    public function update(int $id, array $data): bool
    {
        $this->db->execute(
            'UPDATE doctors SET first_name=?, last_name=?, specialty=?, phone=?,
             email=?, bio=?, status=? WHERE id=?',
            [
                $data['first_name'],
                $data['last_name'],
                $data['specialty'],
                $data['phone']  ?? null,
                $data['email']  ?? null,
                $data['bio']    ?? null,
                $data['status'] ?? 'active',
                $id,
            ]
        );
        return true;
    }

    /**
     * Delete a doctor (cascades schedules and appointments).
     */
    public function delete(int $id): bool
    {
        $this->db->execute('DELETE FROM doctors WHERE id = ?', [$id]);
        return true;
    }

    /**
     * Replace a doctor's weekly schedule.
     */
    public function updateSchedule(int $doctorId, array $schedules): void
    {
        $this->db->execute('DELETE FROM schedules WHERE doctor_id = ?', [$doctorId]);
        foreach ($schedules as $s) {
            if (!empty($s['day_of_week']) && !empty($s['start_time']) && !empty($s['end_time'])) {
                $this->db->execute(
                    'INSERT INTO schedules (doctor_id, day_of_week, start_time, end_time)
                     VALUES (?, ?, ?, ?)',
                    [$doctorId, $s['day_of_week'], $s['start_time'], $s['end_time']]
                );
            }
        }
    }

    /**
     * Total number of active doctors.
     */
    public function countActive(): int
    {
        $row = $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM doctors WHERE status='active'");
        return (int) ($row['cnt'] ?? 0);
    }
}
