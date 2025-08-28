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
                // Check project status and availability with detailed info
                $project = getSingleRecord("
                    SELECT p.*, o.name as org_name,
                           (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id) as current_volunteers
                    FROM projects p 
                    JOIN organizations o ON p.organization_id = o.org_id
                    WHERE p.project_id = ? AND p.status = 'approved'
                ", [$project_id]);
                
                if (!$project) {
                    $error = 'This project is not available for joining.';
                } elseif ($project['max_volunteers'] && (int)$project['current_volunteers'] >= (int)$project['max_volunteers']) {
                    $error = 'This project has reached its maximum number of volunteers (' . $project['max_volunteers'] . ').';
                } elseif ($project['end_date'] && strtotime($project['end_date']) < time()) {
                    $error = 'This project has already ended.';
                } else {
                    // Check organization constraint
                    $user = getSingleRecord("SELECT organization_id FROM users WHERE user_id = ?", [$user_id]);
                    if ($user && $user['organization_id'] && (int)$user['organization_id'] !== (int)$project['organization_id']) {
                        $error = 'You can only join projects from your current organization (' . htmlspecialchars($project['org_name']) . '). Leave your current organization first to join projects from other organizations.';
                    } else {
                        // Join the project
                        $registration_code = generateRegistrationCode();
                        insertRecord(
                            "INSERT INTO volunteer_projects (volunteer_id, project_id, status, registration_code) VALUES (?, ?, 'registered', ?)",
                            [$user_id, $project_id, $registration_code]
                        );
                        
                        // Set organization if not set
                        if (!$user['organization_id']) {
                            updateRecord(
                                "UPDATE users SET organization_id = ? WHERE user_id = ?",
                                [$project['organization_id'], $user_id]
                            );
                        }
                        
                        $success = 'Successfully joined "' . htmlspecialchars($project['title']) . '"! You are now part of ' . htmlspecialchars($project['org_name']) . '. Your registration code is: ' . $registration_code;
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
$filter_skills = $_GET['skills'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';
$show_full = isset($_GET['show_full']) && $_GET['show_full'] === '1';

// Build query for approved projects
$sql = "
    SELECT p.*, o.name as org_name, o.contact_email, o.website,
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

// Filter by skills (search in description)
if ($filter_skills) {
    $sql .= " AND (p.description LIKE ? OR p.title LIKE ?)";
    $params[] = '%' . $filter_skills . '%';
    $params[] = '%' . $filter_skills . '%';
}

// Filter out full projects unless specifically requested
if (!$show_full) {
    $sql .= " AND (p.max_volunteers IS NULL OR (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id) < p.max_volunteers)";
}

// Filter out ended projects
$sql .= " AND (p.end_date IS NULL OR p.end_date >= CURDATE())";

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
    case 'urgency':
        $sql .= " ORDER BY p.start_date ASC, p.created_at DESC";
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

// Get common skills from project descriptions for filter suggestions
$common_skills = ['Teaching', 'Event Management', 'Social Media', 'Programming', 'First Aid', 
                  'Fundraising', 'Marketing', 'Photography', 'Writing', 'Childcare', 
                  'Elderly Care', 'Environmental', 'Construction', 'Art', 'Music'];

<?php if ($success): ?>
    <div class="success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="section-divider">
        <h2>🔍 Discover Volunteer Opportunities</h2>
    </div>
    <p>Find meaningful volunteer projects from verified organizations. 
    <?php if ($user_role === 'volunteer'): ?>
        Join projects that match your skills and interests!
    <?php else: ?>
        <a href="login.php">Login as a volunteer</a> to join projects.
    <?php endif; ?>
    </p>
    
    <!-- Enhanced Filters -->
    <form method="GET" style="margin: 20px 0; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; border: 1px solid #dee2e6;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: bold; color: #495057; font-size: 13px;">🏢 Organization</label>
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
                <label style="font-weight: bold; color: #495057; font-size: 13px;">📍 Location</label>
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
                <label style="font-weight: bold; color: #495057; font-size: 13px;">🎯 Skills/Keywords</label>
                <input type="text" name="skills" value="<?php echo htmlspecialchars($filter_skills); ?>" placeholder="e.g. teaching, fundraising" list="skills-list">
                <datalist id="skills-list">
                    <?php foreach ($common_skills as $skill): ?>
                        <option value="<?php echo htmlspecialchars($skill); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: bold; color: #495057; font-size: 13px;">🔄 Sort By</label>
                <select name="sort">
                    <option value="newest" <?php echo ($sort_by === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo ($sort_by === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="urgency" <?php echo ($sort_by === 'urgency') ? 'selected' : ''; ?>>Most Urgent</option>
                    <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>Title A-Z</option>
                    <option value="start_date" <?php echo ($sort_by === 'start_date') ? 'selected' : ''; ?>>Start Date</option>
                    <option value="volunteers" <?php echo ($sort_by === 'volunteers') ? 'selected' : ''; ?>>Most Volunteers</option>
                </select>
            </div>
        </div>
        
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <label style="display: flex; align-items: center; font-size: 13px; color: #495057;">
                <input type="checkbox" name="show_full" value="1" <?php echo $show_full ? 'checked' : ''; ?> style="margin-right: 5px;">
                Show full projects
            </label>
            
            <div class="action-buttons" style="margin: 0;">
                <button type="submit" class="btn" style="margin: 0;">🔍 Search & Filter</button>
                <?php if ($filter_org || $filter_location || $filter_skills || $sort_by !== 'newest' || $show_full): ?>
                    <a href="browse_projects_new.php" class="btn" style="margin: 0; margin-left: 5px;">✖️ Clear All</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
    
    <!-- Search Results Summary -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin: 15px 0; padding: 10px; background: #e7f3ff; border-radius: 4px;">
        <div>
            <strong><?php echo count($projects); ?> projects found</strong>
            <?php if ($filter_org || $filter_location || $filter_skills): ?>
                <span style="color: #666;">
                    (filtered by 
                    <?php 
                    $filters = [];
                    if ($filter_org) $filters[] = '"' . htmlspecialchars($filter_org) . '"';
                    if ($filter_location) $filters[] = 'location "' . htmlspecialchars($filter_location) . '"';
                    if ($filter_skills) $filters[] = 'skills "' . htmlspecialchars($filter_skills) . '"';
                    echo implode(', ', $filters);
                    ?>)
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ($user_role === 'volunteer'): ?>
            <div style="font-size: 12px; color: #666;">
                <span>💡 Tip: Projects you've already joined are hidden from this list</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($projects)): ?>
    <div class="card" style="text-align: center; padding: 50px;">
        <h3>🔍 No Projects Found</h3>
        <?php if ($filter_org || $filter_location || $filter_skills): ?>
            <p>No projects match your current search criteria.</p>
            <div style="margin: 20px 0;">
                <a href="browse_projects_new.php" class="btn">View All Available Projects</a>
                <button onclick="window.history.back()" class="btn">Modify Search</button>
            </div>
        <?php else: ?>
            <p>No approved volunteer opportunities are available at the moment.</p>
            <p style="color: #666;">Check back later or contact organizations directly!</p>
        <?php endif; ?>
        
        <?php if ($user_role === 'volunteer' && $user_id): ?>
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <p style="font-size: 14px; color: #666;">
                    Remember: You might have already joined available projects. 
                    <a href="volunteer_dashboard_new.php">Check your dashboard</a> to see your current projects.
                </p>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Enhanced Project Cards -->
    <?php foreach ($projects as $index => $project): ?>
    <?php 
    $is_full = $project['max_volunteers'] && (int)$project['volunteer_count'] >= (int)$project['max_volunteers'];
    $is_ending_soon = $project['end_date'] && strtotime($project['end_date']) <= strtotime('+30 days');
    $is_new = strtotime($project['created_at']) >= strtotime('-7 days');
    ?>
    
    <div class="card project-card" style="position: relative; <?php echo $is_full ? 'opacity: 0.85; border-left-color: #dc3545;' : ''; ?>">
        <!-- Project badges -->
        <div style="position: absolute; top: 15px; right: 15px; display: flex; gap: 5px; flex-direction: column; align-items: end;">
            <?php if ($is_new): ?>
                <span class="status-badge" style="background: #28a745; color: white; font-size: 10px;">🆕 NEW</span>
            <?php endif; ?>
            
            <?php if ($is_ending_soon && $project['end_date']): ?>
                <span class="status-badge" style="background: #ffc107; color: #000; font-size: 10px;">⏰ ENDING SOON</span>
            <?php endif; ?>
            
            <?php if ($is_full): ?>
                <span class="status-badge" style="background: #dc3545; color: white; font-size: 10px;">👥 FULL</span>
            <?php endif; ?>
        </div>
        
        <!-- Project Header -->
        <div style="margin-bottom: 15px; padding-right: 100px;">
            <h3 style="color: #007bff; margin-bottom: 5px;"><?php echo htmlspecialchars($project['title']); ?></h3>
            <div style="display: flex; align-items: center; gap: 15px; font-size: 14px; color: #666;">
                <span>🏢 <strong><?php echo htmlspecialchars($project['org_name']); ?></strong></span>
                <span>📅 Posted <?php echo formatDate($project['created_at']); ?></span>
            </div>
        </div>
        
        <!-- Project Info Grid -->
        <div class="info-grid">
            <div class="info-item">
                <strong>📊 Team Status:</strong><br>
                <?php echo (int)$project['volunteer_count']; ?> volunteer<?php echo (int)$project['volunteer_count'] !== 1 ? 's' : ''; ?>
                <?php if ($project['max_volunteers']): ?>
                    / <?php echo (int)$project['max_volunteers']; ?> max
                    <?php 
                    $percentage = ((int)$project['volunteer_count'] / (int)$project['max_volunteers']) * 100;
                    $color = $percentage >= 90 ? '#dc3545' : ($percentage >= 70 ? '#ffc107' : '#28a745');
                    ?>
                    <div style="width: 100%; height: 6px; background: #e9ecef; border-radius: 3px; margin-top: 3px;">
                        <div style="width: <?php echo min(100, $percentage); ?>%; height: 100%; background: <?php echo $color; ?>; border-radius: 3px;"></div>
                    </div>
                <?php else: ?>
                    <span style="color: #28a745; font-size: 12px;">(unlimited)</span>
                <?php endif; ?>
            </div>
            
            <?php if ($project['location']): ?>
            <div class="info-item">
                <strong>📍 Location:</strong><br>
                <?php echo htmlspecialchars($project['location']); ?>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <strong>⏱️ Timeline:</strong><br>
                <?php if ($project['start_date']): ?>
                    <?php echo formatDate($project['start_date']); ?>
                    <?php if ($project['end_date']): ?>
                        <br>to <?php echo formatDate($project['end_date']); ?>
                        <?php if ($is_ending_soon): ?>
                            <span style="color: #dc3545; font-size: 11px;">(ends soon!)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <br><span style="color: #666;">(ongoing)</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color: #666;">Flexible timing</span>
                <?php endif; ?>
            </div>
            
            <div class="info-item">
                <strong>📧 Organization Contact:</strong><br>
                <?php if ($project['contact_email']): ?>
                    <a href="mailto:<?php echo htmlspecialchars($project['contact_email']); ?>" style="font-size: 12px;">
                        <?php echo htmlspecialchars($project['contact_email']); ?>
                    </a>
                <?php else: ?>
                    <span style="color: #666; font-size: 12px;">Contact via project</span>
                <?php endif; ?>
                <?php if ($project['website']): ?>
                    <br><a href="<?php echo htmlspecialchars($project['website']); ?>" target="_blank" rel="noopener" style="font-size: 12px;">
                        🌐 Website
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Project Description -->
        <?php if ($project['description']): ?>
            <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">
                <strong>📝 About this opportunity:</strong>
                <p style="margin: 8px 0 0 0; line-height: 1.5;"><?php echo htmlspecialchars($project['description']); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Action Section -->
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #dee2e6;">
            <?php if ($user_id && $user_role === 'volunteer'): ?>
                <?php if ($is_full): ?>
                    <div style="text-align: center; color: #dc3545;">
                        <strong>❌ Project is Full</strong>
                        <p style="font-size: 13px; margin: 5px 0;">This project has reached its maximum number of volunteers (<?php echo $project['max_volunteers']; ?>).</p>
                        <?php if ($project['contact_email']): ?>
                            <p style="font-size: 12px; color: #666;">You can still <a href="mailto:<?php echo htmlspecialchars($project['contact_email']); ?>">contact the organization</a> to ask about future opportunities.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <form method="POST" onsubmit="return confirmJoin(this)" style="display: inline-block;">
                                <input type="hidden" name="action" value="join_project">
                                <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                <input type="hidden" name="confirmed" value="false">
                                <button type="submit" class="btn btn-success" style="font-size: 14px; padding: 8px 20px;">
                                    🚀 Join This Project
                                </button>
                            </form>
                            
                            <?php if (!isset($_SESSION['organization_id']) || !$_SESSION['organization_id']): ?>
                                <p style="font-size: 12px; color: #666; margin: 5px 0;">
                                    💡 Joining will automatically add you to <strong><?php echo htmlspecialchars($project['org_name']); ?></strong>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div style="text-align: right; color: #666; font-size: 12px;">
                            <?php if ($project['max_volunteers']): ?>
                                <?php $spots_left = (int)$project['max_volunteers'] - (int)$project['volunteer_count']; ?>
                                <span style="color: <?php echo $spots_left <= 2 ? '#dc3545' : '#666'; ?>;">
                                    <?php echo $spots_left; ?> spot<?php echo $spots_left !== 1 ? 's' : ''; ?> remaining
                                </span>
                            <?php else: ?>
                                <span style="color: #28a745;">Unlimited spots available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php elseif ($user_id && $user_role !== 'volunteer'): ?>
                <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 4px;">
                    <strong>ℹ️ Information</strong>
                    <p style="margin: 5px 0; font-size: 14px;">Only volunteers can join projects. Your current role is: <?php echo htmlspecialchars(ucfirst($user_role)); ?></p>
                </div>
            <?php else: ?>
                <div style="text-align: center;">
                    <a href="login.php" class="btn" style="background: #17a2b8; color: white;">🔐 Login to Join Projects</a>
                    <p style="font-size: 12px; color: #666; margin: 5px 0;">Create a volunteer account to start helping your community!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <!-- Information Footer -->
    <div class="card" style="background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%); border: 1px solid #007bff;">
        <div class="section-divider">
            <h4>💡 How Volunteer Registration Works</h4>
        </div>
        
        <div class="info-grid">
            <div style="text-align: center;">
                <strong style="color: #007bff;">1. Choose a Project</strong>
                <p style="font-size: 13px; margin: 5px 0;">Browse and find projects that match your interests and skills.</p>
            </div>
            <div style="text-align: center;">
                <strong style="color: #007bff;">2. Join Instantly</strong>
                <p style="font-size: 13px; margin: 5px 0;">One-click joining with automatic organization membership.</p>
            </div>
            <div style="text-align: center;">
                <strong style="color: #007bff;">3. Get Involved</strong>
                <p style="font-size: 13px; margin: 5px 0;">Start volunteering and access all organization activities.</p>
            </div>
        </div>
        
        <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.7); border-radius: 4px; font-size: 12px; color: #666;">
            <strong>Important:</strong> When you join a project, you automatically become part of that organization. 
            You can only be associated with one organization at a time. To switch organizations, 
            you must leave your current organization first from your <a href="volunteer_dashboard_new.php">volunteer dashboard</a>.
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<?php if ($success): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <h2>Available Projects</h2>
    <p>Browse approved volunteer opportunities from organizations.</p>
</div>

<?php if (empty($projects)): ?>
    <div class="card">
        <p>No projects available at the moment. Check back later!</p>
    </div>
<?php else: ?>
    <?php foreach ($projects as $project): ?>
    <div class="card">
        <h3><?php echo htmlspecialchars($project['title']); ?></h3>
        <p><strong>Organization:</strong> <?php echo htmlspecialchars($project['org_name']); ?></p>
        
        <?php if ($project['description']): ?>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($project['description']); ?></p>
        <?php endif; ?>
        
        <?php if ($project['location']): ?>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($project['location']); ?></p>
        <?php endif; ?>
        
        <?php if ($project['start_date']): ?>
            <p><strong>Start Date:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($project['start_date']))); ?></p>
        <?php endif; ?>
        
        <?php if ($project['end_date']): ?>
            <p><strong>End Date:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($project['end_date']))); ?></p>
        <?php endif; ?>
        
        <p><strong>Current Volunteers:</strong> <?php echo (int)$project['volunteer_count']; ?></p>
        
        <?php if ($user_id && $user_role === 'volunteer'): ?>
            <form method="POST" onsubmit="return confirmJoin(this)">
                <input type="hidden" name="action" value="join_project">
                <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                <input type="hidden" name="confirmed" value="false">
                <button type="submit" class="btn btn-success">Join Project</button>
            </form>
        <?php else: ?>
            <p><em>Login as a volunteer to join projects.</em></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
