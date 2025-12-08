<?php
session_start();
include("sistema/configuracion.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();
$Cajas     = new Cajas();

/* ============================================================
   📌 IDENTIFICAR RESPONSABLE
============================================================ */
$responsable = isset($usuarioApp['id_vendedor'])
    ? intval($usuarioApp['id_vendedor'])
    : intval($usuarioApp['id']);


/* ============================================================
   📌 REGISTRAR PAGO A PROVEEDOR
============================================================ */
if (isset($_POST['GuardarPagoProveedor'])) {

    $id_proveedor = intval($_POST['id_proveedor']);
    $id_factura   = !empty($_POST['id_factura']) ? intval($_POST['id_factura']) : null;

    $fecha_pago   = $_POST['fecha_pago'];
    $monto        = floatval($_POST['monto']);
    $metodo_pago  = $_POST['metodo_pago'];

    $id_banco     = !empty($_POST['id_banco']) ? intval($_POST['id_banco']) : null;
    $referencia   = trim($_POST['referencia']);
    $obs          = $db->real_escape_string($_POST['observaciones']);

    /* ------------------ CONTROL DE VALIDACIÓN ------------------ */
    if ($monto <= 0) {
        echo '<div class="alert alert-danger">Monto inválido.</div>';
        exit;
    }

    /* ============================================================
       1️⃣ INSERTAR PAGO EN proveedor_pago
    ============================================================ */
    $db->SQL("
        INSERT INTO proveedor_pago 
        (id_proveedor, id_factura, fecha_pago, monto, metodo_pago, id_banco, referencia, observaciones, responsable)
        VALUES
        (
            {$id_proveedor},
            ".($id_factura ?: "NULL").",
            '{$fecha_pago}',
            {$monto},
            '{$metodo_pago}',
            ".($id_banco ?: "NULL").",
            ".($referencia ? "'".addslashes($referencia)."'" : "NULL").",
            '{$obs}',
            {$responsable}
        )
    ");

    /* ============================================================
       2️⃣ SI EXISTE FACTURA, ACTUALIZAR monto_pagado + estado
    ============================================================ */
    if ($id_factura) {

        $db->SQL("
            UPDATE proveedor_factura
            SET monto_pagado = monto_pagado + {$monto}
            WHERE id = {$id_factura}
        ");

        // RE-EVALUAR ESTADO
        $F = $db->SQL("SELECT monto_total, monto_pagado FROM proveedor_factura WHERE id={$id_factura}")
                ->fetch_assoc();

        $estado = "PENDIENTE";
        if ($F['monto_pagado'] >= $F['monto_total']) $estado = "PAGADA";
        elseif ($F['monto_pagado'] > 0) $estado = "PARCIAL";

        $db->SQL("UPDATE proveedor_factura SET estado='{$estado}' WHERE id={$id_factura}");
    }

    /* ============================================================
       3️⃣ INSERTAR MOVIMIENTO HISTÓRICO proveedor_movimiento
    ============================================================ */
    $db->SQL("
        INSERT INTO proveedor_movimiento
        (id_proveedor, tipo, descripcion, monto, fecha)
        VALUES
        (
            {$id_proveedor},
            'PAGO',
            'Pago registrado por Bs {$monto}',
            -{$monto},
            NOW()
        )
    ");

    /* ============================================================
       4️⃣ ACTUALIZAR SALDO DEL PROVEEDOR
    ============================================================ */
    $Proveedor->ActualizarSaldo($id_proveedor);

    /* ============================================================
       5️⃣ REGISTRAR EGRESO EN CAJA GENERAL
    ============================================================ */
    $concepto = "Pago a proveedor #{$id_proveedor}";
    $Cajas->CajaGeneralMovimiento(
        "EGRESO",
        $monto,
        $concepto,
        $metodo_pago,
        $id_banco,
        $referencia,
        $responsable
    );

    echo '<div class="alert alert-success">
            <i class="fa fa-check"></i> Pago registrado correctamente.
          </div>
          <meta http-equiv="refresh" content="1;url=proveedor-pagos.php">';
    exit;
}


/* ============================================================
   📌 CONSULTAS PARA LISTADOS
============================================================ */
$ProveedoresSQL = $Proveedor->SelectorProveedores();

$FacturasPendientesSQL = $db->SQL("
    SELECT 
        pf.*,
        p.nombre AS proveedor_nombre
    FROM proveedor_factura pf
    INNER JOIN proveedor p ON p.id = pf.id_proveedor
    WHERE pf.estado IN ('PENDIENTE','PARCIAL')
    ORDER BY pf.fecha_emision DESC
");

$PagosSQL = $db->SQL("
    SELECT 
        pg.*,
        p.nombre AS proveedor_nombre,
        pf.numero_factura
    FROM proveedor_pago pg
    LEFT JOIN proveedor p ON p.id = pg.id_proveedor
    LEFT JOIN proveedor_factura pf ON pf.id = pg.id_factura
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
    <?php include(MODULO."Tema.CSS.php"); ?>
</head>

<body>

<?php include(MODULO.'menu_admin.php'); ?>

<div class="container" id="wrap">

    <div class="page-header">
        <h1>Pagos a Proveedores</h1>
        <button class="btn btn-success pull-right" data-toggle="modal" data-target="#ModalPagoProveedor">
            <i class="fa fa-plus"></i> Registrar Pago
        </button>
        <div style="clear: both;"></div>
    </div>

    <!-- LISTADO DE PAGOS -->
    <table class="table table-bordered table-striped" id="tabla_pagos">
        <thead>
            <tr>
                <th>ID</th>
                <th>Proveedor</th>
                <th>Factura</th>
                <th>Fecha</th>
                <th>Método</th>
                <th>Monto</th>
                <th>Referencia</th>
            </tr>
        </thead>

        <tbody>
            <?php while($p = $PagosSQL->fetch_assoc()): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= $p['proveedor_nombre'] ?></td>
                <td><?= $p['numero_factura'] ?: '-' ?></td>
                <td><?= $p['fecha_pago'] ?></td>
                <td><?= $p['metodo_pago'] ?></td>
                <td><strong><?= number_format($p['monto'],2) ?> Bs</strong></td>
                <td><?= $p['referencia'] ?: '-' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

<?php include("modal_pago.php"); ?>
<?php include(MODULO.'footer.php'); ?>
<?php include(MODULO.'Tema.JS.php'); ?>

<script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
<script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>
<script>
    $('#tabla_pagos').dataTable();
</script>

</body>
</html>
