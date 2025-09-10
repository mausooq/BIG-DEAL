<?php
require_once __DIR__ . '/../auth.php';

// Set JSON header
header('Content-Type: application/json');

// At this point, auth.php already ensured user is logged in

function db() { return getMysqliConnection(); }

// Get property ID
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($property_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    exit();
}

try {
    $mysqli = db();
    
    // Fetch property details with category
    $stmt = $mysqli->prepare("SELECT p.*, c.name AS category_name FROM properties p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ?");
    $stmt->bind_param('i', $property_id);
    $stmt->execute();
    $property = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit();
    }
    
    // Fetch property images
    $images_stmt = $mysqli->prepare("SELECT id, image_url FROM property_images WHERE property_id = ? ORDER BY id");
    $images_stmt->bind_param('i', $property_id);
    $images_stmt->execute();
    $images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $images_stmt->close();
    
    // Format the property data
    $formatted_property = [
        'id' => $property['id'],
        'title' => $property['title'],
        'description' => $property['description'],
        'map_embed_link' => $property['map_embed_link'],
        'listing_type' => $property['listing_type'],
        'price' => $property['price'],
        'location' => $property['location'],
        'landmark' => $property['landmark'],
        'area' => $property['area'],
        'configuration' => $property['configuration'],
        'furniture_status' => $property['furniture_status'],
        'ownership_type' => $property['ownership_type'],
        'facing' => $property['facing'],
        'parking' => $property['parking'],
        'balcony' => $property['balcony'],
        'status' => $property['status'],
        'category_name' => $property['category_name'],
        'created_at' => $property['created_at']
    ];
    
    echo json_encode(['success' => true, 'property' => $formatted_property, 'images' => $images]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
