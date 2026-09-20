<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$page_title = "Reports Hub";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<link rel="stylesheet" href="../assets/css/reports.css?v=20260920">

<main class="reports-page">

    <!-- HERO HEADER SECTION -->
    <header class="reports-hero">
        <div class="hero-content">
            <span class="hero-kicker">
                <i class="bi bi-shield-check"></i> System Analytics & Audit
            </span>
            <h1>Reports Hub</h1>
            <p>Access real-time operating room schedules, clinical logs, room utilization metrics, and surgeon analytics.</p>
        </div>
        <div class="hero-badge">
            <i class="bi bi-bar-chart-line-fill"></i>
            <div>
                <strong>6 Active Modules</strong>
                <span>OR Intelligence System</span>
            </div>
        </div>
    </header>

    <!-- REPORT SELECTION PANEL -->
    <section class="reports-panel">
        
        <div class="panel-header">
            <div>
                <h2>Available Reports</h2>
                <p>Select a specialized module below to generate, filter, and export detailed departmental records.</p>
            </div>
        </div>

        <div class="reports-grid">

            <!-- OR SCHEDULE REPORT -->
            <a href="report_or_schedule.php" class="report-card">
                <div class="card-icon-wrapper icon-schedule">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div class="report-card-content">
                    <div class="card-tag">Real-Time / Operational</div>
                    <h3>OR Schedule Report</h3>
                    <p>View upcoming and ongoing operating room bookings, daily time slots, room assignments, and real-time surgical schedules.</p>
                </div>
                <div class="card-footer">
                    <span>Open Module</span>
                    <i class="bi bi-arrow-right-short"></i>
                </div>
            </a>

            <!-- SURGERY REPORT -->
            <a href="report_surgeries.php" class="report-card">
                <div class="card-icon-wrapper icon-surgeries">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                </div>
                <div class="report-card-content">
                    <div class="card-tag">Clinical / Historical</div>
                    <h3>Surgery Report</h3>
                    <p>Review completed procedures, post-operative outcomes, clinical logs, and historical surgical case details over a selected period.</p>
                </div>
                <div class="card-footer">
                    <span>Open Module</span>
                    <i class="bi bi-arrow-right-short"></i>
                </div>
            </a>

            <!-- PATIENT REPORT -->
            <a href="patient_reports.php" class="report-card">
                <div class="card-icon-wrapper icon-patients">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="report-card-content">
                    <div class="card-tag">Patient Records</div>
                    <h3>Patient Report</h3>
                    <p>Track patient surgical history, medical record numbers (MRN), scheduled procedures, and individual case logs.</p>
                </div>
                <div class="card-footer">
                    <span>Open Module</span>
                    <i class="bi bi-arrow-right-short"></i>
                </div>
            </a>

            <!-- SURGEON REPORT -->
            <a href="report_surgeons.php" class="report-card">
                <div class="card-icon-wrapper icon-surgeons">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div class="report-card-content">
                    <div class="card-tag">Staff Analytics</div>
                    <h3>Surgeon Report</h3>
                    <p>Analyze surgical workload, assigned procedures, specialty performance, and individual surgeon schedules.</p>
                </div>
                <div class="card-footer">
                    <span>Open Module</span>
                    <i class="bi bi-arrow-right-short"></i>
                </div>
            </a>

            <!-- OR UTILIZATION REPORT -->
            <a href="report_room_utilization.php" class="report-card">
                <div class="card-icon-wrapper icon-utilization">
                    <i class="bi bi-door-open-fill"></i>
                </div>
                <div class="report-card-content">
                    <div class="card-tag">Efficiency & Capacity</div>
                    <h3>OR Utilization Report</h3>
                    <p>Evaluate operating room occupancy rates, peak usage hours, turnaround efficiency, and room availability metrics.</p>
                </div>
                <div class="card-footer">
                    <span>Open Module</span>
                    <i class="bi bi-arrow-right-short"></i>
                </div>
            </a>

            <!-- SCHEDULE STATUS REPORT -->
            <div class="report-card-container">
                <a href="report_status.php" class="report-card">
                    <div class="card-icon-wrapper icon-status">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    </div>
                    <div class="report-card-content">
                        <div class="card-tag">Performance & Audit</div>
                        <h3>Schedule Status Report</h3>
                        <p>Monitor procedure completion rates, cancellations, delays, and emergency/STAT status breakdowns across rooms.</p>
                    </div>
                    <div class="card-footer">
                        <span>Open Module</span>
                        <i class="bi bi-arrow-right-short"></i>
                    </div>
                </a>
            </div>

        </div>

    </section>

</main>

<?php

require_once "../includes/footer.php";

?>