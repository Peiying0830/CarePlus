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

// Handle specific record view
$viewingRecord = null;
if (isset($_GET['record']) && is_numeric($_GET['record'])) {
    $recordId = $_GET['record'];
    
    // Fetch specific medical record with appointment and doctor details
    $recordStmt = $db->prepare("
        SELECT mr.*, 
               a.appointment_date, a.appointment_time, a.reason as appointment_reason,
               d.first_name as doctor_fname, d.last_name as doctor_lname,
               d.specialization, d.consultation_fee
        FROM medical_records mr
        JOIN appointments a ON mr.appointment_id = a.appointment_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE mr.record_id = ? AND a.patient_id = ?
        LIMIT 1
    ");
    $recordStmt->execute([$recordId, $patientId]);
    $viewingRecord = $recordStmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all medical records for this patient
$recordsStmt = $db->prepare("
    SELECT mr.*, 
           a.appointment_date, a.appointment_time,
           d.first_name as doctor_fname, d.last_name as doctor_lname,
           d.specialization
    FROM medical_records mr
    JOIN appointments a ON mr.appointment_id = a.appointment_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ?
    ORDER BY mr.created_at DESC, a.appointment_date DESC
");
$recordsStmt->execute([$patientId]);
$allRecords = $recordsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get patient statistics
$statsStmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT mr.record_id) as total_records,
        COUNT(DISTINCT a.doctor_id) as doctors_consulted,
        COUNT(DISTINCT DATE_FORMAT(a.appointment_date, '%Y-%m')) as months_active
    FROM medical_records mr
    JOIN appointments a ON mr.appointment_id = a.appointment_id
    WHERE a.patient_id = ?
");
$statsStmt->execute([$patientId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Group records by year
$recordsByYear = [];
foreach ($allRecords as $record) {
    $year = date('Y', strtotime($record['appointment_date']));
    if (!isset($recordsByYear[$year])) {
        $recordsByYear[$year] = [];
    }
    $recordsByYear[$year][] = $record;
}
krsort($recordsByYear); // Sort by year descending

// Get recent diagnoses (last 5)
$recentDiagnoses = array_slice($allRecords, 0, 5);

// Determine base URL
$baseUrl = defined('SITE_URL') ? SITE_URL : 'http://localhost/CarePlus';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="medicalRecords.css">
</head>
<body>
    <!-- Include Header Navigation -->
    <?php include __DIR__ . '/headerNav.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <main class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">📋 Medical Records</h1>
                    <p class="page-subtitle">Your complete health history and medical documentation</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline" onclick="window.print()">
                        <span>🖨️</span> Print Records
                    </button>
                    <button class="btn btn-primary" onclick="exportRecords()">
                        <span>📥</span> Export PDF
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📄</div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo $stats['total_records'] ?? 0; ?></div>
                        <div class="stat-label">Total Records</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👨‍⚕️</div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo $stats['doctors_consulted'] ?? 0; ?></div>
                        <div class="stat-label">Doctors Consulted</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo $stats['months_active'] ?? 0; ?></div>
                        <div class="stat-label">Months Active</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🩺</div>
                    <div class="stat-details">
                        <div class="stat-value"><?php echo !empty($recentDiagnoses) ? date('M Y', strtotime($recentDiagnoses[0]['appointment_date'])) : 'N/A'; ?></div>
                        <div class="stat-label">Last Visit</div>
                    </div>
                </div>
            </div>

            <?php if ($viewingRecord): ?>
                <!-- Detailed Record View -->
                <div class="record-detail-view">
                    <div class="detail-header">
                        <button class="btn btn-outline" onclick="window.history.back()">
                            <span>←</span> Back to All Records
                        </button>
                        <div class="detail-actions">
                            <button class="btn btn-outline btn-sm" onclick="printRecord(<?php echo $viewingRecord['record_id']; ?>)">
                                <span>🖨️</span> Print
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="downloadRecord(<?php echo $viewingRecord['record_id']; ?>)">
                                <span>📥</span> Download
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <span>📋</span> Medical Record #<?php echo $viewingRecord['record_id']; ?>
                            </h2>
                            <div class="record-date">
                                <?php echo date('F d, Y', strtotime($viewingRecord['appointment_date'])); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Doctor Information -->
                            <div class="info-section">
                                <h3 class="section-title">👨‍⚕️ Consulting Doctor</h3>
                                <div class="doctor-info-box">
                                    <div class="doctor-name">
                                        Dr. <?php echo htmlspecialchars($viewingRecord['doctor_fname'] . ' ' . $viewingRecord['doctor_lname']); ?>
                                    </div>
                                    <div class="doctor-specialty"><?php echo htmlspecialchars($viewingRecord['specialization']); ?></div>
                                    <div class="appointment-info">
                                        <span>📅</span> <?php echo date('F d, Y', strtotime($viewingRecord['appointment_date'])); ?>
                                        <span style="margin-left: 1rem;">🕒</span> <?php echo date('h:i A', strtotime($viewingRecord['appointment_time'])); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Diagnosis -->
                            <?php if ($viewingRecord['diagnosis']): ?>
                                <div class="info-section">
                                    <h3 class="section-title">🔍 Diagnosis</h3>
                                    <div class="info-content">
                                        <?php echo nl2br(htmlspecialchars($viewingRecord['diagnosis'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Symptoms -->
                            <?php if ($viewingRecord['symptoms']): ?>
                                <div class="info-section">
                                    <h3 class="section-title">🤒 Symptoms Reported</h3>
                                    <div class="info-content">
                                        <?php echo nl2br(htmlspecialchars($viewingRecord['symptoms'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Prescriptions -->
                            <?php if ($viewingRecord['prescriptions']): ?>
                                <div class="info-section">
                                    <h3 class="section-title">💊 Prescriptions</h3>
                                    <div class="prescription-box">
                                        <?php echo nl2br(htmlspecialchars($viewingRecord['prescriptions'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Lab Tests -->
                            <?php if ($viewingRecord['lab_tests']): ?>
                                <div class="info-section">
                                    <h3 class="section-title">🧪 Laboratory Tests</h3>
                                    <div class="info-content">
                                        <?php echo nl2br(htmlspecialchars($viewingRecord['lab_tests'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Treatment Plan -->
                            <?php if ($viewingRecord['treatment_plan']): ?>
                                <div class="info-section">
                                    <h3 class="section-title">📝 Treatment Plan</h3>
                                    <div class="info-content">
                                        <?php echo nl2br(htmlspecialchars($viewingRecord['treatment_plan'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Follow-up Notes -->
                            <?php if ($viewingRecord['follow_up_notes']): ?>
                                <div class="info-section">
                                    <h3 class="section-title">📌 Follow-up Notes</h3>
                                    <div class="info-content alert-info">
                                        <?php echo nl2br(htmlspecialchars($viewingRecord['follow_up_notes'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Record Metadata -->
                            <div class="record-metadata">
                                <div class="metadata-item">
                                    <span class="metadata-label">Record Created:</span>
                                    <span class="metadata-value"><?php echo date('F d, Y g:i A', strtotime($viewingRecord['created_at'])); ?></span>
                                </div>
                                <?php if ($viewingRecord['updated_at']): ?>
                                    <div class="metadata-item">
                                        <span class="metadata-label">Last Updated:</span>
                                        <span class="metadata-value"><?php echo date('F d, Y g:i A', strtotime($viewingRecord['updated_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Records List View -->
                
                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('all')">
                        <span>📋</span> All Records (<?php echo count($allRecords); ?>)
                    </button>
                    <button class="tab-btn" onclick="switchTab('recent')">
                        <span>🕒</span> Recent
                    </button>
                    <button class="tab-btn" onclick="switchTab('by-year')">
                        <span>📅</span> By Year
                    </button>
                </div>

                <!-- All Records Tab -->
                <div id="all-tab" class="tab-content active">
                    <?php if (!empty($allRecords)): ?>
                        <div class="records-grid">
                            <?php foreach ($allRecords as $record): ?>
                                <div class="record-card" onclick="viewRecord(<?php echo $record['record_id']; ?>)">
                                    <div class="record-header">
                                        <div class="record-id">#<?php echo $record['record_id']; ?></div>
                                        <div class="record-date-badge">
                                            <?php echo date('M d, Y', strtotime($record['appointment_date'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="record-body">
                                        <h3 class="record-title">
                                            Dr. <?php echo htmlspecialchars($record['doctor_fname'] . ' ' . $record['doctor_lname']); ?>
                                        </h3>
                                        <div class="record-specialty"><?php echo htmlspecialchars($record['specialization']); ?></div>
                                        
                                        <?php if ($record['diagnosis']): ?>
                                            <div class="record-diagnosis">
                                                <strong>Diagnosis:</strong>
                                                <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 100)); ?>
                                                <?php echo strlen($record['diagnosis']) > 100 ? '...' : ''; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="record-meta">
                                            <?php if ($record['prescriptions']): ?>
                                                <span class="meta-badge">💊 Prescription</span>
                                            <?php endif; ?>
                                            <?php if ($record['lab_tests']): ?>
                                                <span class="meta-badge">🧪 Lab Tests</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="record-footer">
                                        <a href="medicalRecords.php?record=<?php echo $record['record_id']; ?>" 
                                           class="btn btn-outline btn-sm" 
                                           onclick="event.stopPropagation()">
                                            <span>👁️</span> View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <h3>No Medical Records Found</h3>
                            <p>Your medical records will appear here after your appointments are completed.</p>
                            <a href="appointment.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <span>📅</span> Book an Appointment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Records Tab -->
                <div id="recent-tab" class="tab-content">
                    <?php if (!empty($recentDiagnoses)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <span>🕒</span> Recent Medical History
                                </h2>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php foreach ($recentDiagnoses as $record): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-date">
                                                    <?php echo date('F d, Y', strtotime($record['appointment_date'])); ?>
                                                </div>
                                                <h4 class="timeline-title">
                                                    Dr. <?php echo htmlspecialchars($record['doctor_fname'] . ' ' . $record['doctor_lname']); ?>
                                                </h4>
                                                <div class="timeline-specialty"><?php echo htmlspecialchars($record['specialization']); ?></div>
                                                <?php if ($record['diagnosis']): ?>
                                                    <div class="timeline-diagnosis">
                                                        <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 150)); ?>
                                                        <?php echo strlen($record['diagnosis']) > 150 ? '...' : ''; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <a href="medicalRecords.php?record=<?php echo $record['record_id']; ?>" 
                                                   class="timeline-link">
                                                    View Full Record →
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">🕒</div>
                            <p>No recent medical records available</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- By Year Tab -->
                <div id="by-year-tab" class="tab-content">
                    <?php if (!empty($recordsByYear)): ?>
                        <?php foreach ($recordsByYear as $year => $yearRecords): ?>
                            <div class="year-section">
                                <div class="year-header">
                                    <h2 class="year-title">📅 <?php echo $year; ?></h2>
                                    <span class="year-count"><?php echo count($yearRecords); ?> record(s)</span>
                                </div>
                                
                                <div class="records-grid">
                                    <?php foreach ($yearRecords as $record): ?>
                                        <div class="record-card" onclick="viewRecord(<?php echo $record['record_id']; ?>)">
                                            <div class="record-header">
                                                <div class="record-id">#<?php echo $record['record_id']; ?></div>
                                                <div class="record-date-badge">
                                                    <?php echo date('M d', strtotime($record['appointment_date'])); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="record-body">
                                                <h3 class="record-title">
                                                    Dr. <?php echo htmlspecialchars($record['doctor_fname'] . ' ' . $record['doctor_lname']); ?>
                                                </h3>
                                                <div class="record-specialty"><?php echo htmlspecialchars($record['specialization']); ?></div>
                                                
                                                <?php if ($record['diagnosis']): ?>
                                                    <div class="record-diagnosis">
                                                        <strong>Diagnosis:</strong>
                                                        <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 80)); ?>
                                                        <?php echo strlen($record['diagnosis']) > 80 ? '...' : ''; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="record-footer">
                                                <a href="medicalRecords.php?record=<?php echo $record['record_id']; ?>" 
                                                   class="btn btn-outline btn-sm" 
                                                   onclick="event.stopPropagation()">
                                                    <span>👁️</span> View
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📅</div>
                            <p>No medical records available</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </main>
    </div>

    <script src="medicalRecords.js"></script>
</body>
</html>