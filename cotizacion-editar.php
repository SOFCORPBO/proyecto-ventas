<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Instancia segura
if (!isset($CotizacionClase)) {
    $CotizacionClase = new Cotizacion();
}

if (!isset($_GET['id'])) {
    echo '<meta http-equiv="refresh" content="0;url=cotizaciones-kanban.php">';
    exit;
}

$idCot = (int)$_GET['id'];

$CotSQL = $db->SQL("
    SELECT c.*, cli.nombre AS cliente_nombre
    FROM cotizacion c
    LEFT JOIN cliente cli ON cli.id = c.id_cliente
    WHERE c.id = {$idCot}
    LIMIT 1
");

if ($CotSQL->num_rows == 0) {
    echo '<div class="alert alert-danger">Cotización no encontrada.</div>';
    exit;
}

$C = $CotSQL->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Editar Cotización | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/font-awesome.min.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    .panel-custom {
        background: #fff;
        border-radius: 6px;
        padding: 20px;
        border: 1px solid #ddd;
        margin-top: 15px;
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 2) {
    include(MODULO . 'menu_vendedor.php');
} elseif ($usuarioApp['id_perfil'] == 1) {
    include(MODULO . 'menu_admin.php');
}
?>

    <div class="container">

        <div class="page-header">
            <h1><i class="fa fa-edit"></i> Editar Cotización</h1>
            <a href="cotizacion-detalle.php?id=<?php echo $idCot; ?>" class="btn btn-default pull-right">
                <i class="fa fa-arrow-left"></i> Volver al detalle
            </a>
            <div class="clearfix"></div>
        </div>

        <?php

    $CotizacionClase->EditarCotizacion();


    ?>

        <div class="panel panel-custom">
            <form method="post" class="form-horizontal">
                <input type="hidden" name="id_cotizacion" value="<?php echo $C['id']; ?>">

                <!-- Código y Cliente -->
                <div class="form-group">
                    <label class="col-md-2 control-label">Código</label>
                    <div class="col-md-3">
                        <input type="text" class="form-control" value="<?php echo $C['codigo']; ?>" readonly>
                    </div>

                    <label class="col-md-2 control-label">Cliente</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" value="<?php echo $C['cliente_nombre']; ?>" readonly>
                    </div>
                </div>

                <!-- Estado y Moneda -->
                <div class="form-group">
                    <label class="col-md-2 control-label">Estado</label>
                    <div class="col-md-3">
                        <select name="estado" class="form-control">
                            <?php
                        $estados = ['PENDIENTE','EN PROCESO','APROBADA','RECHAZADA','VENCIDA'];
                        foreach ($estados as $e):
                        ?>
                            <option value="<?php echo $e; ?>" <?php if ($C['estado']==$e) echo 'selected'; ?>>
                                <?php echo $e; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label class="col-md-2 control-label">Moneda</label>
                    <div class="col-md-3">
                        <select name="moneda" class="form-control">
                            <option value="BOB" <?php if($C['moneda']=='BOB') echo 'selected'; ?>>BOB</option>
                            <option value="USD" <?php if($C['moneda']=='USD') echo 'selected'; ?>>USD</option>
                        </select>
                    </div>
                </div>

                <!-- Fechas -->
                <div class="form-group">
                    <label class="col-md-2 control-label">Fecha inicio</label>
                    <div class="col-md-3">
                        <input type="date" name="fecha_inicio" class="form-control"
                            value="<?php echo $C['fecha_inicio']; ?>">
                    </div>

                    <label class="col-md-2 control-label">Fecha entrega</label>
                    <div class="col-md-3">
                        <input type="date" name="fecha_entrega" class="form-control"
                            value="<?php echo $C['fecha_entrega']; ?>">
                    </div>
                </div>

                <!-- Observación -->
                <div class="form-group">
                    <label class="col-md-2 control-label">Observación</label>
                    <div class="col-md-8">
                        <textarea name="observacion" rows="4"
                            class="form-control"><?php echo $C['observacion']; ?></textarea>
                    </div>
                </div>

                <!-- Botones -->
                <div class="form-group">
                    <div class="col-md-12 text-right">
                        <button type="submit" name="EditarCotizacion" class="btn btn-primary">
                            <i class="fa fa-save"></i> Guardar Cambios
                        </button>
                        <a href="cotizacion-detalle.php?id=<?php echo $idCot; ?>" class="btn btn-default">
                            Cancelar
                        </a>
                    </div>
                </div>

            </form>
        </div>

    </div>

    <?php include(MODULO . 'footer.php'); ?>
    <?php include(MODULO . 'Tema.JS.php'); ?>

</body>

</html>