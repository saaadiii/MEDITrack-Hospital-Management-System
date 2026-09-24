<?php if (!isset($appointments)) {
    $appointments = [];
} ?>
<?php foreach ($appointments as $a): ?>
<?php $due = strtotime($a['appointment_date'] . ' ' . $a['appointment_time']) <= time(); ?>
<tr>
    <td><?= doctor_appointment_display_id($d['id'], $a['doctor_serial']) ?></td>
    <td><?= esc($a['patient_name']) ?></td>
    <td><?= esc(date('d M Y', strtotime($a['appointment_date']))) ?></td>
    <td><?= esc(date('h:i A', strtotime($a['appointment_time']))) ?></td>
    <td><span class="badge"><?= esc($a['status']) ?></span></td>
    <td>
        <?php if ($a['status'] === 'Booked' && $due): ?>
        <form
            class="inline-form doctor-complete-form"
            method="POST"
            action="index.php?page=doctor_appointment_status"
            data-appointment-id="<?= $a[
                      'id'
                  ] ?>"
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
                name="id"
                value="<?= $a['id'] ?>"
            >
            <button
                class="action-btn success"
                type="submit"
            >Complete appointment</button>
        </form>
        <?php elseif ($a['status'] === 'Booked'): ?>
        <span class="note">Available at appointment time</span>
        <?php elseif ($a['status'] === 'Completed'): ?>
        <span class="badge">Completed</span>
        <?php elseif ($a['status'] === 'Did not appear'): ?>
        <span class="badge low">Did not appear</span>
        <?php else: ?>
        <span class="badge low">Cancelled</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (
    !$appointments
): ?>
<tr>
    <td
        colspan="6"
        class="empty-cell"
    >No matching appointments found.</td>
</tr><?php endif; ?>
