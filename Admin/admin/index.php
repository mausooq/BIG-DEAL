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
	} elseif ($action === 'delete_admin') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			// Prevent deleting current user
			if ($id == $_SESSION['admin_id']) {
				$_SESSION['error_message'] = 'You cannot delete your own account!';
			} else {
				// Get username for logging
				$get_stmt = $mysqli->prepare('SELECT username FROM admin_users WHERE id = ?');
				$get_stmt->bind_param('i', $id);
				$get_stmt->execute();
				$user = $get_stmt->get_result()->fetch_assoc();
				$get_stmt->close();
				
				$stmt = $mysqli->prepare('DELETE FROM admin_users WHERE id = ?');
				if ($stmt) {
					$stmt->bind_param('i', $id);
					if ($stmt->execute()) {
						if ($stmt->affected_rows > 0) {
							$_SESSION['success_message'] = 'Admin user deleted successfully!';
							logActivity($mysqli, 'Deleted admin user', 'Username: ' . ($user['username'] ?? 'Unknown') . ', ID: ' . $id);
						} else {
							$_SESSION['error_message'] = 'Admin user not found.';
						}
					} else {
						$_SESSION['error_message'] = 'Failed to delete admin user: ' . $mysqli->error;
					}
					$stmt->close();
				} else {
					$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
				}
			}
		}
	}

	header('Location: index.php');
	exit();
}

// Stats
$totalAdmins = fetchScalar('SELECT COUNT(*) FROM admin_users');
$totalBlogs = fetchScalar('SELECT COUNT(*) FROM blogs');
$totalProperties = fetchScalar('SELECT COUNT(*) FROM properties');
$totalEnquiries = fetchScalar('SELECT COUNT(*) FROM enquiries');

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

$sql = 'SELECT id, username, email, created_at FROM admin_users' . $whereClause . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$adminUsers = $stmt ? $stmt->get_result() : $mysqli->query('SELECT id, username, email, created_at FROM admin_users ORDER BY created_at DESC LIMIT 10');

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

// Recent admin users
$recentAdmins = $mysqli->query("SELECT username, DATE_FORMAT(created_at,'%b %d, %Y') as created_at FROM admin_users ORDER BY created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Users - Big Deal</title>
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
		.actions-cell{ display:flex; gap:8px; justify-content:flex-start; align-items:center; }
		.actions-cell .btn{ width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; flex-shrink:0; }
		.actions-cell .btn:disabled{ opacity:0.5; cursor:not-allowed; }
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
			.table thead th:last-child, .table tbody td:last-child{ text-align:center; }
		}
	</style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('admin'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Search admin users...'); ?>

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
								<div class="text-muted small">Total Admins</div>
								<div class="h4 mb-0"><?php echo $totalAdmins; ?></div>
							</div>
							<div class="text-primary"><i class="fa-solid fa-users fa-lg"></i></div>
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
							<div class="text-success"><i class="fa-regular fa-envelope fa-lg"></i></div>
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
							<div class="text-info"><i class="fa-solid fa-building fa-lg"></i></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card card-stat">
						<div class="card-body d-flex align-items-center justify-content-between">
							<div>
								<div class="text-muted small">Blogs</div>
								<div class="h4 mb-0"><?php echo $totalBlogs; ?></div>
							</div>
							<div class="text-warning"><i class="fa-solid fa-rss fa-lg"></i></div>
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
							<input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search admin users by username or email">
						</div>
						<button class="btn btn-primary ms-2" type="submit">Search</button>
						<a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
					</form>
					<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
						<i class="fa-solid fa-circle-plus me-1"></i>Add Admin
					</button>
				</div>
			</div>

			<div class="row g-4">
				<div class="col-xl-8">
					<div class="card quick-card mb-4">
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
											<th>Created</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php while($row = $adminUsers->fetch_assoc()): ?>
										<tr data-admin='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
											<td class="fw-semibold"><?php echo htmlspecialchars($row['username']); ?></td>
											<td class="text-muted"><?php echo htmlspecialchars($row['email']); ?></td>
											<td class="text-muted"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
											<td class="text-end actions-cell">
												<button class="btn btn-sm btn-outline-secondary btn-edit me-2" data-bs-toggle="modal" data-bs-target="#editAdminModal" title="Edit Admin"><i class="fa-solid fa-pen"></i></button>
												<button class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="<?php echo $row['id'] == $_SESSION['admin_id'] ? 'Cannot delete your own account' : 'Delete Admin'; ?>" <?php echo $row['id'] == $_SESSION['admin_id'] ? 'disabled' : ''; ?>><i class="fa-solid fa-trash"></i></button>
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

				<div class="col-xl-4">
					<div class="card h-100 sticky-side">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-2">
								<div class="h6 mb-0">Recent Admin Users</div>
								<span class="badge bg-light text-dark border">Latest</span>
							</div>
							<div class="list-activity">
								<?php while($a = $recentAdmins->fetch_assoc()): ?>
								<div class="item-row d-flex align-items-center justify-content-between" style="padding:10px 12px; border:1px solid var(--line); border-radius:12px; margin-bottom:10px; background:#fff;">
									<div>
										<span class="item-title fw-semibold"><?php echo htmlspecialchars($a['username']); ?></span>
										<div class="text-muted small"><?php echo htmlspecialchars($a['created_at']); ?></div>
									</div>
									<span class="text-primary"><i class="fa-solid fa-user"></i></span>
								</div>
								<?php endwhile; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Add Admin Modal -->
	<div class="modal fade" id="addAdminModal" tabindex="-1">
		<div class="modal-dialog">
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
						<button type="submit" class="btn btn-primary">Add Admin</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Edit Admin Modal -->
	<div class="modal fade" id="editAdminModal" tabindex="-1">
		<div class="modal-dialog">
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
						<button type="submit" class="btn btn-primary">Update Admin</button>
					</div>
				</form>
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
					<p>Are you sure you want to delete this admin user? This action cannot be undone.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="delete_admin">
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
					const data = JSON.parse(tr.getAttribute('data-admin'));
					document.getElementById('edit_id').value = data.id;
					document.getElementById('edit_username').value = data.username || '';
					document.getElementById('edit_email').value = data.email || '';
					document.getElementById('edit_password').value = '';
				});
			});

			document.querySelectorAll('.btn-delete').forEach(btn => {
				btn.addEventListener('click', function(e){
					if (this.disabled) {
						e.preventDefault();
						e.stopPropagation();
						return false;
					}
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-admin'));
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
