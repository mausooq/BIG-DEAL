<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

header('Content-Type: application/json');

$mysqli = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $level = $_GET['level'] ?? '';
    $parentId = (int)($_GET['state_id'] ?? $_GET['district_id'] ?? $_GET['city_id'] ?? 0);
    $results = [];

    switch ($level) {
        case 'districts':
            if ($parentId) {
                $stmt = $mysqli->prepare("SELECT id, name FROM districts WHERE state_id = ? ORDER BY name ASC");
                $stmt->bind_param('i', $parentId);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) { $results[] = $row; }
                $stmt->close();
            }
            break;
        case 'cities':
            if ($parentId) {
                $stmt = $mysqli->prepare("SELECT id, name FROM cities WHERE district_id = ? ORDER BY name ASC");
                $stmt->bind_param('i', $parentId);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) { $results[] = $row; }
                $stmt->close();
            }
            break;
        case 'towns':
            if ($parentId) {
                $stmt = $mysqli->prepare("SELECT id, name FROM towns WHERE city_id = ? ORDER BY name ASC");
                $stmt->bind_param('i', $parentId);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) { $results[] = $row; }
                $stmt->close();
            }
            break;
    }
    echo json_encode($results);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $scope = $_POST['scope'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $parentId = (int)($_POST['state_id'] ?? $_POST['district_id'] ?? $_POST['city_id'] ?? 0);
    $response = ['success' => false, 'error' => 'Invalid request'];

    if (empty($name)) {
        $response['error'] = 'Name cannot be empty';
        echo json_encode($response);
        exit();
    }

    try {
        switch ($scope) {
            case 'state':
                $stmt = $mysqli->prepare("INSERT INTO states (name) VALUES (?)");
                $stmt->bind_param('s', $name);
                break;
            case 'district':
                if (!$parentId) throw new Exception('State ID is required');
                $stmt = $mysqli->prepare("INSERT INTO districts (state_id, name) VALUES (?, ?)");
                $stmt->bind_param('is', $parentId, $name);
                break;
            case 'city':
                if (!$parentId) throw new Exception('District ID is required');
                $stmt = $mysqli->prepare("INSERT INTO cities (district_id, name) VALUES (?, ?)");
                $stmt->bind_param('is', $parentId, $name);
                break;
            case 'town':
                if (!$parentId) throw new Exception('City ID is required');
                $stmt = $mysqli->prepare("INSERT INTO towns (city_id, name) VALUES (?, ?)");
                $stmt->bind_param('is', $parentId, $name);
                break;
            default:
                throw new Exception('Invalid scope');
        }

        if ($stmt->execute()) {
            $response = ['success' => true, 'id' => $mysqli->insert_id, 'name' => $name];
        } else {
            $response['error'] = 'Failed to create: ' . $mysqli->error;
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    echo json_encode($response);
    exit();
}

echo json_encode(['error' => 'No action specified']);
?>
