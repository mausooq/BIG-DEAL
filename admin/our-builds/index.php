<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

// Flash message
$message = '';
$message_type = 'success';

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

// Handle AJAX reorder request first
if (isset($_POST['action']) && $_POST['action'] === 'reorder_projects') {
    header('Content-Type: application/json');
    $project_orders_json = $_POST['project_orders'] ?? '[]';
    $project_orders = json_decode($project_orders_json, true) ?? [];
    $mysqli = db();
    
    try {
        $mysqli->begin_transaction();
        
        foreach ($project_orders as $order_data) {
            $id = (int)$order_data['id'];
            $order_id = (int)$order_data['order_id'];
            
            $stmt = $mysqli->prepare('UPDATE projects SET order_id = ? WHERE id = ?');
            $stmt->bind_param('ii', $order_id, $id);
            $stmt->execute();
            $stmt->close();
        }
        
        $mysqli->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $mysqli = db();
    try {
        switch ($_POST['action']) {
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) { throw new Exception('Invalid project ID'); }
                
                // Delete project
                $stmt = $mysqli->prepare('DELETE FROM projects WHERE id = ?');
                $stmt->bind_param('i', $id);
                if (!$stmt->execute()) { throw new Exception('Failed to delete project: ' . $mysqli->error); }
                $stmt->close();
                logActivity($mysqli, 'Deleted project', 'ID: ' . $id);
                $message = 'Project deleted successfully';
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Filters from query string
$filters = [
    'name' => $_GET['name'] ?? '',
    'location' => $_GET['location'] ?? '',
];

$mysqli = db();

// Stats
function fetchScalar($sql) { $m = db(); $r = $m->query($sql); $row = $r ? $r->fetch_row() : [0]; return (int)($row[0] ?? 0); }
$totalProjects = fetchScalar("SELECT COUNT(*) FROM projects");

// Build query
$where = [];
$types = '';
$params = [];

if ($filters['name'] !== '') { $where[] = 'p.name LIKE ?'; $types .= 's'; $params[] = '%' . $mysqli->real_escape_string($filters['name']) . '%'; }
if ($filters['location'] !== '') { $where[] = 'p.location LIKE ?'; $types .= 's'; $params[] = '%' . $mysqli->real_escape_string($filters['location']) . '%'; }

// Pagination
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Total count for pagination
$countSql = "SELECT COUNT(*) FROM projects p";
if (!empty($where)) { $countSql .= ' WHERE ' . implode(' AND ', $where); }
$countStmt = $mysqli->prepare($countSql);
if ($countStmt && $types !== '') { $countStmt->bind_param($types, ...$params); }
$totalRows = 0;
if ($countStmt && $countStmt->execute()) {
    $countRes = $countStmt->get_result();
    $row = $countRes ? $countRes->fetch_row() : [0];
    $totalRows = (int)($row[0] ?? 0);
}
$countStmt && $countStmt->close();

$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

// Data query with pagination
$sql = "SELECT p.id, p.name, p.description, p.location, p.order_id,
                DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at,
                (SELECT COUNT(*) FROM project_images pi WHERE pi.project_id = p.id) as image_count,
                (SELECT pi.image_filename FROM project_images pi WHERE pi.project_id = p.id ORDER BY pi.display_order ASC LIMIT 1) as first_image
         FROM projects p";
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.order_id ASC, p.created_at DESC LIMIT ?, ?';

$stmt = $mysqli->prepare($sql);
$bindTypes = $types . 'ii';
$bindParams = $params;
$bindParams[] = $offset;
$bindParams[] = $perPage;
if ($stmt) { $stmt->bind_param($bindTypes, ...$bindParams); }
$stmt && $stmt->execute();
$projects = $stmt ? $stmt->get_result() : $mysqli->query("SELECT p.id, p.name, p.description, p.location, p.image_path, p.order_id, DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at FROM projects p ORDER BY p.order_id ASC, p.created_at DESC LIMIT 10 OFFSET 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Builds - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        body{ background:var(--bg); color:#111827; }
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .table tbody tr:hover{ background:#f9fafb; }
        .table td{ vertical-align: middle; }
        .table-inner thead th{ background:transparent; border-bottom:1px solid var(--line) !important; color:#111827; font-weight:600 !important; font-size:.875rem !important; }
        .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
        .table-inner td, .table-inner th{ border-left:0; border-right:0; }
        .badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        
        /* Actions column */
        .actions-header{ 
            position:sticky;
            right:8px;
            background:white;
            z-index:10;
            text-align:center;
            font-weight:600;
            padding:12px 8px;
            border-left:1px solid var(--line);
        }
        .actions-cell{ 
            position:sticky;
            right:8px;
            background:white;
            z-index:10;
            padding:8px 8px !important; 
            min-width:150px;
            max-width:150px;
            text-align:center;
            vertical-align:middle;
            border-left:1px solid var(--line);
            white-space:nowrap;
            overflow:hidden;
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
        
        /* Modern Animated Action Buttons */
        .modern-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            backdrop-filter: blur(10px);
            box-shadow: 
                0 4px 16px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin: 0 2px;
        }

        .modern-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }

        .modern-btn:hover::before {
            left: 100%;
        }

        .modern-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 
                0 8px 24px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .modern-btn:active {
            transform: translateY(-1px) scale(1.02);
            transition: all 0.1s ease;
        }

        /* View Button - Neutral grey */
        .view-btn {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .view-btn:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
            color: #374151;
        }

        /* Edit Button - Neutral grey */
        .edit-btn {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .edit-btn:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
            color: #374151;
        }

        /* Delete Button - Solid theme red */
        .delete-btn {
            background: var(--primary);
            color: #ffffff;
            border: 1px solid var(--primary-600);
        }

        .delete-btn:hover {
            background: var(--primary-600);
            border-color: var(--primary-600);
            color: #ffffff;
        }

        /* Icon animations */
        .modern-btn .icon {
            transition: all 0.3s ease;
        }

        .modern-btn:hover .icon {
            transform: scale(1.2) rotate(5deg);
        }

        .delete-btn:hover .icon {
            transform: scale(1.2) rotate(-5deg);
        }

        /* Ripple effect */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Glow effect on hover */
        .modern-btn:hover {
            filter: drop-shadow(0 0 12px rgba(255, 255, 255, 0.3));
        }
        
        /* View button specific styles */
        .modern-btn.view-btn {
            background: #ffffff;
            border-color: #e5e7eb;
            color: #374151;
        }
        .modern-btn.view-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
        }
        
        .btn-sm{ padding:0.5rem 1rem; font-size:0.875rem; }
        .btn-primary{ background-color: var(--primary); border-color: var(--primary); }
        .btn-primary:hover, .btn-primary:focus{ background-color: var(--primary-600); border-color: var(--primary-600); }
        .btn-outline-primary{ color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover{ background-color: var(--primary); border-color: var(--primary); color:#fff; }
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
        
        /* Pagination red theme */
        .pagination .page-link {
            color: var(--primary);
            border-color: var(--line);
            background-color: var(--card);
            border-radius: 8px;
            margin: 0 2px;
            padding: 0.5rem 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .pagination .page-link:hover {
            color: #fff;
            background-color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(225, 29, 42, 0.2);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(225, 29, 42, 0.3);
        }
        
        .pagination .page-item.disabled .page-link {
            color: var(--muted);
            background-color: #f8f9fa;
            border-color: var(--line);
            cursor: not-allowed;
        }
        
        .pagination .page-item.disabled .page-link:hover {
            color: var(--muted);
            background-color: #f8f9fa;
            border-color: var(--line);
            transform: none;
            box-shadow: none;
        }
        
        /* Toolbar */
        .toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
        .toolbar .row-top{ display:flex; gap:12px; align-items:center; }
        .input-group-text{ 
            background-color: #fff;
            border-radius: 8px 0 0 8px;
            padding: 0.5rem 0.75rem;
        }
        
        /* Button consistency */
        .btn{ border-radius:8px; font-weight:500; }
        
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
        
        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .table{ font-size:.9rem; }
            .actions-header, .actions-cell{ min-width:140px; }
            .actions-cell .modern-btn{ width:32px; height:32px; font-size:0.7rem; margin:0 1px; }
        }
        
        /* High zoom level stability */
        @media (min-resolution: 1.5dppx) {
            .table-responsive{
                min-width:100%;
            }
            .actions-cell{
                position:sticky;
                right:0;
                background:white;
                z-index:10;
                box-shadow:-2px 0 4px rgba(0,0,0,0.1);
            }
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
        
        /* Project image styling */
        .project-image-container {
            position: relative;
            display: inline-block;
        }
        .project-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--line);
        }
        .image-count-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .image-count-badge i {
            font-size: 8px;
        }
        .project-description {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Drawer styles */
        .drawer{ position:fixed; top:0; right:-500px; width:500px; height:100vh; background:#fff; box-shadow:-12px 0 24px rgba(0,0,0,.08); transition:right .3s cubic-bezier(0.4, 0.0, 0.2, 1); z-index:1040; }
        .drawer.open{ right:0; }
        .drawer-header{ padding:16px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
        .drawer-actions{ display:flex; gap:8px; align-items:center; }
        .drawer-body{ padding:16px 20px 16px 16px; overflow:auto; height:calc(100vh - 64px); }
        .drawer-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.3); opacity:0; pointer-events:none; transition:opacity .3s ease; z-index:1035; }
        .drawer-backdrop.open{ opacity:1; pointer-events:auto; }
        .drawer-image{ width:100%; height:250px; object-fit:contain; border-radius:8px; margin-bottom:1rem; background:#f8f9fa; border:1px solid #e9ecef; }
        .drawer-image-gallery{ display:flex; gap:8px; margin-top:1rem; flex-wrap:wrap; }
        .drawer-image-thumb{ width:90px; height:90px; object-fit:cover; border-radius:6px; cursor:pointer; border:2px solid transparent; transition:all 0.2s ease; flex-shrink:0; }
        .drawer-image-thumb:hover{ border-color:var(--primary); transform:scale(1.05); }
        .drawer-image-thumb.active{ border-color:var(--primary); box-shadow:0 0 0 2px rgba(239, 68, 68, 0.2); }
        .more-images-btn{ background:#f8f9fa; border:2px dashed #dee2e6; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s ease; }
        .more-images-btn:hover{ background:#e9ecef; border-color:#adb5bd; }
        .more-images-content{ text-align:center; color:#6c757d; }
        .more-images-content i{ font-size:1.2rem; margin-bottom:2px; }
        .more-count{ font-size:0.75rem; font-weight:600; }
        .drawer-image{ cursor:pointer; transition:transform 0.2s ease; }
        .drawer-image:hover{ transform:scale(1.02); }
        .project-detail{ margin-bottom:1rem; }
        .project-detail .label{ color:var(--muted); font-size:0.875rem; font-weight:600; margin-bottom:0.25rem; }
        .project-detail .value{ font-weight:600; color:#111827; }
        .project-detail .value.badge{ font-weight:500; }
        .divider{ height:1px; background:var(--line); margin:1rem 0; }
        .two-col{ display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        
        /* Drag and drop styling */
        .drag-handle{ cursor:grab; color:var(--muted); padding:8px; border-radius:6px; transition:all 0.2s ease; }
        .drag-handle:hover{ background:#f3f4f6; color:var(--primary); }
        .drag-handle:active{ cursor:grabbing; }
        .table tbody tr.dragging{ opacity:0.5; background:#f8f9fa; }
        .table tbody tr.drag-over{ border-top:2px solid var(--primary); }
        .order-cell{ width:60px; text-align:center; }
        
        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .drawer{ width:100vw; right:-100vw; }
            .drawer-image{ height:200px; object-fit:contain; }
            .drawer-image-gallery{ gap:6px; }
            .drawer-image-thumb{ width:70px; height:70px; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('our-builds'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Our Builds'); ?>

        <div class="container-fluid p-4">
            <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    Project added successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    Project updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            

            <!-- Search toolbar -->
            <div class="toolbar mb-4">
                <div class="row-top">
                    <form class="d-flex flex-grow-1" method="get">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($filters['name']); ?>" placeholder="Search projects by name">
                        </div>
                        <button class="btn ms-2" type="submit" style="background-color: #ef4444; border-color: #ef4444; color: white;">Search</button>
                        <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                    </form>
                    <a href="add.php" class="btn-animated-add noselect">
                        <span class="text">Add Project</span>
                        <span class="icon">
                            <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
                            <span class="buttonSpan">+</span>
                        </span>
                    </a>
                </div>
            </div>

            <!-- Projects table -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="h6 mb-0">Our Builds</div>
                    </div>
                    <div class="table-responsive table-wrap">
                        <table class="table table-hover table-inner" id="projectsTable">
                            <thead>
                                <tr>
                                    <th class="order-cell" style="min-width:60px;">Order</th>
                                    <th style="min-width:80px;">Image</th>
                                    <th style="min-width:200px;">Name</th>
                                    <th style="min-width:300px;">Description</th>
                                    <th style="min-width:150px;">Location</th>
                                    <th style="min-width:100px;">Created</th>
                                    <th class="actions-header" style="min-width:150px; width:150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $projects->fetch_assoc()): ?>
                                <tr 
                                    data-id="<?php echo (int)$row['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                                    data-order="<?php echo $row['order_id']; ?>"
                                >
                                    <td class="order-cell">
                                        <div class="drag-handle" title="Drag to reorder">
                                            <i class="fa-solid fa-grip-vertical"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($row['first_image']): ?>
                                            <div class="project-image-container">
                                                <img src="../../uploads/projects/<?php echo htmlspecialchars($row['first_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                                     class="project-image">
                                                <?php if ($row['image_count'] > 1): ?>
                                                    <div class="image-count-badge">
                                                        <i class="fa-solid fa-images"></i>
                                                        <span><?php echo (int)$row['image_count']; ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="project-image d-flex align-items-center justify-content-center bg-light text-muted">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="project-description text-muted"><?php echo htmlspecialchars($row['description'] ?: '—'); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['location'] ?: '—'); ?></td>
                                    <td class="text-muted"><?php echo $row['created_at']; ?></td>
                                    <td class="text-end actions-cell">
                                        <button class="modern-btn view-btn btn-view" title="View Project" data-project-id="<?php echo (int)$row['id']; ?>">
                                            <span class="icon"><i class="fa-solid fa-eye"></i></span>
                                        </button>
                                        <a href="edit.php?id=<?php echo (int)$row['id']; ?>" class="modern-btn edit-btn" title="Edit Project">
                                            <span class="icon"><i class="fa-solid fa-pen"></i></span>
                                        </a>
                                        <button class="modern-btn delete-btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteProjectModal" title="Delete Project" data-project-id="<?php echo (int)$row['id']; ?>">
                                            <span class="icon"><i class="fa-solid fa-trash"></i></span>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php 
                        $qs = [];
                        foreach (['name','location'] as $k) { if ($filters[$k] !== '') { $qs[$k] = $filters[$k]; } }
                        $buildPageUrl = function($p) use ($qs) {
                            $qs['page'] = $p;
                            return 'index.php?' . http_build_query($qs);
                        };
                    ?>
                    <nav aria-label="Projects pagination" class="mt-3">
                        <ul class="pagination mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $page <= 1 ? '#' : $buildPageUrl($page-1); ?>" tabindex="-1">Previous</a>
                            </li>
                            <?php 
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                if ($start > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="' . $buildPageUrl(1) . '">1</a></li>';
                                    if ($start > 2) { echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
                                }
                                for ($i = $start; $i <= $end; $i++) {
                                    $active = $i === $page ? ' active' : '';
                                    echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $buildPageUrl($i) . '">' . $i . '</a></li>';
                                }
                                if ($end < $totalPages) {
                                    if ($end < $totalPages - 1) { echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
                                    echo '<li class="page-item"><a class="page-link" href="' . $buildPageUrl($totalPages) . '">' . $totalPages . '</a></li>';
                                }
                            ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $page >= $totalPages ? '#' : $buildPageUrl($page+1); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- Delete Project Modal -->
            <div class="modal fade" id="deleteProjectModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Delete Project</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="post" action="#">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" id="delete-id">
                            <div class="modal-body">
                                <p class="mb-0">Are you sure you want to delete <span class="fw-semibold" id="delete-name">this project</span>?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn-animated-delete noselect">
                                    <span class="text">Delete</span>
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

            <!-- Project View Drawer -->
            <div class="drawer" id="projectDrawer">
                <div class="drawer-header">
                    <h6 class="mb-0" id="drawerTitle">Project Details</h6>
                    <div class="drawer-actions">
                        <button class="btn btn-sm btn-outline-secondary" onclick="closeDrawer()">Close</button>
                    </div>
                </div>
                <div class="drawer-body" id="drawerBody">
                    <!-- Project details will be loaded here -->
                </div>
            </div>
            <div class="drawer-backdrop" id="drawerBackdrop" onclick="closeDrawer()"></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Performance optimized drawer system
        let drawerCache = new Map();
        let isDrawerOpening = false;
        let currentProjectId = null;
        let abortController = null;

        // Pre-cache DOM elements
        const drawer = document.getElementById('projectDrawer');
        const backdrop = document.getElementById('drawerBackdrop');
        const drawerBody = document.getElementById('drawerBody');
        const drawerTitle = document.getElementById('drawerTitle');

        function openDrawer(projectId) {
            // Prevent multiple simultaneous opens
            if (isDrawerOpening || currentProjectId === projectId) return;
            
            // Cancel any pending request
            if (abortController) {
                abortController.abort();
            }
            
            isDrawerOpening = true;
            currentProjectId = projectId;
            
            // Open drawer immediately for better UX
            drawer.classList.add('open');
            backdrop.classList.add('open');
            
            // Check cache first
            if (drawerCache.has(projectId)) {
                renderProjectDetails(drawerCache.get(projectId));
                isDrawerOpening = false;
                return;
            }
            
            // Show loading state
            drawerBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading...</p></div>';
            
            // Create new abort controller
            abortController = new AbortController();
            
            // Fetch with timeout
            const timeoutId = setTimeout(() => abortController.abort(), 8000);
            
            fetch(`get_project_details.php?id=${projectId}`, {
                signal: abortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            })
            .then(response => {
                clearTimeout(timeoutId);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success && currentProjectId === projectId) {
                    drawerCache.set(projectId, data);
                    renderProjectDetails(data);
                } else {
                    throw new Error(data.message || 'Failed to load project details');
                }
            })
            .catch(error => {
                if (error.name !== 'AbortError' && currentProjectId === projectId) {
                    console.error('Error loading project:', error);
                    drawerBody.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            Unable to load project details. Please try again.
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="retryLoad(${projectId})">
                                    <i class="fa-solid fa-refresh me-1"></i>Retry
                                </button>
                            </div>
                        </div>`;
                }
            })
            .finally(() => {
                clearTimeout(timeoutId);
                isDrawerOpening = false;
                abortController = null;
            });
        }

        function renderProjectDetails(data) {
            if (!data || !data.project) return;
            
            const project = data.project;
            const images = data.images || [];
            
            drawerTitle.textContent = project.name || 'Project Details';
            
            // Build HTML efficiently
            const content = `
                ${images.length > 0 ? `
                    <div class="project-detail">
                        <div class="label">Project Images (${images.length})</div>
                        <img src="../../uploads/projects/${images[0].image_filename}" 
                             alt="Project Image" 
                             class="drawer-image" 
                             id="mainImage"
                             loading="lazy">
                        ${images.length > 1 ? `
                            <div class="drawer-image-gallery" id="gallery-${project.id}">
                                ${images.slice(0, 4).map((img, index) => `
                                    <img src="../../uploads/projects/${img.image_filename}" 
                                         alt="Project Image ${index + 1}" 
                                         class="drawer-image-thumb ${index === 0 ? 'active' : ''}" 
                                         data-image-filename="${img.image_filename}"
                                         data-index="${index}"
                                         loading="lazy">
                                `).join('')}
                                ${images.length > 4 ? `
                                    <div class="drawer-image-thumb more-images-btn" 
                                         id="more-tile-${project.id}"
                                         data-prop-id="${project.id}"
                                         data-total="${images.length}" 
                                         data-visible="4"
                                         onclick="showMoreImages(${project.id})">
                                        <div class="more-images-content">
                                            <i class="fa-solid fa-plus"></i>
                                            <span class="more-count">+${images.length - 4}</span>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                    <div class="divider"></div>
                ` : ''}
                
                <div class="project-detail">
                    <div class="label">Project Name</div>
                    <div class="value">${escapeHtml(project.name) || '—'}</div>
                </div>
                ${project.description ? `
                    <div class="project-detail">
                        <div class="label">Description</div>
                        <div class="value">${escapeHtml(project.description)}</div>
                    </div>
                ` : ''}
                <div class="project-detail">
                    <div class="label">Location</div>
                    <div class="value">
                        <i class="fa-solid fa-location-dot"></i>
                        ${escapeHtml(project.location) || 'Not specified'}
                    </div>
                </div>
                <div class="project-detail">
                    <div class="label">Display Order</div>
                    <div class="value">
                        <span class="badge" style="background-color: #ef4444;">${project.order_id}</span>
                    </div>
                </div>
                <div class="divider"></div>
                <div class="two-col">
                    <div class="project-detail">
                        <div class="label">Created</div>
                        <div class="value">${formatDate(project.created_at)}</div>
                    </div>
                    <div class="project-detail">
                        <div class="label">Last Updated</div>
                        <div class="value">${formatDate(project.updated_at)}</div>
                    </div>
                </div>
                <div class="divider"></div>
                <div class="project-detail">
                    <div class="label">Project ID</div>
                    <div class="value">#${project.id}</div>
                </div>
                <div class="project-detail">
                    <div class="label">Total Images</div>
                    <div class="value">${images.length}</div>
                </div>
                <div class="divider"></div>
                <div class="project-detail">
                    <div class="label">Actions</div>
                    <div class="value">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="view.php?id=${project.id}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fa-solid fa-eye me-1"></i>View Full Page
                            </a>
                            <a href="edit.php?id=${project.id}" class="btn btn-outline-secondary btn-sm" target="_blank">
                                <i class="fa-solid fa-edit me-1"></i>Edit Project
                            </a>
                        </div>
                    </div>
                </div>
            `;
            
            drawerBody.innerHTML = content;
        }

        function closeDrawer() {
            drawer.classList.remove('open');
            backdrop.classList.remove('open');
            currentProjectId = null;
            
            // Cancel any pending request
            if (abortController) {
                abortController.abort();
                abortController = null;
            }
            
            // Clear cache periodically to prevent memory leaks
            if (drawerCache.size > 15) {
                drawerCache.clear();
            }
        }

        function retryLoad(projectId) {
            drawerCache.delete(projectId);
            isDrawerOpening = false;
            currentProjectId = null;
            openDrawer(projectId);
        }

        // Utility functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            if (!dateString) return '—';
            return new Date(dateString).toLocaleDateString('en-IN', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Progressive "+more" reveal: add one hidden image each click, no Less
        function showMoreImages(projectId) {
            try {
                const gallery = document.getElementById(`gallery-${projectId}`);
                const moreTile = document.getElementById(`more-tile-${projectId}`);
                if (!gallery || !moreTile) return;
                const total = parseInt(moreTile.getAttribute('data-total') || '0', 10);
                let visible = parseInt(moreTile.getAttribute('data-visible') || '0', 10);
                if (visible >= total) { moreTile.remove(); return; }

                const data = drawerCache.get(projectId);
                const images = (data && data.images) ? data.images : [];
                if (!images || images.length <= visible) { moreTile.remove(); return; }

                for (let i = visible; i < total; i++) {
                    const file = images[i].image_filename;
                    const img = document.createElement('img');
                    img.src = `../../uploads/projects/${file}`;
                    img.alt = `Project Image ${i + 1}`;
                    img.className = 'drawer-image-thumb';
                    img.setAttribute('data-image-filename', file);
                    img.setAttribute('data-index', String(i));
                    img.loading = 'lazy';
                    gallery.insertBefore(img, moreTile);
                    img.addEventListener('click', function(){
                        const mainImage = document.getElementById('mainImage');
                        if (mainImage) {
                            mainImage.src = `../../uploads/projects/${file}`;
                            document.querySelectorAll('.drawer-image-thumb').forEach(t => t.classList.remove('active'));
                            img.classList.add('active');
                        }
                    }, { passive: true });
                }
                moreTile.remove();
            } catch {}
        }

        // Drag and Drop functionality
        let draggedElement = null;
        const tbody = document.querySelector('#projectsTable tbody');
        
        // Make rows draggable
        document.querySelectorAll('#projectsTable tbody tr').forEach(row => {
            const dragHandle = row.querySelector('.drag-handle');
            
            dragHandle.addEventListener('mousedown', function(e) {
                draggedElement = row;
                row.classList.add('dragging');
                
                // Add drag events to the document
                document.addEventListener('mousemove', handleDrag);
                document.addEventListener('mouseup', handleDrop);
                
                e.preventDefault();
            });
            
            // Add hover effects for drop zones
            row.addEventListener('mouseenter', function() {
                if (draggedElement && draggedElement !== row) {
                    row.classList.add('drag-over');
                }
            });
            
            row.addEventListener('mouseleave', function() {
                row.classList.remove('drag-over');
            });
        });
        
        function handleDrag(e) {
            if (!draggedElement) return;
            
            // Find the row under the cursor
            const elementBelow = document.elementFromPoint(e.clientX, e.clientY);
            const rowBelow = elementBelow ? elementBelow.closest('tr') : null;
            
            // Remove all drag-over classes
            document.querySelectorAll('#projectsTable tbody tr').forEach(row => {
                row.classList.remove('drag-over');
            });
            
            // Add drag-over to the row below cursor
            if (rowBelow && rowBelow !== draggedElement) {
                rowBelow.classList.add('drag-over');
            }
        }
        
        function handleDrop(e) {
            if (!draggedElement) return;
            
            // Find the row under the cursor
            const elementBelow = document.elementFromPoint(e.clientX, e.clientY);
            const targetRow = elementBelow ? elementBelow.closest('tr') : null;
            
            // Remove all drag classes
            document.querySelectorAll('#projectsTable tbody tr').forEach(row => {
                row.classList.remove('dragging', 'drag-over');
            });
            
            // If we have a valid target, reorder
            if (targetRow && targetRow !== draggedElement) {
                const draggedIndex = Array.from(tbody.children).indexOf(draggedElement);
                const targetIndex = Array.from(tbody.children).indexOf(targetRow);
                
                if (draggedIndex < targetIndex) {
                    // Moving down - insert after target
                    targetRow.parentNode.insertBefore(draggedElement, targetRow.nextSibling);
                } else {
                    // Moving up - insert before target
                    targetRow.parentNode.insertBefore(draggedElement, targetRow);
                }
                
                // Update order in database
                updateProjectOrder();
            }
            
            // Clean up
            draggedElement = null;
            document.removeEventListener('mousemove', handleDrag);
            document.removeEventListener('mouseup', handleDrop);
        }
        
        function updateProjectOrder() {
            const rows = document.querySelectorAll('#projectsTable tbody tr');
            const projectOrders = [];
            
            rows.forEach((row, index) => {
                const projectId = row.getAttribute('data-id');
                projectOrders.push({
                    id: parseInt(projectId),
                    order_id: index + 1
                });
                row.setAttribute('data-order', index + 1);
            });
            
            // Send AJAX request to update order
            const formData = new FormData();
            formData.append('action', 'reorder_projects');
            formData.append('project_orders', JSON.stringify(projectOrders));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('Project order updated successfully!', 'success');
                } else {
                    showAlert('Failed to update project order: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                showAlert('Error updating project order: ' + error.message, 'danger');
            });
        }
        
        function showAlert(message, type) {
            const alertContainer = document.getElementById('jsAlertContainer');
            const alertId = 'alert-' + Date.now();
            
            const alertHtml = `
                <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            alertContainer.insertAdjacentHTML('beforeend', alertHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                const alertElement = document.getElementById(alertId);
                if (alertElement) {
                    alertElement.remove();
                }
            }, 5000);
        }

        // Event delegation for better performance
        document.addEventListener('click', function(event) {
            // Handle view button clicks
            const viewBtn = event.target.closest('.btn-view');
            if (viewBtn) {
                const tr = viewBtn.closest('tr');
                const projectId = tr?.dataset?.id;
                if (projectId && !isDrawerOpening) {
                    openDrawer(parseInt(projectId));
                }
                return;
            }
            
            // Handle image thumbnail clicks
            const thumb = event.target.closest('.drawer-image-thumb');
            if (thumb && !thumb.classList.contains('more-images-btn')) {
                const imageFilename = thumb.dataset.imageFilename;
                const mainImage = document.getElementById('mainImage');
                if (imageFilename && mainImage) {
                    // Update main image
                    mainImage.src = `../../uploads/projects/${imageFilename}`;
                    
                    // Update active state
                    document.querySelectorAll('.drawer-image-thumb').forEach(t => t.classList.remove('active'));
                    thumb.classList.add('active');
                }
                return;
            }
            
            // Handle delete button clicks
            const deleteBtn = event.target.closest('.btn-delete');
            if (deleteBtn) {
                const tr = deleteBtn.closest('tr');
                const deleteId = document.getElementById('delete-id');
                const deleteName = document.getElementById('delete-name');
                if (deleteId) deleteId.value = tr.dataset.id || '';
                if (deleteName) deleteName.textContent = tr.dataset.name || 'this project';
                return;
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && drawer.classList.contains('open')) {
                closeDrawer();
            }
        });

        // Clean up on page unload
        window.addEventListener('beforeunload', function() {
            if (abortController) {
                abortController.abort();
            }
            drawerCache.clear();
        });

        // Modern Button Ripple Effect
        function createRipple(event) {
            const button = event.currentTarget;
            const circle = document.createElement('span');
            const diameter = Math.max(button.clientWidth, button.clientHeight);
            const radius = diameter / 2;

            circle.style.width = circle.style.height = `${diameter}px`;
            circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
            circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
            circle.classList.add('ripple');

            const ripple = button.querySelector('.ripple');
            if (ripple) {
                ripple.remove();
            }

            button.appendChild(circle);
        }

        // Add event listeners for ripple effect to modern buttons
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.modern-btn').forEach(btn => {
                btn.addEventListener('click', createRipple);
            });
        });

        // Enhanced button click animations
        document.addEventListener('click', function(event) {
            const modernBtn = event.target.closest('.modern-btn');
            if (modernBtn) {
                modernBtn.style.animation = 'none';
                modernBtn.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    modernBtn.style.animation = '';
                    modernBtn.style.transform = '';
                }, 150);
            }
        });
    </script>
</body>
</html>
