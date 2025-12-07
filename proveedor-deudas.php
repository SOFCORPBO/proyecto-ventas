<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');


$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();
global $db;

$Deudas = $db->SQL("
    SELECT *
    FROM proveedor
    WHERE saldo_pendiente > 0
    ORDER BY saldo_pendiente DESC
");

$TotalDeuda = $db->SQL("
    SELECT SUM(saldo_pendiente) AS total
    FROM proveedor
")->fetch_assoc()['total'];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Deudas de Proveedores | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    .badge-riesgo-bajo {
        background: #4caf50;
    }

    .badge-riesgo-medio {
        background: #ff9800;
    }

    .badge-riesgo-alto {
        background: #f44336;
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
            <h1><i class="fa fa-exclamation-circle"></i> Deudas de Proveedores</h1>
            <p class="text-muted">Listado de saldos pendientes por proveedor.</p>
        </div>

        <div class="alert alert-info">
            <strong>Deuda total:</strong> <?= number_format($TotalDeuda,2) ?> Bs
        </div>

        <table class="table table-bordered table-striped" id="tabla_deudas">
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Tipo</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Saldo Pendiente</th>
                    <th>Riesgo</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $Deudas->fetch_assoc()): 
            $saldo = (float)$p['saldo_pendiente'];
            $riesgo = 'Bajo';
            $clase  = 'badge-riesgo-bajo';
            if ($saldo >= 5000 && $saldo < 20000) {
                $riesgo = 'Medio';
                $clase  = 'badge-riesgo-medio';
            } elseif ($saldo >= 20000) {
                $riesgo = 'Alto';
                $clase  = 'badge-riesgo-alto';
            }
        ?>
                <tr>
                    <td><?= $p['nombre'] ?></td>
                    <td><?= $p['tipo_proveedor'] ?></td>
                    <td><?= $p['contacto'] ?></td>
                    <td><?= $p['telefono'] ?></td>
                    <td><strong><?= number_format($saldo,2) ?> Bs</strong></td>
                    <td><span class="badge <?= $clase ?>"><?= $riesgo ?></span></td>
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
    $('#tabla_deudas').dataTable();
    </script>

</body>

</html>