<?php
require_once 'config/config.php';

echo "<h2>Image Path Debug</h2>";

try {
    $mysqli = getMysqliConnection();
    
    // Check states with images
    echo "<h3>States with Images</h3>";
    $result = $mysqli->query("SELECT id, name, image_url FROM states WHERE image_url IS NOT NULL AND image_url != ''");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: {$row['id']}, Name: {$row['name']}<br>";
            echo "Image URL: '{$row['image_url']}'<br>";
            
            // Check if file exists
            $full_path = __DIR__ . '/' . $row['image_url'];
            echo "Full Path: $full_path<br>";
            echo "File Exists: " . (file_exists($full_path) ? 'YES' : 'NO') . "<br>";
            echo "File Size: " . (file_exists($full_path) ? filesize($full_path) . ' bytes' : 'N/A') . "<br>";
            echo "<hr>";
        }
    }
    
    // Check upload directory
    echo "<h3>Upload Directory Check</h3>";
    $upload_dir = __DIR__ . '/uploads/locations/';
    echo "Upload Directory: $upload_dir<br>";
    echo "Directory Exists: " . (is_dir($upload_dir) ? 'YES' : 'NO') . "<br>";
    
    if (is_dir($upload_dir)) {
        echo "Directory Contents:<br>";
        $files = scandir($upload_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "- $file<br>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>


