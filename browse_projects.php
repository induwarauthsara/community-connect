<?php
require_once 'config/database.php';
require_once 'includes/common.php';

startSecureSession();

$success = '';
$error = '';
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

// Handle project join (volunteers only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_project') {
    if (!$user_id || $user_role !== 'volunteer') {
        $error = 'Please login as a volunteer to join projects.';
    } elseif (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    } else {
        $project_id = (int)$_POST['project_id'];
        
        try {
            // Check if already joined
            $existing = getSingleRecord(
                "SELECT * FROM volunteer_projects WHERE volunteer_id = ? AND project_id = ?",
                [$user_id, $project_id]
            );
            
            if ($existing) {
                $error = 'You have already joined this project.';
            } else {
                // Check project status and availability
                $project = getSingleRecord("
                    SELECT p.*, 
                           (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id) as current_volunteers
                    FROM projects p 
                    WHERE p.project_id = ? AND p.status = 'approved'
                ", [$project_id]);
                
                if (!$project) {
                    $error = 'This project is not available for joining.';
                } elseif ($project['max_volunteers'] && (int)$project['current_volunteers'] >= (int)$project['max_volunteers']) {
                    $error = 'This project has reached its maximum number of volunteers.';
                } else {
                    // Check organization constraint
                    $user = getSingleRecord("SELECT organization_id FROM users WHERE user_id = ?", [$user_id]);
                    if ($user && $user['organization_id'] && (int)$user['organization_id'] !== (int)$project['organization_id']) {
                        $error = 'You can only join projects from your current organization. Leave your current organization first to join projects from other organizations.';
                    } else {
                        // Join the project
                        insertRecord(
                            "INSERT INTO volunteer_projects (volunteer_id, project_id, status) VALUES (?, ?, 'registered')",
                            [$user_id, $project_id]
                        );
                        
                        // Set organization if not set
                        if (!$user['organization_id']) {
                            updateRecord(
                                "UPDATE users SET organization_id = ? WHERE user_id = ?",
                                [$project['organization_id'], $user_id]
                            );
                        }
                        
                        $success = 'Successfully joined the project! You are now part of the organization.';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Failed to join project. Please try again.';
        }
    }
}

// Get filter parameters
$filter_org = $_GET['organization'] ?? '';
$filter_location = $_GET['location'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';

// Build query for approved projects
$sql = "
    SELECT p.*, o.name as org_name,
           (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id) as volunteer_count
    FROM projects p
    JOIN organizations o ON p.organization_id = o.org_id
    WHERE p.status = 'approved'
";

$params = [];

// Filter by organization
if ($filter_org) {
    $sql .= " AND o.name LIKE ?";
    $params[] = '%' . $filter_org . '%';
}

// Filter by location
if ($filter_location) {
    $sql .= " AND p.location LIKE ?";
    $params[] = '%' . $filter_location . '%';
}

// Filter out already joined projects for volunteers
if ($user_id && $user_role === 'volunteer') {
    $sql .= " AND p.project_id NOT IN (
        SELECT project_id FROM volunteer_projects WHERE volunteer_id = ?
    )";
    $params[] = $user_id;
}

// Add sorting
switch ($sort_by) {
    case 'oldest':
        $sql .= " ORDER BY p.created_at ASC";
        break;
    case 'title':
        $sql .= " ORDER BY p.title ASC";
        break;
    case 'start_date':
        $sql .= " ORDER BY p.start_date ASC";
        break;
    case 'volunteers':
        $sql .= " ORDER BY volunteer_count DESC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

$projects = getMultipleRecords($sql, $params);

// Get available organizations for filter
$organizations = getMultipleRecords("
    SELECT DISTINCT o.name 
    FROM organizations o 
    JOIN projects p ON o.org_id = p.organization_id 
    WHERE p.status = 'approved' 
    ORDER BY o.name
");

// Get available locations for filter
$locations = getMultipleRecords("
    SELECT DISTINCT p.location 
    FROM projects p 
    WHERE p.status = 'approved' AND p.location IS NOT NULL AND p.location != ''
    ORDER BY p.location
");

$page_title = 'Browse Projects';
include 'includes/header.php';
?>

<?php if ($success): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="section-divider">
        <h2>Browse Available Projects</h2>
    </div>
    <p>Discover volunteer opportunities from approved organizations. 
    <?php if ($user_role === 'volunteer'): ?>
        Join projects to become part of their organization!
    <?php else: ?>
        <a href="login.php">Login as a volunteer</a> to join projects.
    <?php endif; ?>
    </p>
    
    <!-- Filters and Search -->
    <form method="GET" class="form-inline" style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 4px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 12px; color: #666;">Organization</label>
                <select name="organization">
                    <option value="">All Organizations</option>
                    <?php foreach ($organizations as $org): ?>
                        <option value="<?php echo htmlspecialchars($org['name']); ?>" <?php echo ($filter_org === $org['name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($org['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 12px; color: #666;">Location</label>
                <select name="location">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc['location']); ?>" <?php echo ($filter_location === $loc['location']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['location']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 12px; color: #666;">Sort By</label>
                <select name="sort">
                    <option value="newest" <?php echo ($sort_by === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo ($sort_by === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>Title A-Z</option>
                    <option value="start_date" <?php echo ($sort_by === 'start_date') ? 'selected' : ''; ?>>Start Date</option>
                    <option value="volunteers" <?php echo ($sort_by === 'volunteers') ? 'selected' : ''; ?>>Most Volunteers</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <button type="submit" class="btn" style="margin: 0;">Filter & Sort</button>
                <?php if ($filter_org || $filter_location || $sort_by !== 'newest'): ?>
                    <a href="browse_projects.php" class="btn" style="margin: 0; margin-left: 5px;">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
    
    <?php if ($filter_org || $filter_location): ?>
        <div class="text-small" style="margin-bottom: 15px;">
            Showing projects 
            <?php if ($filter_org): ?>from "<?php echo htmlspecialchars($filter_org); ?>"<?php endif; ?>
            <?php if ($filter_org && $filter_location): ?> and <?php endif; ?>
            <?php if ($filter_location): ?>in "<?php echo htmlspecialchars($filter_location); ?>"<?php endif; ?>
            - <?php echo count($projects); ?> projects found
        </div>
    <?php else: ?>
        <div class="text-small" style="margin-bottom: 15px;">
            Showing all available projects - <?php echo count($projects); ?> projects found
        </div>
    <?php endif; ?>
</div>

<?php if (empty($projects)): ?>
    <div class="card">
        <h3>No Projects Available</h3>
        <?php if ($filter_org || $filter_location): ?>
            <p>No projects match your current filters. <a href="browse_projects.php">View all projects</a> or try different filters.</p>
        <?php else: ?>
            <p>No approved volunteer opportunities available at the moment. Check back later!</p>
        <?php endif; ?>
        
        <?php if ($user_role === 'volunteer' && $user_id): ?>
            <p>You might also want to <a href="volunteer_dashboard.php">check your dashboard</a> to see projects you've already joined.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($projects as $project): ?>
    <div class="card project-card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
            <h3><?php echo htmlspecialchars($project['title']); ?></h3>
            <?php echo getStatusBadge($project['status']); ?>
        </div>
        
        <div class="org-info" style="margin-bottom: 15px;">
            <strong>Organization:</strong> <?php echo htmlspecialchars($project['org_name']); ?>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <strong>Current Volunteers:</strong><br>
                <?php echo (int)$project['volunteer_count']; ?>
                <?php if ($project['max_volunteers']): ?>
                    / <?php echo (int)$project['max_volunteers']; ?>
                    <?php if ((int)$project['volunteer_count'] >= (int)$project['max_volunteers']): ?>
                        <span style="color: #dc3545; font-size: 12px;">(FULL)</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-small">(no limit)</span>
                <?php endif; ?>
            </div>
            
            <?php if ($project['location']): ?>
            <div class="info-item">
                <strong>Location:</strong><br>
                <?php echo htmlspecialchars($project['location']); ?>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <strong>Duration:</strong><br>
                <?php if ($project['start_date']): ?>
                    <?php echo formatDate($project['start_date']); ?>
                    <?php if ($project['end_date']): ?>
                        to <?php echo formatDate($project['end_date']); ?>
                    <?php else: ?>
                        (ongoing)
                    <?php endif; ?>
                <?php else: ?>
                    Flexible timing
                <?php endif; ?>
            </div>
            
            <div class="info-item">
                <strong>Posted:</strong><br>
                <?php echo formatDate($project['created_at']); ?>
            </div>
        </div>
        
        <?php if ($project['description']): ?>
            <div style="margin: 15px 0;">
                <strong>About this opportunity:</strong>
                <p><?php echo htmlspecialchars($project['description']); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($user_id && $user_role === 'volunteer'): ?>
            <?php 
            $is_full = $project['max_volunteers'] && (int)$project['volunteer_count'] >= (int)$project['max_volunteers'];
            $is_past = $project['end_date'] && strtotime($project['end_date']) < time();
            ?>
            
            <?php if ($is_full): ?>
                <div class="action-buttons">
                    <button class="btn" disabled>Project Full</button>
                    <span class="text-small">This project has reached its maximum number of volunteers.</span>
                </div>
            <?php elseif ($is_past): ?>
                <div class="action-buttons">
                    <button class="btn" disabled>Project Ended</button>
                    <span class="text-small">This project has already ended.</span>
                </div>
            <?php else: ?>
                <div class="action-buttons">
                    <form method="POST" class="form-inline" onsubmit="return confirmJoin(this)">
                        <input type="hidden" name="action" value="join_project">
                        <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                        <input type="hidden" name="confirmed" value="false">
                        <button type="submit" class="btn btn-success">Join Project</button>
                    </form>
                    <span class="text-small">
                        <?php if (!isset($_SESSION['user_organization_id']) || !$_SESSION['user_organization_id']): ?>
                            Joining this project will add you to <?php echo htmlspecialchars($project['org_name']); ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
        <?php elseif ($user_id && $user_role !== 'volunteer'): ?>
            <div class="action-buttons">
                <span class="text-small"><em>Only volunteers can join projects.</em></span>
            </div>
        <?php else: ?>
            <div class="action-buttons">
                <a href="login.php" class="btn">Login to Join</a>
                <span class="text-small"><em>Login as a volunteer to join this project.</em></span>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    
    <div class="card">
        <div class="text-small">
            <p><strong>Note for volunteers:</strong> 
            When you join a project, you automatically become part of that organization. 
            You can only be associated with one organization at a time. 
            To switch organizations, you must leave your current organization from your dashboard first.</p>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
