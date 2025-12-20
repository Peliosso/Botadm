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

function bot($method, $data = [], $multipart = false) {
    global $API;
    $ch = curl_init($API . "/" . $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $data : http_build_query($data));
    return json_decode(curl_exec($ch), true);
}

function loadData($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

function saveData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

/* ================= UPDATE ================= */

$update = json_decode(file_get_contents("php://input"), true);
if (!$update) exit;

/* ================= /START ================= */

if (isset($update["message"]["text"]) && $update["message"]["text"] === "/start") {
    bot("sendMessage", [
        "chat_id" => $update["message"]["chat"]["id"],
        "text" => "👋 Bem-vindo!\n\nVeja nosso catálogo:",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [["text" => "🛒 Produtos", "url" => $LINK_PRODUTOS]]
            ]
        ])
    ]);
}

/* ================= WELCOME ON/OFF ================= */

if (isset($update["message"]["text"])) {

    $text = $update["message"]["text"];
    $chat_id = $update["message"]["chat"]["id"];
    $from_id = $update["message"]["from"]["id"];

    if ($from_id == $ADMIN_ID && preg_match('/^\/welcome (on|off)$/', $text, $m)) {

        $data = loadData($STORAGE);
        $data["welcome"] = $m[1];
        saveData($STORAGE, $data);

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "👋 Welcome *" . strtoupper($m[1]) . "*",
            "parse_mode" => "Markdown"
        ]);
    }
}

/* ================= BOAS-VINDAS ================= */

if (isset($update["message"]["new_chat_members"])) {

    $data = loadData($STORAGE);
    if (($data["welcome"] ?? "on") === "on") {

        $chat_id = $update["message"]["chat"]["id"];

        foreach ($update["message"]["new_chat_members"] as $membro) {

            $nome = $membro["first_name"] ?? "novo membro";

            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" =>
                    "👋 *Bem-vindo(a), $nome!*\n\n".
                    "Consultas grátis no grupo.\n\n".
                    "Dúvidas: $DONO",
                "parse_mode" => "Markdown"
            ]);
        }
    }
}

/* ================= BAN / UNBAN ================= */

if (isset($update["message"]["text"], $update["message"]["reply_to_message"])) {

    $text = $update["message"]["text"];
    $chat_id = $update["message"]["chat"]["id"];
    $from_id = $update["message"]["from"]["id"];
    $reply_id = $update["message"]["reply_to_message"]["from"]["id"];
    $nome = $update["message"]["reply_to_message"]["from"]["first_name"] ?? "usuário";

    if ($from_id == $ADMIN_ID) {

        if ($text === "/ban") {

            bot("banChatMember", [
                "chat_id" => $chat_id,
                "user_id" => $reply_id
            ]);

            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "🚫 *$nome foi banido com sucesso.*",
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
}

/* ================= WARNS ================= */

if (isset($update["message"]["text"], $update["message"]["reply_to_message"])) {

    $text = $update["message"]["text"];
    $chat_id = $update["message"]["chat"]["id"];
    $from_id = $update["message"]["from"]["id"];
    $reply_id = $update["message"]["reply_to_message"]["from"]["id"];
    $nome = $update["message"]["reply_to_message"]["from"]["first_name"] ?? "usuário";

    if ($from_id == $ADMIN_ID) {

        $data = loadData($STORAGE);
        $data["warns"][$reply_id] = $data["warns"][$reply_id] ?? 0;

        if ($text === "/warn") {

            $data["warns"][$reply_id]++;
            saveData($STORAGE, $data);

            if ($data["warns"][$reply_id] >= $MAX_WARNS) {

                bot("banChatMember", [
                    "chat_id" => $chat_id,
                    "user_id" => $reply_id
                ]);

                bot("sendMessage", [
                    "chat_id" => $chat_id,
                    "text" => "🚫 *$nome foi banido (limite de warns).*",
                    "parse_mode" => "Markdown"
                ]);

            } else {

                bot("sendMessage", [
                    "chat_id" => $chat_id,
                    "text" =>
                        "⚠️ *$nome recebeu um warn*\n".
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
}

/* ================= MENU ================= */

if (isset($update["message"]["text"]) && $update["message"]["text"] === "/menu") {

    bot("sendMessage", [
        "chat_id" => $update["message"]["chat"]["id"],
        "text" => "📌 *Menu Administrativo*",
        "parse_mode" => "Markdown",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [["text" => "🚫 Ban", "callback_data" => "info_ban"]],
                [["text" => "⚠️ Warn", "callback_data" => "info_warn"]],
                [["text" => "👋 Welcome", "callback_data" => "info_welcome"]]
            ]
        ])
    ]);
}

/* ================= CALLBACKS ================= */

if (isset($update["callback_query"])) {

    $id = $update["callback_query"]["id"];
    $data = $update["callback_query"]["data"];

    $msgs = [
        "info_ban" => "Use /ban respondendo a mensagem.",
        "info_warn" => "Use /warn respondendo a mensagem.",
        "info_welcome" => "Use /welcome on ou /welcome off."
    ];

    bot("answerCallbackQuery", [
        "callback_query_id" => $id,
        "text" => $msgs[$data] ?? "Opção inválida",
        "show_alert" => true
    ]);
}