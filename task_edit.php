<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
if ($user['role'] === 'employee') {
    header('Location: index.php');
    exit;
}

$taskId = intval($_GET['id'] ?? 0);
$task = findTaskById($taskId);
if (!$task) {
    header('Location: index.php');
    exit;
}

$users = getAllUsers();
$projects = getAllProjects();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if assigned_to is being changed
    $oldAssignedTo = $task['assigned_to'];
    $newAssignedTo = intval($_POST['assigned_to'] ?? 0) ?: null;
    
    $result = updateTask($taskId, $_POST);
    if ($result['success']) {
        logAuditEvent($user['id'], 'update_task', 'Updated task #' . $taskId, ['assigned_to' => $newAssignedTo]);
        // Notify the new assignee if the task was reassigned
        if ($newAssignedTo && $newAssignedTo !== $oldAssignedTo) {
            createNotification(
                $newAssignedTo,
                'info',
                'New Task Assigned',
                'You have been assigned to task: "' . htmlspecialchars($_POST['title']) . '"'
            );
        }

        // Notify the previous assignee when the task is unassigned or reassigned
        if ($oldAssignedTo && $oldAssignedTo !== $newAssignedTo) {
            if ($newAssignedTo) {
                $newAssignee = findUserById($newAssignedTo);
                $assigneeName = $newAssignee ? $newAssignee['name'] : 'another user';
                createNotification(
                    $oldAssignedTo,
                    'info',
                    'Task Reassigned',
                    'The task "' . htmlspecialchars($_POST['title']) . '" has been reassigned to ' . htmlspecialchars($assigneeName) . '.'
                );
            } else {
                createNotification(
                    $oldAssignedTo,
                    'info',
                    'Task Unassigned',
                    'You were unassigned from task: "' . htmlspecialchars($_POST['title']) . '"'
                );
            }
        }

        header('Location: task_view.php?id=' . $taskId);
        exit;
    }
    $error = $result['message'];
    $task = array_merge($task, $_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
            <h1>Edit Task</h1>
        </div>
    </header>

    <main class="container form-page">

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label>Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>
            <label>Description</label>
            <textarea name="description"><?= htmlspecialchars($task['description']) ?></textarea>
            <label>Assign to</label>
            <select name="assigned_to">
                <option value="">Unassigned</option>
                <?php foreach ($users as $assignee): ?>
                    <option value="<?= $assignee['id'] ?>" <?= $task['assigned_to'] == $assignee['id'] ? 'selected' : '' ?>><?= htmlspecialchars($assignee['name']) ?> (<?= htmlspecialchars($assignee['role']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <label>Project</label>
            <select name="project_id">
                <option value="">No project</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= $project['id'] ?>" <?= $task['project_id'] == $project['id'] ? 'selected' : '' ?>><?= htmlspecialchars($project['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Priority</label>
            <select name="priority">
                <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="low" <?= $task['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="high" <?= $task['priority'] === 'high' ? 'selected' : '' ?>>High</option>
            </select>
            <label>Status</label>
            <select name="status">
                <option value="new" <?= $task['status'] === 'new' ? 'selected' : '' ?>>New</option>
                <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="blocked" <?= $task['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($task['start_date']) ?>">
            <label>Due Date</label>
            <input type="date" name="due_date" value="<?= htmlspecialchars($task['due_date']) ?>">
            <label>Estimated Hours</label>
            <input type="number" step="0.25" name="estimated_hours" value="<?= htmlspecialchars($task['estimated_hours']) ?>">
            <label>Actual Hours</label>
            <input type="number" step="0.25" name="actual_hours" value="<?= htmlspecialchars($task['actual_hours']) ?>">
            <button class="button primary" type="submit">Update Task</button>
        </form>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
