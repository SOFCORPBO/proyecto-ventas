<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Si no hay usuario logueado, salir
if (!isset($usuarioApp) || !is_array($usuarioApp)) {
    echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
    exit;
}

date_default_timezone_set(HORARIO);

// -------------------------------
// MENSAJES
// -------------------------------
$mensaje = '';
$tipo_mensaje = 'info';

// -------------------------------
// REGISTRAR MOVIMIENTO MANUAL
// -------------------------------
if (isset($_POST['RegistrarMovimientoGeneral'])) {

    $tipo         = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $montoRaw     = isset($_POST['monto']) ? $_POST['monto'] : '0';
    $monto        = (float) str_replace(',', '.', $montoRaw);
    $concepto     = trim(isset($_POST['concepto']) ? $_POST['concepto'] : '');
    $metodo_pago  = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'EFECTIVO';
    $id_banco     = !empty($_POST['id_banco']) ? (int) $_POST['id_banco'] : null;
    $referencia   = trim(isset($_POST['referencia']) ? $_POST['referencia'] : '');

    if ($monto <= 0 || $concepto == '' || ($tipo != 'INGRESO' && $tipo != 'EGRESO')) {
        $mensaje = 'Debe indicar un monto válido, un concepto y el tipo de movimiento.';
        $tipo_mensaje = 'danger';
    } else {

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        // Responsable: id_vendedor si existe, de lo contrario id de usuario
        $responsable = !empty($usuarioApp['id_vendedor'])
            ? (int)$usuarioApp['id_vendedor']
            : (int)$usuarioApp['id'];

        // ---- Obtener saldo actual de Caja General ----
        $saldoActualCaja = 0;

        $SaldoSQL = $db->SQL("SELECT saldo_caja FROM caja_general_movimientos ORDER BY id DESC LIMIT 1");
        if ($SaldoSQL && $SaldoSQL->num_rows > 0) {
            $rowSaldo = $SaldoSQL->fetch_assoc();
            $saldoActualCaja = (float)$rowSaldo['saldo_caja'];
        } else {
            // Si no hay movimientos, usar monto de tabla caja (tipo GENERAL)
            $CajaSQL = $db->SQL("SELECT monto FROM caja WHERE tipo_caja='GENERAL' AND habilitado=1 ORDER BY id DESC LIMIT 1");
            if ($CajaSQL && $CajaSQL->num_rows > 0) {
                $rowCaja = $CajaSQL->fetch_assoc();
                $saldoActualCaja = (float)$rowCaja['monto'];
            }
        }

        // Nuevo saldo de caja
        $nuevoSaldoCaja = $saldoActualCaja;
        if ($tipo == 'INGRESO') {
            $nuevoSaldoCaja += $monto;
        } else {
            $nuevoSaldoCaja -= $monto;
        }

        // ---- Manejo de bancos / saldo_banco ----
        $saldoBanco = null;

        if ($metodo_pago !== 'EFECTIVO' && $id_banco) {

            // Registrar movimiento en banco_movimientos
            $conceptoBanco = addslashes($concepto);
            $db->SQL("
                INSERT INTO banco_movimientos (id_banco, fecha, tipo, monto, concepto, id_venta)
                VALUES (
                    '{$id_banco}',
                    NOW(),
                    '{$tipo}',
                    '{$monto}',
                    '{$conceptoBanco}',
                    NULL
                )
            ");

            // Recalcular saldo del banco (saldo_inicial + movimientos)
            $SaldoBancoSQL = $db->SQL("
                SELECT 
                    b.saldo_inicial + 
                    COALESCE(SUM(
                        CASE 
                            WHEN bm.tipo = 'INGRESO' THEN bm.monto 
                            ELSE -bm.monto 
                        END
                    ), 0) AS saldo_actual
                FROM bancos b
                LEFT JOIN banco_movimientos bm ON bm.id_banco = b.id
                WHERE b.id = '{$id_banco}'
                GROUP BY b.id
            ");

            if ($SaldoBancoSQL && $SaldoBancoSQL->num_rows > 0) {
                $rowBanco   = $SaldoBancoSQL->fetch_assoc();
                $saldoBanco = (float)$rowBanco['saldo_actual'];
            }
        }

        // ---- Insertar movimiento en CAJA GENERAL ----
        $conceptoDB   = addslashes($concepto);
        $referenciaDB = addslashes($referencia);

        $sqlInsert = "
            INSERT INTO caja_general_movimientos
                (fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia,
                 responsable, saldo_caja, saldo_banco)
            VALUES
                ('{$fecha}', '{$hora}', '{$tipo}', '{$monto}', '{$conceptoDB}', '{$metodo_pago}', 
                 ".($id_banco ? "'{$id_banco}'" : "NULL").",
                 ".($referenciaDB !== '' ? "'{$referenciaDB}'" : "NULL").",
                 '{$responsable}', '{$nuevoSaldoCaja}', ".($saldoBanco !== null ? "'{$saldoBanco}'" : "NULL")."
                )
        ";
        $db->SQL($sqlInsert);

        $mensaje = 'Movimiento registrado correctamente en Caja General.';
        $tipo_mensaje = 'success';
    }
}

// -------------------------------
// FILTROS DE CONSULTA
// -------------------------------
$hoy = date('Y-m-d');

$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : $hoy;
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : $hoy;
$filtro_metodo = isset($_GET['metodo_pago']) ? $_GET['metodo_pago'] : '';
$filtro_banco  = isset($_GET['id_banco']) ? (int)$_GET['id_banco'] : 0;

// -------------------------------
// LISTAR BANCOS PARA COMBOS
// -------------------------------
$BancosSQL = $db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");

// -------------------------------
// CONSULTA DE MOVIMIENTOS
// -------------------------------
$where = "1=1";

if ($fecha_desde != '') {
    $where .= " AND m.fecha >= '".$fecha_desde."'";
}
if ($fecha_hasta != '') {
    $where .= " AND m.fecha <= '".$fecha_hasta."'";
}
if ($filtro_metodo != '') {
    $filtro_metodo_db = addslashes($filtro_metodo);
    $where .= " AND m.metodo_pago = '".$filtro_metodo_db."'";
}
if ($filtro_banco > 0) {
    $where .= " AND m.id_banco = '".$filtro_banco."'";
}

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

// Totales
$total_ingresos = 0;
$total_egresos  = 0;
$saldo_caja_actual = 0;

if ($MovSQL && $MovSQL->num_rows > 0) {

    // Para sacar saldo actual tomamos el último registro del result o hacemos otra consulta
    $SaldoCajaSQL = $db->SQL("SELECT saldo_caja FROM caja_general_movimientos ORDER BY id DESC LIMIT 1");
    if ($SaldoCajaSQL && $SaldoCajaSQL->num_rows > 0) {
        $rowSC = $SaldoCajaSQL->fetch_assoc();
        $saldo_caja_actual = (float)$rowSC['saldo_caja'];
    }

    // Para recorrer luego en HTML, dejamos el cursor al inicio
    // (esta parte la haremos directamente en el while del HTML, los totales se recalcularán allí)
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Caja General | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
// Menú según perfil
if ($usuarioApp['id_perfil'] == 2) {
    include(MODULO.'menu_vendedor.php');
} elseif ($usuarioApp['id_perfil'] == 1) {
    include(MODULO.'menu_admin.php');
} else {
    echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
    exit;
}
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header" id="banner">
                <div class="row">
                    <div class="col-lg-8 col-md-7 col-sm-6">
                        <h1>Caja General</h1>
                        <p class="lead">Registro de ingresos y egresos mayores, con control de bancos.</p>
                    </div>
                    <div class="col-lg-4 col-md-5 col-sm-6 text-right">
                        <h3>Saldo Caja General</h3>
                        <span class="label label-primary" style="font-size:18px;">
                            Bs <?php echo number_format($saldo_caja_actual, 2); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Mensaje -->
            <?php if ($mensaje != ''): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>

            <div class="row">

                <!-- FORM NUEVO MOVIMIENTO -->
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Registrar Movimiento</strong>
                        </div>
                        <div class="panel-body">
                            <form class="form" method="post" action="">
                                <div class="form-group">
                                    <label>Tipo de Movimiento</label>
                                    <select name="tipo" class="form-control" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="INGRESO">Ingreso</option>
                                        <option value="EGRESO">Egreso</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Monto (Bs)</label>
                                    <input type="number" step="0.01" min="0" name="monto" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label>Concepto</label>
                                    <textarea name="concepto" class="form-control" rows="2" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Método de Pago</label>
                                    <select name="metodo_pago" class="form-control" required>
                                        <option value="EFECTIVO">Efectivo</option>
                                        <option value="TRANSFERENCIA">Transferencia</option>
                                        <option value="DEPOSITO">Depósito</option>
                                        <option value="TARJETA">Tarjeta</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Banco (si aplica)</label>
                                    <select name="id_banco" class="form-control">
                                        <option value="">-- Sin banco --</option>
                                        <?php if ($BancosSQL && $BancosSQL->num_rows > 0): ?>
                                        <?php while($b = $BancosSQL->fetch_assoc()): ?>
                                        <option value="<?php echo $b['id']; ?>">
                                            <?php echo htmlspecialchars($b['nombre']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Referencia / Nº Operación (opcional)</label>
                                    <input type="text" name="referencia" class="form-control">
                                </div>

                                <button type="submit" name="RegistrarMovimientoGeneral"
                                    class="btn btn-primary btn-block">
                                    Registrar Movimiento
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- LISTADO MOVIMIENTOS -->
                <div class="col-md-8">

                    <!-- Filtros -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Filtros de búsqueda</strong>
                        </div>
                        <div class="panel-body">
                            <form class="form-inline" method="get" action="">
                                <div class="form-group">
                                    <label>Desde:&nbsp;</label>
                                    <input type="date" name="desde"
                                        value="<?php echo htmlspecialchars($fecha_desde); ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;Hasta:&nbsp;</label>
                                    <input type="date" name="hasta"
                                        value="<?php echo htmlspecialchars($fecha_hasta); ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;Método:&nbsp;</label>
                                    <select name="metodo_pago" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="EFECTIVO"
                                            <?php echo ($filtro_metodo=='EFECTIVO'?'selected':''); ?>>Efectivo</option>
                                        <option value="TRANSFERENCIA"
                                            <?php echo ($filtro_metodo=='TRANSFERENCIA'?'selected':''); ?>>Transferencia
                                        </option>
                                        <option value="DEPOSITO"
                                            <?php echo ($filtro_metodo=='DEPOSITO'?'selected':''); ?>>Depósito</option>
                                        <option value="TARJETA"
                                            <?php echo ($filtro_metodo=='TARJETA'?'selected':''); ?>>Tarjeta</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;Banco:&nbsp;</label>
                                    <select name="id_banco" class="form-control">
                                        <option value="0">Todos</option>
                                        <?php
                                    // Para el combo de filtros de banco, volvemos a leer bancos (porque el cursor ya se usó arriba)
                                    $BancosFiltroSQL = $db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");
                                    if ($BancosFiltroSQL && $BancosFiltroSQL->num_rows > 0):
                                        while($bf = $BancosFiltroSQL->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $bf['id']; ?>"
                                            <?php echo ($filtro_banco==$bf['id']?'selected':''); ?>>
                                            <?php echo htmlspecialchars($bf['nombre']); ?>
                                        </option>
                                        <?php
                                        endwhile;
                                    endif;
                                    ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-default">
                                    Buscar
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Movimientos de Caja General</strong>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-condensed"
                                    id="tabla_movimientos">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Tipo</th>
                                            <th>Concepto</th>
                                            <th>Método</th>
                                            <th>Banco</th>
                                            <th>Monto (Bs)</th>
                                            <th>Saldo Caja (Bs)</th>
                                            <th>Resp.</th>
                                            <th>Ref.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                    $total_ingresos = 0;
                                    $total_egresos  = 0;

                                    if ($MovSQL && $MovSQL->num_rows > 0):
                                        while($m = $MovSQL->fetch_assoc()):
                                            $esIngreso = ($m['tipo'] == 'INGRESO');
                                            if ($esIngreso) {
                                                $total_ingresos += (float)$m['monto'];
                                            } else {
                                                $total_egresos  += (float)$m['monto'];
                                            }
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($m['fecha']); ?></td>
                                            <td><?php echo htmlspecialchars($m['hora']); ?></td>
                                            <td>
                                                <?php if ($esIngreso): ?>
                                                <span class="label label-success">Ingreso</span>
                                                <?php else: ?>
                                                <span class="label label-danger">Egreso</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($m['concepto']); ?></td>
                                            <td><?php echo htmlspecialchars($m['metodo_pago']); ?></td>
                                            <td><?php echo $m['banco_nombre'] ? htmlspecialchars($m['banco_nombre']) : '-'; ?>
                                            </td>
                                            <td class="text-right">
                                                <?php echo number_format($m['monto'], 2); ?>
                                            </td>
                                            <td class="text-right">
                                                <?php echo number_format($m['saldo_caja'], 2); ?>
                                            </td>
                                            <td><?php echo $m['responsable_usuario'] ? htmlspecialchars($m['responsable_usuario']) : '-'; ?>
                                            </td>
                                            <td><?php echo $m['referencia'] ? htmlspecialchars($m['referencia']) : '-'; ?>
                                            </td>
                                        </tr>
                                        <?php
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="10" class="text-center">
                                                No hay movimientos registrados para los filtros seleccionados.
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="6" class="text-right">Total Ingresos:</th>
                                            <th class="text-right">
                                                Bs <?php echo number_format($total_ingresos, 2); ?>
                                            </th>
                                            <th colspan="3"></th>
                                        </tr>
                                        <tr>
                                            <th colspan="6" class="text-right">Total Egresos:</th>
                                            <th class="text-right">
                                                Bs <?php echo number_format($total_egresos, 2); ?>
                                            </th>
                                            <th colspan="3"></th>
                                        </tr>
                                        <tr>
                                            <th colspan="6" class="text-right">Resultado (Ingresos - Egresos):</th>
                                            <th class="text-right">
                                                Bs <?php echo number_format($total_ingresos - $total_egresos, 2); ?>
                                            </th>
                                            <th colspan="3"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div> <!-- /col-md-8 -->
            </div> <!-- /row -->

        </div> <!-- /container -->
    </div> <!-- /wrap -->

    <?php include(MODULO.'footer.php'); ?>

    <?php include(MODULO.'Tema.JS.php'); ?>
    <script type="text/javascript" src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $(document).ready(function() {
        $('#tabla_movimientos').dataTable({
            "order": [
                [0, 'desc']
            ]
        });
    });
    </script>

</body>

</html>