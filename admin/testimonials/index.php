<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
	$mysqli = db();
	$res = $mysqli->query($sql);
	$row = $res ? $res->fetch_row() : [0];
	return (int)($row[0] ?? 0);
}

// Handle testimonial operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$mysqli = db();

	if ($action === 'add_testimonial') {
		$name = trim($_POST['name'] ?? '');
		$feedback = trim($_POST['feedback'] ?? '');
		$rating = (int)($_POST['rating'] ?? 0);
		$profile_image = '';
		$home_image = '';
		
		// Handle profile image upload
		if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
			$upload_dir = '../../uploads/testimonials/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0777, true);
			}
			$file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
			$profile_image = 'testimonial_profile_' . time() . '_' . uniqid() . '.' . $file_extension;
			$upload_path = $upload_dir . $profile_image;
			if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
				$_SESSION['error_message'] = 'Failed to upload profile image.';
				header('Location: index.php');
				exit();
			}
		}
		
		// Handle home image upload
		if (isset($_FILES['home_image']) && $_FILES['home_image']['error'] === UPLOAD_ERR_OK) {
			$upload_dir = '../../uploads/testimonials/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0777, true);
			}
			$file_extension = pathinfo($_FILES['home_image']['name'], PATHINFO_EXTENSION);
			$home_image = 'testimonial_home_' . time() . '_' . uniqid() . '.' . $file_extension;
			$upload_path = $upload_dir . $home_image;
			if (!move_uploaded_file($_FILES['home_image']['tmp_name'], $upload_path)) {
				$_SESSION['error_message'] = 'Failed to upload home image.';
				header('Location: index.php');
				exit();
			}
		}
		
		if ($name && $feedback && $rating >= 1 && $rating <= 5) {
			$stmt = $mysqli->prepare('INSERT INTO testimonials (name, feedback, rating, profile_image, home_image) VALUES (?,?,?,?,?)');
			if ($stmt) {
				$stmt->bind_param('ssiss', $name, $feedback, $rating, $profile_image, $home_image);
				if ($stmt->execute()) {
					$_SESSION['success_message'] = 'Testimonial added successfully!';
				} else {
					$_SESSION['error_message'] = 'Failed to add testimonial: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		} else {
			$_SESSION['error_message'] = 'All fields are required and rating must be 1-5.';
		}
	} elseif ($action === 'edit_testimonial') {
		$id = (int)($_POST['id'] ?? 0);
		$name = trim($_POST['name'] ?? '');
		$feedback = trim($_POST['feedback'] ?? '');
		$rating = (int)($_POST['rating'] ?? 0);
		$profile_image = $_POST['current_profile_image'] ?? '';
		$home_image = $_POST['current_home_image'] ?? '';
		
		// Handle profile image upload
		if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
			$upload_dir = '../../uploads/testimonials/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0777, true);
			}
			$file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
			$profile_image = 'testimonial_profile_' . time() . '_' . uniqid() . '.' . $file_extension;
			$upload_path = $upload_dir . $profile_image;
			if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
				$_SESSION['error_message'] = 'Failed to upload profile image.';
				header('Location: index.php');
				exit();
			}
		}
		
		// Handle home image upload
		if (isset($_FILES['home_image']) && $_FILES['home_image']['error'] === UPLOAD_ERR_OK) {
			$upload_dir = '../../uploads/testimonials/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0777, true);
			}
			$file_extension = pathinfo($_FILES['home_image']['name'], PATHINFO_EXTENSION);
			$home_image = 'testimonial_home_' . time() . '_' . uniqid() . '.' . $file_extension;
			$upload_path = $upload_dir . $home_image;
			if (!move_uploaded_file($_FILES['home_image']['tmp_name'], $upload_path)) {
				$_SESSION['error_message'] = 'Failed to upload home image.';
				header('Location: index.php');
				exit();
			}
		}
		
		if ($id && $name && $feedback && $rating >= 1 && $rating <= 5) {
			$stmt = $mysqli->prepare('UPDATE testimonials SET name = ?, feedback = ?, rating = ?, profile_image = ?, home_image = ? WHERE id = ?');
			if ($stmt) {
				$stmt->bind_param('ssissi', $name, $feedback, $rating, $profile_image, $home_image, $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Testimonial updated successfully!';
					} else {
						$_SESSION['error_message'] = 'No changes made or testimonial not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to update testimonial: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		} else {
			$_SESSION['error_message'] = 'All fields are required and rating must be 1-5.';
		}
	} elseif ($action === 'delete_testimonial') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			$stmt = $mysqli->prepare('DELETE FROM testimonials WHERE id = ?');
			if ($stmt) {
				$stmt->bind_param('i', $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Testimonial deleted successfully!';
					} else {
						$_SESSION['error_message'] = 'Testimonial not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to delete testimonial: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		}
		// Close action === 'delete_testimonial'
	}

	header('Location: index.php');
	exit();
}

// Get testimonials with search and pagination
$mysqli = db();
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
	$whereClause = ' WHERE name LIKE ? OR feedback LIKE ?';
	$types = 'ss';
	$searchParam1 = '%' . $mysqli->real_escape_string($search) . '%';
	$searchParam2 = '%' . $mysqli->real_escape_string($search) . '%';
	$params[] = $searchParam1;
	$params[] = $searchParam2;
}

$sql = 'SELECT id, name, feedback, rating, profile_image, home_image, created_at FROM testimonials' . $whereClause . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$testimonials = $stmt ? $stmt->get_result() : $mysqli->query('SELECT id, name, feedback, rating, profile_image, home_image, created_at FROM testimonials ORDER BY created_at DESC LIMIT 10');

// Count
$countSql = 'SELECT COUNT(*) FROM testimonials' . $whereClause;
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

// Recent
$recentTestimonials = $mysqli->query("SELECT name, rating, DATE_FORMAT(created_at,'%b %d, %Y') as created_at FROM testimonials ORDER BY created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Testimonials - Big Deal</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<link href="../../assets/css/animated-buttons.css" rel="stylesheet">
	<style>
		/* Base */
		
		body{ background:var(--bg); color:#111827; }

		/* Topbar */

		/* Cards */
		
		.quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
		/* Toolbar */
		.toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
		.toolbar .row-top{ display:flex; gap:12px; align-items:center; }
		/* Table */
		.table{ --bs-table-bg:transparent; border-collapse:collapse; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border-bottom:1px solid var(--line); }
		.table thead th.actions-cell{ text-align:center; padding-right:0; }
		.table thead th:last-child{ text-align:center; padding-right:0; }
		/* Use row-level separators to avoid tiny gaps between cells */
		.table tbody td{ border-top:0; border-bottom:0; }
		.table tbody td.actions-cell{ padding-right:0; display:flex; justify-content:center; }
		.table tbody tr{ box-shadow: inset 0 1px 0 var(--line); }
		.table tbody tr:last-child{ box-shadow: inset 0 1px 0 var(--line), inset 0 -1px 0 var(--line); }
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
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('testimonials'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Testimonials'); ?>

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
							<input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search testimonials by name or feedback">
						</div>
						<button class="btn btn-primary ms-2" type="submit">Search</button>
						<a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
					</form>
					<button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addTestimonialModal">
						<span class="text">Add Testimonial</span>
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
								<div class="h6 mb-0">Testimonials</div>
								<span class="badge bg-light text-dark border"><?php echo $totalCount; ?> total</span>
							</div>
							<div class="table-responsive">
								<table class="table align-middle" id="testimonialsTable">
									<thead>
										<tr>
											<th>Name</th>
											<th>Feedback</th>
											<th class="text-center">Rating</th>
											<th>Home Image</th>
											<th>Created</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php while($row = $testimonials->fetch_assoc()): ?>
										<tr data-test='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
											<td class="fw-semibold">
												<div class="d-flex align-items-center gap-2">
													<?php if ($row['profile_image']): ?>
														<img src="../../uploads/testimonials/<?php echo htmlspecialchars($row['profile_image']); ?>" alt="Profile" style="width: 36px; height: 36px; object-fit: cover; border-radius: 50%;">
													<?php else: ?>
														<span class="d-inline-block rounded-circle bg-light border" style="width:36px; height:36px;"></span>
													<?php endif; ?>
													<span><?php echo htmlspecialchars($row['name']); ?></span>
												</div>
											</td>
											<td class="text-muted" style="max-width:420px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($row['feedback']); ?>"><?php echo htmlspecialchars($row['feedback']); ?></td>
											<td class="text-center align-middle"><?php echo (int)$row['rating']; ?>/5</td>
											<td>
												<?php if ($row['home_image']): ?>
													<img src="../../uploads/testimonials/<?php echo htmlspecialchars($row['home_image']); ?>" alt="Home" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
												<?php else: ?>
													<span class="text-muted">No image</span>
												<?php endif; ?>
											</td>
											<td class="text-muted"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
											<td class="text-end actions-cell">
												<button class="modern-btn edit-btn btn-edit me-2" data-bs-toggle="modal" data-bs-target="#editTestimonialModal" title="Edit Testimonial"><span class="icon"><i class="fa-solid fa-pen"></i></span></button>
												<button class="modern-btn delete-btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Delete Testimonial"><span class="icon"><i class="fa-solid fa-trash"></i></span></button>
											</td>
										</tr>
										<?php endwhile; ?>
									</tbody>
								</table>
							</div>

							<?php if ($totalPages > 1): ?>
							<nav aria-label="Testimonials pagination">
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

				<!-- <div class="col-xl-4">
					<div class="card h-100 sticky-side">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-2">
								<div class="h6 mb-0">Recent Testimonials</div>
								<span class="badge bg-light text-dark border">Latest</span>
							</div>
							<div class="list-activity">
								<?php while($t = $recentTestimonials->fetch_assoc()): ?>
								<div class="item-row d-flex align-items-center justify-content-between" style="padding:10px 12px; border:1px solid var(--line); border-radius:12px; margin-bottom:10px; background:#fff;">
									<span class="item-title fw-semibold"><?php echo htmlspecialchars($t['name']); ?></span>
									<span class="text-warning"><?php echo str_repeat('★', (int)$t['rating']); ?><span class="text-muted"><?php echo str_repeat('☆', 5-(int)$t['rating']); ?></span></span>
								</div>
								<?php endwhile; ?>
							</div>
						</div>
					</div>
				</div> -->
			</div>
		</div>
	</div>

    <style>
    /* Center modals and blur backdrop; match blogs add/edit input styles */
    .modal-dialog.modal-dialog-centered { display:flex; align-items:center; min-height:calc(100% - 3.5rem); }
    .modal-backdrop.show { background: rgba(0,0,0,.4); backdrop-filter: blur(3px); }
    .modal-content { border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.3); border:1px solid rgba(255,255,255,0.1); }
    .modal-body label.form-label { font-size:.9rem; color:#555; font-weight:500; margin-bottom:6px; }
    .modal-body input[type="text"], .modal-body textarea, .modal-body select, .modal-body input[type="file"] { width:100%; padding:.75rem; border:1px solid #E0E0E0; border-radius:8px; font-size:1rem; box-sizing:border-box; }
    .modal-footer { gap:.5rem; }
    .btn.btn-secondary { background:#fff; color:#111827; border:1px solid #E0E0E0; }
    .btn.btn-secondary:hover { background:#f5f5f5; }
    </style>

    <!-- Add Testimonial Modal -->
	<div class="modal fade" id="addTestimonialModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Add New Testimonial</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST" enctype="multipart/form-data">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_testimonial">
						<div class="mb-3">
							<label class="form-label">Name</label>
							<input type="text" class="form-control" name="name" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Feedback</label>
							<textarea class="form-control" name="feedback" rows="5" required></textarea>
						</div>
						<div class="mb-3">
							<label class="form-label">Rating (1-5)</label>
							<select class="form-select" name="rating" required>
								<?php for($r=5; $r>=1; $r--): ?>
									<option value="<?php echo $r; ?>"><?php echo $r; ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">Profile Image</label>
							<input type="file" class="form-control" name="profile_image" accept="image/*">
							<small class="text-muted">Upload a profile picture for the testimonial</small>
						</div>
						<div class="mb-3">
							<label class="form-label">Home Image</label>
							<input type="file" class="form-control" name="home_image" accept="image/*">
							<small class="text-muted">Upload a home/property image for the testimonial</small>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn-animated-confirm noselect">
							<span class="text">Add Testimonial</span>
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

	<!-- Edit Testimonial Modal -->
	<div class="modal fade" id="editTestimonialModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Edit Testimonial</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST" enctype="multipart/form-data">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit_testimonial">
						<input type="hidden" name="id" id="edit_id">
						<input type="hidden" name="current_profile_image" id="edit_current_profile_image">
						<input type="hidden" name="current_home_image" id="edit_current_home_image">
						<div class="mb-3">
							<label class="form-label">Name</label>
							<input type="text" class="form-control" name="name" id="edit_name" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Feedback</label>
							<textarea class="form-control" name="feedback" id="edit_feedback" rows="5" required></textarea>
						</div>
						<div class="mb-3">
							<label class="form-label">Rating (1-5)</label>
							<select class="form-select" name="rating" id="edit_rating" required>
								<?php for($r=5; $r>=1; $r--): ?>
									<option value="<?php echo $r; ?>"><?php echo $r; ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">Profile Image</label>
							<div id="edit_profile_image_preview" class="mb-2"></div>
							<input type="file" class="form-control" name="profile_image" accept="image/*">
							<small class="text-muted">Upload a new profile picture to replace the current one</small>
						</div>
						<div class="mb-3">
							<label class="form-label">Home Image</label>
							<div id="edit_home_image_preview" class="mb-2"></div>
							<input type="file" class="form-control" name="home_image" accept="image/*">
							<small class="text-muted">Upload a new home/property image to replace the current one</small>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn-animated-confirm noselect">
							<span class="text">Update Testimonial</span>
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
	<div class="modal fade" id="deleteModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Confirm Delete</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to delete this testimonial? This action cannot be undone.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="delete_testimonial">
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
			document.querySelectorAll('.btn-edit').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-test'));
					document.getElementById('edit_id').value = data.id;
					document.getElementById('edit_name').value = data.name || '';
					document.getElementById('edit_feedback').value = data.feedback || '';
					document.getElementById('edit_rating').value = data.rating || 5;
					document.getElementById('edit_current_profile_image').value = data.profile_image || '';
					document.getElementById('edit_current_home_image').value = data.home_image || '';
					
					// Update image previews
					const profilePreview = document.getElementById('edit_profile_image_preview');
					const homePreview = document.getElementById('edit_home_image_preview');
					
					if (data.profile_image) {
						profilePreview.innerHTML = '<img src="../../uploads/testimonials/' + data.profile_image + '" alt="Current Profile" style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd;">';
					} else {
						profilePreview.innerHTML = '<span class="text-muted">No profile image</span>';
					}
					
					if (data.home_image) {
						homePreview.innerHTML = '<img src="../../uploads/testimonials/' + data.home_image + '" alt="Current Home" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;">';
					} else {
						homePreview.innerHTML = '<span class="text-muted">No home image</span>';
					}
				});
			});

			document.querySelectorAll('.btn-delete').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-test'));
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
