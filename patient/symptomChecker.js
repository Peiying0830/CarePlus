// symptomChecker.js - AI Symptom Checker Scripts

// Selected symptoms tracking
const selectedSymptoms = new Set();

// Initialize symptom chip selection
function initSymptomChips() {
    const chips = document.querySelectorAll('.symptom-chip');
    
    chips.forEach(chip => {
        chip.addEventListener('click', function() {
            const symptom = this.dataset.symptom;
            
            if (this.classList.contains('selected')) {
                this.classList.remove('selected');
                selectedSymptoms.delete(symptom);
            } else {
                this.classList.add('selected');
                selectedSymptoms.add(symptom);
            }
            
            // Update textarea with selected symptoms
            updateSymptomsTextarea();
        });
    });
}

// Update symptoms textarea with selected chips
function updateSymptomsTextarea() {
    const textarea = document.getElementById('symptoms');
    const currentText = textarea.value;
    const selectedText = Array.from(selectedSymptoms).join(', ');
    
    // Only update if textarea is empty or if we're adding new symptoms
    if (!currentText || currentText.trim() === '') {
        textarea.value = selectedText;
    } else {
        // Check if selected symptoms aren't already in the text
        const missingSymptoms = Array.from(selectedSymptoms).filter(symptom => 
            !currentText.toLowerCase().includes(symptom.toLowerCase())
        );
        
        if (missingSymptoms.length > 0) {
            textarea.value = currentText + (currentText.endsWith('.') || currentText.endsWith(',') ? ' ' : ', ') + missingSymptoms.join(', ');
        }
    }
    
    // Update character count
    updateCharCount();
}

// Character counter for symptoms textarea
function updateCharCount() {
    const textarea = document.getElementById('symptoms');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        const count = textarea.value.length;
        charCount.textContent = count;
        
        // Color coding
        if (count > 900) {
            charCount.style.color = '#f44336';
        } else if (count > 700) {
            charCount.style.color = '#ff9800';
        } else {
            charCount.style.color = '#999';
        }
    }
}

// Reset form
function resetForm() {
    const form = document.getElementById('symptomForm');
    if (form) {
        if (confirm('Are you sure you want to reset the form? All entered information will be lost.')) {
            form.reset();
            
            // Clear selected symptoms
            selectedSymptoms.clear();
            document.querySelectorAll('.symptom-chip.selected').forEach(chip => {
                chip.classList.remove('selected');
            });
            
            // Clear results
            const resultsContainer = document.getElementById('resultsContainer');
            if (resultsContainer) {
                resultsContainer.innerHTML = '';
            }
            
            // Reset character count
            updateCharCount();
        }
    }
}

// Show loading state
function showLoading() {
    const resultsContainer = document.getElementById('resultsContainer');
    resultsContainer.innerHTML = `
        <div class="spinner-container">
            <div class="spinner"></div>
            <div class="loading-text">Analyzing your symptoms with AI...</div>
            <p style="color: #999; font-size: 0.9rem; margin-top: 0.5rem;">This may take a few moments</p>
        </div>
    `;
    
    // Scroll to results
    resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Display results
function displayResults(html) {
    const resultsContainer = document.getElementById('resultsContainer');
    resultsContainer.innerHTML = html;
    
    // Scroll to results
    setTimeout(() => {
        resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
}

// Display error message
function displayError(message) {
    const resultsContainer = document.getElementById('resultsContainer');
    resultsContainer.innerHTML = `
        <div class="alert" style="background: linear-gradient(135deg, #ffebee, #ffcdd2); border-left: 5px solid #f44336; color: #c62828;">
            <div class="alert-icon">❌</div>
            <div class="alert-content">
                <strong>Error:</strong> ${message}
                <p style="margin-top: 0.5rem;">Please try again or contact support if the problem persists.</p>
            </div>
        </div>
    `;
}

// Rule-based fallback analysis
function getFallbackAnalysis(symptoms, duration, age) {
    const lower = symptoms.toLowerCase();
    let html = '';
    let isEmergency = false;

    // Check for emergency symptoms
    if (lower.includes('chest pain') || lower.includes('chest pressure') ||
        lower.includes('difficulty breathing') || lower.includes('shortness of breath') ||
        lower.includes('severe headache') || lower.includes('worst headache') ||
        lower.includes('unconscious') || lower.includes('seizure') ||
        lower.includes('severe bleeding') || lower.includes('paralysis')) {
        
        isEmergency = true;
        html = `
            <div class="emergency-alert">
                <h4>⚠️ EMERGENCY - SEEK IMMEDIATE MEDICAL ATTENTION</h4>
                <p><strong>Your symptoms may indicate a serious medical condition that requires urgent care.</strong></p>
                
                <p><strong>Take Action Now:</strong></p>
                <ul>
                    <li><strong>Call emergency services (999) immediately</strong></li>
                    <li>Go to the nearest hospital emergency department</li>
                    <li>Do NOT drive yourself - ask someone to take you or call an ambulance</li>
                    <li>If alone and symptoms worsen, call 999</li>
                </ul>
                
                <p style="margin-top: 1rem;"><strong>Time is critical. Do not delay seeking emergency care.</strong></p>
            </div>
        `;
    }
    // Common cold/flu symptoms
    else if ((lower.includes('fever') || lower.includes('cough') || lower.includes('sore throat') || 
              lower.includes('runny nose')) && !lower.includes('severe')) {
        html = `
            <div class="suggestion-box">
                <div class="suggestion-title">💊 Possible Upper Respiratory Infection</div>
                <div class="suggestion-content">
                    <p><strong>Your symptoms suggest a common cold or flu-like illness.</strong></p>
                    
                    <p style="margin-top: 1rem;"><strong>Self-Care Recommendations:</strong></p>
                    <ul>
                        <li><strong>Rest:</strong> Get 7-9 hours of sleep per night</li>
                        <li><strong>Hydration:</strong> Drink plenty of fluids (water, warm tea, soup, broth)</li>
                        <li><strong>Symptom Relief:</strong> Use throat lozenges, steam inhalation, or saline nasal spray</li>
                        <li><strong>Nutrition:</strong> Eat light, nutritious meals even if appetite is reduced</li>
                        <li><strong>Monitoring:</strong> Keep track of your temperature and symptom changes</li>
                    </ul>

                    <p style="margin-top: 1rem;"><strong>When to See a Doctor:</strong></p>
                    <ul>
                        <li>Fever above 39°C (102°F) lasting more than 3 days</li>
                        <li>Symptoms persist or worsen after 7-10 days</li>
                        <li>Difficulty breathing or chest pain develops</li>
                        <li>Severe headache, stiff neck, or confusion</li>
                        <li>Persistent vomiting or inability to keep fluids down</li>
                    </ul>

                    <p style="margin-top: 1.5rem; padding: 1rem; background: #f0f8ff; border-radius: 8px;">
                        <strong>Duration:</strong> ${duration}<br>
                        ${age ? `<strong>Age:</strong> ${age} years<br>` : ''}
                        <strong>Expected Recovery:</strong> Most viral infections improve within 7-10 days with proper self-care.
                    </p>

                    <p style="margin-top: 1.5rem; text-align: center;">
                        <a href="appointment.php" class="btn btn-primary">📅 Book Doctor Appointment</a>
                    </p>
                </div>
            </div>
        `;
    }
    // Digestive issues
    else if (lower.includes('stomach') || lower.includes('nausea') || 
             lower.includes('vomiting') || lower.includes('diarrhea') ||
             lower.includes('abdominal pain')) {
        html = `
            <div class="suggestion-box">
                <div class="suggestion-title">🏥 Possible Gastrointestinal Issue</div>
                <div class="suggestion-content">
                    <p><strong>Your symptoms suggest a digestive system problem.</strong></p>
                    
                    <p style="margin-top: 1rem;"><strong>Immediate Self-Care:</strong></p>
                    <ul>
                        <li><strong>Stay Hydrated:</strong> Sip water, oral rehydration solution, or clear broths frequently</li>
                        <li><strong>Dietary Changes:</strong> Start with bland foods like rice, toast, bananas, applesauce (BRAT diet)</li>
                        <li><strong>Avoid:</strong> Dairy products, fatty foods, spicy foods, caffeine, and alcohol</li>
                        <li><strong>Rest:</strong> Allow your digestive system time to recover</li>
                    </ul>

                    <p style="margin-top: 1rem;"><strong>See a Doctor If:</strong></p>
                    <ul>
                        <li>Symptoms persist for more than 48 hours</li>
                        <li>Blood in vomit or stool</li>
                        <li>Signs of dehydration (dark urine, dizziness, dry mouth, decreased urination)</li>
                        <li>Severe or worsening abdominal pain</li>
                        <li>High fever (above 38.5°C / 101°F)</li>
                        <li>Unable to keep any fluids down for 12+ hours</li>
                    </ul>

                    <p style="margin-top: 1.5rem; padding: 1rem; background: #fff9e6; border-radius: 8px; border-left: 4px solid #ff9800;">
                        <strong>⚠️ Warning Signs:</strong> Severe pain, bloody stools, persistent vomiting, or signs of dehydration require immediate medical attention.
                    </p>

                    <p style="margin-top: 1.5rem; text-align: center;">
                        <a href="appointment.php" class="btn btn-primary">📅 Consult a Doctor</a>
                    </p>
                </div>
            </div>
        `;
    }
    // Headache
    else if (lower.includes('headache') && !lower.includes('severe')) {
        html = `
            <div class="suggestion-box">
                <div class="suggestion-title">🤕 Headache Assessment</div>
                <div class="suggestion-content">
                    <p><strong>Common causes of headaches include tension, dehydration, or stress.</strong></p>
                    
                    <p style="margin-top: 1rem;"><strong>Relief Measures:</strong></p>
                    <ul>
                        <li>Rest in a quiet, dark room</li>
                        <li>Apply a cold or warm compress to your head or neck</li>
                        <li>Stay well hydrated (drink water)</li>
                        <li>Practice relaxation techniques or gentle stretching</li>
                        <li>Avoid bright lights, loud noises, and strong smells</li>
                    </ul>

                    <p style="margin-top: 1rem;"><strong>Seek Medical Attention If:</strong></p>
                    <ul>
                        <li>This is the worst headache you've ever experienced</li>
                        <li>Sudden onset of severe headache (thunderclap headache)</li>
                        <li>Headache accompanied by fever, stiff neck, confusion, or vision changes</li>
                        <li>Headache after a head injury</li>
                        <li>Chronic headaches that are worsening</li>
                        <li>Headache with weakness, numbness, or difficulty speaking</li>
                    </ul>

                    <p style="margin-top: 1.5rem; text-align: center;">
                        <a href="appointment.php" class="btn btn-primary">📅 Book Appointment</a>
                    </p>
                </div>
            </div>
        `;
    }
    // General advice
    else {
        html = `
            <div class="suggestion-box">
                <div class="suggestion-title">🩺 General Health Assessment</div>
                <div class="suggestion-content">
                    <p><strong>Based on your symptoms, here are general recommendations:</strong></p>
                    
                    <p style="margin-top: 1rem;"><strong>Self-Monitoring:</strong></p>
                    <ul>
                        <li>Monitor your symptoms for the next 24-48 hours</li>
                        <li>Keep a symptom diary noting any changes or patterns</li>
                        <li>Take your temperature regularly if you feel warm</li>
                        <li>Note any factors that make symptoms better or worse</li>
                    </ul>

                    <p style="margin-top: 1rem;"><strong>General Care:</strong></p>
                    <ul>
                        <li>Get adequate rest (7-9 hours of sleep)</li>
                        <li>Stay well hydrated</li>
                        <li>Eat a balanced, nutritious diet</li>
                        <li>Avoid strenuous activities until you feel better</li>
                        <li>Practice good hygiene</li>
                    </ul>

                    <p style="margin-top: 1rem;"><strong>Consider Booking an Appointment If:</strong></p>
                    <ul>
                        <li>Symptoms persist or worsen over the next few days</li>
                        <li>You develop new or concerning symptoms</li>
                        <li>Your daily activities are significantly affected</li>
                        <li>You're feeling anxious or concerned about your condition</li>
                    </ul>

                    <p style="margin-top: 1.5rem; padding: 1rem; background: #f0f8ff; border-radius: 8px;">
                        <strong>Symptom Duration:</strong> ${duration}<br>
                        ${age ? `<strong>Your Age:</strong> ${age} years<br>` : ''}
                        <strong>Next Steps:</strong> If symptoms don't improve or you're concerned, please book a consultation with a doctor.
                    </p>

                    <p style="margin-top: 1.5rem; text-align: center;">
                        <a href="appointment.php" class="btn btn-primary">📅 Book Doctor Appointment</a>
                    </p>
                </div>
            </div>
        `;
    }

    return html;
}

// Handle form submission
async function handleFormSubmit(e) {
    e.preventDefault();
    
    const symptoms = document.getElementById('symptoms').value.trim();
    const duration = document.getElementById('duration').value.trim();
    const age = document.getElementById('age').value;
    const additional = document.getElementById('additional')?.value.trim() || '';
    
    // Validate
    if (!symptoms) {
        alert('❌ Please describe your symptoms');
        document.getElementById('symptoms').focus();
        return;
    }
    
    if (!duration) {
        alert('❌ Please specify how long you\'ve had these symptoms');
        document.getElementById('duration').focus();
        return;
    }
    
    // Disable submit button
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>🔄</span> Analyzing...';
    
    // Show loading
    showLoading();
    
    try {
        // Try AI API first
        const response = await fetch('symptomCheckerAPI.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                symptoms,
                duration,
                age,
                additional
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.suggestion) {
            // Display AI-generated suggestion
            displayResults(data.suggestion);
        } else if (data.fallback) {
            // Use rule-based fallback
            console.log('AI unavailable, using rule-based analysis');
            const fallbackHtml = getFallbackAnalysis(symptoms, duration, age);
            displayResults(fallbackHtml);
        } else {
            throw new Error(data.error || 'Unknown error occurred');
        }
        
    } catch (error) {
        console.error('Error:', error);
        
        // Use rule-based fallback on error
        console.log('Using rule-based fallback analysis');
        const fallbackHtml = getFallbackAnalysis(symptoms, duration, age);
        displayResults(fallbackHtml);
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span>🔍</span> Analyze Symptoms';
    }
}

// View previous check
function viewCheck(checkId) {
    // This would typically load the check details via AJAX
    alert(`Loading check #${checkId}...\n\nThis feature would display the full details of your previous symptom check.`);
    
    // In production:
    /*
    fetch(`getSymptomCheck.php?id=${checkId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data.check.ai_response);
            }
        });
    */
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Symptom Checker initialized');
    
    // Initialize symptom chips
    initSymptomChips();
    
    // Character counter for symptoms textarea
    const symptomsTextarea = document.getElementById('symptoms');
    if (symptomsTextarea) {
        symptomsTextarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initial count
        
        // Limit to 1000 characters
        symptomsTextarea.addEventListener('keypress', function(e) {
            if (this.value.length >= 1000 && e.key !== 'Backspace' && e.key !== 'Delete') {
                e.preventDefault();
                alert('⚠️ Maximum character limit (1000) reached');
            }
        });
    }
    
    // Form submission
    const form = document.getElementById('symptomForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
    
    // Add tooltips or help text
    const formLabels = document.querySelectorAll('.form-label');
    formLabels.forEach(label => {
        label.style.cursor = 'help';
    });
    
    // Smooth scroll for navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // Add animation to guidelines
    const guidelineItems = document.querySelectorAll('.guideline-item');
    if (guidelineItems.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.animation = 'fadeIn 0.5s ease forwards';
                    }, index * 100);
                }
            });
        }, { threshold: 0.1 });
        
        guidelineItems.forEach(item => observer.observe(item));
    }
    
    // Auto-save functionality (save to localStorage)
    const autoSaveFields = ['symptoms', 'duration', 'age', 'additional'];
    
    // Load saved data
    autoSaveFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            const savedValue = localStorage.getItem(`symptomChecker_${fieldId}`);
            if (savedValue) {
                field.value = savedValue;
                if (fieldId === 'symptoms') {
                    updateCharCount();
                }
            }
            
            // Auto-save on input
            field.addEventListener('input', function() {
                localStorage.setItem(`symptomChecker_${fieldId}`, this.value);
            });
        }
    });
    
    // Clear auto-save on successful submission
    const originalSubmit = form?.onsubmit;
    if (form) {
        form.addEventListener('submit', function() {
            setTimeout(() => {
                autoSaveFields.forEach(fieldId => {
                    localStorage.removeItem(`symptomChecker_${fieldId}`);
                });
            }, 2000); // Clear after 2 seconds
        });
    }
});

// Export functions for external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        resetForm,
        viewCheck,
        getFallbackAnalysis
    };
}