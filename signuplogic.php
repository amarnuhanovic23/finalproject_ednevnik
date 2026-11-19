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

if (strlen($password) < 8) {
    header('Location: signup.php?error=' . urlencode('Lozinka mora imati najmanje 8 karaktera.'));
    exit();
}

if ($timestamp = strtotime($dob)) {
    $dob = date('j.n.Y', $timestamp);
}

$sql = "SELECT id FROM users WHERE username = :username LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':username' => $username]);

if ($stmt->fetch()) {
    header('Location: signup.php?error=' . urlencode('Korisničko ime je zauzeto.'));
    exit();
}

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

header('Location: login.php?registered=1');
exit();
?>
