<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>MEDITrack - Dashboard</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20260923-final"
    >

    <style>
        /* Follow-up reminder card: kept inline so browser cache cannot hide this UI */
        #followup-section .followup-reminder-panel {
            max-width: 760px;
            padding: 22px 24px 24px;
        }

        #followup-section .followup-panel-header {
            margin-bottom: 18px;
        }

        #followup-section .patient-followup-card {
            display: grid;
            grid-template-columns: 110px 1px minmax(0, 1fr);
            gap: 24px;
            align-items: stretch;
        }

        #followup-section .patient-followup-datebox {
            min-height: 104px;
            border-radius: 14px;
            background: linear-gradient(180deg, #f1f5ff 0%, #edf3ff 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid #e1e9fb;
        }

        #followup-section .patient-followup-datebox strong {
            color: #315be3 !important;
            font-size: 34px;
            line-height: 1;
            font-weight: 800 !important;
        }

        #followup-section .patient-followup-datebox span {
            margin-top: 5px;
            color: #315be3;
            font-size: 13px;
            line-height: 1;
            font-weight: 800;
        }

        #followup-section .patient-followup-datebox small {
            margin-top: 8px;
            color: #7b8aa4;
            font-size: 12px;
            font-weight: 600;
        }

        #followup-section .patient-followup-divider {
            width: 1px;
            background: #dbe4f4;
            border-radius: 999px;
        }

        #followup-section .patient-followup-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        #followup-section .patient-followup-toprow {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        #followup-section .patient-followup-doctor-info {
            min-width: 0;
            display: grid;
            gap: 4px;
        }

        #followup-section .patient-followup-doctor-info strong {
            color: #23345f !important;
            font-size: 16px;
            line-height: 1.25;
            font-weight: 750 !important;
        }

        #followup-section .patient-followup-doctor-info span {
            color: #74839d;
            font-size: 12px;
            line-height: 1.35;
        }

        #followup-section .patient-followup-badge {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 34px;
            padding: 0 13px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        #followup-section .patient-followup-badge.tomorrow {
            color: #a35c00;
            background: #fff5d9;
            border: 1px solid #ffe8ad;
        }

        #followup-section .patient-followup-badge.today {
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #c8f3df;
        }

        #followup-section .patient-followup-badge.upcoming {
            color: #315be3;
            background: #eef4ff;
            border: 1px solid #dce8ff;
        }

        #followup-section .patient-followup-badge.overdue {
            color: #c24141;
            background: #fff1f1;
            border: 1px solid #ffd6d6;
        }

        #followup-section .patient-followup-clock {
            font-size: 17px;
            line-height: 1;
        }

        #followup-section .patient-followup-purpose {
            display: flex;
            align-items: center;
            gap: 6px;
            min-height: 42px;
            margin-top: 12px;
            padding: 0 14px;
            border-radius: 10px;
            background: #f1f5fb;
            color: #74839d;
            font-size: 12px;
        }

        #followup-section .patient-followup-purpose-icon {
            color: #425273;
            font-size: 14px;
        }

        #followup-section .patient-followup-purpose strong {
            color: #35425f !important;
            font-size: 12px;
            font-weight: 750 !important;
        }

        #followup-section .patient-followup-message {
            margin-top: 9px;
            color: #9a620d;
            font-size: 11px;
            font-weight: 700;
        }

        #followup-section .patient-followup-message.today-message {
            color: #047857;
        }

        @media(max-width:760px) {
            #followup-section .followup-reminder-panel {
                max-width: none;
            }

            #followup-section .patient-followup-card {
                grid-template-columns: 92px 1px minmax(0, 1fr);
                gap: 16px;
            }

            #followup-section .patient-followup-datebox {
                min-height: 96px;
            }

            #followup-section .patient-followup-datebox strong {
                font-size: 30px;
            }
        }

        @media(max-width:540px) {
            #followup-section .patient-followup-card {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            #followup-section .patient-followup-divider {
                display: none;
            }

            #followup-section .patient-followup-datebox {
                width: 96px;
            }

            #followup-section .patient-followup-toprow {
                align-items: center;
            }
        }
    </style>

</head>


<body class="dashboard-page">


    <!-- =====================================================
     SIDEBAR
===================================================== -->

    <aside class="sidebar">

        <div class="sidebar-brand">
            MEDI<span>Track</span>
        </div>


        <nav>

            <a
                class="nav-link active"
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


    <!-- =====================================================
     MAIN CONTENT
===================================================== -->

    <main class="main-content">


        <!-- =====================================================
     HEADER
===================================================== -->

        <header class="dashboard-header">

            <div>

                <h1>

                    <?= htmlspecialchars($greeting) ?>,
                    <?= htmlspecialchars($patient['name']) ?>

                </h1>


                <p>

                    Patient ID
                    P<?= str_pad((string) $patient_id, 4, '0', STR_PAD_LEFT) ?>


                </p>

            </div>

        </header>


        <!-- =====================================================
     STAT CARDS
===================================================== -->

        <section class="stats-grid">


            <!-- Logged Symptoms -->

            <div class="stat-card">

                <span>
                    Logged symptoms
                </span>

                <strong>
                    <?= $symptom_count ?>
                </strong>

            </div>


            <!-- Next Follow-up -->

            <div class="stat-card">

                <span>
                    Next follow-up
                </span>

                <strong>
                    <?= htmlspecialchars($followup_display) ?>
                </strong>

            </div>


            <!-- Upcoming Appointment -->

            <div class="stat-card">

                <span>
                    Upcoming appointment
                </span>

                <strong>
                    <?= htmlspecialchars($appointment_display) ?>
                </strong>

            </div>


            <!-- Active Prescriptions -->

            <div class="stat-card">

                <span>
                    Active prescriptions
                </span>

                <strong>
                    <?= (int) $prescription_count ?>
                </strong>

            </div>

        </section>


        <!-- =====================================================
     DASHBOARD TABS
===================================================== -->

        <div class="dashboard-tabs">


            <a
                href="#"
                class="tab active"
                data-section="symptoms-section"
            >
                Symptom tracking
            </a>


            <a
                href="#"
                class="tab"
                data-section="followup-section"
            >
                Follow-up reminders
            </a>


            <a
                href="#"
                class="tab"
                data-section="appointment-section"
            >
                Appointment
            </a>

        </div>


        <!-- =====================================================
     SYMPTOM SECTION
===================================================== -->

        <section
            id="symptoms-section"
            class="dashboard-tab-section"
        >


            <section class="panel">

                <div class="panel-header">

                    <div>

                        <h2>
                            Recent symptoms
                        </h2>

                        <p>
                            Your latest recorded symptoms
                        </p>

                    </div>


                    <a
                        class="small-btn"
                        href="index.php?page=symptoms"
                    >
                        + Add symptom
                    </a>

                </div>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Symptom
                                </th>

                                <th>
                                    Severity
                                </th>

                                <th>
                                    Duration
                                </th>

                                <th>
                                    Frequency
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Note
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (count($symptoms_result) > 0): ?>
                            <?php foreach ($symptoms_result as $row): ?>


                            <tr>


                                <td>

                                    <?= htmlspecialchars($row['symptom']) ?>

                                </td>


                                <td>

                                    <div class="severity">

                                        <span class="severity-bars">

                                            <?php for ($i = 1; $i <= 10; $i++): ?>

                                            <i class="<?= $i <= (int) $row['severity']
                                                                                                ? 'filled'
                                                                                                : '' ?>"></i>

                                            <?php endfor; ?>

                                        </span>


                                        <?= (int) $row['severity'] ?>/10

                                    </div>

                                </td>


                                <td>

                                    <?= htmlspecialchars($row['duration']) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars($row['frequency']) ?>

                                </td>


                                <td>

                                    <?= date('d M', strtotime($row['symptom_date'])) ?>

                                </td>


                                <td>

                                    <?= $row['notes'] ? htmlspecialchars($row['notes']) : '—' ?>

                                </td>


                            </tr>


                            <?php endforeach; ?>
                            <?php else: ?>


                            <tr>

                                <td
                                    colspan="6"
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

        </section>


        <!-- =====================================================
     FOLLOW-UP PREVIEW
===================================================== -->

        <section
            id="followup-section"
            class="dashboard-tab-section"
            style="display:none;"
        >

            <section class="panel followup-reminder-panel">

                <div class="panel-header followup-panel-header">

                    <div>

                        <h2>
                            Follow-up reminder
                        </h2>

                        <p>
                            Your next revisit recommended by your doctor
                        </p>

                    </div>

                </div>

                <?php if ($next_followup): ?>
                <?php
                $followupTimestamp = strtotime($next_followup['followup_date']);
                $followupDay = date('d', $followupTimestamp);
                $followupMonth = strtoupper(date('M', $followupTimestamp));
                $followupYear = date('Y', $followupTimestamp);
                $today = new DateTime('today');
                $followupDateObject = new DateTime(date('Y-m-d', $followupTimestamp));
                $dayDifference = (int) $today->diff($followupDateObject)->format('%r%a');
                if ($dayDifference === 0) {
                    $followupBadgeText = 'Today';
                    $followupBadgeClass = 'today';
                } elseif ($dayDifference === 1) {
                    $followupBadgeText = 'Tomorrow';
                    $followupBadgeClass = 'tomorrow';
                } elseif ($dayDifference > 1) {
                    $followupBadgeText = 'Upcoming';
                    $followupBadgeClass = 'upcoming';
                } else {
                    $followupBadgeText = 'Overdue';
                    $followupBadgeClass = 'overdue';
                }
                $doctorDisplayName = trim((string) $next_followup['doctor_name']);
                if (stripos($doctorDisplayName, 'dr.') !== 0 && stripos($doctorDisplayName, 'dr ') !== 0) {
                    $doctorDisplayName = 'Dr. ' . $doctorDisplayName;
                }
                ?>

                <div class="patient-followup-card">

                    <div class="patient-followup-datebox">
                        <strong><?= htmlspecialchars($followupDay) ?></strong>
                        <span><?= htmlspecialchars($followupMonth) ?></span>
                        <small><?= htmlspecialchars($followupYear) ?></small>
                    </div>

                    <div class="patient-followup-divider"></div>

                    <div class="patient-followup-main">

                        <div class="patient-followup-toprow">

                            <div class="patient-followup-doctor-info">
                                <strong><?= htmlspecialchars($doctorDisplayName) ?></strong>
                                <span><?= htmlspecialchars($next_followup['specialization']) ?></span>
                            </div>

                            <div class="patient-followup-badge <?= htmlspecialchars(
                                                        $followupBadgeClass
                                                    ) ?>">
                                <span class="patient-followup-clock">◷</span>
                                <?= htmlspecialchars($followupBadgeText) ?>
                            </div>

                        </div>

                        <div class="patient-followup-purpose">
                            <span>Purpose:</span>
                            <strong><?= htmlspecialchars($next_followup['purpose']) ?></strong>
                        </div>

                        <?php if ($dayDifference === 1): ?>
                        <div class="patient-followup-message">
                            Your follow-up appointment is tomorrow.
                        </div>
                        <?php elseif ($dayDifference === 0): ?>
                        <div class="patient-followup-message today-message">
                            Your follow-up appointment is today.
                        </div>
                        <?php endif; ?>

                    </div>

                </div>

                <?php else: ?>

                <div class="empty-state">

                    <p>
                        No follow-up reminder has been added yet.
                    </p>

                </div>

                <?php endif; ?>

            </section>

        </section>


        <!-- =====================================================
     APPOINTMENT PREVIEW
===================================================== -->

        <section
            id="appointment-section"
            class="dashboard-tab-section"
            style="display:none;"
        >

            <section class="panel">

                <div class="panel-header">

                    <div>

                        <h2>
                            Upcoming appointment
                        </h2>

                        <p>
                            Your next scheduled consultation
                        </p>

                    </div>

                    <a
                        class="small-btn"
                        href="index.php?page=appointments"
                    >
                        Book Appointment
                    </a>

                </div>

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Date</th>
                                <th>Time</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($upcoming_appointment): ?>

                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars(
                                                                    $upcoming_appointment['doctor_name']
                                                                ) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($upcoming_appointment['specialization']) ?>
                                </td>
                                <td>
                                    <?= date(
                                                                    'd M Y',
                                                                    strtotime($upcoming_appointment['appointment_date'])
                                                                ) ?>
                                </td>
                                <td>
                                    <?= date(
                                                                    'h:i A',
                                                                    strtotime($upcoming_appointment['appointment_time'])
                                                                ) ?>
                                </td>
                            </tr>

                            <?php else: ?>

                            <tr>
                                <td
                                    colspan="4"
                                    class="empty-cell"
                                >
                                    You don't have an upcoming appointment.
                                </td>
                            </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </section>


    </main>

    <!-- =====================================================
     TAB SWITCHING
===================================================== -->

    <script>
        document.addEventListener(
            "DOMContentLoaded",
            function() {

                const tabs =
                    document.querySelectorAll(
                        ".dashboard-tabs .tab"
                    );

                const sections =
                    document.querySelectorAll(
                        ".dashboard-tab-section"
                    );

                tabs.forEach(function(tab) {

                    tab.addEventListener(
                        "click",
                        function(event) {

                            event.preventDefault();

                            const target =
                                this.getAttribute(
                                    "data-section"
                                );

                            /*
                             * Remove active
                             * from all tabs
                             */

                            tabs.forEach(function(item) {

                                item.classList.remove(
                                    "active"
                                );

                            });

                            /*
                             * Hide all sections
                             */

                            sections.forEach(function(section) {

                                section.style.display =
                                    "none";

                            });

                            /*
                             * Activate clicked tab
                             */

                            this.classList.add(
                                "active"
                            );

                            /*
                             * Show selected section
                             */

                            const selectedSection =
                                document.getElementById(
                                    target
                                );

                            if (selectedSection) {

                                selectedSection.style.display =
                                    "block";

                            }

                        }
                    );

                });

            }
        );
    </script>


    <script src="assets/js/app.js?v=20260923-final"></script>

</body>

</html>
