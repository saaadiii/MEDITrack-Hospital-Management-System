<?php
$title = 'Receptionist Dashboard';
$current = 'receptionist';
$heading = 'Receptionist Dashboard';
$subheading = 'Patient management · doctor slots · emergency registration';
require __DIR__ . '/_page_top.php';
?>
<?php require __DIR__ . '/partials/_dashboard_overview.php'; ?>
<?php // Stable partition of a dashboard-only list: waiting entries first.
    // Stable partition of a dashboard-only list: waiting entries first.
    $dashboardEmergencies = array_merge(
    array_values(array_filter($emergencies, fn($entry) => $entry['status'] === 'Waiting')),
    array_values(array_filter($emergencies, fn($entry) => $entry['status'] !== 'Waiting'))
); ?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Emergency Queue</h2>
            <p>Current and previous emergency registrations.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Emergency ID</th>
                    <th>Patient</th>
                    <th>Type</th>
                    <th>Arrival Date</th>
                    <th>Arrival Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dashboardEmergencies as $e): ?>
                <tr class="<?= $e['status'] === 'Waiting'
                                        ? 'reception-emergency-waiting'
                                        : '' ?>">
                    <td><strong><?= esc(
                                                $e['display_code'] ??
                                                    'ER-' . str_pad((string) (int) $e['id'], 3, '0', STR_PAD_LEFT)
                                            ) ?></strong></td>
                    <td><?= esc($e['patient_name']) ?></td>
                    <td><?= esc($e['emergency_type']) ?></td>
                    <td><?= esc(date('d M Y', strtotime($e['arrival_time']))) ?></td>
                    <td><?= esc(date('h:i A', strtotime($e['arrival_time']))) ?></td>
                    <td><span class="badge"><?= esc($e['status']) ?></span></td>
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
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/_scripts.php'; ?>
<?php require __DIR__ . '/../shared/bottom.php'; ?>
