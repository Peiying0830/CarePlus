<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Clear any output that might have been generated
ob_start();

try {
    $input = file_get_contents("php://input");
    
    if (empty($input)) {
        throw new Exception("No input received");
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON");
    }
    
    $user_message = isset($data['message']) ? trim($data['message']) : '';
    
    if (empty($user_message)) {
        throw new Exception("Message is empty");
    }
    
    $session_id = isset($data['session_id']) ? $data['session_id'] : 'unknown';
    $patient_id = isset($data['patient_id']) ? $data['patient_id'] : null;
    
    // API KEY
    $api_key = "AIzaSyAzfNamiE6bM_6auc1qqeysHFnirSQVfvQ";
    
    // Prepare payload
    $payload = json_encode([
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => "You are a friendly medical assistant for CarePlus Clinic. Provide brief, helpful health information (2-3 sentences). Always remind users to consult healthcare professionals for medical advice.\n\nUser: " . $user_message
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "topK" => 40,
            "topP" => 0.95,
            "maxOutputTokens" => 200
        ]
    ]);
    
    // Use gemini-2.5-flash
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=" . $api_key;
    
    $ch = curl_init($url);
    
    if ($ch === false) {
        throw new Exception("Failed to initialize cURL");
    }
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    
    curl_close($ch);
    
    // Check for connection errors
    if ($curl_errno !== 0) {
        throw new Exception("Connection failed: " . $curl_error);
    }
    
    // Handle quota exceeded
    if ($http_code === 429) {
        ob_end_clean();
        echo json_encode([
            "reply" => "Too many requests. Please try again in 60 seconds.",
            "error" => "rate_limit"
        ]);
        exit;
    }
    
    // Handle other HTTP errors
    if ($http_code !== 200) {
        $error_data = json_decode($response, true);
        $error_msg = "API error (HTTP " . $http_code . ")";
        
        if (isset($error_data['error']['message'])) {
            $error_msg = $error_data['error']['message'];
        }
        
        throw new Exception($error_msg);
    }
    
    // Parse response
    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to parse API response");
    }
    
    // Extract bot reply safely
    $bot_reply = "I'm sorry, I couldn't answer that right now. Please consult a healthcare professional for proper medical advice.";

    if (!empty($result['candidates'])) {
        $candidate = $result['candidates'][0];

        if (isset($candidate['content']['parts'])) {
            foreach ($candidate['content']['parts'] as $part) {
                if (!empty($part['text'])) {
                    $bot_reply = trim($part['text']);
                    break;
                }
            }
        }
    }

    // Try to save to database
    try {
        if (file_exists(__DIR__ . '/config.php')) {
            require_once __DIR__ . '/config.php';
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO chatbot_logs (patient_id, session_id, user_message, bot_response, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$patient_id, $session_id, $user_message, $bot_reply]);
        }
    } catch (Exception $db_error) {
        error_log("DB save failed: " . $db_error->getMessage());
    }
    
    // IMPORTANT: Clear buffer and send CORRECT response structure
    ob_end_clean();
    echo json_encode([
        "reply" => $bot_reply  // This is what chatbot.js expects!
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    error_log("Chatbot error: " . $e->getMessage());
    
    // Return error in the correct format
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}
?>