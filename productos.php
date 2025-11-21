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
    <title>Catálogo de Servicios | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">

    <!-- ESTILOS NECESARIOS -->
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">

    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
// Menú según perfil
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

            <!-- ✔ EJECUTAR ACCIONES DE SERVICIO -->
            <?php
        $ProductosClase->ActivarServicio();
        $ProductosClase->DesactivarServicio();
        $ProductosClase->EliminarServicio();
        ?>

            <!-- TÍTULO -->
            <div class="page-header" id="banner">
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
                            <th>Proveedor</th>
                            <th>Precio Venta</th>
                            <th>Comisión</th>
                            <th>Estado</th>
                            <th style="width:140px;">Opciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($ProductosStockArray as $row): ?>
                        <tr>

                            <td><?php echo $row['codigo']; ?></td>
                            <td><?php echo $row['nombre']; ?></td>
                            <td><?php echo $row['tipo_servicio']; ?></td>

                            <td>
                                <?php
                            $ProvSql = $db->SQL("SELECT nombre FROM proveedor WHERE id='{$row['proveedor']}'");
                            $Prov    = $ProvSql->fetch_assoc();
                            echo $Prov['nombre'];
                            ?>
                            </td>

                            <td>$ <?php echo $row['precioventa']; ?></td>

                            <td><?php echo $row['comision']; ?>%</td>

                            <td>
                                <?php if($row['habilitado']==1): ?>
                                <span class="label label-success">Activo</span>
                                <?php else: ?>
                                <span class="label label-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <!-- EDITAR -->
                                <a href="<?php echo URLBASE ?>editarproducto/<?php echo $row['id']; ?> /<?php echo $enlace->LimpiaCadenaTexto($row['nombre']); ?>/"
                                    class="btn btn-primary btn-xs" title="Editar">
                                    <i class="fa fa-pencil-square-o"></i>
                                </a>

                                <!-- ACTIVAR O DESACTIVAR -->
                                <?php if($row['habilitado']==1): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="IdServicio" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="DesactivarServicio" class="btn btn-warning btn-xs"
                                        title="Desactivar">
                                        <i class="fa fa-ban"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="IdServicio" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="ActivarServicio" class="btn btn-success btn-xs"
                                        title="Activar">
                                        <i class="fa fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- ELIMINAR -->
                                <form method="post" style="display:inline;"
                                    onsubmit="return confirm('¿Eliminar este servicio?');">
                                    <input type="hidden" name="IdServicio" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="EliminarServicio" class="btn btn-danger btn-xs"
                                        title="Eliminar">
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
    <script type="text/javascript" src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    $(document).ready(function() {
        $('#servicios').dataTable({
            "scrollY": false,
            "scrollX": true
        });
    });
    </script>

</body>

</html>