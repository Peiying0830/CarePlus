<?php 
require_once __DIR__ . '/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $userType = getUserType();
    if ($userType === 'doctor') {
        redirect('doctor/dashboard.php');
    } elseif ($userType === 'patient') {
        redirect('patient/dashboard.php');
    } elseif ($userType === 'admin') {
        redirect('admin/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Smart Clinic Management</title>

    <!-- External CSS -->
    <link rel="stylesheet" href="index.css">
    
    <!-- GUEST HEADER -->
    <?php include __DIR__ . '/headerNav.php'; ?>
</head>
<body>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1>CarePlus - Smart Clinic Management Portal</h1><br>
                <p> 🌼 Welcome to <b>CarePlus</b>! ✨ <br>
                Book appointments, check symptoms, and manage your health easily.</p>

                <div class="hero-buttons">
                    <a href="registration.php" class="btn btn-primary"> 📅 Book Appointment</a>
                    <a href="login.php" class="btn btn-secondary">🤖 Symptom Checker</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Key Features</h2>
            <p class="section-subtitle">Everything you need for modern healthcare</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3 class="feature-title">Online Booking</h3>
                <p class="feature-text">Instant appointment scheduling</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3 class="feature-title">AI Symptom Checker</h3>
                <p class="feature-text">Smart AI-powered assessment</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3 class="feature-title">24/7 Chatbot</h3>
                <p class="feature-text">Ask anything, anytime</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3 class="feature-title">Digital Records</h3>
                <p class="feature-text">View your health records anytime</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💳</div>
                <h3 class="feature-title">Online Payment</h3>
                <p class="feature-text">Secure and instant payments</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <h3 class="feature-title">Reminders</h3>
                <p class="feature-text">Never miss an appointment</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">Ready to get started?</h2>
            <p class="cta-text">Join thousands of users using CarePlus today.</p>
            <div class="hero-buttons" style="justify-content: center;">
                <a href="registration.php?type=patient" class="btn btn-primary">👤 Register as Patient</a>
                <a href="registration.php?type=doctor" class="btn btn-secondary">👨‍⚕️ Register as Doctor</a>
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
                <p>Your smart clinic management system.</p>
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
                <h3>Legal</h3>
                <ul>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms</a></li>
                    <li><a href="security.php">Security</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> CarePlus. All rights reserved.</p>
        </div>
    </footer>

    <!-- External JS (optional - for additional index page features) -->
    <script src="index.js"></script>
</body>
</html>