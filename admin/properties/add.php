<?php
require_once __DIR__ . '/../auth.php';

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
        $map_embed_link = trim($_POST['map_embed_link'] ?? '');

        // Validation
        if ($title === '') { throw new Exception('Title is required'); }
        if ($location === '') { throw new Exception('Location is required'); }
        if ($price === null || $price <= 0) { throw new Exception('Valid price is required'); }
        if ($area === null || $area <= 0) { throw new Exception('Valid area is required'); }

        // Structured location
        $state_id = isset($_POST['state_id']) && $_POST['state_id'] !== '' ? (int)$_POST['state_id'] : 0;
        $district_id = isset($_POST['district_id']) && $_POST['district_id'] !== '' ? (int)$_POST['district_id'] : 0;
        $city_id = isset($_POST['city_id']) && $_POST['city_id'] !== '' ? (int)$_POST['city_id'] : 0;
        $town_id = isset($_POST['town_id']) && $_POST['town_id'] !== '' ? (int)$_POST['town_id'] : 0;
        $pincode = trim($_POST['pincode'] ?? '');
        if ($state_id <= 0 || $district_id <= 0 || $city_id <= 0 || $town_id <= 0 || $pincode === '') {
            throw new Exception('Please select State, District, City, Town and enter Pincode');
        }

        // Insert property
        $sql = "INSERT INTO properties (title, description, listing_type, price, location, landmark, area, configuration, category_id, furniture_status, ownership_type, facing, parking, balcony, status, map_embed_link, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssdsisssssssiss', $title, $description, $listing_type, $price, $location, $landmark, $area, $configuration, $category_id, $furniture_status, $ownership_type, $facing, $parking, $balcony, $status, $map_embed_link);
        
        if (!$stmt->execute()) { 
            throw new Exception('Failed to add property: ' . $mysqli->error); 
        }
        
        $property_id = $mysqli->insert_id;
        $stmt->close();

        // Insert properties_location
        $pl = $mysqli->prepare("INSERT INTO properties_location (property_id, state_id, district_id, city_id, town_id, pincode) VALUES (?, ?, ?, ?, ?, ?)");
        if ($pl) {
            $pl->bind_param('iiiiis', $property_id, $state_id, $district_id, $city_id, $town_id, $pincode);
            if (!$pl->execute()) {
                throw new Exception('Failed to save property location');
            }
            $pl->close();
        }

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

// Get categories and states for dropdowns
$mysqli = db();
$categoriesRes = $mysqli->query("SELECT id, name FROM categories ORDER BY name");
$statesRes = $mysqli->query("SELECT id, name FROM states ORDER BY name");
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<!-- Modal CSS -->
<style>
    /* Modal Overlay */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.35);
        backdrop-filter: blur(2px);
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    /* Modal Container */
    .modal-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 1200px;
        width: 100%;
        max-height: 90vh;
        overflow: hidden;
        position: relative;
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    /* Modal Header */
    .modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #111827;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #6b7280;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .modal-close:hover {
        background: #f3f4f6;
        color: #374151;
    }

    /* Modal Body */
    .modal-body {
        padding: 0;
        max-height: calc(90vh - 140px);
        overflow-y: auto;
    }

    /* Bootstrap Form Overrides */
    .form-control, .form-select {
        border-radius: 12px;
        border: 1px solid #d1d5db;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-label {
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .required {
        color: #ef4444;
        font-weight: 600;
    }

    .btn {
        border-radius: 12px;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
    }

    .btn-primary {
        background: #ef4444;
        border-color: #ef4444;
    }

    .btn-primary:hover {
        background: #b91c1c;
        border-color: #b91c1c;
    }

    .btn-outline-primary {
        color: #ef4444;
        border-color: #ef4444;
    }

    .btn-outline-primary:hover {
        background-color: #ef4444;
        border-color: #ef4444;
        color: #fff;
    }

    .btn-outline-secondary {
        color: #6b7280;
        border-color: #6b7280;
    }

    .btn-outline-secondary:hover {
        background-color: #6b7280;
        border-color: #6b7280;
        color: #fff;
    }

    .btn-outline-danger {
        color: #ef4444;
        border-color: #ef4444;
    }

    .btn-outline-danger:hover {
        background-color: #ef4444;
        border-color: #ef4444;
        color: #fff;
    }

    .alert {
        border: 0;
        border-radius: 12px;
        margin: 1rem;
    }

    .alert-success {
        background: #f0f9ff;
        color: #1e40af;
        border-left: 4px solid #3b82f6;
    }

    .alert-danger {
        background: #fef2f2;
        color: #dc2626;
        border-left: 4px solid #ef4444;
    }

    /* Progress Bar */
    .progressbar {
        display: flex;
        justify-content: space-between;
        position: relative;
        margin-bottom: 2rem;
        padding: 0 2rem;
    }

    .progressbar::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 2rem;
        right: 2rem;
        transform: translateY(-50%);
        height: 4px;
        background-color: #e5e7eb;
        z-index: 1;
        border-radius: 2px;
    }

    .progress {
        position: absolute;
        top: 50%;
        left: 2rem;
        transform: translateY(-50%);
        height: 4px;
        width: 0%;
        background: linear-gradient(90deg, #ef4444, #b91c1c);
        transition: width 0.3s ease;
        z-index: 2;
        border-radius: 2px;
    }

    .progress-step {
        width: 40px;
        height: 40px;
        background-color: #e5e7eb;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        color: #6b7280;
        z-index: 3;
        position: relative;
        transition: all 0.3s ease;
        border: 3px solid white;
    }

    .progress-step.active {
        background: #ef4444;
        color: #fff;
        transform: scale(1.1);
    }

    .progress-step.completed {
        background: #b91c1c;
        color: #fff;
    }

    .step-content {
        display: none;
        animation: fadeIn 0.3s ease-in-out;
    }

    .step-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .step-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 2rem;
        padding: 1.5rem 2rem;
        border-top: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .step-info {
        color: #6b7280;
        font-size: 0.9rem;
    }

    .step-buttons {
        display: flex;
        gap: 0.75rem;
    }

    .btn-step {
        min-width: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .step-indicator {
        background: #fee2e2;
        color: #ef4444;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 1rem;
        display: inline-block;
    }

    /* Image upload */
    .image-preview {
        max-width: 150px;
        max-height: 150px;
        object-fit: cover;
        border-radius: 8px;
        margin: 5px;
    }

    .image-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        background: #f8fafc;
        transition: all 0.3s ease;
    }

    .image-upload-area:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    .image-upload-area.dragover {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    /* Inline add dropdown overlays */
    .location-grid .col-md-6, .location-grid .col-md-12, .location-grid .col-md-4 {
        position: relative;
    }

    .inline-add {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        z-index: 5;
        background: white;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        border-radius: 8px;
        padding: 1rem;
    }

    .inline-add .input-group {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        border-radius: 8px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modal-container {
            margin: 10px;
            max-height: 95vh;
        }
        
        .modal-header, .step-navigation {
            padding: 1rem;
        }
        
        .progressbar {
            padding: 0 1rem;
        }
        
        .progressbar::before {
            left: 1rem;
            right: 1rem;
        }
        
        .progress {
            left: 1rem;
        }
    }
</style>

<!-- Modal HTML -->
<div class="modal-overlay" id="addPropertyModal">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Add New Property</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show m-3" role="alert">
                    <i class="fa-solid fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" id="propertyForm" class="needs-validation" novalidate>
                <!-- Progress Bar -->
                <div class="progressbar">
                    <div class="progress" id="progress"></div>
                    <div class="progress-step active" id="step1">
                        <i class="fa-solid fa-info-circle"></i>
                    </div>
                    <div class="progress-step" id="step2">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="progress-step" id="step3">
                        <i class="fa-solid fa-home"></i>
                    </div>
                    <div class="progress-step" id="step4">
                        <i class="fa-solid fa-images"></i>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <div class="step-indicator" id="stepIndicator">Step 1 of 4: Basic Information</div>
                </div>

                <!-- Step 1: Basic Information -->
                <div class="step-content active" id="content1">
                    <div class="p-4">
                        <h5 class="fw-semibold mb-4"><i class="fa-solid fa-info-circle me-2"></i>Basic Information</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Property Title <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="title" required placeholder="e.g., Beautiful 3BHK Apartment in Downtown">
                                        <div class="invalid-feedback">Please provide a valid property title.</div>
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
                                    
                                    <div class="col-12">
                                        <label class="form-label">Map Embed Link</label>
                                        <input type="url" class="form-control" name="map_embed_link" placeholder="Paste Google Maps embed URL here (e.g., https://www.google.com/maps/embed?pb=...)">
                                        <div class="form-text">
                                            <i class="fa-solid fa-info-circle me-1"></i>
                                            To get the embed link: Go to Google Maps → Search for your property → Click Share → Embed a map → Copy the iframe src URL
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Price (₹) <span class="required">*</span></label>
                                        <input type="number" class="form-control" name="price" step="0.01" required placeholder="0.00" min="0">
                                        <div class="invalid-feedback">Please provide a valid price.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Area (sq ft) <span class="required">*</span></label>
                                        <input type="number" class="form-control" name="area" step="0.01" required placeholder="0" min="0">
                                        <div class="invalid-feedback">Please provide a valid area.</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Location <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="location" required placeholder="City, Area, Locality">
                                        <div class="invalid-feedback">Please provide a valid location.</div>
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
                        
                        <!-- Step Navigation -->
                        <div class="step-navigation">
                            <div class="step-info">
                                <i class="fa-solid fa-lightbulb me-1"></i>
                                Fill in the basic property details to get started
                            </div>
                            <div class="step-buttons">
                                <button type="button" class="btn btn-outline-secondary btn-step" onclick="nextStep()">
                                    <i class="fa-solid fa-arrow-right me-1"></i>Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Location Details -->
                <div class="step-content" id="content2">
                    <div class="p-4">
                        <h5 class="fw-semibold mb-4"><i class="fa-solid fa-location-dot me-2"></i>Location Details</h5>
                                <div class="row g-3 align-items-end location-grid">
                                    <div class="col-md-6">
                                        <label class="form-label">State <span class="required">*</span></label>
                                        <select class="form-select" id="stateSelect" name="state_id" required>
                                            <option value="">Select State</option>
                                            <?php while($s = $statesRes->fetch_assoc()): ?>
                                                <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                            <?php endwhile; ?>
                                            <option value="__add__">+ Add State</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a state.</div>
                                        <div id="stateAddInline" class="mt-2 inline-add" style="display:none;">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="stateAddInput" placeholder="New state name">
                                                <button class="btn btn-primary" type="button" id="stateAddSave">Save</button>
                                                <button class="btn btn-outline-secondary" type="button" id="stateAddCancel">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">District <span class="required">*</span></label>
                                        <select class="form-select" id="districtSelect" name="district_id" required>
                                            <option value="">Select District</option>
                                            <option value="__add__">+ Add District</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a district.</div>
                                        <div id="districtAddInline" class="mt-2 inline-add" style="display:none;">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="districtAddInput" placeholder="New district name">
                                                <button class="btn btn-primary" type="button" id="districtAddSave">Save</button>
                                                <button class="btn btn-outline-secondary" type="button" id="districtAddCancel">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">City <span class="required">*</span></label>
                                        <select class="form-select" id="citySelect" name="city_id" required>
                                            <option value="">Select City</option>
                                            <option value="__add__">+ Add City</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a city.</div>
                                        <div id="cityAddInline" class="mt-2 inline-add" style="display:none;">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="cityAddInput" placeholder="New city name">
                                                <button class="btn btn-primary" type="button" id="cityAddSave">Save</button>
                                                <button class="btn btn-outline-secondary" type="button" id="cityAddCancel">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Town <span class="required">*</span></label>
                                        <select class="form-select" id="townSelect" name="town_id" required>
                                            <option value="">Select Town</option>
                                            <option value="__add__">+ Add Town</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a town.</div>
                                        <div id="townAddInline" class="mt-2 inline-add" style="display:none;">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="townAddInput" placeholder="New town name">
                                                <button class="btn btn-primary" type="button" id="townAddSave">Save</button>
                                                <button class="btn btn-outline-secondary" type="button" id="townAddCancel">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Pincode <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="pincode" id="pincodeInput" placeholder="e.g., 560001" required>
                                        <div class="invalid-feedback">Please enter a valid pincode.</div>
                                    </div>
                        </div>
                        
                        <!-- Step Navigation -->
                        <div class="step-navigation">
                            <div class="step-info">
                                <i class="fa-solid fa-map-marker-alt me-1"></i>
                                Select the complete location hierarchy for your property
                            </div>
                            <div class="step-buttons">
                                <button type="button" class="btn btn-outline-secondary btn-step" onclick="prevStep()">
                                    <i class="fa-solid fa-arrow-left me-1"></i>Back
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-step" onclick="nextStep()">
                                    <i class="fa-solid fa-arrow-right me-1"></i>Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Property Details -->
                <div class="step-content" id="content3">
                    <div class="p-4">
                        <h5 class="fw-semibold mb-4"><i class="fa-solid fa-home me-2"></i>Property Details</h5>
                                
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
                        
                        <!-- Step Navigation -->
                        <div class="step-navigation">
                            <div class="step-info">
                                <i class="fa-solid fa-cog me-1"></i>
                                Configure additional property specifications
                            </div>
                            <div class="step-buttons">
                                <button type="button" class="btn btn-outline-secondary btn-step" onclick="prevStep()">
                                    <i class="fa-solid fa-arrow-left me-1"></i>Back
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-step" onclick="nextStep()">
                                    <i class="fa-solid fa-arrow-right me-1"></i>Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Images & Review -->
                <div class="step-content" id="content4">
                    <div class="p-4">
                        <h5 class="fw-semibold mb-4"><i class="fa-solid fa-images me-2"></i>Property Images & Review</h5>
                                
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
                                
                                <!-- Review Section -->
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6 class="fw-semibold mb-3"><i class="fa-solid fa-clipboard-check me-2"></i>Review Your Property</h6>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <small class="text-muted">Title:</small>
                                            <div id="reviewTitle" class="fw-medium">-</div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Price:</small>
                                            <div id="reviewPrice" class="fw-medium">-</div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Location:</small>
                                            <div id="reviewLocation" class="fw-medium">-</div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Area:</small>
                                            <div id="reviewArea" class="fw-medium">-</div>
                                        </div>
                                    </div>
                                </div>
                                
                        <!-- Step Navigation -->
                        <div class="step-navigation">
                            <div class="step-info">
                                <i class="fa-solid fa-check-circle me-1"></i>
                                Review your property details and add images before submitting
                            </div>
                            <div class="step-buttons">
                                <button type="button" class="btn btn-outline-secondary btn-step" onclick="prevStep()">
                                    <i class="fa-solid fa-arrow-left me-1"></i>Back
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-step" onclick="closeModal()">
                                    <i class="fa-solid fa-times me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-primary btn-step">
                                    <i class="fa-solid fa-check me-1"></i>Add Property
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
        // Multi-step form functionality
        let currentStep = 1;
        const totalSteps = 4;
        const steps = document.querySelectorAll(".progress-step");
        const contents = document.querySelectorAll(".step-content");
        const progress = document.getElementById("progress");
        const stepIndicator = document.getElementById("stepIndicator");

        const stepTitles = [
            "Step 1 of 4: Basic Information",
            "Step 2 of 4: Location Details", 
            "Step 3 of 4: Property Details",
            "Step 4 of 4: Images & Review"
        ];

        function updateProgress() {
            // Update progress bar
            const progressWidth = ((currentStep - 1) / (totalSteps - 1)) * 100;
            progress.style.width = progressWidth + "%";

            // Update step indicators
            steps.forEach((step, index) => {
                step.classList.remove("active", "completed");
                if (index < currentStep - 1) {
                    step.classList.add("completed");
                } else if (index === currentStep - 1) {
                    step.classList.add("active");
                }
            });

            // Show correct content
            contents.forEach((content, index) => {
                if (index === currentStep - 1) {
                    content.classList.add("active");
                } else {
                    content.classList.remove("active");
                }
            });

            // Update step indicator text
            stepIndicator.textContent = stepTitles[currentStep - 1];

            // Update review section if on step 4
            if (currentStep === 4) {
                updateReviewSection();
            }
        }

        function updateReviewSection() {
            const title = document.querySelector('input[name="title"]').value || '-';
            const price = document.querySelector('input[name="price"]').value ? 
                '₹' + parseFloat(document.querySelector('input[name="price"]').value).toLocaleString() : '-';
            const location = document.querySelector('input[name="location"]').value || '-';
            const area = document.querySelector('input[name="area"]').value ? 
                document.querySelector('input[name="area"]').value + ' sq ft' : '-';

            document.getElementById('reviewTitle').textContent = title;
            document.getElementById('reviewPrice').textContent = price;
            document.getElementById('reviewLocation').textContent = location;
            document.getElementById('reviewArea').textContent = area;
        }

        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateProgress();
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateProgress();
            }
        }

        function validateCurrentStep() {
            switch (currentStep) {
                case 1:
                    return validateStep1();
                case 2:
                    return validateStep2();
                case 3:
                    return validateStep3();
                case 4:
                    return validateStep4();
                default:
                    return true;
            }
        }

        function validateStep1() {
            const title = document.querySelector('input[name="title"]');
            const price = document.querySelector('input[name="price"]');
            const area = document.querySelector('input[name="area"]');
            const location = document.querySelector('input[name="location"]');
            
            let isValid = true;

            // Clear previous validation states
            [title, price, area, location].forEach(field => {
                field.classList.remove('is-invalid', 'is-valid');
            });

            // Validate title
            if (!title.value.trim()) {
                title.classList.add('is-invalid');
                isValid = false;
            } else {
                title.classList.add('is-valid');
            }

            // Validate price
            if (!price.value || parseFloat(price.value) <= 0) {
                price.classList.add('is-invalid');
                isValid = false;
            } else {
                price.classList.add('is-valid');
            }

            // Validate area
            if (!area.value || parseFloat(area.value) <= 0) {
                area.classList.add('is-invalid');
                isValid = false;
            } else {
                area.classList.add('is-valid');
            }

            // Validate location
            if (!location.value.trim()) {
                location.classList.add('is-invalid');
                isValid = false;
            } else {
                location.classList.add('is-valid');
            }

            return isValid;
        }

        function validateStep2() {
            const stateSelect = document.querySelector('select[name="state_id"]');
            const districtSelect = document.querySelector('select[name="district_id"]');
            const citySelect = document.querySelector('select[name="city_id"]');
            const townSelect = document.querySelector('select[name="town_id"]');
            const pincodeInput = document.querySelector('input[name="pincode"]');
            
            let isValid = true;

            // Clear previous validation states
            [stateSelect, districtSelect, citySelect, townSelect, pincodeInput].forEach(field => {
                field.classList.remove('is-invalid', 'is-valid');
            });

            // Validate state
            if (!stateSelect.value || stateSelect.value === '') {
                stateSelect.classList.add('is-invalid');
                isValid = false;
            } else {
                stateSelect.classList.add('is-valid');
            }

            // Validate district
            if (!districtSelect.value || districtSelect.value === '') {
                districtSelect.classList.add('is-invalid');
                isValid = false;
            } else {
                districtSelect.classList.add('is-valid');
            }

            // Validate city
            if (!citySelect.value || citySelect.value === '') {
                citySelect.classList.add('is-invalid');
                isValid = false;
            } else {
                citySelect.classList.add('is-valid');
            }

            // Validate town
            if (!townSelect.value || townSelect.value === '') {
                townSelect.classList.add('is-invalid');
                isValid = false;
            } else {
                townSelect.classList.add('is-valid');
            }

            // Validate pincode
            if (!pincodeInput.value.trim()) {
                pincodeInput.classList.add('is-invalid');
                isValid = false;
            } else {
                pincodeInput.classList.add('is-valid');
            }

            return isValid;
        }

        function validateStep3() {
            // Step 3 has optional fields, so no validation needed
            return true;
        }

        function validateStep4() {
            // Images are optional, but show confirmation if none selected
            if (selectedFiles.length === 0) {
                if (!confirm('No images selected. Do you want to continue without images?')) {
                    return false;
                }
            }
            return true;
        }

        // Modal functionality
        function closeModal() {
            // Reset form
            document.getElementById('propertyForm').reset();
            currentStep = 1;
            updateProgress();
            
            // Call global close function
            if (typeof window.closeAddPropertyModal === 'function') {
                window.closeAddPropertyModal();
            }
        }

        // Close modal on overlay click
        document.getElementById('addPropertyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Initialize the form
        updateProgress();

        // Location dependent selects and inline create
        async function fetchJSON(url, options){ const r = await fetch(url, options); if(!r.ok) return []; try { return await r.json(); } catch { return []; } }
        const stateSelect = document.getElementById('stateSelect');
        const districtSelect = document.getElementById('districtSelect');
        const citySelect = document.getElementById('citySelect');
        const townSelect = document.getElementById('townSelect');
        function hideAllInline(){
            const ids = ['stateAddInline','districtAddInline','cityAddInline','townAddInline'];
            ids.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
        }

        stateSelect && stateSelect.addEventListener('change', async function(){
            const sid = this.value || 0;
            if (this.value === '__add__') {
                hideAllInline();
                this.value = '';
                document.getElementById('stateAddInline').style.display = '';
                document.getElementById('stateAddInput').focus();
                return;
            } else {
                document.getElementById('stateAddInline').style.display = 'none';
            }
            districtSelect.innerHTML = '<option value="">Select District</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';
            townSelect.innerHTML = '<option value="">Select Town</option>';
            if (sid) {
                const d = await fetchJSON('hierarchy.php?action=fetch&level=districts&state_id=' + sid);
                districtSelect.innerHTML = '<option value="">Select District</option>' + d.map(x=>`<option value="${x.id}">${x.name}</option>`).join('') + '<option value="__add__">+ Add District</option>';
            } else {
                districtSelect.insertAdjacentHTML('beforeend', '<option value="__add__">+ Add District</option>');
            }
        });
        districtSelect && districtSelect.addEventListener('change', async function(){
            const did = this.value || 0;
            if (this.value === '__add__') {
                hideAllInline();
                this.value = '';
                document.getElementById('districtAddInline').style.display = '';
                document.getElementById('districtAddInput').focus();
                return;
            } else {
                document.getElementById('districtAddInline').style.display = 'none';
            }
            citySelect.innerHTML = '<option value="">Select City</option>';
            townSelect.innerHTML = '<option value="">Select Town</option>';
            if (did) {
                const c = await fetchJSON('hierarchy.php?action=fetch&level=cities&district_id=' + did);
                citySelect.innerHTML = '<option value="">Select City</option>' + c.map(x=>`<option value="${x.id}">${x.name}</option>`).join('') + '<option value="__add__">+ Add City</option>';
            } else {
                citySelect.insertAdjacentHTML('beforeend', '<option value="__add__">+ Add City</option>');
            }
        });
        citySelect && citySelect.addEventListener('change', async function(){
            const cid = this.value || 0;
            if (this.value === '__add__') {
                hideAllInline();
                this.value = '';
                document.getElementById('cityAddInline').style.display = '';
                document.getElementById('cityAddInput').focus();
                return;
            } else {
                document.getElementById('cityAddInline').style.display = 'none';
            }
            townSelect.innerHTML = '<option value="">Select Town</option>';
            if (cid) {
                const t = await fetchJSON('hierarchy.php?action=fetch&level=towns&city_id=' + cid);
                townSelect.innerHTML = '<option value="">Select Town</option>' + t.map(x=>`<option value="${x.id}">${x.name}</option>`).join('') + '<option value="__add__">+ Add Town</option>';
            } else {
                townSelect.insertAdjacentHTML('beforeend', '<option value="__add__">+ Add Town</option>');
            }
        });

        // Town select inline add
        townSelect && townSelect.addEventListener('change', function(){
            if (this.value === '__add__') {
                hideAllInline();
                this.value = '';
                document.getElementById('townAddInline').style.display = '';
                document.getElementById('townAddInput').focus();
            } else {
                document.getElementById('townAddInline').style.display = 'none';
            }
        });
        async function createItem(scope, payload){
            const form = new FormData();
            form.append('action', 'create');
            form.append('scope', scope);
            Object.entries(payload).forEach(([k,v])=> form.append(k, v));
            const r = await fetch('hierarchy.php', { method:'POST', body: form });
            const j = await r.json().catch(()=>({}));
            if (j && j.id) return j;
            alert(j && j.error ? j.error : 'Failed to create');
            return null;
        }
        // Inline add save/cancel handlers
        document.getElementById('stateAddSave')?.addEventListener('click', async ()=>{
            const name = document.getElementById('stateAddInput').value.trim(); if (!name) return;
            const item = await createItem('state', { name }); if (!item) return;
            stateSelect.insertAdjacentHTML('beforeend', `<option value="${item.id}">${item.name}</option>`);
            stateSelect.value = item.id; document.getElementById('stateAddInline').style.display='none';
            stateSelect.dispatchEvent(new Event('change'));
        });
        document.getElementById('stateAddCancel')?.addEventListener('click', ()=>{ document.getElementById('stateAddInline').style.display='none'; });
        document.getElementById('districtAddSave')?.addEventListener('click', async ()=>{
            const sid = stateSelect.value; if (!sid) { alert('Select state first'); return; }
            const name = document.getElementById('districtAddInput').value.trim(); if (!name) return;
            const item = await createItem('district', { name, state_id: sid }); if (!item) return;
            districtSelect.insertAdjacentHTML('beforeend', `<option value="${item.id}">${item.name}</option>`);
            districtSelect.value = item.id; document.getElementById('districtAddInline').style.display='none';
            districtSelect.dispatchEvent(new Event('change'));
        });
        document.getElementById('districtAddCancel')?.addEventListener('click', ()=>{ document.getElementById('districtAddInline').style.display='none'; });
        document.getElementById('cityAddSave')?.addEventListener('click', async ()=>{
            const did = districtSelect.value; if (!did) { alert('Select district first'); return; }
            const name = document.getElementById('cityAddInput').value.trim(); if (!name) return;
            const item = await createItem('city', { name, district_id: did }); if (!item) return;
            citySelect.insertAdjacentHTML('beforeend', `<option value="${item.id}">${item.name}</option>`);
            citySelect.value = item.id; document.getElementById('cityAddInline').style.display='none';
            citySelect.dispatchEvent(new Event('change'));
        });
        document.getElementById('cityAddCancel')?.addEventListener('click', ()=>{ document.getElementById('cityAddInline').style.display='none'; });
        document.getElementById('townAddSave')?.addEventListener('click', async ()=>{
            const cid = citySelect.value; if (!cid) { alert('Select city first'); return; }
            const name = document.getElementById('townAddInput').value.trim(); if (!name) return;
            const item = await createItem('town', { name, city_id: cid }); if (!item) return;
            townSelect.insertAdjacentHTML('beforeend', `<option value="${item.id}">${item.name}</option>`);
            townSelect.value = item.id; document.getElementById('townAddInline').style.display='none';
        });
        document.getElementById('townAddCancel')?.addEventListener('click', ()=>{ document.getElementById('townAddInline').style.display='none'; });

        // UX: Enter saves; Esc or outside click cancels
        function wireInlineUX(inputId, saveBtnId, cancelBtnId){
            const input = document.getElementById(inputId);
            const saveBtn = document.getElementById(saveBtnId);
            const cancelBtn = document.getElementById(cancelBtnId);
            if (!input) return;
            input.addEventListener('keydown', (e)=>{
                if (e.key === 'Enter') { e.preventDefault(); saveBtn?.click(); }
                if (e.key === 'Escape') { e.preventDefault(); cancelBtn?.click(); }
            });
        }
        wireInlineUX('stateAddInput','stateAddSave','stateAddCancel');
        wireInlineUX('districtAddInput','districtAddSave','districtAddCancel');
        wireInlineUX('cityAddInput','cityAddSave','cityAddCancel');
        wireInlineUX('townAddInput','townAddSave','townAddCancel');

        // Close inline add on outside click
        document.addEventListener('mousedown', function(e){
            const containers = ['stateAddInline','districtAddInline','cityAddInline','townAddInline']
                .map(id => document.getElementById(id))
                .filter(Boolean);
            const clickedInside = containers.some(c => c.contains(e.target));
            if (!clickedInside) {
                hideAllInline();
            }
        });
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
            // Open file dialog when clicking anywhere in the drop area except the button or the file input
            const isInteractive = e.target.closest('button, .btn, input[type="file"]');
            if (isInteractive) return;
            e.preventDefault();
            e.stopPropagation();
            imageInput.click();
        });

        // Prevent bubbling from the "Choose Images" button so it doesn't trigger any parent handlers
        const chooseBtn = document.querySelector('.image-upload-area .btn-outline-primary');
        if (chooseBtn) {
            chooseBtn.addEventListener('click', function(e){ e.stopPropagation(); });
        }

        let isHandlingSelection = false;
        function onFilesSelected(e){
            if (isHandlingSelection) return;
            const files = e.target.files;
            if (!files || files.length === 0) return;
            isHandlingSelection = true;
            // Use rAF to ensure FileList is ready across browsers (first-time selection)
            requestAnimationFrame(() => {
                try {
                    handleFiles(files);
                } finally {
                    isHandlingSelection = false;
                }
            });
        }

        imageInput.addEventListener('change', onFilesSelected, { passive: true });
        imageInput.addEventListener('input', onFilesSelected, { passive: true });

        function handleFiles(files) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            // Clear previous selections
            selectedFiles = [];
            imagePreview.innerHTML = '';
            
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
            
            // Update the file input with the selected files
            updateFileInput();
            updateImageInfo();
        }

        function removeImage(container, file) {
            // Remove from selected files
            selectedFiles = selectedFiles.filter(f => f !== file);
            
            // Remove from DOM
            container.remove();
            
            // Update the file input
            updateFileInput();
            updateImageInfo();
        }

        function updateFileInput() {
            // Create a new DataTransfer object
            const dataTransfer = new DataTransfer();
            
            // Add all selected files to the DataTransfer
            selectedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            
            // Update the file input with the new files
            imageInput.files = dataTransfer.files;
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

        // Form validation - validate all steps before submission
        document.getElementById('propertyForm').addEventListener('submit', function(e) {
            // Validate all steps before allowing submission
            if (!validateStep1() || !validateStep2() || !validateStep3() || !validateStep4()) {
                e.preventDefault();
                // Go to the first step with validation error
                if (!validateStep1()) {
                    currentStep = 1;
                } else if (!validateStep2()) {
                    currentStep = 2;
                } else if (!validateStep3()) {
                    currentStep = 3;
                } else if (!validateStep4()) {
                    currentStep = 4;
                }
                updateProgress();
                return;
            }
            
            // If validation passes, show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Adding Property...';
                submitBtn.disabled = true;
            }
        });
    </script>
</body>
</html>
