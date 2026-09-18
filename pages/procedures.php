<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$page_title = "Procedures";

$message = "";
$message_type = "";

$role = $_SESSION['role'] ?? 'Staff';

$can_manage_procedures = in_array(
    $role,
    ['Administrator', 'Scheduler'],
    true
);


/* =========================================================
   ADD / UPDATE / DELETE
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    /*
     * STAFF IS VIEW-ONLY
     * Prevent Add / Update / Delete through direct POST requests.
     */

    if (
        !$can_manage_procedures &&
        in_array($action, ['add', 'update', 'delete'], true)
    ) {
        header("Location: procedures.php");
        exit;
    }


    /* =========================
       ADD PROCEDURE
       ========================= */
    if ($action === "add") {

        $procedure_name = trim($_POST["procedure_name"] ?? "");
        $surgical_type  = trim($_POST["surgical_type"] ?? "");
        $procedure_code = trim($_POST["procedure_code"] ?? "");
        $description    = trim($_POST["description"] ?? "");
        $status         = $_POST["status"] ?? "Active";

        if ($procedure_name === "") {

            $message = "Procedure name is required.";
            $message_type = "danger";

        } else {

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO procedures
                    (
                        procedure_name,
                        surgical_type,
                        procedure_code,
                        description,
                        status
                    )
                    VALUES
                    (
                        :procedure_name,
                        :surgical_type,
                        :procedure_code,
                        :description,
                        :status
                    )
                ");

                $stmt->execute([
                    ":procedure_name" => $procedure_name,
                    ":surgical_type"  => $surgical_type !== "" ? $surgical_type : null,
                    ":procedure_code" => $procedure_code !== "" ? $procedure_code : null,
                    ":description"    => $description !== "" ? $description : null,
                    ":status"         => $status
                ]);

                header("Location: procedures.php?success=added");
                exit;

            } catch (PDOException $e) {

                $message = "Unable to add procedure.";
                $message_type = "danger";
            }
        }
    }

    /* =========================
       UPDATE PROCEDURE
       ========================= */
    elseif ($action === "update") {

        $procedure_id   = (int)($_POST["procedure_id"] ?? 0);
        $procedure_name = trim($_POST["procedure_name"] ?? "");
        $surgical_type  = trim($_POST["surgical_type"] ?? "");
        $procedure_code = trim($_POST["procedure_code"] ?? "");
        $description    = trim($_POST["description"] ?? "");
        $status         = $_POST["status"] ?? "Active";

        if ($procedure_id <= 0 || $procedure_name === "") {

            $message = "Procedure name is required.";
            $message_type = "danger";

        } else {

            try {

                $stmt = $pdo->prepare("
                    UPDATE procedures
                    SET
                        procedure_name = :procedure_name,
                        surgical_type  = :surgical_type,
                        procedure_code = :procedure_code,
                        description    = :description,
                        status         = :status
                    WHERE procedure_id = :procedure_id
                ");

                $stmt->execute([
                    ":procedure_id"   => $procedure_id,
                    ":procedure_name" => $procedure_name,
                    ":surgical_type"  => $surgical_type !== "" ? $surgical_type : null,
                    ":procedure_code" => $procedure_code !== "" ? $procedure_code : null,
                    ":description"    => $description !== "" ? $description : null,
                    ":status"         => $status
                ]);

                header("Location: procedures.php?success=updated");
                exit;

            } catch (PDOException $e) {

                $message = "Unable to update procedure.";
                $message_type = "danger";
            }
        }
    }

    /* =========================
       DELETE PROCEDURE
       ========================= */
    elseif ($action === "delete") {

        $procedure_id = (int)($_POST["procedure_id"] ?? 0);

        if ($procedure_id > 0) {

            try {

                $stmt = $pdo->prepare("
                    DELETE FROM procedures
                    WHERE procedure_id = :procedure_id
                ");

                $stmt->execute([
                    ":procedure_id" => $procedure_id
                ]);

                header("Location: procedures.php?success=deleted");
                exit;

            } catch (PDOException $e) {

                $message = "Unable to delete procedure.";
                $message_type = "danger";
            }
        }
    }
}


/* =========================================================
   SUCCESS MESSAGE
   ========================================================= */

if (isset($_GET["success"])) {

    switch ($_GET["success"]) {

        case "added":
            $message = "Procedure added successfully.";
            $message_type = "success";
            break;

        case "updated":
            $message = "Procedure updated successfully.";
            $message_type = "success";
            break;

        case "deleted":
            $message = "Procedure deleted successfully.";
            $message_type = "success";
            break;
    }
}


/* =========================================================
   GET PROCEDURES
   ========================================================= */

try {

    $stmt = $pdo->query("
        SELECT
            procedure_id,
            procedure_name,
            surgical_type,
            procedure_code,
            description,
            status,
            created_at
        FROM procedures
        ORDER BY procedure_name ASC
    ");

    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $procedures = [];
    $message = "Unable to load procedures.";
    $message_type = "danger";
}


/* =========================================================
   COUNTS
   ========================================================= */

$total_procedures = count($procedures);

$active_procedures = 0;
$inactive_procedures = 0;

foreach ($procedures as $procedure) {

    if ($procedure["status"] === "Active") {
        $active_procedures++;
    }

    if ($procedure["status"] === "Inactive") {
        $inactive_procedures++;
    }
}

?>

<?php require_once "../includes/header.php"; ?>

<link
    rel="stylesheet"
    href="../assets/css/procedures.css?v=20260915"
>

<?php require_once "../includes/sidebar.php"; ?>


<main class="procedures-page">

    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <div class="page-header">

        <div>

            <div class="page-title-row">

                <div class="page-title-icon">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                </div>

                <div>

                    <h1>Procedures</h1>

                    <p>
                        Manage surgical procedures available in the hospital.
                    </p>

                </div>

            </div>

        </div>


        <?php if ($can_manage_procedures): ?>

            <button
                type="button"
                class="btn-add-procedure"
                data-bs-toggle="modal"
                data-bs-target="#addProcedureModal"
            >
                <i class="bi bi-plus-lg"></i>
                Add Procedure
            </button>

        <?php endif; ?>

    </div>


    <!-- =====================================================
         ALERT
         ===================================================== -->

    <?php if ($message !== ""): ?>

        <div class="alert alert-<?= htmlspecialchars($message_type) ?> procedure-alert alert-dismissible fade show">

            <i class="bi bi-info-circle-fill"></i>

            <span>
                <?= htmlspecialchars($message) ?>
            </span>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         SUMMARY CARDS
         ===================================================== -->

    <div class="procedure-summary-grid">

        <div class="procedure-summary-card total">

            <div class="procedure-summary-icon">
                <i class="bi bi-clipboard2-pulse-fill"></i>
            </div>

            <div class="procedure-summary-content">

                <div class="procedure-summary-label">
                    Total Procedures
                </div>

                <div class="procedure-summary-value">
                    <?= $total_procedures ?>
                </div>

            </div>

        </div>


        <div class="procedure-summary-card active">

            <div class="procedure-summary-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div class="procedure-summary-content">

                <div class="procedure-summary-label">
                    Active Procedures
                </div>

                <div class="procedure-summary-value">
                    <?= $active_procedures ?>
                </div>

            </div>

        </div>


        <div class="procedure-summary-card inactive">

            <div class="procedure-summary-icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>

            <div class="procedure-summary-content">

                <div class="procedure-summary-label">
                    Inactive Procedures
                </div>

                <div class="procedure-summary-value">
                    <?= $inactive_procedures ?>
                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         PROCEDURE LIST
         ===================================================== -->

    <div class="procedure-panel">

        <div class="procedure-panel-header">

            <div>

                <h2>
                    Procedure List
                </h2>

                <p>
                    View and manage registered surgical procedures.
                </p>

            </div>

            <div class="procedure-search">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="procedureSearch"
                    placeholder="Search procedures..."
                    autocomplete="off"
                >

            </div>

        </div>


        <div class="procedure-table-wrapper">

            <table class="procedure-table">

                <thead>

                    <tr>

                        <th>Procedure Code</th>

                        <th>Procedure Name</th>

                        <th>Surgical Type</th>

                        <th>Description</th>

                        <th>Status</th>

                        <th class="actions-column">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody id="procedureTableBody">

                    <?php if (empty($procedures)): ?>

                        <tr id="noProcedureRow">

                            <td colspan="6">

                                <div class="empty-state">

                                    <div class="empty-state-icon">
                                        <i class="bi bi-clipboard2-x"></i>
                                    </div>

                                    <h3>
                                        No Procedures Found
                                    </h3>

                                    <p>
                                        Add your first surgical procedure to get started.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($procedures as $procedure): ?>

                            <?php

                            $status = $procedure["status"];

                            if ($status === "Active") {

                                $status_class = "active";
                                $status_icon = "bi-check-circle-fill";

                            } else {

                                $status_class = "inactive";
                                $status_icon = "bi-x-circle-fill";
                            }

                            ?>

                            <tr
                                class="procedure-row"
                                data-search="
                                    <?= htmlspecialchars(
                                        strtolower(
                                            $procedure["procedure_code"] . " " .
                                            $procedure["procedure_name"] . " " .
                                            $procedure["surgical_type"] . " " .
                                            $procedure["description"] . " " .
                                            $procedure["status"]
                                        )
                                    ) ?>
                                "
                            >

                                <td>

                                    <?php if (!empty($procedure["procedure_code"])): ?>

                                        <span class="procedure-code">
                                            <?= htmlspecialchars($procedure["procedure_code"]) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="not-available">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="procedure-name-cell">

                                        <div class="procedure-row-icon">
                                            <i class="bi bi-clipboard2-pulse-fill"></i>
                                        </div>

                                        <div>

                                            <div class="procedure-name">
                                                <?= htmlspecialchars($procedure["procedure_name"]) ?>
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    <?php if (!empty($procedure["surgical_type"])): ?>

                                        <span class="surgical-type">
                                            <?= htmlspecialchars($procedure["surgical_type"]) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="not-available">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="procedure-description">

                                        <?= !empty($procedure["description"])
                                            ? htmlspecialchars($procedure["description"])
                                            : "—"
                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <span class="procedure-status <?= $status_class ?>">

                                        <i class="bi <?= $status_icon ?>"></i>

                                        <?= htmlspecialchars($status) ?>

                                    </span>

                                </td>


                                <td>

                                    <?php if ($can_manage_procedures): ?>

                                        <div class="procedure-actions">

                                            <button
                                                type="button"
                                                class="action-btn edit"
                                                title="Edit Procedure"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editProcedureModal"
                                                onclick='editProcedure(
                                                    <?= json_encode($procedure["procedure_id"]) ?>,
                                                    <?= json_encode($procedure["procedure_name"]) ?>,
                                                    <?= json_encode($procedure["surgical_type"]) ?>,
                                                    <?= json_encode($procedure["procedure_code"]) ?>,
                                                    <?= json_encode($procedure["description"]) ?>,
                                                    <?= json_encode($procedure["status"]) ?>
                                                )'
                                            >
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>


                                            <button
                                                type="button"
                                                class="action-btn delete"
                                                title="Delete Procedure"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteProcedureModal"
                                                onclick='deleteProcedure(
                                                    <?= json_encode($procedure["procedure_id"]) ?>,
                                                    <?= json_encode($procedure["procedure_name"]) ?>
                                                )'
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

                        <tr id="noSearchResults" style="display:none;">

                            <td colspan="6">

                                <div class="empty-search">

                                    <i class="bi bi-search"></i>

                                    <span>
                                        No procedures match your search.
                                    </span>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>


<!-- =========================================================
     ADD PROCEDURE MODAL
     ========================================================= -->

<?php if ($can_manage_procedures): ?>

<div
    class="modal fade"
    id="addProcedureModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content procedure-modal">

            <form method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="add"
                >

                <div class="modal-header">

                    <div class="modal-title-wrapper">

                        <div class="modal-icon add">
                            <i class="bi bi-plus-lg"></i>
                        </div>

                        <div>

                            <h5 class="modal-title">
                                Add Procedure
                            </h5>

                            <p>
                                Register a new surgical procedure.
                            </p>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-8">

                            <label class="form-label">
                                Procedure Name
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="procedure_name"
                                class="form-control"
                                maxlength="200"
                                required
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Procedure Code
                            </label>

                            <input
                                type="text"
                                name="procedure_code"
                                class="form-control"
                                maxlength="50"
                                placeholder="e.g. PROC-001"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Surgical Type
                            </label>

                            <input
                                type="text"
                                name="surgical_type"
                                class="form-control"
                                maxlength="100"
                                placeholder="e.g. General Surgery"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option value="Active">
                                    Active
                                </option>

                                <option value="Inactive">
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="4"
                                placeholder="Enter procedure description..."
                            ></textarea>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn-modal-cancel"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn-modal-save"
                    >
                        <i class="bi bi-check-lg"></i>
                        Save Procedure
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     EDIT PROCEDURE MODAL
     ========================================================= -->

<?php if ($can_manage_procedures): ?>

<div
    class="modal fade"
    id="editProcedureModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content procedure-modal">

            <form method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="update"
                >

                <input
                    type="hidden"
                    name="procedure_id"
                    id="editProcedureId"
                >

                <div class="modal-header">

                    <div class="modal-title-wrapper">

                        <div class="modal-icon edit">
                            <i class="bi bi-pencil-fill"></i>
                        </div>

                        <div>

                            <h5 class="modal-title">
                                Edit Procedure
                            </h5>

                            <p>
                                Update procedure information.
                            </p>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-8">

                            <label class="form-label">
                                Procedure Name
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="procedure_name"
                                id="editProcedureName"
                                class="form-control"
                                maxlength="200"
                                required
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Procedure Code
                            </label>

                            <input
                                type="text"
                                name="procedure_code"
                                id="editProcedureCode"
                                class="form-control"
                                maxlength="50"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Surgical Type
                            </label>

                            <input
                                type="text"
                                name="surgical_type"
                                id="editSurgicalType"
                                class="form-control"
                                maxlength="100"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                id="editProcedureStatus"
                                class="form-select"
                            >

                                <option value="Active">
                                    Active
                                </option>

                                <option value="Inactive">
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                id="editProcedureDescription"
                                class="form-control"
                                rows="4"
                            ></textarea>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn-modal-cancel"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn-modal-save"
                    >
                        <i class="bi bi-check-lg"></i>
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     DELETE PROCEDURE MODAL
     ========================================================= -->

<?php if ($can_manage_procedures): ?>

<div
    class="modal fade"
    id="deleteProcedureModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content procedure-modal delete-modal">

            <form method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="delete"
                >

                <input
                    type="hidden"
                    name="procedure_id"
                    id="deleteProcedureId"
                >

                <div class="delete-modal-body">

                    <div class="delete-icon">
                        <i class="bi bi-trash3"></i>
                    </div>

                    <h4>
                        Delete Procedure?
                    </h4>

                    <p>
                        Are you sure you want to delete
                        <strong id="deleteProcedureName"></strong>?
                    </p>

                    <p class="delete-warning">
                        This action cannot be undone.
                    </p>

                </div>


                <div class="modal-footer delete-footer">

                    <button
                        type="button"
                        class="btn-modal-cancel"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn-delete-confirm"
                    >
                        <i class="bi bi-trash3"></i>
                        Delete Procedure
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<script>

function editProcedure(
    id,
    name,
    surgicalType,
    code,
    description,
    status
) {

    document.getElementById("editProcedureId").value = id;

    document.getElementById("editProcedureName").value = name || "";

    document.getElementById("editSurgicalType").value =
        surgicalType || "";

    document.getElementById("editProcedureCode").value =
        code || "";

    document.getElementById("editProcedureDescription").value =
        description || "";

    document.getElementById("editProcedureStatus").value =
        status || "Active";
}


function deleteProcedure(id, name) {

    document.getElementById("deleteProcedureId").value = id;

    document.getElementById("deleteProcedureName").textContent =
        name || "this procedure";
}


/* =========================================================
   SEARCH
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const searchInput =
        document.getElementById("procedureSearch");

    if (!searchInput) {
        return;
    }

    searchInput.addEventListener("input", function () {

        const searchValue =
            this.value.trim().toLowerCase();

        const rows =
            document.querySelectorAll(".procedure-row");

        const noResults =
            document.getElementById("noSearchResults");

        let visibleCount = 0;

        rows.forEach(function (row) {

            const searchText =
                row.getAttribute("data-search") || "";

            if (
                searchValue === "" ||
                searchText.includes(searchValue)
            ) {

                row.style.display = "";

                visibleCount++;

            } else {

                row.style.display = "none";
            }

        });

        if (noResults) {

            noResults.style.display =
                visibleCount === 0 && searchValue !== ""
                    ? ""
                    : "none";
        }

    });

});

</script>


<?php require_once "../includes/footer.php"; ?>