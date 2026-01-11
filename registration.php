<?php
require_once __DIR__ . '/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(getUserType() . '/dashboard.php');
}

// Get registration type from URL parameter or default to patient
$regType = $_GET['type'] ?? 'patient';
if (!in_array($regType, ['doctor', 'patient', 'admin'])) {
    $regType = 'patient';
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postRegType = $_POST['reg_type'] ?? 'patient';
    
    // Common fields
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    // IC Number is now required for ALL users
    $icNumber = sanitizeInput($_POST['ic_number'] ?? '');

    // Security Question fields
    $securityQuestion = sanitizeInput($_POST['security_question'] ?? '');
    $securityAnswer = sanitizeInput($_POST['security_answer'] ?? '');
    
    // Common validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    // Password Validation
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
        
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter (A-Z)';
    }
        
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter (a-z)';
    }
        
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number (0-9)';
    }
        
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
        $errors[] = 'Password must contain at least one special character (!@#$%^&*...)';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First name and last name are required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }

    // Validate IC Number for EVERYONE
    if (empty($icNumber) || strlen($icNumber) < 12) {
        $errors[] = 'Valid IC number is required (12 digits)';
    }
    
    // Security question validation
    if (empty($securityQuestion)) {
        $errors[] = 'Security question is required';
    }
    
    if (empty($securityAnswer) || strlen($securityAnswer) < 3) {
        $errors[] = 'Security answer must be at least 3 characters';
    }
    
    // Fields specific to Doctor and Patient 
    if ($postRegType !== 'admin') {
        $dob = sanitizeInput($_POST['date_of_birth'] ?? '');
        $gender = sanitizeInput($_POST['gender'] ?? '');
        
        if (empty($dob)) {
            $errors[] = 'Date of birth is required';
        }
        if (empty($gender)) {
            $errors[] = 'Gender is required';
        }
    }
    
    if ($postRegType === 'doctor') {
        // Doctor-specific fields
        $specialization = sanitizeInput($_POST['specialization'] ?? '');
        $licenseNumber = sanitizeInput($_POST['license_number'] ?? '');
        $qualifications = sanitizeInput($_POST['qualifications'] ?? '');
        $experience = sanitizeInput($_POST['experience_years'] ?? '');
        $consultationFee = sanitizeInput($_POST['consultation_fee'] ?? '');
        $doctorCode = sanitizeInput($_POST['doctor_code'] ?? '');
        
        // Availability fields
        $availableDays = $_POST['available_days'] ?? [];
        $startTime = sanitizeInput($_POST['start_time'] ?? '');
        $endTime = sanitizeInput($_POST['end_time'] ?? '');
        
        if (empty($specialization)) {
            $errors[] = 'Specialization is required';
        }
        
        if (empty($licenseNumber)) {
            $errors[] = 'Medical license number is required';
        }
        
        if (empty($qualifications)) {
            $errors[] = 'Qualifications are required';
        }
        
        // Validate availability
        if (empty($availableDays)) {
            $errors[] = 'Please select at least one available day';
        }
        
        if (empty($startTime) || empty($endTime)) {
            $errors[] = 'Please specify working hours';
        }
        
        if (!empty($startTime) && !empty($endTime) && $startTime >= $endTime) {
            $errors[] = 'End time must be after start time';
        }
        
        // Verify doctor registration code
        if ($doctorCode !== 'DOCTOR2025SECURE') {
            $errors[] = 'Invalid doctor registration code';
        }
        
    } elseif ($postRegType === 'patient') {
        // Patient-specific fields
        $address = sanitizeInput($_POST['address'] ?? '');
        $bloodType = sanitizeInput($_POST['blood_type'] ?? '');
        $allergies = sanitizeInput($_POST['allergies'] ?? '');
        $emergencyContact = sanitizeInput($_POST['emergency_contact'] ?? '');
    } elseif ($postRegType === 'admin') {
        // Admin-specific fields
        $department = sanitizeInput($_POST['department'] ?? '');
        $adminCode = sanitizeInput($_POST['admin_code'] ?? '');
        
        if (empty($department)) {
            $errors[] = 'Department is required';
        }
        
        // Verify admin registration code
        if ($adminCode !== 'ADMIN2025SECRET') {
            $errors[] = 'Invalid admin registration code';
        }
    }
    
    if (empty($errors)) {
        $conn = Database::getInstance()->getConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Email already registered';
            $stmt->close();
        } else {
            $stmt->close();
            
            // Check if IC number already exists in the appropriate table
            if ($postRegType === 'doctor') {
                $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE ic_number = ?");
                $stmt->bind_param("s", $icNumber);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) $errors[] = 'IC number already registered';
                $stmt->close();
            } elseif ($postRegType === 'patient') {
                $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE ic_number = ?");
                $stmt->bind_param("s", $icNumber);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) $errors[] = 'IC number already registered';
                $stmt->close();
            } elseif ($postRegType === 'admin') {
                $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE ic_number = ?");
                $stmt->bind_param("s", $icNumber);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) $errors[] = 'IC number already registered';
                $stmt->close();
            }
            
            if (empty($errors)) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Hash security answer
                    $securityAnswerHash = password_hash(strtolower(trim($securityAnswer)), PASSWORD_DEFAULT);
                    
                    // Insert into users table
                    $passwordHash = hashPassword($password);
                    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, user_type, security_question, security_answer) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $email, $passwordHash, $postRegType, $securityQuestion, $securityAnswerHash);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create user account: " . $stmt->error);
                    }
                    
                    $userId = $conn->insert_id;
                    $stmt->close();
                    
                    if ($postRegType === 'doctor') {
                        $availableDaysJson = json_encode($availableDays);
                        $availableHours = $startTime . '-' . $endTime;
                        $experienceInt = (int)($experience ?? 0);
                        $consultationFeeFloat = (float)($consultationFee ?? 0);
                        
                        $stmt = $conn->prepare("
                            INSERT INTO doctors (
                                user_id, ic_number, date_of_birth, gender, first_name, last_name, 
                                phone, specialization, license_number, qualifications,
                                experience_years, consultation_fee, available_days, available_hours
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param(
                            "isssssssssidss",
                            $userId, $icNumber, $dob, $gender, $firstName, $lastName,
                            $phone, $specialization, $licenseNumber, $qualifications,
                            $experienceInt, $consultationFeeFloat, $availableDaysJson, $availableHours
                        );
                        if (!$stmt->execute()) throw new Exception("Failed to create doctor profile: " . $stmt->error);
                        $stmt->close();
                        
                    } elseif ($postRegType === 'patient') {
                        $stmt = $conn->prepare("
                            INSERT INTO patients (
                                user_id, first_name, last_name, ic_number, date_of_birth, 
                                gender, phone, address, blood_type, allergies, emergency_contact
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param(
                            "issssssssss",
                            $userId, $firstName, $lastName, $icNumber, $dob,
                            $gender, $phone, $address, $bloodType, $allergies, $emergencyContact
                        );
                        if (!$stmt->execute()) throw new Exception("Failed to create patient profile: " . $stmt->error);
                        $stmt->close();
                        
                    } elseif ($postRegType === 'admin') {
                        $stmt = $conn->prepare("
                            INSERT INTO admins (
                                user_id, first_name, last_name, ic_number, phone, department
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param(
                            "isssss",
                            $userId, $firstName, $lastName, $icNumber, $phone, $department
                        );
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to create admin profile: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    $success = 'Registration successful! You can now login.';
                    $_POST = [];
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $errors[] = 'Registration failed. Please try again.';
                    logError('Registration error: ' . $e->getMessage());
                }
            }
        }
    }
}

// Predefined security questions
$securityQuestions = [
    "What was the name of your first pet?",
    "What is your mother's maiden name?",
    "What city were you born in?",
    "What was the name of your elementary school?",
    "What is your favorite book?",
    "What was your childhood nickname?",
    "What is the name of your favorite teacher?",
    "What street did you grow up on?",
    "What is your father's middle name?",
    "What was the make of your first car?"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="registration.css">
</head>
<body>

    <div class="register-container">
        <div class="back-home">
            <a href="index.php">← Back to Home</a>
        </div>

        <div class="reg-type-toggle">
            <button type="button" class="toggle-btn <?php echo $regType === 'patient' ? 'active' : ''; ?>" 
                    onclick="window.location.href='registration.php?type=patient'">
                👤 Patient
            </button>
            <button type="button" class="toggle-btn <?php echo $regType === 'doctor' ? 'active' : ''; ?>" 
                    onclick="window.location.href='registration.php?type=doctor'">
                👨‍⚕️ Doctor
            </button>
            <button type="button" class="toggle-btn admin-btn <?php echo $regType === 'admin' ? 'active' : ''; ?>" 
                    onclick="window.location.href='registration.php?type=admin'">
                🔐 Admin
            </button>
        </div>

        <div class="register-header">
            <div class="logo-icon <?php echo $regType === 'admin' ? 'admin' : ''; ?>">
                <?php 
                    if ($regType === 'doctor') echo '👨‍⚕️';
                    elseif ($regType === 'admin') echo '🔐';
                    else echo '👤';
                ?>
            </div>
            <h1 class="register-title">
                <?php 
                    if ($regType === 'doctor') echo 'Doctor Registration';
                    elseif ($regType === 'admin') echo 'Admin Registration';
                    else echo 'Patient Registration';
                ?>
            </h1>
            <p class="register-subtitle">
                <?php 
                    if ($regType === 'doctor') echo 'Join our network of healthcare professionals';
                    elseif ($regType === 'admin') echo 'System administrator account registration';
                    else echo 'Create your account to start booking appointments';
                ?>
            </p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>⚠️ Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="login.php?type=<?php echo $regType; ?>" class="btn btn-primary" style="max-width: 300px; margin: 0 auto;">Go to Login →</a>
            </div>
        <?php else: ?>
        
        <?php if ($regType === 'admin' || $regType === 'doctor'): ?>
        <div class="info-box">
            <strong>⚠️ <?php echo ucfirst($regType); ?> Registration Notice:</strong> This registration requires a valid <?php echo $regType; ?> code. Please contact the system administrator to obtain the registration code.
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <input type="hidden" name="reg_type" value="<?php echo htmlspecialchars($regType); ?>">
            
            <h3 class="section-title">Account Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           placeholder="your.email@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" 
                            minlength="8" placeholder="Minimum 8 characters" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')" title="Show/Hide Password">
                            👁️
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" minlength="6" placeholder="Re-enter password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')" title="Show/Hide Password">
                            👁️
                        </button>
                    </div>
                </div>
            </div>
            
            <h3 class="section-title">🔐 Account Recovery Setup</h3>
            <div class="info-box" style="background: #dbeafe; border-left-color: #3b82f6;">
                <strong>📌 Important:</strong> This security question will help you recover your account if you forget your password. Choose a question only you can answer.
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required" for="security_question">Security Question</label>
                    <select id="security_question" name="security_question" class="form-control" required>
                        <option value="">Select a security question</option>
                        <?php foreach ($securityQuestions as $question): ?>
                        <option value="<?php echo htmlspecialchars($question); ?>" 
                                <?php echo ($_POST['security_question'] ?? '') === $question ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($question); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="security_answer">Security Answer</label>
                    <input type="text" id="security_answer" name="security_answer" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['security_answer'] ?? ''); ?>" 
                           placeholder="Your answer (case-insensitive)" 
                           minlength="3" required>
                    <small style="color: #64748b; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                        💡 Tip: Choose an answer you'll remember but others won't guess
                    </small>
                </div>
            </div>
            
            <h3 class="section-title">Personal Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                           placeholder="John" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                           placeholder="Doe" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           placeholder="0123456789"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="ic_number">IC Number (MyKad)</label>
                    <input type="text" id="ic_number" name="ic_number" class="form-control" 
                           placeholder="000000000000" maxlength="12"
                           value="<?php echo htmlspecialchars($_POST['ic_number'] ?? ''); ?>" required>
                </div>

                <?php if ($regType !== 'admin'): ?>
                <div class="form-group">
                    <label class="form-label required" for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="gender">Gender</label>
                    <select id="gender" name="gender" class="form-control" required>
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($regType === 'doctor'): ?>
            <!-- Doctor-specific fields -->
            <h3 class="section-title">Professional Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required" for="specialization">Specialization</label>
                    <select id="specialization" name="specialization" class="form-control" required>
                        <option value="">Select Specialization</option>
                        <option value="General Practitioner" <?php echo ($_POST['specialization'] ?? '') === 'General Practitioner' ? 'selected' : ''; ?>>General Practitioner</option>
                        <option value="Cardiologist" <?php echo ($_POST['specialization'] ?? '') === 'Cardiologist' ? 'selected' : ''; ?>>Cardiologist</option>
                        <option value="Dermatologist" <?php echo ($_POST['specialization'] ?? '') === 'Dermatologist' ? 'selected' : ''; ?>>Dermatologist</option>
                        <option value="Pediatrician" <?php echo ($_POST['specialization'] ?? '') === 'Pediatrician' ? 'selected' : ''; ?>>Pediatrician</option>
                        <option value="Orthopedic" <?php echo ($_POST['specialization'] ?? '') === 'Orthopedic' ? 'selected' : ''; ?>>Orthopedic</option>
                        <option value="Neurologist" <?php echo ($_POST['specialization'] ?? '') === 'Neurologist' ? 'selected' : ''; ?>>Neurologist</option>
                        <option value="Psychiatrist" <?php echo ($_POST['specialization'] ?? '') === 'Psychiatrist' ? 'selected' : ''; ?>>Psychiatrist</option>
                        <option value="Ophthalmologist" <?php echo ($_POST['specialization'] ?? '') === 'Ophthalmologist' ? 'selected' : ''; ?>>Ophthalmologist</option>
                        <option value="ENT Specialist" <?php echo ($_POST['specialization'] ?? '') === 'ENT Specialist' ? 'selected' : ''; ?>>ENT Specialist</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="license_number">Medical License Number</label>
                    <input type="text" id="license_number" name="license_number" class="form-control"
                        value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>" 
                        placeholder="e.g., MMC12345" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="experience_years">Years of Experience</label>
                    <input type="number" id="experience_years" name="experience_years" class="form-control" 
                        min="0" max="50" placeholder="0"
                        value="<?php echo htmlspecialchars($_POST['experience_years'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="consultation_fee">Consultation Fee (RM)</label>
                    <input type="number" id="consultation_fee" name="consultation_fee" class="form-control" 
                        min="0" step="0.01" placeholder="0.00"
                        value="<?php echo htmlspecialchars($_POST['consultation_fee'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required" for="qualifications">Qualifications</label>
                <textarea id="qualifications" name="qualifications" class="form-control" 
                        rows="3" placeholder="e.g., MBBS, MD, Fellowship details..." required><?php echo htmlspecialchars($_POST['qualifications'] ?? ''); ?></textarea>
            </div>

            <h3 class="section-title">Availability Schedule</h3>

            <div class="form-group">
                <label class="form-label required">Available Days</label>
                <div class="days-checkbox-group">
                    <?php 
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    $selectedDays = $_POST['available_days'] ?? [];
                    foreach ($days as $day): 
                    ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="available_days[]" value="<?php echo $day; ?>" 
                            <?php echo in_array($day, $selectedDays) ? 'checked' : ''; ?>>
                        <span><?php echo $day; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required" for="start_time">Working Hours Start</label>
                    <input type="time" id="start_time" name="start_time" class="form-control" 
                        value="<?php echo htmlspecialchars($_POST['start_time'] ?? '09:00'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="end_time">Working Hours End</label>
                    <input type="time" id="end_time" name="end_time" class="form-control" 
                        value="<?php echo htmlspecialchars($_POST['end_time'] ?? '17:00'); ?>" required>
                </div>
            </div>

            <h3 class="section-title">🔐 Doctor Verification</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required" for="doctor_code">Doctor Registration Code</label>
                    <div class="password-wrapper">
                        <input type="password" id="doctor_code" name="doctor_code" class="form-control"
                            placeholder="Enter doctor registration code" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('doctor_code')" 
                                title="Show/Hide Code">
                            👁️
                        </button>
                    </div>
                    <small style="color: #64748b; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                        Contact system administrator for the registration code
                    </small>
                </div>
            </div>

            <?php elseif ($regType === 'patient'): ?>
            <!-- Patient-specific fields -->
            <h3 class="section-title">Medical & Contact Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="blood_type">Blood Type</label>
                    <select id="blood_type" name="blood_type" class="form-control">
                        <option value="">Select Blood Type</option>
                        <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                        <option value="<?php echo $bt; ?>" <?php echo ($_POST['blood_type'] ?? '') === $bt ? 'selected' : ''; ?>><?php echo $bt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="emergency_contact">Emergency Contact Number</label>
                    <input type="tel" id="emergency_contact" name="emergency_contact" 
                           class="form-control" placeholder="0123456789"
                           value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Home Address</label>
                <textarea id="address" name="address" class="form-control" 
                          rows="3" placeholder="Enter your complete address..."><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="allergies">Known Allergies</label>
                <textarea id="allergies" name="allergies" class="form-control" 
                          rows="2" placeholder="List any known allergies to medications, food, etc..."><?php echo htmlspecialchars($_POST['allergies'] ?? ''); ?></textarea>
            </div>
            
            <?php elseif ($regType === 'admin'): ?>
            <!-- Admin-specific fields -->
            <h3 class="section-title">Administrative Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required" for="department">Department</label>
                    <select id="department" name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <option value="IT & Systems" <?php echo ($_POST['department'] ?? '') === 'IT & Systems' ? 'selected' : ''; ?>>IT & Systems</option>
                        <option value="Patient Services" <?php echo ($_POST['department'] ?? '') === 'Patient Services' ? 'selected' : ''; ?>>Patient Services</option>
                        <option value="Medical Records" <?php echo ($_POST['department'] ?? '') === 'Medical Records' ? 'selected' : ''; ?>>Medical Records</option>
                        <option value="Billing & Finance" <?php echo ($_POST['department'] ?? '') === 'Billing & Finance' ? 'selected' : ''; ?>>Billing & Finance</option>
                        <option value="Human Resources" <?php echo ($_POST['department'] ?? '') === 'Human Resources' ? 'selected' : ''; ?>>Human Resources</option>
                        <option value="Operations" <?php echo ($_POST['department'] ?? '') === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                        <option value="Security" <?php echo ($_POST['department'] ?? '') === 'Security' ? 'selected' : ''; ?>>Security</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required" for="admin_code">Admin Registration Code</label>
                    <div class="password-wrapper">
                        <input type="password" id="admin_code" name="admin_code" class="form-control"
                               placeholder="Enter admin registration code" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('admin_code')" title="Show/Hide Code">
                            👁️
                        </button>
                    </div>
                    <small style="color: #64748b; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                        Contact system administrator for the registration code
                    </small>
                </div>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary">
                Create Account
            </button>
        </form>
        
        <?php endif; ?>
        
        <div class="login-link">
            Already have an account? <a href="login.php?type=<?php echo $regType; ?>">Login here</a>
        </div>
    </div>
    
    <!-- Pass session info to JavaScript for chatbot -->
    <script>
        window.session_id_php = "<?php echo session_id(); ?>";
        window.patient_id_php = <?php echo isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;
    </script>

    <script src="registration.js"></script>
</body>
</html>