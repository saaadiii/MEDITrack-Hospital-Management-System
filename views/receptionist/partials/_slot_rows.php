<?php foreach ($slots as $s): ?>
<tr>
    <td><strong><?= esc(doctor_display_id($s['doctor_id'])) ?></strong></td>
    <td><strong><?= esc($s['doctor_name']) ?></strong>
        <br><small><?= esc(
            $s['specialization']
        ) ?></small>
    </td>
    <td><?= esc($s['slot_date']) ?></td>
    <td><?= esc(
        date('h:i A', strtotime($s['start_time']))
    ) ?> - <?= esc(date('h:i A', strtotime($s['end_time']))) ?></td>
    <td><?= esc($s['status']) ?></td>
    <td><?php if (
         $s['status'] !== 'Booked'
     ): ?>
        <div class="actions slot-row-actions"><a
                class="action-btn secondary"
                href="index.php?page=receptionist_slots&edit=<?= (int) $s[
                    'id'
                ] ?>"
            >Edit</a>
            <form
                class="inline-form receptionist-live-action"
                method="POST"
                action="index.php?page=ajax&action=receptionist_live_action"
                data-confirm="Delete this slot?"
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
                    value="slot_delete"
                >
                <input
                    type="hidden"
                    name="section"
                    value="slots"
                >
                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $s[
                        'id'
                    ] ?>"
                >
                <button class="action-btn danger">Delete</button>
            </form>
        </div><?php else: ?><span class="slot-action-text">Booked</span><?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$slots): ?>
<tr>
    <td
        colspan="6"
        class="empty-cell"
    >No slots found.</td>
</tr><?php endif; ?>
