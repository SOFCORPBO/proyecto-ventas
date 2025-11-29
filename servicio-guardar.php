<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

if (!isset($_POST['nombre'])) {
    header("Location: servicio.php");
    exit;
}

date_default_timezone_set(HORARIO);

// SANITIZAR
$nombre         = addslashes($_POST['nombre']);
$codigo         = addslashes($_POST['codigo']);
$tipo_servicio  = $_POST['tipo_servicio'];
$descripcion    = addslashes($_POST['descripcion']);
$preciocosto    = floatval($_POST['preciocosto']);
$precioventa    = floatval($_POST['precioventa']);
$iva            = floatval($_POST['iva']);
$comision       = floatval($_POST['comision']);
$requiere_boleto = intval($_POST['requiere_boleto']);
$requiere_visa   = intval($_POST['requiere_visa']);
$proveedor      = !empty($_POST['proveedor']) ? intval($_POST['proveedor']) : "NULL";

// ================== SUBIR IMAGEN ==================
$imagen_final = "default.png";

if (!empty($_FILES['imagen']['name'])) {
    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg','jpeg','png'];

    if (in_array($ext, $permitidas)) {
        $imagen_final = "serv_".time().".".$ext;
        move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/servicios/".$imagen_final);
    }
}

// ================== SUBIR ADJUNTO ==================
$adjunto_final = "NULL";

if (!empty($_FILES['archivo']['name'])) {
    $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
    $permitidas = ['pdf','doc','docx'];

    if (in_array($ext, $permitidas)) {
        $archivo = "adj_".time().".".$ext;
        $adjunto_final = "'".$archivo."'";
        move_uploaded_file($_FILES['archivo']['tmp_name'], "uploads/servicios/adjuntos/".$archivo);
    }
}

// ================== INSERTAR EN BD ==================

$SQL = "
INSERT INTO producto (
    codigo, nombre, tipo_servicio, descripcion, requiere_boleto,
    requiere_visa, preciocosto, precioventa, iva, comision,
    proveedor, imagen, especificaciones, stock, habilitado
) VALUES (
    '{$codigo}', '{$nombre}', '{$tipo_servicio}', '{$descripcion}',
    '{$requiere_boleto}', '{$requiere_visa}', '{$preciocosto}',
    '{$precioventa}', '{$iva}', '{$comision}',
    {$proveedor}, '{$imagen_final}', {$adjunto_final},
    NULL, 1
);
";

$db->SQL($SQL);

header("Location: servicio.php?ok=1");
exit;
?>