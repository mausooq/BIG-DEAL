<?php
// Require authentication first
require_once __DIR__ . '/../auth.php';

// Start the session to store form data across steps
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';
$mysqli = getMysqliConnection();
// Load states for dropdown
$states = [];
$rs = $mysqli->query("SELECT id, name FROM states ORDER BY name");
if ($rs) { while($row = $rs->fetch_assoc()){ $states[] = $row; } $rs->close(); }
// Load categories for dropdown
$categories = [];
$rc = $mysqli->query("SELECT id, name FROM categories ORDER BY name");
if ($rc) { while($row = $rc->fetch_assoc()){ $categories[] = $row; } $rc->close(); }

// Define the total number of steps
$total_steps = 5;

// Determine the current step. Default to 1 if not set.
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($current_step < 1 || $current_step > $total_steps) {
    $current_step = 1;
}

// If Plot category selected, do not allow landing on step 3
if ($current_step === 3 && is_plot_category($mysqli)) {
    header('Location: ?step=4');
    exit();
}

// Initialize session data if it doesn't exist
if (!isset($_SESSION['form_data']) || !is_array($_SESSION['form_data'])) {
    $_SESSION['form_data'] = [];
}

// Helper: is selected category a Plot? (case-insensitive match on category name)
function is_plot_category($mysqli) {
    $cid = isset($_SESSION['form_data']['category_id']) ? (int)$_SESSION['form_data']['category_id'] : 0;
    if ($cid <= 0) { return false; }
    $res = $mysqli->query("SELECT name FROM categories WHERE id=".$cid." LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        return strcasecmp(trim($row['name']), 'Plot') === 0;
    }
    return false;
}

// Gating helpers: validation and furthest step logic
function _get_form_value($key) { return $_SESSION['form_data'][$key] ?? ''; }

function validate_step_1(&$msg) {
    $title = trim(_get_form_value('title'));
    $price = _get_form_value('price');
    $area = _get_form_value('area');
    $landmark = trim(_get_form_value('landmark'));
    $configuration = trim(_get_form_value('configuration'));
    $category_id = _get_form_value('category_id');
    $description = trim(_get_form_value('description'));
    $map_embed_link = trim(_get_form_value('map_embed_link'));
    
    if ($title === '') { $msg = 'Please enter Property Title.'; return false; }
    if ($price === '' || !is_numeric($price) || (float)$price <= 0) { $msg = 'Please enter a valid Price.'; return false; }
    if ($area === '' || !is_numeric($area) || (float)$area <= 0) { $msg = 'Please enter a valid Area.'; return false; }
    if ($landmark === '') { $msg = 'Please enter Landmark.'; return false; }
    if ($configuration === '') { $msg = 'Please enter Configuration (e.g., 2BHK, 3BHK).'; return false; }
    if ($category_id === '') { $msg = 'Please select Category.'; return false; }
    if ($description === '') { $msg = 'Please enter Description.'; return false; }
    if ($map_embed_link === '') { $msg = 'Please enter Map Embed Link.'; return false; }
    
    return true;
}

function validate_step_2(&$msg) {
    $state_id = (int)(_get_form_value('state_id') ?: 0);
    $district_id = (int)(_get_form_value('district_id') ?: 0);
    $city_id = (int)(_get_form_value('city_id') ?: 0);
    $town_id = (int)(_get_form_value('town_id') ?: 0);
    $pincode = trim(_get_form_value('pincode'));
    
    if ($state_id <= 0) { $msg = 'Please select State.'; return false; }
    if ($district_id <= 0) { $msg = 'Please select District.'; return false; }
    if ($city_id <= 0) { $msg = 'Please select City.'; return false; }
    if ($town_id <= 0) { $msg = 'Please select Town.'; return false; }
    if ($pincode === '') { $msg = 'Please enter Pincode.'; return false; }
    
    return true;
}

function validate_step_3(&$msg) {
    $furniture_status = trim(_get_form_value('furniture_status'));
    $ownership_type = trim(_get_form_value('ownership_type'));
    $facing = trim(_get_form_value('facing'));
    $parking = trim(_get_form_value('parking'));
    $balcony = _get_form_value('balcony');
    
    if ($furniture_status === '') { $msg = 'Please select Furniture Status.'; return false; }
    if ($ownership_type === '') { $msg = 'Please select Ownership Type.'; return false; }
    if ($facing === '') { $msg = 'Please select Facing.'; return false; }
    if ($parking === '') { $msg = 'Please select Parking.'; return false; }
    if ($balcony === '' || !is_numeric($balcony) || (int)$balcony < 0) { $msg = 'Please enter valid Number of Balconies.'; return false; }
    
    return true;
}

function validate_step_4(&$msg) {
    // Check if at least one image is uploaded
    $uploaded_files = $_SESSION['uploaded_images'] ?? [];
    $images_data = $_SESSION['form_data']['images_data'] ?? [];
    
    if (empty($uploaded_files) && empty($images_data)) {
        $msg = 'Please upload at least one property image.';
        return false;
    }
    
    return true;
}

function furthest_allowed_step($mysqli) {
    $furthest = 1; $m = '';
    if (!validate_step_1($m)) { return $furthest; }
    $furthest = 2;
    if (!validate_step_2($m)) { return $furthest; }
    // If category is Plot, step 3 is skipped; allow reaching preview (5) after images (4)
    if (is_plot_category($mysqli)) { 
        $furthest = 4;
        if (!validate_step_4($m)) { return $furthest; }
        return 5; 
    }
    $furthest = 3;
    if (!validate_step_3($m)) { return $furthest; }
    $furthest = 4;
    if (!validate_step_4($m)) { return $furthest; }
    // After step 4 (images), allow reaching preview (5)
    return 5;
}

// Debug: Log session state (remove in production)
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    error_log("Session Debug - Current Step: " . $current_step);
    error_log("Session Debug - Form Data Keys: " . implode(', ', array_keys($_SESSION['form_data'])));
    error_log("Session Debug - Form Data Count: " . count($_SESSION['form_data']));
}

// Handle "New Property" request - clear all form data
if (isset($_GET['new']) && $_GET['new'] == '1') {
    unset($_SESSION['form_data']);
    $_SESSION['form_data'] = [];
    $current_step = 1;
    // Clear sessionStorage as well
    echo "<script>sessionStorage.removeItem('prop_images');</script>";
}

// Auto-clear form data only when explicitly starting fresh (not from navigation)
if (isset($_GET['fresh']) && $_GET['fresh'] == '1') {
    // This is an explicit fresh start, clear any existing form data
    unset($_SESSION['form_data']);
    $_SESSION['form_data'] = [];
    $current_step = 1;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and save data for the submitted step
    $submitted_step = isset($_POST['step']) ? (int)$_POST['step'] : $current_step;
    
    // Ensure session data array exists
    if (!isset($_SESSION['form_data']) || !is_array($_SESSION['form_data'])) {
        $_SESSION['form_data'] = [];
    }
    
    foreach ($_POST as $key => $value) {
        if ($key !== 'step' && $key !== 'goto_step' && $key !== 'final_submit') {
            if (is_array($value)) {
                // Store arrays (e.g., images_data[])
                $_SESSION['form_data'][$key] = array_map(function($v){ 
                    return is_string($v) ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : ''; 
                }, $value);
                
                // Debug: Log image data when received
                if ($key === 'images_data') {
                    error_log("Received images_data array with " . count($value) . " items");
                }
            } else {
                $_SESSION['form_data'][$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
    }

    // Final submit: persist to database
    if (isset($_POST['final_submit']) && (int)$_POST['final_submit'] === 1) {
        $mysqli = getMysqliConnection();
        try {
            // Get form data from session
            $title = trim($_SESSION['form_data']['title'] ?? '');
            $description = trim($_SESSION['form_data']['description'] ?? '');
            $listing_type = $_SESSION['form_data']['listing_type'] ?? 'Buy';
            $price = $_SESSION['form_data']['price'] !== '' ? (float)$_SESSION['form_data']['price'] : null;
            $location = '';
            $landmark = trim($_SESSION['form_data']['landmark'] ?? '');
            $area = $_SESSION['form_data']['area'] !== '' ? (float)$_SESSION['form_data']['area'] : null;
            $configuration = trim($_SESSION['form_data']['configuration'] ?? '');
            $category_id = isset($_SESSION['form_data']['category_id']) && $_SESSION['form_data']['category_id'] !== '' ? (int)$_SESSION['form_data']['category_id'] : null;
            $furniture_status = $_SESSION['form_data']['furniture_status'] ?? null;
            $ownership_type = $_SESSION['form_data']['ownership_type'] ?? null;
            $facing = $_SESSION['form_data']['facing'] ?? null;
            $parking = $_SESSION['form_data']['parking'] ?? null;
            $balcony = (int)($_SESSION['form_data']['balcony'] ?? 0);
            $status = $_SESSION['form_data']['status'] ?? 'Available';
            $map_embed_link = trim($_SESSION['form_data']['map_embed_link'] ?? '');

        // Validation
            if ($title === '' || $price === null || $price <= 0 || $area === null || $area <= 0) {
                throw new Exception('Please complete required fields before finishing.');
            }

        // Structured location
            $state_id = isset($_SESSION['form_data']['state_id']) && $_SESSION['form_data']['state_id'] !== '' ? (int)$_SESSION['form_data']['state_id'] : 0;
            $district_id = isset($_SESSION['form_data']['district_id']) && $_SESSION['form_data']['district_id'] !== '' ? (int)$_SESSION['form_data']['district_id'] : 0;
            $city_id = isset($_SESSION['form_data']['city_id']) && $_SESSION['form_data']['city_id'] !== '' ? (int)$_SESSION['form_data']['city_id'] : 0;
            $town_id = isset($_SESSION['form_data']['town_id']) && $_SESSION['form_data']['town_id'] !== '' ? (int)$_SESSION['form_data']['town_id'] : 0;
            $pincode = trim($_SESSION['form_data']['pincode'] ?? '');
            
        if ($state_id <= 0 || $district_id <= 0 || $city_id <= 0 || $town_id <= 0 || $pincode === '') {
                throw new Exception('Please complete the location hierarchy before finishing.');
        }

            // Start transaction
            $mysqli->begin_transaction();

        // Insert property
        $sql = "INSERT INTO properties (title, description, listing_type, price, location, landmark, area, configuration, category_id, furniture_status, ownership_type, facing, parking, balcony, status, map_embed_link, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare property statement: ' . $mysqli->error);
            }
        $stmt->bind_param('sssdsisssssssiss', $title, $description, $listing_type, $price, $location, $landmark, $area, $configuration, $category_id, $furniture_status, $ownership_type, $facing, $parking, $balcony, $status, $map_embed_link);
        if (!$stmt->execute()) { 
            throw new Exception('Failed to add property: ' . $mysqli->error); 
        }
        $property_id = $mysqli->insert_id;
        $stmt->close();

        // Insert properties_location
        $pl = $mysqli->prepare("INSERT INTO properties_location (property_id, state_id, district_id, city_id, town_id, pincode) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$pl) {
                throw new Exception('Failed to prepare location statement: ' . $mysqli->error);
            }
            $pl->bind_param('iiiiis', $property_id, $state_id, $district_id, $city_id, $town_id, $pincode);
            if (!$pl->execute()) {
                throw new Exception('Failed to save property location: ' . $mysqli->error); 
            }
            $pl->close();

            // Prefer server-side uploaded files stored in session; fallback to base64 data
            $uploaded_files = $_SESSION['uploaded_images'] ?? [];
            $images_data = $_SESSION['form_data']['images_data'] ?? [];
            
            // Debug: Log image data
            error_log("Image data count: " . count($images_data));
            if (!empty($images_data)) {
                error_log("First image data length: " . strlen($images_data[0] ?? ''));
            }

            $upload_dir = __DIR__ . '/../../uploads/properties/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception('Failed to create uploads directory');
                }
            }
            if (!is_writable($upload_dir)) {
                throw new Exception('Uploads directory is not writable');
            }

            // Case 1: Use files uploaded via AJAX (recommended)
            if (!empty($uploaded_files) && is_array($uploaded_files)) {
                $tmpDir = __DIR__ . '/../../uploads/properties/tmp/';
                foreach ($uploaded_files as $index => $tmpName) {
                    $src = $tmpDir . basename($tmpName);
                    if (!is_file($src)) { continue; }
                    // Build final filename
                    $ext = pathinfo($src, PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = 'property_' . $property_id . '_' . time() . '_' . $index . '.' . $ext;
                    $dest = $upload_dir . $filename;
                    if (@rename($src, $dest)) {
                        $image_url = $filename;
                        $img_stmt = $mysqli->prepare("INSERT INTO property_images (property_id, image_url) VALUES (?, ?)");
                        if ($img_stmt) {
                            $img_stmt->bind_param('is', $property_id, $image_url);
                            $img_stmt->execute();
                            $img_stmt->close();
                        }
                    } else {
                        error_log('Failed to move temp image: ' . $src);
                    }
                }
            }
            // Case 2: Fallback to base64 images posted with form (legacy)
            elseif (!empty($images_data) && is_array($images_data)) {
                foreach ($images_data as $index => $imageDataUrl) {
                    if (empty($imageDataUrl)) continue;
                    if (strpos($imageDataUrl, 'data:') === 0) {
                        $parts = explode(';', $imageDataUrl, 2);
                        if (count($parts) === 2) {
                            $type = $parts[0];
                            $dataPart = $parts[1];
                            $dataParts = explode(',', $dataPart, 2);
                            if (count($dataParts) === 2) {
                                $imageDataUrl = $dataParts[1];
                                $imageData = base64_decode($imageDataUrl);
                                if ($imageData !== false) {
                                    $mime_type = str_replace('data:', '', $type);
                                    $extension = 'jpg';
                                    if (strpos($mime_type, 'png') !== false) $extension = 'png';
                                    else if (strpos($mime_type, 'gif') !== false) $extension = 'gif';
                                    else if (strpos($mime_type, 'webp') !== false) $extension = 'webp';
                                    $filename = 'property_' . $property_id . '_' . time() . '_' . $index . '.' . $extension;
                                    $file_path = $upload_dir . $filename;
                                    if (file_put_contents($file_path, $imageData)) {
                                        $image_url = $filename;
                                        $img_stmt = $mysqli->prepare("INSERT INTO property_images (property_id, image_url) VALUES (?, ?)");
                                        if ($img_stmt) {
                                            $img_stmt->bind_param('is', $property_id, $image_url);
                                            $img_stmt->execute();
                                            $img_stmt->close();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Commit transaction
            $mysqli->commit();

            // Clear session data and redirect
            unset($_SESSION['form_data']);
            $_SESSION['form_data'] = []; // Ensure it's reset to empty array
            // Cleanup temp session image state
            $imageCount = 0;
            if (!empty($uploaded_files)) { $imageCount = count($uploaded_files); }
            elseif (!empty($images_data)) { $imageCount = count($images_data); }
            unset($_SESSION['uploaded_images']);
            unset($_SESSION['uploaded_images_bytes']);
            $_SESSION['success_message'] = 'Property added successfully!' . ($imageCount > 0 ? " ($imageCount images uploaded)" : "");
            echo "<script>sessionStorage.removeItem('prop_images'); window.location.href = 'index.php?success=1';</script>";
        exit();
        
    } catch (Exception $e) {
            // Rollback transaction on error
            $mysqli->rollback();
            $_SESSION['form_error'] = $e->getMessage();
            error_log("Property add error: " . $e->getMessage());
            header('Location: ?step=5&error=1');
            exit();
        } finally {
            $mysqli->close();
        }
    }

            // If AJAX uploaded filenames are sent, persist them in session for server-side save on finish
            if (!empty($_POST['uploaded_filenames']) && is_array($_POST['uploaded_filenames'])) {
                $_SESSION['uploaded_images'] = array_values(array_filter(array_map(function($v){ return basename($v); }, $_POST['uploaded_filenames'])));
            }

            // Determine target step: explicit goto trumps continue
    $goto_step = isset($_POST['goto_step']) ? (int)$_POST['goto_step'] : 0;
    if ($goto_step >= 1 && $goto_step <= $total_steps) {
        // Compute furthest allowed based on saved data
        $maxAllowed = furthest_allowed_step($mysqli);

        // Skip step 3 for Plot category (handle forward/back)
        if ($goto_step === 3 && is_plot_category($mysqli)) {
            if ($submitted_step > 3) { $goto_step = 2; } else { $goto_step = 4; }
        }

        // Only validate current step if going forward, not backward
        if ($goto_step > $submitted_step) {
            $err = '';
            $validCurrent = true;
            if ($submitted_step === 1) { $validCurrent = validate_step_1($err); }
            elseif ($submitted_step === 2) { $validCurrent = validate_step_2($err); }
            elseif ($submitted_step === 3 && !is_plot_category($mysqli)) { $validCurrent = validate_step_3($err); }
            elseif ($submitted_step === 4) { $validCurrent = validate_step_4($err); }

            // Block jumping beyond current if current invalid
            if (!$validCurrent) {
                $_SESSION['form_error'] = $err ?: 'Please complete this step before continuing.';
                header('Location: ?step=' . $submitted_step);
                exit();
            }
        }

        // Clamp to furthest allowed (only for forward navigation)
        if ($goto_step > $submitted_step && $goto_step > $maxAllowed) { 
            $goto_step = $maxAllowed; 
        }

        header("Location: ?step=" . $goto_step);
        exit();
    }

    // Default continue flow
    $next_step = $submitted_step + 1;
    // Validate current step before continuing
    $err = '';
    $validCurrent = true;
    if ($submitted_step === 1) { $validCurrent = validate_step_1($err); }
    elseif ($submitted_step === 2) { $validCurrent = validate_step_2($err); }
    elseif ($submitted_step === 3 && !is_plot_category($mysqli)) { $validCurrent = validate_step_3($err); }
    elseif ($submitted_step === 4) { $validCurrent = validate_step_4($err); }
    if (!$validCurrent) {
        $_SESSION['form_error'] = $err ?: 'Please complete this step before continuing.';
        header('Location: ?step=' . $submitted_step);
        exit();
    }

    // Skip step 3 for Plot category on continue flow
    if ($next_step === 3 && is_plot_category($mysqli)) { $next_step = 4; }

    // Clamp to allowed
    $maxAllowed = furthest_allowed_step($mysqli);
    if ($next_step > $maxAllowed) { $next_step = $maxAllowed; }
    if ($next_step <= $total_steps) {
        header("Location: ?step=" . $next_step);
        exit();
    } else {
        $current_step = $total_steps;
    }
}

// Function to get a value from session data, to pre-fill fields
function get_data($field) {
    if (!isset($_SESSION['form_data']) || !is_array($_SESSION['form_data'])) {
        return '';
    }
    return $_SESSION['form_data'][$field] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property</title>
    <link href="../../assets/css/loader.css" rel="stylesheet">
<style>
        /* General Styling */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

        :root {
            --primary-color: #ef4444; /* Red theme */
            --border-color: #E0E0E0;
            --text-color: #333;
            --label-color: #555;
            --bg-color: #F4F7FA;
            --card-bg-color: #FFFFFF;
            --active-step-color: #ef4444;
            --inactive-step-color: #D1D5DB;
            --completed-step-color: #ef4444;
        }

        body { font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif; background-color: var(--bg-color); color: var(--text-color); margin:0; }
        /* Background iframe with blur effect */
        .background-iframe { 
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
            border: none; 
            z-index: -2; 
        }
        .blur-overlay { 
            position: fixed; 
            top: 0; 
            left: 0; 
        width: 100%;
            height: 100%; 
            background: rgba(0,0,0,.4); 
            backdrop-filter: blur(3px); 
            z-index: -1; 
        }
        /* Modal overlay + container to mimic dialog */
        .modal-overlay { 
            position: fixed; 
            inset: 0; 
            background: transparent; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            padding:20px; 
            z-index: 1000;
        }
        .modal-container { 
            background: var(--card-bg-color); 
            border-radius:16px; 
            box-shadow:0 20px 60px rgba(0,0,0,.3); 
            width:100%; 
            max-width:760px; 
            max-height:90vh; 
            overflow:auto; 
            position: relative;
            z-index: 1001;
            backdrop-filter: blur(0px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* Card Layout */
        .order-card { padding: 2rem; box-sizing: border-box; display:flex; flex-direction:column; }

        /* Step content wrapper (height set dynamically by JS) */
        .step-content { display: block; }
 
        /* Header */
        .card-header {
        display: flex;
        justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
    }

        .card-header h1 {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
    }

        .close-btn {
        font-size: 1.5rem;
            color: #999;
        cursor: pointer;
            border: none;
            background: none;
        }

        /* Progress Bar */
        .progressbar {
            display: flex;
            justify-content: space-between;
            list-style: none;
            counter-reset: step;
        padding: 0;
            margin: 20px 0;
            width: 100%;
        }

        .progressbar li {
            position: relative;
            flex: 1;
            text-align: center;
            counter-increment: step;
            font-size: 14px;
            color: #999;
            cursor: pointer;
        }

        .progressbar li .step-number {
            width: 32px;
            height: 32px;
            line-height: 32px;
            border: 2px solid #e74c3c; /* red border */
            display: block;
            text-align: center;
            margin: 0 auto 10px auto;
            border-radius: 50%;
            background-color: #fff;
            font-weight: bold;
            color: #e74c3c;
            transition: all 0.3s ease;
        }

        .progressbar li .step-label {
            display: block;
            font-size: 14px;
            color: #999;
        }

        .progressbar li::after {
            content: '';
            position: absolute;
            width: calc(100% - 32px);
            height: 2px;
            background-color: #e74c3c;
            top: 16px;
            left: calc(50% + 16px);
            z-index: -1;
            transition: background-color 0.3s ease;
        }

        .progressbar li:last-child::after {
            content: none;
        }

        /* Active state */
        .progressbar li.active {
            color: #e74c3c;
        }

        .progressbar li.active .step-number {
            border-color: #e74c3c;
            background-color: #e74c3c;
            color: #fff;
        }

        .progressbar li.active .step-label {
            color: #e74c3c;
        }

        .progressbar li.active ~ li {
            color: #999;
        }

        .progressbar li.active ~ li .step-number {
            border-color: #ccc;
            background-color: #fff;
            color: #ccc;
        }

        .progressbar li.active ~ li .step-label {
            color: #999;
        }

        .progressbar li.active ~ li::after {
            background-color: #ccc;
        }

        /* Completed state */
        .progressbar li.completed {
            color: #e74c3c;
        }

        .progressbar li.completed .step-number {
            border-color: #e74c3c;
            background-color: #e74c3c;
        color: #fff;
            content: "✓";
        }

        .progressbar li.completed .step-number::before {
            content: "✓";
        }

        .progressbar li.completed .step-label {
            color: #e74c3c;
        }

        .progressbar li.completed::after {
            background-color: #e74c3c;
        }


        /* Form Styling */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .form-group {
        display: flex;
            flex-direction: column;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--label-color);
            margin-bottom: 0.5rem;
        }
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        /* Hide number spinners for Price field */
        #price::-webkit-outer-spin-button,
        #price::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        #price { -moz-appearance: textfield; appearance: textfield; }
        /* Hide number spinners for Area field */
        #area::-webkit-outer-spin-button,
        #area::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        #area { -moz-appearance: textfield; appearance: textfield; }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0, 135, 90, 0.2);
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        /* Special multi-input groups */
        .multi-input-group {
        display: flex;
        align-items: center;
            gap: 0.5rem;
        }
        .multi-input-group input {
            text-align: center;
        }
        .multi-input-group select {
            flex-shrink: 0;
            width: auto;
        }
        .calculate-btn {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg-color);
            border-radius: 8px;
            cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        }

        /* Footer / Navigation */
        .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
            margin-top: auto; /* stick footer to bottom when card has a baseline height */
            gap: 10px; /* add a little space between children */
            flex-wrap: wrap; /* avoid overlap on smaller screens */
            row-gap: 10px;
            padding-top: 12px; /* breathing room above buttons */
        }
        /* Ensure buttons never collide visually */
        .card-footer .btn { margin: 6px; }
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
        border-radius: 8px;
            font-size: 1rem;
        font-weight: 500;
            cursor: pointer;
            text-decoration: none;
        display: inline-block;
        text-align: center;
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-primary:hover { background-color: #b91c1c; }
        .btn-secondary {
            background: #6b7280; 
            color: #ffffff; 
            border: 1px solid #6b7280; 
            border-radius: 8px;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.15);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-secondary:hover { 
            background: #4b5563; 
            border-color: #4b5563;
            transform: translateY(-1px);
            box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.2);
        }

        /* Image Drop Zone Styles (from blog design) */
        .image-drop {
            border: 1px dashed var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s ease;
        }
        .image-drop:hover {
            background: #f0f0f0;
            border-color: var(--primary-color);
        }
        .image-drop.dragover {
            background: #e8f4fd;
            border-color: var(--primary-color);
            border-style: solid;
        }
        .preview img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .progressbar li {
                font-size: 12px;
            }

            .progressbar li::before {
                width: 28px;
                height: 28px;
                line-height: 28px;
                font-size: 12px;
            }
        }

        @media (max-width: 600px) {
            .progressbar {
                flex-direction: column;
                gap: 1rem;
            }

            .progressbar li::after {
                display: none;
            }

            .progressbar li {
                width: 100%;
                text-align: left;
                padding-left: 20px;
            }

            .progressbar li::before {
                position: absolute;
                left: 0;
                top: 0;
                margin: 0;
        }
    }
</style>
</head>
<body>
    <!-- Background iframe showing properties index page -->
    <div class="page-loader-overlay" id="pageLoader"><div class="custom-loader"></div></div>
    <iframe src="index.php" class="background-iframe" title="Properties Background"></iframe>
    
    <!-- Blur overlay for subtle background effect -->
    <div class="blur-overlay"></div>

    <div class="modal-overlay">
    <div class="modal-container">
        <div class="order-card">
        <header class="card-header">
            <h1>Add Property</h1>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" onclick="startNewProperty()" class="btn btn-secondary" style="padding: 8px 16px; font-size: 14px;">Clear From</button>
                <button class="close-btn" aria-label="Close">&times;</button>
        </div>
        </header>

        <?php if (isset($_SESSION['form_error'])): ?>
            <div style="background-color: #fee; border: 1px solid #fcc; color: #c33; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <strong>Error:</strong> <?php echo htmlspecialchars($_SESSION['form_error']); unset($_SESSION['form_error']); ?>
                </div>
            <?php endif; ?>

        <ul class="progressbar">
            <?php for ($i = 1; $i <= $total_steps; $i++): 
                $step_class = '';
                if ($i == $current_step) {
                    $step_class = 'active';
                } elseif ($i < $current_step) {
                    $step_class = 'completed';
                }
                $step_labels = ['Basic Information', 'Location Details', 'Property Details', 'Images', 'Preview'];
            ?>
            <li class="<?php echo $step_class; ?>" title="Click to go to <?php echo $step_labels[$i-1]; ?>" onclick="goToStep(<?php echo $i; ?>)">
                <span class="step-number"><?php echo $i; ?></span>
                <span class="step-label"><?php echo $step_labels[$i-1]; ?></span>
            </li>
            <?php endfor; ?>
        </ul>

        <form method="POST" enctype="multipart/form-data" id="wizardForm">
            <input type="hidden" name="step" value="<?php echo $current_step; ?>">
            <div class="step-content">

            <?php if ($current_step == 1): ?>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="title">Property Title</label>
                    <input type="text" id="title" name="title" value="<?php echo get_data('title'); ?>" placeholder="Beautiful 3BHK Apartment in Downtown" required>
                                    </div>
                <div class="form-group">
                    <label for="listing_type">Listing Type</label>
                    <select id="listing_type" name="listing_type" required>
                        <option value="Buy" <?php echo get_data('listing_type') == 'Buy' ? 'selected' : ''; ?>>Buy</option>
                        <option value="Rent" <?php echo get_data('listing_type') == 'Rent' ? 'selected' : ''; ?>>Rent</option>
                        <option value="PG/Co-living" <?php echo get_data('listing_type') == 'PG/Co-living' ? 'selected' : ''; ?>>PG/Co-living</option>
                                        </select>
                                    </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="Available" <?php echo get_data('status') == 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Sold" <?php echo get_data('status') == 'Sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="Rented" <?php echo get_data('status') == 'Rented' ? 'selected' : ''; ?>>Rented</option>
                    </select>
                                    </div>
                <div class="form-group">
                    <label for="price">Price (₹)</label>
                    <input type="number" step="0.01" id="price" name="price" value="<?php echo get_data('price'); ?>" placeholder="0.00" required>
                                        </div>
                <div class="form-group">
                    <label for="area">Area (sq ft)</label>
                    <input type="number" step="0.01" id="area" name="area" value="<?php echo get_data('area'); ?>" placeholder="0" required>
                                    </div>
            
                <div class="form-group">
                    <label for="landmark">Landmark</label>
                    <input type="text" id="landmark" name="landmark" value="<?php echo get_data('landmark'); ?>" placeholder="Nearby landmark or reference point" required>
                                    </div>
                <div class="form-group">
                    <label for="configuration">Configuration</label>
                    <input type="text" id="configuration" name="configuration" value="<?php echo get_data('configuration'); ?>" placeholder="e.g., 2BHK, 3BHK" required>
                                    </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" <?php echo get_data('category_id') == (string)$cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                                        </select>
                                    </div>
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Describe the property features, amenities, and unique selling points..." required><?php echo get_data('description'); ?></textarea>
                                    </div>
                <div class="form-group full-width">
                    <label for="map_embed_link">Map Embed Link</label>
                    <input type="url" id="map_embed_link" name="map_embed_link" value="<?php echo get_data('map_embed_link'); ?>" placeholder="https://www.google.com/maps/embed?pb=..." required>
                        </div>
                            </div>
            <?php elseif ($current_step == 2): ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="state_id">State</label>
                    <select id="state_id" name="state_id" required>
                                            <option value="">Select State</option>
                        <?php foreach($states as $s): ?>
                            <option value="<?php echo (int)$s['id']; ?>" <?php echo get_data('state_id') == (string)$s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                                            <option value="__add__">+ Add State</option>
                                        </select>
                    <div id="inlineAddState" style="display:none; margin-top:8px;">
                        <div style="display:flex; gap:6px;">
                            <input type="text" id="inlineStateName" placeholder="New state name" style="flex:1; padding:8px; border:1px solid #E0E0E0; border-radius:8px;">
                            <button type="button" id="inlineStateSave" class="btn btn-primary">Save</button>
                            <button type="button" id="inlineStateCancel" class="btn btn-secondary">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                <div class="form-group">
                    <label for="district_id">District</label>
                    <select id="district_id" name="district_id" required>
                                            <option value="">Select District</option>
                                            <option value="__add__">+ Add District</option>
                                        </select>
                    <div id="inlineAddDistrict" style="display:none; margin-top:8px;">
                        <div style="display:flex; gap:6px;">
                            <input type="text" id="inlineDistrictName" placeholder="New district name" style="flex:1; padding:8px; border:1px solid #E0E0E0; border-radius:8px;">
                            <button type="button" id="inlineDistrictSave" class="btn btn-primary">Save</button>
                            <button type="button" id="inlineDistrictCancel" class="btn btn-secondary">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                <div class="form-group">
                    <label for="city_id">City</label>
                    <select id="city_id" name="city_id" required>
                                            <option value="">Select City</option>
                                            <option value="__add__">+ Add City</option>
                                        </select>
                    <div id="inlineAddCity" style="display:none; margin-top:8px;">
                        <div style="display:flex; gap:6px;">
                            <input type="text" id="inlineCityName" placeholder="New city name" style="flex:1; padding:8px; border:1px solid #E0E0E0; border-radius:8px;">
                            <button type="button" id="inlineCitySave" class="btn btn-primary">Save</button>
                            <button type="button" id="inlineCityCancel" class="btn btn-secondary">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                <div class="form-group">
                    <label for="town_id">Town</label>
                    <select id="town_id" name="town_id" required>
                                            <option value="">Select Town</option>
                                            <option value="__add__">+ Add Town</option>
                                        </select>
                    <div id="inlineAddTown" style="display:none; margin-top:8px;">
                        <div style="display:flex; gap:6px;">
                            <input type="text" id="inlineTownName" placeholder="New town name" style="flex:1; padding:8px; border:1px solid #E0E0E0; border-radius:8px;">
                            <button type="button" id="inlineTownSave" class="btn btn-primary">Save</button>
                            <button type="button" id="inlineTownCancel" class="btn btn-secondary">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                <div class="form-group">
                    <label for="pincode">Pincode</label>
                    <input type="text" id="pincode" name="pincode" value="<?php echo get_data('pincode'); ?>" placeholder="560001" required>
                                    </div>
                        </div>
            <?php elseif ($current_step == 3): ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="furniture_status">Furniture Status</label>
                    <select id="furniture_status" name="furniture_status" required>
                                            <option value="">Select</option>
                        <option value="Furnished" <?php echo get_data('furniture_status') == 'Furnished' ? 'selected' : ''; ?>>Furnished</option>
                        <option value="Semi-Furnished" <?php echo get_data('furniture_status') == 'Semi-Furnished' ? 'selected' : ''; ?>>Semi-Furnished</option>
                        <option value="Unfurnished" <?php echo get_data('furniture_status') == 'Unfurnished' ? 'selected' : ''; ?>>Unfurnished</option>
                                        </select>
                                    </div>
                <div class="form-group">
                    <label for="ownership_type">Ownership Type</label>
                    <select id="ownership_type" name="ownership_type" required>
                                            <option value="">Select</option>
                        <option value="Freehold" <?php echo get_data('ownership_type') == 'Freehold' ? 'selected' : ''; ?>>Freehold</option>
                        <option value="Leasehold" <?php echo get_data('ownership_type') == 'Leasehold' ? 'selected' : ''; ?>>Leasehold</option>
                                        </select>
                                    </div>
                <div class="form-group">
                    <label for="facing">Facing</label>
                    <select id="facing" name="facing" required>
                                            <option value="">Select</option>
                        <option value="East" <?php echo get_data('facing') == 'East' ? 'selected' : ''; ?>>East</option>
                        <option value="West" <?php echo get_data('facing') == 'West' ? 'selected' : ''; ?>>West</option>
                        <option value="North" <?php echo get_data('facing') == 'North' ? 'selected' : ''; ?>>North</option>
                        <option value="South" <?php echo get_data('facing') == 'South' ? 'selected' : ''; ?>>South</option>
                                        </select>
                                    </div>
                <div class="form-group">
                    <label for="parking">Parking</label>
                    <select id="parking" name="parking" required>
                                            <option value="">Select</option>
                        <option value="Yes" <?php echo get_data('parking') == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="No" <?php echo get_data('parking') == 'No' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                <div class="form-group">
                    <label for="balcony">Number of Balconies</label>
                    <input type="number" id="balcony" name="balcony" value="<?php echo get_data('balcony'); ?>" min="0" placeholder="0" required>
                                    </div>
                        </div>
            <?php elseif ($current_step == 4): ?>
            <div>
                <div class="form-group full-width">
                    <label for="images">Property Images</label>
                    <div class="image-drop" id="drop">
                        <div style="margin-bottom:8px;font-weight:500;">Drop images here or click to browse</div>
                        <input type="file" id="images" name="images[]" accept="image/*" multiple style="display:none;">
                        <div style="margin-top:8px;">
                            <button type="button" class="btn btn-secondary" id="chooseBtn">Choose Images</button>
                        </div>
                        <div class="text-muted small" style="margin-top:6px;">Supported: JPG, PNG, GIF, WebP. Max 10MB each. You can select multiple files at once.</div>
                    </div>
                    <div id="imageSizeWarning" style="display:none; margin-top:8px; color:#b91c1c; font-weight:500;"></div>
                    <div id="imageTotalWarning" style="display:none; margin-top:8px; color:#b91c1c; font-weight:500;"></div>
                    <div id="uploadLoader" style="display:none; margin-top:8px;">
                        <span style="display:inline-block; width:18px; height:18px; border:2px solid #eee; border-top-color:#ef4444; border-radius:50%; animation:spin .8s linear infinite; vertical-align:middle;"></span>
                        <span style="margin-left:8px; color:#555;">Uploading images...</span>
                    </div>
                    <div id="liveImagesPreview" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;"></div>
                    <div style="margin-top:8px;">
                        <button type="button" id="clearAllImages" class="btn btn-secondary" style="display:none;">Clear All Images</button>
                    </div>
                    <input type="hidden" id="images_data" name="images_data[]">
                </div>
            </div>
            <?php elseif ($current_step == 5): ?>
            <div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Title</label>
                        <div><?php echo htmlspecialchars(get_data('title')); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Listing Type</label>
                        <div><?php echo htmlspecialchars(get_data('listing_type')); ?></div>
                </div>
                    <div class="form-group">
                        <label>Status</label>
                        <div><?php echo htmlspecialchars(get_data('status')); ?></div>
                                </div>
                    <div class="form-group">
                        <label>Price (₹)</label>
                        <div><?php echo htmlspecialchars(get_data('price')); ?></div>
                                        </div>
                    <div class="form-group">
                        <label>Area (sq ft)</label>
                        <div><?php echo htmlspecialchars(get_data('area')); ?></div>
                                        </div>
                    <div class="form-group">
                        <label>Location</label>
                        <div><?php echo htmlspecialchars(get_data('location')); ?></div>
                                        </div>
                    <div class="form-group">
                        <label>Landmark</label>
                        <div><?php echo htmlspecialchars(get_data('landmark')); ?></div>
                                        </div>
                    <div class="form-group">
                        <label>Configuration</label>
                        <div><?php echo htmlspecialchars(get_data('configuration')); ?></div>
                                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <div>
                            <?php 
                                $catName = '';
                                $cid = get_data('category_id');
                                if ($cid) { $r = $mysqli->query("SELECT name FROM categories WHERE id=".(int)$cid); if($r && $row=$r->fetch_assoc()){ $catName=$row['name']; } }
                                echo htmlspecialchars($catName);
                            ?>
                                </div>
                            </div>
                    <div class="form-group full-width">
                        <label>Description</label>
                        <div><?php echo nl2br(htmlspecialchars(get_data('description'))); ?></div>
                            </div>
                    <div class="form-group">
                        <label>State</label>
                        <div>
                            <?php 
                                $stateName = '';
                                $sid = get_data('state_id');
                                if ($sid) { $r = $mysqli->query("SELECT name FROM states WHERE id=".(int)$sid); if($r && $row=$r->fetch_assoc()){ $stateName=$row['name']; } }
                                echo htmlspecialchars($stateName);
                            ?>
                        </div>
                        </div>
                    <div class="form-group">
                        <label>District</label>
                        <div>
                            <?php 
                                $districtName = '';
                                $did = get_data('district_id');
                                if ($did) { $r = $mysqli->query("SELECT name FROM districts WHERE id=".(int)$did); if($r && $row=$r->fetch_assoc()){ $districtName=$row['name']; } }
                                echo htmlspecialchars($districtName);
                            ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <div>
                            <?php 
                                $cityName = '';
                                $cidLoc = get_data('city_id');
                                if ($cidLoc) { $r = $mysqli->query("SELECT name FROM cities WHERE id=".(int)$cidLoc); if($r && $row=$r->fetch_assoc()){ $cityName=$row['name']; } }
                                echo htmlspecialchars($cityName);
                            ?>
                        </div>
                </div>
                    <div class="form-group">
                        <label>Town</label>
                        <div>
                            <?php 
                                $townName = '';
                                $tid = get_data('town_id');
                                if ($tid) { $r = $mysqli->query("SELECT name FROM towns WHERE id=".(int)$tid); if($r && $row=$r->fetch_assoc()){ $townName=$row['name']; } }
                                echo htmlspecialchars($townName);
                            ?>
                        </div>
        </div>
                    <div class="form-group">
                        <label>Pincode</label>
                        <div><?php echo htmlspecialchars(get_data('pincode')); ?></div>
                </div>
                    <div class="form-group">
                        <label>Furniture</label>
                        <div><?php echo htmlspecialchars(get_data('furniture_status')); ?></div>
        </div>
                    <div class="form-group">
                        <label>Ownership</label>
                        <div><?php echo htmlspecialchars(get_data('ownership_type')); ?></div>
    </div>
                    <div class="form-group">
                        <label>Facing</label>
                        <div><?php echo htmlspecialchars(get_data('facing')); ?></div>
</div>
                    <div class="form-group">
                        <label>Parking</label>
                        <div><?php echo htmlspecialchars(get_data('parking')); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Balcony</label>
                        <div><?php echo htmlspecialchars(get_data('balcony')); ?></div>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label>Map</label>
                    <div>
                        <?php $map = get_data('map_embed_link'); if ($map): ?>
                            <iframe src="<?php echo htmlspecialchars($map); ?>" width="100%" height="260" style="border:0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        <?php else: ?>
                            <em>No map provided</em>
                        <?php endif; ?>
    </div>
</div>

                <div class="form-group full-width">
                    <label>Images</label>
                    <div id="previewImagesGrid" style="display:flex; gap:8px; flex-wrap:wrap;"></div>
                </div>

                
            </div>
            <?php endif; ?>
            
            <footer class="card-footer">
                <div>
                    <?php if ($current_step > 1 && $current_step < $total_steps): ?>
                        <button type="submit" name="goto_step" value="<?php echo $current_step - 1; ?>" class="btn btn-secondary">&leftarrow; Back</button>
                    <?php elseif ($current_step == $total_steps): ?>
                        <button type="submit" name="goto_step" value="1" class="btn btn-secondary">Edit</button>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                </div>

                <?php if ($current_step == $total_steps): ?>
                    <button type="submit" class="btn btn-primary" name="final_submit" value="1" onclick="submitWithImages()">Finish</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary" name="goto_step" value="<?php echo min($total_steps, $current_step + 1); ?>">Continue &rightarrow;</button>
                <?php endif; ?>
            </footer>
        </form>
        </div>
      </div>
    </div>

</body>
</html>
<script>
// Dependent dropdowns using simple endpoints (expects hierarchy.php like original)
const stateSel = document.getElementById('state_id');
const districtSel = document.getElementById('district_id');
const citySel = document.getElementById('city_id');
const townSel = document.getElementById('town_id');

async function fetchJSON(url){ try { const r = await fetch(url); if(!r.ok) return []; return await r.json(); } catch { return []; } }

stateSel?.addEventListener('change', async function(){
  const sid = this.value;
  districtSel.innerHTML = '<option value="">Select District</option>';
  citySel.innerHTML = '<option value="">Select City</option>';
  townSel.innerHTML = '<option value="">Select Town</option>';
  if (!sid) return;
  const d = await fetchJSON('hierarchy.php?action=fetch&level=districts&state_id=' + encodeURIComponent(sid));
  d.forEach(it=>{ const o=document.createElement('option'); o.value=it.id; o.textContent=it.name; if('<?php echo get_data('district_id'); ?>'===String(it.id)) o.selected=true; districtSel.appendChild(o); });
  // re-add inline add option
  const addOptD = document.createElement('option'); addOptD.value='__add__'; addOptD.textContent='+ Add District'; districtSel.appendChild(addOptD);
});

districtSel?.addEventListener('change', async function(){
  const did = this.value;
  citySel.innerHTML = '<option value="">Select City</option>';
  townSel.innerHTML = '<option value="">Select Town</option>';
  if (!did) return;
  const c = await fetchJSON('hierarchy.php?action=fetch&level=cities&district_id=' + encodeURIComponent(did));
  c.forEach(it=>{ const o=document.createElement('option'); o.value=it.id; o.textContent=it.name; if('<?php echo get_data('city_id'); ?>'===String(it.id)) o.selected=true; citySel.appendChild(o); });
  const addOptC = document.createElement('option'); addOptC.value='__add__'; addOptC.textContent='+ Add City'; citySel.appendChild(addOptC);
});

citySel?.addEventListener('change', async function(){
  const cid = this.value;
  townSel.innerHTML = '<option value="">Select Town</option>';
  if (!cid) return;
  const t = await fetchJSON('hierarchy.php?action=fetch&level=towns&city_id=' + encodeURIComponent(cid));
  t.forEach(it=>{ const o=document.createElement('option'); o.value=it.id; o.textContent=it.name; if('<?php echo get_data('town_id'); ?>'===String(it.id)) o.selected=true; townSel.appendChild(o); });
  const addOptT = document.createElement('option'); addOptT.value='__add__'; addOptT.textContent='+ Add Town'; townSel.appendChild(addOptT);
});

// On load, trigger chain if state/district/city are preset in session
window.addEventListener('DOMContentLoaded', async ()=>{
  if (stateSel && stateSel.value){ stateSel.dispatchEvent(new Event('change')); }
  // Defer district/city triggers slightly to allow previous population
  setTimeout(()=>{ if (districtSel && districtSel.value){ districtSel.dispatchEvent(new Event('change')); } }, 200);
  setTimeout(()=>{ if (citySel && citySel.value){ citySel.dispatchEvent(new Event('change')); } }, 400);
});

// Inline add (no extra dialog): show inline inputs when selecting __add__
function show(el){ el && (el.style.display='block'); }
function hide(el){ el && (el.style.display='none'); }

stateSel?.addEventListener('change', function(){
  if (this.value === '__add__'){ show(document.getElementById('inlineAddState')); this.value=''; document.getElementById('inlineStateName').focus(); }
  else { hide(document.getElementById('inlineAddState')); }
});
districtSel?.addEventListener('change', function(){
  if (this.value === '__add__'){ show(document.getElementById('inlineAddDistrict')); this.value=''; document.getElementById('inlineDistrictName').focus(); }
  else { hide(document.getElementById('inlineAddDistrict')); }
});
citySel?.addEventListener('change', function(){
  if (this.value === '__add__'){ show(document.getElementById('inlineAddCity')); this.value=''; document.getElementById('inlineCityName').focus(); }
  else { hide(document.getElementById('inlineAddCity')); }
});
townSel?.addEventListener('change', function(){
  if (this.value === '__add__'){ show(document.getElementById('inlineAddTown')); this.value=''; document.getElementById('inlineTownName').focus(); }
  else { hide(document.getElementById('inlineAddTown')); }
});

async function create(scope, payload){
            const form = new FormData();
  form.append('action','create');
            form.append('scope', scope);
  Object.keys(payload).forEach(k=> form.append(k, payload[k]));
            const r = await fetch('hierarchy.php', { method:'POST', body: form });
  try { return await r.json(); } catch { return null; }
}

document.getElementById('inlineStateSave')?.addEventListener('click', async ()=>{
  const name = document.getElementById('inlineStateName').value.trim(); if (!name) return;
  const res = await create('state', { name }); if (!res || !res.id) return;
  const opt = document.createElement('option'); opt.value = res.id; opt.textContent = res.name;
  stateSel.insertBefore(opt, stateSel.querySelector('option[value="__add__"]'));
  stateSel.value = res.id; hide(document.getElementById('inlineAddState'));
  stateSel.dispatchEvent(new Event('change'));
});
document.getElementById('inlineStateCancel')?.addEventListener('click', ()=>{ hide(document.getElementById('inlineAddState')); });

document.getElementById('inlineDistrictSave')?.addEventListener('click', async ()=>{
  if (!stateSel.value){ alert('Select State first'); return; }
  const name = document.getElementById('inlineDistrictName').value.trim(); if (!name) return;
  const res = await create('district', { name, state_id: stateSel.value }); if (!res || !res.id) return;
  const opt = document.createElement('option'); opt.value = res.id; opt.textContent = res.name;
  districtSel.insertBefore(opt, districtSel.querySelector('option[value="__add__"]'));
  districtSel.value = res.id; hide(document.getElementById('inlineAddDistrict'));
  districtSel.dispatchEvent(new Event('change'));
});
document.getElementById('inlineDistrictCancel')?.addEventListener('click', ()=>{ hide(document.getElementById('inlineAddDistrict')); });

document.getElementById('inlineCitySave')?.addEventListener('click', async ()=>{
  if (!districtSel.value){ alert('Select District first'); return; }
  const name = document.getElementById('inlineCityName').value.trim(); if (!name) return;
  const res = await create('city', { name, district_id: districtSel.value }); if (!res || !res.id) return;
  const opt = document.createElement('option'); opt.value = res.id; opt.textContent = res.name;
  citySel.insertBefore(opt, citySel.querySelector('option[value="__add__"]'));
  citySel.value = res.id; hide(document.getElementById('inlineAddCity'));
  citySel.dispatchEvent(new Event('change'));
});
document.getElementById('inlineCityCancel')?.addEventListener('click', ()=>{ hide(document.getElementById('inlineAddCity')); });

document.getElementById('inlineTownSave')?.addEventListener('click', async ()=>{
  if (!citySel.value){ alert('Select City first'); return; }
  const name = document.getElementById('inlineTownName').value.trim(); if (!name) return;
  const res = await create('town', { name, city_id: citySel.value }); if (!res || !res.id) return;
  const opt = document.createElement('option'); opt.value = res.id; opt.textContent = res.name;
  townSel.insertBefore(opt, townSel.querySelector('option[value="__add__"]'));
  townSel.value = res.id; hide(document.getElementById('inlineAddTown'));
});
document.getElementById('inlineTownCancel')?.addEventListener('click', ()=>{ hide(document.getElementById('inlineAddTown')); });

// Client-side image preview and carry-over to preview step
const imagesInput = document.getElementById('images');
const liveImagesPreview = document.getElementById('liveImagesPreview');
const previewImagesGrid = document.getElementById('previewImagesGrid');
const formEl = document.getElementById('wizardForm');
const clearAllImagesBtn = document.getElementById('clearAllImages');
const drop = document.getElementById('drop');
const chooseBtn = document.getElementById('chooseBtn');
let selectedImageDataURLs = [];
            const imageSizeWarning = document.getElementById('imageSizeWarning');
const imageTotalWarning = document.getElementById('imageTotalWarning');
const uploadLoader = document.getElementById('uploadLoader');
const pageLoader = document.getElementById('pageLoader');
let pageLoadingCount = 0;
let uploadedServerFiles = []; // filenames returned by server
let uploadingCount = 0;

async function getLimits(){
  try {
    const r = await fetch('upload_property_images.php?action=limits');
    if (!r.ok) return null; return await r.json();
  } catch { return null; }
}

function setLoader(on){ if (uploadLoader) uploadLoader.style.display = on ? 'block' : 'none'; }
function showPageLoader(){ if (!pageLoader) return; pageLoadingCount++; pageLoader.classList.add('is-visible'); }
function hidePageLoader(){ if (!pageLoader) return; pageLoadingCount = Math.max(0, pageLoadingCount-1); if (pageLoadingCount === 0) pageLoader.classList.remove('is-visible'); }

async function uploadFileToServer(file){
  const form = new FormData();
  form.append('action','upload');
  form.append('file', file);
  const res = await fetch('upload_property_images.php', { method:'POST', body: form, headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' } });
  const data = await res.json().catch(()=>null);
  if (!res.ok || !data || !data.ok) {
    const msg = (data && data.error) ? data.error : 'Upload failed';
    throw new Error(msg);
  }
  return data;
}

function renderThumb(container, dataURL, showRemoveBtn = false){
  const wrap = document.createElement('div');
  wrap.style.width = '90px'; wrap.style.height = '90px'; wrap.style.border = '1px solid #E0E0E0'; wrap.style.borderRadius = '8px'; wrap.style.overflow = 'hidden'; wrap.style.display='flex'; wrap.style.alignItems='center'; wrap.style.justifyContent='center'; wrap.style.position = 'relative';
  const img = document.createElement('img'); img.src = dataURL; img.style.maxWidth='100%'; img.style.maxHeight='100%'; img.style.objectFit='cover';
  wrap.appendChild(img);
  
  if (showRemoveBtn) {
    const removeBtn = document.createElement('button');
    removeBtn.innerHTML = '×';
    removeBtn.style.position = 'absolute'; removeBtn.style.top = '2px'; removeBtn.style.right = '2px';
    removeBtn.style.width = '20px'; removeBtn.style.height = '20px'; removeBtn.style.borderRadius = '50%';
    removeBtn.style.background = '#ef4444'; removeBtn.style.color = 'white'; removeBtn.style.border = 'none';
    removeBtn.style.cursor = 'pointer'; removeBtn.style.fontSize = '12px'; removeBtn.style.fontWeight = 'bold';
    removeBtn.addEventListener('click', function(e) {
      e.stopPropagation(); // Prevent event bubbling to avoid closing the modal
      const index = Array.from(container.children).indexOf(wrap);
      if (index > -1) {
        selectedImageDataURLs.splice(index, 1);
        if (uploadedServerFiles && uploadedServerFiles.length > index) {
          uploadedServerFiles.splice(index, 1);
        }
        wrap.remove();
        updateClearButton();
        try { sessionStorage.setItem('prop_images', JSON.stringify(selectedImageDataURLs)); } catch {}
        try { sessionStorage.setItem('prop_server_files', JSON.stringify(uploadedServerFiles)); } catch {}
      }
    });
    wrap.appendChild(removeBtn);
  }
  
  // Prevent clicks on the image preview from bubbling up
  wrap.addEventListener('click', function(e) {
    e.stopPropagation();
  });
  
  container.appendChild(wrap);
  // enable sorting after adding
  enablePreviewSorting(container);
}

function updateClearButton() {
  if (clearAllImagesBtn) {
    clearAllImagesBtn.style.display = selectedImageDataURLs.length > 0 ? 'block' : 'none';
  }
}

// Drag-and-drop sorting for selected previews (like edit.php existing images)
function enablePreviewSorting(container) {
  if (!container) return;
  let dragSrc = null;

  function handleDragStart(e){
    dragSrc = this; this.style.opacity = '0.7';
    try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', ''); } catch {}
  }
  function handleDragOver(e){ if (e.preventDefault) e.preventDefault(); return false; }
  function handleDragEnter(){ this.classList.add('over'); }
  function handleDragLeave(){ this.classList.remove('over'); }
  function handleDrop(e){
    if (e.stopPropagation) e.stopPropagation();
    if (dragSrc && dragSrc !== this) {
      const nodes = Array.from(container.children);
      const srcIndex = nodes.indexOf(dragSrc);
      const tgtIndex = nodes.indexOf(this);
      if (srcIndex > -1 && tgtIndex > -1) {
        if (srcIndex < tgtIndex) { container.insertBefore(dragSrc, this.nextSibling); }
        else { container.insertBefore(dragSrc, this); }
        syncSelectedOrder(container);
      }
    }
    return false;
  }
  function handleDragEnd(){ this.style.opacity = '1'; Array.from(container.children).forEach(el=> el.classList.remove('over')); }

  Array.from(container.children).forEach(el => {
    el.setAttribute('draggable', 'true');
    el.removeEventListener('dragstart', handleDragStart, false);
    el.removeEventListener('dragenter', handleDragEnter, false);
    el.removeEventListener('dragover', handleDragOver, false);
    el.removeEventListener('dragleave', handleDragLeave, false);
    el.removeEventListener('drop', handleDrop, false);
    el.removeEventListener('dragend', handleDragEnd, false);
    el.addEventListener('dragstart', handleDragStart, false);
    el.addEventListener('dragenter', handleDragEnter, false);
    el.addEventListener('dragover', handleDragOver, false);
    el.addEventListener('dragleave', handleDragLeave, false);
    el.addEventListener('drop', handleDrop, false);
    el.addEventListener('dragend', handleDragEnd, false);
  });
}

function syncSelectedOrder(container){
  // Update selectedImageDataURLs to match current DOM order
  const nodes = Array.from(container.children);
  const newUrls = nodes.map(node => {
    const img = node.querySelector('img');
    return img ? img.src : '';
  }).filter(Boolean);
  // Reorder server files in the same way if both arrays are same length
  if (Array.isArray(uploadedServerFiles) && uploadedServerFiles.length === selectedImageDataURLs.length) {
    const oldToNewIndex = new Map();
    selectedImageDataURLs.forEach((url, idx) => { oldToNewIndex.set(url, idx); });
    const reorderedServer = [];
    newUrls.forEach(url => {
      const oldIdx = oldToNewIndex.get(url);
      if (typeof oldIdx === 'number') { reorderedServer.push(uploadedServerFiles[oldIdx]); }
    });
    uploadedServerFiles = reorderedServer;
    try { sessionStorage.setItem('prop_server_files', JSON.stringify(uploadedServerFiles)); } catch {}
  }
  selectedImageDataURLs = newUrls;
  try { sessionStorage.setItem('prop_images', JSON.stringify(selectedImageDataURLs)); } catch {}
}

// Drag and drop functionality (from blog design)
drop?.addEventListener('dragover', (e) => {
  e.preventDefault();
  drop.classList.add('dragover');
});

drop?.addEventListener('dragleave', (e) => {
  e.preventDefault();
  drop.classList.remove('dragover');
});

drop?.addEventListener('drop', (e) => {
  e.preventDefault();
  drop.classList.remove('dragover');
  if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
    handleFiles(Array.from(e.dataTransfer.files));
  }
});

drop?.addEventListener('click', (e) => {
  e.stopPropagation(); // Prevent event bubbling to avoid closing the modal
  imagesInput?.click();
});
chooseBtn?.addEventListener('click', (e) => {
  e.stopPropagation();
  imagesInput?.click();
});

async function handleFiles(files) {
  const maxSize = 10 * 1024 * 1024;
  let overs = [];
  let totalErr = '';
  const images = files.filter(f => f && f.type && f.type.startsWith('image/'));
  if (images.length === 0) return;
  setLoader(true);
  showPageLoader();
  try {
    for (const file of images) {
      try {
        if (file.size > maxSize) { overs.push(file.name); continue; }
        const data = await uploadFileToServer(file);
        // Successful upload -> store server filename, render local preview
        uploadedServerFiles.push(data.filename);
        const url = URL.createObjectURL(file);
        selectedImageDataURLs.push(url);
        renderThumb(liveImagesPreview, url, true);
        updateClearButton();
      } catch (e) {
        const msg = (e && e.message) ? e.message : 'Upload failed';
        if (msg.toLowerCase().includes('maximum upload size')) {
          totalErr = 'Maximum upload size reached';
          break; // stop uploading more
        }
        overs.push(file.name + ' (' + msg + ')');
      }
    }
  } finally {
    setLoader(false);
    hidePageLoader();
  }

  if (overs.length){
    imageSizeWarning.style.display = 'block';
    imageSizeWarning.textContent = 'These files could not be uploaded: ' + overs.join(', ');
  } else {
    imageSizeWarning.style.display = 'none';
    imageSizeWarning.textContent = '';
  }
  if (totalErr){
    imageTotalWarning.style.display = 'block';
    imageTotalWarning.textContent = totalErr;
  } else {
    imageTotalWarning.style.display = 'none';
    imageTotalWarning.textContent = '';
  }
  try { sessionStorage.setItem('prop_images', JSON.stringify(selectedImageDataURLs)); } catch {}
  try { sessionStorage.setItem('prop_server_files', JSON.stringify(uploadedServerFiles)); } catch {}
}

imagesInput?.addEventListener('change', function(){
  // Don't clear existing previews - allow multiple selections
  const files = Array.from(this.files || []);
  handleFiles(files);
  
  // Clear the input so user can select more files
  this.value = '';
});

// Ensure sorting is enabled on initial DOM once elements exist
window.addEventListener('DOMContentLoaded', ()=>{
  if (liveImagesPreview) { enablePreviewSorting(liveImagesPreview); }
  if (previewImagesGrid) { enablePreviewSorting(previewImagesGrid); }
});

// On submit to step 5, mirror current thumbs to preview grid so user sees them
formEl?.addEventListener('submit', function(){
  // If going to preview step
  const next = (document.activeElement && document.activeElement.name === 'goto_step') ? parseInt(document.activeElement.value,10) : null;
  if (next === 5 && previewImagesGrid){
    previewImagesGrid.innerHTML = '';
    selectedImageDataURLs.forEach(url => renderThumb(previewImagesGrid, url));
    enablePreviewSorting(previewImagesGrid);
  }
  
  // Always submit image data with the form
  if (selectedImageDataURLs.length > 0) {
    // Clear existing hidden inputs
    const existingInputs = formEl.querySelectorAll('input[name="images_data[]"], input[name="uploaded_filenames[]"]');
    existingInputs.forEach(input => input.remove());
    
    // Prefer server filenames; if present, send them. Else send base64 as legacy.
    if (uploadedServerFiles.length > 0) {
      uploadedServerFiles.forEach(fn => {
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = 'uploaded_filenames[]';
        i.value = fn;
        formEl.appendChild(i);
      });
    } else {
      selectedImageDataURLs.forEach((dataURL) => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'images_data[]';
        hiddenInput.value = dataURL;
        formEl.appendChild(hiddenInput);
      });
    }
    
    console.log('Submitting ' + selectedImageDataURLs.length + ' images with form');
  }
});

// Clear all images functionality
clearAllImagesBtn?.addEventListener('click', function(e){
  e.stopPropagation(); // Prevent event bubbling to avoid closing the modal
  selectedImageDataURLs = [];
  uploadedServerFiles = [];
  if (liveImagesPreview) liveImagesPreview.innerHTML = '';
  if (previewImagesGrid) previewImagesGrid.innerHTML = '';
  updateClearButton();
  try { sessionStorage.removeItem('prop_images'); } catch {}
  try { sessionStorage.removeItem('prop_server_files'); } catch {}
  // ask server to clear temp files as well
  fetch('upload_property_images.php', { method:'POST', body: new URLSearchParams({ action:'clear_all' }) });
});

// Restore previews from sessionStorage on load (for both step 4 and 5)
window.addEventListener('DOMContentLoaded', ()=>{
  try {
    const saved = sessionStorage.getItem('prop_images');
    if (saved) {
      const urls = JSON.parse(saved) || [];
      if (liveImagesPreview) {
        liveImagesPreview.innerHTML = '';
        urls.forEach(url => renderThumb(liveImagesPreview, url, true)); // Show remove buttons
        enablePreviewSorting(liveImagesPreview);
      }
      if (previewImagesGrid) {
        previewImagesGrid.innerHTML = '';
        urls.forEach(url => renderThumb(previewImagesGrid, url)); // No remove buttons in preview
        enablePreviewSorting(previewImagesGrid);
      }
      selectedImageDataURLs = urls;
      try { const serverSaved = JSON.parse(sessionStorage.getItem('prop_server_files') || '[]'); uploadedServerFiles = Array.isArray(serverSaved) ? serverSaved : []; } catch {}
      updateClearButton();
    }
  } catch {}
});

// Handle close button click
document.querySelector('.close-btn')?.addEventListener('click', function(){
  // Redirect back to properties index page
  window.location.href = 'index.php';
});

// Prevent accidental form closure - removed click-outside-to-close functionality
// Users must use the close button (X) or complete the form to exit

// Function to start a new property (clear all data)
function startNewProperty() {
  if (confirm('Are you sure you want to start a new property? All current data will be lost.')) {
    // Clear sessionStorage
    sessionStorage.removeItem('prop_images');
    // Redirect to clear session data
    window.location.href = 'add.php?fresh=1';
  }
}

// Function to submit form with images for final submission
function submitWithImages() {
  const form = document.getElementById('wizardForm');
  if (form && selectedImageDataURLs.length > 0) {
    // Clear existing hidden inputs
    const existingInputs = form.querySelectorAll('input[name="images_data[]"]');
    existingInputs.forEach(input => input.remove());
    
    // Prefer server filenames
    if (uploadedServerFiles.length > 0) {
      uploadedServerFiles.forEach((fn) => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'uploaded_filenames[]';
        hiddenInput.value = fn;
        form.appendChild(hiddenInput);
      });
    } else {
      selectedImageDataURLs.forEach((dataURL) => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'images_data[]';
        hiddenInput.value = dataURL;
        form.appendChild(hiddenInput);
      });
    }
    
    console.log('Final submit with ' + selectedImageDataURLs.length + ' images');
  }
  // show page loader during final submit navigation
  showPageLoader();
  return true; // Allow form submission to proceed
}

// Function to handle progress bar step navigation
function goToStep(stepNumber) {
  // First, save current form data
  const form = document.getElementById('wizardForm');
  if (form) {
    // Create a temporary form to submit current data
    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.style.display = 'none';
    
    // Add current step
    const stepInput = document.createElement('input');
    stepInput.type = 'hidden';
    stepInput.name = 'step';
    stepInput.value = '<?php echo $current_step; ?>';
    tempForm.appendChild(stepInput);
    
    // Add goto_step
    const gotoInput = document.createElement('input');
    gotoInput.type = 'hidden';
    gotoInput.name = 'goto_step';
    gotoInput.value = stepNumber;
    tempForm.appendChild(gotoInput);
    
    // Collect all form data from current step
    const formData = new FormData(form);
    for (let [key, value] of formData.entries()) {
      if (key !== 'step' && key !== 'goto_step') {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        tempForm.appendChild(input);
      }
    }
    
    // Add image data if available
    if (selectedImageDataURLs.length > 0) {
      selectedImageDataURLs.forEach((dataURL, index) => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'images_data[]';
        hiddenInput.value = dataURL;
        tempForm.appendChild(hiddenInput);
      });
    }
    
    document.body.appendChild(tempForm);
    tempForm.submit();
  }
}

// Ensure steps 2,3,4 match the visual height of steps 1/5 without hardcoding
document.addEventListener('DOMContentLoaded', function(){
  try {
    const card = document.querySelector('.order-card');
    const content = document.querySelector('.step-content');
    if (!card || !content) return;
    const step = parseInt('<?php echo $current_step; ?>', 10);

    // Measure baseline from step 1/5 and apply as card min-height to keep position stable
    if (step === 1 || step === 5) {
      // Temporarily clear to measure natural height
      card.style.minHeight = '';
      const base = Math.max(card.scrollHeight, card.clientHeight);
      if (base && base > 0) {
        try { sessionStorage.setItem('prop_card_base_h', String(base)); } catch {}
        card.style.minHeight = base + 'px';
      }
      // Content can auto-size; footer is anchored bottom via CSS
      content.style.minHeight = 'auto';
            } else {
      // Steps 2–4: keep card min-height fixed to baseline; let content auto-size to remove bottom whitespace
      const saved = sessionStorage.getItem('prop_card_base_h');
      const baseNum = parseInt(saved || '', 10);
      if (!isNaN(baseNum) && baseNum > 0) {
        card.style.minHeight = baseNum + 'px';
      }
      content.style.minHeight = 'auto';
    }
  } catch {}
});

        // Animated button effects (ripple animation)
        document.addEventListener('DOMContentLoaded', function(){
            document.addEventListener('click', function(e){
                // Ripple + click animation for animated buttons
                const animatedBtn = e.target.closest('.btn-animated-confirm, .btn-animated-add, .btn-animated-delete');
                if (animatedBtn) {
                    const rect = animatedBtn.getBoundingClientRect();
                    const radius = Math.max(rect.width, rect.height) / 2;
                    const diameter = radius * 2;
                    
                    const circle = document.createElement('span');
                    circle.style.position = 'absolute';
                    circle.style.borderRadius = '50%';
                    circle.style.background = 'rgba(255, 255, 255, 0.4)';
                    circle.style.transform = 'scale(0)';
                    circle.style.animation = 'ripple-animation 0.6s linear';
                    circle.style.pointerEvents = 'none';
                    circle.style.width = circle.style.height = `${diameter}px`;
                    circle.style.left = `${(e.clientX - rect.left) - radius}px`;
                    circle.style.top = `${(e.clientY - rect.top) - radius}px`;
                    
                    const existing = animatedBtn.querySelector('.ripple');
                    if (existing) existing.remove();
                    animatedBtn.appendChild(circle);
                    
                    animatedBtn.style.animation = 'none';
                    animatedBtn.style.transform = 'scale(0.95)';
                    setTimeout(() => { 
                        animatedBtn.style.animation = ''; 
                        animatedBtn.style.transform = ''; 
                    }, 150);
                }
            });
        });

    </script>
    <style>
        /* Ripple animation keyframes */
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</script>