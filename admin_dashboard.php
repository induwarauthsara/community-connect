<?php
require_once 'config/database.php';
require_once 'includes/common.php';

// Ensure user is logged in and is admin
requireLogin();
$current_user = getCurrentUser();
if ($current_user['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$page_title = 'Admin Dashboard - Community Connect';
$error_message = '';
$success_message = '';

// Handle AJAX requests for dynamic data
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    
    switch ($action) {
        case 'create_user':
            $name = mysqli_real_escape_string($connection, trim($_POST['name'] ?? ''));
            $email = mysqli_real_escape_string($connection, trim($_POST['email'] ?? ''));
            $password = mysqli_real_escape_string($connection, trim($_POST['password'] ?? ''));
            $role = mysqli_real_escape_string($connection, trim($_POST['role'] ?? ''));
            $phone = mysqli_real_escape_string($connection, trim($_POST['phone'] ?? ''));
            $address = mysqli_real_escape_string($connection, trim($_POST['address'] ?? ''));
            $organization_id = !empty($_POST['organization_id']) ? (int)$_POST['organization_id'] : null;
            
            if (empty($name) || empty($email) || empty($password) || empty($role)) {
                $error_message = 'All required fields must be filled.';
            } else {
                // Check if email already exists
                $check_email = mysqli_query($connection, "SELECT user_id FROM users WHERE email = '$email'");
                if (mysqli_num_rows($check_email) > 0) {
                    $error_message = 'Email already exists.';
                } else {
                    $org_clause = $organization_id ? ", organization_id = $organization_id" : "";
                    $sql = "INSERT INTO users (name, email, password, role, phone, address$org_clause, is_active, email_verified) 
                            VALUES ('$name', '$email', '$password', '$role', '$phone', '$address'" . 
                           ($organization_id ? ", $organization_id" : "") . ", 1, 1)";
                    
                    if (mysqli_query($connection, $sql)) {
                        $success_message = 'User created successfully.';
                    } else {
                        $error_message = 'Error creating user: ' . mysqli_error($connection);
                    }
                }
            }
            break;
            
        case 'update_user':
            $user_id = (int)$_POST['user_id'];
            $name = mysqli_real_escape_string($connection, trim($_POST['name'] ?? ''));
            $email = mysqli_real_escape_string($connection, trim($_POST['email'] ?? ''));
            $role = mysqli_real_escape_string($connection, trim($_POST['role'] ?? ''));
            $phone = mysqli_real_escape_string($connection, trim($_POST['phone'] ?? ''));
            $address = mysqli_real_escape_string($connection, trim($_POST['address'] ?? ''));
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $organization_id = !empty($_POST['organization_id']) ? (int)$_POST['organization_id'] : null;
            
            if (empty($name) || empty($email) || empty($role)) {
                $error_message = 'All required fields must be filled.';
            } else {
                $org_clause = $organization_id ? "organization_id = $organization_id" : "organization_id = NULL";
                $sql = "UPDATE users SET name = '$name', email = '$email', role = '$role', 
                        phone = '$phone', address = '$address', is_active = $is_active, $org_clause 
                        WHERE user_id = $user_id";
                
                if (mysqli_query($connection, $sql)) {
                    $success_message = 'User updated successfully.';
                } else {
                    $error_message = 'Error updating user: ' . mysqli_error($connection);
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
            
        case 'delete_project':
            $project_id = (int)$_POST['project_id'];
            if (mysqli_query($connection, "DELETE FROM projects WHERE project_id = $project_id")) {
                $success_message = 'Project deleted successfully.';
            } else {
                $error_message = 'Error deleting project: ' . mysqli_error($connection);
            }
            break;
    }
}

include 'includes/header.php';
?>

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

<style>
.admin-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: var(--primary-blue);
    font-size: 1rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--dark-blue);
}

.tabs {
    display: flex;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 20px;
}

.tab-button {
    background: none;
    border: none;
    padding: 12px 24px;
    cursor: pointer;
    font-size: 1rem;
    color: #666;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab-button:hover {
    color: var(--primary-blue);
}

.tab-button.active {
    color: var(--primary-blue);
    border-bottom-color: var(--primary-blue);
    font-weight: bold;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.section-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    margin: 0;
}

.form-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.form-actions {
    display: flex;
    gap: 10px;
    padding-top: 10px;
}

.table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: var(--primary-blue);
    color: white;
    padding: 12px;
    text-align: left;
    font-weight: bold;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #e0e0e0;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.role-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
}

.role-admin { background: #ff6b6b; color: white; }
.role-organization { background: #4ecdc4; color: white; }
.role-volunteer { background: #45b7d1; color: white; }

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
}

.status-badge.active { background: #51cf66; color: white; }
.status-badge.inactive { background: #868e96; color: white; }
.status-badge.registered { background: #74c0fc; color: white; }
.status-badge.confirmed { background: #51cf66; color: white; }
.status-badge.completed { background: #37b24d; color: white; }
.status-badge.cancelled { background: #f03e3e; color: white; }

.actions {
    display: flex;
    gap: 5px;
    align-items: center;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background-color 0.3s ease;
}

.btn-primary { background: var(--primary-blue); color: white; }
.btn-primary:hover { background: var(--dark-blue); }

.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #5a6268; }

.btn-success { background: #28a745; color: white; }
.btn-success:hover { background: #218838; }

.btn-danger { background: #dc3545; color: white; }
.btn-danger:hover { background: #c82333; }

.btn-small {
    padding: 4px 8px;
    font-size: 0.8rem;
}

.status-select {
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.report-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.report-card h3 {
    margin: 0 0 15px 0;
    color: var(--primary-blue);
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 10px;
}

.report-stats p {
    margin: 8px 0;
    display: flex;
    justify-content: space-between;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.close {
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.alert {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
    .tabs {
        flex-wrap: wrap;
    }
    
    .tab-button {
        padding: 8px 12px;
        font-size: 0.9rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .data-table {
        font-size: 0.9rem;
    }
    
    .data-table th, .data-table td {
        padding: 8px;
    }
}
</style>

<script>
// Tab functionality
function showTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => tab.classList.remove('active'));
    
    // Hide all tab buttons
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => button.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

// Load statistics
function loadStats() {
    fetch('admin_dashboard.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-users').textContent = data.total_users;
            document.getElementById('total-organizations').textContent = data.total_organizations;
            document.getElementById('total-projects').textContent = data.total_projects;
            document.getElementById('pending-projects').textContent = data.pending_projects;
            document.getElementById('active-assignments').textContent = data.active_assignments;
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Create user form functions
function showCreateUserForm() {
    document.getElementById('create-user-form').style.display = 'block';
}

function hideCreateUserForm() {
    document.getElementById('create-user-form').style.display = 'none';
}

// Create organization form functions
function showCreateOrgForm() {
    document.getElementById('create-org-form').style.display = 'block';
}

function hideCreateOrgForm() {
    document.getElementById('create-org-form').style.display = 'none';
}

// Edit user modal functions
function editUser(user) {
    document.getElementById('edit-user-id').value = user.user_id;
    document.getElementById('edit-name').value = user.name;
    document.getElementById('edit-email').value = user.email;
    document.getElementById('edit-role').value = user.role;
    document.getElementById('edit-phone').value = user.phone || '';
    document.getElementById('edit-address').value = user.address || '';
    document.getElementById('edit-organization-id').value = user.organization_id || '';
    document.getElementById('edit-is-active').checked = user.is_active == 1;
    
    document.getElementById('edit-user-modal').style.display = 'block';
}

function closeEditUserModal() {
    document.getElementById('edit-user-modal').style.display = 'none';
}

// Edit organization modal functions
function editOrg(org) {
    document.getElementById('edit-org-id').value = org.org_id;
    document.getElementById('edit-org-name').value = org.name;
    document.getElementById('edit-org-contact-email').value = org.contact_email || '';
    document.getElementById('edit-org-contact-phone').value = org.contact_phone || '';
    document.getElementById('edit-org-description').value = org.description || '';
    document.getElementById('edit-org-address').value = org.address || '';
    
    document.getElementById('edit-org-modal').style.display = 'block';
}

function closeEditOrgModal() {
    document.getElementById('edit-org-modal').style.display = 'none';
}

// Project functions
function updateProjectStatus(projectId, status) {
    if (confirm('Update project status to ' + status + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_project_status">
            <input type="hidden" name="project_id" value="${projectId}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    } else {
        // Reset the select if cancelled
        location.reload();
    }
}

function viewProject(projectId) {
    // This could open a modal or redirect to a detailed view
    alert('View project details for ID: ' + projectId + '\n(Feature can be expanded)');
}

function viewAssignment(assignmentId) {
    // This could open a modal or redirect to a detailed view
    alert('View assignment details for ID: ' + assignmentId + '\n(Feature can be expanded)');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const userModal = document.getElementById('edit-user-modal');
    const orgModal = document.getElementById('edit-org-modal');
    
    if (event.target === userModal) {
        closeEditUserModal();
    }
    if (event.target === orgModal) {
        closeEditOrgModal();
    }
}

// Load statistics on page load
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
});
</script>

<?php include 'includes/footer.php'; ?>
