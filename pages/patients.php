<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = "";
$message_type = "success";

$edit_patient = null;


/* =========================================================
   LOAD PATIENT FOR EDIT MODAL
   ========================================================= */

if (isset($_GET['edit'])) {

    $edit_id = intval($_GET['edit']);

    if ($edit_id > 0) {

        $stmt = $pdo->prepare("
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
                bed_no,
                contact_number,
                address
            FROM patients
            WHERE patient_id = :patient_id
            LIMIT 1
        ");

        $stmt->execute([
            ':patient_id' => $edit_id
        ]);

        $edit_patient = $stmt->fetch();

        if (!$edit_patient) {

            $message = "Patient record not found.";
            $message_type = "danger";
        }
    }
}


/* =========================================================
   FORM ACTIONS
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? "";


    /* =====================================================
       ADD PATIENT
       ===================================================== */

    if ($action === "add") {

        $patient_number = trim($_POST['patient_number'] ?? '');
        $registry_date = trim($_POST['registry_date'] ?? '');
        $registry_type = trim($_POST['registry_type'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $birth_date = trim($_POST['birth_date'] ?? '');
        $sex = trim($_POST['sex'] ?? '');
        $patient_room_no = trim($_POST['patient_room_no'] ?? '');
        $bed_no = trim($_POST['bed_no'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $address = trim($_POST['address'] ?? '');


        if (
            $patient_number === "" ||
            $registry_date === "" ||
            $first_name === "" ||
            $last_name === ""
        ) {

            $message =
                "Please complete all required patient information.";

            $message_type = "danger";

        } else {

            try {

                $sql = "
                    INSERT INTO patients
                    (
                        patient_number,
                        registry_date,
                        registry_type,
                        first_name,
                        middle_name,
                        last_name,
                        birth_date,
                        sex,
                        patient_room_no,
                        bed_no,
                        contact_number,
                        address
                    )
                    VALUES
                    (
                        :patient_number,
                        :registry_date,
                        :registry_type,
                        :first_name,
                        :middle_name,
                        :last_name,
                        :birth_date,
                        :sex,
                        :patient_room_no,
                        :bed_no,
                        :contact_number,
                        :address
                    )
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':patient_number' =>
                        $patient_number,

                    ':registry_date' =>
                        $registry_date,

                    ':registry_type' =>
                        $registry_type !== ""
                            ? $registry_type
                            : null,

                    ':first_name' =>
                        $first_name,

                    ':middle_name' =>
                        $middle_name !== ""
                            ? $middle_name
                            : null,

                    ':last_name' =>
                        $last_name,

                    ':birth_date' =>
                        $birth_date !== ""
                            ? $birth_date
                            : null,

                    ':sex' =>
                        $sex !== ""
                            ? $sex
                            : null,

                    ':patient_room_no' =>
                        $patient_room_no !== ""
                            ? $patient_room_no
                            : null,

                    ':bed_no' =>
                        $bed_no !== ""
                            ? $bed_no
                            : null,

                    ':contact_number' =>
                        $contact_number !== ""
                            ? $contact_number
                            : null,

                    ':address' =>
                        $address !== ""
                            ? $address
                            : null
                ]);

                header("Location: patients.php?success=added");
                exit;

            } catch (PDOException $e) {

                if ($e->getCode() === '23000') {

                    $message =
                        "The patient number already exists.";

                } else {

                    $message =
                        "Unable to add patient: " .
                        $e->getMessage();
                }

                $message_type = "danger";
            }
        }
    }


    /* =====================================================
       UPDATE PATIENT
       ===================================================== */

    elseif ($action === "update") {

        $patient_id =
            intval($_POST['patient_id'] ?? 0);

        $patient_number =
            trim($_POST['patient_number'] ?? '');

        $registry_date =
            trim($_POST['registry_date'] ?? '');

        $registry_type =
            trim($_POST['registry_type'] ?? '');

        $first_name =
            trim($_POST['first_name'] ?? '');

        $middle_name =
            trim($_POST['middle_name'] ?? '');

        $last_name =
            trim($_POST['last_name'] ?? '');

        $birth_date =
            trim($_POST['birth_date'] ?? '');

        $sex =
            trim($_POST['sex'] ?? '');

        $patient_room_no =
            trim($_POST['patient_room_no'] ?? '');

        $bed_no =
            trim($_POST['bed_no'] ?? '');

        $contact_number =
            trim($_POST['contact_number'] ?? '');

        $address =
            trim($_POST['address'] ?? '');


        if (
            $patient_id <= 0 ||
            $patient_number === "" ||
            $registry_date === "" ||
            $first_name === "" ||
            $last_name === ""
        ) {

            $message =
                "Please complete all required patient information.";

            $message_type = "danger";

        } else {

            try {

                $sql = "
                    UPDATE patients
                    SET
                        patient_number = :patient_number,
                        registry_date = :registry_date,
                        registry_type = :registry_type,
                        first_name = :first_name,
                        middle_name = :middle_name,
                        last_name = :last_name,
                        birth_date = :birth_date,
                        sex = :sex,
                        patient_room_no = :patient_room_no,
                        bed_no = :bed_no,
                        contact_number = :contact_number,
                        address = :address
                    WHERE patient_id = :patient_id
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':patient_number' =>
                        $patient_number,

                    ':registry_date' =>
                        $registry_date,

                    ':registry_type' =>
                        $registry_type !== ""
                            ? $registry_type
                            : null,

                    ':first_name' =>
                        $first_name,

                    ':middle_name' =>
                        $middle_name !== ""
                            ? $middle_name
                            : null,

                    ':last_name' =>
                        $last_name,

                    ':birth_date' =>
                        $birth_date !== ""
                            ? $birth_date
                            : null,

                    ':sex' =>
                        $sex !== ""
                            ? $sex
                            : null,

                    ':patient_room_no' =>
                        $patient_room_no !== ""
                            ? $patient_room_no
                            : null,

                    ':bed_no' =>
                        $bed_no !== ""
                            ? $bed_no
                            : null,

                    ':contact_number' =>
                        $contact_number !== ""
                            ? $contact_number
                            : null,

                    ':address' =>
                        $address !== ""
                            ? $address
                            : null,

                    ':patient_id' =>
                        $patient_id
                ]);

                header("Location: patients.php?success=updated");
                exit;

            } catch (PDOException $e) {

                if ($e->getCode() === '23000') {

                    $message =
                        "The patient number already exists.";

                } else {

                    $message =
                        "Unable to update patient: " .
                        $e->getMessage();
                }

                $message_type = "danger";
            }
        }
    }


    /* =====================================================
       DELETE PATIENT
       ===================================================== */

    elseif ($action === "delete") {

        $patient_id =
            intval($_POST['patient_id'] ?? 0);


        if ($patient_id <= 0) {

            $message = "Invalid patient.";
            $message_type = "danger";

        } else {

            try {

                $stmt = $pdo->prepare("
                    DELETE FROM patients
                    WHERE patient_id = :patient_id
                ");

                $stmt->execute([
                    ':patient_id' =>
                        $patient_id
                ]);

                header("Location: patients.php?success=deleted");
                exit;

            } catch (PDOException $e) {

                $message =
                    "Unable to delete patient. " .
                    "The patient may already be used in an operating room schedule.";

                $message_type = "danger";
            }
        }
    }
}


/* =========================================================
   SUCCESS MESSAGES
   ========================================================= */

if (isset($_GET['success'])) {

    switch ($_GET['success']) {

        case "added":

            $message =
                "Patient successfully added.";

            $message_type = "success";

            break;


        case "updated":

            $message =
                "Patient successfully updated.";

            $message_type = "success";

            break;


        case "deleted":

            $message =
                "Patient successfully deleted.";

            $message_type = "success";

            break;
    }
}


/* =========================================================
   SEARCH PATIENTS
   ========================================================= */

$search =
    trim($_GET['search'] ?? '');


if ($search !== "") {

    $sql = "
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
            bed_no,
            contact_number,
            address,
            created_at
        FROM patients
        WHERE
            patient_number LIKE :search
            OR first_name LIKE :search
            OR middle_name LIKE :search
            OR last_name LIKE :search
            OR contact_number LIKE :search
            OR patient_room_no LIKE :search
            OR bed_no LIKE :search
        ORDER BY
            last_name ASC,
            first_name ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':search' =>
            '%' . $search . '%'
    ]);

} else {

    $sql = "
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
            bed_no,
            contact_number,
            address,
            created_at
        FROM patients
        ORDER BY
            last_name ASC,
            first_name ASC
    ";

    $stmt = $pdo->query($sql);
}


$patients =
    $stmt->fetchAll();


$total_patients =
    count($patients);


$page_title =
    "Patients";


/* =========================================================
   SHARED HEADER
   ========================================================= */

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<link
    rel="stylesheet"
    href="../assets/css/patients.css?v=20260914"
>


<main class="main-content patients-page">


    <!-- =====================================================
         ALERT
         ===================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="patient-alert <?= $message_type === 'success'
                ? 'patient-alert-success'
                : 'patient-alert-error' ?>"
        >

            <i
                class="bi <?= $message_type === 'success'
                    ? 'bi-check-circle-fill'
                    : 'bi-exclamation-circle-fill' ?>"
            ></i>

            <span>
                <?= htmlspecialchars($message) ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ADD PATIENT CARD
         ===================================================== -->

    <section class="patient-form-card">


        <!-- FORM HEADER -->

        <div class="patient-form-header">

            <div class="patient-form-heading">

                <div class="patient-form-icon">

                    <i class="bi bi-person-plus-fill"></i>

                </div>

                <div>

                    <div class="patient-form-eyebrow">
                        PATIENT REGISTRATION
                    </div>

                    <h2>
                        Add Patient
                    </h2>

                    <p>
                        Enter the patient's information below.
                    </p>

                </div>

            </div>


            <div class="required-note">

                <span>*</span>
                Required fields

            </div>

        </div>


        <!-- =================================================
             ADD PATIENT FORM
             ================================================= -->

        <form
            method="POST"
            action="patients.php"
            autocomplete="off"
        >

            <input
                type="hidden"
                name="action"
                value="add"
            >


            <!-- =================================================
                 SECTION 01
                 ================================================= -->

            <div class="patient-form-section">

                <div class="patient-section-title">

                    <div class="patient-section-number">
                        01
                    </div>

                    <div>

                        <h3>
                            Registry Information
                        </h3>

                        <p>
                            Patient registration and identification details.
                        </p>

                    </div>

                </div>


                <div class="patient-form-grid patient-form-grid-3">


                    <!-- Patient Number -->

                    <div class="patient-field">

                        <label>
                            Patient Number
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="patient_number"
                            class="form-control"
                            placeholder="Enter patient number"
                            required
                        >

                    </div>


                    <!-- Registry Date -->

                    <div class="patient-field">

                        <label>
                            Registry Date
                            <span>*</span>
                        </label>

                        <input
                            type="date"
                            name="registry_date"
                            class="form-control"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >

                    </div>


                    <!-- Registry Type -->

                    <div class="patient-field">

                        <label>
                            Registry Type
                        </label>

                        <input
                            type="text"
                            name="registry_type"
                            class="form-control"
                            placeholder="Enter registry type"
                        >

                    </div>


                </div>

            </div>


            <!-- =================================================
                 SECTION 02
                 ================================================= -->

            <div class="patient-form-section">

                <div class="patient-section-title">

                    <div class="patient-section-number">
                        02
                    </div>

                    <div>

                        <h3>
                            Personal Information
                        </h3>

                        <p>
                            Basic information of the registered patient.
                        </p>

                    </div>

                </div>


                <div class="patient-form-grid patient-form-grid-3">


                    <!-- First Name -->

                    <div class="patient-field">

                        <label>
                            First Name
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            class="form-control"
                            placeholder="Enter first name"
                            required
                        >

                    </div>


                    <!-- Middle Name -->

                    <div class="patient-field">

                        <label>
                            Middle Name
                        </label>

                        <input
                            type="text"
                            name="middle_name"
                            class="form-control"
                            placeholder="Enter middle name"
                        >

                    </div>


                    <!-- Last Name -->

                    <div class="patient-field">

                        <label>
                            Last Name
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            class="form-control"
                            placeholder="Enter last name"
                            required
                        >

                    </div>


                    <!-- Birth Date -->

                    <div class="patient-field">

                        <label>
                            Birth Date
                        </label>

                        <input
                            type="date"
                            name="birth_date"
                            class="form-control"
                        >

                    </div>


                    <!-- Sex -->

                    <div class="patient-field">

                        <label>
                            Sex
                        </label>

                        <select
                            name="sex"
                            class="form-select"
                        >

                            <option value="">
                                Select sex
                            </option>

                            <option value="Male">
                                Male
                            </option>

                            <option value="Female">
                                Female
                            </option>

                        </select>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 SECTION 03
                 ================================================= -->

            <div class="patient-form-section">

                <div class="patient-section-title">

                    <div class="patient-section-number">
                        03
                    </div>

                    <div>

                        <h3>
                            Room & Contact Information
                        </h3>

                        <p>
                            Current room, bed, and contact information.
                        </p>

                    </div>

                </div>


                <div class="patient-form-grid patient-form-grid-4">


                    <!-- Room -->

                    <div class="patient-field">

                        <label>
                            Patient Room No.
                        </label>

                        <input
                            type="text"
                            name="patient_room_no"
                            class="form-control"
                            placeholder="Room number"
                        >

                    </div>


                    <!-- Bed -->

                    <div class="patient-field">

                        <label>
                            Bed No.
                        </label>

                        <input
                            type="text"
                            name="bed_no"
                            class="form-control"
                            placeholder="Bed number"
                        >

                    </div>


                    <!-- Contact -->

                    <div class="patient-field">

                        <label>
                            Contact Number
                        </label>

                        <input
                            type="text"
                            name="contact_number"
                            class="form-control"
                            placeholder="Contact number"
                        >

                    </div>


                    <!-- Address -->

                    <div class="patient-field">

                        <label>
                            Address
                        </label>

                        <input
                            type="text"
                            name="address"
                            class="form-control"
                            placeholder="Patient address"
                        >

                    </div>


                </div>

            </div>


            <!-- =================================================
                 FORM ACTIONS
                 ================================================= -->

            <div class="patient-form-actions">

                <div class="patient-form-note">

                    <i class="bi bi-info-circle"></i>

                    <span>
                        Make sure the patient information is correct before saving.
                    </span>

                </div>


                <button
                    type="reset"
                    class="btn-patient-secondary"
                >

                    <i class="bi bi-arrow-counterclockwise"></i>

                    Clear

                </button>


                <button
                    type="submit"
                    class="btn-patient-primary"
                >

                    <i class="bi bi-person-plus-fill"></i>

                    Save Patient

                </button>

            </div>


        </form>

    </section>


    <!-- =====================================================
         REGISTERED PATIENTS
         ===================================================== -->

    <section class="patient-list-card">


        <!-- LIST HEADER -->

        <div class="patient-list-header">

            <div class="patient-list-heading">

                <div class="patient-list-icon">

                    <i class="bi bi-people-fill"></i>

                </div>

                <div>

                    <div class="patient-list-eyebrow">
                        PATIENT RECORDS
                    </div>

                    <h2>
                        Registered Patients
                    </h2>

                    <p>
                        View and manage registered patient records.
                    </p>

                </div>

            </div>


            <div class="patient-list-count">

                <?= number_format($total_patients) ?>

            </div>

        </div>


        <!-- SEARCH -->

        <div class="patient-search-area">

            <form
                method="GET"
                action="patients.php"
                class="patient-search-form"
            >

                <div class="patient-search-input">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search patient number, name, room, bed, or contact..."
                    >

                </div>


                <button
                    type="submit"
                    class="btn-patient-search"
                >

                    <i class="bi bi-search"></i>

                    Search

                </button>


                <?php if ($search !== ""): ?>

                    <a
                        href="patients.php"
                        class="btn-patient-clear"
                    >

                        <i class="bi bi-x-circle"></i>

                        Clear

                    </a>

                <?php endif; ?>


            </form>

        </div>


        <!-- EMPTY STATE -->

        <?php if (empty($patients)): ?>

            <div class="patient-empty">

                <div class="patient-empty-icon">

                    <i class="bi bi-person-x"></i>

                </div>

                <h3>
                    No Patients Found
                </h3>

                <p>

                    <?= $search !== ""
                        ? "No patient records match your search."
                        : "There are currently no registered patients." ?>

                </p>

            </div>


        <?php else: ?>


            <!-- PATIENT TABLE -->

            <div class="patient-table-wrapper">

                <table class="patient-table">

                    <thead>

                        <tr>

                            <th>
                                Patient No.
                            </th>

                            <th>
                                Patient Name
                            </th>

                            <th>
                                Registry
                            </th>

                            <th>
                                Birth Date
                            </th>

                            <th>
                                Sex
                            </th>

                            <th>
                                Room / Bed
                            </th>

                            <th>
                                Contact
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($patients as $patient): ?>

                            <?php

                            $full_patient_name =
                                trim(
                                    $patient['first_name'] . ' ' .
                                    ($patient['middle_name'] ?? '') . ' ' .
                                    $patient['last_name']
                                );

                            ?>


                            <tr>


                                <!-- Patient Number -->

                                <td>

                                    <div class="patient-number-cell">

                                        <span class="patient-number-icon">

                                            <i class="bi bi-person-badge"></i>

                                        </span>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $patient['patient_number']
                                            ) ?>

                                        </strong>

                                    </div>

                                </td>


                                <!-- Patient Name -->

                                <td>

                                    <div class="patient-name-cell">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $full_patient_name
                                            ) ?>

                                        </strong>

                                        <span>

                                            Patient ID:

                                            <?= intval(
                                                $patient['patient_id']
                                            ) ?>

                                        </span>

                                    </div>

                                </td>


                                <!-- Registry -->

                                <td>

                                    <div class="patient-registry-cell">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $patient['registry_date']
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty(
                                                $patient['registry_type']
                                            )
                                        ): ?>

                                            <span>

                                                <?= htmlspecialchars(
                                                    $patient['registry_type']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>


                                    </div>

                                </td>


                                <!-- Birth Date -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $patient['birth_date']
                                        )
                                    ): ?>

                                        <?= htmlspecialchars(
                                            $patient['birth_date']
                                        ) ?>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            Not provided
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Sex -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $patient['sex']
                                        )
                                    ): ?>

                                        <span class="patient-sex-badge">

                                            <?= htmlspecialchars(
                                                $patient['sex']
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            Not provided
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Room / Bed -->

                                <td>

                                    <div class="patient-room-cell">


                                        <?php if (
                                            !empty(
                                                $patient['patient_room_no']
                                            )
                                        ): ?>

                                            <strong>

                                                <i class="bi bi-door-open"></i>

                                                <?= htmlspecialchars(
                                                    $patient['patient_room_no']
                                                ) ?>

                                            </strong>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty(
                                                $patient['bed_no']
                                            )
                                        ): ?>

                                            <span>

                                                Bed

                                                <?= htmlspecialchars(
                                                    $patient['bed_no']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>


                                        <?php if (
                                            empty(
                                                $patient['patient_room_no']
                                            ) &&
                                            empty(
                                                $patient['bed_no']
                                            )
                                        ): ?>

                                            <span class="muted-text">
                                                Not assigned
                                            </span>

                                        <?php endif; ?>


                                    </div>

                                </td>


                                <!-- Contact -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $patient['contact_number']
                                        )
                                    ): ?>

                                        <span class="patient-contact">

                                            <i class="bi bi-telephone"></i>

                                            <?= htmlspecialchars(
                                                $patient['contact_number']
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            Not provided
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Actions -->

                                <td>

                                    <div class="patient-actions">


                                        <!-- EDIT -->

                                        <a
                                            href="patients.php?edit=<?= intval(
                                                $patient['patient_id']
                                            ) ?>"
                                            class="patient-action-edit"
                                            title="Edit patient"
                                        >
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>



                                        <!-- DELETE -->

                                        <form
                                            method="POST"
                                            action="patients.php"
                                            onsubmit="return confirm('Are you sure you want to delete this patient?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >

                                            <input
                                                type="hidden"
                                                name="patient_id"
                                                value="<?= intval(
                                                    $patient['patient_id']
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="patient-action-delete"
                                                title="Delete patient"
                                            >

                                                <i class="bi bi-trash3"></i>

                                            </button>

                                        </form>


                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>


    </section>


</main>


<!-- =========================================================
     EDIT PATIENT MODAL
     ========================================================= -->

<?php if ($edit_patient): ?>

<div
    class="modal fade patient-edit-modal"
    id="editPatientModal"
    tabindex="-1"
    aria-labelledby="editPatientModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content">


            <!-- MODAL HEADER -->

            <div class="patient-modal-header">

                <div class="patient-modal-heading">

                    <div class="patient-modal-icon">

                        <i class="bi bi-pencil-square"></i>

                    </div>

                    <div>

                        <div class="patient-modal-eyebrow">
                            PATIENT INFORMATION
                        </div>

                        <h2 id="editPatientModalLabel">
                            Edit Patient
                        </h2>

                        <p>
                            Update the patient's information below.
                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    class="patient-modal-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                >

                    <i class="bi bi-x-lg"></i>

                </button>

            </div>


            <!-- MODAL FORM -->

            <form
                method="POST"
                action="patients.php"
                autocomplete="off"
            >

                <input
                    type="hidden"
                    name="action"
                    value="update"
                >

                <input
                    type="hidden"
                    name="patient_id"
                    value="<?= intval(
                        $edit_patient['patient_id']
                    ) ?>"
                >


                <!-- =================================================
                     SECTION 01
                     ================================================= -->

                <div class="patient-modal-section">

                    <div class="patient-section-title">

                        <div class="patient-section-number">
                            01
                        </div>

                        <div>

                            <h3>
                                Registry Information
                            </h3>

                            <p>
                                Patient registration and identification details.
                            </p>

                        </div>

                    </div>


                    <div class="patient-form-grid patient-form-grid-3">


                        <!-- Patient Number -->

                        <div class="patient-field">

                            <label>
                                Patient Number
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="patient_number"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['patient_number']
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- Registry Date -->

                        <div class="patient-field">

                            <label>
                                Registry Date
                                <span>*</span>
                            </label>

                            <input
                                type="date"
                                name="registry_date"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['registry_date']
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- Registry Type -->

                        <div class="patient-field">

                            <label>
                                Registry Type
                            </label>

                            <input
                                type="text"
                                name="registry_type"
                                class="form-control"
                                placeholder="Enter registry type"
                                value="<?= htmlspecialchars(
                                    $edit_patient['registry_type'] ?? ''
                                ) ?>"
                            >

                        </div>


                    </div>

                </div>


                <!-- =================================================
                     SECTION 02
                     ================================================= -->

                <div class="patient-modal-section">

                    <div class="patient-section-title">

                        <div class="patient-section-number">
                            02
                        </div>

                        <div>

                            <h3>
                                Personal Information
                            </h3>

                            <p>
                                Basic information of the registered patient.
                            </p>

                        </div>

                    </div>


                    <div class="patient-form-grid patient-form-grid-3">


                        <!-- First Name -->

                        <div class="patient-field">

                            <label>
                                First Name
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="first_name"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['first_name']
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- Middle Name -->

                        <div class="patient-field">

                            <label>
                                Middle Name
                            </label>

                            <input
                                type="text"
                                name="middle_name"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['middle_name'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <!-- Last Name -->

                        <div class="patient-field">

                            <label>
                                Last Name
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="last_name"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['last_name']
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- Birth Date -->

                        <div class="patient-field">

                            <label>
                                Birth Date
                            </label>

                            <input
                                type="date"
                                name="birth_date"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['birth_date'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <!-- Sex -->

                        <div class="patient-field">

                            <label>
                                Sex
                            </label>

                            <select
                                name="sex"
                                class="form-select"
                            >

                                <option value="">
                                    Select sex
                                </option>

                                <option
                                    value="Male"
                                    <?= ($edit_patient['sex'] ?? '') === 'Male'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Male
                                </option>

                                <option
                                    value="Female"
                                    <?= ($edit_patient['sex'] ?? '') === 'Female'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Female
                                </option>

                            </select>

                        </div>


                    </div>

                </div>


                <!-- =================================================
                     SECTION 03
                     ================================================= -->

                <div class="patient-modal-section">

                    <div class="patient-section-title">

                        <div class="patient-section-number">
                            03
                        </div>

                        <div>

                            <h3>
                                Room & Contact Information
                            </h3>

                            <p>
                                Current room, bed, and contact information.
                            </p>

                        </div>

                    </div>


                    <div class="patient-form-grid patient-form-grid-4">


                        <!-- Room -->

                        <div class="patient-field">

                            <label>
                                Patient Room No.
                            </label>

                            <input
                                type="text"
                                name="patient_room_no"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['patient_room_no'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <!-- Bed -->

                        <div class="patient-field">

                            <label>
                                Bed No.
                            </label>

                            <input
                                type="text"
                                name="bed_no"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['bed_no'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <!-- Contact -->

                        <div class="patient-field">

                            <label>
                                Contact Number
                            </label>

                            <input
                                type="text"
                                name="contact_number"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['contact_number'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <!-- Address -->

                        <div class="patient-field">

                            <label>
                                Address
                            </label>

                            <input
                                type="text"
                                name="address"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $edit_patient['address'] ?? ''
                                ) ?>"
                            >

                        </div>


                    </div>

                </div>


                <!-- =================================================
                     MODAL ACTIONS
                     ================================================= -->

                <div class="patient-modal-actions">

                    <div class="patient-form-note">

                        <i class="bi bi-info-circle"></i>

                        <span>
                            Review the updated patient information before saving.
                        </span>

                    </div>


                    <a
                        href="patients.php<?= $search !== ''
                            ? '?search=' . urlencode($search)
                            : '' ?>"
                        class="btn-patient-secondary"
                    >

                        <i class="bi bi-x-circle"></i>

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="btn-patient-primary"
                    >

                        <i class="bi bi-check-circle-fill"></i>

                        Save Changes

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const editModalElement =
        document.getElementById("editPatientModal");

    if (editModalElement) {

        const editModal =
            new bootstrap.Modal(editModalElement);

        editModal.show();


        editModalElement.addEventListener(
            "hidden.bs.modal",
            function () {

                window.location.href =
                    "patients.php<?= $search !== ''
                        ? '?search=' . urlencode($search)
                        : '' ?>";

            }
        );

    }

});
</script>

<?php endif; ?>


<?php

require_once "../includes/footer.php";

?>