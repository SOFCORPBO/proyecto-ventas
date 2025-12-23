<?php
/**
 * ChatModulo - Capa de datos + reglas de bandeja.
 * Depende de: class Conexion (Conectar() => mysqli)
 *
 * Nota:
 * - Mantiene compatibilidad con tu estilo (mysqli + query), pero usa prepared statements donde importa.
 */
class ChatModulo extends Conexion
{
    public function db()
    {
        return $this->Conectar();
    }

    /* =========================
       Helpers
       ========================= */
    public static function normalizeDigits($s)
    {
        $s = (string)$s;
        $digits = preg_replace('/\D+/', '', $s);
        return $digits ?: '';
    }

    /**
     * Normaliza a "numero_e164" simple (solo dígitos).
     * Si recibes números locales de Bolivia (8 dígitos), prefija 591.
     */
    public static function normalizeE164($phoneAny)
    {
        $d = self::normalizeDigits($phoneAny);

        // Si viene como remoteJid "5917xxxxxxx@s.whatsapp.net"
        $d = preg_replace('/@.*/', '', $d);

        // Bolivia: 8 dígitos típicos (7xxxxxxx o 6xxxxxxx)
        if (strlen($d) === 8 && (strpos($d, '7') === 0 || strpos($d, '6') === 0)) {
            $d = '591' . $d;
        }
        return $d;
    }

    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    /* =========================
       Instancias
       ========================= */
    public function getInstancias($onlyActive = true)
    {
        $db = $this->db();
        if ($onlyActive) {
            $rs = $db->query("SELECT * FROM chat_instancia WHERE activo=1 ORDER BY id ASC");
        } else {
            $rs = $db->query("SELECT * FROM chat_instancia ORDER BY id ASC");
        }
        $out = [];
        while ($row = $rs->fetch_assoc()) $out[] = $row;
        return $out;
    }

    public function getInstanciasActivas()
    {
        return $this->getInstancias(true);
    }


    public function getInstanciaById($id)
    {
        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM chat_instancia WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getInstanciaByName($instanceName)
    {
        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM chat_instancia WHERE instance_name=? LIMIT 1");
        $stmt->bind_param("s", $instanceName);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function saveInstancia($data)
    {
        $db = $this->db();

        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $nombre = trim((string)($data['nombre'] ?? ''));
        $base_url = trim((string)($data['base_url'] ?? ''));
        $api_key = trim((string)($data['api_key'] ?? ''));
        $instance_name = trim((string)($data['instance_name'] ?? ''));
        $webhook_secret = trim((string)($data['webhook_secret'] ?? ''));
        $activo = isset($data['activo']) ? (int)$data['activo'] : 1;
        $webhook_enabled = isset($data['webhook_enabled']) ? (int)$data['webhook_enabled'] : 1;

        if ($nombre === '' || $base_url === '' || $api_key === '' || $instance_name === '' || $webhook_secret === '') {
            throw new Exception("Datos incompletos de instancia.");
        }

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE chat_instancia
                                  SET nombre=?, base_url=?, api_key=?, instance_name=?, webhook_secret=?, activo=?, webhook_enabled=?
                                  WHERE id=?");
            $stmt->bind_param("sssssiii", $nombre, $base_url, $api_key, $instance_name, $webhook_secret, $activo, $webhook_enabled, $id);
            $stmt->execute();
            return $id;
        }

        $stmt = $db->prepare("INSERT INTO chat_instancia (nombre, base_url, api_key, instance_name, webhook_secret, activo, webhook_enabled)
                              VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssii", $nombre, $base_url, $api_key, $instance_name, $webhook_secret, $activo, $webhook_enabled);
        $stmt->execute();
        return (int)$db->insert_id;
    }

    public function deleteInstancia($id)
    {
        $db = $this->db();
        $stmt = $db->prepare("UPDATE chat_instancia SET activo=0 WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    /* =========================
       Seguridad / Permisos
       ========================= */
    public function isAdmin($userId)
    {
        $db = $this->db();
        $stmt = $db->prepare("SELECT id_perfil FROM usuario WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return $r && (int)$r['id_perfil'] === 1;
    }

    public function userHasPerm($userId, $codigoPermiso)
    {
        $db = $this->db();
        $stmt = $db->prepare("
            SELECT 1
            FROM usuario u
            JOIN perfil_permiso pp ON pp.id_perfil = u.id_perfil
            JOIN permiso p ON p.id = pp.id_permiso
            WHERE u.id=? AND p.codigo=? AND p.habilitado=1
            LIMIT 1
        ");
        $stmt->bind_param("is", $userId, $codigoPermiso);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return (bool)$r;
    }

    public function logAccion($userId, $accion, $evento = null, $modulo = 'CHAT', $meta = [])
    {
        $db = $this->db();

        $url = $_SERVER['REQUEST_URI'] ?? null;
        $metodo = $_SERVER['REQUEST_METHOD'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $metaJson = null;
        if (!empty($meta)) {
            $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
        }

        $stmt = $db->prepare("INSERT INTO log_usuarios (id_usuario, accion, evento, modulo, url, metodo, ip, user_agent, meta_json, fecha)
                              VALUES (?,?,?,?,?,?,?,?,?,?)");
        $fecha = $this->now();
        $stmt->bind_param("isssssssss", $userId, $accion, $evento, $modulo, $url, $metodo, $ip, $ua, $metaJson, $fecha);
        $stmt->execute();
    }

    /* =========================
       Conversaciones y mensajes
       ========================= */
    public function upsertConversacion($idInstancia, $remoteJid, $numeroAny)
    {
        $db = $this->db();
        $remoteJid = (string)$remoteJid;
        $numeroE164 = self::normalizeE164($numeroAny);

        $stmt = $db->prepare("SELECT id FROM chat_conversacion WHERE id_instancia=? AND remote_jid=? LIMIT 1");
        $stmt->bind_param("is", $idInstancia, $remoteJid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) return (int)$row['id'];

        $idCliente = $this->findClienteByTelefono($numeroE164);

        $stmt = $db->prepare("INSERT INTO chat_conversacion (id_instancia, remote_jid, numero_e164, id_cliente, estado)
                              VALUES (?,?,?,?, 'abierta')");
        $stmt->bind_param("issi", $idInstancia, $remoteJid, $numeroE164, $idCliente);
        $stmt->execute();
        return (int)$db->insert_id;
    }

    public function findClienteByTelefono($numeroE164)
    {
        $db = $this->db();
        $d = self::normalizeDigits($numeroE164);
        if ($d === '') return null;

        // Sufijo para matching flexible (últimos 8 dígitos)
        $suffix = substr($d, -8);

        $stmt = $db->prepare("SELECT id FROM cliente
                              WHERE habilitado=1
                                AND REPLACE(REPLACE(REPLACE(telefono,' ',''),'-',''),'+','') LIKE CONCAT('%', ?, '%')
                              LIMIT 1");
        $stmt->bind_param("s", $suffix);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return $r ? (int)$r['id'] : null;
    }

    public function insertMensaje($convId, $direction, $messageId, $tipo, $texto, $status = 'received', $rawJson = null, $userId = null, $tsWhatsapp = null, $mediaUrl = null, $mime = null)
    {
        $db = $this->db();
        $stmt = $db->prepare("INSERT INTO chat_mensaje
            (id_conversacion, direction, message_id, tipo, texto, media_url, mime_type, status, ts_whatsapp, raw_json, id_usuario, creado_en)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");

        $creado = $this->now();
        $direction = (string)$direction;
        $messageId = $messageId ? (string)$messageId : null;
        $tipo = (string)$tipo;
        $texto = $texto !== null ? (string)$texto : null;
        $mediaUrl = $mediaUrl ? (string)$mediaUrl : null;
        $mime = $mime ? (string)$mime : null;
        $status = (string)$status;
        $ts = $tsWhatsapp !== null ? (int)$tsWhatsapp : null;
        $raw = $rawJson ? (string)$rawJson : null;
        $uid = $userId !== null ? (int)$userId : null;

        $stmt->bind_param("isssssssisis",
            $convId, $direction, $messageId, $tipo, $texto, $mediaUrl, $mime, $status, $ts, $raw, $uid, $creado
        );
        $stmt->execute();
        return (int)$db->insert_id;
    }

    public function actualizarUltimoMensaje($convId, $texto)
    {
        $db = $this->db();
        $stmt = $db->prepare("UPDATE chat_conversacion SET ultimo_mensaje=?, ultimo_mensaje_en=? WHERE id=?");
        $fecha = $this->now();
        $stmt->bind_param("ssi", $texto, $fecha, $convId);
        $stmt->execute();
    }

    public function incrementarNoLeidos($convId)
    {
        $db = $this->db();
        $db->query("UPDATE chat_conversacion SET no_leidos = no_leidos + 1 WHERE id=" . (int)$convId);
    }

    public function marcarLeido($convId)
    {
        $db = $this->db();
        $db->query("UPDATE chat_conversacion SET no_leidos = 0 WHERE id=" . (int)$convId);
    }

    public function listConversaciones($idInstancia, $userId, $search = '', $estado = 'todas', $limit = 60)
    {
        $db = $this->db();
        $limit = max(10, min(200, (int)$limit));
        $isAdmin = $this->isAdmin($userId);

        $search = trim((string)$search);
        $estado = trim((string)$estado);

        $where = "c.id_instancia=?";
        $params = [$idInstancia];
        $types  = "i";

        if (!$isAdmin) {
            $where .= " AND (c.asignado_a IS NULL OR c.asignado_a=?)";
            $types .= "i";
            $params[] = $userId;
        }

        if ($estado !== '' && $estado !== 'todas') {
            $where .= " AND c.estado=?";
            $types .= "s";
            $params[] = $estado;
        }

        if ($search !== '') {
            $where .= " AND (c.numero_e164 LIKE CONCAT('%', ?, '%')
                        OR c.remote_jid LIKE CONCAT('%', ?, '%')
                        OR EXISTS (SELECT 1 FROM cliente cl
                                   WHERE cl.id=c.id_cliente
                                     AND (cl.nombre LIKE CONCAT('%', ?, '%')
                                          OR cl.telefono LIKE CONCAT('%', ?, '%'))))";
            $types .= "ssss";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql = "SELECT c.*, cl.nombre as cliente_nombre, cl.telefono as cliente_telefono
                FROM chat_conversacion c
                LEFT JOIN cliente cl ON cl.id=c.id_cliente
                WHERE $where
                ORDER BY COALESCE(c.ultimo_mensaje_en, c.creado_en) DESC
                LIMIT $limit";

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) $out[] = $row;
        return $out;
    }

    public function getMensajes($convId, $limit = 80)
    {
        $db = $this->db();
        $limit = max(10, min(300, (int)$limit));

        $stmt = $db->prepare("SELECT * FROM chat_mensaje WHERE id_conversacion=? ORDER BY id DESC LIMIT $limit");
        $stmt->bind_param("i", $convId);
        $stmt->execute();
        $res = $stmt->get_result();

        $msgs = [];
        while ($row = $res->fetch_assoc()) $msgs[] = $row;
        return array_reverse($msgs);
    }

    public function getConversacion($convId)
    {
        $db = $this->db();
        $stmt = $db->prepare("SELECT c.*, cl.nombre as cliente_nombre, cl.telefono as cliente_telefono, cl.email as cliente_email
                              FROM chat_conversacion c
                              LEFT JOIN cliente cl ON cl.id=c.id_cliente
                              WHERE c.id=? LIMIT 1");
        $stmt->bind_param("i", $convId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function asignarConversacion($convId, $asignadoA)
    {
        $db = $this->db();
        $stmt = $db->prepare("UPDATE chat_conversacion SET asignado_a=? WHERE id=?");
        $stmt->bind_param("ii", $asignadoA, $convId);
        $stmt->execute();
    }

    public function setEstadoConversacion($convId, $estado)
    {
        $estado = in_array($estado, ['abierta','pendiente','cerrada'], true) ? $estado : 'abierta';
        $db = $this->db();
        $stmt = $db->prepare("UPDATE chat_conversacion SET estado=? WHERE id=?");
        $stmt->bind_param("si", $estado, $convId);
        $stmt->execute();
    }

    public function vincularCliente($convId, $clienteId)
    {
        $db = $this->db();
        $stmt = $db->prepare("UPDATE chat_conversacion SET id_cliente=? WHERE id=?");
        $stmt->bind_param("ii", $clienteId, $convId);
        $stmt->execute();
    }

    public function buscarClientes($q, $limit=20)
    {
        $db = $this->db();
        $q = trim((string)$q);
        $limit = max(5, min(50, (int)$limit));

        if ($q === '') return [];

        $stmt = $db->prepare("SELECT id, nombre, telefono, email
                              FROM cliente
                              WHERE habilitado=1 AND (nombre LIKE CONCAT('%', ?, '%') OR telefono LIKE CONCAT('%', ?, '%') OR ci_pasaporte LIKE CONCAT('%', ?, '%'))
                              ORDER BY nombre ASC
                              LIMIT $limit");
        $stmt->bind_param("sss", $q, $q, $q);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) $out[] = $r;
        return $out;
    }

    /* =========================
       Notas
       ========================= */
    public function addNota($convId, $userId, $nota)
    {
        $db = $this->db();
        $nota = trim((string)$nota);
        if ($nota === '') throw new Exception("Nota vacía.");

        $stmt = $db->prepare("INSERT INTO chat_nota (id_conversacion, id_usuario, nota) VALUES (?,?,?)");
        $stmt->bind_param("iis", $convId, $userId, $nota);
        $stmt->execute();
        return (int)$db->insert_id;
    }

    public function listNotas($convId, $limit=50)
    {
        $db = $this->db();
        $limit = max(10, min(200, (int)$limit));
        $stmt = $db->prepare("SELECT n.*, u.usuario as usuario_nombre
                              FROM chat_nota n
                              LEFT JOIN usuario u ON u.id=n.id_usuario
                              WHERE n.id_conversacion=?
                              ORDER BY n.id DESC
                              LIMIT $limit");
        $stmt->bind_param("i", $convId);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) $out[] = $r;
        return $out;
    }

    /* =========================
       Etiquetas
       ========================= */
    public function listEtiquetas()
    {
        $db = $this->db();
        $rs = $db->query("SELECT * FROM chat_etiqueta WHERE activo=1 ORDER BY nombre ASC");
        $out = [];
        while ($r = $rs->fetch_assoc()) $out[] = $r;
        return $out;
    }

    public function upsertEtiqueta($nombre, $color = null)
    {
        $db = $this->db();
        $nombre = trim((string)$nombre);
        if ($nombre === '') throw new Exception("Nombre vacío.");
        $color = $color ? trim((string)$color) : null;

        $stmt = $db->prepare("INSERT INTO chat_etiqueta (nombre, color, activo)
                              VALUES (?,?,1)
                              ON DUPLICATE KEY UPDATE color=VALUES(color), activo=1");
        $stmt->bind_param("ss", $nombre, $color);
        $stmt->execute();
        return (int)$db->insert_id;
    }

    public function setEtiquetasConversacion($convId, $tagIds = [])
    {
        $db = $this->db();
        $db->query("DELETE FROM chat_conversacion_etiqueta WHERE id_conversacion=".(int)$convId);

        if (empty($tagIds)) return;

        $stmt = $db->prepare("INSERT IGNORE INTO chat_conversacion_etiqueta (id_conversacion, id_etiqueta) VALUES (?,?)");
        foreach ($tagIds as $tagId) {
            $tagId = (int)$tagId;
            if ($tagId <= 0) continue;
            $stmt->bind_param("ii", $convId, $tagId);
            $stmt->execute();
        }
    }

    public function getEtiquetasConversacion($convId)
    {
        $db = $this->db();
        $stmt = $db->prepare("SELECT t.*
                              FROM chat_conversacion_etiqueta ct
                              JOIN chat_etiqueta t ON t.id=ct.id_etiqueta
                              WHERE ct.id_conversacion=?
                              ORDER BY t.nombre ASC");
        $stmt->bind_param("i", $convId);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) $out[] = $r;
        return $out;
    }

    /* =========================
       Plantillas
       ========================= */
    public function listPlantillas()
    {
        $db = $this->db();
        $rs = $db->query("SELECT * FROM chat_plantilla WHERE activo=1 ORDER BY nombre ASC");
        $out = [];
        while ($r = $rs->fetch_assoc()) $out[] = $r;
        return $out;
    }

    public function upsertPlantilla($nombre, $texto)
    {
        $db = $this->db();
        $nombre = trim((string)$nombre);
        $texto  = trim((string)$texto);
        if ($nombre === '' || $texto === '') throw new Exception("Datos incompletos de plantilla.");

        $stmt = $db->prepare("INSERT INTO chat_plantilla (nombre, texto, activo)
                              VALUES (?,?,1)
                              ON DUPLICATE KEY UPDATE texto=VALUES(texto), activo=1");
        $stmt->bind_param("ss", $nombre, $texto);
        $stmt->execute();
        return (int)$db->insert_id;
    }

    /* =========================
       Cola de envío
       ========================= */
    public function enqueueSendText($idInstancia, $numeroAny, $texto)
    {
        $db = $this->db();
        $numeroE164 = self::normalizeE164($numeroAny);
        $texto = trim((string)$texto);
        if ($numeroE164 === '' || $texto === '') throw new Exception("Número o texto vacío.");

        $payload = json_encode([
            'number' => $numeroE164,
            'text'   => $texto,
        ], JSON_UNESCAPED_UNICODE);

        $tipo = 'text';
        $stmt = $db->prepare("INSERT INTO chat_outbox (id_instancia, numero_e164, tipo, payload_json, estado, intentos, siguiente_intento)
                              VALUES (?,?,?,?, 'queued', 0, NULL)");
        $stmt->bind_param("isss", $idInstancia, $numeroE164, $tipo, $payload);
        $stmt->execute();
        return (int)$db->insert_id;
    }

    /* =========================
       Cron jobs
       ========================= */
    public function getCronJobs()
    {
        $db = $this->db();
        $rs = $db->query("SELECT * FROM sys_cron_jobs ORDER BY nombre ASC");
        $out = [];
        while ($r = $rs->fetch_assoc()) $out[] = $r;
        return $out;
    }

    public function saveCronJob($id, $intervaloSeg, $habilitado)
    {
        $db = $this->db();
        $id = (int)$id;
        $intervaloSeg = max(60, (int)$intervaloSeg);
        $habilitado = (int)$habilitado ? 1 : 0;

        $stmt = $db->prepare("UPDATE sys_cron_jobs SET intervalo_seg=?, habilitado=? WHERE id=?");
        $stmt->bind_param("iii", $intervaloSeg, $habilitado, $id);
        $stmt->execute();
    }

    /* =========================
       Utilidades para webhook
       ========================= */
    public static function deepGet($arr, $paths, $default = null)
    {
        foreach ($paths as $path) {
            $cur = $arr;
            $ok = true;
            foreach (explode('.', $path) as $k) {
                if (is_array($cur) && array_key_exists($k, $cur)) {
                    $cur = $cur[$k];
                } else {
                    $ok = false;
                    break;
                }
            }
            if ($ok && $cur !== null) return $cur;
        }
        return $default;
    }

    /* =========================
       Integración CRM/POS (settings + acciones)
       ========================= */

    public function getSetting($k, $default = null)
    {
        $db = $this->db();
        $k = (string)$k;
        $stmt = $db->prepare("SELECT v FROM chat_setting WHERE k=? LIMIT 1");
        if (!$stmt) return $default;
        $stmt->bind_param("s", $k);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return $r ? $r['v'] : $default;
    }

    public function setSetting($k, $v)
    {
        $db = $this->db();
        $k = (string)$k;
        $v = (string)$v;

        $stmt = $db->prepare("INSERT INTO chat_setting (k, v) VALUES (?,?)
                              ON DUPLICATE KEY UPDATE v=VALUES(v), actualizado_en=CURRENT_TIMESTAMP");
        if (!$stmt) return;
        $stmt->bind_param("ss", $k, $v);
        $stmt->execute();
    }

    public function getSettingsByPrefix($prefix)
    {
        $db = $this->db();
        $prefix = (string)$prefix;
        $like = $prefix . '%';
        $stmt = $db->prepare("SELECT k, v FROM chat_setting WHERE k LIKE ? ORDER BY k ASC");
        if (!$stmt) return [];
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[$r['k']] = $r['v'];
        }
        return $out;
    }

    public static function toLocalPhone8($numeroE164)
    {
        $d = self::normalizeDigits($numeroE164);
        if ($d === '') return '';
        if (strlen($d) >= 8) return substr($d, -8);
        return $d;
    }

    /**
     * Crea un cliente mínimo a partir de una conversación (y lo vincula).
     */
    public function createClienteRapidoFromConversacion($convId, $data)
    {
        $db = $this->db();
        $convId = (int)$convId;

        $conv = $this->getConversacion($convId);
        if (!$conv) throw new Exception("Conversación no encontrada.");

        if (!empty($conv['id_cliente'])) {
            return (int)$conv['id_cliente'];
        }

        $nombre = trim((string)($data['nombre'] ?? ''));
        if ($nombre === '') throw new Exception("Nombre requerido.");

        $telefonoE164 = (string)($conv['numero_e164'] ?? '');
        $telefonoLocal = self::toLocalPhone8($telefonoE164);
        if ($telefonoLocal === '') throw new Exception("Número inválido.");

        $ci = trim((string)($data['ci_pasaporte'] ?? ''));
        $tipo = strtoupper(trim((string)($data['tipo_documento'] ?? 'CI')));
        if (!in_array($tipo, ['CI','PASAPORTE','OTRO'], true)) $tipo = 'CI';

        $email = trim((string)($data['email'] ?? ''));
        $dir   = trim((string)($data['direccion'] ?? ''));

        $stmt = $db->prepare("INSERT INTO cliente (nombre, ci_pasaporte, tipo_documento, telefono, email, direccion, descuento, habilitado)
                              VALUES (?,?,?,?,?,?, '0', 1)");
        $stmt->bind_param("ssssss", $nombre, $ci, $tipo, $telefonoLocal, $email, $dir);
        $stmt->execute();

        $clienteId = (int)$db->insert_id;

        $this->vincularCliente($convId, $clienteId);

        return $clienteId;
    }
}
