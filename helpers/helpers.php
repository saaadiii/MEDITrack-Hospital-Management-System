<?php
function esc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return (bool) ($token &&
        !empty($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token));
}
function require_csrf()
{
    if (!verify_csrf()) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page and try again.');
    }
}
function json_response($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}
function render_view($view, $data = [])
{
    extract($data);
    require __DIR__ . '/../views/' . $view . '.php';
}
function redirect($url)
{
    header('Location: ' . $url);
    exit();
}
function auth_roles()
{
    return ['patient', 'doctor', 'receptionist', 'admin'];
}

function get_role_session($role)
{
    if (!in_array($role, auth_roles(), true)) {
        return null;
    }

    $auth = $_SESSION['auth'][$role] ?? null;
    return is_array($auth) ? $auth : null;
}

function set_role_session($role, array $data)
{
    if (!in_array($role, auth_roles(), true)) {
        return false;
    }

    if (!isset($_SESSION['auth']) || !is_array($_SESSION['auth'])) {
        $_SESSION['auth'] = [];
    }

    $data['role'] = $role;
    $data['last_activity'] = time();
    $_SESSION['auth'][$role] = $data;
    return true;
}

function clear_role_session($role)
{
    if (!in_array($role, auth_roles(), true)) {
        return;
    }

    unset($_SESSION['auth'][$role]);
    unset($_SESSION['flash'][$role]);

    if (isset($_SESSION['auth']) && empty($_SESSION['auth'])) {
        unset($_SESSION['auth']);
    }
    if (isset($_SESSION['flash']) && empty($_SESSION['flash'])) {
        unset($_SESSION['flash']);
    }
}

function check_session_timeout($role)
{
    $auth = get_role_session($role);
    if (!$auth || empty($auth['user_id'])) {
        return false;
    }

    if (
        !empty($auth['last_activity']) &&
        time() - (int) $auth['last_activity'] > SESSION_TIMEOUT
    ) {
        clear_role_session($role);
        return false;
    }

    $_SESSION['auth'][$role]['last_activity'] = time();
    return true;
}

function require_role($roles)
{
    $roles = array_values(array_filter((array) $roles, fn($role) =>
        in_array($role, auth_roles(), true)
    ));

    foreach ($roles as $role) {
        $auth = get_role_session($role);
        if ($auth && !empty($auth['user_id'])) {
            if (!check_session_timeout($role)) {
                redirect('index.php?page=login&role=' . urlencode($role));
            }

            $GLOBALS['current_role'] = $role;
            return get_role_session($role);
        }
    }

    $role = $roles[0] ?? 'patient';
    redirect('index.php?page=login&role=' . urlencode($role));
}

function flash($type, $message, $role = null)
{
    $role = $role ?: ($GLOBALS['current_role'] ?? 'global');
    if (!isset($_SESSION['flash'][$role]) || !is_array($_SESSION['flash'][$role])) {
        $_SESSION['flash'][$role] = [];
    }
    $_SESSION['flash'][$role][$type] = $message;
}

function get_flash($type, $role = null)
{
    $role = $role ?: ($GLOBALS['current_role'] ?? 'global');
    $value = $_SESSION['flash'][$role][$type] ?? null;
    unset($_SESSION['flash'][$role][$type]);

    if (isset($_SESSION['flash'][$role]) && empty($_SESSION['flash'][$role])) {
        unset($_SESSION['flash'][$role]);
    }
    if (isset($_SESSION['flash']) && empty($_SESSION['flash'])) {
        unset($_SESSION['flash']);
    }

    return $value;
}
function active_page($page, $current)
{
    return $page === $current ? 'active' : '';
}
function greeting()
{
    $h = (int) date('H');
    return $h < 12 ? 'Good morning' : ($h < 18 ? 'Good afternoon' : 'Good evening');
}
function local_today()
{
    return date('Y-m-d');
}
function valid_ymd_date($value)
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }
    $dt = DateTime::createFromFormat('!Y-m-d', $value);
    return $dt && $dt->format('Y-m-d') === $value;
}
function date_today_or_future($value)
{
    return valid_ymd_date($value) && $value >= local_today();
}
function date_today_or_past($value)
{
    return valid_ymd_date($value) && $value <= local_today();
}
function patient_display_id($id)
{
    return 'P' . str_pad((string) (int) $id, 4, '0', STR_PAD_LEFT);
}
function doctor_display_id($id)
{
    return 'D' . str_pad((string) (int) $id, 4, '0', STR_PAD_LEFT);
}
function appointment_display_id($id)
{
    return str_pad((string) (int) $id, 3, '0', STR_PAD_LEFT);
}
function doctor_appointment_display_id($doctorId, $serial)
{
    $doctor = 'D' . str_pad((string) (int) $doctorId, 4, '0', STR_PAD_LEFT);
    $number = str_pad((string) (int) $serial, 3, '0', STR_PAD_LEFT);
    return $doctor . '-' . $number;
}

function parse_doctor_appointment_display_id($value)
{
    $value = strtoupper(trim((string) $value));
    if (!preg_match('/^D(\d{4})-(\d{3})$/', $value, $m)) {
        return null;
    }
    return ['doctor_id' => (int) $m[1], 'doctor_serial' => (int) $m[2]];
}

function medicine_frequency_display($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return 'Not specified';
    }
    if (preg_match('/^\d+(?:\.\d+)?$/', $value)) {
        return $value . ' times';
    }
    return $value;
}
function medicine_duration_display($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return 'Not specified';
    }
    if (preg_match('/^\d+(?:\.\d+)?$/', $value)) {
        return $value . ' days';
    }
    return $value;
}

function require_patient()
{
    $auth = require_role('patient');
    return (int) $auth['user_id'];
}
function require_patient_ajax()
{
    $auth = get_role_session('patient');
    if (!$auth || empty($auth['user_id'])) {
        json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
    }
    if (!check_session_timeout('patient')) {
        json_response(['success' => false, 'message' => 'Session expired. Please sign in again.'], 401);
    }
    $GLOBALS['current_role'] = 'patient';
    $auth = get_role_session('patient');
    return (int) $auth['user_id'];
}

function doctor_specializations()
{
    return [
        'General Medicine',
        'Internal Medicine',
        'Family Medicine',
        'Cardiology',
        'Dermatology',
        'Neurology',
        'Neurosurgery',
        'Pediatrics',
        'Pediatric Surgery',
        'Obstetrics & Gynecology',
        'General Surgery',
        'Orthopedic Surgery',
        'Ophthalmology',
        'ENT / Otolaryngology',
        'Psychiatry',
        'Urology',
        'Nephrology',
        'Gastroenterology',
        'Pulmonology / Respiratory Medicine',
        'Endocrinology',
        'Rheumatology',
        'Hematology',
        'Medical Oncology',
        'Surgical Oncology',
        'Radiation Oncology',
        'Infectious Diseases',
        'Emergency Medicine',
        'Anesthesiology',
        'Critical Care Medicine',
        'Radiology',
        'Pathology',
        'Physical Medicine & Rehabilitation',
        'Dentistry',
        'Oral & Maxillofacial Surgery',
        'Plastic & Reconstructive Surgery',
        'Cardiothoracic Surgery',
        'Vascular Surgery',
        'Geriatric Medicine',
        'Allergy & Immunology',
        'Clinical Genetics',
        'Nuclear Medicine',
        'Sports Medicine',
        'Pain Medicine'
    ];
}

function hospital_staff_roles()
{
    return ['Nurse', 'Lab Staff', 'Pharmacist', 'Technician', 'Accountant', 'Support Staff'];
}

function stock_categories()
{
    return ['Medicine', 'PPE', 'Medical Supplies', 'Lab Supplies', 'Hygiene'];
}

function hospital_departments()
{
    return [
        'General Medicine',
        'Internal Medicine',
        'Family Medicine / OPD',
        'Emergency',
        'Cardiology',
        'Dermatology',
        'Neurology',
        'Neurosurgery',
        'Pediatrics',
        'Pediatric Surgery',
        'Obstetrics & Gynecology',
        'General Surgery',
        'Orthopedics',
        'Ophthalmology',
        'ENT',
        'Psychiatry',
        'Urology',
        'Nephrology',
        'Gastroenterology',
        'Pulmonology / Respiratory Medicine',
        'Endocrinology',
        'Rheumatology',
        'Hematology',
        'Oncology',
        'Infectious Diseases',
        'Anesthesiology',
        'ICU / Critical Care',
        'Radiology & Imaging',
        'Pathology & Laboratory Medicine',
        'Physical Medicine & Rehabilitation',
        'Dentistry',
        'Oral & Maxillofacial Surgery',
        'Plastic & Reconstructive Surgery',
        'Cardiothoracic Surgery',
        'Vascular Surgery',
        'Geriatrics',
        'Allergy & Immunology',
        'Clinical Genetics',
        'Nuclear Medicine',
        'Pain Management',
        'Physiotherapy',
        'Pharmacy'
    ];
}

function hospital_department_codes()
{
    return [
        'General Medicine' => 'GEN',
        'Internal Medicine' => 'INT',
        'Family Medicine / OPD' => 'OPD',
        'Emergency' => 'EMR',
        'Cardiology' => 'CAR',
        'Dermatology' => 'DER',
        'Neurology' => 'NEU',
        'Neurosurgery' => 'NSG',
        'Pediatrics' => 'PED',
        'Pediatric Surgery' => 'PDS',
        'Obstetrics & Gynecology' => 'OBG',
        'General Surgery' => 'GSU',
        'Orthopedics' => 'ORT',
        'Ophthalmology' => 'OPH',
        'ENT' => 'ENT',
        'Psychiatry' => 'PSY',
        'Urology' => 'URO',
        'Nephrology' => 'NEP',
        'Gastroenterology' => 'GAS',
        'Pulmonology / Respiratory Medicine' => 'PUL',
        'Endocrinology' => 'END',
        'Rheumatology' => 'RHE',
        'Hematology' => 'HEM',
        'Oncology' => 'ONC',
        'Infectious Diseases' => 'INF',
        'Anesthesiology' => 'ANE',
        'ICU / Critical Care' => 'ICU',
        'Radiology & Imaging' => 'RAD',
        'Pathology & Laboratory Medicine' => 'LAB',
        'Physical Medicine & Rehabilitation' => 'PMR',
        'Dentistry' => 'DEN',
        'Oral & Maxillofacial Surgery' => 'OMS',
        'Plastic & Reconstructive Surgery' => 'PLS',
        'Cardiothoracic Surgery' => 'CTS',
        'Vascular Surgery' => 'VAS',
        'Geriatrics' => 'GER',
        'Allergy & Immunology' => 'ALI',
        'Clinical Genetics' => 'CGE',
        'Nuclear Medicine' => 'NUC',
        'Pain Management' => 'PAI',
        'Physiotherapy' => 'PHY',
        'Pharmacy' => 'PHA'
    ];
}
function hospital_department_code($department)
{
    $codes = hospital_department_codes();
    return $codes[$department] ?? null;
}
