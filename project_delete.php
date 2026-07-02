<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
if ($user['role'] === 'employee') {
    header('Location: project_task_management.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $project = findProjectById($id);
    $stmt = db()->prepare('DELETE FROM projects WHERE id = :id');
    $stmt->execute(['id' => $id]);

    logAuditEvent($user['id'], 'delete_project', 'Deleted project #' . $id, [
        'project_name' => $project['name'] ?? '',
    ]);
}

header('Location: project_task_management.php');
exit;
