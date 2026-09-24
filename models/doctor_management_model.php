<?php
class DoctorManagementModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function doctors()
    {
        return mysqli_fetch_all(
            mysqli_query($this->c, 'SELECT * FROM doctors ORDER BY id DESC'),
            MYSQLI_ASSOC
        );
    }

    function searchDoctors($query = '')
    {
        $query = trim($query);
        if ($query === '') {
            return $this->doctors();
        }
        $like = '%' . $query . '%';
        $sql = "SELECT * FROM doctors d
        WHERE d.name LIKE ? OR d.specialization LIKE ? OR d.phone LIKE ? OR d.email LIKE ?
           OR d.department LIKE ? OR d.status LIKE ? OR CONCAT('D',LPAD(d.id,4,'0')) LIKE ?
        ORDER BY d.id DESC LIMIT 100";
        $s = mysqli_prepare($this->c, $sql);
        mysqli_stmt_bind_param($s, 'sssssss', $like, $like, $like, $like, $like, $like, $like);
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    }

    function setDoctorStatus($id, $status)
    {
        if (!in_array($status, ['Active', 'Inactive'], true)) {
            return false;
        }
        $s = mysqli_prepare($this->c, 'UPDATE doctors SET status=? WHERE id=?');
        mysqli_stmt_bind_param($s, 'si', $status, $id);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

}
