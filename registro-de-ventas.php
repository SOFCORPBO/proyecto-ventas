<?php
session_start();
include('sistema/configuracion.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Clases necesarias
include_once("sistema/clase/facturacion_ventas.clase.php");
include_once("sistema/clase/venta.clase.php");

// Validar sesión
$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
$usuario->ZonaAdministrador();

// Instancias
if (!isset($Venta)) {
    $Venta = new Venta();
}
$Facturacion = new FacturacionVentas();

/*
|------------------------------------------------------------
| PROCESAR CANCELACIÓN DE FACTURA (ya existente)
|------------------------------------------------------------
*/
$Venta->CancelarFactura();

/*
|------------------------------------------------------------
| PROCESAR FACTURACIÓN (CONFIRMAR FACTURAR)
|------------------------------------------------------------
*/
if (isset($_POST['ConfirmarFacturacion'])) {

    $idfactura      = intval($_POST['idfactura']);
    $nit            = isset($_POST['nit']) ? trim($_POST['nit']) : '';
    $razon_social   = isset($_POST['razon_social']) ? trim($_POST['razon_social']) : '';
    $nro_comprobante= isset($_POST['nro_comprobante']) ? trim($_POST['nro_comprobante']) : '';

    // Usuario que factura (usuario del sistema logueado)
    $usuario_factura = isset($usuarioApp['id']) ? intval($usuarioApp['id']) : 0;

    $Facturacion->FacturarPorIdFactura($idfactura, [
        'nit'             => $nit,
        'razon_social'    => $razon_social,
        'nro_comprobante' => $nro_comprobante,
        'usuario_factura' => $usuario_factura
    ]);

    echo '<div class="alert alert-success text-center" style="margin:10px;">
            <strong>Factura actualizada correctamente.</strong>
          </div>
          <meta http-equiv="refresh" content="1;url=registro-de-ventas.php">';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?php echo TITULO; ?> - Registro de Ventas</title>
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

            <!-- Tabla de ventas agrupadas por factura -->
            <div class="row">
                <div class="col-sm-12">
                    <table cellpadding="0" cellspacing="0" border="0"
                        class="table table-striped table-bordered table-condensed" id="example">
                        <thead>
                            <tr>
                                <th>Id Factura</th>
                                <th>Cliente</th>
                                <th>Vendedor</th>
                                <th>Total</th>
                                <th>Comisión</th>
                                <th>Método de pago</th>
                                <th>Comprobante</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                        /*
                         * - Usamos TABLA VENTAS
                         * - Agrupamos por idfactura
                         */
                        $FacturasSql = $db->SQL("
                            SELECT idfactura
                            FROM ventas
                            WHERE idfactura IS NOT NULL
                            GROUP BY idfactura
                            ORDER BY idfactura DESC
                        ");

                        while ($row = $FacturasSql->fetch_assoc()):
                            $idfactura = $row['idfactura'];

                            // Resumen de ventas de esa factura
                            $DatosSql = $db->SQL("
                                SELECT
                                    SUM(totalprecio) AS total,
                                    SUM(comision)    AS total_comision,
                                    MIN(fecha)       AS fecha,
                                    MIN(hora)        AS hora,
                                    MIN(vendedor)    AS id_vendedor,
                                    MIN(cliente)     AS id_cliente,
                                    MIN(metodo_pago) AS metodo_pago,
                                    MIN(con_factura) AS con_factura,
                                    MIN(habilitada)  AS habilitada
                                FROM ventas
                                WHERE idfactura = '{$idfactura}'
                            ");
                            $Datos = $DatosSql->fetch_assoc();

                            // Vendedor
                            $VendedorNombre = 'Sin asignar';
                            if (!empty($Datos['id_vendedor'])) {
                                $VendedorSql = $db->SQL("
                                    SELECT nombre, apellido1, apellido2
                                    FROM vendedores
                                    WHERE id='{$Datos['id_vendedor']}'
                                ");
                                if ($VendedorSql && $VendedorSql->num_rows > 0) {
                                    $Vend = $VendedorSql->fetch_assoc();
                                    $VendedorNombre = trim(
                                        $Vend['nombre'] . ' ' .
                                        $Vend['apellido1'] . ' ' .
                                        $Vend['apellido2']
                                    );
                                }
                            }

                            // Cliente
                            $ClienteNombre = 'Cliente Contado';
                            if (!empty($Datos['id_cliente'])) {
                                $ClienteSql = $db->SQL("
                                    SELECT nombre
                                    FROM cliente
                                    WHERE id = '{$Datos['id_cliente']}'
                                ");
                                if ($ClienteSql && $ClienteSql->num_rows > 0) {
                                    $Cli = $ClienteSql->fetch_assoc();
                                    $ClienteNombre = $Cli['nombre'];
                                }
                            }

                            // Leer estado y tipo_comprobante desde factura
                            $FacturaSql = $db->SQL("
                                SELECT habilitado, tipo_comprobante,
                                       nit_cliente, razon_social
                                FROM factura
                                WHERE id='{$idfactura}'
                                LIMIT 1
                            ");
                            $Factura = $FacturaSql->fetch_assoc();

                            $estadoFactura   = isset($Factura['habilitado']) ? (int)$Factura['habilitado'] : (int)$Datos['habilitada'];
                            $tipoComprobante = isset($Factura['tipo_comprobante'])
                                ? $Factura['tipo_comprobante']
                                : ((int)$Datos['con_factura'] === 1 ? 'FACTURA' : 'RECIBO');

                            $nitFactura      = isset($Factura['nit_cliente']) ? $Factura['nit_cliente'] : '';
                            $razonFactura    = isset($Factura['razon_social']) ? $Factura['razon_social'] : '';

                            // Método de pago
                            $MetodoPago = $Datos['metodo_pago'] ?: 'EFECTIVO';

                            // Etiqueta estado
                            $EtiquetaEstado = ($estadoFactura === 1)
                                ? '<span class="label label-success">Activa</span>'
                                : '<span class="label label-danger">Cancelada</span>';

                            // Facturada o no (según MIN con_factura)
                            $yaFacturada = ((int)$Datos['con_factura'] === 1);
                        ?>
                            <tr>
                                <td><?php echo $idfactura; ?></td>
                                <td><?php echo htmlspecialchars($ClienteNombre); ?></td>
                                <td><?php echo htmlspecialchars($VendedorNombre); ?></td>
                                <td>Bs <?php echo number_format($Datos['total'], 2); ?></td>
                                <td>Bs <?php echo number_format($Datos['total_comision'], 2); ?></td>
                                <td><?php echo htmlspecialchars($MetodoPago); ?></td>
                                <td><?php echo htmlspecialchars($tipoComprobante); ?></td>
                                <td><?php echo $Datos['fecha'] . ' ' . $Datos['hora']; ?></td>
                                <td><?php echo $EtiquetaEstado; ?></td>
                                <td>
                                    <!-- Ver comprobante -->
                                    <a href="<?php echo URLBASE ?>reimprimir.php?id=<?php echo $idfactura; ?>"
                                        class="btn btn-primary btn-xs">
                                        Ver comprobante
                                    </a>

                                    <!-- Ver detalle -->
                                    <a href="<?php echo URLBASE; ?>detalle-venta.php?id=<?php echo $idfactura; ?>"
                                        class="btn btn-info btn-xs">
                                        Ver detalle
                                    </a>

                                    <?php if ($estadoFactura === 1): ?>
                                    <?php if (!$yaFacturada): ?>
                                    <!-- Botón Facturar -->
                                    <button type="button" class="btn btn-success btn-xs" data-toggle="modal"
                                        data-target="#FacturarModal<?php echo $idfactura; ?>">
                                        Facturar
                                    </button>
                                    <?php else: ?>
                                    <span class="label label-info">Facturada</span>
                                    <?php endif; ?>

                                    <!-- Botón Cancelar -->
                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal"
                                        data-target="#CancelarFactura<?php echo $idfactura; ?>">
                                        Cancelar
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-default btn-xs" disabled>
                                        Cancelada
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Modal FACTURAR -->
                            <div class="modal fade" id="FacturarModal<?php echo $idfactura; ?>" tabindex="-1"
                                role="dialog" aria-labelledby="FacturarLabel<?php echo $idfactura; ?>"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                                <h4 class="modal-title" id="FacturarLabel<?php echo $idfactura; ?>">
                                                    Facturar Venta #<?php echo $idfactura; ?>
                                                </h4>
                                            </div>
                                            <div class="modal-body">

                                                <input type="hidden" name="idfactura" value="<?php echo $idfactura; ?>">

                                                <div class="form-group">
                                                    <label>NIT</label>
                                                    <input type="text" name="nit" class="form-control"
                                                        value="<?php echo htmlspecialchars($nitFactura); ?>"
                                                        placeholder="NIT del cliente">
                                                </div>

                                                <div class="form-group">
                                                    <label>Razón Social</label>
                                                    <input type="text" name="razon_social" class="form-control"
                                                        value="<?php echo htmlspecialchars($razonFactura); ?>"
                                                        placeholder="Nombre o razón social">
                                                </div>

                                                <div class="form-group">
                                                    <label>Nro. Comprobante / Nro. Factura</label>
                                                    <input type="text" name="nro_comprobante" class="form-control"
                                                        placeholder="Número de factura o comprobante">
                                                </div>

                                                <p class="text-muted">
                                                    Los impuestos IVA (13%) e IT (3%) se calcularán automáticamente
                                                    sobre el total de la venta.
                                                </p>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default"
                                                    data-dismiss="modal">Cerrar</button>
                                                <button type="submit" name="ConfirmarFacturacion"
                                                    class="btn btn-success">
                                                    Confirmar Facturación
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- FIN Modal FACTURAR -->

                            <!-- Modal Cancelar Factura (ya lo tenías) -->
                            <div class="modal fade" id="CancelarFactura<?php echo $idfactura; ?>" tabindex="-1"
                                role="dialog" aria-labelledby="myModalLabel<?php echo $idfactura; ?>"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                            <h4 class="modal-title" id="myModalLabel<?php echo $idfactura; ?>">
                                                Cancelar Factura #<?php echo $idfactura; ?>
                                            </h4>
                                        </div>
                                        <div class="modal-body">
                                            <form class="form-horizontal" method="post" action="">
                                                <input type="hidden" name="Idfactura" value="<?php echo $idfactura; ?>">
                                                <!-- Campo MetodoPago (si CancelarFactura lo usa) -->
                                                <input type="hidden" name="MetodoPago"
                                                    value="<?php echo htmlspecialchars($MetodoPago); ?>">

                                                <div class="form-group">
                                                    <div class="col-sm-12">
                                                        <p>
                                                            ¿Est&aacute; seguro que desea cancelar la factura
                                                            #<?php echo $idfactura; ?>?
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-12 control-label">
                                                        Motivo de la cancelación
                                                    </label>
                                                    <div class="col-sm-12">
                                                        <textarea name="Comentario" class="form-control" rows="3"
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
                            <!-- FIN Modal Cancelar Factura -->

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
            "order": [
                [0, 'desc']
            ]
        });
    });
    </script>
</body>

</html>