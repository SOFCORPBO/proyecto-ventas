<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Helpers mínimos
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?php echo TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <?php include(MODULO . 'Tema.CSS.php'); ?>
</head>

<body>
    <?php
// Menú
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

            <?php
/* ===========================================================
   PROCESO PRINCIPAL - SOLO SI HAY POST DESDE tipo-compra.php
   =========================================================== */
if (isset($_POST['RegistrarCompra'])) {

    $vendedor = isset($usuarioApp['id']) ? (int)$usuarioApp['id'] : 0;
    if ($vendedor <= 0) {
        ?>
            <div class="page-header" id="banner">
                <h1>Error</h1>
                <p class="lead">No se pudo identificar el usuario vendedor.</p>
            </div>
            <?php
        exit;
    }

    /* 1) DATOS DEL FORMULARIO */
    $tipo_comprobante = $_POST['tipo_comprobante'] ?? 'RECIBO';
    $con_factura      = (int)($_POST['con_factura'] ?? 0);
    $nit_cliente      = !empty($_POST['nit_cliente']) ? trim($_POST['nit_cliente']) : null;
    $razon_social     = !empty($_POST['razon_social']) ? trim($_POST['razon_social']) : null;

    $metodo_pago  = $_POST['metodo_pago'] ?? 'EFECTIVO';
    $metodo_pago  = strtoupper(trim($metodo_pago));

    $id_banco     = !empty($_POST['id_banco']) ? (int)$_POST['id_banco'] : null;
    $referencia   = !empty($_POST['referencia']) ? trim($_POST['referencia']) : null;

    $id_tramite   = !empty($_POST['id_tramite']) ? (int)$_POST['id_tramite'] : null;

    // Normalizar método a los que manejas
    $metodosValidos = ['EFECTIVO','TRANSFERENCIA','DEPOSITO','TARJETA','QR'];
    if (!in_array($metodo_pago, $metodosValidos, true)) {
        $metodo_pago = 'EFECTIVO';
    }

    /* 2) LECTURA DEL CARRITO */
    $CarritoSql = $db->SQL("
        SELECT *
        FROM cajatmp
        WHERE vendedor='{$vendedor}'
    ");

    if (!$CarritoSql || $CarritoSql->num_rows <= 0) {
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
        $cliente = (int)($DatosSql && $DatosSql->num_rows ? $DatosSql->fetch_assoc()['cliente'] : 1);

        /* 3) TOTALES */
        $TotalesSql = $db->SQL("
            SELECT 
                SUM(totalprecio) AS total,
                SUM(comision)    AS total_comision
            FROM cajatmp
            WHERE vendedor='{$vendedor}'
        ");
        $Totales        = $TotalesSql ? $TotalesSql->fetch_assoc() : ['total'=>0,'total_comision'=>0];
        $total_general  = (float)$Totales['total'];
        $total_comision = (float)$Totales['total_comision'];

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        $tipo = ($metodo_pago === 'EFECTIVO') ? 1 : 2;

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
            $idfactura = (int)($IdFacturaSql && $IdFacturaSql->num_rows ? $IdFacturaSql->fetch_assoc()['ultimaid'] : 0);

            /* Nro Comprobante */
            $NcSql    = $db->SQL("SELECT MAX(CAST(nro_comprobante AS UNSIGNED)) AS ultimo FROM ventas");
            $ultimo   = (int)($NcSql && $NcSql->num_rows ? $NcSql->fetch_assoc()['ultimo'] : 0);
            $nro_comp = $ultimo + 1;

            /* 6) INSERTAR DETALLES */
            while ($c = $CarritoSql->fetch_assoc()) {

                $producto   = (int)$c['producto'];
                $cantidad   = (int)$c['cantidad'];
                $precio     = (float)$c['precio'];
                $totalLinea = (float)$c['totalprecio'];
                $comision   = (float)$c['comision'];

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
                        " . ($id_banco ? $id_banco : "NULL") . ",
                        " . ($id_tramite ? $id_tramite : "NULL") . ",
                        '{$comision}',1,
                        " . ($nit_cliente   ? "'" . addslashes($nit_cliente) . "'"   : "NULL") . ",
                        " . ($razon_social ? "'" . addslashes($razon_social) . "'" : "NULL") . ",
                        " . ($referencia   ? "'" . addslashes($referencia) . "'"   : "NULL") . ",
                        '{$nro_comp}','{$vendedor}'
                    )
                ");
            }

            /* ======================================================
               MOVIMIENTO AUTOMÁTICO A CAJA CHICA (INGRESO)
               - SIEMPRE SUMA al saldo_resultante, sin importar método.
               - Guarda método/banco/referencia dentro de "referencia"
                 (porque caja_chica_movimientos no tiene esas columnas).
               ====================================================== */
            $conceptoVenta = "Venta de servicios - Factura #{$idfactura}";

            // saldo anterior (por responsable)
            $SaldoSQL = $db->SQL("
                SELECT saldo_resultante
                FROM caja_chica_movimientos
                WHERE responsable='{$vendedor}'
                ORDER BY id DESC
                LIMIT 1
            ");

            $saldoAnterior = ($SaldoSQL && $SaldoSQL->num_rows > 0)
                ? (float)$SaldoSQL->fetch_assoc()['saldo_resultante']
                : 0.0;

            // SIEMPRE sumar
            $saldoNuevo = $saldoAnterior + $total_general;

            // referencia compacta (<=100)
            $refTxt = "V#{$idfactura} MP={$metodo_pago}";
            if (!empty($id_banco)) $refTxt .= " B={$id_banco}";
            if (!empty($referencia)) $refTxt .= " REF={$referencia}";
            $refTxt = substr($refTxt, 0, 100);

            $db->SQL("
                INSERT INTO caja_chica_movimientos
                    (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
                VALUES
                    (
                        '{$fecha}',
                        '{$hora}',
                        'INGRESO',
                        '{$total_general}',
                        '" . addslashes($conceptoVenta) . "',
                        '{$vendedor}',
                        '{$saldoNuevo}',
                        '" . addslashes($refTxt) . "'
                    )
            ");

            /* 8) LIMPIAR CARRITO */
            $db->SQL("DELETE FROM cajatmp WHERE vendedor='{$vendedor}'");

            /* 9) OBTENER INFO PARA IMPRESIÓN */
            $localSql = $db->SQL("
                SELECT establecimiento
                FROM vendedores
                WHERE id='{$vendedor}'
            ");
            $local = $localSql ? $localSql->fetch_assoc() : ['establecimiento' => ''];

            $ventaCabSql = $db->SQL("
                SELECT idfactura, fecha, hora, cliente, nit, razon_social, metodo_pago, nro_comprobante
                FROM ventas
                WHERE idfactura='{$idfactura}'
                LIMIT 1
            ");
            $ventaCab = $ventaCabSql ? $ventaCabSql->fetch_assoc() : [];

            $TotalVentaSql = $db->SQL("
                SELECT SUM(totalprecio) AS total
                FROM ventas
                WHERE idfactura='{$idfactura}'
            ");
            $totalFinal = (float)($TotalVentaSql && $TotalVentaSql->num_rows ? $TotalVentaSql->fetch_assoc()['total'] : 0);

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
                                                    <strong><?php echo h($local['establecimiento']); ?></strong><br>
                                                    <strong>Factura / Comprobante:</strong>
                                                    <?php echo (int)$idfactura; ?><br>
                                                    <strong>Nro. Comprobante:</strong>
                                                    <?php echo h($ventaCab['nro_comprobante'] ?? $nro_comp); ?><br>
                                                    <strong>Fecha:</strong>
                                                    <?php echo h(($ventaCab['fecha'] ?? $fecha) . ' ' . ($ventaCab['hora'] ?? $hora)); ?><br>
                                                    <strong>Cliente (ID):</strong>
                                                    <?php echo h($ventaCab['cliente'] ?? $cliente); ?><br>
                                                    <?php if (!empty($ventaCab['nit'])): ?>
                                                    <strong>NIT/CI:</strong> <?php echo h($ventaCab['nit']); ?><br>
                                                    <?php endif; ?>
                                                    <?php if (!empty($ventaCab['razon_social'])): ?>
                                                    <strong>Razón Social:</strong>
                                                    <?php echo h($ventaCab['razon_social']); ?><br>
                                                    <?php endif; ?>
                                                    <strong>Método de Pago:</strong>
                                                    <?php echo h($ventaCab['metodo_pago'] ?? $metodo_pago); ?><br>
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
                                            if ($LineasSql && $LineasSql->num_rows > 0):
                                                while ($l = $LineasSql->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td align="center"><?php echo h($l['nombre']); ?></td>
                                                <td align="center"><?php echo (int)$l['cantidad']; ?></td>
                                                <td align="center"><?php echo number_format((float)$l['precio'], 2); ?>
                                                </td>
                                                <td align="center">
                                                    <?php echo number_format((float)$l['totalprecio'], 2); ?></td>
                                            </tr>
                                            <?php
                                                endwhile;
                                            endif;
                                            ?>

                                            <tr>
                                                <td align="center"><strong>Total</strong></td>
                                                <td></td>
                                                <td></td>
                                                <td align="center">
                                                    <strong><?php echo number_format($totalFinal, 2); ?></strong>
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

    <?php include(MODULO . 'footer.php'); ?>
    <?php include(MODULO . 'Tema.JS.php'); ?>

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