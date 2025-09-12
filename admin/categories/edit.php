<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

// Get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
	header('Location: index.php');
	exit();
}

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

// Fetch existing category
$stmt = $mysqli->prepare('SELECT id, name, image, created_at, updated_at FROM categories WHERE id = ?');
$stmt && $stmt->bind_param('i', $id) && $stmt->execute();
$category = $stmt ? $stmt->get_result()->fetch_assoc() : null;
$stmt && $stmt->close();
if (!$category) {
	header('Location: index.php');
	exit();
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$name = trim($_POST['name'] ?? '');
		if ($name === '') { throw new Exception('Category name is required'); }

		$has_updated_at = ($mysqli->query("SHOW COLUMNS FROM categories LIKE 'updated_at'")?->num_rows ?? 0) > 0;
		$has_image = ($mysqli->query("SHOW COLUMNS FROM categories LIKE 'image'")?->num_rows ?? 0) > 0;

		$imageFile = $category['image'] ?? null; // default keep existing
		if ($has_image && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = '../../uploads/categories/';
			if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
			$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
			$allowed = ['jpg','jpeg','png','gif','webp'];
			if (!in_array($ext, $allowed)) { throw new Exception('Invalid image type'); }
			if ($_FILES['image']['size'] > 5*1024*1024) { throw new Exception('Image too large (max 5MB)'); }
			$newName = 'category_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
				throw new Exception('Failed to upload image');
			}
			$imageFile = $newName; // store filename
		}

		// Build update
		if ($has_image && $has_updated_at) {
			$stmt = $mysqli->prepare('UPDATE categories SET name = ?, image = ?, updated_at = NOW() WHERE id = ?');
			$stmt && $stmt->bind_param('ssi', $name, $imageFile, $id);
		} elseif ($has_image) {
			$stmt = $mysqli->prepare('UPDATE categories SET name = ?, image = ? WHERE id = ?');
			$stmt && $stmt->bind_param('ssi', $name, $imageFile, $id);
		} elseif ($has_updated_at) {
			$stmt = $mysqli->prepare('UPDATE categories SET name = ?, updated_at = NOW() WHERE id = ?');
			$stmt && $stmt->bind_param('si', $name, $id);
		} else {
			$stmt = $mysqli->prepare('UPDATE categories SET name = ? WHERE id = ?');
			$stmt && $stmt->bind_param('si', $name, $id);
		}

		if (!$stmt || !$stmt->execute()) { throw new Exception('Failed to update category'); }
		$stmt && $stmt->close();

		logActivity($mysqli, 'Updated category', 'ID: ' . $id . ', Name: ' . $name);
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
	<title>Edit Category - Big Deal</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<link href="../../assets/css/animated-buttons.css" rel="stylesheet">
	<style>
		:root{ --bg:#F1EFEC; --card:#ffffff; --muted:#6b7280; --line:#e9eef5; --brand-dark:#2f2f2f; --primary:#e11d2a; --primary-600:#b91c1c; --radius:16px; }
		body{ background:var(--bg); color:#111827; }
		.content{ margin-left:284px; }
		.sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
		.brand{ font-weight:700; font-size:1.25rem; }
		.list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
		.list-group-item i{ width:18px; }
		.list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
		.list-group-item:hover{ background:#f8fafc; }
		/* Topbar styling to match blogs list */
		.navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
		.text-primary{ color:var(--primary)!important; }
		.input-group .form-control{ border-color:var(--line); }
		.input-group-text{ border-color:var(--line); }
		.card{ border:0; border-radius:var(--radius); background:var(--card); }
		.image-drop{ border:2px dashed var(--line); border-radius:12px; padding:1.5rem; text-align:center; background:#fafbfc; transition:all .2s ease; }
		.image-drop.dragover{ border-color:var(--primary); background:#fef2f2; }
		.preview img{ width:140px; height:140px; object-fit:cover; border-radius:10px; border:2px solid #e9eef5; }
		@media (max-width: 991.98px){ .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; } .sidebar.open{ left:12px; } .content{ margin-left:0; } }
	</style>
</head>
<body>
	<?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('categories'); ?>
	<div class="content">
		<?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin'); ?>

		<div class="container-fluid p-4">
			<div class="d-flex align-items-center justify-content-between mb-4">
				<div>
					<h2 class="h4 mb-1 fw-semibold">Edit Category</h2>
					<p class="text-muted mb-0">Update the category details</p>
				</div>
				<a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Back to Categories</a>
			</div>

			<?php if ($message): ?>
				<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
					<i class="fa-solid fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($message); ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>

			<form method="post" enctype="multipart/form-data" id="categoryForm">
				<div class="row">
					<div class="col-lg-12">
						<div class="card mb-4">
							<div class="card-body">
								<div class="mb-3">
									<label class="form-label">Category Name</label>
									<input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
								</div>
								<?php $has_image = ($mysqli->query("SHOW COLUMNS FROM categories LIKE 'image'")?->num_rows ?? 0) > 0; ?>
								<?php if ($has_image): ?>
								<div>
									<label class="form-label">Category Image (optional)</label>
									<div class="image-drop" id="drop">
										<i class="fa-solid fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
										<div class="mb-2">Drop image here or click to browse</div>
										<input type="file" name="image" accept="image/*" class="d-none" id="image">
										<button type="button" class="btn btn-outline-primary" id="chooseBtn"><i class="fa-solid fa-plus me-1"></i>Choose Image</button>
										<div class="text-muted small mt-2">Supported: JPG, PNG, GIF, WebP. Max 5MB</div>
									</div>
									<div class="preview mt-2" id="preview" style="display:block;">
										<?php if (!empty($category['image'])): ?>
											<img src="../../uploads/categories/<?php echo htmlspecialchars($category['image']); ?>" alt="Current Image">
										<?php else: ?>
											<span class="text-muted">No current image</span>
										<?php endif; ?>
									</div>
								</div>
								<?php endif; ?>
								<div class="d-flex justify-content-end gap-2 mt-3">
									<a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-times me-2"></i>Cancel</a>
									<button type="submit" class="btn-animated-confirm noselect">
										<span class="text">Update Category</span>
										<span class="icon">
											<svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path></svg>
										</span>
									</button>
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

		if (drop) {
			drop.addEventListener('dragover', (e)=>{ e.preventDefault(); drop.classList.add('dragover'); });
			drop.addEventListener('dragleave', (e)=>{ e.preventDefault(); drop.classList.remove('dragover'); });
			drop.addEventListener('drop', (e)=>{
				e.preventDefault(); drop.classList.remove('dragover');
				if (e.dataTransfer.files && e.dataTransfer.files[0]) { input.files = e.dataTransfer.files; showPreview(); }
			});
			drop.addEventListener('click', ()=> input && input.click());
		}

		chooseBtn && chooseBtn.addEventListener('click', function(e){ e.stopPropagation(); input && input.click(); });
		input && input.addEventListener('change', showPreview);

		function showPreview(){
			const file = input && input.files && input.files[0];
			if (!file) { return; }
			const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
			if (!allowed.includes(file.type)) { alert('Please select a JPG, PNG, GIF, or WebP image'); input.value=''; return; }
			if (file.size > 5*1024*1024) { alert('Image too large (max 5MB)'); input.value=''; return; }
			const reader = new FileReader();
			reader.onload = (e)=>{ preview.innerHTML = '<img src="' + e.target.result + '" style="width:140px;height:140px;object-fit:cover;border-radius:10px;border:2px solid #e9eef5;">'; };
			reader.readAsDataURL(file);
		}
	</script>
</body>
</html>


