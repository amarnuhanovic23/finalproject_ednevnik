<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'student') {
    header("Location: admin_login.php");
    exit();
}

$student_id = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? 'Student', ENT_QUOTES, 'UTF-8');
$full_name = $username;
$dob = '';

$subjects_map = [
    'history'   => 'History',
    'geo'       => 'Geography',
    'math'      => 'Mathematics',
    'english'   => 'English',
    'physics'   => 'Physics',
    'pe'        => 'Physical Education (P.E.)',
    'chemistry' => 'Chemistry',
    'arts'      => 'Arts',
    'music'     => 'Music',
    'bio'       => 'Biology'
];

$student_grades = [];
foreach ($subjects_map as $key => $label) {
    $student_grades[$key] = [];
}

$userStmt = $conn->prepare("SELECT name, surname, dob FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$student_id]);
if ($uRow = $userStmt->fetch(PDO::FETCH_ASSOC)) {
    $first = ucfirst(trim($uRow['name'] ?? ''));
    $last = ucfirst(trim($uRow['surname'] ?? ''));
    if ($first !== '' || $last !== '') {
        $full_name = htmlspecialchars(trim("$first $last"), ENT_QUOTES, 'UTF-8');
    }
    $dob = htmlspecialchars($uRow['dob'] ?? '', ENT_QUOTES, 'UTF-8');
}

$stmt = $conn->prepare("SELECT LOWER(TRIM(subject)) AS subject, grade FROM grades WHERE student_id = ? ORDER BY id ASC");
$stmt->execute([$student_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $subj = $row['subject'];
    if ($subj === 'geography') $subj = 'geo';
    if ($subj === 'biology') $subj = 'bio';
    if ($subj === 'mathematics') $subj = 'math';

    if (isset($student_grades[$subj])) {
        $student_grades[$subj][] = (int)$row['grade'];
    }
}

$absStmt = $conn->prepare("SELECT subject, absence_date, period, status, note FROM attendance WHERE student_id = ? ORDER BY absence_date DESC, period ASC");
$absStmt->execute([$student_id]);
$absences = $absStmt->fetchAll(PDO::FETCH_ASSOC);

$unexcused_count = 0;
$excused_count   = 0;
$late_count      = 0;
foreach ($absences as $a) {
    if ($a['status'] === 'unexcused') $unexcused_count++;
    elseif ($a['status'] === 'excused') $excused_count++;
    elseif ($a['status'] === 'late') $late_count++;
}

$subject_averages = [];
$total_grades_count = 0;
foreach ($student_grades as $grades_list) {
    if (!empty($grades_list)) {
        $subject_averages[] = array_sum($grades_list) / count($grades_list);
        $total_grades_count += count($grades_list);
    }
}
$overall_gpa = !empty($subject_averages) ? number_format(array_sum($subject_averages) / count($subject_averages), 2) : '0.00';

function getStatusBadge($avg) {
    if ($avg === null) return '<span class="badge bg-secondary-subtle text-secondary">Pending</span>';
    if ($avg >= 4.5) return '<span class="badge bg-success-subtle text-success">Excellent (5)</span>';
    if ($avg >= 3.5) return '<span class="badge bg-primary-subtle text-primary">Very Good (4)</span>';
    if ($avg >= 2.5) return '<span class="badge bg-warning-subtle text-warning">Good (3)</span>';
    if ($avg >= 1.5) return '<span class="badge bg-warning-subtle text-warning">Sufficient (2)</span>';
    return '<span class="badge bg-danger-subtle text-danger">Insufficient (1)</span>';
}
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
                <span>eDnevnik</span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto nav-pills gap-1" id="dashboardTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="grades-tab" data-bs-toggle="pill" data-bs-target="#grades" type="button" role="tab">
                            <i class="bi bi-award me-1"></i> View Grades
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="attendance-tab" data-bs-toggle="pill" data-bs-target="#attendance" type="button" role="tab">
                            <i class="bi bi-calendar-check me-1"></i> View Attendance
                            <?php if (count($absences) > 0): ?>
                                <span class="badge bg-secondary ms-1"><?php echo count($absences); ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="schedule-tab" data-bs-toggle="pill" data-bs-target="#schedule" type="button" role="tab">
                            <i class="bi bi-clock-history me-1"></i> View Schedule
                        </button>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                    <span class="text-secondary small d-none d-md-inline">
                        <i class="bi bi-person-circle me-1"></i> <?php echo $username; ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom border-dark-subtle">
            <div>
                <h1 class="h3 fw-bold text-white mb-1">Welcome back, <?php echo $full_name; ?>!</h1>
                <p class="text-secondary mb-0">
                    Academic Year 2026/2027 &bull; Student Portal
                    <?php if ($dob !== ''): ?>
                        &bull; DOB: <?php echo $dob; ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <span class="badge bg-dark border border-secondary text-light px-3 py-2 rounded-pill">
                    <i class="bi bi-circle-fill text-success me-2" style="font-size: 8px;"></i> Active Semester
                </span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-secondary small text-uppercase fw-semibold mb-1">Total Grade Average</p>
                            <h2 class="fw-bold text-white mb-0"><?php echo $overall_gpa; ?></h2>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-secondary small text-uppercase fw-semibold mb-1">Excused Absences</p>
                            <h2 class="fw-bold text-white mb-0"><?php echo $excused_count; ?></h2>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-secondary small text-uppercase fw-semibold mb-1">Unexcused Absences</p>
                            <h2 class="fw-bold text-white mb-0"><?php echo $unexcused_count; ?></h2>
                        </div>
                        <div class="stat-icon bg-danger-subtle text-danger">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="dashboardTabsContent">
            
            <div class="tab-pane fade show active" id="grades" role="tabpanel">
                <div class="content-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-semibold text-white mb-0">Subject Grades</h5>
                        <span class="text-secondary small">Scale: 1 (Insufficient) – 5 (Excellent)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0 custom-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Grades</th>
                                    <th>Subject Average</th>
                                    <th>Final Standing</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects_map as $key => $subject_name): ?>
                                    <?php 
                                        $grades = $student_grades[$key];
                                        $has_grades = !empty($grades);
                                        $avg = $has_grades ? (array_sum($grades) / count($grades)) : null;
                                    ?>
                                    <tr>
                                        <td class="fw-medium text-white"><?php echo htmlspecialchars($subject_name); ?></td>
                                        <td>
                                            <?php if ($has_grades): ?>
                                                <?php foreach ($grades as $g): ?>
                                                    <?php $safe_g = max(1, min(5, (int)$g)); ?>
                                                    <span class="grade-pill grade-<?php echo $safe_g; ?>"><?php echo $safe_g; ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-secondary small fst-italic">No grades recorded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-semibold text-white">
                                            <?php echo $has_grades ? number_format($avg, 2) : '—'; ?>
                                        </td>
                                        <td>
                                            <?php echo getStatusBadge($avg); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="border-top border-secondary">
                                    <td colspan="2" class="fw-bold text-white text-end">Total Grade Average (Overall GPA):</td>
                                    <td class="fw-bold text-primary fs-5"><?php echo $overall_gpa; ?></td>
                                    <td><?php echo !empty($subject_averages) ? getStatusBadge((float)$overall_gpa) : '<span class="badge bg-secondary-subtle text-secondary">Pending</span>'; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="attendance" role="tabpanel">
                <div class="content-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-semibold text-white mb-0">Attendance &amp; Absences Log</h5>
                        <span class="text-secondary small">
                            Excused: <strong class="text-success"><?php echo $excused_count; ?></strong> &bull;
                            Unexcused: <strong class="text-danger"><?php echo $unexcused_count; ?></strong> &bull;
                            Late: <strong class="text-info"><?php echo $late_count; ?></strong>
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0 custom-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Subject</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($absences)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-4">No absences recorded. Great attendance!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($absences as $abs): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($abs['absence_date']); ?></td>
                                            <td class="text-white fw-medium"><?php echo htmlspecialchars($subjects_map[$abs['subject']] ?? ucfirst($abs['subject'])); ?></td>
                                            <td><?php echo (int)$abs['period']; ?>.</td>
                                            <td>
                                                <?php if ($abs['status'] === 'excused'): ?>
                                                    <span class="badge bg-success-subtle text-success">Excused</span>
                                                <?php elseif ($abs['status'] === 'late'): ?>
                                                    <span class="badge bg-info-subtle text-info">Late</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger">Unexcused</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-secondary small"><?php echo htmlspecialchars($abs['note'] ?? '—'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="schedule" role="tabpanel">
                <div class="content-card p-4">
                    <h5 class="fw-semibold text-white mb-3">Weekly Class Schedule</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-bordered align-middle mb-0 custom-table text-center">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Monday</th>
                                    <th>Tuesday</th>
                                    <th>Wednesday</th>
                                    <th>Thursday</th>
                                    <th>Friday</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-secondary small">1st (08:00)</td>
                                    <td class="text-white">Mathematics</td>
                                    <td class="text-white">English</td>
                                    <td class="text-white">Physics</td>
                                    <td class="text-white">Chemistry</td>
                                    <td class="text-white">History</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary small">2nd (08:50)</td>
                                    <td class="text-white">Biology</td>
                                    <td class="text-white">Geography</td>
                                    <td class="text-white">Mathematics</td>
                                    <td class="text-white">Arts</td>
                                    <td class="text-white">P.E.</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary small">3rd (09:50)</td>
                                    <td class="text-white">Physics</td>
                                    <td class="text-white">Music</td>
                                    <td class="text-white">English</td>
                                    <td class="text-white">Biology</td>
                                    <td class="text-white">Chemistry</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

     <?php
    echo '<script src="https://' . 'cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
    ?>
</body>
</html>
