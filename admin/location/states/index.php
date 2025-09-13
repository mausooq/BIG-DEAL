<?php
require_once __DIR__ . '/../../auth.php';

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

// Handle state operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mysqli = db();

    if ($action === 'add_state') {
        $name = trim($_POST['name'] ?? '');

        // Validation
        if (!$name) {
            $_SESSION['error_message'] = 'State name is required.';
        } else {
            // Handle image upload
            $image_filename = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'state_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
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
                $stmt = $mysqli->prepare('INSERT INTO states (name, image_url) VALUES (?, ?)');
                if ($stmt) {
                    $stmt->bind_param('ss', $name, $image_filename);
                    if ($stmt->execute()) {
                        $state_id = $mysqli->insert_id;
                        logActivity($mysqli, 'Added state', 'Name: ' . $name . ', ID: ' . $state_id);
                        $_SESSION['success_message'] = 'State added successfully!';
                    } else {
                        $_SESSION['error_message'] = 'Failed to add state: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'edit_state') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $current_image = $_POST['current_image'] ?? '';

        if (!$id || !$name) {
            $_SESSION['error_message'] = 'ID and state name are required.';
        } else {
            $image_filename = $current_image;
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'state_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
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
                $stmt = $mysqli->prepare('UPDATE states SET name = ?, image_url = ? WHERE id = ?');
                $stmt->bind_param('ssi', $name, $image_filename, $id);
                
                if ($stmt) {
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            logActivity($mysqli, 'Updated state', 'Name: ' . $name . ', ID: ' . $id);
                            $_SESSION['success_message'] = 'State updated successfully!';
                        } else {
                            $_SESSION['error_message'] = 'No changes made or state not found.';
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to update state: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'delete_state') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Get state details for logging
            $stmt = $mysqli->prepare('SELECT name FROM states WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $state_name = $result ? $result['name'] : 'Unknown';
            $stmt->close();

            $stmt = $mysqli->prepare('DELETE FROM states WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        logActivity($mysqli, 'Deleted state', 'Name: ' . $state_name . ', ID: ' . $id);
                        $_SESSION['success_message'] = 'State deleted successfully!';
                    } else {
                        $_SESSION['error_message'] = 'State not found.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Failed to delete state: ' . $mysqli->error;
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

// Get states with search and pagination
$mysqli = db();
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
    $whereClause = ' WHERE name LIKE ?';
    $types = 's';
    $searchParam = '%' . $mysqli->real_escape_string($search) . '%';
    $params[] = $searchParam;
}

$sql = 'SELECT id, name, image_url FROM states' . $whereClause . ' ORDER BY name ASC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$states = $stmt ? $stmt->get_result() : $mysqli->query('SELECT id, name, image_url FROM states ORDER BY name ASC LIMIT 10');

// Count
$countSql = 'SELECT COUNT(*) FROM states' . $whereClause;
$countStmt = $mysqli->prepare($countSql);
if ($countStmt && $search) {
    $countSearchParam = '%' . $mysqli->real_escape_string($search) . '%';
    $countStmt->bind_param('s', $countSearchParam);
}
$countStmt && $countStmt->execute();
$totalCountRow = $countStmt ? $countStmt->get_result()->fetch_row() : [0];
$totalCount = (int)($totalCountRow[0] ?? 0);
$totalPages = (int)ceil($totalCount / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>States - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../../assets/css/animated-buttons.css" rel="stylesheet">
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
    <?php require_once __DIR__ . '/../../components/sidebar.php'; renderAdminSidebar('location'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'States'); ?>

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

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Location Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page">States</li>
                </ol>
            </nav>

            <!-- Search toolbar -->
            <div class="toolbar mb-4">
                <div class="row-top">
                    <form class="d-flex flex-grow-1" method="get">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search states by name">
                        </div>
                        <button class="btn btn-primary ms-2" type="submit">Search</button>
                        <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                    </form>
                    <button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addStateModal">
                        <span class="text">Add State</span>
                        <span class="icon">
                            <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
                            <span class="buttonSpan">+</span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card quick-card mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="h6 mb-0">States (<?php echo $totalCount; ?>)</div>
                                <div class="d-flex gap-2">
                                    <a href="../districts/index.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-building me-1"></i>Districts
                                    </a>
                                    <a href="../cities/index.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-city me-1"></i>Cities
                                    </a>
                                    <a href="../towns/index.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-map-marker-alt me-1"></i>Towns
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive table-wrap">
                                <table class="table table-hover table-inner" id="statesTable">
                                    <thead>
                                        <tr>
                                            <th style="min-width:80px;">Image</th>
                                            <th style="min-width:200px;">State Name</th>
                                            <th style="min-width:120px;">Districts</th>
                                            <th class="actions-header" style="min-width:120px; width:120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($states && $states->num_rows > 0): ?>
                                            <?php while ($state = $states->fetch_assoc()): ?>
                                                <?php
                                                // Get district count for this state
                                                $districtCount = fetchScalar("SELECT COUNT(*) FROM districts WHERE state_id = " . $state['id']);
                                                ?>
                                                <tr data-state='<?php echo json_encode($state, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                                                    <td>
                                                        <?php if (!empty($state['image_url'])): ?>
                                                            <img src="../../../uploads/locations/<?php echo htmlspecialchars($state['image_url']); ?>" alt="<?php echo htmlspecialchars($state['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                                        <?php else: ?>
                                                            <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="fa-solid fa-map-location-dot text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="fw-semibold">
                                                        <a href="../districts/index.php?state_id=<?php echo $state['id']; ?>" class="badge bg-light text-dark border text-decoration-none" title="View districts in this state">
                                                            <?php echo htmlspecialchars($state['name']); ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-soft"><?php echo $districtCount; ?> districts</span>
                                                    </td>
                                                    <td class="actions-cell">
                                                        <button class="btn btn-sm btn-outline-secondary btn-edit me-1" data-bs-toggle="modal" data-bs-target="#editStateModal" title="Edit State"><i class="fa-solid fa-pen"></i></button>
                                                        <button class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteStateModal" title="Delete State"><i class="fa-solid fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">
                                                    <i class="fa-solid fa-map-location-dot fa-2x mb-2"></i>
                                                    <div>No states found</div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($totalPages > 1): ?>
                            <nav aria-label="States pagination">
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
            </div>
        </div>
    </div>

    <!-- Add State Modal -->
    <div class="modal fade" id="addStateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_state">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">State Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">State Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload an image for this state (JPG, PNG, GIF, WebP - Max 5MB)</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Add State</span>
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

    <!-- Edit State Modal -->
    <div class="modal fade" id="editStateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_state">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="current_image" id="edit_current_image">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">State Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">State Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload a new image to replace the current one (JPG, PNG, GIF, WebP - Max 5MB)</div>
                                <div id="current_image_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Update State</span>
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
    <div class="modal fade" id="deleteStateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the state "<span id="delete_state_name"></span>"?</p>
                    <p class="text-muted small">This action will also delete all associated districts, cities, and towns. This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_state">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="submit" class="btn btn-danger">Delete State</button>
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
                    const data = JSON.parse(tr.getAttribute('data-state'));
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_name').value = data.name || '';
                    document.getElementById('edit_current_image').value = data.image_url || '';
                    
                    // Show current image if exists
                    const preview = document.getElementById('current_image_preview');
                    if (data.image_url) {
                        preview.innerHTML = `
                            <div class="d-flex align-items-center gap-2">
                                <img src="../../../uploads/locations/${data.image_url}" alt="Current image" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                <div>
                                    <div class="small text-muted">Current image:</div>
                                    <div class="small">${data.image_url}</div>
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
                    const data = JSON.parse(tr.getAttribute('data-state'));
                    document.getElementById('delete_id').value = data.id;
                    document.getElementById('delete_state_name').textContent = data.name;
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
