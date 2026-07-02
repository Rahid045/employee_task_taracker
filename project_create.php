<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
if ($user['role'] === 'employee') {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (!$name) {
        $error = 'Project name is required.';
    } else {
        try {
            $stmt = db()->prepare('
                INSERT INTO projects (name, description, status, created_by, created_at)
                VALUES (:name, :description, :status, :created_by, NOW())
            ');
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'status' => $status,
                'created_by' => $user['id']
            ]);
            $success = 'Project created successfully!';
        } catch (Exception $e) {
            $error = 'Failed to create project: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Project - Employee Task Tracker</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
             <img src="images/logo.png" alt="logo" class="login-logo">
            <h1>Create New Project</h1>
        </div>
    </header>

    <main class="container">
        <section class="panel form-panel">
            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="project_task_management.php" class="button primary">View Projects</a>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="POST" action="">
                    <h2>Project Details</h2>

                    <label for="name">Project Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g., Q3 Marketing Campaign">

                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Project description and objectives..."></textarea>

                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                    </select>

                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="submit" class="button primary">Create Project</button>
                        <a href="project_task_management.php" class="button secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
