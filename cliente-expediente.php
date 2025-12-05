<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');
include('sistema/clase/clientes.clase.php');
include('sistema/clase/tramites.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$ClienteClase = new Cliente();
$Tram = new Tramites();

/* ============================================
        VALIDAR ID CLIENTE
============================================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger text-center'>Cliente no válido.</div>";
    exit;
}

$idCliente = intval($_GET['id']);

$Cliente = $ClienteClase->ObtenerClientePorId($idCliente);

if (!$Cliente) {
    echo "<div class='alert alert-danger text-center'>El cliente no existe.</div>";
    exit;
}

/* ============================================
        SERVICIOS DEL CLIENTE
============================================ */
$ServiciosSql = $db->SQL("
    SELECT 
        f.id AS id_factura,
        f.fecha AS fecha_venta,
        fd.cantidad,
        fd.precio,
        fd.subtotal,
        fd.comision,
        fd.tipo_pago,
        p.nombre AS producto_nombre,
        u.usuario AS vendedor,
        f.con_factura,
        f.estado
    FROM factura f
    INNER JOIN factura_detalle fd ON fd.idfactura = f.id
    INNER JOIN producto p ON p.id = fd.producto
    LEFT JOIN usuario u ON u.id = f.vendedor
    WHERE f.cliente = '$idCliente'
    ORDER BY f.id DESC
");

$KpiServ = $db->SQL("
    SELECT 
        COUNT(*) AS total_servicios,
        SUM(total) AS monto_total,
        MAX(fecha) AS ultima_compra
    FROM factura
    WHERE cliente = '$idCliente'
");
$KPI_SERV = $KpiServ->fetch_assoc();

/* ============================================
        TRÁMITES DEL CLIENTE
============================================ */
$TramitesCliente = $Tram->PorCliente($idCliente);

$KpiTramSql = $db->SQL("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN estado='PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN estado='EN_PROCESO' THEN 1 ELSE 0 END) AS en_proceso,
        SUM(CASE WHEN estado='FINALIZADO' THEN 1 ELSE 0 END) AS finalizados,
        SUM(CASE WHEN estado='RECHAZADO' THEN 1 ELSE 0 END) AS rechazados
    FROM tramites
    WHERE id_cliente = '$idCliente'
");
$KPI_TRAM = $KpiTramSql->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Expediente del Cliente | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .card-box {
        padding: 12px;
        border-radius: 10px;
        color: #fff;
        margin-bottom: 15px;
        text-align: center;
    }

    .card-blue {
        background: #2196F3;
    }

    .card-green {
        background: #4CAF50;
    }

    .card-orange {
        background: #FF9800;
    }

    .card-yellow {
        background: #FFC107;
    }

    .card-red {
        background: #F44336;
    }
    </style>

</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==2) include(MODULO.'menu_vendedor.php');
else include(MODULO.'menu_admin.php');
?>

    <div class="container" id="wrap">

        <!-- TÍTULO -->
        <div class="page-header">
            <h1>Expediente de: <?= $Cliente['nombre'] ?></h1>

            <a href="cliente.php" class="btn btn-default pull-right">
                <i class="fa fa-arrow-left"></i> Volver
            </a>

            <div style="clear:both;"></div>
        </div>

        <!-- TABS -->
        <ul class="nav nav-tabs">
            <li class="active"><a data-toggle="tab" href="#servicios">Servicios</a></li>
            <li><a data-toggle="tab" href="#tramites">Trámites</a></li>
            <li><a data-toggle="tab" href="#datos">Datos del Cliente</a></li>
        </ul>

        <div class="tab-content">

            <!-- ==================================================
            TAB SERVICIOS
================================================== -->
            <div id="servicios" class="tab-pane fade in active" style="padding-top:20px;">

                <div class="row">
                    <div class="col-sm-3">
                        <div class="card-box card-blue">
                            <h4>Total Servicios</h4>
                            <h2><?= $KPI_SERV['total_servicios'] ?></h2>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="card-box card-green">
                            <h4>Monto Total</h4>
                            <h2><?= number_format($KPI_SERV['monto_total'],2) ?> Bs</h2>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="card-box card-orange">
                            <h4>Última Compra</h4>
                            <h3><?= $KPI_SERV['ultima_compra'] ?: 'N/A' ?></h3>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-striped" id="tabla_servicios">
                    <thead>
                        <tr>
                            <th>#Factura</th>
                            <th>Fecha</th>
                            <th>Servicio</th>
                            <th>Cant.</th>
                            <th>Precio</th>
                            <th>Total</th>
                            <th>Comisión</th>
                            <th>Pago</th>
                            <th>Factura</th>
                            <th>Vendedor</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($s = $ServiciosSql->fetch_assoc()): ?>
                        <tr>
                            <td><?= $s['id_factura'] ?></td>
                            <td><?= $s['fecha_venta'] ?></td>
                            <td><?= $s['producto_nombre'] ?></td>
                            <td><?= $s['cantidad'] ?></td>
                            <td><?= number_format($s['precio'],2) ?></td>
                            <td><?= number_format($s['subtotal'],2) ?></td>
                            <td><?= number_format($s['comision'],2) ?></td>
                            <td><?= $s['tipo_pago'] ?></td>
                            <td><?= $s['con_factura'] ? "<span class='label label-success'>Sí</span>" : "<span class='label label-default'>No</span>" ?>
                            </td>
                            <td><?= $s['vendedor'] ?></td>
                            <td><span class="label label-info"><?= $s['estado'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            </div>

            <!-- ==================================================
            TAB TRÁMITES
================================================== -->
            <div id="tramites" class="tab-pane fade" style="padding-top:20px;">

                <div class="row">
                    <div class="col-sm-2">
                        <div class="card-box card-blue">
                            <h4>Total</h4>
                            <h2><?= $KPI_TRAM['total'] ?></h2>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="card-box card-yellow">
                            <h4>Pendientes</h4>
                            <h2><?= $KPI_TRAM['pendientes'] ?></h2>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="card-box card-orange">
                            <h4>En Proceso</h4>
                            <h2><?= $KPI_TRAM['en_proceso'] ?></h2>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="card-box card-green">
                            <h4>Finalizados</h4>
                            <h2><?= $KPI_TRAM['finalizados'] ?></h2>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="card-box card-red">
                            <h4>Rechazados</h4>
                            <h2><?= $KPI_TRAM['rechazados'] ?></h2>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-striped" id="tabla_tramites">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>País</th>
                            <th>Inicio</th>
                            <th>Entrega</th>
                            <th>Estado</th>
                            <th>Monto</th>
                            <th>Obs.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
            $est = [
                "PENDIENTE"=>"label-warning",
                "EN_PROCESO"=>"label-info",
                "FINALIZADO"=>"label-success",
                "RECHAZADO"=>"label-danger"
            ];
            while($t = $TramitesCliente->fetch_assoc()): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td><?= $t['tipo_tramite'] ?></td>
                            <td><?= $t['pais_destino'] ?></td>
                            <td><?= $t['fecha_inicio'] ?></td>
                            <td><?= $t['fecha_entrega'] ?: "-" ?></td>
                            <td><span class="label <?= $est[$t['estado']] ?>"><?= $t['estado'] ?></span></td>
                            <td><?= number_format($t['monto_estimado'],2) ?></td>
                            <td><?= nl2br($t['observaciones']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            </div>

            <!-- ==================================================
        TAB DATOS DEL CLIENTE
================================================== -->
            <div id="datos" class="tab-pane fade" style="padding-top:20px;">

                <div class="well">
                    <h4><i class="fa fa-user"></i> Datos Generales</h4>

                    <p><b>Nombre:</b> <?= $Cliente['nombre'] ?></p>
                    <p><b>CI/Pasaporte:</b> <?= $Cliente['ci_pasaporte'] ?></p>
                    <p><b>Tipo Documento:</b> <?= $Cliente['tipo_documento'] ?></p>
                    <p><b>Nacionalidad:</b> <?= $Cliente['nacionalidad'] ?></p>
                    <p><b>Nacimiento:</b> <?= $Cliente['fecha_nacimiento'] ?></p>
                    <p><b>Teléfono:</b> <?= $Cliente['telefono'] ?></p>
                    <p><b>Email:</b> <?= $Cliente['email'] ?></p>
                    <p><b>Dirección:</b> <?= $Cliente['direccion'] ?></p>
                    <p><b>Descuento:</b> <?= $Cliente['descuento'] ?>%</p>
                    <p><b>Estado:</b>
                        <?= $Cliente['habilitado'] 
                ? "<span class='label label-success'>Activo</span>"
                : "<span class='label label-danger'>Inactivo</span>"
            ?>
                    </p>

                </div>

            </div>

        </div><!-- tabs -->

    </div><!-- container -->

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_servicios').dataTable();
    $('#tabla_tramites').dataTable();
    </script>

</body>

</html>