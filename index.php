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
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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

/* =======================================================
   🔥 BOAS-VINDAS — PRIORIDADE MÁXIMA (NÃO MOVER)
   ======================================================= */

if (isset($update["message"]["new_chat_members"])) {

    $chat_id = $update["message"]["chat"]["id"];
    $data = loadData();

    if (($data["welcome"] ?? "on") === "on") {

        foreach ($update["message"]["new_chat_members"] as $membro) {

            // ignora o próprio bot
            if (!empty($membro["is_bot"])) continue;

            $nome = $membro["first_name"] ?? "nome";

            bot("sendPhoto", [
                "chat_id" => $chat_id,
                "photo" => new CURLFile(__DIR__ . "/IMG_6743.jpeg"),
                "caption" =>
                    "Oláa, *$nome*. 🫡\n\n" .
                    "Esperamos garantir a melhor experiência para os nossos membros. 🤗\n\n" .
                    "No nosso grupo você poderá consultar nomes, CPFs, telefones, etc de graça!\n\n" .
                    "Além de aprender vários macetes. 😉\n" .
                    "Qualquer dúvida me chame: $DONO\n\n" .
                    "🎰 • 𝓙𝓸𝓴𝓮𝓻 (𝓥𝓲𝓹)",
                "parse_mode" => "Markdown",
                "reply_markup" => json_encode([
                    "inline_keyboard" => [
                        [
                            ["text" => "🛒 Ver catálogo", "url" => $LINK_PRODUTOS]
                        ]
                    ]
                ])
            ]);
        }
    }

    // encerra aqui para não conflitar com outros comandos
    exit;
}

/* ================= VARIÁVEIS PADRÃO ================= */

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

/* ================= WELCOME ON / OFF ================= */

if ($from_id == $ADMIN_ID && preg_match('/^\/welcome (on|off)$/', $text, $m)) {

    $data = loadData();
    $data["welcome"] = $m[1];
    saveData($data);

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "👋 Welcome *" . strtoupper($m[1]) . "*",
        "parse_mode" => "Markdown"
    ]);
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