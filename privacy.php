<?php
session_start();
require_once __DIR__ . '/config.php';

// Log privacy page views for analytics
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // For now, just log to file
    logError("Privacy page viewed - User ID: " . ($userId ?? 'guest') . " - IP: $ip");
    
} catch (Exception $e) {
    logError('Privacy page logging error: ' . $e->getMessage());
}

// Get user info if logged in (for personalization)
$userName = null;
$userEmail = null;

if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if ($user) {
                $userEmail = $user['email'];
                // Extract first part of email as name
                $userName = explode('@', $userEmail)[0];
            }
        }
    } catch (Exception $e) {
        logError('Error fetching user info: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="privacy.css">
    <?php include __DIR__ . '/headerNav.php'; ?>
    <meta name="description" content="Learn about how CarePlus protects your health data with enterprise-grade security and privacy measures.">
</head>
<body>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">🔒 Security & Privacy</h1>
            <p class="page-subtitle">Your health data is protected with enterprise-grade security</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <p class="personalized-message">
                    Welcome back! Your data is secure and protected 24/7.
                </p>
            <?php endif; ?>
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
                To exercise any of these rights, please contact our Privacy Team at <strong>support@careplus.com</strong> or 
                <?php if (isset($_SESSION['user_id'])): ?>
                    access your privacy settings in your <a href="<?php echo strtolower($_SESSION['user_type']); ?>/dashboard.php" style="color: #3b82f6; text-decoration: underline;">account dashboard</a>.
                <?php else: ?>
                    <a href="login.php" style="color: #3b82f6; text-decoration: underline;">login to access</a> your privacy settings in your account dashboard.
                <?php endif; ?>
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

<!-- Data Breach Notification -->
<section class="content-section">
    <div class="container">
        <div class="content-box">
            <h2 class="content-title">🚨 Data Breach Response</h2>
            <p class="content-text">
                In the unlikely event of a data breach, we will:
            </p>
            <ul class="content-list">
                <li>Notify affected users within 72 hours of discovery</li>
                <li>Provide detailed information about what data was compromised</li>
                <li>Offer free credit monitoring and identity theft protection services</li>
                <li>Work with law enforcement and regulatory authorities</li>
                <li>Conduct a thorough investigation and implement additional safeguards</li>
            </ul>
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
                    <strong>Privacy Team:</strong> <a href="mailto:support@careplus.com">support@careplus.com</a><br>
                    <strong>Security Team:</strong> <a href="mailto:support@careplus.com">support@careplus.com</a><br>
                    <strong>Support:</strong> <a href="mailto:support@careplus.com">support@careplus.com</a><br>
                    <strong>Phone:</strong> +60 12-345 6789 (Mon-Fri, 9AM-8PM MYT)
                </p>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
            <div style="margin-top: 1.5rem; padding: 1rem; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
                <p style="margin: 0; color: #1e3a8a;">
                    <strong>Need immediate help?</strong> You can also contact us through your 
                    <a href="<?php echo strtolower($_SESSION['user_type']); ?>/dashboard.php" style="color: #3b82f6; text-decoration: underline;">dashboard messaging system</a> 
                    for faster response.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Last Updated -->
<section class="content-section">
    <div class="container">
        <div class="content-box" style="text-align: center; padding: 1rem;">
            <p style="color: #64748b; font-size: 0.875rem; margin: 0;">
                Last Updated: <?php echo date('F d, Y'); ?> | 
                Effective Date: January 1, <?php echo date('Y'); ?>
            </p>
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
                    <li><a href="index.php">Home</a></li>
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
            <p>&copy; <?php echo date('Y'); ?> CarePlus - Smart Clinic Management Portal. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="privacy.js"></script>
</body>
</html>