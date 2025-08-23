# Community Connect - AI Coding Agent Instructions

## Project Specifications

- **Project Name**: community-connect
- **Technology Stack**: HTML, CSS, JavaScript, PHP, MySQL (NO external libraries or frameworks)
- **Database Layer**: MySQLi Procedural (NO PDO or ORM)
- **Design Theme**: Blue and White color scheme
- **Architecture**: Pure PHP/MySQL volunteer coordination platform

## Architecture Overview

This is a **PHP/MySQL volunteer coordination platform** with a three-tier role-based architecture:
- **Admin**: System management, project approval, user oversight
- **Organizations**: Project creation, volunteer management  
- **Volunteers**: Profile management, project discovery, self-assignment

**Key Architectural Principle**: Single organization membership per volunteer, with admin-mediated project approval workflow for guest submissions.

## Database Architecture

The platform uses **8 core MySQL tables** with strict foreign key relationships:
- `users` (multi-role: admin/organization/volunteer)
- `organizations` → `users.created_by` 
- `projects` → `users.created_by`, `organizations.org_id`
- `volunteer_projects` (many-to-many assignments)
- `project_suggestions` (guest submissions requiring admin approval)
- `announcements`, `user_sessions`, `activity_logs`

**Critical Pattern**: All database operations use MySQLi Procedural functions in `config/database.php`, never PDO or ORM.

## Core Development Patterns

### Database Operations (MySQLi Procedural)
```php
// Always use MySQLi procedural functions from config/database.php
$connection = getDatabaseConnection();
$user = getSingleRecord("SELECT * FROM users WHERE email = ?", [$email]);
$project_id = insertRecord("INSERT INTO projects (...) VALUES (...)", $params);
logActivity('created_project', 'projects', $project_id);
mysqli_close($connection);
```

### UI/Styling Requirements
- **Color Scheme**: Blue and White theme throughout
- **No External CSS**: Pure CSS only (no Bootstrap, Tailwind, etc.)
- **No External JS**: Vanilla JavaScript only (no jQuery, frameworks)
- **Responsive Design**: CSS Grid/Flexbox for layouts

### Security Model
- **Password hashing**: `hashPassword($password)` and `verifyPassword($password, $hash)`
- **Input sanitization**: `sanitizeInput($input)` before any output
- **Session management**: `startSecureSession()`, `requireLogin()`, `requireRole($role)`
- **Activity logging**: `logActivity($action, $table, $record_id)` for all CUD operations
- **Confirmation dialogs**: ALL Create, Update, Delete operations MUST show confirmation before DB execution

### Confirmation Pattern (MANDATORY)
```javascript
// Frontend confirmation before any database modification
function confirmAction(action, callback) {
    if (confirm(`Are you sure you want to ${action}? This action cannot be undone.`)) {
        callback();
    }
}

// Example usage
confirmAction('delete this project', function() {
    // Only execute after user confirms
    window.location.href = 'delete_project.php?id=' + projectId;
});
```

```php
// Backend should also verify confirmation was sent
if ($_POST['confirmed'] !== 'true') {
    die('Action requires confirmation');
}
// Proceed with database operation only after confirmation
```

### Role-Based Access Control
```php
requireRole('admin', 'index.php');  // Force admin role or redirect
if (hasRole('volunteer')) { /* volunteer-specific logic */ }
```

## Project Setup Workflow

1. **Database Setup**: Run `setup_database.php` (creates DB + default admin user)
2. **Configuration**: Update credentials in both `setup_database.php` and `config/database.php`
3. **Testing**: Run `test_database.php` to verify setup
4. **Default Login**: `admin@communityconnect.com` / `admin123`

## Business Logic Constraints

- **One organization per volunteer**: Enforced via `users.organization_id` FK
- **Project approval flow**: Guest suggestions → `project_suggestions` → admin approval → `projects`
- **Self-assignment**: Volunteers can join projects directly, unique constraint on `volunteer_projects(volunteer_id, project_id)`
- **MANDATORY Confirmation**: ALL Create, Update, Delete operations require user confirmation dialog
- **Double confirmation for critical actions**: Delete operations should ask "Are you absolutely sure?" 
- **Confirmation logging**: Log all confirmation actions in activity_logs for audit trail

## File Structure Conventions

```
├── config/database.php      # All DB functions, security, session management
├── setup_database.php       # One-time DB initialization with sample data
├── test_database.php        # DB verification tool with visual feedback
└── DATABASE_SETUP.md        # Setup documentation
```

## Development Guidelines

### When Adding Features
1. **Always use MySQLi Procedural** - `config/database.php` helper functions only
2. **Log all database modifications**: `logActivity()` for audit trails
3. **Validate inputs**: Use `isValidEmail()`, `sanitizeInput()` before processing
4. **Check permissions**: Use `requireRole()` or `hasRole()` before sensitive operations
5. **MANDATORY Confirmations**: ALL database Create/Update/Delete operations need user confirmation
6. **Implement confirmation dialogs**: Use JavaScript confirm() or custom modal before any CUD operation
7. **Backend confirmation check**: Verify `$_POST['confirmed'] === 'true'` before database execution
8. **Blue/White theme**: Maintain consistent color scheme across all pages
9. **No external dependencies**: Pure HTML/CSS/JS/PHP only

### Confirmation Implementation Examples
```html
<!-- Create Form Example -->
<form onsubmit="return confirmCreate()" method="POST">
    <input type="hidden" name="confirmed" value="false" id="confirmed">
    <!-- form fields -->
    <button type="submit">Create Project</button>
</form>

<script>
function confirmCreate() {
    if (confirm('Are you sure you want to create this project?')) {
        document.getElementById('confirmed').value = 'true';
        return true;
    }
    return false;
}
</script>
```

```php
// PHP Backend Confirmation Check
if ($_POST['action'] === 'create') {
    if ($_POST['confirmed'] !== 'true') {
        die('Error: Action requires confirmation');
    }
    // Proceed with database insertion
    $project_id = insertRecord($sql, $params);
    logActivity('created_project', 'projects', $project_id);
}
```

### Database Schema Changes
- **Modify `setup_database.php`** table definitions using MySQLi, not live DB
- **Add migration logic** for existing installations
- **Update `test_database.php`** to verify new tables/constraints

### Technology Constraints
- **Database**: MySQLi Procedural functions only (mysqli_connect, mysqli_query, etc.)
- **Frontend**: Pure HTML5, CSS3, Vanilla JavaScript
- **Backend**: PHP 7.4+ with MySQLi extension
- **Styling**: Blue (#007bff, #0056b3) and White (#ffffff, #f8f9fa) color palette
- **No Frameworks**: No Bootstrap, jQuery, React, Vue, etc.

## Key Integration Points

- **Email validation**: Built-in `isValidEmail()` function
- **Activity logging**: Automatic via `logActivity()` - tracks user actions with IP/user agent
- **Session management**: Secure session handling with automatic regeneration
- **Error handling**: Database errors logged via `error_log()`, user-friendly messages shown

## Testing & Debugging

- **Database connectivity**: `test_database.php` provides comprehensive health check
- **Helper function testing**: Built into test script (email validation, password hashing)
- **Visual feedback**: All database operations provide HTML-formatted success/error messages
- **Activity logs**: Check `activity_logs` table for user action debugging
