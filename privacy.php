<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="privacy.css">
</head>
<body>

<!-- Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <img src="logo.png" alt="CarePlus Logo" class="logo-img">
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
            <h1 class="page-title">🔒 Security & Privacy</h1>
            <p class="page-subtitle">Your health data is protected with enterprise-grade security</p>
        </div>
    </div>
</section>

<!-- Security Features Grid -->
<section class="security-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Security Features</h2>
            <p class="section-subtitle">Multi-layered protection for your sensitive health information</p>
        </div>
        
        <div class="security-grid" id="security-grid">
            <!-- Cards will be generated dynamically by privacy.js -->
        </div>
    </div>
</section>

<!-- Content Sections -->
<section class="content-section">
    <div class="container">
        <div class="content-box">
            <h2 class="content-title">🔒 How We Protect Your Data</h2>
            <p class="content-text">
                At <?php echo SITE_NAME; ?>, we understand that your health information is among your most sensitive personal data. We've implemented multiple layers of security to ensure your information remains private and secure.
            </p>

            <ul class="feature-list" id="feature-list">
                <!-- Features will be generated dynamically by privacy.js -->
            </ul>
        </div>
    </div>
</section>

<!-- Privacy Rights Section -->
<section class="content-section">
    <div class="container">
        <div class="content-box">
            <h2 class="content-title">👤 Your Privacy Rights</h2>
            <p class="content-text">You have complete control over your health data:</p>
            
            <div class="highlight-box" id="privacy-rights">
                <!-- Privacy rights generated dynamically by privacy.js -->
            </div>

            <p class="content-text">
                To exercise any of these rights, please contact our Privacy Team at <strong>privacy@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com</strong> or access your privacy settings in your account dashboard.
            </p>
        </div>
    </div>
</section>

<!-- Compliance Section -->
<section class="content-section">
    <div class="container">
        <div class="content-box">
            <h2 class="content-title">📋 Compliance & Certifications</h2>
            <p class="content-text">
                <?php echo SITE_NAME; ?> meets or exceeds industry standards for healthcare data protection:
            </p>

            <div class="compliance-badges" id="compliance-badges">
                <!-- Badges generated dynamically by privacy.js -->
            </div>

            <p class="content-text" style="margin-top: 2rem;">
                We undergo regular third-party security audits and penetration testing to ensure our systems remain secure against evolving threats.
            </p>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="content-section">
    <div class="container">
        <div class="content-box">
            <h2 class="content-title">📞 Questions About Privacy?</h2>
            <p class="content-text">
                We're here to address any concerns you may have about your data privacy and security.
            </p>
            <div class="contact-box">
                <p>
                    <strong>Privacy Team:</strong> privacy@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com<br>
                    <strong>Security Team:</strong> security@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com<br>
                    <strong>Support:</strong> support@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About CarePlus</h3>
                <p>Your comprehensive healthcare management platform for modern clinics.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="doctors.php">Our Doctors</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Legal</h3>
                <ul>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                    <li><a href="security.php">Security</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="privacy.js"></script>
</body>
</html>
