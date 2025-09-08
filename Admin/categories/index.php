<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login/');
    exit();
}

function db() { return getMysqliConnection(); }

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

// Fetch categories with property counts
$mysqli = db();

// Check if created_at column exists, if not use a fallback
$check_column = $mysqli->query("SHOW COLUMNS FROM categories LIKE 'created_at'");
$has_created_at = $check_column && $check_column->num_rows > 0;

// Check if image column exists
$check_image = $mysqli->query("SHOW COLUMNS FROM categories LIKE 'image'");
$has_image = $check_image && $check_image->num_rows > 0;

if ($has_created_at && $has_image) {
    $categories_query = "
        SELECT c.id, c.name, c.image,
               COUNT(p.id) as property_count,
               DATE_FORMAT(c.created_at, '%b %d, %Y') as created_date
        FROM categories c 
        LEFT JOIN properties p ON c.id = p.category_id 
        GROUP BY c.id, c.name, c.image, c.created_at 
        ORDER BY c.name ASC
    ";
} elseif ($has_created_at) {
    $categories_query = "
        SELECT c.id, c.name, 
               COUNT(p.id) as property_count,
               DATE_FORMAT(c.created_at, '%b %d, %Y') as created_date
        FROM categories c 
        LEFT JOIN properties p ON c.id = p.category_id 
        GROUP BY c.id, c.name, c.created_at 
        ORDER BY c.name ASC
    ";
} else {
    $categories_query = "
        SELECT c.id, c.name, 
               COUNT(p.id) as property_count,
               'N/A' as created_date
        FROM categories c 
        LEFT JOIN properties p ON c.id = p.category_id 
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
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #8b5cf6;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
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
        /* Cards (match dashboard) */
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .content-card.card{ box-shadow:0 8px 24px rgba(0,0,0,.05); border:1px solid #eef2f7; }
        .page-header.card{ box-shadow:0 8px 20px rgba(0,0,0,.05); border:1px solid var(--line); }
        /* Headings & muted text */
        .text-muted{ color:var(--muted)!important; }
        /* Table (match dashboard) */
        .table{ --bs-table-bg:transparent; }
        .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
        .table tbody tr{ border-top:1px solid var(--line); }
        .table tbody tr:hover{ background:#f9fafb; }
        /* Badges (match dashboard) */
        .badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
        /* Actions cell */
        .actions-cell{ display:flex; gap:8px; justify-content:flex-end; }
        .actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; }
        /* Buttons */
        .btn-primary{ background:var(--primary); border-color:var(--primary); }
        .btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        /* Spacing utilities to mirror dashboard look */
        .main-content .page-header{ margin-bottom:16px; }
        .content-card.card{ padding:16px; }

        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
            .sidebar.open{ left:12px; }
            .content{ margin-left:0; }
            .table{ font-size:.9rem; }
        }
        @media (max-width: 575.98px){
            .page-header .d-flex{ flex-direction:column; align-items:stretch!important; gap:8px; }
            .actions-cell{ justify-content:center; }
            .table thead th:last-child, .table tbody td:last-child{ text-align:center; }
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stats-icon.primary { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
        .stats-icon.success { background: linear-gradient(135deg, var(--success-color), #059669); }
        .stats-icon.warning { background: linear-gradient(135deg, var(--warning-color), #d97706); }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
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
            border-color: var(--border-color);
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
            border: 2px solid var(--border-color);
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .action-buttons .btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
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
            background: var(--primary-color);
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
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
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
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
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
                <!-- Page Header -->
                <div class="page-header card">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1 class="h3 mb-0">
                                <i class="fas fa-tags text-primary me-2"></i>
                                Categories Management
                            </h1>
                            <p class="text-muted mb-0">Manage property categories and classifications</p>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus me-2"></i>Add Category
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards (match dashboard) -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-stat">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small">Categories</div>
                                    <div class="h4 mb-0"><?php echo $total_categories; ?></div>
                                </div>
                                <div class="text-warning"><i class="fa-solid fa-layer-group fa-lg"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-stat">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small">Properties</div>
                                    <div class="h4 mb-0"><?php echo $total_properties; ?></div>
                                </div>
                                <div class="text-primary"><i class="fa-solid fa-building fa-lg"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-stat">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small">Active Categories</div>
                                    <div class="h4 mb-0"><?php echo $categories_with_properties; ?></div>
                                </div>
                                <div class="text-success"><i class="fa-solid fa-chart-pie fa-lg"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="content-card card p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">All Categories</h4>
                    <div class="d-flex gap-2">
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search categories...">
                            <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="refreshTable()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="exportCategories()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>

                    <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="categoriesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Category Name</th>
                                        <th>Properties</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">#<?php echo $category['id']; ?></span>
                                            </td>
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
                                            <td>
                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($category['property_count'] > 0): ?>
                                                    <span class="badge bg-success"><?php echo $category['property_count']; ?> properties</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark">No properties</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo $category['created_date']; ?></small>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
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
                                                </div>
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Category
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Category
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
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#categoriesTable').DataTable({
                pageLength: 25,
                order: [[1, 'asc']], // Sort by category name
                responsive: true,
                language: {
                    search: "Search categories:",
                    lengthMenu: "Show _MENU_ categories per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ categories"
                }
            });
        });

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

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('categoriesTable');
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Clear search function
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            const table = document.getElementById('categoriesTable');
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
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

