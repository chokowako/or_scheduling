<?php

require_once __DIR__ . '/../config/database.php';

$page_title = 'OR Schedule Report';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$patient_search = trim($_GET['patient_search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$sql = "
    SELECT
        os.schedule_id,
        os.surgery_date,
        os.date_end,
        os.start_time,
        os.end_time,
        os.patient_registry_no,
        os.priority,
        os.is_stat,
        os.status,

        p.patient_number,
        p.first_name,
        p.middle_name,
        p.last_name,

        pr.procedure_name,
        pr.surgical_type AS procedure_surgical_type,

        surgeon.first_name AS surgeon_first_name,
        surgeon.middle_name AS surgeon_middle_name,
        surgeon.last_name AS surgeon_last_name,
        surgeon.suffix_name AS surgeon_suffix_name,

        anesthesiologist.first_name AS anesthesiologist_first_name,
        anesthesiologist.middle_name AS anesthesiologist_middle_name,
        anesthesiologist.last_name AS anesthesiologist_last_name,
        anesthesiologist.suffix_name AS anesthesiologist_suffix_name,

        r.room_name

    FROM or_schedules os

    INNER JOIN patients p
        ON os.patient_id = p.patient_id

    INNER JOIN procedures pr
        ON os.procedure_id = pr.procedure_id

    INNER JOIN doctors surgeon
        ON os.surgeon_doctor_id = surgeon.doctor_id

    LEFT JOIN doctors anesthesiologist
        ON os.anesthesiologist_doctor_id = anesthesiologist.doctor_id

    INNER JOIN operating_rooms r
        ON os.room_id = r.room_id

    WHERE os.surgery_date BETWEEN :date_from AND :date_to
";

$params = [
    ':date_from' => $date_from,
    ':date_to' => $date_to
];

if ($patient_search !== '') {
    $sql .= "
        AND (
            CONCAT(
                p.first_name,
                ' ',
                COALESCE(p.middle_name, ''),
                ' ',
                p.last_name
            ) LIKE :patient_search

            OR p.patient_number LIKE :patient_search

            OR os.patient_registry_no LIKE :patient_search
        )
    ";

    $params[':patient_search'] = '%' . $patient_search . '%';
}

if ($status_filter !== '') {
    $sql .= "
        AND os.status = :status
    ";

    $params[':status'] = $status_filter;
}

$sql .= "
    ORDER BY
        os.surgery_date ASC,
        os.start_time ASC,
        r.room_name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$schedules = $stmt->fetchAll();

$statusStmt = $pdo->query("
    SELECT DISTINCT status
    FROM or_schedules
    WHERE status IS NOT NULL
      AND status <> ''
    ORDER BY status
");

$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

$total_schedules = count($schedules);
$stat_count = 0;
$scheduled_count = 0;
$completed_count = 0;
$cancelled_count = 0;

foreach ($schedules as $schedule) {
    if ((int)$schedule['is_stat'] === 1) {
        $stat_count++;
    }

    if (strcasecmp($schedule['status'], 'Scheduled') === 0) {
        $scheduled_count++;
    }

    if (strcasecmp($schedule['status'], 'Completed') === 0) {
        $completed_count++;
    }

    if (strcasecmp($schedule['status'], 'Cancelled') === 0) {
        $cancelled_count++;
    }
}

function formatDoctorName(
    $first_name,
    $middle_name,
    $last_name,
    $suffix_name = null
) {
    $name = trim(
        $first_name . ' ' .
        ($middle_name ? $middle_name . ' ' : '') .
        $last_name
    );

    if (!empty($suffix_name)) {
        $name .= ', ' . $suffix_name;
    }

    return $name;
}

function formatPatientName(
    $first_name,
    $middle_name,
    $last_name
) {
    return trim(
        $first_name . ' ' .
        ($middle_name ? $middle_name . ' ' : '') .
        $last_name
    );
}

function formatTimeValue($time)
{
    if (empty($time)) {
        return '-';
    }

    return date('g:i A', strtotime($time));
}

function formatDateValue($date)
{
    if (empty($date)) {
        return '-';
    }

    return date('M d, Y', strtotime($date));
}

function statusClass($status)
{
    $status = strtolower(trim($status));

    switch ($status) {
        case 'scheduled':
            return 'status-scheduled';

        case 'confirmed':
            return 'status-confirmed';

        case 'in progress':
            return 'status-progress';

        case 'completed':
            return 'status-completed';

        case 'cancelled':
            return 'status-cancelled';

        case 'rescheduled':
            return 'status-rescheduled';

        default:
            return 'status-default';
    }
}

?>

<link rel="stylesheet" href="../assets/css/report_or_schedule.css?v=<?= time(); ?>">

<main class="schedule-report-page">

    <div class="report-page-header">
        <div>
            <div class="report-breadcrumb">
                Reports / OR Schedule Report
            </div>
            <h1>
                <span class="page-title-icon">
                    <i class="bi bi-calendar-event-fill"></i>
                </span>
                OR Schedule Report
            </h1>
            <p>
                View and filter operating room daily schedules and resource allocation.
            </p>
        </div>
    </div>

    <section class="report-filter-card">

        <div class="filter-card-header">
            <div class="filter-title">
                <div class="filter-icon">
                    <i class="bi bi-funnel-fill"></i>
                </div>
                <div>
                    <h2>Report Filters</h2>
                    <span>Set the criteria for the OR schedule report.</span>
                </div>
            </div>
        </div>

        <form method="GET" action="report_or_schedule.php" class="report-filter-form">

            <div class="filter-field">
                <label for="date_from">Date From</label>
                <input
                    type="date"
                    id="date_from"
                    name="date_from"
                    value="<?= htmlspecialchars($date_from) ?>"
                >
            </div>

            <div class="filter-field">
                <label for="date_to">Date To</label>
                <input
                    type="date"
                    id="date_to"
                    name="date_to"
                    value="<?= htmlspecialchars($date_to) ?>"
                >
            </div>

            <div class="filter-field filter-field-wide">
                <label for="patient_search">Patient / Registry No.</label>
                <div class="input-with-icon">
                    <i class="bi bi-search"></i>
                    <input
                        type="text"
                        id="patient_search"
                        name="patient_search"
                        value="<?= htmlspecialchars($patient_search) ?>"
                        placeholder="Search patient or registry number"
                    >
                </div>
            </div>

            <div class="filter-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $status): ?>
                        <option
                            value="<?= htmlspecialchars($status) ?>"
                            <?= $status_filter === $status ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-action btn-filter">
                    <i class="bi bi-funnel-fill"></i>
                    <span>Filter</span>
                </button>

                <a href="report_or_schedule.php" class="btn-action btn-reset">
                    <i class="bi bi-x-lg"></i>
                    <span>Reset</span>
                </a>

                <button type="button" class="btn-action btn-export" onclick="exportToCSV()">
                    <i class="bi bi-download"></i>
                    <span>Export</span>
                </button>

                <button type="button" class="btn-action btn-print" onclick="window.print()">
                    <i class="bi bi-printer"></i>
                    <span>Print</span>
                </button>
            </div>

        </form>

    </section>

    <!-- Polished Summary Cards Grid -->
    <section class="summary-grid">

        <div class="summary-card">
            <div class="summary-icon icon-scheduled">
                <i class="bi bi-calendar2-check-fill"></i>
            </div>
            <div class="summary-content">
                <span>Total Scheduled</span>
                <strong><?= number_format($total_schedules) ?></strong>
            </div>
        </div>

        <div class="summary-card stat-summary">
            <div class="summary-icon icon-stat">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div class="summary-content">
                <span>STAT Cases</span>
                <strong><?= number_format($stat_count) ?></strong>
            </div>
        </div>

        <div class="summary-card completed-summary">
            <div class="summary-icon icon-completed">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="summary-content">
                <span>Completed</span>
                <strong><?= number_format($completed_count) ?></strong>
            </div>
        </div>

        <div class="summary-card cancelled-summary">
            <div class="summary-icon icon-cancelled">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div class="summary-content">
                <span>Cancelled</span>
                <strong><?= number_format($cancelled_count) ?></strong>
            </div>
        </div>

    </section>

    <!-- Table Section -->
    <section class="report-table-card">

        <div class="table-card-header">
            <div>
                <h2>OR Schedule Records</h2>
                <span>
                    Date Range: <?= htmlspecialchars(formatDateValue($date_from)) ?>
                    <?php if ($date_from !== $date_to): ?>
                        to <?= htmlspecialchars(formatDateValue($date_to)) ?>
                    <?php endif; ?>

                    | <strong>Patient / Registry No.:</strong> <?= !empty($patient_search) ? htmlspecialchars($patient_search) : 'All' ?>
                    | <strong>Status:</strong> <?= !empty($status_filter) ? htmlspecialchars($status_filter) : 'All Statuses' ?>
                </span>
            </div>
            <div class="record-count">
                <i class="bi bi-list-ul"></i>
                <?= number_format($total_schedules) ?> record<?= $total_schedules === 1 ? '' : 's' ?>
            </div>
        </div>

        <?php if (empty($schedules)): ?>

            <div class="empty-report">
                <div class="empty-report-icon">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <h3>No Schedule Records Found</h3>
                <p>No OR schedule records match the selected report filters.</p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="schedule-report-table">
                    <thead>
                        <tr>
                            <th>Surgery Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Procedure</th>
                            <th>Surgical Type</th>
                            <th>Surgeon</th>
                            <th>Anesthesiologist</th>
                            <th>Operating Room</th>
                            <th>Priority</th>
                            <th>STAT</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td>
                                    <div class="date-cell">
                                        <strong><?= htmlspecialchars(formatDateValue($schedule['surgery_date'])) ?></strong>
                                    </div>
                                </td>
                                <td>
                                 <div class="time-cell">
							<span><?= htmlspecialchars(formatTimeValue($schedule['start_time'])) ?></span>
							<small><?= htmlspecialchars(formatTimeValue($schedule['end_time'])) ?></small>
						</div>
                                </td>
                                <td>
                                    <div class="patient-cell">
                                        <strong><?= htmlspecialchars(formatPatientName($schedule['first_name'], $schedule['middle_name'], $schedule['last_name'])) ?></strong>
                                        <?php if (!empty($schedule['patient_registry_no'])): ?>
                                            <small>Reg: <?= htmlspecialchars($schedule['patient_registry_no']) ?></small>
                                        <?php elseif (!empty($schedule['patient_number'])): ?>
                                            <small>PN: <?= htmlspecialchars($schedule['patient_number']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="procedure-cell">
                                        <strong><?= htmlspecialchars($schedule['procedure_name']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($schedule['procedure_surgical_type'] ?: '-') ?>
                                </td>
                                <td>
                                    <div class="doctor-cell">
                                        <i class="bi bi-person-badge-fill"></i>
                                        <span><?= htmlspecialchars(formatDoctorName($schedule['surgeon_first_name'], $schedule['surgeon_middle_name'], $schedule['surgeon_last_name'], $schedule['surgeon_suffix_name'])) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($schedule['anesthesiologist_first_name'])): ?>
                                        <div class="doctor-cell">
                                            <i class="bi bi-person-badge"></i>
                                            <span><?= htmlspecialchars(formatDoctorName($schedule['anesthesiologist_first_name'], $schedule['anesthesiologist_middle_name'], $schedule['anesthesiologist_last_name'], $schedule['anesthesiologist_suffix_name'])) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="room-cell">
                                        <i class="bi bi-door-open-fill"></i>
                                        <strong><?= htmlspecialchars($schedule['room_name']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $priority = $schedule['priority'] ?: 'Elective';
                                    $priority_class = strtolower($priority);
                                    ?>
                                    <span class="priority-badge priority-<?= htmlspecialchars($priority_class) ?>">
                                        <?= htmlspecialchars($priority) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ((int)$schedule['is_stat'] === 1): ?>
                                        <span class="stat-badge">
                                            <i class="bi bi-lightning-charge-fill"></i> STAT
                                        </span>
                                    <?php else: ?>
                                        <span class="not-stat">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= htmlspecialchars(statusClass($schedule['status'])) ?>">
                                        <?= htmlspecialchars($schedule['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

    <div class="report-footnote">
        <span><i class="bi bi-info-circle"></i> Report generated from the OR Scheduling System.</span>
        <span>Printed by: <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'System User') ?> | Generated: <?= date('M d, Y h:i A') ?></span>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    if (dateFrom && dateTo) {
        dateFrom.addEventListener('change', function () {
            if (dateFrom.value && dateTo.value < dateFrom.value) {
                dateTo.value = dateFrom.value;
            }
        });
    }
});

function exportToCSV() {
    let table = document.querySelector(".schedule-report-table");
    if (!table) return;

    let rows = table.querySelectorAll("tr");
    let csv = [];

    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ").trim();
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(","));
    }

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "OR_Schedule_Report_" + new Date().toISOString().slice(0,10) + ".csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>