<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

// Helper: activity logging
function logActivity(mysqli $mysqli, string $action, string $details): void {
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    
    // Check if a similar log entry already exists in the last 5 seconds to prevent duplicates
    $check_stmt = $mysqli->prepare("
        SELECT COUNT(*) as count 
        FROM activity_logs 
        WHERE admin_id = ? AND action = ? AND details = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)
    ");
    
    if ($admin_id === null) {
        $check_stmt->bind_param('sss', $admin_id, $action, $details);
    } else {
        $check_stmt->bind_param('iss', $admin_id, $action, $details);
    }
    
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();
    
    // Only insert if no recent duplicate exists
    if ($row['count'] == 0) {
        if ($admin_id === null) {
            $stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details, created_at) VALUES (NULL, ?, ?, NOW())");
            $stmt && $stmt->bind_param('ss', $action, $details);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt && $stmt->bind_param('iss', $admin_id, $action, $details);
        }
        $stmt && $stmt->execute();
        $stmt && $stmt->close();
    }
}

$message = '';
$message_type = 'success';

// Get project ID
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    header('Location: index.php');
    exit;
}

$mysqli = db();

// Fetch project data
$stmt = $mysqli->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

// Fetch project images
$stmt = $mysqli->prepare("SELECT * FROM project_images WHERE project_id = ? ORDER BY display_order ASC");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project_images = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$project) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        // Order ID is not updated in edit form - use existing value
        
        if (empty($name)) {
            throw new Exception('Project name is required');
        }
        
        // Update project
        $stmt = $mysqli->prepare("UPDATE projects SET name = ?, description = ?, location = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('sssi', $name, $description, $location, $project_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update project: ' . $mysqli->error);
        }
        
        $stmt->close();
        
        // Handle image reordering if provided
        if (isset($_POST['image_order']) && !empty($_POST['image_order'])) {
            $image_orders = json_decode($_POST['image_order'], true);
            if (is_array($image_orders)) {
                foreach ($image_orders as $order_data) {
                    $img_id = (int)$order_data['id'];
                    $new_order = (int)$order_data['order'];
                    $update_stmt = $mysqli->prepare("UPDATE project_images SET display_order = ? WHERE id = ? AND project_id = ?");
                    $update_stmt->bind_param('iii', $new_order, $img_id, $project_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
        }
        
        // Handle new image uploads
        $upload_dir = '../../uploads/projects/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            // Get current max order
            $max_order_stmt = $mysqli->prepare("SELECT MAX(display_order) as max_order FROM project_images WHERE project_id = ?");
            $max_order_stmt->bind_param('i', $project_id);
            $max_order_stmt->execute();
            $max_order_result = $max_order_stmt->get_result();
            $max_order_row = $max_order_result->fetch_assoc();
            $image_order = ($max_order_row['max_order'] ?? 0) + 1;
            $max_order_stmt->close();
            
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_extension = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                    
                    if (!in_array($file_extension, $allowed_extensions)) {
                        continue; // Skip invalid files
                    }
                    
                    $filename = 'project_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_path)) {
                        // Insert image record
                        $img_stmt = $mysqli->prepare("INSERT INTO project_images (project_id, image_filename, display_order, created_at) VALUES (?, ?, ?, NOW())");
                        $img_stmt->bind_param('isi', $project_id, $filename, $image_order);
                        $img_stmt->execute();
                        $img_stmt->close();
                        $image_order++;
                    }
                }
            }
        }
        
        // Handle image deletion
        if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
            $delete_ids = json_decode($_POST['delete_images'], true);
            if (is_array($delete_ids)) {
                foreach ($delete_ids as $img_id) {
                    // Get filename to delete file
                    $get_file_stmt = $mysqli->prepare("SELECT image_filename FROM project_images WHERE id = ? AND project_id = ?");
                    $get_file_stmt->bind_param('ii', $img_id, $project_id);
                    $get_file_stmt->execute();
                    $file_result = $get_file_stmt->get_result();
                    if ($file_row = $file_result->fetch_assoc()) {
                        $file_path = $upload_dir . $file_row['image_filename'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                    $get_file_stmt->close();
                    
                    // Delete from database
                    $delete_stmt = $mysqli->prepare("DELETE FROM project_images WHERE id = ? AND project_id = ?");
                    $delete_stmt->bind_param('ii', $img_id, $project_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                }
            }
        }
        
        logActivity($mysqli, 'Updated project', 'ID: ' . $project_id . ', Name: ' . $name);
        
        header('Location: index.php?updated=1');
        exit;
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Our Builds - Big Deal</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
    <link rel="shortcut icon" href="/favicon.ico?v=3" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ef4444;
            --bg: #F4F7FA;
            --card: #FFFFFF;
            --line: #E0E0E0;
            --border-color: #E0E0E0;
            --text: #333;
        }
        body{ background:var(--bg); color:#111827; margin:0; }
        /* Background iframe with blur */
        .background-iframe {
            position: fixed; top:0; left:0; width:100%; height:100%; border:none; z-index:-2;
        }
        .blur-overlay {
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,.4); backdrop-filter: blur(3px); z-index:-1;
        }
        /* Modal overlay + container */
        .modal-overlay { position: fixed; inset:0; background: transparent; display:flex; align-items:center; justify-content:center; padding:20px; z-index:1000; }
        .modal-container { background: var(--card); border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.3); width:100%; max-width:640px; max-height:90vh; overflow:auto; position:relative; z-index:1001; border:1px solid rgba(255,255,255,0.1); }
        .order-card { padding: 2rem; box-sizing: border-box; }
        .card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
        .card-header h1 { font-size:1.25rem; font-weight:600; margin:0; }
        .close-btn { font-size:1.5rem; color:#999; cursor:pointer; border:none; background:none; }
        .btn-animated-confirm { background: var(--primary-color); color:#fff; border:0; padding:.8rem 1.5rem; border-radius:8px; display:inline-flex; align-items:center; gap:.5rem; }
        .btn-outline-secondary { border-radius:8px; }
        .footer-actions { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; }
        .btn { padding:.5rem 1rem; border:none; border-radius:8px; font-size:.875rem; font-weight:500; cursor:pointer; text-decoration:none; display:inline-block; }
        .btn-secondary { background:#ffffff; color:var(--text); border:1px solid var(--border-color); }
        .btn-secondary:hover { background:#f5f5f5; }
        .btn-animated-confirm { padding:.5rem 1rem; font-size:.875rem; }
        /* Form look */
        .form-control{ border-radius:12px; border:1px solid var(--line); }
        .form-control:focus{ border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(239,68,68,.15); }
        .image-drop{ border:2px dashed var(--line); border-radius:12px; padding:1.5rem; text-align:center; background:#fafbfc; transition:all .2s ease; }
        .image-drop.dragover{ border-color:var(--primary-color); background:#fef2f2; }
        .image-drop:hover{ border-color:var(--primary-color); background:#fef2f2; }
        .image-drop .btn-outline-primary{ color:var(--primary-color); border-color:var(--primary-color); }
        .image-drop .btn-outline-primary:hover{ background-color:var(--primary-color); border-color:var(--primary-color); color:#fff; }
        .preview{ margin-top:10px; }
        .preview img{ width:140px; height:140px; object-fit:cover; border-radius:10px; border:2px solid #e9eef5; }
        
        /* Image Gallery Styles */
        .image-gallery, .existing-images {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid var(--line);
        }
        .gallery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .gallery-title {
            font-weight: 600;
            color: var(--text);
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
        }
        .gallery-item {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--line);
            cursor: move;
            transition: all 0.3s ease;
        }
        .gallery-item:hover {
            border-color: var(--primary-color);
            transform: scale(1.05);
        }
        .gallery-item.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-item .remove-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #dc2626;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .gallery-item:hover .remove-btn {
            opacity: 1;
        }
        .gallery-item .order-number {
            position: absolute;
            bottom: 2px;
            left: 2px;
            background: rgba(0,0,0,0.7);
            color: white;
            font-size: 10px;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 600;
        }
        .drop-zone {
            border: 2px dashed var(--primary-color);
            background: #fef2f2;
        }
        @media (max-width: 600px){ 
            .modal-container { max-width: 95%; }
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            }
            .gallery-item {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <!-- Background iframe showing projects index page -->
    <iframe src="index.php" class="background-iframe" title="Projects Background"></iframe>
    <div class="blur-overlay"></div>

    <div class="modal-overlay">
        <div class="modal-container">
            <div class="order-card">
                <header class="card-header">
                    <h1>Edit Project</h1>
                    <button class="close-btn" aria-label="Close">&times;</button>
                </header>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="projectForm">
                    <div class="mb-3">
                        <label class="form-label">Project Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" 
                               value="<?php echo htmlspecialchars($project['name']); ?>" 
                               placeholder="e.g., Luxury Villa, Commercial Complex" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Enter project description..."><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" 
                               value="<?php echo htmlspecialchars($project['location']); ?>" 
                               placeholder="Enter project location...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Project Images</label>
                        
                        <!-- Existing Images -->
                        <?php if (!empty($project_images)): ?>
                            <div class="existing-images mb-3">
                                <div class="gallery-header">
                                    <span class="gallery-title">Current Images</span>
                                    <span class="text-muted small">Drag to reorder</span>
                                </div>
                                <div class="gallery-grid" id="existingImagesGrid">
                                    <?php foreach ($project_images as $index => $img): ?>
                                        <div class="gallery-item existing-item" data-id="<?php echo $img['id']; ?>" data-order="<?php echo $img['display_order']; ?>">
                                            <img src="../../uploads/projects/<?php echo htmlspecialchars($img['image_filename']); ?>" alt="Project Image">
                                            <button type="button" class="remove-btn" onclick="removeExistingImage(<?php echo $img['id']; ?>)" title="Remove image">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                            <div class="order-number"><?php echo $img['display_order']; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Add New Images -->
                        <div id="drop" class="image-drop">
                            <i class="fa-solid fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                            <div class="mb-2">Drop new images here or click to browse</div>
                            <input type="file" name="images[]" id="images" accept="image/*" multiple class="d-none">
                            <button type="button" class="btn btn-outline-primary" id="chooseBtn"><i class="fa-solid fa-plus me-1"></i>Add Images</button>
                            <div class="text-muted small mt-2">Supported: JPG, PNG, WebP. Max 5MB each. Drag to reorder.</div>
                        </div>
                        
                        <!-- New Images Preview -->
                        <div class="image-gallery" id="imageGallery" style="display:none;">
                            <div class="gallery-header">
                                <span class="gallery-title">New Images</span>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllBtn">Clear All</button>
                            </div>
                            <div class="gallery-grid" id="galleryGrid">
                                <!-- New images will be added here -->
                            </div>
                        </div>
                        
                        <!-- Hidden inputs for form submission -->
                        <input type="hidden" name="image_order" id="imageOrder" value="">
                        <input type="hidden" name="delete_images" id="deleteImages" value="">
                    </div>

                    <div class="footer-actions">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Update Project</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path></svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const drop = document.getElementById('drop');
        const input = document.getElementById('images');
        const chooseBtn = document.getElementById('chooseBtn');
        const gallery = document.getElementById('imageGallery');
        const galleryGrid = document.getElementById('galleryGrid');
        const clearAllBtn = document.getElementById('clearAllBtn');
        const existingImagesGrid = document.getElementById('existingImagesGrid');
        const imageOrderInput = document.getElementById('imageOrder');
        const deleteImagesInput = document.getElementById('deleteImages');
        
        let selectedFiles = [];
        let draggedElement = null;
        let deletedImageIds = [];

        // Drag and drop for file input
        if (drop) {
            drop.addEventListener('dragover', (e)=>{ e.preventDefault(); drop.classList.add('dragover'); });
            drop.addEventListener('dragleave', (e)=>{ e.preventDefault(); drop.classList.remove('dragover'); });
            drop.addEventListener('drop', (e)=>{
                e.preventDefault(); 
                drop.classList.remove('dragover');
                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) { 
                    handleFiles(Array.from(e.dataTransfer.files)); 
                }
            });
            drop.addEventListener('click', ()=> input.click());
        }
        
        if (chooseBtn) {
            chooseBtn.addEventListener('click', (e)=>{ e.stopPropagation(); input.click(); });
        }
        
        if (input) {
            input.addEventListener('change', (e) => {
                if (e.target.files && e.target.files.length > 0) {
                    handleFiles(Array.from(e.target.files));
                }
            });
        }

        // Clear all new images
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', () => {
                selectedFiles = [];
                updateGallery();
                input.value = '';
            });
        }

        // Setup drag and drop for existing images
        if (existingImagesGrid) {
            const existingItems = existingImagesGrid.querySelectorAll('.gallery-item');
            existingItems.forEach(item => {
                item.draggable = true;
                item.addEventListener('dragstart', handleDragStart);
                item.addEventListener('dragover', handleDragOver);
                item.addEventListener('drop', handleDrop);
                item.addEventListener('dragend', handleDragEnd);
            });
            
            // Ensure initial order is sequential
            updateExistingImageOrder();
        }

        function handleFiles(files) {
            const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            files.forEach(file => {
                if (!allowed.includes(file.type)) {
                    alert(`File ${file.name} is not a valid image type. Please select JPG, PNG, GIF, or WebP images.`);
                    return;
                }
                if (file.size > maxSize) {
                    alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                    return;
                }
                
                // Add unique ID to file for tracking
                file.uniqueId = Date.now() + Math.random();
                selectedFiles.push(file);
            });
            
            updateGallery();
        }

        function updateGallery() {
            if (selectedFiles.length === 0) {
                if (gallery) gallery.style.display = 'none';
                return;
            }
            
            if (gallery) gallery.style.display = 'block';
            if (galleryGrid) galleryGrid.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const galleryItem = document.createElement('div');
                    galleryItem.className = 'gallery-item';
                    galleryItem.draggable = true;
                    galleryItem.dataset.index = index;
                    galleryItem.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="remove-btn" onclick="removeNewImage(${index})">
                            <i class="fa-solid fa-times"></i>
                        </button>
                        <div class="order-number">${index + 1}</div>
                    `;
                    
                    // Drag and drop for reordering
                    galleryItem.addEventListener('dragstart', handleDragStart);
                    galleryItem.addEventListener('dragover', handleDragOver);
                    galleryItem.addEventListener('drop', handleDrop);
                    galleryItem.addEventListener('dragend', handleDragEnd);
                    
                    if (galleryGrid) galleryGrid.appendChild(galleryItem);
                };
                reader.readAsDataURL(file);
            });
        }

        function removeNewImage(index) {
            selectedFiles.splice(index, 1);
            updateGallery();
            updateImageOrderInput();
        }

        function removeExistingImage(imageId) {
            // Remove image without confirmation dialog
            deletedImageIds.push(imageId);
            const item = document.querySelector(`[data-id="${imageId}"]`);
            if (item) {
                item.remove();
            }
            updateDeleteImagesInput();
            updateExistingImageOrder();
        }

        function updateDeleteImagesInput() {
            if (deleteImagesInput) {
                deleteImagesInput.value = JSON.stringify(deletedImageIds);
            }
        }

        // Drag and drop reordering
        function handleDragStart(e) {
            draggedElement = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.outerHTML);
        }

        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            return false;
        }

        function handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            
            if (draggedElement !== this) {
                // Handle existing images reordering
                if (this.closest('#existingImagesGrid')) {
                    // Swap positions in DOM
                    const parent = this.parentNode;
                    const draggedIndex = Array.from(parent.children).indexOf(draggedElement);
                    const targetIndex = Array.from(parent.children).indexOf(this);
                    
                    if (draggedIndex < targetIndex) {
                        parent.insertBefore(draggedElement, this.nextSibling);
                    } else {
                        parent.insertBefore(draggedElement, this);
                    }
                    
                    // Update order numbers to be sequential
                    updateExistingImageOrder();
                }
                // Handle new images reordering
                else if (this.closest('#galleryGrid')) {
                    const draggedIndex = parseInt(draggedElement.dataset.index);
                    const targetIndex = parseInt(this.dataset.index);
                    
                    // Swap files in array
                    const draggedFile = selectedFiles[draggedIndex];
                    selectedFiles.splice(draggedIndex, 1);
                    selectedFiles.splice(targetIndex, 0, draggedFile);
                    
                    updateGallery();
                }
            }
            return false;
        }

        function handleDragEnd(e) {
            this.classList.remove('dragging');
            draggedElement = null;
        }

        function updateExistingImageOrder() {
            if (existingImagesGrid) {
                const items = existingImagesGrid.querySelectorAll('.gallery-item');
                items.forEach((item, index) => {
                    const newOrder = index + 1;
                    item.dataset.order = newOrder;
                    const orderNumberElement = item.querySelector('.order-number');
                    if (orderNumberElement) {
                        orderNumberElement.textContent = newOrder;
                    }
                });
            }
            updateImageOrderInput();
        }

        function updateImageOrderInput() {
            if (imageOrderInput && existingImagesGrid) {
                const items = existingImagesGrid.querySelectorAll('.gallery-item');
                const orderData = Array.from(items).map(item => ({
                    id: parseInt(item.dataset.id),
                    order: parseInt(item.dataset.order)
                }));
                imageOrderInput.value = JSON.stringify(orderData);
            }
        }

        // Simple validation
        document.getElementById('projectForm').addEventListener('submit', function(e){
            const name = this.querySelector('input[name="name"]').value.trim();
            if (name.length < 2) { 
                e.preventDefault(); 
                alert('Please enter a valid project name'); 
                return;
            }
            
            // Update file input with current files
            if (input && selectedFiles.length > 0) {
                const dt = new DataTransfer();
                selectedFiles.forEach(file => dt.items.add(file));
                input.files = dt.files;
            }
            
            // Update hidden inputs
            updateImageOrderInput();
            updateDeleteImagesInput();
        });

        // Close button navigates back
        document.querySelector('.close-btn')?.addEventListener('click', function(e){ 
            e.preventDefault();
            e.stopPropagation();
            window.location.href = 'index.php';
        });
        document.addEventListener('click', function(e){
            const modal = document.querySelector('.modal-container');
            if (modal && !modal.contains(e.target)) { 
                // Don't close the form on outside click
            }
        });
    </script>
</body>
</html>
