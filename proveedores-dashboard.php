<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');
include('sistema/clase/proveedor.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();

/* ============================
   CONSULTAS PRINCIPALES
============================ */
$KPI = $Proveedor->KPIs();

$Facturas = $db->SQL("
    SELECT f.*, p.nombre AS proveedor
    FROM proveedor_factura f
    INNER JOIN proveedor p ON p.id=f.id_proveedor
    ORDER BY f.id DESC
");

$Pagos = $db->SQL("
    SELECT pg.*, p.nombre AS proveedor
    FROM proveedor_pago pg
    INNER JOIN proveedor p ON p.id=pg.id_proveedor
    ORDER BY pg.id DESC
");

$Deudas = $db->SQL("
    SELECT p.id, p.nombre, p.saldo_pendiente
    FROM proveedor p
    WHERE p.saldo_pendiente > 0
    ORDER BY p.saldo_pendiente DESC
");

$Historial = $db->SQL("
    SELECT 'FACTURA' AS tipo, f.fecha_emision AS fecha, f.monto_total AS monto, p.nombre AS proveedor
    FROM proveedor_factura f
    INNER JOIN proveedor p ON p.id=f.id_proveedor

    UNION ALL

    SELECT 'PAGO' AS tipo, pg.fecha_pago AS fecha, pg.monto AS monto, p.nombre AS proveedor
    FROM proveedor_pago pg
    INNER JOIN proveedor p ON p.id=pg.id_proveedor

    ORDER BY fecha DESC
");

/* ============================
   INGRESOS MENSUALES (FACTURAS)
============================ */
$MontosMensuales = $db->SQL("
    SELECT DATE_FORMAT(fecha_emision, '%Y-%m') AS mes,
           SUM(monto_total) AS total
    FROM proveedor_factura
    GROUP BY mes
    ORDER BY mes ASC
");

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Dashboard Proveedores | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    .kpi {
        padding: 25px;
        border-radius: 8px;
        color: #fff;
        text-align: center;
        margin-bottom: 20px;
    }

    .k1 {
        background: #3f51b5;
    }

    .k2 {
        background: #4caf50;
    }

    .k3 {
        background: #f44336;
    }

    .k4 {
        background: #009688;
    }

    .k5 {
        background: #ff9800;
    }

    .tab-pane {
        padding-top: 20px;
    }

    .timeline {
        border-left: 3px solid #3f51b5;
        margin-left: 20px;
        padding-left: 20px;
    }

    .timeline-item {
        margin-bottom: 20px;
    }

    .timeline-item span {
        font-weight: bold;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1><i class="fa fa-truck"></i> Dashboard de Proveedores</h1>
            <p class="text-muted">Análisis financiero completo de proveedores.</p>
        </div>

        <!-- ============================
         KPIs PRINCIPALES
    ============================= -->
        <div class="row">
            <div class="col-sm-3">
                <div class="kpi k1">
                    <h2><?= $KPI['total'] ?></h2>
                    <small>Total Proveedores</small>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi k2">
                    <h2><?= $KPI['activos'] ?></h2>
                    <small>Activos</small>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi k3">
                    <h2><?= $KPI['inactivos'] ?></h2>
                    <small>Inactivos</small>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="kpi k4">
                    <h2><?= number_format($KPI['deuda_total'],2) ?> Bs</h2>
                    <small>Deuda Total</small>
                </div>
            </div>
        </div>

        <!-- ============================
         PESTAÑAS
    ============================= -->
        <ul class="nav nav-tabs">
            <li class="active"><a data-toggle="tab" href="#kpis">Resumen</a></li>
            <li><a data-toggle="tab" href="#facturas">Facturas</a></li>
            <li><a data-toggle="tab" href="#pagos">Pagos</a></li>
            <li><a data-toggle="tab" href="#deudas">Deudas</a></li>
            <li><a data-toggle="tab" href="#historial">Historial</a></li>
        </ul>

        <div class="tab-content">

            <!-- ============================
             TAB 1 – KPIs y GRÁFICOS
        ============================= -->
            <div id="kpis" class="tab-pane fade in active">

                <h3><i class="fa fa-area-chart"></i> Resumen Financiero General</h3>

                <canvas id="grafico_montos" height="100"></canvas>

            </div>

            <!-- ============================
             TAB 2 – FACTURAS
        ============================= -->
            <div id="facturas" class="tab-pane fade">

                <h3><i class="fa fa-file-text-o"></i> Facturas Recibidas</h3>

                <table class="table table-bordered table-striped" id="tabla_facturas">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Monto Total</th>
                            <th>Pagado</th>
                            <th>Pendiente</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php while($f = $Facturas->fetch_assoc()): 
                    $pendiente = $f['monto_total'] - $f['monto_pagado'];
                ?>
                        <tr>
                            <td><?= $f['id'] ?></td>
                            <td><?= $f['proveedor'] ?></td>
                            <td><?= $f['fecha_emision'] ?></td>
                            <td><?= number_format($f['monto_total'],2) ?> Bs</td>
                            <td><?= number_format($f['monto_pagado'],2) ?> Bs</td>
                            <td><strong><?= number_format($pendiente,2) ?> Bs</strong></td>
                        </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>

            </div>

            <!-- ============================
             TAB 3 – PAGOS
        ============================= -->
            <div id="pagos" class="tab-pane fade">

                <h3><i class="fa fa-money"></i> Pagos a Proveedores</h3>

                <table class="table table-bordered table-striped" id="tabla_pagos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Referencia</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php while($p = $Pagos->fetch_assoc()): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= $p['proveedor'] ?></td>
                            <td><?= $p['fecha_pago'] ?></td>
                            <td><?= number_format($p['monto'],2) ?> Bs</td>
                            <td><?= $p['metodo_pago'] ?></td>
                            <td><?= $p['referencia'] ?></td>
                        </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>

            </div>

            <!-- ============================
             TAB 4 – DEUDAS
        ============================= -->
            <div id="deudas" class="tab-pane fade">

                <h3><i class="fa fa-exclamation-circle"></i> Deudas Pendientes</h3>

                <table class="table table-bordered table-striped" id="tabla_deudas">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Saldo Pendiente</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php while($d = $Deudas->fetch_assoc()): ?>
                        <tr>
                            <td><?= $d['nombre'] ?></td>
                            <td><strong><?= number_format($d['saldo_pendiente'],2) ?> Bs</strong></td>
                        </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>

            </div>

            <!-- ============================
             TAB 5 – HISTORIAL
        ============================= -->
            <div id="historial" class="tab-pane fade">

                <h3><i class="fa fa-book"></i> Historial Financiero</h3>

                <div class="timeline">

                    <?php while($h = $Historial->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <span><?= $h['fecha'] ?> – <?= $h['proveedor'] ?></span><br>
                        <strong><?= $h['tipo'] ?></strong>: <?= number_format($h['monto'],2) ?> Bs
                    </div>
                    <?php endwhile; ?>

                </div>

            </div>

        </div> <!-- /tab-content -->

    </div><!-- /container -->


    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    $("#tabla_facturas").dataTable();
    $("#tabla_pagos").dataTable();
    $("#tabla_deudas").dataTable();

    var ctx = document.getElementById("grafico_montos").getContext("2d");

    var meses = [
        <?php 
            $MontosMensuales->data_seek(0);
            while($m = $MontosMensuales->fetch_assoc()):
                echo "'".$m['mes']."',";
            endwhile;
        ?>
    ];

    var totales = [
        <?php 
            $MontosMensuales->data_seek(0);
            while($m = $MontosMensuales->fetch_assoc()):
                echo $m['total'].",";
            endwhile;
        ?>
    ];

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: meses,
            datasets: [{
                label: "Facturación de Proveedores",
                backgroundColor: "#3f51b5",
                data: totales
            }]
        }
    });
    </script>

</body>

</html>