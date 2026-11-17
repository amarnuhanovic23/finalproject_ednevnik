<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$teacher_username = htmlspecialchars($_SESSION['username'] ?? 'Teacher', ENT_QUOTES, 'UTF-8');
$active_tab       = $_GET['tab'] ?? 'grades';
$success_msg      = $_GET['success'] ?? '';
$error_msg        = $_GET['error'] ?? '';

$subjects_map = [
    'history' => 'History', 'geo' => 'Geography', 'math' => 'Mathematics',
    'english' => 'English', 'physics' => 'Physics', 'pe' => 'P.E.',
    'chemistry' => 'Chemistry', 'arts' => 'Arts', 'music' => 'Music',
     'bio' => 'Biology', 'info' => 'Informatics', 'vjer' => 'Religion',
     'teh' => 'Technical Education', 'soc' => 'Social Studies', 'ger' => 'German'
];

$students = $conn->query("SELECT id, name, surname, dob, username FROM users WHERE role = 'student' ORDER BY surname ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
$selected_student_id = isset($_GET['student_id']) && (int)$_GET['student_id'] > 0 ? (int)$_GET['student_id'] : ($students[0]['id'] ?? 0);

$selected_student_info = null;
foreach ($students as $s) {
    if ((int)$s['id'] === $selected_student_id) { $selected_student_info = $s; break; }
}
if (!$selected_student_info && !empty($students)) {
    $selected_student_info = $students[0];
    $selected_student_id = (int)$students[0]['id'];
}

$selected_student_grades = array_fill_keys(array_keys($subjects_map), []);
if ($selected_student_id > 0) {
    $gStmt = $conn->prepare("SELECT id, LOWER(TRIM(subject)) AS subject, grade FROM grades WHERE student_id = ? ORDER BY id ASC");
    $gStmt->execute([$selected_student_id]);
    foreach ($gStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $subj = str_replace(['geography', 'biology', 'mathematics'], ['geo', 'bio', 'math'], $row['subject']);
        if (isset($selected_student_grades[$subj])) {
            $selected_student_grades[$subj][] = ['id' => (int)$row['id'], 'grade' => (int)$row['grade']];
        }
    }
}

$subject_avgs = [];
foreach ($selected_student_grades as $list) {
    if ($list) { $vals = array_column($list, 'grade'); $subject_avgs[] = array_sum($vals) / count($vals); }
}
$selected_gpa = $subject_avgs ? number_format(array_sum($subject_avgs) / count($subject_avgs), 2) : '0.00';

$student_absences = [];
if ($selected_student_id > 0) {
    $aStmt = $conn->prepare("SELECT id, subject, absence_date, period, status, note FROM attendance WHERE student_id = ? ORDER BY absence_date DESC, period ASC");
    $aStmt->execute([$selected_student_id]);
    $student_absences = $aStmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalSystemGrades   = (int)$conn->query("SELECT COUNT(*) FROM grades")->fetchColumn();
$totalSystemAbsences = (int)$conn->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - eDnevnik</title>
    <?php
    $cdn = "https://" . "cdn.jsdelivr.net/npm/";
    echo '<link rel="stylesheet" href="' . $cdn . 'bootstrap@5.3.3/dist/css/bootstrap.min.css">' . "\n";
    echo '<link rel="stylesheet" href="' . $cdn . 'bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">' . "\n";
    ?>
    <link rel="stylesheet" href="dashboardstyle.css">
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">
            <span class="brand-icon"><i class="bi bi-journal-bookmark-fill"></i></span>
            <span>eDnevnik <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.7rem;">Teacher</span></span>
        </a>
        <div class="collapse navbar-collapse show">
            <ul class="navbar-nav mx-auto nav-pills gap-1" role="tablist">
                <li class="nav-item"><button class="nav-link <?php echo $active_tab==='grades'?'active':''; ?>" data-bs-toggle="pill" data-bs-target="#tab-grades" type="button"><i class="bi bi-pencil-square me-1"></i>Grades</button></li>
                <li class="nav-item"><button class="nav-link <?php echo $active_tab==='attendance'?'active':''; ?>" data-bs-toggle="pill" data-bs-target="#tab-attendance" type="button"><i class="bi bi-calendar-x me-1"></i>Absences</button></li>
                <li class="nav-item"><button class="nav-link <?php echo $active_tab==='students'?'active':''; ?>" data-bs-toggle="pill" data-bs-target="#tab-students" type="button"><i class="bi bi-people me-1"></i>Students</button></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small"><i class="bi bi-person-badge me-1"></i><?php echo $teacher_username; ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill">Logout</a>
            </div>
        </div>
    </div>
</nav>

<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-dark-subtle">
        <div>
            <h1 class="h3 fw-bold text-white mb-1">Welcome, <?php echo $teacher_username; ?>!</h1>
            <p class="text-secondary mb-0">Faculty Gradebook, Attendance &amp; Student Management</p>
        </div>
        <span class="badge bg-dark border border-secondary text-light px-3 py-2 rounded-pill"><i class="bi bi-shield-check text-primary me-1"></i>Admin Access</span>
    </div>

    <?php if ($success_msg): ?><div class="alert alert-success border-0 bg-success-subtle text-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
    <?php if ($error_msg): ?><div class="alert alert-danger border-0 bg-danger-subtle text-danger"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat-card p-4"><p class="text-secondary small text-uppercase mb-1">Total Students</p><h2 class="fw-bold text-white mb-0"><?php echo count($students); ?></h2></div></div>
        <div class="col-md-4"><div class="stat-card p-4"><p class="text-secondary small text-uppercase mb-1">Grades Issued</p><h2 class="fw-bold text-white mb-0"><?php echo $totalSystemGrades; ?></h2></div></div>
        <div class="col-md-4"><div class="stat-card p-4"><p class="text-secondary small text-uppercase mb-1">Absences Logged</p><h2 class="fw-bold text-white mb-0"><?php echo $totalSystemAbsences; ?></h2></div></div>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade <?php echo $active_tab==='grades'?'show active':''; ?>" id="tab-grades">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="content-card p-4">
                        <h5 class="fw-semibold text-white mb-3">Assign New Grade</h5>
                        <form method="POST" action="teacher_actions.php">
                            <input type="hidden" name="action" value="add_grade">
                            <div class="mb-3"><label class="form-label text-secondary small">Student</label>
                                <select class="form-select bg-dark text-white border-secondary" name="student_id" required>
                                    <?php foreach ($students as $stu): ?>
                                        <option value="<?php echo $stu['id']; ?>" <?php echo $stu['id']==$selected_student_id?'selected':''; ?>><?php echo htmlspecialchars(ucfirst($stu['name']).' '.ucfirst($stu['surname'])); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label text-secondary small">Subject</label>
                                <select class="form-select bg-dark text-white border-secondary" name="subject" required>
                                    <?php foreach ($subjects_map as $k => $v): ?><option value="<?php echo $k; ?>"><?php echo $v; ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4"><label class="form-label text-secondary small d-block">Grade (1–5)</label>
                                <div class="btn-group w-100">
                                    <?php for ($g=1; $g<=5; $g++): ?>
                                        <input type="radio" class="btn-check" name="grade" id="ag_<?php echo $g; ?>" value="<?php echo $g; ?>" <?php echo $g===5?'checked':''; ?>>
                                        <label class="btn btn-outline-secondary fw-bold" for="ag_<?php echo $g; ?>"><?php echo $g; ?></label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold">Save Grade</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="content-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-semibold text-white mb-0">Gradebook: <?php echo $selected_student_info ? htmlspecialchars(ucfirst($selected_student_info['name']).' '.ucfirst($selected_student_info['surname'])) : ''; ?></h5>
                            <form method="GET"><input type="hidden" name="tab" value="grades">
                                <select name="student_id" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()">
                                    <?php foreach ($students as $stu): ?><option value="<?php echo $stu['id']; ?>" <?php echo $stu['id']==$selected_student_id?'selected':''; ?>><?php echo htmlspecialchars(ucfirst($stu['name']).' '.ucfirst($stu['surname'])); ?></option><?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        <table class="table table-dark table-hover align-middle mb-0 custom-table">
                            <thead><tr><th>Subject</th><th>Grades (Click to Edit/Delete)</th><th class="text-end">Avg</th></tr></thead>
                            <tbody>
                                <?php foreach ($subjects_map as $k => $label): $items = $selected_student_grades[$k]; $avg = $items ? array_sum(array_column($items,'grade'))/count($items) : null; ?>
                                <tr>
                                    <td class="text-white fw-medium"><?php echo $label; ?></td>
                                    <td>
                                        <?php if ($items): foreach ($items as $it): ?>
                                            <button type="button" class="grade-pill grade-<?php echo $it['grade']; ?> border-0" data-bs-toggle="modal" data-bs-target="#editGradeModal" data-gid="<?php echo $it['id']; ?>" data-gval="<?php echo $it['grade']; ?>"><?php echo $it['grade']; ?></button>
                                        <?php endforeach; else: ?><span class="text-secondary small fst-italic">None</span><?php endif; ?>
                                    </td>
                                    <td class="text-end fw-semibold text-white"><?php echo $avg ? number_format($avg,2) : '—'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot><tr class="border-top border-secondary"><td colspan="2" class="text-end fw-bold text-white">Total GPA:</td><td class="text-end fw-bold text-primary fs-5"><?php echo $selected_gpa; ?></td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade <?php echo $active_tab==='attendance'?'show active':''; ?>" id="tab-attendance">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="content-card p-4">
                        <h5 class="fw-semibold text-white mb-3">Log Absence</h5>
                        <form method="POST" action="teacher_actions.php">
                            <input type="hidden" name="action" value="add_absence">
                            <div class="mb-3"><label class="form-label text-secondary small">Student</label>
                                <select class="form-select bg-dark text-white border-secondary" name="student_id" required>
                                    <?php foreach ($students as $stu): ?><option value="<?php echo $stu['id']; ?>" <?php echo $stu['id']==$selected_student_id?'selected':''; ?>><?php echo htmlspecialchars(ucfirst($stu['name']).' '.ucfirst($stu['surname'])); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label text-secondary small">Subject</label>
                                <select class="form-select bg-dark text-white border-secondary" name="subject"><?php foreach ($subjects_map as $k=>$v): ?><option value="<?php echo $k; ?>"><?php echo $v; ?></option><?php endforeach; ?></select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-7"><label class="form-label text-secondary small">Date</label><input type="date" class="form-control bg-dark text-white border-secondary" name="absence_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                                <div class="col-5"><label class="form-label text-secondary small">Period</label><input type="number" class="form-control bg-dark text-white border-secondary" name="period" min="1" max="8" value="1" required></div>
                            </div>
                            <div class="mb-3"><label class="form-label text-secondary small">Status</label>
                                <select class="form-select bg-dark text-white border-secondary" name="status"><option value="unexcused">Unexcused</option><option value="excused">Excused</option><option value="late">Late</option></select>
                            </div>
                            <div class="mb-3"><label class="form-label text-secondary small">Note</label><input type="text" class="form-control bg-dark text-white border-secondary" name="note" placeholder="Optional"></div>
                            <button type="submit" class="btn btn-warning text-dark w-100 fw-semibold">Record Absence</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="content-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-semibold text-white mb-0">Absences Log</h5>
                            <form method="GET"><input type="hidden" name="tab" value="attendance">
                                <select name="student_id" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()">
                                    <?php foreach ($students as $stu): ?><option value="<?php echo $stu['id']; ?>" <?php echo $stu['id']==$selected_student_id?'selected':''; ?>><?php echo htmlspecialchars(ucfirst($stu['name']).' '.ucfirst($stu['surname'])); ?></option><?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        <table class="table table-dark table-hover align-middle mb-0 custom-table">
                            <thead><tr><th>Date</th><th>Subject</th><th>Period</th><th>Status</th><th>Note</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                <?php if (!$student_absences): ?><tr><td colspan="6" class="text-center text-secondary py-4">No absences recorded.</td></tr>
                                <?php else: foreach ($student_absences as $a): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['absence_date']); ?></td>
                                    <td class="text-white"><?php echo $subjects_map[$a['subject']] ?? $a['subject']; ?></td>
                                    <td><?php echo $a['period']; ?>.</td>
                                    <td><span class="badge bg-<?php echo $a['status']==='excused'?'success':($a['status']==='late'?'info':'danger'); ?>-subtle text-<?php echo $a['status']==='excused'?'success':($a['status']==='late'?'info':'danger'); ?>"><?php echo ucfirst($a['status']); ?></span></td>
                                    <td class="text-secondary small"><?php echo htmlspecialchars($a['note'] ?? '—'); ?></td>
                                    <td class="text-end text-nowrap">
                                        <?php if ($a['status']!=='excused'): ?>
                                            <form method="POST" action="teacher_actions.php" class="d-inline"><input type="hidden" name="action" value="update_absence"><input type="hidden" name="absence_id" value="<?php echo $a['id']; ?>"><input type="hidden" name="student_id" value="<?php echo $selected_student_id; ?>"><input type="hidden" name="status" value="excused"><button class="btn btn-sm btn-outline-success">Excuse</button></form>
                                        <?php endif; ?>
                                        <form method="POST" action="teacher_actions.php" class="d-inline"><input type="hidden" name="action" value="delete_absence"><input type="hidden" name="absence_id" value="<?php echo $a['id']; ?>"><input type="hidden" name="student_id" value="<?php echo $selected_student_id; ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade <?php echo $active_tab==='students'?'show active':''; ?>" id="tab-students">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="content-card p-4">
                        <h5 class="fw-semibold text-white mb-3">Add New Student</h5>
                        <form method="POST" action="teacher_actions.php">
                            <input type="hidden" name="action" value="add_student">
                            <div class="row g-2 mb-3">
                                <div class="col-6"><label class="form-label text-secondary small">Name</label><input type="text" class="form-control bg-dark text-white border-secondary" name="name" required></div>
                                <div class="col-6"><label class="form-label text-secondary small">Surname</label><input type="text" class="form-control bg-dark text-white border-secondary" name="surname" required></div>
                            </div>
                            <div class="mb-3"><label class="form-label text-secondary small">DOB</label><input type="date" class="form-control bg-dark text-white border-secondary" name="dob" required></div>
                            <div class="mb-3"><label class="form-label text-secondary small">Username</label><input type="text" class="form-control bg-dark text-white border-secondary" name="username" required></div>
                            <div class="mb-3"><label class="form-label text-secondary small">Password</label><input type="password" class="form-control bg-dark text-white border-secondary" name="password" minlength="4" required></div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold">Create Student</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="content-card p-4">
                        <h5 class="fw-semibold text-white mb-3">Registered Students</h5>
                        <table class="table table-dark table-hover align-middle mb-0 custom-table">
                            <thead><tr><th>ID</th><th>Full Name</th><th>Username</th><th>DOB</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($students as $stu): ?>
                                <tr>
                                    <td class="text-secondary">#<?php echo $stu['id']; ?></td>
                                    <td class="text-white fw-medium"><?php echo htmlspecialchars(ucfirst($stu['name']).' '.ucfirst($stu['surname'])); ?></td>
                                    <td>@<?php echo htmlspecialchars($stu['username']); ?></td>
                                    <td><?php echo htmlspecialchars($stu['dob']); ?></td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-light me-1" data-bs-toggle="modal" data-bs-target="#editStudentModal" data-id="<?php echo $stu['id']; ?>" data-name="<?php echo htmlspecialchars($stu['name']); ?>" data-surname="<?php echo htmlspecialchars($stu['surname']); ?>" data-dob="<?php echo htmlspecialchars($stu['dob']); ?>" data-user="<?php echo htmlspecialchars($stu['username']); ?>">Edit</button>
                                        <form method="POST" action="teacher_actions.php" class="d-inline" onsubmit="return confirm('Delete this student?');"><input type="hidden" name="action" value="delete_student"><input type="hidden" name="delete_student_id" value="<?php echo $stu['id']; ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="editGradeModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content bg-dark border-secondary">
    <div class="modal-header border-secondary"><h5 class="modal-title text-white">Edit or Delete Grade</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form method="POST" action="teacher_actions.php" class="mb-2"><input type="hidden" name="action" value="edit_grade"><input type="hidden" name="grade_id" id="mEditGid"><input type="hidden" name="student_id" value="<?php echo $selected_student_id; ?>">
            <div class="btn-group w-100 mb-3"><?php for ($g=1; $g<=5; $g++): ?><input type="radio" class="btn-check" name="grade" id="eg_<?php echo $g; ?>" value="<?php echo $g; ?>"><label class="btn btn-outline-primary fw-bold" for="eg_<?php echo $g; ?>"><?php echo $g; ?></label><?php endfor; ?></div>
            <button type="submit" class="btn btn-primary w-100">Update Grade</button>
        </form>
        <form method="POST" action="teacher_actions.php"><input type="hidden" name="action" value="delete_grade"><input type="hidden" name="grade_id" id="mDelGid"><input type="hidden" name="student_id" value="<?php echo $selected_student_id; ?>"><button type="submit" class="btn btn-outline-danger w-100">Delete Grade</button></form>
    </div>
</div></div></div>

<div class="modal fade" id="editStudentModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content bg-dark border-secondary">
    <div class="modal-header border-secondary"><h5 class="modal-title text-white">Edit Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="teacher_actions.php"><div class="modal-body">
        <input type="hidden" name="action" value="edit_student"><input type="hidden" name="edit_student_id" id="mStuId">
        <div class="row g-2 mb-2"><div class="col-6"><label class="form-label small text-secondary">Name</label><input type="text" class="form-control bg-dark text-white border-secondary" name="name" id="mStuName" required></div><div class="col-6"><label class="form-label small text-secondary">Surname</label><input type="text" class="form-control bg-dark text-white border-secondary" name="surname" id="mStuSur" required></div></div>
        <div class="mb-2"><label class="form-label small text-secondary">DOB</label><input type="text" class="form-control bg-dark text-white border-secondary" name="dob" id="mStuDob" required></div>
        <div class="mb-2"><label class="form-label small text-secondary">Username</label><input type="text" class="form-control bg-dark text-white border-secondary" name="username" id="mStuUser" required></div>
        <div class="mb-2"><label class="form-label small text-secondary">New Password (Optional)</label><input type="password" class="form-control bg-dark text-white border-secondary" name="new_password"></div>
    </div><div class="modal-footer border-secondary"><button type="submit" class="btn btn-primary w-100">Save Changes</button></div></form>
</div></div></div>

    <?php
    echo '<script src="https://' . 'cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
    ?>


<script>
document.getElementById('editGradeModal')?.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('mEditGid').value = b.dataset.gid;
    document.getElementById('mDelGid').value = b.dataset.gid;
    const r = document.getElementById('eg_' + b.dataset.gval); if (r) r.checked = true;
});
document.getElementById('editStudentModal')?.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('mStuId').value = b.dataset.id;
    document.getElementById('mStuName').value = b.dataset.name;
    document.getElementById('mStuSur').value = b.dataset.surname;
    document.getElementById('mStuDob').value = b.dataset.dob;
    document.getElementById('mStuUser').value = b.dataset.user;
});
</script>
</body>
</html>
