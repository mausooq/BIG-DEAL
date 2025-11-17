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
		$category = trim($_POST['category'] ?? '');
		
        if ($title && $content) {
            $has_admin_id = ($mysqli->query("SHOW COLUMNS FROM blogs LIKE 'admin_id'")?->num_rows ?? 0) > 0;
            if ($has_admin_id) {
                $stmt = $mysqli->prepare("INSERT INTO blogs (title, content, image_url, category, admin_id) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
                    $stmt->bind_param("ssssi", $title, $content, $image_url, $category, $adminId);
                }
            } else {
                $stmt = $mysqli->prepare("INSERT INTO blogs (title, content, image_url, category) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssss", $title, $content, $image_url, $category);
                }
            }
            if ($stmt) {
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
		$category = trim($_POST['category'] ?? '');
		
		if ($id && $title && $content) {
			$stmt = $mysqli->prepare("UPDATE blogs SET title = ?, content = ?, image_url = ?, category = ? WHERE id = ?");
			if ($stmt) {
				$stmt->bind_param("ssssi", $title, $content, $image_url, $category, $id);
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

$sql = "SELECT id, title, content, image_url, created_at, category, tags FROM blogs" . $whereClause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
	<!-- Favicon -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico?v=4">
	<link rel="shortcut icon" href="/favicon.ico?v=4" type="image/x-icon">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<link href="../../assets/css/animated-buttons.css" rel="stylesheet">
	<style>
		/* Base */
		
		body{ background:var(--bg); color:#111827; }

		 /* account for sidebar margin */

		/* Topbar */

		/* Cards */
		
		.quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
		/* Modern toolbar */
		.toolbar{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; display:flex; flex-direction:column; gap:10px; }
		.toolbar .row-top{ display:flex; gap:12px; align-items:center; }
		.toolbar .row-bottom{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
		.toolbar .chip{ padding:6px 12px; border:1px solid var(--line); border-radius:9999px; background:#fff; color:#374151; text-decoration:none; font-size:.875rem; }
		.toolbar .chip:hover{ border-color:#d1d5db; }
		.toolbar .chip.active{ background:var(--primary); border-color:var(--primary); color:#fff; }
		.toolbar .divider{ width:1px; height:24px; background:var(--line); margin:0 4px; }
		/* Badges */
		.badge-soft{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
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
		/* Blog Cards */
		.blog-card{ 
			border:1px solid var(--line); 
			border-radius:var(--radius); 
			overflow:hidden; 
			transition:all 0.3s ease; 
			height:100%; 
			display:flex; 
			flex-direction:column;
		}
		.blog-card:hover{ 
			transform:translateY(-4px); 
		}
		.blog-image-container{ 
			position:relative; 
			height:160px; 
			overflow:hidden; 
			background:transparent;
			margin:0;
			padding:0;
		}
		.blog-image{ 
			width:100%; 
			height:100%; 
			object-fit:cover; 
			object-position:center;
			transition:transform 0.3s ease;
			display:block;
		}
		.blog-card:hover .blog-image{ 
			transform:scale(1.05); 
		}
		.blog-image-placeholder{ 
			width:100%; 
			height:100%; 
			display:flex; 
			flex-direction:column; 
			align-items:center; 
			justify-content:center; 
			color:var(--muted); 
			background:linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
		}
		.blog-image-placeholder i{ 
			font-size:2rem; 
			margin-bottom:0.5rem; 
		}
		/* Fixed action buttons on image */
		.blog-actions-fixed{ position:absolute; top:8px; right:8px; z-index:3; display:flex; gap:6px; }
		.blog-actions-fixed .btn{ width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border-radius:4px; padding:0; border:0; background:rgba(255,255,255,0.95); backdrop-filter:blur(10px); box-shadow:0 2px 6px rgba(0,0,0,.15); color:inherit; }
		.blog-actions-fixed .btn:hover{ background:rgba(255,255,255,1); box-shadow:0 3px 8px rgba(0,0,0,.18); }
		.blog-actions-fixed .btn:focus{ outline:none; box-shadow:0 0 0 0 rgba(0,0,0,0); }
		.blog-actions-fixed .btn i{ font-size:.6rem; }
		.blog-actions-fixed .btn:disabled, .blog-actions-fixed .btn.disabled{ background:rgba(255,255,255,0.7); cursor:not-allowed; }
		.blog-actions-fixed .btn:disabled i, .blog-actions-fixed .btn.disabled i{ color:#6c757d !important; }
		/* Blog body under image */
		.blog-body{ padding:0.75rem 1rem;}
		.blog-body .blog-title{ color:#111827; font-size:1rem; }
		.blog-body .blog-text{ color:#111827; font-size:0.875rem; }

		.blog-actions{ 
			display:flex; 
			gap:12px; 
		}
		.blog-actions .btn{ 
			width:44px; 
			height:44px; 
			border-radius:50%; 
			display:flex; 
			align-items:center; 
			justify-content:center; 
			border:none; 
			transition:all 0.2s ease;
			box-shadow:0 2px 8px rgba(0,0,0,0.3);
		}
		.blog-actions .btn:hover{ 
			transform:scale(1.1); 
			box-shadow:0 4px 12px rgba(0,0,0,0.4);
		}
		.blog-actions .btn:first-child{ 
			background:var(--primary); 
			color:white; 
		}
		.blog-actions .btn:first-child:hover{ 
			background:var(--primary-600); 
		}
		.blog-actions .btn:last-child{ 
			background:#dc3545; 
			color:white; 
		}
		.blog-actions .btn:last-child:hover{ 
			background:#c82333; 
		}
		.blog-title{ 
			font-weight:600; 
			color:white; 
			margin-bottom:0.75rem; 
			line-height:1.4;
			display:-webkit-box;
			-webkit-line-clamp:2;
			-webkit-box-orient:vertical;
			overflow:hidden;
		}
		.blog-preview{ 
			color:rgba(0, 0, 0, 0.75); 
			font-size:0.85rem; 
			line-height:1.5; 
			margin-bottom:0.75rem; 
			flex:1;
			display:block;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			text-align: left;
		}
		.blog-meta{ 
			margin-top:auto; 
			padding-top:0.75rem; 
			border-top:1px solid rgba(255,255,255,0.3);
		}

		/* Modal styles */
		.modal-content{ border:0; border-radius:var(--radius); }
		.modal-header{ border-bottom:1px solid var(--line); }
		.modal-footer{ border-top:1px solid var(--line); }
		
		/* Button consistency */
		.btn{ border-radius:8px; font-weight:500; }
		.btn-sm{ padding:0.5rem 1rem; font-size:0.875rem; }
		.btn-animated-delete{ padding:0.5rem 1rem; font-size:0.875rem; }
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

			.blog-card{ margin-bottom:1rem; }
		
		@media (max-width: 768px){
			.blog-title{ font-size:1rem; }
			.blog-preview{ font-size:0.85rem; }
		}
		@media (max-width: 575.98px){
			.toolbar .row-top{ flex-direction:column; align-items:stretch; }
			.blog-actions .btn{ width:40px; height:40px; }
			.blog-actions{ gap:8px; }
		}
	</style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('blogs'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Blogs'); ?>

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
					<div class="d-flex align-items-center justify-content-between mb-4">
						<div class="h5 mb-0">Blog Posts</div>
						<span class="badge bg-light text-dark border"><?php echo $totalCount; ?> total</span>
					</div>
					
					<!-- Blog Cards Grid -->
					<div class="row g-4" id="blogsGrid">
						<?php while($row = $blogs->fetch_assoc()): ?>
						<div class="col-lg-2 col-md-3 col-sm-6 col-12" data-blog='<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
							<div class="blog-card">
								<div class="blog-image-container">
								<div class="blog-actions-fixed">
									<a href="#" class="modern-btn view-btn" title="View Blog"><span class="icon"><i class="fa-solid fa-eye"></i></span></a>
										<a href="edit.php?id=<?php echo (int)$row['id']; ?>" class="modern-btn edit-btn" title="Edit Blog"><span class="icon"><i class="fa-solid fa-pen"></i></span></a>
										<button class="modern-btn delete-btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Delete Blog"><span class="icon"><i class="fa-solid fa-trash"></i></span></button>
									</div>
									<?php if (!empty($row['image_url'])): ?>
										<?php 
											$src = $row['image_url'];
											// If it's not a full URL, construct the path
											if (strpos($src, 'http://') !== 0 && strpos($src, 'https://') !== 0) { 
												$src = '../../uploads/blogs/' . $src; 
											}
										?>
										<img src="<?php echo htmlspecialchars($src); ?>" alt="Blog cover" class="blog-image">
									<?php else: ?>
										<div class="blog-image-placeholder">
											<i class="fa-solid fa-image"></i>
											<span>No Image</span>
										</div>
									<?php endif; ?>
								</div>
								<div class="blog-body">
									<h6 class="blog-title"><?php echo htmlspecialchars($row['title']); ?></h6>
									<?php if (!empty($row['category'])): ?>
									<div class="blog-category mb-2">
									<span class="badge bg-danger"><?php echo htmlspecialchars($row['category']); ?></span>
									</div>
									<?php endif; ?>
									<?php if (!empty($row['tags'])): ?>
									<div class="blog-tags mb-2">
									<?php 
									$tags = explode(',', $row['tags']);
									foreach ($tags as $tag): 
										$tag = trim($tag);
										if (!empty($tag)):
									?>
									<span class="badge bg-secondary me-1"><?php echo htmlspecialchars($tag); ?></span>
									<?php 
										endif;
									endforeach; 
									?>
									</div>
									<?php endif; ?>
									<p class="blog-preview"><?php echo htmlspecialchars(substr($row['content'], 0, 80)) . '...'; ?></p>
									<div class="blog-meta"></div>
								</div>
							</div>
						</div>
						<?php endwhile; ?>
					</div>
					
					<!-- Pagination -->
					<?php if ($totalPages > 1): ?>
					<nav aria-label="Blog pagination" class="mt-4">
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
						<button type="submit" class="btn-animated-delete noselect">
							<span class="text">Delete</span>
							<span class="icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
									<path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
								</svg>
							</span>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		// Drawer elements
		function openBlogDrawer(blog){
			const drawer = document.getElementById('blogDrawer');
			const backdrop = document.getElementById('blogDrawerBackdrop');
			const body = document.getElementById('blogDrawerBody');
			const titleEl = document.getElementById('blogDrawerTitle');
			if (!drawer || !backdrop || !body || !titleEl) return;
			titleEl.textContent = blog.title || 'Blog';
			const imgSrc = (blog.image_url && !/^https?:\/\//i.test(blog.image_url)) ? ('../../uploads/blogs/' + blog.image_url) : (blog.image_url || '');
			body.innerHTML = `
				${imgSrc ? `<img src="${imgSrc}" alt="Cover" class="img-fluid mb-3" style=\"border-radius:12px; border:1px solid var(--line);\">` : ''}
				<div class="text-muted small mb-2"><i class="fa-regular fa-calendar me-1"></i>${blog.created_at || ''}</div>
				${blog.category ? `<div class="mb-2"><span class="badge bg-danger">${blog.category}</span></div>` : ''}
				${blog.tags ? `<div class="mb-2">${blog.tags.split(',').map(tag => `<span class="badge bg-secondary me-1">${tag.trim()}</span>`).join('')}</div>` : ''}
				<div style="white-space:pre-wrap; line-height:1.6;">${(blog.content || '').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
				<div class=\"d-flex gap-2 mt-3\"> 
					<a href=\"edit.php?id=${blog.id || ''}\" class=\"btn btn-primary btn-sm\" target=\"_blank\" rel=\"noopener\"> 
						<i class=\"fa-solid fa-pen me-1\"></i>Edit 
					</a> 
				</div>
			`;
			drawer.classList.add('open');
			backdrop.classList.add('open');
		}
		function closeBlogDrawer(){
			document.getElementById('blogDrawer')?.classList.remove('open');
			document.getElementById('blogDrawerBackdrop')?.classList.remove('open');
		}
		// Handle delete button clicks
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
			document.querySelectorAll('.btn-delete').forEach(btn => {
				btn.addEventListener('click', function(){
					const card = this.closest('[data-blog]');
					const data = JSON.parse(card.getAttribute('data-blog'));
					document.getElementById('delete_id').value = data.id;
				});
			});

			// View drawer
			document.querySelectorAll('.blog-card .view-btn').forEach(btn => {
				btn.addEventListener('click', function(e){
					e.preventDefault();
					const wrap = this.closest('[data-blog]');
					if (!wrap) return;
					const data = JSON.parse(wrap.getAttribute('data-blog'));
					openBlogDrawer(data);
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
	<!-- Blog View Drawer -->
	<div class="drawer" id="blogDrawer">
		<div class="drawer-header">
			<h6 class="mb-0" id="blogDrawerTitle">Blog</h6>
			<button class="btn btn-sm btn-outline-secondary" onclick="closeBlogDrawer()">Close</button>
		</div>
		<div class="drawer-body" id="blogDrawerBody"></div>
	</div>
	<div class="drawer-backdrop" id="blogDrawerBackdrop" onclick="closeBlogDrawer()"></div>
