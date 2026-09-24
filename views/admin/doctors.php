<?php
$title = 'Doctor Management';
$current = 'admin_doctors';
$heading = 'Doctor Management';
$subheading = 'Search and manage registered doctors';
require __DIR__ . '/_page_top.php';
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Registered Doctors</h2>
            <p>Doctors create their own accounts. Admin can search, activate or deactivate
                registered doctors.</p>
        </div>
    </div>
    <div class="toolbar admin-doctor-toolbar admin-search-toolbar">
        <input
            id="admin-doctor-search"
            type="search"
            placeholder="Search by doctor ID, name, specialization, phone, email, department or status"
            autocomplete="off"
        >
        <button
            id="admin-doctor-search-button"
            type="button"
            class="small-btn secondary"
        >Search</button>
    </div>
    <input
        type="hidden"
        id="admin-doctor-csrf"
        value="<?= esc(csrf_token()) ?>"
    >
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Doctor ID</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody
                id="admin-doctor-table-body"
                data-empty-message="No matching doctors found."
            ><?php foreach (
                  $doctors
                  as $d
              ): ?>
                <tr>
                    <td>D<?= str_pad((string) (int) $d['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= esc(
                        $d['name']
                    ) ?></td>
                    <td><?= esc($d['specialization']) ?></td>
                    <td><?= esc(
                        $d['phone'] ?? '—'
                    ) ?></td>
                    <td><?= esc($d['email'] ?? '—') ?></td>
                    <td><?= esc(
                        $d['department'] ?? '—'
                    ) ?></td>
                    <td><span class="badge <?= $d['status'] === 'Active' ? 'in-stock' : 'low' ?>"><?= esc(
                        $d['status']
                    ) ?></span></td>
                    <td>
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
                                value="doctor_status"
                            >
                            <input
                                type="hidden"
                                name="section"
                                value="doctors"
                            >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= $d[
                                    'id'
                                ] ?>"
                            >
                            <input
                                type="hidden"
                                name="status"
                                value="<?= $d['status'] === 'Active'
                                    ? 'Inactive'
                                    : 'Active' ?>"
                            >
                            <button class="action-btn <?= $d['status'] === 'Active'
                                ? 'danger'
                                : 'success' ?>"><?= $d['status'] === 'Active'
                                ? 'Deactivate'
                                : 'Activate' ?></button>
                        </form>
                    </td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../shared/bottom.php'; ?>
