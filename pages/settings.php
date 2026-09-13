<?php

session_start();

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit;

}

$page_title = "Settings";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<!-- Settings Page CSS -->
<link
    rel="stylesheet"
    href="../assets/css/settings.css?v=20260907"
>


<div class="main-wrapper">

    <main class="main-content settings-page">


        <!-- =====================================================
             SETTINGS HERO
             ===================================================== -->

        <section class="settings-hero">

            <div class="settings-hero-content">

                <div class="settings-kicker">

                    <span class="kicker-dot"></span>

                    SYSTEM ADMINISTRATION

                </div>


                <h1>
                    System Settings
                </h1>


                <p>
                    Configure your OR Scheduling environment,
                    manage facilities, and control system preferences.
                </p>


                <div class="settings-user-info">

                    <div class="settings-user-avatar">

                        <i class="bi bi-person-fill"></i>

                    </div>


                    <div>

                        <div class="settings-user-label">
                            Signed in as
                        </div>

                        <div class="settings-user-name">

                            <?= htmlspecialchars(
                                $_SESSION['full_name'] ?? 'User'
                            ) ?>

                        </div>

                        <div class="settings-user-role">

                            <i class="bi bi-shield-check"></i>

                            <?= htmlspecialchars(
                                $_SESSION['role'] ?? 'Staff'
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- HERO DECORATION -->

            <div class="settings-hero-decoration">

                <div class="settings-hero-circle circle-one"></div>

                <div class="settings-hero-circle circle-two"></div>

                <div class="settings-hero-icon">

                    <i class="bi bi-sliders2"></i>

                </div>

            </div>


            <!-- SYSTEM STATUS -->

            <div class="settings-system-status">

                <span class="status-pulse"></span>

                <div>

                    <div class="status-title">
                        SYSTEM OPERATIONAL
                    </div>

                    <div class="status-subtitle">
                        Configuration center
                    </div>

                </div>

            </div>

        </section>



        <!-- =====================================================
             SECTION HEADER
             ===================================================== -->

        <div class="settings-section-header">

            <div>

                <div class="section-kicker">
                    CONFIGURATION CENTER
                </div>

                <h2>
                    Manage Your System
                </h2>

                <p>
                    Select a module below to configure your
                    OR Scheduling System.
                </p>

            </div>


            <div class="module-count">

                <span>
                    01
                </span>

                <small>
                    ACTIVE MODULE
                </small>

            </div>

        </div>



        <!-- =====================================================
             SETTINGS MODULES
             ===================================================== -->

        <div class="settings-grid">


            <!-- =================================================
                 FACILITIES
                 ================================================= -->

            <a
                href="facilities.php"
                class="settings-card settings-card-green"
            >

                <div class="card-top">

                    <div class="settings-card-icon">

                        <i class="bi bi-building-fill"></i>

                    </div>

                    <span class="module-status active">
                        ACTIVE
                    </span>

                </div>


                <div class="settings-card-content">

                    <div class="card-label">
                        FACILITY MANAGEMENT
                    </div>

                    <h3>
                        Facilities
                    </h3>

                    <p>
                        Create and manage operating rooms,
                        descriptions, and their current
                        availability status.
                    </p>

                </div>


                <div class="card-footer">

                    <span class="card-action">
                        Open Management
                    </span>

                    <span class="card-arrow">

                        <i class="bi bi-arrow-up-right"></i>

                    </span>

                </div>

            </a>



            <!-- =================================================
                 SYSTEM SETTINGS
                 ================================================= -->

            <div
                class="settings-card settings-card-blue settings-card-disabled"
            >

                <div class="card-top">

                    <div class="settings-card-icon">

                        <i class="bi bi-sliders2"></i>

                    </div>

                    <span class="module-status coming">
                        COMING SOON
                    </span>

                </div>


                <div class="settings-card-content">

                    <div class="card-label">
                        APPLICATION CONFIGURATION
                    </div>

                    <h3>
                        System Settings
                    </h3>

                    <p>
                        Configure general application preferences,
                        scheduling behavior, notifications,
                        and system defaults.
                    </p>

                </div>


                <div class="card-footer">

                    <span class="card-action">
                        Configuration module
                    </span>

                    <span class="card-arrow">

                        <i class="bi bi-lock-fill"></i>

                    </span>

                </div>

            </div>



            <!-- =================================================
                 USER MANAGEMENT
                 ================================================= -->

            <div
                class="settings-card settings-card-purple settings-card-disabled"
            >

                <div class="card-top">

                    <div class="settings-card-icon">

                        <i class="bi bi-people-fill"></i>

                    </div>

                    <span class="module-status coming">
                        COMING SOON
                    </span>

                </div>


                <div class="settings-card-content">

                    <div class="card-label">
                        ACCESS CONTROL
                    </div>

                    <h3>
                        User Management
                    </h3>

                    <p>
                        Manage system users, roles, permissions,
                        and access to administrative features.
                    </p>

                </div>


                <div class="card-footer">

                    <span class="card-action">
                        Access management
                    </span>

                    <span class="card-arrow">

                        <i class="bi bi-lock-fill"></i>

                    </span>

                </div>

            </div>



            <!-- =================================================
                 SYSTEM INFORMATION
                 ================================================= -->

            <div
                class="settings-card settings-card-orange settings-card-disabled"
            >

                <div class="card-top">

                    <div class="settings-card-icon">

                        <i class="bi bi-info-circle-fill"></i>

                    </div>

                    <span class="module-status coming">
                        COMING SOON
                    </span>

                </div>


                <div class="settings-card-content">

                    <div class="card-label">
                        SYSTEM DETAILS
                    </div>

                    <h3>
                        System Information
                    </h3>

                    <p>
                        View application version, system information,
                        database details, and other technical information.
                    </p>

                </div>


                <div class="card-footer">

                    <span class="card-action">
                        System details
                    </span>

                    <span class="card-arrow">

                        <i class="bi bi-lock-fill"></i>

                    </span>

                </div>

            </div>


        </div>



        <!-- =====================================================
             INFORMATION / TIP PANEL
             ===================================================== -->

        <section class="settings-info-panel">

            <div class="info-icon">

                <i class="bi bi-lightbulb-fill"></i>

            </div>


            <div class="info-content">

                <div class="info-label">
                    ADMINISTRATOR TIP
                </div>

                <h3>
                    Keep your operating rooms up to date
                </h3>

                <p>
                    Facility information is used throughout the
                    OR Scheduling System. Changes made in
                    <strong>Facilities</strong> will automatically
                    be reflected in the scheduling and dashboard
                    modules.
                </p>

            </div>


            <div class="info-decoration">

                <i class="bi bi-hospital"></i>

            </div>

        </section>


    </main>

</div>


<?php

require_once "../includes/footer.php";

?>