<?php
session_start();
include('sistema/configuracion.php');
$usuario->LoginCuentaConsulta();
$usuario->ZonaAdministrador();

include("clase/servicios.config.php");
$Config = new ServicioConfig();

if ($_POST) {
    foreach ($_POST as $k => $v) {
        $Config->set($k, $v);
    }
    $mensaje = "Configuración actualizada correctamente.";
}

$data = $Config->all();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Configuración de Servicios</title>
    <?php include(MODULO."Tema.CSS.php"); ?>
</head>

<body>

    <?php include(MODULO.'menu_admin.php'); ?>

    <div class="container">
        <h2>Configuración del Módulo Servicios</h2>

        <?php if(isset($mensaje)): ?>
        <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form method="post" class="panel panel-default">
            <div class="panel-heading"><strong>Opciones generales</strong></div>
            <div class="panel-body">

                <label>Habilitar comisiones</label>
                <select name="permitir_comision" class="form-control">
                    <option value="1" <?php if(($data['permitir_comision']??0)==1) echo 'selected'; ?>>Sí</option>
                    <option value="0" <?php if(($data['permitir_comision']??0)==0) echo 'selected'; ?>>No</option>
                </select>
                <br>

                <label>Tipos de servicio permitidos (separados por coma)</label>
                <input type="text" name="tipos_servicio" class="form-control"
                    value="<?php echo $data['tipos_servicio'] ?? 'PASAJE,PAQUETE,SEGURO,TRAMITE,OTRO'; ?>">
                <br>

                <label>Generar código automáticamente</label>
                <select name="codigo_auto" class="form-control">
                    <option value="1" <?php if(($data['codigo_auto']??0)==1) echo 'selected'; ?>>Sí</option>
                    <option value="0" <?php if(($data['codigo_auto']??0)==0) echo 'selected'; ?>>No</option>
                </select>
                <br>

                <label>Proveedor obligatorio</label>
                <select name="proveedor_obligatorio" class="form-control">
                    <option value="1" <?php if(($data['proveedor_obligatorio']??0)==1) echo 'selected'; ?>>Sí</option>
                    <option value="0" <?php if(($data['proveedor_obligatorio']??0)==0) echo 'selected'; ?>>No</option>
                </select>
                <br>

                <label>Impuesto (%)</label>
                <input type="number" step="0.01" name="impuesto_servicio"
                    value="<?php echo $data['impuesto_servicio'] ?? 0; ?>" class="form-control">

            </div>

            <div class="panel-footer">
                <button class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>

    </div>

    <?php include(MODULO.'footer.php'); ?>
</body>

</html>