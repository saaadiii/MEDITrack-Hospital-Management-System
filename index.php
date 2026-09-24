<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/helpers.php';
foreach (
    [
        'user_model',
        'patient_model',
        'symptom_model',
        'appointment_model',
        'followup_model',
        'prescription_model',
        'doctor_model',
        'patient_management_model',
        'doctor_slot_model',
        'receptionist_appointment_model',
        'emergency_model',
        'admin_model',
        'doctor_management_model',
        'staff_model',
        'equipment_model',
        'inventory_model',
        'billing_model',
        'admin_records_model'
    ]
    as $m
) {
    require_once __DIR__ . '/models/' . $m . '.php';
}
require_once __DIR__ . '/controllers/auth_controller.php';
require_once __DIR__ . '/controllers/patient_controller.php';
require_once __DIR__ . '/controllers/ajax_controller.php';
require_once __DIR__ . '/controllers/doctor_controller.php';
require_once __DIR__ . '/controllers/receptionist_controller.php';
require_once __DIR__ . '/controllers/admin_controller.php';
$auth = new AuthController($conn);
$patient = new PatientController($conn);
$ajax = new AjaxController($conn);
$doctor = new DoctorController($conn);
$reception = new ReceptionistController($conn);
$admin = new AdminController($conn);
$page = $_GET['page'] ?? 'login';
if ($page === 'login') {
    $auth->login();
    exit();
}
if ($page === 'register') {
    $auth->register();
    exit();
}
if ($page === 'logout') {
    $auth->logout($_GET['role'] ?? null);
    exit();
}
if ($page === 'ajax') {
    $action = $_GET['action'] ?? '';
    if ($action === 'search_symptoms') {
        $ajax->searchSymptoms();
    }
    if ($action === 'delete_symptom') {
        $ajax->deleteSymptom();
    }
    if ($action === 'search_appointments') {
        $ajax->searchAppointments();
    }
    if ($action === 'doctors_by_specialization') {
        $ajax->doctorsBySpecialization();
    }
    if ($action === 'doctor_booking_profile') {
        $ajax->doctorBookingProfile();
    }
    if ($action === 'cancel_appointment') {
        $ajax->cancelAppointment();
    }
    if ($action === 'patient_appointment_action') {
        $ajax->patientAppointmentAction();
    }
    if ($action === 'search_receptionist_patients') {
        $ajax->searchReceptionistPatients();
    }
    if ($action === 'search_receptionist_doctors') {
        $ajax->searchReceptionistDoctors();
    }
    if ($action === 'receptionist_doctor_availability') {
        $ajax->receptionistDoctorAvailability();
    }
    if ($action === 'receptionist_appointment_search') {
        $ajax->receptionistAppointmentSearch();
    }
    if ($action === 'receptionist_section_rows') {
        $ajax->receptionistSectionRows();
    }
    if ($action === 'receptionist_live_action') {
        $ajax->receptionistLiveAction();
    }
    if ($action === 'receptionist_booking_doctors') {
        $ajax->receptionistBookingDoctors();
    }
    if ($action === 'receptionist_booking_profile') {
        $ajax->receptionistBookingProfile();
    }
    if ($action === 'receptionist_book_appointment') {
        $ajax->receptionistBookAppointment();
    }
    if ($action === 'search_doctor_patients') {
        $ajax->searchDoctorPatients();
    }
    if ($action === 'doctor_prescription_history') {
        $ajax->doctorPrescriptionHistory();
    }
    if ($action === 'doctor_prescription_cancel') {
        $ajax->doctorPrescriptionCancel();
    }
    if ($action === 'doctor_followup_update') {
        $ajax->doctorFollowupUpdate();
    }
    if ($action === 'doctor_followup_cancel') {
        $ajax->doctorFollowupCancel();
    }
    if ($action === 'doctor_appointments') {
        $ajax->doctorAppointments();
    }
    if ($action === 'doctor_complete_appointment') {
        $ajax->doctorCompleteAppointment();
    }
    if ($action === 'doctor_availability_rows') {
        $ajax->doctorAvailabilityRows();
    }
    if ($action === 'doctor_availability_delete') {
        $ajax->doctorAvailabilityDelete();
    }
    if ($action === 'search_patient_prescriptions') {
        $ajax->searchPatientPrescriptions();
    }
    if ($action === 'admin_doctor_search') {
        $ajax->adminDoctorSearch();
    }
    if ($action === 'admin_staff_search') {
        $ajax->adminStaffSearch();
    }
    if ($action === 'admin_equipment_search') {
        $ajax->adminEquipmentSearch();
    }
    if ($action === 'admin_inventory_search') {
        $ajax->adminInventorySearch();
    }
    if ($action === 'admin_add_equipment_type') {
        $ajax->adminAddEquipmentType();
    }
    if ($action === 'admin_add_stock_item_type') {
        $ajax->adminAddStockItemType();
    }
    if ($action === 'admin_inventory_history') {
        $ajax->adminInventoryHistory();
    }
    if ($action === 'admin_billing_search') {
        $ajax->adminBillingSearch();
    }
    if ($action === 'admin_records_search') {
        $ajax->adminRecordsSearch();
    }
    json_response(['success' => false, 'message' => 'Unknown AJAX action.'], 404);
}
switch ($page) {
    case 'patient':
        $patient->dashboard();
        break;
    case 'profile':
        $patient->profile();
        break;
    case 'symptoms':
        $patient->symptoms();
        break;
    case 'followups':
        $patient->followups();
        break;
    case 'appointments':
        $patient->appointments();
        break;
    case 'prescriptions':
        $patient->prescriptions();
        break;
    case 'prescription_view':
        $patient->prescriptionView();
        break;
    case 'doctor':
        $doctor->dashboard();
        break;
    case 'doctor_profile':
        $doctor->profile();
        break;
    case 'doctor_patients':
        $doctor->patients();
        break;
    case 'doctor_slots':
        $doctor->slots();
        break;
    case 'doctor_prescriptions':
        $doctor->prescriptions();
        break;
    case 'doctor_prescription_delete':
        $doctor->deletePrescription();
        break;
    case 'doctor_followups':
        $doctor->followups();
        break;
    case 'doctor_followup_delete':
        $doctor->deleteFollowup();
        break;
    case 'doctor_appointment_status':
        $doctor->appointmentStatus();
        break;
    case 'doctor_appointment_delete':
        $doctor->deleteAppointment();
        break;
    case 'receptionist':
        $reception->dashboard();
        break;
    case 'receptionist_patients':
        $reception->patients();
        break;
    case 'receptionist_booking':
        $reception->booking();
        break;
    case 'receptionist_appointments':
        $reception->appointments();
        break;
    case 'receptionist_slots':
        $reception->slots();
        break;
    case 'receptionist_emergency':
        $reception->emergency();
        break;
    case 'admin':
        $admin->dashboard();
        break;
    case 'admin_billing':
        $admin->billing();
        break;
    case 'admin_staff':
        $admin->staff();
        break;
    case 'admin_equipment':
        $admin->equipment();
        break;
    case 'admin_inventory':
        $admin->inventory();
        break;
    case 'admin_doctors':
        $admin->doctors();
        break;
    case 'admin_records':
        $admin->records();
        break;
    default:
        http_response_code(404);
        echo 'Page not found.';
}
