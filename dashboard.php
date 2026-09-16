<?php

$page_title = "Dashboard";
require_once "config/database.php";

/* =========================================================
   TODAY'S DATE
   ========================================================= */

$today_display = date("l, F j, Y");

/* =========================================================
   OPERATING ROOMS
   ========================================================= */

$roomsStmt = $pdo->query("
    SELECT
        room_id,
        room_name,
        room_description,
        status
    FROM operating_rooms
    ORDER BY room_name ASC
");

$rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   TODAY'S SCHEDULES
   ========================================================= */

$scheduleStmt = $pdo->query("
    SELECT
        os.schedule_id,
        os.surgery_date,
        os.start_time,
        os.end_time,
        os.status,
        os.priority,

        p.first_name AS patient_first_name,
        p.middle_name AS patient_middle_name,
        p.last_name AS patient_last_name,

        s.first_name AS surgeon_first_name,
        s.last_name AS surgeon_last_name,

        a.first_name AS anesthesiologist_first_name,
        a.last_name AS anesthesiologist_last_name,

        r.room_name,

        pr.procedure_name

    FROM or_schedules os

    LEFT JOIN patients p
        ON os.patient_id = p.patient_id

    LEFT JOIN doctors s
        ON os.surgeon_doctor_id = s.doctor_id

    LEFT JOIN doctors a
        ON os.anesthesiologist_doctor_id = a.doctor_id

    LEFT JOIN operating_rooms r
        ON os.room_id = r.room_id

    LEFT JOIN procedures pr
        ON os.procedure_id = pr.procedure_id

    WHERE os.surgery_date = CURDATE()

    ORDER BY os.start_time ASC
");

$today_schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
$total_today_surgeries = count($today_schedules);

/* =========================================================
   ROOM COUNTS
   ========================================================= */

$total_rooms = count($rooms);
$available_rooms = 0;
$occupied_rooms = 0;
$maintenance_rooms = 0;
$inactive_rooms = 0;

foreach ($rooms as $room) {
    $room_status = strtolower(
        trim($room['status'] ?? '')
    );

    switch ($room_status) {

        case 'available':
            $available_rooms++;
            break;

        case 'occupied':
            $occupied_rooms++;
            break;

        case 'maintenance':
            $maintenance_rooms++;
            break;

        case 'inactive':
            $inactive_rooms++;
            break;
    }
}


/* =========================================================
   SURGERY OVERVIEW
   ========================================================= */

$general_surgery_count = 0;
$obstetrics_count = 0;
$orthopedic_count = 0;
$other_surgery_count = 0;

foreach ($today_schedules as $schedule) {
    $procedure_name = strtolower(
        trim($schedule['procedure_name'] ?? '')
    );

    if ($procedure_name === '') {
        $other_surgery_count++;
        continue;
    }

    if (
        str_contains($procedure_name, 'general')
        || str_contains($procedure_name, 'append')
        || str_contains($procedure_name, 'hernia')
        || str_contains($procedure_name, 'gallbladder')
        || str_contains($procedure_name, 'cholecyst')
    ) {

        $general_surgery_count++;

    } elseif (
        str_contains($procedure_name, 'obstetric')
        || str_contains($procedure_name, 'cesarean')
        || str_contains($procedure_name, 'c-section')
        || str_contains($procedure_name, 'caesarean')
        || str_contains($procedure_name, 'delivery')
        || str_contains($procedure_name, 'maternity')
    ) {
        $obstetrics_count++;
    } elseif (
        str_contains($procedure_name, 'orthopedic')
        || str_contains($procedure_name, 'orthopaedic')
        || str_contains($procedure_name, 'fracture')
        || str_contains($procedure_name, 'knee')
        || str_contains($procedure_name, 'hip')
        || str_contains($procedure_name, 'bone')
        || str_contains($procedure_name, 'joint')
    ) {
        $orthopedic_count++;
    } else {
        $other_surgery_count++;
    }
}


/* =========================================================
   SURGERY OVERVIEW PERCENTAGES
   ========================================================= */

if ($total_today_surgeries > 0) {
    $general_percent =
        round(
            ($general_surgery_count / $total_today_surgeries) * 100
        );
    $obstetrics_percent =
        round(
            ($obstetrics_count / $total_today_surgeries) * 100
        );
    $orthopedic_percent =
        round(
            ($orthopedic_count / $total_today_surgeries) * 100
        );
    $other_percent =
        max(
            0,
            100
            - $general_percent
            - $obstetrics_percent
            - $orthopedic_percent
        );
} else {
    $general_percent = 0;
    $obstetrics_percent = 0;
    $orthopedic_percent = 0;
    $other_percent = 0;
}


/* =========================================================
   DONUT CHART ANGLES
   ========================================================= */

$general_angle =
    ($general_percent / 100) * 360;
$obstetrics_angle =
    $general_angle +
    (($obstetrics_percent / 100) * 360);
$orthopedic_angle =
    $obstetrics_angle +
    (($orthopedic_percent / 100) * 360);

/* =========================================================
   SCHEDULE HELPERS
   ========================================================= */
function dashboard_format_time($time)
{
    if (empty($time)) {
        return '--:--';
    }
    $timestamp = strtotime($time);

    if ($timestamp === false) {
        return '--:--';
    }

    return date('g:i', $timestamp);
}


function dashboard_format_period($time)
{
    if (empty($time)) {
        return '';
    }

    $timestamp = strtotime($time);

    if ($timestamp === false) {
        return '';
    }

    return strtoupper(
        date('A', $timestamp)
    );
}


function dashboard_duration_minutes($start, $end)
{
    if (empty($start) || empty($end)) {
        return 0;
    }

    $start_timestamp = strtotime($start);
    $end_timestamp = strtotime($end);

    if (
        $start_timestamp === false
        || $end_timestamp === false
    ) {
        return 0;
    }

    $minutes = round(
        ($end_timestamp - $start_timestamp) / 60
    );

    return max(0, $minutes);
}


function dashboard_patient_name($schedule)
{
    $name = trim(
        ($schedule['patient_first_name'] ?? '') . ' ' .
        ($schedule['patient_middle_name'] ?? '') . ' ' .
        ($schedule['patient_last_name'] ?? '')
    );

    return $name !== ''
        ? $name
        : 'Unnamed Patient';
}


function dashboard_doctor_name($first_name, $last_name)
{
    $name = trim(
        ($first_name ?? '') . ' ' .
        ($last_name ?? '')
    );

    if ($name === '') {
        return 'Not assigned';
    }

    return 'Dr. ' . $name;
}


function dashboard_status_class($status)
{
    switch (strtolower(trim($status))) {

        case 'scheduled':
            return 'scheduled';

        case 'confirmed':
            return 'confirmed';

        case 'in progress':
            return 'in-progress';

        case 'completed':
            return 'completed';

        case 'cancelled':
            return 'cancelled';

        default:
            return 'default';
    }
}


function dashboard_priority_class($priority)
{
    switch (strtolower(trim($priority))) {

        case 'emergency':
            return 'emergency';

        case 'urgent':
            return 'urgent';

        case 'elective':
            return 'elective';

        default:
            return 'default';
    }
}


function dashboard_priority_icon($priority)
{
    switch (strtolower(trim($priority))) {

        case 'emergency':
            return 'bi-lightning-charge-fill';

        case 'urgent':
            return 'bi-exclamation-circle-fill';

        case 'elective':
            return 'bi-calendar-check';

        default:
            return 'bi-circle';
    }
}


function dashboard_room_status_class($status)
{
    $status = strtolower(trim($status));

    switch ($status) {

        case 'available':
            return 'available-room';

        case 'occupied':
            return 'occupied-room';

        case 'maintenance':
            return 'maintenance-room';

        case 'inactive':
            return 'inactive-room';

        default:
            return 'available-room';
    }
}


function dashboard_room_icon($status)
{
    $status = strtolower(trim($status));

    switch ($status) {

        case 'occupied':
            return 'bi-door-closed';

        case 'maintenance':
            return 'bi-tools';

        case 'inactive':
            return 'bi-slash-circle';

        default:
            return 'bi-door-open';
    }
}


function dashboard_room_status_icon($status)
{
    $status = strtolower(trim($status));

    switch ($status) {

        case 'available':
            return 'bi-check-circle';

        case 'occupied':
            return 'bi-activity';

        case 'maintenance':
            return 'bi-tools';

        case 'inactive':
            return 'bi-dash-circle';

        default:
            return 'bi-check-circle';
    }
}

?>

<?php require_once "includes/header.php"; ?>

<?php require_once "includes/sidebar.php"; ?>

<div class="main-wrapper">

```
<!-- =====================================================
     TOPBAR
     ===================================================== -->

<header class="topbar">

    <div class="topbar-left">

        <button
            type="button"
            class="mobile-menu-btn"
            onclick="document.querySelector('.sidebar')?.classList.toggle('show')"
            aria-label="Open menu"
        >
            <i class="bi bi-list"></i>
        </button>

        <div>

            <div class="page-kicker">
                OPERATING ROOM MANAGEMENT
            </div>

            <h1 class="page-title">
                Dashboard
            </h1>

        </div>

    </div>


    <div class="topbar-right">

        <div class="topbar-date">

            <i class="bi bi-calendar3"></i>

            <span>
                <?= htmlspecialchars($today_display) ?>
            </span>

        </div>


        <button
            type="button"
            class="notification-btn"
            aria-label="Notifications"
        >

            <i class="bi bi-bell"></i>

            <?php if ($total_today_surgeries > 0): ?>

                <span class="notification-dot"></span>

            <?php endif; ?>

        </button>


        <div class="user-profile">

            <div class="user-avatar">

                <?= htmlspecialchars(
                    strtoupper(
                        substr(
                            $full_name ?? 'U',
                            0,
                            1
                        )
                    )
                ) ?>

            </div>

            <div class="user-info">

                <strong>
                    <?= htmlspecialchars($full_name ?? 'User') ?>
                </strong>

                <span>
                    <?= htmlspecialchars($role ?? 'User') ?>
                </span>

            </div>

            <i class="bi bi-chevron-down user-chevron"></i>

        </div>

    </div>

</header>


<!-- =====================================================
     DASHBOARD CONTENT
     ===================================================== -->

<main class="dashboard-content">


    <!-- =================================================
         HERO
         ================================================= -->

    <section class="dashboard-hero">

        <div class="hero-content">

            <div class="hero-label">

                <span class="hero-status-dot"></span>

                OPERATING ROOM COMMAND CENTER

            </div>


            <h2>
                Good day, <?= htmlspecialchars($full_name ?? 'User') ?>.
            </h2>


            <p>
                Monitor today's operating room activity,
                surgical schedules, and room availability
                from one centralized workspace.
            </p>


            <div class="hero-actions">

                <a
                    href="pages/schedules.php"
                    class="hero-primary-btn"
                >
                    <i class="bi bi-calendar-plus"></i>
                    <span>Add Surgery Schedule</span>
                </a>


                <a
                    href="pages/schedules.php"
                    class="hero-secondary-btn"
                >
                    <i class="bi bi-calendar3"></i>
                    <span>View Schedule</span>
                </a>

            </div>

        </div>


        <div class="hero-visual">

            <div class="hero-circle circle-one"></div>

            <div class="hero-circle circle-two"></div>


            <div class="hero-medical-icon">

                <i class="bi bi-hospital"></i>

            </div>


            <div class="hero-floating-card">

                <div class="floating-icon">

                    <i class="bi bi-heart-pulse"></i>

                </div>

                <div>

                    <strong>
                        <?= $total_today_surgeries ?>
                    </strong>

                    <small>
                        Today's procedures
                    </small>

                </div>

            </div>

        </div>

    </section>



    <!-- =================================================
         KPI CARDS
         ================================================= -->

    <section class="stats-grid">


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon green">
                    <i class="bi bi-heart-pulse-fill"></i>
                </div>

                <span class="stat-badge positive">
                    Today
                </span>

            </div>


            <div class="stat-value">
                <?= $total_today_surgeries ?>
            </div>

            <div class="stat-label">
                Scheduled Operations
            </div>

            <div class="stat-footer">
                <i class="bi bi-arrow-up-right"></i>
                Today's operating schedule
            </div>

        </div>



        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon blue">
                    <i class="bi bi-door-open-fill"></i>
                </div>

                <span class="stat-badge available">
                    Available
                </span>

            </div>


            <div class="stat-value">

                <?= $available_rooms ?>

                <span class="stat-total">
                    / <?= $total_rooms ?>
                </span>

            </div>

            <div class="stat-label">
                Available OR Rooms
            </div>

            <div class="stat-footer">
                <i class="bi bi-check-circle"></i>
                Ready for scheduling
            </div>

        </div>





        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon orange">
                    <i class="bi bi-door-closed-fill"></i>
                </div>

                <span class="stat-badge pending">
                    Active
                </span>

            </div>


            <div class="stat-value">
                <?= $occupied_rooms ?>
            </div>

            <div class="stat-label">
                Occupied OR Rooms
            </div>

            <div class="stat-footer">
                <i class="bi bi-clock-history"></i>
                Currently occupied
            </div>

        </div>



        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon purple">
                    <i class="bi bi-hospital-fill"></i>
                </div>

                <span class="stat-badge registered">
                    Registered
                </span>

            </div>


            <div class="stat-value">
                <?= $total_rooms ?>
            </div>

            <div class="stat-label">
                Operating Rooms
            </div>

            <div class="stat-footer">
                <i class="bi bi-grid-3x3-gap"></i>
                Total configured rooms
            </div>

        </div>

    </section>



    <!-- =================================================
         MAIN DASHBOARD GRID
         ================================================= -->

    <section class="dashboard-main-grid">


        <!-- =============================================
             TODAY'S SCHEDULE
             ============================================= -->
        <section class="schedule-panel">
           <div class="panel-header">
                <div>
                    <span class="panel-kicker">
                        TODAY
                    </span>
                    <h3>
                        Today's Schedule
                    </h3>
                </div>

                <span class="period-label">
                    <?= $total_today_surgeries ?>
                    <?= $total_today_surgeries === 1
                        ? 'operation'
                        : 'operations'
                    ?>
                </span>
            </div>

            <div class="schedule-list">
                <?php if (!empty($today_schedules)): ?>
                    <?php foreach ($today_schedules as $schedule): ?>
                        <?php
                        $patient_name =
                            dashboard_patient_name(
                                $schedule
                            );
                        $surgeon_name =
                            dashboard_doctor_name(
                                $schedule['surgeon_first_name'],
                                $schedule['surgeon_last_name']
                            );
                        $anesthesiologist_name =
                            dashboard_doctor_name(
                                $schedule['anesthesiologist_first_name'],
                                $schedule['anesthesiologist_last_name']
                            );
                        $display_time =
                            dashboard_format_time(
                                $schedule['start_time']
                            );
                        $display_period =
                            dashboard_format_period(
                                $schedule['start_time']
                            );
                        $duration_minutes =
                            dashboard_duration_minutes(
                                $schedule['start_time'],
                                $schedule['end_time']
                            );
                        $status =
                            trim(
                                $schedule['status'] ?? ''
                            );
                        if ($status === '') {
                            $status = 'Scheduled';
                        }
                        $priority =
                            trim(
                                $schedule['priority'] ?? ''
                            );
                        if ($priority === '') {
                            $priority = 'Elective';
                        }
                        $status_class =
                            dashboard_status_class(
                                $status
                            );
                        $priority_class =
                            dashboard_priority_class(
                                $priority
                            );
                        $priority_icon =
                            dashboard_priority_icon(
                                $priority
                            );
                        $procedure_name =
                            trim(
                                $schedule['procedure_name'] ?? ''
                            );
                        if ($procedure_name === '') {
                            $procedure_name =
                                'Procedure not specified';
                        }
                        $room_name =
                            trim(
                                $schedule['room_name'] ?? ''
                            );
                        if ($room_name === '') {
                            $room_name =
                                'Room not assigned';
                        }
                        ?>
                        <article class="schedule-item">

                            <!-- TIME / ROOM / STATUS -->
                            <div class="schedule-card-top">
                                <div class="schedule-time-block">
                                    <strong>
                                        <?= htmlspecialchars($display_time) ?>
                                    </strong>
                                    <span>
                                        <?= htmlspecialchars($display_period) ?>
                                    </span>
                                </div>

                                <div class="schedule-top-right">
                                    <span class="schedule-room">
                                        <i class="bi bi-door-open"></i>
                                        <?= htmlspecialchars($room_name) ?>
                                    </span>

                                    <span
                                        class="schedule-status <?= htmlspecialchars($status_class) ?>"
                                    >
                                        <span class="status-dot"></span>
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </div>
                            </div>


                            <!-- PATIENT -->
                            <div class="schedule-patient">
                                <span class="schedule-section-label">
                                    PATIENT
                                </span>
                                <strong>
                                    <?= htmlspecialchars($patient_name) ?>
                                </strong>
                            </div>


                            <!-- PROCEDURE -->
                            <div class="schedule-procedure">
                                <div class="procedure-icon">
                                    <i class="bi bi-heart-pulse"></i>
                                </div>

                                <div class="procedure-content">
                                    <span>
                                        PROCEDURE
                                    </span>
                                    <strong>
                                        <?= htmlspecialchars($procedure_name) ?>
                                    </strong>
                                </div>
                            </div>


                            <!-- TEAM -->
                            <div class="schedule-team-grid">
                                <div class="schedule-team-member">
                                    <div class="team-icon">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <div>
                                        <span>
                                            SURGEON
                                        </span>
                                        <strong>
                                            <?= htmlspecialchars($surgeon_name) ?>
                                        </strong>
                                    </div>
                                </div>

                                <div class="schedule-team-member">
                                    <div class="team-icon anesthesiologist-icon">
                                        <i class="bi bi-person-vcard"></i>
                                    </div>

                                    <div>
                                        <span>
                                            ANESTHESIOLOGIST
                                        </span>
                                        <strong>
                                            <?= htmlspecialchars($anesthesiologist_name) ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <!-- PRIORITY / DURATION -->
                            <div class="schedule-card-footer">

                                <span
                                    class="schedule-priority <?= htmlspecialchars($priority_class) ?>"
                                >
                                    <i class="bi <?= htmlspecialchars($priority_icon) ?>"></i>
                                    <?= htmlspecialchars($priority) ?>
                                </span>

                                <span class="schedule-duration">
                                    <i class="bi bi-clock"></i>
                                    <?php if ($duration_minutes > 0): ?>
                                        <?= htmlspecialchars($duration_minutes) ?>
                                        min
                                    <?php else: ?>
                                        Duration not set
                                    <?php endif; ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="schedule-empty-state">
                        <div class="schedule-empty-icon">
                            <i class="bi bi-calendar-x"></i>
                        </div>
                        <strong>
                            No operations scheduled today
                        </strong>
                        <span>
                            There are currently no operating room
                            procedures scheduled for today.
                        </span>
                    </div>
                <?php endif; ?>
            </div>


            <!-- FIXED ACTION -->

            <div class="schedule-footer">
                <div class="schedule-action">
                    <a
                        href="pages/schedules.php"
                        class="schedule-action-link"
                    >
                        <i class="bi bi-plus-circle"></i>
                        <span>
                            Add Surgery Schedule
                        </span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>



        <!-- =============================================
             OR ROOMS
             ============================================= -->

        <section class="rooms-panel">


            <div class="panel-header">

                <div>

                    <span class="panel-kicker">
                        OPERATING ROOMS
                    </span>

                    <h3>
                        OR Rooms
                    </h3>

                </div>


                <span class="rooms-count">

                    <?= $total_rooms ?>

                    <?= $total_rooms === 1
                        ? 'Room'
                        : 'Rooms'
                    ?>

                </span>

            </div>


            <div class="room-list">


                <?php if (!empty($rooms)): ?>


                    <?php foreach ($rooms as $room): ?>

                        <?php

                        $room_status =
                            trim(
                                $room['status'] ?? ''
                            );

                        if ($room_status === '') {
                            $room_status = 'Available';
                        }

                        $room_status_class =
                            dashboard_room_status_class(
                                $room_status
                            );

                        $room_icon =
                            dashboard_room_icon(
                                $room_status
                            );

                        $room_status_icon =
                            dashboard_room_status_icon(
                                $room_status
                            );

                        $room_description =
                            trim(
                                $room['room_description'] ?? ''
                            );

                        if ($room_description === '') {
                            $room_description =
                                'Operating room';
                        }

                        ?>

                        <div
                            class="room-card <?= htmlspecialchars($room_status_class) ?>"
                        >


                            <div class="room-icon">

                                <i class="bi <?= htmlspecialchars($room_icon) ?>"></i>

                            </div>


                            <div class="room-info">

                                <strong>
                                    <?= htmlspecialchars($room['room_name']) ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars($room_description) ?>
                                </span>

                            </div>


                            <div class="room-status">

                                <i class="bi <?= htmlspecialchars($room_status_icon) ?>"></i>

                                <?= htmlspecialchars($room_status) ?>

                            </div>


                        </div>

                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="room-empty-state">

                        <div class="room-empty-icon">

                            <i class="bi bi-door-closed"></i>

                        </div>

                        <strong>
                            No operating rooms
                        </strong>

                        <span>
                            Add operating rooms to manage
                            availability here.
                        </span>

                    </div>


                <?php endif; ?>


            </div>


            <div class="rooms-footer">

                <a href="pages/facilities.php">

                    <i class="bi bi-plus-circle"></i>

                    <span>
                        Manage Operating Rooms
                    </span>

                    <i class="bi bi-arrow-right"></i>

                </a>

            </div>


        </section>

    </section>



    <!-- =================================================
         LOWER GRID
         ================================================= -->

    <section class="dashboard-lower-grid">


        <!-- =============================================
             SURGERY OVERVIEW
             ============================================= -->
			 
		
        <section class="panel">


            <div class="panel-header">

                <div>

                    <span class="panel-kicker">
                        PROCEDURE MIX
                    </span>

                    <h3>
                        Surgery Overview
                    </h3>

                </div>

                <span class="period-label">
                    Today
                </span>

            </div>


            <div class="overview-content">


                <div class="overview-chart">

                    <?php

                    if ($total_today_surgeries > 0) {

                        $donut_style =
                            "background: conic-gradient("
                            . "var(--green-500) 0deg {$general_angle}deg, "
                            . "var(--blue-500) {$general_angle}deg {$obstetrics_angle}deg, "
                            . "var(--purple-500) {$obstetrics_angle}deg {$orthopedic_angle}deg, "
                            . "#d7e1dd {$orthopedic_angle}deg 360deg"
                            . ");";

                    } else {

                        $donut_style =
                            "background: #d7e1dd;";

                    }

                    ?>

                    <div
                        class="donut-chart"
                        style="<?= htmlspecialchars($donut_style) ?>"
                    >

                        <div class="donut-center">

                            <strong>
                                <?= $total_today_surgeries ?>
                            </strong>

                            <span>
                                Operations
                            </span>

                        </div>

                    </div>

                </div>


                <div class="overview-legend">


                    <div class="legend-item">

                        <div class="legend-label">

                            <span class="legend-dot general"></span>

                            General Surgery

                        </div>

                        <strong>
                            <?= $general_surgery_count ?>
                        </strong>

                    </div>


                    <div class="legend-item">

                        <div class="legend-label">

                            <span class="legend-dot obstetrics"></span>

                            Obstetrics

                        </div>

                        <strong>
                            <?= $obstetrics_count ?>
                        </strong>

                    </div>


                    <div class="legend-item">

                        <div class="legend-label">

                            <span class="legend-dot orthopedic"></span>

                            Orthopedic

                        </div>

                        <strong>
                            <?= $orthopedic_count ?>
                        </strong>

                    </div>


                    <div class="legend-item">

                        <div class="legend-label">

                            <span class="legend-dot other"></span>

                            Other

                        </div>

                        <strong>
                            <?= $other_surgery_count ?>
                        </strong>

                    </div>


                </div>

            </div>

        </section>



        <!-- =============================================
             QUICK ACTIONS
             ============================================= -->

        <section class="panel">


            <div class="panel-header">

                <div>

                    <span class="panel-kicker">
                        SHORTCUTS
                    </span>

                    <h3>
                        Quick Actions
                    </h3>

                </div>

            </div>


            <div class="quick-actions">


                <a
                    href="pages/patients.php"
                    class="quick-action"
                >

                    <div class="quick-icon green">

                        <i class="bi bi-person-plus"></i>

                    </div>

                    <div class="quick-text">

                        <strong>
                            Register Patient
                        </strong>

                        <small>
                            Add a new patient record
                        </small>

                    </div>

                    <i class="bi bi-chevron-right"></i>

                </a>


                <a
                    href="pages/surgeons.php"
                    class="quick-action"
                >

                    <div class="quick-icon blue">

                        <i class="bi bi-person-badge"></i>

                    </div>

                    <div class="quick-text">

                        <strong>
                            Manage Surgeons
                        </strong>

                        <small>
                            View and manage surgical staff
                        </small>

                    </div>

                    <i class="bi bi-chevron-right"></i>

                </a>


                <a
                    href="pages/anesthesiologists.php"
                    class="quick-action"
                >

                    <div class="quick-icon purple">

                        <i class="bi bi-person-vcard"></i>

                    </div>

                    <div class="quick-text">

                        <strong>
                            Manage Anesthesiologists
                        </strong>

                        <small>
                            Manage anesthesia specialists
                        </small>

                    </div>

                    <i class="bi bi-chevron-right"></i>

                </a>


                <a
                    href="pages/reports.php"
                    class="quick-action"
                >

                    <div class="quick-icon orange">

                        <i class="bi bi-bar-chart"></i>

                    </div>

                    <div class="quick-text">

                        <strong>
                            View Reports
                        </strong>

                        <small>
                            Review operating room activity
                        </small>

                    </div>

                    <i class="bi bi-chevron-right"></i>

                </a>


            </div>

        </section>

    </section>


</main>
```

</div>

<?php require_once "includes/footer.php"; ?>
