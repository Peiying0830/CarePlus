<?php
// START SESSION FIRST
session_start();

require_once __DIR__ . '/config.php';

$errors = [];
$success = '';
$step = $_GET['step'] ?? 'verify'; // verify, security, reset, success

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_identity') {
        $email = sanitizeInput($_POST['email'] ?? '');
        $icNumber = sanitizeInput($_POST['ic_number'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required';
        }
        
        if (empty($icNumber)) {
            $errors[] = 'IC number is required';
        }
        
        if (empty($phone)) {
            $errors[] = 'Phone number is required';
        }
        
        if (empty($errors)) {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            try {
                // Check in users table and corresponding profile table
                $stmt = $conn->prepare("
                    SELECT u.user_id, u.email, u.user_type, u.security_question, u.security_answer
                    FROM users u
                    LEFT JOIN doctors d ON u.user_id = d.user_id
                    LEFT JOIN patients p ON u.user_id = p.user_id
                    LEFT JOIN admins a ON u.user_id = a.user_id
                    WHERE u.email = ? 
                    AND (
                        (d.ic_number = ? AND d.phone = ?) OR
                        (p.ic_number = ? AND p.phone = ?) OR
                        (u.user_type = 'admin' AND a.phone = ?)
                    )
                ");
                
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                
                $stmt->bind_param("ssssss", $email, $icNumber, $phone, $icNumber, $phone, $phone);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
                
                if ($user) {
                    // Store user info in session for next step
                    $_SESSION['reset_user_id'] = $user['user_id'];
                    $_SESSION['reset_email'] = $user['email'];
                    $_SESSION['security_question'] = $user['security_question'];
                    $_SESSION['security_answer_hash'] = $user['security_answer'];
                    $_SESSION['reset_attempts'] = 0;
                    
                    header('Location: forgotPassword.php?step=security');
                    exit;
                } else {
                    $errors[] = 'Identity verification failed. Please check your information and try again.';
                }
            } catch (Exception $e) {
                $errors[] = 'An error occurred. Please try again later.';
                logError('Identity verification error: ' . $e->getMessage());
            }
        }
    }
    
    if ($action === 'answer_security') {
        if (!isset($_SESSION['reset_user_id'])) {
            header('Location: forgotPassword.php?step=verify');
            exit;
        }
        
        $securityAnswer = sanitizeInput($_POST['security_answer'] ?? '');
        
        if (empty($securityAnswer)) {
            $errors[] = 'Security answer is required';
        } else {
            // Check attempts
            if ($_SESSION['reset_attempts'] >= 3) {
                $errors[] = 'Too many failed attempts. Please start over.';
                unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['security_question'], $_SESSION['security_answer_hash'], $_SESSION['reset_attempts']);
            } else {
                // Verify security answer (case-insensitive)
                $isCorrect = password_verify(strtolower(trim($securityAnswer)), $_SESSION['security_answer_hash']);
                
                if ($isCorrect) {
                    $_SESSION['security_verified'] = true;
                    header('Location: forgotPassword.php?step=reset');
                    exit;
                } else {
                    $_SESSION['reset_attempts']++;
                    $remaining = 3 - $_SESSION['reset_attempts'];
                    $errors[] = "Incorrect answer. {$remaining} attempts remaining.";
                }
            }
        }
    }
    
    if ($action === 'reset_password') {
        if (!isset($_SESSION['security_verified']) || !$_SESSION['security_verified']) {
            header('Location: forgotPassword.php?step=verify');
            exit;
        }
        
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one UPPERCASE letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number (0-9)';
        } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $errors[] = 'Password must contain at least one special character (!@#$%^&...)';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match';
        }
        
        if (empty($errors)) {
            $userId = $_SESSION['reset_user_id'];
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            try {
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                
                $stmt->bind_param("si", $hashedPassword, $userId);
                $stmt->execute();
                $stmt->close();
                
                // Clear all session data
                unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['security_question'], $_SESSION['security_answer_hash'], $_SESSION['security_verified'], $_SESSION['reset_attempts']);
                
                header('Location: forgotPassword.php?step=success');
                exit;
            } catch (Exception $e) {
                $errors[] = 'Failed to update password. Please try again.';
                logError('Password reset error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery - <?= SITE_NAME; ?></title>
    <link rel="stylesheet" href="forgotPassword.css">
</head>
<body>

<div class="reset-container">

    <?php if ($step !== 'success'): ?>
    <div class="back-link">
        <a href="login.php">← Back to Login</a>
    </div>
    <?php endif; ?>

    <div class="reset-header">
        <div class="logo-icon">🔐</div>
        <h1 class="reset-title"><?= SITE_NAME; ?></h1>
        <p class="reset-subtitle">Account Recovery System</p>
    </div>

    <?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <span class="icon">✅</span>
        <div>
            <strong>Success!</strong><br>
            <?= htmlspecialchars($success); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>⚠️ Error</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($step === 'verify'): ?>
        <!-- Step 1: Identity Verification -->
        <div class="step-header">
            <h2 class="step-title">Verify Your Identity</h2>
            <p class="step-subtitle">Enter your account details to begin the recovery process</p>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="verify_identity">
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       placeholder="your.email@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                       required autofocus>
                <span class="error-message" id="email-error"></span>
            </div>

            <div class="form-group">
                <label class="form-label">IC Number (MyKad)</label>
                <input type="text" name="ic_number" id="ic_number" class="form-control"
                       placeholder="000000000000" maxlength="12"
                       value="<?= htmlspecialchars($_POST['ic_number'] ?? ''); ?>"
                       required>
                <small style="color: #64748b; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                    Enter your 12-digit IC number registered with your account
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" id="phone" class="form-control"
                       placeholder="0123456789"
                       value="<?= htmlspecialchars($_POST['phone'] ?? ''); ?>"
                       required>
                <small style="color: #64748b; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                    Enter your phone number registered with your account
                </small>
            </div>

            <button type="submit" class="btn btn-warning">
                Continue to Security Question →
            </button>
        </form>

    <?php elseif ($step === 'security'): ?>
        <!-- Step 2: Security Question -->
        <div class="step-header">
            <h2 class="step-title">🔒 Step 2: Answer Security Question</h2>
            <p class="step-subtitle">Answer the question you set during registration</p>
        </div>

        <div class="security-question-box">
            <div class="question-icon">❓</div>
            <p class="security-question-text">
                <?= htmlspecialchars($_SESSION['security_question'] ?? 'Security question not found'); ?>
            </p>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="answer_security">
            
            <div class="form-group">
                <label class="form-label">Your Answer</label>
                <input type="text" name="security_answer" class="form-control"
                       placeholder="Enter your answer (case-insensitive)"
                       required autofocus>
                <small style="color: #64748b; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                    💡 Answers are not case-sensitive
                </small>
            </div>

            <?php if (isset($_SESSION['reset_attempts']) && $_SESSION['reset_attempts'] > 0): ?>
            <div class="alert" style="background: #fef3c7; border-left-color: #f59e0b; margin-bottom: 1.5rem;">
                <strong>⚠️ Warning:</strong> You have <?= 3 - $_SESSION['reset_attempts']; ?> attempts remaining
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-warning">
                Verify Answer →
            </button>
        </form>

    <?php elseif ($step === 'reset'): ?>
        <!-- Step 3: Reset Password -->
        <div class="step-header">
            <h2 class="step-title">🔐 Step 3: Create New Password</h2>
            <p class="step-subtitle">Choose a strong password to secure your account</p>
        </div>

        <form method="POST" id="resetForm">
            <input type="hidden" name="action" value="reset_password">
            
            <div class="form-group">
                <label class="form-label">New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" 
                           class="form-control" required minlength="6"
                           placeholder="Minimum 6 characters">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')" title="Show/Hide Password">
                        👁️
                    </button>
                </div>
                <span class="error-message" id="password-error"></span>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" 
                           class="form-control" required minlength="6"
                           placeholder="Re-enter your password">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')" title="Show/Hide Password">
                        👁️
                    </button>
                </div>
                <span class="error-message" id="confirm-error"></span>
            </div>

            <div class="strength-indicator" id="strengthIndicator">
                <div class="strength-header">
                    <span>🔒</span>
                    <span class="strength-label">PASSWORD STRENGTH</span>
                </div>
                <p class="strength-text">Enter a password to see strength analysis...</p>
            </div>

            <button type="submit" class="btn btn-warning">
                Reset Password
            </button>
        </form>

    <?php elseif ($step === 'success'): ?>
        <!-- Step 4: Success -->
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h2 class="success-title">Password Reset Successful!</h2>
            <p class="success-subtitle">Your password has been updated successfully. You can now log in with your new password.</p>
            <a href="login.php" class="btn btn-dark">
                Go to Login →
            </a>
        </div>
    <?php endif; ?>

    <?php if ($step !== 'success'): ?>
    <div class="divider"><span>OR</span></div>

    <div class="links-section">
        <div class="link-item">Remembered your password? <a href="login.php">Login</a></div>
        <div class="link-item">Don't have an account? <a href="registration.php">Register</a></div>
        <div class="link-item">Need help? <a href="mailto:support@careplus.com">Contact Support</a></div>
    </div>
    <?php endif; ?>

</div>

<!-- Pass session info to JavaScript for chatbot -->
<script>
    window.session_id_php = "<?php echo session_id(); ?>";
    window.patient_id_php = <?php echo isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;
</script>

<script src="forgotPassword.js"></script>
<script>
// IC Number validation (numbers only)
const icField = document.getElementById('ic_number');
if (icField) {
    icField.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });
}

// Phone number validation (numbers only)
const phoneField = document.getElementById('phone');
if (phoneField) {
    phoneField.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });
}

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.parentElement.querySelector('.toggle-password');

    if (field.type === 'password') {
        field.type = 'text';
        button.textContent = '🙈';
        button.title = 'Hide Password';
    } else {
        field.type = 'password';
        button.textContent = '👁️';
        button.title = 'Show Password';
    }
}
</script>

</body>
</html>