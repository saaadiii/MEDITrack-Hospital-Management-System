<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>MEDITrack - Follow-ups</title>

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
                class="nav-link"
                href="index.php?page=symptoms"
            >
                Symptom tracking
            </a>

            <a
                class="nav-link active"
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
                    Follow-up reminders
                </h1>

                <p>
                    Keep track of follow-up appointments and instructions
                    from your doctor.
                </p>

            </div>

        </header>


        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        My follow-ups
                    </h2>

                    <p>
                        Follow-ups assigned to your patient account
                    </p>

                </div>

            </div>


            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Purpose</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($followups)): ?>
                        <?php foreach ($followups as $row): ?>

                        <tr>

                            <td>
                                <?= date('d M Y', strtotime($row['followup_date'])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['doctor_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['specialization']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['purpose']) ?>
                            </td>

                            <td>

                                <span class="status <?= strtolower($row['status']) ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>

                            </td>

                        </tr>

                        <?php endforeach; ?>
                        <?php else: ?>

                        <tr>

                            <td
                                colspan="5"
                                class="empty-cell"
                            >
                                No follow-up reminders have been added yet.
                            </td>

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
