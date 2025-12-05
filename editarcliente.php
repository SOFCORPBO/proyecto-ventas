<?php 
session_start();
include ('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$ClientesClase = new Cliente();

// Obtener ID desde URL: editarcliente.php?id=1
$IdCliente = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($IdCliente <= 0) {
    echo "<h3>ID inválido</h3>";
    exit;
}

// Obtener datos del cliente
$Cliente = $ClientesClase->ObtenerClientePorId($IdCliente);

if (!$Cliente) {
    echo "<h3>Cliente no encontrado</h3>";
    exit;
}

// Procesar edición
$ClientesClase->EditarCliente();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Editar Cliente | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">

    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
if($usuarioApp['id_perfil']==2){
    include(MODULO.'menu_vendedor.php');
}elseif($usuarioApp['id_perfil']==1){
    include(MODULO.'menu_admin.php');
}
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header">
                <h1>Editar Cliente</h1>
                <p>Modificar información del pasajero / cliente</p>
            </div>

            <form method="post" class="form-horizontal">

                <input type="hidden" name="id" value="<?php echo $Cliente['id']; ?>">

                <!-- NOMBRE -->
                <div class="col-md-4">
                    <label>Nombre Completo</label>
                    <input type="text" class="form-control" name="nombre" value="<?php echo $Cliente['nombre']; ?>"
                        required>
                </div>

                <!-- CI / PASAPORTE -->
                <div class="col-md-4">
                    <label>CI / Pasaporte</label>
                    <input type="text" class="form-control" name="ci_pasaporte"
                        value="<?php echo $Cliente['ci_pasaporte']; ?>" required>
                </div>

                <!-- TIPO DOCUMENTO -->
                <div class="col-md-4">
                    <label>Tipo Documento</label>
                    <select class="form-control" name="tipo_documento" required>
                        <option value="CI" <?php if($Cliente['tipo_documento']=="CI") echo "selected"; ?>>CI</option>
                        <option value="PASAPORTE" <?php if($Cliente['tipo_documento']=="PASAPORTE") echo "selected"; ?>>
                            Pasaporte</option>
                        <option value="OTRO" <?php if($Cliente['tipo_documento']=="OTRO") echo "selected"; ?>>Otro
                        </option>
                    </select>
                </div>

                <!-- NACIONALIDAD -->
                <div class="col-md-4">
                    <label>Nacionalidad</label>
                    <input type="text" class="form-control" name="nacionalidad"
                        value="<?php echo $Cliente['nacionalidad']; ?>" required>
                </div>

                <!-- FECHA NACIMIENTO -->
                <div class="col-md-4">
                    <label>Fecha de nacimiento</label>
                    <input type="date" class="form-control" name="fecha_nacimiento"
                        value="<?php echo $Cliente['fecha_nacimiento']; ?>">
                </div>

                <!-- TELEFONO -->
                <div class="col-md-4">
                    <label>Teléfono</label>
                    <input type="text" class="form-control" name="telefono" value="<?php echo $Cliente['telefono']; ?>">
                </div>

                <!-- EMAIL -->
                <div class="col-md-6">
                    <label>Correo</label>
                    <input type="email" class="form-control" name="email" value="<?php echo $Cliente['email']; ?>">
                </div>

                <!-- DIRECCION -->
                <div class="col-md-6">
                    <label>Dirección</label>
                    <input type="text" class="form-control" name="direccion"
                        value="<?php echo $Cliente['direccion']; ?>">
                </div>

                <!-- DESCUENTO -->
                <div class="col-md-4">
                    <label>Descuento (%)</label>
                    <input type="number" class="form-control" name="descuento" min="0" step="0.01"
                        value="<?php echo $Cliente['descuento']; ?>">
                </div>

                <!-- ESTADO -->
                <div class="col-md-4">
                    <label>Estado</label>
                    <select class="form-control" name="habilitado">
                        <option value="1" <?php echo ($Cliente['habilitado']==1 ? "selected" : ""); ?>>Habilitado
                        </option>
                        <option value="0" <?php echo ($Cliente['habilitado']==0 ? "selected" : ""); ?>>Inhabilitado
                        </option>
                    </select>
                </div>

                <!-- BOTONES -->
                <div class="col-md-12" style="margin-top:20px;">
                    <button type="submit" name="EditarCliente" class="btn btn-primary">Guardar Cambios</button>
                    <a href="<?php echo URLBASE ?>clientes" class="btn btn-default">Cancelar</a>
                </div>

            </form>

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

</body>

</html>