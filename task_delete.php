<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
if ($user['role'] === 'employee') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = intval($_POST['id'] ?? 0);
    if ($taskId > 0) {
        // Get the task before deleting to notify the assigned user
        $task = findTaskById($taskId);
        if ($task && $task['assigned_to']) {
            // Notify the user that their assigned task was deleted
            createNotification(
                $task['assigned_to'],
                'warning',
                'Task Deleted',
                'The task "' . htmlspecialchars($task['title']) . '" has been deleted.'
            );
        }
        deleteTask($taskId);
        logAuditEvent($user['id'], 'delete_task', 'Deleted task #' . $taskId, ['task_title' => $task['title'] ?? '']);
        $_SESSION['flash_message'] = 'Task deleted successfully.';
    }
}

header('Location: index.php');
exit;
