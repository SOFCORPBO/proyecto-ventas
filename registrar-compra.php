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
   PROCESO PRINCIPAL - SOLO SI HAY POST DESDE tipo-compra.php
   =========================================================== */
if (isset($_POST['RegistrarCompra'])) {

    $vendedor = $usuarioApp['id'];

    // 1) LEER DATOS DEL FORMULARIO (tipo-compra.php)
    $tipo_comprobante = isset($_POST['tipo_comprobante']) ? $_POST['tipo_comprobante'] : 'RECIBO'; // FACTURA | RECIBO
    $con_factura      = isset($_POST['con_factura']) ? intval($_POST['con_factura']) : 0;         // 0 | 1
    $nit_cliente      = !empty($_POST['nit_cliente'])    ? $_POST['nit_cliente']    : null;
    $razon_social     = !empty($_POST['razon_social'])   ? $_POST['razon_social']   : null;

    $metodo_pago      = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'EFECTIVO';
    $id_banco         = !empty($_POST['id_banco'])   ? intval($_POST['id_banco'])  : null;
    $referencia       = !empty($_POST['referencia']) ? $_POST['referencia']        : null;

    // Si más adelante tienes un formulario de trámites, lo puedes enviar por POST
    $id_tramite       = !empty($_POST['id_tramite']) ? intval($_POST['id_tramite']) : null;

    // 2) OBTENER DATOS DE LA CAJA TEMPORAL
    $CarritoSql = $db->SQL("
        SELECT *
        FROM cajatmp
        WHERE vendedor = '{$vendedor}'
    ");

    if ($CarritoSql->num_rows <= 0) {
        // No hay venta cargada
        ?>
            <div class="page-header" id="banner">
                <h1>Error – No hay venta registrada</h1>
                <p class="lead">No hay servicios agregados en la caja temporal.</p>
            </div>
            <?php
    } else {

        // Obtener cliente desde el primer registro del carrito
        $DatosSql = $db->SQL("
            SELECT cliente
            FROM cajatmp
            WHERE vendedor='{$vendedor}'
            LIMIT 1
        ");
        $Datos   = $DatosSql->fetch_assoc();
        $cliente = intval($Datos['cliente']);

        // 3) CALCULAR TOTALES Y COMISIONES
        $TotalesSql = $db->SQL("
            SELECT 
                SUM(totalprecio) AS total,
                SUM(comision)    AS total_comision
            FROM cajatmp
            WHERE vendedor='{$vendedor}'
        ");
        $Totales        = $TotalesSql->fetch_assoc();
        $total_general  = floatval($Totales['total']);
        $total_comision = floatval($Totales['total_comision']);

        $fecha = FechaActual();
        $hora  = HoraActual();

        // Mapeo simple de "tipo" para seguir con la estructura base de factura
        // Por ejemplo: 1 = Contado / 2 = Otro
        $tipo = ($metodo_pago === 'EFECTIVO') ? 1 : 2;

        // 4) INSERTAR CABECERA EN TABLA factura (ESTRUCTURA BASE)
        $FacturaSql = $db->SQL("
            INSERT INTO factura (
                total,
                fecha,
                hora,
                usuario,
                cliente,
                tipo,
                habilitado
            ) VALUES (
                '{$total_general}',
                '{$fecha}',
                '{$hora}',
                '{$vendedor}',
                '{$cliente}',
                '{$tipo}',
                1
            )
        ");

        if (!$FacturaSql) {
            ?>
            <div class="page-header" id="banner">
                <h1>Error al registrar factura</h1>
                <p class="lead">Ocurrió un problema al crear la cabecera de la factura.</p>
            </div>
            <?php
        } else {

            // 5) OBTENER ID DE LA FACTURA Y GENERAR NRO_COMPROBANTE
            $IdFacturaSql = $db->SQL("
                SELECT MAX(id) AS ultimaid
                FROM factura
                WHERE usuario = '{$vendedor}'
            ");
            $IdFactura   = $IdFacturaSql->fetch_assoc();
            $idfactura   = intval($IdFactura['ultimaid']);

            // Nro comprobante autoincremental según lo que ya hay en ventas
            $NcSql   = $db->SQL("SELECT MAX(CAST(nro_comprobante AS UNSIGNED)) AS ultimo FROM ventas");
            $NcRow   = $NcSql->fetch_assoc();
            $ultimo  = !empty($NcRow['ultimo']) ? intval($NcRow['ultimo']) : 0;
            $nro_comp = $ultimo + 1; // siguiente número

            // 6) INSERTAR DETALLES EN TABLA ventas (UNO POR SERVICIO)
            while($c = $CarritoSql->fetch_assoc()){

                $producto   = intval($c['producto']);
                $cantidad   = intval($c['cantidad']);
                $precio     = floatval($c['precio']);
                $totalLinea = floatval($c['totalprecio']);
                $comision   = isset($c['comision']) ? floatval($c['comision']) : 0;

                $InsertVenta = $db->SQL("
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
                        habilitada,
                        nit,
                        razon_social,
                        referencia_pago,
                        nro_comprobante,
                        usuario_factura
                    ) VALUES (
                        '{$idfactura}',
                        '{$producto}',
                        '{$cantidad}',
                        '{$precio}',
                        '{$totalLinea}',
                        '{$vendedor}',
                        '{$cliente}',
                        '{$c['fecha']}',
                        '{$c['hora']}',
                        '{$tipo}',
                        '{$con_factura}',
                        '{$metodo_pago}',
                        ".($id_banco ? $id_banco : "NULL").",
                        ".($id_tramite ? $id_tramite : "NULL").",
                        '{$comision}',
                        1,
                        ".($nit_cliente   ? "'".$nit_cliente."'"   : "NULL").",
                        ".($razon_social ? "'".$razon_social."'" : "NULL").",
                        ".($referencia   ? "'".$referencia."'"   : "NULL").",
                        '{$nro_comp}',
                        '{$vendedor}'
                    )
                ");
            }

            // 7) ACTUALIZAR CAJA (SIGUIENDO LA LÓGICA BASE: TODA VENTA ENTRA A CAJA)
            $MaxIdCajaSql = $db->SQL("SELECT MAX(id) AS IdCaja FROM caja");
            $MaxIdCaja    = $MaxIdCajaSql->fetch_assoc();
            $db->SQL("
                UPDATE caja
                SET monto = monto + '{$total_general}'
                WHERE id = '{$MaxIdCaja['IdCaja']}'
            ");

            // 8) LIMPIAR CAJA TEMPORAL
            $db->SQL("DELETE FROM cajatmp WHERE vendedor='{$vendedor}'");

            // 9) OBTENER DATOS PARA MOSTRAR EL COMPROBANTE
            $localSql = $db->SQL("
                SELECT establecimiento
                FROM vendedores
                WHERE id='{$vendedor}'
            ");
            $local = $localSql->fetch_assoc();

            // Cabecera para mostrar
            $ventaCabSql = $db->SQL("
                SELECT 
                    v.idfactura,
                    v.fecha,
                    v.hora,
                    v.cliente,
                    v.nit,
                    v.razon_social,
                    v.metodo_pago,
                    v.nro_comprobante
                FROM ventas v
                WHERE v.idfactura = '{$idfactura}'
                LIMIT 1
            ");
            $ventaCab = $ventaCabSql->fetch_assoc();

            // Total general desde ventas (por seguridad)
            $TotalVentaSql = $db->SQL("
                SELECT SUM(totalprecio) AS total
                FROM ventas
                WHERE idfactura = '{$idfactura}'
            ");
            $TotalVenta = $TotalVentaSql->fetch_assoc();
            $totalFinal = floatval($TotalVenta['total']);
            ?>

            <div class="page-header" id="banner">
                <h1>Comprobante de Venta</h1>
                <p class="lead">Impresi&oacute;n de comprobante</p>
            </div>

            <div class="row">
                <div class="col-lg-12 well">
                    <table class="table table-bordered" style="background-color:#fff">
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
                                        <table width="95%">
                                            <tr>
                                                <td><br>
                                                    <strong><?php echo $local['establecimiento']; ?></strong><br>
                                                    <strong>Factura / Comprobante:</strong>
                                                    <?php echo $idfactura; ?><br>
                                                    <strong>Nro. Comprobante:</strong>
                                                    <?php echo $ventaCab['nro_comprobante']; ?><br>
                                                    <strong>Fecha:</strong>
                                                    <?php echo $ventaCab['fecha'].' '.$ventaCab['hora']; ?><br>
                                                    <strong>Cliente (ID):</strong>
                                                    <?php echo $ventaCab['cliente']; ?><br>
                                                    <?php if($ventaCab['nit']): ?>
                                                    <strong>NIT/CI:</strong> <?php echo $ventaCab['nit']; ?><br>
                                                    <?php endif; ?>
                                                    <?php if($ventaCab['razon_social']): ?>
                                                    <strong>Razón Social:</strong>
                                                    <?php echo $ventaCab['razon_social']; ?><br>
                                                    <?php endif; ?>
                                                    <strong>Método de Pago:</strong>
                                                    <?php echo $ventaCab['metodo_pago']; ?><br>
                                                </td>
                                            </tr>
                                        </table>

                                        <br>

                                        <table width="95%" border="0">
                                            <tr>
                                                <td align="center"><strong>Servicio</strong></td>
                                                <td align="center"><strong>Cantidad</strong></td>
                                                <td align="center"><strong>Valor</strong></td>
                                                <td align="center"><strong>Subtotal</strong></td>
                                            </tr>

                                            <?php
                                            $LineasSql = $db->SQL("
                                                SELECT v.*, p.nombre
                                                FROM ventas v
                                                INNER JOIN producto p ON p.id = v.producto
                                                WHERE v.idfactura = '{$idfactura}'
                                            ");
                                            while($l = $LineasSql->fetch_assoc()){
                                            ?>
                                            <tr>
                                                <td align="center"><?php echo $l['nombre']; ?></td>
                                                <td align="center"><?php echo $l['cantidad']; ?></td>
                                                <td align="center"><?php echo number_format($l['precio'],2); ?></td>
                                                <td align="center"><?php echo number_format($l['totalprecio'],2); ?>
                                                </td>
                                            </tr>
                                            <?php } ?>

                                            <tr>
                                                <td align="center"><strong>Total</strong></td>
                                                <td></td>
                                                <td></td>
                                                <td align="center">
                                                    <strong><?php echo number_format($totalFinal,2); ?></strong>
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
        } // fin else factura ok
    } // fin else carrito > 0

} else {
    // No vino desde el formulario
    ?>
            <div class="page-header" id="banner">
                <h1>Error – No hay venta registrada</h1>
                <p class="lead">Debe registrar una venta desde el POS antes de acceder a esta página.</p>
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