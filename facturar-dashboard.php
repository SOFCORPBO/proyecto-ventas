<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Factura = new Factura();
$Conexion = new Conexion();
$db = $Conexion->Conectar();

/* =====================================================
   KPI PRINCIPALES
===================================================== */

// Total facturado HOY
$facturadoHoy = $Factura->totalFacturadoHoy()->fetch_assoc()['total'] ?? 0;

// Total SIN factura
$ventasSinFact = $Factura->totalSinFactura()->fetch_assoc()['total'] ?? 0;

// Facturación mensual
$mesActual = date("Y-m");

$facturadoMes = $db->query("
    SELECT SUM(total) AS total
    FROM factura
    WHERE DATE_FORMAT(fecha,'%Y-%m')='$mesActual'
")->fetch_assoc()['total'] ?? 0;

// Impuestos del día
$ivaHoy = $facturadoHoy * 0.13;
$itHoy  = $facturadoHoy * 0.03;

/* =====================================================
   LISTADO GENERAL DE VENTAS
===================================================== */
$VentasSQL = $Factura->listarVentas();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Dashboard de Facturación | <?= TITULO ?></title>

    <!-- CSS principal del sistema -->
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">

    <!-- Tema corporativo -->
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .kpi-box {
        padding: 15px;
        border-radius: 6px;
        color: #fff;
        margin-bottom: 15px;
        text-align: center;
        box-shadow: 0 2px 6px #0003;
    }

    .kpi1 {
        background: #3f51b5;
    }

    .kpi2 {
        background: #4caf50;
    }

    .kpi3 {
        background: #f44336;
    }

    .kpi4 {
        background: #009688;
    }

    .panel-heading i {
        margin-right: 6px;
    }
    </style>
</head>

<body>

    <?php
// Menú según perfil
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1>Dashboard de Facturación y Contabilidad</h1>
            <small class="text-muted">
                Análisis completo de ventas, comprobantes e impuestos generados.
            </small>
        </div>

        <!-- ===========================
         KPIs PRINCIPALES
    ============================ -->

        <div class="row">

            <div class="col-sm-3">
                <div class="kpi-box kpi1">
                    <h2><?= number_format($facturadoHoy,2) ?> Bs</h2>
                    <small>Facturado Hoy</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box kpi2">
                    <h2><?= number_format($facturadoMes,2) ?> Bs</h2>
                    <small>Facturación Mensual</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box kpi3">
                    <h2><?= $ventasSinFact ?></h2>
                    <small>Ventas Sin Factura</small>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="kpi-box kpi4">
                    <h2><?= number_format($ivaHoy,2) ?> Bs</h2>
                    <small>IVA Estimado (13%)</small>
                </div>
            </div>

        </div>

        <!-- ===========================
         TABLA DE VENTAS
    ============================ -->

        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class="fa fa-list-alt"></i> Ventas Registradas</strong>
            </div>

            <div class="panel-body">

                <button class="btn btn-info pull-right" data-toggle="modal" data-target="#modalReportes">
                    <i class="fa fa-folder-open"></i> Reportes
                </button>

                <table class="table table-bordered table-striped" id="tabla_facturacion">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while($v = $VentasSQL->fetch_assoc()): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><?= $v['cliente'] ?></td>
                            <td><strong><?= number_format($v['monto'],2) ?> Bs</strong></td>

                            <td>
                                <?php if($v['con_factura']==1): ?>
                                <span class="label label-success">
                                    <i class="fa fa-check"></i> Facturada
                                </span>
                                <?php else: ?>
                                <span class="label label-danger">
                                    <i class="fa fa-close"></i> Sin Factura
                                </span>
                                <?php endif; ?>
                            </td>

                            <td><?= $v['fecha'] ?></td>

                            <td>
                                <?php if($v['con_factura']==0): ?>
                                <button class="btn btn-success btn-xs"
                                    onclick="facturar(<?= $v['id'] ?>, <?= $v['monto'] ?>)" data-toggle="modal"
                                    data-target="#modalFacturacion">
                                    <i class="fa fa-file-text-o"></i> Facturar
                                </button>
                                <?php else: ?>
                                <button class="btn btn-info btn-xs" onclick="verFactura(<?= $v['id'] ?>)"
                                    data-toggle="modal" data-target="#modalFacturacion">
                                    <i class="fa fa-eye"></i> Ver
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            </div>
        </div>

    </div>

    <!-- ===========================
     MODALES Y JS
============================ -->
    <?php include("modales-facturacion.php"); ?>
    <script src="facturacion.js"></script>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_facturacion').dataTable();
    </script>

</body>

</html>