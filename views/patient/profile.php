<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>MEDITrack - Profile</title>

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
                class="nav-link active"
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


    <main class="main-content">

        <header class="dashboard-header">

            <div>

                <h1>My Profile</h1>

                <p>
                    View and update your patient information
                </p>

            </div>

        </header>


        <section class="panel form-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Personal information
                    </h2>

                    <p>
                        Your account information stored in MEDITrack
                    </p>

                </div>

            </div>


            <?php if ($message): ?>

            <div class="message <?= htmlspecialchars($message_type) ?>">

                <?= htmlspecialchars($message) ?>

            </div>

            <?php endif; ?>


            <form
                method="POST"
                action="index.php?page=profile"
                class="dashboard-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= esc(csrf_token()) ?>"
                >

                <div class="form-row">

                    <div class="form-group">

                        <label>
                            Patient ID
                        </label>

                        <input
                            type="text"
                            value="P<?= str_pad((string) $patient['id'], 4, '0', STR_PAD_LEFT) ?>"
                            disabled
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            value="<?= htmlspecialchars($patient['email']) ?>"
                            disabled
                        >

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="name">
                            Full name
                        </label>

                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="<?= htmlspecialchars($patient['name']) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="phone">
                            Phone
                        </label>

                        <input
                            id="phone"
                            name="phone"
                            type="text"
                            value="<?= htmlspecialchars($patient['phone']) ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="age">
                            Age
                        </label>

                        <input
                            id="age"
                            name="age"
                            type="number"
                            min="1"
                            max="120"
                            value="<?= htmlspecialchars((string) ($patient['age'] ?? '')) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="gender">
                            Gender
                        </label>

                        <select
                            id="gender"
                            name="gender"
                            required
                        >
                            <option value="">Select gender</option>
                            <option
                                value="Male"
                                <?= ($patient['gender'] ?? '') === 'Male'
                                                            ? 'selected'
                                                            : '' ?>
                            >Male</option>
                            <option
                                value="Female"
                                <?= ($patient['gender'] ?? '') === 'Female'
                                                            ? 'selected'
                                                            : '' ?>
                            >Female</option>
                            <option
                                value="Other"
                                <?= ($patient['gender'] ?? '') === 'Other'
                                                            ? 'selected'
                                                            : '' ?>
                            >Other</option>
                        </select>

                    </div>


                    <div class="form-group">

                        <label for="blood_group">
                            Blood group
                        </label>

                        <select
                            id="blood_group"
                            name="blood_group"
                            required
                        >
                            <option value="">Select blood group</option>
                            <?php foreach (
                                                        ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']
                                                        as $group
                                                    ): ?>
                            <option
                                value="<?= $group ?>"
                                <?= ($patient['blood_group'] ?? '') ===
                                $group
                                    ? 'selected'
                                    : '' ?>
                            ><?= $group ?></option>
                            <?php endforeach; ?>
                        </select>

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="emergency_contact">
                            Emergency contact
                        </label>

                        <input
                            id="emergency_contact"
                            name="emergency_contact"
                            type="text"
                            value="<?= htmlspecialchars($patient['emergency_contact'] ?? '') ?>"
                            placeholder="Enter emergency contact number"
                            required
                        >

                    </div>


                    <div class="form-group">

                    </div>

                </div>


                <div class="form-group">

                    <label for="address">
                        Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        rows="4"
                    ><?= htmlspecialchars($patient['address'] ?? '') ?></textarea>

                </div>


                <button
                    class="primary-btn compact"
                    type="submit"
                >
                    Save changes
                </button>

            </form>

        </section>

    </main>


    <script src="assets/js/app.js?v=20260923-final"></script>

</body>
</html>
