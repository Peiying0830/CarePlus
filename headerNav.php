<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<!-- Guest Header Navigation -->
<header class="guest-header">
    <nav class="guest-navbar">
        <!-- Logo -->
        <a href="index.php" class="guest-logo">
            <img src="logo.png" class="guest-logo-img" alt="CarePlus Logo">
            <span class="guest-logo-text">CarePlus - Smart Clinic Management Portal</span>
        </a>

        <!-- Right Side: Auth Buttons + Language -->
        <div class="guest-nav-right">
            <!-- Login & Register Buttons -->
            <div class="guest-auth-buttons">
                <a href="login.php" class="guest-btn guest-btn-login">
                    <span class="guest-btn-icon">🔐</span>
                    <span>Login</span>
                </a>
                <a href="registration.php" class="guest-btn guest-btn-register">
                    <span class="guest-btn-icon">✨</span>
                    <span>Register</span>
                </a>
            </div>

            <!-- Google Translate -->
            <div class="guest-translate-wrapper">
                <div id="google_translate_element"></div>
            </div>
        </div>
    </nav>
</header>

<!-- Pass session info to JavaScript (with proper escaping) -->
<script>
(function() {
    'use strict';
    
    // Safely set session data
    if (typeof window.session_id_php === 'undefined') {
        window.session_id_php = <?= json_encode(session_id(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    }
    if (typeof window.patient_id_php === 'undefined') {
        window.patient_id_php = <?= isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null' ?>;
    }
    if (typeof window.userType === 'undefined') {
        window.userType = <?= json_encode($_SESSION['user_type'] ?? 'guest', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    }
})();
</script>

<!-- Google Translate Script (async to avoid blocking) -->
<script>
function googleTranslateElementInit() {
    if (typeof google !== 'undefined' && google.translate) {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,zh-CN,zh-TW,ms,ta,hi,bn,th,vi,id,ja,ko,ar,es,fr,de,pt,ru,it,nl,pl,tr,sv,no,da,fi,el,he,cs,ro',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }
}
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async defer></script>

<!-- Load CSS & JS -->
<link rel="stylesheet" href="headerNav.css?v=1.5">
<script src="headerNav.js" defer></script>

</body>
</html>