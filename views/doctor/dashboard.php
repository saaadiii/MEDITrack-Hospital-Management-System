<?php $title = 'Doctor Dashboard';
$current = 'doctor';
$logoutRole = 'doctor';
$heading = 'Good evening, ' . ($d['name'] ?? 'Doctor');
$subheading = 'Doctor workspace · ' . ($d['specialization'] ?? '');
$nav = [
    ['doctor', 'index.php?page=doctor', 'Dashboard'],
    ['doctor_profile', 'index.php?page=doctor_profile', 'Profile'],
    ['doctor_patients', 'index.php?page=doctor_patients', 'Patient Management'],
    ['doctor_slots', 'index.php?page=doctor_slots', 'Availability'],
    ['doctor_prescriptions', 'index.php?page=doctor_prescriptions', 'Prescriptions'],
    ['doctor_followups', 'index.php?page=doctor_followups', 'Follow-ups']
];
require __DIR__ . '/../shared/top.php';
?>
<section class="stats-grid">
    <div class="stat-card"><span>Today's appointments</span><strong><?= $stats[
        'appointments'
    ] ?></strong></div>
    <div class="stat-card"><span>My patients</span><strong><?= $stats[
        'patients'
    ] ?></strong></div>
    <div class="stat-card"><span>Active prescriptions</span><strong><?= $stats[
        'prescriptions'
    ] ?></strong></div>
    <div class="stat-card"><span>Active follow-ups</span><strong><?= $stats[
        'followups'
    ] ?></strong></div>
</section>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Today's Appointments</h2>
            <p>Appointments scheduled for today only.</p>
        </div><a
            class="small-btn"
            href="index.php?page=doctor_patients"
        >Manage appointments</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Appointment No.</th>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody><?php
            foreach (
                array_slice($appointments, 0, 8)
                as $a
            ): ?>
                <tr>
                    <td><strong><?= doctor_appointment_display_id(
                        $d['id'],
                        $a['doctor_serial']
                    ) ?></strong></td>
                    <td><strong><?= esc($a['patient_name']) ?></strong>
                        <br><small><?= esc(
                            $a['phone']
                        ) ?></small>
                    </td>
                    <td><?= esc(date('d M Y', strtotime($a['appointment_date']))) ?></td>
                    <td><?= esc(
                        date('h:i A', strtotime($a['appointment_time']))
                    ) ?></td>
                    <td><?= esc($a['reason'] ?: '—') ?></td>
                    <td><span class="badge"><?= esc(
                        $a['status']
                    ) ?></span></td>
                </tr><?php endforeach;
                if (
                    !$appointments
                ): ?>
                <tr>
                    <td
                        colspan="6"
                        class="empty-cell"
                    >No appointments scheduled for today.</td>
                </tr><?php endif;
                ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
