<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$page_title = 'OR Schedule Report';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Get filter inputs from GET request (default to current date on first load)
$current_date = date('Y-m-d');
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : $current_date;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : $current_date;
$patient_search = isset($_GET['patient_search']) ? trim($_GET['patient_search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build dynamic SQL query with parameters
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

if (!empty($patient_search)) {
    $sql .= " AND (CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name) LIKE ? OR os.patient_registry_no LIKE ? OR p.patient_number LIKE ?)";
    $wildcard = "%" . $patient_search . "%";
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
}

if (!empty($status)) {
    $sql .= " AND LOWER(os.status) = ?";
    $params[] = strtolower($status);
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

// Calculate totals based on fetched database records
$total_schedules = count($schedules);
$stat_count = 0;
$completed_count = 0;
$cancelled_count = 0;

foreach ($schedules as $schedule) {
    if ((int)$schedule['is_stat'] === 1) $stat_count++;
    if (strcasecmp($schedule['status'], 'Completed') === 0) $completed_count++;
    if (strcasecmp($schedule['status'], 'Cancelled') === 0) $cancelled_count++;
}

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

// Build human-readable filter summary strings for the header card
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

<main class="schedule-report-page">

    <div class="report-page-header">
        <div>
            <div class="report-breadcrumb">Reports / OR Schedule Report</div>
            <h1>
                <span class="page-title-icon"><i class="bi bi-calendar-event-fill"></i></span>
                OR Schedule Report
            </h1>
            <p>View and filter operating room daily schedules and resource allocation.</p>
        </div>
    </div>

    <!-- Filter Form -->
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

        <form method="GET" class="report-filter-form" id="filterForm">
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
                    <input type="text" id="patient_search" name="patient_search" placeholder="Search patient or registry number" value="<?= htmlspecialchars($patient_search) ?>">
                </div>
            </div>

            <div class="filter-field">
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

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-action btn-filter"><i class="bi bi-funnel-fill"></i><span> Filter</span></button>
                <button type="button" class="btn btn-cancel btn-action btn-reset" onclick="resetFilters()"><i class="bi bi-x-lg"></i><span> Reset</span></button>
                <button type="button" class="btn btn-secondary btn-action btn-export" onclick="openExportModal()"><i class="bi bi-download"></i><span> Export</span></button>
                <button type="button" class="btn btn-secondary btn-action btn-print" onclick="window.print()"><i class="bi bi-printer"></i><span> Print</span></button>
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
						<th>Priority </th>
						<th>STAT</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($schedules)): ?>
						<tr>
							<td colspan="8">
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
								<!-- 1. Operating Room Assignment -->
								<td>
									<div class="room-cell">
										<i class="bi bi-door-open-fill text-success"></i>
										<strong><?= htmlspecialchars($schedule['room_name']) ?></strong>
									</div>
								</td>

								<!-- 2. Daily Time Slots -->
								<td>
									<div class="time-cell">
										<span><?= htmlspecialchars(formatTimeValue($schedule['start_time'])) ?></span>
										<small>to <?= htmlspecialchars(formatTimeValue($schedule['end_time'])) ?></small>
									</div>
								</td>

								<!-- 3. Surgery Date -->
								<td>
									<div class="date-cell">
										<strong><?= htmlspecialchars(formatDateValue($schedule['surgery_date'])) ?></strong>
									</div>
								</td>

								<!-- 4. Patient Information -->
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

								<!-- 5. Procedure Details -->
								<td>
									<div class="procedure-cell">
										<strong><?= htmlspecialchars($schedule['procedure_name']) ?></strong>
										<small><?= htmlspecialchars($schedule['procedure_surgical_type'] ?: 'Standard') ?></small>
									</div>
								</td>

								<!-- 6. Surgical Team (Surgeon & Anesthesiologist) -->
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

								<!-- 7. Priority & STAT -->
								<td>
									<div style="display: flex; gap: 4px; align-items: center;">
										<span class="priority-badge priority-<?= htmlspecialchars(strtolower($priority)) ?>">
											<?= htmlspecialchars($priority) ?>
										</span>
										
									</div>
								</td>
								
								<!-- 8. STAT -->
								<td>
									<div style="display: flex; gap: 4px; align-items: center;">
											<?php if ($isStatVal === 1): ?>
											<span class="stat-badge"><i class="bi bi-lightning-charge-fill"></i> STAT</span>
											<?php endif; ?>
										</span>
									</div>
								</td>

								<!-- 9. Real-Time Status -->
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

</main>

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
    closeExportModal();
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
        if (row.length > 0) csv.push(row.join(","));
    }

    let csvFile = new Blob([csv.join("\n")], { type: "application/vnd.ms-excel" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "OR_Schedule_Report_" + new Date().toISOString().slice(0,10) + ".xls";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function exportToCSV() {
    exportAsExcel();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>