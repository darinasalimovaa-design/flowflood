<?php
$usersFile = __DIR__ . '/users.json';
$TOKEN = getenv('TELEGRAM_TOKEN') ?: '';
$ADMIN_ID = (int)(getenv('ADMIN_ID') ?: 1378641125);

function loadUsers(string $file): array {
    if (!file_exists($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

function saveUsers(string $file, array $users): void {
    file_put_contents($file, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function tgRequest(string $method, array $params): array {
    global $TOKEN;
    if ($TOKEN === '') {
        return ['ok' => false, 'error' => 'Missing TELEGRAM_TOKEN'];
    }

    $ch = curl_init("https://api.telegram.org/bot{$TOKEN}/{$method}");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 7,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => $error ?: 'Request failed'];
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : ['ok' => false, 'error' => 'Invalid JSON response'];
}

function sendMessage(int|string $chat_id, string $text): void {
    tgRequest('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $text,
    ]);
}

$data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($data)) {
    http_response_code(200);
    exit('ok');
}

$message = $data['message'] ?? null;
if (!is_array($message)) {
    http_response_code(200);
    exit('ok');
}

$chat_id = $message['chat']['id'] ?? null;
$text = trim((string)($message['text'] ?? ''));
$from = $message['from'] ?? [];
$username = $from['username'] ?? 'Нет username';
$firstName = $from['first_name'] ?? '';

if ($chat_id === null) {
    http_response_code(200);
    exit('ok');
}

$users = loadUsers($usersFile);
$chatKey = (string)$chat_id;
$step = $users[$chatKey]['step'] ?? null;

if ($text === '/start') {
    unset($users[$chatKey]);
    saveUsers($usersFile, $users);
    sendMessage($chat_id, "Привет! 👋\n\n1 — заявка на вход\n2 — рест\n3 — заявка рекрута");
    http_response_code(200);
    exit('ok');
}

if ($text === '1') {
    $users[$chatKey] = [
        'step' => 'join_age',
        'type' => 'join',
        'username' => $username,
        'first_name' => $firstName,
    ];
    saveUsers($usersFile, $users);
    sendMessage($chat_id, 'Сколько тебе лет?');
    http_response_code(200);
    exit('ok');
}

if ($step === 'join_age') {
    $users[$chatKey]['age'] = $text;
    $users[$chatKey]['step'] = 'join_reason';
    saveUsers($usersFile, $users);
    sendMessage($chat_id, 'Почему хочешь вступить?');
    http_response_code(200);
    exit('ok');
}

if ($step === 'join_reason') {
    $age = $users[$chatKey]['age'] ?? '';
    $msg = "📩 Новая заявка на вход\n\n" .
        "🆔 ID: {$chat_id}\n" .
        "👤 Имя: {$firstName}\n" .
        "🔗 Username: @{$username}\n" .
        "🎂 Возраст: {$age}\n" .
        "💬 Причина: {$text}";

    sendMessage($ADMIN_ID, $msg);
    sendMessage($chat_id, '✅ Заявка отправлена администрации');

    unset($users[$chatKey]);
    saveUsers($usersFile, $users);
    http_response_code(200);
    exit('ok');
}

if ($text === '2') {
    sendMessage($chat_id, "🛌 Рест принят. Отдыхай.");
    http_response_code(200);
    exit('ok');
}

if ($text === '3') {
    sendMessage($chat_id, "📝 Заявка рекрута получена.");
    http_response_code(200);
    exit('ok');
}

sendMessage($chat_id, 'Используй кнопки/команды меню.');
http_response_code(200);
exit('ok');
