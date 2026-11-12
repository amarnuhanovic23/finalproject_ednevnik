<?php 

    session_start();

    require_once 'config.php';

    if($_SESSION['role'] !== 'admin') 
        {
            header("Location: login.php");
            exit();
        }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - eDnevnik</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
<script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="logstyle.css">
</head>

<body>

    <main class="form-signin ">
        <nav class="navbar navbar-light bg-light mb-4">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <img src="ednevnik-logo-transparent.png" alt="eDnevnik Logo" class="d-inline-block align-text-left" style=" height: 50px; width: 100px;">

                </a>
            </div>
        </nav>

        <h2 class="mb-4 ">Please sign in</h2>

        <form action="loginlogic.php" method="POST">

            <input 
                type="text"
                name="username"
                class="form-control mb-3"
                placeholder="Username"
                required
            >

            <input 
                type="password"
                name="password"
                class="form-control mb-3"
                placeholder="Password"
                required
            >

            <div class="checkbox mb-3">
                <label>
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit" name="submit">
                Sign in
            </button>

            <p class="mt-3">
                Don't have an account?
                <a href="signup.php">Sign up</a>
            </p>

        </form>

    </main>



</body>
</html>
