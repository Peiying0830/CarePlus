<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="about.css">
    <?php include __DIR__ . '/headerNav.php'; ?>
</head>
<body>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">About CarePlus</h1>
            <p class="hero-subtitle">Transforming healthcare management through innovative technology and compassionate service</p>
        </div>
    </div>
</section>

<!-- Our Story Section -->
<section class="story-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Our Story</h2>
            <p class="section-subtitle">How we started and where we're going</p>
        </div>
        <div class="story-content">
            <p>
                CarePlus was founded with a simple yet powerful vision: to make healthcare management accessible, efficient, and patient-centered. We recognized that traditional clinic management systems were often complex, expensive, and difficult to use for both healthcare providers and patients.
            </p>
            <p>
                In today's fast-paced world, patients deserve the convenience of managing their healthcare needs online. Doctors need streamlined tools to focus on what matters most—providing quality care. Our platform bridges this gap by combining cutting-edge technology with user-friendly design.
            </p>
            <p>
                Since our inception, we've helped thousands of patients access healthcare services more conveniently while enabling healthcare providers to manage their practices more efficiently. We've reduced administrative burdens, minimized appointment no-shows, and improved overall patient satisfaction.
            </p>
            <p>
                Today, CarePlus continues to evolve, incorporating AI-powered features like our symptom checker and intelligent chatbot, while maintaining our commitment to data security and user privacy. We're proud to be at the forefront of healthcare digital transformation in Malaysia.
            </p>
        </div>
    </div>
</section>

<!-- Mission & Vision Section -->
<section class="mission-vision-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Mission & Vision</h2>
        </div>
        <div class="mission-vision-grid">
            <div class="mission-vision-card">
                <div class="card-icon">🎯</div>
                <h3 class="card-title">Our Mission</h3>
                <p class="card-text">
                    To revolutionize healthcare management by providing an intuitive, secure, and comprehensive digital platform that empowers patients to take control of their health journey while enabling healthcare providers to deliver exceptional care efficiently. We strive to eliminate barriers to healthcare access and improve health outcomes through innovative technology.
                </p>
            </div>
            <div class="mission-vision-card">
                <div class="card-icon">🔭</div>
                <h3 class="card-title">Our Vision</h3>
                <p class="card-text">
                    To become Malaysia's leading healthcare management platform, where every patient has seamless access to quality healthcare services and every healthcare provider has the tools they need to thrive. We envision a future where technology and healthcare work harmoniously to create a healthier, more connected community.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Core Values Section -->
<section class="values-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Our Core Values</h2>
            <p class="section-subtitle">The principles that guide everything we do</p>
        </div>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">🔒</div>
                <h3 class="value-title">Privacy & Security</h3>
                <p class="value-text">
                    We prioritize the protection of your personal health information with bank-level encryption and strict compliance with PDPA 2010 regulations.
                </p>
            </div>
            <div class="value-card">
                <div class="value-icon">💡</div>
                <h3 class="value-title">Innovation</h3>
                <p class="value-text">
                    We continuously evolve our platform with cutting-edge technology, from AI-powered features to smart automation that improves healthcare delivery.
                </p>
            </div>
            <div class="value-card">
                <div class="value-icon">❤️</div>
                <h3 class="value-title">Patient-Centered</h3>
                <p class="value-text">
                    Every feature we build is designed with patients in mind, ensuring accessibility, convenience, and an exceptional user experience.
                </p>
            </div>
            <div class="value-card">
                <div class="value-icon">🤝</div>
                <h3 class="value-title">Reliability</h3>
                <p class="value-text">
                    We maintain 99% system uptime and provide dependable service that healthcare providers and patients can trust.
                </p>
            </div>
            <div class="value-card">
                <div class="value-icon">🌟</div>
                <h3 class="value-title">Excellence</h3>
                <p class="value-text">
                    We're committed to delivering the highest quality platform and support, continuously improving based on user feedback.
                </p>
            </div>
            <div class="value-card">
                <div class="value-icon">🌍</div>
                <h3 class="value-title">Accessibility</h3>
                <p class="value-text">
                    Healthcare should be accessible to everyone. We design our platform to be inclusive, user-friendly, and available 24/7.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Our Impact in Numbers</h2>
        <p style="font-size: 1.2rem; opacity: 0.9;">Making a real difference in healthcare management</p>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">10K+</div>
                <div class="stat-label">Active Patients</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">500+</div>
                <div class="stat-label">Healthcare Providers</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50K+</div>
                <div class="stat-label">Appointments Managed</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">99%</div>
                <div class="stat-label">Customer Satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Meet Our Leadership Team</h2>
            <p class="section-subtitle">Experienced professionals dedicated to transforming healthcare</p>
        </div>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-image-container">
                    <div class="team-image">
                        <img src="Dr. Ahmad Rahman.png" alt="Dr. Ahmad Rahman">
                    </div>
                </div>
                <div class="team-info-container">
                    <div class="team-info">
                        <h3 class="team-name">Dr. Ahmad Rahman</h3>
                        <p class="team-role">Chief Executive Officer</p>
                        <p class="team-description">
                            15+ years in healthcare management with a passion for leveraging technology to improve patient outcomes.
                        </p>
                    </div>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image-container">
                    <div class="team-image">
                        <img src="Sarah Lim.png" alt="Sarah Lim">
                    </div>
                </div>
                <div class="team-info-container">
                    <div class="team-info">
                        <h3 class="team-name">Sarah Lim</h3>
                        <p class="team-role">Chief Technology Officer</p>
                        <p class="team-description">
                            Expert in AI and healthcare systems with a track record of building scalable digital health platforms.
                        </p>
                    </div>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image-container">
                    <div class="team-image">
                        <img src="Dr. Priya Kumar.png" alt="Dr. Priya Kumar">
                    </div>
                </div>
                <div class="team-info-container">
                    <div class="team-info">
                        <h3 class="team-name">Dr. Priya Kumar</h3>
                        <p class="team-role">Chief Medical Officer</p>
                        <p class="team-description">
                            Experienced physician ensuring our platform meets the highest clinical and ethical standards.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">Join Us on Our Mission</h2>
            <p class="cta-text">Experience the future of healthcare management today</p>
            <a href="registration.php" class="btn btn-primary">Get Started Now →</a>
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
<script src="about.js"></script>

</body>
</html>