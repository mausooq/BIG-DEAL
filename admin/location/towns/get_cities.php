<?php
require_once __DIR__ . '/../../auth.php';

function db() { return getMysqliConnection(); }

header('Content-Type: application/json');

$district_id = (int)($_GET['district_id'] ?? 0);

if ($district_id <= 0) {
    echo json_encode([]);
    exit();
}

$mysqli = db();
$stmt = $mysqli->prepare('SELECT id, name FROM cities WHERE district_id = ? ORDER BY name ASC');
$stmt->bind_param('i', $district_id);
$stmt->execute();
$result = $stmt->get_result();

$cities = [];
while ($row = $result->fetch_assoc()) {
    $cities[] = $row;
}

echo json_encode($cities);
?>
