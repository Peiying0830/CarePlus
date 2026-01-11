<?php

/* Session Settings */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 60 * 60 * 8, // 8 hours
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

/* Timezones */
date_default_timezone_set('Asia/Kuala_Lumpur');

/* Site Config */
define('SITE_URL', 'http://localhost/clinic_management');
define('SITE_NAME', 'CarePlus - Smart Clinic Management Portal');

/* Database Config */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinic_management');

/* File Upload Config */
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('QR_CODE_DIR', __DIR__ . '/qrcodes/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB

/* MySQL Database Connection */
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            error_log("DB Error: " . $this->conn->connect_error);
            die("❌ Database connection failed.");
        }
        
        $this->conn->set_charset("utf8mb4");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql, $params = []) {
        if (empty($params)) {
            return $this->conn->query($sql);
        }
        
        // Prepared statement for parameterized queries
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $types = $this->getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    private function getParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
}

/* Auth Helper */
function isLoggedIn() {
    return isset($_SESSION['user_id'], $_SESSION['user_type']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/login.php");
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if (getUserType() !== $role) {
        header("Location: " . SITE_URL . "/unauthorized.php");
        exit;
    }
}

/* Login/ Session Helper */
function loginUser($userId, $userType) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_type'] = $userType;
}

function logoutUser() {
    session_unset();
    session_destroy();
    header("Location: " . SITE_URL . "/login.php");
    exit;
}

/* Utilities */
function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}

function logError($message) {
    error_log(
        date('[Y-m-d H:i:s] ') . $message . PHP_EOL,
        3,
        __DIR__ . '/logs/error.log'
    );
}

function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd M Y H:i') {
    return date($format, strtotime($datetime));
}

function formatTime($time, $format = 'h:i A') {
    return date($format, strtotime($time));
}

function formatCurrency($amount) {
    return "RM " . number_format($amount, 2);
}

/* Symptom Checker log */
function logActivity($userId, $action, $description) {
    $conn = Database::getInstance()->getConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Store in Database
    try {
        $stmt = $conn->prepare("INSERT INTO symptom_checker_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $action, $description, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Activity DB Log Error: " . $e->getMessage());
    }

    // Store in physical .log file
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/symptomChecker.log';
    
    // Ensure log file exists
    if (!file_exists($logFile)) {
        touch($logFile);
    }
    
    $logEntry = date('Y-m-d H:i:s') . " USERID: $userId ACTION: $action DESC: $description IP: $ip" . PHP_EOL;
    $result = @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    if ($result === false) {
        $error = error_get_last();
        error_log("Log write FAILED: $logFile - " . ($error['message'] ?? 'Unknown error'));
    }
}

/* Prescription Activity Logger */
function logPrescriptionActivity($userId, $action, $description) {
    $conn = Database::getInstance()->getConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Store in Database
    try {
        $stmt = $conn->prepare("INSERT INTO prescription_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $action, $description, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Prescription Activity DB Log Error: " . $e->getMessage());
    }

    //  Store in physical .log file
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/prescriptions.log';
    
    // Ensure log file exists
    if (!file_exists($logFile)) {
        touch($logFile);
    }
    
    $logEntry = date('Y-m-d H:i:s') . " [PRESCRIPTION] USER_ID: $userId | ACTION: $action | DESC: $description | IP: $ip" . PHP_EOL;
    $result = @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    if ($result === false) {
        $error = error_get_last();
        error_log("Prescription log write FAILED: $logFile - " . ($error['message'] ?? 'Unknown error'));
    }
}

/* Chatbot Activity Logger */
function logChatbotActivity($userId, $action, $description, $severity = 'info') {
    $conn = Database::getInstance()->getConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/chatbot.log';
    
    if (!file_exists($logFile)) {
        touch($logFile);
    }
    
    $severityTag = strtoupper($severity);
    $logEntry = date('Y-m-d H:i:s') . " [$severityTag] USER_ID: $userId | ACTION: $action | DESC: $description | IP: $ip" . PHP_EOL;
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/* Create Required Direction*/
$directories = [
    __DIR__ . '/uploads',
    __DIR__ . '/qrcodes',
    __DIR__ . '/logs',
    __DIR__ . '/receipts'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

/* Debug Mode */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* Global MySQLi Connection */
$conn = Database::getInstance()->getConnection();

?>