<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');
include('sistema/clase/tramites.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Tram = new Tramites();

/* ================================================
      VALIDACIÓN DE ID
================================================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de trámite no válido.</div>";
    exit;
}

$id_tramite = intval($_GET['id']);

$T = $Tram->Obtener($id_tramite);
if (!$T) {
    echo "<div class='alert alert-danger'>Trámite no encontrado.</div>";
    exit;
}

/* Para mostrar información del cliente */
$ClienteSql = $db->SQL("SELECT * FROM cliente WHERE id=".$T['id_cliente']." LIMIT 1");
$Cliente = $ClienteSql->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Detalle del Trámite | <?= TITULO ?></title>
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .box-info {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 10px;
        background: #fdfdfd;
        margin-bottom: 20px;
    }

    .timeline {
        border-left: 3px solid #428bca;
        padding-left: 15px;
        margin-top: 15px;
    }

    .timeline-item {
        margin-bottom: 15px;
    }

    .timeline-item span {
        font-weight: bold;
    }
    </style>

</head>

<body>

    <?php include($usuarioApp['id_perfil']==1 ? MODULO.'menu_admin.php' : MODULO.'menu_vendedor.php'); ?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1>Detalle del Trámite #<?= $T['id'] ?></h1>
            <a href="tramites.php" class="btn btn-default pull-right">
                <i class="fa fa-arrow-left"></i> Volver
            </a>
            <div style="clear:both;"></div>
        </div>

        <!-- ================================
            DATOS DEL CLIENTE
    ================================= -->
        <div class="box-info">
            <h3><i class="fa fa-user"></i> Cliente</h3>
            <p><b>Nombre:</b> <?= $Cliente['nombre'] ?></p>
            <p><b>CI / Pasaporte:</b> <?= $Cliente['ci_pasaporte'] ?></p>
            <p><b>Teléfono:</b> <?= $Cliente['telefono'] ?></p>
            <p><b>Email:</b> <?= $Cliente['email'] ?></p>
            <p><b>Nacionalidad:</b> <?= $Cliente['nacionalidad'] ?></p>
        </div>

        <!-- ================================
            DATOS DEL TRÁMITE
    ================================= -->
        <div class="box-info">
            <h3><i class="fa fa-file"></i> Información del Trámite</h3>

            <div class="row">
                <div class="col-md-6">
                    <p><b>Tipo:</b> <?= $T['tipo_tramite'] ?></p>
                    <p><b>País destino:</b> <?= $T['pais_destino'] ?></p>
                    <p><b>Fecha inicio:</b> <?= $T['fecha_inicio'] ?></p>
                    <p><b>Entrega:</b> <?= $T['fecha_entrega'] ?: '-' ?></p>
                    <p><b>Vencimiento:</b> <?= $T['fecha_vencimiento'] ?: '-' ?></p>
                </div>
                <div class="col-md-6">
                    <p><b>Estado actual:</b>

                        <?php
                    $estadoLabel = [
                        'PENDIENTE'   => 'label-warning',
                        'EN_PROCESO'  => 'label-info',
                        'FINALIZADO'  => 'label-success',
                        'RECHAZADO'   => 'label-danger'
                    ];
                    ?>

                        <span class="label <?= $estadoLabel[$T['estado']] ?>">
                            <?= $T['estado'] ?>
                        </span>

                    </p>

                    <p><b>Monto estimado:</b> <?= number_format($T['monto_estimado'], 2) ?> Bs</p>
                    <p><b>Observaciones:</b><br><?= nl2br($T['observaciones']) ?></p>
                </div>
            </div>

            <!-- CAMBIO DE ESTADO -->
            <form method="post" style="margin-top:20px;">
                <input type="hidden" name="CambiarEstadoTramite" value="1">
                <input type="hidden" name="id_tramite" value="<?= $T['id'] ?>">

                <label><b>Cambiar estado:</b></label>
                <select name="nuevo_estado" class="form-control" style="max-width:200px; display:inline-block;">
                    <option value="PENDIENTE" <?= $T['estado']=='PENDIENTE'?'selected':'' ?>>Pendiente</option>
                    <option value="EN_PROCESO" <?= $T['estado']=='EN_PROCESO'?'selected':'' ?>>En proceso</option>
                    <option value="FINALIZADO" <?= $T['estado']=='FINALIZADO'?'selected':'' ?>>Finalizado</option>
                    <option value="RECHAZADO" <?= $T['estado']=='RECHAZADO'?'selected':'' ?>>Rechazado</option>
                </select>

                <button class="btn btn-primary">
                    <i class="fa fa-refresh"></i> Actualizar
                </button>
            </form>

        </div>

        <!-- ================================
            TIMELINE / HISTORIAL
    ================================= -->
        <div class="box-info">
            <h3><i class="fa fa-clock-o"></i> Línea de Tiempo</h3>

            <div class="timeline">

                <div class="timeline-item">
                    <span>Inicio:</span> <?= $T['fecha_inicio'] ?>
                </div>

                <?php if ($T['fecha_entrega']): ?>
                <div class="timeline-item">
                    <span>Entrega:</span> <?= $T['fecha_entrega'] ?>
                </div>
                <?php endif; ?>

                <?php if ($T['fecha_vencimiento']): ?>
                <div class="timeline-item">
                    <span>Vencimiento:</span> <?= $T['fecha_vencimiento'] ?>
                </div>
                <?php endif; ?>

                <div class="timeline-item">
                    <span>Estado actual:</span> <?= $T['estado'] ?>
                </div>

            </div>

        </div>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

</body>

</html>