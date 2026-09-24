// Shared date/time formatters used by patient appointment search and booking views.
function formatDate(value) {
    if (!value) return '';
    const parts = String(value).split('-');
    if (parts.length !== 3) return String(value);
    const months = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec'
    ];
    const monthIndex = Number(parts[1]) - 1;
    if (monthIndex < 0 || monthIndex > 11) return String(value);
    return parts[2] + ' ' + months[monthIndex] + ' ' + parts[0];
}

function formatTime(value) {
    if (!value) return '';
    const parts = String(value).split(':');
    if (parts.length < 2) return String(value);
    let h = Number(parts[0]);
    if (Number.isNaN(h)) return String(value);
    const suffix = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return String(h).padStart(2, '0') + ':' + parts[1] + ' ' + suffix;
}

document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';

    // Client-side validation: convenience only; PHP validates again on the server.
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.classList.contains('ajax-cancel-form')) return;

            const required = form.querySelectorAll('[required]');
            let valid = true;
            required.forEach(function (input) {
                if (!String(input.value).trim()) {
                    input.setCustomValidity('This field is required.');
                    valid = false;
                } else {
                    input.setCustomValidity('');
                }
            });

            const password = form.querySelector('#password');
            const confirm = form.querySelector('#confirm_password');
            if (password && password.value.length < 6) {
                password.setCustomValidity('Password must be at least 6 characters.');
                valid = false;
            }
            if (password && confirm && password.value !== confirm.value) {
                confirm.setCustomValidity('Passwords do not match.');
                valid = false;
            } else if (confirm) {
                confirm.setCustomValidity('');
            }

            if (!valid) event.preventDefault();
        });
    });

    // Keep the original confirmation behavior for normal delete/cancel forms.
    document.querySelectorAll('.delete-form:not(.ajax-cancel-form)').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!confirm('Are you sure you want to delete/cancel this?')) event.preventDefault();
        });
    });

    function debounce(fn, delay) {
        let timer;
        return function () {
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(null, args);
            }, delay);
        };
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>'"]/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char];
        });
    }

    function showSearchMessage(tbody, text) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty-cell">' + esc(text) + '</td></tr>';
    }

    const symptomSearch = document.getElementById('symptom-search');
    const symptomBody = document.getElementById('symptom-table-body');
    if (symptomSearch && symptomBody) {
        const renderSymptoms = function (rows) {
            if (!rows.length)
                return showSearchMessage(symptomBody, symptomBody.dataset.emptyMessage);
            symptomBody.innerHTML = rows
                .map(function (row) {
                    return (
                        '<tr>' +
                        '<td>' +
                        esc(row.symptom) +
                        '</td>' +
                        '<td>' +
                        esc(row.severity) +
                        '/10</td>' +
                        '<td>' +
                        esc(row.duration) +
                        '</td>' +
                        '<td>' +
                        esc(row.frequency) +
                        '</td>' +
                        '<td>' +
                        esc(formatDate(row.symptom_date)) +
                        '</td>' +
                        '<td>' +
                        (row.notes ? esc(row.notes) : '—') +
                        '</td>' +
                        '<td class="actions"><a class="action-btn secondary" href="index.php?page=symptoms&edit=' +
                        encodeURIComponent(row.id) +
                        '">Edit</a> ' +
                        '<form class="inline-form symptom-delete-form ajax-cancel-form"><input type="hidden" name="csrf_token" value="' +
                        esc(csrf) +
                        '"><input type="hidden" name="id" value="' +
                        esc(row.id) +
                        '"><button type="submit" class="action-btn danger">Delete</button></form></td>' +
                        '</tr>'
                    );
                })
                .join('');
        };
        const performSearch = function () {
            const q = symptomSearch.value.trim();
            fetch('index.php?page=ajax&action=search_symptoms&q=' + encodeURIComponent(q), {
                headers: { Accept: 'application/json' }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Search failed.');
                    renderSymptoms(data.rows || []);
                })
                .catch(function (error) {
                    console.error(error);
                });
        };
        symptomBody.addEventListener('submit', function (event) {
            const form = event.target.closest('.symptom-delete-form');
            if (!form) return;
            event.preventDefault();
            if (!confirm('Delete this symptom?')) return;
            const body = new FormData(form);
            body.append('q', symptomSearch.value.trim());
            fetch('index.php?page=ajax&action=delete_symptom', {
                method: 'POST',
                body: body,
                headers: { Accept: 'application/json' }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Could not delete symptom.');
                    renderSymptoms(data.rows || []);
                })
                .catch(function (error) {
                    alert(error.message);
                });
        });
        const runSearch = debounce(performSearch, 250);
        symptomSearch.addEventListener('input', runSearch);
        document.getElementById('symptom-search-button')?.addEventListener('click', performSearch);
    }

    const appointmentSearch = document.getElementById('appointment-search');
    const appointmentBody = document.getElementById('appointment-table-body');
    if (appointmentSearch && appointmentBody) {
        const renderAppointments = function (rows) {
            if (!rows.length)
                return showSearchMessage(appointmentBody, appointmentBody.dataset.emptyMessage);
            appointmentBody.innerHTML = rows
                .map(function (row) {
                    const action =
                        row.status === 'Booked'
                            ? '<form class="inline-form patient-appointment-live-form ajax-cancel-form"><input type="hidden" name="csrf_token" value="' +
                              esc(csrf) +
                              '"><input type="hidden" name="action" value="cancel"><input type="hidden" name="appointment_id" value="' +
                              esc(row.id) +
                              '"><button class="action-btn danger" type="submit">Cancel</button></form>'
                            : row.status === 'Cancelled'
                              ? '<form class="inline-form patient-appointment-live-form ajax-cancel-form"><input type="hidden" name="csrf_token" value="' +
                                esc(csrf) +
                                '"><input type="hidden" name="action" value="delete"><input type="hidden" name="appointment_id" value="' +
                                esc(row.id) +
                                '"><button class="action-btn danger" type="submit">Delete</button></form>'
                              : '—';
                    const displayId =
                        row.doctor_id && row.doctor_serial
                            ? 'D' +
                              String(row.doctor_id).padStart(4, '0') +
                              '-' +
                              String(row.doctor_serial).padStart(3, '0')
                            : String(row.id).padStart(3, '0');
                    return (
                        '<tr><td>' +
                        esc(displayId) +
                        '</td><td>' +
                        esc(row.doctor_name) +
                        '</td><td>' +
                        esc(row.specialization) +
                        '</td><td>' +
                        esc(formatDate(row.appointment_date)) +
                        '</td><td>' +
                        esc(formatTime(row.appointment_time)) +
                        '</td><td><span class="status ' +
                        esc(String(row.status).toLowerCase().replace(/\s+/g, '-')) +
                        '">' +
                        esc(row.status) +
                        '</span></td><td>' +
                        action +
                        '</td></tr>'
                    );
                })
                .join('');
        };
        const performSearch = function () {
            const q = appointmentSearch.value.trim();
            fetch('index.php?page=ajax&action=search_appointments&q=' + encodeURIComponent(q), {
                headers: { Accept: 'application/json' }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Search failed.');
                    renderAppointments(data.rows || []);
                })
                .catch(function (error) {
                    console.error(error);
                });
        };
        appointmentBody.addEventListener('submit', function (event) {
            const form = event.target.closest('.patient-appointment-live-form');
            if (!form) return;
            event.preventDefault();
            const action = form.querySelector('[name="action"]')?.value || '';
            if (
                !confirm(
                    action === 'delete'
                        ? 'Delete this cancelled appointment?'
                        : 'Cancel this appointment?'
                )
            )
                return;
            const body = new FormData(form);
            body.append('q', appointmentSearch.value.trim());
            fetch('index.php?page=ajax&action=patient_appointment_action', {
                method: 'POST',
                body: body,
                headers: { Accept: 'application/json' }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success)
                        throw new Error(data.message || 'Could not update appointment.');
                    renderAppointments(data.rows || []);
                })
                .catch(function (error) {
                    alert(error.message);
                });
        });
        const runSearch = debounce(performSearch, 250);
        appointmentSearch.addEventListener('input', runSearch);
        document
            .getElementById('appointment-search-button')
            ?.addEventListener('click', performSearch);
    }

    function formatDate(value) {
        if (!value) return '';
        const parts = String(value).split('-');
        if (parts.length !== 3) return value;
        const months = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec'
        ];
        return parts[2] + ' ' + months[Number(parts[1]) - 1] + ' ' + parts[0];
    }

    function formatTime(value) {
        if (!value) return '';
        const parts = String(value).split(':');
        let h = Number(parts[0]);
        const suffix = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return String(h).padStart(2, '0') + ':' + parts[1] + ' ' + suffix;
    }
});

// Doctor patient live search across patient record fields.
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('doctor-patient-search');
    const tbody = document.getElementById('doctor-patient-table-body');
    const button = document.getElementById('doctor-patient-search-button');
    if (!input || !tbody) return;

    let timer;
    const escapeHtml = function (value) {
        return String(value ?? '').replace(/[&<>'"]/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char];
        });
    };

    const render = function (rows) {
        if (!rows.length) {
            tbody.innerHTML =
                '<tr><td colspan="7" class="empty-cell">' +
                escapeHtml(tbody.dataset.emptyMessage || 'No patients found.') +
                '</td></tr>';
            return;
        }
        tbody.innerHTML = rows
            .map(function (p) {
                const age = p.age || '—';
                const gender = p.gender || '—';
                const last = p.last_appointment || '—';
                return (
                    '<tr>' +
                    '<td>' +
                    escapeHtml('P' + String(Number(p.patient_id) || 0).padStart(4, '0')) +
                    '</td>' +
                    '<td><strong>' +
                    escapeHtml(p.patient_name) +
                    '</strong></td>' +
                    '<td>' +
                    escapeHtml(age) +
                    ' / ' +
                    escapeHtml(gender) +
                    '</td>' +
                    '<td>' +
                    escapeHtml(p.phone) +
                    '</td>' +
                    '<td>' +
                    escapeHtml(p.appointment_count) +
                    '</td>' +
                    '<td>' +
                    escapeHtml(last) +
                    '</td>' +
                    '<td><a class="action-btn secondary" href="index.php?page=doctor_patients&patient=' +
                    encodeURIComponent(p.patient_id) +
                    '">View record</a></td>' +
                    '</tr>'
                );
            })
            .join('');
    };

    const search = function () {
        const q = input.value.trim();
        fetch('index.php?page=ajax&action=search_doctor_patients&q=' + encodeURIComponent(q), {
            headers: { Accept: 'application/json' }
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Search failed.');
                render(data.data || []);
            })
            .catch(function (err) {
                console.error(err);
            });
    };

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(search, 180);
    });
    if (button) button.addEventListener('click', search);
});

// Patient appointment finder: specialization -> doctors -> doctor profile + available slots.
document.addEventListener('DOMContentLoaded', function () {
    const specialization = document.getElementById('specialization-select');
    const doctorList = document.getElementById('specialized-doctors');
    const bookingDate = document.getElementById('booking-date-filter');
    const profilePanel = document.getElementById('doctor-booking-panel');
    const profile = document.getElementById('doctor-booking-profile');
    const csrf = document.getElementById('appointment-csrf-token')?.value || '';
    if (!specialization || !doctorList || !profilePanel || !profile) return;

    const escHtml = function (value) {
        return String(value ?? '').replace(/[&<>'"]/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char];
        });
    };

    const loadDoctors = function () {
        const value = specialization.value.trim();
        profilePanel.hidden = true;
        profile.innerHTML = '';
        if (!value) {
            doctorList.innerHTML =
                '<div class="booking-placeholder">Select a specialization to see doctors in that field.</div>';
            return;
        }

        doctorList.innerHTML = '<div class="booking-placeholder">Loading doctors...</div>';
        const date = bookingDate ? bookingDate.value.trim() : '';
        fetch(
            'index.php?page=ajax&action=doctors_by_specialization&specialization=' +
                encodeURIComponent(value) +
                '&date=' +
                encodeURIComponent(date),
            {
                headers: { Accept: 'application/json' }
            }
        )
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Could not load doctors.');
                const rows = data.data || [];
                if (!rows.length) {
                    doctorList.innerHTML =
                        '<div class="booking-placeholder">No active doctors were found for this specialization.</div>';
                    return;
                }
                doctorList.innerHTML = rows
                    .map(function (d) {
                        const qualification = d.qualification
                            ? '<p><strong>Qualification:</strong> ' +
                              escHtml(d.qualification) +
                              '</p>'
                            : '';
                        const experience =
                            Number(d.experience || 0) > 0
                                ? '<p><strong>Experience:</strong> ' +
                                  escHtml(d.experience) +
                                  ' years</p>'
                                : '';
                        const available = Number(d.available_slot_count || 0);
                        return (
                            '<article class="doctor-choice-card">' +
                            '<h3>' +
                            escHtml(d.name) +
                            '</h3>' +
                            '<span class="doctor-specialty">' +
                            escHtml(d.specialization) +
                            '</span>' +
                            qualification +
                            experience +
                            '<p><strong>Available slots:</strong> ' +
                            available +
                            '</p>' +
                            '<div class="doctor-card-actions"><button type="button" class="action-btn secondary doctor-profile-button" data-doctor-id="' +
                            escHtml(d.id) +
                            '">View profile & slots</button></div>' +
                            '</article>'
                        );
                    })
                    .join('');

                doctorList.querySelectorAll('.doctor-profile-button').forEach(function (button) {
                    button.addEventListener('click', function () {
                        loadProfile(button.dataset.doctorId);
                    });
                });
            })
            .catch(function (error) {
                doctorList.innerHTML =
                    '<div class="booking-placeholder">' + escHtml(error.message) + '</div>';
            });
    };

    const loadProfile = function (doctorId) {
        profilePanel.hidden = false;
        profile.innerHTML =
            '<div class="doctor-loading">Loading doctor profile and available slots...</div>';
        profilePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });

        const date = bookingDate ? bookingDate.value.trim() : '';
        fetch(
            'index.php?page=ajax&action=doctor_booking_profile&doctor_id=' +
                encodeURIComponent(doctorId) +
                '&date=' +
                encodeURIComponent(date),
            {
                headers: { Accept: 'application/json' }
            }
        )
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success)
                    throw new Error(data.message || 'Could not load doctor profile.');
                const d = data.doctor;
                const meta = [
                    d.qualification
                        ? '<div><strong>Qualification:</strong> ' +
                          escHtml(d.qualification) +
                          '</div>'
                        : '',
                    Number(d.experience || 0) > 0
                        ? '<div><strong>Experience:</strong> ' +
                          escHtml(d.experience) +
                          ' years</div>'
                        : '',
                    d.department
                        ? '<div><strong>Department:</strong> ' + escHtml(d.department) + '</div>'
                        : '',
                    d.room_number
                        ? '<div><strong>Consultation room:</strong> ' +
                          escHtml(d.room_number) +
                          '</div>'
                        : ''
                ].join('');

                let slots =
                    '<div class="slot-empty">No available slots have been created for this doctor yet.</div>';
                if (Array.isArray(d.slots) && d.slots.length) {
                    slots =
                        '<div class="booking-slot-list">' +
                        d.slots
                            .map(function (slot) {
                                return (
                                    '<div class="booking-slot">' +
                                    '<div class="booking-slot-date">' +
                                    escHtml(formatDate(slot.slot_date)) +
                                    '</div>' +
                                    '<div class="booking-slot-time">' +
                                    escHtml(formatTime(slot.start_time)) +
                                    ' - ' +
                                    escHtml(formatTime(slot.end_time)) +
                                    '</div>' +
                                    '<form method="POST" action="index.php?page=appointments">' +
                                    '<input type="hidden" name="csrf_token" value="' +
                                    escHtml(csrf) +
                                    '">' +
                                    '<input type="hidden" name="action" value="book">' +
                                    '<input type="hidden" name="slot_id" value="' +
                                    escHtml(slot.id) +
                                    '">' +
                                    '<button class="small-btn" type="submit">Book this slot</button>' +
                                    '</form></div>'
                                );
                            })
                            .join('') +
                        '</div>';
                }

                profile.innerHTML =
                    '<div class="doctor-profile-summary">' +
                    '<div class="doctor-profile-card">' +
                    '<h2>' +
                    escHtml(d.name) +
                    '</h2>' +
                    '<span class="doctor-profile-specialty">' +
                    escHtml(d.specialization) +
                    '</span>' +
                    '<div class="doctor-profile-meta">' +
                    meta +
                    '</div>' +
                    (d.bio ? '<p class="doctor-profile-bio">' + escHtml(d.bio) + '</p>' : '') +
                    '</div>' +
                    '<div class="doctor-slot-area"><h3>Available appointment slots</h3><p>Choose a date and time to book with this doctor.</p>' +
                    slots +
                    '</div>' +
                    '</div>';
            })
            .catch(function (error) {
                profile.innerHTML =
                    '<div class="booking-placeholder">' + escHtml(error.message) + '</div>';
            });
    };

    specialization.addEventListener('change', loadDoctors);
    if (bookingDate) bookingDate.addEventListener('change', loadDoctors);
});

// Keep calendar limits aligned with the user's real local date while the page stays open.
(function () {
    function localToday() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function syncDateRules(root) {
        const today = localToday();
        (root || document)
            .querySelectorAll('input[type="date"][data-date-rule]')
            .forEach(function (input) {
                const rule = input.dataset.dateRule;
                if (rule === 'today-or-future') {
                    input.min = today;
                    if (input.value && input.value < today)
                        input.setCustomValidity('Please select today or a future date.');
                    else input.setCustomValidity('');
                } else if (rule === 'past-or-today') {
                    input.max = today;
                    if (input.dataset.defaultToday === '1' && !input.value) input.value = today;
                    if (input.value && input.value > today)
                        input.setCustomValidity('Future dates are not allowed here.');
                    else input.setCustomValidity('');
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        syncDateRules(document);
        document.addEventListener('focusin', function (event) {
            if (event.target && event.target.matches('input[type="date"][data-date-rule]'))
                syncDateRules(document);
        });
        document.addEventListener('change', function (event) {
            if (event.target && event.target.matches('input[type="date"][data-date-rule]'))
                syncDateRules(document);
        });
        window.setInterval(function () {
            syncDateRules(document);
        }, 60000);
    });
})();

// Admin doctor management: live AJAX search across registered doctors.
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('admin-doctor-search');
    const button = document.getElementById('admin-doctor-search-button');
    const tbody = document.getElementById('admin-doctor-table-body');
    const csrf = document.getElementById('admin-doctor-csrf')?.value || '';
    if (!input || !tbody) return;

    let timer;
    const esc = function (value) {
        return String(value ?? '').replace(/[&<>'"]/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char];
        });
    };
    const doctorId = function (id) {
        return 'D' + String(Number(id) || 0).padStart(4, '0');
    };
    const render = function (rows) {
        if (!rows.length) {
            tbody.innerHTML =
                '<tr><td colspan="8" class="empty-cell">' +
                esc(tbody.dataset.emptyMessage || 'No matching doctors found.') +
                '</td></tr>';
            return;
        }
        tbody.innerHTML = rows
            .map(function (d) {
                const active = d.status === 'Active';
                return (
                    '<tr>' +
                    '<td>' +
                    esc(doctorId(d.id)) +
                    '</td>' +
                    '<td>' +
                    esc(d.name) +
                    '</td>' +
                    '<td>' +
                    esc(d.specialization || '—') +
                    '</td>' +
                    '<td>' +
                    esc(d.phone || '—') +
                    '</td>' +
                    '<td>' +
                    esc(d.email || '—') +
                    '</td>' +
                    '<td>' +
                    esc(d.department || '—') +
                    '</td>' +
                    '<td><span class="badge ' +
                    (active ? 'in-stock' : 'low') +
                    '">' +
                    esc(d.status) +
                    '</span></td>' +
                    '<td><form method="POST" class="inline-form">' +
                    '<input type="hidden" name="csrf_token" value="' +
                    esc(csrf) +
                    '">' +
                    '<input type="hidden" name="action" value="doctor_status">' +
                    '<input type="hidden" name="section" value="doctors">' +
                    '<input type="hidden" name="id" value="' +
                    esc(d.id) +
                    '">' +
                    '<input type="hidden" name="status" value="' +
                    (active ? 'Inactive' : 'Active') +
                    '">' +
                    '<button class="action-btn ' +
                    (active ? 'danger' : 'success') +
                    '" type="submit">' +
                    (active ? 'Deactivate' : 'Activate') +
                    '</button>' +
                    '</form></td>' +
                    '</tr>'
                );
            })
            .join('');
    };
    const search = function () {
        const q = input.value.trim();
        fetch('index.php?page=ajax&action=admin_doctor_search&q=' + encodeURIComponent(q), {
            headers: { Accept: 'application/json' }
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Search failed.');
                render(data.data || []);
            })
            .catch(function (error) {
                console.error(error);
            });
    };
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(search, 180);
    });
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            search();
        }
    });
    if (button) button.addEventListener('click', search);
});

// Admin management: live AJAX searches and in-place edit form loading.
document.addEventListener('DOMContentLoaded', function () {
    const escAdmin = function (value) {
        return String(value ?? '').replace(/[&<>'"]/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char];
        });
    };
    const debounceAdmin = function (fn, delay) {
        let timer;
        return function () {
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(null, args);
            }, delay);
        };
    };
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    const money = function (value) {
        return '৳' + Number(value || 0).toFixed(2);
    };
    const shortId = function (prefix, value) {
        return prefix + String(Number(value) || 0).padStart(4, '0');
    };
    const fullAppointmentId = function (row) {
        return row.doctor_id && row.doctor_serial
            ? 'D' +
                  String(Number(row.doctor_id)).padStart(4, '0') +
                  '-' +
                  String(Number(row.doctor_serial)).padStart(3, '0')
            : String(Number(row.id) || 0).padStart(3, '0');
    };
    const formatSqlDateTime = function (value) {
        if (!value) return '—';
        const parts = String(value).split(' ');
        if (parts.length < 2) return value;
        return formatDate(parts[0]) + ', ' + formatTime(parts[1]);
    };
    const emptyRow = function (tbody, colspan) {
        tbody.innerHTML =
            '<tr><td colspan="' +
            colspan +
            '" class="empty-cell">' +
            escAdmin(tbody.dataset.emptyMessage || 'No records found.') +
            '</td></tr>';
    };
    const bindLiveSearch = function (input, button, run) {
        if (!input) return;
        const delayed = debounceAdmin(run, 220);
        input.addEventListener('input', delayed);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                run();
            }
        });
        if (button) button.addEventListener('click', run);
    };

    // Billing search + edit loader.
    const billingInput = document.getElementById('admin-billing-search');
    const billingButton = document.getElementById('admin-billing-search-button');
    const billingBody = document.getElementById('admin-billing-table-body');
    const billingForm = document.getElementById('admin-billing-form');
    const billingCancel = document.getElementById('admin-billing-cancel-edit');
    const billingSubmit = document.getElementById('admin-billing-submit');
    const chargePatientSearch = document.getElementById('admin-charge-patient-search');
    const chargePatientId = document.getElementById('admin-charge-patient-id');
    const chargePatientResults = document.getElementById('admin-charge-patient-results');
    const chargePatientSelected = document.getElementById('admin-charge-patient-selected');
    const chargeDoctorSearch = document.getElementById('admin-charge-doctor-search');
    const chargeDoctorId = document.getElementById('admin-charge-doctor-id');
    const chargeDoctorResults = document.getElementById('admin-charge-doctor-results');
    const chargeDoctorSelected = document.getElementById('admin-charge-doctor-selected');

    const setupAdminEntityPicker = function (
        input,
        hidden,
        results,
        selected,
        endpoint,
        renderRow,
        selectedLabel,
        emptyText
    ) {
        if (!input || !hidden || !results) return;
        const load = function () {
            fetch(endpoint + encodeURIComponent(input.value.trim()), {
                headers: { Accept: 'application/json' }
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Search failed.');
                    const rows = data.data || [];
                    results.innerHTML = rows.length
                        ? rows.map(renderRow).join('')
                        : '<div class="admin-picker-empty">No matching records found.</div>';
                    results.hidden = false;
                    results.querySelectorAll('[data-picker-id]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            hidden.value = btn.dataset.pickerId || '';
                            input.value = btn.dataset.pickerName || '';
                            if (selected) selected.textContent = selectedLabel(btn);
                            results.hidden = true;
                        });
                    });
                })
                .catch(function () {
                    results.innerHTML =
                        '<div class="admin-picker-empty">Search could not be completed.</div>';
                    results.hidden = false;
                });
        };
        const delayed = debounceAdmin(load, 180);
        input.addEventListener('input', function () {
            hidden.value = '';
            if (selected) selected.textContent = emptyText;
            delayed();
        });
        input.addEventListener('focus', load);
        document.addEventListener('click', function (e) {
            if (!results.contains(e.target) && e.target !== input) results.hidden = true;
        });
    };
    setupAdminEntityPicker(
        chargePatientSearch,
        chargePatientId,
        chargePatientResults,
        chargePatientSelected,
        'index.php?page=ajax&action=admin_records_search&category=patients&q=',
        function (p) {
            return (
                '<button type="button" class="admin-picker-option" data-picker-id="' +
                escAdmin(p.id) +
                '" data-picker-name="' +
                escAdmin(p.name) +
                '"><strong>' +
                escAdmin(shortId('P', p.id)) +
                ' · ' +
                escAdmin(p.name) +
                '</strong><span>' +
                escAdmin(p.phone || '—') +
                ' · ' +
                escAdmin(p.status || '—') +
                '</span></button>'
            );
        },
        function (btn) {
            return (
                'Selected: ' + shortId('P', btn.dataset.pickerId) + ' · ' + btn.dataset.pickerName
            );
        },
        'Select a patient from the search results.'
    );
    setupAdminEntityPicker(
        chargeDoctorSearch,
        chargeDoctorId,
        chargeDoctorResults,
        chargeDoctorSelected,
        'index.php?page=ajax&action=admin_doctor_search&q=',
        function (d) {
            return (
                '<button type="button" class="admin-picker-option" data-picker-id="' +
                escAdmin(d.id) +
                '" data-picker-name="' +
                escAdmin(d.name) +
                '"><strong>' +
                escAdmin(shortId('D', d.id)) +
                ' · ' +
                escAdmin(d.name) +
                '</strong><span>' +
                escAdmin(d.specialization || '—') +
                ' · ' +
                escAdmin(d.status || '—') +
                '</span></button>'
            );
        },
        function (btn) {
            return (
                'Selected: ' + shortId('D', btn.dataset.pickerId) + ' · ' + btn.dataset.pickerName
            );
        },
        'Select the doctor visited from the search results.'
    );
    if (billingInput && billingBody) {
        const billingType = billingBody.dataset.billingType || 'patient';
        const renderBilling = function (rows) {
            if (!rows.length) return emptyRow(billingBody, billingType === 'patient' ? 9 : 8);
            billingBody.innerHTML = rows
                .map(function (b) {
                    let who = '';
                    if (billingType === 'patient')
                        who =
                            '<td>' +
                            escAdmin(
                                (b.patient_id ? shortId('P', b.patient_id) + ' · ' : '') +
                                    (b.patient_name || '—')
                            ) +
                            '</td><td>' +
                            escAdmin(
                                (b.doctor_id ? shortId('D', b.doctor_id) + ' · ' : '') +
                                    (b.doctor_name || '—')
                            ) +
                            '</td>';
                    else if (billingType === 'doctor')
                        who =
                            '<td>' +
                            escAdmin(
                                (b.doctor_id ? shortId('D', b.doctor_id) + ' · ' : '') +
                                    (b.doctor_name || '—')
                            ) +
                            '</td>';
                    else
                        who =
                            '<td>' +
                            escAdmin(
                                (b.staff_id ? shortId('S', b.staff_id) + ' · ' : '') +
                                    (b.staff_name || '—')
                            ) +
                            '</td>';
                    return (
                        '<tr>' +
                        who +
                        '<td>' +
                        escAdmin(b.description) +
                        '</td>' +
                        '<td>' +
                        escAdmin(money(b.amount)) +
                        '</td>' +
                        '<td>' +
                        escAdmin(money(b.paid)) +
                        '</td>' +
                        '<td>' +
                        escAdmin(b.payment_status) +
                        '</td>' +
                        '<td>' +
                        escAdmin(b.transaction_date) +
                        '</td>' +
                        '<td>' +
                        escAdmin(formatSqlDateTime(b.updated_at || b.created_at)) +
                        '</td>' +
                        '<td class="actions"><button type="button" class="action-btn secondary admin-billing-edit"' +
                        ' data-id="' +
                        escAdmin(b.id) +
                        '" data-patient-id="' +
                        escAdmin(b.patient_id || '') +
                        '" data-patient-name="' +
                        escAdmin(b.patient_name || '') +
                        '" data-doctor-id="' +
                        escAdmin(b.doctor_id || '') +
                        '" data-doctor-name="' +
                        escAdmin(b.doctor_name || '') +
                        '" data-staff-id="' +
                        escAdmin(b.staff_id || '') +
                        '" data-description="' +
                        escAdmin(b.description) +
                        '" data-amount="' +
                        escAdmin(b.amount) +
                        '" data-paid="' +
                        escAdmin(b.paid) +
                        '" data-status="' +
                        escAdmin(b.payment_status) +
                        '" data-date="' +
                        escAdmin(b.transaction_date) +
                        '">Edit</button> ' +
                        '<form method="POST" class="inline-form"><input type="hidden" name="csrf_token" value="' +
                        escAdmin(csrf) +
                        '"><input type="hidden" name="action" value="billing_delete"><input type="hidden" name="section" value="billing"><input type="hidden" name="billing_type" value="' +
                        escAdmin(billingType) +
                        '"><input type="hidden" name="id" value="' +
                        escAdmin(b.id) +
                        '"><button class="action-btn danger">Delete</button></form></td></tr>'
                    );
                })
                .join('');
        };
        const searchBilling = function () {
            fetch(
                'index.php?page=ajax&action=admin_billing_search&billing_type=' +
                    encodeURIComponent(billingType) +
                    '&q=' +
                    encodeURIComponent(billingInput.value.trim()),
                { headers: { Accept: 'application/json' } }
            )
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Search failed.');
                    renderBilling(data.data || []);
                })
                .catch(console.error);
        };
        bindLiveSearch(billingInput, billingButton, searchBilling);

        billingBody.addEventListener('click', function (event) {
            const btn = event.target.closest('.admin-billing-edit');
            if (!btn || !billingForm) return;
            billingForm.elements.namedItem('id').value = btn.dataset.id || '';
            if (billingForm.elements.namedItem('patient_id'))
                billingForm.elements.namedItem('patient_id').value = btn.dataset.patientId || '';
            if (billingForm.elements.namedItem('doctor_id'))
                billingForm.elements.namedItem('doctor_id').value = btn.dataset.doctorId || '';
            if (billingForm.elements.namedItem('staff_id'))
                billingForm.elements.namedItem('staff_id').value = btn.dataset.staffId || '';
            if (chargePatientSearch) chargePatientSearch.value = btn.dataset.patientName || '';
            if (chargeDoctorSearch) chargeDoctorSearch.value = btn.dataset.doctorName || '';
            if (chargePatientSelected && btn.dataset.patientId)
                chargePatientSelected.textContent =
                    'Selected: ' +
                    shortId('P', btn.dataset.patientId) +
                    ' · ' +
                    (btn.dataset.patientName || '');
            if (chargeDoctorSelected && btn.dataset.doctorId)
                chargeDoctorSelected.textContent =
                    'Selected: ' +
                    shortId('D', btn.dataset.doctorId) +
                    ' · ' +
                    (btn.dataset.doctorName || '');
            billingForm.elements.namedItem('description').value = btn.dataset.description || '';
            billingForm.elements.namedItem('amount').value = btn.dataset.amount || '';
            billingForm.elements.namedItem('paid').value = btn.dataset.paid || '0';
            billingForm.elements.namedItem('payment_status').value = btn.dataset.status || 'Unpaid';
            billingForm.elements.namedItem('transaction_date').value = btn.dataset.date || '';
            if (billingSubmit)
                billingSubmit.textContent =
                    'Update ' +
                    (billingType === 'patient'
                        ? 'Patient Charge'
                        : billingType === 'doctor'
                          ? 'Doctor Payment'
                          : 'Staff Payment');
            if (billingCancel) billingCancel.hidden = false;
            document
                .getElementById('admin-billing-form-panel')
                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        billingCancel?.addEventListener('click', function () {
            billingForm.reset();
            billingForm.elements.namedItem('id').value = '';
            if (chargePatientId) chargePatientId.value = '';
            if (chargeDoctorId) chargeDoctorId.value = '';
            if (chargePatientSelected)
                chargePatientSelected.textContent = 'Select a patient from the search results.';
            if (chargeDoctorSelected)
                chargeDoctorSelected.textContent =
                    'Select the doctor visited from the search results.';
            billingCancel.hidden = true;
            if (billingSubmit)
                billingSubmit.textContent =
                    'Add ' +
                    (billingType === 'patient'
                        ? 'Patient Charge'
                        : billingType === 'doctor'
                          ? 'Doctor Payment'
                          : 'Staff Payment');
        });
    }

    // Staff search + edit loader.
    const staffInput = document.getElementById('admin-staff-search');
    const staffButton = document.getElementById('admin-staff-search-button');
    const staffBody = document.getElementById('admin-staff-table-body');
    const staffForm = document.getElementById('admin-staff-form');
    const staffCancel = document.getElementById('admin-staff-cancel-edit');
    const staffSubmit = document.getElementById('admin-staff-submit');
    if (staffInput && staffBody) {
        const renderStaff = function (rows) {
            if (!rows.length) return emptyRow(staffBody, 8);
            staffBody.innerHTML = rows
                .map(function (s) {
                    const deactivate =
                        s.status === 'Active'
                            ? ' <form method="POST" class="inline-form"><input type="hidden" name="csrf_token" value="' +
                              escAdmin(csrf) +
                              '"><input type="hidden" name="action" value="staff_delete"><input type="hidden" name="section" value="staff"><input type="hidden" name="id" value="' +
                              escAdmin(s.id) +
                              '"><button class="action-btn danger compact-action">Deactivate</button></form>'
                            : '';
                    return (
                        '<tr><td>' +
                        escAdmin(shortId('S', s.id)) +
                        '</td><td>' +
                        escAdmin(s.name) +
                        '</td><td>' +
                        escAdmin(s.staff_role) +
                        '</td><td>' +
                        escAdmin(s.phone || '—') +
                        '</td><td>' +
                        escAdmin(s.email || '—') +
                        '</td><td>' +
                        escAdmin(s.department || '—') +
                        '</td><td>' +
                        escAdmin(s.status) +
                        '</td><td class="actions compact-actions"><button type="button" class="action-btn secondary compact-action admin-staff-edit" data-id="' +
                        escAdmin(s.id) +
                        '" data-name="' +
                        escAdmin(s.name) +
                        '" data-role="' +
                        escAdmin(s.staff_role) +
                        '" data-phone="' +
                        escAdmin(s.phone || '') +
                        '" data-email="' +
                        escAdmin(s.email || '') +
                        '" data-department="' +
                        escAdmin(s.department || '') +
                        '" data-status="' +
                        escAdmin(s.status) +
                        '">Edit</button>' +
                        deactivate +
                        '</td></tr>'
                    );
                })
                .join('');
        };
        const searchStaff = function () {
            fetch(
                'index.php?page=ajax&action=admin_staff_search&q=' +
                    encodeURIComponent(staffInput.value.trim()),
                { headers: { Accept: 'application/json' } }
            )
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Search failed.');
                    renderStaff(data.data || []);
                })
                .catch(console.error);
        };
        bindLiveSearch(staffInput, staffButton, searchStaff);
        staffBody.addEventListener('click', function (event) {
            const btn = event.target.closest('.admin-staff-edit');
            if (!btn || !staffForm) return;
            staffForm.elements.namedItem('id').value = btn.dataset.id || '';
            staffForm.elements.namedItem('name').value = btn.dataset.name || '';
            staffForm.elements.namedItem('staff_role').value = btn.dataset.role || '';
            staffForm.elements.namedItem('phone').value = btn.dataset.phone || '';
            staffForm.elements.namedItem('email').value = btn.dataset.email || '';
            staffForm.elements.namedItem('department').value = btn.dataset.department || '';
            staffForm.elements.namedItem('status').value = btn.dataset.status || 'Active';
            document.getElementById('admin-staff-form-title').textContent = 'Edit Staff';
            if (staffSubmit) staffSubmit.textContent = 'Update Staff';
            if (staffCancel) staffCancel.hidden = false;
            document
                .getElementById('admin-staff-form-panel')
                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        staffCancel?.addEventListener('click', function () {
            staffForm.reset();
            staffForm.elements.namedItem('id').value = '';
            document.getElementById('admin-staff-form-title').textContent = 'Add Staff';
            if (staffSubmit) staffSubmit.textContent = 'Add Staff';
            staffCancel.hidden = true;
        });
    }

    // Equipment search + edit loader + equipment catalog.
    const equipmentInput = document.getElementById('admin-equipment-search'),
        equipmentButton = document.getElementById('admin-equipment-search-button'),
        equipmentBody = document.getElementById('admin-equipment-table-body'),
        equipmentForm = document.getElementById('admin-equipment-form'),
        equipmentCancel = document.getElementById('admin-equipment-cancel-edit'),
        equipmentSubmit = document.getElementById('admin-equipment-submit');
    const newEquipmentType = document.getElementById('admin-new-equipment-type'),
        addEquipmentType = document.getElementById('admin-add-equipment-type'),
        equipmentTypeMessage = document.getElementById('admin-equipment-type-message'),
        equipmentNameSelect = document.getElementById('admin-equipment-name'),
        equipmentQuantityGroup = document.getElementById('admin-equipment-quantity-group');
    if (equipmentInput && equipmentBody) {
        const renderEquipment = function (rows) {
            if (!rows.length) return emptyRow(equipmentBody, 6);
            equipmentBody.innerHTML = rows
                .map(function (e) {
                    return (
                        '<tr><td>' +
                        escAdmin(e.equipment_code || shortId('E', e.id)) +
                        '</td><td>' +
                        escAdmin(e.name) +
                        '</td><td>' +
                        escAdmin(e.department || '—') +
                        '</td><td>' +
                        escAdmin(e.purchase_date || '—') +
                        '</td><td>' +
                        escAdmin(e.condition_status) +
                        '</td><td class="actions"><button type="button" class="action-btn secondary admin-equipment-edit" data-id="' +
                        escAdmin(e.id) +
                        '" data-name="' +
                        escAdmin(e.name) +
                        '" data-department="' +
                        escAdmin(e.department || '') +
                        '" data-date="' +
                        escAdmin(e.purchase_date || '') +
                        '" data-condition="' +
                        escAdmin(e.condition_status) +
                        '">Edit</button> <form method="POST" class="inline-form"><input type="hidden" name="csrf_token" value="' +
                        escAdmin(csrf) +
                        '"><input type="hidden" name="action" value="equipment_delete"><input type="hidden" name="section" value="equipment"><input type="hidden" name="id" value="' +
                        escAdmin(e.id) +
                        '"><button class="action-btn danger">Delete</button></form></td></tr>'
                    );
                })
                .join('');
        };
        const searchEquipment = function () {
            fetch(
                'index.php?page=ajax&action=admin_equipment_search&q=' +
                    encodeURIComponent(equipmentInput.value.trim()),
                { headers: { Accept: 'application/json' } }
            )
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Search failed.');
                    renderEquipment(data.data || []);
                })
                .catch(console.error);
        };
        bindLiveSearch(equipmentInput, equipmentButton, searchEquipment);
        equipmentBody.addEventListener('click', function (event) {
            const btn = event.target.closest('.admin-equipment-edit');
            if (!btn || !equipmentForm) return;
            equipmentForm.elements.namedItem('id').value = btn.dataset.id || '';
            equipmentForm.elements.namedItem('name').value = btn.dataset.name || '';
            equipmentForm.elements.namedItem('department').value = btn.dataset.department || '';
            equipmentForm.elements.namedItem('purchase_date').value = btn.dataset.date || '';
            equipmentForm.elements.namedItem('condition_status').value =
                btn.dataset.condition || 'Good';
            const q = equipmentForm.elements.namedItem('quantity');
            if (q) {
                q.disabled = true;
                q.required = false;
            }
            if (equipmentQuantityGroup) equipmentQuantityGroup.hidden = true;
            document.getElementById('admin-equipment-form-title').textContent =
                'Edit Medical Equipment';
            if (equipmentSubmit) equipmentSubmit.textContent = 'Update Equipment';
            if (equipmentCancel) equipmentCancel.hidden = false;
            document
                .getElementById('admin-equipment-form-panel')
                ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        equipmentCancel?.addEventListener('click', function () {
            equipmentForm.reset();
            equipmentForm.elements.namedItem('id').value = '';
            const q = equipmentForm.elements.namedItem('quantity');
            if (q) {
                q.disabled = false;
                q.required = true;
                q.value = '1';
            }
            if (equipmentQuantityGroup) equipmentQuantityGroup.hidden = false;
            document.getElementById('admin-equipment-form-title').textContent = 'Medical Equipment';
            if (equipmentSubmit) equipmentSubmit.textContent = 'Add Equipment';
            equipmentCancel.hidden = true;
        });
    }
    addEquipmentType?.addEventListener('click', function () {
        const name = (newEquipmentType?.value || '').trim();
        if (!name) {
            if (equipmentTypeMessage) equipmentTypeMessage.textContent = 'Enter an equipment name.';
            return;
        }
        if (equipmentTypeMessage) equipmentTypeMessage.textContent = '';
        const body = new URLSearchParams({ csrf_token: csrf, name: name });
        fetch('index.php?page=ajax&action=admin_add_equipment_type', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Accept: 'application/json'
            },
            body: body.toString()
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Could not add equipment type.');
                const row = data.data;
                if (equipmentNameSelect && row) {
                    let option = Array.from(equipmentNameSelect.options).find(function (o) {
                        return o.value === row.name;
                    });
                    if (!option) {
                        option = document.createElement('option');
                        option.value = row.name;
                        option.textContent = row.name;
                        equipmentNameSelect.appendChild(option);
                    }
                    equipmentNameSelect.value = row.name;
                }
                if (newEquipmentType) newEquipmentType.value = '';
                if (equipmentTypeMessage)
                    equipmentTypeMessage.textContent = 'Equipment type added.';
            })
            .catch(function (err) {
                if (equipmentTypeMessage) equipmentTypeMessage.textContent = err.message;
            });
    });

    // Inventory search + edit loader + master item catalog + edit history.
    const inventoryInput = document.getElementById('admin-inventory-search'),
        inventoryButton = document.getElementById('admin-inventory-search-button'),
        inventoryBody = document.getElementById('admin-inventory-table-body'),
        inventoryForm = document.getElementById('admin-inventory-form'),
        inventoryCancel = document.getElementById('admin-inventory-cancel-edit'),
        inventorySubmit = document.getElementById('admin-inventory-submit');
    const inventoryItemSelect = document.getElementById('admin-inventory-item'),
        inventoryCategory = document.getElementById('admin-inventory-category'),
        inventoryCategoryDisplay = document.getElementById('admin-inventory-category-display');
    const newStockType = document.getElementById('admin-new-stock-type'),
        newStockCategory = document.getElementById('admin-new-stock-category'),
        addStockType = document.getElementById('admin-add-stock-type'),
        stockTypeMessage = document.getElementById('admin-stock-type-message');
    const historyModal = document.getElementById('admin-stock-history-modal'),
        historyClose = document.getElementById('admin-stock-history-close'),
        historyContent = document.getElementById('admin-stock-history-content'),
        historyTitle = document.getElementById('admin-stock-history-title'),
        historySubtitle = document.getElementById('admin-stock-history-subtitle');
    const syncInventoryCategory = function () {
        if (!inventoryItemSelect) return;
        const option = inventoryItemSelect.options[inventoryItemSelect.selectedIndex];
        const category = option?.dataset.category || '';
        if (inventoryCategory) inventoryCategory.value = category;
        if (inventoryCategoryDisplay) inventoryCategoryDisplay.value = category;
    };
    inventoryItemSelect?.addEventListener('change', syncInventoryCategory);
    syncInventoryCategory();
    if (inventoryInput && inventoryBody) {
        const renderInventory = function (rows) {
            if (!rows.length) return emptyRow(inventoryBody, 9);
            inventoryBody.innerHTML = rows
                .map(function (i) {
                    const low = i.stock_status === 'Low Stock';
                    const updated = i.updated_at_display || i.updated_at || '—';
                    return (
                        '<tr><td>' +
                        escAdmin(shortId('I', i.id)) +
                        '</td><td>' +
                        escAdmin(i.item_name) +
                        '</td><td>' +
                        escAdmin(i.category) +
                        '</td><td>' +
                        escAdmin(i.quantity) +
                        '</td><td>' +
                        escAdmin(i.minimum_level) +
                        '</td><td>' +
                        escAdmin(i.supplier || '—') +
                        '</td><td><button type="button" class="history-link admin-stock-history" data-id="' +
                        escAdmin(i.id) +
                        '" data-item="' +
                        escAdmin(i.item_name) +
                        '">' +
                        escAdmin(updated) +
                        '</button></td><td><span class="badge ' +
                        (low ? 'low' : 'in-stock') +
                        '">' +
                        escAdmin(i.stock_status) +
                        '</span></td><td class="actions"><button type="button" class="action-btn secondary admin-inventory-edit" data-id="' +
                        escAdmin(i.id) +
                        '" data-item="' +
                        escAdmin(i.item_name) +
                        '" data-category="' +
                        escAdmin(i.category) +
                        '" data-quantity="' +
                        escAdmin(i.quantity) +
                        '" data-minimum="' +
                        escAdmin(i.minimum_level) +
                        '" data-supplier="' +
                        escAdmin(i.supplier || '') +
                        '">Edit</button> <form method="POST" class="inline-form"><input type="hidden" name="csrf_token" value="' +
                        escAdmin(csrf) +
                        '"><input type="hidden" name="action" value="inventory_delete"><input type="hidden" name="section" value="inventory"><input type="hidden" name="id" value="' +
                        escAdmin(i.id) +
                        '"><button class="action-btn danger">Delete</button></form></td></tr>'
                    );
                })
                .join('');
        };
        const searchInventory = function () {
            fetch(
                'index.php?page=ajax&action=admin_inventory_search&q=' +
                    encodeURIComponent(inventoryInput.value.trim()),
                { headers: { Accept: 'application/json' } }
            )
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Search failed.');
                    renderInventory(data.data || []);
                })
                .catch(console.error);
        };
        bindLiveSearch(inventoryInput, inventoryButton, searchInventory);
        inventoryBody.addEventListener('click', function (event) {
            const edit = event.target.closest('.admin-inventory-edit');
            if (edit && inventoryForm) {
                inventoryForm.elements.namedItem('id').value = edit.dataset.id || '';
                inventoryForm.elements.namedItem('item_name').value = edit.dataset.item || '';
                syncInventoryCategory();
                inventoryForm.elements.namedItem('quantity').value = edit.dataset.quantity || '0';
                inventoryForm.elements.namedItem('minimum_level').value =
                    edit.dataset.minimum || '0';
                inventoryForm.elements.namedItem('supplier').value = edit.dataset.supplier || '';
                document.getElementById('admin-inventory-form-title').textContent =
                    'Edit Stock Item';
                if (inventorySubmit) inventorySubmit.textContent = 'Update Stock Item';
                if (inventoryCancel) inventoryCancel.hidden = false;
                document
                    .getElementById('admin-inventory-form-panel')
                    ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return;
            }
            const history = event.target.closest('.admin-stock-history');
            if (history) openStockHistory(history.dataset.id, history.dataset.item || 'Stock Item');
        });
        inventoryCancel?.addEventListener('click', function () {
            inventoryForm.reset();
            inventoryForm.elements.namedItem('id').value = '';
            syncInventoryCategory();
            document.getElementById('admin-inventory-form-title').textContent =
                'Smart Hospital Stock Monitor';
            if (inventorySubmit) inventorySubmit.textContent = 'Add Stock Item';
            inventoryCancel.hidden = true;
        });
    }
    addStockType?.addEventListener('click', function () {
        const name = (newStockType?.value || '').trim(),
            category = newStockCategory?.value || '';
        if (!name || !category) {
            if (stockTypeMessage)
                stockTypeMessage.textContent = 'Enter an item name and select a category.';
            return;
        }
        if (stockTypeMessage) stockTypeMessage.textContent = '';
        const body = new URLSearchParams({ csrf_token: csrf, name: name, category: category });
        fetch('index.php?page=ajax&action=admin_add_stock_item_type', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Accept: 'application/json'
            },
            body: body.toString()
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Could not add stock item.');
                const row = data.data;
                if (inventoryItemSelect && row) {
                    let option = Array.from(inventoryItemSelect.options).find(function (o) {
                        return o.value === row.name;
                    });
                    if (!option) {
                        option = document.createElement('option');
                        option.value = row.name;
                        option.textContent = row.name;
                        inventoryItemSelect.appendChild(option);
                    }
                    option.dataset.category = row.category;
                    inventoryItemSelect.value = row.name;
                    syncInventoryCategory();
                }
                if (newStockType) newStockType.value = '';
                if (newStockCategory) newStockCategory.value = '';
                if (stockTypeMessage) stockTypeMessage.textContent = 'Stock item added.';
            })
            .catch(function (err) {
                if (stockTypeMessage) stockTypeMessage.textContent = err.message;
            });
    });
    const historyValue = function (value) {
        return value === null || value === undefined || value === '' ? '—' : String(value);
    };
    const historyChanges = function (row) {
        if (row.action_type === 'Created')
            return [
                'Created with quantity ' +
                    historyValue(row.new_quantity) +
                    ', minimum level ' +
                    historyValue(row.new_minimum_level) +
                    ', category ' +
                    historyValue(row.new_category) +
                    (row.new_supplier ? ' and supplier ' + row.new_supplier : '') +
                    '.'
            ];
        const changes = [];
        [
            ['Item', row.old_item_name, row.new_item_name],
            ['Category', row.old_category, row.new_category],
            ['Quantity', row.old_quantity, row.new_quantity],
            ['Minimum level', row.old_minimum_level, row.new_minimum_level],
            ['Supplier', row.old_supplier, row.new_supplier]
        ].forEach(function (c) {
            if (historyValue(c[1]) !== historyValue(c[2]))
                changes.push(c[0] + ': ' + historyValue(c[1]) + ' → ' + historyValue(c[2]));
        });
        return changes.length ? changes : ['Record saved without a value change.'];
    };
    const openStockHistory = function (id, itemName) {
        if (!historyModal || !historyContent) return;
        historyModal.hidden = false;
        document.body.classList.add('modal-open');
        if (historyTitle) historyTitle.textContent = 'Stock Edit History';
        if (historySubtitle) historySubtitle.textContent = shortId('I', id) + ' · ' + itemName;
        historyContent.innerHTML = '<p class="empty-cell">Loading history...</p>';
        fetch('index.php?page=ajax&action=admin_inventory_history&id=' + encodeURIComponent(id), {
            headers: { Accept: 'application/json' }
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Could not load history.');
                const rows = data.data || [];
                historyContent.innerHTML = rows.length
                    ? rows
                          .map(function (row) {
                              return (
                                  '<article class="history-entry"><div class="history-entry-top"><strong>' +
                                  escAdmin(row.action_type) +
                                  '</strong><time>' +
                                  escAdmin(row.changed_at_display || row.changed_at) +
                                  '</time></div>' +
                                  historyChanges(row)
                                      .map(function (line) {
                                          return '<p>' + escAdmin(line) + '</p>';
                                      })
                                      .join('') +
                                  '</article>'
                              );
                          })
                          .join('')
                    : '<p class="empty-cell">No edit history is recorded for this item yet.</p>';
            })
            .catch(function (err) {
                historyContent.innerHTML =
                    '<p class="empty-cell">' + escAdmin(err.message) + '</p>';
            });
    };
    const closeStockHistory = function () {
        if (historyModal) historyModal.hidden = true;
        document.body.classList.remove('modal-open');
    };
    historyClose?.addEventListener('click', closeStockHistory);
    historyModal?.addEventListener('click', function (event) {
        if (event.target === historyModal) closeStockHistory();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && historyModal && !historyModal.hidden) closeStockHistory();
    });

    // Admin All Records: private symptoms/prescriptions are intentionally not exposed here.
    const setupRecordSearch = function (category, inputId, buttonId, bodyId, render, colspan) {
        const input = document.getElementById(inputId),
            button = document.getElementById(buttonId),
            body = document.getElementById(bodyId);
        if (!input || !body) return;
        const run = function () {
            fetch(
                'index.php?page=ajax&action=admin_records_search&category=' +
                    encodeURIComponent(category) +
                    '&q=' +
                    encodeURIComponent(input.value.trim()),
                { headers: { Accept: 'application/json' } }
            )
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Search failed.');
                    const rows = data.data || [];
                    if (!rows.length) return emptyRow(body, colspan);
                    body.innerHTML = render(rows);
                })
                .catch(console.error);
        };
        bindLiveSearch(input, button, run);
    };
    setupRecordSearch(
        'patients',
        'admin-record-patients-search',
        'admin-record-patients-search-button',
        'admin-record-patients-body',
        function (rows) {
            return rows
                .map(function (p) {
                    return (
                        '<tr><td>' +
                        escAdmin(shortId('P', p.id)) +
                        '</td><td>' +
                        escAdmin(p.name) +
                        '</td><td>' +
                        escAdmin(p.phone || '—') +
                        '</td><td>' +
                        escAdmin(p.email || '—') +
                        '</td><td>' +
                        escAdmin(p.age || '—') +
                        '</td><td>' +
                        escAdmin(p.status) +
                        '</td></tr>'
                    );
                })
                .join('');
        },
        6
    );
    setupRecordSearch(
        'appointments',
        'admin-record-appointments-search',
        'admin-record-appointments-search-button',
        'admin-record-appointments-body',
        function (rows) {
            return rows
                .map(function (a) {
                    return (
                        '<tr><td>' +
                        escAdmin(fullAppointmentId(a)) +
                        '</td><td>' +
                        escAdmin(a.patient_name) +
                        '</td><td>' +
                        escAdmin(a.doctor_name) +
                        '</td><td>' +
                        escAdmin(a.appointment_date) +
                        '</td><td>' +
                        escAdmin(formatTime(a.appointment_time)) +
                        '</td><td>' +
                        escAdmin(a.reason || '—') +
                        '</td><td>' +
                        escAdmin(a.status) +
                        '</td></tr>'
                    );
                })
                .join('');
        },
        7
    );
    setupRecordSearch(
        'followups',
        'admin-record-followups-search',
        'admin-record-followups-search-button',
        'admin-record-followups-body',
        function (rows) {
            return rows
                .map(function (f) {
                    return (
                        '<tr><td>' +
                        escAdmin(shortId('F', f.id)) +
                        '</td><td>' +
                        escAdmin(f.patient_name) +
                        '</td><td>' +
                        escAdmin(f.doctor_name) +
                        '</td><td>' +
                        escAdmin(f.followup_date) +
                        '</td><td>' +
                        escAdmin(f.purpose) +
                        '</td><td>' +
                        escAdmin(f.status) +
                        '</td></tr>'
                    );
                })
                .join('');
        },
        6
    );
});

/* =========================================================
   RECEPTIONIST DASHBOARD OVERVIEW
   Consolidated from receptionist-dashboard.js
   ========================================================= */
(() => {
    'use strict';
    const search = document.getElementById('reception-today-search');
    const status = document.getElementById('reception-today-status');
    if (!search || !status) return;
    const rows = Array.from(document.querySelectorAll('#reception-today-rows tr[data-patient]'));
    const empty = document.getElementById('reception-today-empty');
    const count = document.getElementById('reception-today-count');

    function update() {
        const query = search.value.trim().toLocaleLowerCase();
        const matches = rows.filter(
            (row) =>
                row.dataset.patient.toLocaleLowerCase().includes(query) &&
                (!status.value || row.dataset.status === status.value)
        );
        rows.forEach((row) => {
            row.hidden = true;
        });
        matches.forEach((row) => {
            row.hidden = false;
        });
        empty.hidden = matches.length > 0;
        empty.firstElementChild.textContent = rows.length
            ? 'No appointments match your search.'
            : 'No appointments scheduled for today.';
        count.textContent = 'Showing ' + matches.length + ' of ' + matches.length + ' appointments';
    }
    search.addEventListener('input', () => {
        update();
    });
    status.addEventListener('change', () => {
        update();
    });

    update();
})();
