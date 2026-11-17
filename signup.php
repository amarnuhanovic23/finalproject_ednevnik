<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: student_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: teacher_dashboard.php");
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

<body class="d-flex align-items-center justify-content-center min-vh-100 py-4">

    <main class="login-card p-4 p-md-5 w-100" style="max-width: 460px;">
        
        <div class="text-center mb-4">
            <img src="ednevnik-logo-transparent.png" alt="eDnevnik Logo" class="mb-3" style="height: 55px; width: auto;">
            <h2 class="h4 fw-bold text-white mb-1">Create an Account</h2>
            <p class="text-secondary small mb-0">Register as a new student on eDnevnik</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2 small text-center" role="alert">
                <?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="signuplogic.php" method="POST">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label text-secondary small">First Name</label>
                    <input 
                        type="text" 
                        class="form-control custom-input" 
                        id="name" 
                        name="name" 
                        placeholder="e.g. Amar" 
                        required
                    >
                </div>
                <div class="col-md-6">
                    <label for="surname" class="form-label text-secondary small">Surname</label>
                    <input 
                        type="text" 
                        class="form-control custom-input" 
                        id="surname" 
                        name="surname" 
                        placeholder="e.g. Nuhanović" 
                        required
                    >
                </div>
            </div>

            <div class="mb-3">
                <label for="dob" class="form-label text-secondary small">Date of Birth</label>
                <input 
                    type="date" 
                    class="form-control custom-input" 
                    id="dob" 
                    name="dob" 
                    required
                >
            </div>

            <div class="mb-3">
                <label for="username" class="form-label text-secondary small">Username</label>
                <input 
                    type="text" 
                    class="form-control custom-input" 
                    id="username" 
                    name="username" 
                    placeholder="Choose a username" 
                    required
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-secondary small">Password</label>
                <input 
                    type="password" 
                    class="form-control custom-input" 
                    id="password" 
                    name="password" 
                    placeholder="Create a password" 
                    minlength="8"
                    required
                >
            </div>

            <button type="submit" name="submit" class="w-100 btn btn-primary fw-semibold py-2 rounded-3">
                Create Student Account
            </button>

            <p class="text-center text-secondary small mt-4 mb-0">
                Already have an account?
                <a href="login.php" class="text-primary text-decoration-none fw-medium">Sign in</a>
            </p>
        </form>

    </main>

    <script src="https://jsdelivr.net"></script>
</body>
</html>
