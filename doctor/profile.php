<?php
require_once __DIR__ . '/../config.php';
requireRole('doctor');

$db = Database::getInstance()->getConnection();
$userId = getUserId();

$stmt = $db->prepare("SELECT d.*, u.email FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE u.user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) { 
    echo "Doctor record not found."; 
    exit; 
}

$errors = []; 
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $specialization = sanitizeInput($_POST['specialization'] ?? '');
    $qualifications = sanitizeInput($_POST['qualifications'] ?? '');
    $experience = intval($_POST['years_of_experience'] ?? 0);
    $fee = floatval($_POST['consultation_fee'] ?? 0);

    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First name and last name are required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($specialization)) {
        $errors[] = 'Specialization is required';
    }

    if (empty($errors)) {
        $upd = $db->prepare("UPDATE doctors SET first_name = ?, last_name = ?, phone = ?, specialization = ?, qualifications = ?, years_of_experience = ?, consultation_fee = ? WHERE user_id = ?");
        if ($upd->execute([$firstName, $lastName, $phone, $specialization, $qualifications, $experience, $fee, $userId])) {
            $success = 'Profile updated successfully!';
            $stmt->execute([$userId]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors[] = 'Update failed. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Doctor Profile - <?php echo SITE_NAME; ?></title>
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

    .profile-container {
      max-width: 900px;
      margin: 2rem auto;
      padding: 0 2rem;
    }

    .profile-header {
      background: linear-gradient(135deg, #42a5f5, #1e88e5);
      color: white;
      padding: 3rem 2rem;
      border-radius: 15px 15px 0 0;
      text-align: center;
      box-shadow: 0 4px 15px rgba(66, 165, 245, 0.3);
    }

    .profile-icon {
      width: 100px;
      height: 100px;
      background: white;
      color: #1e88e5;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      margin: 0 auto 1rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .profile-header h1 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    .profile-header p {
      opacity: 0.95;
      font-size: 1.1rem;
    }

    .card {
      background: white;
      border-radius: 0 0 15px 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .card-body {
      padding: 2.5rem;
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

    .form-control:disabled {
      background: #f5f5f5;
      color: #9e9e9e;
      cursor: not-allowed;
    }

    select.form-control {
      cursor: pointer;
    }

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
    }

    .form-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .btn {
      padding: 1rem 2rem;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      width: 100%;
    }

    .btn-primary {
      background: linear-gradient(135deg, #42a5f5, #1e88e5);
      color: white;
      box-shadow: 0 4px 15px rgba(66, 165, 245, 0.4);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #1e88e5, #1565c0);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(66, 165, 245, 0.5);
    }

    .alert {
      padding: 1rem 1.5rem;
      border-radius: 10px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .alert-error {
      background: #ffebee;
      border-left: 4px solid #f44336;
      color: #c62828;
    }

    .alert-success {
      background: #e8f5e9;
      border-left: 4px solid #4caf50;
      color: #2e7d32;
    }

    .info-badge {
      display: inline-block;
      background: #e3f2fd;
      color: #1565c0;
      padding: 0.4rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    @media (max-width: 768px) {
      .profile-container {
        padding: 0 1rem;
      }

      .card-body {
        padding: 1.5rem;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .profile-header h1 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <?php include_once "../headerNav.php"; ?>

  <main class="profile-container">
    <div class="profile-header">
      <div class="profile-icon">👨‍⚕️</div>
      <h1>Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></h1>
      <p><?php echo htmlspecialchars($doc['specialization']); ?></p>
    </div>

    <div class="card">
      <div class="card-body">
        <?php if ($errors): ?>
          <div class="alert alert-error">
            <span>⚠️</span>
            <div>
              <?php foreach($errors as $err): ?>
                <div><?php echo htmlspecialchars($err); ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert alert-success">
            <span>✓</span>
            <span><?php echo htmlspecialchars($success); ?></span>
          </div>
        <?php endif; ?>

        <form method="post">
          <div class="info-badge">Account Information</div>
          
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input class="form-control" value="<?php echo htmlspecialchars($doc['email']); ?>" disabled>
          </div>

          <div class="form-group">
            <label class="form-label">IC Number</label>
            <input class="form-control" value="<?php echo htmlspecialchars($doc['ic_number']); ?>" disabled>
          </div>

          <div class="form-group">
            <label class="form-label">Medical License Number</label>
            <input class="form-control" value="<?php echo htmlspecialchars($doc['license_number']); ?>" disabled>
          </div>

          <div class="info-badge" style="margin-top: 2rem;">Personal Information</div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input name="first_name" class="form-control" value="<?php echo htmlspecialchars($doc['first_name']); ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input name="last_name" class="form-control" value="<?php echo htmlspecialchars($doc['last_name']); ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input name="phone" type="tel" class="form-control" value="<?php echo htmlspecialchars($doc['phone'] ?? ''); ?>" required>
          </div>

          <div class="info-badge" style="margin-top: 2rem;">Professional Information</div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Specialization</label>
              <select name="specialization" class="form-control" required>
                <option value="">Select Specialization</option>
                <option value="General Practice" <?php echo ($doc['specialization'] ?? '') === 'General Practice' ? 'selected' : ''; ?>>General Practice</option>
                <option value="Cardiology" <?php echo ($doc['specialization'] ?? '') === 'Cardiology' ? 'selected' : ''; ?>>Cardiology</option>
                <option value="Dermatology" <?php echo ($doc['specialization'] ?? '') === 'Dermatology' ? 'selected' : ''; ?>>Dermatology</option>
                <option value="Pediatrics" <?php echo ($doc['specialization'] ?? '') === 'Pediatrics' ? 'selected' : ''; ?>>Pediatrics</option>
                <option value="Orthopedics" <?php echo ($doc['specialization'] ?? '') === 'Orthopedics' ? 'selected' : ''; ?>>Orthopedics</option>
                <option value="Neurology" <?php echo ($doc['specialization'] ?? '') === 'Neurology' ? 'selected' : ''; ?>>Neurology</option>
                <option value="Psychiatry" <?php echo ($doc['specialization'] ?? '') === 'Psychiatry' ? 'selected' : ''; ?>>Psychiatry</option>
                <option value="Other" <?php echo ($doc['specialization'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Years of Experience</label>
              <input name="years_of_experience" type="number" class="form-control" 
                     value="<?php echo htmlspecialchars($doc['years_of_experience'] ?? '0'); ?>" 
                     min="0" max="50">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Qualifications</label>
            <textarea name="qualifications" class="form-control" 
                      rows="3" placeholder="e.g., MBBS, MD, Certifications..."><?php echo htmlspecialchars($doc['qualifications'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Consultation Fee (RM)</label>
            <input name="consultation_fee" type="number" class="form-control" 
                   value="<?php echo htmlspecialchars($doc['consultation_fee'] ?? '0.00'); ?>" 
                   min="0" step="0.01" required>
          </div>

          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </main>

  <script src="../script.js"></script>
</body>
</html>