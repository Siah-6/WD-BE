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
$memory_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $island_id = $_POST['island_id'];
        $visible = isset($_POST['visible']) ? 1 : 0;
        $image_alt = trim($_POST['image_alt']);
        
        // Handle file upload
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../contentpic/';
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
        }
        
        $query = "INSERT INTO islandcontents (islandOfPersonalityID, image, content, title, visible, image_alt, adminID) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isssisi", $island_id, $image_name, $content, $title, $visible, $image_alt, $_SESSION['admin_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Memory added successfully!";
            logActivity($_SESSION['admin_id'], 'create', 'islandcontents', mysqli_insert_id($conn), null, json_encode($_POST), $_SERVER['REMOTE_ADDR']);
        } else {
            $message = "Error adding memory: " . mysqli_error($conn);
        }
        
    } elseif ($action === 'edit') {
        $memory_id = $_POST['memory_id'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $island_id = $_POST['island_id'];
        $visible = isset($_POST['visible']) ? 1 : 0;
        $image_alt = trim($_POST['image_alt']);
        
        // Get old values for logging
        $old_query = "SELECT * FROM islandcontents WHERE islandContentID = ?";
        $old_stmt = mysqli_prepare($conn, $old_query);
        mysqli_stmt_bind_param($old_stmt, "i", $memory_id);
        mysqli_stmt_execute($old_stmt);
        $old_data = mysqli_fetch_assoc($old_stmt);
        
        // Handle file upload if new image provided
        $image_name = $old_data['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../contentpic/';
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
        }
        
        $query = "UPDATE islandcontents SET islandOfPersonalityID = ?, image = ?, content = ?, title = ?, visible = ?, image_alt = ?, updated_at = NOW() 
                  WHERE islandContentID = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isssisi", $island_id, $image_name, $content, $title, $visible, $image_alt, $memory_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Memory updated successfully!";
            logActivity($_SESSION['admin_id'], 'update', 'islandcontents', $memory_id, json_encode($old_data), json_encode($_POST), $_SERVER['REMOTE_ADDR']);
        } else {
            $message = "Error updating memory: " . mysqli_error($conn);
        }
        
    } elseif ($action === 'delete') {
        $memory_id = $_POST['memory_id'];
        
        // Get old values for logging
        $old_query = "SELECT * FROM islandcontents WHERE islandContentID = ?";
        $old_stmt = mysqli_prepare($conn, $old_query);
        mysqli_stmt_bind_param($old_stmt, "i", $memory_id);
        mysqli_stmt_execute($old_stmt);
        $old_data = mysqli_fetch_assoc($old_stmt);
        
        $query = "DELETE FROM islandcontents WHERE islandContentID = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $memory_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Memory deleted successfully!";
            logActivity($_SESSION['admin_id'], 'delete', 'islandcontents', $memory_id, json_encode($old_data), null, $_SERVER['REMOTE_ADDR']);
        } else {
            $message = "Error deleting memory: " . mysqli_error($conn);
        }
    }
}

// Get memory data for editing
$memory = null;
if ($action === 'edit' && $memory_id) {
    $query = "SELECT * FROM islandcontents WHERE islandContentID = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $memory_id);
    mysqli_stmt_execute($stmt);
    $memory = mysqli_fetch_assoc($stmt);
}

// Get all islands for dropdown
$islands_query = "SELECT * FROM islandsofpersonality ORDER BY name";
$islands_result = mysqli_query($conn, $islands_query);

// Get all memories for listing
$memories_query = "SELECT ic.*, i.name as island_name 
                   FROM islandcontents ic 
                   JOIN islandsofpersonality i ON ic.islandOfPersonalityID = i.islandOfPersonalityID 
                   ORDER BY ic.created_at DESC";
$memories_result = mysqli_query($conn, $memories_query);

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
    <title>Memories Management - Core Memories</title>
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

        .memories-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .memories-table th {
            background: #667eea;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .memories-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .memories-table tr:hover {
            background: #f8f9fa;
        }

        .memory-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .memory-title {
            font-weight: 600;
            color: #333;
        }

        .memory-island {
            color: #666;
            font-size: 0.9rem;
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

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-buttons a {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: #667eea;
            color: white;
        }

        .delete-btn {
            background: #e74c3c;
            color: white;
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

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem 0;
            }
            
            .memories-table {
                font-size: 0.8rem;
            }
            
            .memory-image {
                width: 40px;
                height: 40px;
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
                <li><a href="memories.php" class="active"><i class="fas fa-images"></i> Memories</a></li>
                <li><a href="islands.php"><i class="fas fa-island-tropical"></i> Islands</a></li>
                <li><a href="upload.php"><i class="fas fa-upload"></i> Upload</a></li>
                <li><a href="activity.php"><i class="fas fa-history"></i> Activity Log</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Memories Management</h1>
                <div class="header-actions">
                    <a href="memories.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Memory
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

            <?php if ($action === 'add' || ($action === 'edit' && $memory)): ?>
                <div class="card">
                    <h3><?php echo $action === 'add' ? 'Add New Memory' : 'Edit Memory'; ?></h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="memory_id" value="<?php echo $memory['islandContentID']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required
                                   value="<?php echo $memory ? htmlspecialchars($memory['title']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="island_id">Island</label>
                            <select id="island_id" name="island_id" required>
                                <?php while ($island = mysqli_fetch_assoc($islands_result)): ?>
                                    <option value="<?php echo $island['islandOfPersonalityID']; ?>"
                                            <?php echo ($memory && $memory['islandOfPersonalityID'] == $island['islandOfPersonalityID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($island['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="content">Content/Description</label>
                            <textarea id="content" name="content"><?php echo $memory ? htmlspecialchars($memory['content']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="image">Image</label>
                            <input type="file" id="image" name="image" accept="image/*"
                                   <?php echo $action === 'add' ? 'required' : ''; ?>>
                            <?php if ($memory && $memory['image']): ?>
                                <small>Current: <?php echo htmlspecialchars($memory['image']); ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="image_alt">Image Alt Text</label>
                            <input type="text" id="image_alt" name="image_alt"
                                   value="<?php echo $memory ? htmlspecialchars($memory['image_alt']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="visible" name="visible" 
                                       <?php echo ($memory && $memory['visible']) ? 'checked' : ''; ?>>
                                <label for="visible">Visible to public</label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $action === 'add' ? 'Add Memory' : 'Update Memory'; ?>
                            </button>
                            <a href="memories.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <div class="card">
                    <table class="memories-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Island</th>
                                <th>Views</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($memory = mysqli_fetch_assoc($memories_result)): ?>
                                <tr>
                                    <td>
                                        <?php if ($memory['image']): ?>
                                            <img src="../contentpic/<?php echo htmlspecialchars($memory['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($memory['title']); ?>" class="memory-image">
                                        <?php else: ?>
                                            <div class="memory-image" style="background: #f0f0f0;"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="memory-title"><?php echo htmlspecialchars($memory['title']); ?></div>
                                        <div class="memory-island"><?php echo htmlspecialchars($memory['island_name']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($memory['island_name']); ?></td>
                                    <td><?php echo number_format($memory['view_count']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $memory['visible'] ? 'status-visible' : 'status-hidden'; ?>">
                                            <?php echo $memory['visible'] ? 'Visible' : 'Hidden'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($memory['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="memories.php?action=edit&id=<?php echo $memory['islandContentID']; ?>" class="edit-btn">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" onclick="confirmDelete(<?php echo $memory['islandContentID']; ?>)" class="delete-btn">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this memory? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="memory_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
