<?php foreach ($appointments as $a): ?>
<tr>
    <td><?= esc(
         !empty($a['doctor_id']) && !empty($a['doctor_serial'])
             ? doctor_appointment_display_id($a['doctor_id'], $a['doctor_serial'])
             : appointment_display_id($a['id'])
     ) ?></td>
    <td><?= esc($a['patient_name']) ?></td>
    <td><?= esc($a['doctor_name']) ?></td>
    <td><?= esc(
        $a['appointment_date']
    ) ?> · <?= esc(date('h:i A', strtotime($a['appointment_time']))) ?></td>
    <td><?= esc(
        $a['reason'] ?? '—'
    ) ?></td>
    <td><?= esc($a['status']) ?></td>
    <td><?php if (
         $a['status'] === 'Booked'
     ): ?>
        <form
            class="inline-form receptionist-live-action"
            method="POST"
            action="index.php?page=ajax&action=receptionist_live_action"
            data-confirm="Cancel this appointment?"
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
                value="appointment_status"
            >
            <input
                type="hidden"
                name="section"
                value="appointments"
            >
            <input
                type="hidden"
                name="id"
                value="<?= (int) $a[
                    'id'
                ] ?>"
            >
            <button class="action-btn danger">Cancel appointment</button>
        </form><?php elseif (
             $a['status'] === 'Completed'
         ): ?><span class="badge">Completed by
            doctor</span><?php elseif (
                 $a['status'] === 'Did not appear'
             ): ?><span class="badge low">Did not
            appear</span><?php else: ?><span
            class="badge low">Cancelled</span><?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (
    !$appointments
): ?>
<tr>
    <td
        colspan="7"
        class="empty-cell"
    >No appointments found.</td>
</tr><?php endif; ?>
