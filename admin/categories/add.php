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
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { throw new Exception('Category name is required'); }

        // Ensure unique
        $chk = $mysqli->prepare('SELECT COUNT(*) FROM categories WHERE name = ?');
        $chk->bind_param('s', $name);
        $chk->execute();
        $exists = (int)($chk->get_result()->fetch_row()[0] ?? 0);
        $chk->close();
        if ($exists > 0) { throw new Exception('Category already exists'); }

        // Columns present?
        $has_created_at = ($mysqli->query("SHOW COLUMNS FROM categories LIKE 'created_at'")?->num_rows ?? 0) > 0;
        $has_updated_at = ($mysqli->query("SHOW COLUMNS FROM categories LIKE 'updated_at'")?->num_rows ?? 0) > 0;
        $has_image = ($mysqli->query("SHOW COLUMNS FROM categories LIKE 'image'")?->num_rows ?? 0) > 0;

        // Handle image (optional)
        $imageFile = null;
        if ($has_image && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/categories/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) { throw new Exception('Invalid image type'); }
            if ($_FILES['image']['size'] > 10*1024*1024) { throw new Exception('Image too large (max 10MB)'); }
            $imageFile = 'category_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageFile)) {
                throw new Exception('Failed to upload image');
            }
        }

        // Build insert
        if ($has_image && $has_created_at && $has_updated_at) {
            $stmt = $mysqli->prepare('INSERT INTO categories (name, image, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
            $stmt->bind_param('ss', $name, $imageFile);
        } elseif ($has_image && $has_created_at) {
            $stmt = $mysqli->prepare('INSERT INTO categories (name, image, created_at) VALUES (?, ?, NOW())');
            $stmt->bind_param('ss', $name, $imageFile);
        } elseif ($has_image) {
            $stmt = $mysqli->prepare('INSERT INTO categories (name, image) VALUES (?, ?)');
            $stmt->bind_param('ss', $name, $imageFile);
        } elseif ($has_created_at && $has_updated_at) {
            $stmt = $mysqli->prepare('INSERT INTO categories (name, created_at, updated_at) VALUES (?, NOW(), NOW())');
            $stmt->bind_param('s', $name);
        } elseif ($has_created_at) {
            $stmt = $mysqli->prepare('INSERT INTO categories (name, created_at) VALUES (?, NOW())');
            $stmt->bind_param('s', $name);
        } else {
            $stmt = $mysqli->prepare('INSERT INTO categories (name) VALUES (?)');
            $stmt->bind_param('s', $name);
        }

        if (!$stmt->execute()) { throw new Exception('Failed to add category'); }
        $stmt->close();

        logActivity($mysqli, 'Added category', 'Name: ' . $name);
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
    <title>Add Category - Big Deal</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=4">
    <link rel="shortcut icon" href="/favicon.ico?v=4" type="image/x-icon">
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
        .modal-container { background: var(--card); border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.3); width:100%; max-width:640px; max-height:90vh; overflow:auto; position:relative; z-index:1001; border:1px solid rgba(255,255,255,0.1); }
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
    <!-- Background iframe showing categories index page -->
    <iframe src="index.php" class="background-iframe" title="Categories Background"></iframe>
    <div class="blur-overlay"></div>

    <div class="modal-overlay">
        <div class="modal-container">
            <div class="order-card">
                <header class="card-header">
                    <h1>Add Category</h1>
                    <button class="close-btn" aria-label="Close">&times;</button>
                </header>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="categoryForm">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="name" placeholder="e.g., Residential, Commercial" required>
                    </div>
                    <div>
                        <label class="form-label">Category Image (optional)</label>
                        <div id="drop" class="image-drop">
                            <i class="fa-solid fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                            <div class="mb-2">Drop image here or click to browse</div>
                            <input type="file" name="image" id="image" accept="image/*" class="d-none">
                            <button type="button" class="btn btn-outline-primary" id="chooseBtn"><i class="fa-solid fa-plus me-1"></i>Choose Image</button>
                            <div class="text-muted small mt-2">Supported: JPG, PNG, GIF, WebP. Max 10MB</div>
                        </div>
                        <div class="preview" id="preview" style="display:none;">
                            <img id="previewImg" src="" alt="Preview">
                        </div>
                    </div>

                    <div class="footer-actions">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Add Category</span>
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
            if (file.size > 10*1024*1024) { alert('Image too large (max 10MB)'); input.value=''; return; }
            const reader = new FileReader();
            reader.onload = (e)=>{ previewImg.src = e.target.result; preview.style.display='block'; };
            reader.readAsDataURL(file);
        }

        // Simple validation
        document.getElementById('categoryForm').addEventListener('submit', function(e){
            const name = this.querySelector('input[name="name"]').value.trim();
            if (name.length < 2) { e.preventDefault(); alert('Please enter a valid category name'); }
        });

        // Close button and outside click navigates back
        document.querySelector('.close-btn')?.addEventListener('click', function(){ window.location.href = 'index.php'; });
        document.addEventListener('click', function(e){
            const modal = document.querySelector('.modal-container');
            if (modal && !modal.contains(e.target)) { window.location.href = 'index.php'; }
        });
    </script>
</body>
</html>


