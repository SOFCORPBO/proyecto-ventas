<?php
session_start();
include ('sistema/configuracion.php');

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
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/sweet-alert.css" />
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap-combobox.css" />
    <?php include(MODULO.'Tema.CSS.php'); ?>
</head>

<body>
    <?php
// Menú inicio
if($usuarioApp['id_perfil']==2){
    include(MODULO.'menu_vendedor.php');
}elseif($usuarioApp['id_perfil']==1){
    include(MODULO.'menu_admin.php');
}else{
    echo'<meta http-equiv="refresh" content="0;url='.URLBASE.'cerrar-sesion"/>';
}
// Menú fin
?>

    <div id="wrap">
        <div class="container">
            <div class="page-header" id="banner">
                <div class="row">
                    <div class="col-lg-4 col-md-4 col-sm-4"></div>
                </div>
            </div>

            <div class="row">
                <?php
            // Acciones sobre el carrito
            $CajaDeVenta->EliminarProducto();
            $CajaDeVenta->LimpiarCarritoCompras();
            $CajaDeVenta->ActualizarCantidadCajaTmp();

            $ComprobarCierreCajaSQL = $db->SQL("SELECT id, tipo FROM cajaregistros ORDER BY id DESC LIMIT 1");
            $ComprobarCierreCaja    = $ComprobarCierreCajaSQL->fetch_assoc();
        ?>

                <div class="row">
                    <div class="col-md-12">

                        <!-- FORM PARA AGREGAR SERVICIOS AL CARRITO -->
                        <form name="nuevo_producto" action="" class="contact-form"
                            onsubmit="enviarDatosProducto(); return false">

                            <div class="row">

                                <!-- Cliente -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <select class="form-control clientes" name="cliente" id="select" required
                                            <?php if($ComprobarCierreCaja['tipo']==2) echo 'disabled'; ?>>
                                            <?php foreach($SelectorClientesArray as $SelectorClientesRow): ?>
                                            <option value="<?php echo $SelectorClientesRow['id']; ?>"
                                                <?php if($SelectorClientesRow['id']==1) echo 'selected="selected"'; ?>>
                                                <?php echo $SelectorClientesRow['nombre']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Fecha -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                            <input type="text" class="form-control" value="<?php echo FechaActual(); ?>"
                                                disabled>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hora -->
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Hora: <div id="hora"></div></label>
                                    </div>
                                </div>

                                <!-- Cajero -->
                                <div class="col-md-4">
                                    <div class="well well-sm">
                                        <center>Nombre del Cajero/a: <?php echo ucwords($usuarioApp['usuario']); ?>
                                        </center>
                                    </div>
                                </div>

                                <!-- Servicios -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <select class="form-control productos" name="codigo" id="select" autofocus>
                                            <option value=""></option>
                                            <?php foreach($ProductosStockArray as $p): ?>
                                            <option value="<?php echo $p['id']; ?>">
                                                <?php echo $p['codigo'].' - '.$p['nombre'].' ('.$p['tipo_servicio'].')'; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Cantidad -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <span class="input-group-addon"><strong>#</strong></span>
                                            <input type="number" min="1" step="1" class="form-control" name="cantidad"
                                                id="cantidad" value="1" onkeypress="return PermitirSoloNumeros(event);"
                                                required <?php if($ComprobarCierreCaja['tipo']==2) echo 'disabled'; ?>>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón Agregar -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <?php if($ComprobarCierreCaja['tipo']==2): ?>
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

                            </div>
                        </form>
                        <!-- FIN FORM AGREGAR SERVICIOS -->

                        <!-- Carrito / detalle -->
                        <div id="resultado"><?php include('consulta.php'); ?></div>

                        <!-- Formulario de cobro (Factura / Recibo / Métodos de pago) -->
                        <?php include (MODULO.'tipo-compra.php'); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include (MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script type="text/javascript">
    // Combobox
    $(document).ready(function() {
        $('.clientes').combobox();
        $('.productos').combobox();
    });

    // Permitir sólo números
    function PermitirSoloNumeros(e) {
        var keynum = window.event ? window.event.keyCode : e.which;
        if ((keynum == 8) || (keynum == 46)) return true;
        return /\d/.test(String.fromCharCode(keynum));
    }

    // Hora del servidor
    window.onload = hora;
    fecha = new Date("<?php echo date('d M Y h:i:s'); ?>");

    function hora() {
        var h = fecha.getHours(),
            m = fecha.getMinutes(),
            s = fecha.getSeconds();
        if (h < 10) h = '0' + h;
        if (m < 10) m = '0' + m;
        if (s < 10) s = '0' + s;
        document.getElementById('hora').innerHTML = h + ":" + m + ":" + s;
        fecha.setSeconds(fecha.getSeconds() + 1);
        setTimeout("hora()", 1000);
    }

    // Evitar doble envío al registrar venta
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

    <script src="<?php echo ESTATICO ?>js/sweet-alert.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/bootstrap-combobox.js"></script>
    <script src="<?php echo ESTATICO ?>js/ajax.js"></script>

</body>

</html>