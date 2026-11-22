<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}



$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$address   = trim($_POST['address'] ?? '');
$postal    = trim($_POST['postal'] ?? '');
$city      = trim($_POST['city'] ?? '');

if (
    $firstName === '' ||
    $lastName === '' ||
    $email === '' ||
    $phone === '' ||
    $address === '' ||
    $postal === '' ||
    $city === ''
) {
    http_response_code(400);
    exit('Please complete all customer information fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Invalid email address.');
}

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

$message = "New product survey\n\n";
$message .= "First name: " . $firstName . "\n";
$message .= "Last name: " . $lastName . "\n";
$message .= "Email: " . $email . "\n";
$message .= "Phone: " . $phone . "\n";
$message .= "Street address: " . $address . "\n";
$message .= "Postal code: " . $postal . "\n";
$message .= "City: " . $city;

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

header('Location: popup.html');
exit;
