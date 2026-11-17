<?php 
session_start();
require_once 'config.php';

// Only redirect if the user is ALREADY logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
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
            <h2 class="h4 fw-bold text-white mb-1">Welcome back</h2>
            <p class="text-secondary small mb-0">Sign in to your student portal</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2 small text-center" role="alert">
                <?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="loginlogic.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label text-secondary small">Username</label>
                <input 
                    type="text"
                    id="username"
                    name="username"
                    class="form-control custom-input"
                    placeholder="Enter your username"
                    required
                    autofocus
                >
            </div>

            <div class="mb-3">
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

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label text-secondary small" for="remember">
                        Remember me
                    </label>
                </div>
                <a href="admin_login.php" class="text-secondary small text-decoration-none hover-white">Admin Login</a>
            </div>

            <button class="w-100 btn btn-primary fw-semibold py-2 rounded-3" type="submit" name="submit">
                Sign in
            </button>

            <p class="text-center text-secondary small mt-4 mb-0">
                Don't have an account?
                <a href="signup.php" class="text-primary text-decoration-none fw-medium">Sign up</a>
            </p>
        </form>

    </main>

    <script src="https://jsdelivr.net"></script>
</body>
</html>
