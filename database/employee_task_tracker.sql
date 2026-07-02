CREATE DATABASE IF NOT EXISTS `employee_task_tracker` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `employee_task_tracker`;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `time_entries`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'manager', 'employee') NOT NULL DEFAULT 'employee',
  `profile_picture` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`created_at`),
  CONSTRAINT `audit_logs_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `projects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `status` ENUM('active', 'on_hold', 'completed') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`created_by`),
  CONSTRAINT `projects_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tasks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `created_by` INT UNSIGNED NOT NULL,
  `project_id` INT UNSIGNED DEFAULT NULL,
  `assigned_to` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('new', 'in_progress', 'blocked', 'completed') NOT NULL DEFAULT 'new',
  `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  `start_date` DATE DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `estimated_hours` DECIMAL(5,2) DEFAULT NULL,
  `actual_hours` DECIMAL(6,2) NOT NULL DEFAULT '0.00',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`assigned_to`),
  INDEX (`created_by`),
  INDEX (`project_id`),
  INDEX (`due_date`),
  CONSTRAINT `tasks_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_assigned_to_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_project_id_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`task_id`),
  INDEX (`user_id`),
  CONSTRAINT `comments_task_id_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `time_entries` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `hours` DECIMAL(5,2) NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`task_id`),
  INDEX (`user_id`),
  CONSTRAINT `time_entries_task_id_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `time_entries_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('urgent', 'warning', 'info', 'success') NOT NULL DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`created_at`),
  CONSTRAINT `notifications_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin User', 'krahid@gmail.com', '$Admin', 'admin'),
('Manager User', 'manager@example.com', '$2y$10$6jCPJ9.qaXbXMOTn.Jnd4ufCx56FLLr.0cQMLjTKI3dPxPv6GzdKq', 'manager'),
('Employee User', 'employee@example.com', '$2y$10$6jCPJ9.qaXbXMOTn.Jnd4ufCx56FLLr.0cQMLjTKI3dPxPv6GzdKq', 'employee');

INSERT INTO `projects` (`name`, `description`, `status`, `created_by`) VALUES
('Q3 Development Sprint', 'Main development cycle for Q3 including new features and bug fixes.', 'active', 1),
('UI/UX Redesign', 'Complete redesign of the user interface for better usability.', 'active', 1),
('Database Optimization', 'Performance optimization and database indexing improvements.', 'on_hold', 1);

INSERT INTO `tasks` (`title`, `description`, `created_by`, `project_id`, `assigned_to`, `status`, `priority`, `start_date`, `due_date`, `estimated_hours`, `actual_hours`) VALUES
('Setup Project Structure', 'Build the initial employee task tracker application and database schema.', 1, 1, 2, 'completed', 'high', '2026-06-01', '2026-06-03', 5.00, 5.00),
('Design Task List UI', 'Create the task list page with filters and statuses.', 1, 2, 3, 'in_progress', 'medium', '2026-06-04', '2026-06-08', 4.00, 1.50),
('Implement Authentication', 'Add login, logout, and session handling to the system.', 1, 1, 2, 'new', 'high', '2026-06-05', '2026-06-07', 3.00, 0.00);

INSERT INTO `comments` (`task_id`, `user_id`, `body`) VALUES
(1, 2, 'Project structure looks good. We can add task editing next.'),
(2, 3, 'I am working on the task list UI today.');

INSERT INTO `time_entries` (`task_id`, `user_id`, `hours`, `notes`) VALUES
(1, 2, 5.00, 'Completed initial setup and migration scripts.'),
(2, 3, 1.50, 'Started UI and layout for the dashboard.');

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `is_read`) VALUES
(3, 'info', 'New Task Assigned', 'You have been assigned a new task: "Design Task List UI"', FALSE),
(2, 'warning', 'Task Due Soon', 'High priority task "Implement Authentication" is due in 2 days', FALSE),
(3, 'success', 'Achievement Unlocked', 'Great work! You have completed your first task.', TRUE),
(1, 'urgent', 'Overdue Task', 'Task "Database Optimization" is 3 days overdue', FALSE);
