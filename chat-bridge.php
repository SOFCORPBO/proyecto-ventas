<?php
/**
 * Puente de integración: desde la bandeja de chat abre Cliente/Trámite/Venta
 * dejando el contexto en $_SESSION['chat_ctx'].
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/sistema/configuracion.php';
require_once __DIR__ . '/sistema/clase/ChatModulo.clase.php';

// MISMO flujo de autenticación que el resto del sistema
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Validación básica de perfil (ajusta si tu sistema usa otros perfiles)
$perfil = isset($usuarioApp['id_perfil']) ? (int)$usuarioApp['id_perfil'] : (int)($_SESSION['id_perfil'] ?? 0);
if (!in_array($perfil, [1, 2], true)) {
    header("Location: " . URLBASE . "iniciar-sesion.php");
    exit;
}

// Acción y conversación
$go     = (string)($_GET['go'] ?? '');
$convId = (int)($_GET['id_conversacion'] ?? ($_GET['convId'] ?? 0));

if ($convId <= 0 || $go === '') {
    // Si alguien entra directo a chat-bridge.php (sin parámetros), lo mandamos a la bandeja.
    header("Location: " . URLBASE . "chat.php");
    exit;
}

// Instancia del módulo chat
$chat = new ChatModulo();

// Obtener conversación
$conv = $chat->getConversacion($convId);
if (!$conv) {
    // Si no existe, vuelve a bandeja
    header("Location: " . URLBASE . "chat.php");
    exit;
}

// Defaults (pueden sobreescribirse en Configuración -> Integración)
$defaults = [
    'chat.integracion.url_cliente_ficha' => 'cliente.php?id={id_cliente}',
    'chat.integracion.url_cliente_nuevo' => 'nuevo-cliente.php?telefono={telefono}&e164={numero_e164}',
    'chat.integracion.url_tramite_nuevo' => 'tramites.php?idcliente={id_cliente}',
    'chat.integracion.url_venta_nueva'   => 'registro-de-ventas.php?idcliente={id_cliente}',
];

$urls = [];
foreach ($defaults as $k => $def) {
    $urls[$k] = $chat->getSetting($k, $def);
}

// Guardar contexto (para que otras pantallas lo usen)
$_SESSION['chat_ctx'] = [
    'id_conversacion' => (int)$conv['id'],
    'id_instancia'    => (int)$conv['id_instancia'],
    'numero_e164'     => (string)$conv['numero_e164'],
    'telefono_local'  => ChatModulo::toLocalPhone8((string)$conv['numero_e164']),
    'id_cliente'      => !empty($conv['id_cliente']) ? (int)$conv['id_cliente'] : null,
    'cliente_nombre'  => (string)($conv['cliente_nombre'] ?? ''),
    'ts'              => time(),
];

function apply_placeholders($url, $ctx) {
    $rep = [
        '{id_conversacion}' => (string)($ctx['id_conversacion'] ?? ''),
        '{id_cliente}'      => (string)($ctx['id_cliente'] ?? ''),
        '{numero_e164}'     => (string)($ctx['numero_e164'] ?? ''),
        '{telefono}'        => (string)($ctx['telefono_local'] ?? ''),
    ];
    return strtr((string)$url, $rep);
}

// Resolver destino
$ctx = $_SESSION['chat_ctx'];
$target = '';

switch ($go) {
    case 'cliente':
        $target = !empty($ctx['id_cliente'])
            ? $urls['chat.integracion.url_cliente_ficha']
            : $urls['chat.integracion.url_cliente_nuevo'];
        break;

    case 'nuevo_cliente':
        $target = $urls['chat.integracion.url_cliente_nuevo'];
        break;

    case 'tramite':
        $target = $urls['chat.integracion.url_tramite_nuevo'];
        break;

    case 'venta':
        $target = $urls['chat.integracion.url_venta_nueva'];
        break;

    default:
        // Acción inválida => bandeja
        header("Location: " . URLBASE . "chat.php");
        exit;
}

$target = apply_placeholders($target, $ctx);

// Sanitizar Location
$target = str_replace(["\r", "\n"], '', $target);

// Si target es relativo, lo convertimos a URL absoluta del sistema
if (!preg_match('#^https?://#i', $target)) {
    $target = URLBASE . ltrim($target, '/');
}

header("Location: " . $target);
exit;