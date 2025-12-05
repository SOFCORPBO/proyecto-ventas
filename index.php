<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title><?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico" />

    <!-- Plugins POS -->
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/sweet-alert.css" />
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap-combobox.css" />

    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>
    <?php
// =========================
//  MENÚ SEGÚN PERFIL
// =========================
if ($usuarioApp['id_perfil'] == 2) {
    include(MODULO.'menu_vendedor.php');
} elseif ($usuarioApp['id_perfil'] == 1) {
    include(MODULO.'menu_admin.php');
} else {
    echo '<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
}
?>

    <div id="wrap">
        <div class="container">

            <!-- ENCABEZADO -->
            <div class="page-header" id="banner">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>POS - Venta de Servicios</h2>
                        <p class="text-muted">
                            Registra ventas de pasajes, paquetes turísticos, trámites y otros servicios.
                        </p>
                    </div>
                </div>
            </div>

            <?php
        // ======================================================
        // ACCIONES SOBRE EL CARRITO (CAJA TMP)
        // ======================================================
        // Estas funciones viven en la clase CajaDeVenta del núcleo
        $CajaDeVenta->EliminarProducto();
        $CajaDeVenta->LimpiarCarritoCompras();
        $CajaDeVenta->ActualizarCantidadCajaTmp();

        // Comprobar si hay apertura / cierre de caja
        $ComprobarCierreCajaSQL = $db->SQL("SELECT id, tipo FROM cajaregistros ORDER BY id DESC LIMIT 1");
        $ComprobarCierreCaja    = $ComprobarCierreCajaSQL->fetch_assoc();
        $CajaCerrada = (isset($ComprobarCierreCaja['tipo']) && $ComprobarCierreCaja['tipo'] == 2);
        ?>

            <div class="row">
                <div class="col-md-12">

                    <!-- ==========================================
                     FORMULARIO: AGREGAR SERVICIO AL CARRITO
                     ========================================== -->
                    <form id="form-agregar-servicio" name="nuevo_producto" action="" method="post" class="contact-form"
                        autocomplete="off" onsubmit="enviarDatosProducto(); return false">

                        <div class="row">

                            <!-- CLIENTE -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cliente</label>
                                    <select class="form-control clientes" name="cliente" required
                                        <?php if ($CajaCerrada) echo 'disabled'; ?>>
                                        <?php foreach ($SelectorClientesArray as $SelectorClientesRow): ?>
                                        <option value="<?php echo $SelectorClientesRow['id']; ?>"
                                            <?php if ($SelectorClientesRow['id'] == 1) echo 'selected="selected"'; ?>>
                                            <?php echo $SelectorClientesRow['nombre']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- FECHA -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="text" class="form-control" value="<?php echo FechaActual(); ?>"
                                            disabled>
                                    </div>
                                </div>
                            </div>

                            <!-- HORA -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Hora</label>
                                    <div class="well well-sm" style="margin-bottom:0;">
                                        <span id="hora"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- CAJERO -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cajero / Agente</label>
                                    <div class="well well-sm" style="margin-bottom:0;">
                                        <center>
                                            <?php echo ucwords($usuarioApp['usuario']); ?>
                                        </center>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /.row -->

                        <div class="row">

                            <!-- SERVICIO -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Servicio</label>
                                    <select class="form-control productos" name="codigo" required
                                        <?php if ($CajaCerrada) echo 'disabled'; ?>>
                                        <option value="">Seleccione un servicio...</option>
                                        <?php foreach ($ProductosStockArray as $p): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo $p['codigo'].' - '.$p['nombre'].' ('.$p['tipo_servicio'].')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- CANTIDAD -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><strong>#</strong></span>
                                        <input type="number" min="1" step="1" class="form-control" name="cantidad"
                                            id="cantidad" value="1" onkeypress="return PermitirSoloNumeros(event);"
                                            required <?php if ($CajaCerrada) echo 'disabled'; ?>>
                                    </div>
                                </div>
                            </div>

                            <!-- BOTÓN AGREGAR -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <?php if ($CajaCerrada): ?>
                                    <button type="button" class="btn btn-primary btn-block" data-container="body"
                                        data-toggle="popover" data-placement="top"
                                        data-content="No ha realizado la apertura de caja, debe abrir caja para poder facturar."
                                        title="Apertura de Caja">
                                        Agregar Servicio
                                    </button>
                                    <?php else: ?>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fa fa-plus"></i> Agregar Servicio
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div><!-- /.row -->
                    </form>
                    <!-- FIN FORM AGREGAR SERVICIO -->

                    <!-- ==========================================
                     DETALLE DEL CARRITO (SERVICIOS AGREGADOS)
                     ========================================== -->
                    <div id="resultado">
                        <?php include('consulta.php'); ?>
                    </div>

                    <!-- ==========================================
                     FORMULARIO DE COBRO / TIPO DE COMPRA
                     (FACTURA, RECIBO, MÉTODO DE PAGO, ETC.)
                     ========================================== -->
                    <?php include(MODULO.'tipo-compra.php'); ?>

                </div>
            </div>

        </div><!-- /.container -->
    </div><!-- /#wrap -->

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <!-- SCRIPTS POS -->
    <script src="<?php echo ESTATICO ?>js/sweet-alert.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/bootstrap-combobox.js"></script>
    <script src="<?php echo ESTATICO ?>js/ajax.js"></script>

    <script type="text/javascript">
    // Inicializar combobox
    $(document).ready(function() {
        $('.clientes').combobox();
        $('.productos').combobox();

        // Popover para mensaje de caja cerrada
        $('[data-toggle="popover"]').popover();
    });

    // Permitir sólo números en cantidad
    function PermitirSoloNumeros(e) {
        var keynum = window.event ? window.event.keyCode : e.which;
        if (keynum === 8 || keynum === 46) return true;
        return /\d/.test(String.fromCharCode(keynum));
    }

    // Hora del servidor (simulada en el cliente)
    window.onload = hora;
    var fecha = new Date("<?php echo date('d M Y H:i:s'); ?>");

    function hora() {
        var h = fecha.getHours(),
            m = fecha.getMinutes(),
            s = fecha.getSeconds();

        if (h < 10) h = '0' + h;
        if (m < 10) m = '0' + m;
        if (s < 10) s = '0' + s;

        document.getElementById('hora').innerHTML = h + ":" + m + ":" + s;
        fecha.setSeconds(fecha.getSeconds() + 1);
        setTimeout(hora, 1000);
    }

    // Evitar doble envío al registrar venta (en tipo-compra.php)
    var statSend = false;

    function ComprobarVenta() {
        if (!statSend) {
            statSend = true;
            return true;
        } else {
            swal("La venta ya se está procesando...");
            return false;
        }
    }
    </script>

</body>

</html>