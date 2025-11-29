<?php
session_start();
define('acceso', true);
include('sistema/configuracion.php');
include('sistema/clases/tramites.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Tram = new Tramites();

/* ===== ACCIONES ===== */
$mensaje = '';
$tipo_mensaje = 'info';

# Guardar
if (isset($_POST['GuardarTramite'])) {
    $r = $Tram->Guardar($_POST);
    $mensaje = $r['msg'];
    $tipo_mensaje = $r['ok'] ? 'success' : 'danger';
}

# Cambio rápido de estado
if (isset($_POST['CambiarEstadoTramite'])) {
    $Tram->CambiarEstado($_POST['id_tramite'], $_POST['nuevo_estado']);
    $mensaje = 'Estado actualizado correctamente.';
    $tipo_mensaje = 'success';
}

/* ===== FILTROS ===== */
$f = [
    'desde'      => $_GET['desde'] ?? '',
    'hasta'      => $_GET['hasta'] ?? '',
    'estado'     => $_GET['estado'] ?? '',
    'tipo'       => $_GET['tipo'] ?? '',
    'id_cliente' => $_GET['id_cliente'] ?? ''
];

$ListaTramites = $Tram->Listar($f);
$KPI = $Tram->KPIs();
$Clientes = $Tram->Clientes();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Trámites | <?= TITULO ?></title>
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==2) include(MODULO.'menu_vendedor.php');
else include(MODULO.'menu_admin.php');
?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1>Gestión de Trámites</h1>
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#ModalTramite">
                <i class="glyphicon glyphicon-plus"></i> Nuevo Trámite
            </button>
            <div style="clear:both"></div>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="row">
            <div class="col-sm-2">
                <div class="panel panel-default text-center">
                    <div class="panel-heading">Total</div>
                    <div class="panel-body">
                        <h3><?= $KPI['total'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="panel panel-warning text-center">
                    <div class="panel-heading">Pendientes</div>
                    <div class="panel-body">
                        <h3><?= $KPI['pendientes'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="panel panel-info text-center">
                    <div class="panel-heading">En proceso</div>
                    <div class="panel-body">
                        <h3><?= $KPI['en_proceso'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="panel panel-success text-center">
                    <div class="panel-heading">Finalizados</div>
                    <div class="panel-body">
                        <h3><?= $KPI['finalizados'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="panel panel-danger text-center">
                    <div class="panel-heading">Rechazados</div>
                    <div class="panel-body">
                        <h3><?= $KPI['rechazados'] ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Filtros</strong></div>
            <div class="panel-body">
                <form class="form-inline">

                    <label>Desde:</label>
                    <input type="date" name="desde" class="form-control" value="<?= $f['desde'] ?>">

                    <label>Hasta:</label>
                    <input type="date" name="hasta" class="form-control" value="<?= $f['hasta'] ?>">

                    <label>Estado:</label>
                    <select name="estado" class="form-control">
                        <option value="">Todos</option>
                        <option value="PENDIENTE" <?= $f['estado']=='PENDIENTE'?'selected':'' ?>>Pendiente</option>
                        <option value="EN_PROCESO" <?= $f['estado']=='EN_PROCESO'?'selected':'' ?>>En proceso</option>
                        <option value="FINALIZADO" <?= $f['estado']=='FINALIZADO'?'selected':'' ?>>Finalizado</option>
                        <option value="RECHAZADO" <?= $f['estado']=='RECHAZADO'?'selected':'' ?>>Rechazado</option>
                    </select>

                    <label>Tipo:</label>
                    <select name="tipo" class="form-control">
                        <option value="">Todos</option>
                        <option value="VISA" <?= $f['tipo']=='VISA'?'selected':'' ?>>Visa</option>
                        <option value="RESIDENCIA" <?= $f['tipo']=='RESIDENCIA'?'selected':'' ?>>Residencia</option>
                        <option value="PASAPORTE" <?= $f['tipo']=='PASAPORTE'?'selected':'' ?>>Pasaporte</option>
                        <option value="OTRO" <?= $f['tipo']=='OTRO'?'selected':'' ?>>Otro</option>
                    </select>

                    <label>Cliente:</label>
                    <select name="id_cliente" class="form-control">
                        <option value="">Todos</option>
                        <?php while($c=$Clientes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= $f['id_cliente']==$c['id']?'selected':'' ?>>
                            <?= $c['nombre'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <button class="btn btn-default">Aplicar</button>
                    <a href="tramites.php" class="btn btn-link">Limpiar</a>

                </form>
            </div>
        </div>

        <!-- TABLA -->
        <table id="tabla_tramites" class="table table-bordered table-striped">
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
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($t=$ListaTramites->fetch_assoc()): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td><?= $t['cliente_nombre'] ?></td>
                    <td><?= $t['tipo_tramite'] ?></td>
                    <td><?= $t['pais_destino'] ?></td>
                    <td><?= $t['fecha_inicio'] ?></td>
                    <td><?= $t['fecha_entrega'] ?: '-' ?></td>
                    <td><span class="label label-info"><?= $t['estado'] ?></span></td>
                    <td><?= number_format($t['monto_estimado'],2) ?></td>
                    <td><?= nl2br($t['observaciones']) ?></td>
                    <td>
                        <!-- Editar -->
                        <button class="btn btn-xs btn-primary btn-editar" data-all='<?= json_encode($t) ?>'
                            data-toggle="modal" data-target="#ModalTramite">Editar</button>

                        <!-- Cambio rápido -->
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

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_tramites').dataTable();

    /* ============================
       MODAL NUEVO / EDITAR
    ============================ */
    $('.btn-editar').click(function() {
        let t = $(this).data('all');

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

</body>

</html>