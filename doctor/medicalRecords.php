<?php
require_once __DIR__ . '/../config.php';
requireRole('doctor');

$db = Database::getInstance()->getConnection();
$userId = getUserId();

$doctorStmt = $db->prepare("SELECT doctor_id FROM doctors WHERE user_id = ? LIMIT 1");
$doctorStmt->execute([$userId]);
$doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);
$doctorId = $doctor['doctor_id'] ?? 0;

$stmt = $db->prepare("SELECT mr.*, p.first_name AS p_fname, p.last_name AS p_lname 
                      FROM medical_records mr 
                      JOIN patients p ON mr.patient_id = p.patient_id 
                      ORDER BY mr.visit_date DESC LIMIT 50");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Medical Records - <?php echo SITE_NAME; ?></title>
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

    .page-header {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin-bottom: 2rem;
    }

    .page-title {
      font-size: 2rem;
      color: #1565c0;
      margin-bottom: 0.5rem;
    }

    .card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin-bottom: 2rem;
      overflow: hidden;
    }

    .card-header {
      padding: 1.5rem 2rem;
      border-bottom: 2px solid #e3f2fd;
    }

    .card-title {
      font-size: 1.5rem;
      color: #1565c0;
      font-weight: 600;
    }

    .card-body {
      padding: 2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-label {
      display: block;
      font-weight: 600;
      color: #424242;
      margin-bottom: 0.5rem;
      font-size: 0.95rem;
    }

    .form-control {
      width: 100%;
      padding: 0.85rem;
      border: 2px solid #e3f2fd;
      border-radius: 10px;
      font-size: 1rem;
      transition: all 0.3s;
      background: #fafafa;
    }

    .form-control:focus {
      outline: none;
      border-color: #42a5f5;
      background: white;
      box-shadow: 0 0 0 3px rgba(66, 165, 245, 0.1);
    }

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
    }

    .btn {
      padding: 1rem 2rem;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-primary {
      background: linear-gradient(135deg, #42a5f5, #1e88e5);
      color: white;
      box-shadow: 0 4px 15px rgba(66, 165, 245, 0.4);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #1e88e5, #1565c0);
      transform: translateY(-2px);
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
    }

    .table tr:hover {
      background: #f5f9ff;
    }

    .spinner {
      border: 4px solid #e3f2fd;
      border-top: 4px solid #42a5f5;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 2rem auto;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
      .container {
        padding: 1rem;
      }

      .card-body {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>

<?php include_once "../headerNav.php"; ?>

<main class="container">
  
  <div class="page-header">
    <h1 class="page-title">Medical Records</h1>
    <p style="color: #757575;">Add and manage patient medical records</p>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Add New Medical Record</h2>
    </div>

    <div class="card-body">
      <form id="recordForm">

        <input type="hidden" name="doctor_id" value="<?php echo $doctorId; ?>">

        <div class="form-group">
          <label class="form-label">Patient ID</label>
          <input type="number" name="patient_id" class="form-control" required placeholder="Enter patient ID">
        </div>

        <div class="form-group">
          <label class="form-label">Visit Date</label>
          <input type="date" name="visit_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Diagnosis</label>
          <textarea name="diagnosis" class="form-control" required placeholder="Enter diagnosis details"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Prescription</label>
          <textarea name="prescription" class="form-control" placeholder="Enter prescription details (optional)"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Additional Notes</label>
          <textarea name="notes" class="form-control" placeholder="Any additional notes or observations"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Add Medical Record</button>

      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Recent Medical Records</h2>
    </div>

    <div class="card-body" id="recordsContainer">
      <?php if ($records): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Visit Date</th>
              <th>Patient Name</th>
              <th>Diagnosis</th>
              <th>Prescription</th>
            </tr>
          </thead>
          <tbody id="recordsTable">

            <?php foreach ($records as $r): ?>
            <tr>
              <td><?php echo date('d M Y', strtotime($r['visit_date'])); ?></td>
              <td><?php echo htmlspecialchars($r['p_fname'].' '.$r['p_lname']); ?></td>
              <td><?php echo nl2br(htmlspecialchars(substr($r['diagnosis'],0,150))); ?><?php echo strlen($r['diagnosis']) > 150 ? '...' : ''; ?></td>
              <td><?php echo nl2br(htmlspecialchars(substr($r['prescription'] ?? 'N/A',0,100))); ?></td>
            </tr>
            <?php endforeach; ?>

          </tbody>
        </table>
      </div>

      <?php else: ?>
      <p style="color: #9e9e9e;">No medical records found.</p>
      <?php endif; ?>
    </div>
  </div>

</main>

<script src="../script.js"></script>

<script>
document.getElementById("recordForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';

    try {
        const res = await Ajax.post("../api/doctor/API_doctorAddRecords.php", formData, true);

        if (!res.success) {
            Utils.showAlert(res.message, "error");
            return;
        }

        Utils.showAlert("Medical record added successfully!", "success");

        loadRecords();
        this.reset();
        this.querySelector('input[name="visit_date"]').value = new Date().toISOString().split('T')[0];
    } catch (error) {
        Utils.showAlert("Failed to add record. Please try again.", "error");
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add Medical Record';
    }
});

async function loadRecords() {
    const container = document.getElementById("recordsContainer");
    container.innerHTML = '<div class="spinner"></div>';

    try {
        const res = await Ajax.get("../api/doctor/get_recent_records.php");

        if (res.success) {
            container.innerHTML = res.html;
        } else {
            container.innerHTML = "<p style='color: #9e9e9e;'>Failed to load records</p>";
        }
    } catch (error) {
        container.innerHTML = "<p style='color: #9e9e9e;'>Error loading records</p>";
    }
}
</script>

</body>
</html>