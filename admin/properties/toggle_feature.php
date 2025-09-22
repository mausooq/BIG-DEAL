<?php
require_once __DIR__ . '/../auth.php';
header('Content-Type: application/json');

function db() { return getMysqliConnection(); }

$property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
if ($property_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid property id']); exit; }

$mysqli = db();

$stmt = $mysqli->prepare('SELECT id FROM features WHERE property_id = ? LIMIT 1');
$stmt && $stmt->bind_param('i', $property_id) && $stmt->execute();
$row = $stmt ? $stmt->get_result()->fetch_assoc() : null;
$stmt && $stmt->close();

if ($row) {
    $del = $mysqli->prepare('DELETE FROM features WHERE id = ?');
    $del && $del->bind_param('i', $row['id']);
    $ok = $del && $del->execute();
    $del && $del->close();
    echo json_encode(['success'=>$ok, 'is_featured'=>false]);
    exit;
}

$ins = $mysqli->prepare('INSERT INTO features (property_id) VALUES (?)');
$ins && $ins->bind_param('i', $property_id);
$ok = $ins && $ins->execute();
$ins && $ins->close();

echo json_encode(['success'=>$ok, 'is_featured'=>true]);
?>


