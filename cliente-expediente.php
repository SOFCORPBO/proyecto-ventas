<?php
session_start();
include('sistema/configuracion.php');


$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$ClienteClase = new Cliente();
$TramiteClase = new Tramites();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='alert alert-danger'>Cliente no válido.</div>");
}

$idCliente = (int)$_GET['id'];

// ===============================
//  DATOS DEL CLIENTE
// ===============================
$Cliente = $ClienteClase->ObtenerClientePorId($idCliente);
if (!$Cliente) {
    die("<div class='alert alert-danger'>El cliente no existe.</div>");
}

// ===============================
//  HISTORIAL DE VENTAS
// ===============================
$VentasSQL = $db->SQL("
    SELECT v.*, p.nombre AS servicio
    FROM ventas v
    LEFT JOIN producto p ON p.id = v.producto
    WHERE v.cliente = {$idCliente}
    ORDER BY v.id DESC
");

$TotalGastadoSQL = $db->SQL("
    SELECT SUM(totalprecio) AS total
    FROM ventas
    WHERE cliente = {$idCliente}
");
$TotalGastado = $TotalGastadoSQL->fetch_assoc()['total'] ?? 0;

// ===============================
//  HISTORIAL DE TRÁMITES
// ===============================
$TramitesSQL = $TramiteClase->PorCliente($idCliente);

// KPIs trámites
$KPIs = [
    'pendientes' => 0,
    'proceso' => 0,
    'finalizados' => 0,
    'rechazados' => 0,
];

foreach ($TramitesSQL as $t) {
    if ($t['estado'] == 'PENDIENTE') $KPIs['pendientes']++;
    if ($t['estado'] == 'EN_PROCESO') $KPIs['proceso']++;
    if ($t['estado'] == 'FINALIZADO') $KPIs['finalizados']++;
    if ($t['estado'] == 'RECHAZADO') $KPIs['rechazados']++;
}

// Reiniciar puntero para iterar después
$TramitesSQL->data_seek(0);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Expediente del Cliente | <?= TITULO ?></title>
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    .kpi-box {
        text-align: center;
        padding: 18px;
        border-radius: 8px;
        color: #fff;
        margin-bottom: 15px;
    }

    .kpi-total {
        background: #3f51b5;
    }

    .kpi-ok {
        background: #4caf50;
    }

    .kpi-warning {
        background: #ff9800;
    }

    .kpi-danger {
        background: #f44336;
    }

    .timeline {
        border-left: 3px solid #3f51b5;
        margin-left: 20px;
        padding-left: 20px;
    }

    .timeline-item {
        margin-bottom: 20px;
    }

    .timeline-item h4 {
        margin-bottom: 5px;
    }

    .timeline-item small {
        color: gray;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 2) include(MODULO.'menu_vendedor.php');
else include(MODULO.'menu_admin.php');
?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1>Expediente de <?= $Cliente['nombre'] ?></h1>
            <a href="cliente.php" class="btn btn-default">← Volver</a>
        </div>

        <!-- ======================================================
         DATOS DEL CLIENTE
    ======================================================= -->
        <div class="panel panel-primary">
            <div class="panel-heading">Datos del Cliente</div>
            <div class="panel-body">
                <div class="row">

                    <div class="col-sm-4">
                        <strong>Nombre:</strong> <br><?= $Cliente['nombre'] ?>
                    </div>

                    <div class="col-sm-4">
                        <strong>CI / Pasaporte:</strong> <br><?= $Cliente['ci_pasaporte'] ?>
                    </div>

                    <div class="col-sm-4">
                        <strong>Teléfono:</strong> <br><?= $Cliente['telefono'] ?>
                    </div>

                </div>
                <hr>
                <div class="row">

                    <div class="col-sm-4">
                        <strong>Email:</strong> <br><?= $Cliente['email'] ?>
                    </div>

                    <div class="col-sm-4">
                        <strong>Nacionalidad:</strong> <br><?= $Cliente['nacionalidad'] ?>
                    </div>

                    <div class="col-sm-4">
                        <strong>Descuento:</strong> <br><?= $Cliente['descuento'] ?>%
                    </div>

                </div>
            </div>
        </div>


        <!-- ======================================================
         KPIs DEL CLIENTE
    ======================================================= -->
        <div class="row">

            <div class="col-sm-3">
                <div class="kpi-box kpi-total">
                    <h4>Total Gastado</h4>
                    <h3><?= number_format($TotalGastado, 2) ?> Bs</h3>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box kpi-ok">
                    <h4>Servicios Comprados</h4>
                    <h3><?= $VentasSQL->num_rows ?></h3>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box kpi-warning">
                    <h4>Trámites en Proceso</h4>
                    <h3><?= $KPIs['proceso'] ?></h3>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box kpi-danger">
                    <h4>Trámites Pendientes</h4>
                    <h3><?= $KPIs['pendientes'] ?></h3>
                </div>
            </div>

        </div>


        <!-- ======================================================
         HISTORIAL DE SERVICIOS / VENTAS
    ======================================================= -->
        <div class="panel panel-success">
            <div class="panel-heading">Historial de Servicios Comprados</div>
            <div class="panel-body">

                <?php if ($VentasSQL->num_rows == 0): ?>
                <div class="alert alert-info">Este cliente no tiene ventas registradas.</div>
                <?php else: ?>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($v = $VentasSQL->fetch_assoc()): ?>
                        <tr>
                            <td><?= $v['servicio'] ?></td>
                            <td><?= $v['cantidad'] ?></td>
                            <td><?= number_format($v['totalprecio'], 2) ?> Bs</td>
                            <td><?= $v['fecha'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php endif; ?>

            </div>
        </div>


        <!-- ======================================================
         HISTORIAL DE TRÁMITES
    ======================================================= -->
        <div class="panel panel-info">
            <div class="panel-heading">Trámites del Cliente</div>
            <div class="panel-body">

                <?php if ($TramitesSQL->num_rows == 0): ?>
                <div class="alert alert-warning">Este cliente no tiene trámites registrados.</div>
                <?php else: ?>

                <div class="timeline">

                    <?php while($t = $TramitesSQL->fetch_assoc()): ?>
                    <div class="timeline-item">

                        <h4><?= $t['tipo_tramite'] ?> – <?= $t['estado'] ?></h4>
                        <small>
                            Inicio: <?= $t['fecha_inicio'] ?> |
                            Entrega: <?= $t['fecha_entrega'] ?: 'No registrada' ?>
                        </small>
                        <p>
                            <strong>País:</strong> <?= $t['pais_destino'] ?><br>
                            <strong>Monto estimado:</strong> <?= number_format($t['monto_estimado'],2) ?> Bs<br>
                            <strong>Observaciones:</strong> <?= nl2br($t['observaciones']) ?>
                        </p>

                    </div>
                    <?php endwhile; ?>

                </div>

                <?php endif; ?>

            </div>
        </div>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

</body>

</html>