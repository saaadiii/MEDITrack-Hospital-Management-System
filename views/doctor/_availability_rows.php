<?php $today = $today ?? date('Y-m-d');
$currentTime = date('H:i:s');
if (!isset($slots)) {
    $slots = [];
}
?>
<?php foreach ($slots as $row):
    $expired =
        $row['available_date'] < $today ||
        ($row['available_date'] === $today && $row['end_time'] <= $currentTime);
    $locked = !empty($row['has_slots']); ?>
<tr>
    <td><?= esc(date('d M Y', strtotime($row['available_date']))) ?></td>
    <td><?= esc(date('h:i A', strtotime($row['start_time']))) ?> - <?= esc(
         date('h:i A', strtotime($row['end_time']))
     ) ?></td>
    <td><?= esc($row['note'] ?: '—') ?></td>
    <td><span class="badge"><?= esc($expired ? 'Expired' : $row['status']) ?></span></td>
    <td><?php if ($locked): ?>
        <span class="slot-action-text">In use</span>
        <?php else: ?>
        <?php if (!$expired): ?><a
            class="action-btn secondary"
            href="index.php?page=doctor_slots&edit=<?= $row[
                'id'
            ] ?>"
        >Edit</a> <?php endif; ?>
        <form
            class="inline-form availability-delete-form"
            method="POST"
            action="index.php?page=doctor_slots"
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
                value="availability_delete"
            >
            <input
                type="hidden"
                name="id"
                value="<?= $row[
                    'id'
                ] ?>"
            >
            <button
                class="action-btn danger"
                type="submit"
            >Delete</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php
endforeach; ?>
<?php if (
    !$slots
): ?>
<tr>
    <td
        colspan="5"
        class="empty-cell"
    >No availability found.</td>
</tr><?php endif; ?>
