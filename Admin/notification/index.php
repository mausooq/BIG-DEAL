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

// Handle notification operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$mysqli = db();

	if ($action === 'add_notification') {
		$message = trim($_POST['message'] ?? '');
		if ($message) {
			$stmt = $mysqli->prepare('INSERT INTO notifications (message) VALUES (?)');
			if ($stmt) {
				$stmt->bind_param('s', $message);
				if ($stmt->execute()) {
					$_SESSION['success_message'] = 'Notification added successfully!';
				} else {
					$_SESSION['error_message'] = 'Failed to add notification: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		} else {
			$_SESSION['error_message'] = 'Message is required.';
		}
	} elseif ($action === 'edit_notification') {
		$id = (int)($_POST['id'] ?? 0);
		$message = trim($_POST['message'] ?? '');
		if ($id && $message) {
			$stmt = $mysqli->prepare('UPDATE notifications SET message = ? WHERE id = ?');
			if ($stmt) {
				$stmt->bind_param('si', $message, $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Notification updated successfully!';
					} else {
						$_SESSION['error_message'] = 'No changes made or notification not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to update notification: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		} else {
			$_SESSION['error_message'] = 'Message is required.';
		}
	} elseif ($action === 'delete_notification') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			$stmt = $mysqli->prepare('DELETE FROM notifications WHERE id = ?');
			if ($stmt) {
				$stmt->bind_param('i', $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Notification deleted successfully!';
					} else {
						$_SESSION['error_message'] = 'Notification not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to delete notification: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		}
	} elseif ($action === 'mark_as_read') {
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
		.content{ margin-left:260px; }
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
		/* Table */
		.table{ --bs-table-bg:transparent; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border:0; }
		.table tbody tr{ border-top:1px solid var(--line); }
		.table tbody tr:hover{ background:#f9fafb; }
		/* Actions cell */
		.actions-cell{ display:flex; gap:8px; justify-content:flex-end; }
		.actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; }
		/* Badges */
		.badge-soft{ background:#f4f7ff; color:#4356e0; border:1px solid #e4e9ff; }
		/* Activity list */
		.list-activity{ max-height:420px; overflow:auto; }
		.sticky-side{ position:sticky; top:96px; }
		/* Buttons */
		.btn-primary{ background:var(--primary); border-color:var(--primary); }
		.btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
		/* Notification specific styles */
		.notification-unread{ background:#fef3f2; border-left:4px solid var(--primary); }
		.notification-read{ background:#f8fafc; }
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
			.table thead th:last-child, .table tbody td:last-child{ text-align:center; }
		}
	</style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('notifications'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Search notifications...'); ?>

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

			<div class="row g-3 mb-3">
				<div class="col-12"><div class="h5 mb-0">Quick Access</div></div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Total Notifications</div>
								<div class="h4 mb-0"><?php echo $totalNotifications; ?></div>
							</div>
							<div class="text-primary"><i class="fa-solid fa-bell fa-lg"></i></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Unread</div>
								<div class="h4 mb-0"><?php echo $unreadNotifications; ?></div>
							</div>
							<div class="text-warning"><i class="fa-solid fa-bell-slash fa-lg"></i></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Properties</div>
								<div class="h4 mb-0"><?php echo $totalProperties; ?></div>
							</div>
							<div class="text-success"><i class="fa-solid fa-building fa-lg"></i></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Enquiries</div>
								<div class="h4 mb-0"><?php echo $totalEnquiries; ?></div>
							</div>
							<div class="text-info"><i class="fa-regular fa-envelope fa-lg"></i></div>
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
							<input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search notifications by message">
						</div>
						<button class="btn btn-primary ms-2" type="submit">Search</button>
						<a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
					</form>
					<div class="d-flex gap-2">
						<button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#markAllReadModal">
							<i class="fa-solid fa-check-double me-1"></i>Mark All Read
						</button>
						<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addNotificationModal">
							<i class="fa-solid fa-circle-plus me-1"></i>Add Notification
						</button>
					</div>
				</div>
			</div>

			<div class="row g-4">
				<div class="col-xl-8">
					<div class="card quick-card mb-4">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-3">
								<div class="h6 mb-0">Notifications</div>
								<span class="badge bg-light text-dark border"><?php echo $totalCount; ?> total</span>
							</div>
							<div class="table-responsive">
								<table class="table align-middle" id="notificationsTable">
									<thead>
										<tr>
											<th>Message</th>
											<th>Status</th>
											<th>Created</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php while($row = $notifications->fetch_assoc()): ?>
										<tr data-notif='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>' class="<?php echo $row['is_read'] ? 'notification-read' : 'notification-unread'; ?>">
											<td class="fw-semibold" style="max-width:420px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($row['message']); ?>"><?php echo htmlspecialchars($row['message']); ?></td>
											<td>
												<?php if ($row['is_read']): ?>
													<span class="badge bg-success">Read</span>
												<?php else: ?>
													<span class="badge bg-warning">Unread</span>
												<?php endif; ?>
											</td>
											<td class="text-muted"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
											<td class="text-end actions-cell">
												<?php if (!$row['is_read']): ?>
													<button class="btn btn-sm btn-outline-success btn-mark-read me-2" data-bs-toggle="modal" data-bs-target="#markReadModal" title="Mark as Read"><i class="fa-solid fa-check"></i></button>
												<?php endif; ?>
												<button class="btn btn-sm btn-outline-secondary btn-edit me-2" data-bs-toggle="modal" data-bs-target="#editNotificationModal" title="Edit Notification"><i class="fa-solid fa-pen"></i></button>
												<button class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Delete Notification"><i class="fa-solid fa-trash"></i></button>
											</td>
										</tr>
										<?php endwhile; ?>
									</tbody>
								</table>
							</div>

							<?php if ($totalPages > 1): ?>
							<nav aria-label="Notifications pagination">
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

				<div class="col-xl-4">
					<div class="card h-100 sticky-side">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-2">
								<div class="h6 mb-0">Recent Notifications</div>
								<span class="badge bg-light text-dark border">Latest</span>
							</div>
							<div class="list-activity">
								<?php while($n = $recentNotifications->fetch_assoc()): ?>
								<div class="item-row d-flex align-items-center justify-content-between" style="padding:10px 12px; border:1px solid var(--line); border-radius:12px; margin-bottom:10px; background:#fff;">
									<div class="flex-grow-1">
										<div class="item-title fw-semibold" style="font-size:0.9rem;"><?php echo htmlspecialchars(substr($n['message'], 0, 50)) . (strlen($n['message']) > 50 ? '...' : ''); ?></div>
										<div class="text-muted small"><?php echo $n['created_at']; ?></div>
									</div>
									<?php if (!$n['is_read']): ?>
										<span class="badge bg-warning">Unread</span>
									<?php else: ?>
										<span class="badge bg-success">Read</span>
									<?php endif; ?>
								</div>
								<?php endwhile; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Add Notification Modal -->
	<div class="modal fade" id="addNotificationModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Add New Notification</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_notification">
						<div class="mb-3">
							<label class="form-label">Message</label>
							<textarea class="form-control" name="message" rows="4" required placeholder="Enter notification message..."></textarea>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">Add Notification</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Edit Notification Modal -->
	<div class="modal fade" id="editNotificationModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Edit Notification</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit_notification">
						<input type="hidden" name="id" id="edit_id">
						<div class="mb-3">
							<label class="form-label">Message</label>
							<textarea class="form-control" name="message" id="edit_message" rows="4" required placeholder="Enter notification message..."></textarea>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">Update Notification</button>
					</div>
				</form>
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
						<button type="submit" class="btn btn-success">Mark as Read</button>
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
						<button type="submit" class="btn btn-warning">Mark All as Read</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Delete Confirmation Modal -->
	<div class="modal fade" id="deleteModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Confirm Delete</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to delete this notification? This action cannot be undone.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="delete_notification">
						<input type="hidden" name="id" id="delete_id">
						<button type="submit" class="btn btn-danger">Delete</button>
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
					const data = JSON.parse(tr.getAttribute('data-notif'));
					document.getElementById('edit_id').value = data.id;
					document.getElementById('edit_message').value = data.message || '';
				});
			});

			document.querySelectorAll('.btn-mark-read').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-notif'));
					document.getElementById('mark_read_id').value = data.id;
				});
			});

			document.querySelectorAll('.btn-delete').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-notif'));
					document.getElementById('delete_id').value = data.id;
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
