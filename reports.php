<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
$tasks = getTasksForUser($user);

// Calculate statistics
$total_tasks = count($tasks);
$completed_tasks = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
$in_progress_tasks = count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress'));
$blocked_tasks = count(array_filter($tasks, fn($t) => $t['status'] === 'blocked'));
$new_tasks = count(array_filter($tasks, fn($t) => $t['status'] === 'new'));
$completion_rate = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

// Overdue tasks
$overdue_tasks = count(array_filter($tasks, fn($t) => $t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['status'] !== 'completed'));

// High priority tasks
$high_priority = count(array_filter($tasks, fn($t) => $t['priority'] === 'high'));

// Group by priority
$low_priority = count(array_filter($tasks, fn($t) => $t['priority'] === 'low'));
$medium_priority = count(array_filter($tasks, fn($t) => $t['priority'] === 'medium'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Employee Task Tracker</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .report-card {
            background: linear-gradient(135deg, #457b9d 0%, #2e5266 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(35, 47, 60, 0.15);
            text-align: center;
        }

        .report-card h4 {
            margin: 0 0 0.5rem;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .report-card .value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .report-card .label {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(35, 47, 60, 0.08);
            margin-bottom: 2rem;
        }

        .chart-container h3 {
            margin-top: 0;
            color: #1d3557;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }

        .detailed-table {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(35, 47, 60, 0.08);
        }

        .detailed-table h3 {
            margin-top: 0;
            color: #1d3557;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge.new { background: #e3f2fd; color: #1565c0; }
        .status-badge.in_progress { background: #fff3e0; color: #f57c00; }
        .status-badge.blocked { background: #ffebee; color: #c62828; }
        .status-badge.completed { background: #e8f5e9; color: #1b5e20; }

        .priority-badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .priority-badge.low { background: #e8f5e9; color: #1b5e20; }
        .priority-badge.medium { background: #fff3e0; color: #f57c00; }
        .priority-badge.high { background: #ffebee; color: #c62828; }

        .summary-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(35, 47, 60, 0.08);
            margin-bottom: 2rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
            color: #333;
        }

        .summary-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #457b9d;
        }

        .alert-box {
            background: #ffe3e3;
            border-left: 4px solid #e63946;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-box strong {
            color: #9f3a38;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
             <img src="images/logo.png" alt="logo" class="login-logo">
            <h1>Reports & Analytics</h1>
        </div>
    </header>

    <main class="container">
        <!-- Overview Cards -->
        <div class="reports-grid">
            <div class="report-card">
                <h4>Total Tasks</h4>
                <div class="value"><?= $total_tasks ?></div>
                <div class="label">All time</div>
            </div>
            <div class="report-card">
                <h4>Completed</h4>
                <div class="value"><?= $completed_tasks ?></div>
                <div class="label"><?= $completion_rate ?>% complete</div>
            </div>
            <div class="report-card">
                <h4>In Progress</h4>
                <div class="value"><?= $in_progress_tasks ?></div>
                <div class="label">Active work</div>
            </div>
            <div class="report-card">
                <h4>Overdue</h4>
                <div class="value" style="color: #ff6b6b;"><?= $overdue_tasks ?></div>
                <div class="label">Needs attention</div>
            </div>
        </div>

        <!-- Alert for overdue tasks -->
        <?php if ($overdue_tasks > 0): ?>
            <div class="alert-box">
                <strong>⚠️ Warning:</strong> You have <strong><?= $overdue_tasks ?></strong> overdue task(s) that need attention.
            </div>
        <?php endif; ?>

        <!-- Charts -->
        <div class="chart-container">
            <h3>Task Status Distribution</h3>
            <div class="chart-wrapper">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h3>Task Priority Distribution</h3>
            <div class="chart-wrapper">
                <canvas id="priorityChart"></canvas>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="summary-section">
            <h3 style="margin-top: 0;">Task Summary</h3>
            <div class="summary-item">
                <span class="summary-label">New Tasks</span>
                <span class="summary-value"><?= $new_tasks ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">In Progress</span>
                <span class="summary-value"><?= $in_progress_tasks ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Blocked Tasks</span>
                <span class="summary-value"><?= $blocked_tasks ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Completed Tasks</span>
                <span class="summary-value"><?= $completed_tasks ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">High Priority Tasks</span>
                <span class="summary-value"><?= $high_priority ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Completion Rate</span>
                <span class="summary-value"><?= $completion_rate ?>%</span>
            </div>
        </div>

        <!-- Recent Tasks Table -->
        <div class="detailed-table">
            <h3 style="margin-top: 0;">Recent Tasks</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($tasks, 0, 10) as $task): ?>
                            <tr>
                                <td><?= htmlspecialchars(substr($task['title'], 0, 40)) ?></td>
                                <td><span class="status-badge <?= $task['status'] ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span></td>
                                <td><span class="priority-badge <?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span></td>
                                <td><?= $task['due_date'] ?? '-' ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 60px; height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden;">
                                            <div style="width: <?= $task['status'] === 'completed' ? '100' : ($task['status'] === 'in_progress' ? '50' : '25') ?>%; height: 100%; background: #457b9d;"></div>
                                        </div>
                                        <span style="font-size: 0.8rem;"><?= $task['status'] === 'completed' ? '100' : ($task['status'] === 'in_progress' ? '50' : '25') ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="assets/app.js"></script>
    <script>
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['New', 'In Progress', 'Blocked', 'Completed'],
                datasets: [{
                    data: [<?= $new_tasks ?>, <?= $in_progress_tasks ?>, <?= $blocked_tasks ?>, <?= $completed_tasks ?>],
                    backgroundColor: ['#4a90e2', '#f4a261', '#e63946', '#2a9d8f'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Priority Chart
        const priorityCtx = document.getElementById('priorityChart').getContext('2d');
        new Chart(priorityCtx, {
            type: 'bar',
            data: {
                labels: ['Low', 'Medium', 'High'],
                datasets: [{
                    label: 'Number of Tasks',
                    data: [<?= $low_priority ?>, <?= $medium_priority ?>, <?= $high_priority ?>],
                    backgroundColor: ['#2a9d8f', '#f4a261', '#e63946'],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
