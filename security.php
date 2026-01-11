<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="security.css">
    <?php include __DIR__ . '/headerNav.php'; ?>
</head>

<body>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">🔒 Security Center</h1>
            <p class="page-subtitle">Enterprise-grade security protecting your healthcare data 24/7</p>
        </div>
    </div>
</section>

<!-- Security Features Section -->
<section class="security-features">
    <div class="container">
        <h2 class="section-title">Our Security Framework</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>End-to-End Encryption</h3>
                <p>All data is encrypted both in transit and at rest using military-grade AES-256 encryption. Your sensitive healthcare information remains protected at all times.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3>Multi-Factor Authentication</h3>
                <p>Advanced MFA protocols ensure that only authorized personnel can access patient records and clinic management systems.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👁️</div>
                <h3>24/7 Monitoring</h3>
                <p>Real-time security monitoring and threat detection systems work around the clock to identify and prevent potential security breaches.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Audit Trails</h3>
                <p>Comprehensive logging of all system activities creates detailed audit trails for compliance and security investigations.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3>Automatic Backups</h3>
                <p>Daily automated backups with geo-redundant storage ensure your data is always safe and recoverable in any situation.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>DDoS Protection</h3>
                <p>Advanced infrastructure with built-in DDoS protection keeps your clinic systems available and responsive at all times.</p>
            </div>
        </div>
    </div>
</section>

<!-- Compliance Section -->
<section class="compliance">
    <div class="container">
        <h2 class="section-title">Compliance & Certifications</h2>
        <div class="compliance-grid">
            <div class="compliance-badge">
                <div class="icon">✅</div>
                <h3>HIPAA Compliant</h3>
                <p>Fully compliant with Health Insurance Portability and Accountability Act standards</p>
            </div>
            <div class="compliance-badge">
                <div class="icon">🌐</div>
                <h3>GDPR Ready</h3>
                <p>Adherence to General Data Protection Regulation for EU data privacy</p>
            </div>
            <div class="compliance-badge">
                <div class="icon">🔒</div>
                <h3>ISO 27001</h3>
                <p>Certified information security management system</p>
            </div>
            <div class="compliance-badge">
                <div class="icon">🏥</div>
                <h3>HL7 FHIR</h3>
                <p>Healthcare interoperability standards for secure data exchange</p>
            </div>
        </div>
    </div>
</section>

<!-- Security Stats -->
<section class="security-stats">
    <div class="container">
        <h2 class="section-title">Security by the Numbers</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">99.99%</div>
                <div class="stat-label">Uptime Guarantee</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">256-bit</div>
                <div class="stat-label">Encryption Standard</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Security Monitoring</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">< 1 min</div>
                <div class="stat-label">Threat Response Time</div>
            </div>
        </div>
    </div>
</section>

<!-- Security Practices -->
<section class="security-practices">
    <div class="container">
        <h2 class="section-title">Our Security Practices</h2>
        <div class="practices-list">
            <div class="practice-item">
                <h3>Regular Security Audits</h3>
                <p>We conduct quarterly third-party security audits and penetration testing to identify and address potential vulnerabilities before they become issues.</p>
            </div>
            <div class="practice-item">
                <h3>Employee Training</h3>
                <p>All staff members undergo mandatory security awareness training and follow strict protocols for handling sensitive healthcare data.</p>
            </div>
            <div class="practice-item">
                <h3>Access Control</h3>
                <p>Role-based access control (RBAC) ensures that users only have access to the information necessary for their specific job functions.</p>
            </div>
            <div class="practice-item">
                <h3>Incident Response</h3>
                <p>Our dedicated security team maintains a comprehensive incident response plan with clear procedures for handling any security events.</p>
            </div>
            <div class="practice-item">
                <h3>Data Privacy</h3>
                <p>We implement privacy by design principles, ensuring that patient data protection is built into every aspect of our system.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="security-cta">
    <div class="container">
        <div class="cta-content">
            <h2>Questions About Our Security?</h2>
            <p>Our security team is here to answer any questions you have about how we protect your data.</p>
            <a href="contact.php" class="cta-button">Contact Security Team</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>CarePlus</h3>
                <p>Smart Clinic Management Portal providing secure, efficient healthcare solutions for modern medical practices.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="doctors.php">Doctors</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Security</h3>
                <ul>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms</a></li>
                    <li><a href="security.php">Security</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> CarePlus. All rights reserved. | Securing healthcare data with excellence.</p>
        </div>
    </div>
</footer>

<!-- Link JavaScript -->
<script src="security.js"></script>
</body>
</html>