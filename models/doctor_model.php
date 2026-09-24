<?php
class DoctorModel
{
    private $c;
    function __construct($c)
    {
        $this->c = $c;
    }
    function byUser($uid)
    {
        $s = mysqli_prepare($this->c, 'SELECT * FROM doctors WHERE user_id=? LIMIT 1');
        mysqli_stmt_bind_param($s, 'i', $uid);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $r;
    }

    function updateProfile($did, $data)
    {
        $s = mysqli_prepare(
            $this->c,
            'UPDATE doctors SET ' .
                ('name=?,phone=?,gender=?,specialization=?,qualification=?,license_number=?,' .
                    'experience=?,department=?,room_number=?,bio=? ') .
                'WHERE id=?'
        );
        if (!$s) {
            return false;
        }
        mysqli_stmt_bind_param(
            $s,
            'ssssssisssi',
            $data['name'],
            $data['phone'],
            $data['gender'],
            $data['specialization'],
            $data['qualification'],
            $data['license_number'],
            $data['experience'],
            $data['department'],
            $data['room_number'],
            $data['bio'],
            $did
        );
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    // Only patients with a real (Booked/Completed) appointment under this doctor are connected to the doctor.
    function allPatients($did, $search = '')
    {
        $sql =
            'SELECT p.id patient_id,p.name patient_name,p.phone,p.age,p.gender,p.email,' .
            'p.address,p.blood_group,p.status,COUNT(a.id) appointment_count,' .
            "MAX(a.appointment_date) last_appointment
        FROM patients p
        " .
            'JOIN appointments a ON a.patient_id=p.id AND a.doctor_id=? AND a.status IN ' .
            "('Booked','Completed')";
        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .=
                ' WHERE (p.name LIKE ? OR p.phone LIKE ? OR p.email LIKE ? OR CAST(p.age AS ' .
                "CHAR) LIKE ? OR p.gender LIKE ? OR COALESCE(p.blood_group,'') LIKE ? OR " .
                "p.status LIKE ? OR CONCAT('P',LPAD(p.id,4,'0')) LIKE ?)";
        }
        $sql .=
            ' GROUP BY p.id,p.name,p.phone,p.age,p.gender,p.email,p.address,p.blood_group,p.status ORDER BY p.name ASC';
        $s = mysqli_prepare($this->c, $sql);
        if ($search !== '') {
            mysqli_stmt_bind_param(
                $s,
                'issssssss',
                $did,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like
            );
        } else {
            mysqli_stmt_bind_param($s, 'i', $did);
        }
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }

    function connectedPatients($did)
    {
        $s = mysqli_prepare(
            $this->c,
            "SELECT p.id patient_id,p.name patient_name,p.phone,MAX(a.id) latest_appointment_id
                              FROM patients p JOIN appointments a ON a.patient_id=p.id
                              WHERE a.doctor_id=? AND a.status IN ('Booked','Completed')
                              GROUP BY p.id,p.name,p.phone ORDER BY p.name"
        );
        mysqli_stmt_bind_param($s, 'i', $did);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }

    function bookablePatients($did, $search = '')
    {
        $sql =
            'SELECT p.id patient_id,p.name patient_name,p.phone,COUNT(a.id) ' .
            "appointment_count,MAX(a.appointment_date) last_appointment,
               " .
            'SUBSTRING_INDEX(GROUP_CONCAT(a.doctor_serial ORDER BY a.appointment_date ' .
            "ASC,a.appointment_time ASC,a.id ASC SEPARATOR ','), ',', 1) " .
            "next_appointment_serial
        FROM patients p JOIN appointments a ON " .
            "a.patient_id=p.id
        WHERE a.doctor_id=? AND a.status='Booked' AND " .
            'TIMESTAMP(a.appointment_date,a.appointment_time)<=NOW()';
        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .=
                " AND (p.name LIKE ? OR p.phone LIKE ? OR COALESCE(p.email,'') LIKE ? OR " .
                "CONCAT('P',LPAD(p.id,4,'0')) LIKE ? OR " .
                "CONCAT('D',LPAD(a.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0')) LIKE ?)";
        }
        $sql .= ' GROUP BY p.id,p.name,p.phone ORDER BY p.name ASC LIMIT 30';
        $s = mysqli_prepare($this->c, $sql);
        if ($search !== '') {
            mysqli_stmt_bind_param($s, 'isssss', $did, $like, $like, $like, $like, $like);
        } else {
            mysqli_stmt_bind_param($s, 'i', $did);
        }
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }

    function clinicalAppointmentOptions($did)
    {
        $s = mysqli_prepare(
            $this->c,
            'SELECT a.*,p.name patient_name,p.phone,p.age,p.gender,p.email,' .
                "ds.start_time,ds.end_time
                              FROM appointments " .
                'a JOIN patients p ON p.id=a.patient_id JOIN doctor_slots ds ON ' .
                "ds.id=a.slot_id
                              WHERE a.doctor_id=? AND " .
                "a.status='Booked' AND TIMESTAMP(a.appointment_date," .
                "a.appointment_time)<=NOW()
                              ORDER BY " .
                'a.appointment_date ASC,a.appointment_time ASC,a.id ASC'
        );
        mysqli_stmt_bind_param($s, 'i', $did);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }

    function canAccessPatient($did, $pid)
    {
        $s = mysqli_prepare(
            $this->c,
            "SELECT 1 FROM appointments WHERE doctor_id=? AND patient_id=? AND status IN ('Booked','Completed') LIMIT 1"
        );
        mysqli_stmt_bind_param($s, 'ii', $did, $pid);
        mysqli_stmt_execute($s);
        $ok = (bool) mysqli_fetch_row(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $ok;
    }

    function appointmentBelongsToPatient($did, $pid, $aid)
    {
        if (!$aid) {
            return false;
        }
        $s = mysqli_prepare(
            $this->c,
            'SELECT 1 FROM appointments WHERE id=? AND doctor_id=? AND patient_id=? AND ' .
                "status='Booked' AND TIMESTAMP(appointment_date,appointment_time)<=NOW() " .
                'LIMIT 1'
        );
        mysqli_stmt_bind_param($s, 'iii', $aid, $did, $pid);
        mysqli_stmt_execute($s);
        $ok = (bool) mysqli_fetch_row(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $ok;
    }

    function appointments($did, $search = '')
    {
        $search = trim($search);
        $base =
            'SELECT a.*,p.name ' .
            'patient_name,p.phone,p.age,p.gender,p.email,ds.start_time,ds.end_time FROM ' .
            'appointments a JOIN patients p ON p.id=a.patient_id JOIN doctor_slots ds ' .
            'ON ds.id=a.slot_id WHERE a.doctor_id=?';
        $isIdPrefix = (bool) preg_match('/^D\d{0,4}(?:-\d{0,3})?$/i', $search);
        if ($search !== '' && $isIdPrefix) {
            $prefix = strtoupper($search) . '%';
            $sql =
                $base .
                (' AND ' .
                    "UPPER(CONCAT('D',LPAD(a.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0'))) " .
                    'LIKE ? ORDER BY a.appointment_date DESC,a.appointment_time DESC');
            $s = mysqli_prepare($this->c, $sql);
            mysqli_stmt_bind_param($s, 'is', $did, $prefix);
        } else {
            $sql = $base;
            if ($search !== '') {
                $like = '%' . $search . '%';
                $sql .=
                    " AND (p.name LIKE ? OR p.phone LIKE ? OR COALESCE(p.email,'') LIKE ? OR " .
                    'a.status LIKE ? OR CAST(a.doctor_serial AS CHAR) LIKE ? OR ' .
                    "a.appointment_date LIKE ? OR TIME_FORMAT(a.appointment_time,'%h:%i %p') " .
                    "LIKE ? OR COALESCE(a.reason,'') LIKE ? OR " .
                    "CONCAT('D',LPAD(a.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0')) LIKE ?)";
            }
            $sql .= ' ORDER BY a.appointment_date DESC,a.appointment_time DESC';
            $s = mysqli_prepare($this->c, $sql);
            if ($search !== '') {
                mysqli_stmt_bind_param(
                    $s,
                    'isssssssss',
                    $did,
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
            } else {
                mysqli_stmt_bind_param($s, 'i', $did);
            }
        }
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }

    function patient($pid, $did)
    {
        $s = mysqli_prepare(
            $this->c,
            "SELECT p.*,COUNT(a.id) visits,MAX(a.appointment_date) last_appointment
                              FROM patients p JOIN appointments a ON a.patient_id=p.id
                              WHERE p.id=? AND a.doctor_id=? AND a.status IN ('Booked','Completed')
                              GROUP BY p.id LIMIT 1"
        );
        mysqli_stmt_bind_param($s, 'ii', $pid, $did);
        mysqli_stmt_execute($s);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $p;
    }
    function symptoms($pid)
    {
        $s = mysqli_prepare(
            $this->c,
            'SELECT * FROM symptoms WHERE patient_id=? ORDER BY symptom_date DESC,id DESC'
        );
        mysqli_stmt_bind_param($s, 'i', $pid);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }

    function prescriptionMedicines($prescriptionId)
    {
        $s = mysqli_prepare(
            $this->c,
            'SELECT id,medicine_name,dosage,frequency,duration,instructions FROM ' .
                'prescription_medicines WHERE prescription_id=? ORDER BY id ASC'
        );
        mysqli_stmt_bind_param($s, 'i', $prescriptionId);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }
    function prescriptions($did, $search = '')
    {
        $sql =
            'SELECT DISTINCT pr.*,p.name patient_name,a.doctor_serial ' .
            'appointment_serial FROM prescriptions pr JOIN patients p ON ' .
            'p.id=pr.patient_id LEFT JOIN appointments a ON a.id=pr.appointment_id AND ' .
            'a.doctor_id=pr.doctor_id WHERE pr.doctor_id=?';
        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .=
                ' AND (p.name LIKE ? OR pr.note LIKE ? OR pr.status LIKE ? OR ' .
                'DATE(pr.created_at) LIKE ? OR ' .
                "CONCAT('D',LPAD(pr.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0')) LIKE " .
                '? OR EXISTS (SELECT 1 FROM prescription_medicines pm WHERE ' .
                'pm.prescription_id=pr.id AND (pm.medicine_name LIKE ? OR pm.dosage LIKE ? ' .
                'OR pm.frequency LIKE ? OR pm.duration LIKE ? OR pm.instructions LIKE ?)))';
        }
        $sql .= ' ORDER BY pr.created_at DESC';
        $s = mysqli_prepare($this->c, $sql);
        if ($search !== '') {
            mysqli_stmt_bind_param(
                $s,
                'issssssssss',
                $did,
                $like,
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
        } else {
            mysqli_stmt_bind_param($s, 'i', $did);
        }
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        foreach ($rows as &$row) {
            $row['medicines'] = $this->prescriptionMedicines((int) $row['id']);
        }
        unset($row);
        return $rows;
    }
    function savePrescription($d, $data)
    {
        if (
            !$this->canAccessPatient($d, (int) $data['patient_id']) ||
            !$this->appointmentBelongsToPatient(
                $d,
                (int) $data['patient_id'],
                (int) $data['appointment_id']
            )
        ) {
            return false;
        }
        $medicines = $data['medicines'] ?? [];
        if (!$medicines) {
            return false;
        }
        $id = (int) ($data['id'] ?? 0);
        $aid = (int) ($data['appointment_id'] ?? 0);
        $aid = $aid ?: null;
        $note = $data['note'] ?? '';
        $status = $data['status'] ?? 'Active';
        mysqli_begin_transaction($this->c);
        try {
            if ($id) {
                $check = mysqli_prepare(
                    $this->c,
                    'SELECT id FROM prescriptions WHERE id=? AND doctor_id=? LIMIT 1'
                );
                mysqli_stmt_bind_param($check, 'ii', $id, $d);
                mysqli_stmt_execute($check);
                $owned = (bool) mysqli_fetch_row(mysqli_stmt_get_result($check));
                mysqli_stmt_close($check);
                if (!$owned) {
                    throw new Exception();
                }
                $s = mysqli_prepare(
                    $this->c,
                    'UPDATE prescriptions SET patient_id=?,appointment_id=?,note=?,status=? WHERE id=? AND doctor_id=?'
                );
                mysqli_stmt_bind_param(
                    $s,
                    'iissii',
                    $data['patient_id'],
                    $aid,
                    $note,
                    $status,
                    $id,
                    $d
                );
                if (!mysqli_stmt_execute($s)) {
                    throw new Exception();
                }
                mysqli_stmt_close($s);
                $s = mysqli_prepare(
                    $this->c,
                    'DELETE FROM prescription_medicines WHERE prescription_id=?'
                );
                mysqli_stmt_bind_param($s, 'i', $id);
                if (!mysqli_stmt_execute($s)) {
                    throw new Exception();
                }
                mysqli_stmt_close($s);
                $prescriptionId = $id;
            } else {
                $s = mysqli_prepare(
                    $this->c,
                    'INSERT INTO prescriptions(patient_id,doctor_id,appointment_id,note,status) VALUES(?,?,?,?,?)'
                );
                mysqli_stmt_bind_param($s, 'iiiss', $data['patient_id'], $d, $aid, $note, $status);
                if (!mysqli_stmt_execute($s)) {
                    throw new Exception();
                }
                $prescriptionId = mysqli_insert_id($this->c);
                mysqli_stmt_close($s);
            }
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO ' .
                    'prescription_medicines(prescription_id,medicine_name,dosage,frequency,duration,instructions) ' .
                    'VALUES(?,?,?,?,?,?)'
            );
            foreach ($medicines as $m) {
                $name = $m['medicine_name'];
                $dosage = $m['dosage'];
                $frequency = $m['frequency'];
                $duration = $m['duration'];
                $instructions = $m['instructions'] ?? '';
                mysqli_stmt_bind_param(
                    $s,
                    'isssss',
                    $prescriptionId,
                    $name,
                    $dosage,
                    $frequency,
                    $duration,
                    $instructions
                );
                if (!mysqli_stmt_execute($s)) {
                    throw new Exception();
                }
            }
            mysqli_stmt_close($s);
            mysqli_commit($this->c);
            return true;
        } catch (Throwable $e) {
            mysqli_rollback($this->c);
            return false;
        }
    }
    function deletePrescription($d, $id)
    {
        $s = mysqli_prepare(
            $this->c,
            "UPDATE prescriptions SET status='Cancelled' WHERE id=? AND doctor_id=?"
        );
        mysqli_stmt_bind_param($s, 'ii', $id, $d);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    function followups($did)
    {
        $s = mysqli_prepare(
            $this->c,
            'SELECT f.*,p.name patient_name,a.doctor_serial appointment_serial FROM ' .
                'followups f JOIN patients p ON p.id=f.patient_id LEFT JOIN appointments a ' .
                'ON a.id=f.appointment_id AND a.doctor_id=f.doctor_id WHERE f.doctor_id=? ' .
                'ORDER BY f.followup_date DESC'
        );
        mysqli_stmt_bind_param($s, 'i', $did);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }
    function saveFollowup($d, $data)
    {
        if (
            !$this->canAccessPatient($d, (int) $data['patient_id']) ||
            !$this->appointmentBelongsToPatient(
                $d,
                (int) $data['patient_id'],
                (int) $data['appointment_id']
            )
        ) {
            return false;
        }
        $id = (int) ($data['id'] ?? 0);
        $aid = (int) ($data['appointment_id'] ?? 0);
        $aid = $aid ?: null;
        if ($id) {
            $s = mysqli_prepare(
                $this->c,
                'UPDATE followups SET ' .
                    'patient_id=?,appointment_id=?,followup_date=?,purpose=?,status=? WHERE ' .
                    'id=? AND doctor_id=?'
            );
            mysqli_stmt_bind_param(
                $s,
                'iisssii',
                $data['patient_id'],
                $aid,
                $data['followup_date'],
                $data['purpose'],
                $data['status'],
                $id,
                $d
            );
        } else {
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO followups(patient_id,doctor_id,appointment_id,followup_date,purpose) VALUES(?,?,?,?,?)'
            );
            mysqli_stmt_bind_param(
                $s,
                'iiiss',
                $data['patient_id'],
                $d,
                $aid,
                $data['followup_date'],
                $data['purpose']
            );
        }
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }
    function updateFollowupDetails($d, $id, $date, $purpose)
    {
        $check = mysqli_prepare(
            $this->c,
            "SELECT id FROM followups WHERE id=? AND doctor_id=? AND status='Active' LIMIT 1"
        );
        mysqli_stmt_bind_param($check, 'ii', $id, $d);
        mysqli_stmt_execute($check);
        $exists = (bool) mysqli_fetch_assoc(mysqli_stmt_get_result($check));
        mysqli_stmt_close($check);
        if (!$exists) {
            return false;
        }
        $s = mysqli_prepare(
            $this->c,
            'UPDATE followups SET followup_date=?,purpose=? WHERE id=? AND doctor_id=?'
        );
        mysqli_stmt_bind_param($s, 'ssii', $date, $purpose, $id, $d);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }
    function deleteFollowup($d, $id)
    {
        $s = mysqli_prepare(
            $this->c,
            "UPDATE followups SET status='Cancelled' WHERE id=? AND doctor_id=?"
        );
        mysqli_stmt_bind_param($s, 'ii', $id, $d);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    // Doctor provides availability; receptionist turns it into bookable slots.
    function availability($did, $date = '')
    {
        $sql = 'SELECT * FROM doctor_availability WHERE doctor_id=?';
        if ($date !== '') {
            $sql .= ' AND available_date=?';
        }
        $sql .= ' ORDER BY available_date DESC,start_time';
        $s = mysqli_prepare($this->c, $sql);
        if ($date !== '') {
            mysqli_stmt_bind_param($s, 'is', $did, $date);
        } else {
            mysqli_stmt_bind_param($s, 'i', $did);
        }
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $r;
    }
    function availabilityDateExists($did, $date, $excludeId = 0)
    {
        if ($excludeId) {
            $s = mysqli_prepare(
                $this->c,
                'SELECT id FROM doctor_availability WHERE doctor_id=? AND available_date=? AND id<>? LIMIT 1'
            );
            mysqli_stmt_bind_param($s, 'isi', $did, $date, $excludeId);
        } else {
            $s = mysqli_prepare(
                $this->c,
                'SELECT id FROM doctor_availability WHERE doctor_id=? AND available_date=? LIMIT 1'
            );
            mysqli_stmt_bind_param($s, 'is', $did, $date);
        }
        mysqli_stmt_execute($s);
        $exists = (bool) mysqli_fetch_row(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $exists;
    }
    function saveAvailability($did, $data)
    {
        $id = (int) ($data['id'] ?? 0);
        $date = $data['available_date'] ?? '';
        $start = $data['start_time'] ?? '';
        $end = $data['end_time'] ?? '';
        $note = trim($data['note'] ?? '');
        $status = $data['status'] ?? 'Available';
        $today = date('Y-m-d');
        $maxDate = date('Y-m-d', strtotime('+9 days'));
        $currentTime = date('H:i');
        if (
            !$date ||
            !$start ||
            !$end ||
            $date < $today ||
            $date > $maxDate ||
            ($date === $today && $start < $currentTime) ||
            $start >= $end ||
            !in_array($status, ['Available', 'Unavailable'], true)
        ) {
            return false;
        }
        if ($this->availabilityDateExists($did, $date, $id)) {
            return false;
        }
        if ($id) {
            $s = mysqli_prepare(
                $this->c,
                'UPDATE doctor_availability SET ' .
                    'available_date=?,start_time=?,end_time=?,note=?,status=? WHERE id=? AND ' .
                    'doctor_id=?'
            );
            mysqli_stmt_bind_param($s, 'sssssii', $date, $start, $end, $note, $status, $id, $did);
        } else {
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO ' .
                    'doctor_availability(doctor_id,available_date,start_time,end_time,note,status) ' .
                    'VALUES(?,?,?,?,?,?)'
            );
            mysqli_stmt_bind_param($s, 'isssss', $did, $date, $start, $end, $note, $status);
        }
        $ok = @mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    function deleteAvailability($did, $id)
    {
        $s = mysqli_prepare($this->c, 'DELETE FROM doctor_availability WHERE id=? AND doctor_id=?');
        mysqli_stmt_bind_param($s, 'ii', $id, $did);
        $ok = mysqli_stmt_execute($s);
        $affected = mysqli_stmt_affected_rows($s);
        mysqli_stmt_close($s);
        return $ok && $affected > 0;
    }

    function completeAppointment($did, $id)
    {
        $s = mysqli_prepare(
            $this->c,
            "UPDATE appointments SET status='Completed' WHERE id=? AND doctor_id=? AND " .
                "status='Booked' AND TIMESTAMP(appointment_date,appointment_time)<=NOW()"
        );
        mysqli_stmt_bind_param($s, 'ii', $id, $did);
        $ok = mysqli_stmt_execute($s);
        $changed = mysqli_stmt_affected_rows($s);
        mysqli_stmt_close($s);
        return $ok && $changed === 1;
    }
}
