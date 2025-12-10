<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');
include('sistema/clase/proveedor.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();

/* ================================
   KPI PRINCIPALES
================================ */
$KPI = $Proveedor->KPIs();

// Facturas según estado
$FacturasEstados = $db->SQL("
    SELECT estado, COUNT(*) AS total, SUM(monto_total - monto_pagado) AS saldo
    FROM proveedor_factura
    GROUP BY estado
");

$fact_pend = $fact_parc = $fact_pag = $fact_venc = 0;
while ($f = $FacturasEstados->fetch_assoc()) {
    if ($f['estado'] == 'PENDIENTE') $fact_pend = $f['total'];
    if ($f['estado'] == 'PARCIAL')   $fact_parc = $f['total'];
    if ($f['estado'] == 'PAGADA')    $fact_pag  = $f['total'];
    if ($f['estado'] == 'VENCIDA')   $fact_venc = $f['total'];
}

// Top 10 proveedores con más deuda
$TopDeudaSQL = $db->SQL("
    SELECT p.nombre, p.tipo_proveedor, p.saldo_pendiente
    FROM proveedor p
    WHERE p.saldo_pendiente > 0
    ORDER BY p.saldo_pendiente DESC
    LIMIT 10
");

// Últimos movimientos financieros
$MovimientosSQL = $db->SQL("
    SELECT pm.*, pr.nombre AS proveedor_nombre
    FROM proveedor_movimiento pm
    LEFT JOIN proveedor pr ON pr.id = pm.id_proveedor
    ORDER BY pm.fecha DESC
    LIMIT 50
");

// Ingresos/Egresos mensuales a proveedores
$PagosMensualesSQL = $db->SQL("
    SELECT DATE_FORMAT(fecha_pago, '%Y-%m') AS mes, SUM(monto) AS total
    FROM proveedor_pago
    GROUP BY mes
    ORDER BY mes ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Dashboard de Proveedores | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
        .kpi-box {
            padding: 18px;
            color: #fff;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .k1 { background:#3f51b5; }
        .k2 { background:#4caf50; }
        .k3 { background:#f44336; }
        .k4 { background:#009688; }
        .k5 { background:#ff9800; }
        .k6 { background:#9c27b0; }
    </style>
</head>
<body>

<?php
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

<div class="container" id="wrap">

    <div class="page-header">
        <h1>Dashboard de Proveedores</h1>
        <small class="text-muted">
            Visión general de facturas, pagos, deudas y comportamiento financiero con proveedores.
        </small>
    </div>

    <!-- ===========================
         KPIs PRINCIPALES
    ============================ -->
    <div class="row">
        <div class="col-sm-3">
            <div class="kpi-box k1">
                <h2><?= (int)$KPI['total'] ?></h2>
                <small>Total Proveedores</small>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="kpi-box k2">
                <h2><?= (int)$KPI['activos'] ?></h2>
                <small>Proveedores Activos</small>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="kpi-box k3">
                <h2><?= (int)$KPI['inactivos'] ?></h2>
                <small>Proveedores Inactivos</small>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="kpi-box k4">
                <h2><?= number_format($KPI['deuda_total'],2) ?> Bs</h2>
                <small>Deuda Total con Proveedores</small>
            </div>
        </div>

        <div class="col-sm-3">
            <div class="kpi-box k5">
                <h2><?= $fact_pend ?></h2>
                <small>Facturas Pendientes</small>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="kpi-box k5">
                <h2><?= $fact_parc ?></h2>
                <small>Facturas en Pago Parcial</small>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="kpi-box k6">
                <h2><?= $fact_venc ?></h2>
                <small>Facturas Vencidas</small>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="kpi-box k6">
                <h2><?= $fact_pag ?></h2>
                <small>Facturas Pagadas</small>
            </div>
        </div>
    </div>

    <!-- ===========================
         TABS PRINCIPALES
    ============================ -->
    <ul class="nav nav-tabs">
        <li class="active"><a href="#tab-resumen" data-toggle="tab">Resumen General</a></li>
        <li><a href="#tab-topdeuda" data-toggle="tab">Top Deudas</a></li>
        <li><a href="#tab-movimientos" data-toggle="tab">Movimientos</a></li>
        <li><a href="#tab-grafico" data-toggle="tab">Pagos Mensuales</a></li>
    </ul>

    <div class="tab-content" style="margin-top:15px;">

        <!-- TAB RESUMEN -->
        <div class="tab-pane fade in active" id="tab-resumen">
            <div class="alert alert-info">
                Este panel te permite entender rápidamente la situación financiera global con tus proveedores:
                total de deudas, facturas vencidas, y comportamiento de pagos.
            </div>
        </div>

        <!-- TAB TOP DEUDA -->
        <div class="tab-pane fade" id="tab-topdeuda">
            <div class="panel panel-default">
                <div class="panel-heading"><strong>Top 10 Proveedores con Mayor Deuda</strong></div>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Tipo</th>
                            <th>Saldo Pendiente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($t = $TopDeudaSQL->fetch_assoc()): ?>
                        <tr>
                            <td><?= $t['nombre'] ?></td>
                            <td><?= $t['tipo_proveedor'] ?></td>
                            <td><strong><?= number_format($t['saldo_pendiente'],2) ?> Bs</strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB MOVIMIENTOS -->
        <div class="tab-pane fade" id="tab-movimientos">
            <div class="panel panel-default">
                <div class="panel-heading"><strong>Últimos Movimientos con Proveedores</strong></div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped" id="tabla_movimientos_prov">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($m = $MovimientosSQL->fetch_assoc()): ?>
                            <tr>
                                <td><?= $m['fecha'] ?></td>
                                <td><?= $m['proveedor_nombre'] ?></td>
                                <td><?= $m['tipo'] ?></td>
                                <td><?= $m['descripcion'] ?></td>
                                <td><strong><?= number_format($m['monto'],2) ?> Bs</strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB GRAFICO PAGOS MENSUALES -->
        <div class="tab-pane fade" id="tab-grafico">
            <div class="panel panel-default">
                <div class="panel-heading"><strong>Pagos Mensuales a Proveedores</strong></div>
                <div class="panel-body">
                    <canvas id="chart_pagos_proveedores" height="100"></canvas>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include(MODULO.'footer.php'); ?>
<?php include(MODULO.'Tema.JS.php'); ?>

<script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
<script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$('#tabla_movimientos_prov').dataTable();

// Gráfico pagos mensuales
var ctx = document.getElementById('chart_pagos_proveedores').getContext('2d');
var chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [
            <?php 
            $PagosMensualesSQL->data_seek(0);
            while($m = $PagosMensualesSQL->fetch_assoc()):
                echo "'".$m['mes']."',";
            endwhile;
            ?>
        ],
        datasets: [{
            label: "Pagos a Proveedores",
            backgroundColor: "#3f51b5",
            data: [
                <?php 
                $PagosMensualesSQL->data_seek(0);
                while($m = $PagosMensualesSQL->fetch_assoc()):
                    echo $m['total'].",";
                endwhile;
                ?>
            ]
        }]
    }
});
</script>

</body>
</html>
