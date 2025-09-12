<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

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

// Get filters from query string (same as index.php)
$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'status' => $_GET['status'] ?? '',
];

$mysqli = db();

// Build query with filters (same logic as index.php)
$where = [];
$types = '';
$params = [];

if ($filters['q'] !== '') {
    $where[] = '(e.name LIKE ? OR e.email LIKE ? OR e.phone LIKE ? OR p.title LIKE ?)';
    $types .= 'ssss';
    $like = '%' . $mysqli->real_escape_string($filters['q']) . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($filters['status'] !== '') {
    $where[] = 'e.status = ?';
    $types .= 's';
    $params[] = $filters['status'];
}

// Build the main query
$sql = "SELECT 
    e.id,
    e.name,
    e.email,
    e.phone,
    e.message,
    e.status,
    DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
    p.title AS property_title,
    p.id AS property_id
FROM enquiries e 
LEFT JOIN properties p ON p.id = e.property_id";

// Add WHERE clause if filters are applied
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY e.created_at DESC';

// Prepare and execute query
$stmt = $mysqli->prepare($sql);
if ($stmt && $types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt && $stmt->execute();
$result = $stmt ? $stmt->get_result() : $mysqli->query($sql);

if (!$result) {
    die('Error fetching enquiries: ' . $mysqli->error);
}

// Check if there are any results
$rowCount = $result->num_rows;
if ($rowCount === 0) {
    // No data to export - redirect back with message
    $redirectUrl = 'index.php?export=no_data';
    // Preserve current filters in redirect
    $filterParams = [];
    if ($filters['q'] !== '') { 
        $filterParams['q'] = $filters['q']; 
    }
    if ($filters['status'] !== '') { 
        $filterParams['status'] = $filters['status']; 
    }
    if (!empty($filterParams)) {
        $redirectUrl .= '&' . http_build_query($filterParams);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Log the export activity with filter details
$filterDetails = [];
if ($filters['q'] !== '') $filterDetails[] = 'Search: ' . $filters['q'];
if ($filters['status'] !== '') $filterDetails[] = 'Status: ' . $filters['status'];

$logMessage = empty($filterDetails) ? 'All enquiries exported to CSV' : 'Filtered enquiries exported to CSV (' . implode(', ', $filterDetails) . ')';
logActivity($mysqli, 'Exported enquiries', $logMessage);

// Set headers for CSV download
$filename = 'enquiries_export_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
$headers = [
    'ID',
    'Name',
    'Email',
    'Phone',
    'Property Title',
    'Property ID',
    'Message',
    'Status',
    'Created At'
];

fputcsv($output, $headers);

// Export data
while ($row = $result->fetch_assoc()) {
    $data = [
        $row['id'],
        $row['name'],
        $row['email'] ?? '—',
        $row['phone'] ?? '—',
        $row['property_title'] ?? '—',
        $row['property_id'] ?? '—',
        $row['message'],
        $row['status'],
        $row['created_at']
    ];
    
    fputcsv($output, $data);
}

fclose($output);
exit;
?>
