<?php
session_start();
include("sistema/configuracion.php");

// Validar sesión
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Asegurarnos de tener los datos mínimos
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

// Buscar servicio (producto) y validar que esté habilitado
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
$comision = isset($Servicio['comision']) ? (float)$Servicio['comision'] : 0; // ⚠ solo para cálculos futuros
$total    = $precio * $cantidad;

// *** IMPORTANTE ***
// Ya NO controlamos stock real (son SERVICIOS).
// Pero como la tabla cajatmp tiene stockTmp y stock, los llenamos con 0
// para no romper nada en otras partes del sistema.

$fecha = FechaActual();
$hora  = HoraActual();

// ¿Ya existe este servicio en el carrito de este vendedor y cliente?
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

    // ➜ Insertar nuevo renglón en cajatmp (SIN columna comision)
  $InsertSql = $db->SQL("
    INSERT INTO cajatmp (
        idfactura,
        producto,
        cantidad,
        precio,
        totalprecio,
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
        '{$vendedor}',
        '{$cliente}',
        0,
        0,
        '{$fecha}',
        '{$hora}'
    )
");


} else {

    // ➜ Ya estaba en el carrito: actualizamos cantidad e importe
    $TmpRow        = $TmpSql->fetch_assoc();
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

// Si todo salió bien, volvemos a pintar la tabla del carrito
if ($InsertSql) {
    include("consulta.php");
} else {
    echo '
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        Error al agregar el servicio al carrito, intente nuevamente.
    </div>';
}