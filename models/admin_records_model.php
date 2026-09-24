<?php
class AdminRecordsModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function patients()
    {
        return mysqli_fetch_all(
            mysqli_query(
                $this->c,
                'SELECT id,name,phone,email,age,gender,blood_group,status FROM patients ORDER BY name'
            ),
            MYSQLI_ASSOC
        );
    }

    function allPatients($query = '')
    {
        $query = trim($query);
        if ($query === '') {
            return mysqli_fetch_all(
                mysqli_query(
                    $this->c,
                    'SELECT id,name,phone,email,age,gender,blood_group,status FROM patients ORDER BY id DESC'
                ),
                MYSQLI_ASSOC
            );
        }
        $like = '%' . $query . '%';
        $s = mysqli_prepare(
            $this->c,
            'SELECT id,name,phone,email,age,gender,blood_group,status FROM patients ' .
                "WHERE CONCAT('P',LPAD(id,4,'0')) LIKE ? OR name LIKE ? OR phone LIKE ? OR " .
                "COALESCE(email,'') LIKE ? OR CAST(age AS CHAR) LIKE ? ORDER BY id DESC " .
                'LIMIT 100'
        );
        mysqli_stmt_bind_param($s, 'sssss', $like, $like, $like, $like, $like);
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    }

    function allAppointments($query = '')
    {
        $base =
            'SELECT a.*,p.name patient_name,d.name doctor_name FROM appointments a JOIN ' .
            'patients p ON p.id=a.patient_id JOIN doctors d ON d.id=a.doctor_id';
        $query = trim($query);
        if ($query === '') {
            return mysqli_fetch_all(
                mysqli_query(
                    $this->c,
                    $base . ' ORDER BY a.appointment_date DESC,a.appointment_time DESC'
                ),
                MYSQLI_ASSOC
            );
        }
        $like = '%' . $query . '%';
        $sql =
            $base .
            (" WHERE CONCAT('D',LPAD(a.doctor_id,4,'0'),'-',LPAD(a.doctor_serial,3,'0')) " .
                'LIKE ? OR p.name LIKE ? OR d.name LIKE ? OR ' .
                "DATE_FORMAT(a.appointment_date,'%Y-%m-%d') LIKE ? OR " .
                "TIME_FORMAT(a.appointment_time,'%h:%i %p') LIKE ? OR COALESCE(a.reason,'') " .
                'LIKE ? ORDER BY a.appointment_date DESC,a.appointment_time DESC LIMIT 100');
        $s = mysqli_prepare($this->c, $sql);
        mysqli_stmt_bind_param($s, 'ssssss', $like, $like, $like, $like, $like, $like);
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    }

    function allFollowups($query = '')
    {
        $base =
            'SELECT f.*,p.name patient_name,d.name ' .
            'doctor_name,a.doctor_serial,a.doctor_id appointment_doctor_id FROM ' .
            'followups f JOIN patients p ON p.id=f.patient_id JOIN doctors d ON ' .
            'd.id=f.doctor_id LEFT JOIN appointments a ON a.id=f.appointment_id';
        $query = trim($query);
        if ($query === '') {
            return mysqli_fetch_all(
                mysqli_query($this->c, $base . ' ORDER BY f.followup_date DESC,f.id DESC'),
                MYSQLI_ASSOC
            );
        }
        $like = '%' . $query . '%';
        $sql =
            $base .
            (" WHERE CONCAT('F',LPAD(f.id,4,'0')) LIKE ? OR p.name LIKE ? OR d.name LIKE " .
                "? OR DATE_FORMAT(f.followup_date,'%Y-%m-%d') LIKE ? OR " .
                "COALESCE(f.purpose,'') LIKE ? ORDER BY f.followup_date DESC,f.id DESC " .
                'LIMIT 100');
        $s = mysqli_prepare($this->c, $sql);
        mysqli_stmt_bind_param($s, 'sssss', $like, $like, $like, $like, $like);
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    }

}
