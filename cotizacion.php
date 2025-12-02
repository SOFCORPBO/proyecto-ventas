<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Clase
$CotizacionClase = new Cotizacion();

// Acciones
$CotizacionClase->CambiarEstado();
$CotizacionClase->EliminarCotizacion();
$CotizacionClase->ConvertirCotizacionVenta();

$Cotizaciones = $CotizacionClase->ListarCotizaciones();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Cotizaciones | <?php echo TITULO ?></title>

    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/font-awesome.min.css">

    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    .estado-label {
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
    }

    .estado-1 {
        background: #f0ad4e;
        color: white;
    }

    /* pendiente */
    .estado-2 {
        background: #5cb85c;
        color: white;
    }

    /* aprobada */
    .estado-3 {
        background: #d9534f;
        color: white;
    }

    /* rechazada */
    .estado-4 {
        background: #777;
        color: white;
    }

    /* vencida */
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil'] == 1) {
    include(MODULO.'menu_admin.php');
} elseif ($usuarioApp['id_perfil'] == 2) {
    include(MODULO.'menu_vendedor.php');
}
?>

    <div class="container">

        <div class="page-header">
            <h1><i class="fa fa-file-text-o"></i> Cotizaciones</h1>
            <a href="cotizacion-nueva.php" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> Nueva Cotización
            </a>
            <div class="clearfix"></div>
        </div>

        <div class="panel panel-default">
            <div class="panel-body">

                <table id="Listado" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Vendedor</th>
                            <th width="200">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($C = $Cotizaciones->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $C['id']; ?></td>
                            <td><?php echo $C['cliente_nombre']; ?></td>
                            <td><?php echo $C['fecha']; ?> <?php echo $C['hora']; ?></td>
                            <td><strong>Bs <?php echo number_format($C['total'], 2); ?></strong></td>
                            <td>
                                <span class="estado-label estado-<?php echo $C['estado']; ?>">
                                    <?php
                                switch($C['estado']) {
                                    case 1: echo "Pendiente"; break;
                                    case 2: echo "Aprobada"; break;
                                    case 3: echo "Rechazada"; break;
                                    case 4: echo "Vencida"; break;
                                }
                                ?>
                                </span>
                            </td>
                            <td><?php echo $C['vendedor']; ?></td>

                            <td class="text-center">

                                <!-- VER -->
                                <a href="cotizacion-ver.php?id=<?php echo $C['id']; ?>" class="btn btn-info btn-xs">
                                    <i class="fa fa-eye"></i>
                                </a>

                                <!-- IMPRIMIR -->
                                <a href="cotizacion-imprimir.php?id=<?php echo $C['id']; ?>"
                                    class="btn btn-default btn-xs" target="_blank">
                                    <i class="fa fa-print"></i>
                                </a>

                                <!-- CAMBIAR ESTADO -->
                                <form method="post" style="display:inline-block">
                                    <input type="hidden" name="IdCotizacion" value="<?php echo $C['id']; ?>">

                                    <select class="form-control input-sm" name="Estado" onchange="this.form.submit()">
                                        <option value="1" <?php if($C['estado']==1) echo 'selected'; ?>>Pendiente
                                        </option>
                                        <option value="2" <?php if($C['estado']==2) echo 'selected'; ?>>Aprobada
                                        </option>
                                        <option value="3" <?php if($C['estado']==3) echo 'selected'; ?>>Rechazada
                                        </option>
                                        <option value="4" <?php if($C['estado']==4) echo 'selected'; ?>>Vencida</option>
                                    </select>

                                    <input type="hidden" name="CambiarEstadoCotizacion">
                                </form>

                                <!-- CONVERTIR A VENTA -->
                                <?php if ($C['estado'] == 2): ?>
                                <form method="post" style="display:inline-block">
                                    <input type="hidden" name="IdCotizacion" value="<?php echo $C['id']; ?>">
                                    <button name="ConvertirCotizacionVenta" class="btn btn-success btn-xs">
                                        <i class="fa fa-shopping-cart"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- ELIMINAR -->
                                <form method="post" style="display:inline-block">
                                    <input type="hidden" name="IdCotizacion" value="<?php echo $C['id']; ?>">
                                    <button name="EliminarCotizacion" class="btn btn-danger btn-xs"
                                        onclick="return confirm('¿Eliminar cotización?')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>

                            </td>
                        </tr>

                        <?php endwhile; ?>
                    </tbody>

                </table>

            </div>
        </div>

    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script>
    $(document).ready(function() {
        $('#Listado').DataTable();
    });
    </script>

</body>

</html>