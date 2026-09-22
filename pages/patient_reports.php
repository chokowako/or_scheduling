<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$page_title = "Patient Report";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

// Get filter inputs from GET request (default to current date on first load)
$current_date = date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : $current_date;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : $current_date;

// Build dynamic SQL query for patient reports/schedules
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

    INNER JOIN patients p
        ON os.patient_id = p.patient_id

    INNER JOIN procedures pr
        ON os.procedure_id = pr.procedure_id

    INNER JOIN doctors surgeon
        ON os.surgeon_doctor_id = surgeon.doctor_id

    WHERE 1=1
";

$params = [];

if (!empty($date_from)) {
    $sql .= " AND os.surgery_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND os.surgery_date <= ?";
    $params[] = $date_to;
}

if (!empty($search)) {
    $sql .= " AND (CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name) LIKE ? OR os.patient_registry_no LIKE ? OR p.patient_number LIKE ?)";
    $wildcard = "%" . $search . "%";
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
}

if (!empty($status)) {
    $sql .= " AND LOWER(os.status) = ?";
    $params[] = strtolower($status);
}

$sql .= " ORDER BY os.surgery_date DESC, os.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients_data = $stmt->fetchAll();

// Calculate metrics based on filtered dataset
$total_patients = count($patients_data);
$scheduled_count = 0;
$completed_count = 0;
$cancelled_count = 0;

foreach ($patients_data as $row) {
    $st = strtolower(trim($row['status']));
    if ($st === 'scheduled' || $st === 'confirmed') $scheduled_count++;
    if ($st === 'completed') $completed_count++;
    if ($st === 'cancelled') $cancelled_count++;
}

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

// Build human-readable filter summary for the report card header
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

<main class="reports-page">

    <!-- HERO HEADER SECTION -->
    <header class="reports-hero">
        <div class="hero-content">
            <span class="hero-kicker">
                <i class="bi bi-people-fill"></i> Clinical Module
            </span>
            <h1>Patient Reporting</h1>
            <p>Track surgical histories, procedure statuses, clinical logs, and patient records.</p>
        </div>
        <div class="hero-badge">
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

    <!-- TOOLBAR & FILTERS -->
    <section class="toolbar-panel">
        <form method="GET" class="filter-grid" id="filterForm">
            <div class="filter-group">
                <label for="search">Search Patient / MRN</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Name or MRN..." value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="filter-group">
                <label for="status">Procedure Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?= strtolower($status) === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="completed" <?= strtolower($status) === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= strtolower($status) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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

            <div class="btn-group">
                <button type="submit" class="btn-primary"><i class="bi bi-funnel-fill"></i> Filter</button>
                <button type="button" class="reset-btn-custom" onclick="resetFilters()"><i class="bi bi-x-lg"></i> Reset</button>
                <button type="button" class="export-btn-custom" onclick="openExportModal()"><i class="bi bi-download"></i> Export</button>
                <button type="button" class="print-btn-custom" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            </div>
        </form>
    </section>

    <!-- DATA TABLE CARD WITH REPORT HEADER -->
    <section class="table-card">
        <div class="report-card-header" style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">Patient Records</h3>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">
                    Date Range: <?= $date_range_label ?> | Patient / Registry No.: <?= $patient_filter_label ?> | Status: <?= $status_filter_label ?>
                </p>
            </div>
            <div style="background: #e2e8f0; color: #334155; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">
                <i class="bi bi-list-task"></i> <?= count($patients_data) ?> records
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

// Modal Control Functions
function openExportModal() {
    document.getElementById('exportModal').classList.add('active');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.remove('active');
}

// Close modal when clicking outside the container
window.addEventListener('click', function(event) {
    const modal = document.getElementById('exportModal');
    if (event.target === modal) {
        closeExportModal();
    }
});

// Export Actions
function exportAsPDF() {
    closeExportModal();
    window.print();
}

function exportAsExcel() {
    let table = document.querySelector(".report-table") || document.querySelector("table"); 
    
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
    downloadLink.setAttribute("download", "Patient_Report_" + new Date().toISOString().slice(0,10) + ".csv");
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