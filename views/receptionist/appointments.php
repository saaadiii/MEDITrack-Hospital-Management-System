<?php
$title = 'Appointments';
$current = 'receptionist_appointments';
$heading = 'Appointments';
$subheading = 'Patient management · doctor slots · emergency registration';
require __DIR__ . '/_page_top.php';
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Appointments</h2>
            <p>Search instantly by patient, doctor, appointment ID, date, reason or status.</p>
        </div>
        <div
            class="toolbar"
            id="receptionist-appointment-search-form"
        >
            <input
                type="search"
                id="receptionist-appointment-search"
                name="appointment_search"
                value="<?= esc(
                                    $appointmentSearch ?? ''
                                ) ?>"
                placeholder="Search by patient, doctor, reason, status, date, time or appointment ID"
                autocomplete="off"
            >
            <button
                class="action-btn secondary"
                type="button"
                id="receptionist-appointment-search-button"
            >Search</button>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Date/Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="receptionist-appointment-table-body"><?php require __DIR__ .
                                '/partials/_appointment_rows.php'; ?></tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/_scripts.php'; ?>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
