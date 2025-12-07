<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');


$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();
$mensaje = '';
$tipo_mensaje = 'info';

global $db;

/* =========================================================
   GUARDAR / EDITAR FACTURA
========================================================= */
if (isset($_POST['GuardarFactura'])) {
    $id_factura      = isset($_POST['id_factura']) ? (int)$_POST['id_factura'] : 0;
    $id_proveedor    = (int)$_POST['id_proveedor'];
    $nro_factura     = trim($_POST['nro_factura']);
    $fecha_emision   = $_POST['fecha_emision'];
    $fecha_venc      = $_POST['fecha_vencimiento'];
    $monto_total     = (float)$_POST['monto_total'];
    $monto_pagado    = isset($_POST['monto_pagado']) ? (float)$_POST['monto_pagado'] : 0;
    $estado          = $_POST['estado'];
    $observacion     = trim($_POST['observacion']);

    if ($id_factura > 0) {
        // UPDATE
        $db->SQL("
            UPDATE proveedor_factura SET
                id_proveedor    = {$id_proveedor},
                nro_factura     = '{$nro_factura}',
                fecha_emision   = '{$fecha_emision}',
                fecha_vencimiento = '{$fecha_venc}',
                monto_total     = {$monto_total},
                monto_pagado    = {$monto_pagado},
                estado          = '{$estado}',
                observacion     = '{$observacion}'
            WHERE id = {$id_factura}
        ");
        $mensaje = 'Factura de proveedor actualizada correctamente.';
        $tipo_mensaje = 'success';
    } else {
        // INSERT
        $db->SQL("
            INSERT INTO proveedor_factura (
                id_proveedor, nro_factura, fecha_emision, fecha_vencimiento,
                monto_total, monto_pagado, estado, observacion
            ) VALUES (
                {$id_proveedor}, '{$nro_factura}', '{$fecha_emision}', '{$fecha_venc}',
                {$monto_total}, {$monto_pagado}, '{$estado}', '{$observacion}'
            )
        ");
        $mensaje = 'Factura de proveedor registrada correctamente.';
        $tipo_mensaje = 'success';
    }

    // Actualizar saldo del proveedor
    $Proveedor->ActualizarSaldo($id_proveedor);
}

/* =========================================================
   ELIMINAR FACTURA
========================================================= */
if (isset($_POST['EliminarFactura'])) {
    $id_factura   = (int)$_POST['id_factura'];
    $id_proveedor = (int)$_POST['id_proveedor'];

    $db->SQL("DELETE FROM proveedor_factura WHERE id = {$id_factura}");

    $Proveedor->ActualizarSaldo($id_proveedor);

    $mensaje = 'Factura eliminada correctamente.';
    $tipo_mensaje = 'success';
}

/* =========================================================
   LISTADO FACTURAS
========================================================= */
$Facturas = $db->SQL("
    SELECT f.*, p.nombre AS proveedor
    FROM proveedor_factura f
    INNER JOIN proveedor p ON p.id = f.id_proveedor
    ORDER BY f.id DESC
");

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Facturas de Proveedores | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    .page-header h1 {
        margin-top: 10px;
    }

    .label-estado {
        font-size: 11px;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1><i class="fa fa-file-text-o"></i> Facturas de Proveedores</h1>

            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#ModalFactura"
                onclick="NuevaFactura()">
                <i class="fa fa-plus"></i> Nueva Factura
            </button>
            <div style="clear:both;"></div>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>

        <table class="table table-bordered table-striped" id="tabla_facturas">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proveedor</th>
                    <th>N° Factura</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th>Monto</th>
                    <th>Pagado</th>
                    <th>Pendiente</th>
                    <th>Estado</th>
                    <th width="120">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($f = $Facturas->fetch_assoc()): 
            $pendiente = $f['monto_total'] - $f['monto_pagado'];
        ?>
                <tr>
                    <td><?= $f['id'] ?></td>
                    <td><?= $f['proveedor'] ?></td>
                    <td><?= $f['nro_factura'] ?></td>
                    <td><?= $f['fecha_emision'] ?></td>
                    <td><?= $f['fecha_vencimiento'] ?></td>
                    <td><?= number_format($f['monto_total'],2) ?> Bs</td>
                    <td><?= number_format($f['monto_pagado'],2) ?> Bs</td>
                    <td><strong><?= number_format($pendiente,2) ?> Bs</strong></td>
                    <td>
                        <?php
                    $clase = 'label-default';
                    if ($f['estado'] == 'PENDIENTE') $clase = 'label-warning';
                    if ($f['estado'] == 'PAGADA')    $clase = 'label-success';
                    if ($f['estado'] == 'PARCIAL')   $clase = 'label-info';
                    ?>
                        <span class="label <?= $clase ?> label-estado">
                            <?= $f['estado'] ?>
                        </span>
                    </td>
                    <td>
                        <!-- Editar -->
                        <button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#ModalFactura"
                            onclick='EditarFactura(<?= json_encode($f, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            <i class="fa fa-pencil"></i>
                        </button>

                        <!-- Eliminar -->
                        <form method="post" style="display:inline-block;"
                            onsubmit="return confirm('¿Eliminar esta factura?');">
                            <input type="hidden" name="EliminarFactura" value="1">
                            <input type="hidden" name="id_factura" value="<?= $f['id'] ?>">
                            <input type="hidden" name="id_proveedor" value="<?= $f['id_proveedor'] ?>">
                            <button class="btn btn-xs btn-danger">
                                <i class="fa fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>

    <?php include('modal_factura.php'); ?>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_facturas').dataTable();

    function NuevaFactura() {
        document.getElementById('FormFactura').reset();
        document.getElementById('id_factura').value = '';
    }

    function EditarFactura(f) {
        document.getElementById('id_factura').value = f.id;
        document.getElementById('id_proveedor').value = f.id_proveedor;
        document.getElementById('nro_factura').value = f.nro_factura;
        document.getElementById('fecha_emision').value = f.fecha_emision;
        document.getElementById('fecha_vencimiento').value = f.fecha_vencimiento;
        document.getElementById('monto_total').value = f.monto_total;
        document.getElementById('monto_pagado').value = f.monto_pagado;
        document.getElementById('estado').value = f.estado;
        document.getElementById('observacion').value = f.observacion;
    }
    </script>

</body>

</html>