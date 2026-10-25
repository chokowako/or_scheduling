<?php

require_once __DIR__ . '/../config/database.php';

$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$patient_search = trim($_GET['patient_search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

// Build SQL WHERE clauses and parameters
$where_clauses = ["os.surgery_date BETWEEN :date_from AND :date_to"];
$params = [
    ':date_from' => $date_from,
    ':date_to' => $date_to
];

if ($patient_search !== '') {
    $where_clauses[] = "(
        CONCAT(
            p.first_name,
            ' ',
            COALESCE(p.middle_name, ''),
            ' ',
            p.last_name
        ) LIKE :patient_search
        OR p.patient_number LIKE :patient_search
        OR os.patient_registry_no LIKE :patient_search
    )";
    $params[':patient_search'] = '%' . $patient_search . '%';
}

if ($status_filter !== '') {
    $where_clauses[] = "os.status = :status";
    $params[':status'] = $status_filter;
}

$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// =========================================================================
// 1. BACKEND EXPORT / DATA STREAMING (MUST RUN BEFORE HTML HEADERS)
// =========================================================================
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Select ALL matching records WITHOUT pagination limit
    $export_sql = "SELECT
        os.surgery_date,
        os.start_time,
        os.end_time,
        p.first_name,
        p.middle_name,
        p.last_name,
        p.patient_number,
        os.patient_registry_no,
        pr.procedure_name,
        pr.surgical_type AS procedure_surgical_type,
        surgeon.first_name AS surgeon_first_name,
        surgeon.last_name AS surgeon_last_name,
        anesthesiologist.first_name AS anesthesiologist_first_name,
        anesthesiologist.last_name AS anesthesiologist_last_name,
        r.room_name,
        os.priority,
        os.is_stat,
        os.status,
        os.pre_op_diagnosis,
        os.post_op_diagnosis,
        os.drains,
        os.specimen_lab_exam,
        os.sponge_count_verified
    FROM or_schedules os
    INNER JOIN patients p ON os.patient_id = p.patient_id
    INNER JOIN procedures pr ON os.procedure_id = pr.procedure_id
    INNER JOIN doctors surgeon ON os.surgeon_doctor_id = surgeon.doctor_id
    LEFT JOIN doctors anesthesiologist ON os.anesthesiologist_doctor_id = anesthesiologist.doctor_id
    LEFT JOIN doctors cardiologist ON os.cardiologist_id = cardiologist.doctor_id
    INNER JOIN operating_rooms r ON os.room_id = r.room_id
    {$where_sql}
    ORDER BY os.surgery_date ASC, os.start_time ASC, r.room_name ASC";

    $export_stmt = $pdo->prepare($export_sql);
    foreach ($params as $key => $val) {
        $export_stmt->bindValue($key, $val);
    }
    $export_stmt->execute();
    $all_export_surgeries = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Option A: JSON Endpoint for PDF Print All
    if ($export_type === 'json') {
        header('Content-Type: application/json');
        echo json_encode($all_export_surgeries);
        exit;
    }

    // Option B: CSV & Excel Backend Downloads
    if ($export_type === 'csv' || $export_type === 'excel') {
        $is_excel = ($export_type === 'excel');
        $ext = $is_excel ? 'xls' : 'csv';
        $content_type = $is_excel ? 'application/vnd.ms-excel' : 'text/csv; charset=utf-8';
        
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename=surgery_report_ALL_' . date('Y-m-d') . '.' . $ext);
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, [
            'Surgery Date', 'Start Time', 'End Time', 'Patient Name', 'Patient Number', 
            'Procedure', 'Surgical Type', 'Surgeon', 'Anesthesiologist', 'Operating Room', 
            'Priority', 'STAT', 'Status', 'Pre-Op Diagnosis', 'Post-Op Diagnosis', 
            'Drains', 'Specimen/Lab Exam', 'Sponge & Count Verified'
        ], ",", '"', "");
        
        foreach ($all_export_surgeries as $s) {
            fputcsv($output, [
                $s['surgery_date'],
                $s['start_time'],
                $s['end_time'],
                trim($s['first_name'] . ' ' . ($s['middle_name'] ? $s['middle_name'] . ' ' : '') . $s['last_name']),
                $s['patient_number'],
                $s['procedure_name'],
                $s['procedure_surgical_type'],
                trim($s['surgeon_first_name'] . ' ' . $s['surgeon_last_name']),
                trim($s['anesthesiologist_first_name'] . ' ' . $s['anesthesiologist_last_name']),
                $s['room_name'],
                $s['priority'] ?: 'Elective',
                (int)$s['is_stat'] === 1 ? 'YES' : 'NO',
                $s['status'],
                $s['pre_op_diagnosis'],
                $s['post_op_diagnosis'],
                $s['drains'],
                $s['specimen_lab_exam'],
                $s['sponge_count_verified']
            ], ",", '"', "");
        }
        fclose($output);
        exit;
    }
}

// =========================================================================
// 2. REGULAR PAGE RENDERING (HTML HEADERS LOAD HERE)
// =========================================================================
$page_title = 'Surgery Report';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// --- PAGINATION CONFIGURATION ---
$records_per_page = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// Count Total Records & Summary Statistics
$count_sql = "
    SELECT 
        COUNT(*) AS total_records,
        SUM(CASE WHEN os.is_stat = 1 THEN 1 ELSE 0 END) AS stat_count,
        SUM(CASE WHEN LOWER(os.status) = 'completed' THEN 1 ELSE 0 END) AS completed_count,
        SUM(CASE WHEN LOWER(os.status) = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
    FROM or_schedules os
    INNER JOIN patients p ON os.patient_id = p.patient_id
    INNER JOIN procedures pr ON os.procedure_id = pr.procedure_id
    INNER JOIN doctors surgeon ON os.surgeon_doctor_id = surgeon.doctor_id
    LEFT JOIN doctors anesthesiologist ON os.anesthesiologist_doctor_id = anesthesiologist.doctor_id
    LEFT JOIN doctors cardiologist ON os.cardiologist_id = cardiologist.doctor_id
    INNER JOIN operating_rooms r ON os.room_id = r.room_id
    {$where_sql}
";

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$totals = $count_stmt->fetch();

$total_surgeries = (int)($totals['total_records'] ?? 0);
$stat_count      = (int)($totals['stat_count'] ?? 0);
$completed_count = (int)($totals['completed_count'] ?? 0);
$cancelled_count = (int)($totals['cancelled_count'] ?? 0);

// Calculate total pages & offset
$total_pages = $total_surgeries > 0 ? (int)ceil($total_surgeries / $records_per_page) : 1;
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $records_per_page;

// Fetch Paginated Display Records
$sql = "SELECT
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
    os.birth_date,
    os.registry_type,

    pr.procedure_name,
    pr.surgical_type AS procedure_surgical_type,
    os.anesthetic,

    surgeon.first_name AS surgeon_first_name,
    surgeon.middle_name AS surgeon_middle_name,
    surgeon.last_name AS surgeon_last_name,
    surgeon.suffix_name AS surgeon_suffix_name,

    anesthesiologist.first_name AS anesthesiologist_first_name,
    anesthesiologist.middle_name AS anesthesiologist_middle_name,
    anesthesiologist.last_name AS anesthesiologist_last_name,
    anesthesiologist.suffix_name AS anesthesiologist_suffix_name,
    
    TRIM(CONCAT(
        COALESCE(anesthesiologist.first_name, ''), ' ',
        CASE WHEN anesthesiologist.middle_name IS NOT NULL AND anesthesiologist.middle_name != '' THEN CONCAT(LEFT(anesthesiologist.middle_name, 1), '. ') ELSE '' END,
        COALESCE(anesthesiologist.last_name, ''),
        CASE WHEN anesthesiologist.suffix_name IS NOT NULL AND anesthesiologist.suffix_name != '' THEN CONCAT(', ', anesthesiologist.suffix_name) ELSE '' END
    )) AS anesthesiologist_full_name,
    
    TRIM(CONCAT(
        COALESCE(cardiologist.first_name, ''), ' ',
        CASE WHEN cardiologist.middle_name IS NOT NULL AND cardiologist.middle_name != '' THEN CONCAT(LEFT(cardiologist.middle_name, 1), '. ') ELSE '' END,
        COALESCE(cardiologist.last_name, ''),
        CASE WHEN cardiologist.suffix_name IS NOT NULL AND cardiologist.suffix_name != '' THEN CONCAT(', ', cardiologist.suffix_name) ELSE '' END
    )) AS cardiologist,
    
    (
        SELECT GROUP_CONCAT(
            TRIM(CONCAT(
                COALESCE(asst_doc.first_name, ''), ' ',
                CASE WHEN asst_doc.middle_name IS NOT NULL AND asst_doc.middle_name != '' THEN CONCAT(LEFT(asst_doc.middle_name, 1), '. ') ELSE '' END,
                COALESCE(asst_doc.last_name, ''),
                CASE WHEN asst_doc.suffix_name IS NOT NULL AND asst_doc.suffix_name != '' THEN CONCAT(', ', asst_doc.suffix_name) ELSE '' END
            )) 
            ORDER BY osa.assistant_order ASC 
            SEPARATOR '<br>'
        )
        FROM or_schedule_assistants osa
        INNER JOIN doctors asst_doc ON osa.doctor_id = asst_doc.doctor_id
        WHERE osa.schedule_id = os.schedule_id
    ) AS assistant_surgeon_full_name,
        
    os.patient_room_no,
    r.room_name,
    
    os.circulating_nurse,
    os.instrument_nurse,
        
    os.pre_op_diagnosis,
    os.post_op_diagnosis,
    os.drains,
    os.specimen_lab_exam,
    os.sponge_count_verified,
    os.remarks,
    os.infections

FROM or_schedules os

INNER JOIN patients p ON os.patient_id = p.patient_id
INNER JOIN procedures pr ON os.procedure_id = pr.procedure_id
INNER JOIN doctors surgeon ON os.surgeon_doctor_id = surgeon.doctor_id
LEFT JOIN doctors anesthesiologist ON os.anesthesiologist_doctor_id = anesthesiologist.doctor_id
LEFT JOIN doctors cardiologist ON os.cardiologist_id = cardiologist.doctor_id
INNER JOIN operating_rooms r ON os.room_id = r.room_id

{$where_sql}

ORDER BY
    os.surgery_date ASC,
    os.start_time ASC,
    r.room_name ASC

LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$surgeries = $stmt->fetchAll();

$statusStmt = $pdo->query("
    SELECT DISTINCT status
    FROM or_schedules
    WHERE status IS NOT NULL
      AND status <> ''
    ORDER BY status
");

$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

function formatDoctorName($first_name, $middle_name, $last_name, $suffix_name = null) {
    if (empty($last_name)) return 'Not assigned';
    $name = trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);
    if (!empty($suffix_name)) {
        $name .= ', ' . $suffix_name;
    }
    return $name;
}

function formatPatientName($first_name, $middle_name, $last_name) {
    return trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);
}

function formatTimeValue($time) {
    if (empty($time)) return '-';
    return date('g:i A', strtotime($time));
}

function formatDateValue($date) {
    if (empty($date)) return '-';
    return date('M d, Y', strtotime($date));
}

function statusClass($status) {
    $status = strtolower(trim($status));
    switch ($status) {
        case 'scheduled': return 'status-scheduled';
        case 'confirmed': return 'status-confirmed';
        case 'in progress': return 'status-progress';
        case 'completed': return 'status-completed';
        case 'cancelled': return 'status-cancelled';
        case 'rescheduled': return 'status-rescheduled';
        default: return 'status-default';
    }
}

function getPaginationUrl($page_number) {
    $query = $_GET;
    $query['page'] = $page_number;
    return '?' . http_build_query($query);
}

?>

<link rel="stylesheet" href="../assets/css/report_surgeries.css?v=20260920">

<style>
/* --- Pagination Component Styles --- */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background-color: #f8fafc;
    border-top: 1px solid #e2e8f0;
    border-radius: 0 0 8px 8px;
    font-size: 14px;
    color: #64748b;
}

.page-info-left {
    font-size: 14px;
    color: #64748b;
}

.page-info-left strong {
    color: #1e293b;
    font-weight: 600;
}

.pagination-nav-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-page-nav {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 14px;
    font-size: 13.5px;
    font-weight: 500;
    text-decoration: none;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background-color: #f1f5f9;
    color: #3b82f6;
    transition: all 0.15s ease-in-out;
}

a.btn-page-nav:hover {
    background-color: #e2e8f0;
    border-color: #94a3b8;
    color: #1d4ed8;
}

a.btn-page-nav.active-next,
a.btn-page-nav.active-last {
    color: #0d9488;
    background-color: #ffffff;
    border-color: #cbd5e1;
}

a.btn-page-nav.active-next:hover,
a.btn-page-nav.active-last:hover {
    background-color: #f8fafc;
    border-color: #94a3b8;
}

.btn-page-nav.disabled {
    color: #94a3b8;
    background-color: #f8fafc;
    border-color: #e2e8f0;
    cursor: not-allowed;
    opacity: 0.7;
}

.current-page-text {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
    padding: 0 4px;
}
</style>

<main class="surgery-report-page">

    <!-- TOPBAR -->
    <header class="topbar dashboard-header-style">               
        <div class="header-left-group">
            <button
                type="button"
                class="mobile-menu-btn"
                onclick="document.querySelector('.sidebar')?.classList.toggle('show')"
                aria-label="Open menu"
            >
                <i class="bi bi-list"></i>
            </button>
            
            <div class="header-text-block"> 
                <h2>
                    <i class="bi bi-heart-pulse-fill"></i> OR Schedule Report
                </h2>
                
                <p class="header-subtitle">
                    Review completed procedures, post-operative outcomes, clinical logs, and historical surgical case details over a selected period.
                </p>
            </div>                  
        </div>

        <div class="header-right-meta">
            <div class="required-note-badge">
                <span class="required-asterisk">*</span> Required fields
            </div>
        </div>                            
    </header>

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

    <form method="GET" action="" class="report-filter-form-inline" id="filterForm">
        <div class="filter-field">
            <label for="date_from">Date From</label>
            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
        </div>

        <div class="filter-field">
            <label for="date_to">Date To</label>
            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
        </div>

        <div class="filter-field filter-field-wide">
            <label for="patient_search">Patient / Registry No.</label>
            <div class="input-with-icon">
                <i class="bi bi-search"></i>
                <input type="text" id="patient_search" name="patient_search" value="<?= htmlspecialchars($patient_search) ?>" placeholder="Search patient or registry number">
            </div>
        </div>

        <div class="filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= $status_filter === $status ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions-inline">
            <button type="submit" class="filter-btn-custom"><i class="bi bi-funnel-fill"></i> Filter </button>
            <button type="button" class="reset-btn-custom" onclick="resetFilters()"><i class="bi bi-x-lg"></i> Reset</button>    
            <button type="button" class="export-btn-custom" onclick="openExportModal()"><i class="bi bi-download"></i> Export</button>
            <button type="button" class="print-btn-custom" onclick="exportAsPDF()"><i class="bi bi-printer-fill"></i> Print All</button>
        </div>
    </form>
</section>

<section class="summary-grid">
    <div class="summary-card">
        <div class="summary-icon bg-emerald-soft text-emerald"><i class="bi bi-calendar2-check-fill"></i></div>
        <div class="summary-content">
            <span>Total Scheduled</span>
            <strong><?= number_format($total_surgeries) ?></strong>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-icon bg-rose-soft text-rose"><i class="bi bi-lightning-charge-fill"></i></div>
        <div class="summary-content">
            <span>STAT Cases</span>
            <strong><?= number_format($stat_count) ?></strong>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-icon bg-emerald-soft text-emerald"><i class="bi bi-check-circle-fill"></i></div>
        <div class="summary-content">
            <span>Completed</span>
            <strong><?= number_format($completed_count) ?></strong>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-icon bg-rose-soft text-rose"><i class="bi bi-x-circle-fill"></i></div>
        <div class="summary-content">
            <span>Cancelled</span>
            <strong><?= number_format($cancelled_count) ?></strong>
        </div>
    </div>
</section>

<section class="report-table-card">
    <div class="table-card-header">
        <div>
            <h2>Surgery Records & Clinical Logs</h2>
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
            <?= number_format($total_surgeries) ?> record<?= $total_surgeries === 1 ? '' : 's' ?>
        </div>
    </div>

    <?php if (empty($surgeries)): ?>
        <div class="empty-report">
            <div class="empty-report-icon"><i class="bi bi-calendar-x"></i></div>
            <h3>No Surgery Records Found</h3>
            <p>No surgery records match the selected report filters.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="surgery-report-table">
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
                        <th>Clinical Logs / Post-Op Outcomes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($surgeries as $surgery): ?>
                        <?php
                        $priority = $surgery['priority'] ?: 'Elective';
                        $priority_class = strtolower($priority);

                        $surgery['formatted_date'] = formatDateValue($surgery['surgery_date']);
                        $surgery['formatted_start_time'] = formatTimeValue($surgery['start_time']);
                        $surgery['formatted_end_time'] = formatTimeValue($surgery['end_time']);
                        $surgery['formatted_patient_name'] = formatPatientName($surgery['first_name'], $surgery['middle_name'], $surgery['last_name']);
                        $surgery['formatted_surgeon'] = formatDoctorName($surgery['surgeon_first_name'], $surgery['surgeon_middle_name'], $surgery['surgeon_last_name'], $surgery['surgeon_suffix_name']);
                        $surgery['formatted_anesthesiologist'] = !empty($surgery['anesthesiologist_first_name']) ? formatDoctorName($surgery['anesthesiologist_first_name'], $surgery['anesthesiologist_middle_name'], $surgery['anesthesiologist_last_name'], $surgery['anesthesiologist_suffix_name']) : 'Not assigned';
                        $surgery['display_priority'] = $priority;
                        $surgery['display_stat'] = ((int)$surgery['is_stat'] === 1) ? 'YES' : 'NO';
                        ?>
                        <tr class="row-<?= htmlspecialchars($priority_class) ?>">
                            <td>
                                <div class="date-cell">
                                    <strong><?= htmlspecialchars(formatDateValue($surgery['surgery_date'])) ?></strong>
                                    <div class="mt-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" onclick='openDetailModal(<?= json_encode($surgery, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="bi bi-eye-fill"></i> Details / Print
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="time-cell">
                                    <div><?= htmlspecialchars(formatTimeValue($surgery['start_time'])) ?></div>
                                    <?php if (!empty($surgery['end_time'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars(formatTimeValue($surgery['end_time'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="patient-cell">
                                    <strong><?= htmlspecialchars(formatPatientName($surgery['first_name'], $surgery['middle_name'], $surgery['last_name'])) ?></strong>
                                    <?php if (!empty($surgery['patient_number']) || !empty($surgery['patient_registry_no'])): ?>
                                        <small><?= htmlspecialchars($surgery['patient_registry_no'] ?: $surgery['patient_number']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="procedure-cell">
                                    <strong><?= htmlspecialchars($surgery['procedure_name']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($surgery['procedure_surgical_type'] ?: '-') ?></td>
                            <td>
                                <div class="doctor-cell">
                                    <i class="bi bi-person-badge-fill"></i>
                                    <span><?= htmlspecialchars(formatDoctorName($surgery['surgeon_first_name'], $surgery['surgeon_middle_name'], $surgery['surgeon_last_name'], $surgery['surgeon_suffix_name'])) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($surgery['anesthesiologist_first_name'])): ?>
                                    <div class="doctor-cell">
                                        <i class="bi bi-person-badge"></i>
                                        <span><?= htmlspecialchars(formatDoctorName($surgery['anesthesiologist_first_name'], $surgery['anesthesiologist_middle_name'], $surgery['anesthesiologist_last_name'], $surgery['anesthesiologist_suffix_name'])) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="room-cell">
                                    <i class="bi bi-door-open-fill"></i>
                                    <strong><?= htmlspecialchars($surgery['room_name']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <span class="priority-badge priority-<?= htmlspecialchars($priority_class) ?>">
                                    <?= htmlspecialchars($priority) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ((int)$surgery['is_stat'] === 1): ?>
                                    <span class="stat-badge"><i class="bi bi-lightning-charge-fill"></i> STAT</span>
                                <?php else: ?>
                                    <span class="not-stat">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= htmlspecialchars(statusClass($surgery['status'])) ?>">
                                    <?= htmlspecialchars($surgery['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (strcasecmp($surgery['status'], 'Completed') === 0): ?>
                                    <div class="clinical-summary-cell small">
                                        <div><strong>Pre-Op:</strong> <?= htmlspecialchars($surgery['pre_op_diagnosis'] ?: '—') ?></div>
                                        <div><strong>Post-Op:</strong> <?= htmlspecialchars($surgery['post_op_diagnosis'] ?: '—') ?></div>
                                        <div><strong>Drains:</strong> <?= htmlspecialchars($surgery['drains'] ?: '—') ?></div>
                                        <div><strong>Specimen:</strong> <?= htmlspecialchars($surgery['specimen_lab_exam'] ?: '—') ?></div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted font-italic">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Control Bar -->
        <div class="pagination-container">
            <div class="page-info-left">
                Page <strong><?= $page ?></strong> of <strong><?= $total_pages ?></strong>
            </div>

            <div class="pagination-nav-group">
                <?php if ($page > 1): ?>
                    <a href="<?= getPaginationUrl(1) ?>" class="btn-page-nav">&laquo;&laquo; First</a>
                <?php else: ?>
                    <span class="btn-page-nav disabled">&laquo;&laquo; First</span>
                <?php endif; ?>

                <?php if ($page > 1): ?>
                    <a href="<?= getPaginationUrl($page - 1) ?>" class="btn-page-nav">&lsaquo; Prev</a>
                <?php else: ?>
                    <span class="btn-page-nav disabled">&lsaquo; Prev</span>
                <?php endif; ?>

                <span class="current-page-text">Page <?= $page ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= getPaginationUrl($page + 1) ?>" class="btn-page-nav active-next">Next &rsaquo;</a>
                <?php else: ?>
                    <span class="btn-page-nav disabled">Next &rsaquo;</span>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= getPaginationUrl($total_pages) ?>" class="btn-page-nav active-last">Last &raquo;&raquo;</a>
                <?php else: ?>
                    <span class="btn-page-nav disabled">Last &raquo;&raquo;</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<div class="report-footnote">
    <span><i class="bi bi-info-circle"></i> Report generated from the OR Scheduling System.</span>
    <span>Printed by: <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'System User') ?> | Generated: <?= date('M d, Y h:i A') ?></span>
</div>

<!-- Export Modal Structure -->
<div id="exportModal" class="modal-overlay">
    <div class="modal-container">
         <div class="modal-header">
            <h3>Export Surgery Report</h3>
            <button type="button" class="modal-close-btn" onclick="closeExportModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        
        <div class="modal-body">
            <p>Choose your preferred export format for the filtered surgery dataset.</p>
            <div class="export-options-list">
                <button type="button" class="export-option-btn pdf" onclick="exportAsPDF()">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Export ALL as PDF / Print
                </button>

                <button type="button" class="export-option-btn excel" onclick="exportAsExcel()">
                    <i class="bi bi-file-earmark-text-fill text-primary"></i> Export ALL as Excel (.xls)
                </button>

                <button type="button" class="export-option-btn csv" onclick="exportToCSV()">
                    <i class="bi bi-file-earmark-text-fill text-primary"></i> Export ALL as CSV
                </button>
            </div>
        </div>
         <div class="modal-footer">
            <button type="button" class="modal-cancel-btn" onclick="closeExportModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- Detail & Print Modal Structure -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-container" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="bi bi-file-earmark-medical"></i> Surgery Case Detailed Report</h3>
            <button type="button" class="modal-close-btn" onclick="closeDetailModal()"><i class="bi bi-x-lg"></i></button>
        </div>
            
        <div class="modal-body" id="printableDetailBody" style="max-height: 70vh; overflow-y: auto;">    
            <section class="Patient_detail">
                <div class="patient-title">
                    <div class="Patient-icon"><i class="bi bi-person-fill"></i></div>
                    <div><h2>Patient Details</h2></div>
                </div>
                <div class="patient-detail-grid">
                    <div class="patient-name-container"><span class="patient-label">Patient Name:</span><span class="patient-value" id="det_patient_name"></span></div>
                    <div class="patient-name-container"><span class="patient-label">Birthdate:</span><span class="patient-value" id="det_birth_date"></span></div>
                    <div class="patient-name-container"><span class="patient-label">Registry No:</span><span class="patient-value" id="det_patient_no"></span></div>
                    <div class="patient-name-container"><span class="patient-label">Registry Type:</span><span class="patient-value" id="det_registry_type"></span></div>
                    <div class="patient-name-container"><span class="patient-label">Room No.:</span><span class="patient-value" id="det_patient_room_no"></span></div>
                    <div class="patient-name-container"><span class="patient-label">OR Room.:</span><span class="patient-value" id="det_room_name"></span></div>
                </div>
            </section>
            
            <section class="Schedule-Details">
                <div class="schedule-inline-layout">
                    <div class="schedule-title-group">
                        <div class="schedule-icon"><i class="bi bi-calendar-event-fill"></i></div>
                        <h2>Schedule Details</h2>
                    </div>    
                    <div class="schedule-detail-grid">
                        <div class="schedule-column"><span class="schedule-label">Surgery Date:</span><span class="schedule-value" id="det_surgery_date"></span></div>
                        <div class="schedule-column"><span class="schedule-label">Surgery Time:</span><span class="schedule-value" id="det_surgery_time"></span></div>
                    </div>
                </div>
            </section>

            <section class="info-cards-section">
                <div class="info-cards-grid">
                    <div class="info-card-item">
                        <div class="info-card-header"><div class="schedule-icon"><i class="bi bi-exclamation-triangle-fill"></i></div><span class="info-card-label">Clinical Priority</span></div>
                        <div class="schedule-value" id="det_priority"></div>
                    </div>
                    <div class="info-card-item">
                        <div class="info-card-header"><div class="schedule-icon"><i class="bi bi-lightning-fill"></i></div><span class="info-card-label">Immediate Attention</span></div>
                        <div class="schedule-value" id="det_immediate_attention"></div>
                    </div>
                    <div class="info-card-item">
                        <div class="info-card-header"><div class="schedule-icon"><i class="bi bi-shield-exclamation"></i></div><span class="info-card-label">Case Status</span></div>
                        <div class="schedule-value" id="det_status"></div>
                    </div>
                </div>
            </section>
            
            <section class="surgical-team-section">
                <div class="surgical-team-header"><div class="surgical-team-icon"><i class="bi bi-people-fill"></i></div><div><h2>Surgical Team</h2></div></div>
                <div class="surgical-team-grid">
                    <div class="surgical-team-field"><span class="surgical-team-label">Surgeon:</span><span class="surgical-team-value" id="det_surgeon"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Anesthesiology:</span><span class="surgical-team-value" id="det_anesthesiology"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Assistant Surgeon:</span><span class="surgical-team-value" id="det_assistant_surgeon"></span></div>
                </div>
            </section>

            <section class="procedure-details-section">
                <div class="surgical-team-header"><div class="surgical-team-icon"><i class="bi bi-journal-medical"></i></div><div><h2>Procedure & Anesthesia</h2></div></div>
                <div class="procedure-3col-grid">
                    <div class="surgical-team-field"><span class="surgical-team-label">Procedure:</span><span class="surgical-team-value" id="det_procedure"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Surgical Type:</span><span class="surgical-team-value" id="det_surgical_type"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Anesthetic:</span><span class="surgical-team-value" id="det_anesthetic"></span></div>
                </div>
            </section>

            <section class="medical-staff-section">
                <div class="surgical-team-header"><div class="surgical-team-icon"><i class="bi bi-people"></i></div><div><h2>Supporting Medical Staff</h2></div></div>
                <div class="medical-staff-grid">
                    <div class="surgical-team-field"><span class="surgical-team-label">Cardiologist:</span><span class="surgical-team-value" id="det_cardiologist"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Circulating Nurse:</span><span class="surgical-team-value" id="det_circulating_nurse"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Instrument Nurse:</span><span class="surgical-team-value" id="det_instrument_nurse"></span></div>
                </div>
            </section>

            <section class="clinical-assessment-section">
                <div class="surgical-team-header"><div class="surgical-team-icon"><i class="bi bi-clipboard-pulse"></i></div><div><h2>Clinical Assessment & Safety Notes</h2></div></div>
                <div class="clinical-assessment-grid">
                    <div class="surgical-team-field"><span class="surgical-team-label">Pre-Operative Diagnosis:</span><span class="surgical-team-value" id="det_pre_op"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Post-Operative Diagnosis:</span><span class="surgical-team-value" id="det_post_op"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Drains:</span><span class="surgical-team-value" id="det_drains"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Specimen / Lab Exam:</span><span class="surgical-team-value" id="det_specimen"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Sponge & Count Verified:</span><span class="surgical-team-value" id="det_sponge"></span></div>
                    <div class="surgical-team-field"><span class="surgical-team-label">Infections:</span><span class="surgical-team-value" id="det_infections"></span></div>
                    <div class="surgical-team-field full-width"><span class="surgical-team-label">Remarks:</span><span class="surgical-team-value" id="det_remarks"></span></div>
                </div>
            </section>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="printSingleRecord()"><i class="bi bi-printer-fill"></i> Print This Record</button>
            <button type="button" class="modal-cancel-btn" onclick="closeDetailModal()">Close</button>
        </div>
    </div>
</div>

</main>

<script>
function applyFilters() {
    document.getElementById('filterForm').submit();
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

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

function openExportModal() {
    document.getElementById('exportModal').classList.add('active');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.remove('active');
}

function openDetailModal(data) {
    document.getElementById('det_patient_name').innerText = data.formatted_patient_name || '—';
    document.getElementById('det_birth_date').innerText = data.birth_date || '—';
    document.getElementById('det_patient_no').innerText = data.patient_registry_no || data.patient_number || '—';
    document.getElementById('det_registry_type').innerText = data.registry_type || '—';
    document.getElementById('det_patient_room_no').innerText = data.patient_room_no || '—'; 
    document.getElementById('det_room_name').innerText = data.room_name || '—';    
    
    document.getElementById('det_surgery_date').innerText =  data.surgery_date + ' - ' + data.date_end ;
    document.getElementById('det_surgery_time').innerText =  data.start_time + ' - ' + data.end_time ;
        
    document.getElementById('det_priority').innerText = data.priority || '—';
    document.getElementById('det_immediate_attention').innerText = (data.is_stat == 1 || data.is_stat === true) ? 'STAT ' : 'No';
                
    document.getElementById('det_surgeon').innerText = data.formatted_surgeon || '—';
    document.getElementById('det_anesthesiology').innerText = data.anesthesiologist_full_name || '—';
    document.getElementById('det_assistant_surgeon').innerHTML = data.assistant_surgeon_full_name || '—';
        
    document.getElementById('det_procedure').innerText = data.procedure_name || '—';
    document.getElementById('det_surgical_type').innerText = data.procedure_surgical_type || 'General';
    document.getElementById('det_anesthetic').innerText = data.anesthetic || '—';
        
    document.getElementById('det_cardiologist').innerText = data.cardiologist || '—';
    document.getElementById('det_circulating_nurse').innerText = data.circulating_nurse || '—';
    document.getElementById('det_instrument_nurse').innerText = data.instrument_nurse || '—';
    
    document.getElementById('det_pre_op').innerText = data.pre_op_diagnosis || '—';
    document.getElementById('det_post_op').innerText = data.post_op_diagnosis || '—';
    document.getElementById('det_drains').innerText = data.drains || '—';
    document.getElementById('det_specimen').innerText = data.specimen_lab_exam || '—';
    document.getElementById('det_sponge').innerText = data.sponge_count_verified || '—';
    document.getElementById('det_infections').innerText = data.infections || '—';
    document.getElementById('det_remarks').innerText = data.remarks || '—';
    document.getElementById('det_status').innerText = data.status || '—';
    
    document.getElementById('detailModal').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('active');
}

function printSingleRecord() {
    var printContent = document.getElementById('printableDetailBody').innerHTML;
    var printedBy = "System User"; 
    var userElement = document.getElementById('logged_in_user');
    if (userElement && userElement.textContent.trim() !== "") {
        printedBy = userElement.textContent.trim();
    }
    
    var now = new Date();
    var dateStr = now.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    var timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    var timestamp = dateStr + ' at ' + timeStr;

    var printWindow = window.open('', '_blank', 'height=700,width=900');
    
    printWindow.document.write('<html><head><title>Surgery Case Detailed Report</title>');
    printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">');
    
    var stylesheets = document.querySelectorAll('link[rel="stylesheet"], style');
    stylesheets.forEach(function(sheet) {
        printWindow.document.write(sheet.outerHTML);
    });
    
    printWindow.document.write('<style>');
    printWindow.document.write('body { background: #ffffff !important; padding: 24px; font-family: inherit; color: #20352d; }');
    printWindow.document.write('.modal-body { max-height: none !important; overflow: visible !important; padding: 0 !important; }');
    printWindow.document.write('section { break-inside: avoid; page-break-inside: avoid; border: 1px solid #cbd5e1 !important; margin-bottom: 16px !important; }');
    printWindow.document.write('.print-header { border-bottom: 2px solid #0d4732; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end; }');
    printWindow.document.write('.print-header h1 { font-size: 1.4rem; font-weight: 700; color: #0d4732; margin: 0; display: flex; align-items: center; gap: 8px; }');
    printWindow.document.write('.print-meta { font-size: 0.85rem; color: #64748b; text-align: right; line-height: 1.4; }');
    printWindow.document.write('</style>');
    
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div class="print-header">');
    printWindow.document.write('<h1><i class="bi bi-file-earmark-medical"></i> Surgery Case Detailed Report</h1>');
    printWindow.document.write('<div class="print-meta">');
    printWindow.document.write('<strong>Printed By:</strong> ' + printedBy + '<br>');
    printWindow.document.write('<strong>Date:</strong> ' + timestamp);
    printWindow.document.write('</div>');
    printWindow.document.write('</div>');
    
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}

window.addEventListener('click', function(event) {
    const exportModal = document.getElementById('exportModal');
    const detailModal = document.getElementById('detailModal');
    if (event.target === exportModal) {
        closeExportModal();
    }
    if (event.target === detailModal) {
        closeDetailModal();
    }
});

// --- EXPORT ALL RECORDS AS LANDSCAPE PDF WITH CLINICAL LOGS ---
function exportAsPDF() {
    closeExportModal();
    
    let fetchUrl = new URL(window.location.href);
    fetchUrl.searchParams.set('export', 'json');

    fetch(fetchUrl.toString())
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                alert("No records available to print.");
                return;
            }

            var printWindow = window.open('', '_blank', 'height=850,width=1300');
            printWindow.document.write('<html><head><title>OR Surgery Report - All Records</title>');
            printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">');
            
            var stylesheets = document.querySelectorAll('link[rel="stylesheet"], style');
            stylesheets.forEach(function(sheet) {
                printWindow.document.write(sheet.outerHTML);
            });

            printWindow.document.write('<style>');
            // Force LANDSCAPE Orientation for PDF Printer
            printWindow.document.write('@page { size: landscape; margin: 10mm; }');
            printWindow.document.write('body { font-family: Arial, sans-serif; padding: 15px; background: #fff; color: #1e293b; }');
            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }');
            printWindow.document.write('th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; vertical-align: top; word-break: break-word; }');
            printWindow.document.write('th { background-color: #f1f5f9; font-weight: bold; color: #0f172a; }');
            printWindow.document.write('.header-print { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-end; }');
            printWindow.document.write('.header-print h2 { margin: 0; font-size: 1.2rem; color: #0f172a; }');
            printWindow.document.write('.header-meta { font-size: 0.75rem; color: #64748b; }');
            printWindow.document.write('.clinical-logs-col { min-width: 170px; max-width: 230px; font-size: 9.5px; line-height: 1.3; }');
            printWindow.document.write('.clinical-logs-col div { margin-bottom: 2px; }');
            printWindow.document.write('</style></head><body>');

            var now = new Date();
            var timestamp = now.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

            printWindow.document.write('<div class="header-print">');
            printWindow.document.write('<h2><i class="bi bi-heart-pulse-fill"></i> OR Surgery Report</h2>');
            printWindow.document.write('<div class="header-meta">Total Records: <strong>' + data.length + '</strong> | Generated: ' + timestamp + '</div>');
            printWindow.document.write('</div>');

            printWindow.document.write('<table><thead><tr>');
            printWindow.document.write('<th>Date</th><th>Time</th><th>Patient</th><th>Procedure</th><th>Type</th><th>Surgeon</th><th>Anesthesiologist</th><th>OR Room</th><th>Priority</th><th>STAT</th><th>Status</th><th>Clinical Logs / Post-Op Outcomes</th>');
            printWindow.document.write('</tr></thead><tbody>');

            data.forEach(item => {
                let pName = trimName((item.first_name || '') + ' ' + (item.middle_name ? item.middle_name + ' ' : '') + (item.last_name || ''));
                let sName = trimName((item.surgeon_first_name || '') + ' ' + (item.surgeon_last_name || ''));
                let aName = trimName((item.anesthesiologist_first_name || '') + ' ' + (item.anesthesiologist_last_name || '')) || 'Not assigned';
                let isStat = parseInt(item.is_stat) === 1 ? 'YES' : 'NO';
                let statusVal = (item.status || '').toLowerCase();

                // Format Clinical Logs HTML
                let clinicalLogsHtml = '—';
                if (statusVal === 'completed') {
                    clinicalLogsHtml = '<div class="clinical-logs-col">' +
                        '<div><strong>Pre-Op:</strong> ' + (item.pre_op_diagnosis || '—') + '</div>' +
                        '<div><strong>Post-Op:</strong> ' + (item.post_op_diagnosis || '—') + '</div>' +
                        '<div><strong>Drains:</strong> ' + (item.drains || '—') + '</div>' +
                        '<div><strong>Specimen:</strong> ' + (item.specimen_lab_exam || '—') + '</div>' +
                    '</div>';
                }

                printWindow.document.write('<tr>');
                printWindow.document.write('<td>' + (item.surgery_date || '-') + '</td>');
                printWindow.document.write('<td>' + (item.start_time || '-') + '<br><small>' + (item.end_time || '-') + '</small></td>');
                printWindow.document.write('<td><strong>' + pName + '</strong><br><small>' + (item.patient_registry_no || item.patient_number || '') + '</small></td>');
                printWindow.document.write('<td>' + (item.procedure_name || '-') + '</td>');
                printWindow.document.write('<td>' + (item.procedure_surgical_type || '-') + '</td>');
                printWindow.document.write('<td>' + sName + '</td>');
                printWindow.document.write('<td>' + aName + '</td>');
                printWindow.document.write('<td>' + (item.room_name || '-') + '</td>');
                printWindow.document.write('<td>' + (item.priority || 'Elective') + '</td>');
                printWindow.document.write('<td>' + isStat + '</td>');
                printWindow.document.write('<td>' + (item.status || '-') + '</td>');
                printWindow.document.write('<td>' + clinicalLogsHtml + '</td>');
                printWindow.document.write('</tr>');
            });

            printWindow.document.write('</tbody></table></body></html>');
            printWindow.document.close();
            printWindow.focus();

            setTimeout(function() {
                printWindow.print();
                printWindow.close();
            }, 500);
        })
        .catch(error => {
            console.error('Export Error:', error);
            alert('Failed to generate full report printout.');
        });
}

function trimName(str) {
    return str.replace(/\s+/g, ' ').trim();
}

function exportAsExcel() {
    let currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('export', 'excel');
    window.location.href = currentUrl.toString();
    closeExportModal();
}

function exportToCSV() {
    let currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('export', 'csv');
    window.location.href = currentUrl.toString();
    closeExportModal();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>