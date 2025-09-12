<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
	$mysqli = db();
	$res = $mysqli->query($sql);
	$row = $res ? $res->fetch_row() : [0];
	return (int)($row[0] ?? 0);
}

// Handle blog operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$mysqli = db();
	
	if ($action === 'add_blog') {
		$title = trim($_POST['title'] ?? '');
		$content = trim($_POST['content'] ?? '');
		$image_url = trim($_POST['image_url'] ?? '');
		
		if ($title && $content) {
			$stmt = $mysqli->prepare("INSERT INTO blogs (title, content, image_url) VALUES (?, ?, ?)");
			if ($stmt) {
				$stmt->bind_param("sss", $title, $content, $image_url);
				if ($stmt->execute()) {
					$_SESSION['success_message'] = 'Blog post added successfully!';
				} else {
					$_SESSION['error_message'] = 'Failed to add blog post: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		} else {
			$_SESSION['error_message'] = 'Title and content are required.';
		}
	} elseif ($action === 'edit_blog') {
		$id = (int)($_POST['id'] ?? 0);
		$title = trim($_POST['title'] ?? '');
		$content = trim($_POST['content'] ?? '');
		$image_url = trim($_POST['image_url'] ?? '');
		
		if ($id && $title && $content) {
			$stmt = $mysqli->prepare("UPDATE blogs SET title = ?, content = ?, image_url = ? WHERE id = ?");
			if ($stmt) {
				$stmt->bind_param("sssi", $title, $content, $image_url, $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Blog post updated successfully!';
					} else {
						$_SESSION['error_message'] = 'No changes made or blog post not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to update blog post: ' . $mysqli->error;
				}
				$stmt->close();
			} else {
				$_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
			}
		} else {
			$_SESSION['error_message'] = 'Title and content are required.';
		}
	} elseif ($action === 'delete_blog') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			$stmt = $mysqli->prepare("DELETE FROM blogs WHERE id = ?");
			if ($stmt) {
				$stmt->bind_param("i", $id);
				if ($stmt->execute()) {
					if ($stmt->affected_rows > 0) {
						$_SESSION['success_message'] = 'Blog post deleted successfully!';
					} else {
						$_SESSION['error_message'] = 'Blog post not found.';
					}
				} else {
					$_SESSION['error_message'] = 'Failed to delete blog post: ' . $mysqli->error;
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


// Get blogs with search and pagination
$mysqli = db();
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
	$whereClause = ' WHERE title LIKE ? OR content LIKE ?';
	$types = 'ss';
	$searchParam1 = '%' . $mysqli->real_escape_string($search) . '%';
	$searchParam2 = '%' . $mysqli->real_escape_string($search) . '%';
	$params[] = $searchParam1;
	$params[] = $searchParam2;
}

$sql = "SELECT id, title, content, image_url, created_at FROM blogs" . $whereClause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) {
	$stmt->bind_param($types, ...$params);
}
$stmt->execute();
$blogs = $stmt->get_result();

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM blogs" . $whereClause;
$countStmt = $mysqli->prepare($countSql);
if ($countStmt && $search) {
	$countSearchParam1 = '%' . $mysqli->real_escape_string($search) . '%';
	$countSearchParam2 = '%' . $mysqli->real_escape_string($search) . '%';
	$countStmt->bind_param('ss', $countSearchParam1, $countSearchParam2);
}
$countStmt->execute();
$totalCount = $countStmt->get_result()->fetch_row()[0];
$totalPages = ceil($totalCount / $limit);

// Recent blogs for sidebar
$recentBlogs = $mysqli->query("SELECT id, title, DATE_FORMAT(created_at,'%b %d, %Y') as created_at FROM blogs ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Blogs - Big Deal</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<link href="../../assets/css/animated-buttons.css" rel="stylesheet">
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
		/* Rounded sidebar with visible radius */
		.sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
		.content{ margin-left:284px; } /* account for sidebar margin */
		.brand{ font-weight:700; font-size:1.25rem; }
		.list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
		.list-group-item i{ width:18px; }
		.list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
		.list-group-item:hover{ background:#f8fafc; }
		/* Topbar */
		.navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
		.text-primary{ color:var(--primary)!important; }
		.input-group .form-control{ border-color:var(--line); }
		.input-group-text{ border-color:var(--line); }
		/* Cards */
		.card{ border:0; border-radius:var(--radius); background:var(--card); }
		.quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
		/* Modern toolbar */
		.toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
		.toolbar .row-top{ display:flex; gap:12px; align-items:center; }
		.toolbar .row-bottom{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
		.toolbar .chip{ padding:6px 12px; border:1px solid var(--line); border-radius:9999px; background:#fff; color:#374151; text-decoration:none; font-size:.875rem; }
		.toolbar .chip:hover{ border-color:#d1d5db; }
		.toolbar .chip.active{ background:var(--primary); border-color:var(--primary); color:#fff; }
		.toolbar .divider{ width:1px; height:24px; background:var(--line); margin:0 4px; }
		/* Table */
		.table{ --bs-table-bg:transparent; border-collapse:collapse; }
		.table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; border-bottom:1px solid var(--line); }
		.table thead th:last-child{ text-align:center; }
		/* Use row-level separators to avoid tiny gaps between cells */
		.table tbody td{ border-top:0; border-bottom:0; }
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
		/* Filters */
		.form-label{ font-weight:600; }
		/* Buttons */
		.btn-primary{ background:var(--primary); border-color:var(--primary); }
		.btn-primary:hover{ background:var(--primary-600); border-color:var(--primary-600); }
		/* Drawer */
		.drawer{ position:fixed; top:0; right:-420px; width:420px; height:100vh; background:#fff; box-shadow:-12px 0 24px rgba(0,0,0,.08); transition:right .25s ease; z-index:1040; }
		.drawer.open{ right:0; }
		.drawer-header{ padding:16px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; }
		.drawer-body{ padding:16px; overflow:auto; height:calc(100vh - 64px); }
		.drawer-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.2); opacity:0; pointer-events:none; transition:opacity .2s ease; z-index:1035; }
		.drawer-backdrop.open{ opacity:1; pointer-events:auto; }
		/* Modern list rows for blogs */
		.item-row{ padding:10px 12px; border:1px solid var(--line); border-radius:12px; margin-bottom:10px; background:#fff; display:flex; align-items:center; justify-content:space-between; gap:12px; }
		.item-row:hover{ box-shadow:0 6px 18px rgba(0,0,0,.06); }
		.item-title{ font-weight:600; }
		.item-meta{ color:#6b7280; font-size:.9rem; }
		/* Modal styles */
		.modal-content{ border:0; border-radius:var(--radius); }
		.modal-header{ border-bottom:1px solid var(--line); }
		.modal-footer{ border-top:1px solid var(--line); }
		/* Mobile responsiveness */
		@media (max-width: 991.98px){
			.sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
			.sidebar.open{ left:12px; }
			.content{ margin-left:0; }
			.table{ font-size:.95rem; }
		}
		@media (max-width: 575.98px){
			.toolbar .row-top{ flex-direction:column; align-items:stretch; }
			.actions-cell{ justify-content:center; }
			.table thead th:last-child, .table tbody td:last-child{ text-align:center; }
		}
	</style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('blogs'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

		<div class="container-fluid p-4">
			<!-- Success/Error Messages -->
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
							<input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search blogs by title or content">
						</div>
						<button class="btn btn-primary ms-2" type="submit">Search</button>
						<a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
					</form>
					<a class="btn-animated-add noselect btn-sm" href="add.php">
						<span class="text">Add Blog</span>
						<span class="icon">
							<svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
							<span class="buttonSpan">+</span>
						</span>
					</a>
				</div>
			</div>

			<div class="row g-4">
				<div class="col-12">
					<div class="card quick-card mb-4">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between mb-3">
								<div class="h6 mb-0">Blog Posts</div>
								<span class="badge bg-light text-dark border"><?php echo $totalCount; ?> total</span>
							</div>
							<div class="table-responsive">
								<table class="table align-middle" id="blogsTable">
									<thead>
										<tr>
											<th>Cover Image</th>
											<th>Title</th>
											<th>Content Preview</th>
											<th>Created</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php while($row = $blogs->fetch_assoc()): ?>
										<tr data-blog='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
											<td>
												<?php if (!empty($row['image_url'])): ?>
													<?php 
														$src = $row['image_url'];
														if (strpos($src, 'http://') !== 0 && strpos($src, 'https://') !== 0) { $src = '../../' . ltrim($src, '/'); }
													?>
													<img src="<?php echo htmlspecialchars($src); ?>" alt="Cover image" style="width:40px;height:40px;object-fit:cover;border-radius:8px;">
												<?php else: ?>
													<span class="text-muted">No image</span>
												<?php endif; ?>
											</td>
											<td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
											<td><?php echo htmlspecialchars(substr($row['content'], 0, 100)) . '...'; ?></td>
											<td class="text-muted"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
											<td class="text-end actions-cell">
										<a class="btn btn-sm btn-outline-secondary me-2" href="edit.php?id=<?php echo (int)$row['id']; ?>" title="Edit Blog">
											<i class="fa-solid fa-pen"></i>
										</a>
										<button class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Delete Blog">
											<i class="fa-solid fa-trash"></i>
										</button>
									</td>
										</tr>
										<?php endwhile; ?>
									</tbody>
								</table>
							</div>
							
							<!-- Pagination -->
							<?php if ($totalPages > 1): ?>
							<nav aria-label="Blog pagination">
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
								<div class="h6 mb-0">Recent Blogs</div>
								<span class="badge bg-light text-dark border">Latest</span>
							</div>
							<div class="list-activity">
								<?php while($b = $recentBlogs->fetch_assoc()): ?>
								<div class="item-row">
									<span class="item-title"><?php echo htmlspecialchars($b['title']); ?></span>
									<span class="item-meta"><?php echo $b['created_at']; ?></span>
								</div>
								<?php endwhile; ?>
							</div>
						</div>
					</div>
				</div> -->
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
					<p>Are you sure you want to delete this blog post? This action cannot be undone.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<form method="POST" style="display: inline;">
						<input type="hidden" name="action" value="delete_blog">
						<input type="hidden" name="id" id="delete_id">
						<button type="submit" class="btn btn-danger">Delete</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		// Handle edit button clicks
		document.addEventListener('DOMContentLoaded', function(){
			document.querySelectorAll('.btn-edit').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-blog'));
					
					document.getElementById('edit_id').value = data.id;
					document.getElementById('edit_title').value = data.title;
					document.getElementById('edit_content').value = data.content;
					document.getElementById('edit_image_url').value = data.image_url || '';
				});
			});

			// Handle delete button clicks
			document.querySelectorAll('.btn-delete').forEach(btn => {
				btn.addEventListener('click', function(){
					const tr = this.closest('tr');
					const data = JSON.parse(tr.getAttribute('data-blog'));
					document.getElementById('delete_id').value = data.id;
				});
			});



			// Form validation for edit blog
			const editBlogModal = document.getElementById('editBlogModal');
			if (editBlogModal) {
				const editForm = editBlogModal.querySelector('form');
				if (editForm) {
					editForm.addEventListener('submit', function(e) {
						const title = this.querySelector('input[name="title"]').value.trim();
						const content = this.querySelector('textarea[name="content"]').value.trim();
						
						if (!title || !content) {
							e.preventDefault();
							alert('Please fill in both title and content fields.');
							return false;
						}
					});
				}
			}

			// Auto-hide alerts after 5 seconds
			setTimeout(function() {
				const alerts = document.querySelectorAll('.alert');
				alerts.forEach(alert => {
					const bsAlert = new bootstrap.Alert(alert);
					bsAlert.close();
				});
			}, 5000);
		});
	</script>
</body>
</html>
