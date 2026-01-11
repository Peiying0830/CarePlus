<?php
session_start();

require_once __DIR__ . '/config.php';

// Log logout activity before destroying session
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'unknown';
    
    // Optional: Log logout activity in database
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $logMessage = "User logged out - User Type: $userType";
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        logError("Logout: User ID $userId ($userType) from IP $ip");
        
    } catch (Exception $e) {
        logError('Logout logging error: ' . $e->getMessage());
    }
}

// Clear session
session_unset();
session_destroy();

// Clear any cookies if you're using them
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Start new session for flash message (optional)
session_start();
$_SESSION['logout_success'] = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - CarePlus</title>
    <link rel="stylesheet" href="logout.css">
</head>
<body>
    <div class="logout-container">
        <div class="logo-container">
            <img src="logo.png" alt="CarePlus Logo" class="logo" onerror="this.style.display='none'">
        </div>

        <h1>Logging You Out...</h1>
        
        <p class="message">
            Thank you for using CarePlus. Your session has been securely terminated.
        </p>

        <div class="spinner-container">
            <div class="spinner"></div>
        </div>

        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>

        <p class="redirect-text">Redirecting to home page...</p>

        <div class="button-container" style="display: none;" id="manualRedirect">
            <a href="index.php" class="btn">
                <span>🏠</span>
                <span>Return to Home</span>
            </a>
        </div>

        <div class="security-note">
            <span class="security-icon">🔒</span>
            <small>Your data is protected. All sessions have been cleared.</small>
        </div>
    </div>

    <script src="logout.js"></script>
</body>
</html>