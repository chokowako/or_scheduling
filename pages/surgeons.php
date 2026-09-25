<?php

session_start();
require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";

$role = $_SESSION['role'] ?? 'Staff';

$can_manage_surgeons = in_array(
    $role,
    ['Administrator', 'Scheduler'],
    true
);

$edit_doctor = null;

/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGES
|--------------------------------------------------------------------------
*/

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case "added":
            $message = "Surgeon successfully added.";
            $message_type = "success";
            break;
        case "updated":
            $message = "Surgeon information successfully updated.";
            $message_type = "success";
            break;
        case "deleted":
            $message = "Surgeon successfully deleted.";
            $message_type = "success";
            break;
    }
}


/*
|--------------------------------------------------------------------------
| ADD / UPDATE / DELETE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? "";
    if (
        !$can_manage_surgeons &&
        in_array($action, ['add', 'update', 'delete'], true)
    ) {
        header("Location: surgeons.php");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | ADD SURGEON
    |--------------------------------------------------------------------------
    */

    if ($action === "add") {
        $last_name = trim($_POST['last_name'] ?? "");
        $first_name = trim($_POST['first_name'] ?? "");
        $middle_name = trim($_POST['middle_name'] ?? "");
        $suffix_name = trim($_POST['suffix_name'] ?? "");
        $birth_date = trim($_POST['birth_date'] ?? "");
        $nick_name = trim($_POST['nick_name'] ?? "");
        $sex_gender = trim($_POST['sex_gender'] ?? "");
        $service_class = trim($_POST['service_class'] ?? "");
        $specialization = trim($_POST['specialization'] ?? "");
        $status = $_POST['status'] ?? "Active";

        if ($last_name === "" || $first_name === "") {
            $message = "Last Name and First Name are required.";
            $message_type = "danger";
        } else {
            try {
                $sql = "
                    INSERT INTO doctors
                    (
                        last_name,
                        first_name,
                        middle_name,
                        suffix_name,
                        birth_date,
                        nick_name,
                        sex_gender,
                        service_class,
                        specialization,
                        status
                    )
                    VALUES
                    (
                        :last_name,
                        :first_name,
                        :middle_name,
                        :suffix_name,
                        :birth_date,
                        :nick_name,
                        :sex_gender,
                        :service_class,
                        :specialization,
                        :status
                    )
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ":last_name" => $last_name,
                    ":first_name" => $first_name,
                    ":middle_name" => $middle_name !== "" ? $middle_name : null,
                    ":suffix_name" => $suffix_name !== "" ? $suffix_name : null,
                    ":birth_date" => $birth_date !== "" ? $birth_date : null,
                    ":nick_name" => $nick_name !== "" ? $nick_name : null,
                    ":sex_gender" => $sex_gender !== "" ? $sex_gender : null,
                    ":service_class" => $service_class !== "" ? $service_class : null,
                    ":specialization" => $specialization !== "" ? $specialization : null,
                    ":status" => $status
                ]);
                header("Location: surgeons.php?success=added");
                exit;
            } catch (PDOException $e) {

                $message = "Unable to add surgeon: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE SURGEON
    |--------------------------------------------------------------------------
    */

    elseif ($action === "update") {
        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        $last_name = trim($_POST['last_name'] ?? "");
        $first_name = trim($_POST['first_name'] ?? "");
        $middle_name = trim($_POST['middle_name'] ?? "");
        $suffix_name = trim($_POST['suffix_name'] ?? "");
        $birth_date = trim($_POST['birth_date'] ?? "");
        $nick_name = trim($_POST['nick_name'] ?? "");
        $sex_gender = trim($_POST['sex_gender'] ?? "");
        $service_class = trim($_POST['service_class'] ?? "");
        $specialization = trim($_POST['specialization'] ?? "");
        $status = $_POST['status'] ?? "Active";

        if ($doctor_id <= 0) {
            $message = "Invalid surgeon.";
            $message_type = "danger";
        } elseif ($last_name === "" || $first_name === "") {

            $message = "Last Name and First Name are required.";
            $message_type = "danger";
        } else {
            try {
                $sql = "
                    UPDATE doctors
                    SET
                        last_name = :last_name,
                        first_name = :first_name,
                        middle_name = :middle_name,
                        suffix_name = :suffix_name,
                        birth_date = :birth_date,
                        nick_name = :nick_name,
                        sex_gender = :sex_gender,
                        service_class = :service_class,
                        specialization = :specialization,
                        status = :status
                    WHERE doctor_id = :doctor_id
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ":last_name" => $last_name,
                    ":first_name" => $first_name,
                    ":middle_name" => $middle_name !== "" ? $middle_name : null,
                    ":suffix_name" => $suffix_name !== "" ? $suffix_name : null,
                    ":birth_date" => $birth_date !== "" ? $birth_date : null,
                    ":nick_name" => $nick_name !== "" ? $nick_name : null,
                    ":sex_gender" => $sex_gender !== "" ? $sex_gender : null,
                    ":service_class" => $service_class !== "" ? $service_class : null,
                    ":specialization" => $specialization !== "" ? $specialization : null,
                    ":status" => $status,
                    ":doctor_id" => $doctor_id
                ]);
                header("Location: surgeons.php?success=updated");
                exit;
            } catch (PDOException $e) {
                $message = "Unable to update surgeon: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE SURGEON
    |--------------------------------------------------------------------------
    */

    elseif ($action === "delete") {
        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        if ($doctor_id <= 0) {
            $message = "Invalid surgeon.";
            $message_type = "danger";
        } else {
            try {
                $sql = "
                    DELETE FROM doctors
                    WHERE doctor_id = :doctor_id
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ":doctor_id" => $doctor_id
                ]);
                header("Location: surgeons.php?success=deleted");
                exit;
            } catch (PDOException $e) {
                $message =
                    "Unable to delete surgeon. This surgeon may already be used in an operating schedule.";
                $message_type = "danger";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| PAGINATION & SEARCH SETUP
|--------------------------------------------------------------------------
*/

$results_per_page = 10; // Show 10 surgeons per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$start_from = ($page - 1) * $results_per_page;

$search = trim($_GET['search'] ?? "");

// 1. Count total records so the page buttons know how many pages to show
if ($search !== "") {
    $count_sql = "
        SELECT COUNT(*) FROM doctors
        WHERE
            last_name LIKE :search
            OR first_name LIKE :search
            OR middle_name LIKE :search
            OR nick_name LIKE :search
            OR service_class LIKE :search
            OR specialization LIKE :search
			ORDER BY last_name ASC, first_name ASC
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([":search" => "%" . $search . "%"]);
    $total_records = $count_stmt->fetchColumn();
} else {
    $count_sql = "SELECT COUNT(*) FROM doctors";
    $total_records = $pdo->query($count_sql)->fetchColumn();
}

$total_pages = ceil($total_records / $results_per_page);

// 2. Fetch only 10 rows for the current page
if ($search !== "") {
    $sql = "
        SELECT
            doctor_id, last_name, first_name, middle_name, suffix_name,
            birth_date, nick_name, sex_gender, service_class,
            specialization, status, created_at
        FROM doctors
        WHERE
            last_name LIKE :search
            OR first_name LIKE :search
            OR middle_name LIKE :search
            OR nick_name LIKE :search
            OR service_class LIKE :search
            OR specialization LIKE :search
        ORDER BY last_name ASC, first_name ASC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%" . $search . "%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start_from, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $sql = "
        SELECT
            doctor_id, last_name, first_name, middle_name, suffix_name,
            birth_date, nick_name, sex_gender, service_class,
            specialization, status, created_at
        FROM doctors
        ORDER BY last_name ASC, first_name ASC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start_from, PDO::PARAM_INT);
    $stmt->execute();
}

$surgeons = $stmt->fetchAll();











/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$total_surgeons = count($surgeons);
$active_count = 0;
$inactive_count = 0;
foreach ($surgeons as $surgeon) {
    if ($surgeon['status'] === "Active") {
        $active_count++;
    }
    if ($surgeon['status'] === "Inactive") {
        $inactive_count++;
    }
}

/*
|--------------------------------------------------------------------------
| PAGE SETUP
|--------------------------------------------------------------------------
*/

$page_title = "Surgeons";
require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<link
    rel="stylesheet"
    href="../assets/css/surgeons.css?v=20260914"
>

<main class="main-content surgeons-page">


    <!-- =========================================================
         ALERT
         ========================================================= -->

    <?php if ($message !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?> surgeon-alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>


    <!-- =========================================================
         SURGEON FORM
         ========================================================= -->

    <?php if ($can_manage_surgeons): ?>
        <section class="surgeon-form-card">
            <div class="surgeon-form-header">
                <div class="surgeon-form-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div>
                    <div class="surgeon-form-eyebrow">
                        SURGEON MANAGEMENT
                    </div>
                    <h1 class="surgeon-form-heading">
                        Add Surgeon
                    </h1>
                    <p class="surgeon-form-description">
                        Register a surgeon for operating room scheduling.
                    </p>
                </div>
            </div>

            <form method="POST">
                <input
                    type="hidden"
                    name="action"
                    value="add"
                >


                <!-- =================================================
                     PERSONAL INFORMATION
                     ================================================= -->

                <div class="surgeon-section-title">
                    <span class="surgeon-section-number">
                        01
                    </span>
                    <div>
                        <strong>Personal Information</strong>
                        <small>Basic surgeon information</small>
                    </div>
                </div>

                <div class="surgeon-form-grid surgeon-form-grid-4">

                    <div class="surgeon-field">
                        <label>
                            Last Name
                            <span>*</span>
                        </label>
                        <input
                            type="text"
                            name="last_name"
                            placeholder="Enter last name"
                            required
                        >
                    </div>

                    <div class="surgeon-field">
                        <label>
                            First Name
                            <span>*</span>
                        </label>
                        <input
                            type="text"
                            name="first_name"
                            placeholder="Enter first name"
                            required
                        >

                    </div>

                    <div class="surgeon-field">
                        <label>
                            Middle Name
                        </label>
                        <input
                            type="text"
                            name="middle_name"
                            placeholder="Enter middle name"
                        >
                    </div>

                    <div class="surgeon-field">
                        <label>
                            Suffix
                        </label>

                        <input
                            type="text"
                            name="suffix_name"
                            placeholder="Jr., Sr., III"
                        >
                    </div>


                    <div class="surgeon-field">
                        <label>
                            Nickname
                        </label>
                        <input
                            type="text"
                            name="nick_name"
                            placeholder="Preferred name"
                        >

                    </div>

                    <div class="surgeon-field">
                        <label>
                            Birth Date
                        </label>
                        <input
                            type="date"
                            name="birth_date"
                        >

                    </div>

                    <div class="surgeon-field">
                        <label>
                            Sex / Gender
                        </label>
                        <select name="sex_gender">
                            <option value="">
                                Select
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


                <!-- =================================================
                     PROFESSIONAL INFORMATION
                     ================================================= -->

                <div class="surgeon-section-title surgeon-section-spacing">
                    <span class="surgeon-section-number">
                        02
                    </span>

                    <div>
                        <strong>Professional Information</strong>
                        <small>Service and specialization</small>
                    </div>
                </div>


                <div class="surgeon-form-grid surgeon-form-grid-3">
                    <div class="surgeon-field">
                        <label>
                            Service Class
                        </label>

                        <input
                            type="text"
                            name="service_class"
                            placeholder="Example: General Surgery"
                        >
                    </div>


                    <div class="surgeon-field">
                        <label>
                            Specialization
                        </label>

                        <input
                            type="text"
                            name="specialization"
                            placeholder="Example: Orthopedic Surgery"
                        >
                    </div>

                    <div class="surgeon-field">
                        <label>
                            Status
                        </label>
                        <select name="status">
                            <option value="Active">
                                Active
                            </option>
                            <option value="Inactive">
                                Inactive
                            </option>
                        </select>
                    </div>
                </div>


                <!-- =================================================
                     FORM ACTIONS
                     ================================================= -->

                <div class="surgeon-form-actions">
                    <div class="surgeon-form-note">
                        <i class="bi bi-info-circle"></i>

                        Fields marked with
                        <strong>*</strong>
                        are required.
                    </div>


                    <div class="surgeon-form-buttons">
                        <button
                            type="reset"
                            class="btn-surgeon-secondary"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Clear
                        </button>

                        <button
                            type="submit"
                            class="btn-surgeon-primary"
                        >
                            <i class="bi bi-person-plus"></i>
                            Add Surgeon
                        </button>
                    </div>
                </div>
            </form>
        </section>
    <?php endif; ?>


    <!-- =========================================================
         SURGEON LIST
         ========================================================= -->

    <section class="surgeon-list-card">

        <div class="surgeon-list-header">
            <div class="surgeon-list-heading-area">
                <div class="surgeon-list-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div>

                    <div class="surgeon-list-eyebrow">
                        REGISTERED SURGEONS
                    </div>
                    <h2 class="surgeon-list-heading">
                        Surgeons
                    </h2>
                </div>
            </div>

            <div class="surgeon-list-count">

                <strong>
                    <?= $total_surgeons ?>
                </strong>

                <span>
                    Total
                </span>

            </div>
        </div>


        <!-- =====================================================
             SEARCH AREA
             ===================================================== -->

        <div class="surgeon-search-area">
            <form
                method="GET"
                class="surgeon-search-form"
            >
                <div class="surgeon-search-input">
                    <i class="bi bi-search"></i>
                    <input
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search by name, service class, or specialization..."
                    >
                </div>

                <button
                    type="submit"
                    class="btn-surgeon-search"
                >
                    <i class="bi bi-search"></i>
                    Search
                </button>

                <?php if ($search !== ""): ?>
                    <a
                        href="surgeons.php"
                        class="btn-surgeon-clear"
                    >
                        <i class="bi bi-x-lg"></i>
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>


        <!-- =====================================================
             TABLE
             ===================================================== -->

        <?php if (empty($surgeons)): ?>

            <div class="surgeon-empty">

                <div class="surgeon-empty-icon">

                    <i class="bi bi-person-x"></i>

                </div>

                <h3>
                    No surgeons found
                </h3>

                <p>
                    No surgeon records match your search.
                </p>

            </div>

        <?php else: ?>


            <div class="surgeon-table-wrapper">

                <table class="surgeon-table">

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Surgeon
                            </th>

                            <th>
                                Sex / Gender
                            </th>

                            <th>
                                Service Class
                            </th>

                            <th>
                                Specialization
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($surgeons as $surgeon): ?>

                        <?php

                        $full_name =
                            trim(
                                $surgeon['last_name']
                                . ", "
                                . $surgeon['first_name']
                                . (
                                    !empty($surgeon['middle_name'])
                                    ? " " . $surgeon['middle_name']
                                    : ""
                                )
                                . (
                                    !empty($surgeon['suffix_name'])
                                    ? " " . $surgeon['suffix_name']
                                    : ""
                                )
                            );

                        ?>

                        <tr>


                            <!-- ID -->

                            <td class="surgeon-id-cell">

                                <span class="surgeon-id-icon">

                                    <i class="bi bi-person-badge"></i>

                                </span>

                                #<?= (int)$surgeon['doctor_id'] ?>

                            </td>


                            <!-- NAME -->

                            <td class="surgeon-name-cell">

                                <strong>
                                    <?= htmlspecialchars($full_name) ?>
                                </strong>

                                <?php if (!empty($surgeon['nick_name'])): ?>

                                    <small>
                                        "<?= htmlspecialchars($surgeon['nick_name']) ?>"
                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- SEX -->

                            <td>

                                <?php if (!empty($surgeon['sex_gender'])): ?>

                                    <span class="surgeon-sex-badge">

                                        <?= htmlspecialchars($surgeon['sex_gender']) ?>

                                    </span>

                                <?php else: ?>

                                    <span class="muted-text">
                                        Not specified
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- SERVICE -->

                            <td>

                                <?php if (!empty($surgeon['service_class'])): ?>

                                    <?= htmlspecialchars($surgeon['service_class']) ?>

                                <?php else: ?>

                                    <span class="muted-text">
                                        Not specified
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- SPECIALIZATION -->

                            <td>

                                <?php if (!empty($surgeon['specialization'])): ?>

                                    <span class="surgeon-specialization">

                                        <i class="bi bi-heart-pulse"></i>

                                        <?= htmlspecialchars($surgeon['specialization']) ?>

                                    </span>

                                <?php else: ?>

                                    <span class="muted-text">
                                        Not specified
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="surgeon-status-badge <?= $surgeon['status'] === 'Active' ? 'status-active' : 'status-inactive' ?>"
                                >

                                    <span class="status-dot"></span>

                                    <?= htmlspecialchars($surgeon['status']) ?>

                                </span>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <?php if ($can_manage_surgeons): ?>

                                    <div class="surgeon-actions">


                                        <!-- EDIT -->

                                        <button
                                            type="button"
                                            class="surgeon-action-edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editSurgeonModal"
                                            data-id="<?= (int)$surgeon['doctor_id'] ?>"
                                            data-last-name="<?= htmlspecialchars($surgeon['last_name'], ENT_QUOTES) ?>"
                                            data-first-name="<?= htmlspecialchars($surgeon['first_name'], ENT_QUOTES) ?>"
                                            data-middle-name="<?= htmlspecialchars($surgeon['middle_name'] ?? '', ENT_QUOTES) ?>"
                                            data-suffix-name="<?= htmlspecialchars($surgeon['suffix_name'] ?? '', ENT_QUOTES) ?>"
                                            data-birth-date="<?= htmlspecialchars($surgeon['birth_date'] ?? '', ENT_QUOTES) ?>"
                                            data-nick-name="<?= htmlspecialchars($surgeon['nick_name'] ?? '', ENT_QUOTES) ?>"
                                            data-sex-gender="<?= htmlspecialchars($surgeon['sex_gender'] ?? '', ENT_QUOTES) ?>"
                                            data-service-class="<?= htmlspecialchars($surgeon['service_class'] ?? '', ENT_QUOTES) ?>"
                                            data-specialization="<?= htmlspecialchars($surgeon['specialization'] ?? '', ENT_QUOTES) ?>"
                                            data-status="<?= htmlspecialchars($surgeon['status'], ENT_QUOTES) ?>"
                                            title="Edit surgeon"
                                        >

                                            <i class="bi bi-pencil-fill"></i>

                                        </button>


                                        <!-- DELETE -->

                                        <button
                                            type="button"
                                            class="surgeon-action-delete"
                                            title="Delete surgeon"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteSurgeonModal"
                                            data-id="<?= (int)$surgeon['doctor_id'] ?>"
                                            data-name="<?= htmlspecialchars($full_name, ENT_QUOTES) ?>"
                                        >

                                            <i class="bi bi-trash-fill"></i>

                                        </button>


                                    </div>

                                <?php else: ?>

                                    <span class="muted-text">
                                        View only
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>
		       
        <?php endif; ?>
		
		
		</div>

      
        <!-- =====================================================
             PAGINATION BUTTONS
             ===================================================== -->
        <?php if ($total_pages > 1): ?>
            <div style="text-align: right; margin-top: 20px; margin-bottom: 30px;">
                
                <!-- Previous Button -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" style="padding: 8px 12px; margin: 2px; border: 1px solid #ccc; text-decoration: none; border-radius: 4px;">&laquo; Previous</a>
                <?php endif; ?>

                <!-- Number Buttons -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span style="padding: 8px 12px; margin: 2px; background: #007bff; color: white; border: 1px solid #007bff; border-radius: 4px;"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" style="padding: 8px 12px; margin: 2px; border: 1px solid #ccc; text-decoration: none; border-radius: 4px;"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Next Button -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" style="padding: 8px 12px; margin: 2px; border: 1px solid #ccc; text-decoration: none; border-radius: 4px;">Next &raquo;</a>
                <?php endif; ?>

            </div>
			
        <?php endif; ?>


    </section>

</main>



<!-- ============================================================
     EDIT SURGEON MODAL
     ============================================================ -->

<?php if ($can_manage_surgeons): ?>

    <div
        class="modal fade"
        id="editSurgeonModal"
        tabindex="-1"
        aria-labelledby="editSurgeonModalLabel"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered surgeon-edit-dialog">

            <div class="modal-content surgeon-edit-modal">


                <!-- HEADER -->

                <div class="modal-header surgeon-modal-header">

                    <div class="surgeon-modal-header-main">

                        <div class="surgeon-modal-icon">

                            <i class="bi bi-person-badge-fill"></i>

                        </div>

                        <div>

                            <div class="surgeon-modal-eyebrow">
                                SURGEON MANAGEMENT
                            </div>

                            <h5
                                class="surgeon-modal-heading"
                                id="editSurgeonModalLabel"
                            >
                                Edit Surgeon
                            </h5>

                            <p class="surgeon-modal-subtitle">
                                Update the surgeon's personal and professional information.
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="btn-close surgeon-modal-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>


                <!-- FORM -->

                <form method="POST">

                    <input
                        type="hidden"
                        name="action"
                        value="update"
                    >

                    <input
                        type="hidden"
                        name="doctor_id"
                        id="edit_doctor_id"
                    >


                    <!-- BODY -->

                    <div class="surgeon-modal-body">


                        <!-- ====================================================
                             PERSONAL INFORMATION
                             ==================================================== -->

                        <section class="surgeon-modal-section">

                            <div class="surgeon-modal-section-heading">

                                <span class="surgeon-modal-section-number">
                                    01
                                </span>

                                <div class="surgeon-modal-section-copy">

                                    <div class="surgeon-modal-section-title">
                                        Personal Information
                                    </div>

                                    <div class="surgeon-modal-section-subtitle">
                                        Basic information about the surgeon
                                    </div>

                                </div>

                            </div>


                            <div class="surgeon-modal-grid surgeon-modal-grid-4">


                                <!-- Last Name -->

                                <div class="surgeon-field">

                                    <label for="edit_last_name">
                                        Last Name <span>*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="last_name"
                                        id="edit_last_name"
                                        class="form-control"
                                        required
                                    >

                                </div>


                                <!-- First Name -->

                                <div class="surgeon-field">

                                    <label for="edit_first_name">
                                        First Name <span>*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="first_name"
                                        id="edit_first_name"
                                        class="form-control"
                                        required
                                    >

                                </div>


                                <!-- Middle Name -->

                                <div class="surgeon-field">

                                    <label for="edit_middle_name">
                                        Middle Name
                                    </label>

                                    <input
                                        type="text"
                                        name="middle_name"
                                        id="edit_middle_name"
                                        class="form-control"
                                    >

                                </div>


                                <!-- Suffix -->

                                <div class="surgeon-field">

                                    <label for="edit_suffix_name">
                                        Suffix
                                    </label>

                                    <input
                                        type="text"
                                        name="suffix_name"
                                        id="edit_suffix_name"
                                        class="form-control"
                                        placeholder="Jr., Sr., III"
                                    >

                                </div>


                                <!-- Birth Date -->

                                <div class="surgeon-field">

                                    <label for="edit_birth_date">
                                        Birth Date
                                    </label>

                                    <input
                                        type="date"
                                        name="birth_date"
                                        id="edit_birth_date"
                                        class="form-control"
                                    >

                                </div>


                                <!-- Nickname -->

                                <div class="surgeon-field">

                                    <label for="edit_nick_name">
                                        Nickname
                                    </label>

                                    <input
                                        type="text"
                                        name="nick_name"
                                        id="edit_nick_name"
                                        class="form-control"
                                    >

                                </div>


                                <!-- Sex / Gender -->

                                <div class="surgeon-field">

                                    <label for="edit_sex_gender">
                                        Sex / Gender <span>*</span>
                                    </label>

                                    <select
                                        name="sex_gender"
                                        id="edit_sex_gender"
                                        class="form-select"
                                        required
                                    >

                                        <option value="">
                                            Select
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

                        </section>


                        <!-- ====================================================
                             PROFESSIONAL INFORMATION
                             ==================================================== -->

                        <section class="surgeon-modal-section surgeon-modal-section-professional">

                            <div class="surgeon-modal-section-heading">

                                <span class="surgeon-modal-section-number">
                                    02
                                </span>

                                <div class="surgeon-modal-section-copy">

                                    <div class="surgeon-modal-section-title">
                                        Professional Information
                                    </div>

                                    <div class="surgeon-modal-section-subtitle">
                                        Service, specialization, and account status
                                    </div>

                                </div>

                            </div>


                            <div class="surgeon-modal-grid surgeon-modal-grid-3">


                                <!-- Service Class -->

                                <div class="surgeon-field">

                                    <label for="edit_service_class">
                                        Service Class <span>*</span>
                                    </label>

                                    <select
                                        name="service_class"
                                        id="edit_service_class"
                                        class="form-select"
                                        required
                                    >

                                        <option value="">
                                            Select service class
                                        </option>

                                        <option value="Regular">
                                            Regular
                                        </option>

                                        <option value="Visiting">
                                            Visiting
                                        </option>

                                    </select>

                                </div>


                                <!-- Specialization -->

                                <div class="surgeon-field">

                                    <label for="edit_specialization">
                                        Specialization <span>*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="specialization"
                                        id="edit_specialization"
                                        class="form-control"
                                        required
                                    >

                                </div>


                                <!-- Status -->

                                <div class="surgeon-field">

                                    <label for="edit_status">
                                        Status <span>*</span>
                                    </label>

                                    <select
                                        name="status"
                                        id="edit_status"
                                        class="form-select"
                                        required
                                    >

                                        <option value="Active">
                                            Active
                                        </option>

                                        <option value="Inactive">
                                            Inactive
                                        </option>

                                    </select>

                                </div>


                            </div>

                        </section>

                    </div>


                    <!-- FOOTER -->

                    <div class="modal-footer surgeon-modal-actions">

                        <div class="surgeon-modal-note">

                            <i class="bi bi-info-circle"></i>

                            <span>
                                Fields marked with <strong>*</strong> are required.
                            </span>

                        </div>


                        <div class="surgeon-modal-buttons">

                            <button
                                type="button"
                                class="btn-surgeon-secondary"
                                data-bs-dismiss="modal"
                            >
                                <i class="bi bi-x-lg"></i>
                                Cancel
                            </button>


                            <button
                                type="submit"
                                class="btn-surgeon-primary"
                            >
                                <i class="bi bi-check2"></i>
                                Save Changes
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

<?php endif; ?>



<!-- ============================================================
     DELETE SURGEON MODAL
     ============================================================ -->

<?php if ($can_manage_surgeons): ?>

    <div
        class="modal fade"
        id="deleteSurgeonModal"
        tabindex="-1"
        aria-labelledby="deleteSurgeonModalLabel"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">


                <!-- HEADER -->

                <div class="modal-header">

                    <div>

                        <div
                            style="
                                font-size: 11px;
                                font-weight: 700;
                                letter-spacing: 1.2px;
                                color: #4da985;
                                margin-bottom: 4px;
                            "
                        >
                            CONFIRM ACTION
                        </div>

                        <h5
                            class="modal-title"
                            id="deleteSurgeonModalLabel"
                        >
                            Delete Surgeon
                        </h5>

                    </div>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>


                <!-- DELETE FORM -->

                <form
                    method="POST"
                    action="surgeons.php"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="delete"
                    >

                    <input
                        type="hidden"
                        name="doctor_id"
                        id="delete_surgeon_id"
                    >


                    <!-- BODY -->

                    <div
                        class="modal-body"
                        style="
                            text-align: center;
                            padding: 30px 25px;
                        "
                    >

                        <div
                            style="
                                width: 58px;
                                height: 58px;
                                margin: 0 auto 18px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                background: #fdecec;
                                color: #dc3545;
                                font-size: 24px;
                            "
                        >

                            <i class="bi bi-trash3"></i>

                        </div>


                        <p
                            style="
                                margin-bottom: 8px;
                                font-size: 15px;
                                color: #42544d;
                            "
                        >

                            Are you sure you want to delete
                            <strong id="delete_surgeon_name"></strong>?

                        </p>


                        <span
                            style="
                                font-size: 13px;
                                color: #7b8b85;
                            "
                        >
                            This action cannot be undone.
                        </span>

                    </div>


                    <!-- FOOTER -->

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>


                        <button
                            type="submit"
                            class="btn btn-danger"
                        >

                            <i class="bi bi-trash3"></i>

                            Delete Surgeon

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

<?php endif; ?>



<script>

document.addEventListener("DOMContentLoaded", function () {


    /*
    |--------------------------------------------------------------------------
    | EDIT SURGEON
    |--------------------------------------------------------------------------
    */

    const editButtons =
        document.querySelectorAll(".surgeon-action-edit");


    editButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("edit_doctor_id").value =
                button.dataset.id;

            document.getElementById("edit_last_name").value =
                button.dataset.lastName;

            document.getElementById("edit_first_name").value =
                button.dataset.firstName;

            document.getElementById("edit_middle_name").value =
                button.dataset.middleName;

            document.getElementById("edit_suffix_name").value =
                button.dataset.suffixName;

            document.getElementById("edit_birth_date").value =
                button.dataset.birthDate;

            document.getElementById("edit_nick_name").value =
                button.dataset.nickName;

            document.getElementById("edit_sex_gender").value =
                button.dataset.sexGender;

            document.getElementById("edit_service_class").value =
                button.dataset.serviceClass;

            document.getElementById("edit_specialization").value =
                button.dataset.specialization;

            document.getElementById("edit_status").value =
                button.dataset.status;

        });

    });


    /*
    |--------------------------------------------------------------------------
    | DELETE SURGEON
    |--------------------------------------------------------------------------
    */

    const deleteSurgeonButtons =
        document.querySelectorAll(".surgeon-action-delete");


    deleteSurgeonButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const surgeonId =
                this.dataset.id || "";

            const surgeonName =
                this.dataset.name || "this surgeon";


            document.getElementById(
                "delete_surgeon_id"
            ).value = surgeonId;


            document.getElementById(
                "delete_surgeon_name"
            ).textContent = surgeonName;

        });

    });

});

</script>


<?php

require_once "../includes/footer.php";

?>