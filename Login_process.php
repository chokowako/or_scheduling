```php
<?php

session_start();

require_once "config/database.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    die("Username and password are required.");
}

$sql = "SELECT * FROM users WHERE username = :username LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':username' => $username
]);

$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];

    header("Location: dashboard.php");
    exit;

} else {

    die("Invalid username or password.");

}

