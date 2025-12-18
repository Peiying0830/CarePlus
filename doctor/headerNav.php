<?php 
$currentPath = $_SERVER['PHP_SELF'];
$baseUrl = defined('SITE_URL') ? SITE_URL : 'http://localhost/CarePlus';

// Get patient name from session or database
$patientName = 'Guest User';
$patientInitials = '👤';

if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT first_name, last_name FROM patients WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($patient && !empty($patient['first_name'])) {
            $patientName = trim($patient['first_name'] . ' ' . $patient['last_name']);
            
            // Generate initials
            $nameParts = explode(' ', $patientName);
            $patientInitials = strtoupper(substr($nameParts[0], 0, 1));
            if (isset($nameParts[1]) && !empty($nameParts[1])) {
                $patientInitials .= strtoupper(substr($nameParts[1], 0, 1));
            }
        }
    } catch (Exception $e) {
        error_log('Error fetching patient name: ' . $e->getMessage());
    }
}

$themeColors = [
    'patient' => ['primary' => '#26a69a', 'secondary' => '#00897b', 'light' => '#d4f1e8'],
];

$colors = $themeColors['patient'];

// Construct logout path - since headerNav.php is in /patient/ folder
// and logout.php is in root folder, we need to go up one directory
$logoutPath = '../logout.php';
?>

<!-- Load External CSS & JS -->
<link rel="stylesheet" href="headerNav.css">
<script src="headerNav.js" defer></script>

<style>
:root {
    --nav-primary: <?= $colors['primary']; ?>;
    --nav-secondary: <?= $colors['secondary']; ?>;
    --nav-light: <?= $colors['light']; ?>;
}
</style>

<header class="header">
    <nav class="navbar">
        <a href="<?= $baseUrl; ?>/index.php" class="logo">
            <img src="logo.png" class="logo-img" alt="Logo">
            <span class="logo-text">CarePlus - Smart Clinic Management Portal</span>
        </a>

        <ul class="nav-menu" id="mobileMenu">
            <div class="user-badge">
                <div class="user-icon"><?= $patientInitials; ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($patientName); ?></div>
                    <div class="user-role">Patient Portal</div>
                </div>
            </div>

            <li><a href="dashboard.php" class="nav-link <?= basename($currentPath)=='dashboard.php' ? 'active':''; ?>">
                <span class="nav-icon">🏡</span>
                <span class="nav-text">Dashboard</span>
            </a></li>
            
            <li><a href="appointment.php" class="nav-link <?= basename($currentPath)=='appointment.php' ? 'active':''; ?>">
                <span class="nav-icon">📅</span>
                <span class="nav-text">Appointments</span>
            </a></li>
            
            <li><a href="medicalRecords.php" class="nav-link <?= basename($currentPath)=='medicalRecords.php' ? 'active':''; ?>">
                <span class="nav-icon">📋</span>
                <span class="nav-text">Medical Records</span>
            </a></li>
            
            <li><a href="symptomChecker.php" class="nav-link <?= basename($currentPath)=='symptomChecker.php' ? 'active':''; ?>">
                <span class="nav-icon">🔍</span>
                <span class="nav-text">Symptom Checker</span>
            </a></li>
            
            <li><a href="profile.php" class="nav-link <?= basename($currentPath)=='profile.php' ? 'active':''; ?>">
                <span class="nav-icon">👤</span>
                <span class="nav-text">Profile</span>
            </a></li>

            <li class="nav-divider"></li>

            <!-- Using relative path to go up one directory to root -->
            <li><a href="<?= $logoutPath; ?>" class="nav-link logout-link" onclick="return confirm('Are you sure you want to logout?')">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Logout</span>
            </a></li>
        </ul>

        <button class="mobile-menu-toggle" id="mobileToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </nav>
</header>

<div class="mobile-menu-overlay" id="mobileOverlay"></div>