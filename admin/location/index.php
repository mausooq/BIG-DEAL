<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }


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

// Handle location operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mysqli = db();

    if ($action === 'add_location') {
        $place_name = trim($_POST['place_name'] ?? '');

        // Validation
        if (!$place_name) {
            $_SESSION['error_message'] = 'Place name is required.';
        } else {
            // Handle image upload
            $image_filename = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'location_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('INSERT INTO locations (place_name, image) VALUES (?, ?)');
                if ($stmt) {
                    $stmt->bind_param('ss', $place_name, $image_filename);
                    if ($stmt->execute()) {
                        $location_id = $mysqli->insert_id;
                        logActivity($mysqli, 'Added location', 'Place: ' . $place_name . ', ID: ' . $location_id);
                        $_SESSION['success_message'] = 'Location added successfully!';
                    } else {
                        $_SESSION['error_message'] = 'Failed to add location: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'edit_location') {
        $id = (int)($_POST['id'] ?? 0);
        $place_name = trim($_POST['place_name'] ?? '');
        $current_image = $_POST['current_image'] ?? '';

        if (!$id || !$place_name) {
            $_SESSION['error_message'] = 'ID and place name are required.';
        } else {
            $image_filename = $current_image;
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'location_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('UPDATE locations SET place_name = ?, image = ? WHERE id = ?');
                $stmt->bind_param('ssi', $place_name, $image_filename, $id);
                
                if ($stmt) {
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            logActivity($mysqli, 'Updated location', 'Place: ' . $place_name . ', ID: ' . $id);
                            $_SESSION['success_message'] = 'Location updated successfully!';
                        } else {
                            $_SESSION['error_message'] = 'No changes made or location not found.';
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to update location: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'delete_location') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Get location details for logging
            $stmt = $mysqli->prepare('SELECT place_name FROM locations WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $place_name = $result ? $result['place_name'] : 'Unknown';
            $stmt->close();

            $stmt = $mysqli->prepare('DELETE FROM locations WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        logActivity($mysqli, 'Deleted location', 'Place: ' . $place_name . ', ID: ' . $id);
                        $_SESSION['success_message'] = 'Location deleted successfully!';
                    } else {
                        $_SESSION['error_message'] = 'Location not found.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Failed to delete location: ' . $mysqli->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
            }
        }
    }

    header('Location: index.php');
    exit();
}

// Get recent activity
$mysqli = db();
$recentStates = $mysqli->query("SELECT name, DATE_FORMAT(NOW(),'%b %d, %Y') as created_at FROM states ORDER BY id DESC LIMIT 5");
$recentDistricts = $mysqli->query("SELECT d.name, s.name as state_name, DATE_FORMAT(NOW(),'%b %d, %Y') as created_at FROM districts d LEFT JOIN states s ON d.state_id = s.id ORDER BY d.id DESC LIMIT 5");
$recentCities = $mysqli->query("SELECT c.name, d.name as district_name, s.name as state_name, DATE_FORMAT(NOW(),'%b %d, %Y') as created_at FROM cities c LEFT JOIN districts d ON c.district_id = d.id LEFT JOIN states s ON d.state_id = s.id ORDER BY c.id DESC LIMIT 5");

// Get locations with search, filters and pagination
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$value = $_GET['value'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

// Build WHERE conditions
$conditions = [];

if ($search) {
    $conditions[] = 'place_name LIKE ?';
    $types .= 's';
    $searchParam = '%' . $mysqli->real_escape_string($search) . '%';
    $params[] = $searchParam;
}

// Apply additional filters
if ($filter && $value) {
    switch ($filter) {
        case 'recent':
            $conditions[] = 'created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            break;
        case 'with_images':
            $conditions[] = 'image IS NOT NULL AND image != ""';
            break;
        case 'no_images':
            $conditions[] = '(image IS NULL OR image = "")';
            break;
    }
}

if (!empty($conditions)) {
    $whereClause = ' WHERE ' . implode(' AND ', $conditions);
}

$sql = 'SELECT id, place_name, image, created_at FROM locations' . $whereClause . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$locations = $stmt ? $stmt->get_result() : $mysqli->query('SELECT id, place_name, image, created_at FROM locations ORDER BY created_at DESC LIMIT 10');

// Count
$countSql = 'SELECT COUNT(*) FROM locations' . $whereClause;
$countStmt = $mysqli->prepare($countSql);
if ($countStmt && $types) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt && $countStmt->execute();
$totalCountRow = $countStmt ? $countStmt->get_result()->fetch_row() : [0];
$totalCount = (int)($totalCountRow[0] ?? 0);
$totalPages = (int)ceil($totalCount / $limit);

// Recent
$recentLocations = $mysqli->query("SELECT place_name, DATE_FORMAT(created_at,'%b %d, %Y') as created_at FROM locations ORDER BY created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Management - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        /* Base */
        :root{
            --bg:#F1EFEC;/* page background */
            --card:#ffffff;/* surfaces */
            --muted:#6b7280;/* secondary text */
            --line:#e9eef5;/* borders */
            --brand-dark:#2f2f2f;/* logo dark */
            --primary:#e11d2a;/* logo red accent */
            --primary-600:#b91c1c;/* darker red hover */
            --radius:16px;
        }
        body{ background:var(--bg); color:#111827; }
        /* Sidebar */
        .sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .content{ margin-left:284px; }
        .list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
        .list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
        .list-group-item:hover{ background:#f8fafc; }
        /* Topbar */
        .navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .text-primary{ color:var(--primary)!important; }
        .input-group .form-control{ border-color:var(--line); }
        .input-group-text{ border-color:var(--line); }
        /* Cards */
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
        /* Toolbar */
        .toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
        .toolbar .row-top{ display:flex; gap:12px; align-items:center; }
        .toolbar .row-bottom{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .toolbar .chip{ padding:6px 12px; border:1px solid var(--line); border-radius:9999px; background:#fff; color:#374151; text-decoration:none; font-size:.875rem; }
        .toolbar .chip:hover{ border-color:#d1d5db; }
        .toolbar .chip.active{ background:var(--primary); border-color:var(--primary); color:#fff; }
        .toolbar .divider{ width:1px; height:24px; background:var(--line); margin:0 4px; }
        /* Table */
        .table{ --bs-table-bg:transparent;  border-bottom-width:0 !important; }
        .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; }
        .table tbody tr:hover{ background:#f9fafb; }
        .table td{ vertical-align: middle; }

        /* Table wrapper */
        .table-wrap{ border:0; border-radius:12px; overflow:hidden; background:#fff; }
        /* Inner borders (match Properties) */
        .table-inner thead th{ background:transparent; border-bottom:1px solid var(--line) !important; color:#111827; font-weight:600; }
        .table-inner thead th, .table-inner tbody td{ padding:0; }
        .table-inner tbody td{ border-top:1px solid var(--line) !important; }
        .table-inner td, .table-inner th{ border-left:0; border-right:0; }
        .table-inner tbody tr{ position:static; }
        .table-inner tbody tr::after{ display:none !important; content:none; }
        /* Actions column - sticky like Properties */
        .actions-header{ 
            position:sticky;
            right:0;
            background:#fff;
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
            background:#fff;
            z-index:10;
            padding:8px 8px !important; 
            min-width:120px;
            max-width:120px;
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
        /* Badges */
        .badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
        /* Activity list */
        .list-activity{ max-height:420px; overflow:auto; }
        .sticky-side{ position:sticky; top:96px; }
        .activity-item{ 
            padding:12px; 
            border:1px solid var(--line); 
            border-radius:8px; 
            margin-bottom:8px; 
            background:#fff; 
            transition:all .2s ease;
        }
        .activity-item:hover{ 
            border-color:var(--primary); 
            background:#fef2f2; 
        }
        /* Buttons */
        .btn-primary{ background:var(--primary); border-color:var(--primary); }
        .btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
            .sidebar.open{ left:12px; }
            .content{ margin-left:0; }
        }
        @media (max-width: 575.98px){
            .toolbar .row-top{ flex-direction:column; align-items:stretch; }
            .toolbar .row-bottom{ gap:6px; }
            .actions-cell{ justify-content:center; }
            .actions-cell .btn{ width:24px; height:24px; font-size:0.7rem; margin:0 1px; }
            .table thead th:last-child, .table tbody td:last-child{ text-align:center; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('location'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Location Management'); ?>

        <div class="container-fluid p-4">
            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Total Locations</div>
                                <div class="h4 mb-0"><?php echo $totalCount; ?></div>
                            </div>
                            <div class="text-primary"><i class="fa-solid fa-map-location-dot fa-lg"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">With Images</div>
                                <div class="h4 mb-0"><?php echo fetchScalar("SELECT COUNT(*) FROM locations WHERE image IS NOT NULL AND image != ''"); ?></div>
                            </div>
                            <div class="text-success"><i class="fa-solid fa-image fa-lg"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Recent (7 days)</div>
                                <div class="h4 mb-0"><?php echo fetchScalar("SELECT COUNT(*) FROM locations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"); ?></div>
                            </div>
                            <div class="text-warning"><i class="fa-solid fa-clock fa-lg"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">No Images</div>
                                <div class="h4 mb-0"><?php echo fetchScalar("SELECT COUNT(*) FROM locations WHERE image IS NULL OR image = ''"); ?></div>
                            </div>
                            <div class="text-danger"><i class="fa-solid fa-image-slash fa-lg"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search toolbar -->
            <div class="toolbar mb-4">
                <div class="row-top">
                    <form class="d-flex flex-grow-1" method="get">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search locations by name">
                        </div>
                        <button class="btn btn-primary ms-2" type="submit">Search</button>
                        <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                    </form>
                    <button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                        <span class="text">Add Location</span>
                        <span class="icon">
                            <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
                            <span class="buttonSpan">+</span>
                        </span>
                    </button>
                </div>
                <div class="row-bottom">
                    <a class="chip js-filter active" href="#" data-filter="all" data-value="all" role="button">All Locations</a>
                    <span class="divider"></span>
                    <a class="chip js-filter" href="#" data-filter="recent" data-value="recent" role="button">Recent</a>
                    <a class="chip js-filter" href="#" data-filter="with_images" data-value="with_images" role="button">With Images</a>
                    <a class="chip js-filter" href="#" data-filter="no_images" data-value="no_images" role="button">No Images</a>
                </div>
            </div>


            <!-- Quick Actions -->
            <div class="row g-4 mb-5">
                <div class="col-12">
                    <div class="card quick-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="h6 mb-0">Quick Actions</div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="states/add.php" class="btn btn-outline-primary w-100">
                                        <i class="fa-solid fa-plus me-2"></i>Add State
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="districts/add.php" class="btn btn-outline-primary w-100">
                                        <i class="fa-solid fa-plus me-2"></i>Add District
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="cities/add.php" class="btn btn-outline-primary w-100">
                                        <i class="fa-solid fa-plus me-2"></i>Add City
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="towns/add.php" class="btn btn-outline-primary w-100">
                                        <i class="fa-solid fa-plus me-2"></i>Add Town
                                    </a>
                                </div>
                            </div>

                            <?php if ($totalPages > 1): ?>
                            <nav aria-label="Locations pagination">
                                <ul class="pagination justify-content-center">
                                    <?php 
                                    // Build pagination URL with current filters
                                    $paginationParams = [];
                                    if ($search) $paginationParams['search'] = $search;
                                    if ($filter) $paginationParams['filter'] = $filter;
                                    if ($value) $paginationParams['value'] = $value;
                                    
                                    for ($i = 1; $i <= $totalPages; $i++): 
                                        $paginationParams['page'] = $i;
                                        $paginationUrl = '?' . http_build_query($paginationParams);
                                    ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $paginationUrl; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100 sticky-side">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="h6 mb-0">Recent States</div>
                                <a href="states/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="list-activity">
                                <?php while($state = $recentStates->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($state['name']); ?></div>
                                            <div class="text-muted small"><?php echo $state['created_at']; ?></div>
                                        </div>
                                        <i class="fa-solid fa-map text-primary"></i>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 sticky-side">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="h6 mb-0">Recent Districts</div>
                                <a href="districts/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="list-activity">
                                <?php while($district = $recentDistricts->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($district['name']); ?></div>
                                            <div class="text-muted small"><?php echo $district['state_name']; ?> • <?php echo $district['created_at']; ?></div>
                                        </div>
                                        <i class="fa-solid fa-building text-primary"></i>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 sticky-side">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="h6 mb-0">Recent Cities</div>
                                <a href="cities/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="list-activity">
                                <?php while($city = $recentCities->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($city['name']); ?></div>
                                            <div class="text-muted small"><?php echo $city['district_name']; ?>, <?php echo $city['state_name']; ?> • <?php echo $city['created_at']; ?></div>
                                        </div>
                                        <i class="fa-solid fa-city text-primary"></i>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            // Toggleable chip filters
            document.querySelectorAll('.js-filter').forEach(function(chip){
                chip.addEventListener('click', function(e){
                    e.preventDefault();
                    var filter = this.getAttribute('data-filter');
                    var value = this.getAttribute('data-value');
                    var url = new URL(window.location.href);
                    var params = url.searchParams;
                    
                    // Remove existing filter params
                    params.delete('filter');
                    params.delete('value');
                    
                    // Add new filter if not 'all'
                    if (value !== 'all') {
                        params.set('filter', filter);
                        params.set('value', value);
                    }
                    
                    // Update active state
                    document.querySelectorAll('.js-filter').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Navigate to new URL
                    url.search = params.toString();
                    window.location.href = url.toString();
                });
            });

            // Set active chip based on current URL params
            const urlParams = new URLSearchParams(window.location.search);
            const currentFilter = urlParams.get('filter');
            const currentValue = urlParams.get('value');
            
            if (currentFilter && currentValue) {
                document.querySelectorAll('.js-filter').forEach(chip => {
                    if (chip.getAttribute('data-filter') === currentFilter && chip.getAttribute('data-value') === currentValue) {
                        chip.classList.add('active');
                    } else {
                        chip.classList.remove('active');
                    }
                });
            } else {
                // Default to 'all' if no filter is set
                document.querySelector('.js-filter[data-value="all"]').classList.add('active');
            }

            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function(){
                    const tr = this.closest('tr');
                    const data = JSON.parse(tr.getAttribute('data-location'));
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_place_name').value = data.place_name || '';
                    document.getElementById('edit_current_image').value = data.image || '';
                    
                    // Show current image if exists
                    const preview = document.getElementById('current_image_preview');
                    if (data.image) {
                        preview.innerHTML = `
                            <div class="d-flex align-items-center gap-2">
                                <img src="../../uploads/locations/${data.image}" alt="Current image" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                <div>
                                    <div class="small text-muted">Current image:</div>
                                    <div class="small">${data.image}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        preview.innerHTML = '<div class="text-muted small">No current image</div>';
                    }
                });
            });
        });
    </script>
</body>
</html>