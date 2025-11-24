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
// Menú
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

            <?php

/* ===========================================================
   PROCESO PRINCIPAL - SOLO SI HAY POST
   =========================================================== */
if(isset($_POST['RegistrarCompra'])){

    /* ======================================
       1) DATOS DESDE EL FORMULARIO
       ====================================== */
    $tipo_comprobante = $_POST['tipo_comprobante']; // FACTURA | RECIBO
    $con_factura      = $_POST['con_factura']; // 0 | 1
    $nit_cliente      = $_POST['nit_cliente'] ?? NULL;
    $razon_social     = $_POST['razon_social'] ?? NULL;

    $metodo_pago      = $_POST['metodo_pago']; // EFECTIVO / TRANSFERENCIA / TARJETA / DEPOSITO / MIXTO
    $id_banco         = !empty($_POST['id_banco']) ? $_POST['id_banco'] : NULL;
    $referencia       = $_POST['referencia'] ?? NULL;

    /* ======================================
       2) OBTENER DATOS DESDE cajatmp
       ====================================== */
    $vendedor = $usuarioApp['id'];

    $DatosSql = $db->SQL("
        SELECT vendedor, cliente 
        FROM cajatmp 
        WHERE vendedor='{$vendedor}' 
        LIMIT 1
    ");
    if($DatosSql->num_rows == 0){
        // No hay venta
        echo "<h3>No hay venta pendiente</h3>";
        exit;
    }
    $Datos = $DatosSql->fetch_assoc();
    $cliente = $Datos['cliente'];

    /* ======================================
       3) CALCULAR TOTALES Y COMISIONES
       ====================================== */
    $totales = $db->SQL("
        SELECT 
            SUM(totalprecio) AS total,
            SUM(comision) AS total_comision
        FROM cajatmp
        WHERE vendedor='{$vendedor}'
    ")->fetch_assoc();

    $total_general   = floatval($totales['total']);
    $total_comision  = floatval($totales['total_comision']);
    $total_caja      = $total_general - $total_comision;

    $fecha = FechaActual();
    $hora  = HoraActual();

    /* ======================================
       4) INSERTAR FACTURA
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
            0,
            0,
            '{$tipo_comprobante}',
            '{$total_general}',
            '{$total_comision}',
            '{$total_caja}',
            '{$fecha}',
            '{$hora}',
            '{$vendedor}',
            '{$cliente}',
            ".($nit_cliente ? "'$nit_cliente'" : "NULL").",
            ".($razon_social ? "'$razon_social'" : "NULL").",
            1,
            '{$metodo_pago}',
            ".($referencia ? "'$referencia'" : "NULL").",
            ".($id_banco ? $id_banco : "NULL").",
            1
        )
    ";
    $db->SQL($sqlFactura);

    /* Obtener ID factura recién generada */
    $IdFactura = $db->SQL("SELECT MAX(id) AS id FROM factura WHERE usuario='{$vendedor}'")->fetch_assoc();
    $idfactura = $IdFactura['id'];

    /* ======================================
       5) INSERTAR DETALLES EN ventas
       ====================================== */
    $CarritoSql = $db->SQL("SELECT * FROM cajatmp WHERE vendedor='{$vendedor}'");

    while($c = $CarritoSql->fetch_assoc()){

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
                hora,
                tipo,
                con_factura,
                metodo_pago,
                id_banco,
                id_tramite,
                comision,
                habilitada
            ) VALUES (
                '{$idfactura}',
                '{$c['producto']}',
                '{$c['cantidad']}',
                '{$c['precio']}',
                '{$c['totalprecio']}',
                '{$vendedor}',
                '{$cliente}',
                '{$c['fecha']}',
                '{$c['hora']}',
                1,
                '{$con_factura}',
                '{$metodo_pago}',
                ".($id_banco ? $id_banco : "NULL").",
                NULL,
                '{$c['comision']}',
                1
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
        $MaxIdCaja = $db->SQL("SELECT MAX(id) AS id FROM caja")->fetch_assoc();

        $db->SQL("
            UPDATE caja
            SET monto = monto + '{$total_caja}'
            WHERE id = '{$MaxIdCaja['id']}'
        ");
    }

    /* ======================================
       8) BORRAR CAJA TEMPORAL
       ====================================== */
    $db->SQL("DELETE FROM cajatmp WHERE vendedor='{$vendedor}'");

    /* ======================================
       9) MOSTRAR COMPROBANTE
       ====================================== */
    $local   = $db->SQL("SELECT establecimiento FROM vendedores WHERE id='{$vendedor}'")->fetch_assoc();
    $venta   = $db->SQL("SELECT total, fecha, hora, cliente FROM factura WHERE id='{$idfactura}'")->fetch_assoc();

    ?>

            <div class="page-header" id="banner">
                <h1>Comprobante de Compra</h1>
                <p class="lead">Impresión de comprobante</p>
            </div>

            <div class="row">
                <div class="col-lg-12 well">

                    <table class="table table-bordered" style="background:white">
                        <tr>
                            <td>
                                <center>
                                    <button onclick="imprimir();" class="btn btn-primary"><i class="fa fa-print"></i>
                                        <strong>IMPRIMIR</strong></button>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo URLBASE ?>" class="btn btn-default"><strong>No,
                                            Gracias</strong></a>

                                    <br><br>

                                    <div id="imprimeme">

                                        <table width="95%">
                                            <tr>
                                                <td><br>
                                                    <strong><?php echo $local['establecimiento']; ?></strong><br>
                                                    <strong>Factura:</strong> <?php echo $idfactura; ?><br>
                                                    <strong>Fecha:</strong>
                                                    <?php echo $venta['fecha'].' '.$venta['hora']; ?><br>
                                                    <strong>Cliente:</strong> <?php echo $venta['cliente']; ?><br>
                                                </td>
                                            </tr>
                                        </table>

                                        <br>

                                        <table width="95%" border="0">
                                            <tr>
                                                <td align="center"><strong>Servicio</strong></td>
                                                <td align="center"><strong>Cantidad</strong></td>
                                                <td align="center"><strong>Valor</strong></td>
                                            </tr>

                                            <?php
$Lineas = $db->SQL("SELECT * FROM ventas WHERE idfactura='{$idfactura}'");
while($l = $Lineas->fetch_assoc()){
    $prod = $db->SQL("SELECT nombre FROM producto WHERE id='{$l['producto']}'")->fetch_assoc();
?>
                                            <tr>
                                                <td align="center"><?php echo $prod['nombre']; ?></td>
                                                <td align="center"><?php echo $l['cantidad']; ?></td>
                                                <td align="center"><?php echo number_format($l['precio'],2); ?></td>
                                            </tr>
                                            <?php } ?>

                                            <tr>
                                                <td align="center"><strong>Total</strong></td>
                                                <td></td>
                                                <td align="center">
                                                    <strong><?php echo number_format($total_general,2); ?></strong></td>
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

            <?php } else { ?>

            <h1>Error – No hay venta registrada</h1>

            <?php } ?>

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