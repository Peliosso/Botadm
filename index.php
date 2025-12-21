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

function askSky($prompt) {
    $apiKey = getenv("WRMGPT_API_KEY");
    if (!$apiKey) return "⚠️ API Key não configurada.";

    $ch = curl_init("https://api.wrmgpt.com/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiKey",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "wormgpt-v7",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ]
        ])
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data["choices"][0]["message"]["content"] ?? "❌ Erro ao obter resposta.";
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

/* ================= AUTO ON / OFF ================= */

if ($from_id == $ADMIN_ID && preg_match('/^\/auto (on|off)$/', $text, $m)) {

    $data = loadData();
    $data["auto"]["status"] = $m[1];
    $data["auto"]["chat_id"] = $chat_id;
    saveData($data);

    $sent = bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🤖 Auto mensagem *" . strtoupper($m[1]) . "*",
        "parse_mode" => "Markdown",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [
                    [
                        "text" => "🗑 Apagar",
                        "callback_data" => "delete_auto|" . $message["message_id"]
                    ]
                ]
            ]
        ])
    ]);
}

/* ================= ANTI-LINK ON / OFF ================= */

if ($from_id == $ADMIN_ID && preg_match('/^\/antilink (on|off)$/', $text, $m)) {

    $data = loadData();
    $data["antilink"] = $m[1];
    saveData($data);

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🔗 Anti-link *" . strtoupper($m[1]) . "*",
        "parse_mode" => "Markdown"
    ]);
}

/* ================= ANTI-LINK ================= */

if (!empty($text)) {

    $data = loadData();

    if (($data["antilink"] ?? "off") === "on") {

        // ignora admins
        if ($from_id != $ADMIN_ID) {

            if (preg_match('/(http|https|t\.me|www\.)/i', $text)) {

                // apaga a mensagem
                bot("deleteMessage", [
                    "chat_id" => $chat_id,
                    "message_id" => $message["message_id"]
                ]);

                // aviso (opcional)
                bot("sendMessage", [
                    "chat_id" => $chat_id,
                    "text" => "🚫 Links não são permitidos aqui.",
                ]);

                exit;
            }
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

/* ================= SKY AI ================= */

if (preg_match('/^\/sky\s+(.+)/s', $text, $m)) {

    $pergunta = trim($m[1]);

    bot("sendChatAction", [
        "chat_id" => $chat_id,
        "action" => "typing"
    ]);

    $resposta = askSky($pergunta);

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🧠 *Sky AI responde:*\n\n$resposta",
        "parse_mode" => "Markdown"
    ]);

    exit;
}

/* ================= STATS ================= */

if ($text === "/stats") {

    $data = loadData();
    $total_warns = array_sum($data["warns"] ?? []);
    $users_warned = count($data["warns"] ?? []);

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" =>
            "📊 *Estatísticas do Grupo*\n\n" .
            "👥 Usuários advertidos: $users_warned\n" .
            "⚠️ Warns totais: $total_warns\n\n" .
            "👋 Welcome: *" . strtoupper($data["welcome"] ?? "on") . "*\n" .
            "🤖 Auto: *" . strtoupper($data["auto"]["status"] ?? "off") . "*",
        "parse_mode" => "Markdown",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [
                    [
                        "text" => "🗑 Apagar",
                        "callback_data" => "delete_stats|" . $message["message_id"]
                    ]
                ]
            ]
        ])
    ]);
}

/* ================= INFO ================= */

if ($text === "/info" && isset($message["reply_to_message"])) {

    $u = $message["reply_to_message"]["from"];
    $data = loadData();

    $id = $u["id"];
    $nome = $u["first_name"] ?? "-";
    $user = $u["username"] ?? "-";
    $warns = $data["warns"][$id] ?? 0;

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" =>
            "👤 *Informações do Usuário*\n\n" .
            "🆔 ID: `$id`\n" .
            "📛 Nome: $nome\n" .
            "🔗 Username: @$user\n" .
            "⚠️ Warns: $warns/$MAX_WARNS",
        "parse_mode" => "Markdown"
    ]);
}

/* ================= ANTI-FLOOD ================= */

if (isset($message["from"]["id"]) && !$message["from"]["is_bot"]) {

    $uid = $message["from"]["id"];
    $now = time();
    $data = loadData();

    $data["flood"][$uid] = array_filter(
        $data["flood"][$uid] ?? [],
        fn($t) => $t > $now - 7
    );

    $data["flood"][$uid][] = $now;

    if (count($data["flood"][$uid]) >= 5) {

        bot("restrictChatMember", [
            "chat_id" => $chat_id,
            "user_id" => $uid,
            "permissions" => json_encode([
                "can_send_messages" => false
            ]),
            "until_date" => $now + 300
        ]);

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "🔇 Usuário mutado por flood (5 minutos)."
        ]);

        $data["flood"][$uid] = [];
    }

    saveData($data);
}

/* ================= MUTE ================= */

if ($from_id == $ADMIN_ID && isset($message["reply_to_message"])) {

    $uid = $message["reply_to_message"]["from"]["id"];
    $nome = $message["reply_to_message"]["from"]["first_name"] ?? "usuário";

    if (preg_match('/^\/mute (\d+)(m|h)$/', $text, $m)) {

        $tempo = $m[1] * ($m[2] == "h" ? 3600 : 60);

        bot("restrictChatMember", [
            "chat_id" => $chat_id,
            "user_id" => $uid,
            "permissions" => json_encode([
                "can_send_messages" => false
            ]),
            "until_date" => time() + $tempo
        ]);

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "🔇 *$nome mutado por {$m[1]}{$m[2]}.*",
            "parse_mode" => "Markdown"
        ]);
    }

    if ($text === "/unmute") {

        bot("restrictChatMember", [
            "chat_id" => $chat_id,
            "user_id" => $uid,
            "permissions" => json_encode([
                "can_send_messages" => true,
                "can_send_media_messages" => true,
                "can_send_other_messages" => true,
                "can_add_web_page_previews" => true
            ])
        ]);

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "🔊 *$nome foi desmutado.*",
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

/* ================= CALLBACK (ÚNICO) ================= */

if (isset($update["callback_query"])) {

    $cb = $update["callback_query"];
    $data = $cb["data"];
    $chat_id = $cb["message"]["chat"]["id"];
    $bot_message_id = $cb["message"]["message_id"];

/* DELETE STATS */

if (strpos($data, "delete_stats|") === 0) {

    $cmd_message_id = explode("|", $data)[1];

    bot("deleteMessage", [
        "chat_id" => $chat_id,
        "message_id" => $bot_message_id
    ]);

    bot("deleteMessage", [
        "chat_id" => $chat_id,
        "message_id" => $cmd_message_id
    ]);

    bot("answerCallbackQuery", [
        "callback_query_id" => $cb["id"]
    ]);

    exit;
}

    /* DELETE AUTO MESSAGE */
    if (strpos($data, "delete_auto|") === 0) {

        $cmd_message_id = explode("|", $data)[1];

        // apaga a mensagem do bot
        bot("deleteMessage", [
            "chat_id" => $chat_id,
            "message_id" => $bot_message_id
        ]);

        // apaga o comando enviado
        bot("deleteMessage", [
            "chat_id" => $chat_id,
            "message_id" => $cmd_message_id
        ]);

        bot("answerCallbackQuery", [
            "callback_query_id" => $cb["id"]
        ]);

        exit;
    }

    /* CALLBACKS GENÉRICOS (menu, info, etc) */
    bot("answerCallbackQuery", [
        "callback_query_id" => $cb["id"],
        "text" => "Use os comandos respondendo a uma mensagem.",
        "show_alert" => true
    ]);

    exit;
}