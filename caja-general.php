<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

if (!isset($usuarioApp)) {
    echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
    exit;
}

date_default_timezone_set(HORARIO);

$mensaje = '';
$tipo_mensaje = 'info';

/* ======================================================
|   REGISTRAR MOVIMIENTO MANUAL EN CAJA GENERAL
====================================================== */
if (isset($_POST['RegistrarMovimientoGeneral'])) {

    $tipo        = $_POST['tipo'];
    $monto       = floatval(str_replace(",", ".", $_POST['monto']));
    $concepto    = trim($_POST['concepto']);
    $metodo_pago = $_POST['metodo_pago'];
    $id_banco    = !empty($_POST['id_banco']) ? intval($_POST['id_banco']) : null;
    $referencia  = trim($_POST['referencia']);

    // Normalización
    $tipo = strtoupper(trim($tipo));
    $metodo_pago = strtoupper(trim($metodo_pago));

    // Métodos permitidos (manteniendo compatibilidad con tu BD)
    $metodos_permitidos = ['EFECTIVO','TRANSFERENCIA','DEPOSITO','TARJETA','QR'];
    if (!in_array($metodo_pago, $metodos_permitidos, true)) {
        $metodo_pago = 'EFECTIVO';
    }

    // Validaciones
    if ($monto <= 0 || $concepto == '' || ($tipo != 'INGRESO' && $tipo != 'EGRESO')) {
        $mensaje = 'Debe indicar un monto válido, un concepto y un tipo.';
        $tipo_mensaje = 'danger';
    } elseif ($metodo_pago != 'EFECTIVO' && empty($id_banco)) {
        $mensaje = 'Debe seleccionar un banco para pagos que no sean en efectivo.';
        $tipo_mensaje = 'warning';
    } else {

        $fecha = date("Y-m-d");
        $hora  = date("H:i:s");

        $responsable = !empty($usuarioApp['id_vendedor'])
            ? intval($usuarioApp['id_vendedor'])
            : intval($usuarioApp['id']);

        /* OBTENER SALDO ANTERIOR */
        $SaldoSQL = $db->SQL("SELECT saldo_caja FROM caja_general_movimientos ORDER BY id DESC LIMIT 1");
        if ($SaldoSQL && $SaldoSQL->num_rows > 0) {
            $saldoAnterior = floatval($SaldoSQL->fetch_assoc()['saldo_caja']);
        } else {
            $saldoAnterior = 0;
        }

        /* NUEVO SALDO */
        $saldoNuevo = ($tipo === 'INGRESO')
            ? $saldoAnterior + $monto
            : $saldoAnterior - $monto;

        if ($saldoNuevo < 0) $saldoNuevo = 0;

        /* MANEJO DE BANCOS */
        $saldoBanco = null;

        if ($metodo_pago != 'EFECTIVO' && $id_banco) {

            $db->SQL("
                INSERT INTO banco_movimientos (id_banco, fecha, tipo, monto, concepto, id_venta)
                VALUES ('{$id_banco}', NOW(), '{$tipo}', '{$monto}', '".addslashes($concepto)."', NULL)
            ");

            $SaldoBancoSQL = $db->SQL("
                SELECT b.saldo_inicial +
                    COALESCE(SUM(CASE WHEN bm.tipo='INGRESO' THEN bm.monto ELSE -bm.monto END),0) AS saldo
                FROM bancos b
                LEFT JOIN banco_movimientos bm ON bm.id_banco=b.id
                WHERE b.id='{$id_banco}'
                GROUP BY b.id
            ");

            if ($SaldoBancoSQL && $SaldoBancoSQL->num_rows > 0) {
                $saldoBanco = floatval($SaldoBancoSQL->fetch_assoc()['saldo']);
            }
        }

        /* INSERTAR MOVIMIENTO */
        $db->SQL("
            INSERT INTO caja_general_movimientos
            (fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia,
             responsable, saldo_caja, saldo_banco)
            VALUES
            (
                '{$fecha}', '{$hora}', '{$tipo}', '{$monto}', '".addslashes($concepto)."',
                '{$metodo_pago}', ".($id_banco ? "'{$id_banco}'" : "NULL").",
                ".($referencia ? "'".addslashes($referencia)."'" : "NULL").",
                '{$responsable}', '{$saldoNuevo}', ".($saldoBanco !== null ? "'{$saldoBanco}'" : "NULL")."
            )
        ");

        $mensaje = 'Movimiento registrado correctamente.';
        $tipo_mensaje = 'success';
    }
}

/* ======================================================
|   FILTROS FUNCIONALES
====================================================== */
$hoy = date('Y-m-d');

$fecha_desde   = $_GET['desde'] ?? $hoy;
$fecha_hasta   = $_GET['hasta'] ?? $hoy;
$filtro_metodo = $_GET['metodo_pago'] ?? '';
$filtro_banco  = $_GET['id_banco'] ?? '';

$where = "1=1";

if (!empty($fecha_desde)) $where .= " AND m.fecha >= '{$fecha_desde}'";
if (!empty($fecha_hasta)) $where .= " AND m.fecha <= '{$fecha_hasta}'";
if (!empty($filtro_metodo)) $where .= " AND m.metodo_pago = '{$filtro_metodo}'";
if (!empty($filtro_banco))  $where .= " AND m.id_banco = '{$filtro_banco}'";

/* ======================================================
|   BANCOS (SELECT)
====================================================== */
$BancosSQL = $db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");

/* ======================================================
|   CONSULTA PRINCIPAL – MOVIMIENTOS
====================================================== */
$MovSQL = $db->SQL("
    SELECT 
        m.*, 
        u.usuario AS responsable_usuario,
        b.nombre AS banco_nombre
    FROM caja_general_movimientos m
    LEFT JOIN usuario u ON u.id = m.responsable
    LEFT JOIN bancos b  ON b.id = m.id_banco
    WHERE {$where}
    ORDER BY m.id DESC
");

/* ======================================================
|   SALDO CAJA (GLOBAL)
====================================================== */
$saldo_caja_actual = 0;
$rowSaldo = $db->SQL("SELECT saldo_caja FROM caja_general_movimientos ORDER BY id DESC LIMIT 1");
if ($rowSaldo && $rowSaldo->num_rows > 0) {
    $saldo_caja_actual = (float)$rowSaldo->fetch_assoc()['saldo_caja'];
}

/* ======================================================
|   REPORTES (MISMO FILTRO $where)
|   - Agrupa TRANSFERENCIA/DEPOSITO como BANCO
====================================================== */
$TotalesSQL = $db->SQL("
    SELECT
        COALESCE(SUM(CASE WHEN m.tipo='INGRESO' THEN m.monto ELSE 0 END),0) AS ingresos,
        COALESCE(SUM(CASE WHEN m.tipo='EGRESO' THEN m.monto ELSE 0 END),0) AS egresos,
        COALESCE(SUM(CASE WHEN m.tipo='INGRESO' THEN m.monto ELSE -m.monto END),0) AS neto
    FROM caja_general_movimientos m
    WHERE {$where}
");
$tot = ($TotalesSQL && $TotalesSQL->num_rows) ? $TotalesSQL->fetch_assoc() : ['ingresos'=>0,'egresos'=>0,'neto'=>0];

$ReporteMetodosSQL = $db->SQL("
    SELECT
        CASE 
            WHEN m.metodo_pago IN ('TRANSFERENCIA','DEPOSITO') THEN 'BANCO'
            ELSE m.metodo_pago
        END AS metodo_grp,
        COALESCE(SUM(CASE WHEN m.tipo='INGRESO' THEN m.monto ELSE 0 END),0) AS total_ingresos,
        COALESCE(SUM(CASE WHEN m.tipo='EGRESO' THEN m.monto ELSE 0 END),0) AS total_egresos,
        COALESCE(SUM(CASE WHEN m.tipo='INGRESO' THEN m.monto ELSE -m.monto END),0) AS neto
    FROM caja_general_movimientos m
    WHERE {$where}
    GROUP BY 
        CASE 
            WHEN m.metodo_pago IN ('TRANSFERENCIA','DEPOSITO') THEN 'BANCO'
            ELSE m.metodo_pago
        END
    ORDER BY metodo_grp
");

$ReporteBancosSQL = $db->SQL("
    SELECT
        COALESCE(b.nombre, 'SIN BANCO') AS banco,
        COALESCE(SUM(CASE WHEN m.tipo='INGRESO' THEN m.monto ELSE 0 END),0) AS ingresos,
        COALESCE(SUM(CASE WHEN m.tipo='EGRESO' THEN m.monto ELSE 0 END),0) AS egresos,
        COALESCE(SUM(CASE WHEN m.tipo='INGRESO' THEN m.monto ELSE -m.monto END),0) AS neto
    FROM caja_general_movimientos m
    LEFT JOIN bancos b ON b.id = m.id_banco
    WHERE {$where}
    GROUP BY m.id_banco
    ORDER BY banco ASC
");

/* ======================================================
|   SALDOS ACTUALES POR BANCO (GLOBAL)
====================================================== */
$SaldosBancosSQL = $db->SQL("
    SELECT 
        b.id, b.nombre, b.numero_cuenta, b.moneda,
        (b.saldo_inicial + COALESCE(SUM(CASE WHEN bm.tipo='INGRESO' THEN bm.monto ELSE -bm.monto END),0)) AS saldo_actual
    FROM bancos b
    LEFT JOIN banco_movimientos bm ON bm.id_banco = b.id
    GROUP BY b.id
    ORDER BY b.nombre ASC
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Caja General | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 2) include(MODULO.'menu_vendedor.php');
elseif ($usuarioApp['id_perfil'] == 1) include(MODULO.'menu_admin.php');
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header">
                <div class="row">
                    <div class="col-md-8">
                        <h1>Caja General</h1>
                        <p class="text-muted">Ingresos del POS + movimientos manuales + control bancario</p>

                        <a href="caja-general-bancos.php" class="btn btn-default btn-sm">
                            Administrar Bancos
                        </a>
                    </div>

                    <div class="col-md-4 text-right">
                        <h3 style="margin-top:0;">Saldo actual</h3>
                        <span class="label label-primary" style="font-size:18px;">Bs
                            <?php echo number_format($saldo_caja_actual, 2); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($mensaje != ''): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>

            <div class="row">

                <!-- FORMULARIO DE MOVIMIENTO -->
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Registrar Movimiento</strong></div>
                        <div class="panel-body">

                            <form method="post">

                                <div class="form-group">
                                    <label>Tipo</label>
                                    <select name="tipo" class="form-control" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="INGRESO">Ingreso</option>
                                        <option value="EGRESO">Egreso</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Monto (Bs)</label>
                                    <input type="number" name="monto" step="0.01" min="0" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label>Concepto</label>
                                    <textarea name="concepto" class="form-control" rows="2" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Método de Pago</label>
                                    <select name="metodo_pago" id="metodo_pago" class="form-control" required>
                                        <option value="EFECTIVO">Efectivo</option>
                                        <option value="TRANSFERENCIA">Banco - Transferencia</option>
                                        <option value="DEPOSITO">Banco - Depósito</option>
                                        <option value="TARJETA">Tarjeta</option>
                                        <option value="QR">QR</option>
                                    </select>
                                    <p class="help-block" style="margin-bottom:0;">
                                        Nota: Transferencia/Depósito se consolidan como “BANCO” en reportes.
                                    </p>
                                </div>

                                <div class="form-group">
                                    <label>Banco (si aplica)</label>
                                    <select name="id_banco" id="id_banco" class="form-control">
                                        <option value="">-- Seleccione banco --</option>
                                        <?php while($b = $BancosSQL->fetch_assoc()): ?>
                                        <option value="<?php echo $b['id']; ?>"><?php echo $b['nombre']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <p class="help-block">Obligatorio si el método no es efectivo.</p>
                                </div>

                                <div class="form-group">
                                    <label>Referencia</label>
                                    <input type="text" name="referencia" class="form-control">
                                </div>

                                <button type="submit" name="RegistrarMovimientoGeneral"
                                    class="btn btn-primary btn-block">
                                    Registrar
                                </button>

                            </form>

                        </div>
                    </div>
                </div>

                <!-- PANEL DERECHO -->
                <div class="col-md-8">

                    <!-- REPORTES -->
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Reportes (según filtros)</strong></div>
                        <div class="panel-body">

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="well well-sm" style="margin-bottom:10px;">
                                        <strong>Total Ingresos:</strong> Bs
                                        <?php echo number_format((float)$tot['ingresos'],2); ?><br>
                                        <strong>Total Egresos:</strong> Bs
                                        <?php echo number_format((float)$tot['egresos'],2); ?><br>
                                        <strong>Neto:</strong> Bs <?php echo number_format((float)$tot['neto'],2); ?>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-condensed" style="margin-bottom:10px;">
                                            <thead>
                                                <tr>
                                                    <th>Método</th>
                                                    <th class="text-right">Ingresos</th>
                                                    <th class="text-right">Egresos</th>
                                                    <th class="text-right">Neto</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($r = $ReporteMetodosSQL->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $r['metodo_grp']; ?></td>
                                                    <td class="text-right">
                                                        <?php echo number_format((float)$r['total_ingresos'],2); ?></td>
                                                    <td class="text-right">
                                                        <?php echo number_format((float)$r['total_egresos'],2); ?></td>
                                                    <td class="text-right">
                                                        <?php echo number_format((float)$r['neto'],2); ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <hr style="margin:10px 0;">

                            <div class="table-responsive">
                                <table class="table table-bordered table-condensed" style="margin-bottom:0;">
                                    <thead>
                                        <tr>
                                            <th>Banco</th>
                                            <th class="text-right">Ingresos</th>
                                            <th class="text-right">Egresos</th>
                                            <th class="text-right">Neto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($rb = $ReporteBancosSQL->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $rb['banco']; ?></td>
                                            <td class="text-right">
                                                <?php echo number_format((float)$rb['ingresos'],2); ?></td>
                                            <td class="text-right"><?php echo number_format((float)$rb['egresos'],2); ?>
                                            </td>
                                            <td class="text-right"><?php echo number_format((float)$rb['neto'],2); ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                    <!-- SALDOS BANCARIOS -->
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Saldos bancarios actuales</strong></div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-condensed"
                                    style="margin-bottom:0;">
                                    <thead>
                                        <tr>
                                            <th>Banco</th>
                                            <th>N° Cuenta</th>
                                            <th>Moneda</th>
                                            <th class="text-right">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($sb = $SaldosBancosSQL->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $sb['nombre']; ?></td>
                                            <td><?php echo $sb['numero_cuenta']; ?></td>
                                            <td><?php echo $sb['moneda']; ?></td>
                                            <td class="text-right">
                                                <?php echo number_format((float)$sb['saldo_actual'],2); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted" style="margin-top:8px;margin-bottom:0;">
                                Calculado con saldo_inicial + movimientos registrados en banco_movimientos.
                            </p>
                        </div>
                    </div>

                    <!-- FILTROS -->
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Filtros</strong></div>
                        <div class="panel-body">

                            <form class="form-inline">

                                <label>Desde:&nbsp;</label>
                                <input type="date" name="desde" value="<?php echo $fecha_desde; ?>"
                                    class="form-control">

                                <label>&nbsp;Hasta:&nbsp;</label>
                                <input type="date" name="hasta" value="<?php echo $fecha_hasta; ?>"
                                    class="form-control">

                                <label>&nbsp;Método:&nbsp;</label>
                                <select name="metodo_pago" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="EFECTIVO" <?php if($filtro_metodo=='EFECTIVO') echo 'selected'; ?>>
                                        Efectivo</option>
                                    <option value="TRANSFERENCIA"
                                        <?php if($filtro_metodo=='TRANSFERENCIA') echo 'selected'; ?>>Banco -
                                        Transferencia</option>
                                    <option value="DEPOSITO" <?php if($filtro_metodo=='DEPOSITO') echo 'selected'; ?>>
                                        Banco - Depósito</option>
                                    <option value="TARJETA" <?php if($filtro_metodo=='TARJETA') echo 'selected'; ?>>
                                        Tarjeta</option>
                                    <option value="QR" <?php if($filtro_metodo=='QR') echo 'selected'; ?>>QR</option>
                                </select>

                                <label>&nbsp;Banco:&nbsp;</label>
                                <select name="id_banco" class="form-control">
                                    <option value="">Todos</option>
                                    <?php
                                    $b2 = $db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");
                                    while ($bf = $b2->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $bf['id']; ?>"
                                        <?php if($bf['id']==$filtro_banco) echo 'selected'; ?>>
                                        <?php echo $bf['nombre']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>

                                <button type="submit" class="btn btn-default">Buscar</button>

                            </form>

                        </div>
                    </div>

                    <!-- TABLA -->
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Movimientos</strong></div>
                        <div class="panel-body">

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="tabla_movimientos">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Tipo</th>
                                            <th>Concepto</th>
                                            <th>Método</th>
                                            <th>Banco</th>
                                            <th>Monto</th>
                                            <th>Saldo Caja</th>
                                            <th>Responsable</th>
                                            <th>Referencia</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php
                                    $total_ing = 0;
                                    $total_egr = 0;

                                    while($m = $MovSQL->fetch_assoc()):
                                        $isIng = ($m['tipo'] == 'INGRESO');
                                        if ($isIng) $total_ing += $m['monto'];
                                        else $total_egr += $m['monto'];

                                        // Mostrar “BANCO” si fue transferencia/deposito
                                        $metodo_mostrar = $m['metodo_pago'];
                                        if (in_array($metodo_mostrar, ['TRANSFERENCIA','DEPOSITO'])) $metodo_mostrar = 'BANCO';
                                ?>
                                        <tr>
                                            <td><?php echo $m['fecha']; ?></td>
                                            <td><?php echo $m['hora']; ?></td>
                                            <td>
                                                <?php echo $isIng
                                                ? "<span class='label label-success'>Ingreso</span>"
                                                : "<span class='label label-danger'>Egreso</span>"; ?>
                                            </td>
                                            <td><?php echo $m['concepto']; ?></td>
                                            <td><?php echo $metodo_mostrar; ?></td>
                                            <td><?php echo $m['banco_nombre'] ?: '-'; ?></td>
                                            <td class="text-right"><?php echo number_format($m['monto'],2); ?></td>
                                            <td class="text-right"><?php echo number_format($m['saldo_caja'],2); ?></td>
                                            <td><?php echo $m['responsable_usuario'] ?: '-'; ?></td>
                                            <td><?php echo $m['referencia'] ?: '-'; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>

                                    <tfoot>
                                        <tr>
                                            <th colspan="6" class="text-right">Total Ingresos:</th>
                                            <th class="text-right">Bs <?php echo number_format($total_ing,2); ?></th>
                                            <th colspan="3"></th>
                                        </tr>

                                        <tr>
                                            <th colspan="6" class="text-right">Total Egresos:</th>
                                            <th class="text-right">Bs <?php echo number_format($total_egr,2); ?></th>
                                            <th colspan="3"></th>
                                        </tr>

                                        <tr>
                                            <th colspan="6" class="text-right">Resultado:</th>
                                            <th class="text-right">Bs
                                                <?php echo number_format($total_ing-$total_egr,2); ?></th>
                                            <th colspan="3"></th>
                                        </tr>
                                    </tfoot>

                                </table>
                            </div>

                        </div>
                    </div>

                </div><!-- /col-md-8 -->

            </div><!-- /row -->

        </div><!-- /container -->
    </div><!-- /wrap -->

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $(document).ready(function() {
        $('#tabla_movimientos').dataTable({
            "order": [
                [0, 'desc']
            ]
        });

        function toggleBanco() {
            var metodo = ($('#metodo_pago').val() || '').toUpperCase();
            var esEfectivo = (metodo === 'EFECTIVO');

            $('#id_banco').prop('disabled', esEfectivo);

            // Si es efectivo, limpiamos banco
            if (esEfectivo) {
                $('#id_banco').val('');
            }
        }

        $('#metodo_pago').on('change', toggleBanco);
        toggleBanco();
    });
    </script>

</body>

</html>