<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: teacher_dashboard.php");
    exit();
}

$subjects_map = [
    'history' => 'History', 'geo' => 'Geography', 'math' => 'Mathematics',
    'english' => 'English', 'physics' => 'Physics', 'pe' => 'P.E.',
    'chemistry' => 'Chemistry', 'arts' => 'Arts', 'music' => 'Music',
     'bio' => 'Biology', 'info' => 'Informatics', 'vjer' => 'Religion',
     'teh' => 'Technical Education', 'soc' => 'Social Studies', 'ger' => 'German'
];

$action     = $_POST['action'] ?? '';
$active_tab = $_POST['active_tab'] ?? 'grades';
$student_id = (int)($_POST['student_id'] ?? 0);

function redirectWithMsg($tab, $sid, $type, $msg) {
    $q = http_build_query(['tab' => $tab, 'student_id' => $sid, $type => $msg]);
    header("Location: teacher_dashboard.php?$q");
    exit();
}

if ($action === 'add_grade') {
    $subj  = trim($_POST['subject'] ?? '');
    $grade = (int)($_POST['grade'] ?? 0);
    if ($student_id <= 0 || !isset($subjects_map[$subj]) || $grade < 1 || $grade > 5) {
        redirectWithMsg('grades', $student_id, 'error', 'Invalid grade input.');
    }
    $conn->prepare("INSERT INTO grades (student_id, subject, grade) VALUES (?, ?, ?)")->execute([$student_id, $subj, $grade]);
    redirectWithMsg('grades', $student_id, 'success', "Grade ($grade) added to {$subjects_map[$subj]}.");
}

if ($action === 'edit_grade') {
    $gid   = (int)($_POST['grade_id'] ?? 0);
    $grade = (int)($_POST['grade'] ?? 0);
    if ($gid > 0 && $grade >= 1 && $grade <= 5) {
        $conn->prepare("UPDATE grades SET grade = ? WHERE id = ?")->execute([$grade, $gid]);
        redirectWithMsg('grades', $student_id, 'success', "Grade updated to $grade.");
    }
    redirectWithMsg('grades', $student_id, 'error', 'Invalid grade.');
}

if ($action === 'delete_grade') {
    $gid = (int)($_POST['grade_id'] ?? 0);
    if ($gid > 0) {
        $conn->prepare("DELETE FROM grades WHERE id = ?")->execute([$gid]);
    }
    redirectWithMsg('grades', $student_id, 'success', 'Grade removed.');
}

if ($action === 'add_absence') {
    $subj   = trim($_POST['subject'] ?? '');
    $date   = trim($_POST['absence_date'] ?? '');
    $period = max(1, min(8, (int)($_POST['period'] ?? 1)));
    $status = trim($_POST['status'] ?? 'unexcused');
    $note   = trim($_POST['note'] ?? '');

    if ($student_id <= 0 || !isset($subjects_map[$subj]) || $date === '' || !in_array($status, ['unexcused', 'excused', 'late'], true)) {
        redirectWithMsg('attendance', $student_id, 'error', 'Please fill in all required attendance fields.');
    }
    $conn->prepare("INSERT INTO attendance (student_id, subject, absence_date, period, status, note) VALUES (?, ?, ?, ?, ?, ?)")
         ->execute([$student_id, $subj, $date, $period, $status, $note !== '' ? $note : null]);
    redirectWithMsg('attendance', $student_id, 'success', 'Absence recorded.');
}

if ($action === 'update_absence') {
    $aid    = (int)($_POST['absence_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    if ($aid > 0 && in_array($status, ['unexcused', 'excused', 'late'], true)) {
        $conn->prepare("UPDATE attendance SET status = ? WHERE id = ?")->execute([$status, $aid]);
    }
    redirectWithMsg('attendance', $student_id, 'success', 'Absence status updated.');
}

if ($action === 'delete_absence') {
    $aid = (int)($_POST['absence_id'] ?? 0);
    if ($aid > 0) {
        $conn->prepare("DELETE FROM attendance WHERE id = ?")->execute([$aid]);
    }
    redirectWithMsg('attendance', $student_id, 'success', 'Absence deleted.');
}

if ($action === 'add_student') {
    $name     = trim($_POST['name'] ?? '');
    $surname  = trim($_POST['surname'] ?? '');
    $dob      = trim($_POST['dob'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $surname === '' || $dob === '' || $username === '' || strlen($password) < 4) {
        redirectWithMsg('students', $student_id, 'error', 'Fill all fields (password min 4 chars).');
    }
    if ($ts = strtotime($dob)) {
        $dob = date('j.n.Y', $ts);
    }
    $chk = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $chk->execute([$username]);
    if ($chk->fetch()) {
        redirectWithMsg('students', $student_id, 'error', 'Username already taken.');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $conn->prepare("INSERT INTO users (name, surname, dob, username, password, role) VALUES (?, ?, ?, ?, ?, 'student')")
         ->execute([$name, $surname, $dob, $username, $hash]);
    redirectWithMsg('students', (int)$conn->lastInsertId(), 'success', 'Student added!');
}

if ($action === 'edit_student') {
    $eid      = (int)($_POST['edit_student_id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $surname  = trim($_POST['surname'] ?? '');
    $dob      = trim($_POST['dob'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $new_pass = $_POST['new_password'] ?? '';

    if ($eid <= 0 || $name === '' || $surname === '' || $dob === '' || $username === '') {
        redirectWithMsg('students', $student_id, 'error', 'All profile fields are required.');
    }
    $chk = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
    $chk->execute([$username, $eid]);
    if ($chk->fetch()) {
        redirectWithMsg('students', $student_id, 'error', 'Username already used by another user.');
    }
    if ($new_pass !== '') {
        $conn->prepare("UPDATE users SET name=?, surname=?, dob=?, username=?, password=? WHERE id=? AND role='student'")
             ->execute([$name, $surname, $dob, $username, password_hash($new_pass, PASSWORD_DEFAULT), $eid]);
    } else {
        $conn->prepare("UPDATE users SET name=?, surname=?, dob=?, username=? WHERE id=? AND role='student'")
             ->execute([$name, $surname, $dob, $username, $eid]);
    }
    redirectWithMsg('students', $eid, 'success', 'Student updated.');
}

if ($action === 'delete_student') {
    $did = (int)($_POST['delete_student_id'] ?? 0);
    if ($did > 0) {
        $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'")->execute([$did]);
    }
    redirectWithMsg('students', 0, 'success', 'Student deleted.');
}

header("Location: teacher_dashboard.php");
exit();
