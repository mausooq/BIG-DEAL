<?php
require_once __DIR__ . '/../auth.php';

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
               p.title AS property_title, p.id AS property_id
        FROM enquiries e LEFT JOIN properties p ON p.id = e.property_id";
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY e.created_at DESC LIMIT 100';

$stmt = $mysqli->prepare($sql);
if ($stmt && $types !== '') { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$enquiries = $stmt ? $stmt->get_result() : $mysqli->query("SELECT e.id, e.name, e.email, e.phone, e.message, e.status, DATE_FORMAT(e.created_at,'%b %d, %Y %h:%i %p') created_at, NULL AS property_title, NULL AS property_id FROM enquiries e ORDER BY e.created_at DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiries - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        
        body{ background:var(--bg); color:#111827; }
        
        /* sidebar styles matching dashboard */

        /* Table: row-level separators to avoid gaps */
        .table{ --bs-table-bg:transparent; border-collapse:collapse; }
        .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border-bottom:1px solid var(--line); }
        .table tbody td{ border-top:0; border-bottom:0; }
        .table tbody tr{ box-shadow: inset 0 1px 0 var(--line); }
        .table tbody tr:last-child{ box-shadow: inset 0 1px 0 var(--line), inset 0 -1px 0 var(--line); }
        .table tbody tr:hover{ background:#f9fafb; }
        /* Toolbar */
        .toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
        .toolbar .row-top{ display:flex; gap:12px; align-items:center; }
        .toolbar .row-bottom{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .toolbar .chip{ padding:6px 12px; border:1px solid var(--line); border-radius:9999px; background:#fff; color:#374151; text-decoration:none; font-size:.875rem; }
        .toolbar .chip:hover{ border-color:#d1d5db; }
        .toolbar .chip.active{ background:var(--primary); border-color:var(--primary); color:#fff; }
        .toolbar .divider{ width:1px; height:24px; background:var(--line); margin:0 4px; }
         /* Actions cell */
         .actions-cell{ display:flex; gap:8px; justify-content:center; }
         /* Buttons */
         .btn-primary{ background:var(--primary); border-color:var(--primary); }
         .btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        /* legacy outline primary no longer used for actions */
        /* Modal theming to match dashboard / property view */
        .modal-content{ border:1px solid var(--line); border-radius:16px; background:var(--card); box-shadow:0 12px 28px rgba(0,0,0,.12); }
        .modal-header{ border-bottom:1px solid var(--line); }
        .modal-footer{ border-top:1px solid var(--line); }
        .info-label{ color:var(--muted); font-size:0.875rem; font-weight:600; margin-bottom:0.25rem; }
        .info-value{ color:#111827; font-weight:600; }
        .modal-body a{ color:var(--primary); text-decoration:none; }
        .modal-body a:hover{ text-decoration:underline; }
        .message-box{ border:1px solid var(--line); background:#fff; border-radius:12px; padding:10px 12px; color:#111827; }
        .two-col{ display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
        .divider{ height:1px; background:var(--line); margin:12px 0; }
        .badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
        .badge-status-success{ background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
        .badge-status-warning{ background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
        .badge-status-danger{ background:#fecaca; color:#991b1b; border:1px solid #fca5a5; }
        /* Modern Animated Action Buttons (match Properties) */
        .modern-btn { width:36px; height:36px; border:none; border-radius:12px; cursor:pointer; position:relative; overflow:hidden; transition: all .4s cubic-bezier(0.175,0.885,0.32,1.275); backdrop-filter: blur(10px); box-shadow: 0 4px 16px rgba(0,0,0,.15), inset 0 1px 0 rgba(255,255,255,.2); display:inline-flex; align-items:center; justify-content:center; font-size:14px; margin:0 2px; }
        .modern-btn::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent); transition:left .6s; }
        .modern-btn:hover::before { left:100%; }
        .modern-btn:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 8px 24px rgba(0,0,0,.2), inset 0 1px 0 rgba(255,255,255,.3); filter: drop-shadow(0 0 12px rgba(255,255,255,.3)); }
        .modern-btn:active { transform: translateY(-1px) scale(1.02); transition: all .1s ease; }
        .modern-btn .icon { transition: all .3s ease; }
        .modern-btn:hover .icon { transform: scale(1.2) rotate(5deg); }
        .view-btn, .edit-btn { background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; }
        .view-btn:hover, .edit-btn:hover { background:#e5e7eb; border-color:#d1d5db; color:#374151; }
        .delete-btn { background: var(--primary); color:#fff; border:1px solid var(--primary-600); }
        .delete-btn:hover { background: var(--primary-600); border-color: var(--primary-600); color:#fff; }
        .ripple { position:absolute; border-radius:50%; background: rgba(255,255,255,.4); transform: scale(0); animation: ripple-animation .6s linear; pointer-events:none; }
        @keyframes ripple-animation { to { transform: scale(4); opacity: 0; } }
        
        /* Button consistency */
        .btn{ border-radius:8px; font-weight:500; }
        .btn-sm{ padding:0.5rem 1rem; font-size:0.875rem; }
        .btn-animated-delete{ padding:0.5rem 1rem; font-size:0.875rem; }
        /* Mobile responsiveness */

            .table{ font-size:.9rem; }
        }
        @media (max-width: 575.98px){
            .toolbar .row-top{ flex-direction:column; align-items:stretch; }
            .toolbar .row-bottom{ gap:6px; }
            .actions-cell{ justify-content:center; }
            .table thead th:last-child, .table tbody td:last-child{ text-align:center; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('enquiries'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Enquiries'); ?>

        <div class="container-fluid p-4">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
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

            <!-- Search toolbar -->
            <div class="toolbar mb-4">
                <div class="row-top">
                    <form class="d-flex flex-grow-1" method="get">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Search enquiries by name, email, phone or property">
                        </div>
                        <button class="btn btn-primary ms-2" type="submit">Search</button>
                        <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                    </form>
                    <?php 
                    // Build export URL with current filters
                    $exportParams = [];
                    if ($filters['q'] !== '') { 
                        $exportParams['q'] = $filters['q']; 
                    }
                    if ($filters['status'] !== '') { 
                        $exportParams['status'] = $filters['status']; 
                    }
                    $exportUrl = 'export.php' . (!empty($exportParams) ? '?' . http_build_query($exportParams) : '');
                    ?>
                    <a href="<?php echo $exportUrl; ?>" class="btn btn-outline-success me-2">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                </div>
                <div class="row-bottom">
                    <?php foreach(['New','In Progress','Closed'] as $status): ?>
                        <?php $isActive = ($filters['status'] ?? '') === $status; ?>
                        <a class="chip js-filter <?php echo $isActive ? 'active' : ''; ?>" href="#" data-filter="status" data-value="<?php echo htmlspecialchars($status, ENT_QUOTES); ?>" role="button"><?php echo $status; ?></a>
                    <?php endforeach; ?>
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
                                    <th class="text-center">Actions</th>
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
                                    <td>
                                        <?php if (!empty($row['property_id'])): ?>
                                            <a href="../properties/view.php?id=<?php echo (int)$row['property_id']; ?>" class="badge bg-light text-dark border text-decoration-none">
                                                <?php echo htmlspecialchars($row['property_title'] ?: ('#' . (int)$row['property_id'])); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">—</span>
                                        <?php endif; ?>
                                    </td>
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
                                    <td class="actions-cell">
                                        <?php 
                                            $enq = [
                                                'id' => (int)$row['id'],
                                                'name' => $row['name'],
                                                'email' => $row['email'],
                                                'phone' => $row['phone'],
                                                'message' => $row['message'],
                                                'status' => $row['status'],
                                                'created_at' => $row['created_at'],
                                                'property_id' => $row['property_id'],
                                                'property_title' => $row['property_title'],
                                            ];
                                            $enqJson = htmlspecialchars(json_encode($enq, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT), ENT_QUOTES);
                                        ?>
                                        <button type="button" class="modern-btn view-btn btn-view-enquiry" data-enquiry='<?php echo $enqJson; ?>' title="View">
                                            <span class="icon"><i class="fa-solid fa-eye"></i></span>
                                        </button>
                                        <button type="button" class="modern-btn delete-btn btn-delete-enquiry" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteEnquiryModal" 
                                                data-enquiry-id="<?php echo (int)$row['id']; ?>"
                                                data-enquiry-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                title="Delete Enquiry">
                                            <span class="icon"><i class="fa-solid fa-trash"></i></span>
                                        </button>
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
    <!-- View Enquiry Modal -->
    <div class="modal fade" id="viewEnquiryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enquiry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="two-col">
                        <div>
                            <div class="info-label">Name</div>
                            <div id="enqName" class="info-value">—</div>
                        </div>
                        <div>
                            <div class="info-label">Status</div>
                            <div id="enqStatus" class="badge bg-light text-dark border rounded-pill px-3">—</div>
                        </div>
                        <div>
                            <div class="info-label">Email</div>
                            <div id="enqEmail" class="info-value">—</div>
                        </div>
                        <div>
                            <div class="info-label">Phone</div>
                            <div id="enqPhone" class="info-value">—</div>
                        </div>
                        <div>
                            <div class="info-label">Created</div>
                            <div id="enqCreated" class="info-value">—</div>
                        </div>
                        <div>
                            <div class="info-label">Property</div>
                            <div id="enqProperty" class="info-value">—</div>
                        </div>
                        <div class="divider"></div>
                        <div style="grid-column:1 / -1;">
                            <div class="info-label">Message</div>
                            <div id="enqMessage" class="message-box">—</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a id="enqPropertyBtn" href="#" class="btn btn-primary" style="display:none;">
                        <i class="fa-solid fa-home me-1"></i>View Property
                    </a>
                    <button type="button" class="modern-btn view-btn" data-bs-dismiss="modal" title="Close"><span class="icon"><i class="fa-solid fa-xmark"></i></span></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Enquiry Modal -->
    <div class="modal fade" id="deleteEnquiryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Enquiry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_enquiry_id">
                        <p>Are you sure you want to delete the enquiry from <span id="delete_enquiry_name" class="fw-semibold">this person</span>?</p>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-delete noselect">
                            <span class="text">Delete Enquiry</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        // Ripple + click animation for modern buttons
        document.addEventListener('click', function(e){
            const modernBtn = e.target.closest('.modern-btn');
            if (modernBtn) {
                const circle = document.createElement('span');
                const diameter = Math.max(modernBtn.clientWidth, modernBtn.clientHeight);
                const radius = diameter / 2;
                const rect = modernBtn.getBoundingClientRect();
                circle.style.width = circle.style.height = `${diameter}px`;
                circle.style.left = `${(e.clientX - rect.left) - radius}px`;
                circle.style.top = `${(e.clientY - rect.top) - radius}px`;
                circle.classList.add('ripple');
                const existing = modernBtn.querySelector('.ripple');
                if (existing) existing.remove();
                modernBtn.appendChild(circle);
                modernBtn.style.animation = 'none';
                modernBtn.style.transform = 'scale(0.95)';
                setTimeout(() => { modernBtn.style.animation = ''; modernBtn.style.transform = ''; }, 150);
            }
        });
        // Toggleable status chips
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
        document.querySelectorAll('.btn-view-enquiry').forEach(function(btn){
            btn.addEventListener('click', function(){
                try{
                    const data = JSON.parse(this.getAttribute('data-enquiry')) || {};
                    document.getElementById('enqName').textContent = data.name || '—';
                    const statusEl = document.getElementById('enqStatus');
                    statusEl.textContent = data.status || '—';
                    statusEl.classList.remove('badge-status-success','badge-status-warning','badge-status-danger');
                    switch((data.status||'').toLowerCase()){
                        case 'new': statusEl.classList.add('badge-status-warning'); break;
                        case 'in progress': statusEl.classList.add('badge-status-warning'); break;
                        case 'closed': statusEl.classList.add('badge-status-success'); break;
                        default: break;
                    }
                    document.getElementById('enqEmail').textContent = data.email || '—';
                    document.getElementById('enqPhone').textContent = data.phone || '—';
                    document.getElementById('enqCreated').textContent = data.created_at || '—';
                    const propEl = document.getElementById('enqProperty');
                    const propBtn = document.getElementById('enqPropertyBtn');
                    if (data.property_id) {
                        const href = `../properties/view.php?id=${data.property_id}`;
                        propEl.innerHTML = `<a href="${href}" class="text-decoration-none">${(data.property_title || ('#' + data.property_id))}</a>`;
                        propBtn.href = href; propBtn.style.display = '';
                    } else {
                        propEl.textContent = '—';
                        propBtn.style.display = 'none';
                    }
                    document.getElementById('enqMessage').textContent = data.message || '—';
                    const modal = new bootstrap.Modal(document.getElementById('viewEnquiryModal'));
                    modal.show();
                }catch(e){ console.error(e); }
            });
        });

        // Handle delete enquiry modal
        document.querySelectorAll('.btn-delete-enquiry').forEach(button => {
            button.addEventListener('click', function() {
                const enquiryId = this.getAttribute('data-enquiry-id');
                const enquiryName = this.getAttribute('data-enquiry-name');
                
                document.getElementById('delete_enquiry_id').value = enquiryId;
                document.getElementById('delete_enquiry_name').textContent = enquiryName;
            });
        });
    });
    </script>
</body>
</html>

