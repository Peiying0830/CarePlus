// security features
const securityFeatures = [
    { icon: "🔐", title: "End-to-End Encryption", text: "All your medical data is encrypted using AES-256 encryption." },
    { icon: "🛡️", title: "HIPAA Compliant", text: "Our platform follows strict healthcare data protection standards." },
    { icon: "👁️", title: "Access Control", text: "Only authorized healthcare providers with your permission can access your records." },
    { icon: "📊", title: "Audit Trails", text: "Every access to your data is logged and monitored." },
    { icon: "🔄", title: "Regular Backups", text: "Data is backed up multiple times daily across secure servers." },
    { icon: "🚨", title: "24/7 Monitoring", text: "Our security team continuously monitors for threats." },
];

const securityGrid = document.getElementById("security-grid");
securityFeatures.forEach(f => {
    const card = document.createElement("div");
    card.className = "security-card";
    card.innerHTML = `
        <div class="security-icon">${f.icon}</div>
        <h3 class="security-title">${f.title}</h3>
        <p class="security-text">${f.text}</p>
    `;
    securityGrid.appendChild(card);
});

// feature list
const featureList = [
    { icon: "🔐", text: "All data transmitted is encrypted using TLS 1.3. Data at rest is AES-256." },
    { icon: "🔑", text: "Multi-factor authentication (MFA) and bcrypt password hashing are used." },
    { icon: "🏥", text: "Role-Based Access: Only authorized providers can access patient records." },
    { icon: "📱", text: "Device Security: Secure authentication on all devices with login notifications." },
    { icon: "🔍", text: "Privacy by Design: We collect minimum data necessary and never sell your info." },
    { icon: "🚫", text: "No Unauthorized Sharing: Your data is never shared without consent." },
];

const featureListContainer = document.getElementById("feature-list");
featureList.forEach(f => {
    const li = document.createElement("li");
    li.innerHTML = `<span class="feature-icon">${f.icon}</span><div>${f.text}</div>`;
    featureListContainer.appendChild(li);
});

// privacy rights
const rights = [
    "Access all your medical records at any time",
    "Download copies of your health data",
    "Request corrections to inaccurate information",
    "Control who can view your medical records",
    "Revoke access permissions at any time",
    "Request deletion of your account and data",
    "Export your data to another healthcare provider"
];

const rightsContainer = document.getElementById("privacy-rights");
const ul = document.createElement("ul");
rights.forEach(r => {
    const li = document.createElement("li");
    li.textContent = r;
    ul.appendChild(li);
});
rightsContainer.appendChild(ul);

// compliance badges
const badges = [
    "🏥 HIPAA Compliant",
    "🔒 ISO 27001 Certified",
    "🛡️ SOC 2 Type II",
    "🇪🇺 GDPR Compliant",
    "🇲🇾 PDPA Compliant"
];

const badgesContainer = document.getElementById("compliance-badges");
badges.forEach(b => {
    const div = document.createElement("div");
    div.className = "badge";
    div.textContent = b;
    badgesContainer.appendChild(div);
});
