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
    'title' => $_GET['title'] ?? '',
    'location' => $_GET['location'] ?? '',
    'listing_type' => $_GET['listing_type'] ?? '',
    'category_id' => isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null,
    'status' => $_GET['status'] ?? '',
];

$mysqli = db();

// Build query with filters (same logic as index.php)
$where = [];
$types = '';
$params = [];

if ($filters['title'] !== '') { 
    $where[] = 'p.title LIKE ?'; 
    $types .= 's'; 
    $params[] = '%' . $mysqli->real_escape_string($filters['title']) . '%'; 
}
if ($filters['location'] !== '') { 
    $where[] = 'p.location LIKE ?'; 
    $types .= 's'; 
    $params[] = '%' . $mysqli->real_escape_string($filters['location']) . '%'; 
}
if ($filters['listing_type'] !== '') { 
    $where[] = 'p.listing_type = ?'; 
    $types .= 's'; 
    $params[] = $filters['listing_type']; 
}
if ($filters['category_id'] !== null) { 
    $where[] = 'p.category_id = ?'; 
    $types .= 'i'; 
    $params[] = (int)$filters['category_id']; 
}
if ($filters['status'] !== '') { 
    $where[] = 'p.status = ?'; 
    $types .= 's'; 
    $params[] = $filters['status']; 
}

// Build the main query
$sql = "SELECT 
    p.id,
    p.title,
    p.price,
    p.location,
    p.landmark,
    p.area,
    p.configuration,
    p.listing_type,
    p.status,
    p.furniture_status,
    p.ownership_type,
    p.facing,
    p.parking,
    p.balcony,
    p.description,
    p.map_embed_link,
    DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
    c.name AS category_name,
    GROUP_CONCAT(pi.image_url ORDER BY pi.id SEPARATOR '; ') as images
FROM properties p 
LEFT JOIN categories c ON c.id = p.category_id
LEFT JOIN property_images pi ON pi.property_id = p.id";

// Add WHERE clause if filters are applied
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' GROUP BY p.id ORDER BY p.created_at DESC';

// Prepare and execute query
$stmt = $mysqli->prepare($sql);
if ($stmt && $types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt && $stmt->execute();
$result = $stmt ? $stmt->get_result() : $mysqli->query($sql);

if (!$result) {
    die('Error fetching properties: ' . $mysqli->error);
}

// Check if there are any results
$rowCount = $result->num_rows;
if ($rowCount === 0) {
    // No data to export - redirect back with message
    $redirectUrl = 'index.php?export=no_data';
    // Preserve current filters in redirect
    $filterParams = [];
    foreach (['title','location','listing_type','status'] as $k) { 
        if ($filters[$k] !== '') { 
            $filterParams[$k] = $filters[$k]; 
        } 
    }
    if ($filters['category_id'] !== null) { 
        $filterParams['category_id'] = (int)$filters['category_id']; 
    }
    if (!empty($filterParams)) {
        $redirectUrl .= '&' . http_build_query($filterParams);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Log the export activity with filter details
$filterDetails = [];
if ($filters['title'] !== '') $filterDetails[] = 'Title: ' . $filters['title'];
if ($filters['location'] !== '') $filterDetails[] = 'Location: ' . $filters['location'];
if ($filters['listing_type'] !== '') $filterDetails[] = 'Listing Type: ' . $filters['listing_type'];
if ($filters['category_id'] !== null) {
    $catStmt = $mysqli->prepare("SELECT name FROM categories WHERE id = ?");
    $catStmt->bind_param('i', $filters['category_id']);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    $categoryName = $catResult->fetch_assoc()['name'] ?? 'Unknown';
    $filterDetails[] = 'Category: ' . $categoryName;
    $catStmt->close();
}
if ($filters['status'] !== '') $filterDetails[] = 'Status: ' . $filters['status'];

$logMessage = empty($filterDetails) ? 'All properties exported to CSV' : 'Filtered properties exported to CSV (' . implode(', ', $filterDetails) . ')';
logActivity($mysqli, 'Exported properties', $logMessage);

// Set headers for CSV download
$filename = 'properties_export_' . date('Y-m-d_H-i-s') . '.csv';
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
    'Title',
    'Category',
    'Listing Type',
    'Price (₹)',
    'Location',
    'Landmark',
    'Area (sqft)',
    'Configuration',
    'Status',
    'Furniture Status',
    'Ownership Type',
    'Facing',
    'Parking',
    'Balcony',
    'Description',
    'Map Embed Link',
    'Images',
    'Created At'
];

fputcsv($output, $headers);

// Export data
while ($row = $result->fetch_assoc()) {
    $data = [
        $row['id'],
        $row['title'],
        $row['category_name'] ?? '—',
        $row['listing_type'],
        number_format((float)$row['price']),
        $row['location'],
        $row['landmark'] ?? '—',
        $row['area'] ?? '—',
        $row['configuration'] ?? '—',
        $row['status'],
        $row['furniture_status'] ?? '—',
        $row['ownership_type'] ?? '—',
        $row['facing'] ?? '—',
        $row['parking'] ?? '—',
        $row['balcony'] ?? '0',
        $row['description'] ?? '—',
        $row['map_embed_link'] ?? '—',
        $row['images'] ?? '—',
        $row['created_at']
    ];
    
    fputcsv($output, $data);
}

fclose($output);
exit;
?>
