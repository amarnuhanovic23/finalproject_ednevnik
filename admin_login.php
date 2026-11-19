<?php 
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: teacher_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student_dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eDnevnik</title>
    <?php
    $cdn = "https://" . "cdn.jsdelivr.net/npm/";
    echo '<link rel="stylesheet" href="' . $cdn . 'bootstrap@5.3.3/dist/css/bootstrap.min.css">' . "\n";
    echo '<link rel="stylesheet" href="' . $cdn . 'bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">' . "\n";
    ?>
    <link rel="stylesheet" href="logstyle.css">
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100">

    <main class="login-card p-4 p-md-5 w-100" style="max-width: 420px;">
        
        <div class="text-center mb-4">
            <img src="ednevnik-logo-transparent.png" alt="eDnevnik Logo" class="mb-3" style="height: 55px; width: auto;">
            <div class="mb-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                    <i class="bi bi-shield-lock-fill me-1"></i> Faculty &amp; Admin Portal
                </span>
            </div>
            <h2 class="h4 fw-bold text-white mb-1">Teacher Sign In</h2>
            <p class="text-secondary small mb-0">Enter your staff credentials to manage grades</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2 small text-center border-0 bg-danger-subtle text-danger" role="alert">
                <i class="bi bi-exclamation-circle me-1"></i>
                <?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="admin_loginlogic.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label text-secondary small">Staff Username</label>
                <input 
                    type="text"
                    id="username"
                    name="username"
                    class="form-control custom-input"
                    placeholder="Enter admin username"
                    required
                    autofocus
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-secondary small">Password</label>
                <input 
                    type="password"
                    id="password"
                    name="password"
                    class="form-control custom-input"
                    placeholder="••••••••"
                    required
                >
            </div>

            <button class="w-100 btn btn-primary fw-semibold py-2 rounded-3" type="submit" name="submit">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign in to Faculty Portal
            </button>

            <div class="text-center mt-4 pt-3 border-top border-dark-subtle">
                <a href="login.php" class="text-secondary small text-decoration-none hover-white">
                    <i class="bi bi-arrow-left me-1"></i> Back to Student Login
                </a>
            </div>
        </form>

    </main>

    <script src="https://jsdelivr.net"></script>
</body>
</html>
