<?php
session_start();
require_once '../connect.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get dashboard statistics
$totalMemoriesQuery = "SELECT COUNT(*) as total FROM islandcontents";
$totalMemoriesResult = mysqli_query($conn, $totalMemoriesQuery);
$totalMemories = mysqli_fetch_assoc($totalMemoriesResult)['total'];

$totalIslandsQuery = "SELECT COUNT(*) as total FROM islandsofpersonality";
$totalIslandsResult = mysqli_query($conn, $totalIslandsQuery);
$totalIslands = mysqli_fetch_assoc($totalIslandsResult)['total'];

$visibleMemoriesQuery = "SELECT COUNT(*) as total FROM islandcontents WHERE visible = 1";
$visibleMemoriesResult = mysqli_query($conn, $visibleMemoriesQuery);
$visibleMemories = mysqli_fetch_assoc($visibleMemoriesResult)['total'];

$totalViewsQuery = "SELECT SUM(view_count) as total FROM islandcontents";
$totalViewsResult = mysqli_query($conn, $totalViewsQuery);
$totalViews = mysqli_fetch_assoc($totalViewsResult)['total'];

// Get recent activity
$recentActivityQuery = "SELECT * FROM admin_activity_log ORDER BY created_at DESC LIMIT 10";
$recentActivityResult = mysqli_query($conn, $recentActivityQuery);

// Get island distribution
$islandStatsQuery = "SELECT i.name, COUNT(ic.islandContentID) as count 
                     FROM islandsofpersonality i 
                     LEFT JOIN islandcontents ic ON i.islandOfPersonalityID = ic.islandOfPersonalityID 
                     GROUP BY i.islandOfPersonalityID, i.name";
$islandStatsResult = mysqli_query($conn, $islandStatsQuery);

// Get most viewed memories
$mostViewedQuery = "SELECT ic.title, ic.view_count, i.name as island 
                    FROM islandcontents ic 
                    JOIN islandsofpersonality i ON ic.islandOfPersonalityID = i.islandOfPersonalityID 
                    WHERE ic.visible = 1 
                    ORDER BY ic.view_count DESC 
                    LIMIT 5";
$mostViewedResult = mysqli_query($conn, $mostViewedQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Core Memories</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #666;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-info {
            flex: 1;
        }

        .activity-action {
            font-weight: 600;
            color: #667eea;
        }

        .activity-time {
            color: #999;
            font-size: 0.9rem;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        .most-viewed-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .most-viewed-item:last-child {
            border-bottom: none;
        }

        .memory-info {
            flex: 1;
        }

        .memory-title {
            font-weight: 600;
            color: #333;
        }

        .memory-island {
            color: #666;
            font-size: 0.9rem;
        }

        .view-count {
            background: #667eea;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #dee2e6;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem 0;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="memories.php"><i class="fas fa-images"></i> Memories</a></li>
                <li><a href="islands.php"><i class="fas fa-island-tropical"></i> Islands</a></li>
                <li><a href="upload.php"><i class="fas fa-upload"></i> Upload</a></li>
                <li><a href="activity.php"><i class="fas fa-history"></i> Activity Log</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <div class="header-actions">
                    <div class="admin-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalMemories; ?></div>
                    <div class="stat-label">Total Memories</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalIslands; ?></div>
                    <div class="stat-label">Islands</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $visibleMemories; ?></div>
                    <div class="stat-label">Visible</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalViews); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
            </div>

            <div class="content-grid">
                <div class="card">
                    <h3><i class="fas fa-chart-pie"></i> Island Distribution</h3>
                    <div class="chart-container">
                        <canvas id="islandChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-eye"></i> Most Viewed</h3>
                    <?php while ($memory = mysqli_fetch_assoc($mostViewedResult)): ?>
                        <div class="most-viewed-item">
                            <div class="memory-info">
                                <div class="memory-title"><?php echo htmlspecialchars($memory['title']); ?></div>
                                <div class="memory-island"><?php echo htmlspecialchars($memory['island']); ?></div>
                            </div>
                            <div class="view-count"><?php echo number_format($memory['view_count']); ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-history"></i> Recent Activity</h3>
                <?php while ($activity = mysqli_fetch_assoc($recentActivityResult)): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></div>
                            <div class="activity-time"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="quick-actions">
                <a href="memories.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Memory
                </a>
                <a href="upload.php" class="btn btn-secondary">
                    <i class="fas fa-upload"></i> Upload Photos
                </a>
                <a href="activity.php" class="btn btn-secondary">
                    <i class="fas fa-chart-line"></i> View Analytics
                </a>
            </div>
        </main>
    </div>

    <script>
        // Island Distribution Chart
        const ctx = document.getElementById('islandChart').getContext('2d');
        const islandData = <?php 
            $data = [];
            while ($row = mysqli_fetch_assoc($islandStatsResult)) {
                $data[] = ['name' => $row['name'], 'count' => $row['count']];
            }
            echo json_encode($data);
        ?>;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: islandData.map(item => item.name),
                datasets: [{
                    data: islandData.map(item => item.count),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#00f2fe'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
