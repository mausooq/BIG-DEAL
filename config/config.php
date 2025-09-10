<?php

// Database configuration (Hostinger remote host so it works locally and on server)
define('DB_HOST', 'auth-db672.hstgr.io');
define('DB_PORT', '3306');
define('DB_NAME', 'u232955123_bigdeal');
define('DB_USER', 'u232955123_bigdeal');
define('DB_PASS', 'Brandweave@24');


function getMysqliConnection() {
    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($mysqli->connect_error) {
            throw new Exception("Connection failed: " . $mysqli->connect_error);
        }
        
        // Set charset
        $mysqli->set_charset("utf8mb4");
        
        return $mysqli;
    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Test connection function
function testDatabaseConnection() {
    try {
        $mysqli = getMysqliConnection();
        echo "Database connection successful!";
        return true;
    } catch (Exception $e) {
        echo "Database connection failed: " . $e->getMessage();
        return false;
    }
}


// testDatabaseConnection();
?>
