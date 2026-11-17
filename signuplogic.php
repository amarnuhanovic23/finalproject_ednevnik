<?php 
session_start();
require_once('config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.php');
    exit();
}

$name     = trim($_POST['name'] ?? '');
$surname  = trim($_POST['surname'] ?? '');
$username = trim($_POST['username'] ?? '');
$dob      = trim($_POST['dob'] ?? '');
$password = $_POST['password'] ?? '';

// 1. Validate required fields
if (
    $name === '' ||
    $surname === '' ||
    $username === '' ||
    $dob === '' ||
    $password === ''
) {
    header('Location: signup.php?error=' . urlencode('Morate popuniti sva polja.'));
    exit();
}

// 2. Validate password length (at least 8 characters)
if (strlen($password) < 8) {
    header('Location: signup.php?error=' . urlencode('Lozinka mora imati najmanje 8 karaktera.'));
    exit();
}

// Format date from YYYY-MM-DD to D.M.YYYY (e.g. 13.5.2013) if needed
if ($timestamp = strtotime($dob)) {
    $dob = date('j.n.Y', $timestamp);
}

// 3. Check if username is already taken (fixed missing quote)
$sql = "SELECT id FROM users WHERE username = :username LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':username' => $username]);

if ($stmt->fetch()) {
    header('Location: signup.php?error=' . urlencode('Korisničko ime je zauzeto.'));
    exit();
}

// 4. Hash password & insert user with 'student' role
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 'student';

$sql = "INSERT INTO users (name, surname, username, dob, password, role) 
        VALUES (:name, :surname, :username, :dob, :password, :role)";
$stmt = $conn->prepare($sql);

try {
    $stmt->execute([
        ':name'     => $name,
        ':surname'  => $surname,
        ':username' => $username,
        ':dob'      => $dob,
        ':password' => $hashedPassword,
        ':role'     => $role
    ]);
} catch (PDOException $e) {
    error_log("Error inserting user: " . $e->getMessage());
    header('Location: signup.php?error=' . urlencode('Greška pri registraciji.'));
    exit();
}

// 5. Redirect to login with success flag
header('Location: login.php?registered=1');
exit();
?>
