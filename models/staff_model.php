<?php
class StaffModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function staff()
    {
        return mysqli_fetch_all(
            mysqli_query($this->c, 'SELECT * FROM staff ORDER BY id DESC'),
            MYSQLI_ASSOC
        );
    }

    function searchStaff($query = '')
    {
        $query = trim($query);
        if ($query === '') {
            return $this->staff();
        }
        $like = '%' . $query . '%';
        $sql =
            'SELECT * FROM staff WHERE name LIKE ? OR staff_role LIKE ? OR phone LIKE ? ' .
            'OR email LIKE ? OR department LIKE ? OR status LIKE ? OR ' .
            "CONCAT('S',LPAD(id,4,'0')) LIKE ? ORDER BY id DESC LIMIT 100";
        $s = mysqli_prepare($this->c, $sql);
        mysqli_stmt_bind_param($s, 'sssssss', $like, $like, $like, $like, $like, $like, $like);
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    }

    function saveStaff($d)
    {
        $id = (int) ($d['id'] ?? 0);
        if ($id) {
            $s = mysqli_prepare(
                $this->c,
                'UPDATE staff SET name=?,staff_role=?,phone=?,email=?,department=?,status=? WHERE id=?'
            );
            mysqli_stmt_bind_param(
                $s,
                'ssssssi',
                $d['name'],
                $d['staff_role'],
                $d['phone'],
                $d['email'],
                $d['department'],
                $d['status'],
                $id
            );
        } else {
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO staff(name,staff_role,phone,email,department,status) VALUES(?,?,?,?,?,?)'
            );
            mysqli_stmt_bind_param(
                $s,
                'ssssss',
                $d['name'],
                $d['staff_role'],
                $d['phone'],
                $d['email'],
                $d['department'],
                $d['status']
            );
        }
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    function deleteStaff($id)
    {
        $s = mysqli_prepare($this->c, "UPDATE staff SET status='Inactive' WHERE id=?");
        mysqli_stmt_bind_param($s, 'i', $id);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

}
