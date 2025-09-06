<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login/');
    exit();
}

function db() { return getMysqliConnection(); }

// Flash
$message = '';
$message_type = 'success';

// Activity log helper
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

// Handle actions: update_status, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $mysqli = db();
    try {
        if ($_POST['action'] === 'update_status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? 'New';
            if ($id <= 0) { throw new Exception('Invalid enquiry'); }
            $stmt = $mysqli->prepare('UPDATE enquiries SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $id);
            if (!$stmt->execute()) { throw new Exception('Failed to update status'); }
            $stmt->close();
            logActivity($mysqli, 'Updated enquiry status', 'ID: ' . $id . ' -> ' . $status);
            $message = 'Status updated';
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { throw new Exception('Invalid enquiry'); }
            $stmt = $mysqli->prepare('DELETE FROM enquiries WHERE id = ?');
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { throw new Exception('Failed to delete enquiry'); }
            $stmt->close();
            logActivity($mysqli, 'Deleted enquiry', 'ID: ' . $id);
            $message = 'Enquiry deleted';
        }
        $message_type = 'success';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Filters
$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'status' => $_GET['status'] ?? '',
];

$mysqli = db();

// Stats
function fetchScalar($sql){ $m = db(); $r = $m->query($sql); $row = $r ? $r->fetch_row() : [0]; return (int)($row[0] ?? 0); }
$totalEnquiries = fetchScalar('SELECT COUNT(*) FROM enquiries');
$newEnquiries = fetchScalar("SELECT COUNT(*) FROM enquiries WHERE status='New'");
$closedEnquiries = fetchScalar("SELECT COUNT(*) FROM enquiries WHERE status='Closed'");

// Build query
$where = [];
$types = '';
$params = [];
if ($filters['q'] !== '') {
    $where[] = '(e.name LIKE ? OR e.email LIKE ? OR e.phone LIKE ? OR p.title LIKE ?)';
    $types .= 'ssss';
    $like = '%' . $mysqli->real_escape_string($filters['q']) . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($filters['status'] !== '') {
    $where[] = 'e.status = ?';
    $types .= 's';
    $params[] = $filters['status'];
}

$sql = "SELECT e.id, e.name, e.email, e.phone, e.message, e.status, DATE_FORMAT(e.created_at,'%b %d, %Y %h:%i %p') created_at,
               p.title AS property_title
        FROM enquiries e LEFT JOIN properties p ON p.id = e.property_id";
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY e.created_at DESC LIMIT 100';

$stmt = $mysqli->prepare($sql);
if ($stmt && $types !== '') { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$enquiries = $stmt ? $stmt->get_result() : $mysqli->query("SELECT e.id, e.name, e.email, e.phone, e.message, e.status, DATE_FORMAT(e.created_at,'%b %d, %Y %h:%i %p') created_at, NULL AS property_title FROM enquiries e ORDER BY e.created_at DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiries - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root{ --bg:#F1EFEC; --card:#ffffff; --muted:#6b7280; --line:#e9eef5; --primary:#e11d2a; --primary-600:#b91c1c; --radius:16px; }
        body{ background:var(--bg); color:#111827; }
        .content{ margin-left:284px; }
        /* sidebar styles matching dashboard */
        .sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
        .list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
        .navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
        .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
        .table tbody tr{ border-top:1px solid var(--line); }
        .table tbody tr:hover{ background:#f9fafb; }
        /* Actions cell */
        .actions-cell{ display:flex; gap:8px; justify-content:flex-end; }
        .actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; }
        /* Mobile responsiveness */
        @media (max-width: 991.98px){
            .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
            .sidebar.open{ left:12px; }
            .content{ margin-left:0; }
            .table{ font-size:.9rem; }
        }
        @media (max-width: 575.98px){
            .actions-cell{ justify-content:center; }
            .table thead th:last-child, .table tbody td:last-child{ text-align:center; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('enquiries'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

        <div class="container-fluid p-4">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-xl-4">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Enquiries</div>
                                <div class="h4 mb-0"><?php echo $totalEnquiries; ?></div>
                            </div>
                            <div class="text-primary"><i class="fa-regular fa-envelope fa-lg"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">New</div>
                                <div class="h4 mb-0"><?php echo $newEnquiries; ?></div>
                            </div>
                            <div class="text-success"><i class="fa-solid fa-circle-dot fa-lg"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="card card-stat">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Closed</div>
                                <div class="h4 mb-0"><?php echo $closedEnquiries; ?></div>
                            </div>
                            <div class="text-danger"><i class="fa-regular fa-circle-check fa-lg"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form class="row g-2" method="get">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Search</label>
                            <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Name, email, phone or property">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Any</option>
                                <?php foreach(['New','In Progress','Closed'] as $s): $sel = ($filters['status']===$s)?'selected':''; ?>
                                    <option value="<?php echo $s; ?>" <?php echo $sel; ?>><?php echo $s; ?></option>
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

            <div class="card">
                <div class="card-body">
                    <div class="h6 mb-3">Enquiries</div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Property</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $enquiries->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="text-muted">
                                        <?php echo htmlspecialchars($row['email'] ?: '—'); ?><br>
                                        <span class="small"><?php echo htmlspecialchars($row['phone'] ?: '—'); ?></span>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['property_title'] ?: '—'); ?></span></td>
                                    <td class="text-muted" style="max-width:360px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($row['message']); ?>"><?php echo htmlspecialchars($row['message']); ?></td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                            <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                                                <?php foreach(['New','In Progress','Closed'] as $s): $sel = ($row['status']===$s)?'selected':''; ?>
                                                    <option value="<?php echo $s; ?>" <?php echo $sel; ?>><?php echo $s; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="text-muted"><?php echo $row['created_at']; ?></td>
                                    <td class="text-end actions-cell">
                                        <form method="post" onsubmit="return confirm('Delete this enquiry?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" title="Delete Enquiry"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


