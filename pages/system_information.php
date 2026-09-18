```php
<?php

session_start();

/* =========================================================
   ACCESS CONTROL
   ========================================================= */

if (!isset($_SESSION['user_id'])) {

    if (
        isset($_GET['modal']) &&
        $_GET['modal'] === '1'
    ) {
        http_response_code(401);
        exit('Unauthorized access.');
    }

    header("Location: ../login.php");
    exit;
}


if (($_SESSION['role'] ?? '') !== 'Administrator') {

    if (
        isset($_GET['modal']) &&
        $_GET['modal'] === '1'
    ) {
        http_response_code(403);
        exit('Administrator access required.');
    }

    header("Location: ../dashboard.php");
    exit;
}


/* =========================================================
   DATABASE
   ========================================================= */

require_once "../config/database.php";


/* =========================================================
   SYSTEM INFORMATION
   ========================================================= */

$system_name = "OR Scheduling System";

$system_version = "1.0.0";

$environment = "Local Development";

$php_version = PHP_VERSION;

$operating_system = PHP_OS_FAMILY;

$server_time = date("F d, Y h:i:s A");

$server_name = $_SERVER['SERVER_NAME'] ?? 'localhost';

$server_port = $_SERVER['SERVER_PORT'] ?? 'Unknown';

$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

$application_path = realpath(__DIR__ . "/..");

$database_name = "or_scheduling";

$database_status = "Connected";

$database_version = "Unknown";


try {

    $database_version = $pdo
        ->query("SELECT VERSION()")
        ->fetchColumn();

} catch (PDOException $e) {

    $database_status = "Connection Error";

}


/* =========================================================
   PAGE SETTINGS
   ========================================================= */

$page_title = "System Information";

require_once "../includes/header.php";

require_once "../includes/sidebar.php";

?>

<link
    rel="stylesheet"
    href="../assets/css/system_information.css?v=20260919"
>


<main class="main-content">

    <div class="system-information-page">


        <!-- =================================================
             PAGE HEADER
             ================================================= -->

        <div class="system-info-page-header">

            <div class="system-info-title-area">

                <div class="system-info-title-icon">

                    <i class="bi bi-info-circle-fill"></i>

                </div>


                <div>

                    <div class="system-info-eyebrow">
                        SYSTEM
                    </div>

                    <h1>
                        System Information
                    </h1>

                    <p>
                        Technical information and environment details
                        for the OR Scheduling System.
                    </p>

                </div>

            </div>


            <a
                href="settings.php"
                class="system-info-back-button"
            >

                <i class="bi bi-arrow-left"></i>

                <span>
                    Back to Settings
                </span>

            </a>

        </div>


        <!-- =================================================
             STATUS BAR
             ================================================= -->

        <div class="system-info-status-bar">

            <div class="system-info-status-main">

                <div class="system-info-status-icon">

                    <i class="bi bi-check-circle-fill"></i>

                </div>


                <div>

                    <div class="system-info-status-title">
                        System is operational
                    </div>

                    <div class="system-info-status-description">
                        Application and database connection are available.
                    </div>

                </div>

            </div>


            <div class="system-info-status-time">

                <span>
                    SERVER TIME
                </span>

                <strong>
                    <?= htmlspecialchars($server_time) ?>
                </strong>

            </div>

        </div>


        <!-- =================================================
             SUMMARY CARDS
             ================================================= -->

        <div class="system-info-summary-grid">


            <!-- APPLICATION -->

            <div class="system-info-summary-card">

                <div class="summary-card-icon summary-icon-green">

                    <i class="bi bi-window-stack"></i>

                </div>


                <div class="summary-card-content">

                    <span>
                        APPLICATION
                    </span>

                    <strong>
                        <?= htmlspecialchars($system_name) ?>
                    </strong>

                    <small>
                        Version <?= htmlspecialchars($system_version) ?>
                    </small>

                </div>

            </div>


            <!-- PHP -->

            <div class="system-info-summary-card">

                <div class="summary-card-icon summary-icon-blue">

                    <i class="bi bi-code-slash"></i>

                </div>


                <div class="summary-card-content">

                    <span>
                        RUNTIME
                    </span>

                    <strong>
                        PHP <?= htmlspecialchars($php_version) ?>
                    </strong>

                    <small>
                        <?= htmlspecialchars($operating_system) ?>
                    </small>

                </div>

            </div>


            <!-- SERVER -->

            <div class="system-info-summary-card">

                <div class="summary-card-icon summary-icon-purple">

                    <i class="bi bi-hdd-rack-fill"></i>

                </div>


                <div class="summary-card-content">

                    <span>
                        SERVER
                    </span>

                    <strong>
                        <?= htmlspecialchars($server_name) ?>
                    </strong>

                    <small>
                        Port <?= htmlspecialchars($server_port) ?>
                    </small>

                </div>

            </div>


            <!-- DATABASE -->

            <div class="system-info-summary-card">

                <div class="summary-card-icon summary-icon-orange">

                    <i class="bi bi-database-fill"></i>

                </div>


                <div class="summary-card-content">

                    <span>
                        DATABASE
                    </span>

                    <strong>
                        <?= htmlspecialchars($database_name) ?>
                    </strong>

                    <small>
                        <?= htmlspecialchars($database_status) ?>
                    </small>

                </div>

            </div>


        </div>


        <!-- =================================================
             INFORMATION GRID
             ================================================= -->

        <div class="system-info-grid">


            <!-- APPLICATION -->

            <section class="system-info-card">

                <div class="system-info-card-header">

                    <div class="system-info-card-heading">

                        <div class="system-info-card-icon green">

                            <i class="bi bi-window-stack"></i>

                        </div>


                        <div>

                            <h2>
                                Application
                            </h2>

                            <p>
                                Application identification
                            </p>

                        </div>

                    </div>

                </div>


                <div class="system-info-card-body">


                    <div class="system-info-row">

                        <span class="system-info-label">
                            System Name
                        </span>

                        <span class="system-info-value">
                            <?= htmlspecialchars($system_name) ?>
                        </span>

                    </div>


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Version
                        </span>

                        <span class="system-info-value">

                            <span class="version-badge">
                                <?= htmlspecialchars($system_version) ?>
                            </span>

                        </span>

                    </div>


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Environment
                        </span>

                        <span class="system-info-value">

                            <span class="info-badge green-badge">

                                <span class="badge-dot"></span>

                                <?= htmlspecialchars($environment) ?>

                            </span>

                        </span>

                    </div>


                </div>

            </section>


            <!-- RUNTIME -->

            <section class="system-info-card">

                <div class="system-info-card-header">

                    <div class="system-info-card-heading">

                        <div class="system-info-card-icon blue">

                            <i class="bi bi-code-slash"></i>

                        </div>


                        <div>

                            <h2>
                                Runtime
                            </h2>

                            <p>
                                Runtime environment
                            </p>

                        </div>

                    </div>

                </div>


                <div class="system-info-card-body">


                    <div class="system-info-row">

                        <span class="system-info-label">
                            PHP Version
                        </span>

                        <span class="system-info-value">
                            <?= htmlspecialchars($php_version) ?>
                        </span>

                    </div>


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Operating System
                        </span>

                        <span class="system-info-value">
                            <?= htmlspecialchars($operating_system) ?>
                        </span>

                    </div>


                </div>

            </section>


            <!-- SERVER -->

            <section class="system-info-card">

                <div class="system-info-card-header">

                    <div class="system-info-card-heading">

                        <div class="system-info-card-icon purple">

                            <i class="bi bi-hdd-rack-fill"></i>

                        </div>


                        <div>

                            <h2>
                                Server
                            </h2>

                            <p>
                                Web server information
                            </p>

                        </div>

                    </div>

                </div>


                <div class="system-info-card-body">


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Server Name
                        </span>

                        <span class="system-info-value">
                            <?= htmlspecialchars($server_name) ?>
                        </span>

                    </div>


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Server Port
                        </span>

                        <span class="system-info-value">

                            <span class="info-badge blue-badge">

                                <?= htmlspecialchars($server_port) ?>

                            </span>

                        </span>

                    </div>


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Server Software
                        </span>

                        <span class="system-info-value">
                            <?= htmlspecialchars($server_software) ?>
                        </span>

                    </div>


                </div>

            </section>


            <!-- DATABASE -->

            <section class="system-info-card">

                <div class="system-info-card-header">

                    <div class="system-info-card-heading">

                        <div class="system-info-card-icon orange">

                            <i class="bi bi-database-fill"></i>

                        </div>


                        <div>

                            <h2>
                                Database
                            </h2>

                            <p>
                                Database connection information
                            </p>

                        </div>

                    </div>

                </div>


                <div class="system-info-card-body">


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Database Name
                        </span>

                        <span class="system-info-value">
                            <?= htmlspecialchars($database_name) ?>
                        </span>

                    </div>


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Connection Status
                        </span>

                        <span class="system-info-value">

                            <?php if ($database_status === "Connected"): ?>

                                <span class="info-badge connected-badge">

                                    <span class="badge-dot"></span>

                                    Connected

                                </span>

                            <?php else: ?>

                                <span class="info-badge error-badge">

                                    <span class="badge-dot"></span>

                                    <?= htmlspecialchars($database_status) ?>

                                </span>

                            <?php endif; ?>

                        </span>

                    </div>


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Database Version
                        </span>

                        <span class="system-info-value">
                            <?= htmlspecialchars($database_version) ?>
                        </span>

                    </div>


                </div>

            </section>


            <!-- APPLICATION PATH -->

            <section class="system-info-card">

                <div class="system-info-card-header">

                    <div class="system-info-card-heading">

                        <div class="system-info-card-icon green">

                            <i class="bi bi-folder2-open"></i>

                        </div>


                        <div>

                            <h2>
                                Application Path
                            </h2>

                            <p>
                                Local application directory
                            </p>

                        </div>

                    </div>

                </div>


                <div class="system-info-card-body">


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Application Directory
                        </span>

                        <span class="system-info-value path-value">
                            <?= htmlspecialchars($application_path ?: 'Unavailable') ?>
                        </span>

                    </div>


                </div>

            </section>


            <!-- DEVELOPER -->

            <section class="system-info-card">

                <div class="system-info-card-header">

                    <div class="system-info-card-heading">

                        <div class="system-info-card-icon green">

                            <i class="bi bi-person-workspace"></i>

                        </div>


                        <div>

                            <h2>
                                Developer
                            </h2>

                            <p>
                                System developer
                            </p>

                        </div>

                    </div>

                </div>


                <div class="system-info-card-body">


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Name
                        </span>

                        <span class="system-info-value">
                            Angel Benigno
                        </span>

                    </div>


                    <div class="system-info-row">

                        <span class="system-info-label">
                            Role
                        </span>

                        <span class="system-info-value">
                            Developer &amp; System Administrator
                        </span>

                    </div>


                </div>

            </section>


        </div>


        <!-- =================================================
             SYSTEM ENVIRONMENT
             ================================================= -->

        <section class="system-environment-card">

            <div class="environment-header">

                <div class="environment-heading">

                    <div class="environment-icon">

                        <i class="bi bi-sliders2"></i>

                    </div>


                    <div>

                        <div class="environment-eyebrow">
                            ENVIRONMENT
                        </div>

                        <h2>
                            System Environment
                        </h2>

                    </div>

                </div>

            </div>


            <div class="environment-grid">


                <div class="environment-item">

                    <span class="environment-label">
                        PHP
                    </span>

                    <strong>
                        <?= htmlspecialchars($php_version) ?>
                    </strong>

                </div>


                <div class="environment-item">

                    <span class="environment-label">
                        Operating System
                    </span>

                    <strong>
                        <?= htmlspecialchars($operating_system) ?>
                    </strong>

                </div>


                <div class="environment-item">

                    <span class="environment-label">
                        Server
                    </span>

                    <strong>
                        <?= htmlspecialchars($server_name) ?>
                    </strong>

                </div>


                <div class="environment-item">

                    <span class="environment-label">
                        Port
                    </span>

                    <strong>
                        <?= htmlspecialchars($server_port) ?>
                    </strong>

                </div>


            </div>

        </section>


        <!-- =================================================
             INFORMATION NOTE
             ================================================= -->

        <div class="system-info-footer-note">

            <i class="bi bi-info-circle"></i>

            <div>
                © 2026 OR Scheduling System. All rights reserved.
            </div>

        </div>


    </div>

</main>

