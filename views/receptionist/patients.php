<?php
$title = 'Patients';
$current = 'receptionist_patients';
$heading = 'Patients';
$subheading = 'Patient management · doctor slots · emergency registration';
require __DIR__ . '/_page_top.php';
?>
<section class="two-col">
    <section class="panel">
        <h2 id="patient-form-title"><?= $editPatient
                        ? 'Update Patient'
                        : 'Register Patient' ?></h2>
        <p>Core patient identity is shared across the hospital.</p>
        <form
            id="patient-form"
            method="POST"
            class="form-grid receptionist-live-save"
            action="index.php?page=ajax&action=receptionist_live_action"
            data-validate="patient"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= esc(csrf_token()) ?>"
            >
            <input
                type="hidden"
                name="action"
                value="patient_save"
            >
            <input
                type="hidden"
                name="section"
                value="patients"
            >
            <input
                type="hidden"
                name="id"
                value="<?= esc($editPatient['id'] ?? 0) ?>"
            >

            <div class="form-group full">
                <label>Name</label>
                <input
                    name="name"
                    value="<?= esc($editPatient['name'] ?? '') ?>"
                    required
                >
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input
                    name="phone"
                    value="<?= esc($editPatient['phone'] ?? '') ?>"
                    required
                >
            </div>
            <div class="form-group">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    value="<?= esc(
                                            $editPatient['email'] ?? ''
                                        ) ?>"
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
                                            $editPatient['age'] ?? ''
                                        ) ?>"
                >
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <?php foreach (['Male', 'Female', 'Other'] as $gender): ?>
                    <option <?= ($editPatient['gender'] ?? '') === $gender
                                                    ? 'selected'
                                                    : '' ?>><?= esc($gender) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>Address</label>
                <input
                    name="address"
                    value="<?= esc($editPatient['address'] ?? '') ?>"
                >
            </div>
            <div class="form-group">
                <label>Emergency contact</label>
                <input
                    name="emergency_contact"
                    value="<?= esc(
                                            $editPatient['emergency_contact'] ?? ''
                                        ) ?>"
                >
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option <?= ($editPatient['status'] ?? 'Active') === 'Active'
                                                ? 'selected'
                                                : '' ?>>Active</option>
                    <option <?= ($editPatient['status'] ?? '') === 'Inactive'
                                                ? 'selected'
                                                : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="full form-actions">
                <button
                    id="patient-submit-btn"
                    class="small-btn"
                ><?= $editPatient
                                        ? 'Update'
                                        : 'Register' ?> Patient</button>
                <button
                    type="button"
                    class="action-btn secondary js-cancel-form"
                    data-form="patient"
                >Cancel</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Patient Records</h2>
                <p>Search registered patient records.</p>
            </div>
        </div>
        <div class="toolbar receptionist-record-search">
            <input
                id="receptionist-patient-search"
                name="search"
                value="<?= esc(
                                    $_GET['search'] ?? ''
                                ) ?>"
                placeholder="Search by patient ID, name, phone, email, age, gender, blood group or status"
            >
            <button
                id="receptionist-patient-search-button"
                type="button"
                class="action-btn secondary"
            >Search</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Patient</th>
                        <th>Phone</th>
                        <th>Age</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="patient-live-results"><?php require __DIR__ .
                                        '/partials/_patient_rows.php'; ?></tbody>
            </table>
        </div>
    </section>
</section>

<?php require __DIR__ . '/_scripts.php'; ?>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
