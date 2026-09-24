<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width,initial-scale=1.0"
    >
    <title>MEDITrack - Prescription Details</title>
    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20260923-final"
    >
    <style>
        .rx-detail-shell {
            max-width: 930px;
            margin: 0
        }

        .rx-back-btn {
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 0 0 16px;
            padding: 0 14px;
            border: 1px solid #bfd0ef;
            border-radius: 9px;
            background: #fff;
            color: #1554d8;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: .18s ease
        }

        .rx-back-btn:hover {
            border-color: #7ea3ef;
            background: #f6f9ff
        }

        .rx-back-btn svg {
            width: 16px;
            height: 16px;
            stroke: currentColor
        }

        .rx-page-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 18px
        }

        .rx-page-title-row h1 {
            margin: 0;
            color: #101a4b;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 27px;
            font-weight: 700;
            line-height: 1.15
        }

        .rx-print-btn {
            height: 40px;
            min-width: 104px;
            border: 1.5px solid #8fb0f7;
            border-radius: 10px;
            background: #fff;
            color: #0f2a67;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: .18s ease
        }

        .rx-print-btn:hover {
            border-color: #3d72ed;
            color: #1554d8;
            background: #f8fbff
        }

        .rx-print-btn svg {
            width: 19px;
            height: 19px;
            stroke: currentColor
        }

        .rx-detail-card {
            background: #fff;
            border: 1px solid #cfdcf2;
            border-radius: 13px;
            padding: 22px 24px 21px;
            box-shadow: 0 12px 34px rgba(15, 23, 42, .04)
        }

        .rx-doctor-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px
        }

        .rx-doctor-main {
            display: flex;
            align-items: center;
            gap: 17px;
            min-width: 0
        }

        .rx-avatar {
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, #dce9ff, #edf4ff);
            color: #2f66dc;
            font-weight: 800;
            font-size: 20px
        }

        .rx-doctor-copy h2 {
            margin: 0 0 5px;
            color: #111b49;
            font-size: 18px
        }

        .rx-doctor-copy p {
            margin: 0;
            color: #7586a5;
            font-size: 12px
        }

        .rx-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 12px;
            background: #eaf8f1;
            color: #0a8d61;
            font-weight: 700;
            font-size: 12px;
            white-space: nowrap
        }

        .rx-status i {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #0cad76
        }

        .rx-divider {
            height: 1px;
            background: #dbe4f0;
            margin: 17px 0
        }

        .rx-info-row {
            display: grid;
            grid-template-columns: 1fr 1px 1fr;
            gap: 24px;
            align-items: center
        }

        .rx-info-sep {
            height: 40px;
            background: #cdd9ea
        }

        .rx-info-item {
            display: flex;
            align-items: center;
            gap: 13px;
            color: #111b49
        }

        .rx-info-item svg {
            width: 24px;
            height: 24px;
            stroke: #0f2a67;
            flex: 0 0 auto
        }

        .rx-info-item span {
            font-size: 12px;
            color: #111b49
        }

        .rx-info-item strong {
            font-weight: 400
        }

        .rx-meds-title {
            margin: 0 0 12px;
            color: #172554;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.5
        }

        .rx-table-wrap {
            overflow-x: auto;
            border: 1px solid #cfdcf2;
            border-radius: 10px
        }

        .rx-med-table {
            width: 100%;
            min-width: 650px;
            border-collapse: separate;
            border-spacing: 0
        }

        .rx-med-table th {
            padding: 13px 18px;
            background: #f1f5fb;
            color: #566785;
            font-size: 11px;
            font-weight: 700;
            text-align: left;
            border: 0
        }

        .rx-med-table td {
            padding: 14px 18px;
            color: #253554;
            font-size: 12px;
            border-top: 1px solid #e7edf6;
            background: #fff
        }

        .rx-med-table td+td,
        .rx-med-table th+th {
            border-left: 1px solid #e0e8f4
        }

        .rx-med-table .rx-med-name {
            font-weight: 400;
            color: #253554
        }

        .rx-med-table .rx-instruction-row td {
            padding: 13px 18px;
            background: #eef6ff;
            border-left: 0
        }

        .rx-instruction-label {
            color: #155fd8;
            font-weight: 400;
            margin-right: 5px
        }

        .rx-instruction-text {
            color: #4f617e
        }

        .rx-no-instruction {
            color: #8a97ad;
            font-style: italic
        }

        .rx-note {
            margin-top: 14px;
            padding: 14px 18px;
            border: 1px solid #e2e8f2;
            border-radius: 10px;
            background: #f7f9fc;
            color: #111b49
        }

        .rx-note strong {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            font-weight: 400
        }

        .rx-note p {
            margin: 0;
            color: #6f7f9b;
            font-size: 12px;
            line-height: 1.55
        }

        @media(max-width:720px) {
            .rx-detail-card {
                padding: 20px
            }

            .rx-page-title-row {
                align-items: flex-start
            }

            .rx-page-title-row h1 {
                font-size: 27px
            }

            .rx-doctor-row {
                align-items: flex-start;
                flex-direction: column
            }

            .rx-info-row {
                grid-template-columns: 1fr;
                gap: 14px
            }

            .rx-info-sep {
                display: none
            }

            .rx-info-item {
                padding-bottom: 13px;
                border-bottom: 1px solid #e7edf6
            }

            .rx-info-item:last-child {
                padding-bottom: 0;
                border-bottom: 0
            }

            .rx-avatar {
                width: 56px;
                height: 56px;
                flex-basis: 56px
            }

            .rx-print-btn {
                min-width: 96px
            }
        }

        @media(max-width:520px) {
            .rx-page-title-row {
                flex-direction: column
            }

            .rx-print-btn {
                width: 100%
            }

            .rx-detail-card {
                padding: 17px
            }

            .rx-doctor-main {
                align-items: flex-start
            }

            .rx-doctor-copy h2 {
                font-size: 18px
            }
        }

        @media print {

            .sidebar,
            .rx-back-btn,
            .rx-page-title-row .rx-print-btn {
                display: none !important
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                max-width: none !important
            }

            .rx-detail-shell {
                max-width: none !important
            }

            .rx-page-title-row {
                margin-bottom: 16px
            }

            .rx-detail-card {
                border: 0 !important;
                box-shadow: none !important;
                padding: 0 !important
            }
        }
    </style>
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
                class="nav-link"
                href="index.php?page=appointments"
            >Appointments</a>
            <a
                class="nav-link active"
                href="index.php?page=prescriptions"
            >Prescriptions</a>
        </nav>
        <div class="sidebar-bottom"><a
                href="index.php?page=logout&amp;role=patient"
                class="signout-link"
            >Sign out</a></div>
    </aside>

    <main class="main-content">
        <?php
        $p = $prescription;
        $doctorName = trim((string) ($p['doctor_name'] ?? 'Doctor'));
        $cleanDoctorName = preg_replace('/^Dr\.?\s+/i', '', $doctorName);
        $parts = preg_split('/\s+/', $cleanDoctorName, -1, PREG_SPLIT_NO_EMPTY);
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if ($initials === '') {
            $initials = 'DR';
        }
        $appointmentDisplay =
            !empty($p['appointment_id']) && !empty($p['appointment_serial'])
                ? doctor_appointment_display_id($p['doctor_id'], $p['appointment_serial'])
                : '—';
        $medicines = $p['medicines'] ?? [];
        ?>
        <div class="rx-detail-shell">
            <button
                type="button"
                class="rx-back-btn"
                onclick="window.location.href='index.php?page=prescriptions'"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M19 12H5" />
                    <path d="m12 19-7-7 7-7" />
                </svg>
                Back to prescriptions
            </button>

            <div class="rx-page-title-row">
                <h1>Prescription details</h1>
                <button
                    type="button"
                    class="rx-print-btn"
                    onclick="window.print()"
                    aria-label="Print prescription"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke-width="1.9"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M6 9V3h12v6" />
                        <path
                            d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"
                        />
                        <rect
                            x="6"
                            y="14"
                            width="12"
                            height="7"
                            rx="1"
                        />
                    </svg>
                    Print
                </button>
            </div>

            <section class="rx-detail-card">
                <div class="rx-doctor-row">
                    <div class="rx-doctor-main">
                        <div class="rx-avatar"><?= esc($initials) ?></div>
                        <div class="rx-doctor-copy">
                            <h2>Dr. <?= esc($cleanDoctorName) ?></h2>
                            <p><?= esc($p['specialization'] ?? '') ?></p>
                        </div>
                    </div>
                    <span class="rx-status"><i></i><?= esc($p['status'] ?? 'Active') ?></span>
                </div>

                <div class="rx-divider"></div>

                <div class="rx-info-row">
                    <div class="rx-info-item">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="16"
                                rx="2"
                            />
                            <path d="M16 3v4M8 3v4M3 10h18" />
                        </svg>
                        <span>Appointment ID: <strong><?= esc($appointmentDisplay) ?></strong></span>
                    </div>
                    <div class="rx-info-sep"></div>
                    <div class="rx-info-item">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="16"
                                rx="2"
                            />
                            <path d="M16 3v4M8 3v4M3 10h18" />
                        </svg>
                        <span>Date: <strong><?= esc(date('d M Y', strtotime($p['created_at']))) ?></strong></span>
                    </div>
                </div>

                <div class="rx-divider"></div>

                <h2 class="rx-meds-title">Medicines &amp; instructions</h2>
                <div class="rx-table-wrap">
                    <table class="rx-med-table">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$medicines): ?>
                            <tr>
                                <td colspan="4">No medicine details recorded.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($medicines as $m): ?>
                            <tr>
                                <td class="rx-med-name"><?= esc($m['medicine_name'] ?: 'Not specified') ?></td>
                                <td><?= esc($m['dosage'] ?: 'Not specified') ?></td>
                                <td><?= esc(
                                                  ($m['frequency'] ?? '') !== '' ? $m['frequency'] : 'Not specified'
                                              ) ?></td>
                                <td><?= esc(($m['duration'] ?? '') !== '' ? $m['duration'] : 'Not specified') ?></td>
                            </tr>
                            <tr class="rx-instruction-row">
                                <td colspan="4">
                                    <span class="rx-instruction-label">Instructions:</span>
                                    <?php if (trim((string) ($m['instructions'] ?? '')) !== ''): ?>
                                    <span class="rx-instruction-text"><?= esc($m['instructions']) ?></span>
                                    <?php else: ?>
                                    <span class="rx-no-instruction">No special instruction</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="rx-note">
                    <strong>Doctor's note</strong>
                    <p><?= esc(
                              trim((string) ($p['note'] ?? '')) !== '' ? $p['note'] : 'No additional note provided.'
                          ) ?></p>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
