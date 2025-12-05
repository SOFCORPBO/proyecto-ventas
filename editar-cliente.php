<?php
session_start();
include ('sistema/configuracion.php');


$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$ClienteClase = new Cliente();


$Cliente = $ClienteClase->ObtenerClientePorId($_GET['id']);
$ClienteClase->EditarCliente();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Editar Cliente | <?= TITULO ?></title>
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php include(MODULO.'menu_admin.php'); ?>

    <div class="container">

        <div class="page-header">
            <h1>Editar Cliente</h1>
        </div>

        <form method="POST">
            <input type="hidden" name="EditarCliente" value="1">
            <input type="hidden" name="id" value="<?= $Cliente['id'] ?>">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label>Nombre</label>
                    <input type="text" required name="nombre" value="<?= $Cliente['nombre'] ?>" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>CI / Pasaporte</label>
                    <input type="text" name="ci_pasaporte" value="<?= $Cliente['ci_pasaporte'] ?>" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Tipo Documento</label>
                    <select name="tipo_documento" class="form-control">
                        <option <?= $Cliente['tipo_documento']=='CI'?'selected':'' ?>>CI</option>
                        <option <?= $Cliente['tipo_documento']=='PASAPORTE'?'selected':'' ?>>PASAPORTE</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Nacionalidad</label>
                    <input type="text" name="nacionalidad" value="<?= $Cliente['nacionalidad'] ?>" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Fecha Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="<?= $Cliente['fecha_nacimiento'] ?>"
                        class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="<?= $Cliente['telefono'] ?>" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= $Cliente['email'] ?>" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Dirección</label>
                    <input type="text" name="direccion" value="<?= $Cliente['direccion'] ?>" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Descuento</label>
                    <input type="number" step="0.01" name="descuento" value="<?= $Cliente['descuento'] ?>"
                        class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Activo</label><br>
                    <input type="checkbox" name="habilitado" <?= $Cliente['habilitado']==1?'checked':'' ?>>
                </div>

            </div>

            <button class="btn btn-primary">Actualizar Cliente</button>
            <a href="<?= URLBASE ?>cliente" class="btn btn-secondary">Cancelar</a>

        </form>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

</body>

</html>