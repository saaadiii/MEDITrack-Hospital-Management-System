<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width,initial-scale=1.0"
    >
    <title>MEDITrack - Prescriptions</title>
    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20260923-final"
    >
    <style>
        .rx-page-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px
        }

        .rx-page-copy h1 {
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 27px;
            font-weight: 700;
            color: #111b49;
            margin: 0 0 6px
        }

        .rx-page-copy p {
            margin: 0;
            color: #7384a4;
            font-size: 13px
        }

        .rx-search-wrap {
            width: min(390px, 100%)
        }

        .rx-search-label {
            display: block;
            margin: 0 0 7px;
            color: #334155;
            font-size: 12px;
            font-weight: 700
        }

        .rx-search-box {
            position: relative
        }

        .rx-search-box input {
            width: 100%;
            height: 44px;
            border: 1px solid #cfdcf3;
            border-radius: 12px;
            background: #fff;
            padding: 0 42px 0 14px;
            color: #172554;
            font: inherit;
            font-size: 13px;
            outline: none
        }

        .rx-search-box input:focus {
            border-color: #6c8cff;
            box-shadow: 0 0 0 3px rgba(65, 105, 225, .1)
        }

        .rx-search-symbol {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #5070d9;
            font-size: 17px;
            pointer-events: none
        }

        .rx-search-help {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 7px;
            color: #94a3b8;
            font-size: 11px
        }

        .rx-results {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px
        }

        .rx-overview-card {
            background: #fff;
            border: 1px solid #d8e2f1;
            border-radius: 17px;
            padding: 18px;
            box-shadow: 0 8px 22px rgba(28, 52, 91, .045);
            display: grid;
            gap: 16px;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease
        }

        .rx-overview-card:hover {
            transform: translateY(-2px);
            border-color: #bdd0ee;
            box-shadow: 0 14px 28px rgba(28, 52, 91, .075)
        }

        .rx-overview-main {
            display: flex;
            gap: 13px;
            align-items: center
        }

        .rx-overview-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, #dbe9ff, #edf4ff);
            color: #3264dd;
            font-size: 18px;
            font-weight: 800;
            flex: 0 0 52px
        }

        .rx-overview-eyebrow {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
            font-weight: 800
        }

        .rx-overview-doctor h2 {
            margin: 2px 0 3px;
            color: #111b49;
            font-size: 17px
        }

        .rx-overview-doctor p {
            margin: 0;
            color: #7384a4;
            font-size: 12px
        }

        .rx-overview-meta {
            display: grid;
            grid-template-columns: 1.2fr 1fr .65fr;
            gap: 10px;
            padding: 12px 0;
            border-top: 1px solid #edf1f7;
            border-bottom: 1px solid #edf1f7
        }

        .rx-overview-meta div {
            min-width: 0
        }

        .rx-overview-meta span {
            display: block;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 4px
        }

        .rx-overview-meta strong {
            display: block;
            color: #172554;
            font-size: 13px;
            font-weight: 400;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .rx-overview-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px
        }

        .rx-overview-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #07875b;
            font-weight: 700;
            font-size: 12px
        }

        .rx-overview-status i {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #19ad78
        }

        .rx-view-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border-radius: 10px;
            background: #eff5ff;
            color: #245dd9;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: 400;
            text-decoration: none
        }

        .rx-view-btn:hover {
            background: #e4eeff
        }

        .rx-empty-state {
            grid-column: 1/-1;
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 17px;
            padding: 42px 28px;
            text-align: center
        }

        .rx-empty-icon {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            margin: 0 auto 14px;
            background: #edf4ff;
            color: #2e63dd;
            font-weight: 800
        }

        .rx-empty-state h3 {
            margin: 0 0 7px;
            color: #111b49
        }

        .rx-empty-state p {
            margin: 0;
            color: #8190aa
        }

        .rx-loading {
            opacity: .55;
            pointer-events: none
        }

        .rx-ajax-error {
            grid-column: 1/-1;
            padding: 14px 16px;
            border: 1px solid #fecaca;
            background: #fff5f5;
            color: #b91c1c;
            border-radius: 12px;
            font-size: 13px
        }

        @media(max-width:1050px) {
            .rx-results {
                grid-template-columns: 1fr
            }
        }

        @media(max-width:760px) {
            .rx-page-head {
                align-items: stretch;
                flex-direction: column
            }

            .rx-search-wrap {
                width: 100%
            }
        }

        @media(max-width:520px) {
            .rx-overview-meta {
                grid-template-columns: 1fr 1fr
            }

            .rx-overview-meta div:last-child {
                grid-column: 1/-1
            }

            .rx-overview-actions {
                align-items: stretch;
                flex-direction: column
            }

            .rx-view-btn {
                justify-content: center
            }

            .rx-search-help {
                flex-direction: column;
                gap: 3px
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
            >Dashboard</a><a
                class="nav-link"
                href="index.php?page=profile"
            >Profile</a><a
                class="nav-link"
                href="index.php?page=symptoms"
            >Symptom tracking</a><a
                class="nav-link"
                href="index.php?page=followups"
            >Follow-up reminders</a><a
                class="nav-link"
                href="index.php?page=appointments"
            >Appointments</a><a
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
        <div class="rx-page-head">
            <div class="rx-page-copy">
                <h1>My Prescriptions</h1>
                <p>Browse your prescriptions and open one to view the full treatment details.</p>
            </div>
            <div class="toolbar patient-standard-search">
                <input
                    id="prescriptionDoctorSearch"
                    type="search"
                    autocomplete="off"
                    placeholder="Search by doctor, specialization, medicine, status, date or appointment ID"
                >
                <button
                    type="button"
                    class="action-btn secondary"
                    id="prescriptionDoctorSearchButton"
                >Search</button>
            </div>
        </div>
        <div
            class="rx-results"
            id="prescriptionResults"
            aria-live="polite"
        ><?php
        $search_query = '';
        require __DIR__ . '/_prescription_cards.php';
        ?></div>
    </main>
    <script>
        (function() {
            const input = document.getElementById('prescriptionDoctorSearch'),
                button = document.getElementById('prescriptionDoctorSearchButton'),
                results = document.getElementById('prescriptionResults');
            let timer = null,
                controller = null;

            function search() {
                const q = input.value.trim();
                if (controller) controller.abort();
                controller = new AbortController();
                results.classList.add('rx-loading');
                fetch('index.php?page=ajax&action=search_patient_prescriptions&q=' +
                    encodeURIComponent(q), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: controller.signal
                    }).then(r => {
                    if (!r.ok) throw new Error();
                    return r.json()
                }).then(data => {
                    if (!data.success) throw new Error();
                    results.innerHTML = data.html
                }).catch(e => {
                    if (e.name === 'AbortError') return;
                    results.innerHTML =
                        '<div class="rx-ajax-error">Could not search prescriptions. Please try again.</div>'
                }).finally(() => results.classList.remove('rx-loading'))
            }
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(search, 220)
            });
            button?.addEventListener('click', search)
        })();
    </script>
</body>
</html>
