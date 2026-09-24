<?php
class AdminController
{
    private $c;
    private $adminModel;
    private $doctorModel;
    private $staffModel;
    private $equipmentModel;
    private $inventoryModel;
    private $billingModel;
    private $recordsModel;

    function __construct($c)
    {
        $this->c = $c;
        $this->adminModel = new AdminModel($c);
        $this->doctorModel = new DoctorManagementModel($c);
        $this->staffModel = new StaffModel($c);
        $this->equipmentModel = new EquipmentModel($c);
        $this->inventoryModel = new InventoryModel($c);
        $this->billingModel = new BillingModel($c);
        $this->recordsModel = new AdminRecordsModel($c);
    }

    private function auth()
    {
        require_role('admin');
    }

    private function messages()
    {
        return [get_flash('success'), get_flash('error')];
    }

    function dashboard()
    {
        $this->auth();
        $stats = $this->adminModel->stats();
        $dashboardSummary = $this->adminModel->dashboardSummary();
        $recentActivity = $this->adminModel->recentActivity();
        $inventory = $this->inventoryModel->inventory();
        [$message, $error] = $this->messages();
        render_view(
            'admin/dashboard',
            compact('stats', 'dashboardSummary', 'recentActivity', 'inventory', 'message', 'error')
        );
    }

    function billing()
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processAction('admin_billing');
        }

        $billingType = $_GET['billing_type'] ?? 'patient';
        if (!in_array($billingType, ['patient', 'doctor', 'staff'], true)) {
            $billingType = 'patient';
        }
        $typeMap = [
            'patient' => 'Patient Charge',
            'doctor' => 'Doctor Payment',
            'staff' => 'Staff Payment'
        ];
        $billing = $this->billingModel->billing($typeMap[$billingType]);
        $doctors = $this->doctorModel->doctors();
        $staff = $this->staffModel->staff();
        [$message, $error] = $this->messages();
        render_view(
            'admin/billing',
            compact('billingType', 'billing', 'doctors', 'staff', 'message', 'error')
        );
    }

    function doctors()
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processAction('admin_doctors');
        }
        $doctors = $this->doctorModel->doctors();
        [$message, $error] = $this->messages();
        render_view('admin/doctors', compact('doctors', 'message', 'error'));
    }

    function staff()
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processAction('admin_staff');
        }
        $staff = $this->staffModel->staff();
        $staffRoleOptions = hospital_staff_roles();
        foreach ($staff as $staffRow) {
            $role = trim($staffRow['staff_role'] ?? '');
            if ($role !== '' && !in_array($role, $staffRoleOptions, true)) {
                $staffRoleOptions[] = $role;
            }
        }
        [$message, $error] = $this->messages();
        render_view('admin/staff', compact('staff', 'staffRoleOptions', 'message', 'error'));
    }

    function equipment()
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processAction('admin_equipment');
        }
        $equipment = $this->equipmentModel->equipment();
        $equipmentTypes = $this->equipmentModel->equipmentTypes();
        [$message, $error] = $this->messages();
        render_view('admin/equipment', compact('equipment', 'equipmentTypes', 'message', 'error'));
    }

    function inventory()
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processAction('admin_inventory');
        }
        $inventory = $this->inventoryModel->inventory();
        $stockItemTypes = $this->inventoryModel->stockItemTypes();
        [$message, $error] = $this->messages();
        render_view('admin/inventory', compact('inventory', 'stockItemTypes', 'message', 'error'));
    }

    function records()
    {
        $this->auth();
        $allPatients = $this->recordsModel->allPatients();
        $allAppointments = $this->recordsModel->allAppointments();
        $allFollowups = $this->recordsModel->allFollowups();
        [$message, $error] = $this->messages();
        render_view(
            'admin/records',
            compact('allPatients', 'allAppointments', 'allFollowups', 'message', 'error')
        );
    }

    private function processAction($returnPage)
    {
        require_csrf();
        $a = $_POST['action'] ?? '';
        $ok = false;
        $msg = 'Action completed.';

        switch ($a) {
            case 'doctor_status':
                $status = $_POST['status'] ?? '';
                if (!in_array($status, ['Active', 'Inactive'], true)) {
                    $msg = 'Invalid doctor status.';
                    break;
                }
                $ok = $this->doctorModel->setDoctorStatus((int) ($_POST['id'] ?? 0), $status);
                $msg = $status === 'Active' ? 'Doctor activated.' : 'Doctor deactivated.';
                break;

            case 'staff_save':
                if (trim($_POST['staff_role'] ?? '') === '') {
                    $msg = 'Please select a staff role.';
                    break;
                }
                if (
                    trim($_POST['department'] ?? '') !== '' &&
                    !in_array(trim($_POST['department']), hospital_departments(), true)
                ) {
                    $msg = 'Please select a valid staff department.';
                    break;
                }
                $ok = $this->staffModel->saveStaff($_POST);
                $msg = (int) ($_POST['id'] ?? 0) > 0 ? 'Staff record updated.' : 'Staff member added.';
                break;

            case 'staff_delete':
                $ok = $this->staffModel->deleteStaff((int) $_POST['id']);
                $msg = 'Staff member deactivated.';
                break;

            case 'equipment_save':
                if (
                    trim($_POST['department'] ?? '') === '' ||
                    !in_array(trim($_POST['department']), hospital_departments(), true)
                ) {
                    $msg = 'Please select a valid equipment department.';
                    break;
                }
                if (
                    (int) ($_POST['id'] ?? 0) === 0 &&
                    ((int) ($_POST['quantity'] ?? 0) < 1 || (int) ($_POST['quantity'] ?? 0) > 100)
                ) {
                    $msg = 'Equipment quantity must be between 1 and 100.';
                    break;
                }
                $purchaseDate = trim($_POST['purchase_date'] ?? '');
                if ($purchaseDate !== '' && !valid_ymd_date($purchaseDate)) {
                    $msg = 'Please select a valid equipment purchase date.';
                    break;
                }
                if ($purchaseDate !== '' && !date_today_or_past($purchaseDate)) {
                    $msg = 'Equipment purchase date cannot be in the future.';
                    break;
                }
                $ok = $this->equipmentModel->saveEquipment($_POST);
                $msg = (int) ($_POST['id'] ?? 0) > 0 ? 'Equipment record updated.' : 'Equipment added.';
                break;

            case 'equipment_delete':
                $ok = $this->equipmentModel->deleteEquipment((int) $_POST['id']);
                $msg = 'Equipment record deleted.';
                break;

            case 'inventory_save':
                $category = trim($_POST['category'] ?? '');
                if (!in_array($category, stock_categories(), true)) {
                    $msg = 'Please select a valid stock category.';
                    break;
                }
                $ok = $this->inventoryModel->saveInventory($_POST);
                $msg = (int) ($_POST['id'] ?? 0) > 0
                    ? 'Stock item updated and history saved.'
                    : 'Stock item added.';
                break;

            case 'inventory_delete':
                $ok = $this->inventoryModel->deleteInventory((int) $_POST['id']);
                $msg = 'Stock item deleted.';
                break;

            case 'billing_save':
                $transactionDate = trim($_POST['transaction_date'] ?? '');
                if (!valid_ymd_date($transactionDate)) {
                    $msg = 'Please select a valid billing date.';
                    break;
                }
                if (!date_today_or_past($transactionDate)) {
                    $msg = 'Billing date cannot be in the future.';
                    break;
                }
                $type = trim($_POST['type'] ?? '');
                if (
                    $type === 'Patient Charge' &&
                    ((int) ($_POST['patient_id'] ?? 0) <= 0 || (int) ($_POST['doctor_id'] ?? 0) <= 0)
                ) {
                    $msg = 'Please select both the patient and the doctor visited.';
                    break;
                }
                $ok = $this->billingModel->saveBilling($_POST);
                $msg = (int) ($_POST['id'] ?? 0) > 0 ? 'Billing record updated.' : 'Billing record added.';
                break;

            case 'billing_delete':
                $ok = $this->billingModel->deleteBilling((int) $_POST['id']);
                $msg = 'Billing record deleted.';
                break;

            default:
                $msg = 'Unknown action.';
        }

        flash(
            $ok ? 'success' : 'error',
            $ok ? $msg : ($msg !== 'Action completed.' ? $msg : 'Could not complete the action.')
        );

        $url = 'index.php?page=' . urlencode($returnPage);
        if ($returnPage === 'admin_billing') {
            $billingType = $_POST['billing_type'] ?? $_GET['billing_type'] ?? 'patient';
            if (in_array($billingType, ['patient', 'doctor', 'staff'], true)) {
                $url .= '&billing_type=' . urlencode($billingType);
            }
        }
        redirect($url);
    }
}
