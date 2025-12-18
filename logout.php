<?php
session_start();
session_unset();
session_destroy();

// Clear any cookies if you're using them
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}
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
            <img src="patient/logo.png" alt="CarePlus Logo" class="logo" onerror="this.style.display='none'">
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

        <p class="redirect-text">Redirecting to login page...</p>

        <div class="button-container" style="display: none;" id="manualRedirect">
            <a href="index.php" class="btn">
                <span>🏠</span>
                <span>Return to Login</span>
            </a>
        </div>
    </div>

    <script src="logout.js"></script>
</body>
</html>