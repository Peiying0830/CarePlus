// Forgot Password Page - Form Validation and Enhancement

document.addEventListener('DOMContentLoaded', function() {
    const resetForm = document.getElementById('resetForm');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');
    const emailError = document.getElementById('email-error');

    // Only run if form exists (not shown after successful submission)
    if (!resetForm) return;

    // Email validation function
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Show error message
    function showError(input, errorElement, message) {
        input.classList.add('input-error');
        input.style.borderColor = '#ef4444';
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        errorElement.style.color = '#ef4444';
        errorElement.style.fontSize = '0.875rem';
        errorElement.style.marginTop = '0.5rem';
    }

    // Clear error message
    function clearError(input, errorElement) {
        input.classList.remove('input-error');
        input.style.borderColor = '';
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }

    // Validate email field on blur
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        
        if (email === '') {
            showError(this, emailError, 'Email address is required');
        } else if (!validateEmail(email)) {
            showError(this, emailError, 'Please enter a valid email address');
        } else {
            clearError(this, emailError);
        }
    });

    // Clear error on input
    emailInput.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
            const email = this.value.trim();
            if (email !== '' && validateEmail(email)) {
                clearError(this, emailError);
            }
        }
    });

    // Form submission validation
    resetForm.addEventListener('submit', function(e) {
        const email = emailInput.value.trim();
        let isValid = true;

        // Validate email
        if (email === '') {
            showError(emailInput, emailError, 'Email address is required');
            isValid = false;
        } else if (!validateEmail(email)) {
            showError(emailInput, emailError, 'Please enter a valid email address');
            isValid = false;
        } else {
            clearError(emailInput, emailError);
        }

        // Prevent submission if validation fails
        if (!isValid) {
            e.preventDefault();
            emailInput.focus();
            return false;
        }

        // Show loading state
        submitBtn.textContent = '⏳ Sending...';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';
        submitBtn.style.cursor = 'not-allowed';
    });

    // Auto-focus email input
    if (emailInput) {
        emailInput.focus();
    }

    // Auto-hide success/error alerts after 10 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 10000);
    });

    // Add manual close button to alerts
    alerts.forEach(alert => {
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '×';
        closeBtn.style.cssText = `
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            transition: opacity 0.3s;
            line-height: 1;
            padding: 0;
            width: 24px;
            height: 24px;
        `;
        
        closeBtn.addEventListener('mouseenter', function() {
            this.style.opacity = '1';
        });
        
        closeBtn.addEventListener('mouseleave', function() {
            this.style.opacity = '0.7';
        });
        
        closeBtn.addEventListener('click', function() {
            alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        });
        
        alert.style.position = 'relative';
        alert.appendChild(closeBtn);
    });

    // Smooth scroll to top if there are alerts
    if (alerts.length > 0) {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Add entrance animation to container
    const container = document.querySelector('.reset-container');
    if (container) {
        container.style.opacity = '0';
        container.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        }, 100);
    }

    // Add ripple effect to button
    submitBtn.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            left: ${x}px;
            top: ${y}px;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        `;
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });

    // Add ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
        
        .btn {
            position: relative;
            overflow: hidden;
        }
        
        .error-message {
            display: none;
        }
        
        .input-error {
            animation: shake 0.4s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);

    // Prevent multiple form submissions
    let isSubmitting = false;
    resetForm.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        isSubmitting = true;
    });

    // Email auto-completion suggestion (optional enhancement)
    const commonDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
    let suggestionElement = null;

    emailInput.addEventListener('input', function() {
        const value = this.value.trim();
        const atIndex = value.indexOf('@');
        
        // Remove existing suggestion
        if (suggestionElement) {
            suggestionElement.remove();
            suggestionElement = null;
        }
        
        // Show suggestion if @ is typed but domain is incomplete
        if (atIndex !== -1 && atIndex < value.length - 1) {
            const domain = value.substring(atIndex + 1);
            const matchedDomain = commonDomains.find(d => d.startsWith(domain) && d !== domain);
            
            if (matchedDomain) {
                suggestionElement = document.createElement('div');
                suggestionElement.style.cssText = `
                    color: #64748b;
                    font-size: 0.875rem;
                    margin-top: 0.25rem;
                    cursor: pointer;
                `;
                suggestionElement.textContent = `Did you mean ${value.substring(0, atIndex + 1)}${matchedDomain}?`;
                suggestionElement.addEventListener('click', function() {
                    emailInput.value = value.substring(0, atIndex + 1) + matchedDomain;
                    this.remove();
                    emailInput.focus();
                });
                emailInput.parentElement.appendChild(suggestionElement);
            }
        }
    });
});