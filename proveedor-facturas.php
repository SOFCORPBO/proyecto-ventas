<?php
session_start();
include("sistema/configuracion.php");
include("sistema/clase/proveedor.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();

/* =========================================================
   GUARDAR FACTURA (CREAR / EDITAR)
   - Si viene id_factura vacío  => CREA
   - Si viene id_factura > 0    => EDITA
========================================================= */
if (isset($_POST['GuardarFacturaProveedor'])) {

    $id_factura       = isset($_POST['id_factura']) ? intval($_POST['id_factura']) : 0;
    $id_proveedor     = intval($_POST['id_proveedor']);
    $numero_factura   = strtoupper(trim($_POST['numero_factura']));
    $fecha_emision    = $_POST['fecha_emision'];
    $fecha_vencimiento= $_POST['fecha_vencimiento'];
    $monto_total      = floatval($_POST['monto_total']);
    $observaciones    = trim($_POST['observaciones']);

    if ($id_factura > 0) {
        // --------- EDITAR ---------
        $db->SQL("
            UPDATE proveedor_factura SET
                id_proveedor     = {$id_proveedor},
                numero_factura   = '{$numero_factura}',
                fecha_emision    = '{$fecha_emision}',
                fecha_vencimiento= '{$fecha_vencimiento}',
                monto_total      = {$monto_total},
                observaciones    = '{$observaciones}'
            WHERE id = {$id_factura}
        ");

        // Recalcular estado con pagos existentes
        $Factura = $db->SQL("
            SELECT id_proveedor, monto_total, monto_pagado
            FROM proveedor_factura
            WHERE id = {$id_factura}
        ")->fetch_assoc();

        $estado = "PENDIENTE";
        if ($Factura['monto_pagado'] >= $Factura['monto_total']) {
            $estado = "PAGADA";
        } elseif ($Factura['monto_pagado'] > 0) {
            $estado = "PARCIAL";
        }

        $db->SQL("UPDATE proveedor_factura SET estado = '{$estado}' WHERE id = {$id_factura}");

        // Actualizar saldo del proveedor
        $Proveedor->ActualizarSaldo($Factura['id_proveedor']);

        echo '<div class="alert alert-success"><i class="fa fa-check"></i> Factura actualizada correctamente.</div>';
        echo '<meta http-equiv="refresh" content="1;url=proveedor-facturas.php">';

    } else {
        // --------- CREAR ---------
        $db->SQL("
            INSERT INTO proveedor_factura (
                id_proveedor, numero_factura, fecha_emision, fecha_vencimiento,
                monto_total, monto_pagado, estado, observaciones
            ) VALUES (
                {$id_proveedor},
                '{$numero_factura}',
                '{$fecha_emision}',
                '{$fecha_vencimiento}',
                {$monto_total},
                0.00,
                'PENDIENTE',
                '{$observaciones}'
            )
        ");

        // Historial movimiento
        $db->SQL("
            INSERT INTO proveedor_movimiento (
                id_proveedor, tipo, descripcion, monto, fecha
            ) VALUES (
                {$id_proveedor},
                'FACTURA',
                'Factura registrada N° {$numero_factura}',
                {$monto_total},
                NOW()
            )
        ");

        // Actualizar saldo proveedor
        $Proveedor->ActualizarSaldo($id_proveedor);

        echo '<div class="alert alert-success"><i class="fa fa-check"></i> Factura registrada correctamente.</div>';
        echo '<meta http-equiv="refresh" content="1;url=proveedor-facturas.php">';
    }
}

/* =========================================================
   ELIMINAR FACTURA
========================================================= */
if (isset($_POST['EliminarFacturaProveedor'])) {

    $id_factura = intval($_POST['id_factura']);

    $factura = $db->SQL("
        SELECT id_proveedor 
        FROM proveedor_factura 
        WHERE id = {$id_factura}
    ")->fetch_assoc();

    if ($factura) {
        $db->SQL("DELETE FROM proveedor_factura WHERE id = {$id_factura}");
        $Proveedor->ActualizarSaldo($factura['id_proveedor']);
    }

    echo '<div class="alert alert-success"><i class="fa fa-trash"></i> Factura eliminada correctamente.</div>';
    echo '<meta http-equiv="refresh" content="1;url=proveedor-facturas.php">';
}

/* =========================================================
   LISTADOS
========================================================= */
$FacturasSQL = $db->SQL("
    SELECT pf.*, p.nombre AS proveedor_nombre
    FROM proveedor_factura pf
    INNER JOIN proveedor p ON p.id = pf.id_proveedor
    ORDER BY pf.id DESC
");

$ProveedoresSQL = $Proveedor->SelectorProveedores();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Facturas de Proveedores | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
    .badge-pendiente {
        background: #f44336;
    }

    .badge-parcial {
        background: #ff9800;
    }

    .badge-pagada {
        background: #4caf50;
    }
    </style>
</head>

<body>

    <?php include(MODULO.'menu_admin.php'); ?>

    <div class="container" id="wrap">

        <div class="page-header">
            <h1>Facturas Recibidas</h1>
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#ModalFacturaProveedor"
                onclick="NuevaFactura()">
                <i class="fa fa-plus"></i> Nueva Factura
            </button>
            <div style="clear:both;"></div>
        </div>

        <table class="table table-bordered table-striped" id="tabla_facturas">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proveedor</th>
                    <th>N° Factura</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th>Total</th>
                    <th>Pagado</th>
                    <th>Estado</th>
                    <th width="150">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($f = $FacturasSQL->fetch_assoc()): ?>
                <tr>
                    <td><?= $f['id'] ?></td>
                    <td><?= $f['proveedor_nombre'] ?></td>
                    <td><?= $f['numero_factura'] ?></td>
                    <td><?= $f['fecha_emision'] ?></td>
                    <td><?= $f['fecha_vencimiento'] ?></td>
                    <td><strong><?= number_format($f['monto_total'], 2) ?> Bs</strong></td>
                    <td><?= number_format($f['monto_pagado'], 2) ?> Bs</td>
                    <td>
                        <?php if($f['estado']=='PENDIENTE'): ?>
                        <span class="badge badge-pendiente">Pendiente</span>
                        <?php elseif($f['estado']=='PARCIAL'): ?>
                        <span class="badge badge-parcial">Parcial</span>
                        <?php else: ?>
                        <span class="badge badge-pagada">Pagada</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Editar -->
                        <button class="btn btn-warning btn-xs"
                            onclick='EditarFactura(<?= json_encode($f, JSON_HEX_QUOT | JSON_HEX_APOS) ?>)'
                            data-toggle="modal" data-target="#ModalFacturaProveedor">
                            <i class="fa fa-pencil"></i>
                        </button>

                        <!-- Eliminar -->
                        <form method="post" style="display:inline-block;"
                            onsubmit="return confirm('¿Eliminar factura?');">
                            <input type="hidden" name="EliminarFacturaProveedor" value="1">
                            <input type="hidden" name="id_factura" value="<?= $f['id'] ?>">
                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>

    <?php include("modal_factura.php"); ?>
    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $('#tabla_facturas').dataTable();

    function NuevaFactura() {
        document.getElementById("FormFacturaProveedor").reset();
        document.getElementById("id_factura").value = "";
    }

    function EditarFactura(f) {
        document.getElementById("id_factura").value = f.id;
        document.getElementById("id_proveedor_factura").value = f.id_proveedor;
        document.getElementById("numero_factura").value = f.numero_factura;
        document.getElementById("fecha_emision").value = f.fecha_emision;
        document.getElementById("fecha_vencimiento").value = f.fecha_vencimiento;
        document.getElementById("monto_total").value = f.monto_total;
        document.getElementById("observaciones_factura").value = f.observaciones;
    }
    </script>

</body>

</html>