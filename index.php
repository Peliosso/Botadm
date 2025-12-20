<?php

/* ================= CONFIG ================= */

$TOKEN = "8362517082:AAHh0b9FSfXlJL0ofprStTZXTKcjKZpy30A";
$API = "https://api.telegram.org/bot$TOKEN";

$ADMIN_ID = 7926471341;
$DONO = "@silenciante";
$LINK_PRODUTOS = "https://jokervip.rf.gd/";

$STORAGE = "storage.json";
$MAX_WARNS = 3;

/* ================= FUNÇÕES ================= */

function bot($method, $data = []) {
    global $API;
    $ch = curl_init("$API/$method");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    return json_decode(curl_exec($ch), true);
}

function loadData() {
    return file_exists("storage.json")
        ? json_decode(file_get_contents("storage.json"), true)
        : [];
}

function saveData($data) {
    file_put_contents("storage.json", json_encode($data, JSON_PRETTY_PRINT));
}

/* ================= UPDATE ================= */

$update = json_decode(file_get_contents("php://input"), true);
if (!$update) exit;

$message = $update["message"] ?? null;
$text = $message["text"] ?? "";
$chat_id = $message["chat"]["id"] ?? null;
$from_id = $message["from"]["id"] ?? null;

/* ================= START ================= */

if ($text === "/start") {

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "👋 Bem-vindo!\n\nVeja nosso catálogo:",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [["text" => "🛒 Produtos", "url" => $LINK_PRODUTOS]]
            ]
        ])
    ]);
}

/* ================= AUTO ON / OFF ================= */

if ($from_id == $ADMIN_ID && preg_match('/^\/auto (on|off)$/', $text, $m)) {

    $data = loadData();
    $data["auto"]["status"] = $m[1];
    $data["auto"]["chat_id"] = $chat_id;
    saveData($data);

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🤖 Auto mensagem *" . strtoupper($m[1]) . "*",
        "parse_mode" => "Markdown"
    ]);
}

/* ================= BOAS-VINDAS ================= */

if (isset($message["new_chat_members"])) {

    $data = loadData();
    if (($data["welcome"] ?? "on") === "on") {

        foreach ($message["new_chat_members"] as $membro) {

            $nome = $membro["first_name"] ?? "membro";

            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" =>
                    "Olá *$nome* 🫡\n\n" .
                    "Seja bem-vindo ao grupo.\n\n" .
                    "Qualquer dúvida: $DONO",
                "parse_mode" => "Markdown"
            ]);
        }
    }
}

/* ================= BAN / UNBAN ================= */

if ($from_id == $ADMIN_ID && isset($message["reply_to_message"])) {

    $reply_id = $message["reply_to_message"]["from"]["id"];
    $nome = $message["reply_to_message"]["from"]["first_name"] ?? "usuário";

    if ($text === "/ban") {

        bot("banChatMember", [
            "chat_id" => $chat_id,
            "user_id" => $reply_id
        ]);

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "🚫 *$nome foi banido.*",
            "parse_mode" => "Markdown"
        ]);
    }

    if ($text === "/unban") {

        bot("unbanChatMember", [
            "chat_id" => $chat_id,
            "user_id" => $reply_id
        ]);

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "♻️ *$nome foi desbanido.*",
            "parse_mode" => "Markdown"
        ]);
    }
}

/* ================= WARNS ================= */

if ($from_id == $ADMIN_ID && isset($message["reply_to_message"])) {

    $reply_id = $message["reply_to_message"]["from"]["id"];
    $nome = $message["reply_to_message"]["from"]["first_name"] ?? "usuário";

    $data = loadData();
    $data["warns"][$reply_id] = $data["warns"][$reply_id] ?? 0;

    if ($text === "/warn") {

        $data["warns"][$reply_id]++;
        saveData($data);

        if ($data["warns"][$reply_id] >= $MAX_WARNS) {

            bot("banChatMember", [
                "chat_id" => $chat_id,
                "user_id" => $reply_id
            ]);

            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "🚫 *$nome banido por warns.*",
                "parse_mode" => "Markdown"
            ]);

        } else {

            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" =>
                    "⚠️ *$nome recebeu um warn*\n" .
                    "({$data["warns"][$reply_id]}/$MAX_WARNS)",
                "parse_mode" => "Markdown"
            ]);
        }
    }

    if ($text === "/warns") {

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "📊 *$nome tem {$data["warns"][$reply_id]}/$MAX_WARNS warns.*",
            "parse_mode" => "Markdown"
        ]);
    }
}

/* ================= MENU ================= */

if ($text === "/menu") {

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "📌 *Menu Administrativo*",
        "parse_mode" => "Markdown",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [["text" => "🚫 Ban", "callback_data" => "ban"]],
                [["text" => "⚠️ Warn", "callback_data" => "warn"]]
            ]
        ])
    ]);
}

/* ================= CALLBACK ================= */

if (isset($update["callback_query"])) {

    bot("answerCallbackQuery", [
        "callback_query_id" => $update["callback_query"]["id"],
        "text" => "Use os comandos respondendo a uma mensagem.",
        "show_alert" => true
    ]);
}