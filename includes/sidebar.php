
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

        <!-- MAIN -->
        <div class="menu-label">
            MAIN
        </div>

        <a
            href="/or_scheduling/dashboard.php"
            class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </span>

            <span>Dashboard</span>
        </a>


        <!-- MANAGEMENT -->
        <div class="menu-label">
            MANAGEMENT
        </div>

        <a
            href="/or_scheduling/pages/patients.php"
            class="<?= $current_page === 'patients.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">
                <i class="bi bi-people-fill"></i>
            </span>

            <span>Patients</span>
        </a>


        <a
            href="/or_scheduling/pages/surgeons.php"
            class="<?= $current_page === 'surgeons.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">
                <i class="bi bi-person-badge-fill"></i>
            </span>

            <span>Surgeons</span>
        </a>


        <a
            href="/or_scheduling/pages/rooms.php"
            class="<?= $current_page === 'rooms.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">
                <i class="bi bi-door-open-fill"></i>
            </span>

            <span>Operating Rooms</span>
        </a>


        <a
            href="/or_scheduling/pages/procedures.php"
            class="<?= $current_page === 'procedures.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">
                <i class="bi bi-clipboard2-pulse-fill"></i>
            </span>

            <span>Procedures</span>
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
	>
		<span class="nav-icon">
			<i class="bi bi-calendar3"></i>
		</span>

		<span>OR Scheduling</span>
	</a>


        <!-- SYSTEM -->
        <div class="menu-label">
            SYSTEM
        </div>

        <a
            href="/or_scheduling/pages/reports.php"
            class="<?= $current_page === 'reports.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">
                <i class="bi bi-bar-chart-fill"></i>
            </span>

            <span>Reports</span>
        </a>


		<?php if (($_SESSION['role'] ?? '') === 'Administrator'): ?>

			<a
				href="/or_scheduling/pages/settings.php"
				class="<?= $current_page === 'settings.php' ? 'active' : '' ?>"
			>
				<span class="nav-icon">
					<i class="bi bi-gear-fill"></i>
				</span>
				<span>Settings</span>
			</a>

		<?php endif; ?>


        <!-- ACCOUNT -->
        <div class="menu-label">
            ACCOUNT
        </div>

        <a href="/or_scheduling/pages/logout.php">

            <span class="nav-icon logout-icon">
                <i class="bi bi-box-arrow-right"></i>
            </span>

            <span>Logout</span>

        </a>

    </nav>

</aside>

