<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

$filters = [
    'q' => trim($_GET['q'] ?? ''),
];

$mysqli = db();

// Count query
$where = [];
$types = '';
$params = [];
if ($filters['q'] !== '') {
    $where[] = '(COALESCE(u.username,\'System\') LIKE ? OR a.action LIKE ?)';
    $types .= 'ss';
    $like = '%' . $mysqli->real_escape_string($filters['q']) . '%';
    array_push($params, $like, $like);
}

$perPage = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM activity_logs a LEFT JOIN admin_users u ON u.id = a.admin_id";
if (!empty($where)) { $countSql .= ' WHERE ' . implode(' AND ', $where); }
$countStmt = $mysqli->prepare($countSql);
if ($countStmt && $types !== '') { $countStmt->bind_param($types, ...$params); }
$totalRows = 0;
if ($countStmt && $countStmt->execute()) {
    $res = $countStmt->get_result();
    $row = $res ? $res->fetch_row() : [0];
    $totalRows = (int)($row[0] ?? 0);
}
$countStmt && $countStmt->close();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

$sql = "SELECT a.id, COALESCE(u.username,'System') AS actor, a.action, a.details,
               DATE_FORMAT(a.created_at,'%b %d, %Y %h:%i %p') as created_at
        FROM activity_logs a LEFT JOIN admin_users u ON u.id = a.admin_id";
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY a.created_at DESC LIMIT ?, ?';

$stmt = $mysqli->prepare($sql);
$bindTypes = $types . 'ii';
$bindParams = $params;
$bindParams[] = $offset;
$bindParams[] = $perPage;
if ($stmt) { $stmt->bind_param($bindTypes, ...$bindParams); }
$stmt && $stmt->execute();
$logs = $stmt ? $stmt->get_result() : $mysqli->query("SELECT a.id, 'System' AS actor, a.action, a.details, DATE_FORMAT(a.created_at,'%b %d, %Y %h:%i %p') as created_at FROM activity_logs a ORDER BY a.created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        
        body{ background:var(--bg); color:#111827; }

        .toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:8px 12px; display:flex; gap:8px; align-items:center; }
        .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
        .badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        /* Make search button red and keep outline secondary for reset */
        .btn-primary{ background:var(--primary); border-color:var(--primary); }
        .btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        .card{ border:0; border-radius:16px; background:var(--card); }
        .card .card-body{ padding:12px; }
        .table td, .table th{ padding:.5rem .75rem; }

        /* Red themed pagination */
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

        }
    </style>
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
<?php // topbar and sidebar use same components ?>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('activity-logs'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Activity Logs'); ?>

        <div class="container-fluid p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <!-- <div class="h5 mb-0">Activity Logs</div> -->
            </div>

            <form class="toolbar mb-3" method="get">
                <div class="input-group" style="max-width:420px;">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Search by actor or action">
                </div>
                <button class="btn btn-primary" type="submit">Search</button>
                <a class="btn btn-outline-secondary" href="index.php">Reset</a>
            </form>

            <div class="card">
                <div class="card-body">
                    <table class="table align-middle table-sm">
                        <thead>
                            <tr>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($a = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($a['actor']); ?></td>
                                <td><span class="badge badge-soft"><?php echo htmlspecialchars($a['action']); ?></span></td>
                                <td class="text-muted" style="max-width:560px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($a['details']); ?>"><?php echo htmlspecialchars($a['details']); ?></td>
                                <td class="text-muted"><?php echo $a['created_at']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <?php 
                        $qs = [];
                        if ($filters['q'] !== '') { $qs['q'] = $filters['q']; }
                        $buildPageUrl = function($p) use ($qs) { $qs['page'] = $p; return 'index.php?' . http_build_query($qs); };
                    ?>
                    <nav aria-label="Logs pagination" class="mt-3">
                        <ul class="pagination mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $page <= 1 ? '#' : $buildPageUrl($page-1); ?>">Previous</a>
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
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

