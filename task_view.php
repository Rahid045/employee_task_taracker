<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
$taskId = intval($_GET['id'] ?? 0);
$task = findTaskById($taskId);
if (!$task) {
    header('Location: index.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'comment') {
        $result = addCommentToTask($taskId, $user['id'], $_POST['comment_body'] ?? '');
        $message = $result['message'];
    } elseif (isset($_POST['action']) && $_POST['action'] === 'time_entry') {
        $result = addTimeEntryToTask($taskId, $user['id'], $_POST['hours'] ?? 0, $_POST['notes'] ?? '');
        $message = $result['message'];
        $task = findTaskById($taskId);
    }
}

$comments = getCommentsForTask($taskId);
$timeEntries = getTimeEntriesForTask($taskId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Task</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
            <h1><?= htmlspecialchars($task['title']) ?></h1>
        </div>
    </header>

    <main class="container">

        <section class="panel">
            <div class="grid-2">
                <div>
                    <h2>Task Details</h2>
                    <p><?= nl2br(htmlspecialchars($task['description'] ?: 'No description')) ?></p>
                </div>
                <div class="details-box">
                    <p><strong>Assigned To:</strong> <?= htmlspecialchars($task['assigned_to_name'] ?: 'Unassigned') ?></p>
                    <p><strong>Project:</strong> <?= htmlspecialchars($task['project_name'] ?: 'No project') ?></p>
                    <p><strong>Status:</strong> <?= ucfirst(str_replace('_', ' ', $task['status'])) ?></p>
                    <p><strong>Priority:</strong> <?= ucfirst($task['priority']) ?></p>
                    <p><strong>Start Date:</strong> <?= $task['start_date'] ?: 'None' ?></p>
                    <p><strong>Due Date:</strong> <?= $task['due_date'] ?: 'None' ?></p>
                    <p><strong>Estimated Hours:</strong> <?= $task['estimated_hours'] ?: '0' ?></p>
                    <p><strong>Actual Hours:</strong> <?= $task['actual_hours'] ?: '0' ?></p>
                </div>
            </div>
        </section>

        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="panel">
            <h2>Comments</h2>
            <?php if (empty($comments)): ?>
                <p>No comments yet.</p>
            <?php else: ?>
                <ul class="comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <li>
                            <strong><?= htmlspecialchars($comment['user_name']) ?>:</strong>
                            <p><?= nl2br(htmlspecialchars($comment['body'])) ?></p>
                            <small><?= $comment['created_at'] ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post" action="" class="inline-form">
                <input type="hidden" name="action" value="comment">
                <label>Add a comment</label>
                <textarea name="comment_body" required></textarea>
                <button class="button primary" type="submit">Post Comment</button>
            </form>
        </section>

        <section class="panel">
            <h2>Time Entries</h2>
            <?php if (empty($timeEntries)): ?>
                <p>No time entries recorded.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Hours</th>
                            <th>Notes</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeEntries as $entry): ?>
                            <tr>
                                <td><?= htmlspecialchars($entry['user_name']) ?></td>
                                <td><?= htmlspecialchars($entry['hours']) ?></td>
                                <td><?= htmlspecialchars($entry['notes']) ?></td>
                                <td><?= $entry['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <form method="post" action="" class="inline-form">
                <input type="hidden" name="action" value="time_entry">
                <label>Hours</label>
                <input type="number" step="0.25" name="hours" required>
                <label>Notes</label>
                <textarea name="notes"></textarea>
                <button class="button primary" type="submit">Add Time</button>
            </form>
        </section>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
