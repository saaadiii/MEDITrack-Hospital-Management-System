<?php

class Patient
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function findByUserId($userId)
    {
        $sql =
            'SELECT id, name, phone, email, address, age, gender, emergency_contact, ' .
            'blood_group FROM patients WHERE user_id = ? LIMIT 1';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $patient = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $patient;
    }

    public function update(
        $patientId,
        $name,
        $phone,
        $address,
        $age,
        $gender,
        $emergencyContact,
        $bloodGroup
    ) {
        $sql =
            'UPDATE patients SET name = ?, phone = ?, address = ?, age = ?, gender = ?, ' .
            'emergency_contact = ?, blood_group = ? WHERE id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'sssisssi',
            $name,
            $phone,
            $address,
            $age,
            $gender,
            $emergencyContact,
            $bloodGroup,
            $patientId
        );
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}
