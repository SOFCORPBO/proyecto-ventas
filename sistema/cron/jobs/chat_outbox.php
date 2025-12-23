<?php
/**
 * Job: chat_outbox
 * - Envía mensajes pendientes (chat_outbox.estado='queued')
 * - Actualiza estado / reintentos
 */
$limit = 20;

$rs = $db->query("SELECT * FROM chat_outbox
                  WHERE estado='queued'
                    AND (siguiente_intento IS NULL OR siguiente_intento <= NOW())
                  ORDER BY id ASC
                  LIMIT $limit");

$processed = 0;

while ($row = $rs->fetch_assoc()) {
    $processed++;

    $outId = (int)$row['id'];
    $idInst = (int)$row['id_instancia'];
    $numero = (string)$row['numero_e164'];
    $payload = json_decode((string)$row['payload_json'], true);
    if (!is_array($payload)) $payload = [];

    $inst = $chat->getInstanciaById($idInst);
    if (!$inst) {
        $stmt = $db->prepare("UPDATE chat_outbox SET estado='error', ultimo_error=? WHERE id=?");
        $err = 'Instancia no existe';
        $stmt->bind_param("si", $err, $outId);
        $stmt->execute();
        continue;
    }

    try {
        $evo = new ChatEvolution($inst['base_url'], $inst['api_key']);
        $text = (string)($payload['text'] ?? '');

        $resp = $evo->sendText($inst['instance_name'], $numero, $text);

        if ($resp['code'] >= 200 && $resp['code'] < 300) {
            // marcar como sent
            $stmt = $db->prepare("UPDATE chat_outbox SET estado='sent', ultimo_error=NULL WHERE id=?");
            $stmt->bind_param("i", $outId);
            $stmt->execute();

            // Upsert conversación + registrar mensaje out (evita perder historial aunque webhook tarde)
            $remoteJid = $numero . '@s.whatsapp.net';
            $convId = $chat->upsertConversacion($idInst, $remoteJid, $numero);

            $msgId = null;
            // Intentar capturar id
            if (is_array($resp['data'])) {
                $msgId = ChatModulo::deepGet($resp['data'], ['key.id', 'data.key.id', 'messageId', 'id'], null);
            }

            $chat->insertMensaje($convId, 'out', $msgId, 'text', $text, 'sent', json_encode($resp['data'], JSON_UNESCAPED_UNICODE), null, null, null, null);
            $chat->actualizarUltimoMensaje($convId, $text);
        } else {
            throw new Exception("HTTP " . $resp['code'] . " - " . $resp['raw']);
        }
    } catch (Exception $e) {
        $attempts = (int)$row['intentos'] + 1;

        // Backoff exponencial (máx 60 min)
        $delaySec = min(3600, (int)pow(2, min(10, $attempts)) * 30);
        $next = date('Y-m-d H:i:s', time() + $delaySec);

        $stmt = $db->prepare("UPDATE chat_outbox
                              SET intentos=?, siguiente_intento=?, ultimo_error=?
                              WHERE id=?");
        $err = substr($e->getMessage(), 0, 2000);
        $stmt->bind_param("issi", $attempts, $next, $err, $outId);
        $stmt->execute();
    }
}

return ['ok' => true, 'processed' => $processed];
