<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

// Get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php'); exit(); }

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

$mysqli = db();

// Fetch existing blog
$stmt = $mysqli->prepare('SELECT id, title, content, image_url, category, created_at, admin_id FROM blogs WHERE id = ?');
$stmt && $stmt->bind_param('i', $id) && $stmt->execute();
$blog = $stmt ? $stmt->get_result()->fetch_assoc() : null;
$stmt && $stmt->close();
if (!$blog) { header('Location: index.php'); exit(); }

// Fetch existing subtitles
$stmt = $mysqli->prepare('SELECT id, subtitle, content, image_url, order_no FROM blog_subtitles WHERE blog_id = ? ORDER BY order_no ASC');
$stmt && $stmt->bind_param('i', $id) && $stmt->execute();
$subtitles = $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : [];
$stmt && $stmt->close();

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$title = trim($_POST['title'] ?? '');
		$content = trim($_POST['content'] ?? '');
		$imageUrlInput = trim($_POST['image_url'] ?? '');
		$category = trim($_POST['category'] ?? '');
		if ($title === '' || $content === '') { throw new Exception('Title and content are required'); }

		$image_url = $blog['image_url'];
		if ($imageUrlInput !== '') { $image_url = $imageUrlInput; }
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = '../../uploads/blogs/';
			if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
			$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
			$allowed = ['jpg','jpeg','png','gif','webp'];
			if (!in_array($ext, $allowed)) { throw new Exception('Invalid image type'); }
			if ($_FILES['image']['size'] > 10*1024*1024) { throw new Exception('Image too large (max 10MB)'); }
			$filename = 'blog_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				throw new Exception('Failed to upload image');
			}
			$image_url = $filename; // Store only filename, not full path
		}

		$stmt = $mysqli->prepare('UPDATE blogs SET title = ?, content = ?, image_url = ?, category = ? WHERE id = ?');
		$stmt && $stmt->bind_param('ssssi', $title, $content, $image_url, $category, $id);
		if (!$stmt || !$stmt->execute()) { throw new Exception('Failed to update blog post'); }
		$stmt && $stmt->close();

		// Handle subtitles update
		// First, delete existing subtitles
		$stmt = $mysqli->prepare('DELETE FROM blog_subtitles WHERE blog_id = ?');
		$stmt && $stmt->bind_param('i', $id) && $stmt->execute();
		$stmt && $stmt->close();

		// Insert new subtitles if provided
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
					if (in_array($ext, $allowed) && $files['size'][$i] <= 10*1024*1024) {
						$filename = 'blog_sub_' . $id . '_' . time() . '_' . $i . '.' . $ext;
						if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $filename)) {
							$sub_image_url = $filename; // Store only filename, not full path
						}
					}
				}
				$ins = $mysqli->prepare('INSERT INTO blog_subtitles (blog_id, subtitle, content, image_url, order_no, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
				if ($ins) {
					$st_null = $st === '' ? null : $st; // Convert empty string to null
					$ins->bind_param('isssi', $id, $st_null, $sc, $sub_image_url, $on);
					$ins->execute();
					$ins->close();
				}
			}
		}

		logActivity($mysqli, 'Updated blog', 'ID: ' . $id . ', Title: ' . $title);
		header('Location: index.php?updated=1');
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
	<title>Edit Blog</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<link href="../../assets/css/animated-buttons.css" rel="stylesheet">
	<style>
		:root {
			--primary-color: #ef4444;
			--bg: #F4F7FA;
			--card: #FFFFFF;
			--line: #E0E0E0;
			--border-color: #E0E0E0;
			--text: #333;
		}
		body{ background:var(--bg); color:#111827; margin:0; }
		/* Background iframe with blur */
		.background-iframe {
			position: fixed; top:0; left:0; width:100%; height:100%; border:none; z-index:-2;
		}
		.blur-overlay {
			position: fixed; top:0; left:0; width:100%; height:100%;
			background: rgba(0,0,0,.4); backdrop-filter: blur(3px); z-index:-1;
		}
		/* Modal overlay + container */
		.modal-overlay { position: fixed; inset:0; background: transparent; display:flex; align-items:center; justify-content:center; padding:20px; z-index:1000; }
		.modal-container { background: var(--card); border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.3); width:100%; max-width:900px; max-height:90vh; overflow:auto; position:relative; z-index:1001; border:1px solid rgba(255,255,255,0.1); }
		.order-card { padding: 2rem; box-sizing: border-box; }
		.card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
		.card-header h1 { font-size:1.25rem; font-weight:600; margin:0; }
		.close-btn { font-size:1.5rem; color:#999; cursor:pointer; border:none; background:none; }
		.btn-animated-confirm { background: var(--primary-color); color:#fff; border:0; padding:.8rem 1.5rem; border-radius:8px; display:inline-flex; align-items:center; gap:.5rem; }
		.btn-outline-secondary { border-radius:8px; }
		.footer-actions { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; }
		.btn { padding:.5rem 1rem; border:none; border-radius:8px; font-size:.875rem; font-weight:500; cursor:pointer; text-decoration:none; display:inline-block; }
		.btn-secondary { background:#ffffff; color:var(--text); border:1px solid var(--border-color); }
		.btn-secondary:hover { background:#f5f5f5; }
		.btn-animated-confirm { padding:.5rem 1rem; font-size:.875rem; }
		/* Form look */
		.form-control{ border-radius:12px; border:1px solid var(--line); }
		.form-control:focus{ border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(239,68,68,.15); }
		.image-drop{ border:2px dashed var(--line); border-radius:12px; padding:1.5rem; text-align:center; background:#fafbfc; transition:all .2s ease; }
		.image-drop.dragover{ border-color:var(--primary-color); background:#fef2f2; }
		.image-drop:hover{ border-color:var(--primary-color); background:#fef2f2; }
		.image-drop .btn-outline-primary{ color:var(--primary-color); border-color:var(--primary-color); }
		.image-drop .btn-outline-primary:hover{ background-color:var(--primary-color); border-color:var(--primary-color); color:#fff; }
		.preview{ margin-top:10px; }
		.preview img{ width:140px; height:140px; object-fit:cover; border-radius:10px; border:2px solid #e9eef5; }
		@media (max-width: 600px){ .modal-container { max-width: 95%; } }
	</style>
</head>
<body>
	<iframe src="index.php" class="background-iframe" title="Blogs Background"></iframe>
	<div class="blur-overlay"></div>
	<div class="modal-overlay">
		<div class="modal-container">
			<div class="order-card">
				<header class="card-header">
					<h1>Edit Blog</h1>
					<button class="close-btn" aria-label="Close" onclick="window.location.href='index.php'">&times;</button>
				</header>

				<?php if ($message): ?>
					<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
						<i class="fa-solid fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($message); ?>
						<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
					</div>
				<?php endif; ?>

				<form method="post" enctype="multipart/form-data" id="blogForm">
					<div class="mb-3">
						<label class="form-label">Title</label>
						<input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($blog['title']); ?>" required>
					</div>
					<div class="mb-3">
						<label class="form-label">Category (optional)</label>
						<input type="text" class="form-control" name="category" value="<?php echo htmlspecialchars($blog['category'] ?? ''); ?>" placeholder="Enter category name">
					</div>
					<div class="mb-3">
						<label class="form-label">Replace Cover-Image (optional)</label>
						<div id="drop" class="image-drop">
							<i class="fa-solid fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
							<div class="mb-2">Drop image here or click to browse</div>
							<input type="file" name="image" id="image" accept="image/*" class="d-none">
							<button type="button" class="btn btn-outline-primary" id="chooseBtn"><i class="fa-solid fa-plus me-1"></i>Choose Image</button>
							<div class="text-muted small mt-2">Supported: JPG, PNG, GIF, WebP. Max 10MB</div>
						</div>
						<div class="preview" id="preview">
							<?php if (!empty($blog['image_url'])): ?>
								<?php 
									$src = $blog['image_url']; 
									if (strpos($src,'http://')!==0 && strpos($src,'https://')!==0) { $src = '../../uploads/blogs/' . $src; } 
								?>
								<img src="<?php echo htmlspecialchars($src); ?>" alt="Current Cover Image">
							<?php else: ?>
								<span class="text-muted">No current image</span>
							<?php endif; ?>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label">Content</label>
						<textarea class="form-control" name="content" rows="8" required><?php echo htmlspecialchars($blog['content']); ?></textarea>
					</div>
					<div class="mb-3">
						<div class="d-flex align-items-center justify-content-between mb-2">
							<label class="form-label mb-0">Subtitles / Sections</label>
							<button type="button" class="btn btn-secondary" id="addSubtitleRow">Add Section</button>
						</div>
						<div id="subtitleList" class="vstack gap-3">
							<?php foreach ($subtitles as $index => $subtitle): ?>
							<div class="border rounded p-3" data-index="<?php echo $index; ?>">
								<div class="row g-3 align-items-start">
									<div class="col-md-6">
										<label class="form-label">Subtitle (optional)</label>
										<input type="text" name="subtitle_title[]" class="form-control" placeholder="Section title (optional)" value="<?php echo htmlspecialchars($subtitle['subtitle'] ?? ''); ?>">
									</div>
									<div class="col-md-3">
										<label class="form-label">Order</label>
										<input type="number" name="subtitle_order[]" class="form-control" min="1" value="<?php echo $subtitle['order_no']; ?>">
									</div>
									<div class="col-md-3 d-flex justify-content-end">
										<button type="button" class="btn btn-secondary mt-4 remove-subtitle">Remove</button>
									</div>
									<div class="col-12">
										<label class="form-label">Content</label>
										<textarea name="subtitle_content[]" class="form-control" rows="4" placeholder="Section content"><?php echo htmlspecialchars($subtitle['content']); ?></textarea>
									</div>
									<div class="col-md-6">
										<label class="form-label">Image (optional)</label>
										<?php if (!empty($subtitle['image_url'])): ?>
											<?php 
												$src = $subtitle['image_url']; 
												if (strpos($src,'http://')!==0 && strpos($src,'https://')!==0) { $src = '../../uploads/blogs/' . $src; } 
											?>
											<div class="mb-2">
												<img src="<?php echo htmlspecialchars($src); ?>" alt="Current image" style="width:100px;height:100px;object-fit:cover;border-radius:8px;">
												<div class="text-muted small">Current image</div>
											</div>
										<?php endif; ?>
										<input type="file" name="subtitle_image[]" accept="image/*" class="form-control">
										<div class="form-text">JPG, PNG, GIF, WebP up to 10MB</div>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
						<div class="text-muted small">Add multiple sections. Image per section is optional.</div>
					</div>
					<div class="footer-actions">
						<a href="index.php" class="btn btn-secondary">Cancel</a>
						<button type="submit" class="btn-animated-confirm noselect">
							<span class="text">Update Blog</span>
							<span class="icon">
								<svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path></svg>
							</span>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		const drop = document.getElementById('drop');
		const input = document.getElementById('image');
		const chooseBtn = document.getElementById('chooseBtn');
		const preview = document.getElementById('preview');

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
			if (file.size > 10*1024*1024) { alert('Image too large (max 10MB)'); input.value=''; return; }
			const reader = new FileReader();
			reader.onload = (e)=>{ preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">'; preview.style.display='block'; };
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
			
			// Get current count from existing subtitles
			let count = list.children.length;
			
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
								<div class="form-text">JPG, PNG, GIF, WebP up to 10MB</div>
							</div>
						</div>
					</div>`;
			}
			
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

		// Ripple animation for animated buttons
		document.addEventListener('click', function(e) {
			const btn = e.target.closest('.btn-animated-confirm, .btn-animated-add, .btn-animated-delete');
			if (!btn) return;
			
			const ripple = document.createElement('span');
			const rect = btn.getBoundingClientRect();
			const size = Math.max(rect.width, rect.height);
			const x = e.clientX - rect.left - size / 2;
			const y = e.clientY - rect.top - size / 2;
			
			ripple.style.cssText = `
				position: absolute;
				width: ${size}px;
				height: ${size}px;
				left: ${x}px;
				top: ${y}px;
				background: rgba(255, 255, 255, 0.3);
				border-radius: 50%;
				transform: scale(0);
				animation: ripple 0.6s linear;
				pointer-events: none;
			`;
			
			btn.style.position = 'relative';
			btn.style.overflow = 'hidden';
			btn.appendChild(ripple);
			
			setTimeout(() => ripple.remove(), 600);
		});

		// Add ripple animation CSS
		const style = document.createElement('style');
		style.textContent = `
			@keyframes ripple {
				to {
					transform: scale(4);
					opacity: 0;
				}
			}
		`;
		document.head.appendChild(style);

		// Close button and outside click navigates back (overlay-only to avoid accidental closes)
		document.querySelector('.close-btn')?.addEventListener('click', function(){ window.location.href = 'index.php'; });
		const overlay = document.querySelector('.modal-overlay');
		overlay?.addEventListener('click', function(e){
			if (e.target === overlay) { window.location.href = 'index.php'; }
		});
	</script>
</body>
</html>

