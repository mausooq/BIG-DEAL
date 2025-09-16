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

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $mysqli = db();
    try {
        switch ($_POST['action']) {

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) { throw new Exception('Invalid property ID'); }
                // Delete children first to satisfy FKs
                $stmt = $mysqli->prepare('DELETE FROM property_images WHERE property_id = ?');
                $stmt && $stmt->bind_param('i', $id) && $stmt->execute() && $stmt->close();
                $stmt = $mysqli->prepare('DELETE FROM features WHERE property_id = ?');
                $stmt && $stmt->bind_param('i', $id) && $stmt->execute() && $stmt->close();
                // Delete property
                $stmt = $mysqli->prepare('DELETE FROM properties WHERE id = ?');
                $stmt->bind_param('i', $id);
                if (!$stmt->execute()) { throw new Exception('Failed to delete property: ' . $mysqli->error); }
                $stmt->close();
                logActivity($mysqli, 'Deleted property', 'ID: ' . $id);
                $message = 'Property deleted successfully';
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Filters from query string per schema
$filters = [
    'title' => $_GET['title'] ?? '',
    'location' => $_GET['location'] ?? '',
    'listing_type' => $_GET['listing_type'] ?? '',
    'category_id' => isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null,
    'status' => $_GET['status'] ?? '',
];

$mysqli = db();

// Stats
function fetchScalar($sql) { $m = db(); $r = $m->query($sql); $row = $r ? $r->fetch_row() : [0]; return (int)($row[0] ?? 0); }
$totalProperties = fetchScalar("SELECT COUNT(*) FROM properties");
$availableCount = fetchScalar("SELECT COUNT(*) FROM properties WHERE status='Available'");
$soldRentedCount = fetchScalar("SELECT COUNT(*) FROM properties WHERE status IN ('Sold','Rented')");
$featuredCount = fetchScalar("SELECT COUNT(*) FROM features");

// Dropdown data
$categoriesRes = $mysqli->query("SELECT id, name FROM categories ORDER BY name");

// Build query
$where = [];
$types = '';
$params = [];

if ($filters['title'] !== '') { $where[] = 'p.title LIKE ?'; $types .= 's'; $params[] = '%' . $mysqli->real_escape_string($filters['title']) . '%'; }
if ($filters['location'] !== '') { $where[] = 'p.location LIKE ?'; $types .= 's'; $params[] = '%' . $mysqli->real_escape_string($filters['location']) . '%'; }
if ($filters['listing_type'] !== '') { $where[] = 'p.listing_type = ?'; $types .= 's'; $params[] = $filters['listing_type']; }
if ($filters['category_id'] !== null) { $where[] = 'p.category_id = ?'; $types .= 'i'; $params[] = (int)$filters['category_id']; }
if ($filters['status'] !== '') { $where[] = 'p.status = ?'; $types .= 's'; $params[] = $filters['status']; }

// Pagination
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Total count for pagination
$countSql = "SELECT COUNT(*)
            FROM properties p LEFT JOIN categories c ON c.id = p.category_id";
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
$sql = "SELECT p.id, p.title, p.price, p.location, p.landmark, p.area, p.configuration, p.listing_type, p.status, p.category_id,
                DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at,
                c.name AS category_name
         FROM properties p LEFT JOIN categories c ON c.id = p.category_id";
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.created_at DESC LIMIT ?, ?';

$stmt = $mysqli->prepare($sql);
$bindTypes = $types . 'ii';
$bindParams = $params;
$bindParams[] = $offset;
$bindParams[] = $perPage;
if ($stmt) { $stmt->bind_param($bindTypes, ...$bindParams); }
$stmt && $stmt->execute();
$properties = $stmt ? $stmt->get_result() : $mysqli->query("SELECT p.id, p.title, p.price, p.location, p.landmark, p.area, p.configuration, p.listing_type, p.status, DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at, NULL AS category_name FROM properties p ORDER BY p.created_at DESC LIMIT 10 OFFSET 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        body{ background:var(--bg); color:#111827; }
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .table tbody tr:hover{ background:#f9fafb; }
        .table td{ vertical-align: middle; }
        /* Inner borders (match Locations) */
        .table-inner thead th{ background:transparent; border-bottom:1px solid var(--line) !important; color:#111827; font-weight:600 !important; font-size:.875rem !important; }
        .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
        /* Remove inner borders for body cells and override Bootstrap defaults */
        .table-inner td, .table-inner th{ border-left:0; border-right:0; } /*mausooq sooooooooooooooooooooooqqqqq*/
        .badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        /* Actions column - fully integrated with table */
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
        /* Drawer styles */
        .drawer{ position:fixed; top:0; right:-500px; width:500px; height:100vh; background:#fff; box-shadow:-12px 0 24px rgba(0,0,0,.08); transition:right .3s cubic-bezier(0.4, 0.0, 0.2, 1); z-index:1040; }
        .drawer.open{ right:0; }
        .drawer-header{ padding:16px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
        .drawer-body{ padding:16px; overflow:auto; height:calc(100vh - 64px); }
        .drawer-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.3); opacity:0; pointer-events:none; transition:opacity .3s ease; z-index:1035; }
        .drawer-backdrop.open{ opacity:1; pointer-events:auto; }
        .drawer-image{ width:100%; height:250px; object-fit:contain; border-radius:8px; margin-bottom:1rem; background:#f8f9fa; border:1px solid #e9ecef; }
        .drawer-image-gallery{ display:flex; gap:8px; margin-top:1rem; }
        .drawer-image-thumb{ width:90px; height:90px; object-fit:cover; border-radius:6px; cursor:pointer; border:2px solid transparent; transition:all 0.2s ease; flex-shrink:0; }
        .drawer-image-thumb:hover{ border-color:#3b82f6; transform:scale(1.05); }
        .drawer-image-thumb.active{ border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59, 130, 246, 0.2); }
        .drawer-image{ cursor:pointer; transition:transform 0.2s ease; }
        .drawer-image:hover{ transform:scale(1.02); }
        /* More images button styles */
        .more-images-btn{ 
            background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            transition: all 0.2s ease;
            border: 2px solid #3b82f6;
        }
        .more-images-btn:hover{ 
            background: linear-gradient(135deg, #1d4ed8, #1e40af); 
            transform: scale(1.05); 
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .more-images-content{ 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            gap: 2px;
        }
        .more-images-content i{ 
            font-size: 1.2rem; 
        }
        .more-count{ 
            font-size: 0.75rem; 
            font-weight: 600; 
        }
        .remaining-images{ 
            margin-top: 8px; 
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn{ 
            from{ opacity: 0; transform: translateY(-10px); } 
            to{ opacity: 1; transform: translateY(0); } 
        }
        .property-detail{ margin-bottom:1rem; }
        .property-detail .label{ color:var(--muted); font-size:0.875rem; font-weight:600; margin-bottom:0.25rem; }
        .property-detail .value{ font-weight:600; color:#111827; }
        .property-detail .value.badge{ font-weight:500; }
        .property-detail .value.price{ font-size:1.25rem; }
        .property-detail .value.area{ font-size:1.25rem; }
        .property-detail .value.location{ display:flex; align-items:center; gap:0.5rem; }
        .property-detail .value.location i{ color:var(--muted); }
        .divider{ height:1px; background:var(--line); margin:1rem 0; }
        .two-col{ display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
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
            .drawer{ width:100vw; right:-100vw; }
            .drawer-image{ height:200px; object-fit:contain; }
            .drawer-image-gallery{ gap:6px; }
            .drawer-image-thumb{ width:70px; height:70px; }
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
        /* Toolbar */
        .toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
        .toolbar .row-top{ display:flex; gap:12px; align-items:center; }
        .toolbar .row-bottom{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .toolbar .chip{ padding:6px 12px; border:1px solid var(--line); border-radius:9999px; background:#fff; color:#374151; text-decoration:none; font-size:.875rem; }
        .toolbar .chip:hover{ border-color:#d1d5db; }
        .toolbar .chip.active{ background:var(--primary); border-color:var(--primary); color:#fff; }
        .toolbar .divider{ width:1px; height:24px; background:var(--line); margin:0 4px; }
        .input-group-text{ 
            background-color: #fff;
            border-radius: 8px 0 0 8px;
            padding: 0.5rem 0.75rem;
        }
        /* Button consistency */
        .btn{ border-radius:8px; font-weight:500; }
        
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
            background: #f3f4f6; /* grey-100 */
            color: #374151;      /* grey-700 */
            border: 1px solid #e5e7eb; /* grey-200 */
        }

        .view-btn:hover {
            background: #e5e7eb; /* grey-200 */
            border-color: #d1d5db; /* grey-300 */
            color: #374151;
        }

        /* Edit Button - Neutral grey */
        .edit-btn {
            background: #f3f4f6; /* grey-100 */
            color: #374151;      /* grey-700 */
            border: 1px solid #e5e7eb; /* grey-200 */
        }

        .edit-btn:hover {
            background: #e5e7eb; /* grey-200 */
            border-color: #d1d5db; /* grey-300 */
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
        @media (max-width: 575.98px){
            .toolbar .row-top{ flex-direction:column; align-items:stretch; }
            .toolbar .row-bottom{ gap:6px; }
            .actions-cell{ justify-content:flex-start; }
            .table thead th:last-child, .table tbody td:last-child{ text-align:left; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('properties'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Properties'); ?>

        <div class="container-fluid p-4">
            <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    Property added successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    Property updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['export']) && $_GET['export'] == 'no_data'): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    No data to export with the current filters applied.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Stats (match dashboard) -->
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Properties</div>
                                <div class="h4 mb-0"><?php echo $totalProperties; ?></div>
                            </div>
                            <div class="text-primary"><i class="fa-solid fa-building fa-lg"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Available</div>
                                <div class="h4 mb-0"><?php echo $availableCount; ?></div>
                            </div>
                            <div class="text-success"><i class="fa-solid fa-check fa-lg"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Sold/Rented</div>
                                <div class="h4 mb-0"><?php echo $soldRentedCount; ?></div>
                            </div>
                            <div class="text-danger"><i class="fa-solid fa-arrow-trend-down fa-lg"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <a href="../features/index.php" class="btn btn-outline-primary btn-lg">
                            <i class="fa-solid fa-star me-2"></i>Featured
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search toolbar -->
            <div class="toolbar mb-4">
                <div class="row-top">
                    <form class="d-flex flex-grow-1" method="get">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($filters['title']); ?>" placeholder="Search properties by name">
                        </div>
                        <button class="btn btn-primary ms-2" type="submit">Search</button>
                        <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                    </form>
                    <?php 
                    // Build export URL with current filters
                    $exportParams = [];
                    foreach (['title','location','listing_type','status'] as $k) { 
                        if ($filters[$k] !== '') { 
                            $exportParams[$k] = $filters[$k]; 
                        } 
                    }
                    if ($filters['category_id'] !== null) { 
                        $exportParams['category_id'] = (int)$filters['category_id']; 
                    }
                    $exportUrl = 'export.php' . (!empty($exportParams) ? '?' . http_build_query($exportParams) : '');
                    ?>
                    <a href="<?php echo $exportUrl; ?>" class="btn btn-outline-success me-2">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    <a href="add.php" class="btn-animated-add noselect">
                        <span class="text">Add Property</span>
                        <span class="icon">
                            <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
                            <span class="buttonSpan">+</span>
                        </span>
                    </a>
                </div>
                <div class="row-bottom">
                    <?php foreach(['Buy','Rent','PG/Co-living'] as $lt): ?>
                        <?php $isActive = ($filters['listing_type'] ?? '') === $lt; ?>
                        <a class="chip js-filter <?php echo $isActive ? 'active' : ''; ?>" href="#" data-filter="listing_type" data-value="<?php echo htmlspecialchars($lt, ENT_QUOTES); ?>" role="button"><?php echo $lt; ?></a>
                    <?php endforeach; ?>
                    <span class="divider"></span>
                    <?php 
                    // Reset categories result pointer
                    $categoriesRes->data_seek(0);
                    while($pc = $categoriesRes->fetch_assoc()): ?>
                        <?php $isC = (string)($filters['category_id'] ?? '') === (string)$pc['id']; ?>
                        <a class="chip js-filter <?php echo $isC ? 'active' : ''; ?>" href="#" data-filter="category_id" data-value="<?php echo (int)$pc['id']; ?>" role="button"><?php echo htmlspecialchars($pc['name']); ?></a>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Properties table -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="h6 mb-0">Properties</div>
                    </div>
                    <div class="table-responsive table-wrap">
                        <table class="table table-hover table-inner" id="propertiesTable">
                            <thead>
                                <tr>
                                    <th style="min-width:200px;">Name</th>
                                    <th style="min-width:120px;">Category</th>
                                    <th style="min-width:100px;">Listing</th>
                                    <th style="min-width:120px;">Price</th>
                                    <th style="min-width:150px;">Location</th>
                                    <th style="min-width:100px;">Status</th>
                                    <th style="min-width:100px;">Created</th>
                                    <th class="actions-header" style="min-width:150px; width:150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $properties->fetch_assoc()): ?>
                                <tr 
                                    data-id="<?php echo (int)$row['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>"
                                    data-category="<?php echo htmlspecialchars($row['category_name'] ?? '', ENT_QUOTES); ?>"
                                    data-category-id="<?php echo (int)($row['category_id'] ?? 0); ?>"
                                    data-listing="<?php echo htmlspecialchars($row['listing_type'], ENT_QUOTES); ?>"
                                    data-price="<?php echo htmlspecialchars((string)$row['price'], ENT_QUOTES); ?>"
                                    data-location="<?php echo htmlspecialchars($row['location'], ENT_QUOTES); ?>"
                                    data-area="<?php echo htmlspecialchars((string)$row['area'], ENT_QUOTES); ?>"
                                    data-config="<?php echo htmlspecialchars($row['configuration'], ENT_QUOTES); ?>"
                                    data-status="<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>"
                                >
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td>
                                        <?php if ($row['category_name'] && $row['category_id']): ?>
                                            <a href="../categories/index.php?category_id=<?php echo (int)$row['category_id']; ?>" class="badge bg-light text-dark border text-decoration-none">
                                                <?php echo htmlspecialchars($row['category_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-soft"><?php echo htmlspecialchars($row['listing_type']); ?></span></td>
                                    <td>₹<?php echo number_format((float)$row['price']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td>
                                        <?php if ($row['status']==='Available'): ?>
                                            <span class="badge bg-success-subtle text-success border">Available</span>
                                        <?php elseif ($row['status']==='Sold'): ?>
                                            <span class="badge bg-danger-subtle text-danger border">Sold</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border">Rented</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted"><?php echo $row['created_at']; ?></td>
                                    <td class="text-end actions-cell">
                                        <button class="modern-btn view-btn btn-view" title="View Property" data-property-id="<?php echo (int)$row['id']; ?>">
                                            <span class="icon"><i class="fa-solid fa-eye"></i></span>
                                        </button>
                                        <a href="edit.php?id=<?php echo (int)$row['id']; ?>" class="modern-btn edit-btn" title="Edit Property">
                                            <span class="icon"><i class="fa-solid fa-pen"></i></span>
                                        </a>
                                        <button class="modern-btn delete-btn btn-delete" data-bs-toggle="modal" data-bs-target="#deletePropertyModal" title="Delete Property" data-property-id="<?php echo (int)$row['id']; ?>">
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
                        foreach (['title','location','listing_type','status'] as $k) { if ($filters[$k] !== '') { $qs[$k] = $filters[$k]; } }
                        if ($filters['category_id'] !== null) { $qs['category_id'] = (int)$filters['category_id']; }
                        $buildPageUrl = function($p) use ($qs) {
                            $qs['page'] = $p;
                            return 'index.php?' . http_build_query($qs);
                        };
                    ?>
                    <nav aria-label="Properties pagination" class="mt-3">
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

            <!-- Delete Property Modal (placeholder) -->
            <div class="modal fade" id="deletePropertyModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Delete Property</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="post" action="#">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" id="delete-id">
                            <div class="modal-body">
                                <p class="mb-0">Are you sure you want to delete <span class="fw-semibold" id="delete-title">this property</span>?</p>
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

            <!-- Property View Drawer -->
            <div class="drawer" id="propertyDrawer">
                <div class="drawer-header">
                    <h6 class="mb-0" id="drawerTitle">Property Details</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="closeDrawer()">Close</button>
                </div>
                <div class="drawer-body" id="drawerBody">
                    <!-- Property details will be loaded here -->
                </div>
            </div>
            <div class="drawer-backdrop" id="drawerBackdrop" onclick="closeDrawer()"></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggleable chip filters for listing_type and category_id
        document.addEventListener('DOMContentLoaded', function(){
            document.querySelectorAll('.js-filter').forEach(function(chip){
                chip.addEventListener('click', function(e){
                    e.preventDefault();
                    var key = this.getAttribute('data-filter');
                    var val = this.getAttribute('data-value');
                    var url = new URL(window.location.href);
                    var params = url.searchParams;
                    var current = params.get(key);
                    if (current && current.toString() === val.toString()) {
                        params.delete(key);
                    } else {
                        params.set(key, val);
                    }
                    url.search = params.toString();
                    window.location.href = url.toString();
                });
            });
        });

        // Performance optimized drawer system
        let drawerCache = new Map();
        let isDrawerOpening = false;
        let currentPropertyId = null;
        let abortController = null;

        // Pre-cache DOM elements
        const drawer = document.getElementById('propertyDrawer');
        const backdrop = document.getElementById('drawerBackdrop');
        const drawerBody = document.getElementById('drawerBody');
        const drawerTitle = document.getElementById('drawerTitle');

        function openDrawer(propertyId) {
            // Prevent multiple simultaneous opens
            if (isDrawerOpening || currentPropertyId === propertyId) return;
            
            // Cancel any pending request
            if (abortController) {
                abortController.abort();
            }
            
            isDrawerOpening = true;
            currentPropertyId = propertyId;
            
            // Open drawer immediately for better UX
            drawer.classList.add('open');
            backdrop.classList.add('open');
            
            // Check cache first
            if (drawerCache.has(propertyId)) {
                renderPropertyDetails(drawerCache.get(propertyId));
                isDrawerOpening = false;
                return;
            }
            
            // Show loading state
            drawerBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading...</p></div>';
            
            // Create new abort controller
            abortController = new AbortController();
            
            // Fetch with timeout
            const timeoutId = setTimeout(() => abortController.abort(), 8000);
            
            fetch(`get_property_details.php?id=${propertyId}`, {
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
                if (data.success && currentPropertyId === propertyId) {
                    drawerCache.set(propertyId, data);
                    renderPropertyDetails(data);
                } else {
                    throw new Error(data.message || 'Failed to load property details');
                }
            })
            .catch(error => {
                if (error.name !== 'AbortError' && currentPropertyId === propertyId) {
                    console.error('Error loading property:', error);
                    drawerBody.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            Unable to load property details. Please try again.
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="retryLoad(${propertyId})">
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

        function renderPropertyDetails(data) {
            if (!data || !data.property) return;
            
            const property = data.property;
            const images = data.images || [];
            
            drawerTitle.textContent = property.title || 'Property Details';
            
             // Build HTML efficiently
             const content = `
                 ${images.length > 0 ? `
                     <div class="property-detail">
                         <div class="label">Property Images (${images.length})</div>
                         <img src="../../uploads/properties/${images[0].image_url}" 
                              alt="Property Image" 
                              class="drawer-image" 
                              id="mainImage"
                              loading="lazy">
                         ${images.length > 1 ? `
                             <div class="drawer-image-gallery">
                                 ${images.slice(0, 4).map((img, index) => `
                                     <img src="../../uploads/properties/${img.image_url}" 
                                          alt="Property Image ${index + 1}" 
                                          class="drawer-image-thumb ${index === 0 ? 'active' : ''}" 
                                          data-image-url="${img.image_url}"
                                          data-index="${index}"
                                          loading="lazy">
                                 `).join('')}
                                 ${images.length > 4 ? `
                                     <div class="drawer-image-thumb more-images-btn" 
                                          data-total="${images.length}" 
                                          data-remaining="${images.length - 4}"
                                          onclick="showMoreImages(${property.id})">
                                         <div class="more-images-content">
                                             <i class="fa-solid fa-plus"></i>
                                             <span class="more-count">+${images.length - 4}</span>
                                         </div>
                                     </div>
                                 ` : ''}
                             </div>
                             ${images.length > 4 ? `
                                 <div class="drawer-image-gallery remaining-images" id="remaining-images-${property.id}" style="display: none;">
                                     ${images.slice(4).map((img, index) => `
                                         <img src="../../uploads/properties/${img.image_url}" 
                                              alt="Property Image ${index + 5}" 
                                              class="drawer-image-thumb" 
                                              data-image-url="${img.image_url}"
                                              data-index="${index + 4}"
                                              loading="lazy">
                                     `).join('')}
                                 </div>
                             ` : ''}
                         ` : ''}
                     </div>
                     <div class="divider"></div>
                 ` : ''}
                
                <div class="property-detail">
                    <div class="label">Category</div>
                    <div class="value badge bg-light text-dark border">${escapeHtml(property.category_name) || '—'}</div>
                </div>
                <div class="property-detail">
                    <div class="label">Listing</div>
                    <div class="value badge badge-soft">${escapeHtml(property.listing_type) || ''}</div>
                </div>
                <div class="two-col">
                    <div class="property-detail">
                        <div class="label">Price</div>
                        <div class="value price">₹${formatNumber(property.price || 0)}</div>
                    </div>
                    <div class="property-detail">
                        <div class="label">Area</div>
                        <div class="value area">${escapeHtml(property.area) || '—'} sqft</div>
                    </div>
                </div>
                <div class="property-detail">
                    <div class="label">Location</div>
                    <div class="value location">
                        <i class="fa-solid fa-location-dot"></i>
                        ${escapeHtml(property.location) || ''} ${property.landmark ? '(' + escapeHtml(property.landmark) + ')' : ''}
                    </div>
                </div>
                <div class="divider"></div>
                <div class="two-col">
                    <div class="property-detail">
                        <div class="label">Config</div>
                        <div class="value">${escapeHtml(property.configuration) || '—'}</div>
                    </div>
                    <div class="property-detail">
                        <div class="label">Furniture</div>
                        <div class="value">${escapeHtml(property.furniture_status) || '—'}</div>
                    </div>
                    <div class="property-detail">
                        <div class="label">Ownership</div>
                        <div class="value">${escapeHtml(property.ownership_type) || '—'}</div>
                    </div>
                    <div class="property-detail">
                        <div class="label">Facing</div>
                        <div class="value">${escapeHtml(property.facing) || '—'}</div>
                    </div>
                    <div class="property-detail">
                        <div class="label">Parking</div>
                        <div class="value">${escapeHtml(property.parking) || '—'}</div>
                    </div>
                    <div class="property-detail">
                        <div class="label">Balcony</div>
                        <div class="value">${property.balcony || 0}</div>
                    </div>
                </div>
                ${property.description ? `
                    <div class="divider"></div>
                    <div class="property-detail">
                        <div class="label">Description</div>
                        <div class="value">${escapeHtml(property.description).substring(0, 300)}${property.description.length > 300 ? '...' : ''}</div>
                    </div>
                ` : ''}
                ${property.map_embed_link ? `
                    <div class="divider"></div>
                    <div class="property-detail">
                        <div class="label">Location Map</div>
                        <div class="value">
                            <div class="drawer-map-container" style="position: relative; width: 100%; height: 200px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 8px;">
                                <iframe 
                                    src="${escapeHtml(property.map_embed_link)}" 
                                    width="100%" 
                                    height="100%" 
                                    style="border:0; border-radius: 8px;" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                Interactive map showing property location
                            </small>
                        </div>
                    </div>
                ` : ''}
                <div class="divider"></div>
                <div class="property-detail">
                    <div class="label">Property ID</div>
                    <div class="value">#${property.id}</div>
                </div>
                <div class="property-detail">
                    <div class="label">Status</div>
                    <div class="value">
                        <span class="badge ${getStatusBadgeClass(property.status)}">${escapeHtml(property.status)}</span>
                    </div>
                </div>
                <div class="property-detail">
                    <div class="label">Created</div>
                    <div class="value">${formatDate(property.created_at)}</div>
                </div>
                <div class="divider"></div>
                <div class="property-detail">
                    <div class="label">Actions</div>
                    <div class="value">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="view.php?id=${property.id}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fa-solid fa-eye me-1"></i>View Details
                            </a>
                            <a href="edit.php?id=${property.id}" class="btn-animated-confirm noselect" target="_blank">
                                <span class="text">Edit Property</span>
                                <span class="icon">
                                    <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"></path>
                                    </svg>
                                </span>
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
            currentPropertyId = null;
            
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

        function retryLoad(propertyId) {
            drawerCache.delete(propertyId);
            isDrawerOpening = false;
            currentPropertyId = null;
            openDrawer(propertyId);
        }

        // Utility functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('en-IN').format(num);
        }

        function formatDate(dateString) {
            if (!dateString) return '—';
            return new Date(dateString).toLocaleDateString('en-IN', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

         function getStatusBadgeClass(status) {
             switch (status) {
                 case 'Available': return 'bg-success';
                 case 'Sold': return 'bg-danger';
                 case 'Rented': return 'bg-warning';
                 default: return 'bg-secondary';
             }
         }

         // Function to show more images
         function showMoreImages(propertyId) {
             const remainingImagesDiv = document.getElementById(`remaining-images-${propertyId}`);
             const moreBtn = document.querySelector(`[data-total][onclick="showMoreImages(${propertyId})"]`);
             
             if (remainingImagesDiv && moreBtn) {
                 if (remainingImagesDiv.style.display === 'none') {
                     remainingImagesDiv.style.display = 'flex';
                     moreBtn.innerHTML = '<div class="more-images-content"><i class="fa-solid fa-minus"></i><span class="more-count">Less</span></div>';
                     moreBtn.setAttribute('onclick', `hideMoreImages(${propertyId})`);
                 }
             }
         }

         // Function to hide more images
         function hideMoreImages(propertyId) {
             const remainingImagesDiv = document.getElementById(`remaining-images-${propertyId}`);
             const moreBtn = document.querySelector(`[data-total][onclick="hideMoreImages(${propertyId})"]`);
             
             if (remainingImagesDiv && moreBtn) {
                 remainingImagesDiv.style.display = 'none';
                 const remainingCount = moreBtn.getAttribute('data-remaining');
                 moreBtn.innerHTML = `<div class="more-images-content"><i class="fa-solid fa-plus"></i><span class="more-count">+${remainingCount}</span></div>`;
                 moreBtn.setAttribute('onclick', `showMoreImages(${propertyId})`);
             }
         }


        // Event delegation for better performance
        document.addEventListener('click', function(event) {
            // Handle view button clicks
            const viewBtn = event.target.closest('.btn-view');
            if (viewBtn) {
                const tr = viewBtn.closest('tr');
                const propertyId = tr?.dataset?.id;
                if (propertyId && !isDrawerOpening) {
                    openDrawer(parseInt(propertyId));
                }
                return;
            }
            
             // Handle image thumbnail clicks
             const thumb = event.target.closest('.drawer-image-thumb');
             if (thumb && !thumb.classList.contains('more-images-btn')) {
                 const imageUrl = thumb.dataset.imageUrl;
                 const mainImage = document.getElementById('mainImage');
                 if (imageUrl && mainImage) {
                     // Update main image
                     mainImage.src = `../../uploads/properties/${imageUrl}`;
                     
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
                const deleteTitle = document.getElementById('delete-title');
                if (deleteId) deleteId.value = tr.dataset.id || '';
                if (deleteTitle) deleteTitle.textContent = tr.dataset.title || 'this property';
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
