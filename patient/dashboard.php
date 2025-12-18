<?php
// dashboard.php - Patient Dashboard
require_once __DIR__ . '/../config.php';
requireRole('patient');

$db = Database::getInstance()->getConnection();
$userId = getUserId();

// Fetch patient info
$patientStmt = $db->prepare("
    SELECT p.*, u.email 
    FROM patients p 
    JOIN users u ON p.user_id = u.user_id 
    WHERE p.user_id = ? 
    LIMIT 1
");
$patientStmt->execute([$userId]);
$patient = $patientStmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    redirect('patient/profile.php');
}

$patientId = $patient['patient_id'];

// Get patient's full name
$patientFullName = trim($patient['first_name'] . ' ' . $patient['last_name']);
$patientFirstName = $patient['first_name'];

// Get today's date
$today = date('Y-m-d');

// Fetch upcoming appointments (today and future)
$upcomingAppointmentsStmt = $db->prepare("
    SELECT a.*, 
           d.first_name as doctor_fname, 
           d.last_name as doctor_lname,
           d.specialization,
           d.consultation_fee
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? 
    AND a.appointment_date >= ?
    AND a.status IN ('confirmed', 'pending')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 5
");
$upcomingAppointmentsStmt->execute([$patientId, $today]);
$upcomingAppointments = $upcomingAppointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Count appointments by status
$appointmentStatsStmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN status = 'confirmed' AND appointment_date >= ? THEN 1 ELSE 0 END) as upcoming,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        COUNT(*) as total
    FROM appointments 
    WHERE patient_id = ?
");
$appointmentStatsStmt->execute([$today, $patientId]);
$appointmentStats = $appointmentStatsStmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent medical records
$medicalRecordsStmt = $db->prepare("
    SELECT a.*, 
           d.first_name as doctor_fname, 
           d.last_name as doctor_lname,
           d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? 
    AND a.status = 'completed'
    ORDER BY a.appointment_date DESC
    LIMIT 3
");
$medicalRecordsStmt->execute([$patientId]);
$recentMedicalRecords = $medicalRecordsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available doctors for quick booking
$availableDoctorsStmt = $db->prepare("
    SELECT doctor_id, first_name, last_name, specialization, consultation_fee
    FROM doctors 
    ORDER BY RAND()
    LIMIT 4
");
$availableDoctorsStmt->execute();
$availableDoctors = $availableDoctorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's appointments
$todayAppointmentsStmt = $db->prepare("
    SELECT a.*, 
           d.first_name as doctor_fname, 
           d.last_name as doctor_lname,
           d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? 
    AND a.appointment_date = ?
    AND a.status IN ('confirmed', 'pending')
    ORDER BY a.appointment_time ASC
");
$todayAppointmentsStmt->execute([$patientId, $today]);
$todayAppointments = $todayAppointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Check for any urgent notifications (if notifications table exists)
$notifications = [];
try {
    $notificationsStmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        AND is_read = 0
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $notificationsStmt->execute([$userId]);
    $notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Notifications table might not exist, that's okay
    error_log("Notifications table not found: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <!-- Include Header Navigation -->
    <?php include __DIR__ . '/headerNav.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <main class="container">
            <!-- Welcome Section -->
            <div class="welcome-section fade-in">
                <div class="welcome-text">
                    <h1 data-patient-name="<?php echo htmlspecialchars($patientFullName); ?>">
                        Welcome back, <?php echo htmlspecialchars($patientFullName); ?>! 👋
                    </h1>
                    <p>Here's what's happening with your health today</p>
                </div>
                <div class="date-time">
                    <div class="current-date"><?php echo date('l, F j, Y'); ?></div>
                    <div class="current-time" id="live-clock"><?php echo date('h:i A'); ?></div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid fade-in">
                <div class="stat-card" data-target="appointment.php">
                    <div class="stat-icon upcoming">
                        <span>📅</span>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $appointmentStats['upcoming'] ?? 0; ?></h3>
                        <p>Upcoming Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card" data-target="appointment.php">
                    <div class="stat-icon pending">
                        <span>⏳</span>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $appointmentStats['pending'] ?? 0; ?></h3>
                        <p>Pending Confirmation</p>
                    </div>
                </div>
                
                <div class="stat-card" data-target="medicalRecords.php">
                    <div class="stat-icon completed">
                        <span>✅</span>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $appointmentStats['completed'] ?? 0; ?></h3>
                        <p>Completed Visits</p>
                    </div>
                </div>
                
                <div class="stat-card" data-target="appointment.php">
                    <div class="stat-icon cancelled">
                        <span>❌</span>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $appointmentStats['cancelled'] ?? 0; ?></h3>
                        <p>Cancelled Appointments</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions fade-in">
                <a href="appointment.php" class="action-card">
                    <div class="action-icon">
                        <span>➕</span>
                    </div>
                    <div class="action-text">
                        <h3>Book Appointment</h3>
                        <p>Schedule a new doctor visit</p>
                    </div>
                </a>
                
                <a href="medicalRecords.php" class="action-card">
                    <div class="action-icon">
                        <span>📋</span>
                    </div>
                    <div class="action-text">
                        <h3>Medical Records</h3>
                        <p>View your health history</p>
                    </div>
                </a>
                
                <a href="profile.php" class="action-card">
                    <div class="action-icon">
                        <span>👤</span>
                    </div>
                    <div class="action-text">
                        <h3>My Profile</h3>
                        <p>Update personal information</p>
                    </div>
                </a>
                
                <a href="#" class="action-card" onclick="alert('Prescriptions feature coming soon!')">
                    <div class="action-icon">
                        <span>💊</span>
                    </div>
                    <div class="action-text">
                        <h3>Prescriptions</h3>
                        <p>View current medications</p>
                    </div>
                </a>
            </div>

            <!-- Today's Schedule -->
            <?php if (!empty($todayAppointments)): ?>
                <div class="today-schedule fade-in">
                    <div class="schedule-header">
                        <h2><span>📅</span> Today's Schedule</h2>
                        <div class="schedule-count"><?php echo count($todayAppointments); ?> appointment(s)</div>
                    </div>
                    
                    <div class="appointment-list">
                        <?php foreach ($todayAppointments as $appointment): ?>
                            <div class="appointment-item today">
                                <div class="appointment-info">
                                    <h4>Dr. <?php echo htmlspecialchars($appointment['doctor_fname'] . ' ' . $appointment['doctor_lname']); ?></h4>
                                    <div class="appointment-doctor"><?php echo htmlspecialchars($appointment['specialization']); ?></div>
                                    <div class="appointment-time">
                                        <span>🕒</span> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                </div>
                                <div class="appointment-status status-<?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Hidden data for JavaScript -->
            <div id="today-appointments-data" style="display: none;">
                <?php echo json_encode($todayAppointments); ?>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Upcoming Appointments -->
                <div class="dashboard-card fade-in">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>⏳</span> Upcoming Appointments
                        </h2>
                        <a href="appointment.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcomingAppointments)): ?>
                            <div class="appointment-list">
                                <?php foreach ($upcomingAppointments as $appointment): ?>
                                    <div class="appointment-item">
                                        <div class="appointment-info">
                                            <h4>Dr. <?php echo htmlspecialchars($appointment['doctor_fname'] . ' ' . $appointment['doctor_lname']); ?></h4>
                                            <div class="appointment-doctor"><?php echo htmlspecialchars($appointment['specialization']); ?></div>
                                            <div class="appointment-time">
                                                <span>📅</span> <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                <span>🕒</span> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                            </div>
                                        </div>
                                        <div class="appointment-status status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">📅</div>
                                <p>No upcoming appointments</p>
                                <a href="appointment.php" class="btn btn-primary" style="margin-top: 1rem;">
                                    <span>➕</span> Book Now
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Available Doctors -->
                <div class="dashboard-card fade-in">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>👨‍⚕️</span> Available Doctors
                        </h2>
                        <a href="doctors.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($availableDoctors)): ?>
                            <div class="doctors-grid">
                                <?php foreach ($availableDoctors as $doctor): ?>
                                    <div class="doctor-card" data-doctor-id="<?php echo $doctor['doctor_id']; ?>">
                                        <div class="doctor-avatar">
                                            <span>👨‍⚕️</span>
                                        </div>
                                        <div class="doctor-info">
                                            <h4>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h4>
                                            <div class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
                                            <div class="doctor-fee">₹<?php echo number_format($doctor['consultation_fee'], 2); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">👨‍⚕️</div>
                                <p>No doctors available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Medical Records -->
                <div class="dashboard-card fade-in">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>📋</span> Recent Medical Records
                        </h2>
                        <a href="medicalRecords.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentMedicalRecords)): ?>
                            <?php foreach ($recentMedicalRecords as $record): ?>
                                <div class="medical-record">
                                    <h4>Dr. <?php echo htmlspecialchars($record['doctor_fname'] . ' ' . $record['doctor_lname']); ?></h4>
                                    <div class="record-doctor"><?php echo htmlspecialchars($record['specialization']); ?></div>
                                    <div class="record-date">
                                        <span>📅</span> <?php echo date('M d, Y', strtotime($record['appointment_date'])); ?>
                                    </div>
                                    <?php if (!empty($record['diagnosis'])): ?>
                                        <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                            <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 100)); ?>...
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <p>No medical records yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="dashboard-card fade-in">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>🔔</span> Notifications
                            <?php if (!empty($notifications)): ?>
                                <span class="notification-badge pulse"></span>
                            <?php endif; ?>
                        </h2>
                        <a href="#" class="btn btn-sm btn-outline" onclick="alert('Notifications page coming soon!')">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($notifications)): ?>
                            <div class="notification-list">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item unread">
                                        <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                        <div class="notification-text">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </div>
                                        <div class="notification-time">
                                            <?php echo date('M d, h:i A', strtotime($notification['created_at'])); ?>
                                        </div>
                                        <div class="notification-badge"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">🔔</div>
                                <p>No new notifications</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Health Tips -->
            <div class="dashboard-card fade-in" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">
                        <span>💡</span> Health Tips
                    </h2>
                </div>
                <div class="card-body">
                    <div class="health-tips-grid">
                        <div class="health-tip">
                            <h4><span>💧</span> Stay Hydrated</h4>
                            <p>Drink at least 8 glasses of water daily for optimal health and proper body function.</p>
                        </div>
                        <div class="health-tip">
                            <h4><span>🚶‍♂️</span> Daily Exercise</h4>
                            <p>30 minutes of moderate exercise daily can improve cardiovascular health and mood.</p>
                        </div>
                        <div class="health-tip">
                            <h4><span>🍎</span> Balanced Diet</h4>
                            <p>Eat a variety of fruits, vegetables, and whole grains for essential nutrients.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="dashboard.js"></script>
</body>
</html>