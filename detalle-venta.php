<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de factura inválido";
    exit;
}

$idfactura = intval($_GET['id']);

$FacturaSQL = $db->SQL("
    SELECT f.*, u.usuario
    FROM factura f
    LEFT JOIN usuario u ON u.id = f.usuario
    WHERE f.id = {$idfactura}
    LIMIT 1
");

if ($FacturaSQL->num_rows == 0) {
    echo "Factura no encontrada";
    exit;
}

$Factura = $FacturaSQL->fetch_assoc();

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
    <meta charset="UTF-8">
    <title>Detalle de Venta #<?php echo $idfactura; ?></title>
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">

    <h3>Detalle Venta #<?php echo $idfactura; ?></h3>

    <p><strong>Vendedor:</strong> <?php echo ucwords($Factura['usuario']); ?></p>
    <p><strong>Total:</strong> <?php echo number_format($Factura['total'],2); ?></p>
    <p><strong>Fecha:</strong> <?php echo $Factura['fecha']." ".$Factura['hora']; ?></p>

    <hr>

    <h4>Servicios incluidos:</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Servicio</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while($d = $DetalleSQL->fetch_assoc()): ?>
            <tr>
                <td><?php echo $d['nombre']; ?></td>
                <td><?php echo $d['cantidad']; ?></td>
                <td><?php echo number_format($d['precio'],2); ?></td>
                <td><?php echo number_format($d['subtotal'],2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>
</body>
</html>
