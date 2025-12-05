<?php
session_start();
include("sistema/configuracion.php");

// Validar sesión
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Validar datos mínimos recibidos
if (!isset($_POST['cantidad'], $_POST['codigo'], $_POST['cliente'])) {
    echo '
    <div class="alert alert-warning alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        Datos incompletos para agregar el servicio.
    </div>';
    exit;
}

$cantidad = filter_var($_POST['cantidad'], FILTER_VALIDATE_INT);
$producto = filter_var($_POST['codigo'], FILTER_VALIDATE_INT);   // id del servicio
$cliente  = filter_var($_POST['cliente'], FILTER_VALIDATE_INT);  // id cliente
$vendedor = $usuarioApp['id'];

if ($cantidad <= 0 || !$producto || !$cliente) {
    echo '
    <div class="alert alert-warning alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        Debe seleccionar servicio, cliente y una cantidad válida.
    </div>';
    exit;
}

// Buscar servicio habilitado
$ServicioSql = $db->SQL("
    SELECT id, nombre, precioventa, comision
    FROM producto
    WHERE id = '{$producto}' AND habilitado = '1'
");

if ($ServicioSql->num_rows <= 0) {
    echo '
    <div class="alert alert-warning alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        El servicio seleccionado no existe o está inactivo.
    </div>';
    exit;
}

$Servicio = $ServicioSql->fetch_assoc();

// Datos de cálculo
$precio   = $Servicio['precioventa'];
$comision = isset($Servicio['comision']) ? (float)$Servicio['comision'] : 0;
$total    = $precio * $cantidad;

$fecha = FechaActual();
$hora  = HoraActual();

// Buscar si ya existe ese servicio en el carrito de este vendedor y cliente
$TmpSql = $db->SQL("
    SELECT id, cantidad, precio
    FROM cajatmp
    WHERE producto = '{$producto}'
      AND vendedor = '{$vendedor}'
      AND cliente  = '{$cliente}'
      AND idfactura IS NULL
    LIMIT 1
");

if ($TmpSql->num_rows == 0) {

    // Insertar nuevo registro en cajatmp
    $InsertSql = $db->SQL("
        INSERT INTO cajatmp (
            idfactura,
            producto,
            cantidad,
            precio,
            totalprecio,
            comision,
            vendedor,
            cliente,
            stockTmp,
            stock,
            fecha,
            hora
        ) VALUES (
            NULL,
            '{$producto}',
            '{$cantidad}',
            '{$precio}',
            '{$total}',
            '{$comision}',
            '{$vendedor}',
            '{$cliente}',
            0,
            0,
            '{$fecha}',
            '{$hora}'
        )
    ");

} else {

    // Ya existía → sumamos cantidades e importes
    $TmpRow = $TmpSql->fetch_assoc();

    $NuevaCantidad = $TmpRow['cantidad'] + $cantidad;
    $NuevoTotal    = $NuevaCantidad * $precio;

    $InsertSql = $db->SQL("
        UPDATE cajatmp SET
            cantidad    = '{$NuevaCantidad}',
            totalprecio = '{$NuevoTotal}',
            fecha       = '{$fecha}',
            hora        = '{$hora}'
        WHERE id = '{$TmpRow['id']}'
    ");
}

// Si todo salió bien, recargar la tabla del carrito
if ($InsertSql) {
    include("consulta.php");
} else {
    echo '
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        Error al agregar el servicio al carrito, intente nuevamente.
    </div>';
}