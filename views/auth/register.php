<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width,initial-scale=1.0"
    >
    <title>MEDITrack - Register</title>
    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20260923-final"
    >
</head>
<body class="auth-page">
    <div class="auth-card auth-split-card register-card">
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
            <h1>Create your MEDITrack account</h1>
            <p class="auth-description">Choose a role and enter your account details</p>
            <div class="role-section">
                <label>Register as</label>
                <div class="role-options auth-role-options">
                    <?php foreach (
                        ['patient' => 'Patient', 'doctor' => 'Doctor', 'receptionist' => 'Receptionist']
                        as $r => $label
                    ): ?>
                    <button
                        type="button"
                        class="role-option <?= $r === ($role ?? 'patient')
                            ? 'active'
                            : '' ?>"
                        data-role="<?= $r ?>"
                    ><?= $label ?></button><?php endforeach; ?>
                </div>
            </div>
            <div
                class="form-message error"
                id="role-note"
                style="display:none"
            >Admin accounts are created by the system and cannot be registered here.</div>
            <?php if (!empty($message)): ?>
            <div class="form-message <?= esc($message_type) ?>"><?= esc(
                $message
            ) ?></div><?php endif; ?>
            <form
                method="POST"
                action="index.php?page=register"
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
                    id="register-role"
                    value="<?= esc($role ?? 'patient') ?>"
                >
                <div class="form-group">
                    <label>Name</label>
                    <input
                        type="text"
                        name="name"
                        value="<?= esc(
                            $name
                        ) ?>"
                        required
                    >
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= esc(
                            $email
                        ) ?>"
                        required
                    >
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input
                        type="password"
                        name="password"
                        required
                    >
                </div>
                <div class="form-group">
                    <label>Confirm password</label>
                    <input
                        type="password"
                        name="confirm_password"
                        required
                    >
                </div>
                <button
                    type="submit"
                    class="auth-button"
                >Create Account</button>
            </form>
            <p class="switch-page">Already have an account? <a href="index.php?page=login">Login</a>
            </p>
        </div>
    </div>
    <script>
        document.querySelectorAll('.auth-role-options .role-option').forEach(b => b
            .addEventListener('click', () => {
                document.querySelectorAll('.auth-role-options .role-option').forEach(x => x
                    .classList.remove('active'));
                b.classList.add('active');
                document.getElementById('register-role').value = b.dataset.role;
            }));
    </script>
</body>
</html>
