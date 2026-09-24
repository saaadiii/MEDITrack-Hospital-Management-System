<?php

require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../models/patient_model.php';
require_once __DIR__ . '/../models/symptom_model.php';
require_once __DIR__ . '/../models/appointment_model.php';
require_once __DIR__ . '/../models/doctor_model.php';
require_once __DIR__ . '/../models/prescription_model.php';
require_once __DIR__ . '/../models/patient_management_model.php';
require_once __DIR__ . '/../models/doctor_slot_model.php';
require_once __DIR__ . '/../models/receptionist_appointment_model.php';
require_once __DIR__ . '/../models/emergency_model.php';
require_once __DIR__ . '/../models/admin_model.php';
require_once __DIR__ . '/../models/doctor_management_model.php';
require_once __DIR__ . '/../models/staff_model.php';
require_once __DIR__ . '/../models/equipment_model.php';
require_once __DIR__ . '/../models/inventory_model.php';
require_once __DIR__ . '/../models/billing_model.php';
require_once __DIR__ . '/../models/admin_records_model.php';

class AjaxController
{
    private $conn;
    private $patientModel;
    private $symptomModel;
    private $appointmentModel;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->patientModel = new Patient($conn);
        $this->symptomModel = new Symptom($conn);
        $this->appointmentModel = new Appointment($conn);
    }

    private function patientId()
    {
        $userId = require_patient_ajax();
        $patient = $this->patientModel->findByUserId($userId);
        if (!$patient) {
            json_response(['success' => false, 'message' => 'Patient profile not found.'], 404);
        }
        return (int) $patient['id'];
    }

    public function searchSymptoms()
    {
        $patientId = $this->patientId();
        $query = trim($_GET['q'] ?? '');
        $rows = $this->symptomModel->searchForPatient($patientId, $query);

        json_response([
            'success' => true,
            'query' => $query,
            'rows' => $rows
        ]);
    }

    public function searchAppointments()
    {
        $patientId = $this->patientId();
        $query = trim($_GET['q'] ?? '');
        $rows = $this->appointmentModel->searchForPatient($patientId, $query);

        json_response([
            'success' => true,
            'query' => $query,
            'rows' => $rows
        ]);
    }
    public function adminDoctorSearch()
    {
        require_role('admin');
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) > 100) {
            json_response(['success' => false, 'message' => 'Search text is too long.'], 422);
        }
        $model = new DoctorManagementModel($this->conn);
        $rows = $model->searchDoctors($query);
        json_response(['success' => true, 'data' => $rows]);
    }
    public function searchReceptionistPatients()
    {
        require_role('receptionist');
        $query = trim($_GET['q'] ?? '');
        $activeOnly = ($_GET['active_only'] ?? '') === '1';
        if (strlen($query) > 100) {
            json_response(['success' => false, 'message' => 'Search text is too long.'], 422);
        }

        $model = new PatientManagementModel($this->conn);
        $rows = $model->searchPatients($query, $activeOnly, 50);
        json_response(['success' => true, 'data' => $rows]);
    }
    public function searchReceptionistDoctors()
    {
        require_role('receptionist');
        $query = trim($_GET['q'] ?? '');
        if ($query === '') {
            json_response(['success' => true, 'data' => []]);
        }
        if (strlen($query) > 100) {
            json_response(['success' => false, 'message' => 'Search text is too long.'], 422);
        }

        $model = new DoctorSlotModel($this->conn);
        json_response([
            'success' => true,
            'data' => $model->searchDoctorsWithAvailability($query)
        ]);
    }
    public function receptionistDoctorAvailability()
    {
        require_role('receptionist');
        $doctorId = (int) ($_GET['doctor_id'] ?? 0);
        if ($doctorId <= 0) {
            json_response(['success' => false, 'message' => 'Invalid doctor.'], 422);
        }

        $model = new DoctorSlotModel($this->conn);
        json_response([
            'success' => true,
            'data' => $model->futureAvailabilityForDoctor($doctorId)
        ]);
    }

    public function searchDoctorPatients()
    {
        $auth = require_role('doctor');
        $query = trim($_GET['q'] ?? '');
        $doctorModel = new DoctorModel($this->conn);
        $doctor = $doctorModel->byUser((int) $auth['user_id']);
        if (!$doctor) {
            json_response(['success' => false, 'message' => 'Doctor profile not found.'], 404);
        }

        // Search across the patient fields supported by the doctor patient lookup.
        $bookedOnly = ($_GET['booked_only'] ?? '') === '1';
        $rows = $bookedOnly
            ? $doctorModel->bookablePatients((int) $doctor['id'], $query)
            : $doctorModel->allPatients((int) $doctor['id'], $query);
        if ($bookedOnly) {
            foreach ($rows as &$row) {
                $serial = (int) ($row['next_appointment_serial'] ?? 0);
                $row['appointment_no'] =
                    $serial > 0 ? doctor_appointment_display_id((int) $doctor['id'], $serial) : '—';
            }
            unset($row);
        }
        json_response(['success' => true, 'data' => $rows]);
    }

    public function doctorsBySpecialization()
    {
        $this->patientId();
        $specialization = trim($_GET['specialization'] ?? '');
        if ($specialization === '') {
            json_response(['success' => true, 'data' => []]);
        }

        $date = trim($_GET['date'] ?? '');
        if ($date !== '' && !date_today_or_future($date)) {
            json_response(
                ['success' => false, 'message' => 'Please select today or a future date.'],
                422
            );
        }
        $rows = $this->appointmentModel->doctorsBySpecialization($specialization, $date);
        json_response(['success' => true, 'data' => $rows]);
    }

    public function doctorBookingProfile()
    {
        $this->patientId();
        $doctorId = (int) ($_GET['doctor_id'] ?? 0);
        if ($doctorId <= 0) {
            json_response(['success' => false, 'message' => 'Invalid doctor.'], 422);
        }

        $date = trim($_GET['date'] ?? '');
        if ($date !== '' && !date_today_or_future($date)) {
            json_response(
                ['success' => false, 'message' => 'Please select today or a future date.'],
                422
            );
        }
        $doctor = $this->appointmentModel->doctorBookingProfile($doctorId, $date);
        if (!$doctor) {
            json_response(['success' => false, 'message' => 'Doctor profile not found.'], 404);
        }

        json_response(['success' => true, 'doctor' => $doctor]);
    }

    public function searchPatientPrescriptions()
    {
        $patientId = $this->patientId();
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) > 100) {
            json_response(['success' => false, 'message' => 'Search text is too long.'], 422);
        }

        $model = new PrescriptionModel($this->conn);
        $prescriptions = $model->searchForPatientByDoctorName($patientId, $query);
        $search_query = $query;

        ob_start();
        require __DIR__ . '/../views/patient/_prescription_cards.php';
        $html = ob_get_clean();

        json_response([
            'success' => true,
            'query' => $query,
            'count' => count($prescriptions),
            'html' => $html
        ]);
    }

    public function cancelAppointment()
    {
        $patientId = $this->patientId();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }

        if (!verify_csrf()) {
            json_response(
                [
                    'success' => false,
                    'message' => 'Invalid security token. Refresh the page and try again.'
                ],
                403
            );
        }

        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        if ($appointmentId <= 0) {
            json_response(['success' => false, 'message' => 'Invalid appointment.'], 422);
        }

        $result = $this->appointmentModel->cancel($patientId, $appointmentId);
        json_response($result, $result['success'] ? 200 : 422);
    }

    public function deleteSymptom()
    {
        $patientId = $this->patientId();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid symptom.'], 422);
        }
        $ok = $this->symptomModel->delete($id, $patientId);
        $q = trim($_POST['q'] ?? '');
        $rows = $this->symptomModel->searchForPatient($patientId, $q);
        json_response(
            [
                'success' => $ok,
                'message' => $ok ? 'Symptom deleted.' : 'Could not delete the symptom.',
                'rows' => $rows
            ],
            $ok ? 200 : 422
        );
    }

    private function doctorContext()
    {
        $auth = require_role('doctor');
        $model = new DoctorModel($this->conn);
        $doctor = $model->byUser((int) $auth['user_id']);
        if (!$doctor) {
            json_response(['success' => false, 'message' => 'Doctor profile not found.'], 404);
        }
        return [$model, $doctor];
    }

    public function doctorPrescriptionHistory()
    {
        [$model, $doctor] = $this->doctorContext();
        $q = trim($_GET['q'] ?? '');
        $prescriptions = $model->prescriptions((int) $doctor['id'], $q);
        $d = $doctor;
        ob_start();
        require __DIR__ . '/../views/doctor/_prescription_history_rows.php';
        $html = ob_get_clean();
        json_response(['success' => true, 'html' => $html, 'count' => count($prescriptions)]);
    }

    public function doctorAppointments()
    {
        [$model, $doctor] = $this->doctorContext();
        $q = trim($_GET['q'] ?? '');
        $appointments = $model->appointments((int) $doctor['id'], $q);
        $d = $doctor;
        ob_start();
        require __DIR__ . '/../views/doctor/_appointment_rows.php';
        $html = ob_get_clean();
        json_response(['success' => true, 'html' => $html, 'count' => count($appointments)]);
    }

    public function doctorCompleteAppointment()
    {
        [$model, $doctor] = $this->doctorContext();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $id = (int) ($_POST['id'] ?? 0);
        $ok = $model->completeAppointment((int) $doctor['id'], $id);
        $q = trim($_POST['q'] ?? '');
        $appointments = $model->appointments((int) $doctor['id'], $q);
        $d = $doctor;
        ob_start();
        require __DIR__ . '/../views/doctor/_appointment_rows.php';
        $html = ob_get_clean();
        json_response(
            [
                'success' => $ok,
                'message' => $ok
                    ? 'Appointment marked as completed.'
                    : 'This appointment can only be completed at or after its scheduled date and time.',
                'html' => $html
            ],
            $ok ? 200 : 422
        );
    }

    public function doctorAvailabilityRows()
    {
        [$model, $doctor] = $this->doctorContext();
        $date = trim($_GET['date'] ?? '');
        $slots = $model->availability((int) $doctor['id'], $date);
        $d = $doctor;
        $today = date('Y-m-d');
        ob_start();
        require __DIR__ . '/../views/doctor/_availability_rows.php';
        $html = ob_get_clean();
        json_response(['success' => true, 'html' => $html]);
    }

    public function doctorAvailabilityDelete()
    {
        [$model, $doctor] = $this->doctorContext();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $id = (int) ($_POST['id'] ?? 0);
        $date = trim($_POST['date'] ?? '');
        $ok = $model->deleteAvailability((int) $doctor['id'], $id);
        $slots = $model->availability((int) $doctor['id'], $date);
        $d = $doctor;
        $today = date('Y-m-d');
        ob_start();
        require __DIR__ . '/../views/doctor/_availability_rows.php';
        $html = ob_get_clean();
        json_response(
            [
                'success' => $ok,
                'message' => $ok ? 'Availability deleted.' : 'Could not delete availability.',
                'html' => $html
            ],
            $ok ? 200 : 422
        );
    }
    private function receptionistRows(
        $section,
        $date = '',
        $search = '',
        $doctorId = 0,
        $department = ''
    ) {
        if ($section === 'patients') {
            $model = new PatientManagementModel($this->conn);
            $patients = $model->patients($search);
            ob_start();
            require __DIR__ . '/../views/receptionist/partials/_patient_rows.php';
            return ob_get_clean();
        }
        if ($section === 'slots') {
            $model = new DoctorSlotModel($this->conn);
            $slots = $model->slots($date, $doctorId, $department);
            ob_start();
            require __DIR__ . '/../views/receptionist/partials/_slot_rows.php';
            return ob_get_clean();
        }
        if ($section === 'appointments') {
            $model = new ReceptionistAppointmentModel($this->conn);
            $appointments = $model->appointments($search);
            ob_start();
            require __DIR__ . '/../views/receptionist/partials/_appointment_rows.php';
            return ob_get_clean();
        }
        if ($section === 'emergency') {
            $model = new EmergencyModel($this->conn);
            $emergencies = $model->emergencies($search);
            ob_start();
            require __DIR__ . '/../views/receptionist/partials/_emergency_rows.php';
            return ob_get_clean();
        }
        return '';
    }
    public function receptionistAppointmentSearch()
    {
        require_role('receptionist');
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) > 120) {
            json_response(['success' => false, 'message' => 'Search text is too long.'], 422);
        }
        $model = new ReceptionistAppointmentModel($this->conn);
        $appointments = $model->appointments($q);
        ob_start();
        require __DIR__ . '/../views/receptionist/partials/_appointment_rows.php';
        $html = ob_get_clean();
        json_response([
            'success' => true,
            'html' => $html,
            'count' => count($appointments),
            'query' => $q
        ]);
    }
    public function receptionistSectionRows()
    {
        require_role('receptionist');
        $section = $_GET['section'] ?? '';
        $date = trim($_GET['date'] ?? '');
        $search = trim($_GET['q'] ?? '');
        $doctorId = (int) ($_GET['doctor_id'] ?? 0);
        $department = trim($_GET['department'] ?? '');
        if (!in_array($section, ['patients', 'slots', 'appointments', 'emergency'], true)) {
            json_response(['success' => false, 'message' => 'Invalid section.'], 422);
        }
        json_response([
            'success' => true,
            'html' => $this->receptionistRows(
                $section,
                $date,
                $search,
                $doctorId,
                $department
            )
        ]);
    }
    public function receptionistLiveAction()
    {
        require_role('receptionist');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }

        $a = $_POST['action'] ?? '';
        $section = $_POST['section'] ?? '';
        $ok = false;
        $message = 'Action could not be completed.';

        if ($a === 'patient_save') {
            $model = new PatientManagementModel($this->conn);
            $ok = $model->savePatient($_POST);
            $section = 'patients';
            $message = $ok ? 'Patient saved successfully.' : 'Could not save patient.';
        } elseif ($a === 'patient_delete') {
            $model = new PatientManagementModel($this->conn);
            $ok = $model->deletePatient((int) ($_POST['id'] ?? 0));
            $section = 'patients';
            $message = $ok ? 'Patient marked inactive.' : 'Could not deactivate patient.';
        } elseif ($a === 'slot_save') {
            $model = new DoctorSlotModel($this->conn);
            $ok = $model->saveSlot($_POST);
            $section = 'slots';
            $message = $ok
                ? 'Doctor slot saved.'
                : 'Could not create slot. It must be inside the selected availability, must ' .
                    'not be in the past, and must not duplicate an existing slot.';
        } elseif ($a === 'slot_delete') {
            $model = new DoctorSlotModel($this->conn);
            $ok = $model->deleteSlot((int) ($_POST['id'] ?? 0));
            $section = 'slots';
            $message = $ok ? 'Slot deleted.' : 'Booked slots cannot be deleted.';
        } elseif ($a === 'appointment_status') {
            $model = new ReceptionistAppointmentModel($this->conn);
            $ok = $model->cancelAppointment((int) ($_POST['id'] ?? 0));
            $section = 'appointments';
            $message = $ok
                ? 'Appointment cancelled successfully.'
                : 'Only a booked appointment can be cancelled.';
        } elseif ($a === 'emergency_save') {
            $model = new EmergencyModel($this->conn);
            $ok = $model->saveEmergency($_POST);
            $section = 'emergency';
            $message = $ok
                ? 'Emergency registration saved.'
                : 'Could not save emergency registration.';
        } elseif ($a === 'emergency_delete') {
            $model = new EmergencyModel($this->conn);
            $ok = $model->deleteEmergency((int) ($_POST['id'] ?? 0));
            $section = 'emergency';
            $message = $ok ? 'Emergency record cancelled.' : 'Could not cancel emergency record.';
        } else {
            json_response(['success' => false, 'message' => 'Unknown action.'], 422);
        }

        $date = trim($_POST['filter_date'] ?? '');
        $search = trim($_POST['search_q'] ?? '');
        $doctorId = (int) ($_POST['filter_doctor_id'] ?? 0);
        $department = trim($_POST['filter_department'] ?? '');
        $html = $this->receptionistRows($section, $date, $search, $doctorId, $department);
        json_response(
            ['success' => $ok, 'message' => $message, 'section' => $section, 'html' => $html],
            $ok ? 200 : 422
        );
    }

    public function receptionistBookingDoctors()
    {
        require_role('receptionist');
        $specialization = trim($_GET['specialization'] ?? '');
        if ($specialization === '') {
            json_response(['success' => true, 'data' => []]);
        }
        $date = trim($_GET['date'] ?? '');
        if ($date !== '' && !date_today_or_future($date)) {
            json_response(
                ['success' => false, 'message' => 'Please select today or a future date.'],
                422
            );
        }
        $model = new Appointment($this->conn);
        json_response([
            'success' => true,
            'data' => $model->doctorsBySpecialization($specialization, $date)
        ]);
    }

    public function receptionistBookingProfile()
    {
        require_role('receptionist');
        $doctorId = (int) ($_GET['doctor_id'] ?? 0);
        if ($doctorId <= 0) {
            json_response(['success' => false, 'message' => 'Invalid doctor.'], 422);
        }
        $date = trim($_GET['date'] ?? '');
        if ($date !== '' && !date_today_or_future($date)) {
            json_response(
                ['success' => false, 'message' => 'Please select today or a future date.'],
                422
            );
        }
        $model = new Appointment($this->conn);
        $doctor = $model->doctorBookingProfile($doctorId, $date);
        if (!$doctor) {
            json_response(['success' => false, 'message' => 'Doctor profile not found.'], 404);
        }
        json_response(['success' => true, 'doctor' => $doctor]);
    }
    public function receptionistBookAppointment()
    {
        require_role('receptionist');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($patientId <= 0 || $slotId <= 0) {
            json_response(
                ['success' => false, 'message' => 'Select a patient and an available slot.'],
                422
            );
        }
        $model = new ReceptionistAppointmentModel($this->conn);
        $ok = $model->createAppointment($patientId, $slotId, $reason);
        json_response(
            [
                'success' => $ok,
                'message' => $ok
                    ? 'Appointment booked successfully.'
                    : 'The appointment could not be booked. The patient must exist and the slot must still be available.'
            ],
            $ok ? 200 : 422
        );
    }

    public function doctorPrescriptionCancel()
    {
        [$model, $doctor] = $this->doctorContext();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $ok = $model->deletePrescription((int) $doctor['id'], (int) ($_POST['id'] ?? 0));
        json_response(
            [
                'success' => $ok,
                'message' => $ok ? 'Prescription cancelled.' : 'Could not cancel prescription.'
            ],
            $ok ? 200 : 422
        );
    }

    public function doctorFollowupUpdate()
    {
        [$model, $doctor] = $this->doctorContext();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $id = (int) ($_POST['id'] ?? 0);
        $date = trim($_POST['followup_date'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        if (!$id || !$date || !$purpose) {
            json_response(
                ['success' => false, 'message' => 'Follow-up date and reason are required.'],
                422
            );
        }
        if (!valid_ymd_date($date)) {
            json_response(
                ['success' => false, 'message' => 'Please select a valid follow-up date.'],
                422
            );
        }
        if (!date_today_or_future($date)) {
            json_response(
                ['success' => false, 'message' => 'Follow-up date cannot be in the past.'],
                422
            );
        }
        $ok = $model->updateFollowupDetails((int) $doctor['id'], $id, $date, $purpose);
        json_response(
            [
                'success' => $ok,
                'message' => $ok
                    ? 'Follow-up updated successfully.'
                    : 'Only an active follow-up belonging to you can be edited.',
                'data' => ['id' => $id, 'followup_date' => $date, 'purpose' => $purpose]
            ],
            $ok ? 200 : 422
        );
    }

    public function doctorFollowupCancel()
    {
        [$model, $doctor] = $this->doctorContext();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $ok = $model->deleteFollowup((int) $doctor['id'], (int) ($_POST['id'] ?? 0));
        json_response(
            [
                'success' => $ok,
                'message' => $ok ? 'Follow-up cancelled.' : 'Could not cancel follow-up.'
            ],
            $ok ? 200 : 422
        );
    }

    public function patientAppointmentAction()
    {
        $patientId = $this->patientId();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $action = $_POST['action'] ?? '';
        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        if ($action === 'cancel') {
            $result = $this->appointmentModel->cancel($patientId, $appointmentId);
        } elseif ($action === 'delete') {
            $result = $this->appointmentModel->delete($patientId, $appointmentId);
        } else {
            json_response(['success' => false, 'message' => 'Invalid appointment action.'], 422);
        }
        $q = trim($_POST['q'] ?? '');
        $rows = $this->appointmentModel->searchForPatient($patientId, $q);
        $result['rows'] = $rows;
        json_response($result, $result['success'] ? 200 : 422);
    }

    private function adminSearchQuery()
    {
        require_role('admin');
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) > 120) {
            json_response(['success' => false, 'message' => 'Search text is too long.'], 422);
        }
        return $query;
    }
    public function adminStaffSearch()
    {
        $query = $this->adminSearchQuery();
        $model = new StaffModel($this->conn);
        json_response(['success' => true, 'data' => $model->searchStaff($query)]);
    }
    public function adminEquipmentSearch()
    {
        $query = $this->adminSearchQuery();
        $model = new EquipmentModel($this->conn);
        json_response(['success' => true, 'data' => $model->searchEquipment($query)]);
    }
    public function adminInventorySearch()
    {
        $query = $this->adminSearchQuery();
        $model = new InventoryModel($this->conn);
        json_response(['success' => true, 'data' => $model->searchInventory($query)]);
    }
    public function adminAddEquipmentType()
    {
        require_role('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            json_response(['success' => false, 'message' => 'Equipment name is required.'], 422);
        }
        $model = new EquipmentModel($this->conn);
        $row = $model->addEquipmentType($name);
        json_response(
            [
                'success' => (bool) $row,
                'message' => $row
                    ? 'Equipment type is ready to use.'
                    : 'Could not add equipment type.',
                'data' => $row
            ],
            $row ? 200 : 422
        );
    }
    public function adminAddStockItemType()
    {
        require_role('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'POST request required.'], 405);
        }
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'Invalid security token.'], 403);
        }
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        if ($name === '' || !in_array($category, stock_categories(), true)) {
            json_response(
                [
                    'success' => false,
                    'message' => 'Enter an item name and select a valid category.'
                ],
                422
            );
        }
        $model = new InventoryModel($this->conn);
        $row = $model->addStockItemType($name, $category);
        json_response(
            [
                'success' => (bool) $row,
                'message' => $row ? 'Stock item is ready to use.' : 'Could not add stock item.',
                'data' => $row
            ],
            $row ? 200 : 422
        );
    }
    public function adminInventoryHistory()
    {
        require_role('admin');
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid stock record.'], 422);
        }
        $model = new InventoryModel($this->conn);
        $item = $model->inventoryById($id);
        if (!$item) {
            json_response(['success' => false, 'message' => 'Stock record not found.'], 404);
        }
        json_response([
            'success' => true,
            'item' => $item,
            'data' => $model->inventoryHistory($id)
        ]);
    }
    public function adminBillingSearch()
    {
        $query = $this->adminSearchQuery();
        $key = $_GET['billing_type'] ?? 'patient';
        $map = [
            'patient' => 'Patient Charge',
            'doctor' => 'Doctor Payment',
            'staff' => 'Staff Payment'
        ];
        if (!isset($map[$key])) {
            json_response(['success' => false, 'message' => 'Invalid billing category.'], 422);
        }
        $model = new BillingModel($this->conn);
        json_response([
            'success' => true,
            'billing_type' => $key,
            'data' => $model->billing($map[$key], $query)
        ]);
    }
    public function adminRecordsSearch()
    {
        $query = $this->adminSearchQuery();
        $category = $_GET['category'] ?? '';
        $model = new AdminRecordsModel($this->conn);
        if ($category === 'patients') {
            $rows = $model->allPatients($query);
        } elseif ($category === 'appointments') {
            $rows = $model->allAppointments($query);
        } elseif ($category === 'followups') {
            $rows = $model->allFollowups($query);
        } else {
            json_response(['success' => false, 'message' => 'Invalid records category.'], 422);
        }
        json_response(['success' => true, 'category' => $category, 'data' => $rows]);
    }
}
