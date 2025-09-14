<?php
require_once __DIR__ . '/../auth.php';

function db() { return getMysqliConnection(); }

function fetchScalar($sql) {
    $mysqli = db();
    $res = $mysqli->query($sql);
    $row = $res ? $res->fetch_row() : [0];
    return (int)($row[0] ?? 0);
}

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

// Handle state operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mysqli = db();

    if ($action === 'add_state') {
        $name = trim($_POST['name'] ?? '');

        // Validation
        if (!$name) {
            $_SESSION['error_message'] = 'State name is required.';
        } else {
            // Handle image upload
            $image_filename = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'state_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('INSERT INTO states (name, image_url) VALUES (?, ?)');
                if ($stmt) {
                    $stmt->bind_param('ss', $name, $image_filename);
                    if ($stmt->execute()) {
                        $state_id = $mysqli->insert_id;
                        logActivity($mysqli, 'Added state', 'Name: ' . $name . ', ID: ' . $state_id);
                        $_SESSION['success_message'] = 'State added successfully!';
                    } else {
                        $_SESSION['error_message'] = 'Failed to add state: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'edit_state') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $current_image = $_POST['current_image'] ?? '';

        if (!$id || !$name) {
            $_SESSION['error_message'] = 'ID and state name are required.';
        } else {
            $image_filename = $current_image;
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'state_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('UPDATE states SET name = ?, image_url = ? WHERE id = ?');
                $stmt->bind_param('ssi', $name, $image_filename, $id);
                
                if ($stmt) {
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            logActivity($mysqli, 'Updated state', 'Name: ' . $name . ', ID: ' . $id);
                            $_SESSION['success_message'] = 'State updated successfully!';
                        } else {
                            $_SESSION['error_message'] = 'No changes made or state not found.';
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to update state: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'delete_state') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Get state details for logging
            $stmt = $mysqli->prepare('SELECT name FROM states WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $state_name = $result ? $result['name'] : 'Unknown';
            $stmt->close();

            $stmt = $mysqli->prepare('DELETE FROM states WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        logActivity($mysqli, 'Deleted state', 'Name: ' . $state_name . ', ID: ' . $id);
                        $_SESSION['success_message'] = 'State deleted successfully!';
                    } else {
                        $_SESSION['error_message'] = 'State not found.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Failed to delete state: ' . $mysqli->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
            }
        }
    } elseif ($action === 'add_district') {
        $name = trim($_POST['name'] ?? '');
        $state_id = (int)($_POST['state_id'] ?? 0);

        if (!$name || !$state_id) {
            $_SESSION['error_message'] = 'District name and state are required.';
        } else {
            $image_filename = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'district_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('INSERT INTO districts (name, state_id, image_url) VALUES (?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sis', $name, $state_id, $image_filename);
                    if ($stmt->execute()) {
                        $district_id = $mysqli->insert_id;
                        logActivity($mysqli, 'Added district', 'Name: ' . $name . ', ID: ' . $district_id);
                        $_SESSION['success_message'] = 'District added successfully!';
                    } else {
                        $_SESSION['error_message'] = 'Failed to add district: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'edit_district') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $state_id = (int)($_POST['state_id'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';

        if (!$id || !$name || !$state_id) {
            $_SESSION['error_message'] = 'ID, district name and state are required.';
        } else {
            $image_filename = $current_image;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'district_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('UPDATE districts SET name = ?, state_id = ?, image_url = ? WHERE id = ?');
                $stmt->bind_param('sisi', $name, $state_id, $image_filename, $id);
                
                if ($stmt) {
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            logActivity($mysqli, 'Updated district', 'Name: ' . $name . ', ID: ' . $id);
                            $_SESSION['success_message'] = 'District updated successfully!';
                        } else {
                            $_SESSION['error_message'] = 'No changes made or district not found.';
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to update district: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'delete_district') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $mysqli->prepare('SELECT name FROM districts WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $district_name = $result ? $result['name'] : 'Unknown';
            $stmt->close();

            $stmt = $mysqli->prepare('DELETE FROM districts WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        logActivity($mysqli, 'Deleted district', 'Name: ' . $district_name . ', ID: ' . $id);
                        $_SESSION['success_message'] = 'District deleted successfully!';
                    } else {
                        $_SESSION['error_message'] = 'District not found.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Failed to delete district: ' . $mysqli->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
            }
        }
    } elseif ($action === 'add_city') {
        $name = trim($_POST['name'] ?? '');
        $district_id = (int)($_POST['district_id'] ?? 0);

        if (!$name || !$district_id) {
            $_SESSION['error_message'] = 'City name and district are required.';
        } else {
            $image_filename = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'city_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('INSERT INTO cities (name, district_id, image_url) VALUES (?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sis', $name, $district_id, $image_filename);
                    if ($stmt->execute()) {
                        $city_id = $mysqli->insert_id;
                        logActivity($mysqli, 'Added city', 'Name: ' . $name . ', ID: ' . $city_id);
                        $_SESSION['success_message'] = 'City added successfully!';
                    } else {
                        $_SESSION['error_message'] = 'Failed to add city: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'edit_city') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $district_id = (int)($_POST['district_id'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';

        if (!$id || !$name || !$district_id) {
            $_SESSION['error_message'] = 'ID, city name and district are required.';
        } else {
            $image_filename = $current_image;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'city_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('UPDATE cities SET name = ?, district_id = ?, image_url = ? WHERE id = ?');
                $stmt->bind_param('sisi', $name, $district_id, $image_filename, $id);
                
                if ($stmt) {
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            logActivity($mysqli, 'Updated city', 'Name: ' . $name . ', ID: ' . $id);
                            $_SESSION['success_message'] = 'City updated successfully!';
                        } else {
                            $_SESSION['error_message'] = 'No changes made or city not found.';
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to update city: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'delete_city') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $mysqli->prepare('SELECT name FROM cities WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $city_name = $result ? $result['name'] : 'Unknown';
            $stmt->close();

            $stmt = $mysqli->prepare('DELETE FROM cities WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        logActivity($mysqli, 'Deleted city', 'Name: ' . $city_name . ', ID: ' . $id);
                        $_SESSION['success_message'] = 'City deleted successfully!';
                    } else {
                        $_SESSION['error_message'] = 'City not found.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Failed to delete city: ' . $mysqli->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
            }
        }
    } elseif ($action === 'add_town') {
        $name = trim($_POST['name'] ?? '');
        $city_id = (int)($_POST['city_id'] ?? 0);

        if (!$name || !$city_id) {
            $_SESSION['error_message'] = 'Town name and city are required.';
        } else {
            $image_filename = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'town_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('INSERT INTO towns (name, city_id, image_url) VALUES (?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sis', $name, $city_id, $image_filename);
                    if ($stmt->execute()) {
                        $town_id = $mysqli->insert_id;
                        logActivity($mysqli, 'Added town', 'Name: ' . $name . ', ID: ' . $town_id);
                        $_SESSION['success_message'] = 'Town added successfully!';
                    } else {
                        $_SESSION['error_message'] = 'Failed to add town: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'edit_town') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $city_id = (int)($_POST['city_id'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';

        if (!$id || !$name || !$city_id) {
            $_SESSION['error_message'] = 'ID, town name and city are required.';
        } else {
            $image_filename = $current_image;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/locations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $timestamp = time();
                    $image_filename = 'town_' . $timestamp . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $image_filename;
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        $_SESSION['error_message'] = 'Image is too large. Maximum size is 5MB.';
                    } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        // Image uploaded successfully
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload image.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid file type. Allowed types: JPG, PNG, GIF, WebP';
                }
            }

            if (!isset($_SESSION['error_message'])) {
                $stmt = $mysqli->prepare('UPDATE towns SET name = ?, city_id = ?, image_url = ? WHERE id = ?');
                $stmt->bind_param('sisi', $name, $city_id, $image_filename, $id);
                
                if ($stmt) {
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            logActivity($mysqli, 'Updated town', 'Name: ' . $name . ', ID: ' . $id);
                            $_SESSION['success_message'] = 'Town updated successfully!';
                        } else {
                            $_SESSION['error_message'] = 'No changes made or town not found.';
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to update town: ' . $mysqli->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
                }
            }
        }
    } elseif ($action === 'delete_town') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $mysqli->prepare('SELECT name FROM towns WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $town_name = $result ? $result['name'] : 'Unknown';
            $stmt->close();

            $stmt = $mysqli->prepare('DELETE FROM towns WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        logActivity($mysqli, 'Deleted town', 'Name: ' . $town_name . ', ID: ' . $id);
                        $_SESSION['success_message'] = 'Town deleted successfully!';
                    } else {
                        $_SESSION['error_message'] = 'Town not found.';
                    }
                } else {
                    $_SESSION['error_message'] = 'Failed to delete town: ' . $mysqli->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = 'Database error: ' . $mysqli->error;
            }
        }
    }

    header('Location: index.php');
    exit();
}

// Get statistics for all location types
$mysqli = db();

$totalStates = fetchScalar("SELECT COUNT(*) FROM states");
$totalDistricts = fetchScalar("SELECT COUNT(*) FROM districts");
$totalCities = fetchScalar("SELECT COUNT(*) FROM cities");
$totalTowns = fetchScalar("SELECT COUNT(*) FROM towns");

// Get states with search and pagination for States tab
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if ($search) {
    $whereClause = ' WHERE name LIKE ?';
    $types = 's';
    $searchParam = '%' . $mysqli->real_escape_string($search) . '%';
    $params[] = $searchParam;
}

$sql = 'SELECT id, name, image_url FROM states' . $whereClause . ' ORDER BY name ASC LIMIT ? OFFSET ?';
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $mysqli->prepare($sql);
if ($stmt && $types) { $stmt->bind_param($types, ...$params); }
$stmt && $stmt->execute();
$states = $stmt ? $stmt->get_result() : $mysqli->query('SELECT id, name, image_url FROM states ORDER BY name ASC LIMIT 10');

// Count
$countSql = 'SELECT COUNT(*) FROM states' . $whereClause;
$countStmt = $mysqli->prepare($countSql);
if ($countStmt && $search) {
    $countSearchParam = '%' . $mysqli->real_escape_string($search) . '%';
    $countStmt->bind_param('s', $countSearchParam);
}
$countStmt && $countStmt->execute();
$totalCountRow = $countStmt ? $countStmt->get_result()->fetch_row() : [0];
$totalCount = (int)($totalCountRow[0] ?? 0);
$totalPages = (int)ceil($totalCount / $limit);

// Get districts with search and pagination for Districts tab
$districtSearch = $_GET['district_search'] ?? '';
$districtPage = max(1, (int)($_GET['district_page'] ?? 1));
$districtOffset = ($districtPage - 1) * $limit;

$districtWhereClause = '';
$districtParams = [];
$districtTypes = '';

if ($districtSearch) {
    $districtWhereClause = ' WHERE d.name LIKE ?';
    $districtTypes = 's';
    $districtSearchParam = '%' . $mysqli->real_escape_string($districtSearch) . '%';
    $districtParams[] = $districtSearchParam;
}

$districtSql = 'SELECT d.id, d.name, d.image_url, s.name as state_name, s.id as state_id FROM districts d LEFT JOIN states s ON d.state_id = s.id' . $districtWhereClause . ' ORDER BY d.name ASC LIMIT ? OFFSET ?';
$districtTypes .= 'ii';
$districtParams[] = $limit;
$districtParams[] = $districtOffset;

$districtStmt = $mysqli->prepare($districtSql);
if ($districtStmt && $districtTypes) { $districtStmt->bind_param($districtTypes, ...$districtParams); }
$districtStmt && $districtStmt->execute();
$districts = $districtStmt ? $districtStmt->get_result() : $mysqli->query('SELECT d.id, d.name, d.image_url, s.name as state_name, s.id as state_id FROM districts d LEFT JOIN states s ON d.state_id = s.id ORDER BY d.name ASC LIMIT 10');

// Count districts
$districtCountSql = 'SELECT COUNT(*) FROM districts d' . $districtWhereClause;
$districtCountStmt = $mysqli->prepare($districtCountSql);
if ($districtCountStmt && $districtSearch) {
    $districtCountSearchParam = '%' . $mysqli->real_escape_string($districtSearch) . '%';
    $districtCountStmt->bind_param('s', $districtCountSearchParam);
}
$districtCountStmt && $districtCountStmt->execute();
$districtTotalCountRow = $districtCountStmt ? $districtCountStmt->get_result()->fetch_row() : [0];
$districtTotalCount = (int)($districtTotalCountRow[0] ?? 0);
$districtTotalPages = (int)ceil($districtTotalCount / $limit);

// Get cities with search and pagination for Cities tab
$citySearch = $_GET['city_search'] ?? '';
$cityPage = max(1, (int)($_GET['city_page'] ?? 1));
$cityOffset = ($cityPage - 1) * $limit;

$cityWhereClause = '';
$cityParams = [];
$cityTypes = '';

if ($citySearch) {
    $cityWhereClause = ' WHERE c.name LIKE ?';
    $cityTypes = 's';
    $citySearchParam = '%' . $mysqli->real_escape_string($citySearch) . '%';
    $cityParams[] = $citySearchParam;
}

$citySql = 'SELECT c.id, c.name, c.image_url, d.name as district_name, d.id as district_id, s.name as state_name FROM cities c LEFT JOIN districts d ON c.district_id = d.id LEFT JOIN states s ON d.state_id = s.id' . $cityWhereClause . ' ORDER BY c.name ASC LIMIT ? OFFSET ?';
$cityTypes .= 'ii';
$cityParams[] = $limit;
$cityParams[] = $cityOffset;

$cityStmt = $mysqli->prepare($citySql);
if ($cityStmt && $cityTypes) { $cityStmt->bind_param($cityTypes, ...$cityParams); }
$cityStmt && $cityStmt->execute();
$cities = $cityStmt ? $cityStmt->get_result() : $mysqli->query('SELECT c.id, c.name, c.image_url, d.name as district_name, d.id as district_id, s.name as state_name FROM cities c LEFT JOIN districts d ON c.district_id = d.id LEFT JOIN states s ON d.state_id = s.id ORDER BY c.name ASC LIMIT 10');

// Count cities
$cityCountSql = 'SELECT COUNT(*) FROM cities c' . $cityWhereClause;
$cityCountStmt = $mysqli->prepare($cityCountSql);
if ($cityCountStmt && $citySearch) {
    $cityCountSearchParam = '%' . $mysqli->real_escape_string($citySearch) . '%';
    $cityCountStmt->bind_param('s', $cityCountSearchParam);
}
$cityCountStmt && $cityCountStmt->execute();
$cityTotalCountRow = $cityCountStmt ? $cityCountStmt->get_result()->fetch_row() : [0];
$cityTotalCount = (int)($cityTotalCountRow[0] ?? 0);
$cityTotalPages = (int)ceil($cityTotalCount / $limit);

// Get towns with search and pagination for Towns tab
$townSearch = $_GET['town_search'] ?? '';
$townPage = max(1, (int)($_GET['town_page'] ?? 1));
$townOffset = ($townPage - 1) * $limit;

$townWhereClause = '';
$townParams = [];
$townTypes = '';

if ($townSearch) {
    $townWhereClause = ' WHERE t.name LIKE ?';
    $townTypes = 's';
    $townSearchParam = '%' . $mysqli->real_escape_string($townSearch) . '%';
    $townParams[] = $townSearchParam;
}

$townSql = 'SELECT t.id, t.name, t.image_url, c.name as city_name, c.id as city_id, d.name as district_name, s.name as state_name FROM towns t LEFT JOIN cities c ON t.city_id = c.id LEFT JOIN districts d ON c.district_id = d.id LEFT JOIN states s ON d.state_id = s.id' . $townWhereClause . ' ORDER BY t.name ASC LIMIT ? OFFSET ?';
$townTypes .= 'ii';
$townParams[] = $limit;
$townParams[] = $townOffset;

$townStmt = $mysqli->prepare($townSql);
if ($townStmt && $townTypes) { $townStmt->bind_param($townTypes, ...$townParams); }
$townStmt && $townStmt->execute();
$towns = $townStmt ? $townStmt->get_result() : $mysqli->query('SELECT t.id, t.name, t.image_url, c.name as city_name, c.id as city_id, d.name as district_name, s.name as state_name FROM towns t LEFT JOIN cities c ON t.city_id = c.id LEFT JOIN districts d ON c.district_id = d.id LEFT JOIN states s ON d.state_id = s.id ORDER BY t.name ASC LIMIT 10');

// Count towns
$townCountSql = 'SELECT COUNT(*) FROM towns t' . $townWhereClause;
$townCountStmt = $mysqli->prepare($townCountSql);
if ($townCountStmt && $townSearch) {
    $townCountSearchParam = '%' . $mysqli->real_escape_string($townSearch) . '%';
    $townCountStmt->bind_param('s', $townCountSearchParam);
}
$townCountStmt && $townCountStmt->execute();
$townTotalCountRow = $townCountStmt ? $townCountStmt->get_result()->fetch_row() : [0];
$townTotalCount = (int)($townTotalCountRow[0] ?? 0);
$townTotalPages = (int)ceil($townTotalCount / $limit);

// Get all states for dropdowns
$allStates = $mysqli->query("SELECT id, name FROM states ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$allDistricts = $mysqli->query("SELECT id, name, state_id FROM districts ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$allCities = $mysqli->query("SELECT id, name, district_id FROM cities ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Management - Big Deal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/animated-buttons.css" rel="stylesheet">
    <style>
        /* Base */
        :root{
            --bg:#F1EFEC;/* page background */
            --card:#ffffff;/* surfaces */
            --muted:#6b7280;/* secondary text */
            --line:#e9eef5;/* borders */
            --brand-dark:#2f2f2f;/* logo dark */
            --primary:#e11d2a;/* logo red accent */
            --primary-600:#b91c1c;/* darker red hover */
            --radius:16px;
        }
        body{ background:var(--bg); color:#111827; }
        .content{ margin-left:284px; }
        /* Sidebar */
        .sidebar{ width:260px; min-height:93vh; background:var(--card); border-right:1px solid var(--line); position:fixed; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .list-group-item{ border:0; padding:.75rem 1rem; border-radius:10px; margin:.15rem .25rem; color:#111827; }
        .list-group-item.active{ background:#eef2ff; color:#3730a3; font-weight:600; }
        .list-group-item:hover{ background:#f8fafc; }
        /* Topbar */
        .navbar{ background:var(--card)!important; border-radius:16px; margin:12px; box-shadow:0 8px 20px rgba(0,0,0,.05); }
        .text-primary{ color:var(--primary)!important; }
        .input-group .form-control{ border-color:var(--line); }
        .input-group-text{ border-color:var(--line); }
        /* Cards */
        .card{ border:0; border-radius:var(--radius); background:var(--card); }
        .quick-card{ border:1px solid #eef2f7; border-radius:var(--radius); }
        /* Location Management Cards */
        .location-card{ 
            background: var(--card); 
            border: 1px solid var(--line); 
            border-radius: var(--radius); 
            text-align: center; 
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            min-height: 280px;
        }
        .location-card:hover{ 
            transform: translateY(-5px); 
            box-shadow: 0 12px 28px rgba(0,0,0,.12); 
            border-color: var(--primary);
        }
        .location-card::before{
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-600));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .location-card:hover::before{
            transform: scaleX(1);
        }
        .location-icon{
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
            transition: all 0.3s ease;
        }
        .location-card:hover .location-icon{
            transform: scale(1.1);
        }
        .location-icon.states{ background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .location-icon.districts{ background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .location-icon.cities{ background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .location-icon.towns{ background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .location-title{
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--brand-dark);
        }
        .location-count{
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .location-description{
            color: var(--muted);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .location-btn{
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .location-btn:hover{
            background: var(--primary-600);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(225, 29, 42, 0.3);
        }
         /* Tab Navigation */
         .nav-tabs{ border-bottom: 1px solid var(--line); }
         .nav-tabs .nav-link{ 
             border: none; 
             color: var(--muted); 
             font-weight: 500; 
             padding: 1rem 1.5rem; 
             border-radius: 0;
             transition: all 0.3s ease;
         }
         .nav-tabs .nav-link:hover{ 
             border: none; 
             color: var(--primary); 
             background: rgba(225, 29, 42, 0.05);
         }
         .nav-tabs .nav-link.active{ 
             background: var(--primary); 
             color: white; 
             border: none;
             font-weight: 600;
         }
         .nav-tabs .nav-link.active:hover{ 
             background: var(--primary-600); 
             color: white; 
         }
         
         /* Table styles */
         .table{ --bs-table-bg:transparent;  border-bottom-width:0 !important; }
         .table thead th{ color:var(--muted); font-size:.875rem; font-weight:600; }
         .table tbody tr:hover{ background:#f9fafb; }
         .table td{ vertical-align: middle; }

         /* Table wrapper */
         .table-wrap{ border:0; border-radius:12px; overflow:hidden; background:#fff; }
         /* Inner borders (match Properties) */
         .table-inner thead th{ background:transparent; border-bottom:1px solid var(--line) !important; color:#111827; font-weight:600; }
         .table-inner thead th, .table-inner tbody td{ padding:0; }
         .table-inner tbody td{ border-top:1px solid var(--line) !important; }
         .table-inner td, .table-inner th{ border-left:0; border-right:0; }
         .table-inner tbody tr{ position:static; }
         .table-inner tbody tr::after{ display:none !important; content:none; }
         /* Actions column - sticky like Properties */
         .actions-header{ 
             position:sticky;
             right:0;
             background:#fff;
             z-index:10;
             text-align:center;
             font-weight:600;
             padding:12px 8px;
             border-left:1px solid var(--line);
             border-bottom:1px solid var(--line) !important;
         }
         .actions-cell{ 
             position:sticky;
             right:0;
             background:#fff;
             z-index:10;
             padding:8px 8px !important; 
             min-width:120px;
             max-width:120px;
             text-align:center;
             vertical-align:middle;
             border-left:1px solid var(--line);
             white-space:nowrap;
             overflow:hidden;
         }
         .actions-cell .btn{ 
             width:28px; 
             height:28px; 
             display:inline-flex; 
             align-items:center; 
             justify-content:center; 
             padding:0; 
             border-radius:6px;
         }
         .badge-soft{ background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
         
         /* Button consistency */
         .btn{ border-radius:8px; font-weight:500; }
         .btn-sm{ padding:0.5rem 1rem; font-size:0.875rem; }
         
         /* Action buttons */
         .actions-cell .btn{ 
             width:28px; 
             height:28px; 
             padding:0; 
             display:inline-flex; 
             align-items:center; 
             justify-content:center; 
             padding:0; 
             border-radius:6px;
         }
         
         /* Mobile responsiveness */
         @media (max-width: 991.98px){
             .sidebar{ left:-300px; right:auto; transition:left .25s ease; position:fixed; top:0; bottom:0; margin:12px; z-index:1050; }
             .sidebar.open{ left:12px; }
             .content{ margin-left:0; }
         }
         @media (max-width: 575.98px){
             .nav-tabs .nav-link{ padding: 0.75rem 1rem; font-size: 0.875rem; }
         }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../components/sidebar.php'; renderAdminSidebar('location'); ?>
    <div class="content">
        <?php require_once __DIR__ . '/../components/topbar.php'; renderAdminTopbar($_SESSION['admin_username'] ?? 'Admin', 'Locations'); ?>

        <div class="container-fluid p-4">


             <!-- Tab Navigation -->
             <div class="card mb-4">
                 <div class="card-body p-0">
                     <ul class="nav nav-tabs nav-fill" id="locationTabs" role="tablist">
                         <li class="nav-item" role="presentation">
                             <button class="nav-link active" id="states-tab" data-bs-toggle="tab" data-bs-target="#states" type="button" role="tab">
                                 <i class="fa-solid fa-map me-2"></i>States (<?php echo $totalStates; ?>)
                             </button>
                         </li>
                         <li class="nav-item" role="presentation">
                             <button class="nav-link" id="districts-tab" data-bs-toggle="tab" data-bs-target="#districts" type="button" role="tab">
                                 <i class="fa-solid fa-building me-2"></i>Districts (<?php echo $totalDistricts; ?>)
                             </button>
                         </li>
                         <li class="nav-item" role="presentation">
                             <button class="nav-link" id="cities-tab" data-bs-toggle="tab" data-bs-target="#cities" type="button" role="tab">
                                 <i class="fa-solid fa-city me-2"></i>Cities (<?php echo $totalCities; ?>)
                             </button>
                         </li>
                         <li class="nav-item" role="presentation">
                             <button class="nav-link" id="towns-tab" data-bs-toggle="tab" data-bs-target="#towns" type="button" role="tab">
                                 <i class="fa-solid fa-map-marker-alt me-2"></i>Towns (<?php echo $totalTowns; ?>)
                             </button>
                         </li>
                     </ul>
                 </div>
             </div>

             <!-- Tab Content -->
             <div class="tab-content" id="locationTabsContent">
                 <!-- States Tab -->
                 <div class="tab-pane fade show active" id="states" role="tabpanel">
                     <div class="card">
                         <div class="card-body">
                             <!-- Search and Filter -->
                             <div class="toolbar mb-5 p-3 bg-light rounded border">
                                 <div class="d-flex align-items-center gap-2">
                                     <form class="d-flex flex-grow-1" method="get">
                                         <div class="input-group">
                                             <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                                             <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search states by name" id="searchInput">
                                         </div>
                                         <button class="btn btn-primary ms-2" type="submit">Search</button>
                                         <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                                     </form>
                                     <button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addStateModal">
                                         <span class="text">Add State</span>
                                         <span class="icon">
                                             <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
                                             <span class="buttonSpan">+</span>
                                         </span>
                                     </button>
                                 </div>
                             </div>

                             <!-- States Cards -->
                             <div class="row g-3">
                                 <?php if ($states && $states->num_rows > 0): ?>
                                     <?php while ($state = $states->fetch_assoc()): ?>
                                         <div class="col-lg-3 col-md-4 col-sm-6" data-state='<?php echo json_encode($state, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                                             <div class="card location-card h-100 position-relative">
                                                 <!-- Full Cover Image -->
                                                 <div class="card-img-overlay d-flex flex-column justify-content-between" style="padding: 0;">
                                                     <?php if (!empty($state['image_url'])): ?>
                                                         <img src="../../uploads/locations/<?php echo htmlspecialchars($state['image_url']); ?>" 
                                                              alt="<?php echo htmlspecialchars($state['name']); ?>" 
                                                              class="w-100 h-100 position-absolute" 
                                                              style="object-fit: cover; z-index: 1;">
                                                     <?php else: ?>
                                                         <!-- Default Icon for No Image -->
                                                         <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center" style="background: #f8f9fa; z-index: 1;">
                                                             <i class="fa-solid fa-map-location-dot text-dark" style="font-size: 4rem;"></i>
                                                         </div>
                                                     <?php endif; ?>
                                                    
                                                     <!-- Action Buttons Overlay -->
                                                     <div class="position-absolute top-0 end-0 p-3" style="z-index: 2;">
                                                         <div class="btn-group" role="group">
                                                             <button class="btn btn-light btn-sm btn-edit-state" 
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#editStateModal" 
                                                                     title="Edit State"
                                                                     style="border-radius: 50%; width: 36px; height: 36px; padding: 0; backdrop-filter: blur(10px); background: rgba(255,255,255,0.9);">
                                                                 <i class="fas fa-edit"></i>
                                                             </button>
                                                             <button class="btn btn-light btn-sm btn-delete-state" 
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#deleteStateModal" 
                                                                     title="Delete State"
                                                                     style="border-radius: 50%; width: 36px; height: 36px; padding: 0; backdrop-filter: blur(10px); background: rgba(255,255,255,0.9);">
                                                                 <i class="fas fa-trash"></i>
                                                             </button>
                                                         </div>
                                                     </div>
                                                    
                                                     <!-- Content Overlay -->
                                                     <div class="p-3 text-white d-flex flex-column justify-content-end" style="z-index: 1; height: 100%;">
                                                         <div class="mt-auto">
                                                             <h5 class="card-title mb-2 fw-bold text-white">
                                                                 <a href="districts/index.php?state_id=<?php echo $state['id']; ?>" 
                                                                    class="text-decoration-none text-white" 
                                                                    title="View districts in this state">
                                                                     <?php echo htmlspecialchars($state['name']); ?>
                                                                 </a>
                                                             </h5>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     <?php endwhile; ?>
                                 <?php else: ?>
                                     <div class="col-12">
                                         <div class="text-center py-5 text-muted">
                                             <i class="fa-solid fa-map-location-dot fa-3x mb-3"></i>
                                             <h5>No states found</h5>
                                             <p>Add your first state to get started</p>
                                         </div>
                                     </div>
                                 <?php endif; ?>
                             </div>

                             <?php if ($totalPages > 1): ?>
                             <nav aria-label="States pagination">
                                 <ul class="pagination justify-content-center">
                                     <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                     <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                         <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                     </li>
                                     <?php endfor; ?>
                                 </ul>
                             </nav>
                             <?php endif; ?>
                         </div>
                     </div>
                 </div>

                 <!-- Districts Tab -->
                 <div class="tab-pane fade" id="districts" role="tabpanel">
                     <div class="card">
                         <div class="card-body">
                             <!-- Search and Filter -->
                             <div class="toolbar mb-5 p-3 bg-light rounded border">
                                 <div class="d-flex align-items-center gap-2">
                                     <form class="d-flex flex-grow-1" method="get">
                                         <div class="input-group">
                                             <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                                             <input type="text" class="form-control" name="district_search" value="<?php echo htmlspecialchars($districtSearch); ?>" placeholder="Search districts by name" id="districtSearchInput">
                                         </div>
                                         <button class="btn btn-primary ms-2" type="submit">Search</button>
                                         <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                                     </form>
                                     <button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addDistrictModal">
                                         <span class="text">Add District</span>
                                         <span class="icon">
                                             <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
                                             <span class="buttonSpan">+</span>
                                         </span>
                                     </button>
                                 </div>
                             </div>

                             <!-- Districts Cards -->
                             <div class="row g-3">
                                 <?php if ($districts && $districts->num_rows > 0): ?>
                                     <?php while ($district = $districts->fetch_assoc()): ?>
                                         <div class="col-lg-3 col-md-4 col-sm-6" data-district='<?php echo json_encode($district, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                                             <div class="card location-card h-100 position-relative">
                                                 <!-- Full Cover Image -->
                                                 <div class="card-img-overlay d-flex flex-column justify-content-between" style="padding: 0;">
                                                     <?php if (!empty($district['image_url'])): ?>
                                                         <img src="../../uploads/locations/<?php echo htmlspecialchars($district['image_url']); ?>" 
                                                              alt="<?php echo htmlspecialchars($district['name']); ?>" 
                                                              class="w-100 h-100 position-absolute" 
                                                              style="object-fit: cover; z-index: 1;">
                                                     <?php else: ?>
                                                         <!-- Default Icon for No Image -->
                                                         <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center" style="background: #f8f9fa; z-index: 1;">
                                                             <i class="fa-solid fa-building text-dark" style="font-size: 4rem;"></i>
                                                         </div>
                                                     <?php endif; ?>
                                                    
                                                     <!-- Action Buttons Overlay -->
                                                     <div class="position-absolute top-0 end-0 p-3" style="z-index: 2;">
                                                         <div class="btn-group" role="group">
                                                             <button class="btn btn-light btn-sm btn-edit-district" 
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#editDistrictModal" 
                                                                     title="Edit District"
                                                                     style="border-radius: 50%; width: 36px; height: 36px; padding: 0; backdrop-filter: blur(10px); background: rgba(255,255,255,0.9);">
                                                                 <i class="fas fa-edit"></i>
                                                             </button>
                                                             <button class="btn btn-light btn-sm btn-delete-district" 
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#deleteDistrictModal" 
                                                                     title="Delete District"
                                                                     style="border-radius: 50%; width: 36px; height: 36px; padding: 0; backdrop-filter: blur(10px); background: rgba(255,255,255,0.9);">
                                                                 <i class="fas fa-trash"></i>
                                                             </button>
                                                         </div>
                                                     </div>
                                                    
                                                     <!-- Content Overlay -->
                                                     <div class="p-3 text-white d-flex flex-column justify-content-end" style="z-index: 1; height: 100%;">
                                                         <div class="mt-auto">
                                                             <h5 class="card-title mb-2 fw-bold text-white">
                                                                 <a href="cities/index.php?district_id=<?php echo $district['id']; ?>" 
                                                                    class="text-decoration-none text-white" 
                                                                    title="View cities in this district">
                                                                     <?php echo htmlspecialchars($district['name']); ?>
                                                                 </a>
                                                             </h5>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     <?php endwhile; ?>
                                 <?php else: ?>
                                     <div class="col-12">
                                         <div class="text-center py-5 text-muted">
                                             <i class="fa-solid fa-building fa-3x mb-3"></i>
                                             <h5>No districts found</h5>
                                             <p>Add your first district to get started</p>
                                         </div>
                                     </div>
                                 <?php endif; ?>
                             </div>

                             <?php if ($districtTotalPages > 1): ?>
                             <nav aria-label="Districts pagination">
                                 <ul class="pagination justify-content-center">
                                     <?php for ($i = 1; $i <= $districtTotalPages; $i++): ?>
                                     <li class="page-item <?php echo $i === $districtPage ? 'active' : ''; ?>">
                                         <a class="page-link" href="?district_page=<?php echo $i; ?>&district_search=<?php echo urlencode($districtSearch); ?>"><?php echo $i; ?></a>
                                     </li>
                                     <?php endfor; ?>
                                 </ul>
                             </nav>
                             <?php endif; ?>
                         </div>
                     </div>
                 </div>

                 <!-- Cities Tab -->
                 <div class="tab-pane fade" id="cities" role="tabpanel">
                     <div class="card">
                         <div class="card-body">
                             <!-- Search and Filter -->
                             <div class="toolbar mb-5 p-3 bg-light rounded border">
                                 <div class="d-flex align-items-center gap-2">
                                     <form class="d-flex flex-grow-1" method="get">
                                         <div class="input-group">
                                             <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                                             <input type="text" class="form-control" name="city_search" value="<?php echo htmlspecialchars($citySearch); ?>" placeholder="Search cities by name" id="citySearchInput">
                                         </div>
                                         <button class="btn btn-primary ms-2" type="submit">Search</button>
                                         <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                                     </form>
                                     <button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addCityModal">
                                         <span class="text">Add City</span>
                                         <span class="icon">
                                             <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
                                             <span class="buttonSpan">+</span>
                                         </span>
                                     </button>
                                 </div>
                             </div>

                             <!-- Cities Cards -->
                             <div class="row g-3">
                                 <?php if ($cities && $cities->num_rows > 0): ?>
                                     <?php while ($city = $cities->fetch_assoc()): ?>
                                         <div class="col-lg-3 col-md-4 col-sm-6" data-city='<?php echo json_encode($city, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                                             <div class="card location-card h-100 position-relative">
                                                 <!-- Full Cover Image -->
                                                 <div class="card-img-overlay d-flex flex-column justify-content-between" style="padding: 0;">
                                                     <?php if (!empty($city['image_url'])): ?>
                                                         <img src="../../uploads/locations/<?php echo htmlspecialchars($city['image_url']); ?>" 
                                                              alt="<?php echo htmlspecialchars($city['name']); ?>" 
                                                              class="w-100 h-100 position-absolute" 
                                                              style="object-fit: cover; z-index: 1;">
                                                     <?php else: ?>
                                                         <!-- Default Icon for No Image -->
                                                         <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center" style="background: #f8f9fa; z-index: 1;">
                                                             <i class="fa-solid fa-city text-dark" style="font-size: 4rem;"></i>
                                                         </div>
                                                     <?php endif; ?>
                                                    
                                                     <!-- Action Buttons Overlay -->
                                                     <div class="position-absolute top-0 end-0 p-3" style="z-index: 2;">
                                                         <div class="btn-group" role="group">
                                                             <button class="btn btn-light btn-sm btn-edit-city" 
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#editCityModal" 
                                                                     title="Edit City"
                                                                     style="border-radius: 50%; width: 36px; height: 36px; padding: 0; backdrop-filter: blur(10px); background: rgba(255,255,255,0.9);">
                                                                 <i class="fas fa-edit"></i>
                                                             </button>
                                                             <button class="btn btn-light btn-sm btn-delete-city" 
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#deleteCityModal" 
                                                                     title="Delete City"
                                                                     style="border-radius: 50%; width: 36px; height: 36px; padding: 0; backdrop-filter: blur(10px); background: rgba(255,255,255,0.9);">
                                                                 <i class="fas fa-trash"></i>
                                                             </button>
                                                         </div>
                                                     </div>
                                                    
                                                     <!-- Content Overlay -->
                                                     <div class="p-3 text-white d-flex flex-column justify-content-end" style="z-index: 1; height: 100%;">
                                                         <div class="mt-auto">
                                                             <h5 class="card-title mb-2 fw-bold text-white">
                                                                 <a href="towns/index.php?city_id=<?php echo $city['id']; ?>" 
                                                                    class="text-decoration-none text-white" 
                                                                    title="View towns in this city">
                                                                     <?php echo htmlspecialchars($city['name']); ?>
                                                                 </a>
                                                             </h5>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     <?php endwhile; ?>
                                 <?php else: ?>
                                     <div class="col-12">
                                         <div class="text-center py-5 text-muted">
                                             <i class="fa-solid fa-city fa-3x mb-3"></i>
                                             <h5>No cities found</h5>
                                             <p>Add your first city to get started</p>
                                         </div>
                                     </div>
                                 <?php endif; ?>
                             </div>

                             <?php if ($cityTotalPages > 1): ?>
                             <nav aria-label="Cities pagination">
                                 <ul class="pagination justify-content-center">
                                     <?php for ($i = 1; $i <= $cityTotalPages; $i++): ?>
                                     <li class="page-item <?php echo $i === $cityPage ? 'active' : ''; ?>">
                                         <a class="page-link" href="?city_page=<?php echo $i; ?>&city_search=<?php echo urlencode($citySearch); ?>"><?php echo $i; ?></a>
                                     </li>
                                     <?php endfor; ?>
                                 </ul>
                             </nav>
                             <?php endif; ?>
                         </div>
                     </div>
                 </div>

                 <!-- Towns Tab -->
                 <div class="tab-pane fade" id="towns" role="tabpanel">
                     <div class="card">
                         <div class="card-body">
                             <!-- Search and Filter -->
                             <div class="toolbar mb-5 p-3 bg-light rounded border">
                                 <div class="d-flex align-items-center gap-2">
                                     <form class="d-flex flex-grow-1" method="get">
                                         <div class="input-group">
                                             <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                                             <input type="text" class="form-control" name="town_search" value="<?php echo htmlspecialchars($townSearch); ?>" placeholder="Search towns by name" id="townSearchInput">
                                         </div>
                                         <button class="btn btn-primary ms-2" type="submit">Search</button>
                                         <a class="btn btn-outline-secondary ms-2" href="index.php">Reset</a>
                                     </form>
                                     <button class="btn-animated-add noselect btn-sm" data-bs-toggle="modal" data-bs-target="#addTownModal">
                                         <span class="text">Add Town</span>
                                         <span class="icon">
                                             <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"></svg>
                                             <span class="buttonSpan">+</span>
                                         </span>
                                     </button>
                                 </div>
                             </div>

                             <!-- Towns Cards -->
                             <div class="row g-3">
                                 <?php if ($towns && $towns->num_rows > 0): ?>
                                     <?php while ($town = $towns->fetch_assoc()): ?>
                                         <div class="col-lg-3 col-md-4 col-sm-6" data-town='<?php echo json_encode($town, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                                             <div class="card location-card h-100 position-relative">
                                                 <!-- Full Cover Image -->
                                                 <div class="card-img-overlay d-flex flex-column justify-content-between" style="padding: 0;">
                                                     <?php if (!empty($town['image_url'])): ?>
                                                         <img src="../../uploads/locations/<?php echo htmlspecialchars($town['image_url']); ?>" 
                                                              alt="<?php echo htmlspecialchars($town['name']); ?>" 
                                                              class="w-100 h-100 position-absolute" 
                                                              style="object-fit: cover; z-index: 1;">
                                                     <?php else: ?>
                                                         <!-- Default Icon for No Image -->
                                                         <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center" style="background: #f8f9fa; z-index: 1;">
                                                             <i class="fa-solid fa-map-marker-alt text-dark" style="font-size: 4rem;"></i>
                                                         </div>
                                                     <?php endif; ?>
                                                    
                                                     <!-- Action Buttons Overlay -->
                                                     <div class="position-absolute top-0 end-0 p-3" style="z-index: 2;">
                                                         <div class="btn-group" role="group">
                                                             <button class="btn btn-light btn-sm btn-edit-town" 
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#editTownModal" 
                                                                     title="Edit Town"
                                                                     style="border-radius: 50%; width: 36px; height: 36px; padding: 0; backdrop-filter: blur(10px); background: rgba(255,255,255,0.9);">
                                                                 <i class="fas fa-edit"></i>
                                                             </button>
                                                             <button class="btn btn-light btn-sm btn-delete-town" 
                                                                     data-bs-toggle="modal" 
                                                                     data-bs-target="#deleteTownModal" 
                                                                     title="Delete Town"
                                                                     style="border-radius: 50%; width: 36px; height: 36px; padding: 0; backdrop-filter: blur(10px); background: rgba(255,255,255,0.9);">
                                                                 <i class="fas fa-trash"></i>
                                                             </button>
                                                         </div>
                                                     </div>
                                                    
                                                     <!-- Content Overlay -->
                                                     <div class="p-3 text-white d-flex flex-column justify-content-end" style="z-index: 1; height: 100%;">
                                                         <div class="mt-auto">
                                                             <h5 class="card-title mb-2 fw-bold text-white">
                                                                 <span class="text-white">
                                                                     <?php echo htmlspecialchars($town['name']); ?>
                                                                 </span>
                                                             </h5>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     <?php endwhile; ?>
                                 <?php else: ?>
                                     <div class="col-12">
                                         <div class="text-center py-5 text-muted">
                                             <i class="fa-solid fa-map-marker-alt fa-3x mb-3"></i>
                                             <h5>No towns found</h5>
                                             <p>Add your first town to get started</p>
                                         </div>
                                     </div>
                                 <?php endif; ?>
                             </div>

                             <?php if ($townTotalPages > 1): ?>
                             <nav aria-label="Towns pagination">
                                 <ul class="pagination justify-content-center">
                                     <?php for ($i = 1; $i <= $townTotalPages; $i++): ?>
                                     <li class="page-item <?php echo $i === $townPage ? 'active' : ''; ?>">
                                         <a class="page-link" href="?town_page=<?php echo $i; ?>&town_search=<?php echo urlencode($townSearch); ?>"><?php echo $i; ?></a>
                                     </li>
                                     <?php endfor; ?>
                                 </ul>
                             </nav>
                             <?php endif; ?>
                         </div>
                     </div>
                 </div>
             </div>

        </div>
    </div>

    <!-- Add State Modal -->
    <div class="modal fade" id="addStateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_state">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">State Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">State Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload an image for this state (JPG, PNG, GIF, WebP - Max 5MB)</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Add State</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit State Modal -->
    <div class="modal fade" id="editStateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_state">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="current_image" id="edit_current_image">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">State Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">State Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload a new image to replace the current one (JPG, PNG, GIF, WebP - Max 5MB)</div>
                                <div id="current_image_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Update State</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete State Modal -->
    <div class="modal fade" id="deleteStateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_state">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Are you sure you want to delete this state? This action cannot be undone.</p>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This will also delete all associated districts, cities, and towns.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-trash me-1"></i>Delete State
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add District Modal -->
    <div class="modal fade" id="addDistrictModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New District</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_district">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">District Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select class="form-control" name="state_id" required>
                                    <option value="">Select State</option>
                                    <?php foreach($allStates as $state): ?>
                                    <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">District Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload an image for this district (JPG, PNG, GIF, WebP - Max 5MB)</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Add District</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit District Modal -->
    <div class="modal fade" id="editDistrictModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit District</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_district">
                        <input type="hidden" name="id" id="edit_district_id">
                        <input type="hidden" name="current_image" id="edit_district_current_image">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">District Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_district_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select class="form-control" name="state_id" id="edit_district_state_id" required>
                                    <option value="">Select State</option>
                                    <?php foreach($allStates as $state): ?>
                                    <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">District Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload a new image to replace the current one (JPG, PNG, GIF, WebP - Max 5MB)</div>
                                <div id="current_district_image_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Update District</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete District Modal -->
    <div class="modal fade" id="deleteDistrictModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete District</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_district">
                        <input type="hidden" name="id" id="delete_district_id">
                        <p>Are you sure you want to delete this district? This action cannot be undone.</p>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This will also delete all associated cities and towns.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-trash me-1"></i>Delete District
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add City Modal -->
    <div class="modal fade" id="addCityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_city">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">City Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">District <span class="text-danger">*</span></label>
                                <select class="form-control" name="district_id" required>
                                    <option value="">Select District</option>
                                    <?php foreach($allDistricts as $district): ?>
                                    <option value="<?php echo $district['id']; ?>"><?php echo htmlspecialchars($district['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">City Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload an image for this city (JPG, PNG, GIF, WebP - Max 5MB)</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Add City</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit City Modal -->
    <div class="modal fade" id="editCityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_city">
                        <input type="hidden" name="id" id="edit_city_id">
                        <input type="hidden" name="current_image" id="edit_city_current_image">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">City Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_city_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">District <span class="text-danger">*</span></label>
                                <select class="form-control" name="district_id" id="edit_city_district_id" required>
                                    <option value="">Select District</option>
                                    <?php foreach($allDistricts as $district): ?>
                                    <option value="<?php echo $district['id']; ?>"><?php echo htmlspecialchars($district['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">City Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload a new image to replace the current one (JPG, PNG, GIF, WebP - Max 5MB)</div>
                                <div id="current_city_image_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Update City</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete City Modal -->
    <div class="modal fade" id="deleteCityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_city">
                        <input type="hidden" name="id" id="delete_city_id">
                        <p>Are you sure you want to delete this city? This action cannot be undone.</p>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This will also delete all associated towns.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-trash me-1"></i>Delete City
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Town Modal -->
    <div class="modal fade" id="addTownModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Town</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_town">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Town Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select class="form-control" name="city_id" required>
                                    <option value="">Select City</option>
                                    <?php foreach($allCities as $city): ?>
                                    <option value="<?php echo $city['id']; ?>"><?php echo htmlspecialchars($city['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Town Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload an image for this town (JPG, PNG, GIF, WebP - Max 5MB)</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Add Town</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Town Modal -->
    <div class="modal fade" id="editTownModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Town</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_town">
                        <input type="hidden" name="id" id="edit_town_id">
                        <input type="hidden" name="current_image" id="edit_town_current_image">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Town Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_town_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select class="form-control" name="city_id" id="edit_town_city_id" required>
                                    <option value="">Select City</option>
                                    <?php foreach($allCities as $city): ?>
                                    <option value="<?php echo $city['id']; ?>"><?php echo htmlspecialchars($city['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Town Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">Upload a new image to replace the current one (JPG, PNG, GIF, WebP - Max 5MB)</div>
                                <div id="current_town_image_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-animated-confirm noselect">
                            <span class="text">Update Town</span>
                            <span class="icon">
                                <svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Town Modal -->
    <div class="modal fade" id="deleteTownModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Town</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_town">
                        <input type="hidden" name="id" id="delete_town_id">
                        <p>Are you sure you want to delete this town? This action cannot be undone.</p>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This will also delete all associated properties.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-trash me-1"></i>Delete Town
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
     <script>

         function searchStates() {
             const searchValue = document.getElementById('searchInput').value;
             window.location.href = '?search=' + encodeURIComponent(searchValue);
         }


         function searchDistricts() {
             const searchValue = document.getElementById('districtSearchInput').value;
             window.location.href = '?district_search=' + encodeURIComponent(searchValue);
         }


         function searchCities() {
             const searchValue = document.getElementById('citySearchInput').value;
             window.location.href = '?city_search=' + encodeURIComponent(searchValue);
         }


         function searchTowns() {
             const searchValue = document.getElementById('townSearchInput').value;
             window.location.href = '?town_search=' + encodeURIComponent(searchValue);
         }






         // Initialize Bootstrap tabs
         document.addEventListener('DOMContentLoaded', function() {
             // Initialize Bootstrap tabs
             var triggerTabList = [].slice.call(document.querySelectorAll('#locationTabs button'));
             triggerTabList.forEach(function (triggerEl) {
                 var tabTrigger = new bootstrap.Tab(triggerEl);
                 triggerEl.addEventListener('click', function (event) {
                     event.preventDefault();
                     tabTrigger.show();
                 });
             });

             // Handle edit state modal
             document.querySelectorAll('.btn-edit-state').forEach(button => {
                 button.addEventListener('click', function() {
                     const row = this.closest('tr');
                     const stateData = JSON.parse(row.getAttribute('data-state'));
                     
                     document.getElementById('edit_id').value = stateData.id;
                     document.getElementById('edit_name').value = stateData.name;
                     document.getElementById('edit_current_image').value = stateData.image_url || '';
                     
                     // Show current image if exists
                     const preview = document.getElementById('current_image_preview');
                     if (stateData.image_url) {
                         preview.innerHTML = '<img src="../../uploads/locations/' + stateData.image_url + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;" alt="Current Image">';
                     } else {
                         preview.innerHTML = '<div class="text-muted">No current image</div>';
                     }
                 });
             });

             // Handle delete state modal
             document.querySelectorAll('.btn-delete-state').forEach(button => {
                 button.addEventListener('click', function() {
                     const row = this.closest('tr');
                     const stateData = JSON.parse(row.getAttribute('data-state'));
                     document.getElementById('delete_id').value = stateData.id;
                 });
             });

             // Handle search input enter key
             document.getElementById('searchInput').addEventListener('keypress', function(e) {
                 if (e.key === 'Enter') {
                     searchStates();
                 }
             });

             // Handle district edit modal
             document.querySelectorAll('.btn-edit-district').forEach(button => {
                 button.addEventListener('click', function() {
                     const row = this.closest('tr');
                     const districtData = JSON.parse(row.getAttribute('data-district'));
                     
                     document.getElementById('edit_district_id').value = districtData.id;
                     document.getElementById('edit_district_name').value = districtData.name;
                     document.getElementById('edit_district_state_id').value = districtData.state_id;
                     document.getElementById('edit_district_current_image').value = districtData.image_url || '';
                     
                     // Show current image if exists
                     const preview = document.getElementById('current_district_image_preview');
                     if (districtData.image_url) {
                         preview.innerHTML = '<img src="../../uploads/locations/' + districtData.image_url + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;" alt="Current Image">';
                     } else {
                         preview.innerHTML = '<div class="text-muted">No current image</div>';
                     }
                 });
             });

             // Handle district delete modal
             document.querySelectorAll('.btn-delete-district').forEach(button => {
                 button.addEventListener('click', function() {
                     const row = this.closest('tr');
                     const districtData = JSON.parse(row.getAttribute('data-district'));
                     document.getElementById('delete_district_id').value = districtData.id;
                 });
             });

             // Handle city edit modal
             document.querySelectorAll('.btn-edit-city').forEach(button => {
                 button.addEventListener('click', function() {
                     const row = this.closest('tr');
                     const cityData = JSON.parse(row.getAttribute('data-city'));
                     
                     document.getElementById('edit_city_id').value = cityData.id;
                     document.getElementById('edit_city_name').value = cityData.name;
                     document.getElementById('edit_city_district_id').value = cityData.district_id;
                     document.getElementById('edit_city_current_image').value = cityData.image_url || '';
                     
                     // Show current image if exists
                     const preview = document.getElementById('current_city_image_preview');
                     if (cityData.image_url) {
                         preview.innerHTML = '<img src="../../uploads/locations/' + cityData.image_url + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;" alt="Current Image">';
                     } else {
                         preview.innerHTML = '<div class="text-muted">No current image</div>';
                     }
                 });
             });

             // Handle city delete modal
             document.querySelectorAll('.btn-delete-city').forEach(button => {
                 button.addEventListener('click', function() {
                     const row = this.closest('tr');
                     const cityData = JSON.parse(row.getAttribute('data-city'));
                     document.getElementById('delete_city_id').value = cityData.id;
                 });
             });

             // Handle town edit modal
             document.querySelectorAll('.btn-edit-town').forEach(button => {
                 button.addEventListener('click', function() {
                     const row = this.closest('tr');
                     const townData = JSON.parse(row.getAttribute('data-town'));
                     
                     document.getElementById('edit_town_id').value = townData.id;
                     document.getElementById('edit_town_name').value = townData.name;
                     document.getElementById('edit_town_city_id').value = townData.city_id;
                     document.getElementById('edit_town_current_image').value = townData.image_url || '';
                     
                     // Show current image if exists
                     const preview = document.getElementById('current_town_image_preview');
                     if (townData.image_url) {
                         preview.innerHTML = '<img src="../../uploads/locations/' + townData.image_url + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;" alt="Current Image">';
                     } else {
                         preview.innerHTML = '<div class="text-muted">No current image</div>';
                     }
                 });
             });

             // Handle town delete modal
             document.querySelectorAll('.btn-delete-town').forEach(button => {
                 button.addEventListener('click', function() {
                     const row = this.closest('tr');
                     const townData = JSON.parse(row.getAttribute('data-town'));
                     document.getElementById('delete_town_id').value = townData.id;
                 });
             });

             // Handle search input enter keys for all tabs
             document.getElementById('districtSearchInput').addEventListener('keypress', function(e) {
                 if (e.key === 'Enter') {
                     searchDistricts();
                 }
             });

             document.getElementById('citySearchInput').addEventListener('keypress', function(e) {
                 if (e.key === 'Enter') {
                     searchCities();
                 }
             });

             document.getElementById('townSearchInput').addEventListener('keypress', function(e) {
                 if (e.key === 'Enter') {
                     searchTowns();
                 }
             });
         });
     </script>
</body>
</html>