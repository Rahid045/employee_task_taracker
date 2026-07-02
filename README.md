# Employee Task Tracker

A lightweight PHP task tracking system designed for XAMPP / localhost.

## Setup
1. Place the `employee_task_tracker` folder inside `C:\xampp\htdocs`
2. Start Apache and MySQL in XAMPP
3. Import `database/employee_task_tracker.sql` into MySQL
4. Open `http://localhost/employee_task_tracker/login.php`

## Default login
- Email: admin@example.com
- Password: Admin123!

## Project structure
- `index.php` – dashboard and task listing
- `login.php` / `logout.php` – authentication
- `task_create.php`, `task_edit.php`, `task_view.php` – task management
- `app/init.php` – database and application logic
- `assets/` – CSS and JavaScript
- `database/employee_task_tracker.sql` – database schema and sample data
