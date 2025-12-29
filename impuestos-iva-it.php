<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
$usuario->ZonaAdministrador();

date_default_timezone_set(HORARIO);

$mensaje = '';
$tipo_mensaje = 'info';

function esc($v){ return addslashes(trim((string)$v)); }

if (isset($_POST['GuardarIVA'])) {
    $porcentaje = (float)($_POST['porcentaje'] ?? 0);
    if ($porcentaje <= 0 || $porcentaje > 100) {
        $mensaje = 'Porcentaje IVA inválido.';
        $tipo_mensaje = 'danger';
    } else {
        $fecha = date('Y-m-d');
        $db->SQL("INSERT INTO iva (porcentaje, fecha) VALUES ('{$porcentaje}','{$fecha}')");
        $mensaje = 'IVA actualizado correctamente.';
        $tipo_mensaje = 'success';
    }
}

// IVA actual (último)
$iva_actual = 0;
$iva_fecha  = '';
$IVA = $db->SQL("SELECT porcentaje, fecha FROM iva ORDER BY id DESC LIMIT 1");
if ($IVA && $IVA->num_rows) {
    $r = $IVA->fetch_assoc();
    $iva_actual = (float)$r['porcentaje'];
    $iva_fecha  = $r['fecha'];
}

// IT: por ahora valor referencia (si lo quieres persistente, lo guardamos en tabla)
$it_ref = 3.0; // ajusta a tu porcentaje real

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>IVA / IT | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==2) include(MODULO.'menu_vendedor.php');
elseif ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header">
                <h1>IVA / IT</h1>
                <p class="lead">Parámetros tributarios (referencia para cálculos del sistema)</p>
            </div>

            <?php if($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-primary">
                        <div class="panel-heading"><strong>IVA (tabla iva)</strong></div>
                        <div class="panel-body">
                            <p><strong>IVA actual:</strong> <?php echo number_format($iva_actual,2); ?>%</p>
                            <p class="text-muted">Fecha: <?php echo $iva_fecha ?: '-'; ?></p>

                            <form method="post" class="form-horizontal" style="margin-top:10px;">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Nuevo IVA (%)</label>
                                    <div class="col-sm-8">
                                        <input type="number" step="0.01" min="0" max="100" name="porcentaje"
                                            class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-8 col-sm-offset-4">
                                        <button class="btn btn-success" name="GuardarIVA">
                                            <i class="fa fa-save"></i> Guardar IVA
                                        </button>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>IT (referencia)</strong></div>
                        <div class="panel-body">
                            <p>Actualmente el sistema calcula IT por porcentaje ingresado en venta/factura.</p>
                            <p><strong>Referencia sugerida:</strong> <?php echo number_format($it_ref,2); ?>%</p>
                            <p class="text-muted">Si quieres que IT sea fijo desde aquí, te lo implemento guardándolo en
                                una tabla de configuración.</p>
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