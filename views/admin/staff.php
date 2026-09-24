<?php
$title = 'Staff Management';
$current = 'admin_staff';
$heading = 'Staff Management';
$subheading = 'Add, search and maintain hospital staff records';
require __DIR__ . '/_page_top.php';
?>
<section class="two-col">
    <section
        class="panel"
        id="admin-staff-form-panel"
    >
        <h2 id="admin-staff-form-title">Add Staff</h2>
        <form
            method="POST"
            id="admin-staff-form"
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
                value="staff_save"
            >
            <input
                type="hidden"
                name="section"
                value="staff"
            >
            <input
                type="hidden"
                name="id"
                value=""
            >
            <div class="form-group">
                <label>Name</label>
                <input
                    name="name"
                    required
                >
            </div>
            <div class="form-group">
                <label>Role</label>
                <select
                    name="staff_role"
                    required
                >
                    <option value="">Select role</option><?php foreach (
                           $staffRoleOptions
                           as $role
                       ): ?>
                    <option value="<?= esc($role) ?>"><?= esc(
                        $role
                    ) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input name="phone">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input name="email">
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department">
                    <option value="">Select department</option><?php foreach (
                           hospital_departments()
                           as $department
                       ): ?>
                    <option value="<?= esc($department) ?>"><?= esc(
                        $department
                    ) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option>Active</option>
                    <option>Inactive</option>
                </select>
            </div>
            <div class="form-actions">
                <button
                    class="small-btn"
                    id="admin-staff-submit"
                >Add Staff</button>
                <button
                    type="button"
                    class="action-btn secondary"
                    id="admin-staff-cancel-edit"
                    hidden
                >Cancel Edit</button>
            </div>
        </form>
    </section>
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Staff Records</h2>
                <p>Search by staff ID, name, role, department or status.</p>
            </div>
        </div>
        <div class="toolbar admin-search-toolbar">
            <input
                id="admin-staff-search"
                type="search"
                placeholder="Search by staff ID, name, role, phone, email, department or status"
                autocomplete="off"
            >
            <button
                id="admin-staff-search-button"
                type="button"
                class="small-btn secondary"
            >Search</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody
                    id="admin-staff-table-body"
                    data-empty-message="No matching staff records found."
                ><?php foreach (
                      $staff
                      as $s
                  ): ?>
                    <tr>
                        <td>S<?= str_pad((string) (int) $s['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= esc(
                            $s['name']
                        ) ?></td>
                        <td><?= esc($s['staff_role']) ?></td>
                        <td><?= esc($s['phone'] ?? '—') ?></td>
                        <td><?= esc(
                            $s['email'] ?? '—'
                        ) ?></td>
                        <td><?= esc($s['department'] ?? '—') ?></td>
                        <td><?= esc(
                            $s['status']
                        ) ?></td>
                        <td class="actions compact-actions">
                            <button
                                type="button"
                                class="action-btn secondary compact-action admin-staff-edit"
                                data-id="<?= $s[
                                    'id'
                                ] ?>"
                                data-name="<?= esc($s['name']) ?>"
                                data-role="<?= esc(
                                    $s['staff_role']
                                ) ?>"
                                data-phone="<?= esc($s['phone'] ?? '') ?>"
                                data-email="<?= esc(
                                    $s['email'] ?? ''
                                ) ?>"
                                data-department="<?= esc($s['department'] ?? '') ?>"
                                data-status="<?= esc(
                                    $s['status']
                                ) ?>"
                            >Edit</button> <?php if (
                                $s['status'] === 'Active'
                            ): ?>
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
                                    value="staff_delete"
                                >
                                <input
                                    type="hidden"
                                    name="section"
                                    value="staff"
                                >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $s[
                                        'id'
                                    ] ?>"
                                >
                                <button class="action-btn danger compact-action">Deactivate</button>
                            </form><?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<?php require __DIR__ . '/../shared/bottom.php'; ?>
