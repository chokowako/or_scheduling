<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get filter inputs from GET request (default to current date on first load)
$current_date = date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : $current_date;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : $current_date;

// Build dynamic SQL WHERE clauses for patient reports/schedules
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

if (!empty($search)) {
    $where_clauses[] = "(CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name) LIKE ? OR os.patient_registry_no LIKE ? OR p.patient_number LIKE ?)";
    $wildcard = "%" . $search . "%";
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
// 1. BACKEND EXPORT / DATA STREAMING (MUST RUN BEFORE HTML HEADERS)
// =========================================================================
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Select ALL matching records WITHOUT pagination limit
    $export_sql = "
        SELECT
            os.schedule_id,
            os.surgery_date,
            os.start_time,
            os.status,
            os.patient_registry_no,
            p.patient_number,
            p.first_name AS patient_first_name,
            p.middle_name AS patient_middle_name,
            p.last_name AS patient_last_name,
            pr.procedure_name,
            surgeon.first_name AS surgeon_first_name,
            surgeon.middle_name AS surgeon_middle_name,
            surgeon.last_name AS surgeon_last_name,
            surgeon.suffix_name AS surgeon_suffix_name
        FROM or_schedules os
        INNER JOIN patients p ON os.patient_id = p.patient_id
        INNER JOIN procedures pr ON os.procedure_id = pr.procedure_id
        INNER JOIN doctors surgeon ON os.surgeon_doctor_id = surgeon.doctor_id
        {$where_sql}
        ORDER BY os.surgery_date DESC, os.start_time DESC
    ";

    $export_stmt = $pdo->prepare($export_sql);
    $export_stmt->execute($params);
    $all_export_patients = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Option A: JSON Endpoint for PDF Print All
    if ($export_type === 'json') {
        header('Content-Type: application/json');
        echo json_encode($all_export_patients);
        exit;
    }

    // Option B: CSV & Excel Backend Stream
    if ($export_type === 'csv' || $export_type === 'excel') {
        $is_excel = ($export_type === 'excel');
        $ext = $is_excel ? 'xls' : 'csv';
        $content_type = $is_excel ? 'application/vnd.ms-excel' : 'text/csv; charset=utf-8';
        
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename=Patient_Report_ALL_' . date('Y-m-d') . '.' . $ext);
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, [
            'MRN / Registry No', 'Patient Name', 'Procedure', 'Surgeon', 'Surgery Date', 'Start Time', 'Status'
        ], ",", '"', "");
        
        foreach ($all_export_patients as $s) {
            $mrn = $s['patient_registry_no'] ?: $s['patient_number'];
            $patientName = trim($s['patient_first_name'] . ' ' . ($s['patient_middle_name'] ? $s['patient_middle_name'] . ' ' : '') . $s['patient_last_name']);
            $surgeonName = trim($s['surgeon_first_name'] . ' ' . ($s['surgeon_middle_name'] ? $s['surgeon_middle_name'] . ' ' : '') . $s['surgeon_last_name'] . ($s['surgeon_suffix_name'] ? ', ' . $s['surgeon_suffix_name'] : ''));

            fputcsv($output, [
                $mrn,
                $patientName,
                $s['procedure_name'],
                $surgeonName,
                $s['surgery_date'],
                $s['start_time'],
                $s['status']
            ], ",", '"', "");
        }
        fclose($output);
        exit;
    }
}

// =========================================================================
// 2. PAGE HTML DISPLAY (INCLUDES HEADERS AFTER EXPORT CHECK)
// =========================================================================
$page_title = "Patient Report";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

// --- PAGINATION SETTINGS ---
$records_per_page = 10; // Set to 5 records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// --- COUNT TOTAL RECORDS ---
$count_sql = "
    SELECT COUNT(*) 
    FROM or_schedules os
    INNER JOIN patients p ON os.patient_id = p.patient_id
    INNER JOIN procedures pr ON os.procedure_id = pr.procedure_id
    INNER JOIN doctors surgeon ON os.surgeon_doctor_id = surgeon.doctor_id
    {$where_sql}
";

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_patients = (int)$count_stmt->fetchColumn();

// Calculate total pages & current page offset
$total_pages = $total_patients > 0 ? (int)ceil($total_patients / $records_per_page) : 1;
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $records_per_page;

// --- FETCH PAGINATED RECORDS ---
$sql = "
    SELECT
        os.schedule_id,
        os.surgery_date,
        os.start_time,
        os.status,
        os.patient_registry_no,
        
        p.patient_number,
        p.first_name AS patient_first_name,
        p.middle_name AS patient_middle_name,
        p.last_name AS patient_last_name,

        pr.procedure_name,

        surgeon.first_name AS surgeon_first_name,
        surgeon.middle_name AS surgeon_middle_name,
        surgeon.last_name AS surgeon_last_name,
        surgeon.suffix_name AS surgeon_suffix_name

    FROM or_schedules os

    INNER JOIN patients p ON os.patient_id = p.patient_id
    INNER JOIN procedures pr ON os.procedure_id = pr.procedure_id
    INNER JOIN doctors surgeon ON os.surgeon_doctor_id = surgeon.doctor_id
    {$where_sql}
    ORDER BY os.surgery_date DESC, os.start_time DESC
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
$patients_data = $stmt->fetchAll();

// Calculate metrics across entire filtered set
$summary_sql = "
    SELECT 
        SUM(CASE WHEN LOWER(os.status) IN ('scheduled', 'confirmed') THEN 1 ELSE 0 END) AS scheduled_count,
        SUM(CASE WHEN LOWER(os.status) = 'completed' THEN 1 ELSE 0 END) AS completed_count,
        SUM(CASE WHEN LOWER(os.status) = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
    FROM or_schedules os
    INNER JOIN patients p ON os.patient_id = p.patient_id
    {$where_sql}
";
$summary_stmt = $pdo->prepare($summary_sql);
$summary_stmt->execute($params);
$summary_data = $summary_stmt->fetch();

$scheduled_count = (int)($summary_data['scheduled_count'] ?? 0);
$completed_count = (int)($summary_data['completed_count'] ?? 0);
$cancelled_count = (int)($summary_data['cancelled_count'] ?? 0);

function formatPatientName($first, $middle, $last) {
    return trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last);
}

function formatDoctorName($first, $middle, $last, $suffix = null) {
    $name = trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last);
    if (!empty($suffix)) $name .= ', ' . $suffix;
    return $name;
}

function formatDateTimeValue($date, $time) {
    if (empty($date)) return '-';
    $formattedDate = date('M d, Y', strtotime($date));
    $formattedTime = !empty($time) ? ' - ' . date('h:i A', strtotime($time)) : '';
    return $formattedDate . $formattedTime;
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

$patient_filter_label = !empty($search) ? htmlspecialchars($search) : 'All';
$status_filter_label = !empty($status) ? htmlspecialchars(ucfirst($status)) : 'All Statuses';
?>

<!-- Shared Hub Styles & Dedicated Patient Module Styles -->
<link rel="stylesheet" href="../assets/css/reports.css?v=20260920">
<link rel="stylesheet" href="../assets/css/patient_reports.css?v=20260920">

<style>
/* Filter Card Layout Styles */
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

.field-date { width: 150px; }
.field-search { width: 220px; }
.field-status { width: 160px; }

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

/* Pagination Component Styles */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0 0 8px 8px;
    font-family: inherit;
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

<main class="reports-page">

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
                <div class="patient-form-eyebrow">
                    <span class="eyebrow-dot"></span> Clinical Module
                </div>
                <h2>
                    Patient Reporting
                </h2>
                <p class="header-subtitle">
                    Track surgical histories, procedure statuses, clinical logs, and patient records.
                </p>
            </div>
        </div>

        <div class="header-right-meta">
            <i class="bi bi-file-earmark-medical-fill"></i>
            <div>
                <strong>Patient Registry</strong>
                <span>OR Intelligence System</span>
            </div>
        </div>                          
    </header>

    <!-- METRICS OVERVIEW -->
    <section class="stats-overview">
        <div class="stat-card">
            <div class="stat-icon total"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <h4>Total Patients</h4>
                <div class="stat-value" id="stat-total"><?= number_format($total_patients) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon scheduled"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-info">
                <h4>Scheduled</h4>
                <div class="stat-value" id="stat-scheduled"><?= number_format($scheduled_count) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon completed"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-info">
                <h4>Completed</h4>
                <div class="stat-value" id="stat-completed"><?= number_format($completed_count) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cancelled"><i class="bi bi-x-circle"></i></div>
            <div class="stat-info">
                <h4>Cancelled</h4>
                <div class="stat-value" id="stat-cancelled"><?= number_format($cancelled_count) ?></div>
            </div>
        </div>
    </section>

    <!-- FILTER FORM SECTION -->
    <section class="report-filter-card">
        <div class="filter-card-header">
            <div class="filter-title">
                <div class="filter-icon"><i class="bi bi-funnel-fill"></i></div>
                <div>
                    <h2>Report Filters</h2>
                    <span>Set the criteria for the patient report.</span>
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
                <label for="search">Patient / Registry No.</label>
                <div class="search-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="search" name="search" placeholder="Search patient or registry num" value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="filter-field-item field-status">
                <label for="status">Procedure Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?= strtolower($status) === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="completed" <?= strtolower($status) === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= strtolower($status) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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

    <!-- DATA TABLE CARD -->
    <section class="table-card">
        <div class="report-card-header" style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">Patient Records</h3>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">
                    Date Range: <?= $date_range_label ?> | Patient / Registry No.: <?= $patient_filter_label ?> | Status: <?= $status_filter_label ?>
                </p>
            </div>
            <div style="background: #e2e8f0; color: #334155; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">
                <i class="bi bi-list-task"></i> <?= number_format($total_patients) ?> record<?= $total_patients === 1 ? '' : 's' ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="report-table" id="patientsTable">
                <thead>
                    <tr>
                        <th>MRN</th>
                        <th>Patient Name</th>
                        <th>Procedure</th>
                        <th>Surgeon</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($patients_data)): ?>
                        <tr>
                            <td colspan="6">
                                <div style="padding: 40px 20px; text-align: center; color: #64748b;">
                                    <i class="bi bi-calendar-x" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                    <strong style="font-size: 15px; color: #475569; display: block; margin-bottom: 4px;">No Patient Records Found</strong>
                                    <span style="font-size: 13px;">No patient records match the selected report filters.</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients_data as $row): 
                            $mrn = $row['patient_registry_no'] ?: $row['patient_number'];
                            $patientFullName = formatPatientName($row['patient_first_name'], $row['patient_middle_name'], $row['patient_last_name']);
                            $surgeonFullName = formatDoctorName($row['surgeon_first_name'], $row['surgeon_middle_name'], $row['surgeon_last_name'], $row['surgeon_suffix_name']);
                            $rowStatus = strtolower(trim($row['status']));
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($mrn) ?></strong></td>
                                <td class="patient-name"><?= htmlspecialchars($patientFullName) ?></td>
                                <td><?= htmlspecialchars($row['procedure_name']) ?></td>
                                <td><?= htmlspecialchars($surgeonFullName) ?></td>
                                <td><?= htmlspecialchars(formatDateTimeValue($row['surgery_date'], $row['start_time'])) ?></td>
                                <td>
                                    <?php if ($rowStatus === 'completed'): ?>
                                        <span class="badge badge-completed">Completed</span>
                                    <?php elseif ($rowStatus === 'cancelled'): ?>
                                        <span class="badge status-cancelled">Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge badge-scheduled"><?= htmlspecialchars(ucfirst($row['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION CONTROLS -->
        <?php if ($total_patients > 0): ?>
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

</main>

<!-- EXPORT DIALOG MODAL -->
<div class="modal-overlay" id="exportModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Export Patient Report</h3>
            <button type="button" class="modal-close-btn" onclick="closeExportModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <p>Choose your preferred export format for the filtered patient dataset.</p>
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
                alert("No patient records available to print.");
                return;
            }

            var printWindow = window.open('', '_blank', 'height=850,width=1300');
            printWindow.document.write('<html><head><title>Patient Report - All Records</title>');
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
            printWindow.document.write('<h2><i class="bi bi-file-earmark-medical-fill"></i> Patient Report (Full Dataset)</h2>');
            printWindow.document.write('<div class="header-meta">Total Records: <strong>' + data.length + '</strong> | Generated: ' + timestamp + '</div>');
            printWindow.document.write('</div>');

            printWindow.document.write('<table><thead><tr>');
            printWindow.document.write('<th>MRN</th><th>Patient Name</th><th>Procedure</th><th>Surgeon</th><th>Surgery Date & Time</th><th>Status</th>');
            printWindow.document.write('</tr></thead><tbody>');

            data.forEach(item => {
                let mrn = item.patient_registry_no || item.patient_number || '-';
                let pName = trimName((item.patient_first_name || '') + ' ' + (item.patient_middle_name ? item.patient_middle_name + ' ' : '') + (item.patient_last_name || ''));
                let sName = trimName((item.surgeon_first_name || '') + ' ' + (item.surgeon_middle_name ? item.surgeon_middle_name + ' ' : '') + (item.surgeon_last_name || '') + (item.surgeon_suffix_name ? ', ' + item.surgeon_suffix_name : ''));
                let dateTime = (item.surgery_date || '-') + (item.start_time ? ' - ' + item.start_time : '');

                printWindow.document.write('<tr>');
                printWindow.document.write('<td><strong>' + mrn + '</strong></td>');
                printWindow.document.write('<td>' + pName + '</td>');
                printWindow.document.write('<td>' + (item.procedure_name || '-') + '</td>');
                printWindow.document.write('<td>' + sName + '</td>');
                printWindow.document.write('<td>' + dateTime + '</td>');
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
            alert('Failed to generate full patient report printout.');
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

<?php
require_once "../includes/footer.php";
?>