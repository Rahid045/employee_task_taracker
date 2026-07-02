<?php
session_start();
require_once __DIR__ . '/app/init.php';
requireLogin();

$user = $_SESSION['user'];
$error = '';
$success = '';
$profilePicture = $user['profile_picture'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (!$name || !$email) {
            $error = 'Name and email are required.';
        } else {
            try {
                $profilePicture = $user['profile_picture'] ?? '';

                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
                        $error = 'Profile picture upload failed.';
                    } else {
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $uploadedFile = $_FILES['profile_picture'];
                        $fileSize = $uploadedFile['size'] ?? 0;
                        $extension = strtolower(pathinfo($uploadedFile['name'] ?? '', PATHINFO_EXTENSION));

                        if ($fileSize > 2 * 1024 * 1024) {
                            $error = 'Profile picture must be 2MB or smaller.';
                        } elseif (!in_array($extension, $allowedExtensions, true)) {
                            $error = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
                        } else {
                            $imageInfo = @getimagesize($uploadedFile['tmp_name']);
                            if ($imageInfo === false) {
                                $error = 'Please upload a valid image file.';
                            } else {
                                $fileName = $user['id'] . '_' . time() . '.' . $extension;
                                $targetPath = __DIR__ . '/images/profile_pictures/' . $fileName;
                                if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                                    $error = 'Could not save profile picture.';
                                } else {
                                    $profilePicture = 'images/profile_pictures/' . $fileName;
                                }
                            }
                        }
                    }
                }

                if (!$error) {
                    $stmt = db()->prepare('UPDATE users SET name = :name, email = :email, profile_picture = :profile_picture WHERE id = :id');
                    $stmt->execute([
                        'name' => $name,
                        'email' => $email,
                        'profile_picture' => $profilePicture,
                        'id' => $user['id']
                    ]);
                    logAuditEvent($user['id'], 'update_profile', 'Updated profile information.', ['name' => $name, 'email' => $email]);
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['profile_picture'] = $profilePicture;
                    $user = $_SESSION['user'];
                    $success = 'Profile updated successfully!';
                }
            } catch (Exception $e) {
                $error = 'Failed to update profile: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!$current_password || !$new_password || !$confirm_password) {
            $error = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            try {
                $user_data = findUserByEmail($user['email']);
                if (!$user_data || !password_verify($current_password, $user_data['password'])) {
                    $error = 'Current password is incorrect.';
                } else {
                    $hashed_password = hashPassword($new_password);
                    $stmt = db()->prepare('UPDATE users SET password = :password WHERE id = :id');
                    $stmt->execute([
                        'password' => $hashed_password,
                        'id' => $user['id']
                    ]);
                    logAuditEvent($user['id'], 'change_password', 'Changed account password.');
                    $success = 'Password changed successfully!';
                }
            } catch (Exception $e) {
                $error = 'Failed to change password: ' . $e->getMessage();
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
    <title>User Profile - Employee Task Tracker</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 900px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }

        .profile-header {
            background: linear-gradient(135deg, #457b9d 0%, #2e5266 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
            border: 3px solid white;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-header h2 {
            margin: 0;
            font-size: 1.8rem;
        }

        .profile-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }

        .info-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(35, 47, 60, 0.08);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .info-value {
            color: #666;
        }

        .form-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(35, 47, 60, 0.08);
            margin-bottom: 1.5rem;
        }

        .form-section h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            color: #1d3557;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .muted {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.4rem;
        }

        .activity-title {
            font-weight: 600;
            color: #333;
        }

        .activity-time {
            font-size: 0.85rem;
            color: #999;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <header class="site-header">
        <div class="container">
            <h1>User Profile</h1>
        </div>
    </header>

    <main class="container">
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($profilePicture)): ?>
                    <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile picture">
                <?php else: ?>
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Role: <strong><?= ucfirst($user['role']) ?></strong></p>
        </div>

        <div class="profile-container">
            <!-- Profile Information -->
            <div>
                <div class="form-section">
                    <h3>Profile Information</h3>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">

                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

                        <label for="profile_picture">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/png,image/jpeg,image/gif,image/webp">
                        <p class="muted">PNG, JPG, GIF, or WEBP up to 2MB.</p>

                        <label for="role">Role</label>
                        <input type="text" id="role" value="<?= ucfirst($user['role']) ?>" disabled style="background: #f5f5f5; cursor: not-allowed;">

                        <div class="form-actions">
                            <button type="submit" class="button primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Settings -->
            <div>
                <div class="form-section">
                    <h3>Change Password</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">

                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>

                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>

                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>

                        <div class="form-actions">
                            <button type="submit" class="button primary">Update Password</button>
                        </div>
                    </form>
                </div>

                <!-- Account Information -->
                <div class="info-section">
                    <h3 style="margin-top: 0;">Account Information</h3>
                    <div class="info-row">
                        <span class="info-label">User ID</span>
                        <span class="info-value">#<?= htmlspecialchars($user['id']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member Since</span>
                        <span class="info-value"><?= date('M d, Y') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value" style="color: #2a9d8f;">Active</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
