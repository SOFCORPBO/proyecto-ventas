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
|   RESPONSABLE
====================================================== */
$idResponsable = !empty($usuarioApp['id_vendedor'])
    ? (int)$usuarioApp['id_vendedor']
    : (int)$usuarioApp['id'];

/* ======================================================
|   REGISTRAR MOVIMIENTO EN CAJA CHICA
====================================================== */
if (isset($_POST['RegistrarMovimientoCajaChica'])) {

    $tipo      = ($_POST['tipo'] == 'EGRESO') ? 'EGRESO' : 'INGRESO';
    $monto     = floatval($_POST['monto']);
    $concepto  = trim($_POST['concepto']);
    $referencia= trim($_POST['referencia']);

    if ($monto <= 0 || $concepto == '') {
        $mensaje = "Debe llenar monto y concepto correctamente.";
        $tipo_mensaje = "danger";
    } else {

        /* Obtener saldo anterior */
        $SaldoSQL = $db->SQL("SELECT saldo_resultante FROM caja_chica_movimientos ORDER BY id DESC LIMIT 1");
        $saldoAnterior = ($SaldoSQL->num_rows > 0)
            ? floatval($SaldoSQL->fetch_assoc()['saldo_resultante'])
            : 0;

        /* Nuevo saldo */
        $saldoNuevo = ($tipo == 'INGRESO')
            ? $saldoAnterior + $monto
            : $saldoAnterior - $monto;

        $fecha = date("Y-m-d");
        $hora  = date("H:i:s");

        /* Insertar movimiento */
        $db->SQL("
            INSERT INTO caja_chica_movimientos
            (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
            VALUES
            (
                '{$fecha}', '{$hora}', '{$tipo}', '{$monto}', '".addslashes($concepto)."',
                '{$idResponsable}', '{$saldoNuevo}',
                ".($referencia ? "'".addslashes($referencia)."'" : "NULL")."
            )
        ");

        $mensaje = "Movimiento registrado correctamente.";
        $tipo_mensaje = "success";
    }
}

/* ======================================================
|   FILTROS
====================================================== */
$filtro_desde = $_GET['desde'] ?? '';
$filtro_hasta = $_GET['hasta'] ?? '';

$where = "1=1";

if ($filtro_desde != '' && $filtro_hasta != '') {
    $where .= " AND fecha >= '{$filtro_desde}' AND fecha <= '{$filtro_hasta}'";
}

/* ======================================================
|   CONSULTA PRINCIPAL
====================================================== */
$MovSQL = $db->SQL("
    SELECT 
        cm.*, 
        u.usuario AS responsable_nombre
    FROM caja_chica_movimientos cm
    LEFT JOIN usuario u ON u.id = cm.responsable
    WHERE {$where}
    ORDER BY cm.id DESC
");

/* SALDO ACTUAL */
$SaldoActualSQL = $db->SQL("SELECT saldo_resultante FROM caja_chica_movimientos ORDER BY id DESC LIMIT 1");
$SaldoActual = ($SaldoActualSQL->num_rows > 0)
    ? floatval($SaldoActualSQL->fetch_assoc()['saldo_resultante'])
    : 0;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Caja Chica | <?php echo TITULO; ?></title>
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
                <h1>Caja Chica</h1>
                <p class="text-muted">Ingresos y egresos menores</p>
            </div>

            <?php if ($mensaje != ''): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>

            <!-- SALDO ACTUAL -->
            <div class="row">
                <div class="col-md-4">
                    <div class="panel panel-info">
                        <div class="panel-heading"><strong>Saldo Actual</strong></div>
                        <div class="panel-body">
                            <h3>Bs <?php echo number_format($SaldoActual,2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">

                <!-- FORMULARIO MOVIMIENTO -->
                <div class="col-md-6">
                    <form method="post" class="panel panel-default">
                        <div class="panel-heading"><strong>Registrar Movimiento</strong></div>
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
                                <input type="number" name="monto" step="0.01" min="0" class="form-control" required>
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

                <!-- FILTRO -->
                <div class="col-md-6">
                    <form method="get" class="panel panel-default">
                        <div class="panel-heading"><strong>Filtro de Reportes</strong></div>
                        <div class="panel-body">

                            <div class="form-group">
                                <label>Desde</label>
                                <input type="date" name="desde" class="form-control"
                                    value="<?php echo $filtro_desde; ?>">
                            </div>

                            <div class="form-group">
                                <label>Hasta</label>
                                <input type="date" name="hasta" class="form-control"
                                    value="<?php echo $filtro_hasta; ?>">
                            </div>

                            <button type="submit" class="btn btn-default">Filtrar</button>
                            <a href="<?php echo URLBASE ?>caja-chica" class="btn btn-link">Quitar filtro</a>

                        </div>
                    </form>
                </div>
            </div>

            <!-- TABLA DE MOVIMIENTOS -->
            <div class="row">
                <div class="col-md-12">

                    <div class="table-responsive">
                        <table id="tabla_caja_chica" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Tipo</th>
                                    <th>Monto</th>
                                    <th>Concepto</th>
                                    <th>Referencia</th>
                                    <th>Responsable</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($m = $MovSQL->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $m['fecha']; ?></td>
                                    <td><?php echo $m['hora']; ?></td>
                                    <td>
                                        <?php echo $m['tipo']=='INGRESO'
                                    ? "<span class='label label-success'>Ingreso</span>"
                                    : "<span class='label label-danger'>Egreso</span>"; ?>
                                    </td>
                                    <td>Bs <?php echo number_format($m['monto'],2); ?></td>
                                    <td><?php echo $m['concepto']; ?></td>
                                    <td><?php echo $m['referencia'] ?: '-'; ?></td>
                                    <td><?php echo $m['responsable_nombre'] ?: $m['responsable']; ?></td>
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

    <script src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $(document).ready(function() {
        $('#tabla_caja_chica').dataTable({
            "order": [
                [0, 'desc']
            ],
            "scrollX": true
        });
    });
    </script>

</body>

</html>