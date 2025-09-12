<?php

// Central database configuration (Hostinger remote host so it works locally and on server)
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'u232955123_bigdeal');
define('DB_USER', 'u232955123_bigdeal');
define('DB_PASS', 'Brandweave@24');

class Database
{
    public $host = DB_HOST;
    public $db_name = DB_NAME;
    public $username = DB_USER;
    public $password = DB_PASS;
    public $conn;

    // Optional base URL (adjust if you need it elsewhere)
    public static $baseUrl = "/";

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";port=" . DB_PORT . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('PDO connection failed: ' . $e->getMessage());
            // You can redirect to a friendly page if you want
            // header('Location: ' . self::$baseUrl . 'error/database-error/');
        }

        return $this->conn;
    }
}

// Backward compatibility for existing MySQLi callers
function getMysqliConnection() {
    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if ($mysqli->connect_error) {
            throw new Exception('Connection failed: ' . $mysqli->connect_error);
        }

        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    } catch (Exception $e) {
        error_log('MySQLi connection failed: ' . $e->getMessage());
        die('Database connection failed. Please try again later.');
    }
}

// Optional manual CLI test:
// php -r "require 'config/config.php'; (new Database())->getConnection() ? print('OK') : print('FAIL');"
?>
