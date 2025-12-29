<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
$usuario->ZonaAdministrador();

date_default_timezone_set(HORARIO);

// Fecha y hora actuales (siguiendo tu esquema VARCHAR)
$fecha = date('Y-m-d');
$hora  = date('H:i:s');

function esc($v) { return addslashes(trim((string)$v)); }

function nombreResponsable($db, $id) {
    if (!$id) return 'No asignado';
    $id = (int)$id;
    $sql = $db->SQL("SELECT nombre, apellido1, apellido2 FROM vendedores WHERE id={$id} LIMIT 1");
    if (!$sql || $sql->num_rows == 0) return 'No asignado';
    $v = $sql->fetch_assoc();
    return trim($v['nombre'].' '.$v['apellido1'].' '.$v['apellido2']);
}

function nombreBanco($db, $id_banco) {
    if (!$id_banco) return '-';
    $id_banco = (int)$id_banco;
    $sql = $db->SQL("SELECT nombre, numero_cuenta FROM bancos WHERE id={$id_banco} LIMIT 1");
    if (!$sql || $sql->num_rows == 0) return '-';
    $b = $sql->fetch_assoc();
    return $b['nombre'] . (!empty($b['numero_cuenta']) ? " ({$b['numero_cuenta']})" : "");
}

function saldoBancoActual($db, $id_banco) {
    $id_banco = (int)$id_banco;
    $sql = $db->SQL("
        SELECT
            b.saldo_inicial + COALESCE(SUM(CASE WHEN bm.tipo='INGRESO' THEN bm.monto ELSE -bm.monto END),0) AS saldo
        FROM bancos b
        LEFT JOIN banco_movimientos bm ON bm.id_banco=b.id
        WHERE b.id={$id_banco}
        GROUP BY b.id
    ");
    if ($sql && $sql->num_rows > 0) return (float)$sql->fetch_assoc()['saldo'];
    return null;
}

/* ==================== SALDOS POR SESIÓN (id_caja / id_cajachica) ==================== */
function saldoCajaGeneralSesion($db, $id_caja) {
    $id_caja = (int)$id_caja;
    $sql = $db->SQL("
        SELECT COALESCE(SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END),0) AS saldo
        FROM caja_general_movimientos
        WHERE id_caja={$id_caja}
    ");
    $row = $sql ? $sql->fetch_assoc() : null;
    return ($row && $row['saldo'] !== null) ? (float)$row['saldo'] : 0.0;
}

function saldoCajaChicaSesion($db, $id_cajachica) {
    $id_cajachica = (int)$id_cajachica;
    $sql = $db->SQL("
        SELECT COALESCE(SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END),0) AS saldo
        FROM caja_chica_movimientos
        WHERE id_cajachica={$id_cajachica}
    ");
    $row = $sql ? $sql->fetch_assoc() : null;
    return ($row && $row['saldo'] !== null) ? (float)$row['saldo'] : 0.0;
}

function ultimoSaldoCajaGeneralSesion($db, $id_caja) {
    $id_caja = (int)$id_caja;
    $sql = $db->SQL("
        SELECT saldo_caja
        FROM caja_general_movimientos
        WHERE id_caja={$id_caja}
        ORDER BY id DESC
        LIMIT 1
    ");
    return ($sql && $sql->num_rows) ? (float)$sql->fetch_assoc()['saldo_caja'] : 0.0;
}

function ultimoSaldoCajaChicaSesion($db, $id_cajachica) {
    $id_cajachica = (int)$id_cajachica;
    $sql = $db->SQL("
        SELECT saldo_resultante
        FROM caja_chica_movimientos
        WHERE id_cajachica={$id_cajachica}
        ORDER BY id DESC
        LIMIT 1
    ");
    return ($sql && $sql->num_rows) ? (float)$sql->fetch_assoc()['saldo_resultante'] : 0.0;
}

/* ==================== ACCIONES ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['AccionCaja'])) {

    $accion = $_POST['AccionCaja'];

    // Responsable elegido por admin (tabla vendedores)
    $responsable = isset($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : 0;
    if ($responsable <= 0) {
        header("Location: cajas.php");
        exit;
    }

    /* ---------- APERTURAR CAJA GENERAL (por responsable) ---------- */
    if ($accion === 'AperturarGeneral') {

        $monto = (float)($_POST['monto_inicial'] ?? 0);
        if ($monto < 0) $monto = 0;

        $origen = (($_POST['origen_general'] ?? 'EFECTIVO') === 'BANCO') ? 'BANCO' : 'EFECTIVO';
        $id_banco = ($origen === 'BANCO') ? (int)($_POST['id_banco_general'] ?? 0) : 0;
        if ($origen === 'BANCO' && $id_banco <= 0) $origen = 'EFECTIVO';

        // Una caja general abierta por responsable
        $abierta = $db->SQL("
            SELECT id FROM caja
            WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1 AND responsable={$responsable}
            LIMIT 1
        ");

        if ($abierta && $abierta->num_rows == 0) {

            // Crear sesión en caja
            $db->SQL("
                INSERT INTO caja
                    (monto, fecha, hora, estado, habilitado, tipo_caja, responsable, observacion, id_banco, metodo_apertura)
                VALUES
                    ('{$monto}', '{$fecha}', '{$hora}', 1, 1, 'GENERAL', {$responsable}, 'Apertura de caja general',
                     ".($id_banco>0 ? $id_banco : "NULL").", '{$origen}')
            ");

            // Obtener id de la sesión creada (sin insert_id)
            $idCajaSQL = $db->SQL("
                SELECT id FROM caja
                WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1 AND responsable={$responsable}
                ORDER BY id DESC LIMIT 1
            ");
            $id_caja = ($idCajaSQL && $idCajaSQL->num_rows) ? (int)$idCajaSQL->fetch_assoc()['id'] : 0;

            // Si origen BANCO: registrar EGRESO en banco_movimientos (retiro)
            $saldoBanco = null;
            $metodo_pago = 'EFECTIVO';
            if ($origen === 'BANCO' && $id_banco > 0) {
                $metodo_pago = 'TRANSFERENCIA';

                $db->SQL("
                    INSERT INTO banco_movimientos (id_banco, fecha, tipo, monto, concepto, id_venta)
                    VALUES ({$id_banco}, NOW(), 'EGRESO', {$monto}, 'Apertura caja general (retiro)', NULL)
                ");

                $saldoBanco = saldoBancoActual($db, $id_banco);
            }

            // Movimiento inicial (saldo por sesión)
            if ($id_caja > 0) {
                $saldoNuevo = $monto;

                $db->SQL("
                    INSERT INTO caja_general_movimientos
                        (id_caja, fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia, responsable, saldo_caja, saldo_banco)
                    VALUES
                        ({$id_caja}, '{$fecha}', '{$hora}', 'INGRESO', {$monto}, 'Apertura de caja general',
                         '{$metodo_pago}', ".($id_banco>0?$id_banco:"NULL").", 'APERTURA', {$responsable}, {$saldoNuevo},
                         ".($saldoBanco!==null?$saldoBanco:"NULL").")
                ");
            }
        }

    /* ---------- CERRAR CAJA GENERAL (por sesión) ---------- */
    } elseif ($accion === 'CerrarGeneral') {

        $id_caja = (int)($_POST['id_caja'] ?? 0);
        if ($id_caja <= 0) {
            header("Location: cajas.php");
            exit;
        }

        $saldoActual = saldoCajaGeneralSesion($db, $id_caja);

        $montoCierre = (float)($_POST['monto_cierre'] ?? $saldoActual);
        if ($montoCierre < 0) $montoCierre = 0;

        $destino = (($_POST['destino_general'] ?? 'EFECTIVO') === 'BANCO') ? 'BANCO' : 'EFECTIVO';
        $id_banco_dest = ($destino === 'BANCO') ? (int)($_POST['id_banco_cierre_general'] ?? 0) : 0;
        if ($destino === 'BANCO' && $id_banco_dest <= 0) $destino = 'EFECTIVO';

        // Cerrar sesión
        $db->SQL("UPDATE caja SET estado=0, observacion='Cierre de caja general' WHERE id={$id_caja}");

        // Si destino BANCO: registrar INGRESO en banco_movimientos (depósito)
        $saldoBanco = null;
        $metodo_pago = 'EFECTIVO';
        if ($destino === 'BANCO' && $id_banco_dest > 0) {
            $metodo_pago = 'DEPOSITO';

            $db->SQL("
                INSERT INTO banco_movimientos (id_banco, fecha, tipo, monto, concepto, id_venta)
                VALUES ({$id_banco_dest}, NOW(), 'INGRESO', {$montoCierre}, 'Cierre caja general (depósito)', NULL)
            ");

            $saldoBanco = saldoBancoActual($db, $id_banco_dest);
        }

        // Movimiento cierre: saldo por sesión en 0
        $db->SQL("
            INSERT INTO caja_general_movimientos
                (id_caja, fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia, responsable, saldo_caja, saldo_banco)
            VALUES
                ({$id_caja}, '{$fecha}', '{$hora}', 'EGRESO', {$montoCierre}, 'Cierre de caja general',
                 '{$metodo_pago}', ".($id_banco_dest>0?$id_banco_dest:"NULL").", 'CIERRE', {$responsable}, 0,
                 ".($saldoBanco!==null?$saldoBanco:"NULL").")
        ");

    /* ---------- APERTURAR CAJA CHICA (por responsable) ---------- */
    } elseif ($accion === 'AperturarChica') {

        $monto = (float)($_POST['monto_inicial_chica'] ?? 0);
        if ($monto < 0) $monto = 0;

        $origen = (($_POST['origen_chica'] ?? 'EFECTIVO') === 'BANCO') ? 'BANCO' : 'EFECTIVO';
        $id_banco = ($origen === 'BANCO') ? (int)($_POST['id_banco_chica'] ?? 0) : 0;
        if ($origen === 'BANCO' && $id_banco <= 0) $origen = 'EFECTIVO';

        // Una caja chica abierta por responsable
        $abierta = $db->SQL("
            SELECT id FROM cajachica
            WHERE tipo=1 AND habilitado=1 AND responsable={$responsable}
            LIMIT 1
        ");

        if ($abierta && $abierta->num_rows == 0) {

            $db->SQL("
                INSERT INTO cajachica
                    (monto, fecha, hora, tipo, responsable, observacion, habilitado, id_banco, metodo_apertura)
                VALUES
                    ('{$monto}', '{$fecha}', '{$hora}', 1, {$responsable}, 'Apertura de caja chica', 1,
                     ".($id_banco>0 ? $id_banco : "NULL").", '{$origen}')
            ");

            $idCajaSQL = $db->SQL("
                SELECT id FROM cajachica
                WHERE tipo=1 AND habilitado=1 AND responsable={$responsable}
                ORDER BY id DESC LIMIT 1
            ");
            $id_cajachica = ($idCajaSQL && $idCajaSQL->num_rows) ? (int)$idCajaSQL->fetch_assoc()['id'] : 0;

            // Si origen BANCO: registrar EGRESO en banco_movimientos (retiro)
            if ($origen === 'BANCO' && $id_banco > 0) {
                $db->SQL("
                    INSERT INTO banco_movimientos (id_banco, fecha, tipo, monto, concepto, id_venta)
                    VALUES ({$id_banco}, NOW(), 'EGRESO', {$monto}, 'Apertura caja chica (retiro)', NULL)
                ");
            }

            // Movimiento inicial (saldo por sesión)
            if ($id_cajachica > 0) {
                $db->SQL("
                    INSERT INTO caja_chica_movimientos
                        (id_cajachica, fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
                    VALUES
                        ({$id_cajachica}, '{$fecha}', '{$hora}', 'INGRESO', {$monto}, 'Apertura de caja chica',
                         {$responsable}, {$monto}, 'APERTURA')
                ");
            }
        }

    /* ---------- CERRAR CAJA CHICA (por sesión) ---------- */
    } elseif ($accion === 'CerrarChica') {

        $id_cajachica = (int)($_POST['id_cajachica'] ?? 0);
        if ($id_cajachica <= 0) {
            header("Location: cajas.php");
            exit;
        }

        $saldoActual = saldoCajaChicaSesion($db, $id_cajachica);

        $montoCierre = (float)($_POST['monto_cierre_chica'] ?? $saldoActual);
        if ($montoCierre < 0) $montoCierre = 0;

        $destino = (($_POST['destino_chica'] ?? 'EFECTIVO') === 'BANCO') ? 'BANCO' : 'EFECTIVO';
        $id_banco_dest = ($destino === 'BANCO') ? (int)($_POST['id_banco_cierre_chica'] ?? 0) : 0;
        if ($destino === 'BANCO' && $id_banco_dest <= 0) $destino = 'EFECTIVO';

        // Cerrar sesión
        $db->SQL("UPDATE cajachica SET habilitado=0, observacion='Cierre de caja chica' WHERE id={$id_cajachica}");

        // Si destino BANCO: registrar INGRESO en banco_movimientos (depósito)
        if ($destino === 'BANCO' && $id_banco_dest > 0) {
            $db->SQL("
                INSERT INTO banco_movimientos (id_banco, fecha, tipo, monto, concepto, id_venta)
                VALUES ({$id_banco_dest}, NOW(), 'INGRESO', {$montoCierre}, 'Cierre caja chica (depósito)', NULL)
            ");
        }

        // Movimiento cierre: saldo por sesión en 0
        $db->SQL("
            INSERT INTO caja_chica_movimientos
                (id_cajachica, fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
            VALUES
                ({$id_cajachica}, '{$fecha}', '{$hora}', 'EGRESO', {$montoCierre}, 'Cierre de caja chica',
                 {$responsable}, 0, 'CIERRE')
        ");
    }

    header("Location: cajas.php");
    exit;
}

/* ==================== CONSULTAS PARA VISUALIZAR ==================== */
$VendedoresSQL = $db->SQL("SELECT id, nombre, apellido1, apellido2 FROM vendedores ORDER BY nombre ASC");
$BancosSQL     = $db->SQL("SELECT id, nombre, numero_cuenta FROM bancos ORDER BY nombre ASC");

$CajasGeneralesAbiertas = $db->SQL("
    SELECT * FROM caja
    WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1
    ORDER BY id DESC
");

$CajasChicasAbiertas = $db->SQL("
    SELECT * FROM cajachica
    WHERE tipo=1 AND habilitado=1
    ORDER BY id DESC
");

/* Movimientos generales: por defecto muestra los últimos 80 */
$MovGeneralSQL = $db->SQL("
    SELECT m.*, b.nombre AS banco_nombre
    FROM caja_general_movimientos m
    LEFT JOIN bancos b ON b.id=m.id_banco
    ORDER BY m.id DESC
    LIMIT 80
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Cajas del Sistema | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO; ?>img/favicon.ico">
    <?php include(MODULO.'Tema.CSS.php'); ?>
    <style>
    .caja-col {
        padding: 15px;
    }

    .tabla-mov {
        max-height: 380px;
        overflow-y: auto;
    }

    .panel-body p {
        margin: 4px 0;
    }

    .mini {
        font-size: 12px;
    }
    </style>
</head>

<body>
    <?php
if ($usuarioApp['id_perfil'] == 2) include(MODULO.'menu_vendedor.php');
elseif ($usuarioApp['id_perfil'] == 1) include(MODULO.'menu_admin.php');
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header">
                <h1>Cajas del Sistema</h1>
                <p class="lead">Caja General y Caja Chica (multi-responsable + banco)</p>
            </div>

            <div class="row">

                <!-- ================= CAJA GENERAL ================= -->
                <div class="col-md-6 caja-col">
                    <div class="panel panel-primary">
                        <div class="panel-heading"><strong>Caja General</strong></div>

                        <div class="panel-body">

                            <h4>Aperturar (por responsable)</h4>
                            <form method="post" class="form-horizontal">
                                <input type="hidden" name="AccionCaja" value="AperturarGeneral">

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Responsable</label>
                                    <div class="col-sm-8">
                                        <select name="responsable_id" class="form-control" required>
                                            <option value="">-- Seleccione --</option>
                                            <?php
                                    $v1 = $db->SQL("SELECT id, nombre, apellido1, apellido2 FROM vendedores ORDER BY nombre ASC");
                                    while($v = $v1->fetch_assoc()):
                                    ?>
                                            <option value="<?php echo (int)$v['id']; ?>">
                                                <?php echo trim($v['nombre'].' '.$v['apellido1'].' '.$v['apellido2']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <p class="help-block mini">Solo permite 1 caja general abierta por responsable.
                                        </p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Monto inicial</label>
                                    <div class="col-sm-8">
                                        <input type="number" step="0.01" min="0" name="monto_inicial"
                                            class="form-control" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Origen</label>
                                    <div class="col-sm-8">
                                        <select name="origen_general" id="origen_general" class="form-control">
                                            <option value="EFECTIVO">Efectivo</option>
                                            <option value="BANCO">Banco</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group" id="grupo_banco_general" style="display:none;">
                                    <label class="col-sm-4 control-label">Banco</label>
                                    <div class="col-sm-8">
                                        <select name="id_banco_general" class="form-control">
                                            <option value="">-- Seleccione --</option>
                                            <?php
                                    $b1 = $db->SQL("SELECT id, nombre, numero_cuenta FROM bancos ORDER BY nombre ASC");
                                    while($b = $b1->fetch_assoc()):
                                    ?>
                                            <option value="<?php echo (int)$b['id']; ?>">
                                                <?php echo $b['nombre'] . (!empty($b['numero_cuenta']) ? " ({$b['numero_cuenta']})" : ""); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <p class="help-block mini">Origen banco genera EGRESO en banco_movimientos.</p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-sm-8 col-sm-offset-4">
                                        <button class="btn btn-success"><i class="glyphicon glyphicon-ok"></i>
                                            Aperturar</button>
                                    </div>
                                </div>
                            </form>

                            <hr>

                            <h4>Cajas Generales Abiertas</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Responsable</th>
                                            <th>Apertura</th>
                                            <th>Banco</th>
                                            <th class="text-right">Saldo</th>
                                            <th style="width:280px;">Cerrar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($CajasGeneralesAbiertas && $CajasGeneralesAbiertas->num_rows > 0): ?>
                                        <?php while($cg = $CajasGeneralesAbiertas->fetch_assoc()): ?>
                                        <?php $saldo = saldoCajaGeneralSesion($db, $cg['id']); ?>
                                        <tr>
                                            <td><?php echo (int)$cg['id']; ?></td>
                                            <td><?php echo nombreResponsable($db, $cg['responsable']); ?></td>
                                            <td><?php echo $cg['fecha'].' '.$cg['hora']; ?><br><span class="mini">Monto:
                                                    <?php echo number_format((float)$cg['monto'],2); ?></span></td>
                                            <td>
                                                <?php echo ($cg['metodo_apertura'] ?? 'EFECTIVO'); ?><br>
                                                <span
                                                    class="mini"><?php echo nombreBanco($db, $cg['id_banco'] ?? null); ?></span>
                                            </td>
                                            <td class="text-right">
                                                <strong><?php echo number_format($saldo,2); ?></strong></td>
                                            <td>
                                                <form method="post" class="form-inline" style="margin:0;">
                                                    <input type="hidden" name="AccionCaja" value="CerrarGeneral">
                                                    <input type="hidden" name="responsable_id"
                                                        value="<?php echo (int)$cg['responsable']; ?>">
                                                    <input type="hidden" name="id_caja"
                                                        value="<?php echo (int)$cg['id']; ?>">

                                                    <input type="number" step="0.01" min="0" name="monto_cierre"
                                                        value="<?php echo number_format($saldo,2,'.',''); ?>"
                                                        class="form-control input-sm" style="width:110px;" required>

                                                    <select name="destino_general"
                                                        class="form-control input-sm destino_general"
                                                        style="width:95px;">
                                                        <option value="EFECTIVO">Efectivo</option>
                                                        <option value="BANCO">Banco</option>
                                                    </select>

                                                    <select name="id_banco_cierre_general"
                                                        class="form-control input-sm banco_general"
                                                        style="width:150px; display:none;">
                                                        <option value="">-- Banco --</option>
                                                        <?php
                                                    $b2 = $db->SQL("SELECT id, nombre, numero_cuenta FROM bancos ORDER BY nombre ASC");
                                                    while($bb = $b2->fetch_assoc()):
                                                    ?>
                                                        <option value="<?php echo (int)$bb['id']; ?>">
                                                            <?php echo $bb['nombre'] . (!empty($bb['numero_cuenta']) ? " ({$bb['numero_cuenta']})" : ""); ?>
                                                        </option>
                                                        <?php endwhile; ?>
                                                    </select>

                                                    <button class="btn btn-danger btn-sm"><i
                                                            class="glyphicon glyphicon-remove"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No hay cajas generales
                                                abiertas.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <hr>
                            <h4>Últimos movimientos (General)</h4>
                            <div class="tabla-mov">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Tipo</th>
                                            <th>Método</th>
                                            <th>Banco</th>
                                            <th class="text-right">Monto</th>
                                            <th class="text-right">Saldo</th>
                                            <th>Concepto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($MovGeneralSQL && $MovGeneralSQL->num_rows > 0): ?>
                                        <?php while($m = $MovGeneralSQL->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo (int)$m['id']; ?></td>
                                            <td><?php echo $m['fecha']; ?></td>
                                            <td><?php echo $m['hora']; ?></td>
                                            <td>
                                                <?php echo ($m['tipo']=='INGRESO')
                                                ? '<span class="label label-success">Ingreso</span>'
                                                : '<span class="label label-danger">Egreso</span>'; ?>
                                            </td>
                                            <td><?php echo $m['metodo_pago']; ?></td>
                                            <td><?php echo !empty($m['banco_nombre']) ? $m['banco_nombre'] : '-'; ?>
                                            </td>
                                            <td class="text-right">
                                                <strong><?php echo number_format((float)$m['monto'],2); ?></strong></td>
                                            <td class="text-right">
                                                <?php echo number_format((float)$m['saldo_caja'],2); ?></td>
                                            <td><?php echo $m['concepto']; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">Sin movimientos.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ================= CAJA CHICA ================= -->
                <div class="col-md-6 caja-col">
                    <div class="panel panel-warning">
                        <div class="panel-heading"><strong>Caja Chica</strong></div>

                        <div class="panel-body">

                            <h4>Aperturar (por responsable)</h4>
                            <form method="post" class="form-horizontal">
                                <input type="hidden" name="AccionCaja" value="AperturarChica">

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Responsable</label>
                                    <div class="col-sm-8">
                                        <select name="responsable_id" class="form-control" required>
                                            <option value="">-- Seleccione --</option>
                                            <?php
                                    $v2 = $db->SQL("SELECT id, nombre, apellido1, apellido2 FROM vendedores ORDER BY nombre ASC");
                                    while($v = $v2->fetch_assoc()):
                                    ?>
                                            <option value="<?php echo (int)$v['id']; ?>">
                                                <?php echo trim($v['nombre'].' '.$v['apellido1'].' '.$v['apellido2']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <p class="help-block mini">Solo permite 1 caja chica abierta por responsable.
                                        </p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Monto inicial</label>
                                    <div class="col-sm-8">
                                        <input type="number" min="0" step="0.01" name="monto_inicial_chica"
                                            class="form-control" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Origen</label>
                                    <div class="col-sm-8">
                                        <select name="origen_chica" id="origen_chica" class="form-control">
                                            <option value="EFECTIVO">Efectivo</option>
                                            <option value="BANCO">Banco</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group" id="grupo_banco_chica" style="display:none;">
                                    <label class="col-sm-4 control-label">Banco</label>
                                    <div class="col-sm-8">
                                        <select name="id_banco_chica" class="form-control">
                                            <option value="">-- Seleccione --</option>
                                            <?php
                                    $b3 = $db->SQL("SELECT id, nombre, numero_cuenta FROM bancos ORDER BY nombre ASC");
                                    while($b = $b3->fetch_assoc()):
                                    ?>
                                            <option value="<?php echo (int)$b['id']; ?>">
                                                <?php echo $b['nombre'] . (!empty($b['numero_cuenta']) ? " ({$b['numero_cuenta']})" : ""); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <p class="help-block mini">Origen banco genera EGRESO en banco_movimientos.</p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-sm-8 col-sm-offset-4">
                                        <button class="btn btn-success"><i class="glyphicon glyphicon-ok"></i>
                                            Aperturar</button>
                                    </div>
                                </div>
                            </form>

                            <hr>

                            <h4>Cajas Chicas Abiertas</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Responsable</th>
                                            <th>Apertura</th>
                                            <th>Banco</th>
                                            <th class="text-right">Saldo</th>
                                            <th style="width:280px;">Cerrar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($CajasChicasAbiertas && $CajasChicasAbiertas->num_rows > 0): ?>
                                        <?php while($cc = $CajasChicasAbiertas->fetch_assoc()): ?>
                                        <?php $saldo = saldoCajaChicaSesion($db, $cc['id']); ?>
                                        <tr>
                                            <td><?php echo (int)$cc['id']; ?></td>
                                            <td><?php echo nombreResponsable($db, $cc['responsable']); ?></td>
                                            <td><?php echo $cc['fecha'].' '.$cc['hora']; ?><br><span class="mini">Monto:
                                                    <?php echo number_format((float)$cc['monto'],2); ?></span></td>
                                            <td>
                                                <?php echo ($cc['metodo_apertura'] ?? 'EFECTIVO'); ?><br>
                                                <span
                                                    class="mini"><?php echo nombreBanco($db, $cc['id_banco'] ?? null); ?></span>
                                            </td>
                                            <td class="text-right">
                                                <strong><?php echo number_format($saldo,2); ?></strong></td>
                                            <td>
                                                <form method="post" class="form-inline" style="margin:0;">
                                                    <input type="hidden" name="AccionCaja" value="CerrarChica">
                                                    <input type="hidden" name="responsable_id"
                                                        value="<?php echo (int)$cc['responsable']; ?>">
                                                    <input type="hidden" name="id_cajachica"
                                                        value="<?php echo (int)$cc['id']; ?>">

                                                    <input type="number" step="0.01" min="0" name="monto_cierre_chica"
                                                        value="<?php echo number_format($saldo,2,'.',''); ?>"
                                                        class="form-control input-sm" style="width:110px;" required>

                                                    <select name="destino_chica"
                                                        class="form-control input-sm destino_chica" style="width:95px;">
                                                        <option value="EFECTIVO">Efectivo</option>
                                                        <option value="BANCO">Banco</option>
                                                    </select>

                                                    <select name="id_banco_cierre_chica"
                                                        class="form-control input-sm banco_chica"
                                                        style="width:150px; display:none;">
                                                        <option value="">-- Banco --</option>
                                                        <?php
                                                    $b4 = $db->SQL("SELECT id, nombre, numero_cuenta FROM bancos ORDER BY nombre ASC");
                                                    while($bb = $b4->fetch_assoc()):
                                                    ?>
                                                        <option value="<?php echo (int)$bb['id']; ?>">
                                                            <?php echo $bb['nombre'] . (!empty($bb['numero_cuenta']) ? " ({$bb['numero_cuenta']})" : ""); ?>
                                                        </option>
                                                        <?php endwhile; ?>
                                                    </select>

                                                    <button class="btn btn-danger btn-sm"><i
                                                            class="glyphicon glyphicon-remove"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No hay cajas chicas abiertas.
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <hr>
                            <a href="<?php echo URLBASE; ?>caja-chica" class="btn btn-default">Ver movimientos</a>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script>
    (function() {
        function toggleBancos() {
            document.getElementById('grupo_banco_general').style.display =
                (document.getElementById('origen_general').value === 'BANCO') ? '' : 'none';

            document.getElementById('grupo_banco_chica').style.display =
                (document.getElementById('origen_chica').value === 'BANCO') ? '' : 'none';
        }

        document.getElementById('origen_general').addEventListener('change', toggleBancos);
        document.getElementById('origen_chica').addEventListener('change', toggleBancos);
        toggleBancos();

        // Cierre General: mostrar banco si destino BANCO
        var dg = document.querySelectorAll('.destino_general');
        for (var i = 0; i < dg.length; i++) {
            dg[i].addEventListener('change', function() {
                var form = this.closest('form');
                var banco = form.querySelector('.banco_general');
                banco.style.display = (this.value === 'BANCO') ? '' : 'none';
            });
        }

        // Cierre Chica
        var dc = document.querySelectorAll('.destino_chica');
        for (var j = 0; j < dc.length; j++) {
            dc[j].addEventListener('change', function() {
                var form = this.closest('form');
                var banco = form.querySelector('.banco_chica');
                banco.style.display = (this.value === 'BANCO') ? '' : 'none';
            });
        }
    })();
    </script>

</body>

</html>