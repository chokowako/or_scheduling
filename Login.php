<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>OR Scheduling System - Login</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

 <link
    rel="stylesheet"
    href="assets/css/login.css"
>
	
</head>

<body>

<div class="login-container">

    <div class="card login-card">
        <div class="card-body p-5">

            <div class="system-icon">
                🏥
            </div>

            <h3 class="text-center fw-bold">
                OR Scheduling System
            </h3>

            <p class="text-center text-muted mb-4">
                Please sign in to continue
            </p>

            <form action="login_process.php" method="POST">

                <div class="mb-3">
                    <label class="form-label">Username</label>

                    <input
                        type="text"
                        name="username"
                        class="form-control form-control-lg"
                        placeholder="Enter username"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>

                    <input
                        type="password"
                        name="password"
                        class="form-control form-control-lg"
                        placeholder="Enter password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-lg w-100"
                >
                    Sign In
                </button>

            </form>

            <div class="text-center mt-4">
                <small class="text-muted">
                    OR Scheduling System
                </small>
            </div>

        </div>
    </div>

</div>

</body>
</html>