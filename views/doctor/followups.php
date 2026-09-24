<?php $title = 'Follow-ups';
$current = 'doctor_followups';
$logoutRole = 'doctor';
$heading = 'Manage Follow-ups';
$subheading = 'Create and manage patient follow-up plans';
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
<section class="panel">
    <div class="panel-header">
        <div>
            <h2 id="followup-form-title">Create Follow-up</h2>
        </div>
    </div>
    <form
        method="POST"
        class="form-grid"
        id="followup-form"
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
            name="id"
            id="followup-edit-id"
            value=""
        >
        <input
            type="hidden"
            name="patient_id"
            id="followup-patient-id"
            value=""
        >
        <div class="form-group patient-picker-wrap">
            <label>Patient</label>
            <input
                type="search"
                id="followup-patient-search"
                placeholder="Search by patient name, phone, email, patient ID or appointment ID"
                autocomplete="off"
            >
            <div
                id="followup-patient-results"
                class="live-patient-results"
                hidden
            ></div><small
                class="note"
                id="followup-patient-selected"
            >Select a patient from the search results.</small>
        </div>
        <div class="form-group">
            <label>Appointment ID</label>
            <select
                name="appointment_id"
                id="followup-appointment"
                required
            >
                <option value="">Select a patient first</option><?php foreach (
                    $appointmentOptions
                    as $a
                ): ?>
                <option
                    value="<?= $a['id'] ?>"
                    data-patient-id="<?= $a[
                        'patient_id'
                    ] ?>"
                ><?= doctor_appointment_display_id(
                    $d['id'],
                    $a['doctor_serial']
                ) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Follow-up date</label>
            <input
                type="date"
                name="followup_date"
                id="followup-date"
                min="<?= esc(
                    date('Y-m-d')
                ) ?>"
                data-date-rule="today-or-future"
                required
            >
        </div>
        <div class="form-group">
            <label>Reason</label>
            <input
                name="purpose"
                id="followup-purpose"
                placeholder="Review progress"
                required
            >
        </div>
        <div
            class="full"
            style="display:flex;gap:10px;align-items:center"
        >
            <button
                class="small-btn"
                id="followup-submit-btn"
            >Create Follow-up</button>
            <button
                type="button"
                class="action-btn secondary"
                id="followup-cancel-edit"
                hidden
            >Cancel Edit</button>
        </div>
    </form>
</section>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Follow-up Records</h2>
        </div>
    </div>
    <div
        id="followup-live-message"
        class="form-message"
        hidden
    ></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Purpose</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="followup-record-body"><?php
            foreach ($followups as $f):
                $appointmentDisplay =
                    !empty($f['appointment_id']) && isset($f['appointment_serial'])
                        ? doctor_appointment_display_id($d['id'], $f['appointment_serial'])
                        : '—'; ?>
                <tr data-followup-id="<?= $f[
                    'id'
                ] ?>">
                    <td class="followup-appointment-cell"><?= esc(
                        $appointmentDisplay
                    ) ?></td>
                    <td class="followup-patient-cell"><?= esc(
                        $f['patient_name']
                    ) ?></td>
                    <td class="followup-date-cell"><?= esc(
                        $f['followup_date']
                    ) ?></td>
                    <td class="followup-purpose-cell"><?= esc(
                        $f['purpose']
                    ) ?></td>
                    <td class="followup-status-cell"><?= esc(
                        $f['status']
                    ) ?></td>
                    <td class="followup-action-cell"><?php if (
                        $f['status'] === 'Active'
                    ): ?>
                        <div style="display:flex;gap:8px;align-items:center">
                            <button
                                type="button"
                                class="action-btn secondary followup-edit-btn"
                                data-id="<?= $f[
                                    'id'
                                ] ?>"
                                data-patient-id="<?= $f['patient_id'] ?>"
                                data-patient-name="<?= esc(
                                    $f['patient_name']
                                ) ?>"
                                data-appointment-id="<?= $f['appointment_id'] ?>"
                                data-appointment-display="<?= esc(
                                    $appointmentDisplay
                                ) ?>"
                                data-date="<?= esc($f['followup_date']) ?>"
                                data-purpose="<?= esc(
                                    $f['purpose']
                                ) ?>"
                            >Edit</button>
                            <form
                                class="inline-form followup-cancel-form"
                                method="POST"
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
                                    name="id"
                                    value="<?= $f[
                                        'id'
                                    ] ?>"
                                >
                                <button class="action-btn danger">Cancel</button>
                            </form>
                        </div><?php else: ?>—<?php endif; ?>
                    </td>
                </tr><?php
                endforeach;
                if (!$followups): ?>
                <tr>
                    <td
                        colspan="6"
                        class="empty-cell"
                    >No follow-ups yet.</td>
                </tr><?php endif;
                ?>
            </tbody>
        </table>
    </div>
</section>
<script>
    (function() {
        const patientId = document.getElementById('followup-patient-id'),
            search = document.getElementById('followup-patient-search'),
            results = document.getElementById('followup-patient-results'),
            selected = document.getElementById('followup-patient-selected'),
            appointment = document.getElementById('followup-appointment'),
            form = document.getElementById('followup-form'),
            body = document.getElementById('followup-record-body'),
            msg = document.getElementById('followup-live-message'),
            editId = document.getElementById('followup-edit-id'),
            dateInput = document.getElementById('followup-date'),
            purposeInput = document.getElementById('followup-purpose'),
            formTitle = document.getElementById('followup-form-title'),
            submitBtn = document.getElementById('followup-submit-btn'),
            cancelEdit = document.getElementById('followup-cancel-edit');
        let timer;
        const esc = v => String(v ?? '').replace(/[&<>'"]/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        } [c]));

        function filterAppointments() {
            if (editId.value) return;
            const pid = patientId.value;
            let first = '';
            Array.from(appointment.options).forEach((o, i) => {
                if (i === 0) {
                    o.hidden = false;
                    o.disabled = false;
                    return
                }
                const match = pid !== '' && o.dataset.patientId === pid;
                o.hidden = !match;
                o.disabled = !match;
                if (match && !first) first = o.value
            });
            appointment.value = first;
            appointment.options[0].textContent = pid === '' ? 'Select a patient first' : (
                first ? 'Select appointment ID' : 'No eligible appointment found')
        }
        async function run() {
            if (editId.value) return;
            const q = search.value.trim();
            patientId.value = '';
            filterAppointments();
            selected.textContent = 'Select a patient from the search results.';
            if (!q) {
                results.hidden = true;
                return
            }
            try {
                const r = await fetch(
                    'index.php?page=ajax&action=search_doctor_patients&booked_only=1&q=' +
                    encodeURIComponent(q), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message || 'Search failed.');
                const rows = j.data || [];
                results.innerHTML = rows.length ? rows.map(p =>
                        '<button type="button" class="patient-search-result" data-id="' +
                        esc(p.patient_id) + '" data-name="' + esc(p.patient_name) +
                        '"><strong>' + esc(p.patient_name) + '</strong><span>Appointment ' +
                        esc(p.appointment_no || '—') + '</span></button>').join('') :
                    '<div class="patient-search-empty">No patient with a booked appointment whose scheduled time has arrived.</div>';
                results.hidden = false;
                results.querySelectorAll('.patient-search-result').forEach(b => b
                    .addEventListener('click', () => {
                        patientId.value = b.dataset.id;
                        search.value = b.dataset.name;
                        selected.textContent = 'Selected: ' + b.dataset.name;
                        results.hidden = true;
                        filterAppointments()
                    }));
            } catch (e) {
                results.innerHTML = '<div class="patient-search-empty">' + esc(e.message) +
                    '</div>';
                results.hidden = false
            }
        }

        function show(t, type) {
            msg.hidden = !t;
            msg.textContent = t || '';
            msg.className = 'form-message ' + (type || 'success')
        }

        function resetEdit() {
            editId.value = '';
            formTitle.textContent = 'Create Follow-up';
            submitBtn.textContent = 'Create Follow-up';
            cancelEdit.hidden = true;
            search.disabled = false;
            appointment.disabled = false;
            patientId.value = '';
            search.value = '';
            selected.textContent = 'Select a patient from the search results.';
            dateInput.value = '';
            purposeInput.value = '';
            Array.from(appointment.options).forEach((o, i) => {
                if (o.dataset.editTemp === '1') o.remove()
            });
            if (appointment.options[0]) appointment.options[0].textContent =
                'Select a patient first';
            filterAppointments()
        }

        function beginEdit(btn) {
            editId.value = btn.dataset.id;
            patientId.value = btn.dataset.patientId || '';
            search.value = btn.dataset.patientName || '';
            selected.textContent = 'Editing follow-up for ' + (btn.dataset.patientName ||
                'patient');
            dateInput.value = btn.dataset.date || '';
            purposeInput.value = btn.dataset.purpose || '';
            formTitle.textContent = 'Edit Follow-up';
            submitBtn.textContent = 'Update Follow-up';
            cancelEdit.hidden = false;
            search.disabled = true;
            appointment.disabled = true;
            Array.from(appointment.options).forEach(o => {
                if (o.dataset.editTemp === '1') o.remove()
            });
            const temp = document.createElement('option');
            temp.value = btn.dataset.appointmentId || '';
            temp.textContent = btn.dataset.appointmentDisplay || '—';
            temp.selected = true;
            temp.dataset.editTemp = '1';
            appointment.appendChild(temp);
            appointment.value = temp.value;
            form.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            dateInput.focus()
        }

        function bindEdits() {
            body.querySelectorAll('.followup-edit-btn').forEach(b => b.addEventListener('click',
                () => beginEdit(b)))
        }

        function bindCancels() {
            body.querySelectorAll('.followup-cancel-form').forEach(f => f.addEventListener(
                'submit', async e => {
                    e.preventDefault();
                    if (!confirm('Cancel this follow-up?')) return;
                    try {
                        const r = await fetch(
                            'index.php?page=ajax&action=doctor_followup_cancel', {
                                method: 'POST',
                                body: new FormData(f),
                                headers: {
                                    Accept: 'application/json'
                                }
                            });
                        const j = await r.json();
                        if (!j.success) throw new Error(j.message ||
                            'Could not cancel follow-up.');
                        const row = f.closest('tr');
                        if (row) {
                            row.querySelector('.followup-status-cell').textContent =
                                'Cancelled';
                            row.querySelector('.followup-action-cell').textContent =
                                '—';
                            if (editId.value === row.dataset.followupId) resetEdit()
                        }
                        show(j.message, 'success')
                    } catch (err) {
                        show(err.message, 'error')
                    }
                }))
        }
        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(run, 180)
        });
        document.addEventListener('click', e => {
            if (!results.contains(e.target) && e.target !== search) results.hidden =
                true
        });
        cancelEdit.addEventListener('click', resetEdit);
        form.addEventListener('submit', async e => {
            if (editId.value) {
                e.preventDefault();
                const fd = new FormData();
                fd.append('csrf_token', form.querySelector('[name="csrf_token"]')
                    .value);
                fd.append('id', editId.value);
                fd.append('followup_date', dateInput.value);
                fd.append('purpose', purposeInput.value);
                try {
                    submitBtn.disabled = true;
                    const r = await fetch(
                        'index.php?page=ajax&action=doctor_followup_update', {
                            method: 'POST',
                            body: fd,
                            headers: {
                                Accept: 'application/json'
                            }
                        });
                    const j = await r.json();
                    if (!j.success) throw new Error(j.message ||
                        'Could not update follow-up.');
                    const row = body.querySelector('tr[data-followup-id="' + CSS
                        .escape(String(j.data.id)) + '"]');
                    if (row) {
                        row.querySelector('.followup-date-cell').textContent = j
                            .data.followup_date;
                        row.querySelector('.followup-purpose-cell').textContent = j
                            .data.purpose;
                        const eb = row.querySelector('.followup-edit-btn');
                        if (eb) {
                            eb.dataset.date = j.data.followup_date;
                            eb.dataset.purpose = j.data.purpose
                        }
                    }
                    show(j.message, 'success');
                    resetEdit()
                } catch (err) {
                    show(err.message, 'error')
                } finally {
                    submitBtn.disabled = false
                }
                return
            }
            if (!patientId.value) {
                e.preventDefault();
                search.setCustomValidity(
                    'Please select a patient from the search results.');
                search.reportValidity();
                setTimeout(() => search.setCustomValidity(''), 0);
                return
            }
            if (!appointment.value) {
                e.preventDefault();
                appointment.setCustomValidity(
                    'Please select an eligible appointment ID.');
                appointment.reportValidity();
                setTimeout(() => appointment.setCustomValidity(''), 0)
            }
        });
        filterAppointments();
        bindEdits();
        bindCancels();
    })();
</script>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
