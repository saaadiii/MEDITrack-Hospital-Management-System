<?php
$title = 'Emergency';
$current = 'receptionist_emergency';
$heading = 'Emergency';
$subheading = 'Patient management · doctor slots · emergency registration';
require __DIR__ . '/_page_top.php';
?>
<section class="two-col">
    <section class="panel">
        <h2 id="emergency-form-title"><?= $editEmergency
                        ? 'Update Emergency'
                        : 'Emergency Fast Registration' ?></h2>
        <form
            id="emergency-form"
            method="POST"
            class="form-grid receptionist-live-save"
            action="index.php?page=ajax&action=receptionist_live_action"
            data-validate="emergency"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= esc(csrf_token()) ?>"
            >
            <input
                type="hidden"
                name="action"
                value="emergency_save"
            >
            <input
                type="hidden"
                name="section"
                value="emergency"
            >
            <input
                type="hidden"
                name="id"
                value="<?= esc($editEmergency['id'] ?? 0) ?>"
            >
            <input
                type="hidden"
                name="arrival_time"
                value="<?= esc(
                                    $editEmergency['arrival_time'] ?? date('Y-m-d H:i:s')
                                ) ?>"
            >
            <div class="form-group full">
                <label>Patient name</label>
                <input
                    name="patient_name"
                    value="<?= esc(
                                        $editEmergency['patient_name'] ?? ''
                                    ) ?>"
                    required
                >
            </div>
            <div class="form-group">
                <label>Age</label>
                <input
                    type="number"
                    name="age"
                    min="0"
                    max="120"
                    value="<?= esc(
                                        $editEmergency['age'] ?? ''
                                    ) ?>"
                >
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender"><?php foreach (
                                    ['Male', 'Female', 'Other']
                                    as $gender
                                ): ?>
                    <option <?= ($editEmergency['gender'] ?? '') === $gender
                        ? 'selected'
                        : '' ?>><?= esc($gender) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input
                    name="phone"
                    value="<?= esc(
                                        $editEmergency['phone'] ?? ''
                                    ) ?>"
                >
            </div>
            <div class="form-group">
                <label>Emergency contact</label>
                <input
                    name="emergency_contact"
                    value="<?= esc(
                                        $editEmergency['emergency_contact'] ?? ''
                                    ) ?>"
                >
            </div>
            <div class="form-group full">
                <label>Emergency type</label>
                <input
                    name="emergency_type"
                    value="<?= esc(
                                        $editEmergency['emergency_type'] ?? ''
                                    ) ?>"
                    required
                >
            </div>
            <div class="form-group full">
                <label>Notes</label>
                <textarea name="notes"><?= esc(
                                    $editEmergency['notes'] ?? ''
                                ) ?></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status"><?php foreach (
                                    ['Waiting', 'Completed', 'Cancelled']
                                    as $status
                                ): ?>
                    <option <?= ($editEmergency['status'] ?? 'Waiting') === $status
                        ? 'selected'
                        : '' ?>><?= esc($status) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="full form-actions">
                <button
                    id="emergency-submit-btn"
                    class="small-btn"
                >Save Emergency</button>
                <button
                    type="button"
                    class="action-btn secondary js-cancel-form"
                    data-form="emergency"
                >Cancel</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Emergency Queue</h2>
                <p>Search by ER code, patient, emergency type or status.</p>
            </div>
            <form
                class="toolbar"
                id="receptionist-emergency-search-form"
            >
                <input
                    id="receptionist-emergency-search"
                    name="q"
                    value="<?= esc(
                                            $_GET['q'] ?? ''
                                        ) ?>"
                    placeholder="Search by emergency ID, patient name, phone, emergency contact, type or status"
                >
                <button class="action-btn secondary">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Patient</th>
                        <th>Type</th>
                        <th>Arrival</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="receptionist-emergency-table-body"><?php require __DIR__ .
                                        '/partials/_emergency_rows.php'; ?></tbody>
            </table>
        </div>
    </section>
</section>
<?php require __DIR__ . '/_scripts.php'; ?>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
