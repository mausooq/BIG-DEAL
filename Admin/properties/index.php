<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login/');
    exit();
}

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
            case 'add':
                $title = trim($_POST['title'] ?? '');
                $listing_type = $_POST['listing_type'] ?? 'Buy';
                $price = $_POST['price'] !== '' ? (float)$_POST['price'] : null;
                $location = trim($_POST['location'] ?? '');
                $area = $_POST['area'] !== '' ? (float)$_POST['area'] : null;
                $configuration = trim($_POST['configuration'] ?? '');
                $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
                $status = $_POST['status'] ?? 'Available';

                if ($title === '') { throw new Exception('Title is required'); }

                $sql = "INSERT INTO properties (title, description, listing_type, price, location, landmark, area, configuration, category_id, furniture_status, ownership_type, facing, parking, balcony, status, created_at) VALUES (?, NULL, ?, ?, ?, NULL, ?, ?, ?, NULL, NULL, NULL, NULL, 0, ?, NOW())";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('ssdsisis', $title, $listing_type, $price, $location, $area, $configuration, $category_id, $status);
                if (!$stmt->execute()) { throw new Exception('Failed to add property: ' . $mysqli->error); }
                $stmt->close();
                logActivity($mysqli, 'Added property', 'Title: ' . $title);
                $message = 'Property added successfully';
                $message_type = 'success';
                break;

            case 'edit':
                $id = (int)($_POST['id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $listing_type = $_POST['listing_type'] ?? 'Buy';
                $price = $_POST['price'] !== '' ? (float)$_POST['price'] : null;
                $location = trim($_POST['location'] ?? '');
                $area = $_POST['area'] !== '' ? (float)$_POST['area'] : null;
                $configuration = trim($_POST['configuration'] ?? '');
                $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
                $status = $_POST['status'] ?? 'Available';

                if ($id <= 0 || $title === '') { throw new Exception('Invalid property data'); }

                $sql = "UPDATE properties SET title=?, listing_type=?, price=?, location=?, area=?, configuration=?, category_id=?, status=? WHERE id=?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('ssdsisisi', $title, $listing_type, $price, $location, $area, $configuration, $category_id, $status, $id);
                if (!$stmt->execute()) { throw new Exception('Failed to update property: ' . $mysqli->error); }
                $stmt->close();
                logActivity($mysqli, 'Updated property', 'ID: ' . $id . ', Title: ' . $title);
                $message = 'Property updated successfully';
                $message_type = 'success';
                break;

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

$sql = "SELECT p.id, p.title, p.price, p.location, p.landmark, p.area, p.configuration, p.listing_type, p.status, p.category_id,
                DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at,
                c.name AS category_name
         FROM properties p LEFT JOIN categories c ON c.id = p.category_id";
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.created_at DESC LIMIT 50';

$stmt = $mysqli->prepare($sql);
if ($stmt && $types !== '') { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$properties = $stmt ? $stmt->get_result() : $mysqli->query("SELECT p.id, p.title, p.price, p.location, p.landmark, p.area, p.configuration, p.listing_type, p.status, DATE_FORMAT(p.created_at,'%b %d, %Y') as created_at, NULL AS category_name FROM properties p ORDER BY p.created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root{ --bg:#F1EFEC; --card:#ffffff; --muted:#6b7280; --line:#e9eef5; --brand-dark:#2f2f2f; --primary:#e11d2a; --primary-600:#b91c1c; --radius:16px; }
        body{ background:var(--bg); color:#111827; }
        .content{ margin-left:284px; }
        /* Sidebar styles copied from dashboard */
        .sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .brand{ font-weight:700; font-size:1.25rem; }
        .list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
        .list-group-item i{ width:18px; }
        .list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
        .list-group-item:hover{ background:#f8fafc; }
        .navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
        .table tbody tr{ border-top:1px solid var(--line); }
        .table tbody tr:hover{ background:#f9fafb; }
        .badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('properties'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

        <div class="container-fluid p-4">
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
            </div>

            <!-- Filter toolbar -->
            <div class="card mb-3">
                <div class="card-body">
                    <form class="row g-2" method="get">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Title</label>
                            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($filters['title']); ?>" placeholder="Search by title">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Location</label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($filters['location']); ?>" placeholder="City, Area">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Listing</label>
                            <select class="form-select" name="listing_type">
                                <option value="">Any</option>
                                <?php foreach(['Buy','Rent','PG/Co-living'] as $lt): $sel = ($filters['listing_type']===$lt)?'selected':''; ?>
                                    <option value="<?php echo $lt; ?>" <?php echo $sel; ?>><?php echo $lt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Category</label>
                            <select class="form-select" name="category_id">
                                <option value="">Any</option>
                                <?php while($c = $categoriesRes->fetch_assoc()): $sel = ((string)$filters['category_id'] === (string)$c['id'])?'selected':''; ?>
                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Any</option>
                                <?php foreach(['Available','Sold','Rented'] as $st): $sel = ($filters['status']===$st)?'selected':''; ?>
                                    <option value="<?php echo $st; ?>" <?php echo $sel; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <a class="btn btn-outline-secondary" href="index.php">Reset</a>
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Properties table -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="h6 mb-0">Properties</div>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPropertyModal"><i class="fa-solid fa-circle-plus me-1"></i>Add Property</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="propertiesTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Listing</th>
                                    <th>Price</th>
                                    <th>Location</th>
                                    <th>Area</th>
                                    <th>Config</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
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
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></span></td>
                                    <td><span class="badge badge-soft"><?php echo htmlspecialchars($row['listing_type']); ?></span></td>
                                    <td>₹<?php echo number_format((float)$row['price']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars((string)$row['area']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['configuration']); ?></td>
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
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary me-1 btn-edit" data-bs-toggle="modal" data-bs-target="#editPropertyModal"><i class="fa-solid fa-pen"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deletePropertyModal"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Property Modal (placeholder) -->
            <div class="modal fade" id="addPropertyModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fa-solid fa-circle-plus me-2"></i>Add Property</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="post" action="#">
                            <input type="hidden" name="action" value="add">
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="title" placeholder="Property title">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Listing</label>
                                        <select class="form-select" name="listing_type">
                                            <option>Buy</option>
                                            <option>Rent</option>
                                            <option>PG/Co-living</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Price</label>
                                        <input type="number" class="form-control" name="price" step="0.01" placeholder="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Location</label>
                                        <input type="text" class="form-control" name="location" placeholder="City, Area">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Area (sqft)</label>
                                        <input type="number" class="form-control" name="area" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Config</label>
                                        <input type="text" class="form-control" name="configuration" placeholder="2BHK">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="category_id">
                                            <option value="">Select</option>
                                            <?php $cats = db()->query("SELECT id,name FROM categories ORDER BY name"); while($c=$cats->fetch_assoc()): ?>
                                                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option>Available</option>
                                            <option>Sold</option>
                                            <option>Rented</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Property Modal (placeholder) -->
            <div class="modal fade" id="editPropertyModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Property</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="post" action="#">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" id="edit-id">
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="title" id="edit-title">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Listing</label>
                                        <select class="form-select" name="listing_type" id="edit-listing">
                                            <option>Buy</option>
                                            <option>Rent</option>
                                            <option>PG/Co-living</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Price</label>
                                        <input type="number" class="form-control" name="price" step="0.01" id="edit-price">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Location</label>
                                        <input type="text" class="form-control" name="location" id="edit-location">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Area (sqft)</label>
                                        <input type="number" class="form-control" name="area" step="0.01" id="edit-area">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Config</label>
                                        <input type="text" class="form-control" name="configuration" id="edit-config">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="category_id" id="edit-category">
                                            <option value="">Select</option>
                                            <?php $cats2 = db()->query("SELECT id,name FROM categories ORDER BY name"); while($c=$cats2->fetch_assoc()): ?>
                                                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" id="edit-status">
                                            <option>Available</option>
                                            <option>Sold</option>
                                            <option>Rented</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
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
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Wire Edit buttons
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function(){
                const tr = this.closest('tr');
                document.getElementById('edit-id').value = tr.dataset.id;
                document.getElementById('edit-title').value = tr.dataset.title || '';
                document.getElementById('edit-listing').value = tr.dataset.listing || 'Buy';
                document.getElementById('edit-price').value = tr.dataset.price || '';
                document.getElementById('edit-location').value = tr.dataset.location || '';
                document.getElementById('edit-area').value = tr.dataset.area || '';
                document.getElementById('edit-config').value = tr.dataset.config || '';
                const cat = document.getElementById('edit-category');
                if (cat) { cat.value = tr.dataset.categoryId || ''; }
                document.getElementById('edit-status').value = tr.dataset.status || 'Available';
            });
        });

        // Wire Delete buttons
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(){
                const tr = this.closest('tr');
                document.getElementById('delete-id').value = tr.dataset.id;
                document.getElementById('delete-title').textContent = tr.dataset.title || 'this property';
            });
        });
    </script>
</body>
</html>


