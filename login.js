// Login Page - Form Validation and Enhancements

document.addEventListener('DOMContentLoaded', function() {
    // Get form elements
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const passwordToggle = document.getElementById('passwordToggle');
    const userTypeInput = document.getElementById('user_type');
    const typeButtons = document.querySelectorAll('.type-btn');
    const logoIcon = document.getElementById('logoIcon');
    const registerLink = document.getElementById('registerLink');
    const errorAlert = document.getElementById('errorAlert');

    // Email validation
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Show error message
    function showError(input, errorId, message) {
        const errorElement = document.getElementById(errorId);
        input.classList.add('input-error');
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }

    // Clear error message
    function clearError(input, errorId) {
        const errorElement = document.getElementById(errorId);
        input.classList.remove('input-error');
        errorElement.textContent = '';
        errorElement.classList.remove('show');
    }

    // Email validation on blur
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        
        if (email === '') {
            showError(this, 'email-error', 'Email address is required');
        } else if (!validateEmail(email)) {
            showError(this, 'email-error', 'Please enter a valid email address');
        } else {
            clearError(this, 'email-error');
        }
    });

    // Clear email error on input
    emailInput.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
            const email = this.value.trim();
            if (email !== '' && validateEmail(email)) {
                clearError(this, 'email-error');
            }
        }
    });

    // Password validation on blur
    passwordInput.addEventListener('blur', function() {
        const password = this.value;
        
        if (password === '') {
            showError(this, 'password-error', 'Password is required');
        } else if (password.length < 6) {
            showError(this, 'password-error', 'Password must be at least 6 characters');
        } else {
            clearError(this, 'password-error');
        }
    });

    // Clear password error on input
    passwordInput.addEventListener('input', function() {
        if (this.classList.contains('input-error')) {
            const password = this.value;
            if (password !== '' && password.length >= 6) {
                clearError(this, 'password-error');
            }
        }
    });

    // Toggle password visibility
    passwordToggle.addEventListener('click', function() {
        const toggleIcon = document.getElementById('toggle-icon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.textContent = '🙈';
        } else {
            passwordInput.type = 'password';
            toggleIcon.textContent = '👁️';
        }
    });

    // User type toggle
    typeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            const icon = this.getAttribute('data-icon');
            
            // Update hidden input
            userTypeInput.value = type;
            
            // Update active button
            typeButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Update logo icon
            logoIcon.textContent = icon;
            
            // Update logo background for admin
            if (type === 'admin') {
                logoIcon.classList.add('admin');
            } else {
                logoIcon.classList.remove('admin');
            }

            // Update register link
            registerLink.href = `registration.php?type=${type}`;
        });
    });

    // Form submission validation
    loginForm.addEventListener('submit', function(e) {
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        let isValid = true;

        // Validate email
        if (email === '') {
            showError(emailInput, 'email-error', 'Email address is required');
            isValid = false;
        } else if (!validateEmail(email)) {
            showError(emailInput, 'email-error', 'Please enter a valid email address');
            isValid = false;
        } else {
            clearError(emailInput, 'email-error');
        }

        // Validate password
        if (password === '') {
            showError(passwordInput, 'password-error', 'Password is required');
            isValid = false;
        } else if (password.length < 6) {
            showError(passwordInput, 'password-error', 'Password must be at least 6 characters');
            isValid = false;
        } else {
            clearError(passwordInput, 'password-error');
        }

        // Prevent submission if validation fails
        if (!isValid) {
            e.preventDefault();
            
            // Focus on first error field
            if (emailInput.classList.contains('input-error')) {
                emailInput.focus();
            } else if (passwordInput.classList.contains('input-error')) {
                passwordInput.focus();
            }
            
            return false;
        }

        // Show loading state
        submitBtn.textContent = '⏳ Logging in...';
        submitBtn.disabled = true;
    });

    // Add ripple effect to button
    submitBtn.addEventListener('click', function(e) {
        if (this.disabled) return;
        
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

    // Auto-hide error alert after 8 seconds
    if (errorAlert) {
        // Add close button to alert
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
            errorAlert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            errorAlert.style.opacity = '0';
            errorAlert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                errorAlert.remove();
            }, 300);
        });
        
        errorAlert.style.position = 'relative';
        errorAlert.appendChild(closeBtn);

        // Auto-hide after 8 seconds
        setTimeout(() => {
            errorAlert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            errorAlert.style.opacity = '0';
            errorAlert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                errorAlert.remove();
            }, 500);
        }, 8000);
    }

    // Prevent multiple form submissions
    let isSubmitting = false;
    loginForm.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        isSubmitting = true;
    });

    // Enter key press on email moves to password
    emailInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            passwordInput.focus();
        }
    });

    // Email auto-completion suggestion
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

    // Add smooth transitions to user type buttons
    typeButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateY(-2px)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Focus management
    if (emailInput && !emailInput.value) {
        emailInput.focus();
    }
});