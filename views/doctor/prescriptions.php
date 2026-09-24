<?php $title = 'Prescriptions';
$current = 'doctor_prescriptions';
$logoutRole = 'doctor';
$heading = 'Create Prescriptions';
$subheading = 'Create and review patient prescriptions';
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
            <h2>New Prescription</h2>
        </div>
    </div>
    <form
        method="POST"
        class="form-grid"
        id="prescription-form"
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
            value="0"
        >
        <input
            type="hidden"
            name="patient_id"
            id="prescription-patient-id"
            value=""
        >
        <div class="form-group patient-picker-wrap">
            <label>Patient</label>
            <input
                type="search"
                id="prescription-patient-search"
                placeholder="Search by patient name, phone, email, patient ID or appointment ID"
                autocomplete="off"
            >
            <div
                id="prescription-patient-results"
                class="live-patient-results"
                hidden
            ></div><small
                class="note"
                id="prescription-patient-selected"
            >Select a patient from the search results.</small>
        </div>
        <div class="form-group">
            <label>Appointment ID</label>
            <select
                name="appointment_id"
                id="prescription-appointment"
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
        <div class="full">
            <div class="medicine-section-header">
                <div>
                    <h2 class="medicine-section-label">Medicines</h2>
                </div>
                <button
                    class="action-btn secondary"
                    type="button"
                    id="add-medicine"
                >+ Add Medicine</button>
            </div>
            <div id="medicine-list"></div>
        </div>
        <div class="form-group full">
            <label>Doctor Note (optional)</label>
            <textarea
                name="note"
                maxlength="2000"
                placeholder="General advice for this prescription"
            ></textarea>
        </div>
        <div class="full">
            <button
                class="small-btn"
                type="submit"
            >Save Prescription</button>
        </div>
    </form>
</section>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Prescription History</h2>
            <p>Search instantly by patient or medicine.</p>
        </div>
        <div class="toolbar">
            <input
                type="search"
                id="prescription-history-search"
                value="<?= esc(
                     $_GET['search'] ?? ''
                 ) ?>"
                placeholder="Search by patient, medicine, appointment ID, note, status or date"
                autocomplete="off"
            >
            <button
                class="action-btn secondary"
                type="button"
                id="prescription-history-search-button"
            >Search</button>
        </div>
    </div>
    <div
        id="prescription-history-message"
        class="form-message"
        hidden
    ></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Patient</th>
                    <th>Medicines</th>
                    <th>Doctor Note</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="prescription-history-body"><?php require __DIR__ .
                 '/_prescription_history_rows.php'; ?></tbody>
        </table>
    </div>
</section>
<template id="medicine-row-template">
    <div class="medicine-row">
        <div class="form-group">
            <label>Medicine</label>
            <input
                name="medicine_name[]"
                maxlength="150"
                required
                placeholder="e.g. Paracetamol"
            >
        </div>
        <div class="form-group">
            <label>Dosage (include unit)</label>
            <input
                name="dosage[]"
                maxlength="100"
                required
                placeholder="e.g. 500 mg / 5 mL / 1 tablet"
            >
        </div>
        <div class="form-group">
            <label>Frequency</label>
            <input
                name="frequency[]"
                maxlength="100"
                required
                placeholder="e.g. 3 times daily"
            >
        </div>
        <div class="form-group">
            <label>Duration</label>
            <input
                name="duration[]"
                maxlength="100"
                required
                placeholder="e.g. 5 days"
            >
        </div>
        <div class="form-group medicine-instructions">
            <label>Instructions (optional)</label>
            <input
                name="instructions[]"
                maxlength="255"
                placeholder="e.g. After food"
            >
        </div>
        <div class="medicine-remove-wrap">
            <button
                type="button"
                class="action-btn danger remove-medicine"
            >Remove</button>
        </div>
    </div>
</template>
<script>
    (function() {
        const list = document.getElementById('medicine-list'),
            tpl = document.getElementById('medicine-row-template'),
            add = document.getElementById('add-medicine');
        const patientId = document.getElementById('prescription-patient-id'),
            patientSearch = document.getElementById('prescription-patient-search'),
            patientResults = document.getElementById('prescription-patient-results'),
            selectedText = document.getElementById('prescription-patient-selected'),
            appointment = document.getElementById('prescription-appointment'),
            form = document.getElementById('prescription-form');
        const historyInput = document.getElementById('prescription-history-search'),
            historyButton = document.getElementById('prescription-history-search-button'),
            historyBody = document.getElementById('prescription-history-body'),
            historyMessage = document.getElementById('prescription-history-message');
        let patientTimer, historyTimer;
        const esc = v => String(v ?? '').replace(/[&<>'"]/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        } [c]));

        function addRow() {
            const row = tpl.content.firstElementChild.cloneNode(true);
            row.querySelector('.remove-medicine').addEventListener('click', () => {
                if (list.children.length > 1) row.remove()
            });
            list.appendChild(row)
        }

        function filterAppointments() {
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
        async function searchPatients() {
            const q = patientSearch.value.trim();
            patientId.value = '';
            filterAppointments();
            selectedText.textContent = 'Select a patient from the search results.';
            if (!q) {
                patientResults.hidden = true;
                patientResults.innerHTML = '';
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
                patientResults.innerHTML = rows.length ? rows.map(p =>
                        '<button type="button" class="patient-search-result" data-id="' +
                        esc(p.patient_id) + '" data-name="' + esc(p.patient_name) +
                        '"><strong>' + esc(p.patient_name) + '</strong><span>Appointment ' +
                        esc(p.appointment_no || '—') + '</span></button>').join('') :
                    '<div class="patient-search-empty">No patient with a booked appointment whose scheduled time has arrived.</div>';
                patientResults.hidden = false;
                patientResults.querySelectorAll('.patient-search-result').forEach(b => b
                    .addEventListener('click', () => {
                        patientId.value = b.dataset.id;
                        patientSearch.value = b.dataset.name;
                        selectedText.textContent = 'Selected: ' + b.dataset.name;
                        patientResults.hidden = true;
                        filterAppointments()
                    }));
            } catch (e) {
                patientResults.innerHTML = '<div class="patient-search-empty">' + esc(e
                    .message) + '</div>';
                patientResults.hidden = false
            }
        }

        function showHistory(text, type) {
            historyMessage.hidden = !text;
            historyMessage.textContent = text || '';
            historyMessage.className = 'form-message ' + (type || 'success')
        }
        async function loadHistory() {
            try {
                const r = await fetch(
                    'index.php?page=ajax&action=doctor_prescription_history&q=' +
                    encodeURIComponent(historyInput.value.trim()), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message || 'Search failed.');
                historyBody.innerHTML = j.html;
                bindCancel();
            } catch (e) {
                showHistory(e.message, 'error')
            }
        }

        function bindCancel() {
            historyBody.querySelectorAll('.doctor-prescription-cancel-form').forEach(f => f
                .addEventListener('submit', async e => {
                    e.preventDefault();
                    if (!confirm('Cancel this prescription?')) return;
                    try {
                        const r = await fetch(
                            'index.php?page=ajax&action=doctor_prescription_cancel', {
                                method: 'POST',
                                body: new FormData(f),
                                headers: {
                                    Accept: 'application/json'
                                }
                            });
                        const j = await r.json();
                        if (!j.success) throw new Error(j.message ||
                            'Could not cancel prescription.');
                        showHistory(j.message, 'success');
                        loadHistory();
                    } catch (err) {
                        showHistory(err.message, 'error')
                    }
                }))
        }
        patientSearch.addEventListener('input', () => {
            clearTimeout(patientTimer);
            patientTimer = setTimeout(searchPatients, 180)
        });
        document.addEventListener('click', e => {
            if (!patientResults.contains(e.target) && e.target !== patientSearch)
                patientResults.hidden = true
        });
        form.addEventListener('submit', e => {
            if (!patientId.value) {
                e.preventDefault();
                patientSearch.setCustomValidity(
                    'Please select a patient from the search results.');
                patientSearch.reportValidity();
                setTimeout(() => patientSearch.setCustomValidity(''), 0)
            }
        });
        historyInput.addEventListener('input', () => {
            clearTimeout(historyTimer);
            historyTimer = setTimeout(loadHistory, 220)
        });
        historyButton.addEventListener('click', loadHistory);
        add.addEventListener('click', addRow);
        filterAppointments();
        addRow();
        bindCancel();
    })();
</script>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
