<?php
session_start();
include("sistema/configuracion.php");
include("sistema/clase/contabilidad.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Cont = new Contabilidad();
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$tab   = $_GET['tab'] ?? 'dashboard';
$desde = $_GET['desde'] ?? date('Y-01-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$empresa = defined('TITULO') ? TITULO : 'EMPRESA';
$nit     = defined('NIT') ? NIT : '-';
$ciudad  = defined('CIUDAD') ? CIUDAD : 'SANTA CRUZ - BOLIVIA';

/* ==========================
   EXPORT / PRINT
========================== */
function output_table_excel_like($filename, $title, $headers, $rows, $as = 'xls'){
    $as = strtolower($as);
    if ($as === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }

    if ($as === 'doc') {
        header("Content-Type: application/msword; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"{$filename}.doc\"");
    } else {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
    }

    echo "<html><head><meta charset='utf-8'></head><body>";
    echo "<h2 style='font-family:Arial'>{$title}</h2>";
    echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;font-family:Arial;font-size:12px;'>";
    echo "<tr>";
    foreach($headers as $th) echo "<th style='background:#f2f2f2'>{$th}</th>";
    echo "</tr>";
    foreach($rows as $r){
        echo "<tr>";
        foreach($r as $td) echo "<td>".h($td)."</td>";
        echo "</tr>";
    }
    echo "</table></body></html>";
    exit;
}

if (isset($_GET['export']) && $_GET['export']=='1') {
    $rep = $_GET['rep'] ?? '';
    $fmt = $_GET['fmt'] ?? 'xls';

    if ($rep === 'estado_resultados') {
        $ER = $Cont->EstadoResultadosData($desde, $hasta);

        $headers = ['TIPO','CUENTA','NOMBRE','DEBE','HABER','SALDO'];
        $rows = [];
        foreach($ER['ingresos'] as $r){
            $rows[] = ['INGRESO', $r['codigo'], $r['nombre'], number_format((float)$r['debe'],2), number_format((float)$r['haber'],2), number_format((float)$r['saldo'],2)];
        }
        $rows[] = ['','','TOTAL INGRESOS','','', number_format((float)$ER['total_ingresos'],2)];
        $rows[] = ['','','','','',''];
        foreach($ER['gastos'] as $r){
            $rows[] = ['GASTO', $r['codigo'], $r['nombre'], number_format((float)$r['debe'],2), number_format((float)$r['haber'],2), number_format((float)$r['saldo'],2)];
        }
        $rows[] = ['','','TOTAL GASTOS','','', number_format((float)$ER['total_gastos'],2)];
        $rows[] = ['','','UTILIDAD NETA','','', number_format((float)$ER['utilidad'],2)];

        output_table_excel_like("estado_resultados_{$desde}_{$hasta}", "ESTADO DE RESULTADOS ({$desde} al {$hasta})", $headers, $rows, $fmt);
    }

    if ($rep === 'balance_general') {
        $BG = $Cont->BalanceGeneralData($desde, $hasta);

        $headers = ['TIPO','CUENTA','NOMBRE','DEBE','HABER','SALDO'];
        $rows = [];
        foreach($BG['activos'] as $r){
            $rows[] = ['ACTIVO', $r['codigo'], $r['nombre'], number_format((float)$r['debe'],2), number_format((float)$r['haber'],2), number_format((float)$r['saldo'],2)];
        }
        $rows[] = ['','','TOTAL ACTIVO','','', number_format((float)$BG['total_activo'],2)];
        $rows[] = ['','','','','',''];
        foreach($BG['pasivos'] as $r){
            $rows[] = ['PASIVO', $r['codigo'], $r['nombre'], number_format((float)$r['debe'],2), number_format((float)$r['haber'],2), number_format((float)$r['saldo'],2)];
        }
        $rows[] = ['','','TOTAL PASIVO','','', number_format((float)$BG['total_pasivo'],2)];
        $rows[] = ['','','','','',''];
        foreach($BG['patrimonio'] as $r){
            $rows[] = ['PATRIMONIO', $r['codigo'], $r['nombre'], number_format((float)$r['debe'],2), number_format((float)$r['haber'],2), number_format((float)$r['saldo'],2)];
        }
        $rows[] = ['','','TOTAL PATRIMONIO','','', number_format((float)$BG['total_patrimonio'],2)];

        output_table_excel_like("balance_general_{$desde}_{$hasta}", "BALANCE GENERAL ({$desde} al {$hasta})", $headers, $rows, $fmt);
    }

    if ($rep === 'balance_comprobacion') {
        $BC = $Cont->BalanceComprobacionDetallado($desde, $hasta);
        $headers = ['CUENTA','NOMBRE','TIPO','DEBE','HABER','SALDO'];
        $rows = [];
        foreach($BC as $r){
            $rows[] = [
                $r['codigo'], $r['nombre'], $r['tipo'],
                number_format((float)$r['debe'],2),
                number_format((float)$r['haber'],2),
                number_format((float)$r['saldo'],2)
            ];
        }
        output_table_excel_like("balance_comprobacion_{$desde}_{$hasta}", "BALANCE DE COMPROBACION ({$desde} al {$hasta})", $headers, $rows, $fmt);
    }

    // si llega aquí
    header("Location: conta-reportes.php");
    exit;
}

if (isset($_GET['print']) && $_GET['print']=='1') {
    $rep = $_GET['rep'] ?? '';
    $fechaImp = date('d/m/Y');
    $horaImp  = date('H:i:s');

    // data
    $title = '';
    $tableHTML = '';

    if ($rep==='balance_general') {
        $BG = $Cont->BalanceGeneralData($desde,$hasta);
        $title = "BALANCE GENERAL";
        ob_start();
        ?>
<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6">
    <thead>
        <tr style="background:#f2f2f2">
            <th>Tipo</th>
            <th>Cuenta</th>
            <th>Nombre</th>
            <th style="text-align:right">Debe</th>
            <th style="text-align:right">Haber</th>
            <th style="text-align:right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($BG['activos'] as $r): ?>
        <tr>
            <td>ACTIVO</td>
            <td><?=h($r['codigo'])?></td>
            <td><?=h($r['nombre'])?></td>
            <td style="text-align:right"><?=number_format((float)$r['debe'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['haber'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['saldo'],2)?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold">
            <td colspan="5" style="text-align:right">TOTAL ACTIVO</td>
            <td style="text-align:right"><?=number_format((float)$BG['total_activo'],2)?></td>
        </tr>

        <tr>
            <td colspan="6" style="height:8px;background:#fff"></td>
        </tr>

        <?php foreach($BG['pasivos'] as $r): ?>
        <tr>
            <td>PASIVO</td>
            <td><?=h($r['codigo'])?></td>
            <td><?=h($r['nombre'])?></td>
            <td style="text-align:right"><?=number_format((float)$r['debe'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['haber'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['saldo'],2)?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold">
            <td colspan="5" style="text-align:right">TOTAL PASIVO</td>
            <td style="text-align:right"><?=number_format((float)$BG['total_pasivo'],2)?></td>
        </tr>

        <tr>
            <td colspan="6" style="height:8px;background:#fff"></td>
        </tr>

        <?php foreach($BG['patrimonio'] as $r): ?>
        <tr>
            <td>PATRIMONIO</td>
            <td><?=h($r['codigo'])?></td>
            <td><?=h($r['nombre'])?></td>
            <td style="text-align:right"><?=number_format((float)$r['debe'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['haber'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['saldo'],2)?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold">
            <td colspan="5" style="text-align:right">TOTAL PATRIMONIO</td>
            <td style="text-align:right"><?=number_format((float)$BG['total_patrimonio'],2)?></td>
        </tr>
    </tbody>
</table>
<?php
        $tableHTML = ob_get_clean();
    }

    if ($rep==='estado_resultados') {
        $ER = $Cont->EstadoResultadosData($desde,$hasta);
        $title = "ESTADO DE RESULTADOS";
        ob_start();
        ?>
<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6">
    <thead>
        <tr style="background:#f2f2f2">
            <th>Tipo</th>
            <th>Cuenta</th>
            <th>Nombre</th>
            <th style="text-align:right">Debe</th>
            <th style="text-align:right">Haber</th>
            <th style="text-align:right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($ER['ingresos'] as $r): ?>
        <tr>
            <td>INGRESO</td>
            <td><?=h($r['codigo'])?></td>
            <td><?=h($r['nombre'])?></td>
            <td style="text-align:right"><?=number_format((float)$r['debe'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['haber'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['saldo'],2)?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold">
            <td colspan="5" style="text-align:right">TOTAL INGRESOS</td>
            <td style="text-align:right"><?=number_format((float)$ER['total_ingresos'],2)?></td>
        </tr>

        <tr>
            <td colspan="6" style="height:8px;background:#fff"></td>
        </tr>

        <?php foreach($ER['gastos'] as $r): ?>
        <tr>
            <td>GASTO</td>
            <td><?=h($r['codigo'])?></td>
            <td><?=h($r['nombre'])?></td>
            <td style="text-align:right"><?=number_format((float)$r['debe'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['haber'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['saldo'],2)?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold">
            <td colspan="5" style="text-align:right">TOTAL GASTOS</td>
            <td style="text-align:right"><?=number_format((float)$ER['total_gastos'],2)?></td>
        </tr>

        <tr style="font-weight:bold">
            <td colspan="5" style="text-align:right">UTILIDAD NETA</td>
            <td style="text-align:right"><?=number_format((float)$ER['utilidad'],2)?></td>
        </tr>
    </tbody>
</table>
<?php
        $tableHTML = ob_get_clean();
    }

    if ($rep==='balance_comprobacion') {
        $BC = $Cont->BalanceComprobacionDetallado($desde,$hasta);
        $title = "BALANCE DE COMPROBACION";
        ob_start();
        ?>
<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6">
    <thead>
        <tr style="background:#f2f2f2">
            <th>Cuenta</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th style="text-align:right">Debe</th>
            <th style="text-align:right">Haber</th>
            <th style="text-align:right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($BC as $r): ?>
        <tr>
            <td><?=h($r['codigo'])?></td>
            <td><?=h($r['nombre'])?></td>
            <td><?=h($r['tipo'])?></td>
            <td style="text-align:right"><?=number_format((float)$r['debe'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['haber'],2)?></td>
            <td style="text-align:right"><?=number_format((float)$r['saldo'],2)?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
        $tableHTML = ob_get_clean();
    }

    if ($title==='') { echo "Reporte no válido"; exit; }

    ?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?=h($title)?></title>
    <style>
    body {
        font-family: Arial;
        font-size: 12px;
        color: #111
    }

    .top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px
    }

    .title {
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        margin: 8px 0 14px
    }

    .sub {
        text-align: center;
        margin-top: -8px;
        color: #444
    }

    .box {
        border: 2px solid #000;
        padding: 10px;
        margin-bottom: 14px
    }

    @media print {
        .noprint {
            display: none
        }
    }
    </style>
</head>

<body>
    <div class="noprint" style="text-align:right;margin-bottom:8px;">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <div class="top">
        <div>
            <div><strong><?=h($empresa)?></strong></div>
            <div>NIT: <?=h($nit)?></div>
            <div><?=h($ciudad)?></div>
        </div>
        <div style="text-align:right;">
            <div>Página: 1</div>
            <div>Fecha: <?=h($fechaImp)?></div>
            <div>Hora: <?=h($horaImp)?></div>
        </div>
    </div>

    <div class="title"><?=h($title)?></div>
    <div class="sub">Del <?=h(date('d/m/Y',strtotime($desde)))?> al <?=h(date('d/m/Y',strtotime($hasta)))?> (Expresado
        en Bolivianos)</div>

    <div class="box">
        <?=$tableHTML?>
    </div>
</body>

</html>
<?php
    exit;
}

/* ==========================
   DATA PARA UI
========================== */
$kpi = $Cont->KPI_Reportes($desde,$hasta);
$serie = $Cont->SerieMensualIngresosGastos($desde,$hasta);
$top = $Cont->TopCuentasMovimiento($desde,$hasta,10);

$ER = $Cont->EstadoResultadosData($desde,$hasta);
$BG = $Cont->BalanceGeneralData($desde,$hasta);
$BC = $Cont->BalanceComprobacionDetallado($desde,$hasta);

$labels = [];
$ingData = [];
$gasData = [];
foreach($serie as $r){
    $labels[] = $r['ym'];
    $ingData[] = (float)($r['ingresos'] ?? 0);
    $gasData[] = (float)($r['gastos'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reportes Contables | <?=h(TITULO)?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?=ESTATICO?>img/favicon.ico">
    <link rel="stylesheet" href="<?=ESTATICO?>css/bootstrap.min.css">
    <?php include(MODULO."Tema.CSS.php"); ?>
    <style>
    .kpi-card {
        background: #fff;
        border: 1px solid #e7e7e7;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px
    }

    .kpi-t {
        font-size: 12px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .4px
    }

    .kpi-v {
        font-size: 22px;
        font-weight: 800;
        margin-top: 4px
    }

    .soft {
        border-radius: 999px;
        padding: 6px 10px;
        display: inline-block
    }

    .soft-a {
        background: #eef2ff;
        color: #1e40af
    }

    .soft-b {
        background: #ecfeff;
        color: #155e75
    }

    .soft-c {
        background: #f0fdf4;
        color: #166534
    }

    .soft-d {
        background: #fff7ed;
        color: #9a3412
    }

    .panel-clean {
        border-radius: 12px;
        overflow: hidden
    }

    .panel-clean .panel-heading {
        background: #f8fafc;
        border-bottom: 1px solid #eee
    }

    .toolbar {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap
    }

    .btn-round {
        border-radius: 10px
    }

    .tab-pane {
        padding-top: 12px
    }

    .tbl thead th {
        background: #f8fafc
    }

    .num {
        text-align: right
    }

    .muted {
        color: #6b7280
    }
    </style>
</head>

<body>

    <?php
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

    <div id="wrap">
        <div class="container">

            <div class="page-header" style="margin-top:10px;">
                <div class="row">
                    <div class="col-sm-7">
                        <h1 style="margin:0;">Reportes Contables</h1>
                        <p class="muted" style="margin:6px 0 0;">Estados financieros, KPIs, gráficas y exportación.</p>
                    </div>
                    <div class="col-sm-5 text-right" style="padding-top:10px;">
                        <div class="toolbar">
                            <a class="btn btn-default btn-round" href="conta-diario.php"><i class="fa fa-book"></i>
                                Libro Diario</a>
                            <a class="btn btn-default btn-round" href="conta-mayor.php"><i class="fa fa-bookmark"></i>
                                Libro Mayor</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs ARRIBA -->
            <div class="row">
                <div class="col-sm-3">
                    <div class="kpi-card">
                        <div class="kpi-t">Comprobantes</div>
                        <div class="kpi-v"><?= (int)$kpi['comprobantes'] ?></div>
                        <div class="muted"><span class="soft soft-a">Rango: <?=h($desde)?> → <?=h($hasta)?></span></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi-card">
                        <div class="kpi-t">Ingresos</div>
                        <div class="kpi-v">Bs <?= number_format((float)$kpi['ingresos'],2) ?></div>
                        <div class="muted"><span class="soft soft-c">Acumulado</span></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi-card">
                        <div class="kpi-t">Gastos</div>
                        <div class="kpi-v">Bs <?= number_format((float)$kpi['gastos'],2) ?></div>
                        <div class="muted"><span class="soft soft-d">Acumulado</span></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi-card">
                        <div class="kpi-t">Utilidad Neta</div>
                        <div class="kpi-v">Bs <?= number_format((float)$kpi['utilidad'],2) ?></div>
                        <div class="muted"><span class="soft soft-b">Ingresos - Gastos</span></div>
                    </div>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="panel panel-default panel-clean">
                <div class="panel-heading"><strong>Filtros</strong></div>
                <div class="panel-body">
                    <form class="form-inline" method="get">
                        <input type="hidden" name="tab" value="<?=h($tab)?>">
                        <label>Desde</label>
                        <input type="date" name="desde" value="<?=h($desde)?>" class="form-control">
                        <label>Hasta</label>
                        <input type="date" name="hasta" value="<?=h($hasta)?>" class="form-control">
                        <button class="btn btn-default">Aplicar</button>
                        <a class="btn btn-link" href="conta-reportes.php">Hoy / Año</a>
                    </form>
                </div>
            </div>

            <!-- TABS -->
            <ul class="nav nav-tabs">
                <li class="<?=($tab==='dashboard'?'active':'')?>"><a
                        href="?tab=dashboard&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>">Dashboard</a></li>
                <li class="<?=($tab==='estado_resultados'?'active':'')?>"><a
                        href="?tab=estado_resultados&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>">Estado de Resultados</a>
                </li>
                <li class="<?=($tab==='balance_general'?'active':'')?>"><a
                        href="?tab=balance_general&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>">Balance General</a></li>
                <li class="<?=($tab==='balance_comprobacion'?'active':'')?>"><a
                        href="?tab=balance_comprobacion&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>">Balance de
                        Comprobación</a></li>
            </ul>

            <div class="tab-content">

                <!-- DASHBOARD -->
                <div class="tab-pane <?=($tab==='dashboard'?'active':'')?>" id="dash">
                    <div class="row">
                        <div class="col-sm-8">
                            <div class="panel panel-default panel-clean">
                                <div class="panel-heading"><strong>Ingresos vs Gastos (Mensual)</strong></div>
                                <div class="panel-body">
                                    <canvas id="chartIG" height="110"></canvas>
                                    <div class="muted" style="margin-top:8px;">Si no se ve la gráfica, revisa conexión
                                        (CDN) o igual puedes usar tablas.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="panel panel-default panel-clean">
                                <div class="panel-heading"><strong>Top cuentas por movimiento</strong></div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered tbl">
                                            <thead>
                                                <tr>
                                                    <th>Cuenta</th>
                                                    <th class="num">Mov.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($top as $r): ?>
                                                <tr>
                                                    <td><?=h($r['codigo'].' - '.$r['nombre'])?></td>
                                                    <td class="num"><?=number_format((float)$r['movimiento'],2)?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if (count($top)===0): ?>
                                                <tr>
                                                    <td colspan="2" class="muted">Sin datos para el rango.</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ESTADO RESULTADOS -->
                <div class="tab-pane <?=($tab==='estado_resultados'?'active':'')?>" id="er">
                    <div class="panel panel-default panel-clean">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-sm-6"><strong>Estado de Resultados</strong></div>
                                <div class="col-sm-6 text-right">
                                    <a class="btn btn-default btn-xs" target="_blank"
                                        href="?print=1&rep=estado_resultados&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-print"></i> Imprimir</a>
                                    <a class="btn btn-default btn-xs"
                                        href="?export=1&rep=estado_resultados&fmt=xls&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-file-excel-o"></i> Excel</a>
                                    <a class="btn btn-default btn-xs"
                                        href="?export=1&rep=estado_resultados&fmt=doc&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-file-word-o"></i> Word</a>
                                    <a class="btn btn-default btn-xs"
                                        href="?export=1&rep=estado_resultados&fmt=csv&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-file-text-o"></i> CSV</a>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <h4 style="margin-top:0;">Ingresos</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered tbl">
                                    <thead>
                                        <tr>
                                            <th>Cuenta</th>
                                            <th>Nombre</th>
                                            <th class="num">Debe</th>
                                            <th class="num">Haber</th>
                                            <th class="num">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($ER['ingresos'] as $r): ?>
                                        <tr>
                                            <td><?=h($r['codigo'])?></td>
                                            <td><?=h($r['nombre'])?></td>
                                            <td class="num"><?=number_format((float)$r['debe'],2)?></td>
                                            <td class="num"><?=number_format((float)$r['haber'],2)?></td>
                                            <td class="num"><?=number_format((float)$r['saldo'],2)?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr style="font-weight:bold">
                                            <td colspan="4" class="num">TOTAL INGRESOS</td>
                                            <td class="num"><?=number_format((float)$ER['total_ingresos'],2)?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4>Gastos</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered tbl">
                                    <thead>
                                        <tr>
                                            <th>Cuenta</th>
                                            <th>Nombre</th>
                                            <th class="num">Debe</th>
                                            <th class="num">Haber</th>
                                            <th class="num">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($ER['gastos'] as $r): ?>
                                        <tr>
                                            <td><?=h($r['codigo'])?></td>
                                            <td><?=h($r['nombre'])?></td>
                                            <td class="num"><?=number_format((float)$r['debe'],2)?></td>
                                            <td class="num"><?=number_format((float)$r['haber'],2)?></td>
                                            <td class="num"><?=number_format((float)$r['saldo'],2)?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr style="font-weight:bold">
                                            <td colspan="4" class="num">TOTAL GASTOS</td>
                                            <td class="num"><?=number_format((float)$ER['total_gastos'],2)?></td>
                                        </tr>
                                        <tr style="font-weight:bold;background:#f8fafc">
                                            <td colspan="4" class="num">UTILIDAD NETA</td>
                                            <td class="num"><?=number_format((float)$ER['utilidad'],2)?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- BALANCE GENERAL -->
                <div class="tab-pane <?=($tab==='balance_general'?'active':'')?>" id="bg">
                    <div class="panel panel-default panel-clean">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-sm-6"><strong>Balance General</strong></div>
                                <div class="col-sm-6 text-right">
                                    <a class="btn btn-default btn-xs" target="_blank"
                                        href="?print=1&rep=balance_general&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-print"></i> Imprimir</a>
                                    <a class="btn btn-default btn-xs"
                                        href="?export=1&rep=balance_general&fmt=xls&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-file-excel-o"></i> Excel</a>
                                    <a class="btn btn-default btn-xs"
                                        href="?export=1&rep=balance_general&fmt=doc&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-file-word-o"></i> Word</a>
                                    <a class="btn btn-default btn-xs"
                                        href="?export=1&rep=balance_general&fmt=csv&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-file-text-o"></i> CSV</a>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">

                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="kpi-card">
                                        <div class="kpi-t">Total Activo</div>
                                        <div class="kpi-v">Bs <?=number_format((float)$BG['total_activo'],2)?></div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="kpi-card">
                                        <div class="kpi-t">Total Pasivo</div>
                                        <div class="kpi-v">Bs <?=number_format((float)$BG['total_pasivo'],2)?></div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="kpi-card">
                                        <div class="kpi-t">Total Patrimonio</div>
                                        <div class="kpi-v">Bs <?=number_format((float)$BG['total_patrimonio'],2)?></div>
                                    </div>
                                </div>
                            </div>

                            <h4 style="margin-top:0;">Activos</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered tbl">
                                    <thead>
                                        <tr>
                                            <th>Cuenta</th>
                                            <th>Nombre</th>
                                            <th class="num">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($BG['activos'] as $r): ?>
                                        <tr>
                                            <td><?=h($r['codigo'])?></td>
                                            <td><?=h($r['nombre'])?></td>
                                            <td class="num"><?=number_format((float)$r['saldo'],2)?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr style="font-weight:bold">
                                            <td colspan="2" class="num">TOTAL ACTIVO</td>
                                            <td class="num"><?=number_format((float)$BG['total_activo'],2)?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4>Pasivos</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered tbl">
                                    <thead>
                                        <tr>
                                            <th>Cuenta</th>
                                            <th>Nombre</th>
                                            <th class="num">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($BG['pasivos'] as $r): ?>
                                        <tr>
                                            <td><?=h($r['codigo'])?></td>
                                            <td><?=h($r['nombre'])?></td>
                                            <td class="num"><?=number_format((float)$r['saldo'],2)?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr style="font-weight:bold">
                                            <td colspan="2" class="num">TOTAL PASIVO</td>
                                            <td class="num"><?=number_format((float)$BG['total_pasivo'],2)?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h4>Patrimonio</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered tbl">
                                    <thead>
                                        <tr>
                                            <th>Cuenta</th>
                                            <th>Nombre</th>
                                            <th class="num">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($BG['patrimonio'] as $r): ?>
                                        <tr>
                                            <td><?=h($r['codigo'])?></td>
                                            <td><?=h($r['nombre'])?></td>
                                            <td class="num"><?=number_format((float)$r['saldo'],2)?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr style="font-weight:bold">
                                            <td colspan="2" class="num">TOTAL PATRIMONIO</td>
                                            <td class="num"><?=number_format((float)$BG['total_patrimonio'],2)?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- BALANCE COMPROBACION -->
                <div class="tab-pane <?=($tab==='balance_comprobacion'?'active':'')?>" id="bc">
                    <div class="panel panel-default panel-clean">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-sm-6"><strong>Balance de Comprobación</strong></div>
                                <div class="col-sm-6 text-right">
                                    <a class="btn btn-default btn-xs" target="_blank"
                                        href="?print=1&rep=balance_comprobacion&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-print"></i> Imprimir</a>
                                    <a class="btn btn-default btn-xs"
                                        href="?export=1&rep=balance_comprobacion&fmt=xls&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-file-excel-o"></i> Excel</a>
                                    <a class="btn btn-default btn-xs"
                                        href="?export=1&rep=balance_comprobacion&fmt=doc&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-file-word-o"></i> Word</a>
                                    <a class="btn btn-default btn-xs"
                                        href="?export=1&rep=balance_comprobacion&fmt=csv&desde=<?=h($desde)?>&hasta=<?=h($hasta)?>"><i
                                            class="fa fa-file-text-o"></i> CSV</a>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped tbl">
                                    <thead>
                                        <tr>
                                            <th>Cuenta</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th class="num">Debe</th>
                                            <th class="num">Haber</th>
                                            <th class="num">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($BC as $r): ?>
                                        <tr>
                                            <td><?=h($r['codigo'])?></td>
                                            <td><?=h($r['nombre'])?></td>
                                            <td><?=h($r['tipo'])?></td>
                                            <td class="num"><?=number_format((float)$r['debe'],2)?></td>
                                            <td class="num"><?=number_format((float)$r['haber'],2)?></td>
                                            <td class="num"><?=number_format((float)$r['saldo'],2)?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (count($BC)===0): ?>
                                        <tr>
                                            <td colspan="6" class="muted">Sin datos para el rango.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- tab-content -->

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>

    <!-- Chart.js (sin composer) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function() {
        var labels = <?= json_encode($labels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
        var ingresos = <?= json_encode($ingData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
        var gastos = <?= json_encode($gasData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;

        var el = document.getElementById('chartIG');
        if (!el || typeof Chart === 'undefined') return;

        new Chart(el, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Ingresos',
                        data: ingresos,
                        tension: 0.25
                    },
                    {
                        label: 'Gastos',
                        data: gastos,
                        tension: 0.25
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(v) {
                                return 'Bs ' + v;
                            }
                        }
                    }
                }
            }
        });
    })();
    </script>
</body>

</html>