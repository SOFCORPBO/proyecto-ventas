<?php 
session_start();
include ('sistema/configuracion.php');
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Cargar datos del cliente a editar
$ClientesClase->URLClienteID();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Editar Cliente | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">

    <!-- Estilos iguales a otros módulos -->
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">

    <!-- Tema -->
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
                <div class="row">
                    <div class="col-lg-12">
                        <h1>Editar Cliente</h1>
                        <p class="lead">Modificar información del pasajero / cliente</p>
                    </div>
                </div>
            </div>

            <?php 
        // Procesa edición
        $ClientesClase->EditarCliente();
        ?>

            <div class="row">
                <form class="form-horizontal" method="post">

                    <input type="hidden" name="id" value="<?php echo $ClienteID['id']; ?>">

                    <!-- Nombre -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">Nombre Completo</label>
                            <input type="text" class="form-control" name="nombre"
                                value="<?php echo $ClienteID['nombre']; ?>" required>
                        </div>
                    </div>

                    <!-- Tipo documento -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Tipo de Documento</label>
                            <select class="form-control" name="tipo_documento">
                                <?php 
                                $opts = ["CI","PASAPORTE","OTRO"];
                                foreach($opts as $o){
                                    $sel = ($ClienteID['tipo_documento']==$o) ? "selected" : "";
                                    echo "<option value='$o' $sel>$o</option>";
                                }
                            ?>
                            </select>
                        </div>
                    </div>

                    <!-- Número documento -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">N° Documento</label>
                            <input type="text" class="form-control" name="numero_documento"
                                value="<?php echo $ClienteID['numero_documento']; ?>">
                        </div>
                    </div>

                    <!-- Nacionalidad -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Nacionalidad</label>
                            <input type="text" class="form-control" name="nacionalidad"
                                value="<?php echo $ClienteID['nacionalidad']; ?>">
                        </div>
                    </div>

                    <!-- Fecha nacimiento -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento"
                                value="<?php echo $ClienteID['fecha_nacimiento']; ?>">
                        </div>
                    </div>

                    <!-- Teléfono -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Teléfono / Celular</label>
                            <input type="text" class="form-control" name="telefono"
                                value="<?php echo $ClienteID['telefono']; ?>">
                        </div>
                    </div>

                    <!-- Correo -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Correo electrónico</label>
                            <input type="email" class="form-control" name="correo"
                                value="<?php echo $ClienteID['correo']; ?>">
                        </div>
                    </div>

                    <!-- Requiere visa -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">¿Requiere Visa?</label>
                            <select class="form-control" name="requiere_visa">
                                <option value="0" <?php echo ($ClienteID['requiere_visa']==0?'selected':''); ?>>No
                                </option>
                                <option value="1" <?php echo ($ClienteID['requiere_visa']==1?'selected':''); ?>>Sí
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="control-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="3"
                                placeholder="Notas del cliente"><?php echo $ClienteID['observaciones']; ?></textarea>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <button type="submit" name="EditarCliente" class="btn btn-primary">
                                Guardar Cambios
                            </button>
                            <a href="<?php echo URLBASE ?>clientes" class="btn btn-default">
                                Cancelar
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