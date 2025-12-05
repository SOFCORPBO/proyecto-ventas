<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
$usuario->ZonaAdministrador();

// Fecha y hora actuales
$fecha = date('Y-m-d');
$hora  = date('H:i:s');

// Obtener responsable
$idResponsable = null;
if (isset($usuarioApp['id_vendedor'])) {
    $idResponsable = (int)$usuarioApp['id_vendedor'];
} elseif (isset($usuarioApp['id_usuario'])) {
    $idResponsable = (int)$usuarioApp['id_usuario'];
}

/* ==================== FUNCIONES DE SALDO ==================== */
function saldoCajaGeneral($db) {
    $sql = $db->SQL("
        SELECT SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END) AS saldo
        FROM caja_general_movimientos
    ");
    $row = $sql->fetch_assoc();
    return $row && $row['saldo'] !== null ? (float)$row['saldo'] : 0.0;
}

function saldoCajaChica($db) {
    $sql = $db->SQL("
        SELECT SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END) AS saldo
        FROM caja_chica_movimientos
    ");
    $row = $sql->fetch_assoc();
    return $row && $row['saldo'] !== null ? (float)$row['saldo'] : 0.0;
}

/* ============================================================
   MANEJO DE ACCIONES: Apertura / Cierre CAJA GENERAL / CHICA
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['AccionCaja'])) {

    $accion = $_POST['AccionCaja'];
    $respSQL = ($idResponsable !== null) ? (int)$idResponsable : 'NULL';

    /* ---------- APERTURA CAJA GENERAL ---------- */
    if ($accion === 'AperturarGeneral') {

        $monto = floatval($_POST['monto_inicial']);

        $abierta = $db->SQL("
            SELECT id FROM caja 
            WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1
        ");

        if ($abierta->num_rows == 0) {

            $db->SQL("
                INSERT INTO caja (monto, fecha, hora, estado, habilitado, tipo_caja, responsable, observacion)
                VALUES ('{$monto}','{$fecha}','{$hora}',1,1,'GENERAL',$respSQL,'Apertura de caja general')
            ");

            $db->SQL("
                INSERT INTO caja_general_movimientos
                (fecha, hora, tipo, monto, concepto, metodo_pago, referencia, responsable, saldo_caja)
                VALUES 
                ('{$fecha}','{$hora}','INGRESO',{$monto},'Apertura de caja general',
                'EFECTIVO','APERTURA',$respSQL,{$monto})
            ");
        }

    /* ---------- CIERRE CAJA GENERAL ---------- */
    } elseif ($accion === 'CerrarGeneral') {

        $montoCierre = floatval($_POST['monto_cierre']);

        $cajaRes = $db->SQL("
            SELECT id FROM caja 
            WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1
            ORDER BY id DESC LIMIT 1
        ");

        if ($cajaRes->num_rows > 0) {

            $caja = $cajaRes->fetch_assoc();

            $db->SQL("UPDATE caja SET estado=0, observacion='Cierre de caja general' WHERE id={$caja['id']}");

            $db->SQL("
                INSERT INTO caja_general_movimientos
                (fecha, hora, tipo, monto, concepto, metodo_pago, referencia, responsable, saldo_caja)
                VALUES 
                ('{$fecha}','{$hora}','EGRESO',{$montoCierre},'Cierre de caja general',
                'EFECTIVO','CIERRE',$respSQL,0)
            ");
        }

    /* ---------- APERTURA CAJA CHICA ---------- */
    } elseif ($accion === 'AperturarChica') {

        $monto = floatval($_POST['monto_inicial_chica']);

        $abierta = $db->SQL("SELECT id FROM cajachica WHERE tipo=1 AND habilitado=1");

        if ($abierta->num_rows == 0) {

            $db->SQL("
                INSERT INTO cajachica (monto, fecha, hora, tipo, responsable, observacion, habilitado)
                VALUES ('{$monto}','{$fecha}','{$hora}',1,$respSQL,'Apertura de caja chica',1)
            ");

            $db->SQL("
                INSERT INTO caja_chica_movimientos
                (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
                VALUES
                ('{$fecha}','{$hora}','INGRESO',$monto,'Apertura de caja chica',$respSQL,$monto,'APERTURA')
            ");
        }

    /* ---------- CIERRE CAJA CHICA ---------- */
    } elseif ($accion === 'CerrarChica') {

        $montoCierre = floatval($_POST['monto_cierre_chica']);

        $cajaRes = $db->SQL("
            SELECT id FROM cajachica WHERE tipo=1 AND habilitado=1 ORDER BY id DESC LIMIT 1
        ");

        if ($cajaRes->num_rows > 0) {

            $caja = $cajaRes->fetch_assoc();

            $db->SQL("UPDATE cajachica SET habilitado=0, observacion='Cierre de caja chica' WHERE id={$caja['id']}");

            $db->SQL("
                INSERT INTO caja_chica_movimientos
                (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
                VALUES
                ('{$fecha}','{$hora}','EGRESO',$montoCierre,'Cierre de caja chica',$respSQL,0,'CIERRE')
            ");
        }
    }

    header("Location: cajas.php");
    exit;
}

/* ============================================================
   CONSULTAS PARA VISUALIZAR
   ============================================================ */
$cajaGeneralRes = $db->SQL("SELECT * FROM caja WHERE tipo_caja='GENERAL' ORDER BY id DESC LIMIT 1");
$cajaGeneral = $cajaGeneralRes->num_rows ? $cajaGeneralRes->fetch_assoc() : null;
$saldoGeneral = saldoCajaGeneral($db);

$cajaChicaRes = $db->SQL("SELECT * FROM cajachica ORDER BY id DESC LIMIT 1");
$cajaChica = $cajaChicaRes->num_rows ? $cajaChicaRes->fetch_assoc() : null;
$saldoChica = saldoCajaChica($db);

function nombreResponsable($db, $id) {
    if (!$id) return 'No asignado';
    $sql = $db->SQL("SELECT nombre, apellido1, apellido2 FROM vendedores WHERE id=$id LIMIT 1");
    if (!$sql || $sql->num_rows == 0) return 'No asignado';
    $v = $sql->fetch_assoc();
    return trim($v['nombre'].' '.$v['apellido1'].' '.$v['apellido2']);
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Cajas del Sistema | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO; ?>img/favicon.ico">

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
    </style>

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
                <h1>Cajas del Sistema</h1>
                <p class="lead">Gestión de Caja General y Caja Chica</p>
            </div>

            <div class="row">

                <!-- =============== CAJA GENERAL =============== -->
                <div class="col-md-6 caja-col">
                    <div class="panel panel-primary">
                        <div class="panel-heading"><strong>Caja General</strong></div>

                        <div class="panel-body">

                            <p>Estado:
                                <?php echo ($cajaGeneral && $cajaGeneral['estado']==1)
                                ? '<span class="label label-success">Abierta</span>'
                                : '<span class="label label-danger">Cerrada</span>'; ?>
                            </p>

                            <p><strong>Saldo actual:</strong> Bs <?php echo number_format($saldoGeneral, 2); ?></p>

                            <?php if ($cajaGeneral): ?>
                            <p><strong>Monto Apertura:</strong> Bs
                                <?php echo number_format($cajaGeneral['monto'], 2); ?></p>
                            <p><strong>Fecha/Hora Apertura:</strong>
                                <?php echo $cajaGeneral['fecha'].' '.$cajaGeneral['hora']; ?></p>
                            <p><strong>Responsable:</strong>
                                <?php echo nombreResponsable($db, $cajaGeneral['responsable']); ?></p>
                            <?php endif; ?>

                            <hr>

                            <!-- APERTURA / CIERRE -->
                            <?php if (!$cajaGeneral || $cajaGeneral['estado']==0): ?>

                            <form method="post" class="form-inline">
                                <input type="hidden" name="AccionCaja" value="AperturarGeneral">
                                <div class="form-group">
                                    <label>Monto inicial:</label>
                                    <input type="number" step="0.01" min="0" name="monto_inicial" class="form-control"
                                        required>
                                </div>
                                <button class="btn btn-success"><i class="glyphicon glyphicon-ok"></i>
                                    Aperturar</button>
                            </form>

                            <?php else: ?>

                            <form method="post" class="form-inline">
                                <input type="hidden" name="AccionCaja" value="CerrarGeneral">
                                <div class="form-group">
                                    <label>Monto cierre:</label>
                                    <input type="number" step="0.01" min="0" name="monto_cierre"
                                        value="<?php echo number_format($saldoGeneral,2,'.',''); ?>"
                                        class="form-control" required>
                                </div>
                                <button class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i>
                                    Cerrar</button>
                            </form>

                            <?php endif; ?>

                            <hr>

                            <!-- MOVIMIENTOS -->
                            <h4>Movimientos de Caja General</h4>

                            <div class="tabla-mov">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Tipo</th>
                                            <th>Método</th>
                                            <th>Monto</th>
                                            <th>Concepto</th>
                                            <th>Banco</th>
                                            <th>Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php
                                $movSQL = $db->SQL("
                                    SELECT * FROM caja_general_movimientos ORDER BY id DESC
                                ");

                                if ($movSQL->num_rows > 0):
                                    while ($m = $movSQL->fetch_assoc()):
                                ?>
                                        <tr>
                                            <td><?php echo $m['id']; ?></td>
                                            <td><?php echo $m['fecha']; ?></td>
                                            <td><?php echo $m['hora']; ?></td>
                                            <td><?php echo ($m['tipo']=='INGRESO')
                                        ? '<span class="text-success">Ingreso</span>'
                                        : '<span class="text-danger">Egreso</span>'; ?></td>
                                            <td><?php echo $m['metodo_pago']; ?></td>
                                            <td><strong><?php echo number_format($m['monto'],2); ?></strong></td>
                                            <td><?php echo $m['concepto']; ?></td>
                                            <td><?php echo $m['id_banco'] ? "Banco #".$m['id_banco'] : '-'; ?></td>
                                            <td><strong><?php echo number_format($m['saldo_caja'],2); ?></strong></td>
                                        </tr>
                                        <?php endwhile; else: ?>
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

                <!-- =============== CAJA CHICA =============== -->
                <div class="col-md-6 caja-col">
                    <div class="panel panel-warning">
                        <div class="panel-heading"><strong>Caja Chica</strong></div>

                        <div class="panel-body">

                            <p>Estado:
                                <?php echo ($cajaChica && $cajaChica['habilitado']==1)
                                ? '<span class="label label-success">Abierta</span>'
                                : '<span class="label label-danger">Cerrada</span>'; ?>
                            </p>

                            <p><strong>Saldo actual:</strong> Bs <?php echo number_format($saldoChica, 2); ?></p>

                            <?php if ($cajaChica): ?>
                            <p><strong>Monto Apertura:</strong> Bs <?php echo number_format($cajaChica['monto'], 2); ?>
                            </p>
                            <p><strong>Fecha/Hora Apertura:</strong>
                                <?php echo $cajaChica['fecha'].' '.$cajaChica['hora']; ?></p>
                            <p><strong>Responsable:</strong>
                                <?php echo nombreResponsable($db, $cajaChica['responsable']); ?></p>
                            <?php endif; ?>

                            <hr>

                            <?php if (!$cajaChica || $cajaChica['habilitado']==0): ?>

                            <form method="post" class="form-inline">
                                <input type="hidden" name="AccionCaja" value="AperturarChica">
                                <div class="form-group">
                                    <label>Monto inicial:</label>
                                    <input type="number" min="0" step="0.01" name="monto_inicial_chica"
                                        class="form-control" required>
                                </div>
                                <button class="btn btn-success"><i class="glyphicon glyphicon-ok"></i>
                                    Aperturar</button>
                            </form>

                            <?php else: ?>

                            <form method="post" class="form-inline">
                                <input type="hidden" name="AccionCaja" value="CerrarChica">
                                <div class="form-group">
                                    <label>Monto cierre:</label>
                                    <input type="number" step="0.01" min="0" name="monto_cierre_chica"
                                        value="<?php echo number_format($saldoChica,2,'.',''); ?>" class="form-control"
                                        required>
                                </div>
                                <button class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i>
                                    Cerrar</button>
                            </form>

                            <?php endif; ?>

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

</body>

</html>