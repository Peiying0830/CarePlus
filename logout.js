// Auto-redirect after 3 seconds
setTimeout(() => {
    window.location.href = 'index.php';
}, 3000);

// Show manual redirect button after 4 seconds if auto-redirect fails
setTimeout(() => {
    const manualRedirect = document.getElementById('manualRedirect');
    if (manualRedirect) {
        manualRedirect.style.display = 'block';
    }
}, 4000);

// Optional: Add fade-out effect before redirect
setTimeout(() => {
    const container = document.querySelector('.logout-container');
    if (container) {
        container.style.transition = 'opacity 0.5s ease';
        container.style.opacity = '0';
    }
}, 2500);