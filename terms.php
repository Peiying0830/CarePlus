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
    <?php include __DIR__ . '/headerNav.php'; ?>
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
            <h1 class="page-title">🏥 Our Services</h1>
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
                    <p class="service-text">
                        Manage your clinic appointments easily with our smart scheduling system.
                        Patients can book appointments, track their status, and receive timely reminders.
                    </p>
                    <ul class="service-features">
                        <li>Online appointment booking</li>
                        <li>View appointments in dashboard</li>
                        <li>Payment status tracking</li>
                        <li>QR code for appointment verification</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/appointmentDashboard.php' : 'login.php'; ?>" class="service-cta">Book Appointment</a>
                </div>
            </div>

            <!-- Symptom Checker -->
            <div class="service-card">
                <div class="service-icon">🤖</div>
                <div class="service-content">
                    <h3 class="service-title">Symptom Checker</h3>
                    <p class="service-text">
                        Check your symptoms using our intelligent medical assessment system.
                        Get possible causes, urgency level, and recommended care based on your inputs.
                    </p>
                    <ul class="service-features">
                        <li>Symptom-based health assessment</li>
                        <li>Urgency level detection (Routine / Urgent / Emergency)</li>
                        <li>Doctor specialty recommendations</li>
                        <li>History of previous symptom checks</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/symptomChecker.php' : 'login.php'; ?>" class="service-cta">Try Symptom Checker</a>
                </div>
            </div>

            <!-- Smart Chatbot -->
            <div class="service-card">
                <div class="service-icon">💬</div>
                <div class="service-content">
                    <h3 class="service-title">24/7 Smart Chatbot Assistant</h3>
                    <p class="service-text">
                        Get instant assistance with common healthcare questions using our smart chatbot.
                        It helps you navigate services, appointments, and clinic information anytime.
                    </p>
                    <ul class="service-features">
                        <li>Available 24/7 for instant support</li>
                        <li>Appointment and clinic guidance</li>
                        <li>General health information</li>
                        <li>Doctor and service lookup</li>
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
                        <li>PDF receipt generation</li>
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
                    <p class="service-text">
                    Stay on top of your health with timely reminders for appointments and follow-ups. 
                    Manage notifications and important updates effortlessly.
                    </p>
                    <ul class="service-features">
                        <li>Instantly view all notifications and stay organized</li>
                        <li>Get alerts for unread messages to never miss key info</li>
                        <li>Quickly mark notifications as read or delete them</li>
                        <li>See a clear overview of total notifications pending</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/appointmentDashboard.php' : 'login.php'; ?>" class="service-cta">Manage Reminders</a>
                </div>
            </div>

            <!-- Doctor Management -->
            <div class="service-card">
                <div class="service-icon">👨‍⚕️</div>
                <div class="service-content">
                    <h3 class="service-title">Expert Doctor Consultation</h3>
                    <p class="service-text">
                        Connect with qualified doctors across multiple specializations. 
                        Browse profiles, reviews, and book appointments easily.
                    </p>
                    <ul class="service-features">
                        <li>Licensed doctors in various specializations</li>
                        <li>View detailed doctor profiles and experience</li>
                        <li>Read patient reviews and ratings</li>
                        <li>Check consultation fees and availability</li>
                    </ul>
                    <a href="doctors.php" class="service-cta">View Doctors</a>
                </div>
            </div>

            <!-- Patient Portal -->
            <div class="service-card">
                <div class="service-icon">🤒</div>
                <div class="service-content">
                    <h3 class="service-title">Patient Portal & Profile</h3>
                    <p class="service-text">
                        Manage your health journey from one personalized dashboard.
                    </p>
                    <ul class="service-features">
                        <li>View your health dashboard at a glance</li>
                        <li>Track upcoming and past appointments</li>
                        <li>Access medical records and prescriptions</li>
                        <li>Monitor health metrics and progress</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/dashboard.php' : 'login.php'; ?>" class="service-cta">Access Portal</a>
                </div>
            </div>

            <!-- Prescription Management -->
            <div class="service-card">
                <div class="service-icon">💊</div>
                <div class="service-content">
                    <h3 class="service-title">Digital Prescription Management</h3>
                    <<p class="service-text">View and manage all your prescriptions securely in one place.</p>
                    <ul class="service-features">
                        <li>See detailed medication information</li>
                        <li>Track prescription history and doctor details</li>
                        <li>Download or print prescription records</li>
                        <li>Easy overview of all prescribed medications</li>
                    </ul>
                    <a href="<?php echo isLoggedIn() ? 'patient/prescriptions.php' : 'login.php'; ?>" class="service-cta">View Prescriptions</a>
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
                        <li>Secure authentication</li>
                    </ul>
                    <a href="registration.php" class="service-cta">Register Now</a>
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