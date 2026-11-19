<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => 'Invalid request method.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if ($userMessage === '') {
    echo json_encode(['reply' => 'Please type a message so I can help you!']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$identifier = $user_id ? 'user_' . $user_id : 'ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$today = date('Y-m-d');
$daily_limit = 15;

$conn->query("
    CREATE TABLE IF NOT EXISTS ai_usage_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(100) NOT NULL,
        usage_date DATE NOT NULL,
        request_count INT DEFAULT 0,
        UNIQUE KEY unique_user_day (identifier, usage_date)
    )
");

$stmt = $conn->prepare("SELECT request_count FROM ai_usage_limits WHERE identifier = ? AND usage_date = ?");
$stmt->bind_param("ss", $identifier, $today);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$current_count = $res ? intval($res['request_count']) : 0;

if ($current_count >= $daily_limit) {
    echo json_encode([
        'reply' => "You have reached your daily limit of {$daily_limit} AI assistant messages. Please try again tomorrow!"
    ]);
    exit();
}

$schedulesContext = "Upcoming Matches & Training Schedules:\n";
$schQuery = $conn->query("SELECT title, sport_type, event_type, event_date, event_time, venue FROM matches_schedules WHERE status = 'UPCOMING' ORDER BY event_date ASC LIMIT 8");
if ($schQuery && $schQuery->num_rows > 0) {
    while ($row = $schQuery->fetch_assoc()) {
        $schedulesContext .= "- {$row['title']} ({$row['sport_type']} - {$row['event_type']}) on {$row['event_date']} at {$row['event_time']} | Venue: {$row['venue']}\n";
    }
} else {
    $schedulesContext .= "- No upcoming fixtures posted at the moment.\n";
}

$clubsContext = "Active Sports Clubs on NSBM SportsHub:\n";
$clubQuery = $conn->query("SELECT DISTINCT club_name, COUNT(user_id) as members FROM user_clubs WHERE status = 'approved' GROUP BY club_name");
if ($clubQuery && $clubQuery->num_rows > 0) {
    while ($c = $clubQuery->fetch_assoc()) {
        $clubsContext .= "- {$c['club_name']} ({$c['members']} active members)\n";
    }
} else {
    $clubsContext .= "- Athletics, Basketball, Cricket, Badminton, Football, Swimming, Volleyball, Rugby, Table Tennis, Chess, Netball, Hockey.\n";
}

$systemPrompt = "You are the official NSBM SportsHub AI Assistant for NSBM Green University students. 
Be helpful, concise, friendly, and accurate. Answer questions about joining sports clubs, checking match schedules, and booking campus sports facilities (grounds, indoor courts, equipment).
Use the live campus database context below when answering:
{$schedulesContext}
{$clubsContext}";

$apiKey = 'YOUR_GEMINI_API_KEY';
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$payload = [
    "system_instruction" => [
        "parts" => [["text" => $systemPrompt]]
    ],
    "contents" => [
        [
            "role" => "user",
            "parts" => [["text" => $userMessage]]
        ]
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? "Sorry, I couldn't process that right now.";

    // Increment daily usage counter
    $upStmt = $conn->prepare("
        INSERT INTO ai_usage_limits (identifier, usage_date, request_count) 
        VALUES (?, ?, 1) 
        ON DUPLICATE KEY UPDATE request_count = request_count + 1
    ");
    $upStmt->bind_param("ss", $identifier, $today);
    $upStmt->execute();

    echo json_encode(['reply' => $reply]);
} else {
    echo json_encode(['reply' => 'The AI assistant is temporarily unavailable. Please try again later.']);
}
//continue pending!