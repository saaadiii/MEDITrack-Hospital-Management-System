<?php if (!isset($prescriptions)) {
    $prescriptions = [];
} ?>
<?php foreach ($prescriptions as $p): ?>
<tr>
    <td><?= !empty($p['appointment_id']) && isset($p['appointment_serial'])
         ? doctor_appointment_display_id($d['id'], $p['appointment_serial'])
         : '—' ?></td>
    <td><?= esc($p['patient_name']) ?></td>
    <td>
        <div class="medicine-history"><?php foreach (
             $p['medicines']
             as $m
         ): ?>
            <div class="medicine-history-item"><strong><?= esc(
                $m['medicine_name']
            ) ?></strong><span><?= esc($m['dosage']) ?> ·
                    <?= esc($m['frequency']) ?> ·
                    <?= esc(
                         $m['duration']
                     ) ?></span><?php if (trim($m['instructions'] ?? '') !== ''): ?><small><?= esc(
                        $m['instructions']
                    ) ?></small><?php endif; ?>
            </div><?php endforeach; ?>
        </div>
    </td>
    <td><?= esc($p['note'] ?: '—') ?></td>
    <td><?= esc(date('d M Y', strtotime($p['created_at']))) ?></td>
    <td><span class="badge"><?= esc($p['status']) ?></span></td>
    <td><?php if (
         $p['status'] === 'Active'
     ): ?>
        <form
            class="inline-form doctor-prescription-cancel-form"
            method="POST"
            action="index.php?page=doctor_prescription_delete"
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
                value="<?= $p[
                    'id'
                ] ?>"
            >
            <button class="action-btn danger">Cancel</button>
        </form><?php else: ?>—<?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (
    !$prescriptions
): ?>
<tr>
    <td
        colspan="7"
        class="empty-cell"
    >No matching prescriptions found.</td>
</tr><?php endif; ?>
