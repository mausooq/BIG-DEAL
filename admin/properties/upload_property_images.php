<?php
require_once __DIR__ . '/../auth.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

// Config
$MAX_FILE_BYTES = 10 * 1024 * 1024; // 10 MB per file
$MAX_TOTAL_BYTES = 40 * 1024 * 1024; // 40 MB per session total

$tmpDir = __DIR__ . '/../../uploads/properties/tmp/';
if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0777, true); }
if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Uploads directory is not writable']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? 'upload');

// Initialize session store
if (!isset($_SESSION['uploaded_images']) || !is_array($_SESSION['uploaded_images'])) {
    $_SESSION['uploaded_images'] = [];
}
if (!isset($_SESSION['uploaded_images_bytes'])) {
    $_SESSION['uploaded_images_bytes'] = 0;
}

function json_ok($data = []){ echo json_encode(array_merge(['ok'=>true], $data)); exit; }
function json_err($msg, $code = 400){ http_response_code($code); echo json_encode(['ok'=>false, 'error'=>$msg]); exit; }

if ($action === 'limits') {
    json_ok([
        'max_file_bytes' => $MAX_FILE_BYTES,
        'max_total_bytes' => $MAX_TOTAL_BYTES,
        'already_bytes' => (int)$_SESSION['uploaded_images_bytes'],
    ]);
}

if ($action === 'delete') {
    $filename = basename($_POST['filename'] ?? '');
    if ($filename === '') json_err('Missing filename');
    $path = $tmpDir . $filename;
    if (is_file($path)) {
        $size = filesize($path) ?: 0;
        @unlink($path);
        // Remove from session arrays
        $_SESSION['uploaded_images'] = array_values(array_filter($_SESSION['uploaded_images'], function($f) use ($filename){ return $f !== $filename; }));
        $_SESSION['uploaded_images_bytes'] = max(0, (int)$_SESSION['uploaded_images_bytes'] - $size);
    }
    json_ok(['deleted' => $filename]);
}

if ($action === 'clear_all') {
    foreach ($_SESSION['uploaded_images'] as $f) {
        $p = $tmpDir . basename($f);
        if (is_file($p)) { @unlink($p); }
    }
    $_SESSION['uploaded_images'] = [];
    $_SESSION['uploaded_images_bytes'] = 0;
    json_ok(['cleared' => true]);
}

// Default: upload
if (!isset($_FILES['file'])) {
    json_err('No file uploaded');
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    // Map common errors
    $msg = 'Upload failed';
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) $msg = 'File exceeds server limit';
    json_err($msg);
}

$size = (int)$file['size'];
if ($size <= 0) json_err('Empty file');
if ($size > $MAX_FILE_BYTES) json_err('File too large. Maximum per file is 10MB');

$currentTotal = (int)$_SESSION['uploaded_images_bytes'];
if ($currentTotal + $size > $MAX_TOTAL_BYTES) {
    json_err('Maximum upload size reached');
}

// Validate mime
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
if (!isset($allowed[$mime])) {
    json_err('Unsupported file type');
}
$ext = $allowed[$mime];

// Generate temp filename
$sid = session_id() ?: 'sess';
$rand = bin2hex(random_bytes(4));
$tempName = 'tmp_' . $sid . '_' . time() . '_' . $rand . '.' . $ext;
$dest = $tmpDir . $tempName;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_err('Failed to store file', 500);
}

// Track in session
$_SESSION['uploaded_images'][] = $tempName;
$_SESSION['uploaded_images_bytes'] = $currentTotal + $size;

json_ok(['filename' => $tempName, 'bytes' => $size]);
?>


