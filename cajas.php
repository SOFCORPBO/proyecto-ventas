<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
$usuario->ZonaAdministrador();

// Datos básicos
$fecha = date('Y-m-d');
$hora  = date('H:i:s');

// Intento de obtener responsable desde sesión
$idResponsable = null;
if (isset($usuarioApp['id_vendedor'])) {
    $idResponsable = (int)$usuarioApp['id_vendedor'];
} elseif (isset($usuarioApp['id_usuario'])) {
    $idResponsable = (int)$usuarioApp['id_usuario'];
}

// Función rápida para sumar saldo de movimientos
function saldoCajaGeneral($db) {
    $sql = $db->SQL("
        SELECT 
            SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END) AS saldo
        FROM caja_general_movimientos
    ");
    $row = $sql->fetch_assoc();
    return $row && $row['saldo'] !== null ? (float)$row['saldo'] : 0.0;
}

function saldoCajaChica($db) {
    $sql = $db->SQL("
        SELECT 
            SUM(CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END) AS saldo
        FROM caja_chica_movimientos
    ");
    $row = $sql->fetch_assoc();
    return $row && $row['saldo'] !== null ? (float)$row['saldo'] : 0.0;
}

/*
|------------------------------------------------------------
|   PROCESAR ACCIONES DE CAJA
|   - Apertura / Cierre Caja GENERAL
|   - Apertura / Cierre Caja CHICA
|------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['AccionCaja'])) {

    $accion = $_POST['AccionCaja'];

    // Normalizamos responsable para SQL
    $respSQL = $idResponsable !== null ? (int)$idResponsable : 'NULL';

    // === APERTURA CAJA GENERAL ===
    if ($accion === 'AperturarGeneral') {
        $monto = isset($_POST['monto_inicial']) ? (float)$_POST['monto_inicial'] : 0;

        // Evitar doble apertura
        $abierta = $db->SQL("
            SELECT id 
            FROM caja 
            WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1
            LIMIT 1
        ");

        if ($abierta->num_rows == 0) {
            // Registrar en tabla caja
            $db->SQL("
                INSERT INTO caja (monto, fecha, hora, estado, habilitado, tipo_caja, responsable, observacion)
                VALUES ('{$monto}','{$fecha}','{$hora}',1,1,'GENERAL',{$respSQL},'Apertura de caja general')
            ");

            // Registrar movimiento inicial
            $db->SQL("
                INSERT INTO caja_general_movimientos
                    (fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia, responsable, saldo_caja, saldo_banco)
                VALUES 
                    ('{$fecha}','{$hora}','INGRESO',{$monto},
                     'Apertura de caja general',
                     'EFECTIVO', NULL, 'APERTURA', {$respSQL}, {$monto}, NULL)
            ");
        }

    // === CIERRE CAJA GENERAL ===
    } elseif ($accion === 'CerrarGeneral') {
        $montoCierre = isset($_POST['monto_cierre']) ? (float)$_POST['monto_cierre'] : 0;

        $cajaRes = $db->SQL("
            SELECT id 
            FROM caja 
            WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($cajaRes->num_rows > 0) {
            $caja = $cajaRes->fetch_assoc();

            // Actualizar estado de caja
            $db->SQL("
                UPDATE caja 
                SET estado=0, observacion='Cierre de caja general'
                WHERE id = {$caja['id']}
            ");

            // Movimiento de cierre (egreso para dejar saldo 0 en caja física)
            $db->SQL("
                INSERT INTO caja_general_movimientos
                    (fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia, responsable, saldo_caja, saldo_banco)
                VALUES 
                    ('{$fecha}','{$hora}','EGRESO',{$montoCierre},
                     'Cierre de caja general',
                     'EFECTIVO', NULL, 'CIERRE', {$respSQL}, 0, NULL)
            ");
        }

    // === APERTURA CAJA CHICA ===
    } elseif ($accion === 'AperturarChica') {
        $monto = isset($_POST['monto_inicial_chica']) ? (float)$_POST['monto_inicial_chica'] : 0;

        // Evitar doble apertura
        $abierta = $db->SQL("
            SELECT id 
            FROM cajachica 
            WHERE tipo=1 AND habilitado=1
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($abierta->num_rows == 0) {
            // Registrar apertura en cajachica
            $db->SQL("
                INSERT INTO cajachica (monto, fecha, hora, tipo, responsable, observacion, habilitado)
                VALUES ('{$monto}','{$fecha}','{$hora}',1,{$respSQL},'Apertura de caja chica',1)
            ");

            // Movimiento en caja_chica_movimientos
            $db->SQL("
                INSERT INTO caja_chica_movimientos
                    (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
                VALUES 
                    ('{$fecha}','{$hora}','INGRESO',{$monto},
                     'Apertura de caja chica',{$respSQL},{$monto},'APERTURA')
            ");
        }

    // === CIERRE CAJA CHICA ===
    } elseif ($accion === 'CerrarChica') {
        $montoCierre = isset($_POST['monto_cierre_chica']) ? (float)$_POST['monto_cierre_chica'] : 0;

        $cajaRes = $db->SQL("
            SELECT id 
            FROM cajachica 
            WHERE tipo=1 AND habilitado=1
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($cajaRes->num_rows > 0) {
            $caja = $cajaRes->fetch_assoc();

            // Marcar como cerrada (habilitado=0)
            $db->SQL("
                UPDATE cajachica 
                SET habilitado=0, observacion='Cierre de caja chica'
                WHERE id = {$caja['id']}
            ");

            // Movimiento de cierre (EGRESO para dejar saldo 0)
            $db->SQL("
                INSERT INTO caja_chica_movimientos
                    (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
                VALUES 
                    ('{$fecha}','{$hora}','EGRESO',{$montoCierre},
                     'Cierre de caja chica',{$respSQL},0,'CIERRE')
            ");
        }
    }

    // Redirección pequeña para evitar re-envío de formulario
    header("Location: cajas.php");
    exit;
}

/*
|------------------------------------------------------------
|   CONSULTAS PARA MOSTRAR ESTADO ACTUAL
|------------------------------------------------------------
*/

// Caja General (último registro)
$cajaGeneralRes = $db->SQL("
    SELECT * 
    FROM caja 
    WHERE tipo_caja='GENERAL' 
    ORDER BY id DESC
    LIMIT 1
");
$cajaGeneral = $cajaGeneralRes->num_rows ? $cajaGeneralRes->fetch_assoc() : null;
$saldoGeneral = saldoCajaGeneral($db);

// Caja Chica (último registro)
$cajaChicaRes = $db->SQL("
    SELECT * 
    FROM cajachica 
    ORDER BY id DESC
    LIMIT 1
");
$cajaChica = $cajaChicaRes->num_rows ? $cajaChicaRes->fetch_assoc() : null;
$saldoChica = saldoCajaChica($db);

// Obtener nombre del responsable (si existe) para mostrar en tarjetas
function nombreResponsable($db, $id) {
    if (!$id) return 'No asignado';
    $sql = $db->SQL("
        SELECT nombre, apellido1, apellido2
        FROM vendedores
        WHERE id = ".(int)$id."
        LIMIT 1
    ");
    if ($sql->num_rows == 0) return 'No asignado';

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
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO; ?>css/bootstrap.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
// Menú
if ($usuarioApp['id_perfil'] == 2) {
    include(MODULO.'menu_vendedor.php');
} elseif ($usuarioApp['id_perfil'] == 1) {
    include(MODULO.'menu_admin.php');
}
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header" id="banner">
                <div class="row">
                    <div class="col-lg-8 col-md-7 col-sm-6">
                        <h1>Cajas del Sistema</h1>
                        <p class="lead">Gestión de Caja General y Caja Chica</p>
                    </div>
                </div>
            </div>

            <div class="row">

                <!-- CAJA GENERAL -->
                <div class="col-md-6">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <strong>Caja General</strong>
                        </div>
                        <div class="panel-body">
                            <p>
                                Estado:
                                <?php if ($cajaGeneral && $cajaGeneral['estado'] == 1): ?>
                                <span class="label label-success">Abierta</span>
                                <?php else: ?>
                                <span class="label label-danger">Cerrada</span>
                                <?php endif; ?>
                            </p>

                            <p><strong>Saldo actual:</strong> Bs <?php echo number_format($saldoGeneral, 2); ?></p>

                            <?php if ($cajaGeneral): ?>
                            <p><strong>Monto Apertura:</strong> Bs
                                <?php echo number_format($cajaGeneral['monto'], 2); ?></p>
                            <p><strong>Fecha/Hora Apertura:</strong>
                                <?php echo $cajaGeneral['fecha'].' '.$cajaGeneral['hora']; ?></p>
                            <p><strong>Responsable:</strong>
                                <?php echo nombreResponsable($db, $cajaGeneral['responsable']); ?></p>
                            <?php else: ?>
                            <p>No hay registros de Caja General.</p>
                            <?php endif; ?>

                            <hr>

                            <?php if (!$cajaGeneral || $cajaGeneral['estado'] == 0): ?>
                            <!-- Formulario de Apertura Caja General -->
                            <form method="post" class="form-inline">
                                <input type="hidden" name="AccionCaja" value="AperturarGeneral">
                                <div class="form-group">
                                    <label for="monto_inicial">Monto inicial:&nbsp;</label>
                                    <input type="number" step="0.01" min="0" name="monto_inicial" id="monto_inicial"
                                        class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="glyphicon glyphicon-ok"></i> Aperturar Caja General
                                </button>
                            </form>
                            <?php else: ?>
                            <!-- Formulario de Cierre Caja General -->
                            <form method="post" class="form-inline">
                                <input type="hidden" name="AccionCaja" value="CerrarGeneral">
                                <div class="form-group">
                                    <label for="monto_cierre">Monto cierre (efectivo actual):&nbsp;</label>
                                    <input type="number" step="0.01" min="0" name="monto_cierre" id="monto_cierre"
                                        class="form-control" required
                                        value="<?php echo number_format($saldoGeneral, 2, '.', ''); ?>">
                                </div>
                                <button type="submit" class="btn btn-danger">
                                    <i class="glyphicon glyphicon-remove"></i> Cerrar Caja General
                                </button>
                            </form>
                            <?php endif; ?>

                            <hr>
                            <a href="<?php echo URLBASE; ?>caja-general" class="btn btn-default">
                                Ver detalle movimientos Caja General
                            </a>
                        </div>
                    </div>
                </div>

                <!-- CAJA CHICA -->
                <div class="col-md-6">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <strong>Caja Chica</strong>
                        </div>
                        <div class="panel-body">
                            <p>
                                Estado:
                                <?php if ($cajaChica && $cajaChica['habilitado'] == 1): ?>
                                <span class="label label-success">Abierta</span>
                                <?php else: ?>
                                <span class="label label-danger">Cerrada</span>
                                <?php endif; ?>
                            </p>

                            <p><strong>Saldo actual:</strong> Bs <?php echo number_format($saldoChica, 2); ?></p>

                            <?php if ($cajaChica): ?>
                            <p><strong>Monto Apertura:</strong> Bs <?php echo number_format($cajaChica['monto'], 2); ?>
                            </p>
                            <p><strong>Fecha/Hora Apertura:</strong>
                                <?php echo $cajaChica['fecha'].' '.$cajaChica['hora']; ?></p>
                            <p><strong>Responsable:</strong>
                                <?php echo nombreResponsable($db, $cajaChica['responsable']); ?></p>
                            <?php else: ?>
                            <p>No hay registros de Caja Chica.</p>
                            <?php endif; ?>

                            <hr>

                            <?php if (!$cajaChica || $cajaChica['habilitado'] == 0): ?>
                            <!-- Apertura Caja Chica -->
                            <form method="post" class="form-inline">
                                <input type="hidden" name="AccionCaja" value="AperturarChica">
                                <div class="form-group">
                                    <label for="monto_inicial_chica">Monto inicial:&nbsp;</label>
                                    <input type="number" step="0.01" min="0" name="monto_inicial_chica"
                                        id="monto_inicial_chica" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="glyphicon glyphicon-ok"></i> Aperturar Caja Chica
                                </button>
                            </form>
                            <?php else: ?>
                            <!-- Cierre Caja Chica -->
                            <form method="post" class="form-inline">
                                <input type="hidden" name="AccionCaja" value="CerrarChica">
                                <div class="form-group">
                                    <label for="monto_cierre_chica">Monto cierre:&nbsp;</label>
                                    <input type="number" step="0.01" min="0" name="monto_cierre_chica"
                                        id="monto_cierre_chica" class="form-control" required
                                        value="<?php echo number_format($saldoChica, 2, '.', ''); ?>">
                                </div>
                                <button type="submit" class="btn btn-danger">
                                    <i class="glyphicon glyphicon-remove"></i> Cerrar Caja Chica
                                </button>
                            </form>
                            <?php endif; ?>

                            <hr>
                            <a href="<?php echo URLBASE; ?>caja-chica" class="btn btn-default">
                                Ver detalle movimientos Caja Chica
                            </a>
                        </div>
                    </div>
                </div>

            </div><!-- /.row -->

        </div><!-- /.container -->
    </div><!-- /#wrap -->

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

</body>

</html>