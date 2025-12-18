// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;

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

// Form submission validation
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    // Password match validation (applies to all user types)
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('❌ Passwords do not match!');
        document.getElementById('confirm_password').focus();
        return;
    }

    // Get registration type
    const regType = document.querySelector('input[name="reg_type"]')?.value;

    // Doctor-specific validation
    if (regType === 'doctor') {
        // Validate available days
        const checkedDays = document.querySelectorAll('input[name="available_days[]"]:checked');
        if (checkedDays.length === 0) {
            e.preventDefault();
            alert('❌ Please select at least one available day!');
            return;
        }

        // Validate working hours
        const startTime = document.getElementById('start_time')?.value;
        const endTime = document.getElementById('end_time')?.value;

        if (startTime && endTime && startTime >= endTime) {
            e.preventDefault();
            alert('❌ End time must be after start time!');
            return;
        }
    }

    // Admin-specific validation (admin code is required)
    if (regType === 'admin') {
        const adminCode = document.getElementById('admin_code')?.value;
        if (!adminCode || adminCode.trim() === '') {
            e.preventDefault();
            alert('❌ Admin registration code is required!');
            document.getElementById('admin_code').focus();
            return;
        }
    }
});

// IC number validation (numbers only) - for doctor and patient only
const icField = document.getElementById('ic_number');
if (icField) {
    icField.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });
}

// Phone number validation (numbers only) - applies to all user types
const phoneField = document.getElementById('phone');
if (phoneField) {
    phoneField.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });
}

// Emergency contact validation (numbers only) - patient only
const emergencyContact = document.getElementById('emergency_contact');
if (emergencyContact) {
    emergencyContact.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });
}

// Time input helper - for doctor only
const startTimeField = document.getElementById('start_time');
const endTimeField = document.getElementById('end_time');

if (startTimeField && endTimeField) {
    // Real-time validation feedback
    const validateTimes = () => {
        const start = startTimeField.value;
        const end = endTimeField.value;
        
        if (start && end && start >= end) {
            endTimeField.setCustomValidity('End time must be after start time');
            endTimeField.style.borderColor = '#ef4444';
        } else {
            endTimeField.setCustomValidity('');
            endTimeField.style.borderColor = '';
        }
    };

    startTimeField.addEventListener('change', validateTimes);
    endTimeField.addEventListener('change', validateTimes);
}

// Select/Deselect all days helper - for doctor only
const daysCheckboxes = document.querySelectorAll('input[name="available_days[]"]');
if (daysCheckboxes.length > 0) {
    // Optional: Add "Select All" functionality
    // You can add a button in the HTML if you want this feature
    window.selectAllDays = function() {
        daysCheckboxes.forEach(checkbox => checkbox.checked = true);
    };
    
    window.deselectAllDays = function() {
        daysCheckboxes.forEach(checkbox => checkbox.checked = false);
    };
    
    window.selectWeekdays = function() {
        const weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        daysCheckboxes.forEach(checkbox => {
            checkbox.checked = weekdays.includes(checkbox.value);
        });
    };
}