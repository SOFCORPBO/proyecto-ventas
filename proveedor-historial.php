<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');


$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();
global $db;

$Historial = $db->SQL("
    SELECT 'FACTURA' AS tipo, f.fecha_emision AS fecha, f.monto_total AS monto,
           p.nombre AS proveedor, f.nro_factura AS ref
    FROM proveedor_factura f
    INNER JOIN proveedor p ON p.id=f.id_proveedor

    UNION ALL

    SELECT 'PAGO' AS tipo, pg.fecha_pago AS fecha, pg.monto AS monto,
           p.nombre AS proveedor, pg.referencia AS ref
    FROM proveedor_pago pg
    INNER JOIN proveedor p ON p.id=pg.id_proveedor

    ORDER BY fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Historial Financiero Proveedores | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    .tipo-factura {
        color: #3f51b5;
        font-weight: bold;
    }

    .tipo-pago {
        color: #4caf50;
        font-weight: bold;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1><i class="fa fa-book"></i> Historial Financiero de Proveedores</h1>
            <p class="text-muted">Línea de tiempo de facturas y pagos.</p>
        </div>

        <table class="table table-bordered table-striped" id="tabla_historial">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Tipo</th>
                    <th>Referencia</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php while($h = $Historial->fetch_assoc()): ?>
                <tr>
                    <td><?= $h['fecha'] ?></td>
                    <td><?= $h['proveedor'] ?></td>
                    <td>
                        <?php if($h['tipo']=='FACTURA'): ?>
                        <span class="tipo-factura">Factura</span>
                        <?php else: ?>
                        <span class="tipo-pago">Pago</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $h['ref'] ?></td>
                    <td><strong><?= number_format($h['monto'],2) ?> Bs</strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_historial').dataTable({
        "order": [
            [0, "desc"]
        ]
    });
    </script>

</body>

</html>