<?php
class EmergencyModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function emergencies($q = '')
    {
        // Build a short display code from admission order: first admitted = ER-001.
        // Keep the database emergency_code untouched for backwards compatibility.
        $r = mysqli_query(
            $this->c,
            'SELECT * FROM emergency_registrations ORDER BY arrival_time ASC,id ASC'
        );
        $rows = mysqli_fetch_all($r, MYSQLI_ASSOC);
        foreach ($rows as $i => &$row) {
            $row['display_code'] = 'ER-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
        }
        unset($row);
        if ($q !== '') {
            $needle = strtolower(trim($q));
            $rows = array_values(
                array_filter($rows, function ($row) use ($needle) {
                    foreach (
                        [
                            'display_code',
                            'patient_name',
                            'emergency_type',
                            'status',
                            'phone',
                            'emergency_contact'
                        ]
                        as $key
                    ) {
                        if (stripos((string) ($row[$key] ?? ''), $needle) !== false) {
                            return true;
                        }
                    }
                    return false;
                })
            );
        }
        return array_reverse($rows);
    }

    function saveEmergency($d)
    {
        $id = (int) ($d['id'] ?? 0);
        if ($id) {
            $s = mysqli_prepare(
                $this->c,
                'UPDATE emergency_registrations SET ' .
                    'patient_name=?,age=?,gender=?,phone=?,emergency_contact=?,emergency_type=?,arrival_time=?,notes=?,status=? ' .
                    'WHERE id=?'
            );
            mysqli_stmt_bind_param(
                $s,
                'sisssssssi',
                $d['patient_name'],
                $d['age'],
                $d['gender'],
                $d['phone'],
                $d['emergency_contact'],
                $d['emergency_type'],
                $d['arrival_time'],
                $d['notes'],
                $d['status'],
                $id
            );
        } else {
            $code = 'ER-' . date('YmdHis') . '-' . random_int(100, 999);
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO ' .
                    ('emergency_registrations(emergency_code,patient_name,age,gender,phone,' .
                        'emergency_contact,emergency_type,arrival_time,notes,status) ') .
                    'VALUES(?,?,?,?,?,?,?,?,?,?)'
            );
            mysqli_stmt_bind_param(
                $s,
                'ssisssssss',
                $code,
                $d['patient_name'],
                $d['age'],
                $d['gender'],
                $d['phone'],
                $d['emergency_contact'],
                $d['emergency_type'],
                $d['arrival_time'],
                $d['notes'],
                $d['status']
            );
        }
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    function deleteEmergency($id)
    {
        $s = mysqli_prepare(
            $this->c,
            "UPDATE emergency_registrations SET status='Cancelled' WHERE id=?"
        );
        mysqli_stmt_bind_param($s, 'i', $id);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

}
