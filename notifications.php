<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$pdo = new PDO(
    "mysql:host=localhost;dbname=employee_task_tracker",
    "root",
    ""
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = $_SESSION['user'];
$tasks = getTasksForUser($user);
// Fetch stored notifications for this user
$stmt = $pdo->prepare("SELECT id, type, title, message, created_at as timestamp FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$dbNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Your existing generated notifications...
$notifications = [];

// ...your overdue/high_priority/new_assigned/completed/system code stays here...

// Merge DB notifications + generated ones
$notifications = array_merge($dbNotifications, $notifications);

// Sort by most recent first
usort($notifications, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Generate notifications based on tasks
$notifications = [];

// Check for overdue tasks
$overdue = array_filter($tasks, fn($t) => $t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['status'] !== 'completed');
foreach ($overdue as $task) {
    $days_overdue = (int)((time() - strtotime($task['due_date'])) / (60 * 60 * 24));
    $notifications[] = [
        'id' => 'overdue_' . $task['id'],
        'type' => 'warning',
        'title' => 'Overdue Task',
        'message' => '"' . htmlspecialchars(substr($task['title'], 0, 40)) . '" is ' . $days_overdue . ' day(s) overdue.',
        'timestamp' => $task['due_date'],
        'icon' => '⏰'
    ];
}

// Check for high priority tasks due soon
$high_priority_soon = array_filter($tasks, function($t) {
    if ($t['priority'] !== 'high' || $t['status'] === 'completed' || !$t['due_date']) return false;
    $days_until_due = (int)((strtotime($t['due_date']) - time()) / (60 * 60 * 24));
    return $days_until_due >= 0 && $days_until_due <= 3;
});
foreach ($high_priority_soon as $task) {
    $days_until = (int)((strtotime($task['due_date']) - time()) / (60 * 60 * 24));
    $notifications[] = [
        'id' => 'urgent_' . $task['id'],
        'type' => 'urgent',
        'title' => 'Urgent Task Due Soon',
        'message' => 'High priority task "' . htmlspecialchars(substr($task['title'], 0, 40)) . '" is due in ' . ($days_until + 1) . ' day(s).',
        'timestamp' => $task['due_date'],
        'icon' => '🔥'
    ];
}

// Check for newly created tasks assigned to user
$new_assigned = array_filter($tasks, fn($t) => $t['status'] === 'new');
if (!empty($new_assigned)) {
    $notifications[] = [
        'id' => 'new_tasks',
        'type' => 'info',
        'title' => 'New Tasks Assigned',
        'message' => 'You have ' . count($new_assigned) . ' new task(s) assigned to you.',
        'timestamp' => date('Y-m-d H:i:s'),
        'icon' => '📋'
    ];
}

// Tasks completed this week (for motivation)
$completed_count = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
if ($completed_count > 0) {
    $notifications[] = [
        'id' => 'achievement',
        'type' => 'success',
        'title' => 'Achievement Unlocked',
        'message' => 'Great work! You have completed ' . $completed_count . ' task(s).',
        'timestamp' => date('Y-m-d H:i:s'),
        'icon' => '🎉'
    ];
}

// System notifications
$notifications[] = [
    'id' => 'system_1',
    'type' => 'info',
    'title' => 'System Update',
    'message' => 'The task tracker system was updated with new features.',
    'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
    'icon' => '⚙️'
];

// Sort by most recent first
usort($notifications, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'clear_all') {
        // In a real app, this would clear notifications from database
        $notifications = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Employee Task Tracker</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .notifications-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #d8dee9;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #457b9d;
            color: white;
            border-color: #457b9d;
        }

        .notification-item {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #d8dee9;
            box-shadow: 0 2px 8px rgba(35, 47, 60, 0.05);
            transition: all 0.3s ease;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .notification-item:hover {
            box-shadow: 0 4px 12px rgba(35, 47, 60, 0.1);
            transform: translateX(4px);
        }

        .notification-item.warning {
            border-left-color: #f4a261;
            background: #fffbf7;
        }

        .notification-item.urgent {
            border-left-color: #e63946;
            background: #ffe3e3;
        }

        .notification-item.info {
            border-left-color: #457b9d;
            background: #f0f4f8;
        }

        .notification-item.success {
            border-left-color: #2a9d8f;
            background: #e6f7f3;
        }

        .notification-icon {
            font-size: 1.8rem;
            flex-shrink: 0;
            min-width: 40px;
            text-align: center;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 700;
            color: #1d3557;
            margin: 0 0 0.25rem;
            font-size: 1rem;
        }

        .notification-message {
            color: #555;
            margin: 0 0 0.5rem;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #999;
        }

        .notification-action {
            flex-shrink: 0;
            display: flex;
            gap: 0.5rem;
        }

        .notification-action button {
            padding: 0.4rem 0.8rem;
            border: none;
            background: #e9ecef;
            color: #333;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .notification-action button:hover {
            background: #d3d6db;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #999;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .notification-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(35, 47, 60, 0.05);
        }

        .stat-box-value {
            font-size: 2rem;
            font-weight: 700;
            color: #457b9d;
        }

        .stat-box-label {
            font-size: 0.9rem;
            color: #999;
            margin-top: 0.25rem;
        }

        .clear-all-btn {
            padding: 0.65rem 1.2rem;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
             <img src="images/logo.png" alt="logo" class="login-logo">
            <h1>Notifications</h1>
        </div>
    </header>

    <main class="container">
        <!-- Notification Statistics -->
        <div class="notification-stats">
            <div class="stat-box">
                <div class="stat-box-value"><?= count($notifications) ?></div>
                <div class="stat-box-label">Total Notifications</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-value"><?= count(array_filter($notifications, fn($n) => $n['type'] === 'urgent')) ?></div>
                <div class="stat-box-label">Urgent</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-value"><?= count(array_filter($notifications, fn($n) => $n['type'] === 'warning')) ?></div>
                <div class="stat-box-label">Warnings</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-value"><?= count(array_filter($notifications, fn($n) => $n['type'] === 'success')) ?></div>
                <div class="stat-box-label">Achievements</div>
            </div>
        </div>

        <!-- Notification Controls -->
        <div class="notifications-header">
            <div class="notifications-filters">
                <button class="filter-btn active" onclick="filterNotifications('all')">All</button>
                <button class="filter-btn" onclick="filterNotifications('urgent')">Urgent</button>
                <button class="filter-btn" onclick="filterNotifications('warning')">Warnings</button>
                <button class="filter-btn" onclick="filterNotifications('success')">Achievements</button>
            </div>
            <?php if (!empty($notifications)): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="clear_all">
                    <button type="submit" class="button secondary clear-all-btn" onclick="return confirm('Clear all notifications?')">Clear All</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Notifications List -->
        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>No Notifications</h3>
                    <p>You're all caught up! No new notifications at this time.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= $notification['type'] ?>" data-type="<?= $notification['type'] ?>">
                        <div class="notification-icon"><?= $notification['icon'] ?></div>
                        <div class="notification-content">
                            <p class="notification-title"><?= htmlspecialchars($notification['title']) ?></p>
                            <p class="notification-message"><?= $notification['message'] ?></p>
                            <span class="notification-time"><?= date('M d, Y \a\t g:i A', strtotime($notification['timestamp'])) ?></span>
                        </div>
                        <div class="notification-action">
                            <button onclick="deleteNotification('<?= $notification['id'] ?>')">Dismiss</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Notification Preferences -->
        <section class="panel" style="margin-top: 2rem;">
            <h3>Notification Preferences</h3>
            <div style="display: grid; gap: 1rem;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" checked style="width: auto; margin-right: 0.75rem; cursor: pointer;">
                    <span>Email notifications for overdue tasks</span>
                </label>
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" checked style="width: auto; margin-right: 0.75rem; cursor: pointer;">
                    <span>Email notifications for new task assignments</span>
                </label>
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" style="width: auto; margin-right: 0.75rem; cursor: pointer;">
                    <span>Daily digest of all notifications</span>
                </label>
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" checked style="width: auto; margin-right: 0.75rem; cursor: pointer;">
                    <span>Urgent notifications (high priority tasks)</span>
                </label>
                <div style="margin-top: 1rem;">
                    <button class="button primary">Save Preferences</button>
                </div>
            </div>
        </section>
    </main>

    <script src="assets/app.js"></script>
    <script>
        function filterNotifications(type) {
            const notifications = document.querySelectorAll('.notification-item');
            const buttons = document.querySelectorAll('.filter-btn');

            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Filter notifications
            notifications.forEach(notification => {
                if (type === 'all') {
                    notification.style.display = 'flex';
                } else if (notification.dataset.type === type) {
                    notification.style.display = 'flex';
                } else {
                    notification.style.display = 'none';
                }
            });
        }

        function deleteNotification(id) {
            const notification = document.querySelector(`[data-id="${id}"]`);
            if (notification) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    notification.remove();
                    // Check if any notifications remain
                    const remaining = document.querySelectorAll('.notification-item');
                    if (remaining.length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        }
    </script>
</body>
</html>
