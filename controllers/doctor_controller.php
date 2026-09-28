<?php
class DoctorController
{
    private $c, $m;
    function __construct($c)
    {
        $this->c = $c;
        $this->m = new DoctorModel($c);
    }
    private function auth()
    {
        $auth = require_role('doctor');
        $d = $this->m->byUser((int) $auth['user_id']);
        if (!$d) {
            // Clear only the doctor login if its profile is stale. Other role
            // sessions in other tabs remain active.
            clear_role_session('doctor');
            redirect('index.php?page=login&role=doctor&profile_reset=doctor');
        }
        return $d;
    }
    function dashboard()
    {
        $d = $this->auth();
        $allAppointments = $this->m->appointments($d['id']);
        $today = local_today();
        $appointments = array_values(
            array_filter($allAppointments, fn($a) => ($a['appointment_date'] ?? '') === $today)
        );
        $followups = $this->m->followups($d['id']);
        $prescriptions = $this->m->prescriptions($d['id']);
        $stats = [
            'appointments' => count($appointments),
            'patients' => count($this->m->connectedPatients($d['id'])),
            'prescriptions' => count(
                array_filter($prescriptions, fn($x) => $x['status'] === 'Active')
            ),
            'followups' => count(array_filter($followups, fn($x) => $x['status'] === 'Active'))
        ];
        render_view(
            'doctor/dashboard',
            compact('d', 'appointments', 'followups', 'prescriptions', 'stats')
        );
    }

    function profile()
    {
        $d = $this->auth();
        $message = '';
        $type = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'gender' => trim($_POST['gender'] ?? ''),
                'specialization' => trim($_POST['specialization'] ?? ''),
                'qualification' => trim($_POST['qualification'] ?? ''),
                'license_number' => trim($_POST['license_number'] ?? ''),
                'experience' => (int) ($_POST['experience'] ?? 0),
                'department' => trim($_POST['department'] ?? ''),
                'room_number' => trim($_POST['room_number'] ?? ''),
                'bio' => trim($_POST['bio'] ?? '')
            ];
            if (strlen($data['name']) < 2 || strlen($data['name']) > 100) {
                $message = 'Full name must be between 2 and 100 characters.';
                $type = 'error';
            } elseif ($data['phone'] !== '' && strlen($data['phone']) > 20) {
                $message = 'Phone number is too long.';
                $type = 'error';
            } elseif (
                $data['gender'] !== '' &&
                !in_array($data['gender'], ['Male', 'Female', 'Other'], true)
            ) {
                $message = 'Please select a valid gender.';
                $type = 'error';
            } elseif (
                $data['specialization'] === '' ||
                !in_array($data['specialization'], doctor_specializations(), true)
            ) {
                $message = 'Please select a valid specialization from the list.';
                $type = 'error';
            } elseif (
                $data['department'] !== '' &&
                !in_array($data['department'], hospital_departments(), true)
            ) {
                $message = 'Please select a valid department from the list.';
                $type = 'error';
            } elseif (
                strlen($data['qualification']) > 150 ||
                strlen($data['license_number']) > 100 ||
                strlen($data['room_number']) > 50
            ) {
                $message = 'One or more profile fields are too long.';
                $type = 'error';
            } elseif ($data['experience'] < 0 || $data['experience'] > 80) {
                $message = 'Please enter valid years of experience.';
                $type = 'error';
            } elseif (strlen($data['bio']) > 1000) {
                $message = 'Professional bio must be 1000 characters or less.';
                $type = 'error';
            } elseif ($this->m->updateProfile((int) $d['id'], $data)) {
                $message = 'Profile updated successfully.';
                $type = 'success';
                $_SESSION['auth']['doctor']['doctor_name'] = $data['name'];
                $doctorAuth = get_role_session('doctor');
                $d = $this->m->byUser((int) $doctorAuth['user_id']);
            } else {
                $message = 'Could not update your profile.';
                $type = 'error';
            }
        }
        render_view('doctor/profile', compact('d', 'message', 'type'));
    }
    function patients()
    {
        $d = $this->auth();
        $search = trim($_GET['search'] ?? '');
        $patients = $this->m->allPatients($d['id'], $search);
        $appointments = $this->m->appointments($d['id']);
        $selected = null;
        $symptoms = [];
        if (isset($_GET['patient'])) {
            $selected = $this->m->patient((int) $_GET['patient'], $d['id']);
            if ($selected) {
                $symptoms = $this->m->symptoms((int) $selected['id']);
            }
        }
        render_view(
            'doctor/patients',
            compact('d', 'patients', 'appointments', 'search', 'selected', 'symptoms')
        );
    }
    function prescriptions()
    {
        $d = $this->auth();
        $message = '';
        $type = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $names = $_POST['medicine_name'] ?? [];
            $dosages = $_POST['dosage'] ?? [];
            $frequencies = $_POST['frequency'] ?? [];
            $durations = $_POST['duration'] ?? [];
            $instructions = $_POST['instructions'] ?? [];
            if (
                !is_array($names) ||
                !is_array($dosages) ||
                !is_array($frequencies) ||
                !is_array($durations) ||
                !is_array($instructions)
            ) {
                $names = $dosages = $frequencies = $durations = $instructions = [];
            }
            $medicines = [];
            $medicineError = false;
            $dosageUnitError = false;
            $count = max(
                count($names),
                count($dosages),
                count($frequencies),
                count($durations),
                count($instructions)
            );
            for ($i = 0; $i < $count; $i++) {
                $m = [
                    'medicine_name' => trim($names[$i] ?? ''),
                    'dosage' => trim($dosages[$i] ?? ''),
                    'frequency' => trim($frequencies[$i] ?? ''),
                    'duration' => trim($durations[$i] ?? ''),
                    'instructions' => trim($instructions[$i] ?? '')
                ];
                if (
                    $m['medicine_name'] === '' &&
                    $m['dosage'] === '' &&
                    $m['frequency'] === '' &&
                    $m['duration'] === '' &&
                    $m['instructions'] === ''
                ) {
                    continue;
                }
                if (
                    $m['medicine_name'] === '' ||
                    $m['dosage'] === '' ||
                    $m['frequency'] === '' ||
                    $m['duration'] === '' ||
                    strlen($m['medicine_name']) > 150 ||
                    strlen($m['dosage']) > 100 ||
                    strlen($m['frequency']) > 100 ||
                    strlen($m['duration']) > 100 ||
                    strlen($m['instructions']) > 255
                ) {
                    $medicineError = true;
                    break;
                }
                if (!preg_match('/[A-Za-z%]/', $m['dosage'])) {
                    $dosageUnitError = true;
                    break;
                }
                $medicines[] = $m;
            }
            $data = [
                'id' => (int) ($_POST['id'] ?? 0),
                'patient_id' => (int) ($_POST['patient_id'] ?? 0),
                'appointment_id' => (int) ($_POST['appointment_id'] ?? 0),
                'note' => trim($_POST['note'] ?? ''),
                'status' => $_POST['status'] ?? 'Active',
                'medicines' => $medicines
            ];
            if (!$data['patient_id']) {
                $message = 'Please select a patient.';
                $type = 'error';
            } elseif (!$data['appointment_id']) {
                $message = 'Please select a patient with a valid appointment ID.';
                $type = 'error';
            } elseif (!$medicines || $medicineError) {
                $message =
                    'Please complete every medicine row: medicine, dosage, frequency and duration are required.';
                $type = 'error';
            } elseif ($dosageUnitError) {
                $message =
                    'Please include a dosage unit, for example 500 mg, 5 mL, or 1 tablet. Do not enter only a number such as 2.5.';
                $type = 'error';
            } elseif (strlen($data['note']) > 2000) {
                $message = 'Doctor note is too long.';
                $type = 'error';
            } elseif (!$this->m->canAccessPatient($d['id'], $data['patient_id'])) {
                $message =
                    'You can only prescribe to a patient who has booked an appointment with you.';
                $type = 'error';
            } elseif ($this->m->savePrescription($d['id'], $data)) {
                $message =
                    'Prescription saved successfully with ' .
                    count($medicines) .
                    ' medicine' .
                    (count($medicines) === 1 ? '' : 's') .
                    '.';
                $type = 'success';
            } else {
                $message =
                    'Could not save prescription. The appointment must belong to this patient ' .
                    'and doctor, still be booked, and its scheduled time must have arrived.';
                $type = 'error';
            }
        }
        $prescriptions = $this->m->prescriptions($d['id'], trim($_GET['search'] ?? ''));
        $patients = $this->m->connectedPatients($d['id']);
        $appointmentOptions = $this->m->clinicalAppointmentOptions($d['id']);
        render_view(
            'doctor/prescriptions',
            compact('d', 'message', 'type', 'prescriptions', 'patients', 'appointmentOptions')
        );
    }
    function deletePrescription()
    {
        $d = $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=doctor_prescriptions');
        }
        require_csrf();
        $this->m->deletePrescription($d['id'], (int) ($_POST['id'] ?? 0));
        flash('success', 'Prescription cancelled.');
        redirect('index.php?page=doctor_prescriptions');
    }
    function followups()
    {
        $d = $this->auth();
        $message = '';
        $type = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $data = [
                'id' => (int) ($_POST['id'] ?? 0),
                'patient_id' => (int) ($_POST['patient_id'] ?? 0),
                'appointment_id' => (int) ($_POST['appointment_id'] ?? 0),
                'followup_date' => trim($_POST['followup_date'] ?? ''),
                'purpose' => trim($_POST['purpose'] ?? ''),
                'status' => $_POST['status'] ?? 'Active'
            ];
            if ($data['id']) {
                if (!$data['followup_date'] || !$data['purpose']) {
                    $message = 'Follow-up date and reason are required.';
                    $type = 'error';
                } elseif (!valid_ymd_date($data['followup_date'])) {
                    $message = 'Please select a valid follow-up date.';
                    $type = 'error';
                } elseif (!date_today_or_future($data['followup_date'])) {
                    $message =
                        'Follow-up date cannot be in the past. Please select today or a future date.';
                    $type = 'error';
                } elseif (
                    $this->m->updateFollowupDetails(
                        $d['id'],
                        $data['id'],
                        $data['followup_date'],
                        $data['purpose']
                    )
                ) {
                    $message = 'Follow-up updated successfully.';
                    $type = 'success';
                } else {
                    $message = 'Only an active follow-up belonging to you can be edited.';
                    $type = 'error';
                }
            } else {
                if (
                    !$data['patient_id'] ||
                    !$data['appointment_id'] ||
                    !$data['followup_date'] ||
                    !$data['purpose']
                ) {
                    $message = 'Patient, appointment ID, date and reason are required.';
                    $type = 'error';
                } elseif (!valid_ymd_date($data['followup_date'])) {
                    $message = 'Please select a valid follow-up date.';
                    $type = 'error';
                } elseif (!date_today_or_future($data['followup_date'])) {
                    $message =
                        'Follow-up date cannot be in the past. Please select today or a future date.';
                    $type = 'error';
                } elseif (!$this->m->canAccessPatient($d['id'], $data['patient_id'])) {
                    $message = 'You can only create a follow-up for a patient connected to you.';
                    $type = 'error';
                } elseif ($this->m->saveFollowup($d['id'], $data)) {
                    $message = 'Follow-up saved successfully.';
                    $type = 'success';
                } else {
                    $message =
                        'Could not save follow-up. The appointment must belong to this patient and ' .
                        'doctor, still be booked, and its scheduled time must have arrived.';
                    $type = 'error';
                }
            }
        }
        $followups = $this->m->followups($d['id']);
        $patients = $this->m->connectedPatients($d['id']);
        $appointmentOptions = $this->m->clinicalAppointmentOptions($d['id']);
        render_view(
            'doctor/followups',
            compact('d', 'message', 'type', 'followups', 'patients', 'appointmentOptions')
        );
    }
    function deleteFollowup()
    {
        $d = $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=doctor_followups');
        }
        require_csrf();
        $this->m->deleteFollowup($d['id'], (int) ($_POST['id'] ?? 0));
        flash('success', 'Follow-up cancelled.');
        redirect('index.php?page=doctor_followups');
    }
    function slots()
    {
        $d = $this->auth();
        $message = '';
        $type = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
            $action = $_POST['action'] ?? '';
            if ($action === 'availability_save') {
                $availabilityId = (int) ($_POST['id'] ?? 0);
                $availabilityDate = trim($_POST['available_date'] ?? '');
                $today = date('Y-m-d');
                $currentTime = date('H:i');
                $startTime = trim($_POST['start_time'] ?? '');
                $parsedDate = DateTime::createFromFormat('Y-m-d', $availabilityDate);
                $validDate = $parsedDate && $parsedDate->format('Y-m-d') === $availabilityDate;
                if (!$validDate) {
                    $ok = false;
                    $message = 'Please select a valid availability date.';
                    $type = 'error';
                } elseif ($availabilityDate < $today) {
                    $ok = false;
                    $message =
                        'Availability cannot be created for a past date. Please select today or a future date.';
                    $type = 'error';
                } elseif (
                    $availabilityDate === $today &&
                    $startTime !== '' &&
                    $startTime < $currentTime
                ) {
                    $ok = false;
                    $message = 'For today, availability must start at the current time or later.';
                    $type = 'error';
                } elseif (
                    $this->m->availabilityDateExists($d['id'], $availabilityDate, $availabilityId)
                ) {
                    $ok = false;
                    $message =
                        'You already submitted availability for this date. Edit the existing entry instead of creating another one.';
                    $type = 'error';
                } elseif (
                    $availabilityId > 0 &&
                    $this->m->availabilityHasSlots($d['id'], $availabilityId)
                ) {
                    $ok = false;
                    $message =
                        'This availability already has appointment slots, so its date or time can no longer be changed.';
                    $type = 'error';
                } else {
                    $ok = $this->m->saveAvailability($d['id'], $_POST);
                    $message = $ok
                        ? 'Availability saved. The receptionist can now create appointment slots inside this time range.'
                        : 'Could not save availability. Check the date and time range.';
                    $type = $ok ? 'success' : 'error';
                }
            } elseif ($action === 'availability_delete') {
                $availabilityId = (int) ($_POST['id'] ?? 0);
                $hasSlots = $this->m->availabilityHasSlots($d['id'], $availabilityId);
                $ok = !$hasSlots && $this->m->deleteAvailability($d['id'], $availabilityId);
                $message = $ok
                    ? 'Availability deleted.'
                    : ($hasSlots
                        ? 'This availability already has appointment slots and cannot be deleted.'
                        : 'Could not delete availability.');
                $type = $ok ? 'success' : 'error';
            }
        }
        $date = $_GET['date'] ?? '';
        $slots = $this->m->availability($d['id'], $date);
        $editSlot = null;
        if (isset($_GET['edit'])) {
            foreach ($this->m->availability($d['id']) as $x) {
                if ((int) $x['id'] === (int) $_GET['edit']) {
                    $editSlot = $x;
                    break;
                }
            }
        }
        render_view('doctor/slots', compact('d', 'slots', 'editSlot', 'date', 'message', 'type'));
    }
    function deleteAppointment()
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
        }
        flash(
            'error',
            'Doctors cannot cancel appointments. Patients or the receptionist can cancel booked appointments.'
        );
        redirect('index.php?page=doctor_patients');
    }
    function appointmentStatus()
    {
        $d = $this->auth();
        require_csrf();
        $ok = $this->m->completeAppointment($d['id'], (int) ($_POST['id'] ?? 0));
        flash(
            $ok ? 'success' : 'error',
            $ok
                ? 'Appointment marked as completed.'
                : 'A booked appointment can only be completed at or after its scheduled date and time.'
        );
        redirect('index.php?page=doctor_patients');
    }
}
