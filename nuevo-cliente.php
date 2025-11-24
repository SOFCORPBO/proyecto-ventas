<?php 
session_start();
include ('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Cargar clase clientes
$ClientesClase = new Clientes();

// Ejecutar registro
$ClientesClase->CrearCliente();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Nuevo Cliente | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">

    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <!-- Menú -->
    <?php
if($usuarioApp['id_perfil']==2){
    include (MODULO.'menu_vendedor.php');
}elseif($usuarioApp['id_perfil']==1){
    include (MODULO.'menu_admin.php');
}
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header">
                <h1>Nuevo Cliente</h1>
                <p>Registrar pasajero / cliente de la agencia</p>
            </div>

            <form method="post" class="form-horizontal">

                <!-- NOMBRE -->
                <div class="col-md-4">
                    <label>Nombre Completo</label>
                    <input type="text" class="form-control" name="nombre" placeholder="Ej: Juan Pérez" required>
                </div>

                <!-- CI / PASAPORTE -->
                <div class="col-md-4">
                    <label>CI / Pasaporte</label>
                    <input type="text" class="form-control" name="ci_pasaporte" placeholder="Documento" required>
                </div>

                <!-- TIPO DOCUMENTO -->
                <div class="col-md-4">
                    <label>Tipo de Documento</label>
                    <select class="form-control" name="tipo_documento" required>
                        <option value="CI">CI</option>
                        <option value="PASAPORTE">Pasaporte</option>
                        <option value="OTRO">Otro</option>
                    </select>
                </div>

                <!-- NACIONALIDAD -->
                <div class="col-md-4">
                    <label>Nacionalidad</label>
                    <input type="text" class="form-control" name="nacionalidad" placeholder="Ej: Boliviano" required>
                </div>

                <!-- FECHA NACIMIENTO -->
                <div class="col-md-4">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" class="form-control" name="fecha_nacimiento">
                </div>

                <!-- TELEFONO -->
                <div class="col-md-4">
                    <label>Teléfono</label>
                    <input type="text" class="form-control" name="telefono" placeholder="Celular">
                </div>

                <!-- EMAIL -->
                <div class="col-md-6">
                    <label>Correo</label>
                    <input type="email" class="form-control" name="email" placeholder="correo@ejemplo.com">
                </div>

                <!-- DIRECCION -->
                <div class="col-md-6">
                    <label>Dirección</label>
                    <input type="text" class="form-control" name="direccion" placeholder="Dirección del cliente">
                </div>

                <!-- DESCUENTO % -->
                <div class="col-md-4">
                    <label>Descuento (%)</label>
                    <input type="number" min="0" step="0.01" class="form-control" name="descuento" placeholder="Ej: 10">
                </div>

                <!-- HABILITADO -->
                <div class="col-md-4">
                    <label>Estado</label>
                    <select class="form-control" name="habilitado">
                        <option value="1">Habilitado</option>
                        <option value="0">Inhabilitado</option>
                    </select>
                </div>

                <!-- BOTONES -->
                <div class="col-md-12" style="margin-top:20px;">
                    <button type="submit" name="CrearCliente" class="btn btn-primary">Registrar Cliente</button>
                    <a href="<?php echo URLBASE ?>clientes" class="btn btn-default">Cancelar</a>
                </div>

            </form>

        </div>
    </div>

    <?php include (MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

</body>

</html>