<?php

require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../models/patient_model.php';
require_once __DIR__ . '/../models/symptom_model.php';
require_once __DIR__ . '/../models/appointment_model.php';
require_once __DIR__ . '/../models/followup_model.php';
require_once __DIR__ . '/../models/prescription_model.php';

class PatientController
{
    private $patientModel;
    private $symptomModel;
    private $appointmentModel;
    private $followupModel;
    private $prescriptionModel;

    public function __construct($conn)
    {
        $this->patientModel = new Patient($conn);
        $this->symptomModel = new Symptom($conn);
        $this->appointmentModel = new Appointment($conn);
        $this->followupModel = new Followup($conn);
        $this->prescriptionModel = new PrescriptionModel($conn);
    }

    private function getPatient()
    {
        $userId = require_patient();
        $patient = $this->patientModel->findByUserId($userId);

        if (!$patient) {
            // This usually happens after the database has been recreated while
            // the browser still contains a login session from the old database.
            // Do not show a fatal error page; clear the stale session and ask the
            // user to sign in/register again against the current database.
            clear_role_session('patient');
            redirect('index.php?page=login&role=patient&profile_reset=patient');
        }

        return $patient;
    }

    public function dashboard()
    {
        $patient = $this->getPatient();
        $patient_id = (int) $patient['id'];

        $symptom_count = $this->symptomModel->countForPatient($patient_id);
        $prescription_count = $this->prescriptionModel->activeCount($patient_id);

        $next_followup = $this->followupModel->nextActive($patient_id);
        $followup_display = $next_followup
            ? date('d M', strtotime($next_followup['followup_date']))
            : 'None';

        $upcoming_appointment = $this->appointmentModel->upcoming($patient_id);
        $appointment_display = $upcoming_appointment
            ? date('d M', strtotime($upcoming_appointment['appointment_date'])) .
                ', ' .
                date('h:i A', strtotime($upcoming_appointment['appointment_time']))
            : 'None';

        $symptoms_result = $this->symptomModel->recentForPatient($patient_id, 5);

        $current_hour = (int) date('H');
        $greeting =
            $current_hour < 12
                ? 'Good morning'
                : ($current_hour < 18
                    ? 'Good afternoon'
                    : 'Good evening');

        render_view(
            'patient/dashboard',
            compact(
                'patient',
                'patient_id',
                'symptom_count',
                'prescription_count',
                'next_followup',
                'followup_display',
                'upcoming_appointment',
                'appointment_display',
                'symptoms_result',
                'greeting'
            )
        );
    }

    public function profile()
    {
        $patient = $this->getPatient();
        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $age = (int) ($_POST['age'] ?? 0);
            $gender = trim($_POST['gender'] ?? '');
            $emergency_contact = trim($_POST['emergency_contact'] ?? '');
            $blood_group = trim($_POST['blood_group'] ?? '');

            if (
                !$name ||
                !$phone ||
                !$address ||
                $age < 1 ||
                !$gender ||
                !$emergency_contact ||
                !$blood_group
            ) {
                $message =
                    'Name, phone, address, age, gender, emergency contact and blood group are required.';
                $message_type = 'error';
            } elseif ($age > 120) {
                $message = 'Please enter a valid age.';
                $message_type = 'error';
            } elseif (
                !in_array($blood_group, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)
            ) {
                $message = 'Please select a valid blood group.';
                $message_type = 'error';
            } elseif (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
                $message = 'Please select a valid gender.';
                $message_type = 'error';
            } elseif (
                $this->patientModel->update(
                    (int) $patient['id'],
                    $name,
                    $phone,
                    $address,
                    $age,
                    $gender,
                    $emergency_contact,
                    $blood_group
                )
            ) {
                $message = 'Profile updated successfully.';
                $message_type = 'success';

                $_SESSION['auth']['patient']['patient_name'] = $name;
                $patient['name'] = $name;
                $patient['phone'] = $phone;
                $patient['address'] = $address;
                $patient['age'] = $age;
                $patient['gender'] = $gender;
                $patient['emergency_contact'] = $emergency_contact;
                $patient['blood_group'] = $blood_group;
            } else {
                $message = 'Could not update your profile.';
                $message_type = 'error';
            }
        }

        render_view('patient/profile', compact('patient', 'message', 'message_type'));
    }

    public function symptoms()
    {
        $patient = $this->getPatient();
        $patient_id = (int) $patient['id'];

        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $action = $_POST['action'] ?? '';

            if ($action === 'add' || $action === 'edit') {
                $symptom = trim($_POST['symptom'] ?? '');
                $severity = (int) ($_POST['severity'] ?? 0);
                $duration = trim($_POST['duration'] ?? '');
                $frequency = trim($_POST['frequency'] ?? '');
                $symptom_date = $_POST['symptom_date'] ?? '';
                $notes = trim($_POST['notes'] ?? '');

                if (
                    !$symptom ||
                    !$duration ||
                    !$frequency ||
                    !$symptom_date ||
                    $severity < 1 ||
                    $severity > 10 ||
                    strlen($symptom) > 150 ||
                    strlen($duration) > 100 ||
                    strlen($frequency) > 100 ||
                    strlen($notes) > 500 ||
                    !valid_ymd_date($symptom_date)
                ) {
                    $message = 'Please complete all required symptom fields.';
                    $message_type = 'error';
                } elseif (!date_today_or_past($symptom_date)) {
                    $message =
                        'Symptom date cannot be in the future. Please select today or an earlier date.';
                    $message_type = 'error';
                } elseif ($action === 'add') {
                    if (
                        $this->symptomModel->create(
                            $patient_id,
                            $symptom,
                            $severity,
                            $duration,
                            $frequency,
                            $symptom_date,
                            $notes
                        )
                    ) {
                        $message = 'Symptom added successfully.';
                        $message_type = 'success';
                    } else {
                        $message = 'Could not save the symptom.';
                        $message_type = 'error';
                    }
                } else {
                    $id = (int) ($_POST['id'] ?? 0);

                    if (
                        $this->symptomModel->update(
                            $id,
                            $patient_id,
                            $symptom,
                            $severity,
                            $duration,
                            $frequency,
                            $symptom_date,
                            $notes
                        )
                    ) {
                        $message = 'Symptom updated successfully.';
                        $message_type = 'success';
                    } else {
                        $message = 'Could not update the symptom.';
                        $message_type = 'error';
                    }
                }
            } elseif ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);

                if ($this->symptomModel->delete($id, $patient_id)) {
                    $message = 'Symptom deleted.';
                    $message_type = 'success';
                } else {
                    $message = 'Could not delete the symptom.';
                    $message_type = 'error';
                }
            }
        }

        $edit_symptom = null;

        if (isset($_GET['edit'])) {
            $edit_id = (int) $_GET['edit'];
            $edit_symptom = $this->symptomModel->find($edit_id, $patient_id);
        }

        $symptoms = $this->symptomModel->allForPatient($patient_id);

        render_view(
            'patient/symptoms',
            compact('message', 'message_type', 'edit_symptom', 'symptoms')
        );
    }

    public function appointments()
    {
        $patient = $this->getPatient();
        $patient_id = (int) $patient['id'];

        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $action = $_POST['action'] ?? '';

            if ($action === 'book') {
                $slot_id = (int) ($_POST['slot_id'] ?? 0);

                if ($slot_id <= 0) {
                    $message = 'Please select an appointment slot.';
                    $message_type = 'error';
                } else {
                    $result = $this->appointmentModel->book($patient_id, $slot_id);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'error';
                }
            } elseif ($action === 'cancel') {
                $appointment_id = (int) ($_POST['appointment_id'] ?? 0);
                $result = $this->appointmentModel->cancel($patient_id, $appointment_id);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            } elseif ($action === 'delete') {
                $appointment_id = (int) ($_POST['appointment_id'] ?? 0);
                $result = $this->appointmentModel->delete($patient_id, $appointment_id);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
        }

        $specializations = $this->appointmentModel->specializations();
        $appointments = $this->appointmentModel->allForPatient($patient_id);

        render_view(
            'patient/appointments',
            compact('message', 'message_type', 'specializations', 'appointments')
        );
    }

    public function followups()
    {
        $patient = $this->getPatient();
        $patient_id = (int) $patient['id'];

        $followups = $this->followupModel->allForPatient($patient_id);

        render_view('patient/followups', compact('followups'));
    }
    public function prescriptions()
    {
        $patient = $this->getPatient();
        $prescriptions = $this->prescriptionModel->allForPatient((int) $patient['id']);
        render_view('patient/prescriptions', compact('patient', 'prescriptions'));
    }

    public function prescriptionView()
    {
        $patient = $this->getPatient();
        $prescriptionId = (int) ($_GET['id'] ?? 0);
        if ($prescriptionId <= 0) {
            http_response_code(404);
            echo 'Prescription not found.';
            return;
        }
        $prescription = $this->prescriptionModel->oneForPatient(
            (int) $patient['id'],
            $prescriptionId
        );
        if (!$prescription) {
            http_response_code(404);
            echo 'Prescription not found.';
            return;
        }
        render_view('patient/prescription_view', compact('patient', 'prescription'));
    }
}
