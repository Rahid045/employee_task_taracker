<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
$search = trim($_GET['search'] ?? '');
$tasks = getTasksForUser($user, $search);
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Task Tracker</title>
    <link rel="stylesheet" href="assets/styles.css">
<style>
    .action-buttons {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .icon-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        padding: 0;
        font-size: 1rem;
        line-height: 1;
    }
</style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
            <img src="images/logo.png" alt="logo" class="login-logo">
            <h1>Task Dashboard</h1>
            <div class="header-actions">
                <span>Welcome, <?= htmlspecialchars($user['name']) ?></span>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="panel summary-panel">
            <div class="summary-card">
                <h2>Open Tasks</h2>
                <p><?= count(array_filter($tasks, fn($task) => $task['status'] !== 'completed')) ?></p>
            </div>
            <div class="summary-card">
                <h2>Completed</h2>
                <p><?= count(array_filter($tasks, fn($task) => $task['status'] === 'completed')) ?></p>
            </div>
            <div class="summary-card">
                <h2>Overdue</h2>
                <p><?= count(array_filter($tasks, fn($task) => $task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed')) ?></p>
            </div>
            <?php if ($user['role'] !== 'employee'): ?>
                <div class="summary-card">
                    <h2><a class="link-button" href="task_create.php">Create Task</a></h2>
                    <p>&nbsp;</p>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($message): ?>
            <div class="alert success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="panel task-table-panel">
            <div class="task-list-header">
                <div>
                    <h2>Task List</h2>
                </div>
                <form class="search-bar" method="get" action="index.php">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search tasks by title, description, or assignee">
                    <button class="button secondary" type="submit">Search</button>
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Assigned</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['title']) ?></td>
                                <td><?= htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned') ?></td>
                                <td><span class="status <?= $task['status'] ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span></td>
                                <td><?= ucfirst($task['priority']) ?></td>
                                <td><?= $task['due_date'] ?: 'None' ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a class="button small icon-button" href="task_view.php?id=<?= $task['id'] ?>" title="View task">👁️</a>
                                        <?php if ($user['role'] !== 'employee'): ?>
                                            <a class="button small secondary icon-button" href="task_edit.php?id=<?= $task['id'] ?>" title="Edit task">✏️</a>
                                            <form method="post" action="task_delete.php" class="inline-form delete-form">
                                                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                                <button class="button small danger confirm-delete icon-button" type="submit" title="Delete task">🗑️</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tasks)): ?>
                            <tr><td colspan="6">No tasks found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
