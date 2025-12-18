<?php
require_once __DIR__ . '/../config.php';
requireRole('patient');

$db = Database::getInstance()->getConnection();
$userId = getUserId();

// fetch patient
$stmt = $db->prepare("SELECT * FROM patients WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    redirect('patient/profile.php');
}
$patientId = $patient['patient_id'];

// stats
$upcomingStmt = $db->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = ? AND status = 'confirmed' AND appointment_date >= CURDATE()");
$upcomingStmt->execute([$patientId]);
$upcomingCount = $upcomingStmt->fetchColumn();

$completedStmt = $db->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = ? AND status = 'completed'");
$completedStmt->execute([$patientId]);
$completedCount = $completedStmt->fetchColumn();

$recordsStmt = $db->prepare("SELECT COUNT(*) as cnt FROM medical_records WHERE patient_id = ?");
$recordsStmt->execute([$patientId]);
$recordsCount = $recordsStmt->fetchColumn();

// recent appointments
$appointmentsStmt = $db->prepare("
  SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.qr_code,
         d.first_name, d.last_name, d.specialization
  FROM appointments a
  JOIN doctors d ON a.doctor_id = d.doctor_id
  WHERE a.patient_id = ?
  ORDER BY a.appointment_date DESC, a.appointment_time DESC
  LIMIT 5
");
$appointmentsStmt->execute([$patientId]);
$appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// recent medical records
$medRecordsStmt = $db->prepare("
  SELECT mr.*, d.first_name AS d_fname, d.last_name AS d_lname
  FROM medical_records mr
  LEFT JOIN doctors d ON mr.doctor_id = d.doctor_id
  WHERE mr.patient_id = ?
  ORDER BY mr.visit_date DESC
  LIMIT 3
");
$medRecordsStmt->execute([$patientId]);
$medRecords = $medRecordsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Patient Dashboard - <?php echo SITE_NAME; ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../styles.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #e3f2fd 0%, #f5f5f5 100%);
      min-height: 100vh;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }

    .dashboard-header {
      background: white;
      padding: 2.5rem;
      border-radius: 20px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      margin-bottom: 2rem;
      background: linear-gradient(135deg, #42a5f5 0%, #1e88e5 100%);
      color: white;
    }

    .dashboard-title {
      font-size: 2.2rem;
      margin-bottom: 0.5rem;
      font-weight: 700;
    }

    .dashboard-subtitle {
      font-size: 1.1rem;
      opacity: 0.95;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, #42a5f5, #1e88e5);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(66, 165, 245, 0.2);
    }

    .stat-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: #1565c0;
      margin-bottom: 0.5rem;
    }

    .stat-label {
      color: #757575;
      font-size: 1rem;
      font-weight: 500;
    }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .action-card {
      background: linear-gradient(135deg, #42a5f5, #1e88e5);
      padding: 2rem;
      border-radius: 15px;
      text-align: center;
      color: white;
      text-decoration: none;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(66, 165, 245, 0.3);
    }

    .action-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 30px rgba(66, 165, 245, 0.4);
    }

    .action-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }

    .action-title {
      font-size: 1.1rem;
      font-weight: 600;
    }

    .card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      margin-bottom: 2rem;
      overflow: hidden;
    }

    .card-header {
      padding: 1.5rem 2rem;
      border-bottom: 2px solid #e3f2fd;
      background: linear-gradient(135deg, #e3f2fd, #f5f5f5);
    }

    .card-title {
      font-size: 1.5rem;
      color: #1565c0;
      font-weight: 600;
    }

    .card-body {
      padding: 2rem;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
    }

    .table th {
      background: #e3f2fd;
      color: #1565c0;
      font-weight: 600;
      text-align: left;
      padding: 1rem;
      border-bottom: 2px solid #bbdefb;
    }

    .table td {
      padding: 1rem;
      border-bottom: 1px solid #f5f5f5;
      color: #424242;
    }

    .table tr:hover {
      background: #f5f9ff;
    }

    .badge {
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-block;
    }

    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-info { background: #d1ecf1; color: #0c5460; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-danger { background: #f8d7da; color: #721c24; }

    .btn {
      padding: 0.6rem 1.2rem;
      border: none;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s;
    }

    .btn-sm {
      padding: 0.4rem 0.8rem;
      font-size: 0.85rem;
    }

    .btn-primary {
      background: linear-gradient(135deg, #42a5f5, #1e88e5);
      color: white;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #1e88e5, #1565c0);
      transform: translateY(-2px);
    }

    .btn-outline {
      background: white;
      color: #1565c0;
      border: 2px solid #1565c0;
    }

    .btn-outline:hover {
      background: #1565c0;
      color: white;
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
      color: #9e9e9e;
    }

    .empty-state-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .record-preview {
      border: 2px solid #e3f2fd;
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: all 0.3s;
    }

    .record-preview:hover {
      border-color: #42a5f5;
      box-shadow: 0 4px 15px rgba(66, 165, 245, 0.15);
    }

    .record-date {
      font-weight: 600;
      color: #1565c0;
      margin-bottom: 0.5rem;
    }

    .record-diagnosis {
      color: #616161;
      line-height: 1.6;
    }

    @media (max-width: 768px) {
      .container {
        padding: 1rem;
      }

      .dashboard-header {
        padding: 1.5rem;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .quick-actions {
        grid-template-columns: repeat(2, 1fr);
      }
    }
  </style>
</head>
<body>
  <?php include_once '../headerNav.php'; ?>

  <main class="container">
    <div class="dashboard-header">
      <h1 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($patient['first_name']); ?>! 👋</h1>
      <p class="dashboard-subtitle">Here's your health dashboard overview</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?php echo $upcomingCount; ?></div>
        <div class="stat-label">Upcoming Appointments</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?php echo $completedCount; ?></div>
        <div class="stat-label">Completed Visits</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-value"><?php echo $recordsCount; ?></div>
        <div class="stat-label">Medical Records</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📆</div>
        <div class="stat-value"><?php echo date('d'); ?></div>
        <div class="stat-label"><?php echo date('M Y'); ?></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Quick Actions</h2>
      </div>
      <div class="card-body">
        <div class="quick-actions">
          <a href="appointmentDashboard.php" class="action-card">
            <div class="action-icon">📅</div>
            <div class="action-title">Book Appointment</div>
          </a>
          <a href="symptomChecker.php" class="action-card" style="background: linear-gradient(135deg, #50C878, #27AE60);">
            <div class="action-icon">🔍</div>
            <div class="action-title">Check Symptoms</div>
          </a>
          <a href="medicalRecords.php" class="action-card" style="background: linear-gradient(135deg, #FF6B6B, #E74C3C);">
            <div class="action-icon">📋</div>
            <div class="action-title">View Records</div>
          </a>
          <a href="profile.php" class="action-card" style="background: linear-gradient(135deg, #F39C12, #E67E22);">
            <div class="action-icon">⚙️</div>
            <div class="action-title">Profile Settings</div>
          </a>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Recent Appointments</h2>
      </div>
      <?php if ($appointments): ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Specialization</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appointments as $a): ?>
                <?php
                  $statusMap = ['pending'=>'warning','confirmed'=>'info','completed'=>'success','cancelled'=>'danger'];
                  $badgeClass = $statusMap[$a['status']] ?? 'primary';
                ?>
                <tr>
                  <td><?php echo date('d M Y', strtotime($a['appointment_date'])); ?></td>
                  <td><?php echo date('h:i A', strtotime($a['appointment_time'])); ?></td>
                  <td>Dr. <?php echo htmlspecialchars($a['first_name'].' '.$a['last_name']); ?></td>
                  <td><?php echo htmlspecialchars($a['specialization']); ?></td>
                  <td><span class="badge badge-<?php echo $badgeClass; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                  <td>
                    <a class="btn btn-sm btn-primary" href="appointmentDashboard.php?view=<?php echo $a['appointment_id']; ?>">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-body" style="padding-top: 0;">
          <a href="appointmentDashboard.php" class="btn btn-outline">View All Appointments</a>
        </div>
      <?php else: ?>
        <div class="card-body">
          <div class="empty-state">
            <div class="empty-state-icon">📅</div>
            <p>No appointments found.</p>
            <a href="appointmentDashboard.php" class="btn btn-primary" style="margin-top: 1rem;">Book Your First Appointment</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Recent Medical Records</h2>
      </div>
      <div class="card-body">
        <?php if ($medRecords): ?>
          <?php foreach ($medRecords as $record): ?>
          <div class="record-preview">
            <div class="record-date">📅 <?php echo date('F d, Y', strtotime($record['visit_date'])); ?>
            <?php if ($record['d_fname']): ?>
              - Dr. <?php echo htmlspecialchars($record['d_fname'].' '.$record['d_lname']); ?>
            <?php endif; ?>
            </div>
            <div class="record-diagnosis"><?php echo nl2br(htmlspecialchars(substr($record['diagnosis'], 0, 150))); ?><?php echo strlen($record['diagnosis']) > 150 ? '...' : ''; ?></div>
          </div>
          <?php endforeach; ?>
          <a href="medicalRecords.php" class="btn btn-outline">View All Medical Records</a>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <p>No medical records yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </main>

  <script src="../script.js"></script>
</body>
</html>