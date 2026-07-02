<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
if ($user['role'] === 'employee') {
    header('Location: index.php');
    exit;
}

$users = getAllUsers();
$projects = getAllProjects();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = createTask($_POST, $user);
    if ($result['success']) {
        $newAssignedTo = intval($_POST['assigned_to'] ?? 0) ?: null;
        if ($newAssignedTo) {
            createNotification(
                $newAssignedTo,
                'info',
                'Task Assigned',
                'You have been assigned to task: "' . htmlspecialchars($_POST['title']) . '"'
            );
        }

        logAuditEvent($user['id'], 'create_task', 'Created task "' . trim($_POST['title'] ?? '') . '"', ['assigned_to' => $newAssignedTo]);
        header('Location: index.php');
        exit;
    }
    $error = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
             <img src="images/logo.png" alt="logo" class="login-logo">
            <h1>Create New Task</h1>
        </div>
    </header>

    <main class="container form-page">

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label>Title</label>
            <input type="text" name="title" required>
            <label>Description</label>
            <textarea name="description"></textarea>
            <label>Assign to</label>
            <select name="assigned_to">
                <option value="">Unassigned</option>
                <?php foreach ($users as $assignee): ?>
                    <option value="<?= $assignee['id'] ?>"><?= htmlspecialchars($assignee['name']) ?> (<?= htmlspecialchars($assignee['role']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <label>Project</label>
            <select name="project_id">
                <option value="">No project</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Priority</label>
            <select name="priority">
                <option value="medium">Medium</option>
                <option value="low">Low</option>
                <option value="high">High</option>
            </select>
            <label>Status</label>
            <select name="status">
                <option value="new">New</option>
                <option value="in_progress">In Progress</option>
                <option value="blocked">Blocked</option>
                <option value="completed">Completed</option>
            </select>
            <label>Start Date</label>
            <input type="date" name="start_date">
            <label>Due Date</label>
            <input type="date" name="due_date">
            <label>Estimated Hours</label>
            <input type="number" step="0.25" name="estimated_hours">
            <button class="button primary" type="submit">Save Task</button>
        </form>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
