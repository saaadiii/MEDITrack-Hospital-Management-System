<?php if (empty($prescriptions)): ?>
<div class="rx-empty-state">
    <div class="rx-empty-icon">Rx</div>
    <h3><?= !empty($search_query)
            ? 'No matching prescriptions'
            : 'No prescriptions available yet' ?></h3>
    <p><?= !empty($search_query)
            ? 'No doctor name contains “' . esc($search_query) . '”. Try another name.'
            : 'Your doctor-created prescriptions will appear here.' ?></p>
</div>
<?php endif; ?>
<?php foreach ($prescriptions as $p): ?>
<?php
$doctorName = trim((string) ($p['doctor_name'] ?? 'Doctor'));
$parts = preg_split('/\s+/', $doctorName, -1, PREG_SPLIT_NO_EMPTY);
$initials = '';
foreach (array_slice($parts, 0, 2) as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
if ($initials === '') {
    $initials = 'DR';
}
$appointmentDisplay =
    !empty($p['appointment_id']) && !empty($p['appointment_serial'])
        ? doctor_appointment_display_id($p['doctor_id'], $p['appointment_serial'])
        : '—';
$medicines = $p['medicines'] ?? [];
?>
<article class="rx-overview-card">
    <div class="rx-overview-main">
        <div class="rx-overview-avatar"><?= esc($initials) ?></div>
        <div class="rx-overview-doctor">
            <span class="rx-overview-eyebrow">Prescription</span>
            <h2>Dr. <?= esc(preg_replace('/^Dr\.?\s+/i', '', $doctorName)) ?></h2>
            <p><?= esc($p['specialization'] ?? '') ?></p>
        </div>
    </div>
    <div class="rx-overview-meta">
        <div><span>Appointment ID</span><strong><?= esc($appointmentDisplay) ?></strong></div>
        <div><span>Date</span><strong><?= esc(
                    date('d M Y', strtotime($p['created_at']))
                ) ?></strong></div>
        <div><span>Medicines</span><strong><?= count($medicines) ?></strong></div>
    </div>
    <div class="rx-overview-actions">
        <span class="rx-overview-status"><i></i><?= esc($p['status'] ?? 'Active') ?></span>
        <a
            class="rx-view-btn"
            href="index.php?page=prescription_view&id=<?= (int) $p[
                        'id'
                    ] ?>"
        >View prescription <span aria-hidden="true">→</span></a>
    </div>
</article>
<?php endforeach; ?>
