// Utility Functions
const Utils = {
    // Show alert message
    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <span>${this.getAlertIcon(type)}</span>
            <span>${message}</span>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => alertDiv.remove(), 5000);
    },
    
    getAlertIcon(type) {
        const icons = { success: '✓', error: '⚠️', warning: '⚠️', info: 'ℹ️' };
        return icons[type] || 'ℹ️';
    },
    
    showLoading(element) {
        const spinner = document.createElement('div');
        spinner.className = 'spinner';
        spinner.id = 'loading-spinner';
        element.appendChild(spinner);
    },
    
    hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) spinner.remove();
    },
    
    formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-MY', options);
    },
    
    formatTime(timeString) {
        return new Date('2000-01-01 ' + timeString).toLocaleTimeString('en-MY', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    },
    
    formatCurrency(amount) {
        return 'RM ' + parseFloat(amount).toFixed(2);
    }
};

//  AJAX Helper
const Ajax = {
    async request(url, method = 'GET', data = null) {
        const options = { method, headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
        if (data && method !== 'GET') options.body = JSON.stringify(data);
        try {
            const response = await fetch(url, options);
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Request failed');
            return result;
        } catch (error) {
            console.error('AJAX Error:', error);
            throw error;
        }
    },
    
    async submitForm(url, formData) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Form submission failed');
            return result;
        } catch (error) {
            console.error('Form Submission Error:', error);
            throw error;
        }
    },
    
    get(url) { return this.request(url, 'GET'); },
    post(url, data) { return this.request(url, 'POST', data); },
    put(url, data) { return this.request(url, 'PUT', data); },
    delete(url) { return this.request(url, 'DELETE'); }
};

// Modal Helper
class Modal {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        if (!this.modal) { console.error(`Modal with id '${modalId}' not found`); return; }
        this.closeBtn = this.modal.querySelector('.modal-close');
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        if (this.closeBtn) this.closeBtn.addEventListener('click', () => this.close());
        this.modal.addEventListener('click', (e) => { if (e.target === this.modal) this.close(); });
    }
    
    open() { this.modal.classList.add('active'); document.body.style.overflow = 'hidden'; }
    close() { this.modal.classList.remove('active'); document.body.style.overflow = ''; }
}

// Form Validator
class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        if (!this.form) return;
        this.errors = {};
        this.setupValidation();
    }
    
    setupValidation() {
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) { e.preventDefault(); this.displayErrors(); }
        });
        
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => input.addEventListener('blur', () => this.validateField(input)));
    }
    
    validateForm() {
        this.errors = {};
        const inputs = this.form.querySelectorAll('[required]');
        inputs.forEach(input => this.validateField(input));
        return Object.keys(this.errors).length === 0;
    }
    
    validateField(field) {
        const name = field.name, value = field.value.trim();
        delete this.errors[name];
        field.classList.remove('error');
        const errorSpan = field.parentElement.querySelector('.error-message');
        if (errorSpan) errorSpan.remove();
        
        if (field.hasAttribute('required') && !value) { this.addError(field, 'This field is required'); return false; }
        if (field.type === 'email' && value) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(value)) { this.addError(field, 'Please enter a valid email'); return false; }
        }
        if (field.type === 'password' && value && field.hasAttribute('minlength')) {
            const minLength = parseInt(field.getAttribute('minlength'));
            if (value.length < minLength) { this.addError(field, `Password must be at least ${minLength} characters`); return false; }
        }
        if (field.type === 'tel' && value) {
            const phonePattern = /^[0-9]{10,11}$/;
            if (!phonePattern.test(value.replace(/\D/g, ''))) { this.addError(field, 'Please enter a valid phone number'); return false; }
        }
        return true;
    }
    
    addError(field, message) {
        this.errors[field.name] = message;
        field.classList.add('error');
        const errorSpan = document.createElement('span');
        errorSpan.className = 'error-message';
        errorSpan.textContent = message;
        field.parentElement.appendChild(errorSpan);
    }
    
    displayErrors() {
        if (Object.keys(this.errors).length > 0) {
            const firstError = Object.values(this.errors)[0];
            Utils.showAlert(firstError, 'error');
        }
    }
}

// Appointment Booking
class AppointmentBooking {
    constructor() {
        this.selectedDate = null;
        this.selectedTime = null;
        this.selectedDoctor = null;
        this.init();
    }
    
    init() { this.setupDatePicker(); this.setupDoctorSelection(); }
    
    setupDatePicker() {
        const dateInput = document.getElementById('appointment_date');
        if (!dateInput) return;
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        dateInput.addEventListener('change', (e) => {
            this.selectedDate = e.target.value;
            if (this.selectedDoctor) this.loadAvailableSlots();
        });
    }
    
    setupDoctorSelection() {
        const doctorSelect = document.getElementById('doctor_id');
        if (!doctorSelect) return;
        doctorSelect.addEventListener('change', (e) => {
            this.selectedDoctor = e.target.value;
            if (this.selectedDate) this.loadAvailableSlots();
        });
    }
    
    async loadAvailableSlots() {
        const slotsContainer = document.getElementById('time-slots');
        if (!slotsContainer) return;
        Utils.showLoading(slotsContainer);
        try {
            const result = await Ajax.get(`api/get_available_slots.php?doctor_id=${this.selectedDoctor}&date=${this.selectedDate}`);
            if (result.success) this.displayTimeSlots(result.data);
            else throw new Error(result.message);
        } catch (error) {
            Utils.showAlert(error.message, 'error');
            slotsContainer.innerHTML = '<p>Failed to load available time slots</p>';
        } finally { Utils.hideLoading(); }
    }
    
    displayTimeSlots(slots) {
        const slotsContainer = document.getElementById('time-slots');
        if (slots.length === 0) { slotsContainer.innerHTML = '<p>No available slots for this date</p>'; return; }
        slotsContainer.innerHTML = '<h4>Available Time Slots:</h4>';
        const slotsGrid = document.createElement('div');
        slotsGrid.className = 'time-slots-grid';
        slotsGrid.style.display = 'grid';
        slotsGrid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(120px, 1fr))';
        slotsGrid.style.gap = '10px';
        slotsGrid.style.marginTop = '15px';
        
        slots.forEach(slot => {
            const slotBtn = document.createElement('button');
            slotBtn.type = 'button';
            slotBtn.className = 'btn btn-outline btn-sm time-slot-btn';
            slotBtn.textContent = Utils.formatTime(slot);
            slotBtn.dataset.time = slot;
            
            slotBtn.addEventListener('click', () => {
                document.querySelectorAll('.time-slot-btn').forEach(btn => btn.classList.remove('active'));
                slotBtn.classList.add('active');
                this.selectedTime = slot;
                document.getElementById('appointment_time').value = slot;
            });
            
            slotsGrid.appendChild(slotBtn);
        });
        
        slotsContainer.appendChild(slotsGrid);
    }
}

// Search Handler
class SearchHandler {
    constructor(searchInputId, searchResultsId) {
        this.searchInput = document.getElementById(searchInputId);
        this.searchResults = document.getElementById(searchResultsId);
        if (this.searchInput && this.searchResults) this.setupSearch();
    }
    
    setupSearch() {
        let timeout = null;
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            const query = e.target.value.trim();
            if (query.length < 2) { this.searchResults.innerHTML = ''; return; }
            timeout = setTimeout(() => this.performSearch(query), 500);
        });
    }
    
    async performSearch(query) {
        Utils.showLoading(this.searchResults);
        try {
            const result = await Ajax.get(`api/search.php?q=${encodeURIComponent(query)}`);
            if (result.success) this.displayResults(result.data);
        } catch (error) {
            Utils.showAlert('Search failed', 'error');
        } finally { Utils.hideLoading(); }
    }
    
    displayResults(results) {
        if (results.length === 0) { this.searchResults.innerHTML = '<p>No results found</p>'; return; }
        let html = '<ul class="search-results-list">';
        results.forEach(item => { html += `<li>${item.title}</li>`; });
        html += '</ul>';
        this.searchResults.innerHTML = html;
    }
}

// Mobile Sidebar Menu
function setupMobileMenu() {
    const toggle = document.querySelector('.mobile-menu-toggle');
    const menu = document.querySelector('.nav-menu');
    const overlay = document.querySelector('.mobile-menu-overlay');
    const body = document.body;

    if (!toggle || !menu || !overlay) return;

    function openMenu() {
        menu.classList.add('active');
        overlay.classList.add('active');
        toggle.classList.add('active');
        body.style.overflow = 'hidden';
    }

    function closeMenu() {
        menu.classList.remove('active');
        overlay.classList.remove('active');
        toggle.classList.remove('active');
        body.style.overflow = '';
    }

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.contains('active') ? closeMenu() : openMenu();
    });

    overlay.addEventListener('click', closeMenu);

    // Close when clicking a link (mobile only)
    menu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 968) closeMenu();
        });
    });

    // ESC key support (nice UX)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
    });

    // Reset if resizing to desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 968) closeMenu();
    });
}

// DataTable Handler
class DataTableHandler {
    constructor(tableId) {
        this.table = document.getElementById(tableId);
        if (this.table) this.setupSorting();
    }
    
    setupSorting() {
        const headers = this.table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => this.sortTable(header.dataset.sort));
        });
    }
    
    sortTable(column) { console.log('Sorting by:', column); }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    setupMobileMenu();
    
    // Form validators
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => new FormValidator(form.id));
    
    // Appointment booking
    if (document.getElementById('appointment_date')) new AppointmentBooking();
});

//Export Global
window.Utils = Utils;
window.Ajax = Ajax;
window.Modal = Modal;
window.FormValidator = FormValidator;
