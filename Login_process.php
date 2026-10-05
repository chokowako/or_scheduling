<?php

session_start();

require_once "config/database.php";

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Check if fields are empty
if ($username === '' || $password === '') {
    $_SESSION['login_error'] = "Username and password are required.";
    header("Location: login.php");
    exit;
}

try {
    $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $username
    ]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        // Prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        header("Location: dashboard.php");
        exit;

    } else {
        // Invalid credentials error
        $_SESSION['login_error'] = "Invalid username or password.";
        header("Location: login.php");
        exit;
    }

} catch (PDOException $e) {
    // Handle database connection or query errors gracefully
    $_SESSION['login_error'] = "A system error occurred. Please try again later.";
    header("Location: login.php");
    exit;
}