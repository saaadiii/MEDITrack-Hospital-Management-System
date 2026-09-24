<?php
class PatientManagementModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function patients($q = '')
    {
        $sql = 'SELECT * FROM patients';
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .=
                " WHERE name LIKE ? OR phone LIKE ? OR COALESCE(email,'') LIKE ? OR " .
                "CAST(age AS CHAR) LIKE ? OR COALESCE(gender,'') LIKE ? OR " .
                "COALESCE(blood_group,'') LIKE ? OR status LIKE ? OR COALESCE(address,'') " .
                "LIKE ? OR COALESCE(emergency_contact,'') LIKE ? OR " .
                "CONCAT('P',LPAD(id,4,'0')) LIKE ?";
        }
        $sql .= ' ORDER BY id DESC';
        $st = mysqli_prepare($this->c, $sql);
        if ($q !== '') {
            mysqli_stmt_bind_param(
                $st,
                'ssssssssss',
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
        }
        mysqli_stmt_execute($st);
        $r = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
        mysqli_stmt_close($st);
        return $r;
    }

    function patient($id)
    {
        $s = mysqli_prepare($this->c, 'SELECT * FROM patients WHERE id=?');
        mysqli_stmt_bind_param($s, 'i', $id);
        mysqli_stmt_execute($s);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $r;
    }

    function savePatient($d)
    {
        $id = (int) ($d['id'] ?? 0);
        if ($id) {
            $s = mysqli_prepare(
                $this->c,
                'UPDATE patients SET name=?,phone=?,email=?,address=?,age=?,gender=?,emergency_contact=?,status=? WHERE id=?'
            );
            mysqli_stmt_bind_param(
                $s,
                'ssssisssi',
                $d['name'],
                $d['phone'],
                $d['email'],
                $d['address'],
                $d['age'],
                $d['gender'],
                $d['emergency_contact'],
                $d['status'],
                $id
            );
        } else {
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO patients(name,phone,email,address,age,gender,emergency_contact,status) VALUES(?,?,?,?,?,?,?,?)'
            );
            mysqli_stmt_bind_param(
                $s,
                'ssssisss',
                $d['name'],
                $d['phone'],
                $d['email'],
                $d['address'],
                $d['age'],
                $d['gender'],
                $d['emergency_contact'],
                $d['status']
            );
        }
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    function deletePatient($id)
    {
        $s = mysqli_prepare($this->c, "UPDATE patients SET status='Inactive' WHERE id=?");
        mysqli_stmt_bind_param($s, 'i', $id);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    function searchPatients($q = '', $activeOnly = false, $limit = 50)
    {
        $q = trim((string) $q);
        $limit = max(1, min((int) $limit, 200));

        $base =
            "SELECT id,name,phone,email,age,gender,blood_group,status,address,emergency_contact " .
            "FROM patients";
        $where = [];
        $params = [];
        $types = '';

        if ($activeOnly) {
            $where[] = "status='Active'";
        }

        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] =
                "(name LIKE ? OR phone LIKE ? OR COALESCE(email,'') LIKE ? OR CAST(age AS CHAR) " .
                "LIKE ? OR COALESCE(gender,'') LIKE ? OR COALESCE(blood_group,'') LIKE ? OR " .
                "status LIKE ? OR COALESCE(address,'') LIKE ? OR COALESCE(emergency_contact,'') " .
                "LIKE ? OR CONCAT('P',LPAD(id,4,'0')) LIKE ?)";
            $types = 'ssssssssss';
            $params = array_fill(0, 10, $like);
        }

        $sql =
            $base .
            ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
            ' ORDER BY name LIMIT ' .
            $limit;

        $stmt = mysqli_prepare($this->c, $sql);
        if ($params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }
}
