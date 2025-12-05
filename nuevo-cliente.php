<?php
session_start();
include ('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$ClienteClase = new Cliente();

$ClienteClase->CrearCliente();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Nuevo Cliente | <?= TITULO ?></title>
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php if($usuarioApp['id_perfil']==1){ include(MODULO.'menu_admin.php'); } ?>

    <div class="container">

        <div class="page-header">
            <h1>Registrar Cliente</h1>
        </div>

        <form method="POST">

            <input type="hidden" name="CrearCliente" value="1">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label>Nombre</label>
                    <input type="text" required name="nombre" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>CI / Pasaporte</label>
                    <input type="text" name="ci_pasaporte" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Tipo Documento</label>
                    <select name="tipo_documento" class="form-control">
                        <option value="CI">CI</option>
                        <option value="PASAPORTE">Pasaporte</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Nacionalidad</label>
                    <input type="text" name="nacionalidad" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Fecha Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Descuento</label>
                    <input type="number" step="0.01" name="descuento" class="form-control" value="0">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Activo</label><br>
                    <input type="checkbox" name="habilitado" checked>
                </div>

            </div>

            <button class="btn btn-success">Guardar Cliente</button>
            <a href="<?= URLBASE ?>cliente" class="btn btn-secondary">Cancelar</a>

        </form>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

</body>

</html>