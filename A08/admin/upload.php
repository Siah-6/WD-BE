<?php
session_start();
require_once '../connect.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$message = '';
$uploaded_files = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $island_id = $_POST['island_id'];
    $titles = $_POST['titles'] ?? [];
    $descriptions = $_POST['descriptions'] ?? [];
    $image_alt_texts = $_POST['image_alt_texts'] ?? [];
    
    $upload_dir = '../contentpic/';
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    foreach ($_FILES['images']['name'] as $key => $name) {
        if ($_FILES['images']['error'][$key] === 0) {
            $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = time() . '_' . basename($name);
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_path)) {
                    $title = $titles[$key] ?? pathinfo($name, PATHINFO_FILENAME);
                    $description = $descriptions[$key] ?? '';
                    $image_alt = $image_alt_texts[$key] ?? $title;
                    
                    $query = "INSERT INTO islandcontents (islandOfPersonalityID, image, content, title, image_alt, adminID) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "issssi", $island_id, $file_name, $description, $title, $image_alt, $_SESSION['admin_id']);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $uploaded_files[] = [
                            'name' => $file_name,
                            'title' => $title,
                            'success' => true
                        ];
                    }
                }
            }
        }
        
        if (!empty($uploaded_files)) {
            $message = "Successfully uploaded " . count($uploaded_files) . " images!";
            logActivity($_SESSION['admin_id'], 'bulk_upload', 'islandcontents', null, null, json_encode($uploaded_files), $_SERVER['REMOTE_ADDR']);
        } else {
            $message = "No files were uploaded or there was an error with the files.";
        }
    }
}

// Get all islands for dropdown
$islands_query = "SELECT * FROM islandsofpersonality ORDER BY name";
$islands_result = mysqli_query($conn, $islands_query);

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
    <title>Bulk Upload - Core Memories</title>
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

        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            background: rgba(102, 126, 234, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .upload-area:hover {
            border-color: #764ba2;
            background: rgba(102, 126, 234, 0.1);
        }

        .upload-area.dragover {
            border-color: #764ba2;
            background: rgba(102, 126, 234, 0.2);
        }

        .upload-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .upload-text {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .upload-hint {
            color: #999;
            font-size: 0.9rem;
        }

        .file-input {
            display: none;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .preview-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .preview-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .preview-info {
            padding: 1rem;
        }

        .preview-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .preview-controls {
            display: flex;
            gap: 0.5rem;
        }

        .preview-input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #e1e1e1;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
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

        .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            
            .preview-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
                <li><a href="upload.php" class="active"><i class="fas fa-upload"></i> Upload</a></li>
                <li><a href="activity.php"><i class="fas fa-history"></i> Activity Log</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Bulk Upload Photos</h1>
                <div class="header-actions">
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

            <div class="card">
                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="island_id">Select Island</label>
                        <select id="island_id" name="island_id" required>
                            <option value="">Choose an island...</option>
                            <?php while ($island = mysqli_fetch_assoc($islands_result)): ?>
                                <option value="<?php echo $island['islandOfPersonalityID']; ?>">
                                    <?php echo htmlspecialchars($island['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            <strong>Click to upload or drag and drop</strong>
                        </div>
                        <div class="upload-hint">
                            Supported formats: JPG, PNG, GIF, WebP (Max 5MB per file)
                        </div>
                        <input type="file" id="fileInput" class="file-input" name="images[]" multiple accept="image/*">
                    </div>

                    <div id="previewGrid" class="preview-grid"></div>

                    <div class="form-actions" style="display: none;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload All Photos
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearPreviews()">
                            <i class="fas fa-times"></i> Clear All
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const previewGrid = document.getElementById('previewGrid');
        const uploadForm = document.getElementById('uploadForm');
        const formActions = uploadForm.querySelector('.form-actions');
        const islandSelect = document.getElementById('island_id');

        let selectedFiles = [];

        // Click to upload
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });

        function handleFiles(files) {
            // Filter valid image files
            const validFiles = files.filter(file => {
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                return validTypes.includes(file.type) && file.size <= 5 * 1024 * 1024; // 5MB limit
            });

            selectedFiles = validFiles;
            displayPreviews();
        }

        function displayPreviews() {
            previewGrid.innerHTML = '';
            
            if (selectedFiles.length === 0) {
                formActions.style.display = 'none';
                return;
            }

            formActions.style.display = 'flex';
            
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="${file.name}" class="preview-image">
                        <div class="preview-info">
                            <input type="text" class="preview-input" placeholder="Title" value="${file.name.split('.')[0]}" data-index="${index}">
                            <input type="text" class="preview-input" placeholder="Alt text" value="${file.name.split('.')[0]}" data-index="${index}" data-field="alt">
                            <div class="preview-controls">
                                <button type="button" class="remove-btn" onclick="removeFile(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    previewGrid.appendChild(previewItem);
                };
                
                reader.readAsDataURL(file);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            displayPreviews();
        }

        function clearPreviews() {
            selectedFiles = [];
            previewGrid.innerHTML = '';
            formActions.style.display = 'none';
            fileInput.value = '';
        }

        // Form submission
        uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            if (selectedFiles.length === 0) {
                alert('Please select at least one image to upload.');
                return;
            }
            
            if (!islandSelect.value) {
                alert('Please select an island for the memories.');
                return;
            }
            
            // Update form with preview data
            const formData = new FormData(uploadForm);
            
            // Add titles and descriptions from previews
            const titleInputs = document.querySelectorAll('.preview-input[data-field="title"]');
            const altInputs = document.querySelectorAll('.preview-input[data-field="alt"]');
            
            titleInputs.forEach((input, index) => {
                formData.append(`titles[${index}]`, input.value);
            });
            
            altInputs.forEach((input, index) => {
                formData.append(`image_alt_texts[${index}]`, input.value);
            });
            
            // Submit the form
            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reload page to show results
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
