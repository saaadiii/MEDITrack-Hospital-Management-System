<?php
class EquipmentModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function equipmentTypes()
    {
        $r = mysqli_query($this->c, 'SELECT id,name FROM equipment_types ORDER BY name');
        return $r ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
    }

    function addEquipmentType($name)
    {
        $name = trim((string) $name);
        if ($name === '' || strlen($name) > 120) {
            return null;
        }
        $s = mysqli_prepare($this->c, 'INSERT IGNORE INTO equipment_types(name) VALUES(?)');
        mysqli_stmt_bind_param($s, 's', $name);
        mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        $s = mysqli_prepare($this->c, 'SELECT id,name FROM equipment_types WHERE name=? LIMIT 1');
        mysqli_stmt_bind_param($s, 's', $name);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $row ?: null;
    }

    private function equipmentType($name)
    {
        $s = mysqli_prepare($this->c, 'SELECT id,name FROM equipment_types WHERE name=? LIMIT 1');
        mysqli_stmt_bind_param($s, 's', $name);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $row ?: null;
    }

    function equipment()
    {
        $sql =
            'SELECT e.*,t.name AS name FROM equipment e ' .
            'INNER JOIN equipment_types t ON t.id=e.equipment_type_id ORDER BY e.id DESC';
        $r = mysqli_query($this->c, $sql);
        return $r ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
    }

    function searchEquipment($query = '')
    {
        $query = trim($query);
        if ($query === '') {
            return $this->equipment();
        }
        $like = '%' . $query . '%';
        $sql =
            'SELECT e.*,t.name AS name FROM equipment e ' .
            'INNER JOIN equipment_types t ON t.id=e.equipment_type_id ' .
            'WHERE e.equipment_code LIKE ? OR t.name LIKE ? OR e.department LIKE ? ' .
            'ORDER BY e.id DESC LIMIT 100';
        $s = mysqli_prepare($this->c, $sql);
        mysqli_stmt_bind_param($s, 'sss', $like, $like, $like);
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    }

    private function reserveEquipmentSequence($prefix, $quantity)
    {
        $quantity = max(1, (int) $quantity);
        $s = mysqli_prepare(
            $this->c,
            'INSERT IGNORE INTO equipment_sequences(department_code,last_number) VALUES(?,0)'
        );
        mysqli_stmt_bind_param($s, 's', $prefix);
        mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        $s = mysqli_prepare(
            $this->c,
            'SELECT last_number FROM equipment_sequences WHERE department_code=? FOR UPDATE'
        );
        mysqli_stmt_bind_param($s, 's', $prefix);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        $last = (int) ($row['last_number'] ?? 0);
        $newLast = $last + $quantity;
        $s = mysqli_prepare(
            $this->c,
            'UPDATE equipment_sequences SET last_number=? WHERE department_code=?'
        );
        mysqli_stmt_bind_param($s, 'is', $newLast, $prefix);
        if (!mysqli_stmt_execute($s)) {
            mysqli_stmt_close($s);
            throw new Exception('Could not reserve equipment IDs.');
        }
        mysqli_stmt_close($s);
        return $last + 1;
    }

    function saveEquipment($d)
    {
        $id = (int) ($d['id'] ?? 0);
        $name = trim($d['name'] ?? '');
        $department = trim($d['department'] ?? '');
        $purchaseDate = trim($d['purchase_date'] ?? '');
        $condition = $d['condition_status'] ?? 'Good';
        $type = $this->equipmentType($name);
        if (!$type || !hospital_department_code($department)) {
            return false;
        }
        if (!in_array($condition, ['Good', 'Needs Maintenance', 'Out of Service'], true)) {
            return false;
        }
        $equipmentTypeId = (int) $type['id'];

        if ($id) {
            $s = mysqli_prepare(
                $this->c,
                'SELECT department,equipment_code FROM equipment WHERE id=? LIMIT 1'
            );
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s);
            $current = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if (!$current) {
                return false;
            }
            if ((string) $current['department'] !== $department) {
                $prefix = hospital_department_code($department);
                mysqli_begin_transaction($this->c);
                try {
                    $seq = $this->reserveEquipmentSequence($prefix, 1);
                    $newCode = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                    $s = mysqli_prepare(
                        $this->c,
                        'UPDATE equipment SET equipment_code=?,equipment_type_id=?,department=?,purchase_date=?,condition_status=? WHERE id=?'
                    );
                    mysqli_stmt_bind_param(
                        $s,
                        'sisssi',
                        $newCode,
                        $equipmentTypeId,
                        $department,
                        $purchaseDate,
                        $condition,
                        $id
                    );
                    if (!mysqli_stmt_execute($s)) {
                        throw new Exception(mysqli_stmt_error($s));
                    }
                    mysqli_stmt_close($s);
                    mysqli_commit($this->c);
                    return true;
                } catch (Throwable $e) {
                    mysqli_rollback($this->c);
                    return false;
                }
            }
            $s = mysqli_prepare(
                $this->c,
                'UPDATE equipment SET equipment_type_id=?,department=?,purchase_date=?,condition_status=? WHERE id=?'
            );
            mysqli_stmt_bind_param(
                $s,
                'isssi',
                $equipmentTypeId,
                $department,
                $purchaseDate,
                $condition,
                $id
            );
            $ok = mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            return $ok;
        }

        $quantity = max(1, min(100, (int) ($d['quantity'] ?? 1)));
        $prefix = hospital_department_code($department);
        mysqli_begin_transaction($this->c);
        try {
            $seq = $this->reserveEquipmentSequence($prefix, $quantity);
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO equipment(equipment_code,equipment_type_id,department,purchase_date,condition_status) VALUES(?,?,?,?,?)'
            );
            for ($i = 0; $i < $quantity; $i++) {
                $code = $prefix . str_pad((string) ($seq + $i), 3, '0', STR_PAD_LEFT);
                mysqli_stmt_bind_param(
                    $s,
                    'sisss',
                    $code,
                    $equipmentTypeId,
                    $department,
                    $purchaseDate,
                    $condition
                );
                if (!mysqli_stmt_execute($s)) {
                    throw new Exception(mysqli_stmt_error($s));
                }
            }
            mysqli_stmt_close($s);
            mysqli_commit($this->c);
            return true;
        } catch (Throwable $e) {
            mysqli_rollback($this->c);
            return false;
        }
    }

    function deleteEquipment($id)
    {
        $s = mysqli_prepare($this->c, 'DELETE FROM equipment WHERE id=?');
        mysqli_stmt_bind_param($s, 'i', $id);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }
}
