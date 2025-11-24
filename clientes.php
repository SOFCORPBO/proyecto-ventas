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
    <title>Clientes | <?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">

    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">

    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>

    <?php
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

            <?php 
        $ClientesClase->CambiarEstado();
        ?>

            <div class="page-header" id="banner">
                <h1>Clientes</h1>

                <a href="<?php echo URLBASE ?>nuevo-cliente" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Nuevo Cliente
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="clientes">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Nacionalidad</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Visa</th>
                            <th>Estado</th>
                            <th style="width:140px;">Opciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($ClientesStockArray as $row): ?>
                        <tr>
                            <td><?php echo $row['nombre']; ?></td>

                            <td><?php echo $row['tipo_documento'].' '.$row['numero_documento']; ?></td>

                            <td><?php echo $row['nacionalidad']; ?></td>

                            <td><?php echo $row['telefono']; ?></td>

                            <td><?php echo $row['correo']; ?></td>

                            <td>
                                <?php echo ($row['requiere_visa']==1) 
                                ? '<span class="label label-warning">Sí</span>' 
                                : '<span class="label label-success">No</span>'; ?>
                            </td>

                            <td>
                                <?php echo ($row['habilitado']==1) 
                                ? '<span class="label label-success">Activo</span>' 
                                : '<span class="label label-danger">Inactivo</span>'; ?>
                            </td>

                            <td>
                                <a href="<?php echo URLBASE ?>editarcliente/<?php echo $row['id']; ?>"
                                    class="btn btn-primary btn-xs" title="Editar">
                                    <i class="fa fa-pencil"></i>
                                </a>

                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="estado"
                                        value="<?php echo ($row['habilitado']==1 ? 0 : 1); ?>">

                                    <button type="submit" name="CambiarEstado"
                                        class="btn btn-<?php echo ($row['habilitado']==1 ? 'warning' : 'success'); ?> btn-xs">
                                        <i class="fa fa-<?php echo ($row['habilitado']==1 ? 'ban' : 'check'); ?>"></i>
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
        $('#clientes').dataTable({
            "scrollY": false,
            "scrollX": true
        });
    });
    </script>

</body>

</html>