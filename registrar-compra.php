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
    <title><?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
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
    exit;
}
?>

    <div id="wrap">
        <div class="container">

            <?php
/* ===========================================================
   PROCESO PRINCIPAL - SOLO SI LLEGA EL POST DEL FORM
   =========================================================== */
if(isset($_POST['RegistrarCompra'])){

    /* ======================================
       1) DATOS DESDE EL FORMULARIO
       ====================================== */
    $tipo_comprobante = isset($_POST['tipo_comprobante']) ? $_POST['tipo_comprobante'] : 'RECIBO'; // FACTURA | RECIBO
    $con_factura      = isset($_POST['con_factura']) ? intval($_POST['con_factura']) : 0;          // 0 | 1
    $nit_cliente      = !empty($_POST['nit_cliente']) ? trim($_POST['nit_cliente']) : NULL;
    $razon_social     = !empty($_POST['razon_social']) ? trim($_POST['razon_social']) : NULL;

    $metodo_pago      = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'EFECTIVO';        // EFECTIVO / TRANSFERENCIA / TARJETA / DEPOSITO
    $id_banco         = !empty($_POST['id_banco']) ? intval($_POST['id_banco']) : NULL;
    $referencia       = !empty($_POST['referencia']) ? trim($_POST['referencia']) : NULL;

    /* ======================================
       2) OBTENER DATOS DESDE cajatmp
       ====================================== */
    $vendedor = $usuarioApp['id'];

    // ¿Existe algo en el carrito?
    $DatosSql = $db->SQL("
        SELECT vendedor, cliente 
        FROM cajatmp 
        WHERE vendedor='{$vendedor}' 
        LIMIT 1
    ");
    if($DatosSql->num_rows == 0){
        // No hay venta
        ?>
            <div class="page-header" id="banner">
                <h1>Error – No hay venta pendiente</h1>
                <p class="lead">No se encontraron servicios en el carrito.</p>
            </div>
            <?php
        exit;
    }
    $Datos   = $DatosSql->fetch_assoc();
    $cliente = $Datos['cliente'];

    /* ======================================
       3) CALCULAR TOTALES Y COMISIONES
       ====================================== */
    $TotalesSql = $db->SQL("
        SELECT 
            SUM(totalprecio) AS total,
            SUM(comision)    AS total_comision
        FROM cajatmp
        WHERE vendedor='{$vendedor}'
    ");
    $totales = $TotalesSql->fetch_assoc();

    $total_general   = !empty($totales['total']) ? floatval($totales['total']) : 0;
    $total_comision  = !empty($totales['total_comision']) ? floatval($totales['total_comision']) : 0;
    $total_caja      = $total_general - $total_comision; // Lo que realmente entra a caja/banco

    if($total_general <= 0){
        ?>
            <div class="page-header" id="banner">
                <h1>Error – Monto inválido</h1>
                <p class="lead">El total de la venta es 0.</p>
            </div>
            <?php
        exit;
    }

    $fecha = FechaActual();
    $hora  = HoraActual();

    /* ======================================
       4) INSERTAR FACTURA
       (asegúrate que la tabla factura tiene estas columnas;
        si aún está con el modelo anterior, habrá que ajustar el SQL)
       ====================================== */
    $sqlFactura = "
        INSERT INTO factura (
            subtotal,
            iva,
            tipo_comprobante,
            total,
            total_comision,
            total_caja,
            fecha,
            hora,
            usuario,
            cliente,
            nit_cliente,
            razon_social,
            tipo,
            metodo_pago,
            referencia,
            id_banco,
            habilitado
        ) VALUES (
            0,                       -- subtotal (si después manejas IVA lo ajustas)
            0,                       -- iva
            '{$tipo_comprobante}',
            '{$total_general}',
            '{$total_comision}',
            '{$total_caja}',
            '{$fecha}',
            '{$hora}',
            '{$vendedor}',
            '{$cliente}',
            ".($nit_cliente   ? "'{$nit_cliente}'"   : "NULL").",
            ".($razon_social  ? "'{$razon_social}'"  : "NULL").",
            1,                       -- tipo (1 = venta normal)
            '{$metodo_pago}',
            ".($referencia    ? "'{$referencia}'"    : "NULL").",
            ".($id_banco      ? $id_banco           : "NULL").",
            1                        -- habilitado
        )
    ";
    $db->SQL($sqlFactura);

    // Obtener ID de factura recién generada
    $IdFacturaSql = $db->SQL("SELECT MAX(id) AS id FROM factura WHERE usuario='{$vendedor}'");
    $IdFactura    = $IdFacturaSql->fetch_assoc();
    $idfactura    = $IdFactura['id'];

    /* ======================================
       5) INSERTAR DETALLES EN detalle_venta
       ====================================== */
    $CarritoSql = $db->SQL("
        SELECT *
        FROM cajatmp
        WHERE vendedor='{$vendedor}'
    ");

    while($c = $CarritoSql->fetch_assoc()){

        $id_servicio = $c['producto'];         // id de producto/servicio
        $cantidad    = $c['cantidad'];
        $precio      = $c['precio'];
        $subtotal    = $c['totalprecio'];
        $comision    = isset($c['comision']) ? $c['comision'] : 0;

        // Inserta detalle
        $db->SQL("
            INSERT INTO detalle_venta (
                idfactura,
                id_servicio,
                cantidad,
                precio,
                subtotal,
                comision
            ) VALUES (
                '{$idfactura}',
                '{$id_servicio}',
                '{$cantidad}',
                '{$precio}',
                '{$subtotal}',
                '{$comision}'
            )
        ");

        // (OPCIONAL) También dejamos registrado en la tabla ventas para mantener compatibilidad
        // Ajusta las columnas de ventas si tu tabla ya fue extendida.
        $db->SQL("
            INSERT INTO ventas (
                idfactura,
                producto,
                cantidad,
                precio,
                totalprecio,
                vendedor,
                cliente,
                fecha,
                hora
            ) VALUES (
                '{$idfactura}',
                '{$id_servicio}',
                '{$cantidad}',
                '{$precio}',
                '{$subtotal}',
                '{$vendedor}',
                '{$cliente}',
                '{$c['fecha']}',
                '{$c['hora']}'
            )
        ");
    }

    /* ======================================
       6) MOVIMIENTO EN BANCO (si aplica)
       ====================================== */
    if($metodo_pago !== "EFECTIVO" && $id_banco){

        $db->SQL("
            INSERT INTO banco_movimientos (
                id_banco,
                fecha,
                tipo,
                monto,
                concepto,
                id_venta
            ) VALUES (
                {$id_banco},
                NOW(),
                'INGRESO',
                {$total_caja},
                'Venta factura #{$idfactura}',
                NULL
            )
        ");
    }

    /* ======================================
       7) ACTUALIZAR CAJA (solo efectivo)
       ====================================== */
    if($metodo_pago === "EFECTIVO"){
        // Última caja abierta
        $MaxIdCajaSql = $db->SQL("SELECT MAX(id) AS id FROM caja");
        $MaxIdCaja    = $MaxIdCajaSql->fetch_assoc();

        if($MaxIdCaja && $MaxIdCaja['id']){
            $db->SQL("
                UPDATE caja
                SET monto = monto + '{$total_caja}'
                WHERE id = '{$MaxIdCaja['id']}'
            ");
        }
    }

    /* ======================================
       8) LIMPIAR CAJA TEMPORAL
       ====================================== */
    $db->SQL("DELETE FROM cajatmp WHERE vendedor='{$vendedor}'");

    /* ======================================
       9) DATOS PARA MOSTRAR COMPROBANTE
       ====================================== */
    $LocalSql = $db->SQL("SELECT establecimiento FROM vendedores WHERE id='{$vendedor}'");
    $local    = $LocalSql->fetch_assoc();

    $ventaSql = $db->SQL("
        SELECT *
        FROM factura
        WHERE id='{$idfactura}'
    ");
    $venta = $ventaSql->fetch_assoc();

    // Datos cliente
    $Cli = $db->SQL("SELECT * FROM cliente WHERE id='{$venta['cliente']}'")->fetch_assoc();
    $nombre_cliente = $Cli ? $Cli['nombre'] : $venta['cliente'];

    ?>

            <div class="page-header" id="banner">
                <h1>Comprobante de Venta</h1>
                <p class="lead">Impresión de comprobante</p>
            </div>

            <div class="row">
                <div class="col-lg-12 well">

                    <table class="table table-bordered" style="background:white">
                        <tr>
                            <td>
                                <center>
                                    <button onclick="imprimir();" class="btn btn-primary">
                                        <i class="fa fa-print"></i> <strong>IMPRIMIR</strong>
                                    </button>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo URLBASE ?>" class="btn btn-default">
                                        <strong>No, Gracias</strong>
                                    </a>

                                    <br><br>

                                    <div id="imprimeme">

                                        <!-- CABECERA COMPROBANTE -->
                                        <table width="95%">
                                            <tr>
                                                <td><br>
                                                    <strong><?php echo $local['establecimiento']; ?></strong><br>
                                                    <strong>Comprobante:</strong> <?php echo $tipo_comprobante; ?><br>
                                                    <strong>Nro Factura:</strong> <?php echo $idfactura; ?><br>
                                                    <strong>Fecha:</strong>
                                                    <?php echo $venta['fecha'].' '.$venta['hora']; ?><br>
                                                    <strong>Cliente:</strong> <?php echo $nombre_cliente; ?><br>
                                                    <?php if(!empty($venta['nit_cliente'])): ?>
                                                    <strong>NIT/CI:</strong> <?php echo $venta['nit_cliente']; ?><br>
                                                    <?php endif; ?>
                                                    <?php if(!empty($venta['razon_social'])): ?>
                                                    <strong>Razón Social:</strong>
                                                    <?php echo $venta['razon_social']; ?><br>
                                                    <?php endif; ?>
                                                    <strong>Método de pago:</strong>
                                                    <?php echo $venta['metodo_pago']; ?><br>
                                                    <?php if(!empty($venta['referencia'])): ?>
                                                    <strong>Ref.:</strong> <?php echo $venta['referencia']; ?><br>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </table>

                                        <br>

                                        <!-- DETALLE DE SERVICIOS -->
                                        <table width="95%" border="0">
                                            <tr>
                                                <td align="center"><strong>Servicio</strong></td>
                                                <td align="center"><strong>Cantidad</strong></td>
                                                <td align="center"><strong>Precio</strong></td>
                                                <td align="center"><strong>Subtotal</strong></td>
                                            </tr>

                                            <?php
                                        $Lineas = $db->SQL("
                                            SELECT dv.*, p.nombre
                                            FROM detalle_venta dv
                                            INNER JOIN producto p ON p.id = dv.id_servicio
                                            WHERE dv.idfactura='{$idfactura}'
                                        ");
                                        while($l = $Lineas->fetch_assoc()):
                                        ?>
                                            <tr>
                                                <td align="center"><?php echo $l['nombre']; ?></td>
                                                <td align="center"><?php echo $l['cantidad']; ?></td>
                                                <td align="center"><?php echo number_format($l['precio'],2); ?></td>
                                                <td align="center"><?php echo number_format($l['subtotal'],2); ?></td>
                                            </tr>
                                            <?php endwhile; ?>

                                            <tr>
                                                <td align="center"><strong>Total</strong></td>
                                                <td></td>
                                                <td></td>
                                                <td align="center">
                                                    <strong><?php echo number_format($total_general,2); ?></strong>
                                                </td>
                                            </tr>
                                        </table>

                                        <br />
                                    </div>

                                </center>
                            </td>
                        </tr>
                    </table>

                </div>
            </div>

            <?php
} else {
    // Si entran directo sin POST
    ?>
            <div class="page-header" id="banner">
                <h1>Error – No hay venta registrada</h1>
                <p class="lead">No se ha enviado ninguna venta para registrar.</p>
            </div>
            <?php
}
?>

        </div>
    </div>

    <?php include (MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <script>
    function imprimir() {
        var objeto = document.getElementById('imprimeme');
        var ventana = window.open('', '_blank');
        ventana.document.write(objeto.innerHTML);
        ventana.document.close();
        ventana.print();
        ventana.close();
    }
    </script>

</body>

</html>