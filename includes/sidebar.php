<?php

$current_page = basename($_SERVER['PHP_SELF']);

?>

<aside class="sidebar" id="sidebar">

    <!-- BRAND -->
    <div class="sidebar-brand">

        <div class="brand-icon">
            <i class="bi bi-hospital"></i>
        </div>

        <div class="brand-text">

            <div class="brand-title">
                OR Scheduling
            </div>

            <div class="brand-subtitle">
                Hospital Management
            </div>

        </div>

    </div>


    <!-- NAVIGATION -->
    <nav class="sidebar-menu">

        <div class="menu-label">
            MAIN
        </div>

        <a
            href="/or_scheduling/dashboard.php"
            class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
        >
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>


        <div class="menu-label">
            MANAGEMENT
        </div>

        <a
            href="/or_scheduling/pages/patients.php"
            class="<?= $current_page === 'patients.php' ? 'active' : '' ?>"
        >
            <i class="bi bi-people-fill"></i>
            <span>Patients</span>
        </a>

        <a
            href="/or_scheduling/pages/surgeons.php"
            class="<?= $current_page === 'surgeons.php' ? 'active' : '' ?>"
        >
            <i class="bi bi-person-badge-fill"></i>
            <span>Surgeons</span>
        </a>

        <a
            href="/or_scheduling/pages/anesthesiologists.php"
            class="<?= $current_page === 'anesthesiologists.php' ? 'active' : '' ?>"
        >
            <i class="bi bi-person-vcard-fill"></i>
            <span>Anesthesiologists</span>
        </a>

        <a
            href="/or_scheduling/pages/rooms.php"
            class="<?= $current_page === 'rooms.php' ? 'active' : '' ?>"
        >
            <i class="bi bi-building-fill"></i>
            <span>Operating Rooms</span>
        </a>

        <a
            href="/or_scheduling/pages/procedures.php"
            class="<?= $current_page === 'procedures.php' ? 'active' : '' ?>"
        >
            <i class="bi bi-clipboard2-pulse-fill"></i>
            <span>Procedures</span>
        </a>


        <div class="menu-label">
            SCHEDULING
        </div>

        <a
            href="/or_scheduling/pages/schedules.php"
            class="<?= $current_page === 'schedules.php' ? 'active' : '' ?>"
        >
            <i class="bi bi-calendar3"></i>
            <span>OR Scheduling</span>
        </a>


        <div class="menu-label">
            SYSTEM
        </div>

        <a
            href="/or_scheduling/pages/reports.php"
            class="<?= $current_page === 'reports.php' ? 'active' : '' ?>"
        >
            <i class="bi bi-bar-chart-fill"></i>
            <span>Reports</span>
        </a>

        <a
            href="/or_scheduling/pages/settings.php"
            class="<?= $current_page === 'settings.php' ? 'active' : '' ?>"
        >
            <i class="bi bi-gear-fill"></i>
            <span>Settings</span>
        </a>


        <div class="menu-label">
            ACCOUNT
        </div>

        <a href="/or_scheduling/logout.php">

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Logout
            </span>

        </a>

    </nav>

</aside>