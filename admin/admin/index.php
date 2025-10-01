<?php
require_once __DIR__ . '/../auth.php';

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

// Handle admin user operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$mysqli = db();

	if ($action === 'add_admin') {
		$username = trim($_POST['username'] ?? '');
		$password = $_POST['password'] ?? '';
		$email = trim($_POST['email'] ?? '');
		
		if ($username && $password && $email) {
			// Check if username already exists
			$check_stmt = $mysqli->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ?');
			$check_stmt->bind_param('s', $username);
			$check_stmt->execute();
			$exists = $check_stmt->get_result()->fetch_row()[0];
			$check_stmt->close();
			
			if ($exists > 0) {
				$_SESSION['error_message'] = 'Username already exists!';
			} else {
				$hashed_password = password_hash($password, PASSWORD_DEFAULT);
				$stmt = $mysqli->prepare('INSERT INTO admin_users (username, password, email, created_at) VALUES (?, ?, ?, NOW())');
				if ($stmt) {
					$stmt->bind_param('sss', $username, $hashed_password, $email);
					if ($stmt->execute()) {
						$_SESSION['success_message'] = 'Admin user added successfully!';
						logActivity($mysqli, 'Added admin user', 'Username: ' . $username);
					} else {
						$_SESSION['error_message'] = 'Failed to add admin user: ' . $mysqli->error;
					}
					$stmt->close();
				} else {
					$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
				}
			}
		} else {
			$_SESSION['error_message'] = 'Username, password, and email are required.';
		}
	} elseif ($action === 'edit_admin') {
		$id = (int)($_POST['id'] ?? 0);
		$username = trim($_POST['username'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = $_POST['password'] ?? '';
		
		if ($id && $username && $email) {
			// Check if username already exists for different user
			$check_stmt = $mysqli->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ? AND id != ?');
			$check_stmt->bind_param('si', $username, $id);
			$check_stmt->execute();
			$exists = $check_stmt->get_result()->fetch_row()[0];
			$check_stmt->close();
			
			if ($exists > 0) {
				$_SESSION['error_message'] = 'Username already exists!';
			} else {
				if ($password) {
					// Update with new password
					$hashed_password = password_hash($password, PASSWORD_DEFAULT);
					$stmt = $mysqli->prepare('UPDATE admin_users SET username = ?, password = ?, email = ? WHERE id = ?');
					$stmt->bind_param('sssi', $username, $hashed_password, $email, $id);
				} else {
					// Update without changing password
					$stmt = $mysqli->prepare('UPDATE admin_users SET username = ?, email = ? WHERE id = ?');
					$stmt->bind_param('ssi', $username, $email, $id);
				}
				
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Admin user updated successfully!';
						logActivity($mysqli, 'Updated admin user', 'Username: ' . $username . ', ID: ' . $id);
					} else {
						$_SESSION['error_message'] = 'No changes made or admin user not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to update admin user: ' . $mysqli->error;
				}
				$stmt->close();
			}
		} else {
			$_SESSION['error_message'] = 'Username and email are required.';
		}
	} elseif ($action === 'suspend_admin') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			// Prevent suspending current user
			if ($id == $_SESSION['admin_id']) {
				$_SESSION['error_message'] = 'You cannot suspend your own account!';
			} else {
				// Get username for logging
				$get_stmt = $mysqli->prepare('SELECT username FROM admin_users WHERE id = ?');
				$get_stmt->bind_param('i', $id);
				$get_stmt->execute();
				$user = $get_stmt->get_result()->fetch_assoc();
				$get_stmt->close();
				
				$stmt = $mysqli->prepare('UPDATE admin_users SET status = ? WHERE id = ?');
				if ($stmt) {
					$status = 'suspended';
					$stmt->bind_param('si', $status, $id);
					if ($stmt->execute()) {
						if ($stmt->affected_rows > 0) {
							$_SESSION['success_message'] = 'Admin user suspended successfully!';
							logActivity($mysqli, 'Suspended admin user', 'Username: ' . ($user['username'] ?? 'Unknown') . ', ID: ' . $id);
						} else {
							$_SESSION['error_message'] = 'Admin user not found.';
						}
					} else {
						$_SESSION['error_message'] = 'Failed to suspend admin user: ' . $mysqli->error;
					}
					$stmt->close();
				} else {
					$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
				}
			}
		}
	} elseif ($action === 'activate_admin') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			// Get username for logging
			$get_stmt = $mysqli->prepare('SELECT username FROM admin_users WHERE id = ?');
			$get_stmt->bind_param('i', $id);
			$get_stmt->execute();
			$user = $get_stmt->get_result()->fetch_assoc();
			$get_stmt->close();
			
			$stmt = $mysqli->prepare('UPDATE admin_users SET status = ? WHERE id = ?');
			if ($stmt) {
				$status = 'active';
				$stmt->bind_param('si', $status, $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Admin user activated successfully!';
						logActivity($mysqli, 'Activated admin user', 'Username: ' . ($user['username'] ?? 'Unknown') . ', ID: ' . $id);
					} else {
						$_SESSION['error_message'] = 'Admin user not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to activate admin user: ' . $mysqli->error;
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

// Get admin users with search and pagination
$mysqli = db();
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
	$whereClause = ' WHERE username LIKE ? OR email LIKE ?';
	$types = 'ss';
	$searchParam1 = '%' . $mysqli->real_escape_string($search) . '%';
	$searchParam2 = '%' . $mysqli->real_escape_string($search) . '%';
	$params[] = $searchParam1;
	$params[] = $searchParam2;
}

$sql = 'SELECT id, username, email, status, created_at FROM admin_users' . $whereClause . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$adminUsers = $stmt ? $stmt->get_result() : $mysqli->query('SELECT id, username, email, status, created_at FROM admin_users ORDER BY created_at DESC LIMIT 10');

// Count
$countSql = 'SELECT COUNT(*) FROM admin_users' . $whereClause;
$countStmt = $mysqli->prepare($countSql);
if ($countStmt && $search) {
	$countSearchParam1 = '%' . $mysqli->real_escape_string($search) . '%';
	$countSearchParam2 = '%' . $mysqli->real_escape_string($search) . '%';
	$countStmt->bind_param('ss', $countSearchParam1, $countSearchParam2);
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
	<title>Admin Users - Big Deal</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
		/* Base */
		
		body{ background:var(--bg); color:#111827; }

		/* Topbar */

		/* Cards */
		
		.card-stat{ box-shadow:0 8px 24px rgba(0,0,0,.05); }
		/* Toolbar */
		.toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
		.toolbar .row-top{ display:flex; gap:12px; align-items:center; }
		/* Table */
		.table{ --bs-table-bg:transparent; border-collapse:collapse; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border-bottom:1px solid var(--line); }
		/* Use row-level separators to avoid tiny gaps and remove default cell bottom borders */
		.table tbody td{ border-top:0; border-bottom:0; }
		.table tbody tr{ box-shadow: inset 0 1px 0 var(--line); }
		.table tbody tr:last-child{ box-shadow: inset 0 1px 0 var(--line), inset 0 -1px 0 var(--line); }
		.table tbody tr:hover{ background:#f9fafb; }
		/* Actions cell */
		.actions-cell{ display:flex; gap:8px; justify-content:flex-start; align-items:center; }
		.actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; flex-shrink:0; }
		.actions-cell .btn:disabled{ opacity:0.5; cursor:not-allowed; }
		
		/* Button consistency */
		.btn{ border-radius:8px; font-weight:500; }
		.btn-sm{ padding:0.5rem 1rem; font-size:0.875rem; }
		.btn-animated-suspend{ padding:0.5rem 1rem; font-size:0.875rem; background:#f59e0b; color:#fff; border:1px solid #d97706; border-radius:8px; display:inline-flex; align-items:center; gap:.5rem; transition: all 0.2s ease; }
		.btn-animated-suspend:hover{ background:#d97706; border-color:#b45309; transform: translateY(-1px); box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.2); }
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
		/* Badges */
		.badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
		/* Buttons */
		.btn-primary{ background:var(--primary); border-color:var(--primary); }
		.btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
        /* Modal backdrop blur */
        .modal-backdrop.show{ background: rgba(0,0,0,.25); backdrop-filter: blur(6px); }

        /* Ensure modals are nicely centered */
        .modal-dialog.modal-dialog-centered{ display:flex; align-items:center; min-height: calc(100% - 1rem); }

        /* Mobile responsiveness */

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
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('admin'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Admin Users'); ?>

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

			<!-- Search toolbar -->
			<div class="toolbar mb-4">
				<div class="row-top">
					<form class="d-flex flex-grow-1" method="get">
						<div class="input-group">
							<span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
							<input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search admin users by username or email">
						</div>
						<button class="btn btn-primary ms-2" type="submit">Search</button>
						<a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
					</form>
					<button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
						<span class="text">Add Admin</span>
						<span class="icon">
							<svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
							<span class="buttonSpan">+</span>
						</span>
					</button>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-body">
					<div class="d-flex align-items-center justify-content-between mb-3">
						<div class="h6 mb-0">Admin Users</div>
						<span class="badge bg-light text-dark border"><?php echo $totalCount; ?> total</span>
					</div>
					<div class="table-responsive">
						<table class="table align-middle" id="adminUsersTable">
							<thead>
								<tr>
									<th>Username</th>
									<th>Email</th>
									<th>Status</th>
									<th>Created</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php while($row = $adminUsers->fetch_assoc()): ?>
								<tr data-admin='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
									<td class="fw-semibold"><?php echo htmlspecialchars($row['username']); ?></td>
									<td class="text-muted"><?php echo htmlspecialchars($row['email']); ?></td>
									<td>
										<?php if ($row['status'] == 'active'): ?>
											<span class="badge bg-success">Active</span>
										<?php else: ?>
											<span class="badge bg-warning">Suspended</span>
										<?php endif; ?>
									</td>
									<td class="text-muted"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td class="text-end actions-cell">
                                        <button class="modern-btn edit-btn btn-edit me-2" data-bs-toggle="modal" data-bs-target="#editAdminModal" title="Edit Admin"><span class="icon"><i class="fa-solid fa-pen"></i></span></button>
                                        <?php if ($row['status'] == 'active'): ?>
                                            <?php if ((int)$row['id'] !== (int)($_SESSION['admin_id'] ?? 0)): ?>
                                                <button class="modern-btn view-btn btn-suspend" data-bs-toggle="modal" data-bs-target="#suspendModal" title="Suspend Admin"><span class="icon"><i class="fa-solid fa-ban"></i></span></button>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">You</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="modern-btn view-btn btn-activate" data-bs-toggle="modal" data-bs-target="#activateModal" title="Activate Admin"><span class="icon"><i class="fa-solid fa-check"></i></span></button>
                                        <?php endif; ?>
                                    </td>
								</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>

					<?php if ($totalPages > 1): ?>
					<nav aria-label="Admin users pagination">
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

	<!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Add New Admin User</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_admin">
						<div class="mb-3">
							<label class="form-label">Username</label>
							<input type="text" class="form-control" name="username" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Email</label>
							<input type="email" class="form-control" name="email" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Password</label>
							<input type="password" class="form-control" name="password" required>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn-animated-confirm noselect">
							<span class="text">Add Admin</span>
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

	<!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Edit Admin User</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit_admin">
						<input type="hidden" name="id" id="edit_id">
						<div class="mb-3">
							<label class="form-label">Username</label>
							<input type="text" class="form-control" name="username" id="edit_username" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Email</label>
							<input type="email" class="form-control" name="email" id="edit_email" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Password <small class="text-muted">(Leave blank to keep current password)</small></label>
							<input type="password" class="form-control" name="password" id="edit_password">
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn-animated-confirm noselect">
							<span class="text">Update Admin</span>
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

	<!-- Suspend Confirmation Modal -->
	<div class="modal fade" id="suspendModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Confirm Suspend</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to suspend this admin user? They will not be able to access the admin panel until reactivated.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="suspend_admin">
						<input type="hidden" name="id" id="suspend_id">
						<button type="submit" class="btn-animated-suspend noselect">
							<span class="text">Suspend</span>
							<span class="icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
									<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
								</svg>
							</span>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Activate Confirmation Modal -->
	<div class="modal fade" id="activateModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Confirm Activation</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to activate this admin user? They will regain access to the admin panel.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="activate_admin">
						<input type="hidden" name="id" id="activate_id">
						<button type="submit" class="btn btn-success">Activate</button>
					</form>
				</div>
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

			// Ripple + click animation for animated buttons
			document.addEventListener('click', function(e){
				const animatedBtn = e.target.closest('.btn-animated-suspend');
				if (animatedBtn) {
					const rect = animatedBtn.getBoundingClientRect();
					const radius = Math.max(rect.width, rect.height) / 2;
					const diameter = radius * 2;

					const circle = document.createElement('span');
					circle.style.position = 'absolute';
					circle.style.borderRadius = '50%';
					circle.style.background = 'rgba(255, 255, 255, 0.4)';
					circle.style.transform = 'scale(0)';
					circle.style.animation = 'ripple-animation 0.6s linear';
					circle.style.pointerEvents = 'none';
					circle.style.width = circle.style.height = `${diameter}px`;
					circle.style.left = `${(e.clientX - rect.left) - radius}px`;
					circle.style.top = `${(e.clientY - rect.top) - radius}px`;

					const existing = animatedBtn.querySelector('.ripple');
					if (existing) existing.remove();
					animatedBtn.appendChild(circle);

					animatedBtn.style.animation = 'none';
					animatedBtn.style.transform = 'scale(0.95)';
					setTimeout(() => {
						animatedBtn.style.animation = '';
						animatedBtn.style.transform = '';
					}, 150);
				}
			});

			// If edit_id is present in URL, pre-open edit modal for that admin
			(function(){
				const params = new URLSearchParams(window.location.search);
				const editId = params.get('edit_id');
				if (editId) {
					const row = Array.from(document.querySelectorAll('#adminUsersTable tbody tr')).find(tr => {
						try { return JSON.parse(tr.getAttribute('data-admin')).id == editId; } catch(e) { return false; }
					});
					if (row) {
						const data = JSON.parse(row.getAttribute('data-admin'));
						document.getElementById('edit_id').value = data.id;
						document.getElementById('edit_username').value = data.username || '';
						document.getElementById('edit_email').value = data.email || '';
						document.getElementById('edit_password').value = '';
						const modal = new bootstrap.Modal(document.getElementById('editAdminModal'));
						modal.show();
					}
				}
			})();
			document.querySelectorAll('.btn-edit').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-admin'));
					document.getElementById('edit_id').value = data.id;
					document.getElementById('edit_username').value = data.username || '';
					document.getElementById('edit_email').value = data.email || '';
					document.getElementById('edit_password').value = '';
				});
			});

			document.querySelectorAll('.btn-suspend').forEach(btn => {
				btn.addEventListener('click', function(e){
					if (this.disabled) {
						e.preventDefault();
						e.stopPropagation();
						return false;
					}
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-admin'));
					document.getElementById('suspend_id').value = data.id;
				});
			});

			document.querySelectorAll('.btn-activate').forEach(btn => {
				btn.addEventListener('click', function(e){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-admin'));
					document.getElementById('activate_id').value = data.id;
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
