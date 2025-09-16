<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

// Flash
$message = '';
$message_type = 'success';

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

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$mysqli = db();
	try {
		$title = trim($_POST['title'] ?? '');
		$content = trim($_POST['content'] ?? '');
		$imageUrlInput = trim($_POST['image_url'] ?? '');

		if ($title === '' || $content === '') { throw new Exception('Title and content are required'); }

		// Determine if blogs table has created_at
		$has_created_at = ($mysqli->query("SHOW COLUMNS FROM blogs LIKE 'created_at'")?->num_rows ?? 0) > 0;

		// Optional image: prefer uploaded file; fallback to URL input
		$image_url = $imageUrlInput !== '' ? $imageUrlInput : null;
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = '../../uploads/blogs/';
			if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
			$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
			$allowed = ['jpg','jpeg','png','gif','webp'];
			if (!in_array($ext, $allowed)) { throw new Exception('Invalid image type'); }
			if ($_FILES['image']['size'] > 5*1024*1024) { throw new Exception('Image too large (max 5MB)'); }
			$filename = 'blog_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				throw new Exception('Failed to upload image');
			}
			$image_url = $filename; // Store only filename, not full path
		}

		// Insert blog
		if ($has_created_at) {
			$stmt = $mysqli->prepare('INSERT INTO blogs (title, content, image_url, created_at) VALUES (?, ?, ?, NOW())');
			$stmt && $stmt->bind_param('sss', $title, $content, $image_url);
		} else {
			$stmt = $mysqli->prepare('INSERT INTO blogs (title, content, image_url) VALUES (?, ?, ?)');
			$stmt && $stmt->bind_param('sss', $title, $content, $image_url);
		}

		if (!$stmt || !$stmt->execute()) { throw new Exception('Failed to add blog post'); }
		$blog_id = $mysqli->insert_id;
		$stmt->close();

		// Insert subtitles if provided
		$subtitles = $_POST['subtitle_title'] ?? [];
		$subcontents = $_POST['subtitle_content'] ?? [];
		$orders = $_POST['subtitle_order'] ?? [];
		$files = $_FILES['subtitle_image'] ?? null;
		if (is_array($subtitles) && count($subtitles) > 0) {
			for ($i = 0; $i < count($subtitles); $i++) {
				$st = trim($subtitles[$i] ?? '');
				$sc = trim($subcontents[$i] ?? '');
				$on = isset($orders[$i]) && $orders[$i] !== '' ? (int)$orders[$i] : ($i + 1);
				if ($sc === '') { continue; } // Only require content, subtitle can be null
				$sub_image_url = null;
				if ($files && isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
					$uploadDir = '../../uploads/blogs/';
					if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
					$ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
					$allowed = ['jpg','jpeg','png','gif','webp'];
					if (in_array($ext, $allowed) && $files['size'][$i] <= 5*1024*1024) {
						$filename = 'blog_sub_' . $blog_id . '_' . time() . '_' . $i . '.' . $ext;
						if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $filename)) {
							$sub_image_url = $filename; // Store only filename, not full path
						}
					}
				}
				$ins = $mysqli->prepare('INSERT INTO blog_subtitles (blog_id, subtitle, content, image_url, order_no, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
				if ($ins) {
					$st_null = $st === '' ? null : $st; // Convert empty string to null
					$ins->bind_param('isssi', $blog_id, $st_null, $sc, $sub_image_url, $on);
					$ins->execute();
					$ins->close();
				}
			}
		}

		logActivity($mysqli, 'Added blog', 'Title: ' . $title);
		header('Location: index.php?success=1');
		exit();
	} catch (Exception $e) {
		$message = $e->getMessage();
		$message_type = 'danger';
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Add Blog - Big Deal</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<link href="../../assets/css/animated-buttons.css" rel="stylesheet">
	<style>
		
		body{ background:var(--bg); color:#111827; }

		/* Topbar */

		.form-control{ border-radius:12px; border:1px solid var(--line); }
		.form-control:focus{ border-color:var(--primary); box-shadow:0 0 0 3px rgba(225, 29, 42, .1); }
		.image-drop{ border:2px dashed var(--line); border-radius:12px; padding:1.5rem; text-align:center; background:#fafbfc; transition:all .2s ease; }
		.image-drop.dragover{ border-color:var(--primary); background:#fef2f2; }
		.preview{ margin-top:10px; }
		.preview img{ width:140px; height:140px; object-fit:cover; border-radius:10px; border:2px solid #e9eef5; }
		  }
	</style>

</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('blogs'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Blog'); ?>

		<div class="container-fluid p-4">
			<div class="d-flex align-items-center justify-content-between mb-4">
				<div>
					<h2 class="h4 mb-1 fw-semibold">Add New Blog Post</h2>
					<p class="text-muted mb-0">Create a blog entry for your site</p>
				</div>
				<a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Back to Blogs</a>
			</div>

			<?php if ($message): ?>
				<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
					<i class="fa-solid fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($message); ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>

			<form method="post" enctype="multipart/form-data" id="blogForm">
				<div class="row">
					<div class="col-lg-12">
						<div class="card mb-4">
							<div class="card-body">
								<div class="mb-3">
									<label class="form-label">Title</label>
									<input type="text" class="form-control" name="title" placeholder="Enter blog title" required>
								</div>
								<div class="mb-3">
									<label class="form-label">Cover Image (optional)</label>
									<div class="image-drop" id="drop">
										<i class="fa-solid fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
										<div class="mb-2">Drop image here or click to browse</div>
										<input type="file" name="image" id="image" accept="image/*" class="d-none">
										<button type="button" class="btn btn-outline-primary" id="chooseBtn"><i class="fa-solid fa-plus me-1"></i>Choose Image</button>
										<div class="text-muted small mt-2">Supported: JPG, PNG, GIF, WebP. Max 5MB</div>
									</div>
									<div class="preview" id="preview" style="display:none;">
										<img id="previewImg" src="" alt="Preview">
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Content</label>
									<textarea class="form-control" name="content" rows="8" placeholder="Write your blog content here" required></textarea>
								</div>
								<div class="mb-3">
									<div class="d-flex align-items-center justify-content-between mb-2">
										<label class="form-label mb-0">Subtitles / Sections</label>
										<button type="button" class="btn btn-outline-primary btn-sm" id="addSubtitleRow"><i class="fa-solid fa-plus me-1"></i>Add Section</button>
									</div>
									<div id="subtitleList" class="vstack gap-3"></div>
									<div class="text-muted small">Add multiple sections. Image per section is optional.</div>
								</div>
								<div class="d-flex justify-content-end gap-2 mt-3">
									<a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-times me-2"></i>Cancel</a>
									<button type="submit" class="btn-animated-confirm noselect">
										<span class="text">Add Blog</span>
										<span class="icon">
											<svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path></svg>
										</span>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		const drop = document.getElementById('drop');
		const input = document.getElementById('image');
		const chooseBtn = document.getElementById('chooseBtn');
		const preview = document.getElementById('preview');
		const previewImg = document.getElementById('previewImg');

		drop.addEventListener('dragover', (e)=>{ e.preventDefault(); drop.classList.add('dragover'); });
		drop.addEventListener('dragleave', (e)=>{ e.preventDefault(); drop.classList.remove('dragover'); });
		drop.addEventListener('drop', (e)=>{
			e.preventDefault(); drop.classList.remove('dragover');
			if (e.dataTransfer.files && e.dataTransfer.files[0]) { input.files = e.dataTransfer.files; showPreview(); }
		});
		drop.addEventListener('click', ()=> input.click());
		chooseBtn.addEventListener('click', (e)=>{ e.stopPropagation(); input.click(); });
		input.addEventListener('change', showPreview);

		function showPreview(){
			const file = input.files && input.files[0];
			if (!file) { preview.style.display='none'; return; }
			const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
			if (!allowed.includes(file.type)) { alert('Please select a JPG, PNG, GIF, or WebP image'); input.value=''; return; }
			if (file.size > 5*1024*1024) { alert('Image too large (max 5MB)'); input.value=''; return; }
			const reader = new FileReader();
			reader.onload = (e)=>{ previewImg.src = e.target.result; preview.style.display='block'; };
			reader.readAsDataURL(file);
		}

		// Simple validation
		document.getElementById('blogForm').addEventListener('submit', function(e){
			const title = this.querySelector('input[name="title"]').value.trim();
			const content = this.querySelector('textarea[name="content"]').value.trim();
			if (title.length < 2 || content.length < 10) { e.preventDefault(); alert('Please enter a valid title and content'); }
		});

		// Subtitles builder
		(function(){
			const list = document.getElementById('subtitleList');
			const addBtn = document.getElementById('addSubtitleRow');
			if (!list || !addBtn) return;
			function rowTemplate(index){
				return `
					<div class="border rounded p-3" data-index="${index}">
						<div class="row g-3 align-items-start">
							<div class="col-md-6">
								<label class="form-label">Subtitle (optional)</label>
								<input type="text" name="subtitle_title[]" class="form-control" placeholder="Section title (optional)">
							</div>
							<div class="col-md-3">
								<label class="form-label">Order</label>
								<input type="number" name="subtitle_order[]" class="form-control" min="1" value="${index+1}">
							</div>
							<div class="col-md-3 d-flex justify-content-end">
								<button type="button" class="btn btn-outline-secondary mt-4 remove-subtitle">Remove</button>
							</div>
							<div class="col-12">
								<label class="form-label">Content</label>
								<textarea name="subtitle_content[]" class="form-control" rows="4" placeholder="Section content"></textarea>
							</div>
							<div class="col-md-6">
								<label class="form-label">Image (optional)</label>
								<input type="file" name="subtitle_image[]" accept="image/*" class="form-control">
								<div class="form-text">JPG, PNG, GIF, WebP up to 5MB</div>
							</div>
						</div>
					</div>`;
			}
			let count = 0;
			function addRow(){
				const wrapper = document.createElement('div');
				wrapper.innerHTML = rowTemplate(count++);
				const node = wrapper.firstElementChild;
				list.appendChild(node);
			}
			addBtn.addEventListener('click', addRow);
			list.addEventListener('click', function(e){
				const btn = e.target.closest('.remove-subtitle');
				if (!btn) return;
				const card = btn.closest('[data-index]');
				if (card) card.remove();
			});
		})();
	</script>
</body>
</html>

