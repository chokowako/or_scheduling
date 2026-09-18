
<?php

session_start();

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit;

}

if (($_SESSION['role'] ?? '') !== 'Administrator') {

    header("Location: ../dashboard.php");
    exit;

}

$page_title = "Settings";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<!-- Settings Page CSS -->
<link
    rel="stylesheet"
    href="../assets/css/settings.css?v=20260917"
>


<!-- =========================================================
     SYSTEM INFORMATION MODAL CSS
     ========================================================= -->

<style>

.system-information-modal .modal-dialog {

    max-width: 720px;

}


.system-information-modal .modal-content {

    border: 0;

    border-radius: 18px;

    overflow: hidden;

    box-shadow:
        0 24px 70px rgba(32, 53, 45, 0.22);

}


/* =====================================================
   MODAL HEADER
   ===================================================== */

.system-information-modal .modal-header {

    padding: 20px 24px;

    border: 0;

    background: linear-gradient(
        135deg,
        #4da985,
        #358e6c
    );

    color: #ffffff;

}


.system-information-modal .modal-title {

    display: flex;

    align-items: center;

    gap: 13px;

    margin: 0;

    font-size: 19px;

    font-weight: 700;

}


.system-information-modal .modal-title-icon {

    width: 42px;

    height: 42px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: rgba(255,255,255,0.16);

    font-size: 19px;

}


.system-information-modal .modal-title-text {

    display: flex;

    flex-direction: column;

    gap: 2px;

}


.system-information-modal .modal-title-subtitle {

    font-size: 11px;

    font-weight: 500;

    opacity: 0.78;

    letter-spacing: 0.02em;

}


.system-information-modal .btn-close {

    filter: brightness(0) invert(1);

    opacity: 0.85;

}


.system-information-modal .btn-close:hover {

    opacity: 1;

}


/* =====================================================
   MODAL BODY
   ===================================================== */

.system-information-modal .modal-body {

    padding: 22px;

    background: #f5f8f7;

    max-height: 72vh;

    overflow-y: auto;

}


/* =====================================================
   INFORMATION GRID
   ===================================================== */

.system-info-container {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 14px;

}


/* =====================================================
   INFORMATION CARD
   ===================================================== */

.system-info-section {

    background: #ffffff;

    border: 1px solid #e2ebe7;

    border-radius: 13px;

    overflow: hidden;

}


.system-info-section-header {

    display: flex;

    align-items: center;

    gap: 9px;

    padding: 11px 14px;

    background: #f1faf6;

    border-bottom: 1px solid #e2ebe7;

    color: #287256;

    font-size: 11px;

    font-weight: 700;

    letter-spacing: 0.07em;

    text-transform: uppercase;

}


.system-info-section-header i {

    font-size: 14px;

}


/* =====================================================
   INFORMATION ROW
   ===================================================== */

.system-info-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 10px 14px;

    border-bottom: 1px solid #eef3f0;

}


.system-info-row:last-child {

    border-bottom: 0;

}


.system-info-label {

    color: #84938d;

    font-size: 11px;

    font-weight: 500;

}


.system-info-value {

    color: #20352d;

    font-size: 12px;

    font-weight: 600;

    text-align: right;

    word-break: break-word;

}


/* =====================================================
   DATABASE STATUS
   ===================================================== */

.system-info-status {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    color: #358e6c;

}


.system-info-status-dot {

    width: 7px;

    height: 7px;

    display: inline-block;

    border-radius: 50%;

    background: #4da985;

    box-shadow: 0 0 0 3px #e2f4ec;

}


/* =====================================================
   APPLICATION PATH
   ===================================================== */

.system-info-path-section {

    grid-column: 1 / -1;

}


.system-info-path {

    padding: 12px 14px;

    color: #52665e;

    background: #f8fbfa;

    font-family: Consolas, "Courier New", monospace;

    font-size: 11px;

    line-height: 1.5;

    word-break: break-all;

}


/* =====================================================
   LOADING
   ===================================================== */

.system-information-loading {

    min-height: 260px;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-direction: column;

    gap: 12px;

    color: #687a73;

    font-size: 13px;

}


.system-information-loading .spinner-border {

    width: 28px;

    height: 28px;

    color: #4da985;

}


/* =====================================================
   ERROR
   ===================================================== */

.system-info-error {

    padding: 18px;

    border-radius: 10px;

    background: #fff5f5;

    border: 1px solid #f0d6d6;

    color: #9b4545;

    font-size: 13px;

}


/* =====================================================
   MODAL FOOTER
   ===================================================== */

.system-information-modal .modal-footer {

    padding: 14px 22px;

    border-top: 1px solid #e2ebe7;

    background: #ffffff;

}


.system-information-modal .modal-footer .btn {

    min-width: 90px;

    border-radius: 8px;

    font-size: 12px;

}


/* =====================================================
   RESPONSIVE
   ===================================================== */

@media (max-width: 650px) {

    .system-information-modal .modal-dialog {

        margin: 12px;

    }


    .system-information-modal .modal-body {

        padding: 15px;

    }


    .system-info-container {

        grid-template-columns: 1fr;

    }


    .system-info-path-section {

        grid-column: auto;

    }


    .system-info-row {

        align-items: flex-start;

    }


    .system-info-value {

        max-width: 55%;

    }

}

</style>


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

            <a
                href="users.php"
                class="settings-card settings-card-purple"
            >

                <div class="card-top">

                    <div class="settings-card-icon">

                        <i class="bi bi-people-fill"></i>

                    </div>

                    <span class="module-status active">
                        ACTIVE
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
                        Open Management
                    </span>

                    <span class="card-arrow">

                        <i class="bi bi-arrow-up-right"></i>

                    </span>

                </div>

            </a>



            <!-- =================================================
                 SYSTEM INFORMATION
                 ================================================= -->

            <a
                href="#"
                class="settings-card settings-card-orange system-information-card"
                id="systemInformationCard"
            >

                <div class="card-top">

                    <div class="settings-card-icon">

                        <i class="bi bi-info-circle-fill"></i>

                    </div>

                    <span class="module-status active">
                        ACTIVE
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
                        View System Information
                    </span>

                    <span class="card-arrow">

                        <i class="bi bi-arrow-up-right"></i>

                    </span>

                </div>

            </a>

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



<!-- =========================================================
     SYSTEM INFORMATION MODAL
     ========================================================= -->

<div
    class="modal fade system-information-modal"
    id="systemInformationModal"
    tabindex="-1"
    aria-labelledby="systemInformationModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <!-- HEADER -->

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="systemInformationModalLabel"
                >

                    <span class="modal-title-icon">

                        <i class="bi bi-info-circle-fill"></i>

                    </span>


                    <span class="modal-title-text">

                        <span>
                            System Information
                        </span>

                        <span class="modal-title-subtitle">
                            OR Scheduling System
                        </span>

                    </span>

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <!-- BODY -->

            <div
                class="modal-body"
                id="systemInformationModalBody"
            >

                <div class="system-information-loading">

                    <div
                        class="spinner-border"
                        role="status"
                    >

                        <span class="visually-hidden">
                            Loading...
                        </span>

                    </div>

                    <div>
                        Loading system information...
                    </div>

                </div>

            </div>


            <!-- FOOTER -->

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>

            </div>

        </div>

    </div>

</div>



<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const card =
            document.getElementById(
                "systemInformationCard"
            );

        const modalElement =
            document.getElementById(
                "systemInformationModal"
            );

        const modalBody =
            document.getElementById(
                "systemInformationModalBody"
            );


        if (
            !card ||
            !modalElement ||
            !modalBody
        ) {

            return;

        }


        const modal =
            new bootstrap.Modal(
                modalElement
            );


        card.addEventListener(
            "click",
            function (event) {

                event.preventDefault();


                modalBody.innerHTML = `

                    <div class="system-information-loading">

                        <div
                            class="spinner-border"
                            role="status"
                        >

                            <span class="visually-hidden">
                                Loading...
                            </span>

                        </div>

                        <div>
                            Loading system information...
                        </div>

                    </div>

                `;


                modal.show();


                fetch(
                    "system_information.php?modal=1",
                    {
                        method: "GET",

                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        }
                    }
                )

                .then(function (response) {

                    if (!response.ok) {

                        throw new Error(
                            "Unable to load system information."
                        );

                    }

                    return response.text();

                })

                .then(function (html) {

                    modalBody.innerHTML = html;

                })

                .catch(function (error) {

                    modalBody.innerHTML = `

                        <div class="system-info-error">

                            <strong>
                                Unable to load system information.
                            </strong>

                            <div class="mt-1">
                                ${error.message}
                            </div>

                        </div>

                    `;

                });

            }
        );

    }
);

</script>


<?php

require_once "../includes/footer.php";

?>

