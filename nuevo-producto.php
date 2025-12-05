<?php 
session_start();
include ('sistema/configuracion.php');
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Nuevo Servicio | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">

    <!-- ESTILOS NECESARIOS DEL SISTEMA (FALTABAN) -->
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
                        <h1>Nuevo Servicio</h1>
                    </div>
                </div>
            </div>

            <?php $ProductosClase->CrearProducto(); ?>

            <div class="row">
                <form class="form-horizontal" method="post">

                    <!-- NOMBRE DEL SERVICIO -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Nombre del Servicio</label>
                            <input type="text" class="form-control" name="Nombre" placeholder="Ej: Pasaje a Miami"
                                required>
                        </div>
                    </div>

                    <!-- CÓDIGO -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Código</label>
                            <input type="text" class="form-control" name="Codigo" placeholder="Código interno" required>
                        </div>
                    </div>

                    <!-- TIPO DE SERVICIO -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Tipo de Servicio</label>
                            <select class="form-control" name="TipoServicio" required>
                                <option value="PASAJE">Pasaje</option>
                                <option value="PAQUETE">Paquete Turístico</option>
                                <option value="SEGURO">Seguro</option>
                                <option value="TRAMITE">Trámite Migratorio</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                    </div>

                    <!-- PROVEEDOR -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Proveedor</label>
                            <select class="form-control" name="Proveedor">
                                <?php foreach($ProveedoresStockArray as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- PRECIO COSTO -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Precio Costo</label>
                            <div class="input-group">
                                <span class="input-group-addon"><strong>$</strong></span>
                                <input type="text" class="form-control" name="PrecioCosto" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>

                    <!-- PRECIO VENTA -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Precio Venta</label>
                            <div class="input-group">
                                <span class="input-group-addon"><strong>$</strong></span>
                                <input type="text" class="form-control" name="PrecioVenta" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>

                    <!-- COMISIÓN -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Comisión (%)</label>
                            <input type="number" class="form-control" name="Comision" placeholder="Ej: 10" min="0"
                                step="0.01">
                        </div>
                    </div>

                    <!-- ES COMISIONABLE -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Es Comisionable</label>
                            <select class="form-control" name="EsComisionable">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- REQUIERE BOLETO -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Requiere Boleto</label>
                            <select class="form-control" name="RequiereBoleto">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- REQUIERE VISA -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Requiere Visa</label>
                            <select class="form-control" name="RequiereVisa">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- DESCRIPCIÓN -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="control-label">Descripción del Servicio</label>
                            <textarea class="form-control" name="DescripcionServicio" rows="4"
                                placeholder="Notas, detalles o condiciones del servicio"></textarea>
                        </div>
                    </div>

                    <!-- BOTONES -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <button type="submit" name="CrearProducto" class="btn btn-primary">Guardar Servicio</button>
                            <button type="reset" class="btn btn-default">Cancelar</button>
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