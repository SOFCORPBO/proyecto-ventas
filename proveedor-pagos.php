<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');
include('sistema/clase/proveedor.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();
$mensaje = '';
$tipo_mensaje = 'info';

global $db;

/* =========================================================
   GUARDAR / EDITAR PAGO
========================================================= */
if (isset($_POST['GuardarPago'])) {
    $id_pago      = isset($_POST['id_pago']) ? (int)$_POST['id_pago'] : 0;
    $id_proveedor = (int)$_POST['id_proveedor'];
    $id_factura   = ($_POST['id_factura'] != '') ? (int)$_POST['id_factura'] : 'NULL';
    $fecha_pago   = $_POST['fecha_pago'];
    $monto        = (float)$_POST['monto'];
    $metodo_pago  = $_POST['metodo_pago'];
    $referencia   = trim($_POST['referencia']);

    $usuario_nombre = $usuarioApp['usuario'];

    if ($id_pago > 0) {
        $db->SQL("
            UPDATE proveedor_pago SET
                id_proveedor = {$id_proveedor},
                id_factura   = {$id_factura},
                fecha_pago   = '{$fecha_pago}',
                monto        = {$monto},
                metodo_pago  = '{$metodo_pago}',
                referencia   = '{$referencia}'
            WHERE id = {$id_pago}
        ");
        $mensaje = 'Pago a proveedor actualizado correctamente.';
        $tipo_mensaje = 'success';
    } else {
        $db->SQL("
            INSERT INTO proveedor_pago (
                id_proveedor, id_factura, fecha_pago, monto,
                metodo_pago, referencia, usuario
            ) VALUES (
                {$id_proveedor}, {$id_factura}, '{$fecha_pago}', {$monto},
                '{$metodo_pago}', '{$referencia}', '{$usuario_nombre}'
            )
        ");
        $mensaje = 'Pago a proveedor registrado correctamente.';
        $tipo_mensaje = 'success';
    }

    // Si el pago está ligado a una factura, actualizamos el monto_pagado
    if ($id_factura != 'NULL') {
        $db->SQL("
            UPDATE proveedor_factura
            SET monto_pagado = monto_pagado + {$monto}
            WHERE id = {$id_factura}
        ");

        // Recalcular estado de la factura
        $Fact = $db->SQL("SELECT monto_total, monto_pagado FROM proveedor_factura WHERE id={$id_factura}")->fetch_assoc();
        $estado = 'PENDIENTE';
        if ($Fact['monto_pagado'] >= $Fact['monto_total']) $estado = 'PAGADA';
        elseif ($Fact['monto_pagado'] > 0 && $Fact['monto_pagado'] < $Fact['monto_total']) $estado = 'PARCIAL';

        $db->SQL("UPDATE proveedor_factura SET estado='{$estado}' WHERE id={$id_factura}");
    }

    // Actualizar saldo proveedor
    $Proveedor->ActualizarSaldo($id_proveedor);
}

/* =========================================================
   ELIMINAR PAGO
========================================================= */
if (isset($_POST['EliminarPago'])) {
    $id_pago      = (int)$_POST['id_pago'];
    $id_proveedor = (int)$_POST['id_proveedor'];

    // Recuperar pago para revertir si estaba ligado a una factura
    $Pago = $db->SQL("SELECT * FROM proveedor_pago WHERE id={$id_pago}")->fetch_assoc();
    if ($Pago && $Pago['id_factura']) {
        $db->SQL("
            UPDATE proveedor_factura
            SET monto_pagado = monto_pagado - {$Pago['monto']}
            WHERE id = {$Pago['id_factura']}
        ");
    }

    $db->SQL("DELETE FROM proveedor_pago WHERE id={$id_pago}");

    if ($Pago && $Pago['id_factura']) {
        $Fact = $db->SQL("SELECT monto_total, monto_pagado FROM proveedor_factura WHERE id={$Pago['id_factura']}")->fetch_assoc();
        $estado = 'PENDIENTE';
        if ($Fact['monto_pagado'] >= $Fact['monto_total']) $estado = 'PAGADA';
        elseif ($Fact['monto_pagado'] > 0 && $Fact['monto_pagado'] < $Fact['monto_total']) $estado = 'PARCIAL';

        $db->SQL("UPDATE proveedor_factura SET estado='{$estado}' WHERE id={$Pago['id_factura']}");
    }

    $Proveedor->ActualizarSaldo($id_proveedor);

    $mensaje = 'Pago eliminado correctamente.';
    $tipo_mensaje = 'success';
}

/* =========================================================
   LISTADO PAGOS
========================================================= */
$Pagos = $db->SQL("
    SELECT pg.*, p.nombre AS proveedor, f.nro_factura
    FROM proveedor_pago pg
    INNER JOIN proveedor p ON p.id=pg.id_proveedor
    LEFT JOIN proveedor_factura f ON f.id=pg.id_factura
    ORDER BY pg.id DESC
");

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Pagos a Proveedores | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1><i class="fa fa-money"></i> Pagos a Proveedores</h1>

            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#ModalPago"
                onclick="NuevoPago()">
                <i class="fa fa-plus"></i> Nuevo Pago
            </button>
            <div style="clear:both;"></div>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>

        <table class="table table-bordered table-striped" id="tabla_pagos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proveedor</th>
                    <th>Factura</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Método</th>
                    <th>Referencia</th>
                    <th>Usuario</th>
                    <th width="120">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $Pagos->fetch_assoc()): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= $p['proveedor'] ?></td>
                    <td><?= $p['nro_factura'] ?></td>
                    <td><?= $p['fecha_pago'] ?></td>
                    <td><?= number_format($p['monto'],2) ?> Bs</td>
                    <td><?= $p['metodo_pago'] ?></td>
                    <td><?= $p['referencia'] ?></td>
                    <td><?= $p['usuario'] ?></td>
                    <td>
                        <!-- Editar -->
                        <button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#ModalPago"
                            onclick='EditarPago(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            <i class="fa fa-pencil"></i>
                        </button>

                        <!-- Eliminar -->
                        <form method="post" style="display:inline-block;"
                            onsubmit="return confirm('¿Eliminar este pago?');">
                            <input type="hidden" name="EliminarPago" value="1">
                            <input type="hidden" name="id_pago" value="<?= $p['id'] ?>">
                            <input type="hidden" name="id_proveedor" value="<?= $p['id_proveedor'] ?>">
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

    <?php include('modal_pago.php'); ?>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_pagos').dataTable();

    function NuevoPago() {
        document.getElementById('FormPago').reset();
        document.getElementById('id_pago').value = '';
    }

    function EditarPago(p) {
        document.getElementById('id_pago').value = p.id;
        document.getElementById('id_proveedor_pago').value = p.id_proveedor;
        document.getElementById('id_factura_pago').value = p.id_factura;
        document.getElementById('fecha_pago').value = p.fecha_pago;
        document.getElementById('monto').value = p.monto;
        document.getElementById('metodo_pago').value = p.metodo_pago;
        document.getElementById('referencia').value = p.referencia;
    }
    </script>

</body>

</html>