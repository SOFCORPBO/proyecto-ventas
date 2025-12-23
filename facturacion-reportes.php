<?php
session_start();
define('acceso', true);

include("sistema/configuracion.php");
include("sistema/clase/facturacion_reportes.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Reportes = new FacturacionReportes();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2, '.', ','); }

$hoy = date('Y-m-d');

$filtros = [
    // Modo de reporte
    'reporte'     => $_GET['reporte']     ?? 'LISTADO', // DIARIO, RANGO, LISTADO, VENDEDOR, CLIENTE, SERVICIO
    // Fechas
    'fecha'       => $_GET['fecha']       ?? $hoy,      // para DIARIO
    'desde'       => $_GET['desde']       ?? date('Y-m-01'),
    'hasta'       => $_GET['hasta']       ?? $hoy,
    // Factura
    'con_factura' => $_GET['con_factura'] ?? ''         // '', 1, 0
];

// Normalizar modo
$filtros['reporte'] = strtoupper(trim($filtros['reporte']));
$modos_validos = ['DIARIO','RANGO','LISTADO','VENDEDOR','CLIENTE','SERVICIO'];
if (!in_array($filtros['reporte'], $modos_validos, true)) $filtros['reporte'] = 'LISTADO';

// KPI (si DIARIO -> usa fecha; si no -> usa desde/hasta)
$KPI = null;
$error_msg = '';

try {
    if ($filtros['reporte'] === 'DIARIO') {
        $KPI = $Reportes->ResumenDiario($filtros['fecha']);
    } else {
        $KPI = $Reportes->ResumenRango($filtros['desde'], $filtros['hasta']);
    }

    // Tablas según reporte
    $tabla = null;

    if ($filtros['reporte'] === 'LISTADO') {
        $desde = $filtros['desde'];
        $hasta = $filtros['hasta'];
        $tabla = $Reportes->ListadoDetallado($desde, $hasta, $filtros['con_factura']);

    } elseif ($filtros['reporte'] === 'VENDEDOR') {
        $tabla = $Reportes->ResumenPorVendedor($filtros['desde'], $filtros['hasta']);

    } elseif ($filtros['reporte'] === 'CLIENTE') {
        $tabla = $Reportes->ResumenPorCliente($filtros['desde'], $filtros['hasta']);

    } elseif ($filtros['reporte'] === 'SERVICIO') {
        $tabla = $Reportes->ResumenPorServicio($filtros['desde'], $filtros['hasta']);

    } elseif ($filtros['reporte'] === 'DIARIO') {
        // si quieres, para DIARIO mostramos listado del mismo día
        $tabla = $Reportes->ListadoDetallado($filtros['fecha'], $filtros['fecha'], $filtros['con_factura']);
    }

} catch (Throwable $e) {
    $error_msg = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Facturación - Reportes | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
        .kpi-box{
            padding: 18px;
            color:#fff;
            border-radius:8px;
            text-align:center;
            margin-bottom: 20px;
        }
        .k1{ background:#3f51b5; } /* Total ventas */
        .k2{ background:#4caf50; } /* Con factura */
        .k3{ background:#ff9800; } /* Sin factura */
        .k4{ background:#009688; } /* Monto facturado */
        .k5{ background:#607d8b; } /* Monto no facturado */
        .k6{ background:#00acc1; } /* Total bruto */
        .k7{ background:#5e35b1; } /* IVA */
        .k8{ background:#546e7a; } /* IT */

        .badge-facturada{ background:#4caf50; }
        .badge-sin-factura{ background:#f44336; }

        .muted-note{ font-size:12px; color:#777; margin-top:8px; }
    </style>
</head>

<body>

<?php
if ($usuarioApp['id_perfil'] == 1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

<div class="container" id="wrap">

    <div class="page-header">
        <h1>Reportes de Facturación</h1>
        <p class="text-muted">
            Panel de reportes contables: ventas facturadas / no facturadas, total bruto, IVA e IT.
        </p>
    </div>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger">
            <i class="fa fa-times"></i> <?= h($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- =============================
         KPI PRINCIPALES (como facturacion-ventas)
    ============================== -->
    <?php
        $tv = (int)($KPI['total_ventas'] ?? 0);
        $vf = (int)($KPI['ventas_con_factura'] ?? 0);
        $vn = (int)($KPI['ventas_sin_factura'] ?? 0);

        $br = (float)($KPI['total_bruto'] ?? 0);
        $iva= (float)($KPI['total_iva'] ?? 0);
        $it = (float)($KPI['total_it'] ?? 0);

        $tf = (float)($KPI['total_facturado'] ?? 0);
        $tn = (float)($KPI['total_no_facturado'] ?? 0);
    ?>

    <div class="row">
        <div class="col-sm-2">
            <div class="kpi-box k1">
                <h2><?= $tv ?></h2>
                <small>Total de Ventas</small>
            </div>
        </div>

        <div class="col-sm-2">
            <div class="kpi-box k2">
                <h2><?= $vf ?></h2>
                <small>Con factura</small>
            </div>
        </div>

        <div class="col-sm-2">
            <div class="kpi-box k3">
                <h2><?= $vn ?></h2>
                <small>Sin factura</small>
            </div>
        </div>

        <div class="col-sm-3">
            <div class="kpi-box k4">
                <h2><?= money($tf) ?> Bs</h2>
                <small>Monto facturado</small>
            </div>
        </div>

        <div class="col-sm-3">
            <div class="kpi-box k5">
                <h2><?= money($tn) ?> Bs</h2>
                <small>Monto no facturado</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-4">
            <div class="kpi-box k6">
                <h2><?= money($br) ?> Bs</h2>
                <small>Total Bruto</small>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="kpi-box k7">
                <h2><?= money($iva) ?> Bs</h2>
                <small>Total IVA</small>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="kpi-box k8">
                <h2><?= money($it) ?> Bs</h2>
                <small>Total IT</small>
            </div>
        </div>
    </div>

    <!-- =============================
         FILTROS (misma estructura)
    ============================== -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Filtros de Reporte</strong>
        </div>
        <div class="panel-body">
            <form method="get" class="form-inline">

                <label>Reporte:&nbsp;</label>
                <select name="reporte" class="form-control">
                    <option value="LISTADO"  <?= $filtros['reporte']==='LISTADO'?'selected':'' ?>>Listado Detallado</option>
                    <option value="DIARIO"   <?= $filtros['reporte']==='DIARIO'?'selected':'' ?>>Resumen Diario</option>
                    <option value="RANGO"    <?= $filtros['reporte']==='RANGO'?'selected':'' ?>>Resumen por Rango</option>
                    <option value="VENDEDOR" <?= $filtros['reporte']==='VENDEDOR'?'selected':'' ?>>Por Vendedor</option>
                    <option value="CLIENTE"  <?= $filtros['reporte']==='CLIENTE'?'selected':'' ?>>Por Cliente</option>
                    <option value="SERVICIO" <?= $filtros['reporte']==='SERVICIO'?'selected':'' ?>>Por Servicio</option>
                </select>

                <label>&nbsp;Fecha (diario):&nbsp;</label>
                <input type="date" name="fecha" value="<?= h($filtros['fecha']) ?>" class="form-control">

                <label>&nbsp;Desde:&nbsp;</label>
                <input type="date" name="desde" value="<?= h($filtros['desde']) ?>" class="form-control">

                <label>&nbsp;Hasta:&nbsp;</label>
                <input type="date" name="hasta" value="<?= h($filtros['hasta']) ?>" class="form-control">

                <label>&nbsp;Factura:&nbsp;</label>
                <select name="con_factura" class="form-control">
                    <option value=""  <?= $filtros['con_factura']===''?'selected':'' ?>>Todas</option>
                    <option value="1" <?= $filtros['con_factura']==='1'?'selected':'' ?>>Con factura</option>
                    <option value="0" <?= $filtros['con_factura']==='0'?'selected':'' ?>>Sin factura</option>
                </select>

                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-filter"></i> Aplicar
                </button>

                <a href="facturacion-reportes.php" class="btn btn-default">
                    Limpiar
                </a>

                <p class="muted-note">
                    Nota: En “Resumen Diario” se usa <strong>Fecha</strong>. En el resto se usa <strong>Desde/Hasta</strong>.
                </p>
            </form>
        </div>
    </div>

    <!-- =============================
         TABLA PRINCIPAL (misma estructura)
    ============================== -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Resultado: <?= h($filtros['reporte']) ?></strong>
        </div>
        <div class="panel-body">

            <?php if (in_array($filtros['reporte'], ['RANGO'], true)): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    Ya tienes el resumen en los KPI de arriba. Si necesitas detalle, cambia el reporte a <strong>Listado Detallado</strong>.
                </div>
            <?php endif; ?>

            <?php if ($filtros['reporte'] === 'DIARIO'): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    Resumen del día en KPI. Abajo se muestra el listado de ese mismo día (opcional).
                </div>
            <?php endif; ?>

            <?php if ($tabla && method_exists($tabla, 'num_rows') && $tabla->num_rows > 0): ?>

                <?php if (in_array($filtros['reporte'], ['LISTADO','DIARIO'], true)): ?>
                    <table class="table table-bordered table-striped" id="tabla_reporte">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha / Hora</th>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Cant.</th>
                                <th>Método Pago</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>IVA</th>
                                <th>IT</th>
                                <th>Vendedor</th>
                                <th>ID Factura</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($v = $tabla->fetch_assoc()): ?>
                            <?php $esFact = (int)($v['con_factura'] ?? 0) === 1; ?>
                            <tr>
                                <td><?= (int)($v['id'] ?? 0) ?></td>
                                <td><?= h(($v['fecha'] ?? '').' '.($v['hora'] ?? '')) ?></td>
                                <td><?= h($v['cliente_nombre'] ?? '') ?></td>
                                <td><?= h($v['servicio_nombre'] ?? '') ?></td>
                                <td><?= money($v['cantidad'] ?? 0) ?></td>
                                <td><?= h($v['metodo_pago'] ?? '-') ?></td>
                                <td><strong><?= money($v['totalprecio'] ?? 0) ?> Bs</strong></td>
                                <td>
                                    <?php if ($esFact): ?>
                                        <span class="badge badge-facturada">Con factura</span>
                                    <?php else: ?>
                                        <span class="badge badge-sin-factura">Sin factura</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= money($v['iva_monto'] ?? 0) ?></td>
                                <td><?= money($v['impuesto_monto'] ?? 0) ?></td>
                                <td><?= h($v['vendedor_nombre'] ?? '') ?></td>
                                <td><?= h($v['idfactura'] ?? '') ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>

                <?php elseif ($filtros['reporte'] === 'VENDEDOR'): ?>
                    <table class="table table-bordered table-striped" id="tabla_reporte">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vendedor</th>
                                <th>Ventas</th>
                                <th>Total</th>
                                <th>Facturado</th>
                                <th>No facturado</th>
                                <th>IVA</th>
                                <th>IT</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i=0; while($r = $tabla->fetch_assoc()): $i++; ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= h($r['vendedor_nombre'] ?? '') ?></td>
                                <td><?= (int)($r['total_ventas'] ?? 0) ?></td>
                                <td><strong><?= money($r['total_bruto'] ?? 0) ?> Bs</strong></td>
                                <td><?= money($r['total_facturado'] ?? 0) ?></td>
                                <td><?= money($r['total_no_facturado'] ?? 0) ?></td>
                                <td><?= money($r['total_iva'] ?? 0) ?></td>
                                <td><?= money($r['total_it'] ?? 0) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>

                <?php elseif ($filtros['reporte'] === 'CLIENTE'): ?>
                    <table class="table table-bordered table-striped" id="tabla_reporte">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Ventas</th>
                                <th>Total</th>
                                <th>Facturado</th>
                                <th>No facturado</th>
                                <th>IVA</th>
                                <th>IT</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i=0; while($r = $tabla->fetch_assoc()): $i++; ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= h($r['cliente_nombre'] ?? '') ?></td>
                                <td><?= (int)($r['total_ventas'] ?? 0) ?></td>
                                <td><strong><?= money($r['total_bruto'] ?? 0) ?> Bs</strong></td>
                                <td><?= money($r['total_facturado'] ?? 0) ?></td>
                                <td><?= money($r['total_no_facturado'] ?? 0) ?></td>
                                <td><?= money($r['total_iva'] ?? 0) ?></td>
                                <td><?= money($r['total_it'] ?? 0) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>

                <?php elseif ($filtros['reporte'] === 'SERVICIO'): ?>
                    <table class="table table-bordered table-striped" id="tabla_reporte">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Servicio</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                                <th>Facturado</th>
                                <th>No facturado</th>
                                <th>IVA</th>
                                <th>IT</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i=0; while($r = $tabla->fetch_assoc()): $i++; ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= h($r['servicio_nombre'] ?? '') ?></td>
                                <td><?= money($r['cantidad_total'] ?? 0) ?></td>
                                <td><strong><?= money($r['total_bruto'] ?? 0) ?> Bs</strong></td>
                                <td><?= money($r['total_facturado'] ?? 0) ?></td>
                                <td><?= money($r['total_no_facturado'] ?? 0) ?></td>
                                <td><?= money($r['total_iva'] ?? 0) ?></td>
                                <td><?= money($r['total_it'] ?? 0) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No hay registros para los filtros seleccionados.
                </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<?php include(MODULO.'footer.php'); ?>
<?php include(MODULO.'Tema.JS.php'); ?>

<script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
<script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>
<script>
$(function(){
    if ($('#tabla_reporte').length){
        $('#tabla_reporte').dataTable({
            "order": [[0, "desc"]]
        });
    }
});
</script>

</body>
</html>
