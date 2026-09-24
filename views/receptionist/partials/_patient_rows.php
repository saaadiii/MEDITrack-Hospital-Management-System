<?php foreach ($patients as $p): ?>
<tr>
    <td><?= esc(patient_display_id($p['id'])) ?></td>
    <td><?= esc($p['name']) ?></td>
    <td><?= esc(
        $p['phone']
    ) ?></td>
    <td><?= esc($p['age'] ?? '—') ?></td>
    <td><?= esc($p['status']) ?></td>
    <td class="actions"><a
            class="action-btn secondary"
            href="index.php?page=receptionist_patients&edit=<?= (int) $p[
                 'id'
             ] ?>"
        >Edit</a>
        <form
            class="inline-form receptionist-live-action"
            method="POST"
            action="index.php?page=ajax&action=receptionist_live_action"
            data-confirm="Deactivate this patient?"
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
                value="patient_delete"
            >
            <input
                type="hidden"
                name="section"
                value="patients"
            >
            <input
                type="hidden"
                name="id"
                value="<?= (int) $p[
                    'id'
                ] ?>"
            >
            <button class="action-btn danger">Deactivate</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (
    !$patients
): ?>
<tr>
    <td
        colspan="6"
        class="empty-cell"
    >No patients found.</td>
</tr><?php endif; ?>
