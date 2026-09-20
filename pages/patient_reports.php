<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$page_title = "Patient Report";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

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
                <div class="stat-value" id="stat-total">4</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon scheduled"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-info">
                <h4>Scheduled</h4>
                <div class="stat-value" id="stat-scheduled">2</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon completed"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-info">
                <h4>Completed</h4>
                <div class="stat-value" id="stat-completed">1</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cancelled"><i class="bi bi-x-circle"></i></div>
            <div class="stat-info">
                <h4>Cancelled</h4>
                <div class="stat-value" id="stat-cancelled">1</div>
            </div>
        </div>
    </section>

    <!-- TOOLBAR & FILTERS -->
    <section class="toolbar-panel">
        <form onsubmit="applyFilters(event)" class="filter-grid" id="filterForm">
            <div class="filter-group">
                <label for="search">Search Patient / MRN</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Name or MRN...">
            </div>

            <div class="filter-group">
                <label for="status">Procedure Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="date_from">From Date</label>
                <input type="date" id="date_from" name="date_from" class="form-control">
            </div>

            <div class="filter-group">
                <label for="date_to">To Date</label>
                <input type="date" id="date_to" name="date_to" class="form-control">
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Filter</button>
                <button type="button" class="btn btn-cancel" onclick="resetFilters()"><i class="bi bi-x-lg"></i> Reset</button>
                <button type="button" class="btn btn-secondary" onclick="openExportModal()"><i class="bi bi-download"></i> Export</button>
                <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            </div>
        </form>
    </section>

    <!-- DATA TABLE -->
    <section class="table-card">
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
                        <th class="action-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-status="scheduled" data-date="2026-09-20">
                        <td><strong>MRN-2026-0812</strong></td>
                        <td class="patient-name">Eleanor Vance</td>
                        <td>Laparoscopic Cholecystectomy</td>
                        <td>Dr. Sarah Jenkins</td>
                        <td>Sep 20, 2026 - 08:30 AM</td>
                        <td><span class="badge badge-scheduled">Scheduled</span></td>
                        <td class="action-cell">
                            <button type="button" class="btn btn-cancel" onclick="confirmCancel('MRN-2026-0812')">
                                <i class="bi bi-slash-circle"></i> Cancel
                            </button>
                        </td>
                    </tr>
                    <tr data-status="scheduled" data-date="2026-09-20">
                        <td><strong>MRN-2026-0813</strong></td>
                        <td class="patient-name">Marcus Brody</td>
                        <td>Coronary Artery Bypass</td>
                        <td>Dr. Alan Grant</td>
                        <td>Sep 20, 2026 - 10:15 AM</td>
                        <td><span class="badge badge-scheduled">Scheduled</span></td>
                        <td class="action-cell">
                            <button type="button" class="btn btn-cancel" onclick="confirmCancel('MRN-2026-0813')">
                                <i class="bi bi-slash-circle"></i> Cancel
                            </button>
                        </td>
                    </tr>
                    <tr data-status="completed" data-date="2026-09-19">
                        <td><strong>MRN-2026-0798</strong></td>
                        <td class="patient-name">Clara Oswald</td>
                        <td>Appendectomy</td>
                        <td>Dr. Sarah Jenkins</td>
                        <td>Sep 19, 2026 - 02:00 PM</td>
                        <td><span class="badge badge-completed">Completed</span></td>
                        <td class="action-cell">-</td>
                    </tr>
                    <tr data-status="cancelled" data-date="2026-09-18">
                        <td><strong>MRN-2026-0754</strong></td>
                        <td class="patient-name">Arthur Pendelton</td>
                        <td>Total Knee Arthroplasty</td>
                        <td>Dr. Robert Chen</td>
                        <td>Sep 18, 2026 - 09:00 AM</td>
                        <td><span class="badge status-cancelled">Cancelled</span></td>
                        <td class="action-cell">-</td>
                    </tr>
                </tbody>
            </table>
            <div id="noDataMsg" style="display: none; padding: 24px; text-align: center; color: #64748b; font-size: 13px;">
                No matching patient records found.
            </div>
        </div>
    </section>

</main>

<!-- EXPORT DIALOG MODAL -->
<div class="modal-overlay" id="exportModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Export Patient Report</h3>
            <button type="button" class="btn btn-secondary" onclick="closeExportModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <p style="font-size: 13px; color: #475569; margin-bottom: 20px;">
            Choose your preferred export format for the filtered patient record dataset.
        </p>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a href="export_patients.php?type=pdf" class="btn btn-secondary" style="justify-content: flex-start;">
                <i class="bi bi-file-earmark-pdf-fill" style="color: #e11d48;"></i> Export as PDF Document
            </a>
            <a href="export_patients.php?type=excel" class="btn btn-secondary" style="justify-content: flex-start;">
                <i class="bi bi-file-earmark-excel-fill" style="color: #15803d;"></i> Export as Excel Spreadsheet (.xlsx)
            </a>
            <a href="export_patients.php?type=csv" class="btn btn-secondary" style="justify-content: flex-start;">
                <i class="bi bi-file-earmark-text-fill" style="color: #0284c7;"></i> Export as CSV Document
            </a>
        </div>
        <div style="margin-top: 24px; text-align: right;">
            <button type="button" class="btn btn-cancel" onclick="closeExportModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
function applyFilters(e) {
    if(e) e.preventDefault();
    
    const search = document.getElementById('search').value.toLowerCase().trim();
    const status = document.getElementById('status').value.toLowerCase();
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;

    const rows = document.querySelectorAll('#patientsTable tbody tr');
    let visibleCount = 0;

    let tot = 0, sched = 0, comp = 0, canc = 0;

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const rowStatus = row.getAttribute('data-status');
        const rowDate = row.getAttribute('data-date');

        let matchesSearch = !search || text.includes(search);
        let matchesStatus = !status || rowStatus === status;
        let matchesDateFrom = !dateFrom || rowDate >= dateFrom;
        let matchesDateTo = !dateTo || rowDate <= dateTo;

        if (matchesSearch && matchesStatus && matchesDateFrom && matchesDateTo) {
            row.style.display = '';
            visibleCount++;
            
            tot++;
            if (rowStatus === 'scheduled') sched++;
            if (rowStatus === 'completed') comp++;
            if (rowStatus === 'cancelled') canc++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('noDataMsg').style.display = visibleCount === 0 ? 'block' : 'none';

    document.getElementById('stat-total').innerText = tot;
    document.getElementById('stat-scheduled').innerText = sched;
    document.getElementById('stat-completed').innerText = comp;
    document.getElementById('stat-cancelled').innerText = canc;
}

function resetFilters() {
    document.getElementById('filterForm').reset();
    applyFilters();
}

function openExportModal() {
    document.getElementById('exportModal').classList.add('active');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.remove('active');
}

function confirmCancel(mrn) {
    if (confirm("Are you sure you want to cancel procedure for patient " + mrn + "?")) {
        window.location.href = "cancel_procedure.php?mrn=" + mrn;
    }
}
</script>

<?php

require_once "../includes/footer.php";

?>