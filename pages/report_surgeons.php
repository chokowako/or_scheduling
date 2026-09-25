<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$page_title = "Surgeon Performance Report";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

// Get filter inputs from GET request
$current_date = date('Y-m-d');
$specialization_filter = isset($_GET['specialization']) ? trim($_GET['specialization']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // Default to start of month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : $current_date;

// Pagination configuration
$per_page = 10;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $per_page;

// Fetch unique list of specializations for the dropdown filter
$spec_stmt = $pdo->query("SELECT DISTINCT specialization FROM doctors WHERE specialization IS NOT NULL AND specialization != '' ORDER BY specialization ASC");
$all_specializations = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);

// Reusable WHERE clause components
$where_sql = " WHERE 1=1";
$params = [];

if (!empty($date_from)) {
    $where_sql .= " AND (os.surgery_date >= ? OR os.surgery_date IS NULL)";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_sql .= " AND (os.surgery_date <= ? OR os.surgery_date IS NULL)";
    $params[] = $date_to;
}

if (!empty($specialization_filter)) {
    $where_sql .= " AND surgeon.specialization = ?";
    $params[] = $specialization_filter;
}

// 1. Count total matching records for pagination calculation
$count_sql = "
    SELECT COUNT(DISTINCT surgeon.doctor_id) 
    FROM doctors surgeon
    LEFT JOIN or_schedules os ON surgeon.doctor_id = os.surgeon_doctor_id
    LEFT JOIN procedures pr ON os.procedure_id = pr.procedure_id
    " . $where_sql;

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_records / $per_page));

// Ensure current page does not exceed total pages
if ($current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $per_page;
}

// 2. Build dynamic SQL query for surgeon workload with LIMIT and OFFSET
$sql = "
    SELECT 
        surgeon.doctor_id,
        surgeon.first_name AS surgeon_first_name,
        surgeon.middle_name AS surgeon_middle_name,
        surgeon.last_name AS surgeon_last_name,
        surgeon.suffix_name AS surgeon_suffix_name,
        surgeon.specialization,
        
        COUNT(os.schedule_id) AS total_cases,
        SUM(CASE WHEN LOWER(os.status) = 'completed' THEN 1 ELSE 0 END) AS completed_cases,
        SUM(CASE WHEN LOWER(os.status) = 'scheduled' OR LOWER(os.status) = 'confirmed' THEN 1 ELSE 0 END) AS scheduled_cases,
        SUM(CASE WHEN LOWER(os.status) = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_cases,
        
        GROUP_CONCAT(DISTINCT pr.procedure_name SEPARATOR ', ') AS assigned_procedures,
        GROUP_CONCAT(DISTINCT os.surgery_date ORDER BY os.surgery_date DESC SEPARATOR ', ') AS schedule_dates

    FROM doctors surgeon

    LEFT JOIN or_schedules os 
        ON surgeon.doctor_id = os.surgeon_doctor_id

    LEFT JOIN procedures pr 
        ON os.procedure_id = pr.procedure_id

    " . $where_sql . "

    GROUP BY surgeon.doctor_id 
    ORDER BY total_cases DESC, surgeon.last_name ASC
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($sql);
$paramIndex = 1;
foreach ($params as $val) {
    $stmt->bindValue($paramIndex++, $val);
}
$stmt->bindValue($paramIndex++, (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$surgeons_report_data = $stmt->fetchAll();

// Calculate metrics based on dataset (full summary metrics can be calculated across all records if preferred, or page-level)
$total_active_surgeons = $total_records;
$total_procedures_handled = 0;
$total_completed = 0;

// To get accurate global totals across all pages for the metric cards, query global sums separately if needed:
$metrics_sql = "
    SELECT 
        COUNT(DISTINCT surgeon.doctor_id) AS total_surgeons,
        COUNT(os.schedule_id) AS total_cases,
        SUM(CASE WHEN LOWER(os.status) = 'completed' THEN 1 ELSE 0 END) AS completed_cases
    FROM doctors surgeon
    LEFT JOIN or_schedules os ON surgeon.doctor_id = os.surgeon_doctor_id
    LEFT JOIN procedures pr ON os.procedure_id = pr.procedure_id
    " . $where_sql;
$metrics_stmt = $pdo->prepare($metrics_sql);
$metrics_stmt->execute($params);
$global_metrics = $metrics_stmt->fetch();

function formatDoctorName($first, $middle, $last, $suffix = null) {
    $name = trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last);
    if (!empty($suffix)) $name .= ', ' . $suffix;
    return $name;
}

$date_range_label = "All Dates";
if (!empty($date_from) && !empty($date_to)) {
    if ($date_from === $date_to) {
        $date_range_label = date('M d, Y', strtotime($date_from));
    } else {
        $date_range_label = date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to));
    }
}
$spec_filter_label = !empty($specialization_filter) ? "Specialization: " . htmlspecialchars($specialization_filter) : 'All Specializations';
?>

<!-- Shared Hub Styles & Dedicated Module Styles -->
<link rel="stylesheet" href="../assets/css/reports.css?v=20260920">
<link rel="stylesheet" href="../assets/css/report_surgeons.css?v=20260920">

<main class="reports-page">

    <!-- HERO HEADER SECTION -->
    <header class="reports-hero">
        <div class="hero-content">
            <span class="hero-kicker">
                <i class="bi bi-person-badge-fill"></i> Surgical Workload Module
            </span>
            <h1>Surgeon Performance Report</h1>
            <p>Analyze surgical workload, assigned procedures, case statuses, and individual surgeon schedules.</p>
        </div>
        <div class="hero-badge">
            <i class="bi bi-clipboard2-pulse-fill"></i>
            <div>
                <strong>Surgeon Analytics</strong>
                <span>OR Intelligence System</span>
            </div>
        </div>
    </header>

    <!-- METRICS OVERVIEW -->
    <section class="stats-overview">
        <div class="stat-card">
            <div class="stat-icon total"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <h4>Active Surgeons</h4>
                <div class="stat-value" id="stat-total"><?= number_format($global_metrics['total_surgeons']) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon scheduled"><i class="bi bi-journal-medical"></i></div>
            <div class="stat-info">
                <h4>Total Assigned Cases</h4>
                <div class="stat-value" id="stat-scheduled"><?= number_format($global_metrics['total_cases']) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon completed"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-info">
                <h4>Completed Cases</h4>
                <div class="stat-value" id="stat-completed"><?= number_format($global_metrics['completed_cases']) ?></div>
            </div>
        </div>
    </section>

    <!-- TOOLBAR & FILTERS (Horizontal Row Layout) -->
    <section class="toolbar-panel">
        <form method="GET" class="filter-form-wrapper" id="filterForm">
            <div class="filter-horizontal-row">
                
                <div class="filter-group">
                    <label for="specialization">Specialization Filter</label>
                    <select id="specialization" name="specialization" class="form-control">
                        <option value="">All Specializations</option>
                        <?php foreach ($all_specializations as $spec): ?>
                            <option value="<?= htmlspecialchars($spec) ?>" <?= $specialization_filter == $spec ? 'selected' : '' ?>>
                                <?= htmlspecialchars($spec) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date_from">From Date</label>
                    <input type="date" id="date_from" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="filter-group">
                    <label for="date_to">To Date</label>
                    <input type="date" id="date_to" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <div class="filter-actions-inline">
                    <label>&nbsp;</label>
                    <div class="btn-group-inline">
                        <button type="submit" class="btn-primary"><i class="bi bi-funnel-fill"></i> Filter</button>
                        <button type="button" class="reset-btn-custom" onclick="resetFilters()"><i class="bi bi-x-lg"></i> Reset</button>
                        <button type="button" class="export-btn-custom" onclick="openExportModal()"><i class="bi bi-download"></i> Export</button>
                        <button type="button" class="print-btn-custom" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                    </div>
                </div>

            </div>
        </form>
    </section>

    <!-- DATA TABLE CARD WITH REPORT HEADER -->
    <section class="table-card">
        <div class="report-card-header" style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">Surgeon Workload & Schedule Summary</h3>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">
                    Date Range: <?= $date_range_label ?> | <?= $spec_filter_label ?>
                </p>
            </div>
            <div style="background: #e2e8f0; color: #334155; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">
                <i class="bi bi-list-task"></i> Showing <?= count($surgeons_report_data) ?> of <?= $total_records ?> surgeons
            </div>
        </div>

        <div class="table-responsive">
            <table class="report-table" id="surgeonReportTable">
                <thead>
                    <tr>
                        <th>Surgeon Name</th>
                        <th>Specialization</th>
                        <th>Assigned Procedures</th>
                        <th>Total Cases</th>
                        <th>Completed</th>
                        <th>Scheduled</th>
                        <th>Cancelled</th>
                        <th>Schedule Dates</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($surgeons_report_data)): ?>
                        <tr>
                            <td colspan="8">
                                <div style="padding: 40px 20px; text-align: center; color: #64748b;">
                                    <i class="bi bi-person-x" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                    <strong style="font-size: 15px; color: #475569; display: block; margin-bottom: 4px;">No Surgeon Data Found</strong>
                                    <span style="font-size: 13px;">No records match your filter parameters.</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($surgeons_report_data as $row): 
                            $surgeonFullName = formatDoctorName($row['surgeon_first_name'], $row['surgeon_middle_name'], $row['surgeon_last_name'], $row['surgeon_suffix_name']);
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($surgeonFullName) ?></strong></td>
                                <td><?= htmlspecialchars($row['specialization'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['assigned_procedures'] ?: 'None Assigned') ?></td>
                                <td><span style="font-weight: 600; color: #0284c7;"><?= (int)$row['total_cases'] ?></span></td>
                                <td><span class="badge badge-completed"><?= (int)$row['completed_cases'] ?></span></td>
                                <td><span class="badge badge-scheduled"><?= (int)$row['scheduled_cases'] ?></span></td>
                                <td><span class="badge status-cancelled"><?= (int)$row['cancelled_cases'] ?></span></td>
                                <td style="font-size: 12px; color: #475569;"><?= htmlspecialchars($row['schedule_dates'] ?: 'No Schedules') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION CONTROLS -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-footer">
                <div class="pagination-info">
                    Page <strong><?= $current_page ?></strong> of <strong><?= $total_pages ?></strong>
                </div>
                <div class="pagination-controls">
                    <?php 
                        // Preserve filter query parameters for pagination links
                        $queryParams = $_GET;
                        unset($queryParams['page']);
                        $queryString = http_build_query($queryParams);
                        $queryPrefix = $queryString ? '&' . $queryString : '';
                    ?>
                    
                    <a href="?page=1<?= $queryPrefix ?>" class="page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                        <i class="bi bi-chevron-double-left"></i> First
                    </a>
                    <a href="?page=<?= max(1, $current_page - 1) ?><?= $queryPrefix ?>" class="page-link <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                        <i class="bi bi-chevron-left"></i> Prev
                    </a>

                    <span class="page-numbers-text">
                        Page <?= $current_page ?>
                    </span>

                    <a href="?page=<?= min($total_pages, $current_page + 1) ?><?= $queryPrefix ?>" class="page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="?page=<?= $total_pages ?><?= $queryPrefix ?>" class="page-link <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                        Last <i class="bi bi-chevron-double-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </section>

</main>

<!-- EXPORT DIALOG MODAL -->
<div class="modal-overlay" id="exportModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Export Surgeon Report</h3>
            <button type="button" class="modal-close-btn" onclick="closeExportModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <p>Choose your preferred export format for the filtered surgeon workload dataset.</p>
            <div class="export-options-list">
                <button type="button" class="export-option-btn pdf" onclick="exportAsPDF()">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Export as PDF Document
                </button>
                <button type="button" class="export-option-btn excel" onclick="exportAsExcel()">
                    <i class="bi bi-file-earmark-excel-fill"></i> Export as Excel Spreadsheet (.xlsx)
                </button>
                <button type="button" class="export-option-btn csv" onclick="exportToCSV()">
                    <i class="bi bi-file-earmark-text-fill"></i> Export as CSV Document
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

function exportAsPDF() {
    closeExportModal();
    window.print();
}

function exportAsExcel() {
    let table = document.querySelector("#surgeonReportTable"); 
    if (!table) {
        alert("Error: No data table found to export.");
        closeExportModal();
        return;
    }

    let rows = table.querySelectorAll("tr");
    let csv = [];

    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ").trim();
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        if (row.length > 0) csv.push(row.join(","));
    }

    let csvContent = "data:text/csv;charset=utf-8," + encodeURIComponent(csv.join("\n"));
    let downloadLink = document.createElement("a");
    downloadLink.setAttribute("href", csvContent);
    downloadLink.setAttribute("download", "Surgeon_Workload_Report_" + new Date().toISOString().slice(0,10) + ".csv");
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);

    closeExportModal();
}

function exportToCSV() {
    exportAsExcel();
}
</script>

<?php
require_once "../includes/footer.php";
?>