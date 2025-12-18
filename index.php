<?php 
require_once __DIR__ . '/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $userType = getUserType();
    if ($userType === 'doctor') {
        redirect('doctor/dashboard.php');
    } elseif ($userType === 'patient') {
        redirect('patient/dashboard.php');
    } elseif ($userType === 'admin') {
        redirect('admin/dashboard.php');
    }
}

// Set variables for the HTML template
$siteName = SITE_NAME;
$currentYear = date('Y');

// Include the HTML template
include 'index.html';
?>
