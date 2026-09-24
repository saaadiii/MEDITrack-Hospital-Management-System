<?php
class Appointment
{
    private $conn;
    function __construct($conn)
    {
        $this->conn = $conn;
    }
    function availableSlots()
    {
        $q =
            'SELECT ds.id,ds.slot_date,ds.start_time ' .
            'slot_time,ds.start_time,ds.end_time,d.name doctor_name,d.specialization ' .
            'FROM doctor_slots ds JOIN doctors d ON d.id=ds.doctor_id WHERE ' .
            "ds.status='Available' AND d.status='Active' AND (ds.slot_date>CURDATE() OR " .
            '(ds.slot_date=CURDATE() AND ds.start_time>=CURTIME())) ORDER BY ' .
            'ds.slot_date,ds.start_time LIMIT 50';
        $r = mysqli_query($this->conn, $q);
        return mysqli_fetch_all($r, MYSQLI_ASSOC);
    }

    function specializations()
    {
        $q =
            'SELECT DISTINCT TRIM(specialization) specialization FROM doctors WHERE ' .
            "status='Active' AND specialization IS NOT NULL AND " .
            "TRIM(specialization)<>'' ORDER BY specialization";
        $r = mysqli_query($this->conn, $q);
        return mysqli_fetch_all($r, MYSQLI_ASSOC);
    }

    function doctorsBySpecialization($specialization, $date = '')
    {
        if ($date !== '') {
            $s = mysqli_prepare(
                $this->conn,
                'SELECT d.id,d.name,d.specialization,d.qualification,d.experience,' .
                    "d.department,d.room_number,d.bio,d.gender,
      (SELECT COUNT(*) FROM " .
                    "doctor_slots ds WHERE ds.doctor_id=d.id AND ds.status='Available' AND " .
                    'ds.slot_date=? AND (ds.slot_date>CURDATE() OR (ds.slot_date=CURDATE() AND ' .
                    "ds.start_time>=CURTIME()))) available_slot_count
      FROM doctors d " .
                    "WHERE d.status='Active' AND d.specialization=? ORDER BY d.name"
            );
            mysqli_stmt_bind_param($s, 'ss', $date, $specialization);
        } else {
            $s = mysqli_prepare(
                $this->conn,
                'SELECT d.id,d.name,d.specialization,d.qualification,d.experience,' .
                    "d.department,d.room_number,d.bio,d.gender,
      (SELECT COUNT(*) FROM " .
                    "doctor_slots ds WHERE ds.doctor_id=d.id AND ds.status='Available' AND " .
                    '(ds.slot_date>CURDATE() OR (ds.slot_date=CURDATE() AND ' .
                    "ds.start_time>=CURTIME()))) available_slot_count
      FROM doctors d " .
                    "WHERE d.status='Active' AND d.specialization=? ORDER BY d.name"
            );
            mysqli_stmt_bind_param($s, 's', $specialization);
        }
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    }

    function doctorBookingProfile($doctorId, $date = '')
    {
        $s = mysqli_prepare(
            $this->conn,
            'SELECT ' .
                'id,name,specialization,qualification,experience,department,room_number,bio,gender ' .
                "FROM doctors WHERE id=? AND status='Active' LIMIT 1"
        );
        mysqli_stmt_bind_param($s, 'i', $doctorId);
        mysqli_stmt_execute($s);
        $doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        if (!$doctor) {
            return null;
        }
        if ($date !== '') {
            $s = mysqli_prepare(
                $this->conn,
                'SELECT id,slot_date,start_time,end_time FROM doctor_slots WHERE ' .
                    "doctor_id=? AND status='Available' AND slot_date=? AND " .
                    '(slot_date>CURDATE() OR (slot_date=CURDATE() AND start_time>=CURTIME())) ' .
                    'ORDER BY slot_date,start_time LIMIT 60'
            );
            mysqli_stmt_bind_param($s, 'is', $doctorId, $date);
        } else {
            $s = mysqli_prepare(
                $this->conn,
                'SELECT id,slot_date,start_time,end_time FROM doctor_slots WHERE ' .
                    "doctor_id=? AND status='Available' AND (slot_date>CURDATE() OR " .
                    '(slot_date=CURDATE() AND start_time>=CURTIME())) ORDER BY ' .
                    'slot_date,start_time LIMIT 60'
            );
            mysqli_stmt_bind_param($s, 'i', $doctorId);
        }
        mysqli_stmt_execute($s);
        $doctor['slots'] = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $doctor;
    }

    private function nextDoctorSerial($doctorId)
    {
        $s = mysqli_prepare(
            $this->conn,
            'INSERT INTO doctor_appointment_counters(doctor_id,last_serial) VALUES(?,1) ' .
                'ON DUPLICATE KEY UPDATE last_serial=last_serial+1'
        );
        if (!$s) {
            throw new Exception('Could not prepare doctor appointment counter.');
        }
        mysqli_stmt_bind_param($s, 'i', $doctorId);
        if (!mysqli_stmt_execute($s)) {
            mysqli_stmt_close($s);
            throw new Exception('Could not allocate appointment number.');
        }
        mysqli_stmt_close($s);
        $s = mysqli_prepare(
            $this->conn,
            'SELECT last_serial FROM doctor_appointment_counters WHERE doctor_id=? LIMIT 1'
        );
        mysqli_stmt_bind_param($s, 'i', $doctorId);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        if (!$row) {
            throw new Exception('Could not allocate appointment number.');
        }
        return (int) $row['last_serial'];
    }
    function allForPatient($pid)
    {
        $s = mysqli_prepare(
            $this->conn,
            'SELECT ' .
                'a.id,a.doctor_id,a.doctor_serial,a.appointment_date,a.appointment_time,a.status,a.reason,d.name ' .
                'doctor_name,d.specialization FROM appointments a JOIN doctors d ON ' .
                'd.id=a.doctor_id WHERE a.patient_id=? ORDER BY a.appointment_date ' .
                'DESC,a.appointment_time DESC'
        );
        mysqli_stmt_bind_param($s, 'i', $pid);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }
    function searchForPatient($pid, $q = '')
    {
        $q = trim($q);
        if ($q === '') {
            return $this->allForPatient($pid);
        }
        $isIdPrefix = (bool) preg_match('/^D\d{0,4}(?:-\d{0,3})?$/i', $q);
        if ($isIdPrefix) {
            $prefix = strtoupper($q) . '%';
            $s = mysqli_prepare(
                $this->conn,
                'SELECT ' .
                    'a.id,a.doctor_id,a.doctor_serial,a.appointment_date,a.appointment_time,a.status,a.reason,d.name ' .
                    'doctor_name,d.specialization FROM appointments a JOIN doctors d ON ' .
                    'd.id=a.doctor_id WHERE a.patient_id=? AND ' .
                    "UPPER(CONCAT('D',LPAD(a.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0'))) " .
                    'LIKE ? ORDER BY a.appointment_date DESC,a.appointment_time DESC'
            );
            mysqli_stmt_bind_param($s, 'is', $pid, $prefix);
        } else {
            $like = '%' . $q . '%';
            $s = mysqli_prepare(
                $this->conn,
                'SELECT ' .
                    'a.id,a.doctor_id,a.doctor_serial,a.appointment_date,a.appointment_time,a.status,a.reason,d.name ' .
                    'doctor_name,d.specialization FROM appointments a JOIN doctors d ON ' .
                    'd.id=a.doctor_id WHERE a.patient_id=? AND (d.name LIKE ? OR ' .
                    "d.specialization LIKE ? OR a.status LIKE ? OR COALESCE(a.reason,'') LIKE ? " .
                    "OR a.appointment_date LIKE ? OR TIME_FORMAT(a.appointment_time,'%h:%i %p') " .
                    'LIKE ? OR ' .
                    "CONCAT('D',LPAD(a.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0')) LIKE " .
                    '?) ORDER BY a.appointment_date DESC,a.appointment_time DESC'
            );
            mysqli_stmt_bind_param(
                $s,
                'isssssss',
                $pid,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like
            );
        }
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }
    function book($pid, $slotId)
    {
        mysqli_begin_transaction($this->conn);
        try {
            $s = mysqli_prepare(
                $this->conn,
                'SELECT ds.*,d.id doctor_id,d.name doctor_name FROM doctor_slots ds JOIN ' .
                    "doctors d ON d.id=ds.doctor_id WHERE ds.id=? AND ds.status='Available' FOR " .
                    'UPDATE'
            );
            mysqli_stmt_bind_param($s, 'i', $slotId);
            mysqli_stmt_execute($s);
            $slot = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if (!$slot) {
                throw new Exception('That slot is no longer available.');
            }
            $doctorId = (int) $slot['doctor_id'];
            $doctorSerial = $this->nextDoctorSerial($doctorId);
            $reason = trim($_POST['reason'] ?? '');
            $s = mysqli_prepare(
                $this->conn,
                'INSERT INTO ' .
                    'appointments(patient_id,doctor_id,doctor_serial,slot_id,appointment_date,appointment_time,reason) ' .
                    'VALUES(?,?,?,?,?,?,?)'
            );
            mysqli_stmt_bind_param(
                $s,
                'iiiisss',
                $pid,
                $doctorId,
                $doctorSerial,
                $slotId,
                $slot['slot_date'],
                $slot['start_time'],
                $reason
            );
            if (!mysqli_stmt_execute($s)) {
                throw new Exception('Could not book the appointment.');
            }
            mysqli_stmt_close($s);
            $s = mysqli_prepare(
                $this->conn,
                "UPDATE doctor_slots SET status='Booked' WHERE id=? AND status='Available'"
            );
            mysqli_stmt_bind_param($s, 'i', $slotId);
            mysqli_stmt_execute($s);
            if (mysqli_stmt_affected_rows($s) !== 1) {
                throw new Exception('That slot is no longer available.');
            }
            mysqli_stmt_close($s);
            mysqli_commit($this->conn);
            return [
                'success' => true,
                'message' =>
                    'Appointment booked successfully with ' .
                    $slot['doctor_name'] .
                    ' (' .
                    doctor_appointment_display_id($doctorId, $doctorSerial) .
                    ').'
            ];
        } catch (Throwable $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    function cancel($pid, $aid)
    {
        mysqli_begin_transaction($this->conn);
        try {
            $s = mysqli_prepare(
                $this->conn,
                "SELECT slot_id FROM appointments WHERE id=? AND patient_id=? AND status='Booked' LIMIT 1"
            );
            mysqli_stmt_bind_param($s, 'ii', $aid, $pid);
            mysqli_stmt_execute($s);
            $a = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if (!$a) {
                throw new Exception('Appointment could not be cancelled.');
            }
            $s = mysqli_prepare(
                $this->conn,
                "UPDATE appointments SET status='Cancelled' WHERE id=? AND patient_id=?"
            );
            mysqli_stmt_bind_param($s, 'ii', $aid, $pid);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            $s = mysqli_prepare(
                $this->conn,
                "UPDATE doctor_slots SET status='Available' WHERE id=?"
            );
            mysqli_stmt_bind_param($s, 'i', $a['slot_id']);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Appointment cancelled.'];
        } catch (Throwable $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    function delete($pid, $aid)
    {
        mysqli_begin_transaction($this->conn);
        try {
            $s = mysqli_prepare(
                $this->conn,
                'SELECT slot_id,status FROM appointments WHERE id=? AND patient_id=? LIMIT 1'
            );
            mysqli_stmt_bind_param($s, 'ii', $aid, $pid);
            mysqli_stmt_execute($s);
            $a = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if (!$a) {
                throw new Exception('Appointment not found.');
            }
            if (in_array($a['status'], ['Completed', 'Did not appear'], true)) {
                throw new Exception('Completed or missed appointments cannot be deleted.');
            }
            if ($a['status'] === 'Booked') {
                $s = mysqli_prepare(
                    $this->conn,
                    "UPDATE doctor_slots SET status='Available' WHERE id=?"
                );
                mysqli_stmt_bind_param($s, 'i', $a['slot_id']);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
            }
            $s = mysqli_prepare(
                $this->conn,
                'DELETE FROM appointments WHERE id=? AND patient_id=?'
            );
            mysqli_stmt_bind_param($s, 'ii', $aid, $pid);
            if (!mysqli_stmt_execute($s)) {
                throw new Exception('Could not delete the appointment.');
            }
            mysqli_stmt_close($s);
            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Appointment deleted successfully.'];
        } catch (Throwable $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    function lastCompletedVisit($pid)
    {
        $s = mysqli_prepare(
            $this->conn,
            'SELECT appointment_date FROM appointments WHERE patient_id=? AND ' .
                "status='Completed' ORDER BY appointment_date DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($s, 'i', $pid);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $r;
    }
    function upcoming($pid)
    {
        $s = mysqli_prepare(
            $this->conn,
            'SELECT a.appointment_date,a.appointment_time,a.status,d.name ' .
                'doctor_name,d.specialization FROM appointments a JOIN doctors d ON ' .
                "d.id=a.doctor_id WHERE a.patient_id=? AND a.status='Booked' AND " .
                '(a.appointment_date>CURDATE() OR (a.appointment_date=CURDATE() AND ' .
                'a.appointment_time>=CURTIME())) ORDER BY ' .
                'a.appointment_date,a.appointment_time LIMIT 1'
        );
        mysqli_stmt_bind_param($s, 'i', $pid);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $r;
    }
}
