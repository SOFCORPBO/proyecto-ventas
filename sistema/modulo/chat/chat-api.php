<?php
// API interna para bandeja de chat (AJAX)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracion.php';
require_once __DIR__ . '/../../clase/ChatModulo.clase.php';
require_once __DIR__ . '/../../clase/ChatEvolution.clase.php';

function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function respond(bool $ok, array $payload = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function current_user_id(): int {
    $keys = ['id_usuario','usuario_id','id','iduser','idUser'];
    foreach ($keys as $k) {
        if (isset($_SESSION[$k]) && (int)$_SESSION[$k] > 0) return (int)$_SESSION[$k];
    }
    return 0;
}

/**
 * Request directo a Evolution API (evita depender de métodos internos).
 * Evolution usa header "apikey: <KEY>" en la mayoría de setups.
 */
function evo_request(string $baseUrl, string $apiKey, string $method, string $path, ?array $payload = null): array {
    $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $apiKey,
    ];

    if ($payload !== null) {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'code' => 0, 'error' => $err ?: 'Curl error', 'data' => null];
    }

    $data = json_decode($resp, true);
    if ($data === null) $data = $resp;

    if ($code >= 200 && $code < 300) {
        return ['ok' => true, 'code' => $code, 'error' => null, 'data' => $data];
    }

    $msg = is_string($data) ? $data : ($data['message'] ?? 'HTTP error');
    return ['ok' => false, 'code' => $code, 'error' => $msg, 'data' => $data];
}

$chat   = new ChatModulo();
$action = (string)($_GET['action'] ?? ($_POST['action'] ?? ''));
$input  = json_input();
$userId = current_user_id();

if ($userId <= 0) {
    respond(false, ['error' => 'No autenticado.'], 401);
}

try {
    // Acciones que NO requieren permiso base (si quieres agregar más, añade aquí)
    $publicActions = ['ping'];

    // Permiso base: ver bandeja
    if (!in_array($action, $publicActions, true)) {
        if (!$chat->userHasPerm($userId, 'chat.bandeja.ver')) {
            respond(false, ['error' => 'Sin permiso.'], 403);
        }
    }

    switch ($action) {

        case 'ping':
            respond(true, ['msg' => 'pong']);
            break;

        case 'list_instancias':
            $onlyActive = true;
            if ($chat->userHasPerm($userId, 'chat.config.gestionar')) {
                $onlyActive = false; // configuración necesita ver todo
            }
            $inst = $chat->getInstancias($onlyActive);
            respond(true, ['data' => $inst]);
            break;

        case 'list_conversations':
            $idInst = (int)($_GET['id_instancia'] ?? ($input['id_instancia'] ?? 0));
            $search = (string)($_GET['search'] ?? ($input['search'] ?? ''));
            $estado = (string)($_GET['estado'] ?? ($input['estado'] ?? 'todas'));
            $limit  = (int)($_GET['limit'] ?? ($input['limit'] ?? 60));
            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);

            $data = $chat->listConversaciones($idInst, $userId, $search, $estado, $limit);
            respond(true, ['data' => $data]);
            break;

        case 'get_conversation':
            $convId = (int)($_GET['id'] ?? ($input['id'] ?? 0));
            if ($convId <= 0) respond(false, ['error' => 'id requerido'], 422);

            $c = $chat->getConversacion($convId);
            if (!$c) respond(false, ['error' => 'No existe'], 404);

            $tags = $chat->getEtiquetasConversacion($convId);
            respond(true, ['data' => $c, 'tags' => $tags]);
            break;

        case 'get_messages':
            $convId = (int)($_GET['id_conversacion'] ?? ($input['id_conversacion'] ?? 0));
            $limit  = (int)($_GET['limit'] ?? ($input['limit'] ?? 80));
            if ($convId <= 0) respond(false, ['error' => 'id_conversacion requerido'], 422);

            $msgs = $chat->getMensajes($convId, $limit);
            respond(true, ['data' => $msgs]);
            break;

        case 'mark_read':
            $convId = (int)($input['id_conversacion'] ?? 0);
            if ($convId <= 0) respond(false, ['error' => 'id_conversacion requerido'], 422);

            $chat->marcarLeido($convId);
            $chat->logAccion($userId, 'chat.mark_read', 'mark_read', 'CHAT', ['convId' => $convId]);
            respond(true);
            break;

        case 'send_text':
            if (!$chat->userHasPerm($userId, 'chat.mensajes.enviar')) {
                respond(false, ['error' => 'Sin permiso para enviar.'], 403);
            }

            $idInst = (int)($input['id_instancia'] ?? 0);
            $convId = (int)($input['id_conversacion'] ?? 0);
            $numero = (string)($input['numero'] ?? '');
            $texto  = (string)($input['texto'] ?? '');

            if ($idInst <= 0 && $convId > 0) {
                $conv = $chat->getConversacion($convId);
                $idInst = $conv ? (int)$conv['id_instancia'] : 0;
                if ($numero === '' && $conv) $numero = (string)$conv['numero_e164'];
            }

            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);
            if ($numero === '' && $convId <= 0) respond(false, ['error' => 'numero o id_conversacion requerido'], 422);
            if (trim($texto) === '') respond(false, ['error' => 'texto requerido'], 422);

            $numeroE164 = ChatModulo::normalizeE164($numero);

            $outId = $chat->enqueueSendText($idInst, $numeroE164, $texto);

            if ($convId > 0) {
                $chat->insertMensaje($convId, 'out', null, 'text', $texto, 'queued', null, $userId, null, null, null);
                $chat->actualizarUltimoMensaje($convId, $texto);
            }

            $chat->logAccion($userId, 'chat.send_text', 'send_text', 'CHAT', [
                'outboxId' => $outId,
                'convId'   => $convId,
                'numero'   => $numeroE164
            ]);

            respond(true, ['data' => ['outbox_id' => $outId, 'numero' => $numeroE164]]);
            break;

        case 'assign_conversation':
            if (!$chat->userHasPerm($userId, 'chat.conversaciones.asignar')) {
                respond(false, ['error' => 'Sin permiso para asignar.'], 403);
            }
            $convId = (int)($input['id_conversacion'] ?? 0);
            $asignadoA = (int)($input['asignado_a'] ?? 0);
            if ($convId <= 0 || $asignadoA <= 0) respond(false, ['error' => 'Datos requeridos'], 422);

            $chat->asignarConversacion($convId, $asignadoA);
            $chat->logAccion($userId, 'chat.assign', 'assign', 'CHAT', ['convId' => $convId, 'asignado_a' => $asignadoA]);
            respond(true);
            break;

        case 'set_status':
            if (!$chat->userHasPerm($userId, 'chat.conversaciones.cerrar')) {
                respond(false, ['error' => 'Sin permiso para cambiar estado.'], 403);
            }
            $convId = (int)($input['id_conversacion'] ?? 0);
            $estado = (string)($input['estado'] ?? 'abierta');
            if ($convId <= 0) respond(false, ['error' => 'id_conversacion requerido'], 422);

            $chat->setEstadoConversacion($convId, $estado);
            $chat->logAccion($userId, 'chat.set_status', 'set_status', 'CHAT', ['convId' => $convId, 'estado' => $estado]);
            respond(true);
            break;

        case 'search_clientes':
            $q = (string)($_GET['q'] ?? ($input['q'] ?? ''));
            $data = $chat->buscarClientes($q, 20);
            respond(true, ['data' => $data]);
            break;

        case 'link_cliente':
            $convId = (int)($input['id_conversacion'] ?? 0);
            $clienteId = (int)($input['id_cliente'] ?? 0);
            if ($convId <= 0 || $clienteId <= 0) respond(false, ['error' => 'Datos requeridos'], 422);

            $chat->vincularCliente($convId, $clienteId);
            $chat->logAccion($userId, 'chat.link_cliente', 'link_cliente', 'CHAT', ['convId' => $convId, 'clienteId' => $clienteId]);
            respond(true);
            break;

        case 'create_cliente_quick':
            if (!$chat->userHasPerm($userId, 'chat.crm.crear_cliente')) {
                respond(false, ['error' => 'Sin permiso para crear cliente.'], 403);
            }
            $convId = (int)($input['id_conversacion'] ?? 0);
            if ($convId <= 0) respond(false, ['error' => 'id_conversacion requerido'], 422);

            $clienteData = $input['cliente'] ?? $input;
            $clienteId = $chat->createClienteRapidoFromConversacion($convId, $clienteData);

            $chat->logAccion($userId, 'chat.create_cliente_quick', 'create_cliente_quick', 'CHAT', ['convId' => $convId, 'clienteId' => $clienteId]);
            respond(true, ['data' => ['id_cliente' => $clienteId]]);
            break;

        case 'get_settings':
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }
            $prefix = (string)($_GET['prefix'] ?? ($input['prefix'] ?? 'chat.integracion.'));
            $data = $chat->getSettingsByPrefix($prefix);
            respond(true, ['data' => $data]);
            break;

        case 'save_settings':
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }
            $settings = $input['settings'] ?? [];
            if (!is_array($settings)) respond(false, ['error' => 'settings inválido'], 422);

            foreach ($settings as $k => $v) {
                $k = (string)$k;
                if (strpos($k, 'chat.integracion.') !== 0) continue;
                $chat->setSetting($k, (string)$v);
            }
            $chat->logAccion($userId, 'chat.save_settings', 'save_settings', 'CHAT', ['count' => count($settings)]);
            respond(true);
            break;

        case 'list_tags':
            $tags = $chat->listEtiquetas();
            respond(true, ['data' => $tags]);
            break;

        case 'save_tag':
            if (!$chat->isAdmin($userId)) {
                respond(false, ['error' => 'Solo administrador.'], 403);
            }
            $nombre = (string)($input['nombre'] ?? '');
            $color  = (string)($input['color'] ?? '');
            $id = $chat->upsertEtiqueta($nombre, $color);
            $chat->logAccion($userId, 'chat.save_tag', 'save_tag', 'CHAT', ['tagId' => $id, 'nombre' => $nombre]);
            respond(true, ['data' => ['id' => $id]]);
            break;

        case 'set_conv_tags':
            $convId = (int)($input['id_conversacion'] ?? 0);
            $tagIds = $input['tag_ids'] ?? [];
            if ($convId <= 0 || !is_array($tagIds)) respond(false, ['error' => 'Datos requeridos'], 422);

            $chat->setEtiquetasConversacion($convId, $tagIds);
            $chat->logAccion($userId, 'chat.set_tags', 'set_conv_tags', 'CHAT', ['convId' => $convId, 'tagIds' => $tagIds]);
            respond(true);
            break;

        case 'list_notes':
            $convId = (int)($_GET['id_conversacion'] ?? ($input['id_conversacion'] ?? 0));
            if ($convId <= 0) respond(false, ['error' => 'id_conversacion requerido'], 422);

            $notes = $chat->listNotas($convId, 60);
            respond(true, ['data' => $notes]);
            break;

        case 'add_note':
            $convId = (int)($input['id_conversacion'] ?? 0);
            $nota   = (string)($input['nota'] ?? '');
            if ($convId <= 0) respond(false, ['error' => 'id_conversacion requerido'], 422);
            if (trim($nota) === '') respond(false, ['error' => 'nota requerida'], 422);

            $nid = $chat->addNota($convId, $userId, $nota);
            $chat->logAccion($userId, 'chat.add_note', 'add_note', 'CHAT', ['convId' => $convId, 'notaId' => $nid]);
            respond(true, ['data' => ['id' => $nid]]);
            break;

        case 'list_templates':
            $tpl = $chat->listPlantillas();
            respond(true, ['data' => $tpl]);
            break;

        case 'save_template':
            if (!$chat->isAdmin($userId)) {
                respond(false, ['error' => 'Solo administrador.'], 403);
            }
            $nombre = (string)($input['nombre'] ?? '');
            $texto  = (string)($input['texto'] ?? '');
            $id = $chat->upsertPlantilla($nombre, $texto);
            $chat->logAccion($userId, 'chat.save_template', 'save_template', 'CHAT', ['tplId' => $id, 'nombre' => $nombre]);
            respond(true, ['data' => ['id' => $id]]);
            break;

        case 'cron_list':
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }
            $jobs = $chat->getCronJobs();
            respond(true, ['data' => $jobs]);
            break;

        case 'cron_save':
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }
            $id = (int)($input['id'] ?? 0);
            $intervalo = (int)($input['intervalo_seg'] ?? 60);
            $hab = (int)($input['habilitado'] ?? 1);
            if ($id <= 0) respond(false, ['error' => 'id requerido'], 422);

            $chat->saveCronJob($id, $intervalo, $hab);
            $chat->logAccion($userId, 'chat.cron_save', 'cron_save', 'CHAT', [
                'jobId' => $id, 'intervalo' => $intervalo, 'habilitado' => $hab
            ]);
            respond(true);
            break;

        case 'instance_save':
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }
            $id = $chat->saveInstancia($input);
            $chat->logAccion($userId, 'chat.instance_save', 'instance_save', 'CHAT', [
                'instanciaId' => $id,
                'nombre' => $input['nombre'] ?? null
            ]);
            respond(true, ['data' => ['id' => $id]]);
            break;

        case 'instance_delete':
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) respond(false, ['error' => 'id requerido'], 422);

            $chat->deleteInstancia($id);
            $chat->logAccion($userId, 'chat.instance_delete', 'instance_delete', 'CHAT', ['instanciaId' => $id]);
            respond(true);
            break;

        case 'instance_test':
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }
            $idInst = (int)($input['id_instancia'] ?? 0);
            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);

            $inst = $chat->getInstanciaById($idInst);
            if (!$inst) respond(false, ['error' => 'No existe'], 404);

            $evo  = new ChatEvolution($inst['base_url'], $inst['api_key']);
            $resp = $evo->connectionState($inst['instance_name']);
            respond(true, ['data' => $resp]);
            break;

        // ============================
        // EVOLUTION: ESTADO / QR / LOGOUT
        // ============================

        case 'evo_connection_state':
            $idInst = (int)($_GET['id_instancia'] ?? ($input['id_instancia'] ?? 0));
            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);

            $inst = $chat->getInstanciaById($idInst);
            if (!$inst) respond(false, ['error' => 'Instancia no encontrada'], 404);

            $r = evo_request($inst['base_url'], $inst['api_key'], 'GET', '/instance/connectionState/' . $inst['instance_name']);
            if (!$r['ok']) respond(false, ['error' => $r['error'], 'data' => $r['data']], 502);

            respond(true, ['data' => $r['data']]);
            break;

        case 'evo_create_instance_qr':
            // Recomiendo solo config/admin
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }

            $idInst = (int)($input['id_instancia'] ?? 0);
            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);

            $inst = $chat->getInstanciaById($idInst);
            if (!$inst) respond(false, ['error' => 'Instancia no encontrada'], 404);

            // En muchos setups, /instance/create devuelve QR cuando qrcode=true
            $payload = [
                'instanceName' => $inst['instance_name'],
                'qrcode'       => true
            ];

            $r = evo_request($inst['base_url'], $inst['api_key'], 'POST', '/instance/create', $payload);
            if (!$r['ok']) respond(false, ['error' => $r['error'], 'data' => $r['data']], 502);

            respond(true, ['data' => $r['data']]);
            break;

        case 'evo_logout_instance':
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }

            $idInst = (int)($input['id_instancia'] ?? 0);
            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);

            $inst = $chat->getInstanciaById($idInst);
            if (!$inst) respond(false, ['error' => 'Instancia no encontrada'], 404);

            $r = evo_request($inst['base_url'], $inst['api_key'], 'DELETE', '/instance/logout/' . $inst['instance_name']);
            if (!$r['ok']) respond(false, ['error' => $r['error'], 'data' => $r['data']], 502);

            respond(true, ['data' => $r['data']]);
            break;

        case 'instance_register_webhook':
            if (!$chat->userHasPerm($userId, 'chat.config.gestionar')) {
                respond(false, ['error' => 'Sin permiso de configuración.'], 403);
            }
            $idInst = (int)($input['id_instancia'] ?? 0);
            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);

            $inst = $chat->getInstanciaById($idInst);
            if (!$inst) respond(false, ['error' => 'No existe'], 404);

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

            $webhookUrl = $scheme . '://' . $host
                . '/sistema/modulo/chat/chat.webhook.php?secret=' . urlencode($inst['webhook_secret'])
                . '&instance=' . urlencode($inst['instance_name']);

            $events = [
                'messages.upsert',
                'messages.update',
                'connection.update',
            ];

            $evo  = new ChatEvolution($inst['base_url'], $inst['api_key']);
            $resp = $evo->setWebhookInstance($webhookUrl, $events, true, false);

            $chat->logAccion($userId, 'chat.webhook_register', 'instance_register_webhook', 'CHAT', [
                'instanciaId' => $idInst,
                'url'         => $webhookUrl,
                'resp_code'   => $resp['code'] ?? null
            ]);

            respond(true, ['data' => ['webhook_url' => $webhookUrl, 'resp' => $resp]]);
            break;

                    case 'evo_state':
            // GET: id_instancia
            $idInst = (int)($_GET['id_instancia'] ?? 0);
            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);

            $inst = $chat->getInstanciaById($idInst);
            if (!$inst) respond(false, ['error' => 'Instancia no encontrada'], 404);

            $evo = new ChatEvolution($inst['base_url'], $inst['api_key']);
            $resp = $evo->connectionState($inst['instance_name']); // ya lo usas en instance_test
            respond(true, ['data' => $resp]);
            break;

        case 'evo_qr':
            // POST: id_instancia
            // Objetivo: pedir QR para conectar WhatsApp Web (Evolution)
            $idInst = (int)($input['id_instancia'] ?? 0);
            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);

            $inst = $chat->getInstanciaById($idInst);
            if (!$inst) respond(false, ['error' => 'Instancia no encontrada'], 404);

            $evo = new ChatEvolution($inst['base_url'], $inst['api_key']);

            /**
             * IMPORTANTE:
             * Aquí depende de cómo implementaste ChatEvolution.clase.php.
             * Te dejo 2 opciones:
             * 1) Si tienes método getQr() o fetchQr() úsalo.
             * 2) Si NO tienes, crea un método en ChatEvolution para llamar endpoint de QR.
             *
             * Por ahora, intentaré llamar a un método común "getQrCode" si existe.
             */
            if (method_exists($evo, 'getQrCode')) {
                $qr = $evo->getQrCode($inst['instance_name']); // debe retornar base64 o data-url
                respond(true, ['data' => ['qr' => $qr]]);
            }

            // Si no existe, devuelve error claro
            respond(false, ['error' => 'Falta implementar getQrCode() en ChatEvolution.clase.php'], 500);
            break;

        case 'evo_logout':
            // POST: id_instancia
            $idInst = (int)($input['id_instancia'] ?? 0);
            if ($idInst <= 0) respond(false, ['error' => 'id_instancia requerido'], 422);

            $inst = $chat->getInstanciaById($idInst);
            if (!$inst) respond(false, ['error' => 'Instancia no encontrada'], 404);

            $evo = new ChatEvolution($inst['base_url'], $inst['api_key']);

            if (method_exists($evo, 'logout')) {
                $out = $evo->logout($inst['instance_name']);
                respond(true, ['data' => $out]);
            }

            respond(false, ['error' => 'Falta implementar logout() en ChatEvolution.clase.php'], 500);
            break;

        default:
            respond(false, ['error' => 'Acción no soportada: ' . $action], 400);
    }

} catch (Throwable $e) {
    respond(false, ['error' => $e->getMessage()], 500);
}