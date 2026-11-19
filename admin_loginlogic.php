<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header("Location: admin_login.php?error=" . urlencode("Please enter both username and password."));
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$password_valid = false;
if ($user) {
    if (password_verify($password, $user['password'])) {
        $password_valid = true;
    } elseif ($password === $user['password']) {
        $password_valid = true;
    }
}

if (!$user || !$password_valid) {
    header("Location: admin_login.php?error=" . urlencode("Invalid staff username or password."));
    exit();
}

if ($user['role'] !== 'admin') {
    header("Location: admin_login.php?error=" . urlencode("Access denied. Please use the Student Login page."));
    exit();
}

session_regenerate_id(true);
$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

header("Location: teacher_dashboard.php");
exit();
?>
