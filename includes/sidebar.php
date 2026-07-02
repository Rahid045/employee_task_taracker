<?php
/**
 * Sidebar Navigation Component
 * Include this in any page that needs the sidebar
 */
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(241, 250, 238, 0.12);
    }

    .sidebar-header h2 {
        margin: 0;
        font-size: 1.1rem;
        color: #f1faee;
        white-space: nowrap;
    }

    .sidebar-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
        cursor: pointer;
        padding: 0;
        position: relative;
    }

    .sidebar-toggle span {
        display: block;
        width: 18px;
        height: 2px;
        background: #f1faee;
        border-radius: 2px;
        position: absolute;
        transition: all 0.3s ease;
    }

    .sidebar-toggle span:nth-child(1) { transform: translateY(-6px); }
    .sidebar-toggle span:nth-child(3) { transform: translateY(6px); }

    body.sidebar-closed .sidebar-toggle span:nth-child(1) { transform: rotate(45deg); }
    body.sidebar-closed .sidebar-toggle span:nth-child(2) { opacity: 0; }
    body.sidebar-closed .sidebar-toggle span:nth-child(3) { transform: rotate(-45deg); }

    .sidebar-user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(241, 250, 238, 0.12);
        background: rgba(0, 0, 0, 0.12);
    }

    .sidebar-user .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #457b9d;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
        overflow: hidden;
    }

    .sidebar-user .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .sidebar-user .user-details {
        overflow: hidden;
    }

    .sidebar-user .user-name,
    .sidebar-user .user-role {
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sidebar-user .user-name {
        color: #f1faee;
        font-weight: 600;
    }

    .sidebar-user .user-role {
        color: #a8c5dd;
        font-size: 0.8rem;
    }

    body.sidebar-closed .sidebar-user .user-details {
        opacity: 0;
        width: 0;
    }
</style>
 
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Task Tracker</h2>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <?php if (!empty($_SESSION['user']['profile_picture'] ?? '')): ?>
                <img src="<?= htmlspecialchars($_SESSION['user']['profile_picture']) ?>" alt="Profile picture">
            <?php else: ?>
                <?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div class="user-details">
            <p class="user-name"><?= htmlspecialchars(substr($_SESSION['user']['name'], 0, 15)) ?></p>
            <p class="user-role"><?= ucfirst($_SESSION['user']['role']) ?></p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <?php if ($_SESSION['user']['role'] !== 'employee'): ?>
               
                <li class="nav-item">
                    <a href="create_user.php" class="nav-link <?= $current_page === 'create_user.php' ? 'active' : '' ?>">
                        <span class="nav-icon">➕</span>
                        <span class="nav-text">Add New user</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="manage_users.php" class="nav-link <?= $current_page === 'manage_users.php' ? 'active' : '' ?>">
                        <span class="nav-icon">👥</span>
                        <span class="nav-text">Manage Users</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="audit_logs.php" class="nav-link <?= $current_page === 'audit_logs.php' ? 'active' : '' ?>">
                        <span class="nav-icon">🧾</span>
                        <span class="nav-text">Audit Logs</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="project_create.php" class="nav-link <?= $current_page === 'project_create.php' ? 'active' : '' ?>">
                        <span class="nav-icon">📁</span>
                        <span class="nav-text">New Project</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a href="project_task_management.php" class="nav-link <?= $current_page === 'project_task_management.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🎯</span>
                    <span class="nav-text">Projects & Tasks</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="reports.php" class="nav-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">Reports</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="notifications.php" class="nav-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🔔</span>
                    <span class="nav-text">Notifications</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="user_profile.php" class="nav-link <?= $current_page === 'user_profile.php' ? 'active' : '' ?>">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">Profile</span>
                </a>
            </li>

            <li class="nav-divider"></li>

            <li class="nav-item">
                <a href="logout.php" class="nav-link nav-logout">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
