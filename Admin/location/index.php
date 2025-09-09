<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login/');
    exit();
}

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
    $mysqli = db();
    $res = $mysqli->query($sql);
    $row = $res ? $res->fetch_row() : [0];
    return (int)($row[0] ?? 0);
}

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


// Get locations with search and pagination
$mysqli = db();
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
    $whereClause = ' WHERE place_name LIKE ?';
    $types = 's';
    $searchParam = '%' . $mysqli->real_escape_string($search) . '%';
    $params[] = $searchParam;
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
if ($countStmt && $search) {
    $countSearchParam = '%' . $mysqli->real_escape_string($search) . '%';
    $countStmt->bind_param('s', $countSearchParam);
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
    <title>Locations - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
        .quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
        /* Toolbar */
        .toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
        .toolbar .row-top{ display:flex; gap:12px; align-items:center; }
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
        /* Buttons */
        .btn-primary{ background:var(--primary); border-color:var(--primary); }
        .btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
            .sidebar.open{ left:12px; }
            .content{ margin-left:0; }
            .table{ font-size:.9rem; }
        }
        @media (max-width: 575.98px){
            .toolbar .row-top{ flex-direction:column; align-items:stretch; }
            .actions-cell{ justify-content:center; }
            .actions-cell .btn{ width:24px; height:24px; font-size:0.7rem; margin:0 1px; }
            .table thead th:last-child, .table tbody td:last-child{ text-align:center; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('location'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

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
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                        <i class="fa-solid fa-circle-plus me-1"></i>Add Location
                    </button>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card quick-card mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="h6 mb-0">Locations</div>
                            </div>
                            <div class="table-responsive table-wrap">
                                <table class="table table-hover table-inner" id="locationsTable">
                                    <thead>
                                        <tr>
                                            <th style="min-width:80px;">Image</th>
                                            <th style="min-width:200px;">Location Name</th>
                                            <th style="min-width:120px;">Created</th>
                                            <th class="actions-header" style="min-width:120px; width:120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($locations && $locations->num_rows > 0): ?>
                                            <?php while ($location = $locations->fetch_assoc()): ?>
                                                <tr data-location='<?php echo json_encode($location, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                                                    <td>
                                                        <?php if (!empty($location['image'])): ?>
                                                            <img src="../../uploads/locations/<?php echo htmlspecialchars($location['image']); ?>" alt="<?php echo htmlspecialchars($location['place_name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                                        <?php else: ?>
                                                            <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="fa-solid fa-map-location-dot text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="fw-semibold"><?php echo htmlspecialchars($location['place_name']); ?></td>
                                                    <td class="text-muted"><?php echo date('M d, Y', strtotime($location['created_at'])); ?></td>
                                                    <td class="actions-cell">
                                                        <button class="btn btn-sm btn-outline-secondary btn-edit me-1" data-bs-toggle="modal" data-bs-target="#editLocationModal" title="Edit Location"><i class="fa-solid fa-pen"></i></button>
                                                        <button class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteLocationModal" title="Delete Location"><i class="fa-solid fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">
                                                    <i class="fa-solid fa-map-location-dot fa-2x mb-2"></i>
                                                    <div>No locations found</div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($totalPages > 1): ?>
                            <nav aria-label="Locations pagination">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- <div class="col-xl-4">
                    <div class="card h-100 sticky-side">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="h6 mb-0">Recent Locations</div>
                                <span class="badge bg-light text-dark border">Latest</span>
                            </div>
                            <div class="list-activity">
                                <?php while($l = $recentLocations->fetch_assoc()): ?>
                                <div class="item-row d-flex align-items-center justify-content-between" style="padding:10px 12px; border:1px solid var(--line); border-radius:12px; margin-bottom:10px; background:#fff;">
                                    <span class="item-title fw-semibold"><?php echo htmlspecialchars($l['place_name']); ?></span>
                                    <span class="text-muted small"><?php echo $l['created_at']; ?></span>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </div>

    <!-- Add Location Modal -->
    <div class="modal fade" id="addLocationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_location">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Place Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="place_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Location Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload an image for this location (JPG, PNG, GIF, WebP - Max 5MB)</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Location</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Location Modal -->
    <div class="modal fade" id="editLocationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_location">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="current_image" id="edit_current_image">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Place Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="place_name" id="edit_place_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Location Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload a new image to replace the current one (JPG, PNG, GIF, WebP - Max 5MB)</div>
                                <div id="current_image_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Location</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteLocationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the location "<span id="delete_location_name"></span>"?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_location">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="submit" class="btn btn-danger">Delete Location</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
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

            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function(){
                    const tr = this.closest('tr');
                    const data = JSON.parse(tr.getAttribute('data-location'));
                    document.getElementById('delete_id').value = data.id;
                    document.getElementById('delete_location_name').textContent = data.place_name;
                });
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function(){
                document.querySelectorAll('.alert').forEach(a => {
                    try { (new bootstrap.Alert(a)).close(); } catch(e) {}
                });
            }, 5000);
        });
    </script>
</body>
</html>
