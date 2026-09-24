<?php
class ReceptionistAppointmentModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    private function nextDoctorSerial($doctorId)
    {
        $s = mysqli_prepare(
            $this->c,
            'INSERT INTO doctor_appointment_counters(doctor_id,last_serial) VALUES(?,1) ' .
                'ON DUPLICATE KEY UPDATE last_serial=last_serial+1'
        );
        if (!$s) {
            throw new Exception();
        }
        mysqli_stmt_bind_param($s, 'i', $doctorId);
        if (!mysqli_stmt_execute($s)) {
            mysqli_stmt_close($s);
            throw new Exception();
        }
        mysqli_stmt_close($s);
        $s = mysqli_prepare(
            $this->c,
            'SELECT last_serial FROM doctor_appointment_counters WHERE doctor_id=? LIMIT 1'
        );
        mysqli_stmt_bind_param($s, 'i', $doctorId);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        if (!$row) {
            throw new Exception();
        }
        return (int) $row['last_serial'];
    }

    function appointments($q = '')
    {
        $q = trim($q);
        $base =
            'SELECT a.*,p.name patient_name,d.name ' .
            'doctor_name,s.slot_date,s.start_time,s.end_time FROM appointments a JOIN ' .
            'patients p ON p.id=a.patient_id JOIN doctors d ON d.id=a.doctor_id JOIN ' .
            'doctor_slots s ON s.id=a.slot_id';
        $isIdPrefix = (bool) preg_match('/^D\d{0,4}(?:-\d{0,3})?$/i', $q);
        if ($q !== '' && $isIdPrefix) {
            $prefix = strtoupper($q) . '%';
            $sql =
                $base .
                (' WHERE ' .
                    "UPPER(CONCAT('D',LPAD(a.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0'))) " .
                    'LIKE ? ORDER BY a.appointment_date DESC,a.appointment_time DESC');
            $s = mysqli_prepare($this->c, $sql);
            mysqli_stmt_bind_param($s, 's', $prefix);
        } else {
            $sql = $base;
            if ($q !== '') {
                $like = '%' . $q . '%';
                $sql .=
                    " WHERE (p.name LIKE ? OR d.name LIKE ? OR COALESCE(a.reason,'') LIKE ? OR " .
                    'a.status LIKE ? OR a.appointment_date LIKE ? OR a.appointment_time LIKE ? ' .
                    "OR CAST(a.id AS CHAR) LIKE ? OR LPAD(a.id,3,'0') LIKE ? OR " .
                    "CONCAT('D',LPAD(a.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0')) LIKE ?)";
            }
            $sql .= ' ORDER BY a.appointment_date DESC,a.appointment_time DESC';
            $s = mysqli_prepare($this->c, $sql);
            if ($q !== '') {
                mysqli_stmt_bind_param(
                    $s,
                    'sssssssss',
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like
                );
            }
        }
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }

    function createAppointment($pid, $sid, $reason)
    {
        mysqli_begin_transaction($this->c);
        try {
            $s = mysqli_prepare(
                $this->c,
                "SELECT id FROM patients WHERE id=? AND status='Active' LIMIT 1"
            );
            mysqli_stmt_bind_param($s, 'i', $pid);
            mysqli_stmt_execute($s);
            $patient = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if (!$patient) {
                throw new Exception();
            }
            $s = mysqli_prepare(
                $this->c,
                "SELECT * FROM doctor_slots WHERE id=? AND status='Available' AND " .
                    '(slot_date>CURDATE() OR (slot_date=CURDATE() AND start_time>=CURTIME())) ' .
                    'FOR UPDATE'
            );
            mysqli_stmt_bind_param($s, 'i', $sid);
            mysqli_stmt_execute($s);
            $slot = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if (!$slot) {
                throw new Exception();
            }
            $doctorId = (int) $slot['doctor_id'];
            $doctorSerial = $this->nextDoctorSerial($doctorId);
            $s = mysqli_prepare(
                $this->c,
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
                $sid,
                $slot['slot_date'],
                $slot['start_time'],
                $reason
            );
            if (!mysqli_stmt_execute($s)) {
                throw new Exception();
            }
            mysqli_stmt_close($s);
            $s = mysqli_prepare(
                $this->c,
                "UPDATE doctor_slots SET status='Booked' WHERE id=? AND status='Available'"
            );
            mysqli_stmt_bind_param($s, 'i', $sid);
            mysqli_stmt_execute($s);
            if (mysqli_stmt_affected_rows($s) !== 1) {
                throw new Exception();
            }
            mysqli_stmt_close($s);
            mysqli_commit($this->c);
            return true;
        } catch (Throwable $e) {
            mysqli_rollback($this->c);
            return false;
        }
    }

    function cancelAppointment($id)
    {
        mysqli_begin_transaction($this->c);
        try {
            $s = mysqli_prepare(
                $this->c,
                "SELECT slot_id FROM appointments WHERE id=? AND status='Booked' FOR UPDATE"
            );
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s);
            $a = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if (!$a) {
                throw new Exception();
            }
            $s = mysqli_prepare(
                $this->c,
                "UPDATE appointments SET status='Cancelled' WHERE id=? AND status='Booked'"
            );
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s);
            $changed = mysqli_stmt_affected_rows($s);
            mysqli_stmt_close($s);
            if ($changed !== 1) {
                throw new Exception();
            }
            $s = mysqli_prepare($this->c, "UPDATE doctor_slots SET status='Available' WHERE id=?");
            mysqli_stmt_bind_param($s, 'i', $a['slot_id']);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            mysqli_commit($this->c);
            return true;
        } catch (Throwable $e) {
            mysqli_rollback($this->c);
            return false;
        }
    }

}
