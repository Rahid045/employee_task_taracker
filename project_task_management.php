<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
$projects = getAllProjects();
$tasks = getTasksForUser($user);
$canManageProjects = $user['role'] !== 'employee';

$tasksByProject = [];
$unassignedTasks = [];
foreach ($tasks as $task) {
    if (!empty($task['project_id'])) {
        $tasksByProject[(int) $task['project_id']][] = $task;
    } else {
        $unassignedTasks[] = $task;
    }
}

$activeTasks = count(array_filter($tasks, fn($task) => $task['status'] !== 'completed'));
$completedTasks = count(array_filter($tasks, fn($task) => $task['status'] === 'completed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects & Tasks - Employee Task Tracker</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .project-toolbar,
        .project-card-header,
        .project-actions,
        .task-table-heading {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .project-toolbar,
        .project-card-header,
        .task-table-heading {
            justify-content: space-between;
        }

        .project-stack {
            display: grid;
            gap: 1.25rem;
        }

        .project-card {
            border: 1px solid #e9ecef;
            border-left: 5px solid #457b9d;
            border-radius: 8px;
            padding: 1.25rem;
            background: #fff;
        }

        .project-card h3,
        .task-table-heading h3 {
            margin: 0;
            color: #1d3557;
        }

        .project-description {
            margin: 0.75rem 0;
            color: #495057;
            line-height: 1.5;
        }

        .project-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .project-status {
            display: inline-flex;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .project-status.active {
            background: #e6ffed;
            color: #1f7a3a;
        }

        .project-status.on_hold {
            background: #fff3cd;
            color: #7a5b00;
        }

        .project-status.completed {
            background: #e6f0ff;
            color: #1d4f91;
        }

        .project-task-table {
            margin-top: 1rem;
        }

        .empty-state {
            padding: 1rem;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
            <img src="images/logo.png" alt="logo" class="login-logo">
            <h1>Projects & Tasks</h1>
        </div>
    </header>

    <main class="container">
        <section class="summary-panel">
            <div class="summary-card">
                <h2>Total Projects</h2>
                <p><?= count($projects) ?></p>
            </div>
            <div class="summary-card">
                <h2>Active Tasks</h2>
                <p><?= $activeTasks ?></p>
            </div>
            <div class="summary-card">
                <h2>Completed Tasks</h2>
                <p><?= $completedTasks ?></p>
            </div>
        </section>

        <section class="panel">
            <div class="project-toolbar">
                <h2>Created Projects</h2>
                <?php if ($canManageProjects): ?>
                    <a href="project_create.php" class="button primary">New Project</a>
                <?php endif; ?>
            </div>

            <?php if (empty($projects)): ?>
                <div class="empty-state">No projects have been created yet.</div>
            <?php else: ?>
                <div class="project-stack">
                    <?php foreach ($projects as $project): ?>
                        <?php $projectTasks = $tasksByProject[(int) $project['id']] ?? []; ?>
                        <article class="project-card">
                            <div class="project-card-header">
                                <div>
                                    <h3><?= htmlspecialchars($project['name']) ?></h3>
                                    <span class="project-status <?= htmlspecialchars($project['status']) ?>">
                                        <?= ucwords(str_replace('_', ' ', $project['status'])) ?>
                                    </span>
                                </div>

                                <?php if ($canManageProjects): ?>
                                    <div class="project-actions">
                                        <a href="project_edit.php?id=<?= $project['id'] ?>" class="button small">Edit</a>
                                        <a href="project_delete.php?id=<?= $project['id'] ?>" class="button small danger" onclick="return confirm('Delete this project? Tasks under it will stay but become unassigned.');">Delete</a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <p class="project-description"><?= nl2br(htmlspecialchars($project['description'] ?: 'No description')) ?></p>
                            <div class="project-meta">
                                Created <?= date('M d, Y', strtotime($project['created_at'])) ?>
                                <?php if (!empty($project['created_by_name'])): ?>
                                    by <?= htmlspecialchars($project['created_by_name']) ?>
                                <?php endif; ?>
                                | <?= count($projectTasks) ?> task<?= count($projectTasks) === 1 ? '' : 's' ?>
                            </div>

                            <div class="project-task-table table-responsive">
                                <?php if (empty($projectTasks)): ?>
                                    <div class="empty-state">No tasks are under this project yet.</div>
                                <?php else: ?>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Assigned To</th>
                                                <th>Status</th>
                                                <th>Priority</th>
                                                <th>Due Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($projectTasks as $task): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($task['title']) ?></td>
                                                    <td><?= htmlspecialchars($task['assigned_to_name'] ?: 'Unassigned') ?></td>
                                                    <td><span class="status <?= htmlspecialchars($task['status']) ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span></td>
                                                    <td><?= ucfirst($task['priority']) ?></td>
                                                    <td><?= $task['due_date'] ?: '-' ?></td>
                                                    <td>
                                                        <a class="button small" href="task_view.php?id=<?= $task['id'] ?>">View</a>
                                                        <?php if ($canManageProjects): ?>
                                                            <a class="button small secondary" href="task_edit.php?id=<?= $task['id'] ?>">Edit</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($unassignedTasks)): ?>
            <section class="panel">
                <div class="task-table-heading">
                    <h3>Unassigned Tasks</h3>
                    <span class="project-meta"><?= count($unassignedTasks) ?> task<?= count($unassignedTasks) === 1 ? '' : 's' ?></span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unassignedTasks as $task): ?>
                                <tr>
                                    <td><?= htmlspecialchars($task['title']) ?></td>
                                    <td><?= htmlspecialchars($task['assigned_to_name'] ?: 'Unassigned') ?></td>
                                    <td><span class="status <?= htmlspecialchars($task['status']) ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span></td>
                                    <td><?= ucfirst($task['priority']) ?></td>
                                    <td><?= $task['due_date'] ?: '-' ?></td>
                                    <td>
                                        <a class="button small" href="task_view.php?id=<?= $task['id'] ?>">View</a>
                                        <?php if ($canManageProjects): ?>
                                            <a class="button small secondary" href="task_edit.php?id=<?= $task['id'] ?>">Edit</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
