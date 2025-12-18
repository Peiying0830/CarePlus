function sendMessage() {
    let msg = document.getElementById("userMessage").value.trim();
    if (msg === "") return;

    appendMessage("You", msg, "user");
    
    // Show loading message
    const loadingDiv = document.createElement("div");
    loadingDiv.className = "bot";
    loadingDiv.id = "loading-message";
    loadingDiv.innerHTML = `<b>Bot:</b> <i>Typing...</i>`;
    document.getElementById("chatbox").appendChild(loadingDiv);

    console.log("Sending message:", msg); // DEBUG

    fetch("chatbot_api.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            message: msg,
            session_id: sessionId,
            patient_id: patientId
        })
    })
        .then(res => {
            console.log("Response status:", res.status); // DEBUG
            console.log("Response ok:", res.ok); // DEBUG
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.text(); // Changed to .text() first for debugging
        })
        .then(text => {
            console.log("Raw response:", text); // DEBUG - see actual response
            
            // Remove loading message
            const loading = document.getElementById("loading-message");
            if (loading) loading.remove();
            
            // Try to parse JSON
            let data;
            try {
                data = JSON.parse(text);
                console.log("Parsed JSON:", data); // DEBUG
            } catch (e) {
                console.error("JSON parse error:", e);
                console.error("Response was:", text);
                appendMessage("Bot", "Error: Invalid response from server", "bot");
                return;
            }
            
            // Check for error first
            if (data.error) {
                console.error("API error:", data.error); // DEBUG
                appendMessage("Bot", "Error: " + data.error, "bot");
            } 
            // Check for reply
            else if (data.reply) {
                console.log("Bot reply:", data.reply); // DEBUG
                appendMessage("Bot", data.reply, "bot");
            } 
            // No reply or error
            else {
                console.error("No reply or error in response:", data); // DEBUG
                appendMessage("Bot", "Sorry, I couldn't generate a response.", "bot");
            }
        })
        .catch(error => {
            // Remove loading message if still present
            const loading = document.getElementById("loading-message");
            if (loading) loading.remove();
            
            console.error("Fetch error:", error); // DEBUG
            appendMessage("Bot", "Network error. Please try again. Check console for details.", "bot");
        });

    document.getElementById("userMessage").value = "";
}

function appendMessage(sender, message, type) {
    const box = document.getElementById("chatbox");
    const div = document.createElement("div");
    div.className = type;
    div.innerHTML = `<b>${sender}:</b> ${message}`;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

// Allow Enter key to send message
document.addEventListener("DOMContentLoaded", function() {
    const input = document.getElementById("userMessage");
    if (input) {
        input.addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                sendMessage();
            }
        });
    }
    
    console.log("Chatbot initialized"); // DEBUG
    console.log("Session ID:", sessionId); // DEBUG
    console.log("Patient ID:", patientId); // DEBUG
});