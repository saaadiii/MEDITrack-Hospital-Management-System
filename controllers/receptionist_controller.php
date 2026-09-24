<?php
class ReceptionistController
{
    private $c;
    private $patientModel;
    private $slotModel;
    private $appointmentModel;
    private $emergencyModel;

    function __construct($c)
    {
        $this->c = $c;
        $this->patientModel = new PatientManagementModel($c);
        $this->slotModel = new DoctorSlotModel($c);
        $this->appointmentModel = new ReceptionistAppointmentModel($c);
        $this->emergencyModel = new EmergencyModel($c);
    }

    private function auth()
    {
        require_role('receptionist');
    }

    function dashboard()
    {
        $this->renderPage('dashboard', 'dashboard');
    }

    function patients()
    {
        $this->renderPage('patients', 'patients');
    }

    function slots()
    {
        $this->renderPage('slots', 'slots');
    }

    function booking()
    {
        $this->renderPage('booking', 'booking');
    }

    function appointments()
    {
        $this->renderPage('appointments', 'appointments');
    }

    function emergency()
    {
        $this->renderPage('emergency', 'emergency');
    }

    private function renderPage($view, $context)
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processAction('receptionist_' . $view);
        }

        $patients = $this->patientModel->patients(trim($_GET['search'] ?? ''));
        $doctors = $this->slotModel->doctors();
        $availabilities = $this->slotModel->availabilities();

        $slotDate = trim($_GET['date'] ?? '');
        $slotDoctorId = (int) ($_GET['doctor_id'] ?? 0);
        $slotDepartment = trim($_GET['department'] ?? '');
        $slots = $this->slotModel->slots($slotDate, $slotDoctorId, $slotDepartment);

        $slotDepartments = [];
        foreach ($doctors as $doc) {
            $department = trim((string) ($doc['department'] ?? ''));
            if ($department !== '' && !in_array($department, $slotDepartments, true)) {
                $slotDepartments[] = $department;
            }
        }
        sort($slotDepartments, SORT_NATURAL | SORT_FLAG_CASE);

        $allAppointments = $this->appointmentModel->appointments();
        $appointmentSearch = trim($_GET['appointment_search'] ?? '');
        $appointments = $appointmentSearch !== ''
            ? $this->appointmentModel->appointments($appointmentSearch)
            : $allAppointments;

        $allEmergencies = $this->emergencyModel->emergencies();
        $emergencySearch = trim($_GET['q'] ?? '');
        $emergencies = $emergencySearch !== ''
            ? $this->emergencyModel->emergencies($emergencySearch)
            : $allEmergencies;

        $today = local_today();
        $todayAppointments = array_values(
            array_filter($allAppointments, fn($a) => ($a['appointment_date'] ?? '') === $today)
        );
        $stats = [
            'patients' => count(array_filter($patients, fn($p) => $p['status'] === 'Active')),
            'slots' => count(array_filter($slots, fn($s) => $s['status'] === 'Available')),
            'appointments' => count($todayAppointments),
            'emergencies' => count(array_filter($allEmergencies, fn($e) => $e['status'] === 'Waiting'))
        ];

        $editPatient = null;
        $editSlot = null;
        $editEmergency = null;
        if ($context === 'patients' && isset($_GET['edit'])) {
            $editPatient = $this->patientModel->patient((int) $_GET['edit']);
        }
        if ($context === 'slots' && isset($_GET['edit'])) {
            foreach ($slots as $slot) {
                if ((int) $slot['id'] === (int) $_GET['edit']) {
                    $editSlot = $slot;
                    break;
                }
            }
        }
        if ($context === 'emergency' && isset($_GET['edit'])) {
            foreach ($allEmergencies as $emergency) {
                if ((int) $emergency['id'] === (int) $_GET['edit']) {
                    $editEmergency = $emergency;
                    break;
                }
            }
        }

        $specializations = [];
        if ($context === 'booking') {
            $appointmentModel = new Appointment($this->c);
            $specializations = $appointmentModel->specializations();
        }

        render_view(
            'receptionist/' . $view,
            compact(
                'patients',
                'doctors',
                'availabilities',
                'slots',
                'slotDate',
                'slotDoctorId',
                'slotDepartment',
                'slotDepartments',
                'specializations',
                'appointments',
                'appointmentSearch',
                'emergencies',
                'stats',
                'editPatient',
                'editSlot',
                'editEmergency'
            )
        );
    }

    private function processAction($returnPage)
    {
        require_csrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'patient_save') {
            $data = $_POST;
            $data['id'] = (int) ($data['id'] ?? 0);
            $this->patientModel->savePatient($data);
            flash('success', 'Patient saved successfully.');
        } elseif ($action === 'patient_delete') {
            $this->patientModel->deletePatient((int) $_POST['id']);
            flash('success', 'Patient marked inactive.');
        } elseif ($action === 'slot_save') {
            $ok = $this->slotModel->saveSlot($_POST);
            flash(
                $ok ? 'success' : 'error',
                $ok
                    ? 'Doctor slot created from the selected availability.'
                    : 'Could not create slot. It must be inside the doctor availability and must not duplicate an existing slot.'
            );
        } elseif ($action === 'slot_delete') {
            $this->slotModel->deleteSlot((int) $_POST['id']);
            flash('success', 'Slot deleted if it was not booked.');
        } elseif ($action === 'appointment_status') {
            $ok = $this->appointmentModel->cancelAppointment((int) ($_POST['id'] ?? 0));
            flash(
                $ok ? 'success' : 'error',
                $ok
                    ? 'Appointment cancelled successfully.'
                    : 'Only a booked appointment can be cancelled by the receptionist.'
            );
        } elseif ($action === 'emergency_save') {
            $this->emergencyModel->saveEmergency($_POST);
            flash('success', 'Emergency registration saved.');
        } elseif ($action === 'emergency_delete') {
            $this->emergencyModel->deleteEmergency((int) $_POST['id']);
            flash('success', 'Emergency record cancelled.');
        }

        if ($returnPage === 'receptionist_dashboard') {
            $returnPage = 'receptionist';
        }
        redirect('index.php?page=' . urlencode($returnPage));
    }
}
