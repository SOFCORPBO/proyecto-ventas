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

// Datos del cliente
$clienteNombre = $Factura['cliente'];
$clienteDocumento = '';
$clienteEmail = '';

$CliSQL = $db->SQL("SELECT * FROM cliente WHERE id = '{$Factura['cliente']}'");
if($CliSQL->num_rows > 0){
    $Cli = $CliSQL->fetch_assoc();
    $clienteNombre    = $Cli['nombre'];
    $clienteDocumento = $Cli['ci_pasaporte'];
    $clienteEmail     = $Cli['email'];
}

// Detalle de venta desde detalle_venta
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

    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    body {
        padding: 20px;
        font-family: Arial;
    }

    h3 {
        margin-bottom: 20px;
    }

    .table th,
    .table td {
        font-size: 14px;
    }

    .encabezado {
        margin-bottom: 20px;
    }
    </style>
</head>

<body>

    <div class="container">

        <div class="encabezado">
            <h3>Factura / Comprobante #<?php echo $idfactura; ?></h3>
            <p><strong>Fecha:</strong> <?php echo $Factura['fecha']." ".$Factura['hora']; ?></p>
            <p><strong>Tipo Comprobante:</strong> <?php echo $Factura['tipo_comprobante']; ?></p>
            <p><strong>Total venta:</strong> <?php echo number_format($Factura['total'],2); ?></p>

            <hr>

            <h4>Datos del Cliente</h4>
            <p><strong>Nombre:</strong> <?php echo $clienteNombre; ?></p>
            <?php if(!empty($clienteDocumento)): ?>
            <p><strong>CI / Pasaporte:</strong> <?php echo $clienteDocumento; ?></p>
            <?php endif; ?>
            <?php if(!empty($Factura['nit_cliente'])): ?>
            <p><strong>NIT/CI Facturación:</strong> <?php echo $Factura['nit_cliente']; ?></p>
            <?php endif; ?>
            <?php if(!empty($Factura['razon_social'])): ?>
            <p><strong>Razón Social:</strong> <?php echo $Factura['razon_social']; ?></p>
            <?php endif; ?>
            <?php if(!empty($clienteEmail)): ?>
            <p><strong>Email:</strong> <?php echo $clienteEmail; ?></p>
            <?php endif; ?>

            <p><strong>Método de pago:</strong> <?php echo $Factura['metodo_pago']; ?></p>
            <?php if(!empty($Factura['referencia'])): ?>
            <p><strong>Referencia:</strong> <?php echo $Factura['referencia']; ?></p>
            <?php endif; ?>
        </div>

        <hr>

        <h4>Detalle de Servicios</h4>

        <?php if ($DetalleSQL->num_rows == 0): ?>

        <div class="alert alert-warning">No hay servicios registrados en esta venta.</div>

        <?php else: ?>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
            $total = 0;
            while($row = $DetalleSQL->fetch_assoc()): 
                $total += $row['subtotal'];
            ?>
                <tr>
                    <td><?php echo $row['nombre']; ?></td>
                    <td><?php echo $row['cantidad']; ?></td>
                    <td><?php echo number_format($row['precio'],2); ?></td>
                    <td><?php echo number_format($row['subtotal'],2); ?></td>
                </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="3" align="right"><strong>Total</strong></td>
                    <td><strong><?php echo number_format($total,2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <?php endif; ?>

        <br>
        <button onclick="window.print();" class="btn btn-primary">
            <i class="fa fa-print"></i> Imprimir
        </button>

    </div>

</body>

</html>