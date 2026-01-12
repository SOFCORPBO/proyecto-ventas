<?php
session_start();
include("sistema/configuracion.php");
include("sistema/clase/contabilidad.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Cont = new Contabilidad();
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$hoy = date('Y-m-d');
$desde = $_GET['desde'] ?? date('Y-01-01');
$hasta = $_GET['hasta'] ?? $hoy;
$idCuenta = (int)($_GET['id_cuenta'] ?? 0);
$estado = $_GET['estado'] ?? '1'; // 1=solo habilitados, 0=incluye anulados
$incluyeAnulados = ($estado === '0');

$cuentas = $Cont->ListarCuentas(true);

$cuentaSel = null;
foreach($cuentas as $c){ if ((int)$c['id']===$idCuenta){ $cuentaSel = $c; break; } }

$detalle = [];
$resumen = [];

if ($idCuenta>0) {
    $detalle = $Cont->MayorPorCuenta($idCuenta, $desde, $hasta, $incluyeAnulados);
} else {
    $resumen = $Cont->ResumenMayor($desde, $hasta, $incluyeAnulados);
}

/* ==========================
   EXPORT
========================== */
if (isset($_GET['export']) && $_GET['export']=='1') {
    $fmt = $_GET['fmt'] ?? 'csv';

    if ($idCuenta>0 && $cuentaSel) {
        // Export detalle de una cuenta
        if ($fmt==='xls') {
            header("Content-Type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=libro_mayor_{$cuentaSel['codigo']}.xls");
            echo "<meta charset='utf-8'>";
            echo "<table border='1'>";
            echo "<tr><th colspan='10'>LIBRO MAYOR - {$cuentaSel['codigo']} - ".h($cuentaSel['nombre'])."</th></tr>";
            echo "<tr><th>FECHA</th><th>N° DOC</th><th>RAZÓN SOCIAL</th><th>GLOSA</th><th>REFERENCIA</th><th>N° CHEQUE</th><th>T.C.</th><th>DEBE</th><th>HABER</th><th>SALDO</th></tr>";

            $saldo = 0;
            $tipo = $cuentaSel['tipo'] ?? '';
            $isDeb = in_array($tipo, ['ACTIVO','GASTO'], true);

            foreach($detalle as $d){
                $de = (float)$d['debe']; $ha=(float)$d['haber'];
                $saldo += $isDeb ? ($de-$ha) : ($ha-$de);

                echo "<tr>";
                echo "<td>".h($d['fecha'])."</td>";
                echo "<td>".h($d['nro_comprobante'] ?? $d['id'])."</td>";
                echo "<td>".h($d['razon_social'] ?? '')."</td>";
                echo "<td>".h($d['glosa'] ?? '')."</td>";
                echo "<td>".h($d['referencia_linea'] ?? $d['referencia'] ?? $d['descripcion_linea'] ?? '')."</td>";
                echo "<td>".h($d['cheque_nro'] ?? '')."</td>";
                echo "<td>".h($d['tipo_cambio'] ?? '')."</td>";
                echo "<td style='text-align:right'>".number_format($de,2,'.','')."</td>";
                echo "<td style='text-align:right'>".number_format($ha,2,'.','')."</td>";
                echo "<td style='text-align:right'>".number_format($saldo,2,'.','')."</td>";
                echo "</tr>";
            }
            echo "</table>";
            exit;
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="libro_mayor_'.$cuentaSel['codigo'].'.csv"');

            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ["FECHA","NRO_DOC","RAZON_SOCIAL","GLOSA","REFERENCIA","N_CHEQUE","TC","DEBE","HABER","SALDO"], ';');

            $saldo = 0;
            $tipo = $cuentaSel['tipo'] ?? '';
            $isDeb = in_array($tipo, ['ACTIVO','GASTO'], true);

            foreach($detalle as $d){
                $de = (float)$d['debe']; $ha=(float)$d['haber'];
                $saldo += $isDeb ? ($de-$ha) : ($ha-$de);

                fputcsv($out, [
                    $d['fecha'],
                    $d['nro_comprobante'] ?? $d['id'],
                    $d['razon_social'] ?? '',
                    $d['glosa'] ?? '',
                    $d['referencia_linea'] ?? $d['referencia'] ?? $d['descripcion_linea'] ?? '',
                    $d['cheque_nro'] ?? '',
                    $d['tipo_cambio'] ?? '',
                    number_format($de,2,'.',''),
                    number_format($ha,2,'.',''),
                    number_format($saldo,2,'.','')
                ], ';');
            }
            fclose($out);
            exit;
        }
    } else {
        // Export resumen
        if ($fmt==='xls') {
            header("Content-Type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=libro_mayor_resumen.xls");
            echo "<meta charset='utf-8'>";
            echo "<table border='1'>";
            echo "<tr><th colspan='6'>RESUMEN LIBRO MAYOR ({$desde} a {$hasta})</th></tr>";
            echo "<tr><th>CUENTA</th><th>NOMBRE</th><th>TIPO</th><th>DEBE</th><th>HABER</th><th>SALDO</th></tr>";
            foreach($resumen as $r){
                $tipo = $r['tipo'] ?? '';
                $isDeb = in_array($tipo, ['ACTIVO','GASTO'], true);
                $saldo = $isDeb ? ((float)$r['debe']-(float)$r['haber']) : ((float)$r['haber']-(float)$r['debe']);
                echo "<tr>";
                echo "<td>".h($r['codigo'])."</td>";
                echo "<td>".h($r['nombre'])."</td>";
                echo "<td>".h($tipo)."</td>";
                echo "<td style='text-align:right'>".number_format((float)$r['debe'],2,'.','')."</td>";
                echo "<td style='text-align:right'>".number_format((float)$r['haber'],2,'.','')."</td>";
                echo "<td style='text-align:right'>".number_format($saldo,2,'.','')."</td>";
                echo "</tr>";
            }
            echo "</table>";
            exit;
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="libro_mayor_resumen.csv"');

            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ["CUENTA","NOMBRE","TIPO","DEBE","HABER","SALDO"], ';');

            foreach($resumen as $r){
                $tipo = $r['tipo'] ?? '';
                $isDeb = in_array($tipo, ['ACTIVO','GASTO'], true);
                $saldo = $isDeb ? ((float)$r['debe']-(float)$r['haber']) : ((float)$r['haber']-(float)$r['debe']);

                fputcsv($out, [
                    $r['codigo'],
                    $r['nombre'],
                    $tipo,
                    number_format((float)$r['debe'],2,'.',''),
                    number_format((float)$r['haber'],2,'.',''),
                    number_format($saldo,2,'.','')
                ], ';');
            }
            fclose($out);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Libro Mayor | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO."Tema.CSS.php"); ?>
    <style>
    .card {
        background: #fff;
        border: 1px solid #e7e7e7;
        border-radius: 12px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
        margin-bottom: 12px
    }

    .card-h {
        padding: 12px 14px;
        border-bottom: 1px solid #eee;
        background: #f7f7f7;
        border-radius: 12px 12px 0 0
    }

    .card-b {
        padding: 14px
    }

    .kpi {
        background: #fff;
        border: 1px solid #e7e7e7;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px
    }

    .kpi .t {
        font-size: 12px;
        color: #777;
        text-transform: uppercase;
        letter-spacing: .4px
    }

    .kpi .v {
        font-size: 20px;
        font-weight: 700;
        margin-top: 6px
    }

    .badge-off {
        background: #ffebee;
        color: #b71c1c;
        padding: 5px 10px;
        border-radius: 999px
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
                    <div class="col-sm-8">
                        <h1 style="margin:0;">Libro Mayor</h1>
                        <p class="text-muted" style="margin:6px 0 0;">Resumen por cuenta + detalle con saldo acumulado.
                        </p>
                    </div>
                    <div class="col-sm-4 text-right" style="padding-top:10px;">
                        <a class="btn btn-primary" href="conta-comprobante.php"><i class="fa fa-plus"></i> Nuevo
                            Comprobante</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-h"><strong>Filtros</strong></div>
                <div class="card-b">
                    <form class="form-inline" method="get">
                        <label>Desde</label>
                        <input type="date" name="desde" value="<?php echo h($desde); ?>" class="form-control">

                        <label>Hasta</label>
                        <input type="date" name="hasta" value="<?php echo h($hasta); ?>" class="form-control">

                        <label>Cuenta</label>
                        <select name="id_cuenta" class="form-control" style="min-width:320px;">
                            <option value="0">-- Resumen (todas) --</option>
                            <?php foreach($cuentas as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>"
                                <?php if((int)$c['id']===$idCuenta) echo 'selected'; ?>>
                                <?php echo h($c['codigo'].' - '.$c['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            <option value="1" <?php if($estado==='1') echo 'selected'; ?>>Solo habilitados</option>
                            <option value="0" <?php if($estado==='0') echo 'selected'; ?>>Incluir anulados</option>
                        </select>

                        <button class="btn btn-default">Aplicar</button>

                        <?php if($idCuenta>0): ?>
                        <a class="btn btn-default"
                            href="?<?php echo http_build_query(array_merge($_GET, ["export"=>1,"fmt"=>"csv"])); ?>"><i
                                class="fa fa-download"></i> Export CSV</a>
                        <a class="btn btn-default"
                            href="?<?php echo http_build_query(array_merge($_GET, ["export"=>1,"fmt"=>"xls"])); ?>"><i
                                class="fa fa-file-excel-o"></i> Export Excel</a>
                        <?php else: ?>
                        <a class="btn btn-default"
                            href="?<?php echo http_build_query(array_merge($_GET, ["export"=>1,"fmt"=>"csv"])); ?>"><i
                                class="fa fa-download"></i> Export Resumen CSV</a>
                        <a class="btn btn-default"
                            href="?<?php echo http_build_query(array_merge($_GET, ["export"=>1,"fmt"=>"xls"])); ?>"><i
                                class="fa fa-file-excel-o"></i> Export Resumen Excel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if($idCuenta>0 && $cuentaSel): ?>
            <?php
            $tipo = $cuentaSel['tipo'] ?? '';
            $isDeb = in_array($tipo, ['ACTIVO','GASTO'], true);
            $tDebe=0; $tHaber=0;
            foreach($detalle as $d){ $tDebe+=(float)$d['debe']; $tHaber+=(float)$d['haber']; }
            $saldoFinal = $isDeb ? ($tDebe-$tHaber) : ($tHaber-$tDebe);
        ?>
            <div class="row">
                <div class="col-sm-3">
                    <div class="kpi">
                        <div class="t">Cuenta</div>
                        <div class="v"><?php echo h($cuentaSel['codigo']); ?></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi">
                        <div class="t">Debe</div>
                        <div class="v"><?php echo number_format($tDebe,2); ?></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi">
                        <div class="t">Haber</div>
                        <div class="v"><?php echo number_format($tHaber,2); ?></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi">
                        <div class="t">Saldo</div>
                        <div class="v"><?php echo number_format($saldoFinal,2); ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-h">
                    <strong>Detalle Libro Mayor</strong>
                    <span class="text-muted"> — <?php echo h($cuentaSel['codigo'].' '.$cuentaSel['nombre']); ?></span>
                </div>
                <div class="card-b">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tblDet">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>N° Doc.</th>
                                    <th>Razón Social</th>
                                    <th>Glosa</th>
                                    <th>Referencia</th>
                                    <th>N° Cheque</th>
                                    <th>T.C.</th>
                                    <th class="text-right">Debe</th>
                                    <th class="text-right">Haber</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $saldo=0; foreach($detalle as $d): ?>
                                <?php
                                    $de=(float)$d['debe']; $ha=(float)$d['haber'];
                                    $saldo += $isDeb ? ($de-$ha) : ($ha-$de);
                                ?>
                                <tr>
                                    <td><?php echo h($d['fecha']); ?></td>
                                    <td><?php echo h($d['nro_comprobante'] ?? $d['id']); ?></td>
                                    <td><?php echo h($d['razon_social'] ?? ''); ?></td>
                                    <td><?php echo h($d['glosa'] ?? ''); ?></td>
                                    <td><?php echo h($d['referencia_linea'] ?? $d['referencia'] ?? $d['descripcion_linea'] ?? ''); ?>
                                    </td>
                                    <td><?php echo h($d['cheque_nro'] ?? ''); ?></td>
                                    <td><?php echo h($d['tipo_cambio'] ?? ''); ?></td>
                                    <td class="text-right"><?php echo number_format($de,2); ?></td>
                                    <td class="text-right"><?php echo number_format($ha,2); ?></td>
                                    <td class="text-right"><?php echo number_format($saldo,2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="7" class="text-right">Totales</th>
                                    <th class="text-right"><?php echo number_format($tDebe,2); ?></th>
                                    <th class="text-right"><?php echo number_format($tHaber,2); ?></th>
                                    <th class="text-right"><?php echo number_format($saldoFinal,2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="text-muted">Saldo calculado por tipo: ACTIVO/GASTO (Debe-Haber) y
                        PASIVO/PATRIMONIO/INGRESO (Haber-Debe).</div>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-h"><strong>Resumen por Cuenta</strong></div>
                <div class="card-b">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tblRes">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th class="text-right">Debe</th>
                                    <th class="text-right">Haber</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($resumen as $r): ?>
                                <?php
                                    $tipo = $r['tipo'] ?? '';
                                    $isDeb = in_array($tipo, ['ACTIVO','GASTO'], true);
                                    $saldo = $isDeb ? ((float)$r['debe']-(float)$r['haber']) : ((float)$r['haber']-(float)$r['debe']);
                                ?>
                                <tr>
                                    <td><?php echo h($r['codigo']); ?></td>
                                    <td><?php echo h($r['nombre']); ?></td>
                                    <td><?php echo h($tipo); ?></td>
                                    <td class="text-right"><?php echo number_format((float)$r['debe'],2); ?></td>
                                    <td class="text-right"><?php echo number_format((float)$r['haber'],2); ?></td>
                                    <td class="text-right"><?php echo number_format($saldo,2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted">Selecciona una cuenta arriba para ver el detalle y saldo acumulado.</div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>
    <script src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>
    <script>
    $(function() {
        if ($('#tblRes').length) $('#tblRes').dataTable({
            "order": [
                [0, "asc"]
            ]
        });
        if ($('#tblDet').length) $('#tblDet').dataTable({
            "order": [
                [0, "asc"]
            ]
        });
    });
    </script>
</body>

</html>