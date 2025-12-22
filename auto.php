<?php

$TOKEN = "8362517082:AAHh0b9FSfXlJL0ofprStTZXTKcjKZpy30A";
$API = "https://api.telegram.org/bot$TOKEN";
$STORAGE = "storage.json";
$LINK_PRODUTOS = "https://jokervip.rf.gd/";

function bot($method, $data = []) {
    global $API;
    $ch = curl_init("$API/$method");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    return json_decode(curl_exec($ch), true);
}

$data = file_exists($STORAGE)
    ? json_decode(file_get_contents($STORAGE), true)
    : [];

if (($data["auto"]["status"] ?? "off") !== "on") exit;

$chat_id = $data["auto"]["chat_id"] ?? null;
if (!$chat_id) exit;

/* APAGA A MENSAGEM ANTERIOR */
if (!empty($data["auto"]["last_message_id"])) {
    bot("deleteMessage", [
        "chat_id" => $chat_id,
        "message_id" => $data["auto"]["last_message_id"]
    ]);
}

/* ENVIA NOVA */
$msg = bot("sendMessage", [
    "chat_id" => $chat_id,
    "text" => "💬 *Gostando das consultas?*\n\nDê uma olhada no nosso catálogo ou teste nossa IA. 👇",
    "parse_mode" => "Markdown",
    "reply_markup" => json_encode([
        "inline_keyboard" => [
            [
                ["text" => "🛒 • Ver catálogo", "url" => $LINK_PRODUTOS]
            ],
            [
                ["text" => "🤖 • IA sem censura (Free)", "url" => "https://jokervip.rf.gd/ai.html"]
            ]
        ]
    ])
]);

/* SALVA O ID DA NOVA */
$data["auto"]["last_message_id"] = $msg["result"]["message_id"] ?? null;
file_put_contents($STORAGE, json_encode($data, JSON_PRETTY_PRINT));