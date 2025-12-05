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

    /* 1) DATOS DEL FORMULARIO */
    $tipo_comprobante = $_POST['tipo_comprobante'] ?? 'RECIBO';
    $con_factura      = intval($_POST['con_factura'] ?? 0);
    $nit_cliente      = $_POST['nit_cliente'] ?: null;
    $razon_social     = $_POST['razon_social'] ?: null;

    $metodo_pago      = $_POST['metodo_pago'] ?? 'EFECTIVO';
    $id_banco         = !empty($_POST['id_banco']) ? intval($_POST['id_banco']) : null;
    $referencia       = $_POST['referencia'] ?: null;

    $id_tramite       = !empty($_POST['id_tramite']) ? intval($_POST['id_tramite']) : null;

    /* 2) LECTURA DEL CARRITO */
    $CarritoSql = $db->SQL("
        SELECT *
        FROM cajatmp
        WHERE vendedor='{$vendedor}'
    ");

    if ($CarritoSql->num_rows <= 0) {
        ?>
            <div class="page-header" id="banner">
                <h1>Error – No hay venta registrada</h1>
                <p class="lead">No hay servicios agregados en la caja temporal.</p>
            </div>
            <?php
    } else {

        /* Obtener cliente */
        $DatosSql = $db->SQL("
            SELECT cliente
            FROM cajatmp
            WHERE vendedor='{$vendedor}'
            LIMIT 1
        ");
        $cliente = intval($DatosSql->fetch_assoc()['cliente']);

        /* 3) TOTALES */
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

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        $tipo = ($metodo_pago == 'EFECTIVO') ? 1 : 2;

        /* 4) CREAR FACTURA */
        $FacturaSql = $db->SQL("
            INSERT INTO factura (
                total, fecha, hora, usuario, cliente, tipo, habilitado
            ) VALUES (
                '{$total_general}','{$fecha}','{$hora}','{$vendedor}',
                '{$cliente}','{$tipo}',1
            )
        ");

        if (!$FacturaSql) {
            ?>
            <div class="page-header" id="banner">
                <h1>Error al registrar factura</h1>
                <p class="lead">Ocurrió un problema al crear la cabecera.</p>
            </div>
            <?php
        } else {

            /* 5) ID FACTURA */
            $IdFacturaSql = $db->SQL("
                SELECT MAX(id) AS ultimaid
                FROM factura
                WHERE usuario='{$vendedor}'
            ");
            $idfactura = intval($IdFacturaSql->fetch_assoc()['ultimaid']);

            /* Nro Comprobante */
            $NcSql   = $db->SQL("SELECT MAX(CAST(nro_comprobante AS UNSIGNED)) AS ultimo FROM ventas");
            $ultimo  = intval($NcSql->fetch_assoc()['ultimo']);
            $nro_comp = $ultimo + 1;

            /* 6) INSERTAR DETALLES */
            while($c = $CarritoSql->fetch_assoc()){

                $producto   = intval($c['producto']);
                $cantidad   = intval($c['cantidad']);
                $precio     = floatval($c['precio']);
                $totalLinea = floatval($c['totalprecio']);
                $comision   = floatval($c['comision']);

                $db->SQL("
                    INSERT INTO ventas (
                        idfactura, producto, cantidad, precio, totalprecio,
                        vendedor, cliente, fecha, hora, tipo,
                        con_factura, metodo_pago, id_banco,
                        id_tramite, comision, habilitada,
                        nit, razon_social, referencia_pago,
                        nro_comprobante, usuario_factura
                    ) VALUES (
                        '{$idfactura}','{$producto}','{$cantidad}','{$precio}','{$totalLinea}',
                        '{$vendedor}','{$cliente}','{$fecha}','{$hora}','{$tipo}',
                        '{$con_factura}','{$metodo_pago}',
                        ".($id_banco ? $id_banco : "NULL").",
                        ".($id_tramite ? $id_tramite : "NULL").",
                        '{$comision}',1,
                        ".($nit_cliente   ? "'".$nit_cliente."'"   : "NULL").",
                        ".($razon_social ? "'".$razon_social."'" : "NULL").",
                        ".($referencia   ? "'".$referencia."'"   : "NULL").",
                        '{$nro_comp}','{$vendedor}'
                    )
                ");
            }

            /* ======================================================
               NUEVO: MOVIMIENTO AUTOMÁTICO A CAJA GENERAL
               ====================================================== */
            $conceptoVenta = "Venta de servicios - Factura #{$idfactura}";

            /* saldo anterior */
            $SaldoSQL = $db->SQL("SELECT saldo_caja FROM caja_general_movimientos ORDER BY id DESC LIMIT 1");
            $saldoAnterior = ($SaldoSQL->num_rows > 0)
                ? floatval($SaldoSQL->fetch_assoc()['saldo_caja'])
                : 0;

            /* nuevo saldo */
            $saldoNuevo = $saldoAnterior + $total_general;

            $id_banco_insert      = ($metodo_pago != 'EFECTIVO' && $id_banco) ? $id_banco : "NULL";
            $referencia_insert    = $referencia ? "'{$referencia}'" : "NULL";

            $db->SQL("
                INSERT INTO caja_general_movimientos
                (fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia,
                 responsable, saldo_caja)
                VALUES
                (
                    '{$fecha}','{$hora}','INGRESO','{$total_general}',
                    '{$conceptoVenta}','{$metodo_pago}',{$id_banco_insert},
                    {$referencia_insert},'{$vendedor}','{$saldoNuevo}'
                )
            ");

            /* ======================================================
               FIN DE REGISTRO EN CAJA GENERAL
               ====================================================== */

            /* 8) LIMPIAR CARRITO */
            $db->SQL("DELETE FROM cajatmp WHERE vendedor='{$vendedor}'");

            /* 9) OBTENER INFO PARA IMPRESIÓN */
            $localSql = $db->SQL("
                SELECT establecimiento
                FROM vendedores
                WHERE id='{$vendedor}'
            ");
            $local = $localSql->fetch_assoc();

            $ventaCabSql = $db->SQL("
                SELECT idfactura, fecha, hora, cliente, nit, razon_social, metodo_pago, nro_comprobante
                FROM ventas
                WHERE idfactura='{$idfactura}'
                LIMIT 1
            ");
            $ventaCab = $ventaCabSql->fetch_assoc();

            $TotalVentaSql = $db->SQL("
                SELECT SUM(totalprecio) AS total
                FROM ventas
                WHERE idfactura='{$idfactura}'
            ");
            $totalFinal = floatval($TotalVentaSql->fetch_assoc()['total']);

            ?>

            <div class="page-header" id="banner">
                <h1>Comprobante de Venta</h1>
                <p class="lead">Impresión de comprobante</p>
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
                                                INNER JOIN producto p ON p.id=v.producto
                                                WHERE v.idfactura='{$idfactura}'
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
        }
    }

} else {
    ?>
            <div class="page-header" id="banner">
                <h1>Error – No hay venta registrada</h1>
                <p class="lead">Debe registrar una venta desde el POS antes de acceder.</p>
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