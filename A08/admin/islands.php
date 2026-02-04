<?php
session_start();
require_once '../connect.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$message = '';
$action = $_GET['action'] ?? 'list';
$island_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $short_desc = trim($_POST['short_description']);
        $long_desc = trim($_POST['long_description']);
        $color = trim($_POST['color']);
        $image = trim($_POST['image']);
        $visible = isset($_POST['visible']) ? 1 : 0;
        
        $query = "INSERT INTO islandsofpersonality (name, shortDescription, longDescription, color, image, visible, adminID) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $name, $short_desc, $long_desc, $color, $image, $visible, $_SESSION['admin_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Island added successfully!";
            logActivity($_SESSION['admin_id'], 'create', 'islandsofpersonality', mysqli_insert_id($conn), null, json_encode($_POST), $_SERVER['REMOTE_ADDR']);
        } else {
            $message = "Error adding island: " . mysqli_error($conn);
        }
        
    } elseif ($action === 'edit') {
        $island_id = $_POST['island_id'];
        $name = trim($_POST['name']);
        $short_desc = trim($_POST['short_description']);
        $long_desc = trim($_POST['long_description']);
        $color = trim($_POST['color']);
        $image = trim($_POST['image']);
        $visible = isset($_POST['visible']) ? 1 : 0;
        
        // Get old values for logging
        $old_query = "SELECT * FROM islandsofpersonality WHERE islandOfPersonalityID = ?";
        $old_stmt = mysqli_prepare($conn, $old_query);
        mysqli_stmt_bind_param($old_stmt, "i", $island_id);
        mysqli_stmt_execute($old_stmt);
        $old_data = mysqli_fetch_assoc($old_stmt);
        
        $query = "UPDATE islandsofpersonality SET name = ?, shortDescription = ?, longDescription = ?, color = ?, image = ?, visible = ?, updated_at = NOW() 
                  WHERE islandOfPersonalityID = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssssi", $name, $short_desc, $long_desc, $color, $image, $visible, $island_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Island updated successfully!";
            logActivity($_SESSION['admin_id'], 'update', 'islandsofpersonality', $island_id, json_encode($old_data), json_encode($_POST), $_SERVER['REMOTE_ADDR']);
        } else {
            $message = "Error updating island: " . mysqli_error($conn);
        }
        
    } elseif ($action === 'delete') {
        $island_id = $_POST['island_id'];
        
        // Check if island has memories
        $check_query = "SELECT COUNT(*) as count FROM islandcontents WHERE islandOfPersonalityID = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "i", $island_id);
        mysqli_stmt_execute($check_stmt);
        $count = mysqli_fetch_assoc($check_stmt)['count'];
        
        if ($count > 0) {
            $message = "Cannot delete island: It contains $count memories. Please delete or reassign the memories first.";
        } else {
            // Get old values for logging
            $old_query = "SELECT * FROM islandsofpersonality WHERE islandOfPersonalityID = ?";
            $old_stmt = mysqli_prepare($conn, $old_query);
            mysqli_stmt_bind_param($old_stmt, "i", $island_id);
            mysqli_stmt_execute($old_stmt);
            $old_data = mysqli_fetch_assoc($old_stmt);
            
            $query = "DELETE FROM islandsofpersonality WHERE islandOfPersonalityID = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $island_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Island deleted successfully!";
                logActivity($_SESSION['admin_id'], 'delete', 'islandsofpersonality', $island_id, json_encode($old_data), null, $_SERVER['REMOTE_ADDR']);
            } else {
                $message = "Error deleting island: " . mysqli_error($conn);
            }
        }
    }
}

// Get island data for editing
$island = null;
if ($action === 'edit' && $island_id) {
    $query = "SELECT * FROM islandsofpersonality WHERE islandOfPersonalityID = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $island_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $island = mysqli_fetch_assoc($result);
}

// Get all islands for listing
$islands_query = "SELECT * FROM islandsofpersonality ORDER BY name";
$islands_result = mysqli_query($conn, $islands_query);

// Get memory count for each island
$island_stats = [];
while ($island_data = mysqli_fetch_assoc($islands_result)) {
    $count_query = "SELECT COUNT(*) as count FROM islandcontents WHERE islandOfPersonalityID = ?";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, "i", $island_data['islandOfPersonalityID']);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count = mysqli_fetch_assoc($count_result)['count'];
    
    $island_stats[] = array_merge($island_data, ['memory_count' => $count]);
}

function logActivity($adminID, $action, $table, $recordID, $oldValues, $newValues, $ip) {
    global $conn;
    $query = "INSERT INTO admin_activity_log (adminID, action, table_name, record_id, old_values, new_values, ip_address) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issssss", $adminID, $action, $table, $recordID, $oldValues, $newValues, $ip);
    mysqli_stmt_execute($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Islands Management - Core Memories</title>
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

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #dee2e6;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
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

        .islands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .island-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .island-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .island-header {
            height: 150px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            position: relative;
        }

        .island-header img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid white;
        }

        .island-content {
            padding: 1.5rem;
        }

        .island-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .island-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .island-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
        }

        .island-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .color-input {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .color-input input[type="color"] {
            width: 60px;
            height: 40px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            cursor: pointer;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-visible {
            background: #d4edda;
            color: #155724;
        }

        .status-hidden {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem 0;
            }
            
            .islands-grid {
                grid-template-columns: 1fr;
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
                <li><a href="islands.php" class="active"><i class="fas fa-island-tropical"></i> Islands</a></li>
                <li><a href="upload.php"><i class="fas fa-upload"></i> Upload</a></li>
                <li><a href="activity.php"><i class="fas fa-history"></i> Activity Log</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Islands Management</h1>
                <div class="header-actions">
                    <a href="islands.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Island
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'Error') === 0 ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($action === 'add' || ($action === 'edit' && $island)): ?>
                <div class="card">
                    <h3><?php echo $action === 'add' ? 'Add New Island' : 'Edit Island'; ?></h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="island_id" value="<?php echo $island['islandOfPersonalityID']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="name">Island Name</label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo $island ? htmlspecialchars($island['name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="short_description">Short Description</label>
                            <input type="text" id="short_description" name="short_description"
                                   value="<?php echo $island ? htmlspecialchars($island['shortDescription']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="long_description">Long Description</label>
                            <textarea id="long_description" name="long_description"><?php echo $island ? htmlspecialchars($island['longDescription']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="image">Image Filename</label>
                            <input type="text" id="image" name="image" required
                                   value="<?php echo $island ? htmlspecialchars($island['image']) : ''; ?>"
                                   placeholder="e.g., island1.jpg">
                        </div>

                        <div class="form-group">
                            <label for="color">Theme Color</label>
                            <div class="color-input">
                                <input type="color" id="color" name="color" 
                                       value="<?php echo $island ? htmlspecialchars($island['color']) : '#667eea'; ?>">
                                <input type="text" value="<?php echo $island ? htmlspecialchars($island['color']) : '#667eea'; ?>" 
                                       placeholder="#667eea" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="visible" name="visible" 
                                       <?php echo ($island && $island['visible']) ? 'checked' : ''; ?>>
                                <label for="visible">Visible to public</label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $action === 'add' ? 'Add Island' : 'Update Island'; ?>
                            </button>
                            <a href="islands.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <div class="islands-grid">
                    <?php foreach ($island_stats as $island): ?>
                        <div class="island-card">
                            <div class="island-header" style="background: <?php echo htmlspecialchars($island['color']); ?>;">
                                <img src="../img/<?php echo htmlspecialchars($island['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($island['name']); ?>">
                            </div>
                            <div class="island-content">
                                <h3 class="island-name"><?php echo htmlspecialchars($island['name']); ?></h3>
                                <p class="island-description"><?php echo htmlspecialchars($island['shortDescription']); ?></p>
                                
                                <div class="island-stats">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $island['memory_count']; ?></div>
                                        <div class="stat-label">Memories</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number">
                                            <span class="status-badge <?php echo $island['visible'] ? 'status-visible' : 'status-hidden'; ?>">
                                                <?php echo $island['visible'] ? 'Visible' : 'Hidden'; ?>
                                            </span>
                                        </div>
                                        <div class="stat-label">Status</div>
                                    </div>
                                </div>

                                <div class="island-actions">
                                    <a href="islands.php?action=edit&id=<?php echo $island['islandOfPersonalityID']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#" onclick="confirmDelete(<?php echo $island['islandOfPersonalityID']; ?>)" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this island? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="island_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
