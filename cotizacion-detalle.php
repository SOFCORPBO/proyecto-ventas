<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Instancia segura
if (!isset($CotizacionClase)) {
    $CotizacionClase = new Cotizacion();
}

// Acciones posibles
$CotizacionClase->ConvertirAVenta();
$CotizacionClase->CambiarEtapa();

if (!isset($_GET['id'])) {
    echo '<meta http-equiv="refresh" content="0;url=cotizacion-kanban.php">';
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

// Detalle
$Detalle = $CotizacionClase->ObtenerDetalle($idCot);

// Etapas
$etapas = $CotizacionClase->getEtapas();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Detalle Cotización | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/font-awesome.min.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>

    <style>
    .badge-etapa {
        font-size: 11px;
    }

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
            <h1><i class="fa fa-file-text-o"></i> Detalle de Cotización</h1>
            <a href="cotizaciones-kanban.php" class="btn btn-default pull-right">
                <i class="fa fa-arrow-left"></i> Volver a cotizaciones
            </a>
            <div class="clearfix"></div>
        </div>

        <div class="row">

            <!-- INFO GENERAL -->
            <div class="col-md-8">
                <div class="panel panel-custom">
                    <h4>Datos Generales</h4>
                    <hr>
                    <p><strong>Código:</strong> <?php echo $C['codigo']; ?></p>
                    <p><strong>Cliente:</strong> <?php echo $C['cliente_nombre']; ?></p>
                    <p><strong>Fecha:</strong> <?php echo $C['fecha'].' '.$C['hora']; ?></p>
                    <p><strong>Validez:</strong> <?php echo $C['validez_dias']; ?> días</p>
                    <p><strong>Fecha vencimiento:</strong> <?php echo $C['fecha_vencimiento']; ?></p>
                    <p><strong>Moneda:</strong> <?php echo $C['moneda']; ?></p>
                    <p><strong>Estado:</strong> <?php echo $C['estado']; ?></p>
                    <p><strong>Etapa:</strong>
                        <span class="label label-info badge-etapa"><?php echo $C['etapa']; ?></span>
                    </p>
                    <p><strong>Observación:</strong><br><?php echo nl2br($C['observacion']); ?></p>
                </div>

            </div>

            <!-- ACCIONES -->
            <div class="col-md-4">
                <div class="panel panel-custom">
                    <h4>Acciones</h4>
                    <hr>

                    <!-- Editar -->
                    <p>
                        <a href="cotizacion-editar.php?id=<?php echo $idCot; ?>" class="btn btn-primary btn-block">
                            <i class="fa fa-edit"></i> Editar Cotización
                        </a>
                    </p>

                    <!-- Convertir a venta -->
                    <form method="post">
                        <input type="hidden" name="id_cotizacion" value="<?php echo $idCot; ?>">
                        <button type="submit" name="ConvertirVenta" class="btn btn-success btn-block">
                            <i class="fa fa-shopping-cart"></i> Convertir a Venta (POS)
                        </button>
                    </form>

                    <hr>

                    <!-- Cambiar etapa -->
                    <form method="post">
                        <input type="hidden" name="id_cotizacion" value="<?php echo $idCot; ?>">
                        <div class="form-group">
                            <label>Nueva etapa</label>
                            <select name="nueva_etapa" class="form-control">
                                <?php foreach ($etapas as $e): ?>
                                <option value="<?php echo $e; ?>" <?php if($C['etapa']==$e) echo 'selected'; ?>>
                                    <?php echo $e; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="MoverEtapa" class="btn btn-warning btn-block">
                            <i class="fa fa-arrows-h"></i> Cambiar etapa
                        </button>
                    </form>

                </div>
            </div>

        </div>

        <!-- DETALLE DE SERVICIOS -->
        <div class="panel panel-custom">
            <h4><i class="fa fa-suitcase"></i> Servicios cotizados</h4>
            <hr>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                $total = 0;
                while ($d = $Detalle->fetch_assoc()):
                    $total += $d['subtotal'];
                    ?>
                        <tr>
                            <td><?php echo $d['nombre']; ?></td>
                            <td><?php echo $d['tipo_servicio']; ?></td>
                            <td><?php echo $d['cantidad']; ?></td>
                            <td><?php echo number_format($d['precio'], 2); ?></td>
                            <td><?php echo number_format($d['subtotal'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">Total</th>
                            <th><?php echo number_format($total, 2).' '.$C['moneda']; ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

    <?php include(MODULO . 'footer.php'); ?>
    <?php include(MODULO . 'Tema.JS.php'); ?>

</body>

</html>