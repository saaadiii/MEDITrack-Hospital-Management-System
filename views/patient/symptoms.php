<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>MEDITrack - Symptoms</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20260923-final"
    >

</head>

<body class="dashboard-page">

    <aside class="sidebar">

        <div class="sidebar-brand">
            MEDI<span>Track</span>
        </div>

        <nav>

            <a
                class="nav-link"
                href="index.php?page=patient"
            >
                Dashboard
            </a>

            <a
                class="nav-link"
                href="index.php?page=profile"
            >
                Profile
            </a>

            <a
                class="nav-link active"
                href="index.php?page=symptoms"
            >
                Symptom tracking
            </a>

            <a
                class="nav-link"
                href="index.php?page=followups"
            >
                Follow-up reminders
            </a>

            <a
                class="nav-link"
                href="index.php?page=appointments"
            >
                Appointments
            </a>


            <a
                class="nav-link"
                href="index.php?page=prescriptions"
            >
                Prescriptions
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a
                href="index.php?page=logout&amp;role=patient"
                class="signout-link"
            >
                Sign out
            </a>

        </div>

    </aside>


    <main class="main-content">

        <header class="dashboard-header">

            <div>

                <h1>
                    Symptom tracking
                </h1>

                <p>
                    Keep a simple record of how you have been feeling.
                </p>

            </div>

        </header>


        <?php if ($message): ?>

        <div class="message <?= htmlspecialchars($message_type) ?>">

            <?= htmlspecialchars($message) ?>

        </div>

        <?php endif; ?>


        <section class="panel form-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        <?= $edit_symptom ? 'Edit symptom' : 'Add symptom' ?>
                    </h2>

                    <p>
                        Record your symptoms for your doctor to review.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                action="index.php?page=symptoms"
                class="dashboard-form"
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
                    value="<?= $edit_symptom ? 'edit' : 'add' ?>"
                >

                <?php if ($edit_symptom): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $edit_symptom['id'] ?>"
                >

                <?php endif; ?>


                <div class="form-row">

                    <div class="form-group">

                        <label for="symptom">
                            Symptom
                        </label>

                        <input
                            id="symptom"
                            name="symptom"
                            type="text"
                            placeholder="e.g. Headache"
                            value="<?= htmlspecialchars($edit_symptom['symptom'] ?? '') ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="severity">
                            Severity (1–10)
                        </label>

                        <input
                            id="severity"
                            name="severity"
                            type="number"
                            min="1"
                            max="10"
                            value="<?= htmlspecialchars($edit_symptom['severity'] ?? '5') ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="duration">
                            Duration
                        </label>

                        <input
                            id="duration"
                            name="duration"
                            type="text"
                            placeholder="e.g. 3 days"
                            value="<?= htmlspecialchars($edit_symptom['duration'] ?? '') ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="frequency">
                            Frequency
                        </label>

                        <input
                            id="frequency"
                            name="frequency"
                            type="text"
                            placeholder="e.g. Daily"
                            value="<?= htmlspecialchars($edit_symptom['frequency'] ?? '') ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="symptom_date">
                            Date
                        </label>

                        <input
                            id="symptom_date"
                            name="symptom_date"
                            type="date"
                            max="<?= htmlspecialchars(date('Y-m-d')) ?>"
                            data-date-rule="past-or-today"
                            data-default-today="1"
                            value="<?= htmlspecialchars(
                                                        $edit_symptom['symptom_date'] ?? date('Y-m-d')
                                                    ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="notes">
                            Note
                        </label>

                        <input
                            id="notes"
                            name="notes"
                            type="text"
                            placeholder="Optional note"
                            value="<?= htmlspecialchars($edit_symptom['notes'] ?? '') ?>"
                        >

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        class="primary-btn compact"
                        type="submit"
                    >
                        <?= $edit_symptom ? 'Update symptom' : 'Save symptom' ?>
                    </button>


                    <?php if ($edit_symptom): ?>

                    <a
                        class="secondary-btn"
                        href="index.php?page=symptoms"
                    >
                        Cancel
                    </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>


        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Symptom history
                    </h2>

                    <p>
                        Your recorded symptoms
                    </p>

                </div>

                <div class="toolbar patient-standard-search">
                    <input
                        id="symptom-search"
                        type="search"
                        placeholder="Search by symptom, severity, duration, frequency, date or note"
                        autocomplete="off"
                    >
                    <button
                        type="button"
                        class="action-btn secondary"
                        id="symptom-search-button"
                    >Search</button>
                </div>

            </div>


            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>Symptom</th>
                            <th>Severity</th>
                            <th>Duration</th>
                            <th>Frequency</th>
                            <th>Date</th>
                            <th>Note</th>
                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody
                        id="symptom-table-body"
                        data-empty-message="No matching symptoms found."
                    >

                        <?php if (count($symptoms)): ?>
                        <?php foreach ($symptoms as $row): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($row['symptom']) ?>
                            </td>

                            <td>
                                <?= (int) $row['severity'] ?>/10
                            </td>

                            <td>
                                <?= htmlspecialchars($row['duration']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['frequency']) ?>
                            </td>

                            <td>
                                <?= date('d M Y', strtotime($row['symptom_date'])) ?>
                            </td>

                            <td>
                                <?= $row['notes'] ? htmlspecialchars($row['notes']) : '—' ?>
                            </td>

                            <td class="actions">

                                <a
                                    class="action-btn secondary"
                                    href="index.php?page=symptoms&edit=<?= (int) $row['id'] ?>"
                                >
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="index.php?page=symptoms"
                                    class="inline-form symptom-delete-form ajax-cancel-form"
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
                                        name="id"
                                        value="<?= (int) $row['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="action-btn danger"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                        <?php endforeach; ?>
                        <?php else: ?>

                        <tr>

                            <td
                                colspan="7"
                                class="empty-cell"
                            >
                                No symptoms have been recorded yet.
                            </td>

                        </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <p class="disclaimer">
                This log helps your doctor spot patterns —
                it isn't a diagnosis.
            </p>

        </section>

    </main>


    <script src="assets/js/app.js?v=20260923-final"></script>

</body>
</html>
