<?php
class PrescriptionModel
{
    private $c;
    function __construct($c)
    {
        $this->c = $c;
    }

    private function medicines($prescriptionId)
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

    function allForPatient($pid)
    {
        $s = mysqli_prepare(
            $this->c,
            'SELECT pr.*,d.id doctor_id,d.name ' .
                'doctor_name,d.specialization,a.doctor_serial appointment_serial FROM ' .
                'prescriptions pr JOIN doctors d ON d.id=pr.doctor_id LEFT JOIN ' .
                'appointments a ON a.id=pr.appointment_id AND a.doctor_id=pr.doctor_id ' .
                'WHERE pr.patient_id=? ORDER BY pr.created_at DESC'
        );
        mysqli_stmt_bind_param($s, 'i', $pid);
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        foreach ($rows as &$row) {
            $row['medicines'] = $this->medicines((int) $row['id']);
        }
        unset($row);
        return $rows;
    }

    function searchForPatientByDoctorName($pid, $query)
    {
        $query = trim($query);
        if ($query === '') {
            return $this->allForPatient($pid);
        }
        $like = '%' . $query . '%';
        $sql =
            'SELECT DISTINCT pr.*,d.id doctor_id,d.name doctor_name,d.specialization,' .
            "a.doctor_serial appointment_serial
        FROM prescriptions pr JOIN " .
            'doctors d ON d.id=pr.doctor_id LEFT JOIN appointments a ON ' .
            "a.id=pr.appointment_id AND a.doctor_id=pr.doctor_id
        WHERE " .
            'pr.patient_id=? AND (d.name LIKE ? OR d.specialization LIKE ? OR pr.status ' .
            "LIKE ? OR DATE(pr.created_at) LIKE ?
          OR CONCAT('D'," .
            "LPAD(pr.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0')) LIKE ?
          " .
            'OR EXISTS (SELECT 1 FROM prescription_medicines pm WHERE ' .
            'pm.prescription_id=pr.id AND (pm.medicine_name LIKE ? OR pm.dosage LIKE ? ' .
            'OR pm.frequency LIKE ? OR pm.duration LIKE ? OR pm.instructions LIKE ' .
            "?)))
        ORDER BY pr.created_at DESC";
        $s = mysqli_prepare($this->c, $sql);
        mysqli_stmt_bind_param(
            $s,
            'issssssssss',
            $pid,
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
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        foreach ($rows as &$row) {
            $row['medicines'] = $this->medicines((int) $row['id']);
        }
        unset($row);
        return $rows;
    }

    function oneForPatient($pid, $prescriptionId)
    {
        $s = mysqli_prepare(
            $this->c,
            'SELECT pr.*,d.id doctor_id,d.name ' .
                'doctor_name,d.specialization,a.doctor_serial appointment_serial FROM ' .
                'prescriptions pr JOIN doctors d ON d.id=pr.doctor_id LEFT JOIN ' .
                'appointments a ON a.id=pr.appointment_id AND a.doctor_id=pr.doctor_id ' .
                'WHERE pr.patient_id=? AND pr.id=? LIMIT 1'
        );
        mysqli_stmt_bind_param($s, 'ii', $pid, $prescriptionId);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s)) ?: null;
        mysqli_stmt_close($s);
        if ($row) {
            $row['medicines'] = $this->medicines((int) $row['id']);
        }
        return $row;
    }

    function activeCount($pid)
    {
        $s = mysqli_prepare(
            $this->c,
            "SELECT COUNT(*) n FROM prescriptions WHERE patient_id=? AND status='Active'"
        );
        mysqli_stmt_bind_param($s, 'i', $pid);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return (int) $r['n'];
    }
}
