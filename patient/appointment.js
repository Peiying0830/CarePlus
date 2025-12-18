// appointment.js - Appointment Management Scripts

// Tab switching functionality
function switchTab(tabName, event) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab content
    const tabElement = document.getElementById(tabName + '-tab');
    if (tabElement) {
        tabElement.classList.add('active');
    }
    
    // Add active class to clicked tab button
    if (event && event.target) {
        event.target.classList.add('active');
    }
}

// Update doctor card selection
function updateDoctorSelection(radio) {
    // Remove selected class from all doctor cards
    document.querySelectorAll('.doctor-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selected class to clicked card
    if (radio && radio.closest('.doctor-card')) {
        radio.closest('.doctor-card').classList.add('selected');
    }
}

// Reset form
function resetForm() {
    const form = document.querySelector('form');
    if (form) {
        form.reset();
        document.querySelectorAll('.doctor-card').forEach(card => {
            card.classList.remove('selected');
        });
    }
}

// Open booking modal/tab
function openBookingModal() {
    switchTab('book');
    // Scroll to booking form
    const bookTab = document.getElementById('book-tab');
    if (bookTab) {
        bookTab.scrollIntoView({ behavior: 'smooth' });
    }
}

// Date and time slot validation
function validateAppointmentDateTime(dateInput, timeSelect) {
    if (!dateInput || !timeSelect) return false;
    
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Check if date is in the past
    if (selectedDate < today) {
        alert('Please select a future date');
        dateInput.focus();
        return false;
    }
    
    // Check if time slot is selected
    if (!timeSelect.value) {
        alert('Please select a time slot');
        timeSelect.focus();
        return false;
    }
    
    // Check if selected time is within clinic hours
    const selectedTime = timeSelect.value;
    const clinicHours = ['09:00:00', '10:00:00', '11:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00'];
    if (!clinicHours.includes(selectedTime)) {
        alert('Please select a valid time slot between 9 AM and 6 PM');
        timeSelect.focus();
        return false;
    }
    
    return true;
}

// Confirm appointment cancellation
function confirmCancel(url) {
    if (confirm('Are you sure you want to cancel this appointment?\n\nNote: Appointments can only be cancelled at least 24 hours in advance.')) {
        window.location.href = url;
    }
    return false;
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

// Format time for display
function formatTime(timeString) {
    const time = timeString.split(':');
    let hour = parseInt(time[0]);
    const minute = time[1];
    const ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 || 12;
    return `${hour}:${minute} ${ampm}`;
}

// Initialize doctor card selection from URL parameters
function initDoctorSelectionFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const doctorId = urlParams.get('doctor');
    const dateParam = urlParams.get('date');
    
    if (doctorId) {
        const radio = document.querySelector(`input[name="doctor_id"][value="${doctorId}"]`);
        if (radio) {
            radio.checked = true;
            updateDoctorSelection(radio);
            switchTab('book', null);
        }
    }
    
    if (dateParam) {
        const dateInput = document.querySelector('input[name="appointment_date"]');
        if (dateInput) {
            dateInput.value = dateParam;
            switchTab('book', null);
        }
    }
}

// Initialize appointment form validation
function initFormValidation() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const doctorSelected = document.querySelector('input[name="doctor_id"]:checked');
            const dateInput = document.querySelector('input[name="appointment_date"]');
            const timeSelect = document.querySelector('select[name="appointment_time"]');
            
            if (!doctorSelected) {
                e.preventDefault();
                alert('Please select a doctor');
                return false;
            }
            
            if (!validateAppointmentDateTime(dateInput, timeSelect)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>⏳</span> Booking...';
            }
            
            return true;
        });
    }
}

// Initialize date input restrictions
function initDateInput() {
    const dateInput = document.querySelector('input[name="appointment_date"]');
    if (dateInput) {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        
        // Set maximum date to 3 months from now
        const maxDate = new Date();
        maxDate.setMonth(maxDate.getMonth() + 3);
        dateInput.max = maxDate.toISOString().split('T')[0];
        
        // Add change event to validate weekends (optional)
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const day = selectedDate.getDay();
            
            // Check if it's a weekend (0 = Sunday, 6 = Saturday)
            if (day === 0 || day === 6) {
                if (!confirm('You have selected a weekend. Our clinic hours may vary on weekends. Continue?')) {
                    this.value = '';
                }
            }
        });
    }
}

// Add smooth scroll to tabs
function initSmoothScrollToTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const onclickAttr = this.getAttribute('onclick');
            if (onclickAttr) {
                const match = onclickAttr.match(/'([^']+)'/);
                if (match) {
                    const tabName = match[1];
                    const tabContent = document.getElementById(tabName + '-tab');
                    if (tabContent) {
                        setTimeout(() => {
                            tabContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 100);
                    }
                }
            }
        });
    });
}

// Add keyboard navigation for doctor cards
function initKeyboardNavigation() {
    document.querySelectorAll('.doctor-card').forEach((card, index) => {
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const radio = this.querySelector('.doctor-radio');
                if (radio) {
                    radio.checked = true;
                    updateDoctorSelection(radio);
                }
            }
            
            // Arrow key navigation
            if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                e.preventDefault();
                const nextCard = document.querySelectorAll('.doctor-card')[index + 1];
                if (nextCard) {
                    nextCard.focus();
                    const radio = nextCard.querySelector('.doctor-radio');
                    if (radio) {
                        radio.checked = true;
                        updateDoctorSelection(radio);
                    }
                }
            }
            
            if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                e.preventDefault();
                const prevCard = document.querySelectorAll('.doctor-card')[index - 1];
                if (prevCard) {
                    prevCard.focus();
                    const radio = prevCard.querySelector('.doctor-radio');
                    if (radio) {
                        radio.checked = true;
                        updateDoctorSelection(radio);
                    }
                }
            }
        });
        
        // Make cards focusable
        card.setAttribute('tabindex', '0');
    });
}

// Load available time slots based on selected date
function loadAvailableTimeSlots() {
    const dateInput = document.querySelector('input[name="appointment_date"]');
    const timeSelect = document.querySelector('select[name="appointment_time"]');
    const doctorRadio = document.querySelector('input[name="doctor_id"]:checked');
    
    if (!dateInput || !timeSelect || !doctorRadio) return;
    
    dateInput.addEventListener('change', function() {
        if (this.value && doctorRadio.value) {
            // Show loading state
            timeSelect.disabled = true;
            timeSelect.innerHTML = '<option value="">Loading available slots...</option>';
            
            // In a real application, you would make an AJAX request here
            // to fetch available time slots for the selected doctor and date
            
            setTimeout(() => {
                // This is a mock response - replace with actual AJAX call
                const mockSlots = [
                    { time: '09:00:00', available: true },
                    { time: '10:00:00', available: true },
                    { time: '11:00:00', available: false },
                    { time: '14:00:00', available: true },
                    { time: '15:00:00', available: true },
                    { time: '16:00:00', available: true },
                    { time: '17:00:00', available: false }
                ];
                
                timeSelect.innerHTML = '<option value="">Select Time Slot</option>';
                mockSlots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.time;
                    option.textContent = formatTime(slot.time);
                    option.disabled = !slot.available;
                    if (!slot.available) {
                        option.textContent += ' (Booked)';
                    }
                    timeSelect.appendChild(option);
                });
                
                timeSelect.disabled = false;
            }, 500);
        }
    });
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Appointment management system initialized');
    
    // Initialize from URL parameters
    initDoctorSelectionFromURL();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize date input restrictions
    initDateInput();
    
    // Initialize smooth scroll to tabs
    initSmoothScrollToTabs();
    
    // Initialize keyboard navigation for doctor cards
    initKeyboardNavigation();
    
    // Load available time slots (mock/example)
    loadAvailableTimeSlots();
    
    // Add animation to new appointment button
    const newAppointmentBtn = document.querySelector('.btn-primary[onclick*="openBookingModal"]');
    if (newAppointmentBtn) {
        newAppointmentBtn.addEventListener('mouseenter', function() {
            this.classList.add('pulse');
        });
        
        newAppointmentBtn.addEventListener('mouseleave', function() {
            this.classList.remove('pulse');
        });
    }
    
    // Add confirmation for form reset
    const resetBtn = document.querySelector('button[onclick*="resetForm"]');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Check for success message and scroll to it
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto-hide success message after 5 seconds
        setTimeout(() => {
            if (successAlert.parentNode) {
                successAlert.style.opacity = '0';
                successAlert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    if (successAlert.parentNode) {
                        successAlert.parentNode.removeChild(successAlert);
                    }
                }, 500);
            }
        }, 5000);
    }
    
    // Add print functionality for appointment details
    const printButtons = document.querySelectorAll('.btn[href*="view="]');
    printButtons.forEach(btn => {
        const originalHref = btn.getAttribute('href');
        if (originalHref && originalHref.includes('view=')) {
            const appointmentId = originalHref.split('view=')[1];
            btn.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                if (confirm('Print appointment details?')) {
                    window.open(`print_appointment.php?id=${appointmentId}`, '_blank');
                }
            });
        }
    });
});

// Export functions for use in other modules (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        switchTab,
        updateDoctorSelection,
        resetForm,
        openBookingModal,
        validateAppointmentDateTime,
        confirmCancel,
        formatDate,
        formatTime
    };
}