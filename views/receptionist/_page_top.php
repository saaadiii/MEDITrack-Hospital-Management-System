<?php
$logoutRole = 'receptionist';
$nav = [
    ['receptionist', 'index.php?page=receptionist', 'Dashboard'],
    ['receptionist_patients', 'index.php?page=receptionist_patients', 'Patients'],
    ['receptionist_booking', 'index.php?page=receptionist_booking', 'Book Appointment'],
    ['receptionist_appointments', 'index.php?page=receptionist_appointments', 'Appointments'],
    ['receptionist_slots', 'index.php?page=receptionist_slots', 'Doctor Slots'],
    ['receptionist_emergency', 'index.php?page=receptionist_emergency', 'Emergency']
];
$message = get_flash('success') ?? '';
$error = get_flash('error') ?? '';
$selectedAvailabilityId = (int) ($editSlot['availability_id'] ?? 0);
require __DIR__ . '/../shared/top.php';
?>
<section class="stats-grid">
    <div class="stat-card"><span>Active patients</span><strong><?= esc($stats['patients']) ?></strong></div>
    <div class="stat-card"><span>Available slots</span><strong><?= esc($stats['slots']) ?></strong></div>
    <div class="stat-card"><span>Today's appointments</span><strong><?= esc($stats['appointments']) ?></strong></div>
    <div class="stat-card"><span>Waiting emergencies</span><strong><?= esc($stats['emergencies']) ?></strong></div>
</section>
<div id="receptionist-live-message" class="form-message" hidden></div>
