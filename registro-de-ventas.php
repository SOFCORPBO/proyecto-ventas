<?php
session_start();
include('sistema/configuracion.php');

// Mostrar errores SOLO si estás depurando
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

include_once("sistema/clase/facturacion_ventas.clase.php");
include_once("sistema/clase/venta.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
$usuario->ZonaAdministrador();

if (!isset($Venta)) {
    $Venta = new Venta();
}
$Facturacion = new FacturacionVentas();

/*
|------------------------------------------------------------
| PROCESAR CANCELACIÓN DE FACTURA (usa tu clase Venta)
|------------------------------------------------------------
*/
$Venta->CancelarFactura();

/*
|------------------------------------------------------------
| PROCESAR FACTURACIÓN (CONFIRMAR FACTURAR)
|------------------------------------------------------------
*/
if (isset($_POST['ConfirmarFacturacion'])) {

    $idfactura       = (int)($_POST['idfactura'] ?? 0);
    $nit             = isset($_POST['nit']) ? trim($_POST['nit']) : '';
    $razon_social    = isset($_POST['razon_social']) ? trim($_POST['razon_social']) : '';
    $nro_comprobante = isset($_POST['nro_comprobante']) ? trim($_POST['nro_comprobante']) : '';

    // Usuario que factura (usuario del sistema logueado)
    $usuario_factura = isset($usuarioApp['id']) ? (int)$usuarioApp['id'] : 0;

    if ($idfactura > 0) {
        $Facturacion->FacturarPorIdFactura($idfactura, [
            'nit'             => $nit,
            'razon_social'    => $razon_social,
            'nro_comprobante' => $nro_comprobante,
            'usuario_factura' => $usuario_factura
        ]);

        echo '<div class="alert alert-success text-center" style="margin:10px;">
                <strong>Factura actualizada correctamente.</strong>
              </div>
              <meta http-equiv="refresh" content="1;url=registro-ventas.php">';
        exit;
    }
}

/*
|------------------------------------------------------------
| FILTROS (GET)
|------------------------------------------------------------
*/
$hoy = date('Y-m-d');

$desde  = isset($_GET['desde']) ? trim($_GET['desde']) : '';
$hasta  = isset($_GET['hasta']) ? trim($_GET['hasta']) : '';
$metodo = isset($_GET['metodo']) ? trim($_GET['metodo']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : ''; // 1=activa,0=cancelada,''=todas

$where = "v.idfactura IS NOT NULL";

if ($desde !== '') $where .= " AND v.fecha >= '" . addslashes($desde) . "'";
if ($hasta !== '') $where .= " AND v.fecha <= '" . addslashes($hasta) . "'";
if ($metodo !== '') $where .= " AND v.metodo_pago = '" . addslashes($metodo) . "'";

if ($estado !== '') {
    // Preferimos estado de FACTURA si existe, si no cae al de VENTAS
    $where .= " AND ( (f.habilitado IS NOT NULL AND f.habilitado = '" . addslashes($estado) . "')
                    OR (f.habilitado IS NULL AND v.habilitada = '" . addslashes($estado) . "') )";
}

/*
|------------------------------------------------------------
| LISTA DE FACTURAS (IDFACTURA) SEGÚN FILTROS
|------------------------------------------------------------
*/
$FacturasSql = $db->SQL("
    SELECT v.idfactura
    FROM ventas v
    LEFT JOIN factura f ON f.id = v.idfactura
    WHERE {$where}
    GROUP BY v.idfactura
    ORDER BY v.idfactura DESC
");
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

    <style>
    .filtros-wrap .form-control {
        margin-right: 8px;
        margin-bottom: 8px;
    }

    .badge-soft {
        background: #f3f3f3;
        color: #333;
        border: 1px solid #ddd;
    }
    </style>
</head>

<body>
    <?php
if ($usuarioApp['id_perfil'] == 2) {
    include(MODULO . 'menu_vendedor.php');
} elseif ($usuarioApp['id_perfil'] == 1) {
    include(MODULO . 'menu_admin.php');
} else {
    echo '<meta http-equiv="refresh" content="0;url=' . URLBASE . 'cerrar-sesion"/>';
    exit;
}
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header" id="banner">
                <div class="row">
                    <div class="col-lg-8 col-md-7 col-sm-6">
                        <h1>Registro de Ventas</h1>
                        <p class="text-muted">Listado de facturas/ventas agrupadas por idfactura</p>
                    </div>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="panel panel-default filtros-wrap">
                <div class="panel-heading"><strong>Filtros</strong></div>
                <div class="panel-body">
                    <form method="get" class="form-inline">
                        <label>Desde:&nbsp;</label>
                        <input type="date" name="desde" value="<?php echo htmlspecialchars($desde ?: ''); ?>"
                            class="form-control">

                        <label>&nbsp;Hasta:&nbsp;</label>
                        <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta ?: ''); ?>"
                            class="form-control">

                        <label>&nbsp;Método:&nbsp;</label>
                        <select name="metodo" class="form-control">
                            <option value="">Todos</option>
                            <?php
                        $metodos = ['EFECTIVO','TRANSFERENCIA','DEPOSITO','TARJETA','QR'];
                        foreach ($metodos as $m) {
                            $sel = ($metodo === $m) ? 'selected' : '';
                            echo "<option value=\"{$m}\" {$sel}>{$m}</option>";
                        }
                        ?>
                        </select>

                        <label>&nbsp;Estado:&nbsp;</label>
                        <select name="estado" class="form-control">
                            <option value="" <?php echo ($estado==='' ? 'selected' : ''); ?>>Todas</option>
                            <option value="1" <?php echo ($estado==='1' ? 'selected' : ''); ?>>Activas</option>
                            <option value="0" <?php echo ($estado==='0' ? 'selected' : ''); ?>>Canceladas</option>
                        </select>

                        <button type="submit" class="btn btn-default">Buscar</button>
                        <a href="registro-ventas.php" class="btn btn-link">Limpiar</a>
                    </form>
                </div>
            </div>

            <!-- TABLA -->
            <div class="row">
                <div class="col-sm-12">
                    <table cellpadding="0" cellspacing="0" border="0"
                        class="table table-striped table-bordered table-condensed" id="tabla_registro_ventas">
                        <thead>
                            <tr>
                                <th>Id Factura</th>
                                <th>Cliente</th>
                                <th>Vendedor</th>
                                <th>Total</th>
                                <th>Comisión</th>
                                <th>Método</th>
                                <th>Comprobante</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th style="min-width:220px;">Opciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                    while ($row = $FacturasSql->fetch_assoc()):
                        $idfactura = (int)$row['idfactura'];

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
                        $Datos = $DatosSql ? $DatosSql->fetch_assoc() : null;
                        if (!$Datos) continue;

                        // Vendedor
                        $VendedorNombre = 'Sin asignar';
                        if (!empty($Datos['id_vendedor'])) {
                            $VendedorSql = $db->SQL("
                                SELECT nombre, apellido1, apellido2
                                FROM vendedores
                                WHERE id='{$Datos['id_vendedor']}'
                                LIMIT 1
                            ");
                            if ($VendedorSql && $VendedorSql->num_rows > 0) {
                                $Vend = $VendedorSql->fetch_assoc();
                                $VendedorNombre = trim($Vend['nombre'].' '.$Vend['apellido1'].' '.$Vend['apellido2']);
                            }
                        }

                        // Cliente
                        $ClienteNombre = 'Cliente Contado';
                        if (!empty($Datos['id_cliente'])) {
                            $ClienteSql = $db->SQL("
                                SELECT nombre
                                FROM cliente
                                WHERE id = '{$Datos['id_cliente']}'
                                LIMIT 1
                            ");
                            if ($ClienteSql && $ClienteSql->num_rows > 0) {
                                $Cli = $ClienteSql->fetch_assoc();
                                $ClienteNombre = $Cli['nombre'];
                            }
                        }

                        // Estado y tipo comprobante desde factura (si existe)
                        $FacturaSql = $db->SQL("
                            SELECT habilitado, tipo_comprobante, nit_cliente, razon_social
                            FROM factura
                            WHERE id='{$idfactura}'
                            LIMIT 1
                        ");
                        $Factura = ($FacturaSql && $FacturaSql->num_rows > 0) ? $FacturaSql->fetch_assoc() : null;

                        $estadoFactura = $Factura && isset($Factura['habilitado'])
                            ? (int)$Factura['habilitado']
                            : (int)$Datos['habilitada'];

                        $tipoComprobante = $Factura && !empty($Factura['tipo_comprobante'])
                            ? $Factura['tipo_comprobante']
                            : (((int)$Datos['con_factura'] === 1) ? 'FACTURA' : 'RECIBO');

                        $nitFactura   = $Factura && isset($Factura['nit_cliente']) ? (string)$Factura['nit_cliente'] : '';
                        $razonFactura = $Factura && isset($Factura['razon_social']) ? (string)$Factura['razon_social'] : '';

                        $MetodoPago = !empty($Datos['metodo_pago']) ? $Datos['metodo_pago'] : 'EFECTIVO';

                        $EtiquetaEstado = ($estadoFactura === 1)
                            ? '<span class="label label-success">Activa</span>'
                            : '<span class="label label-danger">Cancelada</span>';

                        $yaFacturada = ((int)$Datos['con_factura'] === 1);
                    ?>
                            <tr>
                                <td><?php echo (int)$idfactura; ?></td>
                                <td><?php echo htmlspecialchars($ClienteNombre); ?></td>
                                <td><?php echo htmlspecialchars($VendedorNombre); ?></td>
                                <td>Bs <?php echo number_format((float)$Datos['total'], 2); ?></td>
                                <td>Bs <?php echo number_format((float)$Datos['total_comision'], 2); ?></td>
                                <td><span class="label badge-soft"><?php echo htmlspecialchars($MetodoPago); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($tipoComprobante); ?></td>
                                <td><?php echo htmlspecialchars($Datos['fecha'].' '.$Datos['hora']); ?></td>
                                <td><?php echo $EtiquetaEstado; ?></td>
                                <td>
                                    <a href="<?php echo URLBASE ?>reimprimir.php?id=<?php echo (int)$idfactura; ?>"
                                        class="btn btn-primary btn-xs">
                                        Ver comprobante
                                    </a>

                                    <a href="<?php echo URLBASE; ?>detalle-venta.php?id=<?php echo (int)$idfactura; ?>"
                                        class="btn btn-info btn-xs">
                                        Ver detalle
                                    </a>

                                    <?php if ($estadoFactura === 1): ?>
                                    <?php if (!$yaFacturada): ?>
                                    <button type="button" class="btn btn-success btn-xs" data-toggle="modal"
                                        data-target="#FacturarModal<?php echo (int)$idfactura; ?>">
                                        Facturar
                                    </button>
                                    <?php else: ?>
                                    <span class="label label-info">Facturada</span>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-danger btn-xs" data-toggle="modal"
                                        data-target="#CancelarFactura<?php echo (int)$idfactura; ?>">
                                        Cancelar
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-default btn-xs" disabled>
                                        Cancelada
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- MODAL FACTURAR -->
                            <div class="modal fade" id="FacturarModal<?php echo (int)$idfactura; ?>" tabindex="-1"
                                role="dialog" aria-labelledby="FacturarLabel<?php echo (int)$idfactura; ?>"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                                <h4 class="modal-title"
                                                    id="FacturarLabel<?php echo (int)$idfactura; ?>">
                                                    Facturar Venta #<?php echo (int)$idfactura; ?>
                                                </h4>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="idfactura"
                                                    value="<?php echo (int)$idfactura; ?>">

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
                            <!-- FIN MODAL FACTURAR -->

                            <!-- MODAL CANCELAR FACTURA -->
                            <div class="modal fade" id="CancelarFactura<?php echo (int)$idfactura; ?>" tabindex="-1"
                                role="dialog" aria-labelledby="CancelLabel<?php echo (int)$idfactura; ?>"
                                aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                            <h4 class="modal-title" id="CancelLabel<?php echo (int)$idfactura; ?>">
                                                Cancelar Factura #<?php echo (int)$idfactura; ?>
                                            </h4>
                                        </div>
                                        <div class="modal-body">
                                            <form class="form-horizontal" method="post" action="">
                                                <input type="hidden" name="Idfactura"
                                                    value="<?php echo (int)$idfactura; ?>">
                                                <input type="hidden" name="MetodoPago"
                                                    value="<?php echo htmlspecialchars($MetodoPago); ?>">

                                                <div class="form-group">
                                                    <div class="col-sm-12">
                                                        <p>¿Está seguro que desea cancelar la factura
                                                            #<?php echo (int)$idfactura; ?>?</p>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-12 control-label">Motivo de la
                                                        cancelación</label>
                                                    <div class="col-sm-12">
                                                        <textarea name="Comentario" class="form-control" rows="3"
                                                            placeholder="Describa el motivo de cancelación"
                                                            required></textarea>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="col-sm-12">
                                                        <button type="button" class="btn btn-default"
                                                            data-dismiss="modal">Cerrar</button>
                                                        <button type="submit" name="CancelarFactura"
                                                            class="btn btn-primary">Sí, Cancelar</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- FIN MODAL CANCELAR -->

                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <?php include(MODULO . 'footer.php'); ?>
    <?php include(MODULO . 'Tema.JS.php'); ?>

    <script type="text/javascript" src="<?php echo ESTATICO; ?>js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="<?php echo ESTATICO; ?>js/dataTables.bootstrap.js"></script>

    <script>
    $(document).ready(function() {
        $('#tabla_registro_ventas').dataTable({
            "order": [
                [0, 'desc']
            ]
        });
    });
    </script>

</body>

</html>