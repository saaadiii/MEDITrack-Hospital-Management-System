<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>MEDITrack - Appointments</title>
    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20260923-final"
    >
</head>
<body class="dashboard-page">

    <aside class="sidebar">
        <div class="sidebar-brand">MEDI<span>Track</span></div>
        <nav>
            <a
                class="nav-link"
                href="index.php?page=patient"
            >Dashboard</a>
            <a
                class="nav-link"
                href="index.php?page=profile"
            >Profile</a>
            <a
                class="nav-link"
                href="index.php?page=symptoms"
            >Symptom tracking</a>
            <a
                class="nav-link"
                href="index.php?page=followups"
            >Follow-up reminders</a>
            <a
                class="nav-link active"
                href="index.php?page=appointments"
            >Appointments</a>
            <a
                class="nav-link"
                href="index.php?page=prescriptions"
            >Prescriptions</a>
        </nav>
        <div class="sidebar-bottom">
            <a
                href="index.php?page=logout&amp;role=patient"
                class="signout-link"
            >Sign out</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="dashboard-header">
            <div>
                <h1>Appointments</h1>
                <p>Choose a specialization, select a doctor, then book one of their available slots.
                </p>
            </div>
        </header>

        <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars(
            $message
        ) ?></div>
        <?php endif; ?>

        <input
            type="hidden"
            id="appointment-csrf-token"
            value="<?= esc(csrf_token()) ?>"
        >

        <section class="panel appointment-finder">
            <div class="panel-header">
                <div>
                    <h2>Find a doctor</h2>
                    <p>Start by selecting the medical specialization you need.</p>
                </div>
            </div>

            <div class="booking-filter-row">
                <div class="specialization-filter">
                    <label for="specialization-select">Specialization</label>
                    <select id="specialization-select">
                        <option value="">Select a specialization</option>
                        <?php foreach ($specializations as $item): ?>
                        <option value="<?= esc($item['specialization']) ?>"><?= esc(
                            $item['specialization']
                        ) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="booking-date-filter">
                    <label for="booking-date-filter">Appointment date (optional)</label>
                    <input
                        type="date"
                        id="booking-date-filter"
                        data-date-rule="today-or-future"
                    >
                </div>
            </div>

            <div
                id="specialized-doctors"
                class="doctor-choice-grid"
                aria-live="polite"
            >
                <div class="booking-placeholder">Select a specialization to see doctors in that
                    field.</div>
            </div>
        </section>

        <section
            class="panel doctor-booking-panel"
            id="doctor-booking-panel"
            hidden
        >
            <div
                id="doctor-booking-profile"
                aria-live="polite"
            ></div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>My appointments</h2>
                    <p>Your appointment history</p>
                </div>
                <div class="toolbar patient-standard-search">
                    <input
                        id="appointment-search"
                        type="search"
                        placeholder="Search by doctor, specialization, reason, status, date, time or appointment ID"
                        autocomplete="off"
                    >
                    <button
                        type="button"
                        class="action-btn secondary"
                        id="appointment-search-button"
                    >Search</button>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody
                        id="appointment-table-body"
                        data-empty-message="No matching appointments found."
                    >
                        <?php if (count($appointments)): ?>
                        <?php foreach ($appointments as $row): ?>
                        <tr>
                            <td><?= esc(
                                                            !empty($row['doctor_id']) && !empty($row['doctor_serial'])
                                                                ? doctor_appointment_display_id(
                                                                    $row['doctor_id'],
                                                                    $row['doctor_serial']
                                                                )
                                                                : appointment_display_id($row['id'])
                                                        ) ?></td>
                            <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                            <td><?= htmlspecialchars($row['specialization']) ?></td>
                            <td><?= date('d M Y', strtotime($row['appointment_date'])) ?></td>
                            <td><?= date('H:i', strtotime($row['appointment_time'])) ?></td>
                            <td><span class="status <?= esc(
                                                            strtolower(str_replace(' ', '-', $row['status']))
                                                        ) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                            <td>
                                <?php if ($row['status'] === 'Booked'): ?>
                                <form
                                    method="POST"
                                    action="index.php?page=appointments"
                                    class="inline-form patient-appointment-live-form ajax-cancel-form"
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
                                        value="cancel"
                                    >
                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int) $row[
                                                                                    'id'
                                                                                ] ?>"
                                    >
                                    <button
                                        class="action-btn danger"
                                        type="submit"
                                    >Cancel</button>
                                </form>
                                <?php elseif ($row['status'] === 'Cancelled'): ?>
                                <form
                                    method="POST"
                                    action="index.php?page=appointments"
                                    class="inline-form patient-appointment-live-form ajax-cancel-form"
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
                                        value="delete"
                                    >
                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int) $row[
                                                                                    'id'
                                                                                ] ?>"
                                    >
                                    <button
                                        class="action-btn danger"
                                        type="submit"
                                    >Delete</button>
                                </form>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td
                                colspan="7"
                                class="empty-cell"
                            >You have no appointments yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="assets/js/app.js?v=20260923-final"></script>
</body>
</html>
