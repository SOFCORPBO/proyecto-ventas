<?php
session_start();
include("sistema/configuracion.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$cn = $db->Conectar();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc($cn, $v){ return $cn->real_escape_string((string)$v); }

$desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
$tipo  = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';

$desdeSQL = esc($cn, $desde);
$hastaSQL = esc($cn, $hasta);
$tipoSQL  = esc($cn, $tipo);

$where = "WHERE fecha BETWEEN '{$desdeSQL}' AND '{$hastaSQL}'";
if ($tipo !== '') $where .= " AND tipo_comprobante='{$tipoSQL}'";

$KpiSQL = $db->SQL("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN tipo_comprobante='INGRESO' THEN 1 ELSE 0 END) AS ingresos,
        SUM(CASE WHEN tipo_comprobante='EGRESO'  THEN 1 ELSE 0 END) AS egresos
    FROM contabilidad_diario
    {$where}
");
$k = $KpiSQL ? $KpiSQL->fetch_assoc() : ['total'=>0,'ingresos'=>0,'egresos'=>0];

$SumasSQL = $db->SQL("
    SELECT
        IFNULL(SUM(d.debe),0) AS debe,
        IFNULL(SUM(d.haber),0) AS haber
    FROM contabilidad_diario_detalle d
    INNER JOIN contabilidad_diario a ON a.id=d.id_diario
    {$where}
");
$s = $SumasSQL ? $SumasSQL->fetch_assoc() : ['debe'=>0,'haber'=>0];

$Lista = $db->SQL("
    SELECT *
    FROM contabilidad_diario
    {$where}
    ORDER BY fecha DESC, id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Libro Diario | <?= TITULO ?></title>
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
        .kpi-box{ padding:14px; border-radius:6px; color:#fff; text-align:center; margin-bottom:15px; }
        .kpi1{ background:#3f51b5; }
        .kpi2{ background:#009688; }
        .kpi3{ background:#795548; }
        .kpi4{ background:#607d8b; }
        .filter-bar{ background:#fff; border:1px solid #e7e7e7; padding:10px; border-radius:6px; margin-bottom:15px; }
        .panel-heading strong{ font-weight:700; }
    </style>
</head>
<body>

<?php
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

<div class="container" id="wrap">
    <div class="page-header">
        <h1>Libro Diario</h1>
        <a href="conta-diario-nuevo.php" class="btn btn-primary pull-right">
            <i class="fa fa-plus"></i> Nuevo Comprobante
        </a>
        <div style="clear:both;"></div>
    </div>

    <div class="row">
        <div class="col-sm-3"><div class="kpi-box kpi1"><h2><?= (int)$k['total'] ?></h2><small>Total</small></div></div>
        <div class="col-sm-3"><div class="kpi-box kpi2"><h2><?= (int)$k['ingresos'] ?></h2><small>Ingresos</small></div></div>
        <div class="col-sm-3"><div class="kpi-box kpi3"><h2><?= (int)$k['egresos'] ?></h2><small>Egresos</small></div></div>
        <div class="col-sm-3"><div class="kpi-box kpi4"><h2><?= number_format(((float)$s['debe']-(float)$s['haber']),2) ?></h2><small>Diferencia</small></div></div>
    </div>

    <div class="filter-bar">
        <form class="form-inline" method="get">
            <label>Desde</label>
            <input type="date" name="desde" class="form-control" value="<?= h($desde) ?>">
            <label>Hasta</label>
            <input type="date" name="hasta" class="form-control" value="<?= h($hasta) ?>">

            <label>Tipo</label>
            <select name="tipo" class="form-control">
                <option value="">TODOS</option>
                <?php foreach(['INGRESO','EGRESO','TRASPASO','AJUSTE','APERTURA','CIERRE'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($tipo===$t?'selected':'') ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>

            <button class="btn btn-default"><i class="fa fa-search"></i> Filtrar</button>
        </form>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Comprobantes</strong></div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tabla_diario">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Nro</th>
                            <th>Razón Social</th>
                            <th>Glosa</th>
                            <th>Referencia</th>
                            <th>Cheque</th>
                            <th width="90">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($Lista): while($a = $Lista->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$a['id'] ?></td>
                            <td><?= h($a['fecha']) ?></td>
                            <td><?= h($a['tipo_comprobante']) ?></td>
                            <td><?= h($a['nro_comprobante']) ?></td>
                            <td><?= h($a['razon_social']) ?></td>
                            <td><?= h($a['glosa']) ?></td>
                            <td><?= h($a['referencia']) ?></td>
                            <td><?= h($a['cheque_nro']) ?></td>
                            <td>
                                <a class="btn btn-info btn-xs" href="conta-diario-ver.php?id=<?= (int)$a['id'] ?>">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include(MODULO.'footer.php'); ?>
<?php include(MODULO.'Tema.JS.php'); ?>
<script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
<script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>
<script>
$('#tabla_diario').dataTable({ "order": [[1,"desc"],[0,"desc"]] });
</script>
</body>
</html>
