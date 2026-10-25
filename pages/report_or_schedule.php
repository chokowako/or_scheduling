<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get filter inputs from GET request (default to current date on first load)
$current_date = date('Y-m-d');
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : $current_date;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : $current_date;
$patient_search = isset($_GET['patient_search']) ? trim($_GET['patient_search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build base dynamic WHERE conditions
$where_clauses = [];
$params = [];

if (!empty($date_from)) {
    $where_clauses[] = "os.surgery_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "os.surgery_date <= ?";
    $params[] = $date_to;
}

if (!empty($patient_search)) {
    $where_clauses[] = "(CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name) LIKE ? OR os.patient_registry_no LIKE ? OR p.patient_number LIKE ?)";
    $wildcard = "%" . $patient_search . "%";
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
}

if (!empty($status)) {
    $where_clauses[] = "LOWER(os.status) = ?";
    $params[] = strtolower($status);
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// =========================================================================
// 1. BACKEND EXPORT / DATA STREAMING (BEFORE HTML OUTPUT)
// =========================================================================
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Select ALL matching records WITHOUT pagination limit
    $export_sql = "
        SELECT
            r.room_name,
            os.start_time,
            os.end_time,
            os.surgery_date,
            p.patient_number,
            os.patient_registry_no,
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
            os.priority,
            os.is_stat,
            os.status
        FROM or_schedules os
        INNER JOIN patients p ON os.patient_id = p.patient_id
        INNER JOIN procedures pr ON os.procedure_id = pr.procedure_id
        INNER JOIN doctors surgeon ON os.surgeon_doctor_id = surgeon.doctor_id
        LEFT JOIN doctors anesthesiologist ON os.anesthesiologist_doctor_id = anesthesiologist.doctor_id
        INNER JOIN operating_rooms r ON os.room_id = r.room_id
        {$where_sql}
        ORDER BY
            os.surgery_date ASC,
            os.start_time ASC,
            r.room_name ASC
    ";

    $export_stmt = $pdo->prepare($export_sql);
    $export_stmt->execute($params);
    $all_export_schedules = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Option A: JSON Endpoint for PDF Print All
    if ($export_type === 'json') {
        header('Content-Type: application/json');
        echo json_encode($all_export_schedules);
        exit;
    }

    // Option B: CSV & Excel Backend Stream
    if ($export_type === 'csv' || $export_type === 'excel') {
        $is_excel = ($export_type === 'excel');
        $ext = $is_excel ? 'xls' : 'csv';
        $content_type = $is_excel ? 'application/vnd.ms-excel' : 'text/csv; charset=utf-8';
        
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename=OR_Schedule_Report_ALL_' . date('Y-m-d') . '.' . $ext);
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, [
            'Operating Room', 'Start Time', 'End Time', 'Surgery Date', 
            'Patient Name', 'Patient Number / Reg', 'Procedure', 'Surgical Type', 
            'Surgeon', 'Anesthesiologist', 'Priority', 'STAT', 'Status'
        ], ",", '"', "");
        
        foreach ($all_export_schedules as $s) {
            $patientName = trim($s['first_name'] . ' ' . ($s['middle_name'] ? $s['middle_name'] . ' ' : '') . $s['last_name']);
            $surgeonName = trim($s['surgeon_first_name'] . ' ' . $s['surgeon_last_name']);
            $anesName = !empty($s['anesthesiologist_first_name']) ? trim($s['anesthesiologist_first_name'] . ' ' . $s['anesthesiologist_last_name']) : 'Unassigned';

            fputcsv($output, [
                $s['room_name'],
                $s['start_time'],
                $s['end_time'],
                $s['surgery_date'],
                $patientName,
                !empty($s['patient_registry_no']) ? $s['patient_registry_no'] : $s['patient_number'],
                $s['procedure_name'],
                $s['procedure_surgical_type'] ?: 'Standard',
                $surgeonName,
                $anesName,
                $s['priority'] ?: 'Elective',
                (int)$s['is_stat'] === 1 ? 'YES' : 'NO',
                $s['status']
            ], ",", '"', "");
        }
        fclose($output);
        exit;
    }
}

// =========================================================================
// 2. PAGE HTML DISPLAY
// =========================================================================
$page_title = 'OR Schedule Report';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// --- PAGINATION SETTINGS ---
$records_per_page = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// Count Total Records
$count_sql = "
    SELECT COUNT(*) 
    FROM or_schedules os
    INNER JOIN patients p ON os.patient_id = p.patient_id
    INNER JOIN procedures pr ON os.procedure_id = pr.procedure_id
    INNER JOIN doctors surgeon ON os.surgeon_doctor_id = surgeon.doctor_id
    LEFT JOIN doctors anesthesiologist ON os.anesthesiologist_doctor_id = anesthesiologist.doctor_id
    INNER JOIN operating_rooms r ON os.room_id = r.room_id
    {$where_sql}
";

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_schedules = (int)$count_stmt->fetchColumn();

// Calculate total pages & current page offset
$total_pages = $total_schedules > 0 ? (int)ceil($total_schedules / $records_per_page) : 1;
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $records_per_page;

// Fetch Paginated Records
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
    INNER JOIN patients p ON os.patient_id = p.patient_id
    INNER JOIN procedures pr ON os.procedure_id = pr.procedure_id
    INNER JOIN doctors surgeon ON os.surgeon_doctor_id = surgeon.doctor_id
    LEFT JOIN doctors anesthesiologist ON os.anesthesiologist_doctor_id = anesthesiologist.doctor_id
    INNER JOIN operating_rooms r ON os.room_id = r.room_id
    {$where_sql}
    ORDER BY
        os.surgery_date ASC,
        os.start_time ASC,
        r.room_name ASC
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($sql);
$param_index = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_index++, $param);
}
$stmt->bindValue($param_index++, $records_per_page, PDO::PARAM_INT);
$stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
$stmt->execute();
$schedules = $stmt->fetchAll();

// Dropdown Status Options
$statusStmt = $pdo->query("
    SELECT DISTINCT status
    FROM or_schedules
    WHERE status IS NOT NULL
      AND status <> ''
    ORDER BY status
");
$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

// Count stats across entire filtered set
$summary_sql = "
    SELECT 
        SUM(CASE WHEN os.is_stat = 1 THEN 1 ELSE 0 END) AS stat_count,
        SUM(CASE WHEN LOWER(os.status) = 'completed' THEN 1 ELSE 0 END) AS completed_count,
        SUM(CASE WHEN LOWER(os.status) = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
    FROM or_schedules os
    INNER JOIN patients p ON os.patient_id = p.patient_id
    {$where_sql}
";
$summary_stmt = $pdo->prepare($summary_sql);
$summary_stmt->execute($params);
$summary_data = $summary_stmt->fetch();

$stat_count = (int)($summary_data['stat_count'] ?? 0);
$completed_count = (int)($summary_data['completed_count'] ?? 0);
$cancelled_count = (int)($summary_data['cancelled_count'] ?? 0);

function formatDoctorName($first_name, $middle_name, $last_name, $suffix_name = null) {
    $name = trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);
    if (!empty($suffix_name)) $name .= ', ' . $suffix_name;
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

$date_range_label = "All Dates";
if (!empty($date_from) && !empty($date_to)) {
    if ($date_from === $date_to) {
        $date_range_label = date('M d, Y', strtotime($date_from));
    } else {
        $date_range_label = date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to));
    }
} elseif (!empty($date_from)) {
    $date_range_label = "From " . date('M d, Y', strtotime($date_from));
} elseif (!empty($date_to)) {
    $date_range_label = "Through " . date('M d, Y', strtotime($date_to));
}

$patient_filter_label = !empty($patient_search) ? htmlspecialchars($patient_search) : 'All';
$status_filter_label = !empty($status) ? htmlspecialchars(ucfirst($status)) : 'All Statuses';
?>

<link rel="stylesheet" href="../assets/css/report_or_schedule.css?v=<?= time(); ?>">

<style>
/* --- Exact Filter Card Styles Matching Screenshot --- */
.report-filter-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
    margin-bottom: 24px;
}

.filter-card-header {
    margin-bottom: 20px;
}

.filter-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.filter-icon {
    width: 36px;
    height: 36px;
    background-color: #e6f4ea;
    color: #137333;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.filter-title h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.filter-title span {
    font-size: 13px;
    color: #64748b;
}

/* Horizontal Flex Form Row */
.report-filter-form-custom {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

.filter-field-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-field-item label {
    font-size: 12px;
    font-weight: 600;
    color: #334155;
}

.filter-field-item input,
.filter-field-item select {
    height: 38px;
    padding: 0 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    color: #1e293b;
    outline: none;
    background-color: #ffffff;
    transition: border-color 0.15s ease;
}

.filter-field-item input:focus,
.filter-field-item select:focus {
    border-color: #107c41;
}

/* Input sizes matching layout */
.field-date { width: 150px; }
.field-search { width: 220px; }
.field-status { width: 160px; }

/* Input with icon wrapper */
.search-input-wrap {
    position: relative;
}

.search-input-wrap i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}

.search-input-wrap input {
    padding-left: 32px;
    width: 100%;
}

/* Custom Action Buttons */
.filter-actions-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}

.btn-filter-dark {
    height: 38px;
    padding: 0 18px;
    background-color: #0f5132;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.btn-filter-dark:hover { background-color: #0b3d26; }

.btn-reset-pink {
    height: 38px;
    padding: 0 16px;
    background-color: #fce8e6;
    color: #d93025;
    border: 1px solid #f8c4b8;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.btn-reset-pink:hover { background-color: #f8d7da; }

.btn-export-outline {
    height: 38px;
    padding: 0 16px;
    background-color: #ffffff;
    color: #0f5132;
    border: 1px solid #0f5132;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.btn-export-outline:hover { background-color: #f4fbf7; }

.btn-print-blue {
    height: 38px;
    padding: 0 18px;
    background-color: #2563eb;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.btn-print-blue:hover { background-color: #1d4ed8; }

/* Pagination Controls Styling */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0 0 8px 8px;
    font-size: 14px;
    color: #64748b;
}

.page-info-left { font-size: 14px; color: #64748b; }
.page-info-left strong { color: #1e293b; font-weight: 600; }

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

a.btn-page-nav:hover { background-color: #e2e8f0; border-color: #94a3b8; color: #1d4ed8; }
a.btn-page-nav.active-next, a.btn-page-nav.active-last { color: #0d9488; background-color: #ffffff; border-color: #cbd5e1; }
.btn-page-nav.disabled { color: #94a3b8; background-color: #f8fafc; border-color: #e2e8f0; cursor: not-allowed; opacity: 0.7; }
.current-page-text { font-size: 14px; font-weight: 500; color: #1e293b; padding: 0 4px; }
</style>

<main class="schedule-report-page">

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
                    <i class="bi bi-calendar-event-fill"></i> OR Schedule Report
                </h2>
                <p class="header-subtitle">
                    View and filter operating room daily schedules and resource allocation.
                </p>
            </div>                  
        </div>

        <div class="header-right-meta">
            <div class="required-note-badge">
                <span class="required-asterisk">*</span> Required fields
            </div>
        </div>                            
    </header>

    <!-- Filter Form UI -->
    <section class="report-filter-card">
        <div class="filter-card-header">
            <div class="filter-title">
                <div class="filter-icon"><i class="bi bi-funnel-fill"></i></div>
                <div>
                    <h2>Report Filters</h2>
                    <span>Set the criteria for the OR schedule report.</span>
                </div>
            </div>
        </div>

        <form method="GET" class="report-filter-form-custom" id="filterForm">
            <div class="filter-field-item field-date">
                <label for="date_from">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
            </div>

            <div class="filter-field-item field-date">
                <label for="date_to">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
            </div>

            <div class="filter-field-item field-search">
                <label for="patient_search">Patient / Registry No.</label>
                <div class="search-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="patient_search" name="patient_search" placeholder="Search patient or registry num" value="<?= htmlspecialchars($patient_search) ?>">
                </div>
            </div>

            <div class="filter-field-item field-status">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $statOpt): ?>
                        <option value="<?= htmlspecialchars(strtolower($statOpt)) ?>" <?= (strtolower($status) === strtolower($statOpt)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($statOpt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-actions-group">
                <button type="submit" class="btn-filter-dark"><i class="bi bi-funnel-fill"></i> Filter</button>
                <button type="button" class="btn-reset-pink" onclick="resetFilters()"><i class="bi bi-x-lg"></i> Reset</button>
                <button type="button" class="btn-export-outline" onclick="openExportModal()"><i class="bi bi-download"></i> Export</button>
                <button type="button" class="btn-print-blue" onclick="exportAsPDF()"><i class="bi bi-printer"></i> Print</button>
            </div>
        </form>
    </section>

    <!-- Summary Cards Grid -->
    <section class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon icon-scheduled"><i class="bi bi-calendar2-check-fill"></i></div>
            <div class="summary-content">
                <span>Total Scheduled</span>
                <strong id="stat-total"><?= number_format($total_schedules) ?></strong>
            </div>
        </div>

        <div class="summary-card stat-summary">
            <div class="summary-icon icon-stat"><i class="bi bi-lightning-charge-fill"></i></div>
            <div class="summary-content">
                <span>STAT Cases</span>
                <strong id="stat-stat"><?= number_format($stat_count) ?></strong>
            </div>
        </div>

        <div class="summary-card completed-summary">
            <div class="summary-icon icon-completed"><i class="bi bi-check-circle-fill"></i></div>
            <div class="summary-content">
                <span>Completed</span>
                <strong id="stat-completed"><?= number_format($completed_count) ?></strong>
            </div>
        </div>

        <div class="summary-card cancelled-summary">
            <div class="summary-icon icon-cancelled"><i class="bi bi-x-circle-fill"></i></div>
            <div class="summary-content">
                <span>Cancelled</span>
                <strong id="stat-cancelled"><?= number_format($cancelled_count) ?></strong>
            </div>
        </div>
    </section>

    <!-- Table Section -->
    <section class="report-table-card">
        <div class="report-card-header" style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">OR Schedule Records</h3>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">
                    Date Range: <?= $date_range_label ?> | Patient / Registry No.: <?= $patient_filter_label ?> | Status: <?= $status_filter_label ?>
                </p>
            </div>
            <div style="background: #e2e8f0; color: #334155; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">
                <i class="bi bi-list-task"></i> <span id="recordCountText"><?= number_format($total_schedules) ?> record<?= $total_schedules === 1 ? '' : 's' ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="schedule-report-table" id="scheduleTable">
                <thead>
                    <tr>
                        <th>Operating Room</th>
                        <th>Time Slot</th>
                        <th>Surgery Date</th>
                        <th>Patient & Registry</th>
                        <th>Procedure</th>
                        <th>Surgical Team</th>
                        <th>Priority</th>
                        <th>STAT</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="9">
                                <div style="padding: 40px 20px; text-align: center; color: #64748b;">
                                    <i class="bi bi-calendar-x" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                    <strong style="font-size: 15px; color: #475569; display: block; margin-bottom: 4px;">No OR Schedule Records Found</strong>
                                    <span style="font-size: 13px;">No OR schedule records match the selected report filters.</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $schedule): 
                            $patientName = formatPatientName($schedule['first_name'], $schedule['middle_name'], $schedule['last_name']);
                            $isStatVal = (int)$schedule['is_stat'];
                            $priority = $schedule['priority'] ?: 'Elective';
                        ?>
                            <tr>
                                <td>
                                    <div class="room-cell">
                                        <i class="bi bi-door-open-fill text-success"></i>
                                        <strong><?= htmlspecialchars($schedule['room_name']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="time-cell">
                                        <span><?= htmlspecialchars(formatTimeValue($schedule['start_time'])) ?></span>
                                        <small>to <?= htmlspecialchars(formatTimeValue($schedule['end_time'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <strong><?= htmlspecialchars(formatDateValue($schedule['surgery_date'])) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="patient-cell">
                                        <strong><?= htmlspecialchars($patientName) ?></strong>
                                        <small>
                                            <?php if (!empty($schedule['patient_registry_no'])): ?>
                                                Reg: <?= htmlspecialchars($schedule['patient_registry_no']) ?>
                                            <?php else: ?>
                                                PN: <?= htmlspecialchars($schedule['patient_number']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="procedure-cell">
                                        <strong><?= htmlspecialchars($schedule['procedure_name']) ?></strong>
                                        <small><?= htmlspecialchars($schedule['procedure_surgical_type'] ?: 'Standard') ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="doctor-cell" style="flex-direction: column; align-items: flex-start; gap: 2px;">
                                        <span><i class="bi bi-person-badge-fill"></i> <strong>Surgeon:</strong> <?= htmlspecialchars(formatDoctorName($schedule['surgeon_first_name'], $schedule['surgeon_middle_name'], $schedule['surgeon_last_name'], $schedule['surgeon_suffix_name'])) ?></span>
                                        <span><i class="bi bi-person-badge"></i> <strong>Anes:</strong> 
                                            <?php if (!empty($schedule['anesthesiologist_first_name'])): ?>
                                                <?= htmlspecialchars(formatDoctorName($schedule['anesthesiologist_first_name'], $schedule['anesthesiologist_middle_name'], $schedule['anesthesiologist_last_name'], $schedule['anesthesiologist_suffix_name'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unassigned</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px; align-items: center;">
                                        <span class="priority-badge priority-<?= htmlspecialchars(strtolower($priority)) ?>">
                                            <?= htmlspecialchars($priority) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px; align-items: center;">
                                        <?php if ($isStatVal === 1): ?>
                                            <span class="stat-badge"><i class="bi bi-lightning-charge-fill"></i> STAT</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= htmlspecialchars(statusClass($schedule['status'])) ?>">
                                        <?= htmlspecialchars($schedule['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls Matching UI -->
        <?php if ($total_schedules > 0): ?>
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
        <span>Generated: <?= date('M d, Y h:i A') ?></span>
    </div>

    <!-- Export Modal Overlay -->
    <div class="modal-overlay" id="exportModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Export OR Schedule Report</h3>
                <button type="button" class="modal-close-btn" onclick="closeExportModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <p>Choose your preferred export format for the filtered OR schedule dataset.</p>
                <div class="export-options-list">
                    <button type="button" class="export-option-btn pdf" onclick="exportAsPDF()">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Export ALL as PDF Document
                    </button>
                    <button type="button" class="export-option-btn excel" onclick="exportAsExcel()">
                        <i class="bi bi-file-earmark-excel-fill"></i> Export ALL as Excel Spreadsheet (.xls)
                    </button>
                    <button type="button" class="export-option-btn csv" onclick="exportToCSV()">
                        <i class="bi bi-file-earmark-text-fill"></i> Export ALL as CSV Document
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-cancel-btn" onclick="closeExportModal()">Cancel</button>
            </div>
        </div>
    </div>

</main>

<script>
function resetFilters() {
    window.location.href = window.location.pathname;
}

function openExportModal() {
    document.getElementById('exportModal').classList.add('active');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.remove('active');
}

window.addEventListener('click', function(event) {
    const modal = document.getElementById('exportModal');
    if (event.target === modal) {
        closeExportModal();
    }
});

// LANDSCAPE PDF / PRINT ALL EXPORT
function exportAsPDF() {
    closeExportModal();
    
    let fetchUrl = new URL(window.location.href);
    fetchUrl.searchParams.set('export', 'json');

    fetch(fetchUrl.toString())
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                alert("No schedule records available to print.");
                return;
            }

            var printWindow = window.open('', '_blank', 'height=850,width=1300');
            printWindow.document.write('<html><head><title>OR Schedule Report - All Records</title>');
            printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">');
            
            var stylesheets = document.querySelectorAll('link[rel="stylesheet"], style');
            stylesheets.forEach(function(sheet) {
                printWindow.document.write(sheet.outerHTML);
            });

            printWindow.document.write('<style>');
            printWindow.document.write('@page { size: landscape; margin: 10mm; }');
            printWindow.document.write('body { font-family: Arial, sans-serif; padding: 15px; background: #fff; color: #1e293b; }');
            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; }');
            printWindow.document.write('th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }');
            printWindow.document.write('th { background-color: #f1f5f9; font-weight: bold; color: #0f172a; }');
            printWindow.document.write('.header-print { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-end; }');
            printWindow.document.write('.header-print h2 { margin: 0; font-size: 1.2rem; color: #0f172a; }');
            printWindow.document.write('.header-meta { font-size: 0.75rem; color: #64748b; }');
            printWindow.document.write('</style></head><body>');

            var now = new Date();
            var timestamp = now.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

            printWindow.document.write('<div class="header-print">');
            printWindow.document.write('<h2><i class="bi bi-calendar-event-fill"></i> OR Schedule Report (Full Dataset)</h2>');
            printWindow.document.write('<div class="header-meta">Total Records: <strong>' + data.length + '</strong> | Generated: ' + timestamp + '</div>');
            printWindow.document.write('</div>');

            printWindow.document.write('<table><thead><tr>');
            printWindow.document.write('<th>Operating Room</th><th>Time Slot</th><th>Surgery Date</th><th>Patient & Registry</th><th>Procedure</th><th>Surgeon</th><th>Anesthesiologist</th><th>Priority</th><th>STAT</th><th>Status</th>');
            printWindow.document.write('</tr></thead><tbody>');

            data.forEach(item => {
                let pName = trimName((item.first_name || '') + ' ' + (item.middle_name ? item.middle_name + ' ' : '') + (item.last_name || ''));
                let sName = trimName((item.surgeon_first_name || '') + ' ' + (item.surgeon_last_name || ''));
                let aName = trimName((item.anesthesiologist_first_name || '') + ' ' + (item.anesthesiologist_last_name || '')) || 'Unassigned';
                let isStat = parseInt(item.is_stat) === 1 ? 'YES' : 'NO';
                let regNo = item.patient_registry_no ? ('Reg: ' + item.patient_registry_no) : ('PN: ' + (item.patient_number || '-'));

                printWindow.document.write('<tr>');
                printWindow.document.write('<td><strong>' + (item.room_name || '-') + '</strong></td>');
                printWindow.document.write('<td>' + (item.start_time || '-') + '<br><small>to ' + (item.end_time || '-') + '</small></td>');
                printWindow.document.write('<td>' + (item.surgery_date || '-') + '</td>');
                printWindow.document.write('<td><strong>' + pName + '</strong><br><small>' + regNo + '</small></td>');
                printWindow.document.write('<td><strong>' + (item.procedure_name || '-') + '</strong><br><small>' + (item.procedure_surgical_type || 'Standard') + '</small></td>');
                printWindow.document.write('<td>' + sName + '</td>');
                printWindow.document.write('<td>' + aName + '</td>');
                printWindow.document.write('<td>' + (item.priority || 'Elective') + '</td>');
                printWindow.document.write('<td>' + isStat + '</td>');
                printWindow.document.write('<td>' + (item.status || '-') + '</td>');
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
            alert('Failed to generate full OR schedule printout.');
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