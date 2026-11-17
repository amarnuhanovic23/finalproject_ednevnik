<?php
session_start();
require_once 'config.php';

// Block direct GET access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header("Location: login.php?error=" . urlencode("Please enter both username and password."));
    exit();
}

// Fetch user by username
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify password (supports both password_hash() and plain-text test rows)
$password_valid = false;
if ($user) {
    if (password_verify($password, $user['password'])) {
        $password_valid = true;
    } elseif ($password === $user['password']) {
        $password_valid = true;
    }
}

if (!$user || !$password_valid) {
    header("Location: login.php?error=" . urlencode("Invalid username or password."));
    exit();
}

// Prevent session fixation and store required session variables
session_regenerate_id(true);
$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

// Redirect based on role
if ($user['role'] === 'student') {
    header("Location: student_dashboard.php");
    exit();
} elseif ($user['role'] === 'admin') {
    header("Location: teacher_dashboard.php");
    exit();
} else {
    session_unset();
    session_destroy();
    header("Location: login.php?error=" . urlencode("Invalid account role."));
    exit();
}
?>
