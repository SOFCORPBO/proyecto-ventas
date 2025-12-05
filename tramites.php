<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');
include('sistema/clase/tramites.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Tram = new Tramites();

/* ============================================
      MANEJO DE ACCIONES
============================================ */
$mensaje = "";
$tipo_mensaje = "info";

/* GUARDAR / EDITAR */
if (isset($_POST['GuardarTramite'])) {
    $r = $Tram->Guardar($_POST);
    $mensaje = $r['msg'];
    $tipo_mensaje = $r['ok'] ? 'success' : 'danger';
}

/* CAMBIAR ESTADO */
if (isset($_POST['CambiarEstadoTramite'])) {
    $Tram->CambiarEstado($_POST['id_tramite'], $_POST['nuevo_estado']);
    $mensaje = "Estado del trámite actualizado.";
    $tipo_mensaje = "success";
}

/* DESACTIVAR */
if (isset($_POST['DesactivarTramite'])) {
    $Tram->Desactivar($_POST['id_tramite']);
    $mensaje = "Trámite desactivado.";
    $tipo_mensaje = "warning";
}

/* ACTIVAR */
if (isset($_POST['ActivarTramite'])) {
    $Tram->Activar($_POST['id_tramite']);
    $mensaje = "Trámite activado.";
    $tipo_mensaje = "success";
}

/* ============================================
      FILTROS
============================================ */
$f = [
    'desde'      => $_GET['desde'] ?? '',
    'hasta'      => $_GET['hasta'] ?? '',
    'estado'     => $_GET['estado'] ?? '',
    'tipo'       => $_GET['tipo'] ?? '',
    'id_cliente' => $_GET['id_cliente'] ?? ''
];

$KPI           = $Tram->KPIs();
$ListaTramites = $Tram->Listar($f);
$ListaClientes = $Tram->Clientes();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Gestión de Trámites | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .card-box {
        padding: 18px;
        border-radius: 10px;
        color: #fff;
        margin-bottom: 15px;
        text-align: center;
    }

    .card-total {
        background: #607d8b;
    }

    .card-pendiente {
        background: #ff9800;
    }

    .card-proceso {
        background: #03a9f4;
    }

    .card-finalizado {
        background: #4caf50;
    }

    .card-rechazado {
        background: #ff5722;
    }

    .filtros-box {
        padding: 12px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==2) include(MODULO.'menu_vendedor.php');
else include(MODULO.'menu_admin.php');
?>

    <div class="container" id="wrap">

        <!-- =================================
      TÍTULO
================================= -->
        <div class="page-header">
            <h1>Gestión de Trámites</h1>

            <button class="btn btn-primary pull-right" id="btnNuevo" data-toggle="modal" data-target="#ModalTramite">
                <i class="fa fa-plus"></i> Nuevo Trámite
            </button>
            <div style="clear:both;"></div>
        </div>

        <!-- MENSAJE -->
        <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>

        <!-- =================================
      KPI TARJETAS
================================= -->
        <div class="row">

            <div class="col-sm-2">
                <div class="card-box card-total">
                    <h4>Total</h4>
                    <h2><?= $KPI['total'] ?></h2>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="card-box card-pendiente">
                    <h4>Pendientes</h4>
                    <h2><?= $KPI['pendientes'] ?></h2>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="card-box card-proceso">
                    <h4>En Proceso</h4>
                    <h2><?= $KPI['en_proceso'] ?></h2>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="card-box card-finalizado">
                    <h4>Finalizados</h4>
                    <h2><?= $KPI['finalizados'] ?></h2>
                </div>
            </div>

            <div class="col-sm-2">
                <div class="card-box card-rechazado">
                    <h4>Rechazados</h4>
                    <h2><?= $KPI['rechazados'] ?></h2>
                </div>
            </div>

        </div>

        <!-- =================================
      FILTROS
================================= -->
        <div class="filtros-box">
            <form class="form-inline">

                <label>Desde:</label>
                <input type="date" name="desde" class="form-control" value="<?= $f['desde'] ?>">

                <label>Hasta:</label>
                <input type="date" name="hasta" class="form-control" value="<?= $f['hasta'] ?>">

                <label>Estado:</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="PENDIENTE" <?= strtoupper($f['estado'])=='PENDIENTE'?'selected':'' ?>>Pendiente
                    </option>
                    <option value="EN_PROCESO" <?= strtoupper($f['estado'])=='EN_PROCESO'?'selected':'' ?>>En proceso
                    </option>
                    <option value="FINALIZADO" <?= strtoupper($f['estado'])=='FINALIZADO'?'selected':'' ?>>Finalizado
                    </option>
                    <option value="RECHAZADO" <?= strtoupper($f['estado'])=='RECHAZADO'?'selected':'' ?>>Rechazado
                    </option>
                </select>

                <label>Tipo:</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos</option>
                    <option value="VISA" <?= strtoupper($f['tipo'])=='VISA'?'selected':'' ?>>Visa</option>
                    <option value="RESIDENCIA" <?= strtoupper($f['tipo'])=='RESIDENCIA'?'selected':'' ?>>Residencia
                    </option>
                    <option value="PASAPORTE" <?= strtoupper($f['tipo'])=='PASAPORTE'?'selected':'' ?>>Pasaporte
                    </option>
                    <option value="OTRO" <?= strtoupper($f['tipo'])=='OTRO'?'selected':'' ?>>Otro</option>
                </select>

                <label>Cliente:</label>
                <select name="id_cliente" class="form-control">
                    <option value="">Todos</option>
                    <?php while($c=$ListaClientes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= $f['id_cliente']==$c['id']?'selected':'' ?>>
                        <?= $c['nombre'] ?>
                    </option>
                    <?php endwhile; ?>
                </select>

                <button class="btn btn-dark">Aplicar</button>
                <a href="tramites.php" class="btn btn-link">Limpiar</a>

            </form>
        </div>

        <!-- =================================
      LISTADO
================================= -->
        <table class="table table-bordered table-striped" id="tabla_tramites">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
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
$estadoLabel = [
    "PENDIENTE"=>"label-warning",
    "EN_PROCESO"=>"label-info",
    "FINALIZADO"=>"label-success",
    "RECHAZADO"=>"label-danger",
];

while($t = $ListaTramites->fetch_assoc()):
?>
                <tr>

                    <td><?= $t['id'] ?></td>
                    <td><?= $t['cliente_nombre'] ?></td>
                    <td><?= $t['tipo_tramite'] ?></td>
                    <td><?= $t['pais_destino'] ?></td>
                    <td><?= $t['fecha_inicio'] ?></td>
                    <td><?= $t['fecha_entrega'] ?: "-" ?></td>

                    <td><span class="label <?= $estadoLabel[$t['estado']] ?>"><?= $t['estado'] ?></span></td>

                    <td><?= number_format($t['monto_estimado'],2) ?></td>
                    <td><?= nl2br($t['observaciones']) ?></td>

                    <td>

                        <!-- VER -->
                        <a href="ver-tramites.php?id=<?= $t['id'] ?>" class="btn btn-xs btn-info" title="Ver trámite">
                            <i class="fa fa-eye"></i>
                        </a>

                        <!-- EDITAR -->
                        <button class="btn btn-xs btn-primary btn-editar"
                            data-json='<?= json_encode($t, JSON_HEX_APOS|JSON_HEX_QUOT) ?>' data-toggle="modal"
                            data-target="#ModalTramite">
                            <i class="fa fa-pencil"></i>
                        </button>

                        <!-- ACTIVAR / DESACTIVAR -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id_tramite" value="<?= $t['id'] ?>">

                            <?php if($t['habilitado'] == 1): ?>
                            <button class="btn btn-xs btn-warning" name="DesactivarTramite" title="Desactivar">
                                <i class="fa fa-ban"></i>
                            </button>
                            <?php else: ?>
                            <button class="btn btn-xs btn-success" name="ActivarTramite" title="Activar">
                                <i class="fa fa-check"></i>
                            </button>
                            <?php endif; ?>
                        </form>

                        <!-- CAMBIO ESTADO -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="CambiarEstadoTramite" value="1">
                            <input type="hidden" name="id_tramite" value="<?= $t['id'] ?>">

                            <select name="nuevo_estado" class="form-control input-sm" onchange="this.form.submit();">
                                <option value="PENDIENTE" <?= $t['estado']=='PENDIENTE'?'selected':'' ?>>Pendiente
                                </option>
                                <option value="EN_PROCESO" <?= $t['estado']=='EN_PROCESO'?'selected':'' ?>>En proceso
                                </option>
                                <option value="FINALIZADO" <?= $t['estado']=='FINALIZADO'?'selected':'' ?>>Finalizado
                                </option>
                                <option value="RECHAZADO" <?= $t['estado']=='RECHAZADO'?'selected':'' ?>>Rechazado
                                </option>
                            </select>
                        </form>

                    </td>

                </tr>
                <?php endwhile; ?>

            </tbody>
        </table>

    </div>

    <?php include(MODULO."footer.php"); ?>
    <?php include(MODULO."Tema.JS.php"); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_tramites').dataTable();

    /* RESET NUEVO TRAMITE */
    $('#btnNuevo').click(function() {
        $('#ModalTramite form')[0].reset();
        $('#frm_id_tramite').val("");
    });

    /* EDITAR TRAMITE */
    $('.btn-editar').click(function() {
        let t = JSON.parse($(this).attr('data-json'));

        $('#frm_id_tramite').val(t.id);
        $('#frm_id_cliente').val(t.id_cliente);
        $('#frm_tipo_tramite').val(t.tipo_tramite);
        $('#frm_pais_destino').val(t.pais_destino);
        $('#frm_fecha_inicio').val(t.fecha_inicio);
        $('#frm_fecha_entrega').val(t.fecha_entrega);
        $('#frm_fecha_vencimiento').val(t.fecha_vencimiento);
        $('#frm_estado').val(t.estado);
        $('#frm_monto_estimado').val(t.monto_estimado);
        $('#frm_observaciones').val(t.observaciones);
    });
    </script>

    <?php include("modal_tramite.php"); ?>

</body>

</html>