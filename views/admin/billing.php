<?php
$title = 'Cost & Billing';
$current = 'admin_billing';
$heading = 'Cost & Billing';
$subheading = 'Manage patient charges, doctor payments and staff payments separately';
require __DIR__ . '/_page_top.php';
?>
<?php
$billingLabels = [
    'patient' => 'Patient Charges',
    'doctor' => 'Doctor Payments',
    'staff' => 'Staff Payments'
];
$billingTypeValues = [
    'patient' => 'Patient Charge',
    'doctor' => 'Doctor Payment',
    'staff' => 'Staff Payment'
];
$currentBillingType = $billingTypeValues[$billingType];
?>
<nav
    class="admin-subnav"
    aria-label="Cost and billing categories"
>
    <?php foreach (
         $billingLabels
         as $key => $label
     ): ?><a
        class="admin-subnav-link <?= $billingType === $key
            ? 'active'
            : '' ?>"
        href="index.php?page=admin_billing&billing_type=<?= $key ?>"
    ><?= esc(
        $label
    ) ?></a><?php endforeach; ?>
</nav>

<section
    class="panel"
    id="admin-billing-form-panel"
>
    <div class="panel-header">
        <div>
            <h2 id="admin-billing-form-title"><?= esc(
                 $billingLabels[$billingType]
             ) ?></h2>
            <p><?php if ($billingType === 'patient') {
                echo 'Record the patient charge together with the doctor the patient visited.';
            } elseif ($billingType === 'doctor') {
                echo 'Record payments made to registered doctors.';
            } else {
                echo 'Record payments made to hospital staff.';
            } ?></p>
        </div>
    </div>
    <form
        method="POST"
        class="form-grid"
        id="admin-billing-form"
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
            name="action"
            value="billing_save"
        >
        <input
            type="hidden"
            name="section"
            value="billing"
        >
        <input
            type="hidden"
            name="billing_type"
            value="<?= esc(
                $billingType
            ) ?>"
        >
        <input
            type="hidden"
            name="type"
            value="<?= esc(
                $currentBillingType
            ) ?>"
        >
        <input
            type="hidden"
            name="id"
            value=""
        >
        <?php if ($billingType === 'patient'): ?>
        <div class="form-group admin-entity-picker">
            <label>Patient</label>
            <input
                type="hidden"
                name="patient_id"
                id="admin-charge-patient-id"
                required
            >
            <input
                type="search"
                id="admin-charge-patient-search"
                placeholder="Search by patient ID, name, phone, email, age, gender, blood group or status"
                autocomplete="off"
            >
            <div
                id="admin-charge-patient-results"
                class="admin-picker-results"
                hidden
            ></div>
            <small
                class="note"
                id="admin-charge-patient-selected"
            >Select a patient from the search results.</small>
        </div>
        <div class="form-group admin-entity-picker">
            <label>Doctor visited</label>
            <input
                type="hidden"
                name="doctor_id"
                id="admin-charge-doctor-id"
                required
            >
            <input
                type="search"
                id="admin-charge-doctor-search"
                placeholder="Search by doctor ID, name, specialization, phone, email, department or status"
                autocomplete="off"
            >
            <div
                id="admin-charge-doctor-results"
                class="admin-picker-results"
                hidden
            ></div>
            <small
                class="note"
                id="admin-charge-doctor-selected"
            >Select the doctor visited from the search results.</small>
        </div>
        <?php elseif ($billingType === 'doctor'): ?>
        <div class="form-group">
            <label>Doctor</label>
            <select
                name="doctor_id"
                required
            >
                <option value="">Select doctor</option><?php foreach (
                       $doctors
                       as $d
                   ): ?>
                <option value="<?= $d['id'] ?>"><?= esc(
                    $d['name']
                ) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
        <div class="form-group">
            <label>Staff member</label>
            <select
                name="staff_id"
                required
            >
                <option value="">Select staff member</option><?php foreach (
                       $staff
                       as $s
                   ): ?>
                <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> — <?= esc(
                     $s['staff_role']
                 ) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <label>Description</label>
            <input
                name="description"
                required
            >
        </div>
        <div class="form-group">
            <label>Amount</label>
            <input
                type="number"
                min="0"
                step="0.01"
                name="amount"
                required
            >
        </div>
        <div class="form-group">
            <label><?= $billingType === 'patient'
                  ? 'Paid'
                  : 'Paid amount' ?></label>
            <input
                type="number"
                min="0"
                step="0.01"
                name="paid"
                value="0"
            >
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="payment_status">
                <option>Unpaid</option>
                <option>Partial</option>
                <option>Paid</option>
            </select>
        </div>
        <div class="form-group">
            <label>Date</label>
            <input
                type="date"
                name="transaction_date"
                value="<?= date(
                      'Y-m-d'
                  ) ?>"
                max="<?= date(
                    'Y-m-d'
                ) ?>"
                data-date-rule="past-or-today"
                data-default-today="1"
                required
            >
        </div>
        <div class="full form-actions">
            <button
                class="small-btn"
                id="admin-billing-submit"
            >Add <?= esc(
                  rtrim($billingLabels[$billingType], 's')
              ) ?></button>
            <button
                type="button"
                class="action-btn secondary"
                id="admin-billing-cancel-edit"
                hidden
            >Cancel Edit</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= esc(
                 $billingLabels[$billingType]
             ) ?> Records</h2>
            <p>Search and maintain <?= strtolower(
                 esc($billingLabels[$billingType])
             ) ?>.</p>
        </div>
    </div>
    <div class="toolbar admin-search-toolbar">
        <input
            id="admin-billing-search"
            type="search"
            placeholder="<?= esc(
                 $billingType === 'patient'
                     ? 'Search by patient, doctor, description, amount, paid amount, status, date or ID'
                     : ($billingType === 'doctor'
                         ? 'Search by doctor, description, amount, paid amount, status, date or ID'
                         : 'Search by staff member, description, amount, paid amount, status, date or ID')
             ) ?>"
            autocomplete="off"
        >
        <button
            id="admin-billing-search-button"
            type="button"
            class="small-btn secondary"
        >Search</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php if ($billingType === 'patient'): ?>
                    <th>Patient</th>
                    <th>Doctor</th><?php elseif (
                           $billingType === 'doctor'
                       ): ?>
                    <th>Doctor</th><?php else: ?>
                    <th>Staff</th><?php endif; ?>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Last edited</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody
                id="admin-billing-table-body"
                data-billing-type="<?= esc(
                      $billingType
                  ) ?>"
                data-empty-message="No matching billing records found."
            >
                <?php
                  foreach ($billing as $b): ?>
                <tr>
                    <?php if ($billingType === 'patient'): ?>
                    <td><?= !empty($b['patient_id'])
                        ? esc(patient_display_id($b['patient_id']) . ' · ' . ($b['patient_name'] ?? '—'))
                        : esc($b['patient_name'] ?? '—') ?></td>
                    <td><?= !empty($b['doctor_id'])
                        ? esc(doctor_display_id($b['doctor_id']) . ' · ' . ($b['doctor_name'] ?? '—'))
                        : esc($b['doctor_name'] ?? '—') ?></td><?php elseif (
                            $billingType === 'doctor'
                        ): ?>
                    <td><?= !empty($b['doctor_id'])
                        ? esc(doctor_display_id($b['doctor_id']) . ' · ' . ($b['doctor_name'] ?? '—'))
                        : esc($b['doctor_name'] ?? '—') ?></td><?php else: ?>
                    <td><?= !empty($b['staff_id'])
                        ? esc(
                            'S' .
                                str_pad((string) (int) $b['staff_id'], 4, '0', STR_PAD_LEFT) .
                                ' · ' .
                                ($b['staff_name'] ?? '—')
                        )
                        : esc($b['staff_name'] ?? '—') ?></td><?php endif; ?>
                    <td><?= esc($b['description']) ?></td>
                    <td>৳<?= number_format(
                        (float) $b['amount'],
                        2
                    ) ?></td>
                    <td>৳<?= number_format((float) $b['paid'], 2) ?></td>
                    <td><?= esc(
                        $b['payment_status']
                    ) ?></td>
                    <td><?= esc($b['transaction_date']) ?></td>
                    <td><?= date(
                        'd M Y, h:i A',
                        strtotime($b['updated_at'] ?? $b['created_at'])
                    ) ?></td>
                    <td class="actions">
                        <button
                            type="button"
                            class="action-btn secondary admin-billing-edit"
                            data-id="<?= $b[
                                    'id'
                                ] ?>"
                            data-patient-id="<?= esc($b['patient_id'] ?? '') ?>"
                            data-patient-name="<?= esc(
                                $b['patient_name'] ?? ''
                            ) ?>"
                            data-doctor-id="<?= esc($b['doctor_id'] ?? '') ?>"
                            data-doctor-name="<?= esc(
                                $b['doctor_name'] ?? ''
                            ) ?>"
                            data-staff-id="<?= esc($b['staff_id'] ?? '') ?>"
                            data-description="<?= esc(
                                $b['description']
                            ) ?>"
                            data-amount="<?= esc($b['amount']) ?>"
                            data-paid="<?= esc(
                                $b['paid']
                            ) ?>"
                            data-status="<?= esc($b['payment_status']) ?>"
                            data-date="<?= esc(
                                $b['transaction_date']
                            ) ?>"
                        >Edit</button>
                        <form
                            method="POST"
                            class="inline-form"
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
                                name="action"
                                value="billing_delete"
                            >
                            <input
                                type="hidden"
                                name="section"
                                value="billing"
                            >
                            <input
                                type="hidden"
                                name="billing_type"
                                value="<?= esc(
                                    $billingType
                                ) ?>"
                            >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= $b[
                                    'id'
                                ] ?>"
                            >
                            <button class="action-btn danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach;
                  if (!$billing): ?>
                <tr>
                    <td
                        colspan="10"
                        class="empty-cell"
                    >No records found.</td>
                </tr><?php endif;
                  ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../shared/bottom.php'; ?>
