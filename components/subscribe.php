<?php
header('Content-Type: application/json; charset=UTF-8');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
  exit;
}

$email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
  exit;
}

// Bootstrap DB connection (reusing config)
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
  if (!function_exists('getMysqliConnection')) {
    $cfg = __DIR__ . '/../config/config.php';
    if (file_exists($cfg)) { require_once $cfg; }
  }
  if (function_exists('getMysqliConnection')) {
    try { $mysqli = getMysqliConnection(); } catch (Throwable $e) { $mysqli = null; }
  }
}

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Database connection error. Please try again.']);
  exit;
}

try {
  $stmt = $mysqli->prepare('INSERT INTO subscribed_email (email) VALUES (?) ON DUPLICATE KEY UPDATE email = email');
  $stmt->bind_param('s', $email);
  if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    $already = ($affected === 0);
    echo json_encode([
      'status' => 'ok',
      'message' => $already ? "You're already subscribed." : 'Thank you for subscribing!'
    ]);
  } else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error subscribing. Please try again.']);
  }
  $stmt->close();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Error subscribing. Please try again.']);
}
exit;
?>


