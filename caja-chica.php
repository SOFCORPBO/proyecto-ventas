<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Usuario responsable
$idUsuario = $usuarioApp['id'];

// ====== REGISTRAR MOVIMIENTO ======
if (isset($_POST['RegistrarMovimientoCajaChica'])) {

    $tipo      = ($_POST['tipo'] === 'EGRESO') ? 'EGRESO' : 'INGRESO';
    $monto     = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;
    $concepto  = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
    $referencia= isset($_POST['referencia']) ? trim($_POST['referencia']) : '';

    if ($monto > 0 && $concepto != '') {

        // Saldo anterior (si no hay registros, asumimos 0)
        $SaldoSql = $db->SQL("
            SELECT saldo_resultante 
            FROM caja_chica_movimientos 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $SaldoRow = $SaldoSql->fetch_assoc();
        $saldo_anterior = isset($SaldoRow['saldo_resultante']) ? floatval($SaldoRow['saldo_resultante']) : 0;

        // Nuevo saldo
        if ($tipo == 'INGRESO') {
            $saldo_nuevo = $saldo_anterior + $monto;
        } else {
            $saldo_nuevo = $saldo_anterior - $monto;
        }

        $fecha = FechaActual();
        $hora  = HoraActual();

        $db->SQL("
            INSERT INTO caja_chica_movimientos (
                fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia
            ) VALUES (
                '{$fecha}', '{$hora}', '{$tipo}', '{$monto}', '{$concepto}', '{$idUsuario}', '{$saldo_nuevo}', " .
                ($referencia != '' ? "'{$referencia}'" : "NULL") . "
            )
        ");

        echo '
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            Movimiento registrado correctamente en Caja Chica.
        </div>';
    } else {
        echo '
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            Debe indicar un monto y un concepto válidos.
        </div>';
    }
}

// ====== FILTRO DE FECHAS (simple: diario / rango) ======
$filtro_desde = isset($_GET['desde']) ? $_GET['desde'] : '';
$filtro_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : '';

$where = "1=1";

if ($filtro_desde != '' && $filtro_hasta != '') {
    $where .= " AND fecha >= '{$filtro_desde}' AND fecha <= '{$filtro_hasta}'";
}

// Movimientos
$MovSql = $db->SQL("
    SELECT cm.*, v.usuario AS responsable_nombre
    FROM caja_chica_movimientos cm
    LEFT JOIN usuarios v ON v.id = cm.responsable
    WHERE {$where}
    ORDER BY cm.id DESC
");

// Saldo actual
$SaldoActualSql = $db->SQL("
    SELECT saldo_resultante 
    FROM caja_chica_movimientos 
    ORDER BY id DESC 
    LIMIT 1
");
$SaldoActualRow = $SaldoActualSql->fetch_assoc();
$SaldoActual = isset($SaldoActualRow['saldo_resultante']) ? floatval($SaldoActualRow['saldo_resultante']) : 0;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Caja Chica | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
// Menú
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
                <h1>Caja Chica</h1>
                <p class="lead">Registro de ingresos y egresos menores</p>
            </div>

            <!-- SALDO ACTUAL -->
            <div class="row">
                <div class="col-md-4">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <strong>Saldo Actual Caja Chica</strong>
                        </div>
                        <div class="panel-body">
                            <h3>Bs <?php echo number_format($SaldoActual, 2); ?></h3>
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
                                <label>Tipo de Movimiento</label>
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
                                <label>Referencia (opcional)</label>
                                <input type="text" name="referencia" class="form-control">
                            </div>

                            <button type="submit" name="RegistrarMovimientoCajaChica" class="btn btn-primary">
                                Guardar Movimiento
                            </button>
                        </div>
                    </form>
                </div>

                <!-- FILTRO POR FECHAS -->
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
                            <a href="<?php echo URLBASE ?>caja-chica" class="btn btn-link">Quitar filtro</a>
                            <p class="help-block">
                                • Para reporte diario: usa la misma fecha en Desde y Hasta.<br>
                                • Para reporte mensual: usa el primer y último día del mes.
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABLA MOVIMIENTOS -->
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table id="tabla_caja_chica" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Tipo</th>
                                    <th>Monto</th>
                                    <th>Concepto</th>
                                    <th>Referencia</th>
                                    <th>Responsable</th>
                                    <th>Saldo Resultante</th>
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
                                    <td><?php echo $m['referencia']; ?></td>
                                    <td><?php echo isset($m['responsable_nombre']) ? $m['responsable_nombre'] : $m['responsable']; ?>
                                    </td>
                                    <td>Bs <?php echo number_format($m['saldo_resultante'],2); ?></td>
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
        $('#tabla_caja_chica').dataTable({
            "scrollY": false,
            "scrollX": true
        });
    });
    </script>
</body>

</html>