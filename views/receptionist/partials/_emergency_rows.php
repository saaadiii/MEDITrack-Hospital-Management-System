<?php foreach ($emergencies as $e): ?>
<tr>
    <td><strong><?= esc(
         $e['display_code'] ?? 'ER-' . str_pad((string) (int) $e['id'], 3, '0', STR_PAD_LEFT)
     ) ?></strong></td>
    <td><?= esc($e['patient_name']) ?></td>
    <td><?= esc($e['emergency_type']) ?></td>
    <td><?= esc($e['arrival_time']) ?></td>
    <td><span class="badge"><?= esc($e['status']) ?></span></td>
    <td class="actions">
        <a
            class="action-btn secondary"
            href="index.php?page=receptionist_emergency&edit=<?= (int) $e[
                  'id'
              ] ?>"
        >Edit</a>
        <?php if (($e['status'] ?? '') === 'Waiting'): ?>
        <form
            class="inline-form receptionist-live-action"
            method="POST"
            action="index.php?page=ajax&action=receptionist_live_action"
            data-confirm="Cancel this emergency record?"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= esc(csrf_token()) ?>"
            >
            <input
                type="hidden"
                name="action"
                value="emergency_delete"
            >
            <input
                type="hidden"
                name="section"
                value="emergency"
            >
            <input
                type="hidden"
                name="id"
                value="<?= (int) $e['id'] ?>"
            >
            <button class="action-btn danger">Cancel</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (
    !$emergencies
): ?>
<tr>
    <td
        colspan="6"
        class="empty-cell"
    >No emergency records found.</td>
</tr><?php endif; ?>
