<?php
require_once __DIR__ . '/../auth.php';

// Set JSON header
header('Content-Type: application/json');

// At this point, auth.php already ensured user is logged in

function db() { return getMysqliConnection(); }

// Get project ID
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit();
}

try {
    $mysqli = db();
    
    // Fetch project details
    $stmt = $mysqli->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->bind_param('i', $project_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit();
    }
    
    // Fetch project images ordered by display_order
    $images_stmt = $mysqli->prepare("SELECT id, image_filename, display_order FROM project_images WHERE project_id = ? ORDER BY display_order ASC, id ASC");
    $images_stmt->bind_param('i', $project_id);
    $images_stmt->execute();
    $images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $images_stmt->close();
    
    // Format the project data
    $formatted_project = [
        'id' => $project['id'],
        'name' => $project['name'],
        'description' => $project['description'],
        'location' => $project['location'],
        'order_id' => $project['order_id'],
        'created_at' => $project['created_at'],
        'updated_at' => $project['updated_at']
    ];
    
    echo json_encode(['success' => true, 'project' => $formatted_project, 'images' => $images]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
