<?php
require_once __DIR__ . '/config.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Here you would typically save to database or send email
        // For now, we'll just show success message
        $success = 'Thank you for contacting us! We will get back to you within 24 hours.';
        
        // Clear form data on success
        $name = $email = $phone = $subject = $message = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="contact.css">
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

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <p>We're here to help! Get in touch with our support team</p>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            
            <!-- Contact Information -->
            <div class="contact-info">
                <h2>Get In Touch</h2>
                <p>
                    Have questions or need assistance? Our team is ready to help you. 
                    Reach out to us through any of the following channels.
                </p>

                <div class="info-cards">
                    <!-- Phone -->
                    <div class="info-card">
                        <div class="info-icon">📞</div>
                        <div class="info-content">
                            <h3>Phone</h3>
                            <p><a href="tel:+60123456789">+60 12-345 6789</a></p>
                            <p style="font-size: 0.9rem; margin-top: 0.3rem;">Mon-Fri: 9:00 AM - 6:00 PM</p>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="info-card">
                        <div class="info-icon">✉️</div>
                        <div class="info-content">
                            <h3>Email</h3>
                            <p><a href="mailto:support@careplus.com">support@careplus.com</a></p>
                            <p style="font-size: 0.9rem; margin-top: 0.3rem;">We'll respond within 24 hours</p>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="info-card">
                        <div class="info-icon">📍</div>
                        <div class="info-content">
                            <h3>Location</h3>
                            <p>No. 227, Jalan Raja Permaisuri Bainun<br>30250 Ipoh, Perak<br>Malaysia</p>
                        </div>
                    </div>

                    <!-- Hours -->
                    <div class="info-card">
                        <div class="info-icon">🕒</div>
                        <div class="info-content">
                            <h3>Office Hours</h3>
                            <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                            Saturday: 9:00 AM - 1:00 PM<br>
                            Sunday: Closed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form">
                <h2>Send Us a Message</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success">✓ <?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">✗ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="" id="contactForm">
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($name ?? ''); ?>"
                               placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="your.email@example.com">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                               placeholder="+60 12-345 6789">
                        <small>Optional - for faster response</small>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject <span class="required">*</span></label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="general" <?php echo ($subject ?? '') === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="appointment" <?php echo ($subject ?? '') === 'appointment' ? 'selected' : ''; ?>>Appointment Help</option>
                            <option value="technical" <?php echo ($subject ?? '') === 'technical' ? 'selected' : ''; ?>>Technical Support</option>
                            <option value="billing" <?php echo ($subject ?? '') === 'billing' ? 'selected' : ''; ?>>Billing Question</option>
                            <option value="feedback" <?php echo ($subject ?? '') === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                            <option value="other" <?php echo ($subject ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" name="message" required 
                                  placeholder="Tell us how we can help you..."><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>

        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="container">
        <h2 style="font-size: 2.5rem; color: #1e293b; text-align: center; margin-bottom: 2rem; font-weight: 800;">Find Us</h2>
        <div class="map-container">
            <div class="map-placeholder">
                🗺️
                <!-- You can replace this with an actual Google Maps embed -->
                <!-- <iframe src="YOUR_GOOGLE_MAPS_EMBED_URL" width="100%" height="400" style="border:0; border-radius: 15px;" allowfullscreen="" loading="lazy"></iframe> -->
            </div>
            <p style="text-align: center; color: #64748b; margin-top: 1.5rem;">
                No. 227, Jalan Raja Permaisuri Bainun, 30250 Ipoh, Perak, Malaysia
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

<script src="contact.js"></script>
</body>
</html>