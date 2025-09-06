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
        /* Actions cell */
        .actions-cell{ display:flex; gap:8px; justify-content:flex-end; }
        .actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; }
        /* Drawer styles */
        .drawer{ position:fixed; top:0; right:-420px; width:420px; height:100vh; background:#fff; box-shadow:-12px 0 24px rgba(0,0,0,.08); transition:right .25s ease; z-index:1040; }
        .drawer.open{ right:0; }
        .drawer-header{ padding:16px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
        .drawer-body{ padding:16px; overflow:auto; height:calc(100vh - 64px); }
        .drawer-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.2); opacity:0; pointer-events:none; transition:opacity .2s ease; z-index:1035; }
        .drawer-backdrop.open{ opacity:1; pointer-events:auto; }
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
        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
            .sidebar.open{ left:12px; }
            .content{ margin-left:0; }
            .table{ font-size:.9rem; }
            .drawer{ width:100vw; right:-100vw; }
        }
        @media (max-width: 575.98px){
            .toolbar .row-top{ flex-direction:column; align-items:stretch; }
            .actions-cell{ justify-content:center; }
            .table thead th:last-child, .table tbody td:last-child{ text-align:center; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('properties'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

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
                        <a href="add.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-circle-plus me-1"></i>Add Property</a>
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
                                    <td class="text-end actions-cell">
                                        <button class="btn btn-sm btn-outline-info me-1 btn-view" title="View Property"><i class="fa-solid fa-eye"></i></button>
                                        <a href="edit.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit Property"><i class="fa-solid fa-pen"></i></a>
                                        <button class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deletePropertyModal" title="Delete Property"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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
        // Drawer functions
        function openDrawer(propertyId) {
            const drawer = document.getElementById('propertyDrawer');
            const backdrop = document.getElementById('drawerBackdrop');
            const drawerBody = document.getElementById('drawerBody');
            const drawerTitle = document.getElementById('drawerTitle');
            
            // Show loading state
            drawerBody.innerHTML = '<div class="text-center"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading property details...</p></div>';
            drawer.classList.add('open');
            backdrop.classList.add('open');
            
            // Fetch property details
            fetch(`get_property_details.php?id=${propertyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        drawerTitle.textContent = data.property.title || 'Property Details';
                        drawerBody.innerHTML = `
                            <div class="property-detail">
                                <div class="label">Category</div>
                                <div class="value badge bg-light text-dark border">${data.property.category_name || '—'}</div>
                            </div>
                            <div class="property-detail">
                                <div class="label">Listing</div>
                                <div class="value badge badge-soft">${data.property.listing_type || ''}</div>
                            </div>
                            <div class="two-col">
                                <div class="property-detail">
                                    <div class="label">Price</div>
                                    <div class="value price">₹${Number(data.property.price || 0).toLocaleString()}</div>
                                </div>
                                <div class="property-detail">
                                    <div class="label">Area</div>
                                    <div class="value area">${data.property.area || '—'} sqft</div>
                                </div>
                            </div>
                            <div class="property-detail">
                                <div class="label">Location</div>
                                <div class="value location">
                                    <i class="fa-solid fa-location-dot"></i>
                                    ${data.property.location || ''} ${data.property.landmark ? '(' + data.property.landmark + ')' : ''}
                                </div>
                            </div>
                            <div class="divider"></div>
                            <div class="two-col">
                                <div class="property-detail">
                                    <div class="label">Config</div>
                                    <div class="value">${data.property.configuration || '—'}</div>
                                </div>
                                <div class="property-detail">
                                    <div class="label">Furniture</div>
                                    <div class="value">${data.property.furniture_status || '—'}</div>
                                </div>
                                <div class="property-detail">
                                    <div class="label">Ownership</div>
                                    <div class="value">${data.property.ownership_type || '—'}</div>
                                </div>
                                <div class="property-detail">
                                    <div class="label">Facing</div>
                                    <div class="value">${data.property.facing || '—'}</div>
                                </div>
                                <div class="property-detail">
                                    <div class="label">Parking</div>
                                    <div class="value">${data.property.parking || '—'}</div>
                                </div>
                                <div class="property-detail">
                                    <div class="label">Balcony</div>
                                    <div class="value">${data.property.balcony || 0}</div>
                                </div>
                            </div>
                            <div class="divider"></div>
                            <div class="property-detail">
                                <div class="label">Description</div>
                                <div class="value">${(data.property.description || '').slice(0, 200)}${(data.property.description || '').length > 200 ? '...' : ''}</div>
                            </div>
                        `;
                    } else {
                        drawerBody.innerHTML = '<div class="alert alert-danger">Error loading property details</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    drawerBody.innerHTML = '<div class="alert alert-danger">Error loading property details</div>';
                });
        }
        
        function closeDrawer() {
            document.getElementById('propertyDrawer').classList.remove('open');
            document.getElementById('drawerBackdrop').classList.remove('open');
        }

        // Wire View buttons
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', function(){
                const tr = this.closest('tr');
                const propertyId = tr.dataset.id;
                openDrawer(propertyId);
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


