<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
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


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";


    /*
    |--------------------------------------------------------------------------
    | ADD FACILITY
    |--------------------------------------------------------------------------
    */

    if ($action === "add") {

        $room_name = trim($_POST['room_name'] ?? "");
        $room_description = trim($_POST['room_description'] ?? "");
        $status = $_POST['status'] ?? "Available";


        if ($room_name === "") {

            $message = "Facility / room name is required.";
            $message_type = "danger";

        } else {

            $allowed_statuses = [
                "Available",
                "Occupied",
                "Maintenance",
                "Inactive"
            ];

            if (!in_array($status, $allowed_statuses, true)) {

                $status = "Available";

            }


            try {

                $sql = "
                    INSERT INTO operating_rooms
                    (
                        room_name,
                        room_description,
                        status
                    )
                    VALUES
                    (
                        :room_name,
                        :room_description,
                        :status
                    )
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":room_name" => $room_name,
                    ":room_description" => $room_description,
                    ":status" => $status
                ]);


                header("Location: facilities.php?success=added");
                exit;

            } catch (PDOException $e) {

                $message = "Unable to add facility: " . $e->getMessage();
                $message_type = "danger";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | EDIT FACILITY
    |--------------------------------------------------------------------------
    */

    elseif ($action === "edit") {

        $room_id = intval($_POST['room_id'] ?? 0);
        $room_name = trim($_POST['room_name'] ?? "");
        $room_description = trim($_POST['room_description'] ?? "");
        $status = $_POST['status'] ?? "Available";


        $allowed_statuses = [
            "Available",
            "Occupied",
            "Maintenance",
            "Inactive"
        ];


        if ($room_id <= 0) {

            $message = "Invalid facility.";
            $message_type = "danger";

        } elseif ($room_name === "") {

            $message = "Facility / room name is required.";
            $message_type = "danger";

        } else {

            if (!in_array($status, $allowed_statuses, true)) {

                $status = "Available";

            }


            try {

                $sql = "
                    UPDATE operating_rooms
                    SET
                        room_name = :room_name,
                        room_description = :room_description,
                        status = :status
                    WHERE room_id = :room_id
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":room_name" => $room_name,
                    ":room_description" => $room_description,
                    ":status" => $status,
                    ":room_id" => $room_id
                ]);


                header("Location: facilities.php?success=updated");
                exit;

            } catch (PDOException $e) {

                $message = "Unable to update facility: " . $e->getMessage();
                $message_type = "danger";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DELETE FACILITY
    |--------------------------------------------------------------------------
    */

    elseif ($action === "delete") {

        $room_id = intval($_POST['room_id'] ?? 0);


        if ($room_id <= 0) {

            $message = "Invalid facility.";
            $message_type = "danger";

        } else {

            try {

                $sql = "
                    DELETE FROM operating_rooms
                    WHERE room_id = :room_id
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":room_id" => $room_id
                ]);


                header("Location: facilities.php?success=deleted");
                exit;

            } catch (PDOException $e) {

                $message = "Unable to delete facility: " . $e->getMessage();
                $message_type = "danger";

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

if (isset($_GET['success'])) {

    switch ($_GET['success']) {

        case "added":
            $message = "Facility successfully added.";
            $message_type = "success";
            break;

        case "updated":
            $message = "Facility successfully updated.";
            $message_type = "success";
            break;

        case "deleted":
            $message = "Facility successfully deleted.";
            $message_type = "success";
            break;

    }

}


/*
|--------------------------------------------------------------------------
| GET FACILITIES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        room_id,
        room_name,
        room_description,
        status,
        created_at
    FROM operating_rooms
    ORDER BY room_name ASC
";

$stmt = $pdo->query($sql);

$facilities = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| STATUS COUNTS
|--------------------------------------------------------------------------
*/

$total_facilities = count($facilities);

$available_count = 0;
$occupied_count = 0;
$maintenance_count = 0;
$inactive_count = 0;


foreach ($facilities as $facility) {

    switch ($facility['status']) {

        case "Available":
            $available_count++;
            break;

        case "Occupied":
            $occupied_count++;
            break;

        case "Maintenance":
            $maintenance_count++;
            break;

        case "Inactive":
            $inactive_count++;
            break;

    }

}


/*
|--------------------------------------------------------------------------
| PAGE SETUP
|--------------------------------------------------------------------------
*/

$page_title = "Facilities";

require_once "../includes/header.php";

require_once "../includes/sidebar.php";

?>

<!-- Facility Page CSS -->
<link
    rel="stylesheet"
    href="../assets/css/facilities.css?v=20260907"
>


<main class="main-content">

    <!-- =========================================================
         TOP BAR
         ========================================================= -->

    <div class="page-topbar">

        <div>

            <div class="page-kicker">
                SYSTEM MANAGEMENT
            </div>

            <h1 class="page-title">
                Facilities
            </h1>

            <p class="page-description">
                Manage operating rooms and facility availability.
            </p>

        </div>


        <div class="page-user">

            <div class="page-user-icon">
                <i class="bi bi-person-fill"></i>
            </div>

            <div>

                <div class="page-user-name">
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                </div>

                <div class="page-user-role">
                    <?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?>
                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         ALERT
         ========================================================= -->

    <?php if ($message !== ""): ?>

        <div class="alert alert-<?= htmlspecialchars($message_type) ?> facility-alert">

            <i class="bi bi-check-circle-fill me-2"></i>

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         SUMMARY CARDS
         ========================================================= -->

    <div class="facility-summary">


        <!-- TOTAL -->

        <div class="facility-summary-card summary-total">

            <div class="summary-icon">

                <i class="bi bi-building-fill"></i>

            </div>

            <div>

                <div class="summary-label">
                    Total Facilities
                </div>

                <div class="summary-number">
                    <?= $total_facilities ?>
                </div>

            </div>

        </div>


        <!-- AVAILABLE -->

        <div class="facility-summary-card summary-available">

            <div class="summary-icon">

                <i class="bi bi-check-circle-fill"></i>

            </div>

            <div>

                <div class="summary-label">
                    Available
                </div>

                <div class="summary-number">
                    <?= $available_count ?>
                </div>

            </div>

        </div>


        <!-- OCCUPIED -->

        <div class="facility-summary-card summary-occupied">

            <div class="summary-icon">

                <i class="bi bi-person-workspace"></i>

            </div>

            <div>

                <div class="summary-label">
                    Occupied
                </div>

                <div class="summary-number">
                    <?= $occupied_count ?>
                </div>

            </div>

        </div>


        <!-- MAINTENANCE -->

        <div class="facility-summary-card summary-maintenance">

            <div class="summary-icon">

                <i class="bi bi-tools"></i>

            </div>

            <div>

                <div class="summary-label">
                    Maintenance
                </div>

                <div class="summary-number">
                    <?= $maintenance_count ?>
                </div>

            </div>

        </div>


    </div>


    <!-- =========================================================
         FACILITY TABLE
         ========================================================= -->

    <div class="facility-panel">


        <!-- PANEL HEADER -->

        <div class="facility-panel-header">

            <div>

                <h2>
                    Operating Rooms
                </h2>

                <p>
                    Manage your hospital operating room facilities.
                </p>

            </div>


            <button
                type="button"
                class="btn btn-primary add-facility-btn"
                data-bs-toggle="modal"
                data-bs-target="#addFacilityModal"
            >

                <i class="bi bi-plus-lg me-2"></i>

                Add Facility

            </button>

        </div>


        <!-- SEARCH -->

        <div class="facility-toolbar">

            <div class="facility-search">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="facilitySearch"
                    class="form-control"
                    placeholder="Search facilities..."
                >

            </div>

        </div>


        <!-- TABLE -->

        <div class="table-responsive">

            <table class="table facility-table">

                <thead>

                    <tr>

                        <th>
                            Facility
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Created
                        </th>

                        <th class="text-end">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody id="facilityTableBody">

                    <?php if (count($facilities) > 0): ?>

                        <?php foreach ($facilities as $facility): ?>

                            <?php

                            $status = $facility['status'];

                            switch ($status) {

                                case "Available":

                                    $status_class = "status-available";
                                    $status_icon = "bi-check-circle-fill";

                                    break;

                                case "Occupied":

                                    $status_class = "status-occupied";
                                    $status_icon = "bi-person-fill";

                                    break;

                                case "Maintenance":

                                    $status_class = "status-maintenance";
                                    $status_icon = "bi-tools";

                                    break;

                                default:

                                    $status_class = "status-inactive";
                                    $status_icon = "bi-x-circle-fill";

                                    break;

                            }

                            ?>

                            <tr class="facility-row">


                                <!-- FACILITY -->

                                <td>

                                    <div class="facility-name">

                                        <div class="facility-room-icon">

                                            <i class="bi bi-building"></i>

                                        </div>

                                        <div>

                                            <div class="facility-room-name">

                                                <?= htmlspecialchars($facility['room_name']) ?>

                                            </div>

                                            <div class="facility-room-id">

                                                Room ID:
                                                <?= htmlspecialchars($facility['room_id']) ?>

                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <!-- DESCRIPTION -->

                                <td>

                                    <div class="facility-description">

                                        <?=
                                            htmlspecialchars(
                                                $facility['room_description']
                                                ?: 'No description provided.'
                                            )
                                        ?>

                                    </div>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="facility-status <?= $status_class ?>"
                                    >

                                        <i
                                            class="bi <?= $status_icon ?>"
                                        ></i>

                                        <?= htmlspecialchars($status) ?>

                                    </span>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <span class="facility-date">

                                        <?=
                                            date(
                                                "M d, Y",
                                                strtotime($facility['created_at'])
                                            )
                                        ?>

                                    </span>

                                </td>


                                <!-- ACTIONS -->

                                <td class="text-end">

                                    <div class="facility-actions">

                                        <button
                                            type="button"
                                            class="facility-action-btn edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editFacilityModal"

                                            data-id="<?= htmlspecialchars($facility['room_id']) ?>"

                                            data-name="<?= htmlspecialchars($facility['room_name']) ?>"

                                            data-description="<?= htmlspecialchars($facility['room_description'] ?? '') ?>"

                                            data-status="<?= htmlspecialchars($facility['status']) ?>"
                                        >

                                            <i class="bi bi-pencil-fill"></i>

                                        </button>


                                        <button
                                            type="button"
                                            class="facility-action-btn delete-btn"

                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteFacilityModal"

                                            data-id="<?= htmlspecialchars($facility['room_id']) ?>"

                                            data-name="<?= htmlspecialchars($facility['room_name']) ?>"
                                        >

                                            <i class="bi bi-trash-fill"></i>

                                        </button>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="5"
                                class="text-center facility-empty"
                            >

                                <div class="empty-icon">

                                    <i class="bi bi-building"></i>

                                </div>

                                <div class="empty-title">
                                    No facilities found
                                </div>

                                <div class="empty-text">
                                    Click "Add Facility" to create your first operating room.
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
     ADD FACILITY MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="addFacilityModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Add Facility
                    </h5>

                    <p class="modal-subtitle">
                        Create a new operating room facility.
                    </p>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form method="POST">

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="action"
                        value="add"
                    >


                    <!-- ROOM NAME -->

                    <div class="mb-3">

                        <label class="form-label">
                            Facility / Room Name
                        </label>

                        <input
                            type="text"
                            name="room_name"
                            class="form-control"
                            placeholder="Example: OR 01"
                            required
                        >

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="room_description"
                            class="form-control"
                            rows="3"
                            placeholder="Example: General Surgery Operating Room"
                        ></textarea>

                    </div>


                    <!-- STATUS -->

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="Available">
                                Available
                            </option>

                            <option value="Occupied">
                                Occupied
                            </option>

                            <option value="Maintenance">
                                Maintenance
                            </option>

                            <option value="Inactive">
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-check-lg me-2"></i>

                        Save Facility

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     EDIT FACILITY MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="editFacilityModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Edit Facility
                    </h5>

                    <p class="modal-subtitle">
                        Update facility information.
                    </p>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form method="POST">

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="action"
                        value="edit"
                    >

                    <input
                        type="hidden"
                        name="room_id"
                        id="editRoomId"
                    >


                    <!-- ROOM NAME -->

                    <div class="mb-3">

                        <label class="form-label">
                            Facility / Room Name
                        </label>

                        <input
                            type="text"
                            name="room_name"
                            id="editRoomName"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="room_description"
                            id="editRoomDescription"
                            class="form-control"
                            rows="3"
                        ></textarea>

                    </div>


                    <!-- STATUS -->

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            id="editRoomStatus"
                            class="form-select"
                        >

                            <option value="Available">
                                Available
                            </option>

                            <option value="Occupied">
                                Occupied
                            </option>

                            <option value="Maintenance">
                                Maintenance
                            </option>

                            <option value="Inactive">
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-check-lg me-2"></i>

                        Save Changes

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     DELETE FACILITY MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="deleteFacilityModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-sm">

        <div class="modal-content delete-modal">


            <div class="modal-body text-center">

                <div class="delete-icon">

                    <i class="bi bi-trash3-fill"></i>

                </div>


                <h5 class="delete-title">
                    Delete Facility?
                </h5>


                <p class="delete-message">

                    Are you sure you want to delete

                    <strong id="deleteFacilityName"></strong>?

                    <br>

                    This action cannot be undone.

                </p>


                <form method="POST">

                    <input
                        type="hidden"
                        name="action"
                        value="delete"
                    >

                    <input
                        type="hidden"
                        name="room_id"
                        id="deleteRoomId"
                    >


                    <div class="delete-actions">

                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>


                        <button
                            type="submit"
                            class="btn btn-danger"
                        >
                            Delete Facility
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {


    /*
    |--------------------------------------------------------------------------
    | EDIT FACILITY
    |--------------------------------------------------------------------------
    */

    const editButtons =
        document.querySelectorAll(".edit-btn");


    editButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("editRoomId").value =
                this.dataset.id;

            document.getElementById("editRoomName").value =
                this.dataset.name;

            document.getElementById("editRoomDescription").value =
                this.dataset.description;

            document.getElementById("editRoomStatus").value =
                this.dataset.status;

        });

    });


    /*
    |--------------------------------------------------------------------------
    | DELETE FACILITY
    |--------------------------------------------------------------------------
    */

    const deleteButtons =
        document.querySelectorAll(".delete-btn");


    deleteButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("deleteRoomId").value =
                this.dataset.id;

            document.getElementById("deleteFacilityName").textContent =
                this.dataset.name;

        });

    });


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    const searchInput =
        document.getElementById("facilitySearch");


    if (searchInput) {

        searchInput.addEventListener("keyup", function () {

            const searchValue =
                this.value.toLowerCase();


            const rows =
                document.querySelectorAll(".facility-row");


            rows.forEach(function (row) {

                const rowText =
                    row.textContent.toLowerCase();


                if (rowText.includes(searchValue)) {

                    row.style.display = "";

                } else {

                    row.style.display = "none";

                }

            });

        });

    }

});

</script>


<?php

require_once "../includes/footer.php";

?>