<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /or_scheduling/login.php");
    exit;
}

$page_title = "OR Scheduling";

$success = isset($_GET['success']) && $_GET['success'] == 1;
$error = "";


/* ============================================================
   ADD SCHEDULE
   ============================================================ */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'add_schedule'
) {

    $patient_id = (int)($_POST['patient_id'] ?? 0);

    $patient_registry_no = trim(
        $_POST['patient_registry_no'] ?? ''
    );

    $registry_date =
        $_POST['registry_date'] ?? null;

    $birth_date =
        $_POST['birth_date'] ?? null;

    $registry_type = trim(
        $_POST['registry_type'] ?? ''
    );

    $patient_room_no = trim(
        $_POST['patient_room_no'] ?? ''
    );

    $bed_no = trim(
        $_POST['bed_no'] ?? ''
    );

    $surgery_date =
        $_POST['surgery_date'] ?? '';

    $date_end =
        $_POST['date_end'] ?? null;

    $start_time =
        $_POST['start_time'] ?? '';

    $end_time =
        $_POST['end_time'] ?? '';

    $room_id =
        (int)($_POST['room_id'] ?? 0);

    $is_stat =
        isset($_POST['is_stat']) ? 1 : 0;

    $priority =
        $_POST['priority'] ?? 'Elective';

    $surgeon_doctor_id =
        (int)($_POST['surgeon_doctor_id'] ?? 0);

    $anesthesiologist_doctor_id =
        !empty($_POST['anesthesiologist_doctor_id'])
            ? (int)$_POST['anesthesiologist_doctor_id']
            : null;

    $anesthetic =
        trim($_POST['anesthetic'] ?? '');

    $cardiologist =
        trim($_POST['cardiologist'] ?? '');

    $circulating_nurse =
        trim($_POST['circulating_nurse'] ?? '');

    $instrument_nurse =
        trim($_POST['instrument_nurse'] ?? '');

    $procedure_id =
        (int)($_POST['procedure_id'] ?? 0);

    $surgical_type = "";

    $remarks =
        trim($_POST['remarks'] ?? '');

    $infections =
        trim($_POST['infections'] ?? '');


    /* ========================================================
       ASSISTANT SURGEONS
       ======================================================== */

    $assistants =
        $_POST['assistant_doctor_ids'] ?? [];


    /*
     * Always make sure assistants is an array.
     */
    if (!is_array($assistants)) {
        $assistants = [$assistants];
    }


    /*
     * Convert submitted values to integers.
     */
    $assistants =
        array_map(
            'intval',
            $assistants
        );


    /*
     * Remove empty / invalid values.
     */
    $assistants =
        array_filter(
            $assistants,
            function ($doctorId) {
                return $doctorId > 0;
            }
        );


    /*
     * Remove duplicate assistant doctors.
     */
    $assistants =
        array_values(
            array_unique($assistants)
        );


    /* ------------------------------------------------------------
       VALIDATION
       ------------------------------------------------------------ */

    if ($patient_id <= 0) {

        $error =
            "Please select a patient.";

    }

    elseif (empty($surgery_date)) {

        $error =
            "Please select the surgery date.";

    }

    elseif (
        empty($start_time) ||
        empty($end_time)
    ) {

        $error =
            "Please enter the surgery start and end time.";

    }

    elseif ($room_id <= 0) {

        $error =
            "Please select an operating room.";

    }

    elseif ($surgeon_doctor_id <= 0) {

        $error =
            "Please select the primary surgeon.";

    }

    elseif ($procedure_id <= 0) {

        $error =
            "Please select a procedure.";

    }

    elseif (
        !in_array(
            $priority,
            [
                'Elective',
                'Urgent',
                'Emergency'
            ],
            true
        )
    ) {

        $error =
            "Invalid priority selected.";

    }


    /* ------------------------------------------------------------
       VERIFY OPERATING ROOM AVAILABILITY
       ------------------------------------------------------------ */

    if (
        empty($error) &&
        $room_id > 0
    ) {

        $roomCheckStmt =
            $pdo->prepare("
                SELECT
                    room_id,
                    room_name,
                    status
                FROM operating_rooms
                WHERE room_id = :room_id
                LIMIT 1
            ");


        $roomCheckStmt->execute([
            ':room_id' =>
                $room_id
        ]);


        $selectedRoom =
            $roomCheckStmt->fetch();


        if (!$selectedRoom) {

            $error =
                "The selected operating room does not exist.";

        }

        elseif (
            strtolower(
                trim(
                    $selectedRoom['status']
                )
            ) !== 'available'
        ) {

            $error =
                "The selected operating room is no longer available.";

        }
    }


    /* ------------------------------------------------------------
       GET SURGICAL TYPE FROM SELECTED PROCEDURE
       ------------------------------------------------------------ */

    if (
        empty($error) &&
        $procedure_id > 0
    ) {

        $procedureTypeStmt =
            $pdo->prepare("
                SELECT
                    surgical_type
                FROM procedures
                WHERE procedure_id = :procedure_id
                  AND status = 'Active'
                LIMIT 1
            ");


        $procedureTypeStmt->execute([
            ':procedure_id' =>
                $procedure_id
        ]);


        $procedureType =
            $procedureTypeStmt->fetch();


        if (!$procedureType) {

            $error =
                "The selected procedure is invalid.";

        }

        else {

            $surgical_type =
                trim(
                    $procedureType['surgical_type'] ?? ''
                );

        }
    }


    /* ------------------------------------------------------------
       DATE / TIME VALIDATION
       ------------------------------------------------------------ */

    if (empty($error)) {

        if ($date_end === '') {
            $date_end = null;
        }


        if (
            $date_end !== null &&
            $date_end < $surgery_date
        ) {

            $error =
                "End date cannot be earlier than the surgery date.";

        }

        elseif (
            $end_time <= $start_time &&
            (
                $date_end === null ||
                $date_end === $surgery_date
            )
        ) {

            $error =
                "End time must be later than start time.";

        }
    }


    /* ------------------------------------------------------------
       SAVE
       ------------------------------------------------------------ */

    if (empty($error)) {

        try {

            $pdo->beginTransaction();


            /* ----------------------------------------------------
               INSERT MAIN SCHEDULE
               ---------------------------------------------------- */

            $stmt =
                $pdo->prepare("
                    INSERT INTO or_schedules
                    (
                        patient_id,

                        patient_registry_no,
                        registry_date,
                        birth_date,
                        registry_type,
                        patient_room_no,
                        bed_no,

                        surgery_date,
                        date_end,
                        start_time,
                        end_time,

                        room_id,

                        is_stat,
                        priority,

                        surgeon_doctor_id,
                        anesthesiologist_doctor_id,

                        anesthetic,
                        cardiologist,
                        circulating_nurse,
                        instrument_nurse,

                        procedure_id,
                        surgical_type,

                        remarks,
                        infections,

                        status,
                        created_by
                    )
                    VALUES
                    (
                        :patient_id,

                        :patient_registry_no,
                        :registry_date,
                        :birth_date,
                        :registry_type,
                        :patient_room_no,
                        :bed_no,

                        :surgery_date,
                        :date_end,
                        :start_time,
                        :end_time,

                        :room_id,

                        :is_stat,
                        :priority,

                        :surgeon_doctor_id,
                        :anesthesiologist_doctor_id,

                        :anesthetic,
                        :cardiologist,
                        :circulating_nurse,
                        :instrument_nurse,

                        :procedure_id,
                        :surgical_type,

                        :remarks,
                        :infections,

                        'Scheduled',
                        :created_by
                    )
                ");


            $stmt->execute([

                ':patient_id' =>
                    $patient_id,

                ':patient_registry_no' =>
                    $patient_registry_no ?: null,

                ':registry_date' =>
                    $registry_date ?: null,

                ':birth_date' =>
                    $birth_date ?: null,

                ':registry_type' =>
                    $registry_type ?: null,

                ':patient_room_no' =>
                    $patient_room_no ?: null,

                ':bed_no' =>
                    $bed_no ?: null,

                ':surgery_date' =>
                    $surgery_date,

                ':date_end' =>
                    $date_end,

                ':start_time' =>
                    $start_time,

                ':end_time' =>
                    $end_time,

                ':room_id' =>
                    $room_id,

                ':is_stat' =>
                    $is_stat,

                ':priority' =>
                    $priority,

                ':surgeon_doctor_id' =>
                    $surgeon_doctor_id,

                ':anesthesiologist_doctor_id' =>
                    $anesthesiologist_doctor_id,

                ':anesthetic' =>
                    $anesthetic ?: null,

                ':cardiologist' =>
                    $cardiologist ?: null,

                ':circulating_nurse' =>
                    $circulating_nurse ?: null,

                ':instrument_nurse' =>
                    $instrument_nurse ?: null,

                ':procedure_id' =>
                    $procedure_id,

                ':surgical_type' =>
                    $surgical_type ?: null,

                ':remarks' =>
                    $remarks ?: null,

                ':infections' =>
                    $infections ?: null,

                ':created_by' =>
                    $_SESSION['user_id']

            ]);


            $schedule_id =
                (int)$pdo->lastInsertId();


            /* ====================================================
               UPDATE OPERATING ROOM STATUS
               ==================================================== */

            /*
             * Change the selected room from Available
             * to Occupied after the schedule is created.
             *
             * The status condition prevents us from occupying
             * a room that was already taken.
             */
            $roomStatusStmt =
                $pdo->prepare("
                    UPDATE operating_rooms
                    SET status = 'Occupied'
                    WHERE room_id = :room_id
                      AND status = 'Available'
                ");


            $roomStatusStmt->execute([
                ':room_id' =>
                    $room_id
            ]);


            /*
             * If no room was updated, another schedule may
             * have occupied the room before this update.
             *
             * Roll back the schedule in that situation.
             */
            if (
                $roomStatusStmt->rowCount() !== 1
            ) {

                throw new Exception(
                    "The selected operating room is no longer available."
                );
            }


            /* ====================================================
               INSERT ASSISTANT SURGEONS
               ==================================================== */

            if (!empty($assistants)) {

                $assistantStmt =
                    $pdo->prepare("
                        INSERT INTO or_schedule_assistants
                        (
                            schedule_id,
                            doctor_id,
                            assistant_order
                        )
                        VALUES
                        (
                            :schedule_id,
                            :doctor_id,
                            :assistant_order
                        )
                    ");


                /*
                 * Verify every assistant doctor.
                 *
                 * LOWER(TRIM()) makes the specialization
                 * check more reliable if there are spaces
                 * or different capitalization in the database.
                 */
                $verifyAssistantStmt =
                    $pdo->prepare("
                        SELECT
                            doctor_id
                        FROM doctors
                        WHERE doctor_id = :doctor_id
                          AND LOWER(TRIM(specialization)) = 'surgeon'
                          AND status = 'Active'
                        LIMIT 1
                    ");


                $assistantOrder = 1;


                foreach (
                    $assistants
                    as $assistantDoctorId
                ) {

                    $assistantDoctorId =
                        (int)$assistantDoctorId;


                    /*
                     * Verify assistant doctor.
                     *
                     * The primary surgeon is allowed
                     * to also be an assistant surgeon.
                     */
                    $verifyAssistantStmt->execute([
                        ':doctor_id' =>
                            $assistantDoctorId
                    ]);


                    $validAssistant =
                        $verifyAssistantStmt->fetch();


                    /*
                     * Ignore invalid doctor IDs.
                     */
                    if (!$validAssistant) {
                        continue;
                    }


                    /*
                     * Insert assistant surgeon.
                     */
                    $assistantStmt->execute([

                        ':schedule_id' =>
                            $schedule_id,

                        ':doctor_id' =>
                            $assistantDoctorId,

                        ':assistant_order' =>
                            $assistantOrder

                    ]);


                    $assistantOrder++;
                }
            }


            /* ----------------------------------------------------
               COMMIT
               ---------------------------------------------------- */

            $pdo->commit();


            header(
                "Location: schedules.php?success=1"
            );

            exit;


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }


            $error =
                "Unable to save the schedule. " .
                $e->getMessage();
        }
    }
}


/* ============================================================
   LOAD PATIENTS
   ============================================================ */

$stmt = $pdo->query("
    SELECT
        patient_id,
        patient_number,
        registry_date,
        registry_type,
        first_name,
        middle_name,
        last_name,
        birth_date,
        sex,
        patient_room_no,
        bed_no
    FROM patients
    ORDER BY last_name ASC, first_name ASC
");

$patients =
    $stmt->fetchAll();


/* ============================================================
   LOAD ACTIVE DOCTORS
   ============================================================ */

$stmt = $pdo->query("
    SELECT
        doctor_id,
        last_name,
        first_name,
        middle_name,
        suffix_name,
        specialization,
        service_class,
        status
    FROM doctors
    WHERE status = 'Active'
    ORDER BY last_name ASC, first_name ASC
");

$doctors =
    $stmt->fetchAll();


/* ============================================================
   FILTER DOCTORS BY SPECIALIZATION
   ============================================================ */

$surgeons = [];
$anesthesiologists = [];
$cardiologists = [];


foreach (
    $doctors as $doctor
) {

    $specialization =
        strtolower(
            trim(
                $doctor['specialization'] ?? ''
            )
        );


    if (
        $specialization ===
        'surgeon'
    ) {

        $surgeons[] =
            $doctor;

    }

    elseif (
        $specialization ===
        'anesthesiologist'
    ) {

        $anesthesiologists[] =
            $doctor;

    }

    elseif (
        $specialization ===
        'cardiologist'
    ) {

        $cardiologists[] =
            $doctor;
    }
}


/* ============================================================
   LOAD OPERATING ROOMS
   ============================================================ */

$stmt = $pdo->query("
    SELECT
        room_id,
        room_name,
        room_description,
        status
    FROM operating_rooms
    WHERE status = 'Available'
    ORDER BY room_name ASC
");

$rooms =
    $stmt->fetchAll();


/* ============================================================
   LOAD PROCEDURES
   ============================================================ */

$procedures_stmt =
    $pdo->query("
        SELECT
            procedure_id,
            procedure_name,
            procedure_code,
            surgical_type
        FROM procedures
        WHERE status = 'Active'
        ORDER BY procedure_name ASC
    ");

$procedures =
    $procedures_stmt->fetchAll();


/* ============================================================
   LOAD ANESTHETICS
   ============================================================ */

$anesthetics_stmt =
    $pdo->query("
        SELECT
            anesthetic_id,
            anesthetic_name
        FROM anesthetics
        WHERE status = 'Active'
        ORDER BY anesthetic_name ASC
    ");

$anesthetics =
    $anesthetics_stmt->fetchAll();


/* ============================================================
   LOAD EXISTING SCHEDULES
   ============================================================ */

$stmt = $pdo->query("
    SELECT
        os.schedule_id,
        os.surgery_date,
        os.date_end,
        os.start_time,
        os.end_time,
        os.status,
        os.priority,
        os.is_stat,

        p.patient_number,
        p.first_name AS patient_first_name,
        p.middle_name AS patient_middle_name,
        p.last_name AS patient_last_name,

        surgeon.first_name AS surgeon_first_name,
        surgeon.last_name AS surgeon_last_name,

        anesth.first_name AS anesthesiologist_first_name,
        anesth.last_name AS anesthesiologist_last_name,

        r.room_name,

        pr.procedure_name

    FROM or_schedules os

    INNER JOIN patients p
        ON os.patient_id = p.patient_id

    INNER JOIN doctors surgeon
        ON os.surgeon_doctor_id = surgeon.doctor_id

    LEFT JOIN doctors anesth
        ON os.anesthesiologist_doctor_id = anesth.doctor_id

    INNER JOIN operating_rooms r
        ON os.room_id = r.room_id

    INNER JOIN procedures pr
        ON os.procedure_id = pr.procedure_id

    ORDER BY
        os.surgery_date DESC,
        os.start_time DESC
");

$schedules =
    $stmt->fetchAll();


/* ============================================================
   LOAD ASSISTANTS FOR EXISTING SCHEDULES
   ============================================================ */

$assistantsBySchedule = [];


if (!empty($schedules)) {

    $scheduleIds =
        array_column(
            $schedules,
            'schedule_id'
        );


    $placeholders =
        implode(
            ',',
            array_fill(
                0,
                count($scheduleIds),
                '?'
            )
        );


    $stmt =
        $pdo->prepare("
            SELECT
                osa.schedule_id,
                d.first_name,
                d.last_name,
                d.middle_name,
                d.suffix_name
            FROM or_schedule_assistants osa

            INNER JOIN doctors d
                ON osa.doctor_id = d.doctor_id

            WHERE osa.schedule_id IN ($placeholders)

            ORDER BY
                osa.schedule_id,
                osa.assistant_order
        ");


    $stmt->execute(
        $scheduleIds
    );


    foreach (
        $stmt->fetchAll()
        as $assistant
    ) {

        $assistantsBySchedule[
            $assistant['schedule_id']
        ][] =
            $assistant;
    }
}


/* ============================================================
   HELPER FUNCTIONS
   ============================================================ */

function doctorFullName(
    array $doctor
): string {

    $name =
        trim(
            ($doctor['first_name'] ?? '') . ' ' .
            ($doctor['middle_name'] ?? '') . ' ' .
            ($doctor['last_name'] ?? '')
        );


    if (
        !empty(
            $doctor['suffix_name']
        )
    ) {

        $name .=
            ', ' .
            $doctor['suffix_name'];
    }


    return $name;
}


function formatDateDisplay(
    ?string $date
): string {

    if (!$date) {
        return '—';
    }


    return date(
        'M d, Y',
        strtotime($date)
    );
}


function formatTimeDisplay(
    ?string $time
): string {

    if (!$time) {
        return '—';
    }


    return date(
        'g:i A',
        strtotime($time)
    );
}


function statusClass(
    string $status
): string {

    return strtolower(
        str_replace(
            ' ',
            '-',
            trim($status)
        )
    );
}


function priorityClass(
    string $priority,
    bool $isStat = false
): string {

    if ($isStat) {
        return 'stat';
    }


    return strtolower(
        $priority
    );
}


require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<link
    rel="stylesheet"
    href="/or_scheduling/assets/css/schedules.css?v=20260910"
>


<main class="main-content schedule-main-content">

    <!-- ========================================================
         PAGE HEADER
         ======================================================== -->

    <header class="schedule-page-header">

        <div class="schedule-page-header-copy">

            <div class="schedule-kicker">
                Operating Room Management
            </div>

            <div class="schedule-title-row">

                <div class="schedule-title-icon">
                    <i class="bi bi-calendar2-plus"></i>
                </div>

                <div>

                    <h1>OR Scheduling</h1>

                    <p>
                        Create and manage operating room schedules,
                        surgical teams, procedures, and patient assignments.
                    </p>

                </div>

            </div>

        </div>


        <div class="schedule-header-status">

            <span class="live-dot"></span>

            <span>Scheduling Workspace</span>

        </div>

    </header>


    <!-- ========================================================
         ALERTS
         ======================================================== -->

    <?php if ($success): ?>

        <div class="schedule-alert schedule-alert-success">

            <div class="schedule-alert-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div>
                <strong>Schedule saved successfully.</strong>
                The operating room schedule has been added.
            </div>

        </div>

    <?php endif; ?>


    <?php if (!empty($error)): ?>

        <div class="schedule-alert schedule-alert-error">

            <div class="schedule-alert-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>

            <div>
                <?= htmlspecialchars($error) ?>
            </div>

        </div>

    <?php endif; ?>


    <!-- ========================================================
         CREATE SCHEDULE WORKSPACE
         ======================================================== -->

    <form
        method="POST"
        action="schedules.php"
        class="schedule-workspace"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="action"
            value="add_schedule"
        >


        <!-- ====================================================
             WORKSPACE HEADER
             ==================================================== -->

        <div class="workspace-header">

            <div class="workspace-heading">

                <div class="workspace-heading-icon">
                    <i class="bi bi-clipboard2-pulse"></i>
                </div>

                <div>

                    <div class="workspace-eyebrow">
                        New Schedule
                    </div>

                    <h2>Create Surgery Schedule</h2>

                    <p>
                        Complete the clinical and scheduling information below.
                    </p>

                </div>

            </div>


            <div class="workspace-required">
                <span>*</span> Required fields
            </div>

        </div>


        <!-- ====================================================
             01 PATIENT
             ==================================================== -->

        <section class="clinical-section">

            <div class="section-heading">

                <div class="section-step">01</div>

                <div class="section-icon">
                    <i class="bi bi-person-vcard"></i>
                </div>

                <div>

                    <h3>Patient Information</h3>

                    <p>
                        Select the patient who will undergo the procedure.
                    </p>

                </div>

            </div>


            <div class="patient-selection-card">

                <div class="patient-main-field">

                    <label
                        for="patient_id"
                        class="form-label"
                    >
                        Patient <span class="required">*</span>
                    </label>

                    <div class="input-icon-wrapper">

                        <i class="bi bi-search"></i>

                        <select
                            name="patient_id"
                            id="patient_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Search or select patient
                            </option>

                            <?php foreach ($patients as $patient): ?>

                                <?php
                                $patientName = trim(
                                    $patient['last_name'] . ', ' .
                                    $patient['first_name'] . ' ' .
                                    ($patient['middle_name'] ?? '')
                                );
                                ?>

                                <option
                                    value="<?= (int)$patient['patient_id'] ?>"
                                    data-patient-number="<?= htmlspecialchars($patient['patient_number'] ?? '') ?>"
                                    data-registry-date="<?= htmlspecialchars($patient['registry_date'] ?? '') ?>"
                                    data-registry-type="<?= htmlspecialchars($patient['registry_type'] ?? '') ?>"
                                    data-birth-date="<?= htmlspecialchars($patient['birth_date'] ?? '') ?>"
                                    data-room="<?= htmlspecialchars($patient['patient_room_no'] ?? '') ?>"
                                    data-bed="<?= htmlspecialchars($patient['bed_no'] ?? '') ?>"
                                >
                                    <?= htmlspecialchars($patientName) ?>
                                    —
                                    <?= htmlspecialchars($patient['patient_number'] ?? '') ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <div
                    class="patient-quick-info"
                    id="patientQuickInfo"
                >

                    <div class="patient-info-item">

                        <span class="patient-info-label">
                            Registry No.
                        </span>

                        <strong id="patientRegistryDisplay">
                            —
                        </strong>

                    </div>

                    <div class="patient-info-item">

                        <span class="patient-info-label">
                            Registry Type
                        </span>

                        <strong id="patientRegistryTypeDisplay">
                            —
                        </strong>

                    </div>

                    <div class="patient-info-item">

                        <span class="patient-info-label">
                            Birth Date
                        </span>

                        <strong id="patientBirthDisplay">
                            —
                        </strong>

                    </div>

                    <div class="patient-info-item">

                        <span class="patient-info-label">
                            Location
                        </span>

                        <strong id="patientLocationDisplay">
                            —
                        </strong>

                    </div>

                </div>

            </div>


            <input
                type="hidden"
                name="patient_registry_no"
                id="patient_registry_no"
            >

            <input
                type="hidden"
                name="registry_date"
                id="registry_date"
            >

            <input
                type="hidden"
                name="registry_type"
                id="registry_type"
            >

            <input
                type="hidden"
                name="birth_date"
                id="birth_date"
            >

            <input
                type="hidden"
                name="patient_room_no"
                id="patient_room_no"
            >

            <input
                type="hidden"
                name="bed_no"
                id="bed_no"
            >

        </section>


        <!-- ====================================================
             02 SCHEDULE DETAILS
             ==================================================== -->

        <section class="clinical-section clinical-section-soft">

            <div class="section-heading">

                <div class="section-step">02</div>

                <div class="section-icon">
                    <i class="bi bi-clock-history"></i>
                </div>

                <div>

                    <h3>Schedule Details</h3>

                    <p>
                        Set the surgery date, duration, operating room,
                        and scheduling priority.
                    </p>

                </div>

            </div>


            <div class="schedule-planning-grid">

                <div class="planning-card">

                    <div class="planning-card-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>

                    <div class="planning-card-content">

                        <span class="planning-card-label">
                            Surgery Date
                        </span>

                        <label
                            for="surgery_date"
                            class="visually-hidden"
                        >
                            Surgery Date
                        </label>

                        <input
                            type="date"
                            name="surgery_date"
                            id="surgery_date"
                            class="form-control"
                            required
                        >

                    </div>

                </div>


                <div class="planning-card">

                    <div class="planning-card-icon">
                        <i class="bi bi-calendar-range"></i>
                    </div>

                    <div class="planning-card-content">

                        <span class="planning-card-label">
                            End Date
                        </span>

                        <label
                            for="date_end"
                            class="visually-hidden"
                        >
                            End Date
                        </label>

                        <input
                            type="date"
                            name="date_end"
                            id="date_end"
                            class="form-control"
                        >

                    </div>

                </div>


                <div class="planning-card planning-time-card">

                    <div class="planning-card-icon">
                        <i class="bi bi-stopwatch"></i>
                    </div>

                    <div class="planning-card-content">

                        <span class="planning-card-label">
                            Operating Time
                        </span>

                        <div class="time-inputs">

                            <div>

                                <label
                                    for="start_time"
                                    class="visually-hidden"
                                >
                                    Start Time
                                </label>

                                <input
                                    type="time"
                                    name="start_time"
                                    id="start_time"
                                    class="form-control"
                                    required
                                >

                                <small>Start</small>

                            </div>

                            <span class="time-separator">
                                →
                            </span>

                            <div>

                                <label
                                    for="end_time"
                                    class="visually-hidden"
                                >
                                    End Time
                                </label>

                                <input
                                    type="time"
                                    name="end_time"
                                    id="end_time"
                                    class="form-control"
                                    required
                                >

                                <small>End</small>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="room-priority-grid">

                <div class="room-selection-panel">

                    <div class="room-panel-header">

                        <div class="room-panel-icon">
                            <i class="bi bi-hospital"></i>
                        </div>

                        <div>

                            <span class="panel-eyebrow">
                                Operating Room
                            </span>

                            <h4>
                                Select OR
                                <span class="required">*</span>
                            </h4>

                        </div>

                    </div>

                    <label
                        for="room_id"
                        class="visually-hidden"
                    >
                        Operating Room
                    </label>

                    <select
                        name="room_id"
                        id="room_id"
                        class="form-select room-select"
                        required
                    >

                        <option value="">
                            Choose operating room
                        </option>

                        <?php foreach ($rooms as $room): ?>

                            <option
                                value="<?= (int)$room['room_id'] ?>"
                                <?= ($room['status'] ?? '') === 'Inactive'
                                    ? 'disabled'
                                    : '' ?>
                            >
                                <?= htmlspecialchars($room['room_name']) ?>

                                <?php if (!empty($room['status'])): ?>
                                    —
                                    <?= htmlspecialchars($room['status']) ?>
                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <div class="room-helper">

                        <i class="bi bi-info-circle"></i>

                        Only available and active rooms should be assigned
                        for scheduled procedures.

                    </div>

                </div>


                <div class="priority-panel">

                    <div class="priority-panel-header">

                        <div class="priority-panel-icon">
                            <i class="bi bi-exclamation-diamond"></i>
                        </div>

                        <div>

                            <span class="panel-eyebrow">
                                Clinical Priority
                            </span>

                            <h4>Priority</h4>

                        </div>

                    </div>

                    <label
                        for="priority"
                        class="visually-hidden"
                    >
                        Priority
                    </label>

                    <select
                        name="priority"
                        id="priority"
                        class="form-select"
                    >

                        <option value="Elective">
                            Elective
                        </option>

                        <option value="Urgent">
                            Urgent
                        </option>

                        <option value="Emergency">
                            Emergency
                        </option>

                    </select>

                    <div class="priority-description">
                        Select the clinical scheduling priority
                        for this procedure.
                    </div>

                </div>


                <div class="stat-panel">

                    <div class="stat-panel-header">

                        <div class="stat-panel-icon">
                            <i class="bi bi-lightning-charge"></i>
                        </div>

                        <div>

                            <span class="panel-eyebrow">
                                Immediate Attention
                            </span>

                            <h4>STAT Procedure</h4>

                        </div>

                    </div>

                    <div class="stat-control">

                        <label class="stat-switch">

                            <input
                                type="checkbox"
                                name="is_stat"
                                id="is_stat"
                                value="1"
                            >

                            <span class="stat-slider"></span>

                        </label>

                        <div>

                            <strong id="statLabel">
                                No
                            </strong>

                            <span>
                                Mark this procedure as STAT
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- ====================================================
             03 SURGICAL TEAM
             ==================================================== -->

        <section class="clinical-section">

            <div class="section-heading">

                <div class="section-step">03</div>

                <div class="section-icon">
                    <i class="bi bi-people"></i>
                </div>

                <div>

                    <h3>Surgical Team</h3>

                    <p>
                        Assign the primary surgeon, anesthesiologist,
                        and assistant surgeons.
                    </p>

                </div>

            </div>


            <div class="team-primary-grid">

                <div class="team-member-card primary-member">

                    <div class="member-avatar surgeon-avatar">
                        <i class="bi bi-person-check"></i>
                    </div>

                    <div class="member-content">

                        <span class="member-role">
                            PRIMARY SURGEON
                        </span>

                        <label
                            for="surgeon_doctor_id"
                            class="form-label"
                        >
                            Surgeon <span class="required">*</span>
                        </label>

                        <select
                            name="surgeon_doctor_id"
                            id="surgeon_doctor_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Select primary surgeon
                            </option>

                            <?php foreach ($surgeons as $doctor): ?>

                                <option
                                    value="<?= (int)$doctor['doctor_id'] ?>"
                                >
                                    <?= htmlspecialchars(
                                        doctorFullName($doctor)
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <div class="team-member-card">

                    <div class="member-avatar anesthesia-avatar">
                        <i class="bi bi-heart-pulse"></i>
                    </div>

                    <div class="member-content">

                        <span class="member-role">
                            ANESTHESIOLOGY
                        </span>

                        <label
                            for="anesthesiologist_doctor_id"
                            class="form-label"
                        >
                            Anesthesiologist
                        </label>

                        <select
                            name="anesthesiologist_doctor_id"
                            id="anesthesiologist_doctor_id"
                            class="form-select"
                        >

                            <option value="">
                                Select anesthesiologist
                            </option>

                            <?php foreach ($anesthesiologists as $doctor): ?>

                                <option
                                    value="<?= (int)$doctor['doctor_id'] ?>"
                                >
                                    <?= htmlspecialchars(
                                        doctorFullName($doctor)
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            </div>


            <!-- ====================================================
                 ASSISTANT SURGEONS
                 ==================================================== -->

            <div class="assistants-workspace">

                <div class="assistants-workspace-header">

                    <div class="assistant-heading">

                        <div class="assistant-heading-icon">
                            <i class="bi bi-person-plus"></i>
                        </div>

                        <div>

                            <h4>Assistant Surgeons</h4>

                            <p>
                                Add additional doctors participating
                                in the surgical procedure.
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="btn-add-assistant"
                        id="addAssistantBtn"
                    >
                        <i class="bi bi-plus-lg"></i>
                        Add Assistant
                    </button>

                </div>


                <div
                    id="assistantsContainer"
                    class="assistants-container"
                >

                    <div class="assistant-row">

                        <div class="assistant-number">
                            01
                        </div>

                        <div class="assistant-role-icon">
                            <i class="bi bi-person"></i>
                        </div>

                        <div class="assistant-select-wrapper">

                            <label
                                class="visually-hidden"
                            >
                                Assistant Surgeon
                            </label>

                            <select
                                name="assistant_doctor_ids[]"
                                class="form-select assistant-select"
                            >

                                <option value="">
                                    Select assistant surgeon
                                </option>

                                <?php foreach ($surgeons as $doctor): ?>

                                    <option
                                        value="<?= (int)$doctor['doctor_id'] ?>"
                                    >
                                        <?= htmlspecialchars(
                                            doctorFullName($doctor)
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <button
                            type="button"
                            class="btn-remove-assistant"
                            title="Remove assistant"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                    </div>

                </div>

            </div>

        </section>


        <!-- ====================================================
             04 PROCEDURE & ANESTHESIA
             ==================================================== -->

        <section class="clinical-section clinical-section-soft">

            <div class="section-heading">

                <div class="section-step">04</div>

                <div class="section-icon">
                    <i class="bi bi-activity"></i>
                </div>

                <div>

                    <h3>Procedure &amp; Anesthesia</h3>

                    <p>
                        Define the planned surgical procedure and
                        supporting clinical personnel.
                    </p>

                </div>

            </div>


            <div class="procedure-main-grid">

                <div class="procedure-selection-card">

                    <div class="procedure-card-icon">
                        <i class="bi bi-clipboard2-check"></i>
                    </div>

                    <div class="procedure-card-content">

                        <span class="clinical-eyebrow">
                            Surgical Procedure
                        </span>

                        <label
                            for="procedure_id"
                            class="form-label"
                        >
                            Procedure <span class="required">*</span>
                        </label>

                        <select
                            name="procedure_id"
                            id="procedure_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Select Procedure
                            </option>

                            <?php foreach ($procedures as $procedure): ?>

                                <option
                                    value="<?= (int)$procedure['procedure_id'] ?>"
                                    data-surgical-type="<?= htmlspecialchars(
                                        $procedure['surgical_type'] ?? ''
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $procedure['procedure_name']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <div class="anesthetic-card">

                    <div class="anesthetic-card-icon">
                        <i class="bi bi-droplet"></i>
                    </div>

                    <div class="anesthetic-card-content">

                        <span class="clinical-eyebrow">
                            Anesthesia
                        </span>

                        <label
                            for="anesthetic"
                            class="form-label"
                        >
                            Anesthetic
                        </label>

                        <select
                            name="anesthetic"
                            id="anesthetic"
                            class="form-select"
                        >

                            <option value="">
                                Select Anesthetic
                            </option>

                            <?php foreach ($anesthetics as $anesthetic): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $anesthetic['anesthetic_name']
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $anesthetic['anesthetic_name']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            </div>


            <div class="clinical-details-grid">

                <div>

                    <label
                        for="surgical_type"
                        class="form-label"
                    >
                        Surgical Type
                    </label>

                    <input
                        type="text"
                        name="surgical_type"
                        id="surgical_type"
                        class="form-control"
                        placeholder="Automatically filled from procedure"
                        readonly
                    >

                </div>


                <div>

                    <label
                        for="cardiologist"
                        class="form-label"
                    >
                        Cardiologist
                    </label>

                    <select
                        name="cardiologist"
                        id="cardiologist"
                        class="form-select"
                    >

                        <option value="">
                            Select cardiologist
                        </option>

                        <?php foreach ($cardiologists as $doctor): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    doctorFullName($doctor)
                                ) ?>"
                            >
                                <?= htmlspecialchars(
                                    doctorFullName($doctor)
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div>

                    <label
                        for="circulating_nurse"
                        class="form-label"
                    >
                        Circulating Nurse
                    </label>

                    <input
                        type="text"
                        name="circulating_nurse"
                        id="circulating_nurse"
                        class="form-control"
                        placeholder="Enter circulating nurse"
                    >

                </div>


                <div>

                    <label
                        for="instrument_nurse"
                        class="form-label"
                    >
                        Instrument Nurse
                    </label>

                    <input
                        type="text"
                        name="instrument_nurse"
                        id="instrument_nurse"
                        class="form-control"
                        placeholder="Enter instrument nurse"
                    >

                </div>

            </div>

        </section>


        <!-- ====================================================
             05 ADDITIONAL INFORMATION
             ==================================================== -->

        <section class="clinical-section">

            <div class="section-heading">

                <div class="section-step">05</div>

                <div class="section-icon">
                    <i class="bi bi-journal-medical"></i>
                </div>

                <div>

                    <h3>Additional Information</h3>

                    <p>
                        Add important clinical notes, precautions,
                        or infection-related information.
                    </p>

                </div>

            </div>


            <div class="notes-grid">

                <div class="note-card">

                    <div class="note-card-header">

                        <div class="note-icon">
                            <i class="bi bi-chat-left-text"></i>
                        </div>

                        <div>

                            <h4>Remarks</h4>

                            <span>
                                Additional scheduling or clinical notes
                            </span>

                        </div>

                    </div>

                    <label
                        for="remarks"
                        class="visually-hidden"
                    >
                        Remarks
                    </label>

                    <textarea
                        name="remarks"
                        id="remarks"
                        class="form-control"
                        placeholder="Enter additional remarks or instructions..."
                    ></textarea>

                </div>


                <div class="note-card infection-note">

                    <div class="note-card-header">

                        <div class="note-icon">
                            <i class="bi bi-shield-exclamation"></i>
                        </div>

                        <div>

                            <h4>Infections / Precautions</h4>

                            <span>
                                Record relevant infection-control information
                            </span>

                        </div>

                    </div>

                    <label
                        for="infections"
                        class="visually-hidden"
                    >
                        Infections / Precautions
                    </label>

                    <textarea
                        name="infections"
                        id="infections"
                        class="form-control"
                        placeholder="Enter infection status, precautions, or other relevant information..."
                    ></textarea>

                </div>

            </div>

        </section>


        <!-- ====================================================
             SAVE AREA
             ==================================================== -->

        <div class="schedule-submit-area">

            <div class="submit-information">

                <div class="submit-check">
                    <i class="bi bi-shield-check"></i>
                </div>

                <div>

                    <strong>
                        Ready to schedule?
                    </strong>

                    <span>
                        Review the information before saving the
                        operating room schedule.
                    </span>

                </div>

            </div>


            <div class="schedule-form-actions">

                <button
                    type="reset"
                    class="btn-schedule-secondary"
                >
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Clear Form
                </button>

                <button
                    type="submit"
                    class="btn-schedule-primary"
                >
                    <i class="bi bi-calendar2-check"></i>
                    Save Schedule
                </button>

            </div>

        </div>

    </form>


    <!-- ========================================================
         EXISTING SCHEDULES
         ======================================================== -->

    <section class="schedule-list-card">

        <div class="schedule-list-header">

            <div class="schedule-list-heading">

                <div class="list-heading-icon">
                    <i class="bi bi-calendar3"></i>
                </div>

                <div>

                    <span class="clinical-eyebrow">
                        Schedule Management
                    </span>

                    <h2>Existing Schedules</h2>

                    <p>
                        Review scheduled operating room procedures.
                    </p>

                </div>

            </div>


            <div class="schedule-count">

                <strong>
                    <?= count($schedules) ?>
                </strong>

                <span>
                    Total Schedules
                </span>

            </div>

        </div>


        <?php if (empty($schedules)): ?>

            <div class="schedule-empty">

                <div class="schedule-empty-illustration">

                    <div class="empty-calendar">

                        <div class="empty-calendar-top"></div>

                        <i class="bi bi-calendar-x"></i>

                        <span class="empty-plus">
                            +
                        </span>

                    </div>

                </div>

                <h3>
                    No schedules yet
                </h3>

                <p>
                    Once an operating room schedule is created,
                    it will appear here for easy management.
                </p>

                <div class="empty-hint">
                    <i class="bi bi-arrow-up"></i>
                    Create your first schedule above
                </div>

            </div>

        <?php else: ?>

            <div class="schedule-table-wrapper">

                <table class="schedule-table">

                    <thead>

                        <tr>

                            <th>
                                Date / Time
                            </th>

                            <th>
                                Patient
                            </th>

                            <th>
                                Operating Room
                            </th>

                            <th>
                                Procedure
                            </th>

                            <th>
                                Primary Surgeon
                            </th>

                            <th>
                                Assistants
                            </th>

                            <th>
                                Anesthesiologist
                            </th>

                            <th>
                                Priority
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($schedules as $schedule): ?>

                            <?php

                            $patientName =
                                trim(
                                    $schedule['patient_last_name'] . ', ' .
                                    $schedule['patient_first_name'] . ' ' .
                                    ($schedule['patient_middle_name'] ?? '')
                                );


                            $surgeonName =
                                trim(
                                    $schedule['surgeon_first_name'] . ' ' .
                                    $schedule['surgeon_last_name']
                                );


                            $anesthesiologistName =
                                trim(
                                    ($schedule['anesthesiologist_first_name'] ?? '') . ' ' .
                                    ($schedule['anesthesiologist_last_name'] ?? '')
                                );


                            $priorityCss =
                                priorityClass(
                                    $schedule['priority'],
                                    (bool)$schedule['is_stat']
                                );


                            $statusCss =
                                statusClass(
                                    $schedule['status']
                                );

                            ?>

                            <tr>

                                <!-- DATE / TIME -->

                                <td class="schedule-date-cell">

                                    <strong>
                                        <?= htmlspecialchars(
                                            formatDateDisplay(
                                                $schedule['surgery_date']
                                            )
                                        ) ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars(
                                            formatTimeDisplay(
                                                $schedule['start_time']
                                            )
                                        ) ?>

                                        –

                                        <?= htmlspecialchars(
                                            formatTimeDisplay(
                                                $schedule['end_time']
                                            )
                                        ) ?>
                                    </span>

                                    <?php if (
                                        !empty($schedule['date_end']) &&
                                        $schedule['date_end'] !==
                                        $schedule['surgery_date']
                                    ): ?>

                                        <small>
                                            Ends
                                            <?= htmlspecialchars(
                                                formatDateDisplay(
                                                    $schedule['date_end']
                                                )
                                            ) ?>
                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- PATIENT -->

                                <td class="patient-cell">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $patientName
                                        ) ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars(
                                            $schedule['patient_number'] ?? ''
                                        ) ?>
                                    </span>

                                </td>


                                <!-- ROOM -->

                                <td>

                                    <span class="room-badge">

                                        <i class="bi bi-hospital"></i>

                                        <?= htmlspecialchars(
                                            $schedule['room_name']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- PROCEDURE -->

                                <td class="procedure-cell">

                                    <?= htmlspecialchars(
                                        $schedule['procedure_name']
                                    ) ?>

                                </td>


                                <!-- SURGEON -->

                                <td>

                                    <div class="doctor-table-cell">

                                        <div class="table-avatar">
                                            <i class="bi bi-person"></i>
                                        </div>

                                        <span>
                                            <?= htmlspecialchars(
                                                $surgeonName
                                            ) ?>
                                        </span>

                                    </div>

                                </td>


                                <!-- ASSISTANTS -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $assistantsBySchedule[
                                                $schedule['schedule_id']
                                            ]
                                        )
                                    ): ?>

                                        <div class="assistant-list">

                                            <?php foreach (
                                                $assistantsBySchedule[
                                                    $schedule['schedule_id']
                                                ]
                                                as $assistant
                                            ): ?>

                                                <span>

                                                    <i class="bi bi-person-plus"></i>

                                                    <?= htmlspecialchars(
                                                        trim(
                                                            $assistant['first_name'] . ' ' .
                                                            $assistant['last_name']
                                                        )
                                                    ) ?>

                                                </span>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            No assistants
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ANESTHESIOLOGIST -->

                                <td>

                                    <?php if (
                                        !empty(
                                            trim(
                                                $anesthesiologistName
                                            )
                                        )
                                    ): ?>

                                        <div class="doctor-table-cell">

                                            <div class="table-avatar anesthesia-table-avatar">
                                                <i class="bi bi-heart-pulse"></i>
                                            </div>

                                            <span>
                                                <?= htmlspecialchars(
                                                    $anesthesiologistName
                                                ) ?>
                                            </span>

                                        </div>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            Not assigned
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- PRIORITY -->

                                <td>

                                    <span
                                        class="priority-badge <?= htmlspecialchars(
                                            $priorityCss
                                        ) ?>"
                                    >

                                        <?php if ($schedule['is_stat']): ?>

                                            <i class="bi bi-lightning-charge-fill"></i>

                                            STAT

                                        <?php else: ?>

                                            <?php if (
                                                $schedule['priority'] ===
                                                'Emergency'
                                            ): ?>

                                                <i class="bi bi-exclamation-octagon-fill"></i>

                                            <?php elseif (
                                                $schedule['priority'] ===
                                                'Urgent'
                                            ): ?>

                                                <i class="bi bi-exclamation-triangle-fill"></i>

                                            <?php else: ?>

                                                <i class="bi bi-check-circle"></i>

                                            <?php endif; ?>

                                            <?= htmlspecialchars(
                                                $schedule['priority']
                                            ) ?>

                                        <?php endif; ?>

                                    </span>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="status-badge <?= htmlspecialchars(
                                            $statusCss
                                        ) ?>"
                                    >

                                        <span class="status-dot"></span>

                                        <?= htmlspecialchars(
                                            $schedule['status']
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


    /* ========================================================
       PATIENT AUTO-FILL
       ======================================================== */

    const patientSelect =
        document.getElementById(
            "patient_id"
        );


    const patientRegistryNo =
        document.getElementById(
            "patient_registry_no"
        );


    const registryDate =
        document.getElementById(
            "registry_date"
        );


    const registryType =
        document.getElementById(
            "registry_type"
        );


    const birthDate =
        document.getElementById(
            "birth_date"
        );


    const patientRoomNo =
        document.getElementById(
            "patient_room_no"
        );


    const bedNo =
        document.getElementById(
            "bed_no"
        );


    const patientRegistryDisplay =
        document.getElementById(
            "patientRegistryDisplay"
        );


    const patientRegistryTypeDisplay =
        document.getElementById(
            "patientRegistryTypeDisplay"
        );


    const patientBirthDisplay =
        document.getElementById(
            "patientBirthDisplay"
        );


    const patientLocationDisplay =
        document.getElementById(
            "patientLocationDisplay"
        );


    function formatPatientDate(
        dateString
    ) {

        if (!dateString) {
            return "—";
        }


        const date =
            new Date(
                dateString +
                "T00:00:00"
            );


        if (
            isNaN(
                date.getTime()
            )
        ) {

            return dateString;
        }


        return date.toLocaleDateString(
            "en-US",
            {
                month: "short",
                day: "2-digit",
                year: "numeric"
            }
        );
    }


    function updatePatientInformation() {

        const selected =
            patientSelect.options[
                patientSelect.selectedIndex
            ];


        if (
            !selected ||
            !selected.value
        ) {

            patientRegistryNo.value =
                "";

            registryDate.value =
                "";

            registryType.value =
                "";

            birthDate.value =
                "";

            patientRoomNo.value =
                "";

            bedNo.value =
                "";


            patientRegistryDisplay.textContent =
                "—";

            patientRegistryTypeDisplay.textContent =
                "—";

            patientBirthDisplay.textContent =
                "—";

            patientLocationDisplay.textContent =
                "—";


            return;
        }


        const registryNo =
            selected.dataset.patientNumber ||
            "";


        const regDate =
            selected.dataset.registryDate ||
            "";


        const regType =
            selected.dataset.registryType ||
            "";


        const birth =
            selected.dataset.birthDate ||
            "";


        const room =
            selected.dataset.room ||
            "";


        const bed =
            selected.dataset.bed ||
            "";


        patientRegistryNo.value =
            registryNo;


        registryDate.value =
            regDate;


        registryType.value =
            regType;


        birthDate.value =
            birth;


        patientRoomNo.value =
            room;


        bedNo.value =
            bed;


        patientRegistryDisplay.textContent =
            registryNo ||
            "—";


        patientRegistryTypeDisplay.textContent =
            regType ||
            "—";


        patientBirthDisplay.textContent =
            formatPatientDate(
                birth
            );


        if (
            room ||
            bed
        ) {

            let location = "";


            if (room) {
                location += room;
            }


            if (bed) {

                if (location) {
                    location += " / ";
                }


                location +=
                    "Bed " +
                    bed;
            }


            patientLocationDisplay.textContent =
                location ||
                "—";

        }

        else {

            patientLocationDisplay.textContent =
                "Not assigned";
        }
    }


    if (patientSelect) {

        patientSelect.addEventListener(
            "change",
            updatePatientInformation
        );

    }


    /* ========================================================
       SURGICAL TYPE AUTO-FILL
       ======================================================== */

    const procedureSelect =
        document.getElementById(
            "procedure_id"
        );


    const surgicalTypeInput =
        document.getElementById(
            "surgical_type"
        );


    function updateSurgicalType() {

        if (
            !procedureSelect ||
            !surgicalTypeInput
        ) {

            return;
        }


        const selected =
            procedureSelect.options[
                procedureSelect.selectedIndex
            ];


        if (
            !selected ||
            !selected.value
        ) {

            surgicalTypeInput.value =
                "";

            return;
        }


        surgicalTypeInput.value =
            selected.dataset.surgicalType ||
            "";
    }


    if (procedureSelect) {

        procedureSelect.addEventListener(
            "change",
            updateSurgicalType
        );


        updateSurgicalType();
    }


    /* ========================================================
       ASSISTANT SURGEONS
       ======================================================== */

    const addAssistantBtn =
        document.getElementById(
            "addAssistantBtn"
        );


    const assistantsContainer =
        document.getElementById(
            "assistantsContainer"
        );


    /*
     * Update assistant numbers.
     */
    function updateAssistantNumbers() {

        if (!assistantsContainer) {
            return;
        }


        const rows =
            assistantsContainer.querySelectorAll(
                ".assistant-row"
            );


        rows.forEach(
            function (
                row,
                index
            ) {

                const number =
                    row.querySelector(
                        ".assistant-number"
                    );


                if (number) {

                    number.textContent =
                        String(
                            index + 1
                        ).padStart(
                            2,
                            "0"
                        );
                }

            }
        );
    }


    /*
     * Attach remove button.
     */
    function attachAssistantRemove(
        row
    ) {

        if (!row) {
            return;
        }


        const removeBtn =
            row.querySelector(
                ".btn-remove-assistant"
            );


        if (!removeBtn) {
            return;
        }


        removeBtn.addEventListener(
            "click",
            function () {

                const rows =
                    assistantsContainer.querySelectorAll(
                        ".assistant-row"
                    );


                /*
                 * Always keep one row.
                 */
                if (
                    rows.length <= 1
                ) {

                    const select =
                        row.querySelector(
                            ".assistant-select"
                        );


                    if (select) {
                        select.value = "";
                    }


                    return;
                }


                row.remove();

                updateAssistantNumbers();

            }
        );
    }


    /*
     * Add Assistant.
     *
     * IMPORTANT:
     * We create a completely new select instead
     * of cloning the existing select.
     */
    if (
        addAssistantBtn &&
        assistantsContainer
    ) {

        addAssistantBtn.addEventListener(
            "click",
            function () {

                /*
                 * Create new assistant row.
                 */
                const newRow =
                    document.createElement(
                        "div"
                    );


                newRow.className =
                    "assistant-row";


                /*
                 * Number.
                 */
                const number =
                    document.createElement(
                        "div"
                    );


                number.className =
                    "assistant-number";

                number.textContent =
                    "01";


                /*
                 * Icon.
                 */
                const icon =
                    document.createElement(
                        "div"
                    );


                icon.className =
                    "assistant-role-icon";

                icon.innerHTML =
                    '<i class="bi bi-person"></i>';


                /*
                 * Select wrapper.
                 */
                const selectWrapper =
                    document.createElement(
                        "div"
                    );


                selectWrapper.className =
                    "assistant-select-wrapper";


                /*
                 * Select.
                 */
                const select =
                    document.createElement(
                        "select"
                    );


                select.name =
                    "assistant_doctor_ids[]";


                select.className =
                    "form-select assistant-select";


                /*
                 * Empty option.
                 */
                const emptyOption =
                    document.createElement(
                        "option"
                    );


                emptyOption.value =
                    "";


                emptyOption.textContent =
                    "Select assistant surgeon";


                select.appendChild(
                    emptyOption
                );


                /*
                 * Get surgeon options from
                 * the existing assistant select.
                 */
                const originalSelect =
                    assistantsContainer.querySelector(
                        ".assistant-select"
                    );


                if (originalSelect) {

                    Array.from(
                        originalSelect.options
                    ).forEach(
                        function (option) {

                            /*
                             * Skip empty option because
                             * we already created it.
                             */
                            if (!option.value) {
                                return;
                            }


                            const newOption =
                                document.createElement(
                                    "option"
                                );


                            newOption.value =
                                option.value;


                            newOption.textContent =
                                option.textContent.trim();


                            select.appendChild(
                                newOption
                            );

                        }
                    );
                }


                /*
                 * Put select inside wrapper.
                 */
                selectWrapper.appendChild(
                    select
                );


                /*
                 * Remove button.
                 */
                const removeButton =
                    document.createElement(
                        "button"
                    );


                removeButton.type =
                    "button";


                removeButton.className =
                    "btn-remove-assistant";


                removeButton.title =
                    "Remove assistant";


                removeButton.innerHTML =
                    '<i class="bi bi-x-lg"></i>';


                /*
                 * Build row.
                 */
                newRow.appendChild(
                    number
                );


                newRow.appendChild(
                    icon
                );


                newRow.appendChild(
                    selectWrapper
                );


                newRow.appendChild(
                    removeButton
                );


                /*
                 * Add row to page.
                 */
                assistantsContainer.appendChild(
                    newRow
                );


                /*
                 * Attach remove function.
                 */
                attachAssistantRemove(
                    newRow
                );


                /*
                 * Update numbers.
                 */
                updateAssistantNumbers();


                /*
                 * Focus new select.
                 */
                select.focus();

            }
        );
    }


    /*
     * Attach remove button to
     * the original first row.
     */
    if (assistantsContainer) {

        assistantsContainer
            .querySelectorAll(
                ".assistant-row"
            )
            .forEach(
                function (row) {

                    attachAssistantRemove(
                        row
                    );

                }
            );

    }


    /* ========================================================
       STAT TOGGLE
       ======================================================== */

    const statCheckbox =
        document.getElementById(
            "is_stat"
        );


    const statLabel =
        document.getElementById(
            "statLabel"
        );


    function updateStatLabel() {

        if (
            !statCheckbox ||
            !statLabel
        ) {

            return;
        }


        if (
            statCheckbox.checked
        ) {

            statLabel.textContent =
                "Yes";

        }

        else {

            statLabel.textContent =
                "No";
        }
    }


    if (statCheckbox) {

        statCheckbox.addEventListener(
            "change",
            updateStatLabel
        );

    }


    /* ========================================================
       RESET FORM
       ======================================================== */

    const scheduleForm =
        document.querySelector(
            ".schedule-workspace"
        );


    if (scheduleForm) {

        scheduleForm.addEventListener(
            "reset",
            function () {

                setTimeout(
                    function () {

                        if (
                            assistantsContainer
                        ) {

                            const rows =
                                assistantsContainer.querySelectorAll(
                                    ".assistant-row"
                                );


                            rows.forEach(
                                function (
                                    row,
                                    index
                                ) {

                                    if (
                                        index > 0
                                    ) {

                                        row.remove();
                                    }

                                }
                            );


                            updateAssistantNumbers();

                        }


                        if (statLabel) {

                            statLabel.textContent =
                                "No";
                        }


                        updatePatientInformation();

                        updateSurgicalType();

                    },
                    0
                );

            }
        );

    }


});

</script>


<?php require_once "../includes/footer.php"; ?>