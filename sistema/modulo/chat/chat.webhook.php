<?php
/**
 * Webhook para Evolution API -> registra mensajes y updates en tu BD.
 *
 * IMPORTANTES:
 * 1) Registra este endpoint en Evolution: /webhook/instance
 * 2) Protegido por secret + nombre de instancia:
 *      /sistema/modulo/chat/chat.webhook.php?secret=XXX&instance=MyInstance
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracion.php';
require_once __DIR__ . '/../../clase/ChatModulo.clase.php';

$chat = new ChatModulo();

$secret = $_GET['secret'] ?? '';
$instanceName = $_GET['instance'] ?? '';

if ($secret === '' || $instanceName === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing secret/instance']);
    exit;
}

$inst = $chat->getInstanciaByName($instanceName);
if (!$inst) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Unknown instance']);
    exit;
}

if (!hash_equals((string)$inst['webhook_secret'], (string)$secret)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) $payload = [];

$event = ChatModulo::deepGet($payload, ['event', 'type', 'action', 'data.event'], 'unknown');
$event = strtolower((string)$event);

try {
    $idInst = (int)$inst['id'];

    // helper para extraer campos frecuentes
    $remoteJid = ChatModulo::deepGet($payload, [
        'data.key.remoteJid',
        'data.remoteJid',
        'key.remoteJid',
        'remoteJid',
        'data.message.key.remoteJid'
    ]);

    $fromMe = ChatModulo::deepGet($payload, [
        'data.key.fromMe',
        'data.fromMe',
        'key.fromMe',
        'fromMe',
        'data.message.key.fromMe'
    ], false);

    $messageId = ChatModulo::deepGet($payload, [
        'data.key.id',
        'data.id',
        'key.id',
        'id',
        'data.message.key.id'
    ]);

    $timestamp = ChatModulo::deepGet($payload, [
        'data.messageTimestamp',
        'data.message.timestamp',
        'data.messageTimestampMs',
        'messageTimestamp',
        'timestamp'
    ]);

    $texto = ChatModulo::deepGet($payload, [
        'data.message.conversation',
        'data.message.extendedTextMessage.text',
        'data.message.imageMessage.caption',
        'data.message.videoMessage.caption',
        'data.message.documentMessage.caption',
        'message.conversation',
        'message.extendedTextMessage.text',
    ], null);

    $tipo = 'text';
    if ($texto === null) {
        // Detectar otros tipos mínimos
        if (ChatModulo::deepGet($payload, ['data.message.imageMessage'], null)) $tipo = 'image';
        else if (ChatModulo::deepGet($payload, ['data.message.videoMessage'], null)) $tipo = 'video';
        else if (ChatModulo::deepGet($payload, ['data.message.documentMessage'], null)) $tipo = 'document';
        else $tipo = 'unknown';
    }

    // Para updates de estado (ej: messages.update)
    if (strpos($event, 'messages.update') !== false || strpos($event, 'message.update') !== false) {
        $newStatus = ChatModulo::deepGet($payload, ['data.status', 'status', 'data.update.status'], null);
        if ($messageId && $newStatus) {
            $db = $chat->db();
            $stmt = $db->prepare("UPDATE chat_mensaje SET status=? WHERE message_id=?");
            $newStatus = (string)$newStatus;
            $messageId = (string)$messageId;
            $stmt->bind_param("ss", $newStatus, $messageId);
            $stmt->execute();
        }
        echo json_encode(['ok' => true, 'event' => $event]);
        exit;
    }

    // Para estado conexión (ej: connection.update)
    if (strpos($event, 'connection.update') !== false || strpos($event, 'connection') !== false) {
        $state = ChatModulo::deepGet($payload, ['data.state', 'state', 'data.connection', 'data.status'], 'unknown');
        $db = $chat->db();
        $stmt = $db->prepare("UPDATE chat_instancia SET estado=?, ultimo_check=? WHERE id=?");
        $now = date('Y-m-d H:i:s');
        $state = (string)$state;
        $stmt->bind_param("ssi", $state, $now, $idInst);
        $stmt->execute();

        echo json_encode(['ok' => true, 'event' => $event]);
        exit;
    }

    // Evento de mensaje (upsert / messages.upsert)
    if (!$remoteJid) {
        // Si no viene remoteJid, igual respondemos OK para evitar reintentos eternos
        echo json_encode(['ok' => true, 'event' => $event, 'warn' => 'no remoteJid']);
        exit;
    }

    $numeroE164 = ChatModulo::normalizeE164($remoteJid);

    $convId = $chat->upsertConversacion($idInst, $remoteJid, $numeroE164);

    $direction = $fromMe ? 'out' : 'in';
    $status = $fromMe ? 'sent' : 'received';

    // Guardar raw (limitado si quieres)
    $rawJson = $raw;

    // Insert mensaje
    $chat->insertMensaje($convId, $direction, $messageId, $tipo, $texto, $status, $rawJson, null, $timestamp, null, null);

    // Actualizar conversación
    if (!$fromMe) {
        $chat->incrementarNoLeidos($convId);
    }
    $chat->actualizarUltimoMensaje($convId, $texto ? (string)$texto : ('['.$tipo.']'));

    echo json_encode(['ok' => true, 'event' => $event, 'convId' => $convId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
