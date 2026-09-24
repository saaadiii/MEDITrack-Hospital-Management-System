<?php
$title = 'Admin Dashboard';
$current = 'admin';
$heading = 'Admin Dashboard';
$subheading = 'Hospital-wide monitoring, billing, staff, equipment and stock';
require __DIR__ . '/_page_top.php';
?>
<section class="stats-grid">
    <div class="stat-card"><span>Active patients</span><strong><?= $stats['patients'] ?></strong></div>
    <div class="stat-card"><span>Active doctors</span><strong><?= $stats['doctors'] ?></strong></div>
    <div class="stat-card"><span>Booked appointments</span><strong><?= $stats[
         'appointments'
     ] ?></strong></div>
    <div class="stat-card"><span>Low stock</span><strong><?= $stats['low_stock'] ?></strong></div>
</section>
<?php
$dashIcon = function ($name) {
    $paths = [
        'overview' =>
            '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-14 4h3m4 0h3"/>',
        'patient' =>
            '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m1-16a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/>',
        'billing' =>
            '<rect x="4" y="2" width="16" height="20" rx="3"/><path d="M8 7h8m-8 5h8m-8 5h4"/>',
        'appointment' =>
            '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-10 5 2 2 4-4"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'cancel' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'stock' => '<path d="m12 2 9 5v10l-9 5-9-5V7l9-5Zm-9 5 9 5 9-5M12 12v10M7 4.8l10 5.5v5"/>'
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        ($paths[$name] ?? $paths['clock']) .
        '</svg>';
};
$today = $dashboardSummary['today'];
$lowItems = array_values(
    array_filter($inventory, function ($i) {
        return $i['stock_status'] === 'Low Stock';
    })
);
usort($lowItems, function ($a, $b) {
    return (int) $a['quantity'] <=> (int) $b['quantity'];
});
?>
<div class="admin-dashboard-grid">
    <section class="admin-dash-panel">
        <div class="admin-dash-heading">
            <h2><?= $dashIcon(
                  'overview'
              ) ?>Hospital Overview</h2><span class="admin-dash-date"><?= esc(date('F Y')) ?></span>
        </div>
        <div class="admin-monthly-grid">
            <div class="admin-monthly-card tone-blue"><span
                    class="admin-dash-icon"><?= $dashIcon(
                           'patient'
                       ) ?></span><span
                    class="admin-metric-label">Patients Registered This
                    Month</span><strong><?= number_format(
                        (int) $dashboardSummary['monthly_patients']
                    ) ?></strong></div>
            <div class="admin-monthly-card tone-green"><span
                    class="admin-dash-icon"><?= $dashIcon(
                           'billing'
                       ) ?></span><span
                    class="admin-metric-label">Revenue Collected This Month</span><strong
                    class="admin-revenue"
                ><span>BDT</span> <?= number_format(
                    (float) $dashboardSummary['monthly_revenue'],
                    2
                ) ?></strong></div>
        </div>
    </section>
    <section class="admin-dash-panel">
        <div class="admin-dash-heading">
            <h2><?= $dashIcon(
                  'appointment'
              ) ?>Today’s Operations</h2><span class="admin-dash-date"><?= esc(date('d M Y')) ?></span>
        </div>
        <div class="admin-operations-grid">
            <?php foreach (
                  [
                      ['appointment', 'tone-blue', 'Total Appointments', $today['total']],
                      ['check', 'tone-green', 'Completed Appointments', $today['Completed']],
                      ['cancel', 'tone-red', 'Cancelled Appointments', $today['Cancelled']],
                      ['clock', 'tone-amber', 'Pending Appointments', $today['Booked']]
                  ]
                  as $op
              ): ?>
            <a
                class="admin-operation <?= esc(
                       $op[1]
                   ) ?>"
                href="index.php?page=admin_records"
            ><span class="admin-dash-icon"><?= $dashIcon(
                $op[0]
            ) ?></span><span><span
                        class="admin-metric-label"><?= esc(
                            $op[2]
                        ) ?></span><strong><?= number_format($op[3]) ?></strong></span></a>
            <?php endforeach; ?>
        </div>
        <?php if (
              $today['Did not appear'] > 0
          ): ?>
        <p class="admin-no-show">Did not appear: <?= (int) $today[
            'Did not appear'
        ] ?></p><?php endif; ?>
    </section>
    <section class="admin-dash-panel">
        <div class="admin-dash-heading">
            <h2><?= $dashIcon(
                  'stock'
              ) ?>Stock Alerts</h2><a
                href="index.php?page=admin_inventory">View All</a>
        </div>
        <div class="table-wrap admin-alert-table-wrap">
            <table class="admin-alert-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Current</th>
                        <th>Minimum</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($lowItems, 0, 5) as $i): ?>
                    <tr>
                        <td><?= esc(
                            $i['item_name']
                        ) ?></td>
                        <td><?= esc($i['category']) ?></td>
                        <td><?= (int) $i['quantity'] ?></td>
                        <td><?= (int) $i[
                            'minimum_level'
                        ] ?></td>
                        <td><span class="admin-stock-badge"><?= (int) $i['quantity'] === 0
                            ? 'Out of Stock'
                            : 'Low Stock' ?></span></td>
                    </tr><?php endforeach; ?>
                    <?php if (
                        !$lowItems
                    ): ?>
                    <tr>
                        <td
                            colspan="5"
                            class="empty-cell"
                        >All stock levels are above their minimum.</td>
                    </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="admin-dash-panel">
        <div class="admin-dash-heading">
            <h2><?= $dashIcon(
                  'clock'
              ) ?>Recent Activity</h2><span class="admin-dash-date">Latest updates</span>
        </div>
        <ul class="admin-activity-list">
            <?php foreach ($recentActivity as $event):
                  $tones = [
                      'patient' => 'tone-green',
                      'appointment' => 'tone-blue',
                      'stock' => 'tone-amber',
                      'billing' => 'tone-blue'
                  ]; ?>
            <li><span class="admin-dash-icon <?= esc($tones[$event['kind']]) ?>"><?= $dashIcon(
                $event['kind']
            ) ?></span>
                <div class="admin-activity-copy">
                    <strong><?= esc(
                        $event['title']
                    ) ?></strong><span><?= esc($event['detail']) ?></span></div>
                <time datetime="<?= esc(
                    date('c', strtotime($event['event_time']))
                ) ?>"><?= esc(date('d M', strtotime($event['event_time']))) ?>
                    <br><?= esc(
                        date('h:i A', strtotime($event['event_time']))
                    ) ?>
                </time>
            </li>
            <?php
              endforeach; ?>
            <?php if (
                !$recentActivity
            ): ?>
            <li class="admin-activity-empty">No recent activity yet.</li><?php endif; ?>
        </ul>
    </section>
</div>

<?php require __DIR__ . '/../shared/bottom.php'; ?>
