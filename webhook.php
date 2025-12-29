<?php
$users = json_decode(file_get_contents("users.json"), true) ?? [];

$TOKEN = "8416517417:AAFsW6Wffa6V8oFGIqRmRP642B-cOgi0L1M";
$ADMIN_ID = 1378641125; // твой TG ID

$data = json_decode(file_get_contents("php://input"), true);

function sendMessage($chat_id, $text) {
    global $TOKEN;
    file_get_contents(
        "https://api.telegram.org/bot$TOKEN/sendMessage?" .
        http_build_query([
            "chat_id" => $chat_id,
            "text" => $text
        ])
    );
}

if (!$data) {
    exit;
}

$message = $data["message"] ?? null;

if (!$message) {
    exit;
}

$chat_id = $message["chat"]["id"];
$text = trim($message["text"] ?? "");

if ($text === "/start") {
    sendMessage($chat_id,
        "Привет!  👋\n\n" .
        "1 — заявка на вход\n" .
        "2 — рест\n" .
        "3 — заявка рекрута"
    );
    exit;
}

// === ЗАЯВКА НА ВХОД ===

if ($text === "1") {
    $users[$chat_id]["step"] = "join_age";
    sendMessage($chat_id, "Сколько тебе лет? ");
    file_put_contents("users.json", json_encode($users));
    exit;
}

if (($users[$chat_id]["step"] ?? "") === "join_age") {
    $users[$chat_id]["age"] = $text;
    $users[$chat_id]["step"] = "join_reason";
    sendMessage($chat_id, "Почему хочешь вступить?");
    file_put_contents("users.json", json_encode($users));
    exit;
}

if (($users[$chat_id]["step"] ?? "") === "join_reason") {
    $age = $users[$chat_id]["age"];

    $msg =
        "📩 Новая заявка на вход\n\n" . 
        "🆔 ID:  $chat_id\n" . 
        "🎂 Возраст: $age\n" .
        "💬 Причина: $text";

    sendMessage($ADMIN_ID, $msg);
    sendMessage($chat_id, "✅ Заявка отправлена администрации");

    unset($users[$chat_id]);
    file_put_contents("users.json", json_encode($users));
    exit;
}

// === REST ===
if ($text === "2") {
    sendMessage($chat_id, "REST команда получена.");
    exit;
}

// === ЗАЯВКА РЕКРУТА ===
if ($text === "3") {
    sendMessage($chat_id, "Заявка рекрута получена.");
    exit;
}

file_put_contents("users. json", json_encode($users));
?>