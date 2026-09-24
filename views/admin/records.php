<?php
$title = 'All Records';
$current = 'admin_records';
$heading = 'All Records';
$subheading = 'Hospital-wide patient, appointment and follow-up records';
require __DIR__ . '/_page_top.php';
?>
<section class="panel">
    <h2>All Hospital Records</h2>
    <p>Admin can review patient, appointment and follow-up records. Symptoms and prescriptions are
        excluded from Admin access for patient privacy.</p>
</section>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Patients</h2>
            <p>Search hospital patient records.</p>
        </div>
    </div>
    <div class="toolbar admin-search-toolbar">
        <input
            id="admin-record-patients-search"
            type="search"
            placeholder="Search by patient ID, name, phone, email or age"
            autocomplete="off"
        >
        <button
            type="button"
            id="admin-record-patients-search-button"
            class="small-btn secondary"
        >Search</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Age</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody
                id="admin-record-patients-body"
                data-empty-message="No matching patients found."
            ><?php foreach (
                $allPatients
                as $p
            ): ?>
                <tr>
                    <td>P<?= str_pad((string) (int) $p['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= esc(
                        $p['name']
                    ) ?></td>
                    <td><?= esc($p['phone']) ?></td>
                    <td><?= esc($p['email'] ?? '—') ?></td>
                    <td><?= esc(
                        $p['age'] ?? '—'
                    ) ?></td>
                    <td><?= esc($p['status']) ?></td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Appointments</h2>
            <p>Search hospital appointment records.</p>
        </div>
    </div>
    <div class="toolbar admin-search-toolbar">
        <input
            id="admin-record-appointments-search"
            type="search"
            placeholder="Search by appointment ID, patient, doctor, date, time or reason"
            autocomplete="off"
        >
        <button
            type="button"
            id="admin-record-appointments-search-button"
            class="small-btn secondary"
        >Search</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody
                id="admin-record-appointments-body"
                data-empty-message="No matching appointments found."
            ><?php foreach (
                $allAppointments
                as $a
            ): ?>
                <tr>
                    <td><?= !empty($a['doctor_serial'])
                        ? doctor_appointment_display_id($a['doctor_id'], $a['doctor_serial'])
                        : appointment_display_id($a['id']) ?></td>
                    <td><?= esc($a['patient_name']) ?></td>
                    <td><?= esc(
                        $a['doctor_name']
                    ) ?></td>
                    <td><?= esc($a['appointment_date']) ?></td>
                    <td><?= date(
                        'h:i A',
                        strtotime($a['appointment_time'])
                    ) ?></td>
                    <td><?= esc($a['reason'] ?? '—') ?></td>
                    <td><?= esc(
                        $a['status']
                    ) ?></td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Follow-ups</h2>
            <p>Search hospital follow-up records.</p>
        </div>
    </div>
    <div class="toolbar admin-search-toolbar">
        <input
            id="admin-record-followups-search"
            type="search"
            placeholder="Search by follow-up ID, patient, doctor, date or purpose"
            autocomplete="off"
        >
        <button
            type="button"
            id="admin-record-followups-search-button"
            class="small-btn secondary"
        >Search</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Follow-up ID</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Purpose</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody
                id="admin-record-followups-body"
                data-empty-message="No matching follow-ups found."
            ><?php foreach (
                $allFollowups
                as $x
            ): ?>
                <tr>
                    <td>F<?= str_pad((string) (int) $x['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= esc(
                        $x['patient_name']
                    ) ?></td>
                    <td><?= esc($x['doctor_name']) ?></td>
                    <td><?= esc($x['followup_date']) ?></td>
                    <td><?= esc(
                        $x['purpose']
                    ) ?></td>
                    <td><?= esc($x['status']) ?></td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
