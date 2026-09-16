```php
<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$page_title = "Operating Rooms";

$allowed_statuses = [
    "Available",
    "Occupied",
    "Maintenance",
    "Inactive"
];

$message = "";
$message_type = "";

/* =========================================================
   ADD ROOM
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_room"])) {

    $room_name = trim($_POST["room_name"] ?? "");
    $room_description = trim($_POST["room_description"] ?? "");
    $status = trim($_POST["status"] ?? "Available");

    if ($room_name === "") {

        $message = "Room name is required.";
        $message_type = "danger";

    } elseif (!in_array($status, $allowed_statuses, true)) {

        $message = "Invalid room status.";
        $message_type = "danger";

    } else {

        $stmt = $pdo->prepare("
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
        ");

        $stmt->execute([
            ":room_name" => $room_name,
            ":room_description" => $room_description,
            ":status" => $status
        ]);

        $message = "Operating room added successfully.";
        $message_type = "success";
    }
}


/* =========================================================
   UPDATE ROOM
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_room"])) {

    $room_id = (int)($_POST["room_id"] ?? 0);
    $room_name = trim($_POST["room_name"] ?? "");
    $room_description = trim($_POST["room_description"] ?? "");
    $status = trim($_POST["status"] ?? "Available");

    if ($room_id <= 0) {

        $message = "Invalid room.";
        $message_type = "danger";

    } elseif ($room_name === "") {

        $message = "Room name is required.";
        $message_type = "danger";

    } elseif (!in_array($status, $allowed_statuses, true)) {

        $message = "Invalid room status.";
        $message_type = "danger";

    } else {

        $stmt = $pdo->prepare("
            UPDATE operating_rooms
            SET
                room_name = :room_name,
                room_description = :room_description,
                status = :status
            WHERE room_id = :room_id
        ");

        $stmt->execute([
            ":room_name" => $room_name,
            ":room_description" => $room_description,
            ":status" => $status,
            ":room_id" => $room_id
        ]);

        $message = "Operating room updated successfully.";
        $message_type = "success";
    }
}


/* =========================================================
   DELETE ROOM
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_room"])) {

    $room_id = (int)($_POST["room_id"] ?? 0);

    if ($room_id > 0) {

        $stmt = $pdo->prepare("
            DELETE FROM operating_rooms
            WHERE room_id = :room_id
        ");

        $stmt->execute([
            ":room_id" => $room_id
        ]);

        $message = "Operating room deleted successfully.";
        $message_type = "success";
    }
}


/* =========================================================
   GET ROOMS
========================================================= */

$stmt = $pdo->query("
    SELECT
        room_id,
        room_name,
        room_description,
        status,
        created_at
    FROM operating_rooms
    ORDER BY room_name ASC
");

$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   COUNTS
========================================================= */

$total_rooms = count($rooms);

$available_rooms = 0;
$occupied_rooms = 0;
$maintenance_rooms = 0;
$inactive_rooms = 0;

foreach ($rooms as $room) {

    switch (strtolower(trim($room["status"] ?? ""))) {

        case "available":
            $available_rooms++;
            break;

        case "occupied":
            $occupied_rooms++;
            break;

        case "maintenance":
            $maintenance_rooms++;
            break;

        case "inactive":
            $inactive_rooms++;
            break;
    }
}


/* =========================================================
   HEADER + SIDEBAR
========================================================= */

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<link
    rel="stylesheet"
    href="../assets/css/rooms.css?v=20260915"
>


<main class="rooms-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="rooms-page-header">

        <div>

            <div class="rooms-kicker">
                OPERATING ROOMS
            </div>

            <h1>
                Operating Rooms
            </h1>

            <p>
                Manage operating rooms, availability, and room status.
            </p>

        </div>


        <button
            type="button"
            class="rooms-add-btn"
            data-bs-toggle="modal"
            data-bs-target="#addRoomModal"
        >
            <i class="bi bi-plus-lg"></i>
            Add Operating Room
        </button>

    </div>


    <!-- =====================================================
         ALERT
    ====================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="alert alert-<?= htmlspecialchars($message_type) ?> rooms-alert"
            role="alert"
        >
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- =====================================================
         SUMMARY CARDS
    ====================================================== -->

    <div class="rooms-summary">


        <!-- TOTAL -->

        <div class="room-summary-card total">

            <div class="room-summary-icon">

                <i class="bi bi-building-fill"></i>

            </div>

            <div class="room-summary-info">

                <span>
                    Total Rooms
                </span>

                <strong>
                    <?= $total_rooms ?>
                </strong>

            </div>

        </div>


        <!-- AVAILABLE -->

        <div class="room-summary-card available">

            <div class="room-summary-icon">

                <i class="bi bi-check-circle-fill"></i>

            </div>

            <div class="room-summary-info">

                <span>
                    Available
                </span>

                <strong>
                    <?= $available_rooms ?>
                </strong>

            </div>

        </div>


        <!-- OCCUPIED -->

        <div class="room-summary-card occupied">

            <div class="room-summary-icon">

                <i class="bi bi-person-workspace"></i>

            </div>

            <div class="room-summary-info">

                <span>
                    Occupied
                </span>

                <strong>
                    <?= $occupied_rooms ?>
                </strong>

            </div>

        </div>


        <!-- MAINTENANCE -->

        <div class="room-summary-card maintenance">

            <div class="room-summary-icon">

                <i class="bi bi-tools"></i>

            </div>

            <div class="room-summary-info">

                <span>
                    Maintenance
                </span>

                <strong>
                    <?= $maintenance_rooms ?>
                </strong>

            </div>

        </div>


        <!-- INACTIVE -->

        <div class="room-summary-card inactive">

            <div class="room-summary-icon">

                <i class="bi bi-x-circle-fill"></i>

            </div>

            <div class="room-summary-info">

                <span>
                    Inactive
                </span>

                <strong>
                    <?= $inactive_rooms ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- =====================================================
         ROOM MANAGEMENT PANEL
    ====================================================== -->

    <section class="rooms-panel">


        <!-- PANEL HEADER -->

        <div class="rooms-panel-header">

            <div>

                <div class="rooms-panel-kicker">
                    ROOM MANAGEMENT
                </div>

                <h2>
                    Operating Room List
                </h2>

                <p>
                    View and manage all registered operating rooms.
                </p>

            </div>

            <div class="rooms-count">
                <?= $total_rooms ?> room<?= $total_rooms === 1 ? "" : "s" ?>
            </div>

        </div>


        <!-- TOOLBAR -->

        <div class="rooms-toolbar">

            <div class="rooms-search">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="roomSearch"
                    placeholder="Search operating rooms..."
                    autocomplete="off"
                >

            </div>

        </div>


        <!-- TABLE -->

        <div class="rooms-table-wrapper">

            <table class="rooms-table">

                <thead>

                    <tr>

                        <th>
                            Room
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Created Date
                        </th>

                        <th class="text-end">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody id="roomsTableBody">


                <?php if (empty($rooms)): ?>

                    <tr>

                        <td
                            colspan="5"
                            class="rooms-empty"
                        >

                            <div class="rooms-empty-icon">

                                <i class="bi bi-building"></i>

                            </div>

                            <strong>
                                No operating rooms found
                            </strong>

                            <span>
                                Add an operating room to get started.
                            </span>

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($rooms as $room): ?>

                        <?php

                        $room_id = (int)$room["room_id"];

                        $room_name = $room["room_name"] ?? "";
                        $room_description = $room["room_description"] ?? "";
                        $room_status = trim($room["status"] ?? "Inactive");

                        $status_key = strtolower($room_status);

                        $room_icon = "bi-building";
                        $status_icon = "bi-x-circle-fill";

                        if ($status_key === "available") {

                            $room_icon = "bi-building";
                            $status_icon = "bi-check-circle-fill";

                        } elseif ($status_key === "occupied") {

                            $room_icon = "bi-building";
                            $status_icon = "bi-person-fill";

                        } elseif ($status_key === "maintenance") {

                            $room_icon = "bi-tools";
                            $status_icon = "bi-tools";

                        } elseif ($status_key === "inactive") {

                            $room_icon = "bi-building";
                            $status_icon = "bi-x-circle-fill";
                        }

                        ?>

                        <tr
                            class="room-row"
                            data-room-name="<?= htmlspecialchars(strtolower($room_name)) ?>"
                            data-room-description="<?= htmlspecialchars(strtolower($room_description)) ?>"
                        >


                            <!-- ROOM -->

                            <td>

                                <div class="room-cell">

                                    <div class="room-icon <?= htmlspecialchars($status_key) ?>">

                                        <i class="bi <?= htmlspecialchars($room_icon) ?>"></i>

                                    </div>

                                    <div class="room-info">

                                        <div class="room-name">
                                            <?= htmlspecialchars($room_name) ?>
                                        </div>

                                        <div class="room-id">
                                            Room #<?= $room_id ?>
                                        </div>

                                    </div>

                                </div>

                            </td>


                            <!-- DESCRIPTION -->

                            <td>

                                <div class="room-description">

                                    <?= htmlspecialchars(
                                        $room_description !== ""
                                            ? $room_description
                                            : "Operating room"
                                    ) ?>

                                </div>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="room-status <?= htmlspecialchars($status_key) ?>"
                                >

                                    <i class="bi <?= htmlspecialchars($status_icon) ?>"></i>

                                    <?= htmlspecialchars($room_status) ?>

                                </span>

                            </td>


                            <!-- CREATED -->

                            <td>

                                <div class="room-created">

                                    <?= !empty($room["created_at"])
                                        ? date("M d, Y", strtotime($room["created_at"]))
                                        : "—"
                                    ?>

                                </div>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <div class="room-actions">


                                    <!-- EDIT -->

                                    <button
                                        type="button"
                                        class="room-action-btn room-action-edit"
                                        title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRoomModal"
                                        data-id="<?= $room_id ?>"
                                        data-name="<?= htmlspecialchars($room_name, ENT_QUOTES) ?>"
                                        data-description="<?= htmlspecialchars($room_description, ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($room_status, ENT_QUOTES) ?>"
                                    >

                                        <i class="bi bi-pencil-fill"></i>

                                    </button>


                                    <!-- DELETE -->

                                    <button
                                        type="button"
                                        class="room-action-btn room-action-delete"
                                        title="Delete"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteRoomModal"
                                        data-id="<?= $room_id ?>"
                                        data-name="<?= htmlspecialchars($room_name, ENT_QUOTES) ?>"
                                    >

                                        <i class="bi bi-trash3"></i>

                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </section>


</main>


<!-- =========================================================
     ADD ROOM MODAL
========================================================= -->

<div
    class="modal fade"
    id="addRoomModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <div class="modal-kicker">
                        OPERATING ROOM
                    </div>

                    <h5 class="modal-title">
                        Add Operating Room
                    </h5>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <form method="POST">

                <div class="modal-body">


                    <div class="mb-3">

                        <label
                            for="add_room_name"
                            class="form-label"
                        >
                            Room Name
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="add_room_name"
                            name="room_name"
                            placeholder="Example: Operating Room 1"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label
                            for="add_room_description"
                            class="form-label"
                        >
                            Description
                        </label>

                        <textarea
                            class="form-control"
                            id="add_room_description"
                            name="room_description"
                            rows="3"
                            placeholder="Enter room description"
                        ></textarea>

                    </div>


                    <div class="mb-3">

                        <label
                            for="add_status"
                            class="form-label"
                        >
                            Status
                        </label>

                        <select
                            class="form-select"
                            id="add_status"
                            name="status"
                        >

                            <?php foreach ($allowed_statuses as $status): ?>

                                <option
                                    value="<?= htmlspecialchars($status) ?>"
                                    <?= $status === "Available" ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($status) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn rooms-btn-cancel"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        name="add_room"
                        class="btn rooms-btn-save"
                    >
                        <i class="bi bi-check-lg"></i>
                        Save Room
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     EDIT ROOM MODAL
========================================================= -->

<div
    class="modal fade"
    id="editRoomModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <div class="modal-kicker">
                        OPERATING ROOM
                    </div>

                    <h5 class="modal-title">
                        Edit Operating Room
                    </h5>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <form method="POST">

                <input
                    type="hidden"
                    name="room_id"
                    id="edit_room_id"
                >


                <div class="modal-body">


                    <div class="mb-3">

                        <label
                            for="edit_room_name"
                            class="form-label"
                        >
                            Room Name
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="edit_room_name"
                            name="room_name"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label
                            for="edit_room_description"
                            class="form-label"
                        >
                            Description
                        </label>

                        <textarea
                            class="form-control"
                            id="edit_room_description"
                            name="room_description"
                            rows="3"
                        ></textarea>

                    </div>


                    <div class="mb-3">

                        <label
                            for="edit_status"
                            class="form-label"
                        >
                            Status
                        </label>

                        <select
                            class="form-select"
                            id="edit_status"
                            name="status"
                        >

                            <?php foreach ($allowed_statuses as $status): ?>

                                <option value="<?= htmlspecialchars($status) ?>">
                                    <?= htmlspecialchars($status) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn rooms-btn-cancel"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        name="update_room"
                        class="btn rooms-btn-save"
                    >
                        <i class="bi bi-check-lg"></i>
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     DELETE ROOM MODAL
========================================================= -->

<div
    class="modal fade"
    id="deleteRoomModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <div class="modal-kicker">
                        CONFIRM ACTION
                    </div>

                    <h5 class="modal-title">
                        Delete Operating Room
                    </h5>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <form method="POST">

                <input
                    type="hidden"
                    name="room_id"
                    id="delete_room_id"
                >


                <div class="modal-body delete-modal-body">

                    <div class="delete-room-icon">

                        <i class="bi bi-trash3"></i>

                    </div>

                    <p>
                        Are you sure you want to delete
                        <strong id="delete_room_name"></strong>?
                    </p>

                    <span>
                        This action cannot be undone.
                    </span>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn rooms-btn-cancel"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        name="delete_room"
                        class="btn rooms-btn-delete"
                    >
                        <i class="bi bi-trash3"></i>
                        Delete Room
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       SEARCH
    ====================================================== */

    const searchInput = document.getElementById("roomSearch");

    const roomRows = document.querySelectorAll(".room-row");


    if (searchInput) {

        searchInput.addEventListener("input", function () {

            const searchValue = this.value
                .toLowerCase()
                .trim();


            roomRows.forEach(function (row) {

                const roomName =
                    row.dataset.roomName || "";

                const roomDescription =
                    row.dataset.roomDescription || "";

                const rowText =
                    row.textContent.toLowerCase();


                const matches =
                    roomName.includes(searchValue) ||
                    roomDescription.includes(searchValue) ||
                    rowText.includes(searchValue);


                row.style.display =
                    matches ? "" : "none";

            });

        });

    }


    /* =====================================================
       EDIT ROOM
    ====================================================== */

    const editButtons =
        document.querySelectorAll(".room-action-edit");


    editButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("edit_room_id").value =
                this.dataset.id || "";

            document.getElementById("edit_room_name").value =
                this.dataset.name || "";

            document.getElementById("edit_room_description").value =
                this.dataset.description || "";

            document.getElementById("edit_status").value =
                this.dataset.status || "Available";

        });

    });


    /* =====================================================
       DELETE ROOM
    ====================================================== */

    const deleteButtons =
        document.querySelectorAll(".room-action-delete");


    deleteButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("delete_room_id").value =
                this.dataset.id || "";

            document.getElementById("delete_room_name").textContent =
                this.dataset.name || "this operating room";

        });

    });

});

</script>


<?php

require_once "../includes/footer.php";

?>
```
