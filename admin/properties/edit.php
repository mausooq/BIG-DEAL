<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login/');
    exit();
}

function db() { return getMysqliConnection(); }
    
// Get property ID from URL
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($property_id <= 0) {
    header('Location: index.php');
    exit();
}

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

// Fetch property data
$mysqli = db();
$stmt = $mysqli->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->bind_param('i', $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    header('Location: index.php');
    exit();
}

// Fetch existing images
$images_stmt = $mysqli->prepare("SELECT id, image_url FROM property_images WHERE property_id = ? ORDER BY id");
$images_stmt->bind_param('i', $property_id);
$images_stmt->execute();
$existing_images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$images_stmt->close();

// Determine if property is already featured
$feat_check = $mysqli->prepare("SELECT id FROM features WHERE property_id = ? LIMIT 1");
$feat_check->bind_param('i', $property_id);
$feat_check->execute();
$feat_res = $feat_check->get_result();
$isFeatured = (bool)$feat_res->fetch_assoc();
$feat_check->close();

// Handle feature toggle (add/remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_feature') {
    if ($isFeatured) {
        $delf = $mysqli->prepare('DELETE FROM features WHERE property_id = ?');
        $delf && $delf->bind_param('i', $property_id) && $delf->execute() && $delf->close();
        logActivity($mysqli, 'Removed feature', 'Property ID: ' . $property_id);
        header('Location: edit.php?id=' . $property_id . '&feat_removed=1');
        exit();
    } else {
        $addf = $mysqli->prepare('INSERT INTO features (property_id) VALUES (?)');
        if ($addf) { $addf->bind_param('i', $property_id); $addf->execute(); $addf->close(); }
        logActivity($mysqli, 'Added feature', 'Property ID: ' . $property_id);
        header('Location: edit.php?id=' . $property_id . '&feat_added=1');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        // Update property
        $sql = "UPDATE properties SET title=?, description=?, listing_type=?, price=?, location=?, landmark=?, area=?, configuration=?, category_id=?, furniture_status=?, ownership_type=?, facing=?, parking=?, balcony=?, status=?, map_embed_link=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        // Types: s s s d s s d s i s s s s i s s i
        $stmt->bind_param('sssdssdsissssissi', $title, $description, $listing_type, $price, $location, $landmark, $area, $configuration, $category_id, $furniture_status, $ownership_type, $facing, $parking, $balcony, $status, $map_embed_link, $property_id);
        
        if (!$stmt->execute()) { 
            throw new Exception('Failed to update property: ' . $mysqli->error); 
        }
        $stmt->close();

        // Upsert properties_location
        $check = $mysqli->prepare('SELECT id FROM properties_location WHERE property_id = ? LIMIT 1');
        $check && $check->bind_param('i', $property_id) && $check->execute();
        $row = $check ? $check->get_result()->fetch_assoc() : null;
        $check && $check->close();
        if ($row) {
            $upd = $mysqli->prepare('UPDATE properties_location SET state_id=?, district_id=?, city_id=?, town_id=?, pincode=? WHERE property_id=?');
            if ($upd) { $upd->bind_param('iiiisi', $state_id, $district_id, $city_id, $town_id, $pincode, $property_id); $upd->execute(); $upd->close(); }
        } else {
            $ins = $mysqli->prepare('INSERT INTO properties_location (property_id, state_id, district_id, city_id, town_id, pincode) VALUES (?, ?, ?, ?, ?, ?)');
            if ($ins) { $ins->bind_param('iiiiis', $property_id, $state_id, $district_id, $city_id, $town_id, $pincode); $ins->execute(); $ins->close(); }
        }

        // Handle image uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = '../../uploads/properties/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

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
        }

        // Handle image deletion
        if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $image_id) {
                $img_id = (int)$image_id;
                if ($img_id > 0) {
                    // Get image URL for deletion
                    $get_img_stmt = $mysqli->prepare("SELECT image_url FROM property_images WHERE id = ? AND property_id = ?");
                    $get_img_stmt->bind_param('ii', $img_id, $property_id);
                    $get_img_stmt->execute();
                    $img_result = $get_img_stmt->get_result()->fetch_assoc();
                    $get_img_stmt->close();
                    
                    if ($img_result) {
                        // Delete file - construct full path since we store only filename
                        $file_path = '../../uploads/properties/' . basename($img_result['image_url']);
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                        
                        // Delete database record
                        $del_img_stmt = $mysqli->prepare("DELETE FROM property_images WHERE id = ?");
                        $del_img_stmt->bind_param('i', $img_id);
                        $del_img_stmt->execute();
                        $del_img_stmt->close();
                    }
                }
            }
        }

        logActivity($mysqli, 'Updated property', 'Title: ' . $title . ', ID: ' . $property_id);
        
        // Redirect to properties list after successful update
        header('Location: index.php?updated=1');
        exit();
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get categories and states for dropdowns
$categoriesRes = $mysqli->query("SELECT id, name FROM categories ORDER BY name");
$statesRes = $mysqli->query("SELECT id, name FROM states ORDER BY name");

// Load existing structured location
$pl_stmt = $mysqli->prepare('SELECT state_id, district_id, city_id, town_id, pincode FROM properties_location WHERE property_id = ? LIMIT 1');
$pl_stmt && $pl_stmt->bind_param('i', $property_id) && $pl_stmt->execute();
$pl = $pl_stmt ? $pl_stmt->get_result()->fetch_assoc() : null;
$pl_stmt && $pl_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property</title>
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ef4444;
            --border-color: #E0E0E0;
            --text-color: #333;
            --label-color: #555;
            --bg-color: #F4F7FA;
            --card-bg-color: #FFFFFF;
        }
        body, input, select, textarea, button { font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif; }
        body { background: var(--bg-color); margin:0; color: var(--text-color); }
        .background-iframe { position: fixed; inset: 0; width:100%; height:100%; border:none; z-index:-2; }
        .blur-overlay { position: fixed; inset:0; background: rgba(0,0,0,.4); backdrop-filter: blur(3px); z-index:-1; }
        .modal-overlay { position: fixed; inset:0; display:flex; align-items:center; justify-content:center; padding:20px; z-index:1000; }
        .modal-container { background: var(--card-bg-color); border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.3); width:100%; max-width:900px; max-height:90vh; overflow:auto; position:relative; border:1px solid rgba(255,255,255,0.1); }
        .order-card { padding: 2rem; box-sizing: border-box; display:flex; flex-direction:column; }
        .card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.25rem; }
        .card-header h1 { font-size: 1.4rem; font-weight: 600; margin:0; }
        .close-btn { font-size: 1.5rem; color:#999; cursor:pointer; border:none; background:none; }
        .section-title { font-weight:600; margin: 1.25rem 0 .75rem; }
        label { font-size: .9rem; color: var(--label-color); font-weight:500; margin-bottom: 6px; display:block; }
        input[type="text"], input[type="number"], input[type="url"], select, textarea { width:100%; padding:.75rem; border:1px solid var(--border-color); border-radius:8px; font-family:'Inter',sans-serif; font-size:1rem; box-sizing:border-box; }
        textarea { min-height: 100px; resize: vertical; }
        .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .full { grid-column: 1 / -1; }
        .inline-add { margin-top:8px; }
        .existing-image { position:relative; display:inline-block; margin:5px; }
        .existing-image img { max-width:150px; max-height:150px; object-fit:cover; border-radius:8px; border:1px solid var(--border-color); }
        .image-delete-btn { position:absolute; top:-6px; right:-6px; width:22px; height:22px; border-radius:50%; background:#dc2626; color:#fff; border:none; cursor:pointer; font-size:12px; display:flex; align-items:center; justify-content:center; }
        .image-upload-box { border:1px dashed var(--border-color); border-radius:12px; padding:1rem; text-align:center; background:#fafafa; }
        .btn { padding: .75rem 1.25rem; border-radius:8px; border:1px solid var(--border-color); background:#fff; cursor:pointer; font-weight:500; text-decoration:none; display:inline-block; }
        .btn-primary { background: var(--primary-color); color:#fff; border:none; }
        .btn-secondary { background:#fff; color: var(--text-color); }
        .btn-secondary:hover { background:#f5f5f5; }
        .footer-actions { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; }
        /* New images preview grid */
        #imagePreview { display:flex; flex-wrap:wrap; gap:8px; }
        .image-preview-container { position:relative; width:150px; height:150px; border-radius:8px; overflow:hidden; border:1px solid var(--border-color); }
        .image-preview-container img { width:100%; height:100%; object-fit:cover; display:block; }
        .image-remove-btn { position:absolute; top:6px; right:6px; width:22px; height:22px; border-radius:50%; background:#ef4444; color:#fff; border:none; cursor:pointer; font-size:12px; display:flex; align-items:center; justify-content:center; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <iframe src="index.php" class="background-iframe" title="Properties Background"></iframe>
    <div class="blur-overlay"></div>
    <div class="modal-overlay">
        <div class="modal-container">
            <div class="order-card">
                <header class="card-header">
                    <h1>Edit Property</h1>
                    <button class="close-btn" aria-label="Close" onclick="window.location.href='index.php'">&times;</button>
                </header>

                <?php if ($message): ?>
                    <div style="background-color:#eef9f1;border:1px solid #ccead6;color:#166534;padding:10px;margin:10px 0;border-radius:6px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="propertyForm">
                    <div>
                        <div class="section-title">Basic Information</div>
                        <div class="grid">
                            <div class="full">
                                <label>Property Title<span style="color:#dc2626"> *</span></label>
                                <input type="text" name="title" required value="<?php echo htmlspecialchars($property['title']); ?>" placeholder="e.g., Beautiful 3BHK Apartment in Downtown">
                            </div>
                            <div>
                                <label>Listing Type<span style="color:#dc2626"> *</span></label>
                                <select name="listing_type" required>
                                    <option value="Buy" <?php echo $property['listing_type'] === 'Buy' ? 'selected' : ''; ?>>Buy</option>
                                    <option value="Rent" <?php echo $property['listing_type'] === 'Rent' ? 'selected' : ''; ?>>Rent</option>
                                    <option value="PG/Co-living" <?php echo $property['listing_type'] === 'PG/Co-living' ? 'selected' : ''; ?>>PG/Co-living</option>
                                </select>
                            </div>
                            <div>
                                <label>Status</label>
                                <select name="status">
                                    <option value="Available" <?php echo $property['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="Sold" <?php echo $property['status'] === 'Sold' ? 'selected' : ''; ?>>Sold</option>
                                    <option value="Rented" <?php echo $property['status'] === 'Rented' ? 'selected' : ''; ?>>Rented</option>
                                </select>
                            </div>
                            <div>
                                <label>Price (₹)<span style="color:#dc2626"> *</span></label>
                                <input type="number" step="0.01" name="price" required value="<?php echo htmlspecialchars($property['price'] ?? ''); ?>" placeholder="0.00">
                            </div>
                            <div>
                                <label>Area (sq ft)<span style="color:#dc2626"> *</span></label>
                                <input type="number" step="0.01" name="area" required value="<?php echo htmlspecialchars($property['area'] ?? ''); ?>" placeholder="0">
                            </div>
                            <div>
                                <label>Location<span style="color:#dc2626"> *</span></label>
                                <input type="text" name="location" required value="<?php echo htmlspecialchars($property['location'] ?? ''); ?>" placeholder="City, Area, Locality">
                            </div>
                            <div>
                                <label>Landmark</label>
                                <input type="text" name="landmark" value="<?php echo htmlspecialchars($property['landmark'] ?? ''); ?>" placeholder="Nearby landmark or reference point">
                            </div>
                            <div>
                                <label>Configuration</label>
                                <input type="text" name="configuration" value="<?php echo htmlspecialchars($property['configuration'] ?? ''); ?>" placeholder="e.g., 2BHK, 3BHK">
                            </div>
                            <div>
                                <label>Category</label>
                                <select name="category_id">
                                    <option value="">Select Category</option>
                                    <?php while($c = $categoriesRes->fetch_assoc()): ?>
                                        <option value="<?php echo (int)$c['id']; ?>" <?php echo ($property['category_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="full">
                                <label>Description</label>
                                <textarea name="description" placeholder="Describe the property features, amenities, and unique selling points..."><?php echo htmlspecialchars($property['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="full">
                                <label>Map Embed Link</label>
                                <input type="url" name="map_embed_link" value="<?php echo htmlspecialchars($property['map_embed_link'] ?? ''); ?>" placeholder="https://www.google.com/maps/embed?pb=...">
                            </div>
                        </div>

                        <div class="section-title">Location Details</div>
                        <div class="grid">
                            <div>
                                <label>State<span style="color:#dc2626"> *</span></label>
                                <select id="stateSelect" name="state_id" required>
                                    <option value="">Select State</option>
                                    <?php while($s = $statesRes->fetch_assoc()): ?>
                                        <option value="<?php echo (int)$s['id']; ?>" <?php echo (!empty($pl['state_id']) && (int)$pl['state_id'] === (int)$s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endwhile; ?>
                                    <option value="__add__">+ Add State</option>
                                </select>
                                <div id="stateAddInline" class="inline-add" style="display:none;">
                                    <div style="display:flex; gap:6px;">
                                        <input type="text" id="stateAddInput" placeholder="New state name" style="flex:1; padding:8px; border:1px solid #E0E0E0; border-radius:8px;">
                                        <button type="button" class="btn btn-primary" id="stateAddSave">Save</button>
                                        <button type="button" class="btn btn-secondary" id="stateAddCancel">Cancel</button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label>District<span style="color:#dc2626"> *</span></label>
                                <select id="districtSelect" name="district_id" required>
                                    <option value="">Select District</option>
                                    <option value="__add__">+ Add District</option>
                                </select>
                                <div id="districtAddInline" class="inline-add" style="display:none;">
                                    <div style="display:flex; gap:6px;">
                                        <input type="text" id="districtAddInput" placeholder="New district name" style="flex:1; padding:8px; border:1px solid #E0E0E0; border-radius:8px;">
                                        <button type="button" class="btn btn-primary" id="districtAddSave">Save</button>
                                        <button type="button" class="btn btn-secondary" id="districtAddCancel">Cancel</button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label>City<span style="color:#dc2626"> *</span></label>
                                <select id="citySelect" name="city_id" required>
                                    <option value="">Select City</option>
                                    <option value="__add__">+ Add City</option>
                                </select>
                                <div id="cityAddInline" class="inline-add" style="display:none;">
                                    <div style="display:flex; gap:6px;">
                                        <input type="text" id="cityAddInput" placeholder="New city name" style="flex:1; padding:8px; border:1px solid #E0E0E0; border-radius:8px;">
                                        <button type="button" class="btn btn-primary" id="cityAddSave">Save</button>
                                        <button type="button" class="btn btn-secondary" id="cityAddCancel">Cancel</button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label>Town<span style="color:#dc2626"> *</span></label>
                                <select id="townSelect" name="town_id" required>
                                    <option value="">Select Town</option>
                                    <option value="__add__">+ Add Town</option>
                                </select>
                                <div id="townAddInline" class="inline-add" style="display:none;">
                                    <div style="display:flex; gap:6px;">
                                        <input type="text" id="townAddInput" placeholder="New town name" style="flex:1; padding:8px; border:1px solid #E0E0E0; border-radius:8px;">
                                        <button type="button" class="btn btn-primary" id="townAddSave">Save</button>
                                        <button type="button" class="btn btn-secondary" id="townAddCancel">Cancel</button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label>Pincode<span style="color:#dc2626"> *</span></label>
                                <input type="text" name="pincode" id="pincodeInput" placeholder="e.g., 560001" value="<?php echo htmlspecialchars($pl['pincode'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="section-title">Property Details</div>
                        <div class="grid">
                            <div>
                                <label>Furniture Status</label>
                                <select name="furniture_status">
                                    <option value="">Select</option>
                                    <option value="Furnished" <?php echo $property['furniture_status'] === 'Furnished' ? 'selected' : ''; ?>>Furnished</option>
                                    <option value="Semi-Furnished" <?php echo $property['furniture_status'] === 'Semi-Furnished' ? 'selected' : ''; ?>>Semi-Furnished</option>
                                    <option value="Unfurnished" <?php echo $property['furniture_status'] === 'Unfurnished' ? 'selected' : ''; ?>>Unfurnished</option>
                                </select>
                            </div>
                            <div>
                                <label>Ownership Type</label>
                                <select name="ownership_type">
                                    <option value="">Select</option>
                                    <option value="Freehold" <?php echo $property['ownership_type'] === 'Freehold' ? 'selected' : ''; ?>>Freehold</option>
                                    <option value="Leasehold" <?php echo $property['ownership_type'] === 'Leasehold' ? 'selected' : ''; ?>>Leasehold</option>
                                </select>
                            </div>
                            <div>
                                <label>Facing</label>
                                <select name="facing">
                                    <option value="">Select</option>
                                    <option value="East" <?php echo $property['facing'] === 'East' ? 'selected' : ''; ?>>East</option>
                                    <option value="West" <?php echo $property['facing'] === 'West' ? 'selected' : ''; ?>>West</option>
                                    <option value="North" <?php echo $property['facing'] === 'North' ? 'selected' : ''; ?>>North</option>
                                    <option value="South" <?php echo $property['facing'] === 'South' ? 'selected' : ''; ?>>South</option>
                                </select>
                            </div>
                            <div>
                                <label>Parking</label>
                                <select name="parking">
                                    <option value="">Select</option>
                                    <option value="Yes" <?php echo $property['parking'] === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="No" <?php echo $property['parking'] === 'No' ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            <div>
                                <label>Number of Balconies</label>
                                <input type="number" name="balcony" min="0" value="<?php echo htmlspecialchars($property['balcony'] ?? '0'); ?>">
                            </div>
                        </div>

                        <?php if (!empty($existing_images)): ?>
                        <div class="section-title">Current Images</div>
                        <div>
                            <?php foreach ($existing_images as $image): ?>
                                <div class="existing-image">
                                    <img src="../../uploads/properties/<?php echo htmlspecialchars(basename($image['image_url'])); ?>" alt="Property Image">
                                    <button type="button" class="image-delete-btn" onclick="deleteImage(<?php echo $image['id']; ?>)">×</button>
                                    <input type="hidden" name="delete_images[]" id="delete_<?php echo $image['id']; ?>" value="">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="section-title">Add New Images</div>
                        <div class="image-upload-box" id="imageUploadArea">
                            <div style="margin-bottom:8px;font-weight:500;">Drop images here or click to browse</div>
                            <div style="color:#6b7280; font-size:.9rem;">Upload multiple images (JPG, PNG, GIF, WebP) - Max 5MB each</div>
                            <input type="file" name="images[]" id="imageInput" multiple accept="image/*" style="display:none;">
                            <div style="margin-top:10px;">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('imageInput').click()">Choose Images</button>
                                <button type="button" class="btn btn-secondary" id="clearNewImages" style="margin-left:8px;">Clear Selected</button>
                            </div>
                        </div>
                        <div id="imagePreview" style="margin-top:10px;"></div>
                        <div id="imageInfo" style="margin-top:6px;color:#6b7280;font-size:.9rem;"></div>

                        <div class="footer-actions">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn-animated-confirm noselect">
                                <span class="text">Update Property</span>
                                <span class="icon">
                                    <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Clear selected new images button
        (function(){
            const clearBtn = document.getElementById('clearNewImages');
            const imageInput = document.getElementById('imageInput');
            const imagePreview = document.getElementById('imagePreview');
            const imageInfo = document.getElementById('imageInfo');
            if (clearBtn && imageInput) {
                clearBtn.addEventListener('click', function(){
                    // Reset file input
                    imageInput.value = '';
                    // Clear previews/info if present
                    if (imagePreview) imagePreview.innerHTML = '';
                    if (imageInfo) imageInfo.innerHTML = '';
                });
            }
        })();
        // Prefill chained selects on load
        const existing = {
            state_id: <?php echo isset($pl['state_id']) ? (int)$pl['state_id'] : 0; ?>,
            district_id: <?php echo isset($pl['district_id']) ? (int)$pl['district_id'] : 0; ?>,
            city_id: <?php echo isset($pl['city_id']) ? (int)$pl['city_id'] : 0; ?>,
            town_id: <?php echo isset($pl['town_id']) ? (int)$pl['town_id'] : 0; ?>
        };
        async function fetchJSON(url, options){ const r = await fetch(url, options); if(!r.ok) return []; try { return await r.json(); } catch { return []; } }
        const stateSelect = document.getElementById('stateSelect');
        const districtSelect = document.getElementById('districtSelect');
        const citySelect = document.getElementById('citySelect');
        const townSelect = document.getElementById('townSelect');
        function hideAllInline(){
            const ids = ['stateAddInline','districtAddInline','cityAddInline','townAddInline'];
            ids.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
        }
        async function loadDistricts(){
            const sid = stateSelect.value || 0;
            const d = sid ? await fetchJSON('hierarchy.php?action=fetch&level=districts&state_id=' + sid) : [];
            districtSelect.innerHTML = '<option value="">Select District</option>' + d.map(x=>`<option value="${x.id}">${x.name}</option>`).join('');
            if (existing.district_id) { districtSelect.value = existing.district_id; }
        }
        async function loadCities(){
            const did = districtSelect.value || 0;
            const c = did ? await fetchJSON('hierarchy.php?action=fetch&level=cities&district_id=' + did) : [];
            citySelect.innerHTML = '<option value="">Select City</option>' + c.map(x=>`<option value="${x.id}">${x.name}</option>`).join('');
            if (existing.city_id) { citySelect.value = existing.city_id; }
        }
        async function loadTowns(){
            const cid = citySelect.value || 0;
            const t = cid ? await fetchJSON('hierarchy.php?action=fetch&level=towns&city_id=' + cid) : [];
            townSelect.innerHTML = '<option value="">Select Town</option>' + t.map(x=>`<option value="${x.id}">${x.name}</option>`).join('');
            if (existing.town_id) { townSelect.value = existing.town_id; }
        }
        stateSelect && stateSelect.addEventListener('change', async function(){
            if (this.value === '__add__') {
                this.value = '';
                document.getElementById('stateAddInline').style.display = '';
                document.getElementById('stateAddInput').focus();
                return;
            } else {
                document.getElementById('stateAddInline').style.display = 'none';
            }
            existing.district_id=0; existing.city_id=0; existing.town_id=0;
            await loadDistricts();
            districtSelect.insertAdjacentHTML('beforeend', '<option value="__add__">+ Add District</option>');
            citySelect.innerHTML='<option value="">Select City</option>';
            townSelect.innerHTML='<option value="">Select Town</option>';
        });
        districtSelect && districtSelect.addEventListener('change', async function(){
            if (this.value === '__add__') {
                this.value = '';
                document.getElementById('districtAddInline').style.display = '';
                document.getElementById('districtAddInput').focus();
                return;
            } else {
                document.getElementById('districtAddInline').style.display = 'none';
            }
            existing.city_id=0; existing.town_id=0;
            await loadCities();
            citySelect.insertAdjacentHTML('beforeend', '<option value="__add__">+ Add City</option>');
            townSelect.innerHTML='<option value="">Select Town</option>';
        });
        citySelect && citySelect.addEventListener('change', async function(){
            if (this.value === '__add__') {
                this.value = '';
                document.getElementById('cityAddInline').style.display = '';
                document.getElementById('cityAddInput').focus();
                return;
            } else {
                document.getElementById('cityAddInline').style.display = 'none';
            }
            existing.town_id=0; await loadTowns();
            townSelect.insertAdjacentHTML('beforeend', '<option value="__add__">+ Add Town</option>');
        });
        // initialize
        (async function initChains(){ if (existing.state_id) { await loadDistricts(); await loadCities(); await loadTowns(); } })();

        async function promptName(label){ return ''; }
        async function createItem(scope, payload){
            const form = new FormData(); form.append('action','create'); form.append('scope', scope);
            Object.entries(payload).forEach(([k,v])=> form.append(k, v));
            const r = await fetch('hierarchy.php', { method:'POST', body: form });
            const j = await r.json().catch(()=>({})); if (j && j.id) return j; alert(j && j.error ? j.error : 'Failed to create'); return null;
        }
        // Inline add save/cancel
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
                    previewContainer.className = 'image-preview-container';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.title = file.name;

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'image-remove-btn';
                    removeBtn.textContent = '×';
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
                    ${count} new image${count !== 1 ? 's' : ''} selected (${sizeInMB} MB total)
                `;
            } else {
                imageInfo.innerHTML = '';
            }
        }

        // Delete existing image
        function deleteImage(imageId) {
            if (confirm('Are you sure you want to delete this image?')) {
                document.getElementById('delete_' + imageId).value = imageId;
                document.querySelector('[onclick="deleteImage(' + imageId + ')"]').closest('.existing-image').style.display = 'none';
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
        });
    </script>
</body>
</html>
