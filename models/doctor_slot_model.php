<?php
class DoctorSlotModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function doctors()
    {
        $r = mysqli_query($this->c, 'SELECT * FROM doctors ORDER BY name');
        return mysqli_fetch_all($r, MYSQLI_ASSOC);
    }

    function availabilities()
    {
        $q =
            'SELECT av.*,d.name doctor_name,d.specialization FROM doctor_availability ' .
            "av JOIN doctors d ON d.id=av.doctor_id WHERE av.status='Available' AND " .
            'av.available_date>=CURDATE() ORDER BY ' .
            'av.available_date,av.start_time,d.name';
        $r = mysqli_query($this->c, $q);
        return mysqli_fetch_all($r, MYSQLI_ASSOC);
    }

    function slots($date = '', $doctorId = 0, $department = '')
    {
        $base =
            'SELECT s.*,d.name doctor_name,d.specialization,d.department FROM ' .
            'doctor_slots s JOIN doctors d ON d.id=s.doctor_id';
        $where = [];
        $date = trim((string) $date);
        $doctorId = (int) $doctorId;
        $department = trim((string) $department);
        if ($date !== '' && valid_ymd_date($date)) {
            $where[] = "s.slot_date='" . mysqli_real_escape_string($this->c, $date) . "'";
        }
        if ($doctorId > 0) {
            $where[] = 'd.id=' . $doctorId;
        }
        if ($department !== '') {
            $where[] =
                "COALESCE(d.department,'')='" .
                mysqli_real_escape_string($this->c, $department) .
                "'";
        }
        $sql =
            $base .
            ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
            ' ORDER BY s.slot_date,s.start_time';
        $r = mysqli_query($this->c, $sql);
        return $r ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
    }

    function saveSlot($d)
    {
        $id = (int) ($d['id'] ?? 0);
        $availabilityId = (int) ($d['availability_id'] ?? 0);
        $date = $d['slot_date'] ?? '';
        $start = $d['start_time'] ?? '';
        $end = $d['end_time'] ?? '';
        $status = $d['status'] ?? 'Available';
        if (
            !$availabilityId ||
            !$date ||
            !$start ||
            !$end ||
            $start >= $end ||
            !in_array($status, ['Available', 'Closed'], true)
        ) {
            return false;
        }
        if (
            !valid_ymd_date($date) ||
            $date < local_today() ||
            ($date === local_today() && $start < date('H:i'))
        ) {
            return false;
        }
        $s = mysqli_prepare(
            $this->c,
            "SELECT * FROM doctor_availability WHERE id=? AND status='Available' AND " .
                'available_date=? AND start_time<=? AND end_time>=? LIMIT 1'
        );
        mysqli_stmt_bind_param($s, 'isss', $availabilityId, $date, $start, $end);
        mysqli_stmt_execute($s);
        $av = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        if (!$av) {
            return false;
        }
        $doctorId = (int) $av['doctor_id'];
        if ($id) {
            $s = mysqli_prepare($this->c, 'SELECT status FROM doctor_slots WHERE id=?');
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s);
            $old = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if (!$old || $old['status'] === 'Booked') {
                return false;
            }
            $s = mysqli_prepare(
                $this->c,
                'UPDATE doctor_slots SET doctor_id=?,availability_id=?,slot_date=?,start_time=?,end_time=?,status=? WHERE id=?'
            );
            mysqli_stmt_bind_param(
                $s,
                'iissssi',
                $doctorId,
                $availabilityId,
                $date,
                $start,
                $end,
                $status,
                $id
            );
        } else {
            $s = mysqli_prepare(
                $this->c,
                'SELECT id FROM doctor_slots WHERE doctor_id=? AND slot_date=? AND start_time=? LIMIT 1'
            );
            mysqli_stmt_bind_param($s, 'iss', $doctorId, $date, $start);
            mysqli_stmt_execute($s);
            $dup = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if ($dup) {
                return false;
            }
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO doctor_slots(doctor_id,availability_id,slot_date,start_time,end_time,status) VALUES(?,?,?,?,?,?)'
            );
            mysqli_stmt_bind_param(
                $s,
                'iissss',
                $doctorId,
                $availabilityId,
                $date,
                $start,
                $end,
                $status
            );
        }
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    function deleteSlot($id)
    {
        $s = mysqli_prepare($this->c, "DELETE FROM doctor_slots WHERE id=? AND status<>'Booked'");
        mysqli_stmt_bind_param($s, 'i', $id);
        $ok = mysqli_stmt_execute($s);
        $affected = mysqli_stmt_affected_rows($s);
        mysqli_stmt_close($s);
        return $ok && $affected > 0;
    }

    function searchDoctorsWithAvailability($q)
    {
        $q = trim((string) $q);
        if ($q === '') {
            return [];
        }

        $like = '%' . $q . '%';
        $sql = "SELECT d.id, d.name, d.specialization, COUNT(av.id) AS availability_count
                FROM doctors d
                INNER JOIN doctor_availability av ON av.doctor_id = d.id
                WHERE d.status = 'Active'
                  AND av.status = 'Available'
                  AND av.available_date >= CURDATE()
                  AND d.name LIKE ?
                GROUP BY d.id, d.name, d.specialization
                ORDER BY d.name
                LIMIT 20";
        $stmt = mysqli_prepare($this->c, $sql);
        mysqli_stmt_bind_param($stmt, 's', $like);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }

    function futureAvailabilityForDoctor($doctorId)
    {
        $doctorId = (int) $doctorId;
        if ($doctorId <= 0) {
            return [];
        }

        $sql = "SELECT id, available_date, start_time, end_time, note
                FROM doctor_availability
                WHERE doctor_id = ?
                  AND status = 'Available'
                  AND (available_date > CURDATE()
                       OR (available_date = CURDATE() AND end_time > CURTIME()))
                ORDER BY available_date, start_time";
        $stmt = mysqli_prepare($this->c, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $doctorId);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }
}
