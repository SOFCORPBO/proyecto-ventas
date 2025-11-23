<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID inválido";
    exit;
}

$idfactura = intval($_GET['id']);

// Obtener factura
$FacturaSQL = $db->SQL("
    SELECT *
    FROM factura
    WHERE id = {$idfactura}
    LIMIT 1
");

if ($FacturaSQL->num_rows == 0) {
    echo "Factura no encontrada";
    exit;
}

$Factura = $FacturaSQL->fetch_assoc();

// Obtener detalle de venta
$DetalleSQL = $db->SQL("
    SELECT dv.*, p.nombre
    FROM detalle_venta dv
    INNER JOIN producto p ON p.id = dv.id_servicio
    WHERE dv.idfactura = {$idfactura}
");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura #<?php echo $idfactura; ?></title>

    <!-- BOOTSTRAP FIJO Y SEGURO -->
    <link rel="stylesheet" href="/Punto-de-venta-php-master/estatico/css/bootstrap.min.css">
    <style>
        body { padding: 20px; font-family: Arial; }
        h3 { margin-bottom: 20px; }
        .table th, .table td { font-size: 14px; }
    </style>
</head>

<body>

<div class="container">

    <h3>Factura #<?php echo $idfactura; ?></h3>

    <p><strong>Fecha:</strong> <?php echo $Factura['fecha']." ".$Factura['hora']; ?></p>
    <p><strong>Total venta:</strong> <?php echo number_format($Factura['total'],2); ?></p>

    <hr>

    <h4>Detalle de Servicios</h4>

    <?php if ($DetalleSQL->num_rows == 0): ?>

        <div class="alert alert-warning">No hay servicios registrados en esta venta.</div>

    <?php else: ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Servicio</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $DetalleSQL->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['nombre']; ?></td>
                    <td><?php echo $row['cantidad']; ?></td>
                    <td><?php echo number_format($row['precio'],2); ?></td>
                    <td><?php echo number_format($row['subtotal'],2); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php endif; ?>

</div>

</body>
</html>
