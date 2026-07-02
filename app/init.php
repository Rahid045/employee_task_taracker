<?php

function db(): PDO
{
    static $pdo;
    if ($pdo) {
        return $pdo;
    }

    $dsn = 'mysql:host=127.0.0.1;dbname=employee_task_tracker;charset=utf8mb4';
    $user = 'root';
    $password = '';

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function setNoCacheHeaders(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

function requireLogin(): void
{
    setNoCacheHeaders();
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function destroyUserSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies') && isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }

    session_destroy();
    setNoCacheHeaders();
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyStoredPassword(string $password, string $storedHash): bool
{
    if ($storedHash === '') {
        return false;
    }

    if ($storedHash === $password) {
        return true;
    }

    if (preg_match('/^[a-f0-9]{32}$/i', $storedHash)) {
        return md5($password) === $storedHash;
    }

    return password_verify($password, $storedHash);
}

function getClientIpAddress(): string
{
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $value = $_SERVER[$key];
            if (is_string($value)) {
                return explode(',', $value)[0];
            }
        }
    }

    return 'unknown';
}

function logAuditEvent(?int $userId, string $action, string $details = '', ?array $context = null): void
{
    try {
        $detailsText = trim($details);
        if ($context && is_array($context)) {
            $extras = [];
            foreach ($context as $key => $value) {
                $extras[] = $key . '=' . (is_scalar($value) ? (string) $value : json_encode($value));
            }

            if ($detailsText !== '') {
                $detailsText .= ' | ' . implode(', ', $extras);
            } else {
                $detailsText = implode(', ', $extras);
            }
        }

        $stmt = db()->prepare(
            'INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent, created_at)
             VALUES (:user_id, :action, :details, :ip_address, :user_agent, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'details' => $detailsText,
            'ip_address' => getClientIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
    } catch (Throwable $e) {
        // Ignore audit logging errors so they do not break the app.
    }
}

function getAuditLogs(int $limit = 100): array
{
    try {
        $stmt = db()->prepare(
            'SELECT a.*, u.name AS user_name, u.email AS user_email
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             ORDER BY a.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getAllUsers(): array
{
    return db()->query('SELECT id, name, email, role FROM users ORDER BY name')->fetchAll();
}

function getAllProjects(): array
{
    return db()->query(
        'SELECT p.*, u.name AS created_by_name
         FROM projects p
         LEFT JOIN users u ON p.created_by = u.id
         ORDER BY p.created_at DESC'
    )->fetchAll();
}

function findProjectById(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM projects WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function findUserByEmail(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    return $stmt->fetch() ?: null;
}

function findUserById(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function getTasksForUser(array $user, ?string $search = null): array
{
    $search = trim($search ?? '');
    $hasSearch = $search !== '';
    $params = [];

    if ($user['role'] === 'employee') {
        $query = 'SELECT t.*, u.name AS assigned_to_name, p.name AS project_name FROM tasks t
             LEFT JOIN users u ON t.assigned_to = u.id
             LEFT JOIN projects p ON t.project_id = p.id
             WHERE t.assigned_to = :user_id';
        $params['user_id'] = $user['id'];
    } else {
        $query = 'SELECT t.*, u.name AS assigned_to_name, p.name AS project_name FROM tasks t
             LEFT JOIN users u ON t.assigned_to = u.id
             LEFT JOIN projects p ON t.project_id = p.id
             WHERE 1=1';
    }

    if ($hasSearch) {
        $query .= ' AND (t.title LIKE :search OR t.description LIKE :search OR u.name LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $query .= ' ORDER BY t.created_at DESC, t.due_date IS NULL, t.due_date ASC';
    $stmt = db()->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function deleteTask(int $id): bool
{
    $stmt = db()->prepare('DELETE FROM tasks WHERE id = :id');
    return $stmt->execute(['id' => $id]);
}

function findTaskById(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT t.*, u.name AS assigned_to_name, p.name AS project_name FROM tasks t
         LEFT JOIN users u ON t.assigned_to = u.id
         LEFT JOIN projects p ON t.project_id = p.id
         WHERE t.id = :id'
    );
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function createTask(array $data, array $user): array
{
    $title = trim($data['title'] ?? '');
    if ($title === '') {
        return ['success' => false, 'message' => 'Title is required.'];
    }

    $assignedTo = $data['assigned_to'] ? intval($data['assigned_to']) : null;
    $projectId = !empty($data['project_id']) ? intval($data['project_id']) : null;
    $status = in_array($data['status'] ?? '', ['new', 'in_progress', 'blocked', 'completed'], true) ? $data['status'] : 'new';
    $priority = in_array($data['priority'] ?? '', ['low', 'medium', 'high'], true) ? $data['priority'] : 'medium';
    $startDate = $data['start_date'] ?: null;
    $dueDate = $data['due_date'] ?: null;
    $estimatedHours = $data['estimated_hours'] !== '' ? floatval($data['estimated_hours']) : null;

    $stmt = db()->prepare(
        'INSERT INTO tasks (title, description, created_by, project_id, assigned_to, status, priority, start_date, due_date, estimated_hours, actual_hours, created_at, updated_at)
         VALUES (:title, :description, :created_by, :project_id, :assigned_to, :status, :priority, :start_date, :due_date, :estimated_hours, 0, NOW(), NOW())'
    );
    $stmt->execute([
        'title' => $title,
        'description' => trim($data['description'] ?? ''),
        'created_by' => $user['id'],
        'project_id' => $projectId,
        'assigned_to' => $assignedTo,
        'status' => $status,
        'priority' => $priority,
        'start_date' => $startDate,
        'due_date' => $dueDate,
        'estimated_hours' => $estimatedHours,
    ]);

    return ['success' => true, 'message' => 'Task created successfully.'];
}

function updateTask(int $id, array $data): array
{
    $task = findTaskById($id);
    if (!$task) {
        return ['success' => false, 'message' => 'Task not found.'];
    }

    $title = trim($data['title'] ?? '');
    if ($title === '') {
        return ['success' => false, 'message' => 'Title is required.'];
    }

    $assignedTo = $data['assigned_to'] ? intval($data['assigned_to']) : null;
    $projectId = !empty($data['project_id']) ? intval($data['project_id']) : null;
    $status = in_array($data['status'] ?? '', ['new', 'in_progress', 'blocked', 'completed'], true) ? $data['status'] : $task['status'];
    $priority = in_array($data['priority'] ?? '', ['low', 'medium', 'high'], true) ? $data['priority'] : $task['priority'];
    $startDate = $data['start_date'] ?: null;
    $dueDate = $data['due_date'] ?: null;
    $estimatedHours = $data['estimated_hours'] !== '' ? floatval($data['estimated_hours']) : null;
    $actualHours = $data['actual_hours'] !== '' ? floatval($data['actual_hours']) : $task['actual_hours'];

    $stmt = db()->prepare(
        'UPDATE tasks SET title = :title, description = :description, project_id = :project_id, assigned_to = :assigned_to,
         status = :status, priority = :priority, start_date = :start_date, due_date = :due_date,
         estimated_hours = :estimated_hours, actual_hours = :actual_hours, updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'title' => $title,
        'description' => trim($data['description'] ?? ''),
        'project_id' => $projectId,
        'assigned_to' => $assignedTo,
        'status' => $status,
        'priority' => $priority,
        'start_date' => $startDate,
        'due_date' => $dueDate,
        'estimated_hours' => $estimatedHours,
        'actual_hours' => $actualHours,
        'id' => $id,
    ]);

    return ['success' => true, 'message' => 'Task updated successfully.'];
}

function getCommentsForTask(int $taskId): array
{
    $stmt = db()->prepare(
        'SELECT c.*, u.name AS user_name FROM comments c
         JOIN users u ON c.user_id = u.id
         WHERE c.task_id = :task_id ORDER BY c.created_at DESC'
    );
    $stmt->execute(['task_id' => $taskId]);
    return $stmt->fetchAll();
}

function getTimeEntriesForTask(int $taskId): array
{
    $stmt = db()->prepare(
        'SELECT t.*, u.name AS user_name FROM time_entries t
         JOIN users u ON t.user_id = u.id
         WHERE t.task_id = :task_id ORDER BY t.created_at DESC'
    );
    $stmt->execute(['task_id' => $taskId]);
    return $stmt->fetchAll();
}

function addCommentToTask(int $taskId, int $userId, string $body): array
{
    $body = trim($body);
    if ($body === '') {
        return ['success' => false, 'message' => 'Comment cannot be empty.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO comments (task_id, user_id, body, created_at, updated_at)
         VALUES (:task_id, :user_id, :body, NOW(), NOW())'
    );
    $stmt->execute([
        'task_id' => $taskId,
        'user_id' => $userId,
        'body' => $body,
    ]);

    return ['success' => true, 'message' => 'Comment added successfully.'];
}

function addTimeEntryToTask(int $taskId, int $userId, $hours, ?string $notes): array
{
    $hours = floatval($hours);
    if ($hours <= 0) {
        return ['success' => false, 'message' => 'Hours must be greater than zero.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO time_entries (task_id, user_id, hours, notes, created_at, updated_at)
         VALUES (:task_id, :user_id, :hours, :notes, NOW(), NOW())'
    );
    $stmt->execute([
        'task_id' => $taskId,
        'user_id' => $userId,
        'hours' => $hours,
        'notes' => trim($notes ?? ''),
    ]);

    $update = db()->prepare(
        'UPDATE tasks SET actual_hours = actual_hours + :hours, updated_at = NOW() WHERE id = :task_id'
    );
    $update->execute(['hours' => $hours, 'task_id' => $taskId]);

    return ['success' => true, 'message' => 'Time entry recorded successfully.'];
}

// Create notification for user
function createNotification($userId, $type, $title, $message) {
    $stmt = db()->prepare(
        'INSERT INTO notifications (user_id, type, title, message, is_read, created_at)
         VALUES (:user_id, :type, :title, :message, FALSE, NOW())'
    );
    return $stmt->execute([
        'user_id' => $userId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
    ]);
}
