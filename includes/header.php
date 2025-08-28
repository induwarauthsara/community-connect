<?php
/**
 * Shared Header Component - Community Connect
 * Blue and White theme, minimal design
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Community Connect'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f8f9fa; 
            color: #333; 
            line-height: 1.6; 
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .header { 
            background: #007bff; 
            color: white; 
            padding: 15px 0; 
            margin-bottom: 20px; 
        }
        .header h1 { 
            text-align: center; 
            font-size: 1.8rem; 
        }
        .nav { 
            text-align: center; 
            margin-top: 10px; 
        }
        .nav a { 
            color: white; 
            text-decoration: none; 
            margin: 0 15px; 
            padding: 5px 10px; 
            border-radius: 4px; 
        }
        .nav a:hover { 
            background: rgba(255,255,255,0.2); 
        }
        .card { 
            background: white; 
            border: 1px solid #e9ecef; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #495057; 
        }
        input, textarea, select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ced4da; 
            border-radius: 4px; 
            font-size: 16px; 
        }
        textarea { 
            height: 80px; 
            resize: vertical; 
        }
        .btn { 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 16px; 
            margin-right: 10px; 
            margin-bottom: 10px; 
        }
        .btn:hover { 
            background: #0056b3; 
        }
        .btn-danger { 
            background: #dc3545; 
        }
        .btn-danger:hover { 
            background: #b02a37; 
        }
        .btn-success { 
            background: #28a745; 
        }
        .btn-success:hover { 
            background: #1e7e34; 
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 15px; 
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 15px; 
        }
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
        }
        .table th, .table td { 
            border: 1px solid #dee2e6; 
            padding: 8px 12px; 
            text-align: left; 
        }
        .table th { 
            background: #e9ecef; 
            font-weight: bold; 
        }
        .table tr:nth-child(even) { 
            background: #f8f9fa; 
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-danger { background: #f8d7da; color: #721c24; }
        .status-info { background: #d1ecf1; color: #0c5460; }
        .status-default { background: #e9ecef; color: #495057; }
        .form-inline { 
            display: inline-block; 
            margin-right: 10px; 
        }
        .text-small { 
            font-size: 0.9rem; 
            color: #6c757d; 
        }
        .section-divider {
            border-bottom: 2px solid #007bff;
            margin: 20px 0;
            padding-bottom: 10px;
        }
        .project-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        .volunteer-info {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .org-info {
            background: #fff2e7;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .action-buttons {
            margin-top: 15px;
        }
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .info-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #007bff;
        }
        .info-item strong {
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Community Connect</h1>
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="nav">
                <?php if ($_SESSION['user_role'] === 'volunteer'): ?>
                    <a href="volunteer_dashboard.php">Dashboard</a>
                    <a href="browse_projects.php">Browse Projects</a>
                <?php elseif ($_SESSION['user_role'] === 'organization'): ?>
                    <a href="organization_dashboard.php">Dashboard</a>
                    <a href="browse_projects.php">Browse Projects</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="container">
