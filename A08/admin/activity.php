<?php
session_start();
require_once '../connect.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get activity log with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$totalQuery = "SELECT COUNT(*) as total FROM admin_activity_log";
$totalResult = mysqli_query($conn, $totalQuery);
$total = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($total / $limit);

$activityQuery = "SELECT al.*, a.username 
                   FROM admin_activity_log al 
                   LEFT JOIN admin a ON al.adminID = a.adminID 
                   ORDER BY al.created_at DESC 
                   LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $activityQuery);
mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
mysqli_stmt_execute($stmt);
$activities = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Core Memories</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid #e1e1e1;
            margin-bottom: 2rem;
        }

        .sidebar-header h2 {
            color: #667eea;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-menu li {
            margin-bottom: 0.5rem;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-menu a:hover, .nav-menu a.active {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            border-right: 3px solid #667eea;
        }

        .nav-menu i {
            margin-right: 1rem;
            width: 20px;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #dee2e6;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .activity-table th {
            background: #667eea;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .activity-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-table tr:hover {
            background: #f8f9fa;
        }

        .action-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .action-create {
            background: #d4edda;
            color: #155724;
        }

        .action-update {
            background: #cce5ff;
            color: #004085;
        }

        .action-delete {
            background: #f8d7da;
            color: #721c24;
        }

        .action-login {
            background: #e2e3e5;
            color: #383d41;
        }

        .action-logout {
            background: #fff3cd;
            color: #856404;
        }

        .action-bulk_upload {
            background: #d1ecf1;
            color: #0c5460;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.8rem 1.2rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .pagination a.active {
            background: #764ba2;
        }

        .pagination a.disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }

        .details-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem 0;
            }
            
            .activity-table {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Core Memories</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="memories.php"><i class="fas fa-images"></i> Memories</a></li>
                <li><a href="islands.php"><i class="fas fa-island-tropical"></i> Islands</a></li>
                <li><a href="upload.php"><i class="fas fa-upload"></i> Upload</a></li>
                <li><a href="activity.php" class="active"><i class="fas fa-history"></i> Activity Log</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Activity Log</h1>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>

            <div class="card">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>Record ID</th>
th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($activity = mysqli_fetch_assoc($activities)): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($activity['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                <td>
                                    <span class="action-badge action-<?php echo strtolower($activity['action']); ?>">
                                        <?php echo htmlspecialchars($activity['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($activity['table_name']); ?></td>
                                <td><?php echo $activity['record_id'] ?: '-'; ?></td>
                                <td>
                                    <?php if ($activity['old_values']): ?>
                                        <div class="details-preview">
                                            <strong>Before:</strong><br>
                                            <?php echo htmlspecialchars($activity['old_values']); ?><br><br>
                                            <strong>After:</strong><br>
                                            <?php echo htmlspecialchars($activity['new_values']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
