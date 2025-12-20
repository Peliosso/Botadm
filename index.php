<?php

// ===================== CONFIGURAÇÕES =====================
$TOKEN = "8362517082:AAHh0b9FSfXlJL0ofprStTZXTKcjKZpy30A";
$API = "https://api.telegram.org/bot$TOKEN";

$DONO = "@silenciante";
$LINK_PRODUTOS = "https://seusite.com";

// ID do grupo (ex: -1001234567890)
$CHAT_GRUPO = "-1003052688657";

// ID do usuário que pode abrir o painel
$ADMIN_ID = 7926471341;

// Arquivo de controle
$STORAGE = "storage.json";

// Intervalo da mensagem automática (5 minutos)
$INTERVALO = 300;

// ===================== FUNÇÃO API =====================
function bot($method, $data = [], $multipart = false) {
    global $API;

    $ch = curl_init($API . "/" . $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($multipart) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    return json_decode(curl_exec($ch), true);
}

// ===================== UPDATE =====================
$update = json_decode(file_get_contents("php://input"), true);
$AGORA = time();

// ===================== BOAS-VINDAS =====================
if (isset($update["message"]["new_chat_members"])) {

    $chat_id = $update["message"]["chat"]["id"];
    $nome = $update["message"]["new_chat_members"][0]["first_name"];

    $texto = "Oláa, *$nome*. 🫡  

Esperamos garantir a **melhor experiência** para os nossos membros. 🤗  

No nosso grupo você poderá consultar **nomes, CPFs, telefones**, etc **de graça**!  

Além de aprender vários **macetes** 😉  
Qualquer dúvida, me chame: **$DONO**  

🎰 • _𝓙𝓸𝓴𝓮𝓻 (𝓥𝓲𝓹)_";

    bot("sendPhoto", [
        "chat_id" => $chat_id,
        "photo" => new CURLFile(__DIR__ . "/IMG_6743.JPEG"),
        "caption" => $texto,
        "parse_mode" => "Markdown",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [
                    ["text" => "👑 Dono", "url" => "https://t.me/" . str_replace("@","",$DONO)],
                    ["text" => "🛒 Produtos", "url" => $LINK_PRODUTOS]
                ],
                [
                    ["text" => "⚙️ Gerenciar", "callback_data" => "painel"]
                ]
            ]
        ])
    ], true);
}

// ===================== COMANDOS BAN / UNBAN =====================
if (isset($update["message"]["text"])) {

    $chat_id = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"];
    $reply = $update["message"]["reply_to_message"]["from"]["id"] ?? null;

    if ($text === "/ban" && $reply) {
        bot("banChatMember", [
            "chat_id" => $chat_id,
            "user_id" => $reply
        ]);
    }

    if ($text === "/unban" && $reply) {
        bot("unbanChatMember", [
            "chat_id" => $chat_id,
            "user_id" => $reply
        ]);
    }

    // ===================== ANTI-LINK =====================
    if (preg_match('/https?:\/\/|t\.me\//i', $text)) {
        bot("deleteMessage", [
            "chat_id" => $chat_id,
            "message_id" => $update["message"]["message_id"]
        ]);
    }
}

// ===================== CALLBACKS =====================
if (isset($update["callback_query"])) {

    $data = $update["callback_query"]["data"];
    $chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $callback_id = $update["callback_query"]["id"];
    $from_id = $update["callback_query"]["from"]["id"];

    // ===================== PAINEL SOMENTE PARA ADMIN =====================
    if ($data === "painel") {
        if ($from_id != $ADMIN_ID) {
            bot("answerCallbackQuery", [
                "callback_query_id" => $callback_id,
                "text" => "⚠️ Apenas o administrador pode abrir este painel",
                "show_alert" => true
            ]);
            exit;
        }

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⚙️ *Painel Administrativo*\n\nEscolha uma opção:",
            "parse_mode" => "Markdown",
            "reply_markup" => json_encode([
                "inline_keyboard" => [
                    [["text" => "🚫 Banir usuário", "callback_data" => "info_ban"]],
                    [["text" => "♻️ Desbanir usuário", "callback_data" => "info_unban"]]
                ]
            ])
        ]);
    }

    if ($data === "info_ban") {
        bot("answerCallbackQuery", [
            "callback_query_id" => $callback_id,
            "text" => "Use /ban respondendo a mensagem do usuário",
            "show_alert" => true
        ]);
    }

    if ($data === "info_unban") {
        bot("answerCallbackQuery", [
            "callback_query_id" => $callback_id,
            "text" => "Use /unban respondendo a mensagem do usuário",
            "show_alert" => true
        ]);
    }
}

// ===================== MENSAGEM AUTOMÁTICA (SEM CRON) =====================
if ($update) {

    $data = file_exists($STORAGE)
        ? json_decode(file_get_contents($STORAGE), true)
        : [];

    $ultimo = $data["last_time"] ?? 0;

    if (($AGORA - $ultimo) >= $INTERVALO) {

        if (isset($data["last_msg"])) {
            bot("deleteMessage", [
                "chat_id" => $CHAT_GRUPO,
                "message_id" => $data["last_msg"]
            ]);
        }

        $msg = bot("sendMessage", [
            "chat_id" => $CHAT_GRUPO,
            "text" => "✨ *Gostando das consultas?*\n\nConfira abaixo o nosso catálogo de produtos.",
            "parse_mode" => "Markdown",
            "reply_markup" => json_encode([
                "inline_keyboard" => [
                    [
                        ["text" => "🛒 Ver Catálogo", "url" => $LINK_PRODUTOS]
                    ]
                ]
            ])
        ]);

        file_put_contents($STORAGE, json_encode([
            "last_time" => $AGORA,
            "last_msg" => $msg["result"]["message_id"]
        ]));
    }
}