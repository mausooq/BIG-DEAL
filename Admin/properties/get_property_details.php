<?php
session_start();
require_once '../../config/config.php';

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

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
    
    // Format the property data
    $formatted_property = [
        'id' => $property['id'],
        'title' => $property['title'],
        'description' => $property['description'],
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
    
    echo json_encode(['success' => true, 'property' => $formatted_property]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
