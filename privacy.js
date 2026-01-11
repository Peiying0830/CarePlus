// Security Features
const securityFeatures = [
    { 
        icon: "🔐", 
        title: "End-to-End Encryption", 
        text: "All your medical data is encrypted using military-grade AES-256 encryption, ensuring maximum security for your sensitive health information." 
    },
    { 
        icon: "🛡️", 
        title: "HIPAA Compliant", 
        text: "Our platform strictly adheres to HIPAA regulations and healthcare data protection standards to safeguard your privacy." 
    },
    { 
        icon: "👁️", 
        title: "Granular Access Control", 
        text: "Only authorized healthcare providers with your explicit permission can access your medical records. You control who sees what." 
    },
    { 
        icon: "📊", 
        title: "Complete Audit Trails", 
        text: "Every single access to your data is logged, timestamped, and monitored. You can review who accessed your records and when." 
    },
    { 
        icon: "🔄", 
        title: "Automated Backups", 
        text: "Your data is automatically backed up multiple times daily across geographically distributed secure servers with 99.99% uptime." 
    },
    { 
        icon: "🚨", 
        title: "24/7 Security Monitoring", 
        text: "Our dedicated security operations center continuously monitors for threats, anomalies, and potential breaches around the clock." 
    },
];

const securityGrid = document.getElementById("security-grid");
if (securityGrid) {
    securityFeatures.forEach((feature, index) => {
        const card = document.createElement("div");
        card.className = "security-card";
        card.style.animationDelay = `${index * 0.1}s`;
        card.innerHTML = `
            <div class="security-icon">${feature.icon}</div>
            <h3 class="security-title">${feature.title}</h3>
            <p class="security-text">${feature.text}</p>
        `;
        securityGrid.appendChild(card);
    });
}

// Protection Features List
const protectionFeatures = [
    { 
        icon: "🔐", 
        text: "<strong>Military-Grade Encryption:</strong> All data transmitted between your device and our servers uses TLS 1.3 protocol. Data stored at rest is protected with AES-256 encryption." 
    },
    { 
        icon: "🔑", 
        text: "<strong>Advanced Authentication:</strong> Multi-factor authentication (MFA) is required for all accounts. Passwords are hashed using bcrypt with individual salts for maximum security." 
    },
    { 
        icon: "🏥", 
        text: "<strong>Role-Based Access Control:</strong> Healthcare providers can only access patient records they're authorized to view. Access levels are strictly enforced and regularly audited." 
    },
    { 
        icon: "📱", 
        text: "<strong>Device Security:</strong> Secure authentication tokens on all devices with automatic session timeout. You receive instant notifications for all login attempts." 
    },
    { 
        icon: "🔍", 
        text: "<strong>Privacy by Design:</strong> We collect only the minimum data necessary to provide our services. Your information is never sold, rented, or shared with third parties for marketing." 
    },
    { 
        icon: "🚫", 
        text: "<strong>Zero Unauthorized Sharing:</strong> Your health data is never shared with insurance companies, employers, or other entities without your explicit written consent." 
    },
];

const featureListContainer = document.getElementById("feature-list");
if (featureListContainer) {
    protectionFeatures.forEach((feature, index) => {
        const li = document.createElement("li");
        li.style.animationDelay = `${index * 0.1}s`;
        li.innerHTML = `
            <span class="feature-icon">${feature.icon}</span>
            <div>${feature.text}</div>
        `;
        featureListContainer.appendChild(li);
    });
}

// Privacy Rights
const privacyRights = [
    "Access and view all your medical records at any time through your secure portal",
    "Download complete copies of your health data in standard, machine-readable formats",
    "Request corrections to any inaccurate or incomplete information in your records",
    "Control exactly who can view your medical records with fine-grained permissions",
    "Revoke access permissions from any healthcare provider at any time",
    "Request complete deletion of your account and all associated data",
    "Export your entire data portfolio to transfer to another healthcare provider"
];

const rightsContainer = document.getElementById("privacy-rights");
if (rightsContainer) {
    const ul = document.createElement("ul");
    privacyRights.forEach((right, index) => {
        const li = document.createElement("li");
        li.textContent = right;
        li.style.animationDelay = `${index * 0.05}s`;
        ul.appendChild(li);
    });
    rightsContainer.appendChild(ul);
}

// Compliance Badges
const complianceBadges = [
    "🏥 HIPAA Compliant",
    "🔒 ISO 27001 Certified",
    "🛡️ SOC 2 Type II Audited",
    "🇪🇺 GDPR Compliant",
    "🇲🇾 PDPA Compliant"
];

const badgesContainer = document.getElementById("compliance-badges");
if (badgesContainer) {
    complianceBadges.forEach((badge, index) => {
        const div = document.createElement("div");
        div.className = "badge";
        div.textContent = badge;
        div.style.animationDelay = `${index * 0.1}s`;
        badgesContainer.appendChild(div);
    });
}

// Smooth Scroll Behavior
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

// Intersection Observer for Animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all content boxes
document.querySelectorAll('.content-box').forEach(box => {
    box.style.opacity = '0';
    box.style.transform = 'translateY(30px)';
    box.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(box);
});

// Add Loading State
window.addEventListener('load', () => {
    document.body.classList.add('loaded');
});