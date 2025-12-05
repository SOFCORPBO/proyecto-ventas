<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$ClienteClase = new Cliente();
//$TramitesClase = new Tramites();
//$AlertasClase = new Alertas();

$idCliente = intval($_GET['id']);

$Cliente = $ClientesClase->ObtenerClientePorId($idCliente);
$Tramites = $ClientesClase->TramitesCliente($idCliente);
$Historial = $ClientesClase->HistorialServicios($idCliente);
$Cotizaciones = $ClientesClase->CotizacionesCliente($idCliente);
$Alertas = $ClientesClase->AlertasCliente($idCliente);
$Dashboard = $ClientesClase->DashboardCliente($idCliente);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Expediente del Cliente | <?= TITULO ?></title>
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php include(MODULO.'menu_admin.php'); ?>

    <div class="container">

        <div class="page-header">
            <h1>Expediente del Cliente</h1>
        </div>

        <?php include("dashboard-cliente.php"); ?>

        <h3>Datos Personales</h3>
        <ul>
            <li><b>Nombre:</b> <?= $Cliente['nombre'] ?></li>
            <li><b>Documento:</b> <?= $Cliente['ci_pasaporte'] ?></li>
            <li><b>Email:</b> <?= $Cliente['email'] ?></li>
            <li><b>Teléfono:</b> <?= $Cliente['telefono'] ?></li>
        </ul>

        <hr>

        <h3>Trámites</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Destino</th>
                    <th>Estado</th>
                    <th>Vencimiento</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while($t = $Tramites->fetch_assoc()): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td><?= $t['tipo_tramite'] ?></td>
                    <td><?= $t['pais_destino'] ?></td>
                    <td><?= $t['estado'] ?></td>
                    <td><?= $t['fecha_vencimiento'] ?></td>
                    <td>
                        <a href="tramites/ver.php?id=<?= $t['id'] ?>" class="btn btn-info btn-xs">Ver</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <hr>

        <h3>Historial de Servicios</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Venta</th>
                    <th>Servicio</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php while($h = $Historial->fetch_assoc()): ?>
                <tr>
                    <td><?= $h['id'] ?></td>
                    <td><?= $h['servicio'] ?></td>
                    <td><?= number_format($h['total'],2) ?></td>
                    <td><?= $h['fecha'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <hr>

        <h3>Cotizaciones</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while($c = $Cotizaciones->fetch_assoc()): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= $c['fecha'] ?></td>
                    <td><?= $c['total'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <hr>

        <h3>Alertas</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while($a = $Alertas->fetch_assoc()): ?>
                <tr>
                    <td><?= $a['mensaje'] ?></td>
                    <td><?= $a['fecha_alerta'] ?></td>
                    <td><?= $a['estado_alerta'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

</body>

</html>