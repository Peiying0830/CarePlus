<?php
// dashboard.php - Doctor Dashboard
require_once __DIR__ . '/../config.php';
requireRole('doctor');

$db = Database::getInstance()->getConnection();
$userId = getUserId();

// Fetch doctor info
$doctorStmt = $db->prepare("
    SELECT d.*, u.email 
    FROM doctors d 
    JOIN users u ON d.user_id = u.user_id 
    WHERE d.user_id = ? 
    LIMIT 1
");
$doctorStmt->execute([$userId]);
$doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    redirect('doctor/profile.php');
}

$doctorId = $doctor['doctor_id'];

// Get doctor's full name
$doctorFullName = trim($doctor['first_name'] . ' ' . $doctor['last_name']);
$doctorFirstName = $doctor['first_name'];

// Get today's date
$today = date('Y-m-d');

// Fetch today's appointments
$todayAppointmentsStmt = $db->prepare("
    SELECT a.*, 
           p.first_name as patient_fname, 
           p.last_name as patient_lname,
           p.date_of_birth,
           p.phone,
           p.blood_type
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? 
    AND a.appointment_date = ?
    AND a.status IN ('confirmed', 'pending')
    ORDER BY a.appointment_time ASC
");
$todayAppointmentsStmt->execute([$doctorId, $today]);
$todayAppointments = $todayAppointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming appointments (next 7 days)
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$upcomingAppointmentsStmt = $db->prepare("
    SELECT a.*, 
           p.first_name as patient_fname, 
           p.last_name as patient_lname,
           p.phone
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? 
    AND a.appointment_date > ?
    AND a.appointment_date <= ?
    AND a.status IN ('confirmed', 'pending')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 6
");
$upcomingAppointmentsStmt->execute([$doctorId, $today, $nextWeek]);
$upcomingAppointments = $upcomingAppointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Count appointments by status
$appointmentStatsStmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN status = 'confirmed' AND appointment_date = ? THEN 1 ELSE 0 END) as today,
        SUM(CASE WHEN status = 'confirmed' AND appointment_date > ? THEN 1 ELSE 0 END) as upcoming,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        COUNT(*) as total
    FROM appointments 
    WHERE doctor_id = ?
");
$appointmentStatsStmt->execute([$today, $today, $doctorId]);
$appointmentStats = $appointmentStatsStmt->fetch(PDO::FETCH_ASSOC);

// Get total patients count
$totalPatientsStmt = $db->prepare("
    SELECT COUNT(DISTINCT patient_id) as total_patients
    FROM appointments 
    WHERE doctor_id = ?
");
$totalPatientsStmt->execute([$doctorId]);
$totalPatients = $totalPatientsStmt->fetch(PDO::FETCH_ASSOC)['total_patients'] ?? 0;

// Fetch recent patients
$recentPatientsStmt = $db->prepare("
    SELECT DISTINCT p.*, 
           MAX(a.appointment_date) as last_visit,
           COUNT(a.appointment_id) as total_visits
    FROM patients p
    JOIN appointments a ON p.patient_id = a.patient_id
    WHERE a.doctor_id = ?
    GROUP BY p.patient_id
    ORDER BY last_visit DESC
    LIMIT 6
");
$recentPatientsStmt->execute([$doctorId]);
$recentPatients = $recentPatientsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending appointments (need confirmation)
$pendingAppointmentsStmt = $db->prepare("
    SELECT a.*, 
           p.first_name as patient_fname, 
           p.last_name as patient_lname,
           p.phone
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? 
    AND a.status = 'pending'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 5
");
$pendingAppointmentsStmt->execute([$doctorId]);
$pendingAppointments = $pendingAppointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate earnings (this month)
$currentMonth = date('Y-m');
$earningsStmt = $db->prepare("
    SELECT 
        COUNT(*) as completed_appointments,
        SUM(d.consultation_fee) as total_earnings
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.doctor_id = ? 
    AND a.status = 'completed'
    AND DATE_FORMAT(a.appointment_date, '%Y-%m') = ?
");
$earningsStmt->execute([$doctorId, $currentMonth]);
$earnings = $earningsStmt->fetch(PDO::FETCH_ASSOC);

// Check for any urgent notifications
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
    error_log("Notifications table not found: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - <?php echo SITE_NAME; ?></title>
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
                    <h1 data-doctor-name="<?php echo htmlspecialchars($doctorFullName); ?>">
                        Welcome back, Dr. <?php echo htmlspecialchars($doctor['last_name']); ?>! 👨‍⚕️
                    </h1>
                    <p><?php echo htmlspecialchars($doctor['specialization']); ?> • Ready to make a difference today</p>
                </div>
                <div class="date-time">
                    <div class="current-date"><?php echo date('l, F j, Y'); ?></div>
                    <div class="current-time" id="live-clock"><?php echo date('h:i A'); ?></div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid fade-in">
                <div class="stat-card" data-target="appointments.php">
                    <div class="stat-icon today">
                        <span>📅</span>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $appointmentStats['today'] ?? 0; ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card" data-target="appointments.php">
                    <div class="stat-icon pending">
                        <span>⏳</span>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $appointmentStats['pending'] ?? 0; ?></h3>
                        <p>Pending Confirmation</p>
                    </div>
                </div>
                
                <div class="stat-card" data-target="patients.php">
                    <div class="stat-icon patients">
                        <span>👥</span>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalPatients; ?></h3>
                        <p>Total Patients</p>
                    </div>
                </div>
                
                <div class="stat-card" data-target="earnings.php">
                    <div class="stat-icon earnings">
                        <span>💰</span>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($earnings['total_earnings'] ?? 0, 0); ?></h3>
                        <p>This Month's Earnings</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions fade-in">
                <a href="appointments.php" class="action-card">
                    <div class="action-icon">
                        <span>📋</span>
                    </div>
                    <div class="action-text">
                        <h3>View Appointments</h3>
                        <p>Manage your schedule</p>
                    </div>
                </a>
                
                <a href="patients.php" class="action-card">
                    <div class="action-icon">
                        <span>👥</span>
                    </div>
                    <div class="action-text">
                        <h3>Patient Records</h3>
                        <p>Access medical history</p>
                    </div>
                </a>
                
                <a href="profile.php" class="action-card">
                    <div class="action-icon">
                        <span>👤</span>
                    </div>
                    <div class="action-text">
                        <h3>My Profile</h3>
                        <p>Update your information</p>
                    </div>
                </a>
                
                <a href="#" class="action-card" onclick="alert('Reports feature coming soon!')">
                    <div class="action-icon">
                        <span>📊</span>
                    </div>
                    <div class="action-text">
                        <h3>Reports</h3>
                        <p>View analytics</p>
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
                            <div class="appointment-item today" data-appointment-id="<?php echo $appointment['appointment_id']; ?>">
                                <div class="appointment-info">
                                    <h4><?php echo htmlspecialchars($appointment['patient_fname'] . ' ' . $appointment['patient_lname']); ?></h4>
                                    <div class="appointment-details">
                                        <span>🕒 <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></span>
                                        <span>📞 <?php echo htmlspecialchars($appointment['phone']); ?></span>
                                        <?php if (!empty($appointment['blood_type'])): ?>
                                            <span>🩸 <?php echo htmlspecialchars($appointment['blood_type']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($appointment['reason'])): ?>
                                        <div class="appointment-reason">
                                            <?php echo htmlspecialchars($appointment['reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="appointment-actions">
                                    <div class="appointment-status status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </div>
                                    <?php if ($appointment['status'] === 'pending'): ?>
                                        <button class="btn-action confirm" onclick="confirmAppointment(<?php echo $appointment['appointment_id']; ?>)">
                                            ✓ Confirm
                                        </button>
                                    <?php endif; ?>
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
                <!-- Pending Confirmations -->
                <?php if (!empty($pendingAppointments)): ?>
                <div class="dashboard-card fade-in urgent">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>⚠️</span> Pending Confirmations
                            <span class="notification-badge pulse"></span>
                        </h2>
                        <a href="appointments.php?status=pending" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="appointment-list">
                            <?php foreach ($pendingAppointments as $appointment): ?>
                                <div class="appointment-item pending">
                                    <div class="appointment-info">
                                        <h4><?php echo htmlspecialchars($appointment['patient_fname'] . ' ' . $appointment['patient_lname']); ?></h4>
                                        <div class="appointment-time">
                                            <span>📅</span> <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                            <span>🕒</span> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                        </div>
                                    </div>
                                    <div class="appointment-actions">
                                        <button class="btn-action confirm" onclick="confirmAppointment(<?php echo $appointment['appointment_id']; ?>)">
                                            ✓
                                        </button>
                                        <button class="btn-action cancel" onclick="cancelAppointment(<?php echo $appointment['appointment_id']; ?>)">
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Upcoming Appointments -->
                <div class="dashboard-card fade-in">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>⏰</span> Upcoming Appointments
                        </h2>
                        <a href="appointments.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcomingAppointments)): ?>
                            <div class="appointment-list">
                                <?php foreach ($upcomingAppointments as $appointment): ?>
                                    <div class="appointment-item">
                                        <div class="appointment-info">
                                            <h4><?php echo htmlspecialchars($appointment['patient_fname'] . ' ' . $appointment['patient_lname']); ?></h4>
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
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Patients -->
                <div class="dashboard-card fade-in">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>👥</span> Recent Patients
                        </h2>
                        <a href="patients.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentPatients)): ?>
                            <div class="patients-list">
                                <?php foreach ($recentPatients as $patient): ?>
                                    <div class="patient-card" data-patient-id="<?php echo $patient['patient_id']; ?>">
                                        <div class="patient-avatar">
                                            <span><?php echo strtoupper(substr($patient['first_name'], 0, 1)); ?></span>
                                        </div>
                                        <div class="patient-info">
                                            <h4><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h4>
                                            <div class="patient-details">
                                                <span>Last visit: <?php echo date('M d', strtotime($patient['last_visit'])); ?></span>
                                                <span>Total visits: <?php echo $patient['total_visits']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">👥</div>
                                <p>No patients yet</p>
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

            <!-- Performance Summary -->
            <div class="dashboard-card fade-in" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">
                        <span>📊</span> Monthly Performance
                    </h2>
                </div>
                <div class="card-body">
                    <div class="performance-grid">
                        <div class="performance-item">
                            <div class="performance-icon">💼</div>
                            <h4><?php echo $earnings['completed_appointments'] ?? 0; ?></h4>
                            <p>Consultations</p>
                        </div>
                        <div class="performance-item">
                            <div class="performance-icon">💰</div>
                            <h4>₹<?php echo number_format($earnings['total_earnings'] ?? 0, 0); ?></h4>
                            <p>Earnings</p>
                        </div>
                        <div class="performance-item">
                            <div class="performance-icon">⭐</div>
                            <h4><?php echo $appointmentStats['completed'] ?? 0; ?></h4>
                            <p>Total Completed</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="dashboard.js"></script>
</body>
</html>