<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');
include('sistema/clase/tramites.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Tram = new Tramites();

/* ============================================
          VALIDAR CLIENTE
============================================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Cliente no válido.</div>";
    exit;
}

$idCliente = intval($_GET['id']);

/* ============================================
          OBTENER DATOS DEL CLIENTE
============================================ */
$ClienteSql = $db->SQL("SELECT * FROM cliente WHERE id=$idCliente LIMIT 1");
$Cliente = $ClienteSql->fetch_assoc();

if (!$Cliente) {
    echo "<div class='alert alert-danger'>El cliente no existe.</div>";
    exit;
}

/* ============================================
        TRÁMITES DEL CLIENTE
============================================ */
$TramitesCliente = $Tram->PorCliente($idCliente);

/* ============================================
        KPI DEL CLIENTE
============================================ */
$KPI = [
    'total' => 0,
    'pendientes' => 0,
    'en_proceso' => 0,
    'finalizados' => 0,
    'rechazados' => 0
];

$KpiSql = $db->SQL("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN estado='PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN estado='EN_PROCESO' THEN 1 ELSE 0 END) AS en_proceso,
        SUM(CASE WHEN estado='FINALIZADO' THEN 1 ELSE 0 END) AS finalizados,
        SUM(CASE WHEN estado='RECHAZADO' THEN 1 ELSE 0 END) AS rechazados
    FROM tramites
    WHERE id_cliente = '$idCliente'
");

$KPI = $KpiSql->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Trámites del Cliente | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .card-box {
        padding: 14px;
        border-radius: 10px;
        color: #fff;
        margin-bottom: 15px;
        text-align: center;
    }

    .card-blue {
        background: #2196F3;
    }

    .card-yellow {
        background: #FFC107;
    }

    .card-cyan {
        background: #03A9F4;
    }

    .card-green {
        background: #4CAF50;
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

        <!-- TITULO -->
        <div class="page-header">
            <h1>Trámites de: <?= $Cliente['nombre'] ?></h1>

            <a href="clientes.php" class="btn btn-default pull-right">
                <i class="fa fa-arrow-left"></i> Volver
            </a>

            <div style="clear:both;"></div>
        </div>

        <!-- DATOS DEL CLIENTE -->
        <div class="well">
            <h4><i class="fa fa-user"></i> Datos del Cliente</h4>
            <p><b>Nombre:</b> <?= $Cliente['nombre'] ?></p>
            <p><b>CI / Pasaporte:</b> <?= $Cliente['ci_pasaporte'] ?></p>
            <p><b>Teléfono:</b> <?= $Cliente['telefono'] ?></p>
            <p><b>Email:</b> <?= $Cliente['email'] ?></p>
        </div>

        <!-- KPI -->
        <div class="row">

            <div class="col-sm-2">
                <div class="card-box card-blue">
                    <h4>Total Trámites</h4>
                    <h2><?= $KPI['total'] ?></h2>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="card-box card-yellow">
                    <h4>Pendientes</h4>
                    <h2><?= $KPI['pendientes'] ?></h2>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="card-box card-cyan">
                    <h4>En Proceso</h4>
                    <h2><?= $KPI['en_proceso'] ?></h2>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="card-box card-green">
                    <h4>Finalizados</h4>
                    <h2><?= $KPI['finalizados'] ?></h2>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="card-box card-red">
                    <h4>Rechazados</h4>
                    <h2><?= $KPI['rechazados'] ?></h2>
                </div>
            </div>

        </div>

        <!-- TABLA DE TRÁMITES -->
        <table id="tabla_tramites" class="table table-bordered table-striped">
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
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php
$estados = [
    "PENDIENTE"   => "label-warning",
    "EN_PROCESO"  => "label-info",
    "FINALIZADO"  => "label-success",
    "RECHAZADO"   => "label-danger"
];

while($t = $TramitesCliente->fetch_assoc()):
?>
                <tr>

                    <td><?= $t['id'] ?></td>
                    <td><?= $t['tipo_tramite'] ?></td>
                    <td><?= $t['pais_destino'] ?></td>
                    <td><?= $t['fecha_inicio'] ?></td>
                    <td><?= $t['fecha_entrega'] ?: '-' ?></td>

                    <td>
                        <span class="label <?= $estados[$t['estado']] ?>"><?= $t['estado'] ?></span>
                    </td>

                    <td><?= number_format($t['monto_estimado'],2) ?></td>
                    <td><?= nl2br($t['observaciones']) ?></td>

                    <td>
                        <a href="ver-tramites.php?id=<?= $t['id'] ?>" class="btn btn-xs btn-primary">
                            <i class="fa fa-eye"></i>
                        </a>
                    </td>

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
    $('#tabla_tramites').dataTable();
    </script>

</body>

</html>