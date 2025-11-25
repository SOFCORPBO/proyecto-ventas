<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$idUsuario = $usuarioApp['id'];

// ====== REGISTRAR MOVIMIENTO CAJA GENERAL ======
if (isset($_POST['RegistrarMovimientoCajaGeneral'])) {

    $tipo        = ($_POST['tipo'] === 'EGRESO') ? 'EGRESO' : 'INGRESO';
    $monto       = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;
    $concepto    = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
    $metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'EFECTIVO';
    $id_banco    = !empty($_POST['id_banco']) ? intval($_POST['id_banco']) : NULL;
    $referencia  = isset($_POST['referencia']) ? trim($_POST['referencia']) : '';

    if ($monto > 0 && $concepto != '') {

        // Saldo de caja general (efectivo)
        $SaldoCajaSql = $db->SQL("
            SELECT saldo_caja 
            FROM caja_general_movimientos 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $SaldoCajaRow = $SaldoCajaSql->fetch_assoc();
        $saldo_caja_anterior = isset($SaldoCajaRow['saldo_caja']) ? floatval($SaldoCajaRow['saldo_caja']) : 0;

        // Saldo de banco (específico) si aplica
        $saldo_banco_anterior = NULL;
        if ($id_banco) {
            $SaldoBancoSql = $db->SQL("
                SELECT saldo_banco 
                FROM caja_general_movimientos 
                WHERE id_banco='{$id_banco}' 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $SaldoBancoRow = $SaldoBancoSql->fetch_assoc();
            $saldo_banco_anterior = isset($SaldoBancoRow['saldo_banco']) ? floatval($SaldoBancoRow['saldo_banco']) : 0;
        }

        // Calcular nuevos saldos
        $saldo_caja_nuevo = $saldo_caja_anterior;
        $saldo_banco_nuevo = $saldo_banco_anterior;

        if ($metodo_pago == 'EFECTIVO') {
            if ($tipo == 'INGRESO') {
                $saldo_caja_nuevo += $monto;
            } else {
                $saldo_caja_nuevo -= $monto;
            }
        } else {
            // Movimiento bancario
            if ($id_banco) {
                if ($tipo == 'INGRESO') {
                    $saldo_banco_nuevo += $monto;
                } else {
                    $saldo_banco_nuevo -= $monto;
                }
            }
        }

        $fecha = FechaActual();
        $hora  = HoraActual();

        // Insertar en caja_general_movimientos
        $db->SQL("
            INSERT INTO caja_general_movimientos (
                fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia, responsable, saldo_caja, saldo_banco
            ) VALUES (
                '{$fecha}', '{$hora}', '{$tipo}', '{$monto}', '{$concepto}', '{$metodo_pago}', " .
                ($id_banco ? $id_banco : "NULL") . ",
                ".($referencia != '' ? "'{$referencia}'" : "NULL").",
                '{$idUsuario}', '{$saldo_caja_nuevo}', ".
                ($saldo_banco_nuevo === NULL ? "NULL" : "'{$saldo_banco_nuevo}'")."
            )
        ");

        // Insertar también en banco_movimientos si es bancario (conciliación)
        if ($metodo_pago != 'EFECTIVO' && $id_banco) {
            $tipoBanco = ($tipo == 'INGRESO') ? 'INGRESO' : 'EGRESO';
            $db->SQL("
                INSERT INTO banco_movimientos (
                    id_banco, fecha, tipo, monto, concepto, id_venta
                ) VALUES (
                    '{$id_banco}', NOW(), '{$tipoBanco}', '{$monto}', 'Movimiento caja general: {$concepto}', NULL
                )
            ");
        }

        echo '
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            Movimiento registrado correctamente en Caja General.
        </div>';
    } else {
        echo '
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            Debe indicar un monto y un concepto válidos.
        </div>';
    }
}

// ====== FILTRO DE FECHAS ======
$filtro_desde = isset($_GET['desde']) ? $_GET['desde'] : '';
$filtro_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : '';
$where = "1=1";

if ($filtro_desde != '' && $filtro_hasta != '') {
    $where .= " AND fecha >= '{$filtro_desde}' AND fecha <= '{$filtro_hasta}'";
}

$MovSql = $db->SQL("
    SELECT cgm.*, u.usuario AS responsable_nombre, b.nombre AS banco_nombre
    FROM caja_general_movimientos cgm
    LEFT JOIN usuarios u ON u.id = cgm.responsable
    LEFT JOIN bancos b ON b.id = cgm.id_banco
    WHERE {$where}
    ORDER BY cgm.id DESC
");

// Saldos consolidados
$SaldoCajaSql = $db->SQL("
    SELECT saldo_caja 
    FROM caja_general_movimientos 
    ORDER BY id DESC LIMIT 1
");
$SaldoCajaRow = $SaldoCajaSql->fetch_assoc();
$SaldoCaja = isset($SaldoCajaRow['saldo_caja']) ? floatval($SaldoCajaRow['saldo_caja']) : 0;

// Saldos por banco (para conciliación)
$SaldoBancosSql = $db->SQL("
    SELECT b.id, b.nombre, 
           (SELECT saldo_banco 
            FROM caja_general_movimientos 
            WHERE id_banco = b.id 
            ORDER BY id DESC LIMIT 1) AS saldo_banco
    FROM bancos b
");
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
if($usuarioApp['id_perfil']==2){
    include (MODULO.'menu_vendedor.php');
}elseif($usuarioApp['id_perfil']==1){
    include (MODULO.'menu_admin.php');
}else{
    echo'<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
}
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header" id="banner">
                <h1>Caja General</h1>
                <p class="lead">Ingresos/Egresos mayores y conciliación bancaria</p>
            </div>

            <!-- SALDOS CONSOLIDADOS -->
            <div class="row">

                <div class="col-md-4">
                    <div class="panel panel-info">
                        <div class="panel-heading"><strong>Saldo Caja (Efectivo)</strong></div>
                        <div class="panel-body">
                            <h3>Bs <?php echo number_format($SaldoCaja,2); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Saldos por Banco -->
                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Saldos por Banco</strong></div>
                        <div class="panel-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Banco</th>
                                        <th>Saldo (Bs)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($sb = $SaldoBancosSql->fetch_assoc()): 
                                    $saldo_banco = isset($sb['saldo_banco']) ? floatval($sb['saldo_banco']) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo $sb['nombre']; ?></td>
                                        <td>Bs <?php echo number_format($saldo_banco,2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- FORM NUEVO MOVIMIENTO -->
            <div class="row">
                <div class="col-md-6">
                    <form method="post" class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Registrar Movimiento</strong>
                        </div>
                        <div class="panel-body">

                            <div class="form-group">
                                <label>Tipo</label>
                                <select name="tipo" class="form-control" required>
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
                                <input type="text" name="concepto" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Método de Pago</label>
                                <select name="metodo_pago" id="metodo_pago" class="form-control" required>
                                    <option value="EFECTIVO">Efectivo</option>
                                    <option value="TRANSFERENCIA">Transferencia</option>
                                    <option value="DEPOSITO">Depósito</option>
                                    <option value="TARJETA">Tarjeta</option>
                                </select>
                            </div>

                            <div id="grupo_banco" style="display:none;">
                                <div class="form-group">
                                    <label>Banco / Cuenta</label>
                                    <select name="id_banco" class="form-control">
                                        <option value="">Seleccione banco</option>
                                        <?php
                                    $BancosSql = $db->SQL("SELECT id, nombre, numero_cuenta FROM bancos ORDER BY nombre");
                                    while($b = $BancosSql->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $b['id']; ?>">
                                            <?php echo $b['nombre'].' - '.$b['numero_cuenta']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Referencia (voucher / operación)</label>
                                    <input type="text" name="referencia" class="form-control">
                                </div>
                            </div>

                            <button type="submit" name="RegistrarMovimientoCajaGeneral" class="btn btn-primary">
                                Guardar Movimiento
                            </button>
                        </div>
                    </form>
                </div>

                <!-- FILTRO REPORTE -->
                <div class="col-md-6">
                    <form method="get" class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Filtro de Reportes</strong>
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label>Desde (YYYY-MM-DD)</label>
                                <input type="text" name="desde" class="form-control"
                                    value="<?php echo htmlspecialchars($filtro_desde); ?>">
                            </div>
                            <div class="form-group">
                                <label>Hasta (YYYY-MM-DD)</label>
                                <input type="text" name="hasta" class="form-control"
                                    value="<?php echo htmlspecialchars($filtro_hasta); ?>">
                            </div>
                            <button type="submit" class="btn btn-default">Filtrar</button>
                            <a href="<?php echo URLBASE ?>caja-general" class="btn btn-link">Quitar filtro</a>
                            <p class="help-block">
                                • Reportes diarios/mensuales usando rango de fechas.
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABLA MOVIMIENTOS -->
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table id="tabla_caja_general" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Tipo</th>
                                    <th>Monto</th>
                                    <th>Concepto</th>
                                    <th>Método</th>
                                    <th>Banco</th>
                                    <th>Referencia</th>
                                    <th>Responsable</th>
                                    <th>Saldo Caja</th>
                                    <th>Saldo Banco</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($m = $MovSql->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $m['fecha']; ?></td>
                                    <td><?php echo $m['hora']; ?></td>
                                    <td><?php echo $m['tipo']; ?></td>
                                    <td>Bs <?php echo number_format($m['monto'],2); ?></td>
                                    <td><?php echo $m['concepto']; ?></td>
                                    <td><?php echo $m['metodo_pago']; ?></td>
                                    <td><?php echo $m['banco_nombre']; ?></td>
                                    <td><?php echo $m['referencia']; ?></td>
                                    <td><?php echo isset($m['responsable_nombre']) ? $m['responsable_nombre'] : $m['responsable']; ?>
                                    </td>
                                    <td>Bs <?php echo number_format($m['saldo_caja'],2); ?></td>
                                    <td>
                                        <?php echo $m['saldo_banco'] !== null ? 'Bs '.number_format($m['saldo_banco'],2) : '-'; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>
    <script type="text/javascript" src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>
    <script>
    $(document).ready(function() {
        $('#tabla_caja_general').dataTable({
            "scrollY": false,
            "scrollX": true
        });

        function actualizarGrupoBanco() {
            var metodo = $('#metodo_pago').val();
            if (metodo === 'EFECTIVO') {
                $('#grupo_banco').hide();
            } else {
                $('#grupo_banco').show();
            }
        }
        $('#metodo_pago').on('change', actualizarGrupoBanco);
        actualizarGrupoBanco();
    });
    </script>
</body>

</html>