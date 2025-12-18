<?php
require_once __DIR__ . '/../config.php';
requireRole('patient');

$db = Database::getInstance()->getConnection();
$userId = getUserId();

// Fetch patient info
$stmt = $db->prepare("SELECT * FROM patients WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    redirect('patient/profile.php');
}
$patientId = $patient['patient_id'];

// Handle appointment booking
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    try {
        $doctorId = $_POST['doctor_id'] ?? '';
        $appointmentDate = $_POST['appointment_date'] ?? '';
        $appointmentTime = $_POST['appointment_time'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $symptoms = $_POST['symptoms'] ?? '';
        
        // Validate inputs
        if (empty($doctorId) || empty($appointmentDate) || empty($appointmentTime)) {
            throw new Exception('Please fill all required fields');
        }
        
        // Check if date is in the future
        $today = date('Y-m-d');
        if ($appointmentDate < $today) {
            throw new Exception('Appointment date must be in the future');
        }
        
        // Check doctor availability (basic check - you might want to implement more complex logic)
        $checkStmt = $db->prepare("
            SELECT COUNT(*) as cnt 
            FROM appointments 
            WHERE doctor_id = ? 
            AND appointment_date = ? 
            AND appointment_time = ?
            AND status IN ('confirmed', 'pending')
        ");
        $checkStmt->execute([$doctorId, $appointmentDate, $appointmentTime]);
        $existingAppointments = $checkStmt->fetchColumn();
        
        if ($existingAppointments > 0) {
            throw new Exception('Selected time slot is not available. Please choose another time.');
        }
        
        // Generate QR code data (simplified)
        $qrData = "CarePlus|{$patientId}|{$doctorId}|{$appointmentDate}|{$appointmentTime}";
        $qrCode = md5($qrData); // In production, use a proper QR generation library
        
        // Insert appointment
        $insertStmt = $db->prepare("
            INSERT INTO appointments 
            (patient_id, doctor_id, appointment_date, appointment_time, reason, symptoms, status, qr_code, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");
        
        $insertStmt->execute([
            $patientId, 
            $doctorId, 
            $appointmentDate, 
            $appointmentTime, 
            $reason, 
            $symptoms,
            $qrCode
        ]);
        
        $appointmentId = $db->lastInsertId();
        $message = "Appointment booked successfully! Your appointment ID is #{$appointmentId}";
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Handle appointment cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $cancelId = $_GET['cancel'];
    
    // Verify ownership
    $verifyStmt = $db->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND patient_id = ?");
    $verifyStmt->execute([$cancelId, $patientId]);
    $appointment = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($appointment) {
        // Check if appointment can be cancelled (at least 24 hours before)
        $appointmentDateTime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
        $now = time();
        $appointmentTime = strtotime($appointmentDateTime);
        
        if (($appointmentTime - $now) < 86400) { // Less than 24 hours
            $message = "Appointments can only be cancelled at least 24 hours in advance.";
            $messageType = 'error';
        } else {
            $cancelStmt = $db->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE appointment_id = ?");
            $cancelStmt->execute([$cancelId]);
            $message = "Appointment cancelled successfully!";
            $messageType = 'success';
        }
    }
}

// Fetch all doctors for booking form - REMOVED is_active since it doesn't exist in your table
$doctorsStmt = $db->prepare("
    SELECT doctor_id, first_name, last_name, specialization, consultation_fee
    FROM doctors 
    ORDER BY specialization, last_name
");
$doctorsStmt->execute();
$doctors = $doctorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all appointments for this patient
$appointmentsStmt = $db->prepare("
    SELECT a.*, 
           d.first_name as doctor_fname, 
           d.last_name as doctor_lname,
           d.specialization,
           d.consultation_fee
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$appointmentsStmt->execute([$patientId]);
$allAppointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Filter appointments for tabs
$upcomingAppointments = array_filter($allAppointments, function($app) {
    return in_array($app['status'], ['confirmed', 'pending']) && 
           strtotime($app['appointment_date']) >= strtotime(date('Y-m-d'));
});

$pastAppointments = array_filter($allAppointments, function($app) {
    return in_array($app['status'], ['completed', 'cancelled']) || 
           strtotime($app['appointment_date']) < strtotime(date('Y-m-d'));
});

$confirmedAppointments = array_filter($allAppointments, function($app) {
    return $app['status'] === 'confirmed';
});

// Determine base URL
$baseUrl = defined('SITE_URL') ? SITE_URL : 'http://localhost/CarePlus';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="appointment.css">
</head>
<body>
    <!-- Include Header Navigation -->
    <?php include __DIR__ . '/headerNav.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <main class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">📅 Manage Appointments</h1>
                <button class="btn btn-primary" onclick="openBookingModal()">
                    <span>➕</span> Book New Appointment
                </button>
            </div>

            <!-- Message Alert -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                    <span><?php echo $messageType === 'error' ? '❌' : '✅'; ?></span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('upcoming')">
                    <span>⏳</span> Upcoming (<?php echo count($upcomingAppointments); ?>)
                </button>
                <button class="tab-btn" onclick="switchTab('past')">
                    <span>📜</span> Past (<?php echo count($pastAppointments); ?>)
                </button>
                <button class="tab-btn" onclick="switchTab('book')">
                    <span>➕</span> Book Appointment
                </button>
                <button class="tab-btn" onclick="switchTab('all')">
                    <span>📋</span> All Appointments (<?php echo count($allAppointments); ?>)
                </button>
            </div>

            <!-- Upcoming Appointments Tab -->
            <div id="upcoming-tab" class="tab-content active">
                <?php if (!empty($upcomingAppointments)): ?>
                    <div class="appointment-cards">
                        <?php foreach ($upcomingAppointments as $appointment): ?>
                            <div class="appointment-card status-<?php echo $appointment['status']; ?>">
                                <div class="appointment-header">
                                    <div class="appointment-id">#<?php echo $appointment['appointment_id']; ?></div>
                                    <div class="status-badge"><?php echo ucfirst($appointment['status']); ?></div>
                                </div>
                                
                                <div class="appointment-details">
                                    <h3>Dr. <?php echo htmlspecialchars($appointment['doctor_fname'] . ' ' . $appointment['doctor_lname']); ?></h3>
                                    <div class="appointment-doctor"><?php echo htmlspecialchars($appointment['specialization']); ?></div>
                                    <div class="appointment-datetime">
                                        <span>📅</span> <?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?>
                                        <span>🕒</span> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                    
                                    <?php if ($appointment['reason']): ?>
                                        <div class="appointment-reason">
                                            <strong>Reason:</strong> <?php echo htmlspecialchars($appointment['reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($appointment['qr_code']): ?>
                                        <div class="qr-code-container">
                                            <div class="qr-code">[QR Code: <?php echo substr($appointment['qr_code'], 0, 8); ?>...]</div>
                                            <small>Show this QR code at reception</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="appointment-actions">
                                    <?php if ($appointment['status'] === 'confirmed'): ?>
                                        <a href="appointment.php?view=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary btn-sm">
                                            <span>👁️</span> View Details
                                        </a>
                                        <?php if (strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']) - time() > 86400): ?>
                                            <a href="appointment.php?cancel=<?php echo $appointment['appointment_id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                <span>❌</span> Cancel
                                            </a>
                                        <?php endif; ?>
                                    <?php elseif ($appointment['status'] === 'pending'): ?>
                                        <span class="btn btn-outline btn-sm">
                                            <span>⏳</span> Awaiting Confirmation
                                        </span>
                                        <a href="appointment.php?cancel=<?php echo $appointment['appointment_id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            <span>❌</span> Cancel
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📅</div>
                        <p>No upcoming appointments found</p>
                        <button class="btn btn-primary" onclick="switchTab('book')" style="margin-top: 1rem;">
                            <span>➕</span> Book Your First Appointment
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Past Appointments Tab -->
            <div id="past-tab" class="tab-content">
                <?php if (!empty($pastAppointments)): ?>
                    <div class="appointment-cards">
                        <?php foreach ($pastAppointments as $appointment): ?>
                            <div class="appointment-card status-<?php echo $appointment['status']; ?>">
                                <div class="appointment-header">
                                    <div class="appointment-id">#<?php echo $appointment['appointment_id']; ?></div>
                                    <div class="status-badge"><?php echo ucfirst($appointment['status']); ?></div>
                                </div>
                                
                                <div class="appointment-details">
                                    <h3>Dr. <?php echo htmlspecialchars($appointment['doctor_fname'] . ' ' . $appointment['doctor_lname']); ?></h3>
                                    <div class="appointment-doctor"><?php echo htmlspecialchars($appointment['specialization']); ?></div>
                                    <div class="appointment-datetime">
                                        <span>📅</span> <?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?>
                                        <span>🕒</span> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                    
                                    <?php if ($appointment['status'] === 'completed' && isset($appointment['diagnosis'])): ?>
                                        <div class="appointment-reason">
                                            <strong>Diagnosis:</strong> <?php echo htmlspecialchars(substr($appointment['diagnosis'], 0, 100)); ?>...
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="appointment-actions">
                                    <a href="appointment.php?view=<?php echo $appointment['appointment_id']; ?>" class="btn btn-outline btn-sm">
                                        <span>👁️</span> View Details
                                    </a>
                                    <?php if ($appointment['status'] === 'completed'): ?>
                                        <a href="medicalRecords.php?record=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary btn-sm">
                                            <span>📋</span> View Medical Record
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📜</div>
                        <p>No past appointments found</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Book Appointment Tab -->
            <div id="book-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>🩺</span> Book New Appointment
                        </h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="appointmentForm">
                            <!-- Step 1: Select Doctor -->
                            <div class="form-group">
                                <label class="form-label">Step 1: Select Doctor</label>
                                <div class="doctor-cards">
                                    <?php if (!empty($doctors)): ?>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <label class="doctor-card">
                                                <input type="radio" 
                                                       name="doctor_id" 
                                                       value="<?php echo $doctor['doctor_id']; ?>" 
                                                       class="doctor-radio" 
                                                       required
                                                       onchange="updateDoctorSelection(this)">
                                                <div class="doctor-info">
                                                    <h4>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h4>
                                                    <div class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
                                                    <div class="doctor-fee">₹<?php echo number_format($doctor['consultation_fee'], 2); ?></div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-state" style="grid-column: 1 / -1;">
                                            <div class="empty-icon">👨‍⚕️</div>
                                            <p>No doctors available at the moment</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Step 2: Select Date & Time -->
                            <div class="form-group">
                                <label class="form-label">Step 2: Select Date & Time</label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <input type="date" 
                                               name="appointment_date" 
                                               id="appointment_date"
                                               class="form-control" 
                                               min="<?php echo date('Y-m-d'); ?>"
                                               required>
                                        <small style="display: block; margin-top: 0.5rem; color: #666;">Select appointment date</small>
                                    </div>
                                    <div>
                                        <select name="appointment_time" id="appointment_time" class="form-control" required>
                                            <option value="">Select Time Slot</option>
                                            <option value="09:00:00">09:00 AM</option>
                                            <option value="10:00:00">10:00 AM</option>
                                            <option value="11:00:00">11:00 AM</option>
                                            <option value="14:00:00">02:00 PM</option>
                                            <option value="15:00:00">03:00 PM</option>
                                            <option value="16:00:00">04:00 PM</option>
                                            <option value="17:00:00">05:00 PM</option>
                                        </select>
                                        <small style="display: block; margin-top: 0.5rem; color: #666;">Clinic hours: 9 AM - 6 PM</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Appointment Details -->
                            <div class="form-group">
                                <label class="form-label">Step 3: Appointment Details (Optional)</label>
                                <textarea name="reason" 
                                          class="form-control" 
                                          placeholder="Please describe the reason for your visit..."
                                          rows="3"></textarea>
                                <small style="display: block; margin-top: 0.5rem; color: #666;">This helps the doctor prepare for your visit</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Symptoms (Optional)</label>
                                <textarea name="symptoms" 
                                          class="form-control" 
                                          placeholder="List any symptoms you're experiencing..."
                                          rows="3"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="book_appointment" class="btn btn-primary" style="padding: 1rem 2rem;">
                                    <span>📅</span> Book Appointment
                                </button>
                                <button type="button" class="btn btn-outline" onclick="resetForm()">
                                    <span>🔄</span> Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- All Appointments Tab -->
            <div id="all-tab" class="tab-content">
                <?php if (!empty($allAppointments)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <span>📋</span> All Appointments (<?php echo count($allAppointments); ?>)
                            </h2>
                        </div>
                        <div class="card-body">
                            <div style="overflow-x: auto;">
                                <table class="appointment-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Doctor</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allAppointments as $appointment): ?>
                                            <tr>
                                                <td>#<?php echo $appointment['appointment_id']; ?></td>
                                                <td>Dr. <?php echo htmlspecialchars($appointment['doctor_fname'] . ' ' . $appointment['doctor_lname']); ?></td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?><br>
                                                    <small><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="status-badge" style="display: inline-block;">
                                                        <?php echo ucfirst($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="appointment.php?view=<?php echo $appointment['appointment_id']; ?>" 
                                                       class="btn btn-outline btn-sm">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <p>No appointments found</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="appointment.js"></script>
</body>
</html>