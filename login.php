<?php
session_start();
require_once __DIR__ . '/app/init.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Find user by email
    $user = findUserByEmail($email);

    if ($user) {

        // Compare entered password with stored password
        if (verifyStoredPassword($password, $user['password'])) {

            // Remove password before storing session
            unset($user['password']);

            $_SESSION['user'] = $user;
            logAuditEvent($user['id'], 'login', 'User signed in successfully.');

            header('Location: index.php');
            exit;
        }
    }

    $error = 'Invalid email or password.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Employee Task Tracker</title>

    <link rel="stylesheet" href="assets/styles.css">

    <style>
        .login-logo{
            display:block;
            margin:0 auto 1rem;
            width:120px;
            height:auto;
        }

        .login-page{
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }
    </style>
</head>

<body>

<main class="login-page">

    <form class="login-form" method="POST">

        <img src="images/logo.png" alt="Task Tracker Logo" class="login-logo">

        <h1>Task Tracker Login</h1>

        <?php if ($error): ?>
            <div class="alert error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button class="button primary" type="submit">
            Sign In
        </button>

    </form>

</main>

</body>
</html>