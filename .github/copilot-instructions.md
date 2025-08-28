# Community Connect - AI Agent Instructions

## Architecture Overview
This is a **pure PHP/MySQL volunteer coordination platform** with zero external dependencies. The architecture follows a simplified MVC pattern without frameworks, using MySQLi procedural functions exclusively.

### Core Philosophy
- **NO frameworks**: Pure HTML/CSS/JavaScript/PHP only
- **NO password hashing**: Store passwords in plain text (development-only)
- **NO complex functions**: Keep database operations minimal and direct

## Key Components

### Database Layer (`config/database.php`)
- Global `$connection` variable established on include
- Simple connection: `mysqli_connect($db_host, $db_username, $db_password, $db_name)`
- Use direct queries: `mysqli_query($connection, "SELECT * FROM table WHERE id = $escaped_id")`

### Authentication (`includes/common.php`)
- `isLoggedIn()`: Checks `$_SESSION['user_id']` existence
- `requireLogin()`: Redirects to `login.php` if not authenticated
- `getCurrentUser()`: Returns user data with simple query
- Session management: PHP sessions only, started in common.php

### Role-Based Access Control
Three roles with specific dashboard routing:
- `admin` → `admin_dashboard.php`
- `organization` → `organization_dashboard.php` 
- `volunteer` → `volunteer_dashboard.php`

## Critical Patterns

### Database Operations
```php
// Escape input for simple queries
$email = mysqli_real_escape_string($connection, $email);
$result = mysqli_query($connection, "SELECT * FROM users WHERE email = '$email'");
$user = mysqli_fetch_assoc($result);
```

### File Structure Conventions
- **Dashboard files**: Role-specific with `{role}_dashboard.php`
- **Authentication**: Simple login/signup with session management
- **Public pages**: `index.php` (landing), `browse_projects.php` (public projects)
- **Assets**: Organized in `assets/css/`, `assets/images/` with `logo.png` used throughout

### Form Processing Pattern
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = mysqli_real_escape_string($connection, trim($_POST['field'] ?? ''));
    // Simple validation
    if (empty($field)) {
        $error_message = 'Field required';
    } else {
        // Direct query
        mysqli_query($connection, "INSERT INTO table (field) VALUES ('$field')");
    }
}
```

## Business Logic

### User Management
- One volunteer per organization rule enforced
- One assignment per volunteer per project
- Guest submissions saved as `pending` projects for admin approval

### Project Workflow
1. **Guest submissions** → projects table with `status = 'pending'`
2. **Admin approval** → changes status to `approved` 
3. **Volunteer assignment** → creates record in `volunteer_projects` table

### Database Schema (Key Tables)
- `users`: Multi-role with `organization_id` foreign key
- `organizations`: Created by admin, linked to users
- `projects`: Status workflow (pending→approved→active)
- `volunteer_projects`: Junction table for assignments

## Development Workflows

### Database Setup
Run `setup_database.php` to initialize complete schema and default admin user:
- Email: `admin@communityconnect.com`
- Password: `admin123` (plain text)

### Adding New Features
1. Always include `config/database.php` and `includes/common.php`
2. Use `requireLogin()` or role-specific checks
3. Include `includes/header.php` and `includes/footer.php` for consistent UI
4. Use `htmlspecialchars()` for output sanitization (replaced `sanitizeInput()`)

### CSS/Styling
- Single stylesheet: `assets/css/main.css`
- Blue/white theme with CSS variables: `--primary-blue`, `--dark-blue`
- Logo integration required throughout using `assets/images/logo.png`

## Critical Anti-Patterns
- **Never use prepared statements** - This codebase was explicitly simplified to avoid them
- **Never use password hashing** - Plain text storage for development ease
- **Never use external libraries** - Pure PHP/HTML/CSS/JS only
- **Never use complex database functions** - Keep queries simple and direct

## Error Handling
- Use `$error_message` and `$success_message` variables
- Display with `htmlspecialchars()` in templates
- Simple `die()` for critical database connection failures
