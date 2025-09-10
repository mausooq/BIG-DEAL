<?php
// Centralized authentication guard for all admin pages

// Start session if not already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Prevent cached views of protected pages after logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Load config using absolute path relative to this file
require_once __DIR__ . '/../config/config.php';

// Enforce admin session
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    $loginUri = preg_replace('#/admin/.*$#', '/admin/login/', $reqUri);
    if ($loginUri === $reqUri || empty($loginUri)) {
        // Fallback relative path if regex failed
        $loginUri = '../login/';
    }
    header('Location: ' . $loginUri);
    exit();
}

// Optionally, ensure admin is still active (defensive)
if (isset($_SESSION['admin_username'])) {
    try {
        $mysqli = getMysqliConnection();
        $stmt = $mysqli->prepare("SELECT status FROM admin_users WHERE username = ? LIMIT 1");
        $stmt && $stmt->bind_param('s', $_SESSION['admin_username']);
        $stmt && $stmt->execute();
        $res = $stmt ? $stmt->get_result() : null;
        $row = $res ? $res->fetch_assoc() : null;
        $stmt && $stmt->close();
        if ($row && isset($row['status']) && $row['status'] !== 'active') {
            // Invalidate session if suspended
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            $reqUri = $_SERVER['REQUEST_URI'] ?? '';
            $loginUri = preg_replace('#/admin/.*$#', '/admin/login/', $reqUri);
            if ($loginUri === $reqUri || empty($loginUri)) { $loginUri = '../login/'; }
            header('Location: ' . $loginUri);
            exit();
        }
    } catch (Throwable $e) {
        // On DB error, continue but do not block access unnecessarily
    }
}

// You can implement idle timeout here if needed
?>


