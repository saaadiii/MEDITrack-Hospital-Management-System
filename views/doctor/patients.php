<?php $title = 'Patient Management';
$current = 'doctor_patients';
$logoutRole = 'doctor';
$heading = 'Patient Management';
$subheading = 'View patient information, symptoms and visit history';
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
    <form
        class="toolbar"
        method="GET"
    >
        <input
            type="hidden"
            name="page"
            value="doctor_patients"
        >
        <input
            id="doctor-patient-search"
            name="search"
            value="<?= esc(
                $search
            ) ?>"
            placeholder="Search by patient ID, name, phone, email, age, gender, blood group or status"
        >
        <button
            id="doctor-patient-search-button"
            class="small-btn"
            type="button"
        >Search</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Patient</th>
                    <th>Age/Gender</th>
                    <th>Phone</th>
                    <th>Appointments</th>
                    <th>Last Appointment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody
                id="doctor-patient-table-body"
                data-empty-message="No patients with an appointment under your account were found."
            ><?php
            foreach ($patients as $p): ?>
                <tr>
                    <td><?= esc(
                        patient_display_id($p['patient_id'])
                    ) ?></td>
                    <td><strong><?= esc($p['patient_name']) ?></strong></td>
                    <td><?= esc(
                        $p['age'] ?? '—'
                    ) ?> / <?= esc($p['gender'] ?? '—') ?></td>
                    <td><?= esc($p['phone']) ?></td>
                    <td><?= esc(
                        $p['appointment_count']
                    ) ?></td>
                    <td><?= esc(
                        $p['last_appointment'] ?? 'No appointment yet'
                    ) ?></td>
                    <td><a
                            class="action-btn secondary"
                            href="index.php?page=doctor_patients&patient=<?= $p[
                                'patient_id'
                            ] ?>"
                        >View record</a></td>
                </tr><?php endforeach;
                if (
                    !$patients
                ): ?>
                <tr>
                    <td
                        colspan="7"
                        class="empty-cell"
                    >No patients with a booked/completed appointment under your account were found.
                    </td>
                </tr><?php endif;
                ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (
    $selected
): ?>
<section class="two-col">
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2><?= esc(
                    $selected['name']
                ) ?></h2>
                <p>Patient ID: <?= esc(
                    patient_display_id($selected['id'])
                ) ?> · Patient profile</p>
            </div>
        </div>
        <p><b>Age:</b> <?= esc(
             $selected['age'] ?? '—'
         ) ?> · <b>Gender:</b> <?= esc($selected['gender'] ?? '—') ?></p>
        <p><b>Phone:</b> <?= esc(
            $selected['phone']
        ) ?></p>
        <p><b>Blood group:</b> <?= esc(
            $selected['blood_group'] ?? '—'
        ) ?></p>
        <p><b>Email:</b> <?= esc($selected['email'] ?? '—') ?></p>
        <p><b>Address:</b> <?= esc(
            $selected['address'] ?? '—'
        ) ?></p>
        <p><b>Visits with you:</b> <?= esc(
            $selected['visits']
        ) ?></p>
    </section>
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Recent Symptoms</h2>
                <p>Information entered by the patient.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Symptom</th>
                        <th>Severity</th>
                        <th>Duration</th>
                        <th>Date</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody><?php
                foreach (array_slice($symptoms, 0, 10) as $s): ?>
                    <tr>
                        <td><?= esc($s['symptom']) ?></td>
                        <td><?= esc(
                            $s['severity']
                        ) ?>/10</td>
                        <td><?= esc($s['duration']) ?></td>
                        <td><?= esc($s['symptom_date']) ?></td>
                        <td><?= esc(
                            $s['notes'] ?: '—'
                        ) ?></td>
                    </tr><?php endforeach;
                    if (
                        !$symptoms
                    ): ?>
                    <tr>
                        <td
                            colspan="5"
                            class="empty-cell"
                        >No symptoms recorded.</td>
                    </tr><?php endif;
                    ?>
                </tbody>
            </table>
        </div>
    </section>
</section><?php endif; ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>My Appointments</h2>
            <p>Search your appointments and complete them only when the scheduled time has arrived.
            </p>
        </div>
        <div class="toolbar doctor-appointment-searchbar">
            <input
                type="search"
                id="doctor-appointment-search"
                placeholder="Search by patient, date, time, reason, status or appointment ID"
            >
            <button
                type="button"
                id="doctor-appointment-search-button"
                class="action-btn secondary"
            >Search</button>
        </div>
    </div>
    <div
        id="doctor-appointment-message"
        class="form-message"
        hidden
    ></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Appointment No.</th>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="doctor-appointment-table-body"><?php require __DIR__ .
                '/_appointment_rows.php'; ?></tbody>
        </table>
    </div>
</section>

<script>
    (function() {
        const input = document.getElementById('doctor-appointment-search'),
            button = document.getElementById('doctor-appointment-search-button'),
            tbody = document.getElementById('doctor-appointment-table-body'),
            msg = document.getElementById('doctor-appointment-message');
        if (!input || !tbody) return;
        let timer;

        function show(text, type) {
            if (!msg) return;
            msg.hidden = !text;
            msg.textContent = text || '';
            msg.className = 'form-message ' + (type || 'success');
        }
        async function load() {
            try {
                const r = await fetch('index.php?page=ajax&action=doctor_appointments&q=' +
                    encodeURIComponent(input.value.trim()), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message ||
                'Could not load appointments.');
                tbody.innerHTML = j.html;
                bindComplete();
            } catch (e) {
                show(e.message, 'error');
            }
        }

        function bindComplete() {
            tbody.querySelectorAll('.doctor-complete-form').forEach(form => form
                .addEventListener('submit', async e => {
                    e.preventDefault();
                    if (!confirm('Mark this appointment completed?')) return;
                    const fd = new FormData(form);
                    fd.append('q', input.value.trim());
                    try {
                        const r = await fetch(
                            'index.php?page=ajax&action=doctor_complete_appointment', {
                                method: 'POST',
                                body: fd,
                                headers: {
                                    Accept: 'application/json'
                                }
                            });
                        const j = await r.json();
                        if (!j.success) throw new Error(j.message ||
                            'Could not complete appointment.');
                        tbody.innerHTML = j.html;
                        show(j.message, 'success');
                        bindComplete();
                    } catch (err) {
                        show(err.message, 'error');
                    }
                }));
        }
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(load, 220)
        });
        button?.addEventListener('click', load);
        bindComplete();
    })();
</script>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
