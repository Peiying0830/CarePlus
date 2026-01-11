<?php
session_start();

require_once __DIR__ . '/config.php';

// Create DB connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch all doctors using MySQLi
$result = $conn->query("SELECT * FROM doctors");

$doctors = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Doctors - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="doctors.css">
    <?php include __DIR__ . '/headerNav.php'; ?>
</head>
<body>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>Our Expert Doctors</h1>
        <p>Meet our team of experienced healthcare professionals</p>
    </div>
</section>

<!-- Filter Section -->
<section class="filter-section">
    <div class="container">
        <div class="filter-container">
            <div class="filter-group">
                <label for="specialization-filter">Specialization:</label>
                <select id="specialization-filter" class="filter-select">
                    <option value="">All Specializations</option>
                    <option value="General Practice">General Practice</option>
                    <option value="Cardiology">Cardiology</option>
                    <option value="Dermatology">Dermatology</option>
                    <option value="Pediatrics">Pediatrics</option>
                    <option value="Orthopedics">Orthopedics</option>
                    <option value="Neurology">Neurology</option>
                    <option value="Psychiatry">Psychiatry</option>
                    <option value="ENT">ENT</option>
                    <option value="Ophthalmology">Ophthalmology</option>
                    <option value="Gynecology">Gynecology</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="experience-filter">Experience:</label>
                <select id="experience-filter" class="filter-select">
                    <option value="">Any Experience</option>
                    <option value="0-5">0-5 years</option>
                    <option value="5-10">5-10 years</option>
                    <option value="10-20">10-20 years</option>
                    <option value="20+">20+ years</option>
                </select>
            </div>

            <div class="search-box">
                <input type="text" id="search-input" class="search-input" placeholder="🔍 Search by doctor name...">
            </div>
        </div>
    </div>
</section>

<!-- Doctors Section -->
<section class="doctors-section">
    <div class="container">
        <div class="doctors-grid" id="doctors-grid">
            <?php if (empty($doctors)): ?>
                <div class="no-results" style="grid-column: 1 / -1;">
                    <div class="no-results-icon">👨‍⚕️</div>
                    <h3>No Doctors Available</h3>
                    <p>Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($doctors as $doctor): ?>
                    <div class="doctor-card" 
                         data-specialization="<?php echo htmlspecialchars($doctor['specialization'] ?? ''); ?>"
                         data-experience="<?php echo htmlspecialchars($doctor['experience_years'] ?? '0'); ?>"
                         data-name="<?php echo htmlspecialchars(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '')); ?>">
                        
                        <div class="doctor-image-container">
                            <?php 
                            if (!empty($doctor['profile_picture']) && file_exists($doctor['profile_picture'])): 
                            ?>
                                <img src="<?php echo htmlspecialchars($doctor['profile_picture']); ?>" 
                                     alt="Dr. <?php echo htmlspecialchars(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '')); ?>" 
                                     class="doctor-image"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="default-avatar" style="display: none;">👨‍⚕️</div>
                            <?php else: ?>
                                <div class="default-avatar">👨‍⚕️</div>
                            <?php endif; ?>
                        </div>

                        <div class="doctor-info">
                            <h3 class="doctor-name">
                                Dr. <?php echo htmlspecialchars(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? '')); ?>
                            </h3>
                            <div class="doctor-specialization">
                                <?php echo htmlspecialchars($doctor['specialization'] ?? 'General Practice'); ?>
                            </div>

                            <div class="doctor-details">
                                <div class="detail-item">
                                    <span class="detail-icon">🎓</span>
                                    <span><?php echo htmlspecialchars($doctor['qualifications'] ?? 'MBBS'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">💼</span>
                                    <span><?php echo htmlspecialchars($doctor['experience_years'] ?? '0'); ?> years experience</span>
                                </div>
                                <?php if (!empty($doctor['email'])): ?>
                                <div class="detail-item">
                                    <span class="detail-icon">📧</span>
                                    <span><?php echo htmlspecialchars($doctor['email']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($doctor['phone'])): ?>
                                <div class="detail-item">
                                    <span class="detail-icon">📞</span>
                                    <span><?php echo htmlspecialchars($doctor['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="doctor-footer">
                                <div>
                                    <span class="fee-label">Consultation Fee</span>
                                    <div class="consultation-fee">
                                        RM <?php echo number_format($doctor['consultation_fee'] ?? 0, 2); ?>
                                    </div>
                                </div>
                                <a href="<?php echo isLoggedIn() ? 'patient/appointmentDashboard.php?doctor_id=' . ($doctor['doctor_id'] ?? '') : 'login.php'; ?>" 
                                   class="book-btn">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="no-results-message" class="no-results" style="display: none;">
            <div class="no-results-icon">🔍</div>
            <h3>No Doctors Found</h3>
            <p>Try adjusting your filters or search criteria.</p>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Why Choose Our Doctors</h2>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($doctors); ?>+</div>
                <div class="stat-label">Expert Doctors</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">15+</div>
                <div class="stat-label">Specializations</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">10k+</div>
                <div class="stat-label">Happy Patients</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">98%</div>
                <div class="stat-label">Satisfaction Rate</div>
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

<!-- Load JavaScript Files -->
<script src="doctors.js"></script>

</body>
</html>