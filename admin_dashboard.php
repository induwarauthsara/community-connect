<?php
require_once 'config/database.php';
require_once 'includes/common.php';

// Ensure user is logged in and is admin
requireRole('admin');
$current_user = getCurrentUser();

$page_title = 'Admin Dashboard - Community Connect';
$error_message = '';
$success_message = '';

if (isset($_GET['action']) && $_GET['action'] === 'get_stats') {
    header('Content-Type: application/json');
    
    $stats = [];
    
    // Total users
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = mysqli_fetch_assoc($result)['count'];
    
    // Total organizations
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM organizations");
    $stats['total_organizations'] = mysqli_fetch_assoc($result)['count'];
    
    // Total projects
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM projects");
    $stats['total_projects'] = mysqli_fetch_assoc($result)['count'];
    
    // Pending projects
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM projects WHERE status = 'pending'");
    $stats['pending_projects'] = mysqli_fetch_assoc($result)['count'];
    
    // Active assignments
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM volunteer_projects WHERE status = 'confirmed'");
    $stats['active_assignments'] = mysqli_fetch_assoc($result)['count'];
    
    echo json_encode($stats);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'get_project_details') {
    header('Content-Type: application/json');
    
    $project_id = (int)$_GET['project_id'];
    
    $query = "SELECT p.*, o.name as org_name, 
              (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id AND vp.status = 'confirmed') as confirmed_volunteers
              FROM projects p 
              LEFT JOIN organizations o ON p.organization_id = o.org_id 
              WHERE p.project_id = $project_id";
    
    $result = mysqli_query($connection, $query);
    $project = mysqli_fetch_assoc($result);
    
    if ($project) {
        // Get volunteer assignments
        $volunteers_query = "SELECT u.name, u.email, vp.status, vp.assigned_at 
                           FROM volunteer_projects vp 
                           JOIN users u ON vp.volunteer_id = u.user_id 
                           WHERE vp.project_id = $project_id 
                           ORDER BY vp.assigned_at DESC";
        $volunteers_result = mysqli_query($connection, $volunteers_query);
        $volunteers = [];
        while ($volunteer = mysqli_fetch_assoc($volunteers_result)) {
            $volunteers[] = $volunteer;
        }
        $project['volunteers'] = $volunteers;
        
        echo json_encode($project);
    } else {
        echo json_encode(['error' => 'Project not found']);
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'get_assignment_details') {
    header('Content-Type: application/json');
    
    $assignment_id = (int)$_GET['assignment_id'];
    
    $query = "SELECT vp.*, 
                     u.name as volunteer_name, u.email as volunteer_email, u.phone as volunteer_phone, 
                     u.address as volunteer_address, u.created_at as volunteer_joined,
                     p.title as project_title, p.description as project_description, 
                     p.location as project_location, p.start_date, p.end_date,
                     o.name as org_name, o.contact_email as org_email
              FROM volunteer_projects vp 
              JOIN users u ON vp.volunteer_id = u.user_id 
              JOIN projects p ON vp.project_id = p.project_id 
              LEFT JOIN organizations o ON p.organization_id = o.org_id 
              WHERE vp.id = $assignment_id";
    
    $result = mysqli_query($connection, $query);
    $assignment = mysqli_fetch_assoc($result);
    
    if ($assignment) {
        // Get volunteer's other assignments count
        $other_assignments_query = "SELECT COUNT(*) as total_assignments 
                                  FROM volunteer_projects vp2 
                                  WHERE vp2.volunteer_id = {$assignment['volunteer_id']}";
        $other_result = mysqli_query($connection, $other_assignments_query);
        $other_data = mysqli_fetch_assoc($other_result);
        $assignment['total_assignments'] = $other_data['total_assignments'];
        
        // Get volunteer's completed assignments count
        $completed_assignments_query = "SELECT COUNT(*) as completed_assignments 
                                      FROM volunteer_projects vp3 
                                      WHERE vp3.volunteer_id = {$assignment['volunteer_id']} 
                                      AND vp3.status = 'completed'";
        $completed_result = mysqli_query($connection, $completed_assignments_query);
        $completed_data = mysqli_fetch_assoc($completed_result);
        $assignment['completed_assignments'] = $completed_data['completed_assignments'];
        
        echo json_encode($assignment);
    } else {
        echo json_encode(['error' => 'Assignment not found']);
    }
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    
    switch ($action) {
        case 'create_user':
            $name = mysqli_real_escape_string($connection, trim($_POST['name'] ?? ''));
            $username = mysqli_real_escape_string($connection, trim($_POST['username'] ?? ''));
            $email = mysqli_real_escape_string($connection, trim($_POST['email'] ?? ''));
            $password = mysqli_real_escape_string($connection, trim($_POST['password'] ?? ''));
            $role = mysqli_real_escape_string($connection, trim($_POST['role'] ?? ''));
            $phone = mysqli_real_escape_string($connection, trim($_POST['phone'] ?? ''));
            $address = mysqli_real_escape_string($connection, trim($_POST['address'] ?? ''));
            $organization_id = !empty($_POST['organization_id']) ? (int)$_POST['organization_id'] : null;
            
            if (empty($name) || empty($username) || empty($email) || empty($password) || empty($role)) {
                $error_message = 'All required fields must be filled.';
            } else {
                // Check if username already exists
                $check_username = mysqli_query($connection, "SELECT user_id FROM users WHERE username = '$username'");
                if (mysqli_num_rows($check_username) > 0) {
                    $error_message = 'Username already exists.';
                } else {
                    // Check if email already exists
                    $check_email = mysqli_query($connection, "SELECT user_id FROM users WHERE email = '$email'");
                    if (mysqli_num_rows($check_email) > 0) {
                        $error_message = 'Email already exists.';
                    } else {
                        $org_clause = $organization_id ? ", organization_id = $organization_id" : "";
                        $sql = "INSERT INTO users (name, username, email, password, role, phone, address$org_clause, is_active, email_verified) 
                                VALUES ('$name', '$username', '$email', '$password', '$role', '$phone', '$address'" . 
                               ($organization_id ? ", $organization_id" : "") . ", 1, 1)";
                        
                        if (mysqli_query($connection, $sql)) {
                            $success_message = 'User created successfully.';
                        } else {
                            $error_message = 'Error creating user: ' . mysqli_error($connection);
                        }
                    }
                }
            }
            break;
            
        case 'update_user':
            $user_id = (int)$_POST['user_id'];
            $name = mysqli_real_escape_string($connection, trim($_POST['name'] ?? ''));
            $username = mysqli_real_escape_string($connection, trim($_POST['username'] ?? ''));
            $email = mysqli_real_escape_string($connection, trim($_POST['email'] ?? ''));
            $role = mysqli_real_escape_string($connection, trim($_POST['role'] ?? ''));
            $phone = mysqli_real_escape_string($connection, trim($_POST['phone'] ?? ''));
            $address = mysqli_real_escape_string($connection, trim($_POST['address'] ?? ''));
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $organization_id = !empty($_POST['organization_id']) ? (int)$_POST['organization_id'] : null;
            
            if (empty($name) || empty($username) || empty($email) || empty($role)) {
                $error_message = 'All required fields must be filled.';
            } else {
                // Check if username already exists for other users
                $check_username = mysqli_query($connection, "SELECT user_id FROM users WHERE username = '$username' AND user_id != $user_id");
                if (mysqli_num_rows($check_username) > 0) {
                    $error_message = 'Username already exists.';
                } else {
                    // Check if email already exists for other users
                    $check_email = mysqli_query($connection, "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id");
                    if (mysqli_num_rows($check_email) > 0) {
                        $error_message = 'Email already exists.';
                    } else {
                        $org_clause = $organization_id ? "organization_id = $organization_id" : "organization_id = NULL";
                        $sql = "UPDATE users SET name = '$name', username = '$username', email = '$email', role = '$role', 
                                phone = '$phone', address = '$address', is_active = $is_active, $org_clause 
                                WHERE user_id = $user_id";
                        
                        if (mysqli_query($connection, $sql)) {
                            $success_message = 'User updated successfully.';
                        } else {
                            $error_message = 'Error updating user: ' . mysqli_error($connection);
                        }
                    }
                }
            }
            break;
            
        case 'delete_user':
            $user_id = (int)$_POST['user_id'];
            if ($user_id === $current_user['user_id']) {
                $error_message = 'You cannot delete your own account.';
            } else {
                if (mysqli_query($connection, "DELETE FROM users WHERE user_id = $user_id")) {
                    $success_message = 'User deleted successfully.';
                } else {
                    $error_message = 'Error deleting user: ' . mysqli_error($connection);
                }
            }
            break;
            
        case 'create_organization':
            $name = mysqli_real_escape_string($connection, trim($_POST['name'] ?? ''));
            $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
            $contact_email = mysqli_real_escape_string($connection, trim($_POST['contact_email'] ?? ''));
            $contact_phone = mysqli_real_escape_string($connection, trim($_POST['contact_phone'] ?? ''));
            $address = mysqli_real_escape_string($connection, trim($_POST['address'] ?? ''));
            
            if (empty($name)) {
                $error_message = 'Organization name is required.';
            } else {
                $sql = "INSERT INTO organizations (name, description, contact_email, contact_phone, address, created_by) 
                        VALUES ('$name', '$description', '$contact_email', '$contact_phone', '$address', {$current_user['user_id']})";
                
                if (mysqli_query($connection, $sql)) {
                    $success_message = 'Organization created successfully.';
                } else {
                    $error_message = 'Error creating organization: ' . mysqli_error($connection);
                }
            }
            break;
            
        case 'update_organization':
            $org_id = (int)$_POST['org_id'];
            $name = mysqli_real_escape_string($connection, trim($_POST['name'] ?? ''));
            $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
            $contact_email = mysqli_real_escape_string($connection, trim($_POST['contact_email'] ?? ''));
            $contact_phone = mysqli_real_escape_string($connection, trim($_POST['contact_phone'] ?? ''));
            $address = mysqli_real_escape_string($connection, trim($_POST['address'] ?? ''));
            
            if (empty($name)) {
                $error_message = 'Organization name is required.';
            } else {
                $sql = "UPDATE organizations SET name = '$name', description = '$description', 
                        contact_email = '$contact_email', contact_phone = '$contact_phone', address = '$address' 
                        WHERE org_id = $org_id";
                
                if (mysqli_query($connection, $sql)) {
                    $success_message = 'Organization updated successfully.';
                } else {
                    $error_message = 'Error updating organization: ' . mysqli_error($connection);
                }
            }
            break;
            
        case 'delete_organization':
            $org_id = (int)$_POST['org_id'];
            if (mysqli_query($connection, "DELETE FROM organizations WHERE org_id = $org_id")) {
                $success_message = 'Organization deleted successfully.';
            } else {
                $error_message = 'Error deleting organization: ' . mysqli_error($connection);
            }
            break;
            
        case 'approve_project':
            $project_id = (int)$_POST['project_id'];
            if (mysqli_query($connection, "UPDATE projects SET status = 'approved' WHERE project_id = $project_id")) {
                $success_message = 'Project approved successfully.';
            } else {
                $error_message = 'Error approving project: ' . mysqli_error($connection);
            }
            break;
            
        case 'update_project_status':
            $project_id = (int)$_POST['project_id'];
            $status = mysqli_real_escape_string($connection, trim($_POST['status'] ?? ''));
            
            if (mysqli_query($connection, "UPDATE projects SET status = '$status' WHERE project_id = $project_id")) {
                $success_message = 'Project status updated successfully.';
            } else {
                $error_message = 'Error updating project status: ' . mysqli_error($connection);
            }
            break;
            
        case 'update_assignment_status':
            $assignment_id = (int)$_POST['assignment_id'];
            $status = mysqli_real_escape_string($connection, trim($_POST['status'] ?? ''));
            
            // Validate status
            $valid_statuses = ['registered', 'confirmed', 'completed', 'cancelled'];
            if (!in_array($status, $valid_statuses)) {
                $error_message = 'Invalid assignment status.';
                break;
            }
            
            // Update completed_at if status is being set to completed
            $completed_at_sql = '';
            if ($status === 'completed') {
                $completed_at_sql = ', completed_at = NOW()';
            } elseif ($status !== 'completed') {
                $completed_at_sql = ', completed_at = NULL';
            }
            
            if (mysqli_query($connection, "UPDATE volunteer_projects SET status = '$status' $completed_at_sql WHERE id = $assignment_id")) {
                $success_message = 'Assignment status updated successfully.';
            } else {
                $error_message = 'Error updating assignment status: ' . mysqli_error($connection);
            }
            break;
            
        case 'delete_project':
            $project_id = (int)$_POST['project_id'];
            if (mysqli_query($connection, "DELETE FROM projects WHERE project_id = $project_id")) {
                $success_message = 'Project deleted successfully.';
            } else {
                $error_message = 'Error deleting project: ' . mysqli_error($connection);
            }
            break;
            
        case 'create_project':
            $title = mysqli_real_escape_string($connection, trim($_POST['title'] ?? ''));
            $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
            $location = mysqli_real_escape_string($connection, trim($_POST['location'] ?? ''));
            $start_date = trim($_POST['start_date'] ?? '');
            $end_date = trim($_POST['end_date'] ?? '');
            $start_time = trim($_POST['start_time'] ?? '');
            $end_time = trim($_POST['end_time'] ?? '');
            $capacity = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null;
            $skills_needed = mysqli_real_escape_string($connection, trim($_POST['skills_needed'] ?? ''));
            $requirements = mysqli_real_escape_string($connection, trim($_POST['requirements'] ?? ''));
            $priority = mysqli_real_escape_string($connection, trim($_POST['priority'] ?? 'medium'));
            $organization_id = !empty($_POST['organization_id']) ? (int)$_POST['organization_id'] : null;
            
            if (empty($title)) {
                $error_message = 'Project title is required.';
            } else {
                $start_date_value = !empty($start_date) ? "'$start_date'" : 'NULL';
                $end_date_value = !empty($end_date) ? "'$end_date'" : 'NULL';
                $start_time_value = !empty($start_time) ? "'$start_time'" : 'NULL';
                $end_time_value = !empty($end_time) ? "'$end_time'" : 'NULL';
                $capacity_value = $capacity ? $capacity : 'NULL';
                $org_value = $organization_id ? $organization_id : 'NULL';
                
                $sql = "INSERT INTO projects (title, description, location, start_date, end_date, start_time, end_time, 
                        capacity, skills_needed, requirements, priority, organization_id, created_by, status) 
                        VALUES ('$title', '$description', '$location', $start_date_value, $end_date_value, 
                        $start_time_value, $end_time_value, $capacity_value, '$skills_needed', '$requirements', 
                        '$priority', $org_value, {$current_user['user_id']}, 'approved')";
                
                if (mysqli_query($connection, $sql)) {
                    $success_message = 'Project created successfully.';
                } else {
                    $error_message = 'Error creating project: ' . mysqli_error($connection);
                }
            }
            break;
    }
}

include 'includes/header.php';
?>

<!-- Admin Dashboard Specific Styles -->
<link rel="stylesheet" href="assets/css/admin_dashboard.css">

<div class="admin-dashboard">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($current_user['name']); ?>!</p>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Analytics Dashboard -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Users</h3>
            <div class="stat-number" id="total-users">Loading...</div>
        </div>
        <div class="stat-card">
            <h3>Organizations</h3>
            <div class="stat-number" id="total-organizations">Loading...</div>
        </div>
        <div class="stat-card">
            <h3>Total Projects</h3>
            <div class="stat-number" id="total-projects">Loading...</div>
        </div>
        <div class="stat-card">
            <h3>Pending Projects</h3>
            <div class="stat-number" id="pending-projects">Loading...</div>
        </div>
        <div class="stat-card">
            <h3>Active Assignments</h3>
            <div class="stat-number" id="active-assignments">Loading...</div>
        </div>
    </div>
    
    <!-- Tab Navigation -->
    <div class="tabs">
        <button class="tab-button active" onclick="showTab('users')">User Management</button>
        <button class="tab-button" onclick="showTab('organizations')">Organizations</button>
        <button class="tab-button" onclick="showTab('projects')">Projects</button>
        <button class="tab-button" onclick="showTab('assignments')">Assignments</button>
        <button class="tab-button" onclick="showTab('reports')">Reports</button>
    </div>
    
    <!-- User Management Tab -->
    <div id="users-tab" class="tab-content active">
        <div class="section-header">
            <h2>User Management</h2>
            <button class="btn btn-primary" onclick="showCreateUserForm()">Create New User</button>
        </div>
        
        <!-- Create User Form (Initially Hidden) -->
        <div id="create-user-form" class="form-container" style="display: none;">
            <h3>Create New User</h3>
            <form method="POST" onsubmit="return confirmCreate()">
                <input type="hidden" name="action" value="create_user">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="organization">Organization</option>
                            <option value="volunteer">Volunteer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="organization_id">Organization</label>
                        <select id="organization_id" name="organization_id">
                            <option value="">None</option>
                            <?php
                            $orgs = mysqli_query($connection, "SELECT org_id, name FROM organizations ORDER BY name");
                            while ($org = mysqli_fetch_assoc($orgs)) {
                                echo "<option value='{$org['org_id']}'>" . htmlspecialchars($org['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create User</button>
                    <button type="button" class="btn btn-secondary" onclick="hideCreateUserForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Organization</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $users_query = "SELECT u.*, o.name as org_name 
                                   FROM users u 
                                   LEFT JOIN organizations o ON u.organization_id = o.org_id 
                                   ORDER BY u.user_id DESC";
                    $users = mysqli_query($connection, $users_query);
                    while ($user = mysqli_fetch_assoc($users)):
                    ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                        <td><?php echo htmlspecialchars($user['org_name'] ?? 'None'); ?></td>
                        <td><span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                        <td class="actions">
                            <button class="btn btn-small btn-secondary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</button>
                            <?php if ($user['user_id'] !== $current_user['user_id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Organizations Tab -->
    <div id="organizations-tab" class="tab-content">
        <div class="section-header">
            <h2>Organization Management</h2>
            <button class="btn btn-primary" onclick="showCreateOrgForm()">Create New Organization</button>
        </div>
        
        <!-- Create Organization Form -->
        <div id="create-org-form" class="form-container" style="display: none;">
            <h3>Create New Organization</h3>
            <form method="POST" onsubmit="return confirmCreate()">
                <input type="hidden" name="action" value="create_organization">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="org-name">Name *</label>
                        <input type="text" id="org-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="org-contact-email">Contact Email</label>
                        <input type="email" id="org-contact-email" name="contact_email">
                    </div>
                    <div class="form-group">
                        <label for="org-contact-phone">Contact Phone</label>
                        <input type="text" id="org-contact-phone" name="contact_phone">
                    </div>
                </div>
                <div class="form-group">
                    <label for="org-description">Description</label>
                    <textarea id="org-description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="org-address">Address</label>
                    <textarea id="org-address" name="address" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Organization</button>
                    <button type="button" class="btn btn-secondary" onclick="hideCreateOrgForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- Organizations Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact Email</th>
                        <th>Contact Phone</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $orgs_query = "SELECT * FROM organizations ORDER BY created_at DESC";
                    $organizations = mysqli_query($connection, $orgs_query);
                    while ($org = mysqli_fetch_assoc($organizations)):
                    ?>
                    <tr>
                        <td><?php echo $org['org_id']; ?></td>
                        <td><?php echo htmlspecialchars($org['name']); ?></td>
                        <td><?php echo htmlspecialchars($org['contact_email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($org['contact_phone'] ?? ''); ?></td>
                        <td><?php echo date('M j, Y', strtotime($org['created_at'])); ?></td>
                        <td class="actions">
                            <button class="btn btn-small btn-secondary" onclick="editOrg(<?php echo htmlspecialchars(json_encode($org)); ?>)">Edit</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                <input type="hidden" name="action" value="delete_organization">
                                <input type="hidden" name="org_id" value="<?php echo $org['org_id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Projects Tab -->
    <div id="projects-tab" class="tab-content">
        <div class="section-header">
            <h2>Project Management</h2>
            <button class="btn btn-primary" onclick="showCreateProjectForm()">Create New Project</button>
        </div>
        
        <!-- Create Project Form (Initially Hidden) -->
        <div id="create-project-form" class="form-container" style="display: none;">
            <h3>Create New Project</h3>
            <form method="POST" onsubmit="return confirmCreate()">
                <input type="hidden" name="action" value="create_project">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="project-title">Title *</label>
                        <input type="text" id="project-title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="project-location">Location</label>
                        <input type="text" id="project-location" name="location">
                    </div>
                    <div class="form-group">
                        <label for="project-start-date">Start Date</label>
                        <input type="date" id="project-start-date" name="start_date">
                    </div>
                    <div class="form-group">
                        <label for="project-end-date">End Date</label>
                        <input type="date" id="project-end-date" name="end_date">
                    </div>
                    <div class="form-group">
                        <label for="project-start-time">Start Time</label>
                        <input type="time" id="project-start-time" name="start_time">
                    </div>
                    <div class="form-group">
                        <label for="project-end-time">End Time</label>
                        <input type="time" id="project-end-time" name="end_time">
                    </div>
                    <div class="form-group">
                        <label for="project-capacity">Capacity</label>
                        <input type="number" id="project-capacity" name="capacity" min="1">
                    </div>
                    <div class="form-group">
                        <label for="project-priority">Priority</label>
                        <select id="project-priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="project-organization-id">Organization</label>
                        <select id="project-organization-id" name="organization_id">
                            <option value="">None (Admin Project)</option>
                            <?php
                            $orgs = mysqli_query($connection, "SELECT org_id, name FROM organizations ORDER BY name");
                            while ($org = mysqli_fetch_assoc($orgs)) {
                                echo "<option value='{$org['org_id']}'>" . htmlspecialchars($org['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="project-skills-needed">Skills Needed</label>
                    <textarea id="project-skills-needed" name="skills_needed" rows="2" placeholder="e.g., Communication, Teamwork, Computer Skills"></textarea>
                </div>
                <div class="form-group">
                    <label for="project-requirements">Requirements</label>
                    <textarea id="project-requirements" name="requirements" rows="2" placeholder="Special requirements or qualifications"></textarea>
                </div>
                <div class="form-group">
                    <label for="project-description">Description</label>
                    <textarea id="project-description" name="description" rows="4" placeholder="Describe the project goals, activities, and requirements..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Project</button>
                    <button type="button" class="btn btn-secondary" onclick="hideCreateProjectForm()">Cancel</button>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Organization</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>Capacity</th>
                        <th>Current Volunteers</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $projects_query = "SELECT p.*, o.name as org_name 
                                      FROM projects p 
                                      LEFT JOIN organizations o ON p.organization_id = o.org_id 
                                      ORDER BY p.created_at DESC";
                    $projects = mysqli_query($connection, $projects_query);
                    while ($project = mysqli_fetch_assoc($projects)):
                    ?>
                    <tr>
                        <td><?php echo $project['project_id']; ?></td>
                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                        <td><?php echo htmlspecialchars($project['org_name'] ?? 'Guest Submission'); ?></td>
                        <td>
                            <select onchange="updateProjectStatus(<?php echo $project['project_id']; ?>, this.value)" class="status-select">
                                <option value="pending" <?php echo $project['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $project['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="active" <?php echo $project['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $project['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </td>
                        <td><?php echo $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'TBD'; ?></td>
                        <td><?php echo $project['capacity'] ?: 'Unlimited'; ?></td>
                        <td><?php echo $project['current_volunteers']; ?></td>
                        <td class="actions">
                            <?php if ($project['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this project?')">
                                    <input type="hidden" name="action" value="approve_project">
                                    <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                    <button type="submit" class="btn btn-small btn-success">Approve</button>
                                </form>
                            <?php endif; ?>
                            <button class="btn btn-small btn-secondary" onclick="viewProject(<?php echo $project['project_id']; ?>)">View</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                <input type="hidden" name="action" value="delete_project">
                                <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Assignments Tab -->
    <div id="assignments-tab" class="tab-content">
        <div class="section-header">
            <h2>Assignment Tracking</h2>
        </div>
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Assignment ID</th>
                        <th>Volunteer</th>
                        <th>Project</th>
                        <th>Organization</th>
                        <th>Status</th>
                        <th>Hours</th>
                        <th>Assigned Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $assignments_query = "SELECT vp.*, u.name as volunteer_name, u.email as volunteer_email, 
                                                 p.title as project_title, o.name as org_name
                                         FROM volunteer_projects vp
                                         LEFT JOIN users u ON vp.volunteer_id = u.user_id
                                         LEFT JOIN projects p ON vp.project_id = p.project_id
                                         LEFT JOIN organizations o ON p.organization_id = o.org_id
                                         ORDER BY vp.assigned_at DESC";
                    $assignments = mysqli_query($connection, $assignments_query);
                    while ($assignment = mysqli_fetch_assoc($assignments)):
                    ?>
                    <tr>
                        <td><?php echo $assignment['id']; ?></td>
                        <td>
                            <div><?php echo htmlspecialchars($assignment['volunteer_name']); ?></div>
                            <small><?php echo htmlspecialchars($assignment['volunteer_email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($assignment['project_title']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['org_name'] ?? 'N/A'); ?></td>
                        <td><span class="status-badge <?php echo $assignment['status']; ?>"><?php echo ucfirst($assignment['status']); ?></span></td>
                        <td><?php echo $assignment['hours_contributed']; ?> hrs</td>
                        <td><?php echo date('M j, Y', strtotime($assignment['assigned_at'])); ?></td>
                        <td class="actions">
                            <button class="btn btn-small btn-secondary" onclick="viewAssignment(<?php echo $assignment['id']; ?>)">View Details</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Reports Tab -->
    <div id="reports-tab" class="tab-content">
        <div class="section-header">
            <h2>System Reports</h2>
        </div>
        
        <div class="reports-grid">
            <div class="report-card">
                <h3>User Activity Report</h3>
                <div class="report-stats">
                    <?php
                    $active_users = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE is_active = 1");
                    $inactive_users = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE is_active = 0");
                    $admin_count = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
                    $org_count = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'organization'");
                    $volunteer_count = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'volunteer'");
                    ?>
                    <p>Active Users: <strong><?php echo mysqli_fetch_assoc($active_users)['count']; ?></strong></p>
                    <p>Inactive Users: <strong><?php echo mysqli_fetch_assoc($inactive_users)['count']; ?></strong></p>
                    <p>Admins: <strong><?php echo mysqli_fetch_assoc($admin_count)['count']; ?></strong></p>
                    <p>Organizations: <strong><?php echo mysqli_fetch_assoc($org_count)['count']; ?></strong></p>
                    <p>Volunteers: <strong><?php echo mysqli_fetch_assoc($volunteer_count)['count']; ?></strong></p>
                </div>
            </div>
            
            <div class="report-card">
                <h3>Project Status Report</h3>
                <div class="report-stats">
                    <?php
                    $pending_projects = mysqli_query($connection, "SELECT COUNT(*) as count FROM projects WHERE status = 'pending'");
                    $approved_projects = mysqli_query($connection, "SELECT COUNT(*) as count FROM projects WHERE status = 'approved'");
                    $active_projects = mysqli_query($connection, "SELECT COUNT(*) as count FROM projects WHERE status = 'active'");
                    $completed_projects = mysqli_query($connection, "SELECT COUNT(*) as count FROM projects WHERE status = 'completed'");
                    ?>
                    <p>Pending: <strong><?php echo mysqli_fetch_assoc($pending_projects)['count']; ?></strong></p>
                    <p>Approved: <strong><?php echo mysqli_fetch_assoc($approved_projects)['count']; ?></strong></p>
                    <p>Active: <strong><?php echo mysqli_fetch_assoc($active_projects)['count']; ?></strong></p>
                    <p>Completed: <strong><?php echo mysqli_fetch_assoc($completed_projects)['count']; ?></strong></p>
                </div>
            </div>
            
            <div class="report-card">
                <h3>Assignment Status Report</h3>
                <div class="report-stats">
                    <?php
                    $registered_assignments = mysqli_query($connection, "SELECT COUNT(*) as count FROM volunteer_projects WHERE status = 'registered'");
                    $confirmed_assignments = mysqli_query($connection, "SELECT COUNT(*) as count FROM volunteer_projects WHERE status = 'confirmed'");
                    $completed_assignments = mysqli_query($connection, "SELECT COUNT(*) as count FROM volunteer_projects WHERE status = 'completed'");
                    $total_hours = mysqli_query($connection, "SELECT SUM(hours_contributed) as total FROM volunteer_projects");
                    ?>
                    <p>Registered: <strong><?php echo mysqli_fetch_assoc($registered_assignments)['count']; ?></strong></p>
                    <p>Confirmed: <strong><?php echo mysqli_fetch_assoc($confirmed_assignments)['count']; ?></strong></p>
                    <p>Completed: <strong><?php echo mysqli_fetch_assoc($completed_assignments)['count']; ?></strong></p>
                    <p>Total Hours: <strong><?php echo mysqli_fetch_assoc($total_hours)['total'] ?: '0'; ?></strong></p>
                </div>
            </div>
            
            <div class="report-card">
                <h3>Top Organizations</h3>
                <div class="report-stats">
                    <?php
                    $top_orgs_query = "SELECT o.name, COUNT(p.project_id) as project_count 
                                      FROM organizations o 
                                      LEFT JOIN projects p ON o.org_id = p.organization_id 
                                      GROUP BY o.org_id, o.name 
                                      ORDER BY project_count DESC 
                                      LIMIT 5";
                    $top_orgs = mysqli_query($connection, $top_orgs_query);
                    while ($org = mysqli_fetch_assoc($top_orgs)):
                    ?>
                    <p><?php echo htmlspecialchars($org['name']); ?>: <strong><?php echo $org['project_count']; ?> projects</strong></p>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-user-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditUserModal()">&times;</span>
        <h3>Edit User</h3>
        <form id="edit-user-form" method="POST" onsubmit="return confirmUpdate()">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit-user-id">
            <div class="form-grid">
                <div class="form-group">
                    <label for="edit-name">Name *</label>
                    <input type="text" id="edit-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit-username">Username *</label>
                    <input type="text" id="edit-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="edit-email">Email *</label>
                    <input type="email" id="edit-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit-role">Role *</label>
                    <select id="edit-role" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="organization">Organization</option>
                        <option value="volunteer">Volunteer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-phone">Phone</label>
                    <input type="text" id="edit-phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="edit-organization-id">Organization</label>
                    <select id="edit-organization-id" name="organization_id">
                        <option value="">None</option>
                        <?php
                        $orgs = mysqli_query($connection, "SELECT org_id, name FROM organizations ORDER BY name");
                        while ($org = mysqli_fetch_assoc($orgs)) {
                            echo "<option value='{$org['org_id']}'>" . htmlspecialchars($org['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit-is-active" name="is_active"> Active
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label for="edit-address">Address</label>
                <textarea id="edit-address" name="address" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update User</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Organization Modal -->
<div id="edit-org-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditOrgModal()">&times;</span>
        <h3>Edit Organization</h3>
        <form id="edit-org-form" method="POST" onsubmit="return confirmUpdate()">
            <input type="hidden" name="action" value="update_organization">
            <input type="hidden" name="org_id" id="edit-org-id">
            <div class="form-grid">
                <div class="form-group">
                    <label for="edit-org-name">Name *</label>
                    <input type="text" id="edit-org-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit-org-contact-email">Contact Email</label>
                    <input type="email" id="edit-org-contact-email" name="contact_email">
                </div>
                <div class="form-group">
                    <label for="edit-org-contact-phone">Contact Phone</label>
                    <input type="text" id="edit-org-contact-phone" name="contact_phone">
                </div>
            </div>
            <div class="form-group">
                <label for="edit-org-description">Description</label>
                <textarea id="edit-org-description" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="edit-org-address">Address</label>
                <textarea id="edit-org-address" name="address" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Organization</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditOrgModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Project Details Modal -->
<div id="project-details-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeProjectDetailsModal()">&times;</span>
        <h2>Project Details</h2>
        <div id="project-details-content">
            <div class="loading">Loading project details...</div>
        </div>
    </div>
</div>

<!-- Assignment Details Modal -->
<div id="assignment-details-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeAssignmentDetailsModal()">&times;</span>
        <h2>Assignment Details</h2>
        <div id="assignment-details-content">
            <div class="loading">Loading assignment details...</div>
        </div>
    </div>
</div>

<!-- Admin Dashboard Specific JavaScript -->
<script src="assets/js/admin_dashboard.js"></script>

<?php include 'includes/footer.php'; ?>
