<?php
class User
{
    private $conn;
    public $lastError = '';
    function __construct($conn)
    {
        $this->conn = $conn;
    }
    function findByLogin($login, $role)
    {
        $login = trim($login);
        if ($role === 'patient') {
            $sql =
                'SELECT u.id user_id,u.email,u.password,u.role,p.id ' .
                'patient_id,p.name,p.phone FROM users u JOIN patients p ON p.user_id=u.id ' .
                "WHERE u.role='patient' AND (u.email=? OR p.phone=?) LIMIT 1";
            $s = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($s, 'ss', $login, $login);
        } elseif ($role === 'doctor') {
            $sql =
                'SELECT u.id user_id,u.email,u.password,u.role,d.id ' .
                'doctor_id,d.name,d.specialization FROM users u JOIN doctors d ON ' .
                "d.user_id=u.id WHERE u.role='doctor' AND u.email=? LIMIT 1";
            $s = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($s, 's', $login);
        } else {
            $sql =
                'SELECT id user_id,email,password,role FROM users WHERE role=? AND LOWER(email)=LOWER(?) LIMIT 1';
            $s = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($s, 'ss', $role, $login);
        }
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s)) ?: null;
        mysqli_stmt_close($s);
        return $row;
    }
    function emailExists($email)
    {
        $email = trim($email);
        $s = mysqli_prepare(
            $this->conn,
            'SELECT id FROM users WHERE LOWER(email)=LOWER(?) LIMIT 1'
        );
        mysqli_stmt_bind_param($s, 's', $email);
        mysqli_stmt_execute($s);
        $ok = mysqli_num_rows(mysqli_stmt_get_result($s)) > 0;
        mysqli_stmt_close($s);
        return $ok;
    }
    function createAccount($name, $email, $password, $role)
    {
        $this->lastError = '';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!mysqli_begin_transaction($this->conn)) {
            $this->lastError = mysqli_error($this->conn);
            return false;
        }
        try {
            $s = mysqli_prepare(
                $this->conn,
                'INSERT INTO users(email,password,role) VALUES(?,?,?)'
            );
            if (!$s) {
                throw new Exception(mysqli_error($this->conn));
            }
            mysqli_stmt_bind_param($s, 'sss', $email, $hash, $role);
            if (!mysqli_stmt_execute($s)) {
                throw new Exception(mysqli_stmt_error($s));
            }
            $uid = mysqli_insert_id($this->conn);
            mysqli_stmt_close($s);
            if ($role === 'patient') {
                $phone = '';
                $address = '';
                $s = mysqli_prepare(
                    $this->conn,
                    'INSERT INTO patients(user_id,name,phone,email,address) VALUES(?,?,?,?,?)'
                );
                if (!$s) {
                    throw new Exception(mysqli_error($this->conn));
                }
                mysqli_stmt_bind_param($s, 'issss', $uid, $name, $phone, $email, $address);
                if (!mysqli_stmt_execute($s)) {
                    throw new Exception(mysqli_stmt_error($s));
                }
                mysqli_stmt_close($s);
            } elseif ($role === 'doctor') {
                $specialization = 'General Medicine';
                $s = mysqli_prepare(
                    $this->conn,
                    'INSERT INTO doctors(user_id,name,specialization,email) VALUES(?,?,?,?)'
                );
                if (!$s) {
                    throw new Exception(mysqli_error($this->conn));
                }
                mysqli_stmt_bind_param($s, 'isss', $uid, $name, $specialization, $email);
                if (!mysqli_stmt_execute($s)) {
                    throw new Exception(mysqli_stmt_error($s));
                }
                mysqli_stmt_close($s);
            }
            if (!mysqli_commit($this->conn)) {
                throw new Exception(mysqli_error($this->conn));
            }
            return true;
        } catch (Throwable $e) {
            mysqli_rollback($this->conn);
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
