<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="terms.css">
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
            <h1 class="page-title"> Our Services</h1>
            <p class="page-subtitle">Comprehensive healthcare solutions designed for your convenience and wellbeing</p>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">What We Offer</h2>
            <p class="section-subtitle">Innovative healthcare services at your fingertips</p>
        </div>
        
        <div class="services-grid">
            <!-- Online Appointment Booking -->
            <div class="service-card">
                <div class="service-icon">📅</div>
                <div class="service-content">
                    <h3 class="service-title">Appointment Management</h3>
                    <p class="service-text">Streamline your clinic appointments with our intelligent booking system. Patients can schedule, reschedule, and manage appointments 24/7.</p>
                    <ul class="service-features">
                        <li>Online booking anytime, anywhere</li>
                        <li>Automated reminders via email</li>
                        <li>Real-time availability checking</li>
                        <li>Conflict detection system</li>
                        <li>QR code check-in for contactless service</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/appointmentDashboard.php' : 'login.php'; ?>" class="service-cta">Book Appointment</a>
                </div>
            </div>

            <!-- AI Symptom Checker -->
            <div class="service-card">
                <div class="service-icon">🤖</div>
                <div class="service-content">
                    <h3 class="service-title">AI-Powered Symptom Checker</h3>
                    <p class="service-text">Get preliminary health assessments powered by advanced AI technology. Our system helps you understand symptoms before your visit.</p>
                    <ul class="service-features">
                        <li>Intelligent symptom analysis</li>
                        <li>Preliminary diagnosis suggestions</li>
                        <li>Doctor specialty recommendations</li>
                        <li>Instant results and insights</li>
                        <li>Privacy-protected assessments</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/symptomChecker.php' : 'login.php'; ?>" class="service-cta">Try Symptom Checker</a>
                </div>
            </div>

            <!-- Smart Chatbot -->
            <div class="service-card">
                <div class="service-icon">💬</div>
                <div class="service-content">
                    <h3 class="service-title">24/7 Smart Chatbot Assistant</h3>
                    <p class="service-text">Get instant answers to your healthcare questions with our AI-powered chatbot. Available round the clock for your convenience.</p>
                    <ul class="service-features">
                        <li>Available around the clock</li>
                        <li>Multi-language support</li>
                        <li>Appointment booking assistance</li>
                        <li>General health information</li>
                        <li>Clinic hours and location info</li>
                    </ul>
                    <a href="index.php" class="service-cta">Chat Now</a>
                </div>
            </div>

            <!-- Digital Medical Records -->
            <div class="service-card">
                <div class="service-icon">📋</div>
                <div class="service-content">
                    <h3 class="service-title">Digital Medical Records</h3>
                    <p class="service-text">Access your complete medical history securely from anywhere. All records are encrypted and PDPA 2010 compliant.</p>
                    <ul class="service-features">
                        <li>Secure electronic health records</li>
                        <li>Access from any device</li>
                        <li>Prescription history tracking</li>
                        <li>Lab results storage</li>
                        <li>AES-256 encryption protection</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/records.php' : 'login.php'; ?>" class="service-cta">View Records</a>
                </div>
            </div>

            <!-- Online Payment System -->
            <div class="service-card">
                <div class="service-icon">💳</div>
                <div class="service-content">
                    <h3 class="service-title">Secure Online Payments</h3>
                    <p class="service-text">Pay for consultations and services securely online. Multiple payment options for your convenience.</p>
                    <ul class="service-features">
                        <li>TNG eWallet integration</li>
                        <li>DuitNow instant transfer</li>
                        <li>Secure payment gateway</li>
                        <li>Automatic receipt generation</li>
                        <li>Payment history tracking</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/payments.php' : 'login.php'; ?>" class="service-cta">Make Payment</a>
                </div>
            </div>

            <!-- Appointment Reminders -->
            <div class="service-card">
                <div class="service-icon">🔔</div>
                <div class="service-content">
                    <h3 class="service-title">Smart Reminders & Notifications</h3>
                    <p class="service-text">Never miss an appointment with our automated reminder system.</p>
                    <ul class="service-features">
                        <li>Email and SMS notifications</li>
                        <li>Customizable reminder times</li>
                        <li>Prescription refill alerts</li>
                        <li>Follow-up appointment reminders</li>
                        <li>Health tips and updates</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/appointmentDashboard.php' : 'login.php'; ?>" class="service-cta">Manage Reminders</a>
                </div>
            </div>

            <!-- Doctor Management -->
            <div class="service-card">
                <div class="service-icon">👨‍⚕️</div>
                <div class="service-content">
                    <h3 class="service-title">Expert Doctor Consultation</h3>
                    <p class="service-text">Connect with experienced and licensed healthcare professionals across various medical specializations.</p>
                    <ul class="service-features">
                        <li>Licensed medical practitioners</li>
                        <li>Multiple specializations available</li>
                        <li>Detailed doctor profiles</li>
                        <li>Patient reviews and ratings</li>
                        <li>Flexible consultation hours</li>
                    </ul>
                    <a href="doctors.php" class="service-cta">View Doctors</a>
                </div>
            </div>

            <!-- Patient Portal -->
            <div class="service-card">
                <div class="service-icon">👤</div>
                <div class="service-content">
                    <h3 class="service-title">Patient Portal & Profile</h3>
                    <p class="service-text">Personalized healthcare dashboard for managing your health journey.</p>
                    <ul class="service-features">
                        <li>Personal health dashboard</li>
                        <li>Appointment history</li>
                        <li>Medical document storage</li>
                        <li>Health metrics tracking</li>
                        <li>Communication with doctors</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/dashboard.php' : 'login.php'; ?>" class="service-cta">Access Portal</a>
                </div>
            </div>

            <!-- Prescription Management -->
            <div class="service-card">
                <div class="service-icon">💊</div>
                <div class="service-content">
                    <h3 class="service-title">Digital Prescription Management</h3>
                    <p class="service-text">Manage and track your prescriptions digitally with ease and security.</p>
                    <ul class="service-features">
                        <li>Digital prescription storage</li>
                        <li>Medication reminders</li>
                        <li>Refill requests</li>
                        <li>Drug interaction alerts</li>
                        <li>Pharmacy integration</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/prescriptions.php' : 'login.php'; ?>" class="service-cta">View Prescriptions</a>
                </div>
            </div>

            <!-- Report Generation -->
            <div class="service-card">
                <div class="service-icon">📊</div>
                <div class="service-content">
                    <h3 class="service-title">Reports & Analytics</h3>
                    <p class="service-text">Comprehensive health reports and insights for better healthcare decisions.</p>
                    <ul class="service-features">
                        <li>Detailed medical reports</li>
                        <li>Health trend analysis</li>
                        <li>Appointment statistics</li>
                        <li>Treatment progress tracking</li>
                        <li>Exportable documents</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/reports.php' : 'login.php'; ?>" class="service-cta">View Reports</a>
                </div>
            </div>

            <!-- Multi-User Support -->
            <div class="service-card">
                <div class="service-icon">👥</div>
                <div class="service-content">
                    <h3 class="service-title">Multi-User Role Management</h3>
                    <p class="service-text">Secure access for patients, doctors, and administrators with role-based permissions.</p>
                    <ul class="service-features">
                        <li>Patient accounts</li>
                        <li>Doctor profiles</li>
                        <li>Admin dashboard</li>
                        <li>Custom access levels</li>
                        <li>Secure authentication</li>
                    </ul>
                    <a href="registration.php" class="service-cta">Register Now</a>
                </div>
            </div>

            <!-- Mobile Responsive -->
            <div class="service-card">
                <div class="service-icon">📱</div>
                <div class="service-content">
                    <h3 class="service-title">Mobile-Friendly Platform</h3>
                    <p class="service-text">Access all features seamlessly on any device - desktop, tablet, or smartphone.</p>
                    <ul class="service-features">
                        <li>Responsive design</li>
                        <li>Cross-device synchronization</li>
                        <li>Touch-optimized interface</li>
                        <li>Fast loading times</li>
                        <li>Offline access to records</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/dashboard.php' : 'login.php'; ?>" class="service-cta">Get Started</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Our Impact</h2>
        <p style="font-size: 1.2rem; opacity: 0.9;">Transforming healthcare management across Malaysia</p>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">70%</div>
                <div class="stat-label">Paperwork Reduction</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">40%</div>
                <div class="stat-label">Fewer Errors</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50%</div>
                <div class="stat-label">Reduced Wait Time</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">99%</div>
                <div class="stat-label">System Uptime</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">Ready to Experience Better Healthcare?</h2>
            <p class="cta-text">Join thousands of patients using CarePlus for convenient healthcare management</p>
            <div class="cta-buttons">
                <a href="registration.php?type=patient" class="btn btn-primary">Register as Patient</a>
                <a href="contact.php" class="btn btn-secondary">Contact Us</a>
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

<script src="terms.js"></script>
</body>
</html>