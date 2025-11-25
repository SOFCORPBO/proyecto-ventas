<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de factura inválido";
    exit;
}

$idfactura = intval($_GET['id']);

/*
|------------------------------------------------------------
| CABECERA DE LA VENTA
|   - Intentamos leer desde factura (estructura base)
|   - Completamos con datos de vendedor y cliente
|------------------------------------------------------------
*/
$FacturaSQL = $db->SQL("
    SELECT 
        f.*,
        v.nombre   AS vendedor_nombre,
        v.apellido1 AS vendedor_apellido1,
        v.apellido2 AS vendedor_apellido2,
        c.nombre   AS cliente_nombre
    FROM factura f
    LEFT JOIN vendedores v ON v.id = f.usuario
    LEFT JOIN cliente   c ON c.id = f.cliente
    WHERE f.id = {$idfactura}
    LIMIT 1
");

if ($FacturaSQL->num_rows == 0) {
    // Si por alguna razón no existe factura, intentamos tomar datos mínimos desde ventas
    $CabeceraSql = $db->SQL("
        SELECT 
            MIN(fecha)   AS fecha,
            MIN(hora)    AS hora,
            MIN(vendedor) AS id_vendedor,
            MIN(cliente) AS id_cliente,
            SUM(totalprecio) AS total
        FROM ventas
        WHERE idfactura = {$idfactura}
    ");
    $Factura = $CabeceraSql->fetch_assoc();

    // Vendedor
    $Factura['vendedor_nombre'] = '';
    $Factura['vendedor_apellido1'] = '';
    $Factura['vendedor_apellido2'] = '';
    if (!empty($Factura['id_vendedor'])) {
        $VendSql = $db->SQL("SELECT nombre, apellido1, apellido2 FROM vendedores WHERE id='{$Factura['id_vendedor']}'");
        if ($VendSql && $VendSql->num_rows > 0) {
            $Vend = $VendSql->fetch_assoc();
            $Factura['vendedor_nombre']   = $Vend['nombre'];
            $Factura['vendedor_apellido1'] = $Vend['apellido1'];
            $Factura['vendedor_apellido2'] = $Vend['apellido2'];
        }
    }

    // Cliente
    $Factura['cliente_nombre'] = 'Cliente Contado';
    if (!empty($Factura['id_cliente'])) {
        $CliSql = $db->SQL("SELECT nombre FROM cliente WHERE id='{$Factura['id_cliente']}'");
        if ($CliSql && $CliSql->num_rows > 0) {
            $Cli = $CliSql->fetch_assoc();
            $Factura['cliente_nombre'] = $Cli['nombre'];
        }
    }

    // Total
    if (!isset($Factura['total'])) {
        $Factura['total'] = $Factura['totalprecio'] ?? 0;
    }

} else {
    $Factura = $FacturaSQL->fetch_assoc();
}

/*
|------------------------------------------------------------
| DETALLE DE LA VENTA
|   - AHORA desde tabla VENTAS (no detalle_venta)
|------------------------------------------------------------
*/
$DetalleSQL = $db->SQL("
    SELECT v.*, p.nombre
    FROM ventas v
    INNER JOIN producto p ON p.id = v.producto
    WHERE v.idfactura = {$idfactura}
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalle de Venta #<?php echo $idfactura; ?></title>
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
</head>

<body>
    <div class="container" style="margin-top:20px;">

        <h3>Detalle Venta #<?php echo $idfactura; ?></h3>

        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($Factura['cliente_nombre'] ?? ''); ?></p>
        <p><strong>Vendedor:</strong>
            <?php echo ucwords(trim(
                ($Factura['vendedor_nombre'] ?? '') . ' ' .
                ($Factura['vendedor_apellido1'] ?? '') . ' ' .
                ($Factura['vendedor_apellido2'] ?? '')
            )); ?>
        </p>
        <p><strong>Total:</strong> <?php echo isset($Factura['total']) ? number_format($Factura['total'], 2) : ''; ?>
        </p>
        <p><strong>Fecha:</strong> <?php echo ($Factura['fecha'] ?? '') . " " . ($Factura['hora'] ?? ''); ?></p>

        <hr>

        <h4>Servicios incluidos:</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Cant.</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $TotalDetalle = 0;
                while ($d = $DetalleSQL->fetch_assoc()):
                    $subtotal = $d['totalprecio'];
                    $TotalDetalle += $subtotal;
                ?>
                <tr>
                    <td><?php echo $d['nombre']; ?></td>
                    <td><?php echo $d['cantidad']; ?></td>
                    <td><?php echo number_format($d['precio'], 2); ?></td>
                    <td><?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="3" class="text-right"><strong>Total Detalle</strong></td>
                    <td><strong><?php echo number_format($TotalDetalle, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <a href="<?php echo URLBASE; ?>registro-de-ventas" class="btn btn-default">Volver al listado</a>
    </div>
</body>

</html>