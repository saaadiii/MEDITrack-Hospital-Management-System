<?php
// Dashboard-only derived lists. Never replace the full appointments or slots arrays.
$today = local_today();
$dashboardSpecialties = [];
foreach ($doctors as $doctor) {
    $dashboardSpecialties[(int) $doctor['id']] = trim((string) ($doctor['specialization'] ?? ''));
}
$todayAppointments = array_values(
    array_filter($appointments, fn($a) => $a['appointment_date'] === $today)
);
usort($todayAppointments, fn($a, $b) => strcmp($a['appointment_time'], $b['appointment_time']));
$dashboardDoctors = [];
foreach ($doctors as $doctor) {
    if ($doctor['status'] !== 'Active') {
        continue;
    }
    $doctorSlots = array_values(
        array_filter(
            $slots,
            fn($slot) => $slot['slot_date'] === $today &&
                (int) $slot['doctor_id'] === (int) $doctor['id']
        )
    );
    if (!$doctorSlots) {
        continue;
    }
    $remaining = array_values(
        array_filter($doctorSlots, fn($slot) => $slot['start_time'] >= date('H:i:s'))
    );
    $available = count(array_filter($remaining, fn($slot) => $slot['status'] === 'Available'));
    $booked = count(array_filter($remaining, fn($slot) => $slot['status'] === 'Booked'));
    $doctor['available_count'] = $available;
    $doctor['slot_label'] =
        $available > 0
            ? $available . ' slot' . ($available === 1 ? '' : 's') . ' available'
            : (!$remaining
                ? 'No remaining slots'
                : ($booked === count($remaining)
                    ? 'Fully booked'
                    : 'No available slots'));
    $dashboardDoctors[] = $doctor;
}
?>
<div class="reception-overview">
    <section class="reception-overview-panel">
        <div class="reception-overview-heading">
            <h2>Today's Appointments</h2><a
                href="index.php?page=receptionist_appointments"
            >View All</a>
        </div>
        <div class="reception-overview-filters">
            <input
                type="search"
                id="reception-today-search"
                placeholder="Search patient name or ID"
                aria-label="Search today's appointments by patient name or ID"
                autocomplete="off"
            >
            <select
                id="reception-today-status"
                aria-label="Filter today's appointments by status"
            >
                <option value="">All statuses</option><?php foreach (
                       ['Booked', 'Completed', 'Cancelled', 'Did not appear']
                       as $status
                   ): ?>
                <option><?= esc($status) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="table-wrap">
            <table class="reception-today-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="reception-today-rows">
                    <?php foreach ($todayAppointments as $index => $a):
                    
                           $patientCode = patient_display_id($a['patient_id']);
                           $tone =
                               [
                                   'Booked' => 'blue',
                                   'Completed' => 'green',
                                   'Cancelled' => 'red',
                                   'Did not appear' => 'amber'
                               ][$a['status']] ?? 'blue';
                           ?>
                    <tr
                        data-patient="<?= esc($a['patient_name'] . ' ' . $patientCode) ?>"
                        data-status="<?= esc(
                            $a['status']
                        ) ?>"
                    >
                        <td class="reception-time"><?= esc(date('h:i A', strtotime($a['appointment_time']))) ?></td>
                        <td><?= esc($a['patient_name']) ?><small><?= esc($patientCode) ?></small></td>
                        <td><?= esc($a['doctor_name']) ?>
                            <?php if (($dashboardSpecialties[(int) $a['doctor_id']] ?? '') !== ''): ?><small><?= esc(
                                $dashboardSpecialties[(int) $a['doctor_id']]
                            ) ?></small><?php endif; ?>
                        </td>
                        <td><span
                                class="reception-status reception-status-<?= esc($tone) ?>"><?= esc(
                                    $a['status']
                                ) ?></span>
                        </td>
                    </tr>
                    <?php
                       endforeach; ?>
                    <tr
                        id="reception-today-empty"
                        <?= $todayAppointments
                                ? 'hidden'
                                : '' ?>
                    >
                        <td
                            colspan="4"
                            class="empty-cell"
                        >No appointments scheduled for today.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="reception-overview-footer"><span
                id="reception-today-count"
                role="status"
            >Showing <?= count(
                  $todayAppointments
              ) ?> of <?= count($todayAppointments) ?> appointments</span></div>
    </section>
    <section class="reception-overview-panel">
        <div class="reception-overview-heading">
            <h2>Doctor Slots Today</h2><a
                href="index.php?page=receptionist_slots&amp;date=<?= esc(
                      $today
                  ) ?>"
            >View All</a>
        </div>
        <div class="reception-doctor-list">
            <?php foreach ($dashboardDoctors as $doctor): ?>
            <a
                class="reception-doctor-row"
                href="index.php?page=receptionist_slots&amp;date=<?= esc(
                        $today
                    ) ?>&amp;doctor_id=<?= (int) $doctor['id'] ?>"
                aria-label="<?= esc(
                    'View today’s slots for ' . $doctor['name']
                ) ?>"
            >
                <span
                    class="reception-doctor-icon"
                    aria-hidden="true"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    >
                        <circle
                            cx="12"
                            cy="7"
                            r="4"
                        />
                        <path d="M4 22v-3a8 8 0 0 1 16 0v3M8 14v5m8-5v3m-10 5v-2a2 2 0 0 1 4 0v2" />
                        <circle
                            cx="16"
                            cy="19"
                            r="2"
                        />
                    </svg>
                </span>
                <span
                    class="reception-doctor-name"><strong><?= esc($doctor['name']) ?></strong><small><?= esc(
                        $doctor['specialization']
                    ) ?></small></span>
                <span class="reception-status reception-status-<?= $doctor['available_count'] > 0
                         ? 'green'
                         : 'red' ?>"><?= esc($doctor['slot_label']) ?></span>
            </a>
            <?php endforeach; ?>
            <?php if (
                !$dashboardDoctors
            ): ?>
            <p class="empty-cell">No doctor slots scheduled for today.</p><?php endif; ?>
        </div>
    </section>
</div>
