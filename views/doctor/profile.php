<?php
$title = 'Doctor Profile';
$current = 'doctor_profile';
$logoutRole = 'doctor';
$heading = 'My Profile';
$subheading = 'View and update your doctor information';
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


<section class="panel form-panel">
    <div class="panel-header">
        <div>
            <h2>Doctor information</h2>
            <p>Your personal and professional information stored in MEDITrack</p>
        </div>
    </div>

    <form
        method="POST"
        action="index.php?page=doctor_profile"
        class="dashboard-form"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= esc(csrf_token()) ?>"
        >

        <div class="form-row">
            <div class="form-group">
                <label>Doctor ID</label>
                <input
                    type="text"
                    value="D<?= str_pad(
                                        (string) $d['id'],
                                        4,
                                        '0',
                                        STR_PAD_LEFT
                                    ) ?>"
                    disabled
                >
            </div>
            <div class="form-group">
                <label>Email</label>
                <input
                    type="email"
                    value="<?= esc(
                                        $d['email'] ?? (get_role_session('doctor')['user_email'] ?? '')
                                    ) ?>"
                    disabled
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="name">Full name</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="100"
                    value="<?= esc(
                                        $d['name'] ?? ''
                                    ) ?>"
                    required
                >
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input
                    id="phone"
                    name="phone"
                    type="text"
                    maxlength="20"
                    value="<?= esc(
                                        $d['phone'] ?? ''
                                    ) ?>"
                    placeholder="Enter phone number"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="gender">Gender</label>
                <select
                    id="gender"
                    name="gender"
                >
                    <option value="">Select gender</option>
                    <option
                        value="Male"
                        <?= ($d['gender'] ?? '') === 'Male'
                                                ? 'selected'
                                                : '' ?>
                    >Male</option>
                    <option
                        value="Female"
                        <?= ($d['gender'] ?? '') === 'Female'
                                                ? 'selected'
                                                : '' ?>
                    >Female</option>
                    <option
                        value="Other"
                        <?= ($d['gender'] ?? '') === 'Other'
                                                ? 'selected'
                                                : '' ?>
                    >Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="specialization">Specialization</label>
                <select
                    id="specialization"
                    name="specialization"
                    required
                >
                    <option value="">Select specialization</option>
                    <?php
                                        $currentSpecialization = trim($d['specialization'] ?? '');
                                        $specializationOptions = doctor_specializations();
                                        if (
                                            $currentSpecialization !== '' &&
                                            !in_array($currentSpecialization, $specializationOptions, true)
                                        ): ?>
                    <option
                        value="<?= esc($currentSpecialization) ?>"
                        selected
                    ><?= esc(
                        $currentSpecialization
                    ) ?> (Current)</option>
                    <?php endif;
                                        foreach ($specializationOptions as $specialization): ?>
                    <option
                        value="<?= esc($specialization) ?>"
                        <?= $currentSpecialization ===
                        $specialization
                            ? 'selected'
                            : '' ?>
                    ><?= esc($specialization) ?></option>
                    <?php endforeach;
                                        ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="qualification">Qualification</label>
                <input
                    id="qualification"
                    name="qualification"
                    type="text"
                    maxlength="150"
                    value="<?= esc(
                                        $d['qualification'] ?? ''
                                    ) ?>"
                    placeholder="e.g. MBBS, FCPS"
                >
            </div>
            <div class="form-group">
                <label for="license_number">Medical registration / license no.</label>
                <input
                    id="license_number"
                    name="license_number"
                    type="text"
                    maxlength="100"
                    value="<?= esc(
                                        $d['license_number'] ?? ''
                                    ) ?>"
                    placeholder="Enter registration number"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="experience">Years of experience</label>
                <input
                    id="experience"
                    name="experience"
                    type="number"
                    min="0"
                    max="80"
                    value="<?= esc(
                                        (string) ($d['experience'] ?? 0)
                                    ) ?>"
                >
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <select
                    id="department"
                    name="department"
                >
                    <option value="">Select department</option>
                    <?php
                                        $currentDepartment = trim($d['department'] ?? '');
                                        $departmentOptions = hospital_departments();
                                        if (
                                            $currentDepartment !== '' &&
                                            !in_array($currentDepartment, $departmentOptions, true)
                                        ): ?>
                    <option
                        value="<?= esc($currentDepartment) ?>"
                        selected
                    ><?= esc(
                        $currentDepartment
                    ) ?> (Current)</option>
                    <?php endif;
                                        foreach ($departmentOptions as $department): ?>
                    <option
                        value="<?= esc($department) ?>"
                        <?= $currentDepartment ===
                        $department
                            ? 'selected'
                            : '' ?>
                    ><?= esc($department) ?></option>
                    <?php endforeach;
                                        ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="room_number">Consultation room</label>
                <input
                    id="room_number"
                    name="room_number"
                    type="text"
                    maxlength="50"
                    value="<?= esc(
                                        $d['room_number'] ?? ''
                                    ) ?>"
                    placeholder="e.g. Room 304"
                >
            </div>
            <div class="form-group">
                <label>Account status</label>
                <input
                    type="text"
                    value="<?= esc($d['status'] ?? 'Active') ?>"
                    disabled
                >
            </div>
        </div>

        <div class="form-group">
            <label for="bio">Professional bio</label>
            <textarea
                id="bio"
                name="bio"
                rows="4"
                maxlength="1000"
                placeholder="Short professional introduction"
            ><?= esc(
                            $d['bio'] ?? ''
                        ) ?></textarea>
        </div>

        <button
            class="primary-btn compact"
            type="submit"
        >Save changes</button>
    </form>
</section>

<?php require __DIR__ . '/../shared/bottom.php'; ?>
