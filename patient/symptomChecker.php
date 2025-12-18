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

// Fetch recent symptom checks for this patient (if you have the table)
$recentChecks = [];
try {
    $checksStmt = $db->prepare("
        SELECT * FROM symptom_checks 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $checksStmt->execute([$userId]);
    $recentChecks = $checksStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
    error_log("Symptom checks table not available: " . $e->getMessage());
}

// Determine base URL
$baseUrl = defined('SITE_URL') ? SITE_URL : 'http://localhost/CarePlus';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Symptom Checker - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="symptomChecker.css">
</head>
<body>
    <!-- Include Header Navigation -->
    <?php include __DIR__ . '/headerNav.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <main class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <h1 class="page-title">🔍 AI Symptom Checker</h1>
                    <p class="page-subtitle">Get preliminary health assessment based on your symptoms</p>
                </div>
                <div class="header-icon">🩺</div>
            </div>

            <!-- Disclaimer Alert -->
            <div class="alert alert-warning">
                <div class="alert-icon">⚠️</div>
                <div class="alert-content">
                    <strong>Medical Disclaimer:</strong> This is an AI-powered preliminary assessment tool. It does not replace professional medical advice, diagnosis, or treatment. Always consult a qualified healthcare provider for accurate medical guidance.
                </div>
            </div>

            <!-- Main Symptom Checker Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span>📝</span> Describe Your Symptoms
                    </h2>
                </div>
                <div class="card-body">
                    <form id="symptomForm">
                        <!-- Common Symptoms Selection -->
                        <div class="form-group">
                            <label class="form-label">
                                <span>🏥</span> Quick Select Common Symptoms
                            </label>
                            <p class="form-helper">Click on symptoms you're experiencing (optional)</p>
                            <div class="common-symptoms" id="commonSymptoms">
                                <div class="symptom-chip" data-symptom="fever">🌡️ Fever</div>
                                <div class="symptom-chip" data-symptom="cough">😷 Cough</div>
                                <div class="symptom-chip" data-symptom="headache">🤕 Headache</div>
                                <div class="symptom-chip" data-symptom="sore throat">😣 Sore Throat</div>
                                <div class="symptom-chip" data-symptom="fatigue">😴 Fatigue</div>
                                <div class="symptom-chip" data-symptom="nausea">🤢 Nausea</div>
                                <div class="symptom-chip" data-symptom="dizziness">😵 Dizziness</div>
                                <div class="symptom-chip" data-symptom="body ache">💪 Body Ache</div>
                                <div class="symptom-chip" data-symptom="runny nose">👃 Runny Nose</div>
                                <div class="symptom-chip" data-symptom="shortness of breath">😮‍💨 Shortness of Breath</div>
                                <div class="symptom-chip" data-symptom="chest pain">💔 Chest Pain</div>
                                <div class="symptom-chip" data-symptom="stomach pain">🤰 Stomach Pain</div>
                                <div class="symptom-chip" data-symptom="loss of appetite">🍽️ Loss of Appetite</div>
                                <div class="symptom-chip" data-symptom="vomiting">🤮 Vomiting</div>
                                <div class="symptom-chip" data-symptom="diarrhea">🚽 Diarrhea</div>
                                <div class="symptom-chip" data-symptom="rash">🔴 Rash</div>
                            </div>
                        </div>

                        <!-- Detailed Symptoms Description -->
                        <div class="form-group">
                            <label class="form-label" for="symptoms">
                                <span>✍️</span> Describe Your Symptoms in Detail *
                            </label>
                            <p class="form-helper">Be as specific as possible. Include severity, location, and any patterns you've noticed.</p>
                            <textarea 
                                id="symptoms" 
                                class="form-control" 
                                rows="6" 
                                required 
                                placeholder="Example: I've been experiencing a persistent dry cough for the last 2 days. The cough is worse at night and I have a mild fever around 38°C. I also feel very tired and have body aches..."></textarea>
                            <div class="char-counter">
                                <span id="charCount">0</span> / 1000 characters
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="duration">
                                    <span>⏱️</span> How Long Have You Had These Symptoms? *
                                </label>
                                <input 
                                    id="duration" 
                                    class="form-control" 
                                    placeholder="e.g., 2 days, 1 week, few hours" 
                                    required>
                            </div>

                            <!-- Age -->
                            <div class="form-group">
                                <label class="form-label" for="age">
                                    <span>👤</span> Your Age (Optional)
                                </label>
                                <input 
                                    id="age" 
                                    type="number" 
                                    class="form-control" 
                                    placeholder="Age in years" 
                                    min="1" 
                                    max="120">
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="form-group">
                            <label class="form-label" for="additional">
                                <span>📋</span> Additional Information (Optional)
                            </label>
                            <p class="form-helper">Any relevant medical history, allergies, or current medications</p>
                            <textarea 
                                id="additional" 
                                class="form-control" 
                                rows="3" 
                                placeholder="e.g., I have asthma, allergic to penicillin, currently taking blood pressure medication..."></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span>🔍</span> Analyze Symptoms
                            </button>
                            <button type="button" class="btn btn-outline" onclick="resetForm()">
                                <span>🔄</span> Reset Form
                            </button>
                        </div>
                    </form>

                    <!-- Results Container -->
                    <div id="resultsContainer"></div>
                </div>
            </div>

            <!-- Recent Checks -->
            <?php if (!empty($recentChecks)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span>📜</span> Recent Symptom Checks
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="recent-checks-list">
                            <?php foreach ($recentChecks as $check): ?>
                                <div class="recent-check-item">
                                    <div class="check-date">
                                        <?php echo date('M d, Y g:i A', strtotime($check['created_at'])); ?>
                                    </div>
                                    <div class="check-symptoms">
                                        <?php echo htmlspecialchars(substr($check['symptoms'], 0, 100)); ?>
                                        <?php echo strlen($check['symptoms']) > 100 ? '...' : ''; ?>
                                    </div>
                                    <button class="btn btn-outline btn-sm" onclick="viewCheck(<?php echo $check['id']; ?>)">
                                        View Details
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Health Tips -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span>💡</span> When to Seek Immediate Medical Attention
                    </h2>
                </div>
                <div class="card-body">
                    <div class="emergency-guidelines">
                        <div class="guideline-item">
                            <div class="guideline-icon">🚨</div>
                            <div class="guideline-content">
                                <h4>Chest Pain or Pressure</h4>
                                <p>Especially if spreading to arms, jaw, or back</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <div class="guideline-icon">😮‍💨</div>
                            <div class="guideline-content">
                                <h4>Severe Breathing Difficulty</h4>
                                <p>Shortness of breath that's getting worse</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <div class="guideline-icon">🤕</div>
                            <div class="guideline-content">
                                <h4>Sudden Severe Headache</h4>
                                <p>Worst headache of your life</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <div class="guideline-icon">😵</div>
                            <div class="guideline-content">
                                <h4>Loss of Consciousness</h4>
                                <p>Fainting, confusion, or inability to wake up</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <div class="guideline-icon">🩸</div>
                            <div class="guideline-content">
                                <h4>Severe Bleeding</h4>
                                <p>That doesn't stop with pressure</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <div class="guideline-icon">⚡</div>
                            <div class="guideline-content">
                                <h4>Sudden Paralysis</h4>
                                <p>Weakness in face, arm, or leg</p>
                            </div>
                        </div>
                    </div>
                    <div class="emergency-footer">
                        <strong>Emergency Contact:</strong> 999 | 
                        <a href="appointment.php" class="link-primary">Book Non-Emergency Appointment</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="symptomChecker.js"></script>
</body>
</html>