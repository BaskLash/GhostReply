<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$OPENAI_API_KEY = ""; // dein Key
$MODEL = "gpt-4o-mini";

$input = json_decode(file_get_contents("php://input"), true);
$action = $input["action"] ?? "";
$tweet  = $input["tweet"] ?? "";
$url    = $input["url"] ?? "";

$systemPrompt = "You are a helpful assistant that writes high quality social media replies. Each reply must be at most 280 characters, suitable for an X post.";

$prompts = [
    "prompt" => "Write a helpful response related to the tweet below. Keep it natural, short, and no more than 280 characters.\n\nTweet:\n$tweet",
    "templates" => "Write a friendly response using a conversational tone. Max 280 characters.\n\nTweet:\n$tweet",
    "ai-reply" => "Write a high quality reply to this tweet. The reply should sound human and thoughtful. Max 280 characters.\n\nTweet:\n$tweet",
    "regenerate" => "Rewrite a new version of a reply to this tweet. Make it slightly different and engaging. Max 280 characters.\n\nTweet:\n$tweet"
];

if (!isset($prompts[$action])) {
    echo json_encode(["error" => "Invalid action"]);
    exit;
}

$userPrompt = $prompts[$action];

$payload = [
    "model" => $MODEL,
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $userPrompt]
    ],
    "temperature" => 0.7
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $OPENAI_API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => "Curl error: " . curl_error($ch)]);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

$reply = $data["choices"][0]["message"]["content"] ?? null;

if (!$reply) {
    echo json_encode(["error" => "No AI response"]);
    exit;
}

// Maximal 280 Zeichen erzwingen
$reply = mb_substr(trim($reply), 0, 280);

echo json_encode([
    "success" => true,
    "reply" => $reply
]);

exit;

?>