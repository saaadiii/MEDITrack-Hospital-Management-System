<?php
$title = 'Doctor Slots';
$current = 'receptionist_slots';
$heading = 'Doctor Slots';
$subheading = 'Patient management · doctor slots · emergency registration';
require __DIR__ . '/_page_top.php';
?>
<section class="two-col">
    <section class="panel">
        <h2 id="slot-form-title"><?= $editSlot ? 'Update Slot' : 'Create Doctor Slot' ?></h2>
        <form
            id="slot-form"
            method="POST"
            class="form-grid receptionist-live-save"
            action="index.php?page=ajax&action=receptionist_live_action"
            data-validate="slot"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= esc(csrf_token()) ?>"
            >
            <input
                type="hidden"
                name="action"
                value="slot_save"
            >
            <input
                type="hidden"
                name="section"
                value="slots"
            >
            <input
                type="hidden"
                name="id"
                value="<?= esc($editSlot['id'] ?? 0) ?>"
            >

            <div class="form-group full">
                <label>Doctor</label>
                <select
                    id="slot_doctor_id"
                    required
                >
                    <option value="">Select doctor</option>
                    <?php foreach ($doctors as $doctor): ?>
                    <?php if (($doctor['status'] ?? 'Active') === 'Active'): ?>
                    <option
                        value="<?= (int) $doctor['id'] ?>"
                        <?= (int) ($editSlot[
                            'doctor_id'
                        ] ?? 0) === (int) $doctor['id']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= esc($doctor['name']) ?>
                        <?= !empty($doctor['specialization']) ? ' — ' . esc($doctor['specialization']) : '' ?>
                    </option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label>Availability</label>
                <select
                    name="availability_id"
                    id="availability_id"
                    required
                    <?= empty(
                                            $editSlot
                                        )
                                            ? 'disabled'
                                            : '' ?>
                >
                    <option value="">Select doctor first</option>
                </select>
                <div
                    id="availability_help"
                    class="availability-help"
                >Choose a doctor to load their available dates and times.</div>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input
                    type="date"
                    id="slot_date"
                    name="slot_date"
                    value="<?= esc(
                                            $editSlot['slot_date'] ?? ''
                                        ) ?>"
                    required
                    readonly
                >
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option <?= ($editSlot['status'] ?? 'Available') === 'Available'
                                                ? 'selected'
                                                : '' ?>>Available</option>
                    <option <?= ($editSlot['status'] ?? '') === 'Closed'
                                                ? 'selected'
                                                : '' ?>>Closed</option>
                </select>
            </div>
            <div class="form-group">
                <label>Start</label>
                <input
                    type="time"
                    id="slot_start"
                    name="start_time"
                    value="<?= esc(
                                            isset($editSlot['start_time']) ? substr($editSlot['start_time'], 0, 5) : ''
                                        ) ?>"
                    required
                >
            </div>
            <div class="form-group">
                <label>End</label>
                <input
                    type="time"
                    id="slot_end"
                    name="end_time"
                    value="<?= esc(
                                            isset($editSlot['end_time']) ? substr($editSlot['end_time'], 0, 5) : ''
                                        ) ?>"
                    required
                >
            </div>
            <div class="full form-actions">
                <button
                    id="slot-submit-btn"
                    class="small-btn"
                ><?= $editSlot
                                        ? 'Update'
                                        : 'Create' ?> Slot</button>
                <button
                    type="button"
                    class="action-btn secondary js-cancel-form"
                    data-form="slot"
                >Cancel</button>
            </div>
        </form>
        <?php if (
                        !$availabilities
                    ): ?>
        <p class="note">No doctor availability has been submitted yet. A doctor must provide
            availability first.</p><?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Doctor Slots</h2>
                <p>Available slots are shown to patients for booking.</p>
            </div>
            <form
                class="toolbar receptionist-slot-filterbar"
                method="GET"
                id="receptionist-slot-filter"
            >
                <input
                    type="hidden"
                    name="page"
                    value="receptionist_slots"
                >
                <select
                    name="doctor_id"
                    aria-label="Filter by doctor"
                >
                    <option value="">All doctors</option>
                    <?php foreach ($doctors as $doctor): ?>
                    <option
                        value="<?= (int) $doctor['id'] ?>"
                        <?= (int) ($slotDoctorId ??
                            0) === (int) $doctor['id']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= esc(doctor_display_id((int) $doctor['id'])) ?> · <?= esc(
                             $doctor['name']
                         ) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select
                    name="department"
                    aria-label="Filter by department"
                >
                    <option value="">All departments</option>
                    <?php foreach ($slotDepartments ?? [] as $department): ?>
                    <option
                        value="<?= esc($department) ?>"
                        <?= ($slotDepartment ?? '') ===
                        $department
                            ? 'selected'
                            : '' ?>
                    ><?= esc($department) ?></option>
                    <?php endforeach; ?>
                </select>
                <input
                    type="date"
                    name="date"
                    value="<?= esc(
                                            $slotDate ?? ''
                                        ) ?>"
                    aria-label="Filter by date"
                >
                <button
                    class="action-btn secondary"
                    type="submit"
                >Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="slot-table">
                <thead>
                    <tr>
                        <th>Doctor ID</th>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="receptionist-slot-table-body"><?php require __DIR__ .
                                        '/partials/_slot_rows.php'; ?></tbody>
            </table>
        </div>
    </section>
</section>


<?php require __DIR__ . '/_scripts.php'; ?>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
