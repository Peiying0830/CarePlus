<?php
require_once 'config.php';
$session_id = session_id();
$patient_id = getUserId() ?? null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>CarePlus AI Assistant</title>
    <link rel="stylesheet" href="assets/css/chatbot.css">
</head>
<body>

<div class="chat-container">
    <h2>🤖 CarePlus AI Assistant</h2>
    <div id="chatbox"></div>
    <div class="chat-input">
        <input type="text" id="userMessage" placeholder="Type your question...">
        <button onclick="sendMessage()">Send</button>
    </div>
</div>

<script>
    const sessionId = "<?= htmlspecialchars($session_id) ?>";
    const patientId = "<?= htmlspecialchars($patient_id) ?>";
</script>

<script src="chatbot.js"></script>
</body>
</html>
