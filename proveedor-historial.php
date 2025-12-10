<?php
session_start();
include("sistema/configuracion.php");
include("sistema/clase/proveedor.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();

$Movimientos = $db->SQL("
    SELECT m.*, p.nombre AS proveedor_nombre
    FROM proveedor_movimiento m
    INNER JOIN proveedor p ON p.id = m.id_proveedor
    ORDER BY m.fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Historial Financiero de Proveedores | <?= TITULO ?></title>
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO."Tema.CSS.php"); ?>
</head>

<body>

    <?php include(MODULO."menu_admin.php"); ?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1>Historial Financiero de Proveedores</h1>
        </div>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                </tr>
            </thead>

            <tbody>
                <?php while($m = $Movimientos->fetch_assoc()): ?>
                <tr>
                    <td><?= $m['proveedor_nombre'] ?></td>
                    <td><?= $m['tipo'] ?></td>
                    <td><?= $m['descripcion'] ?></td>
                    <td><strong><?= number_format($m['monto'],2) ?> Bs</strong></td>
                    <td><?= $m['fecha'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>

    <?php include(MODULO."footer.php"); ?>

</body>

</html>