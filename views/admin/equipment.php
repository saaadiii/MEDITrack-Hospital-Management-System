<?php
$title = 'Equipment Management';
$current = 'admin_equipment';
$heading = 'Equipment Management';
$subheading = 'Track reusable hospital equipment and condition';
require __DIR__ . '/_page_top.php';
?>
<section
    class="panel"
    id="admin-equipment-form-panel"
>
    <div class="panel-header">
        <div>
            <h2 id="admin-equipment-form-title">Medical Equipment</h2>
            <p>Add equipment as individual hospital assets.</p>
        </div>
    </div>
    <form
        method="POST"
        class="form-grid"
        id="admin-equipment-form"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= esc(
                 csrf_token()
             ) ?>"
        >
        <input
            type="hidden"
            name="action"
            value="equipment_save"
        >
        <input
            type="hidden"
            name="section"
            value="equipment"
        >
        <input
            type="hidden"
            name="id"
            value=""
        >
        <div class="form-group">
            <label>Equipment</label>
            <select
                name="name"
                id="admin-equipment-name"
                required
            >
                <option value="">Select equipment</option><?php foreach (
                      $equipmentTypes
                      as $type
                  ): ?>
                <option value="<?= esc($type['name']) ?>"><?= esc(
                    $type['name']
                ) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Department</label>
            <select
                name="department"
                required
            >
                <option value="">Select department</option><?php foreach (
                      hospital_departments()
                      as $department
                  ): ?>
                <option value="<?= esc($department) ?>"><?= esc(
                    $department
                ) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div
            class="form-group"
            id="admin-equipment-quantity-group"
        >
            <label>Quantity</label>
            <input
                type="number"
                min="1"
                max="100"
                name="quantity"
                value="1"
                required
            >
        </div>
        <div class="form-group">
            <label>Purchase date</label>
            <input
                type="date"
                name="purchase_date"
                max="<?= date(
                      'Y-m-d'
                  ) ?>"
                data-date-rule="past-or-today"
            >
        </div>
        <div class="form-group">
            <label>Condition</label>
            <select name="condition_status">
                <option>Good</option>
                <option>Needs Maintenance</option>
                <option>Out of Service</option>
            </select>
        </div>
        <div class="full form-actions">
            <button
                class="small-btn"
                id="admin-equipment-submit"
            >Add Equipment</button>
            <button
                type="button"
                class="action-btn secondary"
                id="admin-equipment-cancel-edit"
                hidden
            >Cancel Edit</button>
        </div>
    </form>
    <div
        class="catalog-subform"
        id="admin-equipment-type-add"
    >
        <div class="catalog-subform-heading">
            <h3>Add Equipment Type</h3>
        </div>
        <div class="catalog-subform-fields">
            <input
                type="text"
                id="admin-new-equipment-type"
                maxlength="120"
                placeholder="Equipment name"
            >
            <button
                type="button"
                class="small-btn"
                id="admin-add-equipment-type"
            >Add</button><span
                class="catalog-message"
                id="admin-equipment-type-message"
            ></span>
        </div>
    </div>
</section>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Equipment Records</h2>
        </div>
    </div>
    <div class="toolbar admin-search-toolbar">
        <input
            id="admin-equipment-search"
            type="search"
            placeholder="Search by equipment ID, equipment name or department"
            autocomplete="off"
        >
        <button
            id="admin-equipment-search-button"
            type="button"
            class="small-btn secondary"
        >Search</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Equipment ID</th>
                    <th>Equipment</th>
                    <th>Department</th>
                    <th>Purchase Date</th>
                    <th>Condition</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody
                id="admin-equipment-table-body"
                data-empty-message="No matching equipment records found."
            ><?php foreach (
                 $equipment
                 as $e
             ): ?>
                <tr>
                    <td><?= esc(
                        $e['equipment_code'] ?? 'E' . str_pad((string) (int) $e['id'], 4, '0', STR_PAD_LEFT)
                    ) ?></td>
                    <td><?= esc($e['name']) ?></td>
                    <td><?= esc($e['department'] ?? '—') ?></td>
                    <td><?= esc(
                        $e['purchase_date'] ?? '—'
                    ) ?></td>
                    <td><?= esc(
                        $e['condition_status']
                    ) ?></td>
                    <td class="actions">
                        <button
                            type="button"
                            class="action-btn secondary admin-equipment-edit"
                            data-id="<?= $e[
                                'id'
                            ] ?>"
                            data-name="<?= esc($e['name']) ?>"
                            data-department="<?= esc(
                                $e['department'] ?? ''
                            ) ?>"
                            data-date="<?= esc($e['purchase_date'] ?? '') ?>"
                            data-condition="<?= esc(
                                $e['condition_status']
                            ) ?>"
                        >Edit</button>
                        <form
                            method="POST"
                            class="inline-form"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= esc(
                                    csrf_token()
                                ) ?>"
                            >
                            <input
                                type="hidden"
                                name="action"
                                value="equipment_delete"
                            >
                            <input
                                type="hidden"
                                name="section"
                                value="equipment"
                            >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= $e[
                                    'id'
                                ] ?>"
                            >
                            <button class="action-btn danger">Delete</button>
                        </form>
                    </td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../shared/bottom.php'; ?>
