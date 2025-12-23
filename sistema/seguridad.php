<?php
// sistema/seguridad.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/conexion.php'; // ajusta al archivo real de conexión

function require_login(): void {
  if (empty($_SESSION['uid'])) {
    header('Location: iniciar-sesion.php');
    exit;
  }
}

function can(string $permiso): bool {
  $perms = $_SESSION['permisos'] ?? [];
  return in_array($permiso, $perms, true);
}

function require_permission(string $permiso): void {
  require_login();
  if (!can($permiso)) {
    http_response_code(403);
    echo "Acceso denegado";
    exit;
  }
}

function cargar_permisos_por_perfil($db, int $idPerfil): array {
  // mysqli example (ajusta si usas PDO)
  $sql = "SELECT p.codigo
          FROM perfil_permiso pp
          INNER JOIN permiso p ON p.id = pp.id_permiso
          WHERE pp.id_perfil = ? AND p.habilitado=1";
  $stmt = $db->prepare($sql);
  $stmt->bind_param("i", $idPerfil);
  $stmt->execute();
  $res = $stmt->get_result();

  $out = [];
  while ($row = $res->fetch_assoc()) {
    $out[] = $row['codigo'];
  }
  return $out;
}

function auditoria($db, string $accion, string $evento, string $modulo, array $meta = []): void {
  $uid = $_SESSION['uid'] ?? null;
  if (!$uid) return;

  $url = $_SERVER['REQUEST_URI'] ?? null;
  $metodo = $_SERVER['REQUEST_METHOD'] ?? null;
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
  $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);

  $sql = "INSERT INTO log_usuarios (id_usuario, accion, evento, modulo, url, metodo, ip, user_agent, meta_json, fecha)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
  $stmt = $db->prepare($sql);
  $stmt->bind_param("issssssss", $uid, $accion, $evento, $modulo, $url, $metodo, $ip, $ua, $metaJson);
  $stmt->execute();
}