<?php
class InventoryModel
{
    private $c;

    function __construct($c)
    {
        $this->c = $c;
    }

    function stockItemTypes()
    {
        $r = mysqli_query($this->c, 'SELECT id,name,category FROM stock_item_types ORDER BY name');
        return $r ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
    }

    function addStockItemType($name, $category)
    {
        $name = trim((string) $name);
        $category = trim((string) $category);
        if ($name === '' || strlen($name) > 120 || !in_array($category, stock_categories(), true)) {
            return null;
        }
        $s = mysqli_prepare(
            $this->c,
            'INSERT IGNORE INTO stock_item_types(name,category) VALUES(?,?)'
        );
        mysqli_stmt_bind_param($s, 'ss', $name, $category);
        mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        $s = mysqli_prepare(
            $this->c,
            'SELECT id,name,category FROM stock_item_types WHERE name=? LIMIT 1'
        );
        mysqli_stmt_bind_param($s, 's', $name);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $row ?: null;
    }

    private function stockItemType($name)
    {
        $s = mysqli_prepare(
            $this->c,
            'SELECT id,name,category FROM stock_item_types WHERE name=? LIMIT 1'
        );
        mysqli_stmt_bind_param($s, 's', $name);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $row ?: null;
    }

    function inventory()
    {
        return mysqli_fetch_all(
            mysqli_query(
                $this->c,
                "SELECT *,CASE WHEN quantity<=minimum_level THEN 'Low Stock' ELSE 'In " .
                    "Stock' END stock_status,DATE_FORMAT(updated_at,'%d %b %Y, %h:%i %p') " .
                    'updated_at_display FROM inventory ORDER BY item_name'
            ),
            MYSQLI_ASSOC
        );
    }

    function searchInventory($query = '')
    {
        $query = trim($query);
        if ($query === '') {
            return $this->inventory();
        }
        $like = '%' . $query . '%';
        $sql =
            "SELECT *,CASE WHEN quantity<=minimum_level THEN 'Low Stock' ELSE 'In " .
            "Stock' END stock_status,DATE_FORMAT(updated_at,'%d %b %Y, %h:%i %p') " .
            "updated_at_display
        FROM inventory
        WHERE item_name LIKE ? " .
            'OR category LIKE ? OR supplier LIKE ? OR CAST(quantity AS CHAR) LIKE ? OR ' .
            "CAST(minimum_level AS CHAR) LIKE ?
           OR DATE_FORMAT(updated_at," .
            "'%Y-%m-%d %H:%i') LIKE ? OR DATE_FORMAT(updated_at,'%d %b %Y, %h:%i %p') " .
            "LIKE ?
           OR CONCAT('I',LPAD(id,4,'0')) LIKE ?
        ORDER BY " .
            'item_name LIMIT 100';
        $s = mysqli_prepare($this->c, $sql);
        mysqli_stmt_bind_param(
            $s,
            'ssssssss',
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like
        );
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    }

    private function writeInventoryHistory($id, $action, $old, $new, $changedAt = null)
    {
        $oldItem = $old['item_name'] ?? null;
        $newItem = $new['item_name'] ?? null;
        $oldCategory = $old['category'] ?? null;
        $newCategory = $new['category'] ?? null;
        $oldQty = array_key_exists('quantity', $old) ? (int) $old['quantity'] : null;
        $newQty = array_key_exists('quantity', $new) ? (int) $new['quantity'] : null;
        $oldMin = array_key_exists('minimum_level', $old) ? (int) $old['minimum_level'] : null;
        $newMin = array_key_exists('minimum_level', $new) ? (int) $new['minimum_level'] : null;
        $oldSupplier = $old['supplier'] ?? null;
        $newSupplier = $new['supplier'] ?? null;
        if ($changedAt) {
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO ' .
                    ('inventory_history(inventory_id,action_type,old_item_name,new_item_name,' .
                        'old_category,new_category,old_quantity,new_quantity,old_minimum_level,' .
                        'new_minimum_level,old_supplier,new_supplier,changed_at) ') .
                    'VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            mysqli_stmt_bind_param(
                $s,
                'isssssiiiisss',
                $id,
                $action,
                $oldItem,
                $newItem,
                $oldCategory,
                $newCategory,
                $oldQty,
                $newQty,
                $oldMin,
                $newMin,
                $oldSupplier,
                $newSupplier,
                $changedAt
            );
        } else {
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO ' .
                    ('inventory_history(inventory_id,action_type,old_item_name,new_item_name,' .
                        'old_category,new_category,old_quantity,new_quantity,old_minimum_level,' .
                        'new_minimum_level,old_supplier,new_supplier) ') .
                    'VALUES(?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            mysqli_stmt_bind_param(
                $s,
                'isssssiiiiss',
                $id,
                $action,
                $oldItem,
                $newItem,
                $oldCategory,
                $newCategory,
                $oldQty,
                $newQty,
                $oldMin,
                $newMin,
                $oldSupplier,
                $newSupplier
            );
        }
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

    function saveInventory($d)
    {
        $id = (int) ($d['id'] ?? 0);
        $itemName = trim($d['item_name'] ?? '');
        $type = $this->stockItemType($itemName);
        if (!$type) {
            return false;
        }
        $new = [
            'item_name' => $type['name'],
            'category' => $type['category'],
            'quantity' => max(0, (int) ($d['quantity'] ?? 0)),
            'minimum_level' => max(0, (int) ($d['minimum_level'] ?? 0)),
            'supplier' => trim($d['supplier'] ?? '')
        ];
        if ($id) {
            $s = mysqli_prepare(
                $this->c,
                'SELECT item_name,category,quantity,minimum_level,supplier FROM inventory WHERE id=? LIMIT 1'
            );
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s);
            $old = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if (!$old) {
                return false;
            }
            $changed =
                (string) $old['item_name'] !== $new['item_name'] ||
                (string) $old['category'] !== $new['category'] ||
                (int) $old['quantity'] !== $new['quantity'] ||
                (int) $old['minimum_level'] !== $new['minimum_level'] ||
                (string) ($old['supplier'] ?? '') !== $new['supplier'];
            $s = mysqli_prepare(
                $this->c,
                'UPDATE inventory SET item_name=?,category=?,quantity=?,minimum_level=?,supplier=? WHERE id=?'
            );
            mysqli_stmt_bind_param(
                $s,
                'ssiisi',
                $new['item_name'],
                $new['category'],
                $new['quantity'],
                $new['minimum_level'],
                $new['supplier'],
                $id
            );
            $ok = mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            if ($ok && $changed) {
                $this->writeInventoryHistory($id, 'Updated', $old, $new);
            }
            return $ok;
        } else {
            $s = mysqli_prepare(
                $this->c,
                'INSERT INTO inventory(item_name,category,quantity,minimum_level,supplier) VALUES(?,?,?,?,?)'
            );
            mysqli_stmt_bind_param(
                $s,
                'ssiis',
                $new['item_name'],
                $new['category'],
                $new['quantity'],
                $new['minimum_level'],
                $new['supplier']
            );
            $ok = mysqli_stmt_execute($s);
            $newId = (int) mysqli_insert_id($this->c);
            mysqli_stmt_close($s);
            if ($ok) {
                $this->writeInventoryHistory($newId, 'Created', [], $new);
            }
            return $ok;
        }
    }

    function inventoryHistory($id)
    {
        $s = mysqli_prepare(
            $this->c,
            "SELECT h.*,DATE_FORMAT(h.changed_at,'%d %b %Y, %h:%i %p') " .
                'changed_at_display FROM inventory_history h WHERE h.inventory_id=? ORDER ' .
                'BY h.changed_at DESC,h.id DESC'
        );
        mysqli_stmt_bind_param($s, 'i', $id);
        mysqli_stmt_execute($s);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    }

    function inventoryById($id)
    {
        $s = mysqli_prepare(
            $this->c,
            "SELECT *,DATE_FORMAT(updated_at,'%d %b %Y, %h:%i %p') updated_at_display FROM inventory WHERE id=? LIMIT 1"
        );
        mysqli_stmt_bind_param($s, 'i', $id);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        return $row ?: null;
    }

    function deleteInventory($id)
    {
        $s = mysqli_prepare($this->c, 'DELETE FROM inventory WHERE id=?');
        mysqli_stmt_bind_param($s, 'i', $id);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        return $ok;
    }

}
