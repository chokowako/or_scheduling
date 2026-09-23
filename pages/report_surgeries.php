<?php

require_once __DIR__ . '/../config/database.php';

$page_title = 'Surgery Report';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$patient_search = trim($_GET['patient_search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

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
        r.room_name,
        
        os.cardiologist,
        os.circulating_nurse,
            
        os.pre_op_diagnosis,
        os.post_op_diagnosis,
        os.drains,
        os.specimen_lab_exam,
        os.sponge_count_verified,
        os.remarks,
        os.infections
        
	

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

// Handle Export to CSV or Excel Action securely from backend
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    if ($export_type === 'csv' || $export_type === 'excel') {
        $is_excel = ($export_type === 'excel');
        $ext = $is_excel ? 'xls' : 'csv';
        $content_type = $is_excel ? 'application/vnd.ms-excel' : 'text/csv; charset=utf-8';
        
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename=surgery_report_' . date('Y-m-d') . '.' . $ext);
        
        $output = fopen('php://output', 'w');
        
        // Add Column Headers including Priority and Clinical Logs
        fputcsv($output, [
            'Surgery Date', 'Start Time', 'End Time', 'Patient Name', 'Patient Number', 
            'Procedure', 'Surgical Type', 'Surgeon', 'Anesthesiologist', 'Operating Room', 
            'Priority', 'STAT', 'Status', 'Pre-Op Diagnosis', 'Post-Op Diagnosis', 
            'Drains', 'Specimen/Lab Exam', 'Sponge & Count Verified'
        ]);
        
        foreach ($surgeries as $s) {
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
            ]);
        }
        fclose($output);
        exit;
    }
}

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

?>

<link rel="stylesheet" href="../assets/css/report_surgeries.css?v=20260920">

<main class="surgery-report-page">

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
            Review completed procedures, post-operative outcomes, clinical logs, and historical surgical case details over a selected period.
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
            <button type="button" class="print-btn-custom" onclick="window.print()"><i class="bi bi-printer-fill"></i> Print</button>
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

                        // Prepare formatted names for the detail view modal
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
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Export as PDF Document / Print
                </button>

                <button type="button" class="export-option-btn excel" onclick="exportAsExcel()">
                    <i class="bi bi-file-earmark-text-fill text-primary"></i> Export as Excel Spreadsheet (.xls)
                </button>

                <button type="button" class="export-option-btn csv" onclick="exportAsCSV()">
                    <i class="bi bi-file-earmark-text-fill text-primary"></i> Export as CSV Document
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
					<div class="Patient-icon">
						<i class="bi bi-person-fill"></i>
					</div>
					<div>
						<h2>Patient Details</h2>
					</div>
				</div>
					
				<div class="patient-detail-grid">
					<div class="patient-name-container">
						<span class="patient-label">Patient Name:</span>
						<span class="patient-value" id="det_patient_name"></span>
					</div>
					
					<div class="patient-name-container">
						<span class="patient-label">Birthdate:</span>
						<span class="patient-value" id="det_birth_date"></span>
					</div>
					
					<div class="patient-name-container">
						<span class="patient-label">Registry No:</span>
						<span class="patient-value" id="det_patient_no"></span>
					</div>
					
					<div class="patient-name-container">
						<span class="patient-label">Registry Type:</span>
						<span class="patient-value" id="det_registry_type"></span>
					</div>

					<div class="patient-name-container">
						<span class="patient-label">Room:</span>
						<span class="patient-value" id="det_room"></span>
					</div>
				</div>
			</section>
	
	
            <table class="table table-bordered table-sm">
		           
                <tr>
                    <th>Procedure:</th>
                    <td id="det_procedure"></td>
                </tr>
                <tr>
                    <th>Surgical Type:</th>
                    <td id="det_surgical_type"></td>
                </tr>
                <tr>
                    <th>Surgery Date & Time:</th>
                    <td id="det_datetime"></td>
                </tr>
                <tr>
                    <th>Operating Room:</th>
                    <td id="det_room"></td>
                </tr>
                <tr>
                    <th>Surgeon:</th>
                    <td id="det_surgeon"></td>
                </tr>
                <tr>
                    <th>Anesthesiologist:</th>
                    <td id="det_anesthesiologist"></td>
                </tr>
                <tr>
                    <th>Priority & STAT:</th>
                    <td id="det_priority_stat"></td>
                </tr>
                <tr>
                    <th>Case Status:</th>
                    <td id="det_status"></td>
                </tr>
                <tr>
                    <th>Pre-Operative Diagnosis:</th>
                    <td id="det_pre_op"></td>
                </tr>
                <tr>
                    <th>Post-Operative Diagnosis:</th>
                    <td id="det_post_op"></td>
                </tr>
                <tr>
                    <th>Drains:</th>
                    <td id="det_drains"></td>
                </tr>
                <tr>
                    <th>Specimen / Lab Exam:</th>
                    <td id="det_specimen"></td>
                </tr>
                <tr>
                    <th>Sponge & Count Verified:</th>
                    <td id="det_sponge"></td>
                </tr>			
				 <tr>
                    <th>anesthetic:</th>
                    <td id="det_anesthetic"></td>
                </tr>
				 <tr>
                    <th>cardiologist:</th>
                    <td id="det_cardiologist"></td>
                </tr>
				 <tr>
                    <th>circulating nurse:</th>
                    <td id="det_circulating_nurse"></td>
                </tr>
				 <tr>
                    <th>remarks:</th>
                    <td id="det_remarks"></td>
                </tr>
				<tr>
                    <th>infections:</th>
                    <td id="det_infections"></td>
                </tr>
				
				
			
            </table>
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
    document.getElementById('filterForm').reset();
    applyFilters();
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
	document.getElementById('det_registry_type').innerText = data.registry_type || data.registry_type || '—';
	document.getElementById('det_room').innerText = data.room_name || '—';
	
    document.getElementById('det_procedure').innerText = data.procedure_name || '—';
    document.getElementById('det_surgical_type').innerText = data.procedure_surgical_type || 'General';
    document.getElementById('det_datetime').innerText = data.formatted_date + ' (' + data.formatted_start_time + ' - ' + data.formatted_end_time + ')';    
    document.getElementById('det_surgeon').innerText = data.formatted_surgeon || '—';
    document.getElementById('det_anesthesiologist').innerText = data.formatted_anesthesiologist || '—';
    document.getElementById('det_priority_stat').innerText = data.display_priority + (data.display_stat === 'YES' ? ' (STAT)' : '');
    document.getElementById('det_status').innerText = data.status || '—';
    document.getElementById('det_pre_op').innerText = data.pre_op_diagnosis || '—';
    document.getElementById('det_post_op').innerText = data.post_op_diagnosis || '—';
    document.getElementById('det_drains').innerText = data.drains || '—';
    document.getElementById('det_specimen').innerText = data.specimen_lab_exam || '—';
    document.getElementById('det_sponge').innerText = data.sponge_count_verified || '—';

	document.getElementById('det_anesthetic').innerText = data.anesthetic || '—';
	document.getElementById('det_cardiologist').innerText = data.cardiologist || '—';
	document.getElementById('det_circulating_nurse').innerText = data.circulating_nurse || '—';
	document.getElementById('det_remarks').innerText = data.remarks || '—';
	document.getElementById('det_infections').innerText = data.infections || '—';
	

    document.getElementById('detailModal').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('active');
}

function printSingleRecord() {
    // Opens standard print dialog. You can add a print media CSS query if you want to isolate only the modal content during print.
    window.print();
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

function exportAsPDF() {
    closeExportModal();
    window.print();
}

function exportAsExcel() {
    let table = document.querySelector(".surgery-report-table") || document.querySelector("table"); 
    
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>