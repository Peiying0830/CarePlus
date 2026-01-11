<?php
session_start();

require_once __DIR__ . '/config.php';

$success = '';
$error = '';

// Initialize form variables
$name = '';
$email = '';
$phone = '';
$subject = '';
$message = '';

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
        // Save to database
        try {
            $conn = Database::getInstance()->getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $stmt = $conn->prepare("
                INSERT INTO contact_submissions (name, email, phone, subject, message, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("ssssss", $name, $email, $phone, $subject, $message, $ip);
            $stmt->execute();
            $stmt->close();
            
            $success = 'Thank you for contacting us! We will get back to you within 24 hours.';

            // Clear form data on success
            $name = $email = $phone = $subject = $message = '';
            
        } catch (Exception $e) {
            error_log("Contact form error: " . $e->getMessage());
            $error = 'Sorry, there was an error submitting your message. Please try again.';
        }
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
    <?php include __DIR__ . '/headerNav.php'; ?>
</head>
<body>

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
                            <p style="font-size: 0.9rem; margin-top: 0.3rem;">Mon-Sat: 9:00 AM - 8:00 PM</p>
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
                            <p>Klinik Careclinics<br>Ipoh, Perak<br>Malaysia</p>
                        </div>
                    </div>

                    <!-- Hours -->
                    <div class="info-card">
                        <div class="info-icon">🕒</div>
                        <div class="info-content">
                            <h3>Office Hours</h3>
                            <p>Monday - Satday: 9:00 AM - 8:00 PM<br>
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
                               value="<?php echo htmlspecialchars($name); ?>"
                               placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($email); ?>"
                               placeholder="your.email@example.com">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo htmlspecialchars($phone); ?>"
                               placeholder="+60 12-345 6789">
                        <small>Optional - for faster response</small>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject <span class="required">*</span></label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="general" <?php echo $subject === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="appointment" <?php echo $subject === 'appointment' ? 'selected' : ''; ?>>Appointment Help</option>
                            <option value="technical" <?php echo $subject === 'technical' ? 'selected' : ''; ?>>Technical Support</option>
                            <option value="billing" <?php echo $subject === 'billing' ? 'selected' : ''; ?>>Billing Question</option>
                            <option value="feedback" <?php echo $subject === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                            <option value="other" <?php echo $subject === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" name="message" required 
                                  placeholder="Tell us how we can help you..."><?php echo htmlspecialchars($message); ?></textarea>
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
            <div class="map-responsive">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31816.902024006842!2d101.0727071743164!3d4.573760500000005!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31caec3401c4be25%3A0x8a57a41e76418dfe!2sKlinik%20Careclinics%20Ipoh!5e0!3m2!1sen!2smy!4v1766746059246!5m2!1sen!2smy" 
                    width="100%" 
                    height="450" 
                    style="border:0; border-radius: 15px;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <p style="text-align: center; color: #64748b; margin-top: 1.5rem; font-size: 1.05rem;">
                📍 Klinik Careclinics, Ipoh, Perak, Malaysia
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

<!-- External JS -->
<script src="contact.js"></script>

</body>
</html>