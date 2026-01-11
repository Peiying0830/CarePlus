<?php
session_start();

require_once __DIR__ . '/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $userType = getUserType();
    redirect($userType . '/dashboard.php');
}

$errors = [];
$loginType = $_GET['type'] ?? 'patient';

// Validate login type
if (!in_array($loginType, ['doctor', 'patient', 'admin'])) {
    $loginType = 'patient';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? 'patient';
    
    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required';
    } else {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        try {
            $stmt = $conn->prepare("SELECT user_id, email, password_hash, user_type FROM users WHERE email = ?");
            
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Check if user type matches
                if ($user['user_type'] !== $userType) {
                    $errors[] = 'Invalid credentials for ' . ucfirst($userType) . ' login';
                } else {
                    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    $updateStmt->bind_param("i", $user['user_id']);
                    $updateStmt->execute();
                    $updateStmt->close();

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['email'] = $user['email'];
                    
                    redirect($user['user_type'] . '/dashboard.php');
                }
            } else {
                $errors[] = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $errors[] = 'Login failed. Please try again.';
            logError('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

    <div class="login-container">
        <div class="back-home">
            <a href="index.php">← Back to Home</a>
        </div>

        <div class="login-header">
            <div class="logo-icon <?php echo $loginType === 'admin' ? 'admin' : ''; ?>" id="logoIcon">
                <?php 
                    if ($loginType === 'doctor') echo '👨‍⚕️';
                    elseif ($loginType === 'admin') echo '🔐';
                    else echo '👤';
                ?>
            </div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Login to access your account</p>
        </div>

        <div class="user-type-toggle">
            <button type="button" class="type-btn <?php echo $loginType === 'patient' ? 'active' : ''; ?>" 
                    data-type="patient" data-icon="👤">
                👤 Patient
            </button>
            <button type="button" class="type-btn <?php echo $loginType === 'doctor' ? 'active' : ''; ?>" 
                    data-type="doctor" data-icon="👨‍⚕️">
                👨‍⚕️ Doctor
            </button>
            <button type="button" class="type-btn admin-btn <?php echo $loginType === 'admin' ? 'active' : ''; ?>" 
                    data-type="admin" data-icon="🔐">
                🔐 Admin
            </button>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" id="errorAlert">
                <strong>⚠️ Login Failed</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="user_type" id="user_type" value="<?php echo htmlspecialchars($loginType); ?>">
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       placeholder="your.email@example.com" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       required autofocus>
                <span class="error-message" id="email-error"></span>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" id="passwordToggle"
                            aria-label="Toggle password visibility" title="Show/Hide Password">
                        <span id="toggle-icon">👁️</span>
                    </button>
                </div>
                <span class="error-message" id="password-error"></span>
                <div class="forgot-password">
                    <a href="forgotPassword.php">Forgot Password?</a>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn">
                Login
            </button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="registration.php?type=<?php echo $loginType; ?>" id="registerLink">Register here</a>
        </div>
    </div>

    <!-- Chatbot Variables -->
    <script>
        // Define variables for chatbot
        const session_id_php = "<?php echo session_id(); ?>";
        const patient_id_php = null; // Guest mode during login
        
        // Debug log
        console.log('Session ID:', session_id_php);
        console.log('Patient ID:', patient_id_php);
    </script>

    <script src="login.js"></script>
</body>
</html>