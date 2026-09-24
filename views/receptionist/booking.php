<?php
$title = 'Book Appointment';
$current = 'receptionist_booking';
$heading = 'Book Appointment';
$subheading = 'Patient management · doctor slots · emergency registration';
require __DIR__ . '/_page_top.php';
?>
<input
    type="hidden"
    id="receptionist-booking-csrf"
    value="<?= esc(csrf_token()) ?>"
>
<section class="panel receptionist-booking-patient-panel">
    <div class="panel-header">
        <div>
            <h2>Book Appointment</h2>
            <p>Book an available doctor slot for a registered walk-in patient.</p>
        </div>
    </div>
    <div class="form-grid">
        <div class="form-group admin-entity-picker">
            <label>Patient</label>
            <input
                type="hidden"
                id="receptionist-booking-patient-id"
            >
            <input
                type="search"
                id="receptionist-booking-patient-search"
                placeholder="Search by patient ID or name"
                autocomplete="off"
            >
            <div
                id="receptionist-booking-patient-results"
                class="admin-picker-results"
                hidden
            ></div>
            <small
                id="receptionist-booking-patient-selected"
                class="note"
            >Select a registered patient.</small>
        </div>
        <div class="form-group">
            <label>Specialization</label>
            <select id="receptionist-booking-specialization">
                <option value="">Select specialization</option>
                <?php foreach ($specializations as $item): ?>
                <option value="<?= esc(
                    $item['specialization']
                ) ?>"><?= esc($item['specialization']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Appointment date (optional)</label>
            <input
                type="date"
                id="receptionist-booking-date"
                data-date-rule="today-or-future"
            >
        </div>
        <div class="form-group">
            <label>Reason (optional)</label>
            <input
                id="receptionist-booking-reason"
                placeholder="Appointment reason"
            >
        </div>
    </div>
</section>
<section class="panel appointment-finder">
    <div class="panel-header">
        <div>
            <h2>Available Doctors</h2>
            <p>Select a specialization to view doctors and available appointment slots.</p>
        </div>
    </div>
    <div
        id="receptionist-booking-doctors"
        class="doctor-choice-grid"
        aria-live="polite"
    >
        <div class="booking-placeholder">Select a specialization to see doctors.</div>
    </div>
</section>
<section
    class="panel doctor-booking-panel"
    id="receptionist-booking-profile-panel"
    hidden
>
    <div
        id="receptionist-booking-profile"
        aria-live="polite"
    ></div>
</section>

<?php require __DIR__ . '/_scripts.php'; ?>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
