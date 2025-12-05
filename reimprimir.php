<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID inválido";
    exit;
}

$idfactura = intval($_GET['id']);

/*
|---------------------------------------------------------
| CABECERA DESDE ventas
|---------------------------------------------------------
*/
$CabeceraSQL = $db->SQL("
    SELECT 
        v.idfactura,
        SUM(v.totalprecio) AS total,
        MIN(v.fecha)       AS fecha,
        MIN(v.hora)        AS hora,
        v.vendedor,
        v.cliente,
        cli.nombre AS cliente_nombre,
        ven.nombre AS vendedor_nombre,
        ven.apellido1 AS vendedor_apellido1,
        ven.apellido2 AS vendedor_apellido2
    FROM ventas v
    LEFT JOIN cliente   cli ON cli.id = v.cliente
    LEFT JOIN vendedores ven ON ven.id = v.vendedor
    WHERE v.idfactura = {$idfactura}
    GROUP BY 
        v.idfactura,
        v.vendedor,
        v.cliente,
        cli.nombre,
        ven.nombre,
        ven.apellido1,
        ven.apellido2
    LIMIT 1
");

if ($CabeceraSQL->num_rows == 0) {
    echo "Factura no encontrada";
    exit;
}

$Factura = $CabeceraSQL->fetch_assoc();

/*
|---------------------------------------------------------
| DETALLE DESDE ventas
|---------------------------------------------------------
*/
$DetalleSQL = $db->SQL("
    SELECT v.*, p.nombre
    FROM ventas v
    INNER JOIN producto p ON p.id = v.producto
    WHERE v.idfactura = {$idfactura}
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Factura #<?php echo $idfactura; ?></title>

    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
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
        font-size: 13px;
    }
    </style>
</head>

<body>

    <div class="container">

        <h3>Factura #<?php echo $idfactura; ?></h3>

        <p><strong>Cliente:</strong> <?php echo $Factura['cliente_nombre']; ?></p>
        <p><strong>Vendedor:</strong>
            <?php
        echo trim(
            $Factura['vendedor_nombre'].' '.
            $Factura['vendedor_apellido1'].' '.
            $Factura['vendedor_apellido2']
        );
        ?>
        </p>
        <p><strong>Fecha:</strong> <?php echo $Factura['fecha'].' '.$Factura['hora']; ?></p>
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
                    <td><?php echo number_format($row['totalprecio'],2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php endif; ?>

        <div class="text-center" style="margin-top:20px;">
            <button class="btn btn-primary" onclick="window.print();">
                Imprimir
            </button>
        </div>

    </div>

</body>

</html>