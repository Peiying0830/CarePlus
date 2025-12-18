<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$symptoms = trim($input['symptoms'] ?? '');
$duration = trim($input['duration'] ?? '');
$age = $input['age'] ?? null;

// Validate input
if (empty($symptoms)) {
    echo json_encode(['success' => false, 'error' => 'Symptoms description is required']);
    exit;
}

// Google Gemini API Configuration - YOUR API KEY
$apiKey = 'AIzaSyAfuPhHnwRphOp5Cy7NOum-xNn2pRKw6QU';
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

// Prepare the prompt for Gemini
$prompt = "You are a medical AI assistant providing preliminary symptom analysis. 

Patient Information:
- Symptoms: {$symptoms}
- Duration: {$duration}
" . ($age ? "- Age: {$age} years\n" : "") . "

Please analyze these symptoms and provide:
1. Possible conditions (not definitive diagnoses)
2. Self-care recommendations
3. When to see a doctor
4. Any red flags requiring immediate attention

IMPORTANT: Format your response in HTML with these specific styles:
- Use <div class='emergency-alert'> for emergency situations requiring immediate medical attention
- Use <div class='suggestion-box'> for general advice
- Include <div class='suggestion-title'> for section headers
- Use <div class='suggestion-content'> for main content
- Use <ul> and <li> for lists
- Use <strong> for emphasis
- Include this link at the end: <a href='appointmentDashboard.php' class='btn btn-primary' style='display:inline-block; padding:1rem 2rem; background:linear-gradient(135deg, #50C878, #27AE60); color:white; text-decoration:none; border-radius:10px; margin-top:1rem;'>📅 Book Appointment</a>

Be empathetic, clear, and medically responsible. Always include a disclaimer that this is preliminary advice and not a substitute for professional medical diagnosis.";

// Prepare API request for Gemini
$requestData = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 2048,
    ],
    'safetySettings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ]
    ]
];

// Initialize cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle errors
if ($curlError) {
    error_log("Symptom Checker API cURL Error: " . $curlError);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to connect to AI service',
        'fallback' => true
    ]);
    exit;
}

if ($httpCode !== 200) {
    error_log("Symptom Checker API Error: HTTP {$httpCode} - {$response}");
    echo json_encode([
        'success' => false,
        'error' => 'AI service returned an error. Please try again.',
        'fallback' => true,
        'debug' => json_decode($response, true)
    ]);
    exit;
}

// Parse response
$apiResponse = json_decode($response, true);

if (!$apiResponse || !isset($apiResponse['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response from AI service',
        'fallback' => true
    ]);
    exit;
}

$suggestion = $apiResponse['candidates'][0]['content']['parts'][0]['text'];

// Log the symptom check (optional - store in database)
try {
    $stmt = $pdo->prepare("
        INSERT INTO symptom_checks (user_id, symptoms, duration, age, ai_response, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $symptoms,
        $duration,
        $age,
        $suggestion
    ]);
} catch (Exception $e) {
    // Log error but don't fail the request
    error_log("Failed to log symptom check: " . $e->getMessage());
}

// Return successful response
echo json_encode([
    'success' => true,
    'suggestion' => $suggestion,
    'timestamp' => date('Y-m-d H:i:s')
]);