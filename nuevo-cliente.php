<?php 
session_start();
include ('sistema/configuracion.php');
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Muy importante: usar la clase que creamos
// $ClientesClase viene instanciada en clientes.clase.php (incluida desde configuracion.php)
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Nuevo Cliente | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">

    <!-- Mismos estilos base que otros módulos -->
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">

    <!-- Tema -->
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
// Menú según perfil (igual que en productos.php, nuevo-producto.php, etc.)
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
                <div class="row">
                    <div class="col-lg-12">
                        <h1>Nuevo Cliente</h1>
                        <p class="lead">Registrar pasajero / cliente de la agencia</p>
                    </div>
                </div>
            </div>

            <?php 
        // Procesa el POST si viene del formulario
        $ClientesClase->CrearCliente(); 
        ?>

            <div class="row">
                <form class="form-horizontal" method="post">

                    <!-- Nombre completo -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">Nombre Completo</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Ej: Juan Pérez López"
                                required>
                        </div>
                    </div>

                    <!-- Tipo de documento -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Tipo de Documento</label>
                            <select class="form-control" name="tipo_documento">
                                <option value="CI">CI</option>
                                <option value="PASAPORTE">Pasaporte</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                    </div>

                    <!-- Número de documento -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">N° Documento</label>
                            <input type="text" class="form-control" name="numero_documento"
                                placeholder="Ej: 12345678 LP">
                        </div>
                    </div>

                    <!-- Nacionalidad -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Nacionalidad</label>
                            <input type="text" class="form-control" name="nacionalidad" placeholder="Ej: Boliviana">
                        </div>
                    </div>

                    <!-- Fecha nacimiento -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento">
                        </div>
                    </div>

                    <!-- Teléfono -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Teléfono / Celular</label>
                            <input type="text" class="form-control" name="telefono" placeholder="Ej: 70000000">
                        </div>
                    </div>

                    <!-- Correo -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Correo electrónico</label>
                            <input type="email" class="form-control" name="correo" placeholder="Ej: cliente@gmail.com">
                        </div>
                    </div>

                    <!-- Requiere visa -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">¿Requiere Visa?</label>
                            <select class="form-control" name="requiere_visa">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="control-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="3"
                                placeholder="Notas sobre el cliente, preferencias, restricciones, etc."></textarea>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <button type="submit" name="CrearCliente" class="btn btn-primary">
                                Guardar Cliente
                            </button>
                            <a href="<?php echo URLBASE ?>clientes" class="btn btn-default">
                                Volver al listado
                            </a>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>

    <?php include (MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php');?>

</body>

</html>