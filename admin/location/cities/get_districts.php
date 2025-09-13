<?php
require_once __DIR__ . '/../../auth.php';

function db() { return getMysqliConnection(); }

header('Content-Type: application/json');

$state_id = (int)($_GET['state_id'] ?? 0);

if ($state_id <= 0) {
    echo json_encode([]);
    exit();
}

$mysqli = db();
$stmt = $mysqli->prepare('SELECT id, name FROM districts WHERE state_id = ? ORDER BY name ASC');
$stmt->bind_param('i', $state_id);
$stmt->execute();
$result = $stmt->get_result();

$districts = [];
while ($row = $result->fetch_assoc()) {
    $districts[] = $row;
}

echo json_encode($districts);
?>
