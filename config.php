<?php

/* ===== SESSION SETTINGS ===== */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 60 * 60 * 8, // 8 hours
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

/* ===== TIMEZONE ===== */
date_default_timezone_set('Asia/Kuala_Lumpur');

/* ===== SITE CONFIG ===== */
define('SITE_URL', 'http://localhost/clinic_management');
define('SITE_NAME', 'CarePlus - Smart Clinic Management Portal');
define('SITE_EMAIL', 'noreply@careplus.com'); // Email for system notifications

/* ===== DATABASE CONFIG ===== */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinic_management');

/* ===== API KEYS ===== */
define('GEMINI_API_KEY', 'AIzaSyAfuPhHnwRphOp5Cy7NOum-xNn2pRKw6QU');
define('STRIPE_PUBLIC_KEY', 'your_stripe_public_key_here');
define('BREVO_API_KEY', 'your_brevo_api_key_here');

/* ===== FILE UPLOAD CONFIG ===== */
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('QR_CODE_DIR', __DIR__ . '/qrcodes/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB

/* ===== PDO DATABASE CONNECTION ===== */
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES    => false
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            die("❌ Database connection failed.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

/* ===== AUTH HELPERS ===== */
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

/* ===== LOGIN / SESSION HELPERS ===== */
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

/* ===== UTILITIES ===== */
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

/* ===== CREATE REQUIRED DIRECTORIES ===== */
$directories = [
    __DIR__ . '/uploads',
    __DIR__ . '/qrcodes',
    __DIR__ . '/logs',
    __DIR__ . '/receipts'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

/* ===== DEBUG MODE ===== */
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>