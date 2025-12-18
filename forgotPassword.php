<?php
require_once __DIR__ . '/config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $errors[] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    } else {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        try {
            $stmt = $conn->prepare("SELECT user_id, email, user_type FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $conn->prepare(
                    "INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE token = ?, expiry = ?"
                );
                $stmt->execute([$email, $token, $expiry, $token, $expiry]);
                
                $resetLink = SITE_URL . '/reset-password.php?token=' . $token;
                $subject = 'Password Reset Request - ' . SITE_NAME;
                $message = "Hello,\n\nYou requested a password reset.\n\nReset link: " . $resetLink .
                           "\n\nThis link expires in 1 hour.\n\nIf you did not request this, ignore this email.\n\nRegards,\n" . SITE_NAME;
                
                $headers = "From: " . SITE_EMAIL . "\r\n";
                $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
                
                mail($email, $subject, $message, $headers);
                
                $success = 'Password reset instructions have been sent to your email address.';
            } else {
                $success = 'If an account exists with this email, password reset instructions have been sent.';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred. Please try again later.';
            logError('Password reset error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= SITE_NAME; ?></title>
    <link rel="stylesheet" href="forgotPassword.css">
</head>
<body>

    <div class="reset-container">

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>

        <div class="reset-header">
            <div class="logo-icon">🔑</div>
            <h1 class="reset-title">Forgot Password?</h1>
            <p class="reset-subtitle">Enter your email and we'll send you a password reset link.</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <span class="icon">✅</span>
                <div>
                    <strong>Success!</strong><br>
                    <?= htmlspecialchars($success); ?><br>
                    <small>Please check your inbox and spam folder.</small>
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

        <?php if (empty($success)): ?>
            <div class="info-box">
                <strong>📧 How it works:</strong>
                Enter your email and you will receive a secure password reset link, valid for 1 hour.
            </div>

            <form method="POST" id="resetForm">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="your.email@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required autofocus>
                    <span class="error-message" id="email-error"></span>
                </div>

                <button type="submit" class="btn btn-warning" id="submitBtn">
                    📨 Send Reset Link
                </button>
            </form>

            <div class="security-tips">
                <h3>🔒 Security Tips</h3>
                <ul>
                    <li>Never share your reset link</li>
                    <li>The link expires in 1 hour</li>
                    <li>Use strong passwords</li>
                    <li>Avoid reusing passwords</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="info-box">
                <strong>📬 What's next?</strong>
                Check your email for the reset link. If not received, check spam or try again.
            </div>
        <?php endif; ?>

        <div class="divider"><span>OR</span></div>

        <div class="links-section">
            <div class="link-item">Remembered? <a href="login.php">Login</a></div>
            <div class="link-item">New user? <a href="registration.php">Register</a></div>
            <div class="link-item">Need help? <a href="contact.php">Contact Support</a></div>
        </div>

    </div>

    <script src="forgotPassword.js"></script>
</body>
</html>