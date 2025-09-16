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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        /* Base */
        
        body{ background:var(--bg); color:#111827; }

         /* account for sidebar margin */

        /* Topbar */

        /* Cards */
        
        .card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
         /* Form elements */
         .form-control, .form-select{ border-radius:12px; border:1px solid var(--line); }
         .form-control:focus, .form-select:focus{ border-color:var(--line); box-shadow:none; }
         .form-control::placeholder{ font-weight:300; color:#9ca3af; opacity:0.8; }
         .form-label{ font-weight:600; color:#111827; }
         .form-text{ color:var(--muted); font-size:0.875rem; }
        /* Buttons */   
        .btn{ border-radius:12px; font-weight:500; }
        .btn-primary{ background:var(--primary); border-color:var(--primary); }
        .btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        .btn-outline-primary{ color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover{ background-color: var(--primary); border-color: var(--primary); color:#fff; }
        .btn-outline-secondary{ color: var(--muted); border-color: var(--muted); }
        .btn-outline-secondary:hover{ background-color: var(--muted); border-color: var(--muted); color:#fff; }
        /* Section styling */
        .section-header{ border-bottom:2px solid var(--line); padding-bottom:1rem; margin-bottom:2rem; }
        .section-title{ color:var(--brand-dark); font-weight:600; font-size:1.1rem; }
        .required{ color:var(--primary); font-weight:600; }
        /* Image upload */
        .image-preview{ max-width:150px; max-height:150px; object-fit:cover; border-radius:8px; margin:5px; }
        .image-upload-area{ border:2px dashed var(--line); border-radius:12px; padding:2rem; text-align:center; background:#f8fafc; transition:all 0.3s ease; }
        .image-upload-area:hover{ border-color:var(--primary); background:#fef2f2; }
        .image-upload-area.dragover{ border-color:var(--primary); background:#fef2f2; }
        /* Typography */
        .h4, .h5, .h6{ font-weight:600; color:#111827; }
        .text-muted{ color:var(--muted)!important; }
        .fw-semibold{ font-weight:600; }
        /* Alerts */
        .alert{ border:0; border-radius:12px; }
        .alert-success{ background:#f0f9ff; color:#1e40af; border-left:4px solid #3b82f6; }
        .alert-danger{ background:#fef2f2; color:#dc2626; border-left:4px solid var(--primary); }
        /* Mobile responsiveness */

        }
        @media (max-width: 575.98px){
            .section-title{ font-size:1rem; }
            .form-label{ font-size:0.9rem; }
        }
        /* Inline add dropdown overlays */
        .location-grid .col-md-6, .location-grid .col-md-12, .location-grid .col-md-4 { position: relative; }
        .inline-add { position:absolute; left:0; right:0; top: calc(100% + 6px); z-index: 5; background: var(--card); }
        .inline-add .input-group { box-shadow: 0 6px 20px rgba(0,0,0,.12); border-radius: 8px; }
        .location-card { padding-bottom: 64px; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('properties'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Property'); ?>

        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="h4 mb-1 fw-semibold">Add New Property</h2>
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
                    <div class="col-lg-12">
                        <div class="card mb-4 location-card">
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

                        <!-- Structured Location -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="section-header">
                                    <h5 class="section-title"><i class="fa-solid fa-location-dot me-2"></i>Location Details</h5>
                                </div>
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
                                
                                <!-- Form Actions inside form -->
                                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fa-solid fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn-animated-confirm noselect">
                                        <span class="text">Add Property</span>
                                        <span class="icon">
                                            <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // Form validation
        document.getElementById('propertyForm').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const location = document.querySelector('input[name="location"]').value.trim();
            const price = document.querySelector('input[name="price"]').value;
            const area = document.querySelector('input[name="area"]').value;
            
            // Location details validation
            const stateId = document.querySelector('select[name="state_id"]').value;
            const districtId = document.querySelector('select[name="district_id"]').value;
            const cityId = document.querySelector('select[name="city_id"]').value;
            const townId = document.querySelector('select[name="town_id"]').value;
            const pincode = document.querySelector('input[name="pincode"]').value.trim();

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
            
            // Validate location details
            if (!stateId || stateId === '') {
                alert('Please select a state');
                e.preventDefault();
                return;
            }
            if (!districtId || districtId === '') {
                alert('Please select a district');
                e.preventDefault();
                return;
            }
            if (!cityId || cityId === '') {
                alert('Please select a city');
                e.preventDefault();
                return;
            }
            if (!townId || townId === '') {
                alert('Please select a town');
                e.preventDefault();
                return;
            }
            if (!pincode || pincode === '') {
                alert('Please enter a pincode');
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
