<?php
session_start();
include('sistema/configuracion.php');

// Validar sesión
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
$usuario->ZonaAdministrador();

// Asegurar que la clase Venta esté disponible
if (!isset($Venta)) {
    $Venta = new Venta();
}

/*
|------------------------------------------------------------
| PROCESAR CANCELACIÓN DE FACTURA
|   - Usa la lógica de Venta->CancelarFactura()
|   - Ajusta caja, marca factura y venta como anuladas
|   - NO mueve inventario (son servicios)
|------------------------------------------------------------
*/
$Venta->CancelarFactura();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?php echo ESTATICO; ?>img/favicon.ico">
    <link rel="stylesheet" type="text/css" href="<?php echo ESTATICO; ?>css/dataTables.bootstrap.css">
    <?php include(MODULO . 'Tema.CSS.php'); ?>
</head>

<body>
    <?php
    // Menú inicio
    if ($usuarioApp['id_perfil'] == 2) {
        include(MODULO . 'menu_vendedor.php');
    } elseif ($usuarioApp['id_perfil'] == 1) {
        include(MODULO . 'menu_admin.php');
    } else {
        echo '<meta http-equiv="refresh" content="0;url=' . URLBASE . 'cerrar-sesion"/>';
    }
    // Menú fin
    ?>

    <div id="wrap">
        <div class="container">

            <div class="page-header" id="banner">
                <div class="row">
                    <div class="col-lg-8 col-md-7 col-sm-6">
                        <h1>Registro de Ventas</h1>
                    </div>
                </div>
            </div>

            <!-- Tabla de facturas -->
            <div class="row">
                <div class="col-sm-12">
                    <table cellpadding="0" cellspacing="0" border="0"
                        class="table table-striped table-bordered table-condensed" id="example" data-sort-name="id"
                        data-sort-order="desc">
                        <thead>
                            <tr>
                                <td><strong>Id Factura</strong></td>
                                <td><strong>Total</strong></td>
                                <td><strong>Comisión</strong></td>
                                <td><strong>Fecha</strong></td>
                                <td><strong>Vendedor</strong></td>
                                <td><strong>Estado</strong></td>
                                <td><strong>Comprobante</strong></td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            /*
                             * Ahora traemos:
                             * - Datos de la factura
                             * - Nombre del vendedor (JOIN con vendedores)
                             */
                            $facturasSql = $db->SQL("
                                SELECT f.*,
                                       v.nombre   AS vendedor_nombre,
                                       v.apellido1 AS vendedor_apellido1,
                                       v.apellido2 AS vendedor_apellido2
                                FROM factura f
                                LEFT JOIN vendedores v ON v.id = f.usuario
                                ORDER BY f.id DESC
                            ");

                            while ($factura = $facturasSql->fetch_array()):
                                // Calcular comisión total de la factura (suma de comision_monto en detalleventa)
                                $ComisionSql = $db->SQL("
                                    SELECT SUM(comision_monto) AS total_comision
                                    FROM detalleventa
                                    WHERE idfactura = '{$factura['id']}'
                                ");
                                $Comision = $ComisionSql->fetch_assoc();
                                $totalComision = isset($Comision['total_comision']) ? (float)$Comision['total_comision'] : 0;
                            ?>
                            <tr>
                                <td data-sort-order="desc"><?php echo $factura['id']; ?></td>

                                <td>Bs <?php echo number_format($factura['total'], 2); ?></td>

                                <td>Bs <?php echo number_format($totalComision, 2); ?></td>

                                <td><?php echo $factura['fecha'] . ' ' . $factura['hora']; ?></td>

                                <td>
                                    <?php
                                    $nombreVendedor = trim(
                                        ($factura['vendedor_nombre'] ?? '') . ' ' .
                                        ($factura['vendedor_apellido1'] ?? '') . ' ' .
                                        ($factura['vendedor_apellido2'] ?? '')
                                    );
                                    echo $nombreVendedor !== '' ? $nombreVendedor : 'Sin asignar';
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    if ($factura['habilitado'] == 1) {
                                            echo '<span class="label label-success">Activa</span>';
                                        } else {
                                        echo '<span class="label label-danger">Cancelada</span>';
                                    }
                                    ?>
                                </td>

                                <td>
                                    <!-- Ver venta / comprobante -->
                                    <a href="<?php echo URLBASE ?>detalle-venta.php?id=<?php echo $cajatmp['id']; ?>"
                                        class="btn btn-primary btn-sm">Ver venta</a>

                                    <!-- Ver detalle de servicios (si implementas detalle-venta.php) -->
                                    <a href="<?php echo URLBASE; ?>detalle-venta/<?php echo $factura['id']; ?>"
                                        class="btn btn-info btn-sm">
                                        Ver detalle
                                    </a>

                                    <?php if ($factura['habilitado'] == 1): ?>
                                    <!-- Botón para cancelar factura -->
                                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal"
                                        data-target="#CancelarFactura<?php echo $factura['id']; ?>">
                                        Cancelar Factura
                                    </button>

                                    <!-- Modal Cancelar Factura -->
                                    <div class="modal fade" id="CancelarFactura<?php echo $factura['id']; ?>"
                                        tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                    <h4 class="modal-title" id="myModalLabel">
                                                        Cancelar Factura #<?php echo $factura['id']; ?>
                                                    </h4>
                                                </div>
                                                <div class="modal-body">
                                                    <form class="form-horizontal" method="post" action="">
                                                        <input type="hidden" name="Idfactura"
                                                            value="<?php echo $factura['id']; ?>">
                                                        <!-- Tipo de factura (forma de pago) -->
                                                        <input type="hidden" name="tipo"
                                                            value="<?php echo $factura['tipo']; ?>">

                                                        <div class="form-group">
                                                            <div class="col-sm-12">
                                                                <p>
                                                                    ¿Est&aacute; seguro que desea cancelar la factura
                                                                    #<?php echo $factura['id']; ?>?
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-12 control-label">
                                                                Motivo de la cancelación
                                                            </label>
                                                            <div class="col-sm-12">
                                                                <textarea name="Comentario" class="form-control"
                                                                    rows="3"
                                                                    placeholder="Describa el motivo de cancelación"
                                                                    required></textarea>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <div class="col-sm-12">
                                                                <button type="button" class="btn btn-default"
                                                                    data-dismiss="modal">
                                                                    Cerrar
                                                                </button>
                                                                <button type="submit" name="CancelarFactura"
                                                                    class="btn btn-primary">
                                                                    Sí, Cancelar
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Modal Final -->
                                    <?php else: ?>
                                    <button type="button" class="btn btn-default btn-sm disabled">
                                        Factura Cancelada
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <?php include(MODULO . 'footer.php'); ?>

    <!-- JS al final para mejor rendimiento -->
    <?php include(MODULO . 'Tema.JS.php'); ?>
    <script type="text/javascript" language="javascript" src="<?php echo ESTATICO; ?>js/jquery.dataTables.min.js">
    </script>
    <script type="text/javascript" language="javascript" src="<?php echo ESTATICO; ?>js/dataTables.bootstrap.js">
    </script>
    <script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
        $('#example').dataTable({
            "order": [0, 'desc']
        });
    });
    </script>
</body>

</html>