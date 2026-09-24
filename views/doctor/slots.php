<?php $title = 'Doctor Availability';
$current = 'doctor_slots';
$logoutRole = 'doctor';
$heading = 'My Availability';
$subheading = 'Tell the receptionist when you are available for appointments';
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
<?php
$s = $editSlot;
$today = date('Y-m-d');
$maxAvailabilityDate = date('Y-m-d', strtotime('+9 days'));
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= $s
                ? 'Edit Availability'
                : 'Provide Availability' ?></h2>
            <p>Provide the date and time range you can see patients. The receptionist will create
                individual bookable slots from it.</p>
        </div>
    </div>
    <form
        method="POST"
        class="form-grid"
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
            value="availability_save"
        >
        <input
            type="hidden"
            name="id"
            value="<?= esc(
                $s['id'] ?? ''
            ) ?>"
        >
        <div class="form-group">
            <label>Date</label>
            <input
                type="date"
                id="doctorAvailabilityDate"
                name="available_date"
                min="<?= esc(
                    $today
                ) ?>"
                max="<?= esc($maxAvailabilityDate) ?>"
                data-editing="<?= $s
                    ? '1'
                    : '0' ?>"
                required
                value="<?= esc(
                    $s['available_date'] ?? $today
                ) ?>"
            ><small class="note">Availability can be submitted within the next 10 calendar
                days.</small>
        </div>
        <div class="form-group">
            <label>Available From</label>
            <input
                type="time"
                id="doctorAvailabilityStart"
                name="start_time"
                required
                value="<?= esc(
                    $s['start_time'] ?? '09:00'
                ) ?>"
            ><small
                class="note"
                id="doctorAvailabilityTimeNote"
            >For today, choose the current time or later.</small>
        </div>
        <div class="form-group">
            <label>Available Until</label>
            <input
                type="time"
                id="doctorAvailabilityEnd"
                name="end_time"
                required
                value="<?= esc(
                    $s['end_time'] ?? '12:00'
                ) ?>"
            >
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option
                    value="Available"
                    <?= ($s[
                        'status'
                    ] ??
                        'Available') ===
                    'Available'
                        ? 'selected'
                        : '' ?>
                >Available</option>
                <option
                    value="Unavailable"
                    <?= ($s['status'] ?? '') === 'Unavailable'
                        ? 'selected'
                        : '' ?>
                >Unavailable</option>
            </select>
        </div>
        <div class="form-group full">
            <label>Note (optional)</label>
            <input
                name="note"
                maxlength="255"
                placeholder="e.g. Morning clinic"
                value="<?= esc(
                    $s['note'] ?? ''
                ) ?>"
            >
        </div>
        <div class="full">
            <button
                class="small-btn"
                type="submit"
            ><?= $s
                ? 'Update Availability'
                : 'Submit Availability' ?></button><?php if (
                $s
            ): ?> <a
                class="action-btn secondary"
                href="index.php?page=doctor_slots"
            >Cancel edit</a><?php endif; ?>
        </div>
    </form>
</section>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Availability Calendar</h2>
            <p>The receptionist uses these ranges to create patient appointment slots.</p>
        </div>
    </div>
    <div
        id="availability-live-message"
        class="form-message"
        hidden
    ></div>
    <form
        class="toolbar"
        id="availability-filter-form"
    >
        <input
            type="date"
            id="availability-filter-date"
            value="<?= esc(
                $date
            ) ?>"
        >
        <button
            class="small-btn"
            type="submit"
        >Filter</button>
        <button
            class="action-btn secondary"
            type="button"
            id="availability-filter-clear"
            <?= $date
                ? ''
                : 'hidden' ?>
        >Clear</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Available Time</th>
                    <th>Note</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="availability-table-body"><?php require __DIR__ .
                '/_availability_rows.php'; ?></tbody>
        </table>
    </div>
</section>
<script>
    (function() {
        const dateInput = document.getElementById('doctorAvailabilityDate'),
            startInput = document.getElementById('doctorAvailabilityStart'),
            endInput = document.getElementById('doctorAvailabilityEnd'),
            timeNote = document.getElementById('doctorAvailabilityTimeNote');
        if (dateInput && startInput && endInput) {
            const isEditing = dateInput.dataset.editing === '1';

            function pad(n) {
                return String(n).padStart(2, '0')
            }

            function localToday() {
                const n = new Date();
                return n.getFullYear() + '-' + pad(n.getMonth() + 1) + '-' + pad(n.getDate())
            }

            function localMaxDate() {
                const n = new Date();
                n.setDate(n.getDate() + 9);
                return n.getFullYear() + '-' + pad(n.getMonth() + 1) + '-' + pad(n.getDate())
            }

            function localTime() {
                const n = new Date();
                return pad(n.getHours()) + ':' + pad(n.getMinutes())
            }

            function nextQuarterRange() {
                const n = new Date();
                n.setSeconds(0, 0);
                const rem = n.getMinutes() % 15;
                if (rem) n.setMinutes(n.getMinutes() + (15 - rem));
                else n.setMinutes(n.getMinutes() + 15);
                const start = pad(n.getHours()) + ':' + pad(n.getMinutes());
                const e = new Date(n.getTime() + 60 * 60000);
                if (e.getDate() !== n.getDate()) return [start, '23:59'];
                return [start, pad(e.getHours()) + ':' + pad(e.getMinutes())]
            }

            function sync() {
                const t = localToday(),
                    max = localMaxDate(),
                    now = localTime();
                dateInput.min = t;
                dateInput.max = max;
                if (!isEditing && (!dateInput.value || dateInput.value < t || dateInput.value >
                        max)) dateInput.value = t;
                const todaySelected = dateInput.value === t;
                startInput.min = todaySelected ? now : '';
                endInput.min = todaySelected ? now : '';
                if (timeNote) timeNote.hidden = !todaySelected;
                if (todaySelected && !isEditing && startInput.value < now) {
                    const range = nextQuarterRange();
                    startInput.value = range[0];
                    endInput.value = range[1]
                }
                dateInput.setCustomValidity(dateInput.value && dateInput.value > max ?
                    'Availability can only be submitted within the next 10 calendar days.' :
                    '');
                startInput.setCustomValidity(todaySelected && startInput.value && startInput
                    .value < now ?
                    'For today, availability must start at the current time or later.' : '');
                endInput.setCustomValidity(startInput.value && endInput.value && endInput
                    .value <= startInput.value ?
                    'Available Until must be later than Available From.' : '');
            }
            [dateInput, startInput, endInput].forEach(el => {
                el.addEventListener('change', sync);
                el.addEventListener('input', sync);
                el.addEventListener('focus', sync)
            });
            sync();
            setInterval(sync, 60000);
        }
        const filter = document.getElementById('availability-filter-form'),
            filterDate = document.getElementById('availability-filter-date'),
            clear = document.getElementById('availability-filter-clear'),
            tbody = document.getElementById('availability-table-body'),
            msg = document.getElementById('availability-live-message');

        function show(text, type) {
            msg.hidden = !text;
            msg.textContent = text || '';
            msg.className = 'form-message ' + (type || 'success')
        }
        async function load() {
            const q = filterDate.value;
            try {
                const r = await fetch(
                    'index.php?page=ajax&action=doctor_availability_rows&date=' +
                    encodeURIComponent(q), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message ||
                'Could not load availability.');
                tbody.innerHTML = j.html;
                clear.hidden = !q;
                bindDeletes();
            } catch (e) {
                show(e.message, 'error')
            }
        }

        function bindDeletes() {
            tbody.querySelectorAll('.availability-delete-form').forEach(form => form
                .addEventListener('submit', async e => {
                    e.preventDefault();
                    if (!confirm('Delete this availability?')) return;
                    const fd = new FormData(form);
                    fd.append('date', filterDate.value);
                    try {
                        const r = await fetch(
                            'index.php?page=ajax&action=doctor_availability_delete', {
                                method: 'POST',
                                body: fd,
                                headers: {
                                    Accept: 'application/json'
                                }
                            });
                        const j = await r.json();
                        if (!j.success) throw new Error(j.message ||
                            'Could not delete availability.');
                        tbody.innerHTML = j.html;
                        show(j.message, 'success');
                        bindDeletes();
                    } catch (err) {
                        show(err.message, 'error')
                    }
                }));
        }
        filter.addEventListener('submit', e => {
            e.preventDefault();
            load()
        });
        filterDate.addEventListener('change', load);
        clear.addEventListener('click', () => {
            filterDate.value = '';
            load()
        });
        bindDeletes();
    })();
</script>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
