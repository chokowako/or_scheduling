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
| CHECK ADMINISTRATOR ACCESS
|--------------------------------------------------------------------------
*/

if (($_SESSION['role'] ?? '') !== 'Administrator') {

    header("Location: ../dashboard.php");
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
    | ADD USER
    |--------------------------------------------------------------------------
    */

    if ($action === "add") {

        $username = trim($_POST['username'] ?? "");
        $password = $_POST['password'] ?? "";
        $full_name = trim($_POST['full_name'] ?? "");
        $role = $_POST['role'] ?? "Staff";


        /*
        |----------------------------------------------------------------------
        | VALIDATE
        |----------------------------------------------------------------------
        */

        if ($username === "") {

            $message = "Username is required.";
            $message_type = "danger";

        } elseif ($password === "") {

            $message = "Password is required.";
            $message_type = "danger";

        } elseif ($full_name === "") {

            $message = "Full name is required.";
            $message_type = "danger";

        } else {

            $allowed_roles = [
                "Administrator",
                "Scheduler",
                "Staff"
            ];


            if (!in_array($role, $allowed_roles, true)) {

                $role = "Staff";

            }


            /*
            |------------------------------------------------------------------
            | CHECK DUPLICATE USERNAME
            |------------------------------------------------------------------
            */

            $checkStmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE username = :username
                LIMIT 1
            ");

            $checkStmt->execute([
                ":username" => $username
            ]);


            if ($checkStmt->fetch()) {

                $message = "Username already exists.";
                $message_type = "danger";

            } else {

                try {

                    /*
                    |----------------------------------------------------------
                    | HASH PASSWORD
                    |----------------------------------------------------------
                    */

                    $hashed_password = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                    /*
                    |----------------------------------------------------------
                    | INSERT USER
                    |----------------------------------------------------------
                    */

                    $sql = "
                        INSERT INTO users
                        (
                            username,
                            password,
                            full_name,
                            role
                        )
                        VALUES
                        (
                            :username,
                            :password,
                            :full_name,
                            :role
                        )
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        ":username" => $username,
                        ":password" => $hashed_password,
                        ":full_name" => $full_name,
                        ":role" => $role
                    ]);


                    header("Location: users.php?success=added");
                    exit;

                } catch (PDOException $e) {

                    $message = "Unable to add user: " . $e->getMessage();
                    $message_type = "danger";

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | EDIT USER
    |--------------------------------------------------------------------------
    */

    elseif ($action === "edit") {

        $user_id = intval($_POST['user_id'] ?? 0);

        $username = trim($_POST['username'] ?? "");

        $password = $_POST['password'] ?? "";

        $full_name = trim($_POST['full_name'] ?? "");

        $role = $_POST['role'] ?? "Staff";


        $allowed_roles = [
            "Administrator",
            "Scheduler",
            "Staff"
        ];


        /*
        |----------------------------------------------------------------------
        | VALIDATE
        |----------------------------------------------------------------------
        */

        if ($user_id <= 0) {

            $message = "Invalid user.";
            $message_type = "danger";

        } elseif ($username === "") {

            $message = "Username is required.";
            $message_type = "danger";

        } elseif ($full_name === "") {

            $message = "Full name is required.";
            $message_type = "danger";

        } else {

            if (!in_array($role, $allowed_roles, true)) {

                $role = "Staff";

            }


            /*
            |------------------------------------------------------------------
            | CHECK DUPLICATE USERNAME
            |------------------------------------------------------------------
            */

            $checkStmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE username = :username
                AND user_id <> :user_id
                LIMIT 1
            ");

            $checkStmt->execute([
                ":username" => $username,
                ":user_id" => $user_id
            ]);


            if ($checkStmt->fetch()) {

                $message = "Username already exists.";
                $message_type = "danger";

            } else {

                try {

                    /*
                    |----------------------------------------------------------
                    | UPDATE WITH NEW PASSWORD
                    |----------------------------------------------------------
                    */

                    if ($password !== "") {

                        $hashed_password = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );


                        $sql = "
                            UPDATE users
                            SET
                                username = :username,
                                password = :password,
                                full_name = :full_name,
                                role = :role
                            WHERE user_id = :user_id
                        ";


                        $stmt = $pdo->prepare($sql);


                        $stmt->execute([
                            ":username" => $username,
                            ":password" => $hashed_password,
                            ":full_name" => $full_name,
                            ":role" => $role,
                            ":user_id" => $user_id
                        ]);

                    }

                    /*
                    |----------------------------------------------------------
                    | UPDATE WITHOUT CHANGING PASSWORD
                    |----------------------------------------------------------
                    */

                    else {

                        $sql = "
                            UPDATE users
                            SET
                                username = :username,
                                full_name = :full_name,
                                role = :role
                            WHERE user_id = :user_id
                        ";


                        $stmt = $pdo->prepare($sql);


                        $stmt->execute([
                            ":username" => $username,
                            ":full_name" => $full_name,
                            ":role" => $role,
                            ":user_id" => $user_id
                        ]);

                    }


                    /*
                    |----------------------------------------------------------
                    | UPDATE SESSION IF CURRENT USER WAS EDITED
                    |----------------------------------------------------------
                    */

                    if ($user_id === (int)$_SESSION['user_id']) {

                        $_SESSION['full_name'] = $full_name;
                        $_SESSION['role'] = $role;

                    }


                    header("Location: users.php?success=updated");
                    exit;

                } catch (PDOException $e) {

                    $message = "Unable to update user: " . $e->getMessage();
                    $message_type = "danger";

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    elseif ($action === "delete") {

        $user_id = intval($_POST['user_id'] ?? 0);


        if ($user_id <= 0) {

            $message = "Invalid user.";
            $message_type = "danger";

        } elseif ($user_id === (int)$_SESSION['user_id']) {

            $message = "You cannot delete the account you are currently using.";
            $message_type = "danger";

        } else {

            try {

                $sql = "
                    DELETE FROM users
                    WHERE user_id = :user_id
                ";


                $stmt = $pdo->prepare($sql);


                $stmt->execute([
                    ":user_id" => $user_id
                ]);


                header("Location: users.php?success=deleted");
                exit;

            } catch (PDOException $e) {

                $message = "Unable to delete user: " . $e->getMessage();
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

            $message = "User successfully added.";
            $message_type = "success";

            break;


        case "updated":

            $message = "User successfully updated.";
            $message_type = "success";

            break;


        case "deleted":

            $message = "User successfully deleted.";
            $message_type = "success";

            break;

    }

}


/*
|--------------------------------------------------------------------------
| GET USERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        user_id,
        username,
        full_name,
        role,
        created_at
    FROM users
    ORDER BY full_name ASC
";


$stmt = $pdo->query($sql);

$users = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| USER COUNTS
|--------------------------------------------------------------------------
*/

$total_users = count($users);

$administrator_count = 0;
$scheduler_count = 0;
$staff_count = 0;


foreach ($users as $user) {

    switch ($user['role']) {

        case "Administrator":

            $administrator_count++;

            break;


        case "Scheduler":

            $scheduler_count++;

            break;


        case "Staff":

            $staff_count++;

            break;

    }

}


/*
|--------------------------------------------------------------------------
| PAGE SETUP
|--------------------------------------------------------------------------
*/

$page_title = "User Management";

require_once "../includes/header.php";

require_once "../includes/sidebar.php";

?>

<!-- User Management Page CSS -->

<link
    rel="stylesheet"
    href="../assets/css/users.css?v=20260917"
>


<main class="main-content">


    <!-- =========================================================
         TOP BAR
         ========================================================= -->

    <div class="page-topbar">

        <div>

            <div class="page-kicker">
                ACCESS CONTROL
            </div>

            <h1 class="page-title">
                User Management
            </h1>

            <p class="page-description">
                Manage system users, roles, and access to administrative features.
            </p>

        </div>


        <div class="page-user">

            <div class="page-user-icon">

                <i class="bi bi-person-fill"></i>

            </div>

            <div>

                <div class="page-user-name">

                    <?= htmlspecialchars(
                        $_SESSION['full_name'] ?? 'User'
                    ) ?>

                </div>

                <div class="page-user-role">

                    <?= htmlspecialchars(
                        $_SESSION['role'] ?? 'Staff'
                    ) ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         ALERT
         ========================================================= -->

    <?php if ($message !== ""): ?>

        <div
            class="alert alert-<?= htmlspecialchars($message_type) ?> user-alert"
        >

            <?php if ($message_type === "success"): ?>

                <i class="bi bi-check-circle-fill me-2"></i>

            <?php else: ?>

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

            <?php endif; ?>

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         SUMMARY CARDS
         ========================================================= -->

    <div class="user-summary">


        <!-- TOTAL USERS -->

        <div class="user-summary-card summary-total">

            <div class="summary-icon">

                <i class="bi bi-people-fill"></i>

            </div>

            <div>

                <div class="summary-label">
                    Total Users
                </div>

                <div class="summary-number">
                    <?= $total_users ?>
                </div>

            </div>

        </div>


        <!-- ADMINISTRATORS -->

        <div class="user-summary-card summary-administrator">

            <div class="summary-icon">

                <i class="bi bi-shield-lock-fill"></i>

            </div>

            <div>

                <div class="summary-label">
                    Administrators
                </div>

                <div class="summary-number">
                    <?= $administrator_count ?>
                </div>

            </div>

        </div>


        <!-- SCHEDULERS -->

        <div class="user-summary-card summary-scheduler">

            <div class="summary-icon">

                <i class="bi bi-calendar-check-fill"></i>

            </div>

            <div>

                <div class="summary-label">
                    Schedulers
                </div>

                <div class="summary-number">
                    <?= $scheduler_count ?>
                </div>

            </div>

        </div>


        <!-- STAFF -->

        <div class="user-summary-card summary-staff">

            <div class="summary-icon">

                <i class="bi bi-person-badge-fill"></i>

            </div>

            <div>

                <div class="summary-label">
                    Staff
                </div>

                <div class="summary-number">
                    <?= $staff_count ?>
                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         USER MANAGEMENT PANEL
         ========================================================= -->

    <div class="user-panel">


        <!-- PANEL HEADER -->

        <div class="user-panel-header">

            <div>

                <h2>
                    System Users
                </h2>

                <p>
                    Manage user accounts and their system access roles.
                </p>

            </div>


            <button
                type="button"
                class="btn btn-primary add-user-btn"

                data-bs-toggle="modal"
                data-bs-target="#addUserModal"
            >

                <i class="bi bi-plus-lg me-2"></i>

                Add User

            </button>

        </div>


        <!-- SEARCH -->

        <div class="user-toolbar">

            <div class="user-search">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="userSearch"
                    class="form-control"
                    placeholder="Search users..."
                >

            </div>

        </div>


        <!-- TABLE -->

        <div class="table-responsive">

            <table class="table user-table">

                <thead>

                    <tr>

                        <th>
                            User
                        </th>

                        <th>
                            Username
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Created
                        </th>

                        <th class="text-end">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody id="userTableBody">

                    <?php if (count($users) > 0): ?>

                        <?php foreach ($users as $user): ?>

                            <?php

                            $role = $user['role'];


                            switch ($role) {

                                case "Administrator":

                                    $role_class = "role-administrator";
                                    $role_icon = "bi-shield-lock-fill";

                                    break;


                                case "Scheduler":

                                    $role_class = "role-scheduler";
                                    $role_icon = "bi-calendar-check-fill";

                                    break;


                                default:

                                    $role_class = "role-staff";
                                    $role_icon = "bi-person-fill";

                                    break;

                            }

                            ?>


                            <tr class="user-row">


                                <!-- USER -->

                                <td>

                                    <div class="user-name-cell">

                                        <div class="user-avatar">

                                            <i class="bi bi-person-fill"></i>

                                        </div>


                                        <div>

                                            <div class="user-full-name">

                                                <?= htmlspecialchars(
                                                    $user['full_name']
                                                ) ?>

                                            </div>


                                            <div class="user-id">

                                                User ID:
                                                <?= htmlspecialchars(
                                                    $user['user_id']
                                                ) ?>

                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <!-- USERNAME -->

                                <td>

                                    <span class="user-username">

                                        @<?= htmlspecialchars(
                                            $user['username']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ROLE -->

                                <td>

                                    <span
                                        class="user-role <?= $role_class ?>"
                                    >

                                        <i
                                            class="bi <?= $role_icon ?>"
                                        ></i>

                                        <?= htmlspecialchars($role) ?>

                                    </span>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <span class="user-date">

                                        <?= date(
                                            "M d, Y",
                                            strtotime($user['created_at'])
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ACTIONS -->

                                <td class="text-end">

                                    <div class="user-actions">


                                        <!-- EDIT -->

                                        <button
                                            type="button"
                                            class="user-action-btn edit-user-btn"

                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal"

                                            data-id="<?= htmlspecialchars(
                                                $user['user_id']
                                            ) ?>"

                                            data-username="<?= htmlspecialchars(
                                                $user['username'],
                                                ENT_QUOTES
                                            ) ?>"

                                            data-full-name="<?= htmlspecialchars(
                                                $user['full_name'],
                                                ENT_QUOTES
                                            ) ?>"

                                            data-role="<?= htmlspecialchars(
                                                $user['role'],
                                                ENT_QUOTES
                                            ) ?>"
                                        >

                                            <i class="bi bi-pencil-fill"></i>

                                        </button>


                                        <!-- DELETE -->

                                        <button
                                            type="button"
                                            class="user-action-btn delete-user-btn"

                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteUserModal"

                                            data-id="<?= htmlspecialchars(
                                                $user['user_id']
                                            ) ?>"

                                            data-name="<?= htmlspecialchars(
                                                $user['full_name'],
                                                ENT_QUOTES
                                            ) ?>"
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
                                class="text-center user-empty"
                            >

                                <div class="empty-icon">

                                    <i class="bi bi-people"></i>

                                </div>

                                <div class="empty-title">
                                    No users found
                                </div>

                                <div class="empty-text">
                                    Click "Add User" to create your first system user.
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
     ADD USER MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="addUserModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Add User
                    </h5>

                    <p class="modal-subtitle">
                        Create a new system user account.
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


                    <!-- FULL NAME -->

                    <div class="mb-3">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            placeholder="Example: Juan Dela Cruz"
                            required
                        >

                    </div>


                    <!-- USERNAME -->

                    <div class="mb-3">

                        <label class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="Enter username"
                            required
                        >

                    </div>


                    <!-- PASSWORD -->

                    <div class="mb-3">

                        <label class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter password"
                            required
                        >

                    </div>


                    <!-- ROLE -->

                    <div class="mb-3">

                        <label class="form-label">
                            Role
                        </label>

                        <select
                            name="role"
                            class="form-select"
                            required
                        >

                            <option value="Staff">
                                Staff
                            </option>

                            <option value="Scheduler">
                                Scheduler
                            </option>

                            <option value="Administrator">
                                Administrator
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

                        Save User

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     EDIT USER MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="editUserModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Edit User
                    </h5>

                    <p class="modal-subtitle">
                        Update user account information.
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
                        name="user_id"
                        id="editUserId"
                    >


                    <!-- FULL NAME -->

                    <div class="mb-3">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            id="editUserFullName"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- USERNAME -->

                    <div class="mb-3">

                        <label class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            id="editUserUsername"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- PASSWORD -->

                    <div class="mb-3">

                        <label class="form-label">
                            New Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            id="editUserPassword"
                            class="form-control"
                            placeholder="Leave blank to keep current password"
                        >

                        <div class="form-text">
                            Leave blank if you do not want to change the password.
                        </div>

                    </div>


                    <!-- ROLE -->

                    <div class="mb-3">

                        <label class="form-label">
                            Role
                        </label>

                        <select
                            name="role"
                            id="editUserRole"
                            class="form-select"
                            required
                        >

                            <option value="Staff">
                                Staff
                            </option>

                            <option value="Scheduler">
                                Scheduler
                            </option>

                            <option value="Administrator">
                                Administrator
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
     DELETE USER MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="deleteUserModal"
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
                    Delete User?
                </h5>


                <p class="delete-message">

                    Are you sure you want to delete

                    <strong id="deleteUserName"></strong>?

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
                        name="user_id"
                        id="deleteUserId"
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
                            Delete User
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
    | EDIT USER
    |--------------------------------------------------------------------------
    */

    const editButtons =
        document.querySelectorAll(".edit-user-btn");


    editButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("editUserId").value =
                this.dataset.id;

            document.getElementById("editUserFullName").value =
                this.dataset.fullName;

            document.getElementById("editUserUsername").value =
                this.dataset.username;

            document.getElementById("editUserRole").value =
                this.dataset.role;

            document.getElementById("editUserPassword").value =
                "";

        });

    });


    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    const deleteButtons =
        document.querySelectorAll(".delete-user-btn");


    deleteButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("deleteUserId").value =
                this.dataset.id;

            document.getElementById("deleteUserName").textContent =
                this.dataset.name;

        });

    });


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    const searchInput =
        document.getElementById("userSearch");


    if (searchInput) {

        searchInput.addEventListener("keyup", function () {

            const searchValue =
                this.value.toLowerCase();


            const rows =
                document.querySelectorAll(".user-row");


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