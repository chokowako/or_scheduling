
<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$page_title = "Reports";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<link
    rel="stylesheet"
    href="../assets/css/reports.css?v=20260919"
>


<main class="reports-page">

    <!-- PAGE HEADER -->
    <div class="reports-page-header">

        <div>

            <div class="reports-kicker">
                SYSTEM REPORTS
            </div>

            <h1>
                Reports
            </h1>

            <p>
                View and generate reports from the OR Scheduling System.
            </p>

        </div>

    </div>


    <!-- REPORT PANEL -->
    <section class="reports-panel">

        <div class="reports-panel-header">

            <div>

                <div class="reports-panel-kicker">
                    REPORT CENTER
                </div>

                <h2>
                    Available Reports
                </h2>

                <p>
                    Select a report below to view detailed information.
                </p>

            </div>

        </div>


        <div class="reports-grid">


            <!-- OR SCHEDULE REPORT -->
            <div class="report-card">

                <div class="report-card-icon">
                    <i class="bi bi-calendar3"></i>
                </div>

                <div class="report-card-content">

                    <h3>
                        OR Schedule Report
                    </h3>

                    <p>
                        View scheduled operating room activities and surgery schedules.
                    </p>

                    <a
                        href="report_or_schedule.php"
                        class="report-card-link"
                    >
                        View Report
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            <!-- SURGERY REPORT -->
            <div class="report-card">

                <div class="report-card-icon">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                </div>

                <div class="report-card-content">

                    <h3>
                        Surgery Report
                    </h3>

                    <p>
                        View surgery and procedure records within a selected period.
                    </p>

                    <a
                        href="report_surgeries.php"
                        class="report-card-link"
                    >
                        View Report
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            <!-- PATIENT REPORT -->
            <div class="report-card">

                <div class="report-card-icon">
                    <i class="bi bi-people-fill"></i>
                </div>

                <div class="report-card-content">

                    <h3>
                        Patient Report
                    </h3>

                    <p>
                        View patient records and scheduled surgical procedures.
                    </p>

                    <a
                        href="report_patients.php"
                        class="report-card-link"
                    >
                        View Report
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            <!-- SURGEON REPORT -->
            <div class="report-card">

                <div class="report-card-icon">
                    <i class="bi bi-person-badge-fill"></i>
                </div>

                <div class="report-card-content">

                    <h3>
                        Surgeon Report
                    </h3>

                    <p>
                        View surgical activity and procedures associated with surgeons.
                    </p>

                    <a
                        href="report_surgeons.php"
                        class="report-card-link"
                    >
                        View Report
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            <!-- OR UTILIZATION REPORT -->
            <div class="report-card">

                <div class="report-card-icon">
                    <i class="bi bi-door-open-fill"></i>
                </div>

                <div class="report-card-content">

                    <h3>
                        OR Utilization Report
                    </h3>

                    <p>
                        View operating room usage and scheduled activities.
                    </p>

                    <a
                        href="report_room_utilization.php"
                        class="report-card-link"
                    >
                        View Report
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            <!-- SCHEDULE STATUS REPORT -->
            <div class="report-card">

                <div class="report-card-icon">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                </div>

                <div class="report-card-content">

                    <h3>
                        Schedule Status Report
                    </h3>

                    <p>
                        View scheduled, completed, cancelled, and rescheduled procedures.
                    </p>

                    <a
                        href="report_status.php"
                        class="report-card-link"
                    >
                        View Report
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


        </div>

    </section>

</main>


<?php

require_once "../includes/footer.php";

?>

