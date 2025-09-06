<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login/');
    exit();
}

function db() { return getMysqliConnection(); }

// Flash message
$message = '';
$message_type = 'success';

// Helper: activity logging
function logActivity(mysqli $mysqli, string $action, string $details): void {
    $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli = db();
    try {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $listing_type = $_POST['listing_type'] ?? 'Buy';
        $price = $_POST['price'] !== '' ? (float)$_POST['price'] : null;
        $location = trim($_POST['location'] ?? '');
        $landmark = trim($_POST['landmark'] ?? '');
        $area = $_POST['area'] !== '' ? (float)$_POST['area'] : null;
        $configuration = trim($_POST['configuration'] ?? '');
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
        $furniture_status = $_POST['furniture_status'] ?? null;
        $ownership_type = $_POST['ownership_type'] ?? null;
        $facing = $_POST['facing'] ?? null;
        $parking = $_POST['parking'] ?? null;
        $balcony = (int)($_POST['balcony'] ?? 0);
        $status = $_POST['status'] ?? 'Available';

        // Validation
        if ($title === '') { throw new Exception('Title is required'); }
        if ($location === '') { throw new Exception('Location is required'); }
        if ($price === null || $price <= 0) { throw new Exception('Valid price is required'); }
        if ($area === null || $area <= 0) { throw new Exception('Valid area is required'); }

        // Insert property
        $sql = "INSERT INTO properties (title, description, listing_type, price, location, landmark, area, configuration, category_id, furniture_status, ownership_type, facing, parking, balcony, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssdsisssssssis', $title, $description, $listing_type, $price, $location, $landmark, $area, $configuration, $category_id, $furniture_status, $ownership_type, $facing, $parking, $balcony, $status);
        
        if (!$stmt->execute()) { 
            throw new Exception('Failed to add property: ' . $mysqli->error); 
        }
        
        $property_id = $mysqli->insert_id;
        $stmt->close();

        // Handle image uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = '../../uploads/properties/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $uploaded_images = [];
            $image_count = count($_FILES['images']['name']);
            
            for ($i = 0; $i < $image_count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_extension = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        // Generate unique filename with property ID, timestamp, and index
                        $timestamp = time();
                        $filename = 'property_' . $property_id . '_' . $timestamp . '_' . $i . '.' . $file_extension;
                        $file_path = $upload_dir . $filename;
                        
                        // Check file size (max 5MB per image)
                        if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
                            throw new Exception('Image ' . ($i + 1) . ' is too large. Maximum size is 5MB.');
                        }
                        
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $file_path)) {
                            $image_url = $filename; // Store only filename, not full path
                            $uploaded_images[] = $image_url;
                            
                            // Insert image record
                            $img_stmt = $mysqli->prepare("INSERT INTO property_images (property_id, image_url) VALUES (?, ?)");
                            $img_stmt->bind_param('is', $property_id, $image_url);
                            $img_stmt->execute();
                            $img_stmt->close();
                        } else {
                            throw new Exception('Failed to upload image ' . ($i + 1));
                        }
                    } else {
                        throw new Exception('Invalid file type for image ' . ($i + 1) . '. Allowed types: JPG, PNG, GIF, WebP');
                    }
                } else {
                    $error_messages = [
                        UPLOAD_ERR_INI_SIZE => 'Image ' . ($i + 1) . ' exceeds upload_max_filesize',
                        UPLOAD_ERR_FORM_SIZE => 'Image ' . ($i + 1) . ' exceeds MAX_FILE_SIZE',
                        UPLOAD_ERR_PARTIAL => 'Image ' . ($i + 1) . ' was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file uploaded for image ' . ($i + 1),
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder for image ' . ($i + 1),
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write image ' . ($i + 1) . ' to disk',
                        UPLOAD_ERR_EXTENSION => 'Image ' . ($i + 1) . ' upload stopped by extension'
                    ];
                    $error_code = $_FILES['images']['error'][$i];
                    if (isset($error_messages[$error_code])) {
                        throw new Exception($error_messages[$error_code]);
                    }
                }
            }
            
            if (empty($uploaded_images)) {
                throw new Exception('No valid images were uploaded');
            }
        }

        logActivity($mysqli, 'Added property', 'Title: ' . $title . ', ID: ' . $property_id);
        $message = 'Property added successfully!';
        $message_type = 'success';
        
        // Redirect to properties list after successful submission
        header('Location: index.php?success=1');
        exit();
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get categories for dropdown
$mysqli = db();
$categoriesRes = $mysqli->query("SELECT id, name FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root{ --bg:#F1EFEC; --card:#ffffff; --muted:#6b7280; --line:#e9eef5; --brand-dark:#2f2f2f; --primary:#e11d2a; --primary-600:#b91c1c; --radius:16px; }
        body{ background:var(--bg); color:#111827; }
        .content{ margin-left:284px; }
        /* Sidebar styles copied from dashboard */
        .sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .brand{ font-weight:700; font-size:1.25rem; }
        .list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
        .list-group-item i{ width:18px; }
        .list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
        .list-group-item:hover{ background:#f8fafc; }
        .navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .form-control, .form-select{ border-radius:12px; border:1px solid var(--line); }
        .form-control:focus, .form-select:focus{ border-color:#3b82f6; box-shadow:0 0 0 0.2rem rgba(59, 130, 246, 0.25); }
        .btn{ border-radius:12px; font-weight:500; }
        .btn-primary{ background:#3b82f6; border-color:#3b82f6; }
        .btn-primary:hover{ background:#2563eb; border-color:#2563eb; }
        .section-header{ border-bottom:2px solid var(--line); padding-bottom:1rem; margin-bottom:2rem; }
        .section-title{ color:var(--brand-dark); font-weight:600; font-size:1.1rem; }
        .required{ color:#dc2626; }
        .image-preview{ max-width:150px; max-height:150px; object-fit:cover; border-radius:8px; margin:5px; }
        .image-upload-area{ border:2px dashed var(--line); border-radius:12px; padding:2rem; text-align:center; background:#f8fafc; transition:all 0.3s ease; }
        .image-upload-area:hover{ border-color:#3b82f6; background:#f0f9ff; }
        .image-upload-area.dragover{ border-color:#3b82f6; background:#f0f9ff; }
        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
            .sidebar.open{ left:12px; }
            .content{ margin-left:0; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('properties'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="h4 mb-1">Add New Property</h2>
                    <p class="text-muted mb-0">Fill in the details to add a new property listing</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back to Properties
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" id="propertyForm">
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="section-header">
                                    <h5 class="section-title"><i class="fa-solid fa-info-circle me-2"></i>Basic Information</h5>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Property Title <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="title" required placeholder="e.g., Beautiful 3BHK Apartment in Downtown">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Listing Type <span class="required">*</span></label>
                                        <select class="form-select" name="listing_type" required>
                                            <option value="Buy">Buy</option>
                                            <option value="Rent">Rent</option>
                                            <option value="PG/Co-living">PG/Co-living</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="4" placeholder="Describe the property features, amenities, and unique selling points..."></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Price (₹) <span class="required">*</span></label>
                                        <input type="number" class="form-control" name="price" step="0.01" required placeholder="0.00">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Area (sq ft) <span class="required">*</span></label>
                                        <input type="number" class="form-control" name="area" step="0.01" required placeholder="0">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Location <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="location" required placeholder="City, Area, Locality">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Landmark</label>
                                        <input type="text" class="form-control" name="landmark" placeholder="Nearby landmark or reference point">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Configuration</label>
                                        <input type="text" class="form-control" name="configuration" placeholder="e.g., 2BHK, 3BHK">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="category_id">
                                            <option value="">Select Category</option>
                                            <?php while($c = $categoriesRes->fetch_assoc()): ?>
                                                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="Available">Available</option>
                                            <option value="Sold">Sold</option>
                                            <option value="Rented">Rented</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Property Details -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="section-header">
                                    <h5 class="section-title"><i class="fa-solid fa-home me-2"></i>Property Details</h5>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Furniture Status</label>
                                        <select class="form-select" name="furniture_status">
                                            <option value="">Select</option>
                                            <option value="Furnished">Furnished</option>
                                            <option value="Semi-Furnished">Semi-Furnished</option>
                                            <option value="Unfurnished">Unfurnished</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ownership Type</label>
                                        <select class="form-select" name="ownership_type">
                                            <option value="">Select</option>
                                            <option value="Freehold">Freehold</option>
                                            <option value="Leasehold">Leasehold</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Facing</label>
                                        <select class="form-select" name="facing">
                                            <option value="">Select</option>
                                            <option value="East">East</option>
                                            <option value="West">West</option>
                                            <option value="North">North</option>
                                            <option value="South">South</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Parking</label>
                                        <select class="form-select" name="parking">
                                            <option value="">Select</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Number of Balconies</label>
                                        <input type="number" class="form-control" name="balcony" min="0" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="section-header">
                                    <h5 class="section-title"><i class="fa-solid fa-images me-2"></i>Property Images</h5>
                                </div>
                                
                                <div class="image-upload-area" id="imageUploadArea">
                                    <i class="fa-solid fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h6>Drop images here or click to browse</h6>
                                    <p class="text-muted small mb-3">Upload multiple images (JPG, PNG, GIF, WebP) - Max 5MB each</p>
                                    <input type="file" class="form-control d-none" name="images[]" id="imageInput" multiple accept="image/*">
                                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('imageInput').click()">
                                        <i class="fa-solid fa-plus me-2"></i>Choose Images
                                    </button>
                                </div>
                                
                                <div id="imagePreview" class="mt-3"></div>
                                <div id="imageInfo" class="mt-2 text-muted small"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3"><i class="fa-solid fa-lightbulb me-2"></i>Tips for Better Listings</h6>
                                <ul class="list-unstyled small text-muted">
                                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Use descriptive and attractive titles</li>
                                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Add high-quality images</li>
                                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Include accurate measurements</li>
                                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Mention nearby amenities</li>
                                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Be specific about location</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save me-2"></i>Add Property
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image upload handling
        const imageUploadArea = document.getElementById('imageUploadArea');
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');
        const imageInfo = document.getElementById('imageInfo');
        let selectedFiles = [];

        // Drag and drop functionality
        imageUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUploadArea.classList.add('dragover');
        });

        imageUploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            if (!imageUploadArea.contains(e.relatedTarget)) {
                imageUploadArea.classList.remove('dragover');
            }
        });

        imageUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            handleFiles(files);
        });

        imageUploadArea.addEventListener('click', (e) => {
            if (e.target === imageUploadArea || e.target.closest('.image-upload-area')) {
                imageInput.click();
            }
        });

        imageInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        function handleFiles(files) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            Array.from(files).forEach(file => {
                // Validate file type
                if (!allowedTypes.includes(file.type)) {
                    alert(`File "${file.name}" is not a valid image type. Please use JPG, PNG, GIF, or WebP.`);
                    return;
                }
                
                // Validate file size
                if (file.size > maxSize) {
                    alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                    return;
                }
                
                // Add to selected files
                selectedFiles.push(file);
                
                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    const previewContainer = document.createElement('div');
                    previewContainer.className = 'image-preview-container position-relative d-inline-block me-2 mb-2';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview';
                    img.title = file.name;
                    img.style.width = '150px';
                    img.style.height = '150px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '8px';
                    img.style.border = '2px solid #e9eef5';
                    
                    // Add remove button
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-sm btn-danger position-absolute';
                    removeBtn.style.top = '5px';
                    removeBtn.style.right = '5px';
                    removeBtn.style.width = '24px';
                    removeBtn.style.height = '24px';
                    removeBtn.style.borderRadius = '50%';
                    removeBtn.style.padding = '0';
                    removeBtn.innerHTML = '<i class="fa-solid fa-times" style="font-size: 10px;"></i>';
                    removeBtn.onclick = () => removeImage(previewContainer, file);
                    
                    previewContainer.appendChild(img);
                    previewContainer.appendChild(removeBtn);
                    imagePreview.appendChild(previewContainer);
                };
                reader.readAsDataURL(file);
            });
            
            updateImageInfo();
        }

        function removeImage(container, file) {
            // Remove from selected files
            selectedFiles = selectedFiles.filter(f => f !== file);
            
            // Remove from DOM
            container.remove();
            
            updateImageInfo();
        }

        function updateImageInfo() {
            const count = selectedFiles.length;
            const totalSize = selectedFiles.reduce((sum, file) => sum + file.size, 0);
            const sizeInMB = (totalSize / (1024 * 1024)).toFixed(2);
            
            if (count > 0) {
                imageInfo.innerHTML = `
                    <i class="fa-solid fa-info-circle me-1"></i>
                    ${count} image${count !== 1 ? 's' : ''} selected (${sizeInMB} MB total)
                `;
            } else {
                imageInfo.innerHTML = '';
            }
        }

        // Form validation
        document.getElementById('propertyForm').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const location = document.querySelector('input[name="location"]').value.trim();
            const price = document.querySelector('input[name="price"]').value;
            const area = document.querySelector('input[name="area"]').value;

            if (!title) {
                alert('Please enter a property title');
                e.preventDefault();
                return;
            }
            if (!location) {
                alert('Please enter a location');
                e.preventDefault();
                return;
            }
            if (!price || parseFloat(price) <= 0) {
                alert('Please enter a valid price');
                e.preventDefault();
                return;
            }
            if (!area || parseFloat(area) <= 0) {
                alert('Please enter a valid area');
                e.preventDefault();
                return;
            }
            
            // Validate images
            if (selectedFiles.length === 0) {
                if (!confirm('No images selected. Do you want to continue without images?')) {
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html>
