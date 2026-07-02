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
$project = findProjectById($id);
if (!$project) {
    header('Location: project_task_management.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($name === '') {
        $error = 'Project name is required.';
        $project = array_merge($project, $_POST);
    } else {
        $stmt = db()->prepare(
            'UPDATE projects
             SET name = :name, description = :description, status = :status, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'id' => $id,
        ]);

        logAuditEvent($user['id'], 'update_project', 'Updated project #' . $id, ['project_name' => $name]);
        header('Location: project_task_management.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Employee Task Tracker</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
            <img src="images/logo.png" alt="logo" class="login-logo">
            <h1>Edit Project</h1>
        </div>
    </header>

    <main class="container form-page">
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label for="name">Project Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($project['name']) ?>" required>

            <label for="description">Description</label>
            <textarea id="description" name="description"><?= htmlspecialchars($project['description']) ?></textarea>

            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active" <?= $project['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="on_hold" <?= $project['status'] === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                <option value="completed" <?= $project['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap;">
                <button class="button primary" type="submit">Save Changes</button>
                <a href="project_task_management.php" class="button secondary">Cancel</a>
            </div>
        </form>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
