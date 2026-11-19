<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');

require_once 'config.php';

$apiKey = 'YOUR_GEMINI_API_KEY';

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Please enter a message.']);
    exit;
}

if (mb_strlen($userMessage) > 300) {
    $userMessage = mb_substr($userMessage, 0, 300);
}

$conn->query("
    CREATE TABLE IF NOT EXISTS ai_usage_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_identifier VARCHAR(100) NOT NULL,
        usage_date DATE NOT NULL,
        request_count INT DEFAULT 0,
        tokens_used INT DEFAULT 0,
        UNIQUE KEY unique_user_date (user_identifier, usage_date)
    )
");

$isLoggedIn = isset($_SESSION['user_id']);
$identifier = $isLoggedIn ? 'user_' . $_SESSION['user_id'] : 'ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$today = date('Y-m-d');

$dailyMsgLimit   = $isLoggedIn ? 15 : 4;
$dailyTokenLimit = $isLoggedIn ? 12000 : 3000;

$currentMsgs = 0;
$currentTokens = 0;

$stmt = $conn->prepare("SELECT request_count, tokens_used FROM ai_usage_limits WHERE user_identifier = ? AND usage_date = ?");
if ($stmt) {
    $stmt->bind_param("ss", $identifier, $today);
    $stmt->execute();
    $usage = $stmt->get_result()->fetch_assoc();
    $currentMsgs   = $usage['request_count'] ?? 0;
    $currentTokens = $usage['tokens_used'] ?? 0;
}

if ($currentMsgs >= $dailyMsgLimit || $currentTokens >= $dailyTokenLimit) {
    $limitMsg = $isLoggedIn
        ? "You have reached your daily AI usage limit ({$dailyMsgLimit} messages). Please try again tomorrow."
        : "You have reached the daily guest limit ({$dailyMsgLimit} messages). Please log in to continue using NSport Pulse AI.";
    echo json_encode([
        'reply' => $limitMsg,
        'remaining_messages' => 0
    ]);
    exit;
}

$clubsList = [];
$cRes = $conn->query("SELECT club_name FROM clubs ORDER BY club_name");
if ($cRes) {
    while ($r = $cRes->fetch_assoc()) {
        $clubsList[] = $r['club_name'];
    }
}
$clubsText = !empty($clubsList) ? implode(', ', $clubsList) : 'Athletics, Badminton, Chess, Cricket, Football, Rugby, Swimming, Table Tennis, Tennis, Volleyball';

$matchesList = [];
$mRes = $conn->query("SELECT title, sport_type, event_date, event_time, venue FROM matches_schedules WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");
if ($mRes) {
    while ($r = $mRes->fetch_assoc()) {
        $matchesList[] = "{$r['title']} ({$r['sport_type']}) on {$r['event_date']} at {$r['event_time']} - {$r['venue']}";
    }
}
$matchesText = !empty($matchesList) ? implode(' | ', $matchesList) : 'No upcoming matches scheduled right now.';

$systemPrompt = "You are NSport Pulse AI, the official assistant for NSBM SportsHub (NSBM Green University). "
    . "STRICT RULES: "
    . "1. Reply ONLY in clear, professional English. Never use Sinhala characters or Sinhala words even if the user writes in Sinhala. "
    . "2. NEVER use any emojis, emoticons, or decorative symbols in your response. Use plain text only. "
    . "3. Keep answers concise, accurate, and under 120 words. "
    . "Available Sports Clubs: {$clubsText}. "
    . "Upcoming Matches: {$matchesText}. "
    . "To join a club, students go to 'Sports Clubs', select a sport, and submit a join request (must be logged in). "
    . "To book a facility, students go to 'Facility Bookings', select a venue, date, and time slot, and submit for admin approval.";

$payload = [
    "system_instruction" => [
        "parts" => [
            ["text" => $systemPrompt]
        ]
    ],
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => $userMessage]
            ]
        ]
    ],
    "generationConfig" => [
        "maxOutputTokens" => 350,
        "temperature" => 0.6
    ]
];

$modelsToTry = ['gemini-flash-latest', 'gemini-2.0-flash', 'gemini-2.5-flash-preview-04-17'];

$listCh = curl_init("https://generativelanguage.googleapis.com/v1beta/models?key=" . urlencode($apiKey));
curl_setopt($listCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($listCh, CURLOPT_TIMEOUT, 10);
curl_setopt($listCh, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($listCh, CURLOPT_SSL_VERIFYHOST, false);
$listRes = curl_exec($listCh);
curl_close($listCh);

if ($listRes) {
    $listData = json_decode($listRes, true);
    if (!empty($listData['models'])) {
        $detected = [];
        foreach ($listData['models'] as $m) {
            $mName = str_replace('models/', '', $m['name'] ?? '');
            $methods = $m['supportedGenerationMethods'] ?? [];
            if (in_array('generateContent', $methods) && stripos($mName, 'flash') !== false && stripos($mName, 'image') === false && stripos($mName, 'audio') === false) {
                $detected[] = $mName;
            }
        }
        if (!empty($detected)) {
            $modelsToTry = array_unique(array_merge($detected, $modelsToTry));
        }
    }
}

$lastError = 'No response from API';

foreach ($modelsToTry as $model) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        $aiReply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($aiReply) {
            $aiReply = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{0D80}-\x{0DFF}]/u', '', $aiReply);
            $aiReply = trim(preg_replace('/\s{2,}/', ' ', $aiReply));

            $tokensUsed = $data['usageMetadata']['totalTokenCount'] ?? 400;

            $update = $conn->prepare("
                INSERT INTO ai_usage_limits (user_identifier, usage_date, request_count, tokens_used)
                VALUES (?, ?, 1, ?)
                ON DUPLICATE KEY UPDATE
                    request_count = request_count + 1,
                    tokens_used = tokens_used + ?
            ");
            if ($update) {
                $update->bind_param("ssii", $identifier, $today, $tokensUsed, $tokensUsed);
                $update->execute();
            }

            $remaining = max(0, $dailyMsgLimit - ($currentMsgs + 1));

            echo json_encode([
                'reply' => $aiReply,
                'remaining_messages' => $remaining
            ]);
            exit;
        }
    }

    $errData = json_decode($response, true);
    $lastError = $errData['error']['message'] ?? ($curlErr ?: "HTTP {$httpCode}");
}

echo json_encode([
    'reply' => "Error: {$lastError}"
]);