// Tab switching functionality
function switchTab(tabName) {
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
    const clickedBtn = event?.target?.closest('.tab-btn');
    if (clickedBtn) {
        clickedBtn.classList.add('active');
    }
    
    // Save active tab to localStorage
    try {
        localStorage.setItem('activeRecordsTab', tabName);
    } catch (e) {
        console.log('Could not save tab state');
    }
}

// View record details
function viewRecord(recordId) {
    if (recordId) {
        window.location.href = `medicalRecords.php?record=${recordId}`;
    }
}

// Print specific record
function printRecord(recordId) {
    if (recordId) {
        window.print();
    }
}

// Download record as PDF (mock function - would require server-side implementation)
function downloadRecord(recordId) {
    alert(`Downloading record #${recordId}...\n\nNote: This feature requires server-side PDF generation.\nPlease use the Print function and save as PDF for now.`);
    
    // In production, you would make an AJAX call to generate PDF:
    /*
    fetch(`generate_pdf.php?record_id=${recordId}`)
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `medical_record_${recordId}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Error downloading PDF:', error);
            alert('Failed to download record. Please try again.');
        });
    */
}

// Export all records
function exportRecords() {
    if (confirm('Export all medical records as PDF?\n\nThis will create a comprehensive document of your medical history.')) {
        alert('Preparing export...\n\nNote: This feature requires server-side PDF generation.\nPlease use the Print function for now.');
        
        // In production:
        // window.location.href = 'export_all_records.php';
    }
}

// Search/Filter records
function filterRecords(searchTerm) {
    const records = document.querySelectorAll('.record-card');
    const lowerSearchTerm = searchTerm.toLowerCase();
    let visibleCount = 0;
    
    records.forEach(record => {
        const text = record.textContent.toLowerCase();
        if (text.includes(lowerSearchTerm)) {
            record.style.display = '';
            visibleCount++;
        } else {
            record.style.display = 'none';
        }
    });
    
    // Show empty state if no results
    const recordsGrid = document.querySelector('.records-grid');
    if (recordsGrid) {
        let emptyState = recordsGrid.querySelector('.search-empty-state');
        
        if (visibleCount === 0 && searchTerm) {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'empty-state search-empty-state';
                emptyState.style.gridColumn = '1 / -1';
                emptyState.innerHTML = `
                    <div class="empty-icon">🔍</div>
                    <h3>No records found</h3>
                    <p>No medical records match your search for "${searchTerm}"</p>
                `;
                recordsGrid.appendChild(emptyState);
            }
        } else if (emptyState) {
            emptyState.remove();
        }
    }
}

// Initialize search functionality
function initSearch() {
    const searchInput = document.getElementById('recordSearch');
    if (searchInput) {
        // Debounce search input
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterRecords(e.target.value);
            }, 300);
        });
        
        // Clear search
        const clearBtn = document.getElementById('clearSearch');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterRecords('');
            });
        }
    }
}

// Format dates for better readability
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Calculate age from date
function calculateAge(dateString) {
    const birthDate = new Date(dateString);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age;
}

// Animate stat counters
function animateStatCounters() {
    const statValues = document.querySelectorAll('.stat-value');
    
    statValues.forEach(stat => {
        const text = stat.textContent.trim();
        const isNumber = !isNaN(parseInt(text));
        
        if (isNumber) {
            const target = parseInt(text);
            let current = 0;
            const increment = target / 30; // 30 frames
            const duration = 1000; // 1 second
            const frameTime = duration / 30;
            
            stat.textContent = '0';
            
            const counter = setInterval(() => {
                current += increment;
                if (current >= target) {
                    stat.textContent = target;
                    clearInterval(counter);
                } else {
                    stat.textContent = Math.floor(current);
                }
            }, frameTime);
        }
    });
}

// Add reading time estimate for records
function addReadingTimeEstimate() {
    const diagnosisElements = document.querySelectorAll('.record-diagnosis, .info-content');
    
    diagnosisElements.forEach(element => {
        const text = element.textContent;
        const wordCount = text.split(/\s+/).length;
        const readingTime = Math.ceil(wordCount / 200); // Average reading speed: 200 words/minute
        
        if (readingTime > 1 && !element.querySelector('.reading-time')) {
            const badge = document.createElement('span');
            badge.className = 'meta-badge reading-time';
            badge.textContent = `📖 ${readingTime} min read`;
            badge.style.marginLeft = '0.5rem';
            
            const parent = element.closest('.record-card, .info-section');
            if (parent) {
                const metaContainer = parent.querySelector('.record-meta') || parent;
                metaContainer.appendChild(badge);
            }
        }
    });
}

// Initialize tooltips for medical terms
function initMedicalTermTooltips() {
    const medicalTerms = {
        'hypertension': 'High blood pressure',
        'diabetes': 'A condition affecting blood sugar levels',
        'diagnosis': 'The identification of a disease or condition',
        'prescription': 'A written order for medication',
        'symptoms': 'Physical or mental features indicating a condition',
        'prognosis': 'The likely course of a disease or ailment'
    };
    
    const contentElements = document.querySelectorAll('.info-content, .record-diagnosis, .timeline-diagnosis');
    
    contentElements.forEach(element => {
        let html = element.innerHTML;
        
        Object.keys(medicalTerms).forEach(term => {
            const regex = new RegExp(`\\b${term}\\b`, 'gi');
            html = html.replace(regex, match => {
                return `<span class="medical-term" title="${medicalTerms[term.toLowerCase()]}">${match}</span>`;
            });
        });
        
        element.innerHTML = html;
    });
}

// Add smooth scroll behavior
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Initialize keyboard shortcuts
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + P: Print
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
        
        // Ctrl/Cmd + F: Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            const searchInput = document.getElementById('recordSearch');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape: Clear search or go back
        if (e.key === 'Escape') {
            const searchInput = document.getElementById('recordSearch');
            if (searchInput && searchInput.value) {
                searchInput.value = '';
                filterRecords('');
            } else if (window.location.search.includes('record=')) {
                window.history.back();
            }
        }
        
        // Arrow keys for tab navigation
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            const activeTab = document.querySelector('.tab-btn.active');
            if (activeTab) {
                const tabs = Array.from(document.querySelectorAll('.tab-btn'));
                const currentIndex = tabs.indexOf(activeTab);
                let nextIndex;
                
                if (e.key === 'ArrowRight') {
                    nextIndex = (currentIndex + 1) % tabs.length;
                } else {
                    nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                }
                
                tabs[nextIndex].click();
            }
        }
    });
}

// Load active tab from localStorage
function loadActiveTab() {
    try {
        const savedTab = localStorage.getItem('activeRecordsTab');
        if (savedTab) {
            const tabButton = document.querySelector(`[onclick*="switchTab('${savedTab}')"]`);
            if (tabButton) {
                tabButton.click();
            }
        }
    } catch (e) {
        console.log('Could not load saved tab state');
    }
}

// Add card click animation
function initCardAnimations() {
    const cards = document.querySelectorAll('.record-card');
    
    cards.forEach(card => {
        card.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        card.addEventListener('mouseup', function() {
            this.style.transform = '';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
}

// Initialize lazy loading for images (if any)
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Create a summary timeline view
function createTimelineView() {
    const records = document.querySelectorAll('.record-card');
    if (records.length === 0) return;
    
    // This function would create a visual timeline of medical events
    // Implementation would depend on specific requirements
    console.log('Timeline view created with', records.length, 'records');
}

// Export to CSV functionality
function exportToCSV() {
    const records = [];
    document.querySelectorAll('.record-card').forEach(card => {
        const id = card.querySelector('.record-id')?.textContent || '';
        const date = card.querySelector('.record-date-badge')?.textContent || '';
        const doctor = card.querySelector('.record-title')?.textContent || '';
        const specialty = card.querySelector('.record-specialty')?.textContent || '';
        const diagnosis = card.querySelector('.record-diagnosis')?.textContent || '';
        
        records.push({
            ID: id.trim(),
            Date: date.trim(),
            Doctor: doctor.trim(),
            Specialty: specialty.trim(),
            Diagnosis: diagnosis.trim().replace(/\n/g, ' ')
        });
    });
    
    if (records.length === 0) {
        alert('No records to export');
        return;
    }
    
    // Convert to CSV
    const headers = Object.keys(records[0]);
    const csvContent = [
        headers.join(','),
        ...records.map(record => 
            headers.map(header => `"${record[header]}"`).join(',')
        )
    ].join('\n');
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `medical_records_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Medical Records system initialized');
    
    // Load saved tab state
    loadActiveTab();
    
    // Initialize search functionality
    initSearch();
    
    // Animate stat counters
    setTimeout(animateStatCounters, 300);
    
    // Add reading time estimates
    addReadingTimeEstimate();
    
    // Initialize medical term tooltips
    initMedicalTermTooltips();
    
    // Initialize smooth scroll
    initSmoothScroll();
    
    // Initialize keyboard shortcuts
    initKeyboardShortcuts();
    
    // Initialize card animations
    initCardAnimations();
    
    // Initialize lazy loading
    initLazyLoading();
    
    // Add stagger animation to record cards
    const recordCards = document.querySelectorAll('.record-card');
    recordCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Add print optimization
    window.addEventListener('beforeprint', function() {
        document.body.classList.add('printing');
    });
    
    window.addEventListener('afterprint', function() {
        document.body.classList.remove('printing');
    });
    
    // Add stat card hover effects
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.querySelector('.stat-icon').style.transform = 'scale(1.1) rotate(5deg)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.querySelector('.stat-icon').style.transform = 'scale(1) rotate(0deg)';
        });
    });
    
    // Initialize timeline markers animation
    const timelineMarkers = document.querySelectorAll('.timeline-marker');
    if (timelineMarkers.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'pulse 2s ease infinite';
                }
            });
        }, { threshold: 0.5 });
        
        timelineMarkers.forEach(marker => observer.observe(marker));
    }
    
    // Add context menu for record cards
    recordCards.forEach(card => {
        card.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            
            const recordId = this.querySelector('.record-id')?.textContent.replace('#', '');
            if (recordId && confirm('Quick actions for this record:\n\n- View Details\n- Print Record\n- Download PDF\n\nPress OK to view details')) {
                viewRecord(recordId);
            }
        });
    });
    
    // Check for URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('record')) {
        // Scroll to top of record detail
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // Add year section collapse functionality
    const yearHeaders = document.querySelectorAll('.year-header');
    yearHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const recordsGrid = this.nextElementSibling;
            if (recordsGrid && recordsGrid.classList.contains('records-grid')) {
                recordsGrid.style.display = recordsGrid.style.display === 'none' ? 'grid' : 'none';
                
                const icon = this.querySelector('.year-title');
                if (icon) {
                    icon.textContent = recordsGrid.style.display === 'none' 
                        ? '📁 ' + icon.textContent.replace('📅 ', '').replace('📁 ', '')
                        : '📅 ' + icon.textContent.replace('📅 ', '').replace('📁 ', '');
                }
            }
        });
    });
});

// Export functions for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        switchTab,
        viewRecord,
        printRecord,
        downloadRecord,
        exportRecords,
        filterRecords,
        formatDate,
        calculateAge,
        exportToCSV
    };
}