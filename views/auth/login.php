<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width,initial-scale=1.0"
    >
    <title>MEDITrack - Login</title>
    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20260923-final"
    >
</head>
<body class="auth-page">
    <div class="auth-card auth-split-card login-card">
        <div class="auth-visual">
            <div class="brand"><span class="brand-main">MEDI</span><span
                    class="brand-accent">Track</span></div>
            <p class="brand-subtitle">Hospital patient, appointment & resource management</p>
            <img
                src="assets/images/medical login.jpg"
                alt="Medical healthcare illustration"
                class="medical-login-image"
            >
        </div>
        <div class="auth-content">
            <h1>Sign in to MEDITrack</h1>
            <p class="auth-description">Access your healthcare management dashboard</p>
            <div class="role-section">
                <label>Sign in as</label>
                <div class="role-options auth-role-options">
                    <?php foreach (
                        [
                            'patient' => 'Patient',
                            'doctor' => 'Doctor',
                            'receptionist' => 'Receptionist',
                            'admin' => 'Admin'
                        ]
                        as $r => $label
                    ): ?>
                    <button
                        type="button"
                        class="role-option <?= ($selected_role ?? 'patient') === $r
                            ? 'active'
                            : '' ?>"
                        data-role="<?= $r ?>"
                    ><?= $label ?></button><?php endforeach; ?>
                </div>
            </div>
            <?php if (!empty($error)): ?>
            <div class="form-message error"><?= esc($error) ?></div><?php endif; ?>
            <?php if (
                ($_GET['profile_reset'] ?? '') ===
                'patient'
            ): ?>
            <div class="form-message error">Your previous patient session no longer matches the
                current database. Please sign in again, or create a new patient account if this is a
                fresh database.</div><?php endif; ?>
            <?php if (
                ($_GET['profile_reset'] ?? '') ===
                'doctor'
            ): ?>
            <div class="form-message error">Your previous doctor session no longer matches the
                current database. Please sign in again, or create a new doctor account if this is a
                fresh database.</div><?php endif; ?>
            <?php if (
                isset($_GET['registered'])
            ): ?>
            <div class="form-message success">Account created successfully! Please sign in to
                continue.</div><?php endif; ?>
            <form
                method="POST"
                action="index.php?page=login"
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
                    name="role"
                    id="selected-role"
                    value="<?= esc(
                        $selected_role ?? 'patient'
                    ) ?>"
                >
                <div class="form-group">
                    <label for="login">Email or phone</label>
                    <input
                        type="text"
                        id="login"
                        name="login"
                        placeholder="you@meditrack.hospital"
                        value="<?= esc(
                            $login ?? ''
                        ) ?>"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                    >
                </div>
                <button
                    type="submit"
                    class="auth-button"
                >Sign in</button>
            </form>
            <p class="switch-page">Don't have an account? <a href="index.php?page=register">Create
                    Account</a></p>
        </div>
    </div>
    <script>
        document.querySelectorAll('.auth-role-options .role-option').forEach(b => b
            .addEventListener('click', () => {
                document.querySelectorAll('.auth-role-options .role-option').forEach(x => x
                    .classList.remove('active'));
                b.classList.add('active');
                document.getElementById('selected-role').value = b.dataset.role;
                document.getElementById('login').placeholder = b.dataset.role ===
                    'patient' ? 'Email or phone' : 'Email';
            }));
    </script>
</body>
</html>
