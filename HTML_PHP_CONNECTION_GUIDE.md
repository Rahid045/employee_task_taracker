# Employee Task Tracker - HTML to PHP Connection Guide

## Overview
This guide explains how to use the standalone HTML files with the PHP backend. Each HTML file is designed to connect with its corresponding PHP file.

## File Structure

### HTML Files (Frontend)
- `login_standalone.html` → `login.php` (Authentication)
- `index_standalone.html` → `index.php` (Dashboard)
- `task_create_standalone.html` → `task_create.php` (Create Tasks)
- `task_view_standalone.html` → `task_view.php` (View Tasks)
- `task_edit_standalone.html` → `task_edit.php` (Edit Tasks)
- `logout_standalone.html` → `logout.php` (Logout)

### PHP Files (Backend)
- `login.php` - Handles login form submissions
- `index.php` - Displays task dashboard (requires authentication)
- `task_create.php` - Creates new tasks (requires authentication)
- `task_view.php` - Displays task details (requires authentication)
- `task_edit.php` - Updates task information (requires authentication)
- `task_delete.php` - Deletes tasks (requires authentication)
- `logout.php` - Destroys session and redirects to login

## How It Works

### 1. Login Flow
1. User opens `login_standalone.html` (or `login.html`)
2. Enters email and password
3. Form POSTs to `login.php`
4. `login.php` validates credentials and creates session
5. On success: redirects to `index.php`
6. On failure: displays error message in login form

### 2. Dashboard View
1. User accesses `index.php` (session is checked automatically)
2. Displays task list with user's assigned tasks
3. Shows summary cards (Open tasks, Completed, Overdue)
4. Provides links to Create, View, Edit, and Delete tasks

### 3. Create Task
1. User clicks "Create Task" from dashboard
2. Form at `task_create.html` opens (users with admin/manager role only)
3. Fills in: Title, Description, Assignee, Priority, Status, Dates, Hours
4. Form POSTs to `task_create.php`
5. Task is saved to database
6. Redirects back to `index.php`

### 4. View Task
1. User clicks "View" on a task from dashboard
2. Page opens with task details from `task_view.php`
3. Displays: Task info, Comments, Time entries
4. Shows options to:
   - Add comments
   - Add time entries
   - Edit task (admin/manager only)
   - Delete task (admin/manager only)

### 5. Edit Task
1. User clicks "Edit" from task view
2. Form at `task_edit.php` opens with pre-filled values
3. Updates any fields: Title, Description, Assignee, Status, Priority, Dates, Hours
4. Form POSTs to `task_edit.php`
5. Task is updated in database
6. Redirects back to task view

### 6. Delete Task
1. User clicks "Delete" from task view or dashboard
2. Confirmation dialog appears
3. If confirmed, form POSTs to `task_delete.php` with task ID
4. Task is deleted from database
5. Redirects back to `index.php`

### 7. Logout
1. User clicks "Logout" from dashboard
2. Session is destroyed via `logout.php`
3. Redirects to login page

## Quick Start

### Option A: Use Original PHP Files (Recommended for Dynamic Data)
These PHP files already include HTML templates and handle both display and processing:
```
1. Open http://localhost/employee_task_tracker/login.php
2. Log in with valid credentials
3. Navigate using the provided links
```

### Option B: Use Standalone HTML Files
For a more separated frontend/backend approach:
```
1. Open http://localhost/employee_task_tracker/login_standalone.html
2. Log in (connects to login.php backend)
3. This redirects to index.php which handles the session and displays data
4. Continue navigation from there
```

### Option C: Hybrid Approach (Recommended)
Use the standalone HTML files to understand form structure, but access the PHP files directly for full functionality:
- Forms in HTML files submit to the corresponding PHP files
- PHP files handle validation, database operations, and session management
- PHP files also include the HTML templates for display

## Form Connections

### Login Form
**HTML**: `login_standalone.html`
**PHP Backend**: `login.php`
**Form Fields**:
- `email` (email input)
- `password` (password input)

### Create Task Form
**HTML**: `task_create_standalone.html`
**PHP Backend**: `task_create.php`
**Form Fields**:
- `title` (required)
- `description`
- `assigned_to`
- `priority`
- `status`
- `start_date`
- `due_date`
- `estimated_hours`

### Edit Task Form
**HTML**: `task_edit_standalone.html`
**PHP Backend**: `task_edit.php`
**Form Fields** (same as Create, plus):
- `id` (hidden, task ID from URL parameter)
- `actual_hours`

### Delete Form
**PHP Backend**: `task_delete.php`
**Method**: POST
**Hidden Fields**:
- `id` (task ID)

### Comment Form
**PHP Backend**: `task_view.php`
**Method**: POST
**Hidden Fields**:
- `action` = "comment"
**Form Fields**:
- `comment_body` (textarea)

### Time Entry Form
**PHP Backend**: `task_view.php`
**Method**: POST
**Hidden Fields**:
- `action` = "time_entry"
**Form Fields**:
- `hours` (number)
- `notes` (textarea)

## Database Requirements

The PHP backend requires a MySQL database with the following tables:
- `users` (id, name, email, password, role)
- `tasks` (id, title, description, created_by, assigned_to, status, priority, start_date, due_date, estimated_hours, actual_hours, created_at, updated_at)
- `comments` (id, task_id, user_id, body, created_at)
- `time_entries` (id, task_id, user_id, hours, notes, created_at)

## Security Features

Your PHP backend includes:
- Session-based authentication (`session_start()`)
- Password hashing verification (`password_verify()`)
- SQL injection prevention (prepared statements with parameterized queries)
- HTML escaping for output (`htmlspecialchars()`)
- Role-based access control (employee vs admin/manager)
- Flash messages for user feedback (`$_SESSION['flash_message']`)

## Configuration

The database connection is configured in `app/init.php`:
```php
$dsn = 'mysql:host=127.0.0.1;dbname=employee_task_tracker;charset=utf8mb4';
$user = 'root';
$password = '';
```

Make sure your local MySQL server is running and the database exists.

## Styling

All HTML files link to `assets/styles.css` for consistent styling.

## JavaScript Features

- Form validation (client-side checks before submission)
- Date constraints (start date and due date minimum = today)
- Delete confirmation dialog
- Search functionality
- Responsive table layout

## Troubleshooting

### "Session not found" Error
- Make sure PHP sessions are enabled
- Check that cookies are not blocked in browser

### "Task not found" Error
- Verify the task ID in the URL parameter is valid
- Check the task exists in the database

### Database Connection Error
- Verify MySQL server is running
- Check credentials in `app/init.php`
- Ensure database `employee_task_tracker` exists

### Forms Not Submitting
- Check browser console for JavaScript errors
- Verify form action URLs are correct
- Check that PHP files exist in the root directory

## Next Steps

1. Test the login flow with valid credentials
2. Create a test task
3. View and edit the task
4. Add comments and time entries
5. Delete the test task
6. Log out

For more details on the PHP backend logic, check the individual PHP files and `app/init.php`.
