<?php
require_once __DIR__ . '/../config.php';
requireRole('patient');

$db = Database::getInstance()->getConnection();
$userId = getUserId();

// fetch user + patient
$stmt = $db->prepare("SELECT u.email, p.* FROM users u JOIN patients p ON u.user_id = p.user_id WHERE u.user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

$errors = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $blood = sanitizeInput($_POST['blood_type'] ?? '');
    $allergies = sanitizeInput($_POST['allergies'] ?? '');
    $emergency = sanitizeInput($_POST['emergency_contact'] ?? '');

    $upd = $db->prepare("UPDATE patients SET phone = ?, address = ?, blood_type = ?, allergies = ?, emergency_contact = ? WHERE user_id = ?");
    if ($upd->execute([$phone, $address, $blood, $allergies, $emergency, $userId])) {
        $success = 'Profile updated successfully!';
        // refresh data
        $stmt->execute([$userId]);
        $me = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $errors = 'Update failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="profile.css">
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
                    <h1 class="page-title">👤 My Profile</h1>
                    <p class="page-subtitle">Manage your personal information and preferences</p>
                </div>
            </div>

            <div class="card fade-in" style="animation-delay: 0.1s">
                <div class="card-header">
                    <h2 class="card-title">📋 Personal Information</h2>
                </div>
                <div class="card-body">
                    <div class="profile-section">
                        <div class="profile-row">
                            <span class="profile-label">Full Name:</span>
                            <span class="profile-value"><?php echo htmlspecialchars($me['first_name'].' '.$me['last_name']); ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="profile-label">Email:</span>
                            <span class="profile-value"><?php echo htmlspecialchars($me['email']); ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="profile-label">IC Number:</span>
                            <span class="profile-value"><?php echo htmlspecialchars($me['ic_number']); ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="profile-label">Date of Birth:</span>
                            <span class="profile-value"><?php echo date('F d, Y', strtotime($me['date_of_birth'])); ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="profile-label">Gender:</span>
                            <span class="profile-value"><?php echo ucfirst($me['gender']); ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="profile-label">Patient ID:</span>
                            <span class="profile-value">
                                <span class="info-badge"><?php echo $me['patient_id']; ?></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card fade-in" style="animation-delay: 0.2s">
                <div class="card-header">
                    <h2 class="card-title">✏️ Edit Contact & Medical Information</h2>
                </div>
                <div class="card-body">

                    <?php if ($errors): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($errors); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="post" id="profileForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">📞 Phone Number</label>
                                <input name="phone" class="form-control" type="tel" value="<?php echo htmlspecialchars($me['phone'] ?? ''); ?>" placeholder="e.g., 012-345-6789">
                            </div>

                            <div class="form-group">
                                <label class="form-label">🚨 Emergency Contact</label>
                                <input name="emergency_contact" class="form-control" type="tel" value="<?php echo htmlspecialchars($me['emergency_contact'] ?? ''); ?>" placeholder="e.g., 012-345-6789">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">🏠 Address</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Enter your complete address"><?php echo htmlspecialchars($me['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">🩸 Blood Type</label>
                                <select name="blood_type" class="form-control">
                                    <option value="">Select Blood Type</option>
                                    <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $b): ?>
                                        <option value="<?php echo $b; ?>" <?php echo (($me['blood_type'] ?? '') === $b) ? 'selected' : ''; ?>>
                                            <?php echo $b; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">⚠️ Allergies</label>
                            <textarea name="allergies" class="form-control" rows="3" placeholder="List any allergies or medical conditions"><?php echo htmlspecialchars($me['allergies'] ?? ''); ?></textarea>
                        </div>

                        <div class="btn-row">
                            <button class="btn btn-primary" type="submit">💾 Save Changes</button>
                            <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
                        </div>
                    </form>

                </div>
            </div>
        </main>
    </div>

    <script src="profile.js"></script>
</body>
</html>