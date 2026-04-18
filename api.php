<?php
// Frontend ko allow karne ke liye headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 🛑 APNI ASLI API KEYS YAHAN DALO (KISI KO MAT DIKHANA)
$GEMINI_API_KEY     = "AIzaSyAF7DhzuD3AhTnNTydKPMmN77MsFcdtL7E";
$CHATGPT_API_KEY    = "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";
$GROQ_API_KEY       = "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";
$OPENROUTER_API_KEY = "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";

// Frontend se aaya hua JSON data padho
$requestData = json_decode(file_get_contents('php://input'), true);

if (!$requestData) {
    echo json_encode(["reply" => "Trinova Core: Access Denied. No data received."]);
    exit;
}

// Variables nikalna
$modelToUse = strtolower($requestData['model'] ?? 'gemini'); 
$userMessage = $requestData['message'];
$aiName = $requestData['ai_name'] ?? 'AI';

// 🚀 YAHAN MAIN CHANGE KIYA HAI: HINGLISH SYSTEM PROMPT
$systemPrompt = "You are $aiName. Keep your replies very short, conversational, and natural like a WhatsApp text message. Do not act like an AI. You are chatting with a human friend. You MUST reply ONLY in Hinglish (Hindi language written using the English alphabet, for example: 'Haan bhai, kaisa hai? Kya chal raha hai?'). NEVER use pure English. NEVER use the Hindi script (Devanagari). Be casual and friendly.";

$aiReply = "";

// ==========================================
// 🚀 1. GEMINI API LOGIC
// ==========================================
if ($modelToUse === 'gemini') {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $GEMINI_API_KEY;
    
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $systemPrompt . "\n\nUser says: " . $userMessage]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    if(isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $aiReply = $responseData['candidates'][0]['content']['parts'][0]['text'];
    } else {
        $aiReply = "Gemini API Error: " . $response;
    }
} 

// ==========================================
// 🚀 2. CHATGPT API LOGIC (OpenAI)
// ==========================================
else if ($modelToUse === 'chatgpt') {
    $url = "https://api.openai.com/v1/chat/completions";
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $CHATGPT_API_KEY
    ];
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => $userMessage]
        ]
    ];

    $aiReply = makeOpenAIRequest($url, $headers, $data);
}

// ==========================================
// 🚀 3. GROQ API LOGIC (Ultra Fast)
// ==========================================
else if ($modelToUse === 'groq') {
    $url = "https://api.groq.com/openai/v1/chat/completions";
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $GROQ_API_KEY
    ];
    $data = [
        "model" => "llama3-8b-8192", 
        "messages" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => $userMessage]
        ]
    ];

    $aiReply = makeOpenAIRequest($url, $headers, $data);
}

// ==========================================
// 🚀 4. OPENROUTER API LOGIC
// ==========================================
else if ($modelToUse === 'openrouter') {
    $url = "https://openrouter.ai/api/v1/chat/completions";
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $OPENROUTER_API_KEY,
        "HTTP-Referer: http://localhost",
        "X-Title: Trinova"
    ];
    $data = [
        "model" => "mistralai/mistral-7b-instruct:free",
        "messages" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => $userMessage]
        ]
    ];

    $aiReply = makeOpenAIRequest($url, $headers, $data);
}

// Default fallback
else {
    $aiReply = "Error: Invalid AI Model selected ($modelToUse).";
}

// Wapas Frontend ko reply bhejna
echo json_encode([
    "reply" => trim($aiReply)
]);

// 🛠️ HELPER FUNCTION
function makeOpenAIRequest($url, $headers, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return "cURL Error: " . $error;
    }

    $responseData = json_decode($response, true);
    if(isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    } else {
        return "API Error: " . $response;
    }
}
?>
