<?php 
session_start();
include ('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Acciones del CRUD
$ProductosClase->ActivarServicio();
$ProductosClase->DesactivarServicio();
$ProductosClase->EliminarServicio();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Catálogo de Servicios | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/font-awesome.min.css">

    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
// Menú según tipo de usuario
if($usuarioApp['id_perfil']==2){
    include (MODULO.'menu_vendedor.php');
}elseif($usuarioApp['id_perfil']==1){
    include (MODULO.'menu_admin.php');
}else{
    echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
}
?>

    <div id="wrap">
        <div class="container">

            <!-- TÍTULO -->
            <div class="page-header">
                <div class="row">
                    <div class="col-lg-8 col-md-7 col-sm-6">
                        <h1>Catálogo de Servicios</h1>

                        <a href="<?php echo URLBASE ?>nuevo-producto" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Nuevo Servicio
                        </a>
                    </div>
                </div>
            </div>

            <!-- TABLA -->
            <div class="table-responsive">
                <table class="table table-bordered" id="servicios">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Servicio</th>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Precio Costo</th>
                            <th>Precio Venta</th>
                            <th>IVA</th>
                            <th>Comisión</th>
                            <th>Requiere Boleto</th>
                            <th>Requiere Visa</th>
                            <th>Impuesto</th>
                            <th>Especificaciones</th>
                            <th>Estado</th>
                            <th style="width:140px;">Opciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($ProductosStockArray as $row): ?>

                        <?php  
                    // Obtener nombre de categoría
                    $CategoriaSQL = $db->SQL("SELECT nombre FROM categorias_servicios WHERE id='{$row['categoria_id']}'");
                    $Categoria = $CategoriaSQL->fetch_assoc();

                    // Obtener proveedor
                    $ProvSQL = $db->SQL("SELECT nombre FROM proveedor WHERE id='{$row['proveedor']}'");
                    $Prov = $ProvSQL->fetch_assoc();
                    ?>

                        <tr>
                            <td><?php echo $row['codigo']; ?></td>
                            <td><?php echo $row['nombre']; ?></td>
                            <td><?php echo $row['tipo_servicio']; ?></td>
                            <td><?php echo $Categoria['nombre'] ?: '-'; ?></td>
                            <td><?php echo $Prov['nombre'] ?: '-'; ?></td>

                            <td>Bs <?php echo number_format($row['preciocosto'], 2); ?></td>
                            <td>Bs <?php echo number_format($row['precioventa'], 2); ?></td>

                            <td><?php echo number_format($row['iva'], 2); ?>%</td>
                            <td><?php echo number_format($row['comision'], 2); ?>%</td>

                            <td>
                                <?php echo ($row['requiere_boleto']==1)
                                ? '<span class="label label-info">Sí</span>'
                                : '<span class="label label-default">No</span>'; ?>
                            </td>

                            <td>
                                <?php echo ($row['requiere_visa']==1)
                                ? '<span class="label label-info">Sí</span>'
                                : '<span class="label label-default">No</span>'; ?>
                            </td>

                            <td><?php echo $row['impuesto']; ?>%</td>

                            <td>
                                <?php echo (strlen($row['especificaciones']) > 40)
                                ? substr($row['especificaciones'],0,40)."..."
                                : $row['especificaciones']; ?>
                            </td>

                            <td>
                                <?php echo ($row['habilitado']==1)
                                ? '<span class="label label-success">Activo</span>'
                                : '<span class="label label-danger">Inactivo</span>'; ?>
                            </td>

                            <td>

                                <!-- EDITAR -->
                                <a href="<?php echo URLBASE ?>servicio-editar.php?id=<?= $row['id'] ?>"
                                    class="btn btn-primary btn-xs">
                                    <i class="fa fa-pencil-square-o"></i>
                                </a>

                                <!-- ACTIVAR / DESACTIVAR -->
                                <?php if($row['habilitado']==1): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="IdServicio" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="DesactivarServicio" class="btn btn-warning btn-xs">
                                        <i class="fa fa-ban"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="IdServicio" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="ActivarServicio" class="btn btn-success btn-xs">
                                        <i class="fa fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- ELIMINAR -->
                                <form method="post" style="display:inline;"
                                    onsubmit="return confirm('¿Eliminar este servicio?');">
                                    <input type="hidden" name="IdServicio" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="EliminarServicio" class="btn btn-danger btn-xs">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>

                            </td>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    <?php include (MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>
    <script>
    $(document).ready(function() {
        $('#servicios').dataTable({
            "scrollX": true,
            "pageLength": 10
        });
    });
    </script>

</body>

</html>