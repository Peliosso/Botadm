<?php

$TOKEN = "8362517082:AAHh0b9FSfXlJL0ofprStTZXTKcjKZpy30A";
$API = "https://api.telegram.org/bot$TOKEN";
$STORAGE = "storage.json";
$LINK_PRODUTOS = "https://jokervip.rf.gd/";

function bot($method, $data = []) {
    global $API;
    $ch = curl_init("$API/$method");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    return curl_exec($ch);
}

$data = file_exists($STORAGE)
    ? json_decode(file_get_contents($STORAGE), true)
    : [];

if (($data["auto"]["status"] ?? "off") !== "on") {
    exit;
}

$chat_id = $data["auto"]["chat_id"] ?? null;
if (!$chat_id) exit;

bot("sendMessage", [
    "chat_id" => $chat_id,
    "text" => "💬 *Gostando das consultas?*\n\nDê uma olhada no nosso catálogo. 👇",
    "parse_mode" => "Markdown",
    "reply_markup" => json_encode([
        "inline_keyboard" => [
            [
                ["text" => "🛒 Ver catálogo", "url" => $LINK_PRODUTOS]
            ]
        ]
    ])
]);
