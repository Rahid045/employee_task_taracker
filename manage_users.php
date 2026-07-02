<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
if ($user['role'] === 'employee') {
    header('Location: index.php');
    exit;
}

$users = getAllUsers();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_user') {
        $targetId = intval($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'employee';
        $password = trim($_POST['password'] ?? '');

        if ($targetId <= 0 || $name === '' || $email === '') {
            $error = 'Please complete the required fields.';
        } elseif (!in_array($role, ['admin', 'manager', 'employee'], true)) {
            $error = 'Invalid role selected.';
        } else {
            $targetUser = findUserById($targetId);
            if (!$targetUser) {
                $error = 'User not found.';
            } else {
                $sql = 'UPDATE users SET name = :name, email = :email, role = :role';
                $params = [
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'id' => $targetId,
                ];

                if ($password !== '') {
                    $sql .= ', password = :password';
                    $params['password'] = hashPassword($password);
                }

                $sql .= ' WHERE id = :id';
                $stmt = db()->prepare($sql);
                $stmt->execute($params);

                logAuditEvent($user['id'], 'update_user', 'Updated user #' . $targetId, ['target_user_id' => $targetId, 'role' => $role]);
                $success = 'User updated successfully.';
                $users = getAllUsers();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .user-grid { display: grid; gap: 1rem; }
        .user-card { background: #fff; border-radius: 12px; padding: 1rem 1.25rem; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); }
        .user-card h3 { margin: 0 0 0.35rem; }
        .user-meta { color: #64748b; font-size: 0.95rem; }
        .user-actions { margin-top: 0.85rem; display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .inline-form { display: inline-block; }
        .muted { color: #64748b; }
        .form-grid { display: grid; gap: 1rem; }
        .field-group label { display: block; margin-bottom: 0.35rem; font-weight: 600; }
        .field-group input, .field-group select { width: 100%; min-height: 42px; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
            <h1>Manage Users</h1>
        </div>
    </header>

    <main class="container">
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <section class="panel">
            <h2>Existing Users</h2>
            <div class="user-grid">
                <?php foreach ($users as $userItem): ?>
                    <div class="user-card">
                        <h3><?= htmlspecialchars($userItem['name']) ?></h3>
                        <div class="user-meta">Email: <?= htmlspecialchars($userItem['email']) ?></div>
                        <div class="user-meta">Role: <?= htmlspecialchars(ucfirst($userItem['role'])) ?></div>
                        <div class="user-actions">
                            <button class="button secondary" type="button" onclick="document.getElementById('edit-user-<?= $userItem['id'] ?>').style.display = document.getElementById('edit-user-<?= $userItem['id'] ?>').style.display === 'block' ? 'none' : 'block';">Edit</button>
                        </div>
                        <div id="edit-user-<?= $userItem['id'] ?>" style="display:none; margin-top: 1rem;">
                            <form method="post" action="" class="form-grid">
                                <input type="hidden" name="action" value="update_user">
                                <input type="hidden" name="user_id" value="<?= $userItem['id'] ?>">
                                <div class="field-group">
                                    <label for="name-<?= $userItem['id'] ?>">Full Name</label>
                                    <input id="name-<?= $userItem['id'] ?>" type="text" name="name" value="<?= htmlspecialchars($userItem['name']) ?>" required>
                                </div>
                                <div class="field-group">
                                    <label for="email-<?= $userItem['id'] ?>">Email</label>
                                    <input id="email-<?= $userItem['id'] ?>" type="email" name="email" value="<?= htmlspecialchars($userItem['email']) ?>" required>
                                </div>
                                <div class="field-group">
                                    <label for="role-<?= $userItem['id'] ?>">Role</label>
                                    <select id="role-<?= $userItem['id'] ?>" name="role">
                                        <option value="admin" <?= $userItem['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="manager" <?= $userItem['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                                        <option value="employee" <?= $userItem['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                                    </select>
                                </div>
                                <div class="field-group">
                                    <label for="password-<?= $userItem['id'] ?>">New Password (leave blank to keep current)</label>
                                    <input id="password-<?= $userItem['id'] ?>" type="password" name="password">
                                </div>
                                <div class="user-actions">
                                    <button class="button primary" type="submit">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
