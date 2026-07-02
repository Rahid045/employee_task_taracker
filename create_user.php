<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

if ($_SESSION['user']['role'] === 'employee') {
    header('Location: index.php');
    exit;
}

$error = '';
$name = '';
$email = '';
$role = 'employee';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'employee';
    $validRoles = ['admin', 'manager', 'employee'];

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, $validRoles, true)) {
        $error = 'Invalid role selected.';
    } elseif (findUserByEmail($email)) {
        $error = 'A user with that email address already exists.';
    } else {
        $stmt = db()->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => hashPassword($password),
            'role' => $role,
        ]);

        $createdUserId = (int) db()->lastInsertId();
        logAuditEvent($_SESSION['user']['id'], 'create_user', 'Created user ' . $email . ' with role ' . $role, ['target_user_id' => $createdUserId]);
        $_SESSION['flash_message'] = 'New user created successfully.';
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - Employee Task Tracker</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .page-shell {
            min-height: 100vh;
            background: #fffad1;
        }

        .main-content {
            margin-left: 260px;
            padding: 2.5rem 2rem 3rem;
        }

        .form-panel {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d7dee6;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
            padding: 32px;
        }

        .form-panel h1 {
            margin-top: 0;
            margin-bottom: 0.25rem;
            font-size: 1.8rem;
            color: #0f172a;
        }

        .form-panel p.description {
            margin: 0 0 1.5rem;
            color: #475569;
        }

        .field-group {
            margin-bottom: 1rem;
        }

        .field-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #334155;
            font-weight: 600;
        }

        .field-group input,
        .field-group select {
            width: 100%;
            min-height: 46px;
            padding: 0 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            font-size: 0.95rem;
            color: #0f172a;
        }

        .field-group input:focus,
        .field-group select:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .form-actions .button {
            min-width: 160px;
        }

        .alert {
            padding: 0.95rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 900px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="page-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <section class="form-panel">
            <h1>Create New User</h1>
            <p class="description">Add a new user to the task tracker with a role and secure password.</p>

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="create_user.php">
                <div class="field-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                </div>

                <div class="field-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <div class="field-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="field-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="manager" <?= $role === 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="employee" <?= $role === 'employee' ? 'selected' : '' ?>>Employee</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button primary">Create User</button>
                    <button type="button" class="button secondary" onclick="window.location.href='index.php'">Cancel</button>
                </div>
            </form>
        </section>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
