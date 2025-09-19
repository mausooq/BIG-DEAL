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
    <title>Edit Category</title>
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        :root { --border-color:#E0E0E0; --text:#333; --bg:#F4F7FA; --card:#fff; --primary:#ef4444; }
        body, input, select, textarea, button { font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif; }
        body { background: var(--bg); margin:0; color: var(--text); }
        .background-iframe { position: fixed; inset:0; width:100%; height:100%; border:none; z-index:-2; }
        .blur-overlay { position: fixed; inset:0; background: rgba(0,0,0,.4); backdrop-filter: blur(3px); z-index:-1; }
        .modal-overlay { position: fixed; inset:0; display:flex; align-items:center; justify-content:center; padding:20px; z-index:1000; }
        .modal-container { background: var(--card); border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.3); width:100%; max-width:760px; max-height:90vh; overflow:auto; position:relative; border:1px solid rgba(255,255,255,0.1); }
        .order-card { padding:2rem; box-sizing:border-box; display:flex; flex-direction:column; }
        .card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; }
        .card-header h1 { font-size:1.4rem; font-weight:600; margin:0; }
        .close-btn { font-size:1.5rem; color:#999; cursor:pointer; border:none; background:none; }
        label { font-size:.9rem; color:#555; font-weight:500; margin-bottom:6px; display:block; }
        input[type="text"], input[type="file"], select, textarea { width:100%; padding:.75rem; border:1px solid var(--border-color); border-radius:8px; font-size:1rem; box-sizing:border-box; }
        .image-drop{ border:1px dashed var(--border-color); border-radius:12px; padding:1rem; text-align:center; background:#fafafa; }
        .preview img{ width:140px; height:140px; object-fit:cover; border-radius:10px; border:1px solid var(--border-color); }
        .footer-actions { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; }
        .btn { padding:.8rem 1.2rem; border:none; border-radius:8px; font-size:1rem; font-weight:500; cursor:pointer; text-decoration:none; display:inline-block; }
        .btn-secondary { background:#ffffff; color:var(--text); border:1px solid var(--border-color); }
        .btn-secondary:hover { background:#f5f5f5; }
    </style>
</head>
<body>
    <iframe src="index.php" class="background-iframe" title="Categories Background"></iframe>
    <div class="blur-overlay"></div>
    <div class="modal-overlay">
        <div class="modal-container">
            <div class="order-card">
                <header class="card-header">
                    <h1>Edit Category</h1>
                    <button class="close-btn" aria-label="Close" onclick="window.location.href='index.php'">&times;</button>
                </header>

                <?php if ($message): ?>
                    <div style="background-color:#eef9f1;border:1px solid #ccead6;color:#166534;padding:10px;margin:10px 0;border-radius:6px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="categoryForm">
                    <div>
                        <div class="mb-3">
                            <label>Category Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                        </div>
                        <?php $has_image = ($mysqli->query("SHOW COLUMNS FROM categories LIKE 'image'")?->num_rows ?? 0) > 0; ?>
                        <?php if ($has_image): ?>
                        <div class="mb-2">
                            <label>Category Image (optional)</label>
                            <div class="image-drop" id="drop">
                                <div style="margin-bottom:8px;font-weight:500;">Drop image here or click to browse</div>
                                <input type="file" name="image" accept="image/*" id="image" style="display:none;">
                                <div style="margin-top:8px;">
                                    <button type="button" class="btn btn-secondary" id="chooseBtn">Choose Image</button>
                                </div>
                                <div class="text-muted small" style="margin-top:6px;">Supported: JPG, PNG, GIF, WebP. Max 5MB</div>
                            </div>
                            <div class="preview" id="preview" style="margin-top:8px;display:block;">
                                <?php if (!empty($category['image'])): ?>
                                    <img src="../../uploads/categories/<?php echo htmlspecialchars($category['image']); ?>" alt="Current Image">
                                <?php else: ?>
                                    <span class="text-muted">No current image</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="footer-actions">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn-animated-confirm noselect">
                                <span class="text">Update Category</span>
                                <span class="icon">
                                    <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path></svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
            reader.onload = (e)=>{ preview.innerHTML = '<img src="' + e.target.result + '" style="width:140px;height:140px;object-fit:cover;border-radius:10px;border:1px solid #e0e0e0;">'; };
            reader.readAsDataURL(file);
        }
    </script>
</body>
</html>

