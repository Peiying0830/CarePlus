// Chatbot Toggle
const chatbotBtn = document.getElementById("chatbot-btn");
const chatbotBox = document.getElementById("chatbot-box");
const chatArea = document.getElementById("chat-area");

chatbotBtn.onclick = () => {
    chatbotBox.style.display = chatbotBox.style.display === "flex" ? "none" : "flex";

    if (chatArea.children.length === 0) {
        appendBot("👋 Hi! I'm your CarePlus AI assistant. How can I help you today?");
    }
};

document.querySelector(".chatbot-close").onclick = () => {
    chatbotBox.style.display = "none";
};

// Send message
document.getElementById("chat-send").onclick = sendMessage;
document.getElementById("chat-input").addEventListener("keypress", function (e) {
    if (e.key === "Enter") sendMessage();
});

function sendMessage() {
    const input = document.getElementById("chat-input");
    const text = input.value.trim();
    if (!text) return;

    appendUser(text);
    input.value = "";

    const loadingId = appendBot("⏳ Typing...");

    fetch("chatbot_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            message: text,
            session_id: session_id_php,
            patient_id: patient_id_php
        })
    })
        .then(res => res.json())
        .then(data => {
            document.getElementById(loadingId)?.remove();
            appendBot(data.reply || "⚠️ Error processing response");
        })
        .catch(() => {
            document.getElementById(loadingId)?.remove();
            appendBot("⚠️ Unable to connect to server.");
        });
}

function appendUser(msg) {
    const div = document.createElement("div");
    div.className = "user-message";
    div.textContent = msg;
    chatArea.appendChild(div);
    chatArea.scrollTop = chatArea.scrollHeight;
}

function appendBot(msg) {
    const div = document.createElement("div");
    div.className = "bot-message";
    const id = "msg-" + Date.now();
    div.id = id;
    div.textContent = msg;
    chatArea.appendChild(div);
    chatArea.scrollTop = chatArea.scrollHeight;
    return id;
}
