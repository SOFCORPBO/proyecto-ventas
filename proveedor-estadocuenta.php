<?php
session_start();
define('acceso', true);

include('sistema/configuracion.php');
include('sistema/clase/proveedor.clase.php');

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Proveedor = new Proveedor();

$idProveedor = isset($_GET['id_proveedor']) ? (int)$_GET['id_proveedor'] : 0;

// Select proveedores
$ProveedoresSQL = $Proveedor->SelectorProveedores();

// KPIs + tablas solo si se selecciona proveedor
$kpi = [
    'deuda' => 0,
    'facturas' => 0,
    'pagos' => 0,
    'vencidas' => 0
];
$FacturasSQL = $PagosSQL = null;

if ($idProveedor > 0) {
    // KPI deuda
    $saldo = $Proveedor->ActualizarSaldo($idProveedor);
    $kpi['deuda'] = $saldo;

    // KPI facturas
    $kpi['facturas'] = $db->SQL("
        SELECT COUNT(*) total FROM proveedor_factura WHERE id_proveedor={$idProveedor}
    ")->fetch_assoc()['total'];

    // KPI pagos
    $kpi['pagos'] = $db->SQL("
        SELECT COUNT(*) total FROM proveedor_pago WHERE id_proveedor={$idProveedor}
    ")->fetch_assoc()['total'];

    // KPI vencidas
    $kpi['vencidas'] = $db->SQL("
        SELECT COUNT(*) total 
        FROM proveedor_factura 
        WHERE id_proveedor={$idProveedor} AND estado='VENCIDA'
    ")->fetch_assoc()['total'];

    // Facturas del proveedor
    $FacturasSQL = $db->SQL("
        SELECT * 
        FROM proveedor_factura
        WHERE id_proveedor={$idProveedor}
        ORDER BY fecha_emision DESC
    ");

    // Pagos del proveedor
    $PagosSQL = $db->SQL("
        SELECT * 
        FROM proveedor_pago
        WHERE id_proveedor={$idProveedor}
        ORDER BY fecha_pago DESC
    ");

    // Info del proveedor
    $ProvInfo = $db->SQL("
        SELECT * FROM proveedor WHERE id={$idProveedor}
    ")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de Cuenta Proveedor | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO.'Tema.CSS.php'); ?>

    <style>
        .kpi-box {
            padding: 15px;
            border-radius: 6px;
            color: #fff;
            text-align: center;
            margin-bottom: 15px;
        }
        .k1 { background:#3f51b5; }
        .k2 { background:#4caf50; }
        .k3 { background:#f44336; }
        .k4 { background:#ff9800; }
    </style>
</head>
<body>

<?php
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

<div class="container" id="wrap">

    <div class="page-header">
        <h1>Estado de Cuenta de Proveedor</h1>
    </div>

    <!-- FORM SELECCION PROVEEDOR -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Seleccione un proveedor</strong></div>
        <div class="panel-body">
            <form method="GET" class="form-inline">
                <select name="id_proveedor" class="form-control" required>
                    <option value="">-- Seleccionar proveedor --</option>
                    <?php
                    $ProveedoresSQL->data_seek(0);
                    while($p = $ProveedoresSQL->fetch_assoc()):
                    ?>
                    <option value="<?= $p['id'] ?>" <?= $idProveedor==$p['id']?'selected':'' ?>>
                        <?= $p['nombre'] ?> (<?= $p['tipo_proveedor'] ?>)
                    </option>
                    <?php endwhile; ?>
                </select>

                <button class="btn btn-primary">
                    <i class="fa fa-search"></i> Ver Estado de Cuenta
                </button>
            </form>
        </div>
    </div>

    <?php if ($idProveedor > 0 && $ProvInfo): ?>

        <!-- INFO PROVEEDOR + KPIs -->
        <div class="panel panel-default">
            <div class="panel-heading"><strong>Información del Proveedor</strong></div>
            <div class="panel-body">
                <p>
                    <strong>Nombre:</strong> <?= $ProvInfo['nombre'] ?><br>
                    <strong>Tipo:</strong> <?= $ProvInfo['tipo_proveedor'] ?><br>
                    <strong>Contacto:</strong> <?= $ProvInfo['contacto'] ?><br>
                    <strong>Teléfono:</strong> <?= $ProvInfo['telefono'] ?><br>
                    <strong>Dirección:</strong> <?= $ProvInfo['direccion'] ?><br>
                </p>

                <div class="row">
                    <div class="col-sm-3">
                        <div class="kpi-box k1">
                            <h2><?= number_format($kpi['deuda'],2) ?> Bs</h2>
                            <small>Saldo Pendiente</small>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="kpi-box k2">
                            <h2><?= (int)$kpi['facturas'] ?></h2>
                            <small>Total Facturas</small>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="kpi-box k3">
                            <h2><?= (int)$kpi['vencidas'] ?></h2>
                            <small>Facturas Vencidas</small>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="kpi-box k4">
                            <h2><?= (int)$kpi['pagos'] ?></h2>
                            <small>Pagos Registrados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLAS DETALLE -->
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-facturas" data-toggle="tab">Facturas</a></li>
            <li><a href="#tab-pagos" data-toggle="tab">Pagos</a></li>
        </ul>

        <div class="tab-content" style="margin-top:15px;">

            <!-- FACTURAS -->
            <div class="tab-pane fade in active" id="tab-facturas">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Facturas del proveedor</strong></div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped" id="tabla_facturas_prov_est">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nro Factura</th>
                                    <th>Emisión</th>
                                    <th>Vencimiento</th>
                                    <th>Monto Total</th>
                                    <th>Pagado</th>
                                    <th>Saldo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($f = $FacturasSQL->fetch_assoc()): 
                                    $saldo = $f['monto_total'] - $f['monto_pagado'];
                                    $label = 'default';
                                    if ($f['estado']=='PENDIENTE') $label='warning';
                                    if ($f['estado']=='PARCIAL')   $label='info';
                                    if ($f['estado']=='PAGADA')    $label='success';
                                    if ($f['estado']=='VENCIDA')   $label='danger';
                                ?>
                                <tr>
                                    <td><?= $f['id'] ?></td>
                                    <td><?= $f['numero_factura'] ?></td>
                                    <td><?= $f['fecha_emision'] ?></td>
                                    <td><?= $f['fecha_vencimiento'] ?></td>
                                    <td><?= number_format($f['monto_total'],2) ?> Bs</td>
                                    <td><?= number_format($f['monto_pagado'],2) ?> Bs</td>
                                    <td><strong><?= number_format($saldo,2) ?> Bs</strong></td>
                                    <td><span class="label label-<?= $label ?>"><?= $f['estado'] ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PAGOS -->
            <div class="tab-pane fade" id="tab-pagos">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Pagos realizados al proveedor</strong></div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped" id="tabla_pagos_prov_est">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Factura</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($p = $PagosSQL->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $p['id'] ?></td>
                                    <td><?= $p['fecha_pago'] ?></td>
                                    <td><?= $p['id_factura'] ?: '-' ?></td>
                                    <td><?= number_format($p['monto'],2) ?> Bs</td>
                                    <td><?= $p['metodo_pago'] ?></td>
                                    <td><?= $p['referencia'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>

</div>

<?php include(MODULO.'footer.php'); ?>
<?php include(MODULO.'Tema.JS.php'); ?>

<script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
<script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>
<script>
$('#tabla_facturas_prov_est').dataTable();
$('#tabla_pagos_prov_est').dataTable();
</script>

</body>
</html>
