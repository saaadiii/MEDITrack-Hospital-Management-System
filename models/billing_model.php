<?php
class BillingModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function billing($type = null, $query = '')
    {
        $base = "SELECT b.*,p.name patient_name,d.name doctor_name,s.name staff_name
         FROM billing b
         LEFT JOIN patients p ON p.id=b.patient_id
         LEFT JOIN doctors d ON d.id=b.doctor_id
         LEFT JOIN staff s ON s.id=b.staff_id";
        $query = trim($query);
        $where =
            '(p.name LIKE ? OR d.name LIKE ? OR s.name LIKE ? OR b.description LIKE ? ' .
            "OR b.payment_status LIKE ?
          OR DATE_FORMAT(b.transaction_date," .
            "'%Y-%m-%d') LIKE ? OR CAST(b.amount AS CHAR) LIKE ? OR CAST(b.paid AS " .
            "CHAR) LIKE ?
          OR CONCAT('B',LPAD(b.id,4,'0')) LIKE ? OR " .
            "CONCAT('P',LPAD(p.id,4,'0')) LIKE ?
          OR CONCAT('D',LPAD(d.id,4," .
            "'0')) LIKE ? OR CONCAT('S',LPAD(s.id,4,'0')) LIKE ?)";
        if ($type && $query !== '') {
            $like = '%' . $query . '%';
            $stmt = mysqli_prepare(
                $this->c,
                $base .
                    ' WHERE b.type=? AND ' .
                    $where .
                    ' ORDER BY b.transaction_date DESC,b.id DESC'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssssssss',
                $type,
                $like,
                $like,
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
        } elseif ($type) {
            $stmt = mysqli_prepare(
                $this->c,
                $base . ' WHERE b.type=? ORDER BY b.transaction_date DESC,b.id DESC'
            );
            mysqli_stmt_bind_param($stmt, 's', $type);
        } elseif ($query !== '') {
            $like = '%' . $query . '%';
            $stmt = mysqli_prepare(
                $this->c,
                $base . ' WHERE ' . $where . ' ORDER BY b.transaction_date DESC,b.id DESC'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssssss',
                $like,
                $like,
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
        } else {
            return mysqli_fetch_all(
                mysqli_query($this->c, $base . ' ORDER BY b.transaction_date DESC,b.id DESC'),
                MYSQLI_ASSOC
            );
        }
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }

    function saveBilling($d)
    {
        $id = (int) ($d['id'] ?? 0);
        $type = trim($d['type'] ?? '');
        if (!in_array($type, ['Patient Charge', 'Doctor Payment', 'Staff Payment'], true)) {
            return false;
        }
    
        $patientId = $type === 'Patient Charge' ? (int) ($d['patient_id'] ?? 0) : null;
        $doctorId = in_array($type, ['Patient Charge', 'Doctor Payment'], true)
            ? (int) ($d['doctor_id'] ?? 0)
            : null;
        $staffId = $type === 'Staff Payment' ? (int) ($d['staff_id'] ?? 0) : null;
        if ($type === 'Patient Charge' && (!$patientId || !$doctorId)) {
            return false;
        }
        if ($type === 'Doctor Payment' && !$doctorId) {
            return false;
        }
        if ($type === 'Staff Payment' && !$staffId) {
            return false;
        }
    
        $description = trim($d['description'] ?? '');
        $amount = (float) ($d['amount'] ?? 0);
        $paid = (float) ($d['paid'] ?? 0);
        $paymentStatus = $d['payment_status'] ?? 'Unpaid';
        $transactionDate = $d['transaction_date'] ?? '';
    
        if ($id) {
            $sql =
                'UPDATE billing SET ' .
                ('patient_id=?,doctor_id=?,staff_id=?,type=?,description=?,amount=?,paid=?,' .
                    'payment_status=?,transaction_date=? ') .
                'WHERE id=?';
            $s = mysqli_prepare($this->c, $sql);
            mysqli_stmt_bind_param(
                $s,
                'iiissddssi',
                $patientId,
                $doctorId,
                $staffId,
                $type,
                $description,
                $amount,
                $paid,
                $paymentStatus,
                $transactionDate,
                $id
            );
        } else {
            $sql =
                'INSERT INTO ' .
                'billing(patient_id,doctor_id,staff_id,type,description,amount,paid,payment_status,transaction_date) ' .
                'VALUES(?,?,?,?,?,?,?,?,?)';
            $s = mysqli_prepare($this->c, $sql);
            mysqli_stmt_bind_param(
                $s,
                'iiissddss',
                $patientId,
                $doctorId,
                $staffId,
                $type,
                $description,
                $amount,
                $paid,
                $paymentStatus,
                $transactionDate
            );
        }
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    function deleteBilling($id)
    {
        $s = mysqli_prepare($this->c, 'DELETE FROM billing WHERE id=?');
        mysqli_stmt_bind_param($s, 'i', $id);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

}
