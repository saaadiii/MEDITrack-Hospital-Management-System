<?php

class Symptom
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function create($patientId, $symptom, $severity, $duration, $frequency, $date, $notes)
    {
        $sql =
            'INSERT INTO symptoms (patient_id, symptom, severity, duration, frequency, ' .
            'symptom_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'isissss',
            $patientId,
            $symptom,
            $severity,
            $duration,
            $frequency,
            $date,
            $notes
        );
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function update(
        $id,
        $patientId,
        $symptom,
        $severity,
        $duration,
        $frequency,
        $date,
        $notes
    ) {
        $sql =
            'UPDATE symptoms SET symptom = ?, severity = ?, duration = ?, frequency = ' .
            '?, symptom_date = ?, notes = ? WHERE id = ? AND patient_id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'sissssii',
            $symptom,
            $severity,
            $duration,
            $frequency,
            $date,
            $notes,
            $id,
            $patientId
        );
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function delete($id, $patientId)
    {
        $sql = 'DELETE FROM symptoms WHERE id = ? AND patient_id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $id, $patientId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function find($id, $patientId)
    {
        $sql = 'SELECT * FROM symptoms WHERE id = ? AND patient_id = ? LIMIT 1';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $id, $patientId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row;
    }

    public function allForPatient($patientId)
    {
        $sql =
            'SELECT id, symptom, severity, duration, frequency, symptom_date, notes ' .
            'FROM symptoms WHERE patient_id = ? ORDER BY symptom_date DESC, id DESC';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $patientId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public function searchForPatient($patientId, $query = '')
    {
        if ($query === '') {
            return $this->allForPatient($patientId);
        }
        $like = '%' . $query . '%';
        $sql =
            'SELECT id, symptom, severity, duration, frequency, symptom_date, notes ' .
            'FROM symptoms WHERE patient_id = ? AND (symptom LIKE ? OR CAST(severity AS ' .
            'CHAR) LIKE ? OR duration LIKE ? OR frequency LIKE ? OR symptom_date LIKE ? ' .
            'OR notes LIKE ?) ORDER BY symptom_date DESC, id DESC';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'issssss',
            $patientId,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like
        );
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public function recentForPatient($patientId, $limit = 5)
    {
        $limit = max(1, min(20, (int) $limit));
        $sql = "SELECT symptom, severity, duration, frequency, symptom_date, notes FROM symptoms WHERE patient_id = ? ORDER BY symptom_date DESC, id DESC LIMIT $limit";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $patientId);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public function countForPatient($patientId)
    {
        $sql = 'SELECT COUNT(*) AS total FROM symptoms WHERE patient_id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $patientId);
        mysqli_stmt_execute($stmt);
        $count = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
        mysqli_stmt_close($stmt);
        return $count;
    }
}
