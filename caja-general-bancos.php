<?php
session_start();
include('sistema/configuracion.php');
include('sistema/clase/cajas.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

if (!isset($usuarioApp)) {
    echo '<meta http-equiv="refresh" content="0;url=' . URLBASE . 'cerrar-sesion"/>';
    exit;
}

date_default_timezone_set(HORARIO);

$Cajas = new Cajas();

$mensaje = '';
$tipo_mensaje = 'info';

/* ======================================================
|   TAB ACTIVO
====================================================== */
$tab = isset($_GET['tab']) ? strtolower(trim($_GET['tab'])) : 'bancos';
if (!in_array($tab, ['bancos', 'qr'], true)) $tab = 'bancos';

/* ======================================================
|   HELPERS: SUBIR IMAGEN QR
====================================================== */
function subirImagenQR($file)
{
    if (!isset($file) || empty($file['name'])) return null;
    if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) return null;

    $maxSize = 2 * 1024 * 1024; // 2MB
    if (!empty($file['size']) && $file['size'] > $maxSize) return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $permitidos = ['png', 'jpg', 'jpeg', 'webp'];
    if (!in_array($ext, $permitidos, true)) return null;

    // Guardar en /estatico/img/qr/
    $dirAbs = __DIR__ . '/estatico/img/qr/';
    if (!is_dir($dirAbs)) {
        @mkdir($dirAbs, 0775, true);
    }

    $nombre = 'qr_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $destAbs = $dirAbs . $nombre;

    if (!move_uploaded_file($file['tmp_name'], $destAbs)) return null;

    // Guardar en DB como ruta relativa al ESTATICO
    return 'img/qr/' . $nombre;
}

/* ======================================================
|   ESTADOS FORMULARIOS
====================================================== */
$editando_banco = false;
$banco_edit = [
    'id' => '',
    'nombre' => '',
    'numero_cuenta' => '',
    'tipo_cuenta' => 'CAJA_AHORRO',
    'moneda' => 'BOB',
    'saldo_inicial' => '0.00'
];

$editando_qr = false;
$qr_edit = [
    'id' => '',
    'nombre' => '',
    'proveedor' => '',
    'moneda' => 'BOB',
    'id_banco' => '',
    'imagen_qr' => '',
    'habilitado' => 1
];

/* ======================================================
|   GUARDAR BANCO (CREAR / ACTUALIZAR)
====================================================== */
if (isset($_POST['GuardarBanco'])) {

    $tab = 'bancos';

    $id            = (int)($_POST['id'] ?? 0);
    $nombre        = $_POST['nombre'] ?? '';
    $numero_cuenta = $_POST['numero_cuenta'] ?? '';
    $tipo_cuenta   = $_POST['tipo_cuenta'] ?? 'CAJA_AHORRO';
    $moneda        = $_POST['moneda'] ?? 'BOB';
    $saldo_inicial = floatval(str_replace(",", ".", $_POST['saldo_inicial'] ?? 0));

    if (trim($nombre) === '' || trim($numero_cuenta) === '') {
        $mensaje = 'Debe completar Nombre y Número de cuenta.';
        $tipo_mensaje = 'danger';
    } else {
        if ($id > 0) {
            $ok = $Cajas->BancoActualizar($id, $nombre, $numero_cuenta, $tipo_cuenta, $moneda, $saldo_inicial);
            $mensaje = $ok ? 'Banco actualizado correctamente.' : 'No se pudo actualizar el banco.';
            $tipo_mensaje = $ok ? 'success' : 'danger';
        } else {
            $ok = $Cajas->BancoCrear($nombre, $numero_cuenta, $tipo_cuenta, $moneda, $saldo_inicial);
            $mensaje = $ok ? 'Banco creado correctamente.' : 'No se pudo crear el banco.';
            $tipo_mensaje = $ok ? 'success' : 'danger';
        }
    }
}

/* ======================================================
|   ELIMINAR BANCO (SEGURO)
====================================================== */
if (isset($_GET['eliminar_banco'])) {
    $tab = 'bancos';

    $id = (int)$_GET['eliminar_banco'];

    if ($Cajas->BancoPuedeEliminar($id)) {
        $ok = $Cajas->BancoEliminar($id);
        $mensaje = $ok ? 'Banco eliminado.' : 'No se pudo eliminar el banco.';
        $tipo_mensaje = $ok ? 'success' : 'danger';
    } else {
        $mensaje = 'No se puede eliminar: el banco tiene movimientos registrados.';
        $tipo_mensaje = 'warning';
    }
}

/* ======================================================
|   EDITAR BANCO (CARGAR FORM)
====================================================== */
if (isset($_GET['editar_banco'])) {
    $tab = 'bancos';

    $id = (int)$_GET['editar_banco'];
    $sql = $Cajas->BancoObtenerPorId($id);
    if ($sql && $sql->num_rows > 0) {
        $editando_banco = true;
        $banco_edit = $sql->fetch_assoc();
    }
}

/* ======================================================
|   GUARDAR QR (CREAR / ACTUALIZAR)
====================================================== */
if (isset($_POST['GuardarQR'])) {

    $tab = 'qr';

    $id        = (int)($_POST['id_qr'] ?? 0);
    $nombre    = $_POST['nombre_qr'] ?? '';
    $proveedor = $_POST['proveedor'] ?? '';
    $moneda    = $_POST['moneda_qr'] ?? 'BOB';
    $id_banco  = !empty($_POST['id_banco_qr']) ? (int)$_POST['id_banco_qr'] : null;
    $habilitado = isset($_POST['habilitado']) ? (int)$_POST['habilitado'] : 1;

    if (trim($nombre) === '') {
        $mensaje = 'Debe completar el nombre de la cuenta QR.';
        $tipo_mensaje = 'danger';
    } else {

        // Subir imagen si llega
        $rutaImagen = null;
        if (!empty($_FILES['imagen_qr']['name'])) {
            $rutaImagen = subirImagenQR($_FILES['imagen_qr']);
            if ($rutaImagen === null) {
                $mensaje = 'Imagen inválida. Use PNG/JPG/JPEG/WEBP (máx 2MB).';
                $tipo_mensaje = 'warning';
            }
        }

        if ($mensaje === '') {
            if ($id > 0) {
                // Si no se sube imagen, no se cambia (rutaImagen = null)
                $ok = $Cajas->QrActualizar($id, $nombre, $proveedor, $moneda, $id_banco, $rutaImagen, $habilitado);
                $mensaje = $ok ? 'QR actualizado correctamente.' : 'No se pudo actualizar el QR.';
                $tipo_mensaje = $ok ? 'success' : 'danger';
            } else {
                $ok = $Cajas->QrCrear($nombre, $proveedor, $moneda, $id_banco, $rutaImagen, $habilitado);
                $mensaje = $ok ? 'QR creado correctamente.' : 'No se pudo crear el QR.';
                $tipo_mensaje = $ok ? 'success' : 'danger';
            }
        }
    }
}

/* ======================================================
|   ELIMINAR QR (SEGURO)
====================================================== */
if (isset($_GET['eliminar_qr'])) {
    $tab = 'qr';

    $id = (int)$_GET['eliminar_qr'];

    if ($Cajas->QrPuedeEliminar($id)) {
        $ok = $Cajas->QrEliminar($id);
        $mensaje = $ok ? 'QR eliminado.' : 'No se pudo eliminar el QR.';
        $tipo_mensaje = $ok ? 'success' : 'danger';
    } else {
        $mensaje = 'No se puede eliminar: este QR ya fue usado en movimientos.';
        $tipo_mensaje = 'warning';
    }
}

/* ======================================================
|   EDITAR QR (CARGAR FORM)
====================================================== */
if (isset($_GET['editar_qr'])) {
    $tab = 'qr';

    $id = (int)$_GET['editar_qr'];
    $sql = $Cajas->QrObtenerPorId($id);
    if ($sql && $sql->num_rows > 0) {
        $editando_qr = true;
        $qr_edit = $sql->fetch_assoc();
    }
}

/* ======================================================
|   LISTADOS
====================================================== */
$BancosSQL = $Cajas->BancosListarConSaldo();
$QRSQL = $Cajas->QrListarConBanco(false);

// Para combos (bancos)
$BancosComboSQL = $db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Bancos / QR | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
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
                        <h2>Finanzas: Bancos y QR</h2>
                        <p class="text-muted">
                            Administra bancos/cuentas y cuentas QR asociadas a un banco.
                        </p>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="caja-general.php" class="btn btn-default">Volver a Caja General</a>
                    </div>
                </div>
            </div>

            <?php if ($mensaje != ''): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>

            <!-- TABS -->
            <ul class="nav nav-tabs" style="margin-bottom:15px;">
                <li class="<?php echo ($tab==='bancos') ? 'active' : ''; ?>">
                    <a href="caja-general-bancos.php?tab=bancos">Bancos</a>
                </li>
                <li class="<?php echo ($tab==='qr') ? 'active' : ''; ?>">
                    <a href="caja-general-bancos.php?tab=qr">QR</a>
                </li>
            </ul>

            <?php if ($tab === 'bancos'): ?>

            <div class="row">

                <!-- FORMULARIO BANCOS -->
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong><?php echo $editando_banco ? 'Editar Banco' : 'Nuevo Banco'; ?></strong>
                        </div>
                        <div class="panel-body">
                            <form method="post">

                                <input type="hidden" name="id" value="<?php echo $banco_edit['id'] ?? ''; ?>">

                                <div class="form-group">
                                    <label>Nombre del banco</label>
                                    <input type="text" name="nombre" class="form-control"
                                        value="<?php echo htmlspecialchars($banco_edit['nombre'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Número de cuenta</label>
                                    <input type="text" name="numero_cuenta" class="form-control"
                                        value="<?php echo htmlspecialchars($banco_edit['numero_cuenta'] ?? ''); ?>"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label>Tipo de cuenta</label>
                                    <select name="tipo_cuenta" class="form-control">
                                        <option value="CAJA_AHORRO"
                                            <?php echo (($banco_edit['tipo_cuenta'] ?? '')==='CAJA_AHORRO')?'selected':''; ?>>
                                            Caja de ahorro
                                        </option>
                                        <option value="CUENTA_CORRIENTE"
                                            <?php echo (($banco_edit['tipo_cuenta'] ?? '')==='CUENTA_CORRIENTE')?'selected':''; ?>>
                                            Cuenta corriente
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Moneda</label>
                                    <input type="text" name="moneda" class="form-control"
                                        value="<?php echo htmlspecialchars($banco_edit['moneda'] ?? 'BOB'); ?>">
                                    <p class="help-block">Ej: BOB, USD</p>
                                </div>

                                <div class="form-group">
                                    <label>Saldo inicial</label>
                                    <input type="number" name="saldo_inicial" step="0.01" min="0" class="form-control"
                                        value="<?php echo htmlspecialchars($banco_edit['saldo_inicial'] ?? '0.00'); ?>">
                                </div>

                                <button type="submit" name="GuardarBanco" class="btn btn-primary btn-block">
                                    <?php echo $editando_banco ? 'Actualizar' : 'Guardar'; ?>
                                </button>

                                <?php if ($editando_banco): ?>
                                <a href="caja-general-bancos.php?tab=bancos"
                                    class="btn btn-default btn-block">Cancelar</a>
                                <?php endif; ?>

                            </form>
                        </div>
                    </div>
                </div>

                <!-- LISTA BANCOS -->
                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Listado de bancos</strong></div>
                        <div class="panel-body">

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Banco</th>
                                            <th>N° Cuenta</th>
                                            <th>Tipo</th>
                                            <th>Moneda</th>
                                            <th class="text-right">Saldo Inicial</th>
                                            <th class="text-right">Saldo Actual</th>
                                            <th style="width:160px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($b = $BancosSQL->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $b['nombre']; ?></td>
                                            <td><?php echo $b['numero_cuenta']; ?></td>
                                            <td><?php echo $b['tipo_cuenta']; ?></td>
                                            <td><?php echo $b['moneda']; ?></td>
                                            <td class="text-right">
                                                <?php echo number_format((float)$b['saldo_inicial'],2); ?></td>
                                            <td class="text-right">
                                                <?php echo number_format((float)$b['saldo_actual'],2); ?></td>
                                            <td>
                                                <a class="btn btn-xs btn-warning"
                                                    href="caja-general-bancos.php?tab=bancos&editar_banco=<?php echo $b['id']; ?>">
                                                    Editar
                                                </a>
                                                <a class="btn btn-xs btn-danger"
                                                    href="caja-general-bancos.php?tab=bancos&eliminar_banco=<?php echo $b['id']; ?>"
                                                    onclick="return confirm('¿Eliminar banco? Solo se eliminará si NO tiene movimientos.');">
                                                    Eliminar
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <p class="text-muted" style="margin-top:10px;margin-bottom:0;">
                                Nota: si un banco ya tiene movimientos, no se permite eliminarlo (seguridad contable).
                            </p>

                        </div>
                    </div>
                </div>

            </div>

            <?php else: ?>

            <div class="row">

                <!-- FORMULARIO QR -->
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong><?php echo $editando_qr ? 'Editar QR' : 'Nuevo QR'; ?></strong>
                        </div>
                        <div class="panel-body">

                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="id_qr" value="<?php echo $qr_edit['id'] ?? ''; ?>">

                                <div class="form-group">
                                    <label>Nombre</label>
                                    <input type="text" name="nombre_qr" class="form-control"
                                        value="<?php echo htmlspecialchars($qr_edit['nombre'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Proveedor</label>
                                    <input type="text" name="proveedor" class="form-control"
                                        value="<?php echo htmlspecialchars($qr_edit['proveedor'] ?? ''); ?>">
                                    <p class="help-block">Ej: Banco Unión, Tigo Money, etc.</p>
                                </div>

                                <div class="form-group">
                                    <label>Moneda</label>
                                    <input type="text" name="moneda_qr" class="form-control"
                                        value="<?php echo htmlspecialchars($qr_edit['moneda'] ?? 'BOB'); ?>">
                                </div>

                                <div class="form-group">
                                    <label>Banco asociado</label>
                                    <select name="id_banco_qr" class="form-control">
                                        <option value="">-- Seleccione --</option>
                                        <?php
                                        $b3 = $db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");
                                        while($bb = $b3->fetch_assoc()):
                                            $sel = (!empty($qr_edit['id_banco']) && (int)$qr_edit['id_banco']==(int)$bb['id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $bb['id']; ?>" <?php echo $sel; ?>>
                                            <?php echo $bb['nombre']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <p class="help-block">El banco se usará para reportes y movimientos QR.</p>
                                </div>

                                <div class="form-group">
                                    <label>Imagen QR</label>
                                    <input type="file" name="imagen_qr" class="form-control">
                                    <?php if (!empty($qr_edit['imagen_qr'])): ?>
                                    <p style="margin-top:8px;margin-bottom:0;">
                                        <img src="<?php echo ESTATICO . $qr_edit['imagen_qr']; ?>"
                                            style="max-width:100%;border:1px solid #ddd;padding:4px;">
                                    </p>
                                    <?php endif; ?>
                                    <p class="help-block">PNG/JPG/JPEG/WEBP - Máx 2MB.</p>
                                </div>

                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="habilitado" class="form-control">
                                        <option value="1"
                                            <?php echo ((int)($qr_edit['habilitado'] ?? 1)===1)?'selected':''; ?>>
                                            Habilitado</option>
                                        <option value="0"
                                            <?php echo ((int)($qr_edit['habilitado'] ?? 1)===0)?'selected':''; ?>>
                                            Deshabilitado</option>
                                    </select>
                                </div>

                                <button type="submit" name="GuardarQR" class="btn btn-primary btn-block">
                                    <?php echo $editando_qr ? 'Actualizar' : 'Guardar'; ?>
                                </button>

                                <?php if ($editando_qr): ?>
                                <a href="caja-general-bancos.php?tab=qr" class="btn btn-default btn-block">Cancelar</a>
                                <?php endif; ?>

                            </form>

                        </div>
                    </div>
                </div>

                <!-- LISTADO QR -->
                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Listado de cuentas QR</strong></div>
                        <div class="panel-body">

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-condensed">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Proveedor</th>
                                            <th>Banco</th>
                                            <th>Moneda</th>
                                            <th>Estado</th>
                                            <th>Imagen</th>
                                            <th style="width:170px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($q = $QRSQL->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $q['nombre']; ?></td>
                                            <td><?php echo $q['proveedor'] ?: '-'; ?></td>
                                            <td><?php echo $q['banco_nombre'] ?: '-'; ?></td>
                                            <td><?php echo $q['moneda'] ?: 'BOB'; ?></td>
                                            <td>
                                                <?php echo ((int)$q['habilitado']===1)
                                                    ? "<span class='label label-success'>Activo</span>"
                                                    : "<span class='label label-default'>Inactivo</span>"; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($q['imagen_qr'])): ?>
                                                <a href="<?php echo ESTATICO . $q['imagen_qr']; ?>"
                                                    target="_blank">Ver</a>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a class="btn btn-xs btn-warning"
                                                    href="caja-general-bancos.php?tab=qr&editar_qr=<?php echo $q['id']; ?>">
                                                    Editar
                                                </a>
                                                <a class="btn btn-xs btn-danger"
                                                    href="caja-general-bancos.php?tab=qr&eliminar_qr=<?php echo $q['id']; ?>"
                                                    onclick="return confirm('¿Eliminar QR? Solo se eliminará si NO fue usado en movimientos.');">
                                                    Eliminar
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <p class="text-muted" style="margin-top:10px;margin-bottom:0;">
                                Nota: si un QR ya fue usado en Caja General, no se permitirá eliminarlo.
                            </p>

                        </div>
                    </div>
                </div>

            </div>

            <?php endif; ?>

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>
</body>

</html>