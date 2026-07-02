<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

if ($_SESSION['user']['role'] === 'employee') {
    header('Location: index.php');
    exit;
}

$logs = getAuditLogs(200);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .log-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; }
        .log-table th, .log-table td { border-bottom: 1px solid #e2e8f0; padding: 0.8rem; text-align: left; }
        .log-table th { background: #f8fafc; }
        .log-table tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-size: 0.8rem; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
            <h1>Audit Logs</h1>
        </div>
    </header>

    <main class="container">
        <section class="panel">
            <h2>System Activity</h2>
            <p class="muted">This log shows login activity and administrative actions with the time, user, and details.</p>
            <div class="table-responsive">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                                <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?><br><span class="muted"><?= htmlspecialchars($log['user_email'] ?? '') ?></span></td>
                                <td><span class="badge"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td><?= htmlspecialchars($log['details'] ?? '') ?></td>
                                <td><?= htmlspecialchars($log['ip_address'] ?? 'unknown') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5">No audit entries yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
