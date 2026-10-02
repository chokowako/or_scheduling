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

        <!-- Dedicated Toggle Button -->
        <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
            <i class="bi bi-list"></i>
        </button>
    </div>


    <!-- NAVIGATION -->
    <nav class="sidebar-menu">

        <!-- MAIN -->
        <div class="menu-label">
            MAIN
        </div>

        <a
            href="/or_scheduling/dashboard.php"
            class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
            title="Dashboard"
        >
            <span class="nav-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </span>
            <span class="nav-text">Dashboard</span>
        </a>


        <!-- MANAGEMENT -->
        <div class="menu-label">
            MANAGEMENT
        </div>

        <a
            href="/or_scheduling/pages/patients.php"
            class="<?= $current_page === 'patients.php' ? 'active' : '' ?>"
            title="Patients"
        >
            <span class="nav-icon">
                <i class="bi bi-people-fill"></i>
            </span>
            <span class="nav-text">Patients</span>
        </a>


        <a
            href="/or_scheduling/pages/surgeons.php"
            class="<?= $current_page === 'surgeons.php' ? 'active' : '' ?>"
            title="Surgeons"
        >
            <span class="nav-icon">
                <i class="bi bi-person-badge-fill"></i>
            </span>
            <span class="nav-text">Surgeons</span>
        </a>


        <a
            href="/or_scheduling/pages/rooms.php"
            class="<?= $current_page === 'rooms.php' ? 'active' : '' ?>"
            title="Operating Rooms"
        >
            <span class="nav-icon">
                <i class="bi bi-door-open-fill"></i>
            </span>
            <span class="nav-text">Operating Rooms</span>
        </a>


        <a
            href="/or_scheduling/pages/procedures.php"
            class="<?= $current_page === 'procedures.php' ? 'active' : '' ?>"
            title="Procedures"
        >
            <span class="nav-icon">
                <i class="bi bi-clipboard2-pulse-fill"></i>
            </span>
            <span class="nav-text">Procedures</span>
        </a>


        <!-- SCHEDULING -->
        <div class="menu-label">
            SCHEDULING
        </div>

        <a
            href="/or_scheduling/pages/ORschedules.php"
            target="_blank"
            rel="noopener noreferrer"
            class="<?= $current_page === 'schedules.php' ? 'active' : '' ?>"
            title="OR Scheduling"
        >
            <span class="nav-icon">
                <i class="bi bi-calendar2-check-fill"></i>
            </span>
            <span class="nav-text">OR Scheduling</span>
        </a>


        <!-- SYSTEM -->
        <div class="menu-label">
            SYSTEM
        </div>

        <a
            href="/or_scheduling/pages/reports.php"
            class="<?= $current_page === 'reports.php' ? 'active' : '' ?>"
            title="Reports"
        >
            <span class="nav-icon">
                <i class="bi-file-earmark-medical-fill"></i>
            </span>
            <span class="nav-text">Reports</span>
        </a>


        <?php if (($_SESSION['role'] ?? '') === 'Administrator'): ?>

            <a
                href="/or_scheduling/pages/settings.php"
                class="<?= $current_page === 'settings.php' ? 'active' : '' ?>"
                title="Settings"
            >
                <span class="nav-icon">
                    <i class="bi-gear-fill"></i>
                </span>
                <span class="nav-text">Settings</span>
            </a>

        <?php endif; ?>


        <!-- ACCOUNT -->
        <div class="menu-label">
            ACCOUNT
        </div>

        <a href="/or_scheduling/pages/logout.php" title="Logout">
            <span class="nav-icon logout-icon">
                <i class="bi bi-box-arrow-right"></i>
            </span>
            <span class="nav-text">Logout</span>
        </a>

    </nav>


<script>
    // 1. Run immediately on desktop only to prevent flicker. 
    // On mobile (< 768px), we default to expanded/hidden drawer to prevent getting locked out.
    if (window.innerWidth > 768 && localStorage.getItem("sidebarState") === "collapsed") {
        document.body.classList.add("sidebar-collapsed");
    }

    // 2. Handle toggle click event
    document.addEventListener("DOMContentLoaded", function () {
        const toggleBtn = document.getElementById("sidebarToggle");
        const body = document.body;

        if (toggleBtn) {
            toggleBtn.addEventListener("click", function () {
                if (window.innerWidth <= 768) {
                    // Mobile behavior: toggle sliding drawer overlay
                    body.classList.toggle("mobile-sidebar-open");
                } else {
                    // Desktop behavior: toggle mini sidebar collapse
                    body.classList.toggle("sidebar-collapsed");

                    if (body.classList.contains("sidebar-collapsed")) {
                        localStorage.setItem("sidebarState", "collapsed");
                    } else {
                        localStorage.setItem("sidebarState", "expanded");
                    }
                }
            });
        }
    });
	
	
	
	document.addEventListener('DOMContentLoaded', function () {
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');

    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', function (e) {
            e.stopPropagation(); // Prevents click from bubbling up
            sidebar.classList.toggle('active'); // or 'show', depending on what your sidebar CSS uses
        });
    }
});


</script>

</aside>

