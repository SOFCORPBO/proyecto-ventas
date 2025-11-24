<?php
session_start();
include('sistema/configuracion.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

// Validar ID de factura
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de factura inválido";
    exit;
}

$idfactura = intval($_GET['id']);

// Obtener datos de la factura + nombre de cliente
$FacturaSQL = $db->SQL("
    SELECT 
        f.*,
        c.nombre AS nombre_cliente
    FROM factura f
    LEFT JOIN cliente c ON c.id = f.cliente
    WHERE f.id = {$idfactura}
    LIMIT 1
");

if ($FacturaSQL->num_rows == 0) {
    echo "Factura no encontrada";
    exit;
}

$Factura = $FacturaSQL->fetch_assoc();

// Obtener detalle desde tabla VENTAS (ya adaptada a servicios)
$DetalleSQL = $db->SQL("
    SELECT v.*, p.nombre 
    FROM ventas v
    INNER JOIN producto p ON p.id = v.producto
    WHERE v.idfactura = {$idfactura}
");

// Banco (si aplica)
$Banco = null;
if (!empty($Factura['id_banco'])) {
    $id_banco = intval($Factura['id_banco']);
    $BancoSQL = $db->SQL("
        SELECT nombre, numero_cuenta 
        FROM bancos 
        WHERE id = {$id_banco}
        LIMIT 1
    ");
    if ($BancoSQL->num_rows > 0) {
        $Banco = $BancoSQL->fetch_assoc();
    }
}

// Totales
$total_general  = (float)$Factura['total'];
$total_comision = isset($Factura['total_comision']) ? (float)$Factura['total_comision'] : 0;
$total_caja     = isset($Factura['total_caja']) ? (float)$Factura['total_caja'] : $total_general;

// Tipo comprobante / método pago
$tipo_comprobante = $Factura['tipo_comprobante']; // FACTURA | RECIBO
$metodo_pago      = $Factura['metodo_pago'];      // EFECTIVO, TRANSFERENCIA, etc.
$nit_cliente      = $Factura['nit_cliente'];
$razon_social     = $Factura['razon_social'];
$referencia       = $Factura['referencia'];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Comprobante #<?php echo $idfactura; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php // Usamos el mismo sistema de temas del proyecto ?>
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
    body {
        padding: 20px;
    }

    .tabla-detalle th,
    .tabla-detalle td {
        font-size: 13px;
    }

    .bloque-datos p {
        margin: 0;
    }

    @media print {
        .no-print {
            display: none;
        }
    }
    </style>
</head>

<body>

    <div class="container">

        <div class="row no-print">
            <div class="col-md-12 text-right">
                <button onclick="window.print();" class="btn btn-primary">
                    <i class="fa fa-print"></i> Imprimir
                </button>
                <a href="<?php echo URLBASE; ?>" class="btn btn-default">
                    Volver al sistema
                </a>
            </div>
        </div>

        <hr class="no-print">

        <div class="row">
            <div class="col-md-12">
                <h3>
                    Comprobante de Venta
                    <small>#<?php echo $idfactura; ?></small>
                </h3>
                <p><strong>Tipo de comprobante:</strong> <?php echo $tipo_comprobante; ?></p>
            </div>
        </div>

        <hr>

        <div class="row bloque-datos">
            <div class="col-md-6">
                <h4>Datos del Cliente</h4>
                <p><strong>Cliente:</strong>
                    <?php
                if (!empty($Factura['cliente']) && $Factura['cliente'] != 0) {
                    echo !empty($Factura['nombre_cliente'])
                        ? $Factura['nombre_cliente']." (ID: ".$Factura['cliente'].")"
                        : "ID Cliente: ".$Factura['cliente'];
                } else {
                    echo "Cliente Contado";
                }
                ?>
                </p>
                <?php if (!empty($nit_cliente)): ?>
                <p><strong>NIT / CI:</strong> <?php echo $nit_cliente; ?></p>
                <?php endif; ?>
                <?php if (!empty($razon_social)): ?>
                <p><strong>Razón Social:</strong> <?php echo $razon_social; ?></p>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <h4>Datos de la Operación</h4>
                <p><strong>Fecha y hora:</strong> <?php echo $Factura['fecha']." ".$Factura['hora']; ?></p>
                <p><strong>Método de pago:</strong> <?php echo $metodo_pago; ?></p>
                <?php if ($Banco): ?>
                <p><strong>Banco:</strong> <?php echo $Banco['nombre']; ?></p>
                <p><strong>N° de cuenta:</strong> <?php echo $Banco['numero_cuenta']; ?></p>
                <?php endif; ?>
                <?php if (!empty($referencia)): ?>
                <p><strong>Referencia:</strong> <?php echo $referencia; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <h4>Detalle de Servicios</h4>

        <?php if ($DetalleSQL->num_rows == 0): ?>

        <div class="alert alert-warning">
            No hay servicios registrados en esta venta.
        </div>

        <?php else: ?>

        <table class="table table-bordered table-striped tabla-detalle">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Cant.</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                    <th>Comisión</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $DetalleSQL->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['nombre']; ?></td>
                    <td class="text-center"><?php echo $row['cantidad']; ?></td>
                    <td class="text-right"><?php echo number_format($row['precio'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['totalprecio'], 2); ?></td>
                    <td class="text-right">
                        <?php echo isset($row['comision']) ? number_format($row['comision'], 2) : '0.00'; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 col-md-offset-8">
                <table class="table">
                    <tr>
                        <th class="text-right">Total servicios:</th>
                        <td class="text-right"><?php echo number_format($total_general, 2); ?></td>
                    </tr>
                    <tr>
                        <th class="text-right">Total comisión:</th>
                        <td class="text-right"><?php echo number_format($total_comision, 2); ?></td>
                    </tr>
                    <tr>
                        <th class="text-right">Total para caja:</th>
                        <td class="text-right"><?php echo number_format($total_caja, 2); ?></td>
                    </tr>
                </table>
            </div>
        </div>

    </div>

</body>

</html>