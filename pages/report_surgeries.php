<?php

require_once __DIR__ . '/../config/database.php';

$page_title = 'Surgery Report';

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

$surgeries = $stmt->fetchAll();

$statusStmt = $pdo->query("
    SELECT DISTINCT status
    FROM or_schedules
    WHERE status IS NOT NULL
      AND status <> ''
    ORDER BY status
");

$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

$total_surgeries = count($surgeries);
$stat_count = 0;
$completed_count = 0;
$cancelled_count = 0;

foreach ($surgeries as $surgery) {

    if ((int)$surgery['is_stat'] === 1) {
        $stat_count++;
    }

    if (strcasecmp($surgery['status'], 'Completed') === 0) {
        $completed_count++;
    }

    if (strcasecmp($surgery['status'], 'Cancelled') === 0) {
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

<link
    rel="stylesheet"
    href="../assets/css/report_surgeries.css?v=20260919"
>

<main class="surgery-report-page">

```
<div class="report-page-header">

    <div>

        <div class="report-breadcrumb">
            Reports / Surgery Report
        </div>

        <h1>

            <span class="page-title-icon">
                <i class="bi bi-heart-pulse-fill"></i>
            </span>

            Surgery Report

        </h1>

        <p>
            View and filter scheduled surgeries and operating room activities.
        </p>

    </div>

    <button
        type="button"
        class="print-report-btn"
        onclick="window.print()"
    >
        <i class="bi bi-printer-fill"></i>
        Print Report
    </button>

</div>


<section class="report-filter-card">

    <div class="filter-card-header">

        <div class="filter-title">

            <div class="filter-icon">
                <i class="bi bi-funnel-fill"></i>
            </div>

            <div>

                <h2>
                    Report Filters
                </h2>

                <span>
                    Set the criteria for the surgery report.
                </span>

            </div>

        </div>

    </div>


    <form
        method="GET"
        action="report_surgeries.php"
        class="report-filter-form"
    >

        <div class="filter-field">

            <label for="date_from">
                Date From
            </label>

            <input
                type="date"
                id="date_from"
                name="date_from"
                value="<?= htmlspecialchars($date_from) ?>"
            >

        </div>


        <div class="filter-field">

            <label for="date_to">
                Date To
            </label>

            <input
                type="date"
                id="date_to"
                name="date_to"
                value="<?= htmlspecialchars($date_to) ?>"
            >

        </div>


        <div class="filter-field filter-field-wide">

            <label for="patient_search">
                Patient / Registry No.
            </label>

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

            <label for="status">
                Status
            </label>

            <select
                id="status"
                name="status"
            >

                <option value="">
                    All Statuses
                </option>

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

            <button
                type="submit"
                class="search-btn"
            >
                <i class="bi bi-search"></i>
                Search
            </button>

            <a
                href="report_surgeries.php"
                class="clear-btn"
            >
                <i class="bi bi-arrow-counterclockwise"></i>
                Clear
            </a>

        </div>

    </form>

</section>


<section class="summary-grid">

    <div class="summary-card">

        <div class="summary-icon">
            <i class="bi bi-calendar2-check-fill"></i>
        </div>

        <div class="summary-content">

            <span>
                Total Surgeries
            </span>

            <strong>
                <?= number_format($total_surgeries) ?>
            </strong>

        </div>

    </div>


    <div class="summary-card stat-summary">

        <div class="summary-icon">
            <i class="bi bi-lightning-charge-fill"></i>
        </div>

        <div class="summary-content">

            <span>
                STAT Cases
            </span>

            <strong>
                <?= number_format($stat_count) ?>
            </strong>

        </div>

    </div>


    <div class="summary-card completed-summary">

        <div class="summary-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>

        <div class="summary-content">

            <span>
                Completed
            </span>

            <strong>
                <?= number_format($completed_count) ?>
            </strong>

        </div>

    </div>


    <div class="summary-card cancelled-summary">

        <div class="summary-icon">
            <i class="bi bi-x-circle-fill"></i>
        </div>

        <div class="summary-content">

            <span>
                Cancelled
            </span>

            <strong>
                <?= number_format($cancelled_count) ?>
            </strong>

        </div>

    </div>

</section>


<section class="report-table-card">

    <div class="table-card-header">

        <div>

            <h2>
                Surgery Records
            </h2>

            <span>

                <?= htmlspecialchars(formatDateValue($date_from)) ?>

                <?php if ($date_from !== $date_to): ?>

                    to
                    <?= htmlspecialchars(formatDateValue($date_to)) ?>

                <?php endif; ?>

            </span>

        </div>

        <div class="record-count">

            <i class="bi bi-list-ul"></i>

            <?= number_format($total_surgeries) ?>

            record<?= $total_surgeries === 1 ? '' : 's' ?>

        </div>

    </div>


    <?php if (empty($surgeries)): ?>

        <div class="empty-report">

            <div class="empty-report-icon">
                <i class="bi bi-calendar-x"></i>
            </div>

            <h3>
                No Surgery Records Found
            </h3>

            <p>
                No surgery records match the selected report filters.
            </p>

        </div>

    <?php else: ?>

        <div class="table-responsive">

            <table class="surgery-report-table">

                <thead>

                    <tr>

                        <th>
                            Surgery Date
                        </th>

                        <th>
                            Time
                        </th>

                        <th>
                            Patient
                        </th>

                        <th>
                            Registry No.
                        </th>

                        <th>
                            Procedure
                        </th>

                        <th>
                            Surgical Type
                        </th>

                        <th>
                            Surgeon
                        </th>

                        <th>
                            Anesthesiologist
                        </th>

                        <th>
                            Operating Room
                        </th>

                        <th>
                            Priority
                        </th>

                        <th>
                            STAT
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($surgeries as $surgery): ?>

                        <tr>

                            <td>

                                <div class="date-cell">

                                    <strong>
                                        <?= htmlspecialchars(
                                            formatDateValue(
                                                $surgery['surgery_date']
                                            )
                                        ) ?>
                                    </strong>

                                </div>

                            </td>


                            <td>

                                <div class="time-cell">

                                    <span>
                                        <?= htmlspecialchars(
                                            formatTimeValue(
                                                $surgery['start_time']
                                            )
                                        ) ?>
                                    </span>

                                    <small>
                                        -
                                        <?= htmlspecialchars(
                                            formatTimeValue(
                                                $surgery['end_time']
                                            )
                                        ) ?>
                                    </small>

                                </div>

                            </td>


                            <td>

                                <div class="patient-cell">

                                    <strong>
                                        <?= htmlspecialchars(
                                            formatPatientName(
                                                $surgery['first_name'],
                                                $surgery['middle_name'],
                                                $surgery['last_name']
                                            )
                                        ) ?>
                                    </strong>

                                    <?php if (!empty($surgery['patient_number'])): ?>

                                        <small>
                                            <?= htmlspecialchars(
                                                $surgery['patient_number']
                                            ) ?>
                                        </small>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $surgery['patient_registry_no']
                                        ?: $surgery['patient_number']
                                        ?: '-'
                                ) ?>

                            </td>


                            <td>

                                <div class="procedure-cell">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $surgery['procedure_name']
                                        ) ?>
                                    </strong>

                                </div>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $surgery['procedure_surgical_type']
                                        ?: '-'
                                ) ?>

                            </td>


                            <td>

                                <div class="doctor-cell">

                                    <i class="bi bi-person-badge-fill"></i>

                                    <span>

                                        <?= htmlspecialchars(
                                            formatDoctorName(
                                                $surgery['surgeon_first_name'],
                                                $surgery['surgeon_middle_name'],
                                                $surgery['surgeon_last_name'],
                                                $surgery['surgeon_suffix_name']
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            </td>


                            <td>

                                <?php if (
                                    !empty(
                                        $surgery[
                                            'anesthesiologist_first_name'
                                        ]
                                    )
                                ): ?>

                                    <div class="doctor-cell">

                                        <i class="bi bi-person-badge"></i>

                                        <span>

                                            <?= htmlspecialchars(
                                                formatDoctorName(
                                                    $surgery[
                                                        'anesthesiologist_first_name'
                                                    ],
                                                    $surgery[
                                                        'anesthesiologist_middle_name'
                                                    ],
                                                    $surgery[
                                                        'anesthesiologist_last_name'
                                                    ],
                                                    $surgery[
                                                        'anesthesiologist_suffix_name'
                                                    ]
                                                )
                                            ) ?>

                                        </span>

                                    </div>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Not assigned
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <div class="room-cell">

                                    <i class="bi bi-door-open-fill"></i>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $surgery['room_name']
                                        ) ?>
                                    </strong>

                                </div>

                            </td>


                            <td>

                                <?php
                                $priority = $surgery['priority']
                                    ?: 'Elective';

                                $priority_class =
                                    strtolower($priority);
                                ?>

                                <span
                                    class="priority-badge priority-<?= htmlspecialchars(
                                        $priority_class
                                    ) ?>"
                                >
                                    <?= htmlspecialchars($priority) ?>
                                </span>

                            </td>


                            <td>

                                <?php if (
                                    (int)$surgery['is_stat'] === 1
                                ): ?>

                                    <span class="stat-badge">

                                        <i class="bi bi-lightning-charge-fill"></i>

                                        STAT

                                    </span>

                                <?php else: ?>

                                    <span class="not-stat">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span
                                    class="status-badge <?= htmlspecialchars(
                                        statusClass(
                                            $surgery['status']
                                        )
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $surgery['status']
                                    ) ?>
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

    <span>
        <i class="bi bi-info-circle"></i>
        Report generated from the OR Scheduling System.
    </span>

    <span>
        Generated:
        <?= date('M d, Y h:i A') ?>
    </span>

</div>
```

</main>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    if (dateFrom && dateTo) {

        dateFrom.addEventListener('change', function () {

            if (
                dateFrom.value &&
                dateTo.value < dateFrom.value
            ) {
                dateTo.value = dateFrom.value;
            }

        });

    }

});

</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
