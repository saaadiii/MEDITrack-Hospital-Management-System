
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const receptionistCsrf = <?= json_encode(csrf_token()) ?>;
        const doctorId = document.getElementById('slot_doctor_id');
        const availability = document.getElementById('availability_id');
        const availabilityHelp = document.getElementById('availability_help');
        const date = document.getElementById('slot_date');
        const start = document.getElementById('slot_start');
        const end = document.getElementById('slot_end');
        const initialDoctorId = <?= (int) ($editSlot['doctor_id'] ?? 0) ?>;
        const initialAvailabilityId = <?= (int) $selectedAvailabilityId ?>;
        const initialStart = <?= json_encode(
                isset($editSlot['start_time']) ? substr($editSlot['start_time'], 0, 5) : ''
            ) ?>;
        const initialEnd = <?= json_encode(
                isset($editSlot['end_time'])
                    ? substr(
                        $editSlot['end_time'],
        
                        0,
                        5
                    )
                    : ''
            ) ?>;

        const escapeHtml = function(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        };

        const formatDate = function(value) {
            if (!value) return '';
            const parts = String(value).split('-');
            if (parts.length !== 3) return value;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                'Sep', 'Oct', 'Nov', 'Dec'
            ];
            return parts[2] + ' ' + (months[Number(parts[1]) - 1] || parts[1]) + ' ' +
                parts[0];
        };

        const formatTime = function(value) {
            if (!value) return '';
            const parts = String(value).split(':');
            let hour = Number(parts[0]);
            if (Number.isNaN(hour)) return value;
            const suffix = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12 || 12;
            return String(hour).padStart(2, '0') + ':' + parts[1] + ' ' + suffix;
        };

        const resetAvailability = function(message) {
            if (!availability) return;
            availability.innerHTML = '<option value="">' + escapeHtml(message ||
                'Select doctor first') + '</option>';
            availability.disabled = true;
            if (availabilityHelp) availabilityHelp.textContent =
                'Choose a doctor to load their available dates and times.';
            if (date) date.value = '';
            if (start) {
                start.value = '';
                start.removeAttribute('min');
                start.removeAttribute('max');
            }
            if (end) {
                end.value = '';
                end.removeAttribute('min');
                end.removeAttribute('max');
            }
        };

        const applyAvailability = function(preserveTimes) {
            if (!availability || !date || !start || !end) return;
            const option = availability.options[availability.selectedIndex];
            if (!option || !option.dataset.date) return;
            date.value = option.dataset.date;
            start.min = option.dataset.start;
            start.max = option.dataset.end;
            end.min = option.dataset.start;
            end.max = option.dataset.end;
            if (!preserveTimes || !start.value) start.value = option.dataset.start;
            if (!preserveTimes || !end.value) end.value = option.dataset.end;
            if (availabilityHelp) {
                availabilityHelp.textContent = 'Available ' + formatDate(option.dataset
                        .date) + ', ' + formatTime(option.dataset.start) + ' – ' +
                    formatTime(option.dataset.end) + '.';
            }
        };

        const loadAvailabilities = async function(selectedId, preserveTimes) {
            if (!doctorId || !doctorId.value || !availability) {
                resetAvailability();
                return;
            }
            availability.disabled = true;
            availability.innerHTML =
                '<option value="">Loading availability...</option>';
            if (availabilityHelp) availabilityHelp.textContent =
                'Loading this doctor’s availability...';
            try {
                const response = await fetch(
                    'index.php?page=ajax&action=receptionist_doctor_availability&doctor_id=' +
                    encodeURIComponent(doctorId.value), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                const json = await response.json();
                if (!json.success) throw new Error(json.message ||
                    'Could not load availability.');
                const rows = json.data || [];
                if (!rows.length) {
                    availability.innerHTML =
                        '<option value="">No availability found</option>';
                    availability.disabled = true;
                    if (availabilityHelp) availabilityHelp.textContent =
                        'This doctor has no upcoming availability.';
                    if (date) date.value = '';
                    return;
                }
                availability.innerHTML =
                    '<option value="">Select date & time</option>' + rows.map(
                        function(av) {
                            const selected = Number(selectedId || 0) === Number(av
                                .id) ? ' selected' : '';
                            return '<option value="' + escapeHtml(av.id) +
                                '" data-date="' + escapeHtml(av.available_date) +
                                '" data-start="' + escapeHtml(String(av.start_time)
                                    .slice(0, 5)) + '" data-end="' + escapeHtml(
                                    String(av.end_time).slice(0, 5)) + '"' +
                                selected + '>' + escapeHtml(formatDate(av
                                    .available_date) + ' • ' + formatTime(av
                                    .start_time) + ' – ' + formatTime(av
                                    .end_time)) + '</option>';
                        }).join('');
                availability.disabled = false;
                if (selectedId && availability.value) {
                    if (preserveTimes) {
                        start.value = initialStart;
                        end.value = initialEnd;
                    }
                    applyAvailability(Boolean(preserveTimes));
                } else if (availabilityHelp) {
                    availabilityHelp.textContent =
                        'Select one of this doctor’s available dates and times.';
                }
            } catch (error) {
                availability.innerHTML =
                    '<option value="">Could not load availability</option>';
                availability.disabled = true;
                if (availabilityHelp) availabilityHelp.textContent = error.message;
            }
        };

        if (doctorId) {
            doctorId.addEventListener('change', function() {
                resetAvailability();
                if (doctorId.value) loadAvailabilities(0, false);
            });
        }

        if (availability) availability.addEventListener('change', function() {
            applyAvailability(false);
        });
        if (initialDoctorId && doctorId && availability) loadAvailabilities(
            initialAvailabilityId, true);

        document.querySelectorAll('form[data-validate="slot"]').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                const startValue = form.querySelector('[name="start_time"]')
                    ?.value;
                const endValue = form.querySelector('[name="end_time"]')
                    ?.value;
                if (!doctorId?.value) {
                    event.preventDefault();
                    alert('Please select a doctor.');
                    return;
                }
                if (startValue && endValue && startValue >= endValue) {
                    event.preventDefault();
                    alert('End time must be after start time.');
                }
            });
        });

        const removeEditFromUrl = function() {
            const url = new URL(window.location.href);
            if (url.searchParams.has('edit')) {
                url.searchParams.delete('edit');
                window.history.replaceState({}, '', url.toString());
            }
        };

        const resetPatientForm = function() {
            const form = document.getElementById('patient-form');
            if (!form) return;
            form.querySelector('[name="id"]').value = '0';
            ['name', 'phone', 'email', 'age', 'address', 'emergency_contact'].forEach(
                function(name) {
                    const field = form.querySelector('[name="' + name + '"]');
                    if (field) field.value = '';
                });
            const gender = form.querySelector('[name="gender"]');
            if (gender) gender.value = 'Male';
            const status = form.querySelector('[name="status"]');
            if (status) status.value = 'Active';
            const title = document.getElementById('patient-form-title');
            if (title) title.textContent = 'Register Patient';
            const submit = document.getElementById('patient-submit-btn');
            if (submit) submit.textContent = 'Register Patient';
            removeEditFromUrl();
            form.querySelector('[name="name"]')?.focus();
        };

        const resetSlotForm = function() {
            const form = document.getElementById('slot-form');
            if (!form) return;
            form.querySelector('[name="id"]').value = '0';
            if (doctorId) doctorId.value = '';
            resetAvailability('Select doctor first');
            const status = form.querySelector('[name="status"]');
            if (status) status.value = 'Available';
            const title = document.getElementById('slot-form-title');
            if (title) title.textContent = 'Create Doctor Slot';
            const submit = document.getElementById('slot-submit-btn');
            if (submit) submit.textContent = 'Create Slot';
            removeEditFromUrl();
            doctorId?.focus();
        };

        const resetEmergencyForm = function() {
            const form = document.getElementById('emergency-form');
            if (!form) return;
            form.querySelector('[name="id"]').value = '0';
            ['patient_name', 'age', 'phone', 'emergency_contact', 'emergency_type',
                'notes'
            ].forEach(function(name) {
                const field = form.querySelector('[name="' + name + '"]');
                if (field) field.value = '';
            });
            const gender = form.querySelector('[name="gender"]');
            if (gender) gender.value = 'Male';
            const status = form.querySelector('[name="status"]');
            if (status) status.value = 'Waiting';
            const title = document.getElementById('emergency-form-title');
            if (title) title.textContent = 'Emergency Fast Registration';
            const submit = document.getElementById('emergency-submit-btn');
            if (submit) submit.textContent = 'Save Emergency';
            removeEditFromUrl();
            form.querySelector('[name="patient_name"]')?.focus();
        };

        document.querySelectorAll('.js-cancel-form').forEach(function(button) {
            button.addEventListener('click', function() {
                const type = button.dataset.form;
                if (type === 'patient') resetPatientForm();
                if (type === 'slot') resetSlotForm();
                if (type === 'emergency') resetEmergencyForm();
            });
        });

        const search = document.getElementById('receptionist-patient-search');
        const searchButton = document.getElementById('receptionist-patient-search-button');
        const results = document.getElementById('patient-live-results');
        if (search && results) {
            let timer;
            const loadPatients = async function() {
                try {
                    const q = search.value.trim();
                    const response = await fetch(
                        'index.php?page=ajax&action=receptionist_section_rows&section=patients&q=' +
                        encodeURIComponent(q), {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                    const json = await response.json();
                    if (!json.success) throw new Error(json.message ||
                        'Search failed.');
                    results.innerHTML = json.html;
                } catch (e) {
                    results.innerHTML =
                        '<tr><td colspan="6" class="empty-cell">Search could not be loaded.</td></tr>';
                }
            };
            search.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(loadPatients, 220);
            });
            search.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(timer);
                    loadPatients();
                }
            });
            searchButton?.addEventListener('click', loadPatients);
        }
    });
</script>

<script>
    (function() {
        const liveMessage = document.getElementById('receptionist-live-message');
        const targets = {
            patients: 'patient-live-results',
            slots: 'receptionist-slot-table-body',
            appointments: 'receptionist-appointment-table-body',
            emergency: 'receptionist-emergency-table-body'
        };

        function showMessage(text, type) {
            if (!liveMessage) return;
            liveMessage.hidden = !text;
            liveMessage.textContent = text || '';
            liveMessage.className = 'form-message ' + (type || 'success');
        }

        function replaceRows(section, html) {
            const id = targets[section],
                el = id ? document.getElementById(id) : null;
            if (el) el.innerHTML = html;
        }
        async function submitLive(form, preserveSlot) {
            const confirmText = form.dataset.confirm;
            if (confirmText && !confirm(confirmText)) return;
            const fd = new FormData(form);
            if ((fd.get('section') || '') === 'slots') {
                const filter = document.querySelector(
                    '#receptionist-slot-filter input[name="date"]');
                if (filter) fd.append('filter_date', filter.value || '');
                const doctor = document.querySelector(
                    '#receptionist-slot-filter select[name="doctor_id"]');
                if (doctor) fd.append('filter_doctor_id', doctor.value || '');
                const department = document.querySelector(
                    '#receptionist-slot-filter select[name="department"]');
                if (department) fd.append('filter_department', department.value || '');
            }
            if ((fd.get('section') || '') === 'patients') {
                const q = document.getElementById('receptionist-patient-search');
                if (q) fd.append('search_q', q.value.trim());
            }
            if ((fd.get('section') || '') === 'appointments') {
                const q = document.getElementById('receptionist-appointment-search');
                if (q) fd.append('search_q', q.value.trim());
            }
            if ((fd.get('section') || '') === 'emergency') {
                const q = document.getElementById('receptionist-emergency-search');
                if (q) fd.append('search_q', q.value.trim());
            }
            const btn = form.querySelector('button[type="submit"],button:not([type])');
            if (btn) btn.disabled = true;
            try {
                const r = await fetch(
                'index.php?page=ajax&action=receptionist_live_action', {
                    method: 'POST',
                    body: fd,
                    headers: {
                        Accept: 'application/json'
                    }
                });
                const j = await r.json();
                if (!j.success) throw new Error(j.message || 'Action failed.');
                replaceRows(j.section, j.html);
                showMessage(j.message, 'success');
                if (!preserveSlot && form.classList.contains('receptionist-live-save')) {
                    const section = fd.get('section');
                    if (section === 'patients' || section === 'emergency') {
                        const id = form.querySelector('[name="id"]');
                        if (id) id.value = '0';
                        const url = new URL(window.location.href);
                        url.searchParams.delete('edit');
                        history.replaceState({}, '', url);
                        if (section === 'patients') {
                            ['name', 'phone', 'email', 'age', 'address',
                                'emergency_contact'].forEach(n => {
                                const f = form.querySelector('[name="' + n + '"]');
                                if (f) f.value = ''
                            });
                            const g = form.querySelector('[name="gender"]'),
                                st = form.querySelector('[name="status"]');
                            if (g) g.value = 'Male';
                            if (st) st.value = 'Active';
                            document.getElementById('patient-form-title').textContent =
                                'Register Patient';
                            document.getElementById('patient-submit-btn').textContent =
                                'Register Patient';
                        }
                        if (section === 'emergency') {
                            ['patient_name', 'age', 'phone', 'emergency_contact',
                                'emergency_type', 'notes'
                            ].forEach(n => {
                                const f = form.querySelector('[name="' + n + '"]');
                                if (f) f.value = ''
                            });
                            const g = form.querySelector('[name="gender"]'),
                                st = form.querySelector('[name="status"]');
                            if (g) g.value = 'Male';
                            if (st) st.value = 'Waiting';
                            document.getElementById('emergency-form-title').textContent =
                                'Emergency Fast Registration';
                            document.getElementById('emergency-submit-btn').textContent =
                                'Save Emergency';
                        }
                    }
                }
                if (preserveSlot) {
                    const id = form.querySelector('[name="id"]');
                    if (id) id.value = '0';
                    const url = new URL(window.location.href);
                    url.searchParams.delete('edit');
                    history.replaceState({}, '', url);
                    const title = document.getElementById('slot-form-title'),
                        submit = document.getElementById('slot-submit-btn');
                    if (title) title.textContent = 'Create Doctor Slot';
                    if (submit) submit.textContent = 'Create Slot';
                }
            } catch (e) {
                showMessage(e.message, 'error')
            } finally {
                if (btn) btn.disabled = false;
            }
        }
        document.addEventListener('submit', function(e) {
            const actionForm = e.target.closest('.receptionist-live-action');
            if (actionForm) {
                e.preventDefault();
                submitLive(actionForm, false);
                return;
            }
            const save = e.target.closest('.receptionist-live-save');
            if (save) {
                e.preventDefault();
                submitLive(save, save.id === 'slot-form');
                return;
            }
        });
        const slotFilter = document.getElementById('receptionist-slot-filter');
        async function loadSlots() {
            if (!slotFilter) return;
            const date = slotFilter.querySelector('[name="date"]')?.value || '';
            const doctorId = slotFilter.querySelector('[name="doctor_id"]')?.value || '';
            const department = slotFilter.querySelector('[name="department"]')?.value || '';
            try {
                const r = await fetch(
                    'index.php?page=ajax&action=receptionist_section_rows&section=slots&date=' +
                    encodeURIComponent(date) + '&doctor_id=' + encodeURIComponent(
                        doctorId) + '&department=' + encodeURIComponent(department), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message || 'Could not filter slots.');
                replaceRows('slots', j.html);
            } catch (e) {
                showMessage(e.message, 'error')
            }
        }
        if (slotFilter) {
            slotFilter.addEventListener('submit', e => {
                e.preventDefault();
                loadSlots()
            });
            slotFilter.querySelector('[name="date"]')?.addEventListener('change', loadSlots);
            slotFilter.querySelector('[name="doctor_id"]')?.addEventListener('change',
                loadSlots);
            slotFilter.querySelector('[name="department"]')?.addEventListener('change',
                loadSlots);
        }

        const appointmentSearch = document.getElementById('receptionist-appointment-search');
        const appointmentSearchButton = document.getElementById(
            'receptionist-appointment-search-button');
        let appointmentSearchTimer = null;
        async function loadReceptionistAppointments() {
            if (!appointmentSearch) return;
            const q = appointmentSearch.value.trim();
            if (appointmentSearchButton) appointmentSearchButton.disabled = true;
            try {
                const r = await fetch(
                    'index.php?page=ajax&action=receptionist_appointment_search&q=' +
                    encodeURIComponent(q), {
                        headers: {
                            Accept: 'application/json'
                        },
                        cache: 'no-store'
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message ||
                    'Could not search appointments.');
                replaceRows('appointments', j.html);
                showMessage('', 'success');
            } catch (e) {
                showMessage(e.message, 'error');
            } finally {
                if (appointmentSearchButton) appointmentSearchButton.disabled = false;
            }
        }
        if (appointmentSearchButton) appointmentSearchButton.addEventListener('click',
            loadReceptionistAppointments);
        if (appointmentSearch) {
            appointmentSearch.addEventListener('input', function() {
                clearTimeout(appointmentSearchTimer);
                appointmentSearchTimer = setTimeout(loadReceptionistAppointments, 220);
            });
            appointmentSearch.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(appointmentSearchTimer);
                    loadReceptionistAppointments();
                }
            });
        }

        const emergencySearchForm = document.getElementById(
            'receptionist-emergency-search-form');
        const emergencySearch = document.getElementById('receptionist-emergency-search');
        let emergencySearchTimer = null;
        async function loadEmergencies() {
            if (!emergencySearch) return;
            const q = emergencySearch.value.trim();
            try {
                const r = await fetch(
                    'index.php?page=ajax&action=receptionist_section_rows&section=emergency&q=' +
                    encodeURIComponent(q), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message ||
                    'Could not search emergency queue.');
                replaceRows('emergency', j.html);
            } catch (e) {
                showMessage(e.message, 'error');
            }
        }
        if (emergencySearchForm) emergencySearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loadEmergencies();
        });
        if (emergencySearch) emergencySearch.addEventListener('input', function() {
            clearTimeout(emergencySearchTimer);
            emergencySearchTimer = setTimeout(loadEmergencies, 220);
        });
    })();
</script>


<script>
    (function() {
        const patientSearch = document.getElementById('receptionist-booking-patient-search');
        const patientId = document.getElementById('receptionist-booking-patient-id');
        const patientResults = document.getElementById('receptionist-booking-patient-results');
        const patientSelected = document.getElementById(
        'receptionist-booking-patient-selected');
        const specialization = document.getElementById('receptionist-booking-specialization');
        const bookingDate = document.getElementById('receptionist-booking-date');
        const doctorList = document.getElementById('receptionist-booking-doctors');
        const profilePanel = document.getElementById('receptionist-booking-profile-panel');
        const profile = document.getElementById('receptionist-booking-profile');
        const reason = document.getElementById('receptionist-booking-reason');
        const csrf = document.getElementById('receptionist-booking-csrf')?.value || '';
        if (!patientSearch || !patientId || !specialization || !doctorList || !profilePanel || !
            profile) return;
        const esc = v => String(v ?? '').replace(/[&<>'\"]/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '\"': '&quot;'
        } [c]));
        const patientCode = id => 'P' + String(Number(id) || 0).padStart(4, '0');
        const doctorCode = id => 'D' + String(Number(id) || 0).padStart(4, '0');
        const fmtDate = v => {
            if (!v) return '';
            const [y, m, d] = String(v).split('-');
            const ms = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
                'Oct', 'Nov', 'Dec'
            ];
            return d + ' ' + (ms[Number(m) - 1] || m) + ' ' + y
        };
        const fmtTime = v => {
            if (!v) return '';
            const p = String(v).split(':');
            let h = Number(p[0]);
            const ap = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return String(h).padStart(2, '0') + ':' + p[1] + ' ' + ap
        };
        let patientTimer;
        async function loadPatients() {
            const q = patientSearch.value.trim();
            if (!q) {
                patientResults.innerHTML = '';
                patientResults.hidden = true;
                return;
            }
            try {
                const r = await fetch(
                    'index.php?page=ajax&action=search_receptionist_patients&active_only=1&q=' +
                    encodeURIComponent(q), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message || 'Search failed.');
                const rows = j.data || [];
                patientResults.innerHTML = rows.length ? rows.map(p =>
                        '<button type="button" class="admin-picker-option" data-id="' + esc(
                            p.id) + '" data-name="' + esc(p.name) + '"><strong>' + esc(
                            patientCode(p.id)) + ' · ' + esc(p.name) + '</strong><span>' +
                        esc(p.phone || '—') + '</span></button>').join('') :
                    '<div class="admin-picker-empty">No matching patients found.</div>';
                patientResults.hidden = false;
                patientResults.querySelectorAll('[data-id]').forEach(b => b
                    .addEventListener('click', () => {
                        patientId.value = b.dataset.id;
                        patientSearch.value = b.dataset.name;
                        patientSelected.textContent = 'Selected: ' + patientCode(b
                            .dataset.id) + ' · ' + b.dataset.name;
                        patientResults.hidden = true;
                    }));
            } catch (e) {
                patientResults.innerHTML = '<div class="admin-picker-empty">' + esc(e
                    .message) + '</div>';
                patientResults.hidden = false;
            }
        }
        patientSearch.addEventListener('focus', () => {
            if (patientSearch.value.trim()) loadPatients();
        });
        patientSearch.addEventListener('input', () => {
            patientId.value = '';
            patientSelected.textContent = 'Select a registered patient.';
            clearTimeout(patientTimer);
            if (!patientSearch.value.trim()) {
                patientResults.innerHTML = '';
                patientResults.hidden = true;
                return;
            }
            patientTimer = setTimeout(loadPatients, 180)
        });
        document.addEventListener('click', e => {
            if (!patientResults.contains(e.target) && e.target !== patientSearch)
                patientResults.hidden = true;
        });
        async function loadDoctors() {
            profilePanel.hidden = true;
            profile.innerHTML = '';
            const spec = specialization.value.trim();
            if (!spec) {
                doctorList.innerHTML =
                    '<div class="booking-placeholder">Select a specialization to see doctors.</div>';
                return
            }
            doctorList.innerHTML =
                '<div class="booking-placeholder">Loading doctors...</div>';
            try {
                const date = bookingDate?.value.trim() || '';
                const r = await fetch(
                    'index.php?page=ajax&action=receptionist_booking_doctors&specialization=' +
                    encodeURIComponent(spec) + '&date=' + encodeURIComponent(date), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message || 'Could not load doctors.');
                const rows = j.data || [];
                doctorList.innerHTML = rows.length ? rows.map(d =>
                        '<article class="doctor-choice-card"><h3>' + esc(doctorCode(d.id)) +
                        ' · ' + esc(d.name) + '</h3><span class="doctor-specialty">' + esc(d
                            .specialization) + '</span>' + (d.qualification ?
                            '<p><strong>Qualification:</strong> ' + esc(d.qualification) +
                            '</p>' : '') + '<p><strong>Available slots:</strong> ' + esc(d
                            .available_slot_count || 0) +
                        '</p><div class="doctor-card-actions"><button type="button" class="action-btn secondary receptionist-doctor-profile" data-id="' +
                        esc(d.id) + '">View profile & slots</button></div></article>').join(
                        '') :
                    '<div class="booking-placeholder">No active doctors were found for this specialization.</div>';
                doctorList.querySelectorAll('.receptionist-doctor-profile').forEach(b => b
                    .addEventListener('click', () => loadProfile(b.dataset.id)));
            } catch (e) {
                doctorList.innerHTML = '<div class="booking-placeholder">' + esc(e
                    .message) + '</div>';
            }
        }
        async function loadProfile(did) {
            profilePanel.hidden = false;
            profile.innerHTML =
                '<div class="doctor-loading">Loading doctor profile and available slots...</div>';
            try {
                const date = bookingDate?.value.trim() || '';
                const r = await fetch(
                    'index.php?page=ajax&action=receptionist_booking_profile&doctor_id=' +
                    encodeURIComponent(did) + '&date=' + encodeURIComponent(date), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message ||
                    'Could not load doctor profile.');
                const d = j.doctor;
                let slots =
                    '<div class="slot-empty">No available slots have been created for this doctor.</div>';
                if (Array.isArray(d.slots) && d.slots.length) {
                    slots = '<div class="booking-slot-list">' + d.slots.map(sl =>
                        '<div class="booking-slot"><div class="booking-slot-date">' +
                        esc(fmtDate(sl.slot_date)) +
                        '</div><div class="booking-slot-time">' + esc(fmtTime(sl
                            .start_time)) + ' - ' + esc(fmtTime(sl.end_time)) +
                        '</div><button type="button" class="small-btn receptionist-book-slot" data-slot="' +
                        esc(sl.id) + '" data-doctor="' + esc(did) +
                        '">Book this slot</button></div>').join('') + '</div>';
                }
                profile.innerHTML =
                    '<div class="doctor-profile-summary"><div class="doctor-profile-card"><h2>' +
                    esc(doctorCode(d.id)) + ' · ' + esc(d.name) +
                    '</h2><span class="doctor-profile-specialty">' + esc(d.specialization) +
                    '</span><div class="doctor-profile-meta">' + (d.qualification ?
                        '<div><strong>Qualification:</strong> ' + esc(d.qualification) +
                        '</div>' : '') + (d.department ?
                        '<div><strong>Department:</strong> ' + esc(d.department) +
                        '</div>' : '') + (d.room_number ?
                        '<div><strong>Consultation room:</strong> ' + esc(d.room_number) +
                        '</div>' : '') +
                    '</div></div><div class="doctor-slot-area"><h3>Available appointment slots</h3>' +
                    slots + '</div></div>';
                profile.querySelectorAll('.receptionist-book-slot').forEach(b => b
                    .addEventListener('click', () => bookSlot(b)));
            } catch (e) {
                profile.innerHTML = '<div class="booking-placeholder">' + esc(e.message) +
                    '</div>';
            }
        }
        async function bookSlot(btn) {
            if (!patientId.value) {
                alert('Please select a patient first.');
                patientSearch.focus();
                return
            }
            btn.disabled = true;
            const fd = new FormData();
            fd.append('csrf_token', csrf);
            fd.append('patient_id', patientId.value);
            fd.append('slot_id', btn.dataset.slot);
            fd.append('reason', reason?.value.trim() || '');
            try {
                const r = await fetch(
                    'index.php?page=ajax&action=receptionist_book_appointment', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const j = await r.json();
                if (!j.success) throw new Error(j.message || 'Could not book appointment.');
                const m = document.getElementById('receptionist-live-message');
                if (m) {
                    m.hidden = false;
                    m.className = 'form-message success';
                    m.textContent = j.message;
                }
                await loadProfile(btn.dataset.doctor);
            } catch (e) {
                alert(e.message)
            } finally {
                btn.disabled = false;
            }
        }
        specialization.addEventListener('change', loadDoctors);
        if (bookingDate) bookingDate.addEventListener('change', loadDoctors);
    })();
</script>
