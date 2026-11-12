<?php 

    session_start();

    require_once 'config.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    } 
    if($_SESSION['role'] !== 'student') 
        {
            header("Location: admin_login.php");
            exit();
        }

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header("Location: login.php");
        exit();
    }

    ?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Student Dashboard - eDnevnik</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
        <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="dashboardstyle.css">

    </head>
    <body>

    <main class="container mt-5">
        <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
        <p>This is your student dashboard.</p>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <nav class="navbar navbar-expand navbar-dark bg-dark mt-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">eDnevnik</a>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#">View Grades</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">View Attendance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">View Schedule</a>
                        </li>
                    </ul>
                </div>
            </div>