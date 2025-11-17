<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
	$mysqli = db();
	$res = $mysqli->query($sql);
	$row = $res ? $res->fetch_row() : [0];
	return (int)($row[0] ?? 0);
}

// Handle notification operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$mysqli = db();

	if ($action === 'mark_as_read') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			$stmt = $mysqli->prepare('UPDATE notifications SET is_read = TRUE WHERE id = ?');
			if ($stmt) {
				$stmt->bind_param('i', $id);
				if ($stmt->execute()) {
					$_SESSION['success_message'] = 'Notification marked as read!';
				} else {
					$_SESSION['error_message'] = 'Failed to mark notification as read: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		}
	} elseif ($action === 'mark_all_read') {
		$stmt = $mysqli->prepare('UPDATE notifications SET is_read = TRUE WHERE is_read = FALSE');
		if ($stmt) {
			if ($stmt->execute()) {
				$_SESSION['success_message'] = 'All notifications marked as read!';
			} else {
				$_SESSION['error_message'] = 'Failed to mark all notifications as read: ' . $mysqli->error;
			}
			$stmt->close();
		} else {
			$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
		}
	}

	header('Location: index.php');
	exit();
}

// Stats
$totalNotifications = fetchScalar('SELECT COUNT(*) FROM notifications');
$unreadNotifications = fetchScalar('SELECT COUNT(*) FROM notifications WHERE is_read = FALSE');
$totalBlogs = fetchScalar('SELECT COUNT(*) FROM blogs');
$totalProperties = fetchScalar('SELECT COUNT(*) FROM properties');
$totalEnquiries = fetchScalar('SELECT COUNT(*) FROM enquiries');

// Get notifications with search and pagination
$mysqli = db();
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
	$whereClause = ' WHERE message LIKE ?';
	$types = 's';
	$searchParam = '%' . $mysqli->real_escape_string($search) . '%';
	$params[] = $searchParam;
}

$sql = 'SELECT id, message, is_read, created_at FROM notifications' . $whereClause . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$notifications = $stmt ? $stmt->get_result() : $mysqli->query('SELECT id, message, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 10');

// Count
$countSql = 'SELECT COUNT(*) FROM notifications' . $whereClause;
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
$recentNotifications = $mysqli->query("SELECT message, is_read, DATE_FORMAT(created_at,'%b %d, %Y') as created_at FROM notifications ORDER BY created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Notifications - Big Deal</title>
	<!-- Favicon -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico?v=4">
	<link rel="shortcut icon" href="/favicon.ico?v=4" type="image/x-icon">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<style>
		/* Base */
		
		body{ background:var(--bg); color:#111827; }

		/* Topbar */

		/* Cards */
		
		.card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
		/* Remove box styling on notifications wrapper */
		.quick-card{ border:0; background:transparent; }
		/* Toolbar */
		.toolbar{ background:transparent; border:0; border-radius:0; padding:12px; display:flex; flex-direction:column; gap:10px; }
		.toolbar .row-top{ display:flex; gap:12px; align-items:center; }
		/* Table */
		.table{ --bs-table-bg:transparent; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
		.table tbody tr{ border-top:1px solid var(--line); }
		.table tbody tr:hover{ background:#f9fafb; }
		/* Actions cell */
		.actions-cell{ display:flex; gap:8px; justify-content:flex-end; }
		.actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; }
		/* Badges */
		.badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
		/* Activity list */
		.list-activity{ max-height:420px; overflow:auto; }
		.sticky-side{ position:sticky; top:96px; }
		/* Buttons */
        .btn-primary{ background:var(--primary); border-color:var(--primary); }
        .btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        .btn-primary:focus, .btn-primary:active, .btn-primary:focus-visible{ 
            background:var(--primary-600) !important; 
            border-color:var(--primary-600) !important; 
            box-shadow: 0 0 0 .25rem rgba(225,29,42,.25) !important; 
            color:#fff !important;
        }
        .btn-outline-primary{ color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover{ background-color: var(--primary); border-color: var(--primary); color:#fff; }
		/* Notification specific styles */
		.notification-card{ 
			border:1px solid var(--line); 
			border-radius:12px; 
			padding:16px; 
			margin-bottom:12px; 
			background:#fff; 
			transition:all 0.2s ease;
			position:relative;
		}
		.notification-card:hover{ 
			box-shadow:0 4px 12px rgba(0,0,0,.06); 
			transform:translateY(-1px);
		}
		.notification-unread{ 
			background:#fff; 
			border-left:0;
			box-shadow:none;
		}
		.notification-read{ 
			background:#fff; 
			border-left:0;
		}
		.notification-header{
			display:flex;
			align-items:center;
			justify-content:space-between;
			margin-bottom:8px;
		}
		.notification-title{
			font-weight:600;
			font-size:0.95rem;
			color:#111827;
			margin:0;
		}
		/* Ensure the name part uses exact 600, not browser 'bolder' */
		.notification-title strong{ font-weight:600; }
		.notification-meta{
			display:flex;
			align-items:center;
			justify-content:space-between;
			margin-top:8px;
		}
		.notification-time{
			color:var(--muted);
			font-size:0.85rem;
		}
		.notification-type{
			display:inline-flex;
			align-items:center;
			gap:4px;
			padding:4px 8px;
			border-radius:6px;
			font-size:0.75rem;
			font-weight:500;
		}
		.notification-type.info{ background:#fee2e2; color:var(--primary); }
		.notification-type.warning{ background:#fff3e0; color:#f57c00; }
		.notification-type.success{ background:#e8f5e8; color:#2e7d32; }
		.notification-type.error{ background:#ffebee; color:#c62828; }
		.unread-indicator{
			position:absolute;
			top:12px;
			right:12px;
			width:8px;
			height:8px;
			background:var(--primary);
			border-radius:50%;
		}
		/* Mobile responsiveness */

			.table{ font-size:.9rem; }
		}
		@media (max-width: 575.98px){
			.toolbar .row-top{ flex-direction:column; align-items:stretch; }
			.actions-cell{ justify-content:center; }
			.table thead th:last-child, .table tbody td:last-child{ text-align:center; }
		}

		/* Pagination - red theme */
		.pagination .page-link{
			color: var(--primary);
			border-color: var(--line);
		}
		.pagination .page-link:hover{
			color:#fff;
			background-color: var(--primary);
			border-color: var(--primary);
		}
		.pagination .page-link:focus{
			box-shadow: 0 0 0 .2rem rgba(225,29,42,.2);
		}
		.page-item.active .page-link{
			background-color: var(--primary);
			border-color: var(--primary);
			color:#fff;
		}
	</style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('notifications'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Notifications'); ?>

		<div class="container-fluid p-4">
			<?php if (isset($_SESSION['success_message'])): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					<?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>
			<?php if (isset($_SESSION['error_message'])): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>

			<!-- Header only; toolbar removed -->

			<div class="card quick-card mb-4">
				<div class="card-body">
					<div class="d-flex align-items-center justify-content-between mb-4">
						<div class="h6 mb-0">Notifications</div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-light text-dark border"><?php echo $totalCount; ?> total</span>
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="badge bg-light text-dark border"><?php echo $unreadNotifications; ?> unread</span>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#markAllReadModal">
                                    <i class="fa-solid fa-check-double me-1"></i>Mark All Read
                                </button>
                            <?php endif; ?>
                        </div>
					</div>
					
					<div class="notifications-container">
						<?php 
						$notifications->data_seek(0); // Reset pointer
						while($row = $notifications->fetch_assoc()): 
							// Determine notification type based on content
							$type = 'info';
							$icon = 'fa-bell';
							if (stripos($row['message'], 'error') !== false || stripos($row['message'], 'failed') !== false) {
								$type = 'error';
								$icon = 'fa-exclamation-triangle';
							} elseif (stripos($row['message'], 'success') !== false || stripos($row['message'], 'completed') !== false) {
								$type = 'success';
								$icon = 'fa-check-circle';
							} elseif (stripos($row['message'], 'warning') !== false || stripos($row['message'], 'attention') !== false) {
								$type = 'warning';
								$icon = 'fa-exclamation-circle';
							}
						?>
						<div class="notification-card <?php echo $row['is_read'] ? 'notification-read' : 'notification-unread'; ?>" data-notif='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
							<?php if (!$row['is_read']): ?>
								<div class="unread-indicator"></div>
							<?php endif; ?>
							
							<div class="notification-header">
								<div class="d-flex align-items-center gap-3">
									<div class="notification-type <?php echo $type; ?>">
										<i class="fa-solid <?php echo $icon; ?>"></i>
										<?php echo ucfirst($type); ?>
									</div>
									<?php if ($row['is_read']): ?>
										<span class="badge bg-light text-dark border">Read</span>
									<?php else: ?>
										<span class="badge bg-light text-dark border">Unread</span>
									<?php endif; ?>
								</div>
							</div>
							
                            <?php 
                                $msg = $row['message'];
                                $pid = 0; $namePart = ''; $propLabel = '';
                                // Expect format: New enquiry from NAME — [PID:ID] TITLE (em dash U+2014)
                                if (preg_match('/^\s*New\s+enquiry\s+from\s+(.+?)\s+\x{2014}\s+\[PID:(\d+)\]\s+(.*)$/u', $msg, $m)) {
                                    $namePart = trim($m[1]);
                                    $pid = (int)$m[2];
                                    $propLabel = trim($m[3]);
                                } elseif (preg_match('/^\s*New\s+enquiry\s+from\s+(.+?)\s+—\s+\[PID:(\d+)\]\s+(.*)$/u', $msg, $m)) { // fallback ascii dash
                                    $namePart = trim($m[1]);
                                    $pid = (int)$m[2];
                                    $propLabel = trim($m[3]);
                                } elseif (preg_match('/^\s*New\s+enquiry\s+from\s+(.+?)\s+\x{2014}\s+(.*)$/u', $msg, $m)) { // no PID, em dash
                                    $namePart = trim($m[1]);
                                    $propLabel = trim($m[2]);
                                } elseif (preg_match('/^\s*New\s+enquiry\s+from\s+(.+?)\s+—\s+(.*)$/u', $msg, $m)) { // no PID, ascii dash
                                    $namePart = trim($m[1]);
                                    $propLabel = trim($m[2]);
                                } elseif (preg_match('/\[PID:(\d+)\]\s*(.*)/u', $msg, $m)) {
                                    $pid = (int)$m[1];
                                    $propLabel = trim($m[2]);
                                } else {
                                    $propLabel = $msg;
                                }
                            ?>
                            <div class="notification-title">
                                <?php if ($namePart !== ''): ?>
                                    <strong>New enquiry from <?php echo htmlspecialchars($namePart); ?></strong>
                                <?php endif; ?>
                                <?php if ($pid > 0): ?>
                                    — <a href="../properties/view.php?id=<?php echo $pid; ?>" style="color:#111827; text-decoration:none;">
                                        <?php echo htmlspecialchars($propLabel ?: ('Property #' . $pid)); ?>
                                      </a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($propLabel); ?>
                                <?php endif; ?>
                            </div>
							
							<div class="notification-meta">
								<span class="notification-time">
									<i class="fa-solid fa-clock me-1"></i>
									<?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?>
								</span>
								
                                <div class="d-flex gap-2 ms-auto">
                                    <?php if ($pid > 0): ?>
                                        <a class="btn btn-sm btn-outline-danger" href="../properties/view.php?id=<?php echo $pid; ?>" title="View Property">
                                            <i class="fa-solid fa-eye me-1"></i>View
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$row['is_read']): ?>
                                        <button class="btn btn-sm btn-primary btn-mark-read" data-bs-toggle="modal" data-bs-target="#markReadModal" title="Mark as Read">
                                            <i class="fa-solid fa-check me-1"></i>Mark as Read
                                        </button>
                                    <?php endif; ?>
                                </div>
							</div>
						</div>
						<?php endwhile; ?>
						
						<?php if ($totalCount == 0): ?>
							<div class="text-center py-5">
								<i class="fa-solid fa-bell-slash fa-3x text-muted mb-3"></i>
								<h5 class="text-muted">No notifications found</h5>
								<p class="text-muted">You're all caught up! No notifications to display.</p>
							</div>
						<?php endif; ?>
					</div>

					<?php if ($totalPages > 1): ?>
					<nav aria-label="Notifications pagination" class="mt-4">
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

	<!-- Mark as Read Modal -->
	<div class="modal fade" id="markReadModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Mark as Read</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to mark this notification as read?</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="mark_as_read">
						<input type="hidden" name="id" id="mark_read_id">
						<button type="submit" class="btn btn-primary">Mark as Read</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Mark All as Read Modal -->
	<div class="modal fade" id="markAllReadModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Mark All as Read</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to mark all notifications as read?</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="mark_all_read">
						<button type="submit" class="btn btn-primary">Mark All as Read</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function(){
			document.querySelectorAll('.btn-mark-read').forEach(btn => {
				btn.addEventListener('click', function(){
					const card = this.closest('.notification-card');
					const data = JSON.parse(card.getAttribute('data-notif'));
					document.getElementById('mark_read_id').value = data.id;
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
