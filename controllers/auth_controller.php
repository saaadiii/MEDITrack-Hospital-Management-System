<?php
class AuthController
{
    private $users;
    function __construct($conn)
    {
        $this->users = new User($conn);
    }
    function login()
    {
        $role = $_GET['role'] ?? 'patient';
        if (!in_array($role, auth_roles(), true)) {
            $role = 'patient';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $role = $_POST['role'] ?? $role;
            $login = trim($_POST['login'] ?? '');
            $password = $_POST['password'] ?? '';
            if (
                !in_array($role, ['patient', 'doctor', 'receptionist', 'admin'], true) ||
                $login === '' ||
                $password === ''
            ) {
                $error = 'Please select a valid role and enter your login details.';
            } else {
                $u = $this->users->findByLogin($login, $role);
                if ($u && password_verify($password, $u['password'])) {
                    // Regenerate the shared PHP session ID after login, but keep
                    // the other role logins that may already be active in other tabs.
                    session_regenerate_id(true);

                    $roleSession = [
                        'user_id' => (int) $u['user_id'],
                        'user_email' => $u['email']
                    ];

                    if ($role === 'patient') {
                        $roleSession['patient_id'] = (int) $u['patient_id'];
                        $roleSession['patient_name'] = $u['name'];
                    } elseif ($role === 'doctor') {
                        $roleSession['doctor_id'] = (int) $u['doctor_id'];
                        $roleSession['doctor_name'] = $u['name'];
                    }

                    set_role_session($role, $roleSession);

                    if ($role === 'patient') {
                        redirect('index.php?page=patient');
                    }
                    if ($role === 'doctor') {
                        redirect('index.php?page=doctor');
                    }
                    if ($role === 'receptionist') {
                        redirect('index.php?page=receptionist');
                    }
                    redirect('index.php?page=admin');
                }
                $error = 'Invalid email/phone or password for the selected role.';
            }
        }
        render_view('auth/login', [
            'error' => $error ?? '',
            'login' => $login ?? '',
            'selected_role' => $role ?? 'patient'
        ]);
    }
    function register()
    {
        $message = '';
        $message_type = '';
        $name = '';
        $email = '';
        $role = 'patient';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $name = trim($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? 'patient';
            if (!in_array($role, ['patient', 'doctor', 'receptionist'], true)) {
                $message = 'Admin accounts cannot be created from registration.';
            } elseif ($name === '' || $email === '' || $password === '' || $confirm === '') {
                $message = 'Please fill in all required fields.';
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $message = 'Name must be between 2 and 100 characters.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Please enter a valid email address.';
            } elseif ($password !== $confirm) {
                $message = 'Passwords do not match.';
            } elseif (strlen($password) < 6) {
                $message = 'Password must be at least 6 characters.';
            } elseif ($this->users->emailExists($email)) {
                $message = 'An account with this email already exists.';
            } elseif ($this->users->createAccount($name, $email, $password, $role)) {
                redirect('index.php?page=login&registered=1&role=' . urlencode($role));
            } else {
                $message =
                    'Registration failed: ' .
                    ($this->users->lastError ?: 'Please check the database schema and try again.');
            }
            $message_type = $message ? 'error' : 'success';
        }
        render_view('auth/register', compact('message', 'message_type', 'name', 'email', 'role'));
    }
    function logout($role = null)
    {
        if ($role !== null && in_array($role, auth_roles(), true)) {
            clear_role_session($role);
            redirect('index.php?page=login&role=' . urlencode($role));
        }

        // Fallback for an old/bookmarked logout URL without a role.
        // This signs out every role, matching the original project behaviour.
        unset($_SESSION['auth'], $_SESSION['flash']);
        redirect('index.php?page=login');
    }
}
