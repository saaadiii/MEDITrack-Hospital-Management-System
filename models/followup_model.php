<?php

class Followup
{
    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function allForPatient($patientId)
    {
        $sql =
            'SELECT f.id, f.followup_date, f.purpose, f.status, d.name AS doctor_name, ' .
            'd.specialization FROM followups f JOIN doctors d ON d.id = f.doctor_id ' .
            'WHERE f.patient_id = ? ORDER BY f.followup_date ASC, f.id ASC';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $patientId);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public function nextActive($patientId)
    {
        $sql =
            'SELECT f.followup_date, f.purpose, f.status, d.name AS doctor_name, ' .
            'd.specialization FROM followups f JOIN doctors d ON d.id = f.doctor_id ' .
            "WHERE f.patient_id = ? AND f.status = 'Active' AND f.followup_date >= " .
            'CURDATE() ORDER BY f.followup_date ASC, f.id ASC LIMIT 1';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $patientId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row;
    }
}
