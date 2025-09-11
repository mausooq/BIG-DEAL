<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
    $mysqli = db();
    $res = $mysqli->query($sql);
    $row = $res ? $res->fetch_row() : [0];
    return (int)($row[0] ?? 0);
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name'] ?? '');
                if (!empty($name)) {
                    $mysqli = db();
                    
                    // Check if category name already exists
                    $check_stmt = $mysqli->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
                    $check_stmt->bind_param("s", $name);
                    $check_stmt->execute();
                    $exists = $check_stmt->get_result()->fetch_row()[0];
                    $check_stmt->close();
                    
                    if ($exists > 0) {
                        $message = "Category '$name' already exists!";
                        $message_type = 'warning';
                    } else {
                        // Handle image upload
                        $image_path = null;
                        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                            $upload_dir = '../../uploads/categories/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            
                            if (in_array($file_extension, $allowed_extensions)) {
                                $new_filename = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
                                $upload_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                                    $image_path = $new_filename; // Store only filename, not full path
                                } else {
                                    $message = "Error uploading image!";
                                    $message_type = 'danger';
                                    break;
                                }
                            } else {
                                $message = "Invalid file type! Please upload JPG, PNG, GIF, or WebP images only.";
                                $message_type = 'danger';
                                break;
                            }
                        }
                        
                        // Insert with timestamp columns if they exist
                        $check_column = $mysqli->query("SHOW COLUMNS FROM categories LIKE 'created_at'");
                        $has_created_at = $check_column && $check_column->num_rows > 0;
                        $check_image = $mysqli->query("SHOW COLUMNS FROM categories LIKE 'image'");
                        $has_image = $check_image && $check_image->num_rows > 0;
                        
                        if ($has_created_at && $has_image) {
                            $stmt = $mysqli->prepare("INSERT INTO categories (name, image, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                            $stmt->bind_param("ss", $name, $image_path);
                        } elseif ($has_created_at) {
                            $stmt = $mysqli->prepare("INSERT INTO categories (name, created_at, updated_at) VALUES (?, NOW(), NOW())");
                            $stmt->bind_param("s", $name);
                        } elseif ($has_image) {
                            $stmt = $mysqli->prepare("INSERT INTO categories (name, image) VALUES (?, ?)");
                            $stmt->bind_param("ss", $name, $image_path);
                        } else {
                            $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
                            $stmt->bind_param("s", $name);
                        }
                        
                        if ($stmt->execute()) {
                            $message = "Category '$name' added successfully!";
                            $message_type = 'success';
                            
                            // Log activity (use NULL when admin_id is not set to avoid FK errors)
                            $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
                            if ($admin_id === null) {
                                $log_stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details) VALUES (NULL, ?, ?)");
                            } else {
                                $log_stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details) VALUES (?, ?, ?)");
                            }
                            $action = "Added new category";
                            $details = "Category: $name" . ($image_path ? " with image" : "");
                            if ($admin_id === null) {
                                $log_stmt->bind_param("ss", $action, $details);
                            } else {
                                $log_stmt->bind_param("iss", $admin_id, $action, $details);
                            }
                            $log_stmt->execute();
                        } else {
                            $message = "Error adding category: " . $mysqli->error;
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    }
                } else {
                    $message = "Category name cannot be empty!";
                    $message_type = 'danger';
                }
                break;
                
            case 'edit':
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                if ($id > 0 && !empty($name)) {
                    $mysqli = db();
                    
                    // Check if the new name already exists for a different category
                    $check_stmt = $mysqli->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
                    $check_stmt->bind_param("si", $name, $id);
                    $check_stmt->execute();
                    $exists = $check_stmt->get_result()->fetch_row()[0];
                    $check_stmt->close();
                    
                    if ($exists > 0) {
                        $message = "Category name '$name' already exists!";
                        $message_type = 'warning';
                    } else {
                        // Handle image upload
                        $image_path = null;
                        $update_image = false;
                        
                        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                            $upload_dir = '../../uploads/categories/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            
                            if (in_array($file_extension, $allowed_extensions)) {
                                $new_filename = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
                                $upload_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                                    $image_path = $new_filename; // Store only filename, not full path
                                    $update_image = true;
                                } else {
                                    $message = "Error uploading image!";
                                    $message_type = 'danger';
                                    break;
                                }
                            } else {
                                $message = "Invalid file type! Please upload JPG, PNG, GIF, or WebP images only.";
                                $message_type = 'danger';
                                break;
                            }
                        }
                        
                        // Update with timestamp if the column exists
                        $check_column = $mysqli->query("SHOW COLUMNS FROM categories LIKE 'updated_at'");
                        $has_updated_at = $check_column && $check_column->num_rows > 0;
                        $check_image = $mysqli->query("SHOW COLUMNS FROM categories LIKE 'image'");
                        $has_image = $check_image && $check_image->num_rows > 0;
                        
                        if ($has_updated_at && $has_image && $update_image) {
                            $stmt = $mysqli->prepare("UPDATE categories SET name = ?, image = ?, updated_at = NOW() WHERE id = ?");
                            $stmt->bind_param("ssi", $name, $image_path, $id);
                        } elseif ($has_updated_at && $has_image) {
                            $stmt = $mysqli->prepare("UPDATE categories SET name = ?, updated_at = NOW() WHERE id = ?");
                            $stmt->bind_param("si", $name, $id);
                        } elseif ($has_updated_at) {
                            $stmt = $mysqli->prepare("UPDATE categories SET name = ?, updated_at = NOW() WHERE id = ?");
                            $stmt->bind_param("si", $name, $id);
                        } else {
                            $stmt = $mysqli->prepare("UPDATE categories SET name = ? WHERE id = ?");
                            $stmt->bind_param("si", $name, $id);
                        }
                        
                        if ($stmt->execute()) {
                            $message = "Category updated successfully!";
                            $message_type = 'success';
                            
                            // Log activity (use NULL when admin_id is not set to avoid FK errors)
                            $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
                            if ($admin_id === null) {
                                $log_stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details) VALUES (NULL, ?, ?)");
                            } else {
                                $log_stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details) VALUES (?, ?, ?)");
                            }
                            $action = "Updated category";
                            $details = "Category ID: $id, New name: $name" . ($update_image ? " with new image" : "");
                            if ($admin_id === null) {
                                $log_stmt->bind_param("ss", $action, $details);
                            } else {
                                $log_stmt->bind_param("iss", $admin_id, $action, $details);
                            }
                            $log_stmt->execute();
                        } else {
                            $message = "Error updating category: " . $mysqli->error;
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    }
                } else {
                    $message = "Invalid category data!";
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $mysqli = db();
                    
                    // Check if category is being used by properties
                    $check_stmt = $mysqli->prepare("SELECT COUNT(*) FROM properties WHERE category_id = ?");
                    $check_stmt->bind_param("i", $id);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    $count = $result->fetch_row()[0];
                    $check_stmt->close();
                    
                    if ($count > 0) {
                        $message = "Cannot delete category: It is being used by $count properties!";
                        $message_type = 'warning';
                    } else {
                        $stmt = $mysqli->prepare("DELETE FROM categories WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        if ($stmt->execute()) {
                            $message = "Category deleted successfully!";
                            $message_type = 'success';
                            
                            // Log activity (use NULL when admin_id is not set to avoid FK errors)
                            $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
                            if ($admin_id === null) {
                                $log_stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details) VALUES (NULL, ?, ?)");
                            } else {
                                $log_stmt = $mysqli->prepare("INSERT INTO activity_logs (admin_id, action, details) VALUES (?, ?, ?)");
                            }
                            $action = "Deleted category";
                            $details = "Category ID: $id";
                            if ($admin_id === null) {
                                $log_stmt->bind_param("ss", $action, $details);
                            } else {
                                $log_stmt->bind_param("iss", $admin_id, $action, $details);
                            }
                            $log_stmt->execute();
                        } else {
                            $message = "Error deleting category: " . $mysqli->error;
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    }
                } else {
                    $message = "Invalid category ID!";
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get categories with search
$mysqli = db();
$search = $_GET['search'] ?? '';

// Check if created_at column exists, if not use a fallback
$check_column = $mysqli->query("SHOW COLUMNS FROM categories LIKE 'created_at'");
$has_created_at = $check_column && $check_column->num_rows > 0;

// Check if image column exists
$check_image = $mysqli->query("SHOW COLUMNS FROM categories LIKE 'image'");
$has_image = $check_image && $check_image->num_rows > 0;

$whereClause = '';
if ($search) {
    $whereClause = ' WHERE c.name LIKE "%' . $mysqli->real_escape_string($search) . '%"';
}

if ($has_created_at && $has_image) {
    $categories_query = "
        SELECT c.id, c.name, c.image,
               COUNT(p.id) as property_count,
               DATE_FORMAT(c.created_at, '%b %d, %Y') as created_date
        FROM categories c 
        LEFT JOIN properties p ON c.id = p.category_id" . $whereClause . "
        GROUP BY c.id, c.name, c.image, c.created_at 
        ORDER BY c.name ASC
    ";
} elseif ($has_created_at) {
    $categories_query = "
        SELECT c.id, c.name, 
               COUNT(p.id) as property_count,
               DATE_FORMAT(c.created_at, '%b %d, %Y') as created_date
        FROM categories c 
        LEFT JOIN properties p ON c.id = p.category_id" . $whereClause . "
        GROUP BY c.id, c.name, c.created_at 
        ORDER BY c.name ASC
    ";
} else {
    $categories_query = "
        SELECT c.id, c.name, 
               COUNT(p.id) as property_count,
               'N/A' as created_date
        FROM categories c 
        LEFT JOIN properties p ON c.id = p.category_id" . $whereClause . "
        GROUP BY c.id, c.name
        ORDER BY c.name ASC
    ";
}

$categories_result = $mysqli->query($categories_query);
if (!$categories_result) {
    error_log("Error fetching categories: " . $mysqli->error);
    $categories_result = null;
}

// Get total counts for stats with error handling
$total_categories = 0;
$total_properties = 0;
$categories_with_properties = 0;

try {
    $result = $mysqli->query("SELECT COUNT(*) FROM categories");
    if ($result) {
        $total_categories = $result->fetch_row()[0];
    }
    
    $result = $mysqli->query("SELECT COUNT(*) FROM properties");
    if ($result) {
        $total_properties = $result->fetch_row()[0];
    }
    
    $result = $mysqli->query("SELECT COUNT(DISTINCT category_id) FROM properties WHERE category_id IS NOT NULL");
    if ($result) {
        $categories_with_properties = $result->fetch_row()[0];
    }
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Big Deal Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    
    <style>
        :root {
            --bg:#F1EFEC;
            --card:#ffffff;
            --muted:#6b7280;
            --line:#e9eef5;
            --brand-dark:#2f2f2f;
            --primary:#e11d2a;
            --primary-600:#b91c1c;
            --radius:16px;
        }

        body{ background:var(--bg); color:#111827; }
        .content{ margin-left:284px; }
        /* Sidebar styles copied from dashboard */
        .sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .brand{ font-weight:700; font-size:1.25rem; }
        .list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
        .list-group-item i{ width:18px; }
        .list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
        .list-group-item:hover{ background:#f8fafc; }
        /* Topbar */
        .navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .text-primary{ color:var(--primary)!important; }
        .input-group .form-control{ border-color:var(--line); }
        .input-group-text{ 
            border-color:var(--line); 
            background-color: #fff;
            border-radius: 8px 0 0 8px;
            padding: 0.5rem 0.75rem;
        }
        /* Button consistency */
        .btn{ border-radius:8px; font-weight:500; }
        .btn-sm{ padding:0.5rem 1rem; font-size:0.875rem; }
        /* Cards (match dashboard) */
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .content-card.card{ box-shadow:0 8px 24px rgba(0,0,0,.05); border:1px solid #eef2f7; }
        /* Headings & muted text */
        .text-muted{ color:var(--muted)!important; }
        /* Table (match properties exactly) */
        .table{ --bs-table-bg:transparent; }
        .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
        .table tbody tr:hover{ background:#f9fafb; }
        .table td{ vertical-align: middle; }
        /* Inner borders (match Properties exactly) */
        .table-inner thead th{ background:transparent; border-bottom:1px solid var(--line) !important; color:#111827; font-weight:600; }
        /* Remove inner borders for body cells and override Bootstrap defaults */
        .table-inner td, .table-inner th{ border-left:0; border-right:0; }
        /* Ensure consistent border thickness */
        .table-inner tbody td{ border-top:1px solid var(--line) !important; }
        /* Toolbar */
        .toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
        .toolbar .row-top{ display:flex; gap:12px; align-items:center; }
        /* Table wrapper styling */
        .table-wrap{ border:0; border-radius:12px; overflow:hidden; background:#fff; }
        /* Actions column - fully integrated with table */
        .actions-header{ 
            position:sticky;
            right:0;
            background:white;
            z-index:10;
            text-align:center;
            font-weight:600;
            padding:12px 8px;
            border-left:1px solid var(--line);
            border-bottom:1px solid var(--line) !important;
        }
        .actions-cell{ 
            position:sticky;
            right:0;
            background:white;
            z-index:10;
            padding:8px 8px !important; 
            min-width:120px;
            max-width:120px;
            text-align:center;
            vertical-align:middle;
            border-left:1px solid var(--line);
            white-space:nowrap;
        }
        .actions-cell .btn{ 
            width:28px; 
            height:28px; 
            display:inline-flex; 
            align-items:center; 
            justify-content:center; 
            border-radius:4px; 
            padding:0; 
            margin:0 2px;
            font-size:0.75rem;
            border-width:1px;
        }
        /* Badges */
        .badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
        /* Buttons */
        .btn-primary{ background:var(--primary); border-color:var(--primary); }
        .btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        .content-card.card{ padding:16px; }
        .small-title{ font-size:16px; }

        /* Table zoom stability */
        .table-responsive{
            overflow-x:auto;
            -webkit-overflow-scrolling:touch;
            position:relative;
        }
        .table-responsive::-webkit-scrollbar{
            height:8px;
        }
        .table-responsive::-webkit-scrollbar-track{
            background:#f1f1f1;
            border-radius:4px;
        }
        .table-responsive::-webkit-scrollbar-thumb{
            background:#c1c1c1;
            border-radius:4px;
        }
        .table-responsive::-webkit-scrollbar-thumb:hover{
            background:#a8a8a8;
        }
        
        /* Zoom stability improvements */
        .table{
            table-layout:fixed;
            width:100%;
        }
        
        /* Ensure table rows maintain proper structure */
        .table tbody tr{
            border-top:1px solid var(--line);
        }
        .table tbody tr:hover{
            background:#f9fafb;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
            .sidebar.open{ left:12px; }
            .content{ margin-left:0; }
            .table{ font-size:.9rem; }
            .actions-header, .actions-cell{ min-width:100px; }
            .actions-cell .btn{ width:24px; height:24px; font-size:0.7rem; margin:0 1px; }
        }
        @media (max-width: 575.98px){
            .toolbar .row-top{ flex-direction:column; align-items:stretch; }
            .actions-cell{ justify-content:center; }
            .table thead th:last-child, .table tbody td:last-child{ text-align:center; }
        }


        .content-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }


        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background: var(--light-color);
            border: none;
            font-weight: 600;
            color: var(--text-primary);
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 20px 25px rgba(0,0,0,0.1);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid var(--line);
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(225, 29, 42, 0.1);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.5rem;
        }


        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Image Upload Styles */
        .image-upload-container {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            background: #fafbfc;
        }

        .image-upload-container:hover {
            border-color: var(--primary-color);
            background: #f8f9ff;
        }

        .image-upload-container.dragover {
            border-color: var(--primary-color);
            background: #f0f4ff;
            transform: scale(1.02);
        }

        .image-upload-icon {
            font-size: 2.5rem;
            color: var(--muted);
            margin-bottom: 1rem;
        }

        .image-upload-text {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .image-upload-subtext {
            color: var(--muted);
            font-size: 0.875rem;
        }

        .image-preview-container {
            position: relative;
            display: inline-block;
            margin-top: 1rem;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .image-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .image-remove-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--danger-color);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .image-remove-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .current-image-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid var(--border-color);
        }

        .current-image-label {
            font-size: 0.875rem;
            color: var(--muted);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .current-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: block;
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-input-label:hover {
            background: var(--primary-600);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(225, 29, 42, 0.3);
        }

        .upload-progress {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.5rem;
            display: none;
        }

        .upload-progress-bar {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('categories'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>
        <div class="container-fluid p-4">
                <!-- Alert Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>



            <!-- Search toolbar -->
            <div class="toolbar mb-4">
                <div class="row-top">
                    <form class="d-flex flex-grow-1" method="get">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search categories by name">
                        </div>
                        <button class="btn btn-primary ms-2" type="submit">Search</button>
                        <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                    </form>
                    <button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <span class="text">Add Category</span>
                        <span class="icon">
                            <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
                            <span class="buttonSpan">+</span>
                        </span>
                    </button>
                </div>
            </div>

                <!-- Categories Table -->
                <div class="content-card card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0 small-title">All Categories</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm" onclick="refreshTable()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="exportCategories()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>

                    <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                        <div class="table-responsive table-wrap">
                            <table class="table table-hover table-inner" id="categoriesTable">
                                <thead>
                                    <tr>
                                        <th style="min-width:80px;">Image</th>
                                        <th style="min-width:200px;">Category Name</th>
                                        <th style="min-width:120px;">Properties</th>
                                        <th style="min-width:120px;">Created Date</th>
                                        <th class="actions-header" style="min-width:120px; width:120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($category['image'])): ?>
                                                    <img src="../../uploads/categories/<?php echo htmlspecialchars($category['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                                         class="category-image" 
                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                                <?php else: ?>
                                                    <div class="category-placeholder" 
                                                         style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td>
                                                <?php if ($category['property_count'] > 0): ?>
                                                    <span class="badge bg-light text-dark border"><?php echo $category['property_count']; ?> properties</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark border">No properties</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted"><?php echo $category['created_date']; ?></td>
                                            <td class="actions-cell">
                                                <button class="btn btn-outline-secondary btn-sm" 
                                                        onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['image'] ?? ''); ?>')"
                                                        title="Edit Category">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($category['property_count'] == 0): ?>
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')"
                                                            title="Delete Category">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary btn-sm" disabled title="Cannot delete - category has properties">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-tags"></i>
                            <h5>No Categories Found</h5>
                            <p class="mb-3">Get started by adding your first property category.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus me-2"></i>Add First Category
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus text-primary me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="categoryName" name="name" 
                                   placeholder="e.g., Residential, Commercial, Villa" required>
                            <div class="invalid-feedback">
                                Category name must be 2-50 characters and contain only letters, numbers, spaces, hyphens, and underscores.
                            </div>
                            <div class="valid-feedback">
                                Category name looks good!
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category Image</label>
                            <div class="image-upload-container" id="addImageUploadContainer">
                                <div class="image-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="image-upload-text">Click to upload or drag and drop</div>
                                <div class="image-upload-subtext">JPG, PNG, GIF, or WebP (max 5MB)</div>
                                <div class="file-input-wrapper">
                                    <input type="file" class="file-input" id="categoryImage" name="image" 
                                           accept="image/*" onchange="previewImage(this, 'addImagePreview')">
                                    <label for="categoryImage" class="file-input-label">
                                        <i class="fas fa-plus me-2"></i>Choose Image
                                    </label>
                                </div>
                                <div class="upload-progress" id="addUploadProgress">
                                    <div class="upload-progress-bar" id="addUploadProgressBar"></div>
                                </div>
                            </div>
                            <div id="addImagePreview" class="image-preview-container" style="display: none;">
                                <img id="addImagePreviewImg" src="" alt="Preview" class="image-preview">
                                <button type="button" class="image-remove-btn" onclick="removeImage('categoryImage', 'addImagePreview')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Add Category</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit text-primary me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editCategoryId">
                        <div class="mb-3">
                            <label for="editCategoryName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="editCategoryName" name="name" required>
                            <div class="invalid-feedback">
                                Category name must be 2-50 characters and contain only letters, numbers, spaces, hyphens, and underscores.
                            </div>
                            <div class="valid-feedback">
                                Category name looks good!
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category Image</label>
                            
                            <!-- Current Image Display -->
                            <div id="editCurrentImage" class="current-image-container" style="display: none;">
                                <div class="current-image-label">Current Image</div>
                                <img id="editCurrentImageImg" src="" alt="Current Image" class="current-image">
                            </div>
                            
                            <!-- New Image Upload -->
                            <div class="image-upload-container" id="editImageUploadContainer">
                                <div class="image-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="image-upload-text">Click to upload new image or drag and drop</div>
                                <div class="image-upload-subtext">JPG, PNG, GIF, or WebP (max 5MB). Leave empty to keep current image.</div>
                                <div class="file-input-wrapper">
                                    <input type="file" class="file-input" id="editCategoryImage" name="image" 
                                           accept="image/*" onchange="previewImage(this, 'editImagePreview')">
                                    <label for="editCategoryImage" class="file-input-label">
                                        <i class="fas fa-upload me-2"></i>Choose New Image
                                    </label>
                                </div>
                                <div class="upload-progress" id="editUploadProgress">
                                    <div class="upload-progress-bar" id="editUploadProgressBar"></div>
                                </div>
                            </div>
                            
                            <!-- New Image Preview -->
                            <div id="editImagePreview" class="image-preview-container" style="display: none;">
                                <img id="editImagePreviewImg" src="" alt="New Image Preview" class="image-preview">
                                <button type="button" class="image-remove-btn" onclick="removeImage('editCategoryImage', 'editImagePreview')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Update Category</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>Delete Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<strong id="deleteCategoryName"></strong>"?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-delete noselect">
                            <span class="text">Delete Category</span>
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                    <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>

        // Edit category function
        function editCategory(id, name, image) {
            document.getElementById('editCategoryId').value = id;
            document.getElementById('editCategoryName').value = name;
            
            // Handle image preview
            const currentImageDiv = document.getElementById('editCurrentImage');
            const currentImageImg = document.getElementById('editCurrentImageImg');
            const previewDiv = document.getElementById('editImagePreview');
            const previewImg = document.getElementById('editImagePreviewImg');
            const uploadContainer = document.getElementById('editImageUploadContainer');
            
            if (image && image.trim() !== '') {
                currentImageImg.src = '../../uploads/categories/' + image;
                currentImageDiv.style.display = 'block';
            } else {
                currentImageDiv.style.display = 'none';
            }
            
            // Clear new image preview and reset upload container
            previewImg.src = '';
            previewDiv.style.display = 'none';
            uploadContainer.style.display = 'block';
            
            // Reset file input
            document.getElementById('editCategoryImage').value = '';
            
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }

        // Delete category function
        function deleteCategory(id, name) {
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteCategoryName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
        }

        // Refresh table function
        function refreshTable() {
            location.reload();
        }

        // Export categories function
        function exportCategories() {
            const table = document.getElementById('categoriesTable');
            if (!table) return;
            
            let csv = 'ID,Category Name,Properties,Created Date\n';
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const id = cells[0].textContent.replace('#', '');
                const name = cells[1].textContent.trim();
                const properties = cells[2].textContent.trim();
                const createdDate = cells[3].textContent.trim();
                
                csv += `"${id}","${name}","${properties}","${createdDate}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'categories_export.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Clear search function (now redirects to reset URL)
        function clearSearch() {
            window.location.href = 'index.php';
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Enhanced form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const nameInput = this.querySelector('input[name="name"]');
                if (nameInput) {
                    const name = nameInput.value.trim();
                    if (name.length < 2) {
                        e.preventDefault();
                        alert('Category name must be at least 2 characters long.');
                        nameInput.focus();
                        return;
                    }
                    if (name.length > 50) {
                        e.preventDefault();
                        alert('Category name cannot exceed 50 characters.');
                        nameInput.focus();
                        return;
                    }
                    // Check for special characters that might cause issues
                    if (!/^[a-zA-Z0-9\s\-_]+$/.test(name)) {
                        e.preventDefault();
                        alert('Category name contains invalid characters. Use only letters, numbers, spaces, hyphens, and underscores.');
                        nameInput.focus();
                        return;
                    }
                }
            });
        });

        // Add real-time validation feedback
        document.querySelectorAll('input[name="name"]').forEach(input => {
            input.addEventListener('input', function() {
                const value = this.value.trim();
                const feedback = this.parentElement.querySelector('.invalid-feedback') || 
                               this.parentElement.querySelector('.valid-feedback');
                
                if (value.length < 2) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else if (value.length > 50) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else if (!/^[a-zA-Z0-9\s\-_]+$/.test(value)) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                }
            });
        });

        // Image preview function
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const previewImg = document.getElementById(previewId + 'Img');
            const uploadContainer = input.closest('.image-upload-container');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    uploadContainer.style.display = 'none';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
                uploadContainer.style.display = 'block';
            }
        }

        // Remove image function
        function removeImage(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const uploadContainer = input.closest('.image-upload-container');
            
            input.value = '';
            preview.style.display = 'none';
            uploadContainer.style.display = 'block';
        }

        // File size validation and drag & drop
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                validateAndPreviewFile(this);
            });
        });

        // Add drag and drop functionality
        document.querySelectorAll('.image-upload-container').forEach(container => {
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            container.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            container.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const input = this.querySelector('input[type="file"]');
                    input.files = files;
                    validateAndPreviewFile(input);
                }
            });
        });

        // File validation and preview function
        function validateAndPreviewFile(input) {
            const file = input.files[0];
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    showAlert('File size must be less than 5MB', 'warning');
                    input.value = '';
                    return;
                }
                
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showAlert('Please select a valid image file (JPG, PNG, GIF, or WebP)', 'warning');
                    input.value = '';
                    return;
                }
                
                // Show preview
                const previewId = input.id === 'categoryImage' ? 'addImagePreview' : 'editImagePreview';
                previewImage(input, previewId);
            }
        }

        // Show alert function
        function showAlert(message, type) {
            const alertContainer = document.querySelector('.container-fluid');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle')} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.insertBefore(alertDiv, alertContainer.firstChild);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
        }
    </script>
</body>
</html>

