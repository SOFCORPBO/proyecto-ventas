<?php 
session_start();
include ('sistema/configuracion.php');
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$ProductosClase->URLProductoID();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Editar Servicio | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">

    <!-- ESTILOS QUE FALTABAN (OBLIGATORIOS) -->
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">

    <!-- Estilos del tema -->
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
                        <h1>Editar Servicio</h1>
                    </div>
                </div>
            </div>

            <?php
        // Procesa edición
        $ProductosClase->EditarProducto();
        ?>

            <div class="row">
                <form class="form-horizontal" method="post">

                    <input type="hidden" name="Id" value="<?php echo $ProductoID['id']; ?>" />

                    <!-- NOMBRE DEL SERVICIO -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Nombre del Servicio</label>
                            <input type="text" class="form-control" name="Nombre"
                                value="<?php echo $ProductoID['nombre']; ?>" required>
                        </div>
                    </div>

                    <!-- CODIGO -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Código</label>
                            <input type="text" class="form-control" name="Codigo"
                                value="<?php echo $ProductoID['codigo']; ?>" required>
                        </div>
                    </div>

                    <!-- TIPO DE SERVICIO -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Tipo de Servicio</label>
                            <select class="form-control" name="TipoServicio" required>
                                <?php
                            $tipos = ["PASAJE","PAQUETE","SEGURO","TRAMITE","OTRO"];
                            foreach($tipos as $ts):
                            ?>
                                <option value="<?php echo $ts; ?>"
                                    <?php echo ($ProductoID['tipo_servicio']==$ts?'selected':''); ?>>
                                    <?php echo $ts; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- PROVEEDOR -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Proveedor</label>
                            <select class="form-control" name="Proveedor">
                                <?php foreach($ProveedoresStockArray as $p): ?>
                                <option value="<?php echo $p['id']; ?>"
                                    <?php echo ($ProductoID['proveedor']==$p['id']?'selected':''); ?>>
                                    <?php echo $p['nombre']; ?>
                                </option>
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
                                <input type="text" class="form-control" name="PrecioCosto"
                                    value="<?php echo $ProductoID['preciocosto']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- PRECIO VENTA -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Precio Venta</label>
                            <div class="input-group">
                                <span class="input-group-addon"><strong>$</strong></span>
                                <input type="text" class="form-control" name="PrecioVenta"
                                    value="<?php echo $ProductoID['precioventa']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- COMISIÓN -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Comisión (%)</label>
                            <input type="number" class="form-control" name="Comision" min="0" step="0.01"
                                value="<?php echo $ProductoID['comision']; ?>">
                        </div>
                    </div>

                    <!-- ES COMISIONABLE -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Es Comisionable</label>
                            <select class="form-control" name="EsComisionable">
                                <option value="1" <?php echo ($ProductoID['es_comisionable']==1?'selected':''); ?>>Sí
                                </option>
                                <option value="0" <?php echo ($ProductoID['es_comisionable']==0?'selected':''); ?>>No
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- REQUIERE BOLETO -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Requiere Boleto</label>
                            <select class="form-control" name="RequiereBoleto">
                                <option value="1" <?php echo ($ProductoID['requiere_boleto']==1?'selected':''); ?>>Sí
                                </option>
                                <option value="0" <?php echo ($ProductoID['requiere_boleto']==0?'selected':''); ?>>No
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- REQUIERE VISA -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Requiere Visa</label>
                            <select class="form-control" name="RequiereVisa">
                                <option value="1" <?php echo ($ProductoID['requiere_visa']==1?'selected':''); ?>>Sí
                                </option>
                                <option value="0" <?php echo ($ProductoID['requiere_visa']==0?'selected':''); ?>>No
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- DESCRIPCIÓN -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="control-label">Descripción del Servicio</label>
                            <textarea class="form-control" name="DescripcionServicio" rows="4"
                                placeholder="Detalle o notas del servicio"><?php echo $ProductoID['descripcion']; ?></textarea>
                        </div>
                    </div>

                    <!-- BOTONES -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <button type="submit" name="EditarProducto" class="btn btn-primary">
                                Guardar Cambios
                            </button>
                            <a href="<?php echo URLBASE ?>productos" class="btn btn-default">Cancelar</a>
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