<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security - <?php echo SITE_NAME; ?></title>

    <!-- LINK CSS -->
    <link rel="stylesheet" href="assets/security.css">
</head>

<body>

<!-- Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <img src="images/logo.png" class="logo-img">
            <div class="logo-text">
                <div class="logo-title">CarePlus - Smart Clinic Management Portal</div>
            </div>
        </div>
        <a href="index.php" class="back-link">← Back to Home</a>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">🔒 Security Center</h1>
            <p class="page-subtitle">Enterprise-grade security protecting your healthcare data 24/7</p>
        </div>
    </div>
</section>

<!-- Continue the rest of your HTML content here (same as before)... -->

<!-- Link JavaScript -->
<script src="security.js"></script>
</body>
</html>
