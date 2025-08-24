# Community Connect - AI Coding Agent Instructions

## MVP-only (First-year friendly)

Keep this project as a minimal viable product so it's easy to build and explain in a viva. Only implement what is listed below—nothing extra.

### Code Organization & Quality Rules
- **Minimal & Readable**: Every file should be minimal, simple, readable, short, and powerful
- **No Code Repetition**: Don't repeat the same code again - use shared components in `includes/` folder
- **Shared Components**: Commonly used code should be organized in one folder (`includes/`) with descriptive names
- **Direct Processing**: All form processing happens directly in the same PHP file, no separate endpoint files
- **No External Dependencies**: Pure HTML/CSS/JS/PHP only

### Technology Constraints  
- Build only the basics: register, login, simple dashboards, create project, approve project, browse/join project
- Use only PHP, MySQL (MySQLi procedural), HTML, CSS, and a tiny bit of vanilla JS for confirmation dialogs
- No advanced features: no emails, no images/avatars, no notifications, no AJAX/fetch, no modals beyond browser confirm()
- Simple UI in blue/white with basic forms and tables
- Use the provided helper functions in `config/database.php` and existing shared components in `includes/`

## Project Parts and Branch Policy

The project is split into 5 parts:
1. Login
2. Home Page
3. Admin Page
4. Functionalities
5. Help Page

You are currently on the `login` branch. In this branch:
- Only implement the Login and Register functionality. Do not create or modify Home, Admin, Functionalities, or Help pages.
- Keep the code minimal and stick to the MVP scope below.
- Remove files that belong to other parts (Home, Admin, Functionalities, Help) from this branch to keep it clean.
- Keep the following setup/test files for convenience, but do NOT modify them in this branch: `README.md`, `setup_database.php`, `test_database.php`.

Expected files to remain in this branch (Login/Register only):
- `config/database.php` - Core database functions and security helpers
- `includes/` - Shared components and utilities (header, footer, common functions)
  - `includes/header.php` - Shared HTML header with consistent styling
  - `includes/footer.php` - Shared footer with JavaScript functions
  - `includes/common.php` - Any other reusable code components
- `login.php` - Login form processing and authentication
- `login.html` - Login form interface
- `signup.php` - Registration form processing
- `signup.html` - Registration form interface
- `logout.php` - Session termination and logout functionality
- `forgot.html` - Password reset interface (basic)
- Supporting CSS/JS files: `style.css`, `login.css`, `signup.css`, `forgot.css`, `script.js`, `signup.js`
- `.github/copilot-instructions.md` (this file)

Additionally keep (read-only, not modified here):
- `README.md`
- `setup_database.php` 
- `test_database.php`

## Project Specifications

- **Project Name**: community-connect
- **Technology Stack**: HTML, CSS, JavaScript, PHP, MySQL (NO external libraries or frameworks)
- **Database Layer**: MySQLi Procedural (NO PDO or ORM)
- **Design Theme**: Blue and White color scheme
- **Architecture**: Pure PHP/MySQL volunteer coordination platform
- make this code very simple for understand to First-year university student level
- MVP Only: implement only the features listed in "MVP Scope" below; avoid enhancements and extras

## MVP Scope (Only build this)

1) Authentication
- Register (email, password, role selection: admin/organization/volunteer) and Login/Logout

2) Roles and pages (use files already in the repo)
- Volunteer: `volunteer_dashboard.php`
    - View simple welcome text and link to browse projects
    - Browse approved projects and join a project (one click with confirmation)
- Organization: `organization_dashboard.php`
    - Create a simple project (title, description) → status starts as `pending`
    - View own projects (table)
- Admin: keep it simple
    - Admin UI is out of scope in this branch; approval logic will be respected by status checks

3) Projects flow
- New project → status `pending`
- Admin approves → status `approved`
- Volunteers can join only approved projects
- Enforce unique join per volunteer per project

4) Constraints kept simple
- One organization per volunteer (via `users.organization_id`)
- Mandatory confirmation dialogs for Create/Update/Delete
- Server must check `$_POST['confirmed'] === 'true'` before DB changes

## Architecture Overview

This is a **PHP/MySQL volunteer coordination platform** with a three-tier role-based architecture:
- **Admin**: System management, project approval, user oversight
- **Organizations**: Project creation, volunteer management  
- **Volunteers**: Profile management, project discovery, self-assignment

**Key Architectural Principle**: Single organization membership per volunteer, with admin-mediated project approval workflow for guest submissions.

### Pages in this repository (use these, no new pages required for MVP)
This branch focuses on Login/Register functionality only. Other pages (Home, Admin, Functionalities, Help) are handled in their own branches.
- `login.php`: Login form processing with email/password authentication
- `login.html`: Login form interface with blue/white styling
- `signup.php`: Registration form processing with role selection (admin/organization/volunteer)  
- `signup.html`: Registration form interface
- `logout.php`: Session termination and logout functionality
- `forgot.html`: Basic password reset interface (static form for MVP)
- `config/database.php`: All MySQLi helper functions and security helpers
- `includes/`: Shared code components and utilities
  - `includes/header.php`: Common HTML header with blue/white styling
  - `includes/footer.php`: Common footer with JavaScript functions
  - `includes/common.php`: Any other reusable functions or components
  
Preserved files (do not modify in this branch):
- `README.md`, `setup_database.php`, `test_database.php`: Setup/testing reference only

## Database Architecture

The platform uses **4 core MySQL tables** with strict foreign key relationships:
- `users` (multi-role: admin/organization/volunteer)
- `organizations` → `users.created_by` 
- `projects` → `users.created_by`, `organizations.org_id`
- `volunteer_projects` (many-to-many assignments)

**Critical Pattern**: All database operations use MySQLi Procedural functions in `config/database.php`, never PDO or ORM.

## Core Development Patterns

### Database Operations (MySQLi Procedural)
```php
// Always use MySQLi procedural functions from config/database.php
$user = getSingleRecord("SELECT * FROM users WHERE email = ?", [$email]);
$project_id = insertRecord(
    "INSERT INTO projects (title, description, created_by, status) VALUES (?, ?, ?, 'pending')",
    [$title, $description, $user_id]
);
```

### UI/Styling Requirements
- **Color Scheme**: Blue and White theme throughout
- **No External CSS**: Pure CSS only (no Bootstrap, Tailwind, etc.)
- **No External JS**: Vanilla JavaScript only (no jQuery, frameworks)
- **Responsive Design**: CSS Grid/Flexbox for layouts

### MVP Do/Don't
- Do keep forms and tables minimal and readable
- Do use browser `confirm()` for all create/update/delete confirmations
- Do sanitize inputs and validate email
- Do organize common code in `includes/` folder to avoid repetition
- Do make every file minimal, simple, readable, short, and powerful
- Don't add extra pages or features not in MVP scope
- Don't use external libraries, frameworks, or separate routing systems
- Don't repeat the same code - use shared components instead

## Functionalities to implement in this branch

Focus only on the Login/Register functionality and keep it very simple:

1) User Registration (signup.php/signup.html)
- Registration form with email, password, and role selection (admin/organization/volunteer)
- Server-side validation: email format, password requirements, duplicate email check
- Password hashing using `hashPassword()` function
- Store user data in `users` table with appropriate role
- Basic success/error feedback messages

2) User Authentication (login.php/login.html)  
- Login form with email/password fields
- Server-side authentication using `verifyPassword()` function
- Session management with `startSecureSession()`
- Role-based redirection:
  - Admin → admin dashboard
  - Organization → organization dashboard  
  - Volunteer → volunteer dashboard
- Remember session state and prevent unauthorized access

3) Session Management (logout.php)
- Secure session termination
- Clear all session data
- Redirect to login page with logout confirmation

4) Password Recovery (forgot.html)
- Basic static form interface (no email functionality in MVP)
- Form fields for email input
- Simple UI message about password reset (static for MVP)

5) Shared Components and Security
- Use `config/database.php` MySQLi helper functions
- Input sanitization using `sanitizeInput()` 
- Email validation using `isValidEmail()`
- Secure session handling
- Blue/white themed styling across all forms
- Basic client-side form validation with vanilla JavaScript

Out of scope for this branch:
- Dashboard pages (Home, Admin, Functionalities, Help) - these are in other branches
- Actual email sending for password recovery (MVP uses static form only)
- Advanced features like remember me, account lockout, etc.

### Functionalities responsibilities (implement here, MVP-simple)
- User Registration: Email/password signup with role selection (admin/organization/volunteer)
- User Authentication: Email/password login with session management
- Role-based Navigation: Redirect users to appropriate dashboards based on their role
- Session Management: Secure login/logout functionality with proper session handling
- Input Validation: Email format validation, password requirements, duplicate email prevention
- Password Security: Hash passwords on registration, verify on login using secure functions
- Basic UI: Clean blue/white themed forms with client-side validation feedback
- Error Handling: User-friendly messages for login failures, registration errors, validation issues

### Security Model
- **Password hashing**: `hashPassword($password)` and `verifyPassword($password, $hash)`
- **Input sanitization**: `sanitizeInput($input)` before any output
- **Session management**: `startSecureSession()`, `requireLogin()`, `requireRole($role)`
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
- **Project approval flow**: Guest submissions saved to `projects` with status `pending` → admin review/approval
- **Self-assignment**: Volunteers can join projects directly, unique constraint on `volunteer_projects(volunteer_id, project_id)`
- **MANDATORY Confirmation**: ALL Create, Update, Delete operations require user confirmation dialog
- **Double confirmation for critical actions**: Delete operations should ask "Are you absolutely sure?" 

## File Structure Conventions

```
├── config/database.php      # All DB functions, security, session management
├── includes/                # Shared components folder (NO separate endpoints)
│   ├── header.php          # Common HTML header with blue/white styling
│   ├── footer.php          # Common footer with JavaScript confirmation functions  
│   └── common.php          # Other reusable functions and utilities
├── organization_dashboard.php  # Organization profile and project CRUD
├── volunteer_dashboard.php    # Volunteer profile and project viewing
├── browse_projects.php       # Project browsing and joining functionality
├── setup_database.php       # One-time DB initialization (creates DB, tables, default admin)
├── test_database.php        # DB verification tool with visual feedback
└── README.md               # Setup documentation
```

## Development Guidelines

### When Implementing the MVP
1. **Always use MySQLi Procedural** - `config/database.php` helper functions only
2. **Validate inputs**: Use `isValidEmail()`, `sanitizeInput()` before processing
3. **Check permissions**: Use `requireRole()` or `hasRole()` before sensitive operations
4. **MANDATORY Confirmations**: ALL database Create/Update/Delete operations need user confirmation
5. **Implement confirmation dialogs**: Use JavaScript confirm() or custom modal before any CUD operation
6. **Backend confirmation check**: Verify `$_POST['confirmed'] === 'true'` before database execution
7. **Blue/White theme**: Maintain consistent color scheme across all pages
8. **No external dependencies**: Pure HTML/CSS/JS/PHP only
9. **Stay MVP**: No emails, no uploads, no images, no search/pagination, no notifications, no separate endpoints/routes
10. **Direct processing**: All form processing happens directly in the same PHP file, no separate endpoint files
11. **Shared components**: Use `includes/` folder for common code to avoid repetition
12. **Minimal files**: Keep every file minimal, simple, readable, short, and powerful

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
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    }
    // Proceed with database insertion
    $project_id = insertRecord($sql, $params);
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
- **Session management**: Secure session handling with automatic regeneration
- **Error handling**: Database errors logged via `error_log()`, user-friendly messages shown

## Testing & Debugging
## Viva tips (keep it simple)
- Be ready to explain: tables, basic CRUD flow, and confirmation checks
- Walk through: register → login → org creates project (pending) → admin approves → volunteer joins
- Point out: MySQLi procedural, password hashing, input sanitization, and session checks


- **Database connectivity**: `test_database.php` provides comprehensive health check
- **Helper function testing**: Built into test script (email validation, password hashing)
- **Visual feedback**: All database operations provide HTML-formatted success/error messages
