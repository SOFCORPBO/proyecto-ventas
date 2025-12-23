<?php
session_start();
include("sistema/configuracion.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

/* ============================================================
   Helpers (robustos para tu clase Conexion)
============================================================ */
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function getv($k, $d=''){ return isset($_GET[$k]) ? $_GET[$k] : $d; }
function postv($k, $d=''){ return isset($_POST[$k]) ? $_POST[$k] : $d; }

/**
 * Obtiene un mysqli válido (si tu Conexion lo expone).
 * En algunos repos, $db->Conectar() devuelve mysqli, en otros devuelve $this.
 */
function dbi(){
    global $db;

    // 1) Intento directo
    if (is_object($db) && method_exists($db, 'Conectar')) {
        $c = $db->Conectar();
        if (is_object($c) && method_exists($c, 'real_escape_string')) return $c;

        // Si Conectar devuelve un objeto "Conexion" con Conectar interno
        if (is_object($c) && method_exists($c, 'Conectar')) {
            $c2 = $c->Conectar();
            if (is_object($c2) && method_exists($c2, 'real_escape_string')) return $c2;
        }
    }
    return null;
}

function esc($v){
    $cn = dbi();
    if ($cn && method_exists($cn, 'real_escape_string')) {
        return $cn->real_escape_string((string)$v);
    }
    // Fallback (no ideal, pero evita fatales)
    return addslashes((string)$v);
}

function tx_begin(){
    global $db;
    $cn = dbi();
    if ($cn && method_exists($cn, 'begin_transaction')) return $cn->begin_transaction();
    return $db->SQL("START TRANSACTION");
}
function tx_commit(){
    global $db;
    $cn = dbi();
    if ($cn && method_exists($cn, 'commit')) return $cn->commit();
    return $db->SQL("COMMIT");
}
function tx_rollback(){
    global $db;
    $cn = dbi();
    if ($cn && method_exists($cn, 'rollback')) return $cn->rollback();
    return $db->SQL("ROLLBACK");
}
function last_insert_id(){
    global $db;
    $cn = dbi();
    if ($cn && property_exists($cn, 'insert_id')) return (int)$cn->insert_id;
    $r = $db->SQL("SELECT LAST_INSERT_ID() AS id");
    $row = $r ? $r->fetch_assoc() : ['id'=>0];
    return (int)($row['id'] ?? 0);
}

/* ============================================================
   Tabs (sub-módulo)
============================================================ */
$tab = getv('tab', 'diario');
$tabsValidos = ['cuentas','diario','nuevo','ver','mayor','reportes'];
if (!in_array($tab, $tabsValidos, true)) $tab = 'diario';

/* Usuario creador */
$usuario_id = isset($usuarioApp['id']) ? (int)$usuarioApp['id'] : (isset($usuarioApp['id_usuario']) ? (int)$usuarioApp['id_usuario'] : 0);

$msg = '';
$err = '';

/* ============================================================
   ACCIONES CRUD (todo en este archivo)
============================================================ */
if ($_SERVER['REQUEST_METHOD']==='POST') {

    /* ---------- PLAN DE CUENTAS: Crear / Editar / Deshabilitar ---------- */
    if (isset($_POST['CrearCuenta'])) {
        $codigo   = trim(postv('codigo'));
        $nombre   = trim(postv('nombre'));
        $tipo     = trim(postv('tipo'));
        $nivel    = (int)postv('nivel', 1);
        $padre_id = postv('padre_id', '');
        $padre_id = ($padre_id==='' ? 'NULL' : (int)$padre_id);

        if ($codigo==='' || $nombre==='' || $tipo==='') {
            $err = "Complete Código, Nombre y Tipo.";
        } else {
            $codigoSQL = esc($codigo);
            $nombreSQL = esc($nombre);
            $tipoSQL   = esc($tipo);

            $ex = $db->SQL("SELECT id FROM contabilidad_cuentas WHERE codigo='{$codigoSQL}' LIMIT 1");
            if ($ex && $ex->num_rows>0) {
                $err = "Ya existe una cuenta con ese código.";
            } else {
                $ok = $db->SQL("
                    INSERT INTO contabilidad_cuentas (codigo,nombre,tipo,nivel,padre_id,habilitado)
                    VALUES ('{$codigoSQL}','{$nombreSQL}','{$tipoSQL}',{$nivel},{$padre_id},1)
                ");
                if ($ok) $msg = "Cuenta creada correctamente.";
                else $err = "No se pudo crear la cuenta.";
                $tab = 'cuentas';
            }
        }
    }

    if (isset($_POST['EditarCuenta'])) {
        $id       = (int)postv('id', 0);
        $codigo   = trim(postv('codigo'));
        $nombre   = trim(postv('nombre'));
        $tipo     = trim(postv('tipo'));
        $nivel    = (int)postv('nivel', 1);
        $padre_id = postv('padre_id', '');
        $padre_id = ($padre_id==='' ? 'NULL' : (int)$padre_id);

        if ($id<=0) {
            $err = "ID inválido.";
        } elseif ($codigo==='' || $nombre==='' || $tipo==='') {
            $err = "Complete Código, Nombre y Tipo.";
        } else {
            $codigoSQL = esc($codigo);
            $nombreSQL = esc($nombre);
            $tipoSQL   = esc($tipo);

            $ex = $db->SQL("SELECT id FROM contabilidad_cuentas WHERE codigo='{$codigoSQL}' AND id<>{$id} LIMIT 1");
            if ($ex && $ex->num_rows>0) {
                $err = "Ese código ya está en uso por otra cuenta.";
            } else {
                if ($padre_id!=='NULL' && (int)$padre_id === $id) $padre_id='NULL';

                $ok = $db->SQL("
                    UPDATE contabilidad_cuentas
                    SET codigo='{$codigoSQL}', nombre='{$nombreSQL}', tipo='{$tipoSQL}',
                        nivel={$nivel}, padre_id={$padre_id}
                    WHERE id={$id}
                    LIMIT 1
                ");
                if ($ok) $msg = "Cuenta actualizada correctamente.";
                else $err = "No se pudo actualizar la cuenta.";
                $tab = 'cuentas';
            }
        }
    }

    if (isset($_POST['EliminarCuenta'])) {
        $id = (int)postv('id', 0);
        if ($id<=0) $err = "ID inválido.";
        else {
            $ok = $db->SQL("UPDATE contabilidad_cuentas SET habilitado=0 WHERE id={$id} LIMIT 1");
            if ($ok) $msg = "Cuenta deshabilitada correctamente.";
            else $err = "No se pudo deshabilitar la cuenta.";
            $tab = 'cuentas';
        }
    }

    /* ---------- COMPROBANTE DIARIO: Guardar ---------- */
    if (isset($_POST['GuardarComprobante'])) {

        $fecha = esc(postv('fecha', date('Y-m-d')));
        $tipo  = esc(postv('tipo_comprobante', 'INGRESO'));

        $nro   = trim(postv('nro_comprobante',''));
        $nroSQL= ($nro==='' ? "NULL" : ("'".esc($nro)."'"));

        $razon = trim(postv('razon_social',''));
        $glosa = trim(postv('glosa',''));
        $ref   = trim(postv('referencia',''));
        $cheq  = trim(postv('cheque_nro',''));
        $proy  = trim(postv('proyecto',''));

        $razonSQL = ($razon==='' ? "NULL" : ("'".esc($razon)."'"));
        $glosaSQL = ($glosa==='' ? "NULL" : ("'".esc($glosa)."'"));
        $refSQL   = ($ref==='' ? "NULL" : ("'".esc($ref)."'"));
        $cheqSQL  = ($cheq==='' ? "NULL" : ("'".esc($cheq)."'"));
        $proySQL  = ($proy==='' ? "NULL" : ("'".esc($proy)."'"));

        $ids   = isset($_POST['id_cuenta']) ? $_POST['id_cuenta'] : [];
        $descs = isset($_POST['desc_linea']) ? $_POST['desc_linea'] : [];
        $refs  = isset($_POST['ref_linea']) ? $_POST['ref_linea'] : [];
        $debes = isset($_POST['debe']) ? $_POST['debe'] : [];
        $habes = isset($_POST['haber']) ? $_POST['haber'] : [];

        $sumDebe = 0.0; $sumHaber = 0.0;
        $lineas = [];

        for ($i=0; $i<count($ids); $i++){
            $idC = (int)$ids[$i];
            if ($idC<=0) continue;

            $debe  = (float)str_replace(',', '.', (string)($debes[$i] ?? 0));
            $haber = (float)str_replace(',', '.', (string)($habes[$i] ?? 0));
            if ($debe==0 && $haber==0) continue;

            $sumDebe += $debe;
            $sumHaber+= $haber;

            $dl = trim($descs[$i] ?? '');
            $rl = trim($refs[$i] ?? '');

            $lineas[] = [
                'id_cuenta' => $idC,
                'debe'      => $debe,
                'haber'     => $haber,
                'descSQL'   => ($dl==='' ? "NULL" : ("'".esc($dl)."'")),
                'refSQL'    => ($rl==='' ? "NULL" : ("'".esc($rl)."'")),
            ];
        }

        $diff = round($sumDebe - $sumHaber, 2);

        if ($glosa==='') {
            $err = "Debe registrar una glosa.";
            $tab = 'nuevo';
        } elseif (count($lineas) < 2) {
            $err = "Debe registrar al menos 2 líneas contables.";
            $tab = 'nuevo';
        } elseif ($diff != 0.00) {
            $err = "Comprobante descuadrado. Diferencia: {$diff}";
            $tab = 'nuevo';
        } else {

            tx_begin();
            try {
                $ok = $db->SQL("
                    INSERT INTO contabilidad_diario
                    (fecha,tipo_comprobante,nro_comprobante,razon_social,glosa,descripcion,referencia,cheque_nro,proyecto,creado_por)
                    VALUES
                    ('{$fecha}','{$tipo}',{$nroSQL},{$razonSQL},{$glosaSQL},{$glosaSQL},{$refSQL},{$cheqSQL},{$proySQL},{$usuario_id})
                ");
                if (!$ok) throw new Exception("No se pudo guardar cabecera.");

                $idDiario = last_insert_id();
                if ($idDiario <= 0) throw new Exception("No se pudo obtener ID del comprobante.");

                // Auto número estilo contable: I-000001 / E-000001 ...
                if ($nro === '') {
                    $pref = strtoupper(substr((string)postv('tipo_comprobante','I'),0,1));
                    $nroGen = $pref . "-" . str_pad((string)$idDiario, 6, "0", STR_PAD_LEFT);
                    $nroGenEsc = esc($nroGen);
                    $db->SQL("UPDATE contabilidad_diario SET nro_comprobante='{$nroGenEsc}' WHERE id={$idDiario} LIMIT 1");
                }

                foreach($lineas as $ln){
                    $idC  = (int)$ln['id_cuenta'];
                    $debe = number_format((float)$ln['debe'], 2, '.', '');
                    $haber= number_format((float)$ln['haber'],2, '.', '');

                    $ok2 = $db->SQL("
                        INSERT INTO contabilidad_diario_detalle
                        (id_diario,id_cuenta,descripcion_linea,referencia_linea,debe,haber)
                        VALUES
                        ({$idDiario},{$idC},{$ln['descSQL']},{$ln['refSQL']},{$debe},{$haber})
                    ");
                    if (!$ok2) throw new Exception("No se pudo guardar detalle.");
                }

                tx_commit();
                header("Location: contabilidad.php?tab=ver&id=".$idDiario);
                exit;

            } catch(Exception $e){
                tx_rollback();
                $err = $e->getMessage();
                $tab = 'nuevo';
            }
        }
    }
}

/* ============================================================
   DATOS COMUNES
============================================================ */
$CuentasSQL = $db->SQL("SELECT id,codigo,nombre,tipo,nivel,padre_id FROM contabilidad_cuentas WHERE habilitado=1 ORDER BY codigo ASC");
$cuentas = [];
if ($CuentasSQL) while($r=$CuentasSQL->fetch_assoc()) $cuentas[]=$r;

function buildOptions($cuentas){
    $html = '<option value="0">Seleccione...</option>';
    foreach($cuentas as $c){
        $html .= '<option value="'.(int)$c['id'].'">'.h($c['codigo'].' - '.$c['nombre']).'</option>';
    }
    return $html;
}
$optionsHtml = buildOptions($cuentas);
$optionsJson = json_encode($optionsHtml);

/* Árbol */
$byId = [];
$children = [];
foreach($cuentas as $c){
    $id = (int)$c['id'];
    $byId[$id] = $c;
    $pid = (int)($c['padre_id'] ?? 0);
    if (!isset($children[$pid])) $children[$pid] = [];
    $children[$pid][] = $id;
}

function renderTree($rootPid, $children, $byId){
    if (!isset($children[$rootPid])) return '';
    $html = '<ul class="acct-tree">';
    foreach($children[$rootPid] as $id){
        $c = $byId[$id];
        $hasKids = isset($children[$id]) && count($children[$id])>0;

        $html .= '<li>';
        if ($hasKids){
            $html .= '<a href="#" class="tree-toggle" data-target="#node_'.$id.'">
                        <span class="caret caret-right"></span>
                        <span class="code">'.h($c['codigo']).'</span> '.h($c['nombre']).'
                      </a>';
            $html .= '<div class="tree-node collapse" id="node_'.$id.'">';
            $html .= renderTree($id, $children, $byId);
            $html .= '</div>';
        } else {
            $html .= '<span class="leaf">
                        <span class="dot"></span>
                        <span class="code">'.h($c['codigo']).'</span> '.h($c['nombre']).'
                      </span>';
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

/* ============================================================
   KPIs y Listados
============================================================ */
$KpiCuentasSQL = $db->SQL("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN tipo='ACTIVO' THEN 1 ELSE 0 END) AS activos,
        SUM(CASE WHEN tipo='PASIVO' THEN 1 ELSE 0 END) AS pasivos,
        SUM(CASE WHEN tipo='INGRESO' THEN 1 ELSE 0 END) AS ingresos,
        SUM(CASE WHEN tipo='GASTO' THEN 1 ELSE 0 END) AS gastos
    FROM contabilidad_cuentas
    WHERE habilitado=1
");
$kC = $KpiCuentasSQL ? $KpiCuentasSQL->fetch_assoc() : ['total'=>0,'activos'=>0,'pasivos'=>0,'ingresos'=>0,'gastos'=>0];

/* Diario */
$desde = getv('desde', date('Y-m-01'));
$hasta = getv('hasta', date('Y-m-d'));
$tipoF = trim(getv('tipo',''));

$desdeSQL = esc($desde);
$hastaSQL = esc($hasta);
$tipoFSQL = esc($tipoF);

$whereDiario = "WHERE fecha BETWEEN '{$desdeSQL}' AND '{$hastaSQL}'";
if ($tipoF!=='') $whereDiario .= " AND tipo_comprobante='{$tipoFSQL}'";

$KpiDiarioSQL = $db->SQL("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN tipo_comprobante='INGRESO' THEN 1 ELSE 0 END) AS ingresos,
        SUM(CASE WHEN tipo_comprobante='EGRESO'  THEN 1 ELSE 0 END) AS egresos,
        SUM(CASE WHEN tipo_comprobante='TRASPASO' THEN 1 ELSE 0 END) AS traspasos
    FROM contabilidad_diario
    {$whereDiario}
");
$kD = $KpiDiarioSQL ? $KpiDiarioSQL->fetch_assoc() : ['total'=>0,'ingresos'=>0,'egresos'=>0,'traspasos'=>0];

$SumasDiarioSQL = $db->SQL("
    SELECT
        IFNULL(SUM(d.debe),0) AS debe,
        IFNULL(SUM(d.haber),0) AS haber
    FROM contabilidad_diario_detalle d
    INNER JOIN contabilidad_diario a ON a.id=d.id_diario
    {$whereDiario}
");
$sD = $SumasDiarioSQL ? $SumasDiarioSQL->fetch_assoc() : ['debe'=>0,'haber'=>0];

$ListaDiario = $db->SQL("
    SELECT *
    FROM contabilidad_diario
    {$whereDiario}
    ORDER BY fecha DESC, id DESC
");

/* Ver comprobante */
$idVer = (int)getv('id', 0);
$cab = null; $det = null;
if ($tab==='ver' && $idVer>0){
    $CabSQL = $db->SQL("SELECT * FROM contabilidad_diario WHERE id={$idVer} LIMIT 1");
    $cab = $CabSQL ? $CabSQL->fetch_assoc() : null;
    if ($cab){
        $det = $db->SQL("
            SELECT d.*, c.codigo, c.nombre
            FROM contabilidad_diario_detalle d
            INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
            WHERE d.id_diario={$idVer}
            ORDER BY c.codigo ASC
        ");
    } else {
        $err = "Comprobante no encontrado.";
        $tab = 'diario';
    }
}

/* Libro Mayor */
$idMayor = (int)getv('id_cuenta', 0);
$movs = [];
$tDebeM=0; $tHaberM=0;

if ($tab==='mayor' && $idMayor>0){
    $MovSQL = $db->SQL("
        SELECT a.fecha, a.id, a.tipo_comprobante, a.nro_comprobante, a.glosa,
               d.debe, d.haber
        FROM contabilidad_diario_detalle d
        INNER JOIN contabilidad_diario a ON a.id=d.id_diario
        WHERE d.id_cuenta={$idMayor}
          AND a.fecha BETWEEN '{$desdeSQL}' AND '{$hastaSQL}'
        ORDER BY a.fecha ASC, a.id ASC
    ");
    if ($MovSQL){
        while($m=$MovSQL->fetch_assoc()){
            $movs[]=$m;
            $tDebeM += (float)$m['debe'];
            $tHaberM+= (float)$m['haber'];
        }
    }
}

/* Reportes: Balance de comprobación */
$rowsBal = [];
$tDebeB=0; $tHaberB=0;
if ($tab==='reportes'){
    $BalSQL = $db->SQL("
        SELECT c.codigo, c.nombre, c.tipo,
               IFNULL(SUM(d.debe),0) AS debe,
               IFNULL(SUM(d.haber),0) AS haber
        FROM contabilidad_diario_detalle d
        INNER JOIN contabilidad_diario a ON a.id=d.id_diario
        INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
        WHERE a.fecha BETWEEN '{$desdeSQL}' AND '{$hastaSQL}'
        GROUP BY c.id
        ORDER BY c.codigo ASC
    ");
    if ($BalSQL){
        while($r=$BalSQL->fetch_assoc()){
            $rowsBal[]=$r;
            $tDebeB += (float)$r['debe'];
            $tHaberB+= (float)$r['haber'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Contabilidad | <?= TITULO ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
        /* ===== Layout / estética consistente ===== */
        .page-title{ margin:0; font-weight:800; letter-spacing:.2px; }
        .subnav{ margin:10px 0 15px; }
        .subnav .nav>li>a{ border-radius:10px; padding:10px 14px; }
        .subnav .nav>li.active>a{ font-weight:800; }

        .card-soft{
            background:#fff;
            border:1px solid #e7e7e7;
            border-radius:10px;
            padding:12px;
            margin-bottom:12px;
        }
        .muted{ color:#777; }

        /* KPI */
        .kpi{
            border-radius:10px;
            padding:14px;
            color:#fff;
            margin-bottom:12px;
            text-align:center;
        }
        .k1{ background:#3f51b5; }
        .k2{ background:#009688; }
        .k3{ background:#795548; }
        .k4{ background:#607d8b; }
        .k5{ background:#2e7d32; }

        /* Barra filtros */
        .filter-bar{
            background:#fff;
            border:1px solid #e7e7e7;
            border-radius:10px;
            padding:10px;
            margin-bottom:12px;
        }

        /* Árbol estilo contable (tipo video) */
        .tree-wrap{ background:#fff; border:1px solid #e7e7e7; border-radius:10px; padding:10px; }
        .tree-title{ font-size:12px; text-transform:uppercase; color:#777; margin-bottom:8px; letter-spacing:.4px; }
        .acct-tree{ list-style:none; padding-left:0; margin:0; }
        .acct-tree li{ margin:4px 0; }
        .acct-tree .code{ font-family: monospace; font-weight:800; }
        .acct-tree .leaf{ display:block; padding:6px 8px; border-radius:8px; color:#333; }
        .acct-tree .leaf:hover{ background:#f6f6f6; }
        .acct-tree .dot{ display:inline-block; width:8px; height:8px; border-radius:50%; background:#9e9e9e; margin-right:8px; }
        .tree-toggle{ display:block; padding:6px 8px; border-radius:8px; color:#333; text-decoration:none; }
        .tree-toggle:hover{ background:#f6f6f6; text-decoration:none; }
        .caret-right{ transform: rotate(-90deg); display:inline-block; transition: transform .15s ease; margin-right:8px; }
        .tree-toggle.open .caret-right{ transform: rotate(0deg); }

        /* Comprobante */
        .voucher-header{
            background:#fff;
            border:1px solid #e7e7e7;
            border-radius:10px;
            padding:12px;
        }
        .voucher-header .title{
            font-weight:900;
            letter-spacing:.4px;
        }
        .tbl-head{ background:#f6f6f6; }
        .totals-box{ text-align:right; }
        .totals-box strong{ display:inline-block; min-width:90px; }

        @media print{
            .no-print{ display:none !important; }
            body{ padding-top:0 !important; }
        }
    </style>
</head>
<body>

<?php
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

<div class="container" id="wrap">

    <div class="page-header no-print" style="margin-top:10px;">
        <div class="row">
            <div class="col-sm-8">
                <h1 class="page-title">Contabilidad</h1>
                <div class="muted">Plan de cuentas, comprobantes, mayor y reportes. Interfaz unificada y consistente con tu sistema.</div>
            </div>
            <div class="col-sm-4 text-right" style="padding-top:10px;">
                <?php if ($tab==='diario'): ?>
                    <a class="btn btn-primary" href="contabilidad.php?tab=nuevo"><i class="fa fa-plus"></i> Nuevo Comprobante</a>
                <?php endif; ?>
                <?php if ($tab==='ver' && $idVer>0): ?>
                    <button class="btn btn-primary" onclick="window.print();"><i class="fa fa-print"></i> Imprimir</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

    <!-- Sub-navegación -->
    <div class="subnav no-print">
        <ul class="nav nav-pills">
            <li class="<?= ($tab==='diario'?'active':'') ?>"><a href="contabilidad.php?tab=diario"><i class="fa fa-book"></i> Libro Diario</a></li>
            <li class="<?= ($tab==='nuevo'?'active':'') ?>"><a href="contabilidad.php?tab=nuevo"><i class="fa fa-pencil-square-o"></i> Registro Comprobante</a></li>
            <li class="<?= ($tab==='cuentas'?'active':'') ?>"><a href="contabilidad.php?tab=cuentas"><i class="fa fa-sitemap"></i> Plan de Cuentas</a></li>
            <li class="<?= ($tab==='mayor'?'active':'') ?>"><a href="contabilidad.php?tab=mayor"><i class="fa fa-bookmark"></i> Libro Mayor</a></li>
            <li class="<?= ($tab==='reportes'?'active':'') ?>"><a href="contabilidad.php?tab=reportes"><i class="fa fa-bar-chart"></i> Reportes</a></li>
        </ul>
    </div>

    <?php if ($tab==='cuentas'): ?>
        <!-- ===================== PLAN DE CUENTAS ===================== -->
        <div class="row">
            <div class="col-sm-4">
                <div class="tree-wrap">
                    <div class="tree-title">Plan de Cuentas (Árbol)</div>

                    <?php
                    $tiposOrden = ['ACTIVO','PASIVO','PATRIMONIO','INGRESO','GASTO'];
                    foreach($tiposOrden as $tp):
                        $grpId = "grp_".$tp;
                        ?>
                        <div style="margin-bottom:8px;">
                            <a class="tree-toggle open" href="#" data-target="#<?= $grpId ?>">
                                <span class="caret caret-right"></span> <strong><?= h($tp) ?></strong>
                            </a>
                            <div class="tree-node collapse in" id="<?= $grpId ?>">
                                <?php
                                $roots = [];
                                foreach($cuentas as $c){
                                    $pid = (int)($c['padre_id'] ?? 0);
                                    if ($pid===0 && $c['tipo']===$tp) $roots[] = (int)$c['id'];
                                }
                                echo '<ul class="acct-tree">';
                                foreach($roots as $rid){
                                    $c = $byId[$rid];
                                    $hasKids = isset($children[$rid]) && count($children[$rid])>0;

                                    echo '<li>';
                                    if ($hasKids){
                                        echo '<a href="#" class="tree-toggle" data-target="#node_'.$rid.'"><span class="caret caret-right"></span>
                                                <span class="code">'.h($c['codigo']).'</span> '.h($c['nombre']).'
                                              </a>';
                                        echo '<div class="tree-node collapse" id="node_'.$rid.'">';
                                        echo renderTree($rid, $children, $byId);
                                        echo '</div>';
                                    } else {
                                        echo '<span class="leaf"><span class="dot"></span><span class="code">'.h($c['codigo']).'</span> '.h($c['nombre']).'</span>';
                                    }
                                    echo '</li>';
                                }
                                echo '</ul>';
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="row" style="margin-top:12px;">
                    <div class="col-xs-12"><div class="kpi k1"><h2><?= (int)$kC['total'] ?></h2><small>Total Cuentas</small></div></div>
                    <div class="col-xs-6"><div class="kpi k2"><h2><?= (int)$kC['activos'] ?></h2><small>Activos</small></div></div>
                    <div class="col-xs-6"><div class="kpi k3"><h2><?= (int)$kC['pasivos'] ?></h2><small>Pasivos</small></div></div>
                    <div class="col-xs-6"><div class="kpi k5"><h2><?= (int)$kC['ingresos'] ?></h2><small>Ingresos</small></div></div>
                    <div class="col-xs-6"><div class="kpi k4"><h2><?= (int)$kC['gastos'] ?></h2><small>Gastos</small></div></div>
                </div>
            </div>

            <div class="col-sm-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Listado de Cuentas</strong>
                        <button class="btn btn-primary btn-sm pull-right" data-toggle="modal" data-target="#ModalCuenta" onclick="NuevaCuenta()">
                            <i class="fa fa-plus"></i> Nueva Cuenta
                        </button>
                        <div style="clear:both;"></div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="tabla_cuentas">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Nivel</th>
                                        <th>Padre</th>
                                        <th width="120">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $ListaCuentas = $db->SQL("
                                        SELECT c.*,
                                               p.codigo AS padre_codigo,
                                               p.nombre AS padre_nombre
                                        FROM contabilidad_cuentas c
                                        LEFT JOIN contabilidad_cuentas p ON p.id=c.padre_id
                                        WHERE c.habilitado=1
                                        ORDER BY c.codigo ASC
                                    ");
                                    if ($ListaCuentas):
                                        while($c=$ListaCuentas->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?= (int)$c['id'] ?></td>
                                        <td><?= h($c['codigo']) ?></td>
                                        <td><?= h($c['nombre']) ?></td>
                                        <td><?= h($c['tipo']) ?></td>
                                        <td><?= (int)$c['nivel'] ?></td>
                                        <td><?= !empty($c['padre_id']) ? h(($c['padre_codigo']??'').' - '.($c['padre_nombre']??'')) : '-' ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-xs"
                                                onclick='EditarCuenta(<?= json_encode($c, JSON_HEX_QUOT|JSON_HEX_APOS) ?>)'
                                                data-toggle="modal" data-target="#ModalCuenta">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <form method="post" style="display:inline-block;" onsubmit="return confirm('¿Deshabilitar esta cuenta?');">
                                                <input type="hidden" name="EliminarCuenta">
                                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                                <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="muted">Sugerencia: usa cuentas padre para agrupar (igual que el árbol).</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CUENTA -->
        <div class="modal fade" id="ModalCuenta" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <form method="post" id="FormCuenta">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><i class="fa fa-sitemap"></i> Cuenta Contable</h4>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="id_cuenta">

                            <div class="row">
                                <div class="col-sm-4">
                                    <label>Código</label>
                                    <input type="text" name="codigo" id="codigo" class="form-control" required>
                                </div>
                                <div class="col-sm-8">
                                    <label>Nombre</label>
                                    <input type="text" name="nombre" id="nombre" class="form-control" required>
                                </div>
                            </div>

                            <div class="row" style="margin-top:10px;">
                                <div class="col-sm-6">
                                    <label>Tipo</label>
                                    <select name="tipo" id="tipo" class="form-control" required>
                                        <option value="">Seleccione...</option>
                                        <option value="ACTIVO">ACTIVO</option>
                                        <option value="PASIVO">PASIVO</option>
                                        <option value="PATRIMONIO">PATRIMONIO</option>
                                        <option value="INGRESO">INGRESO</option>
                                        <option value="GASTO">GASTO</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label>Nivel</label>
                                    <input type="number" name="nivel" id="nivel" class="form-control" min="1" value="1">
                                </div>
                            </div>

                            <div class="form-group" style="margin-top:10px;">
                                <label>Cuenta Padre (opcional)</label>
                                <select name="padre_id" id="padre_id" class="form-control">
                                    <option value="">Sin padre</option>
                                    <?php foreach($cuentas as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>"><?= h($p['codigo'].' - '.$p['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                            <button type="submit" name="CrearCuenta" id="btnCrear" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
                            <button type="submit" name="EditarCuenta" id="btnEditar" class="btn btn-success" style="display:none;"><i class="fa fa-check"></i> Actualizar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($tab==='diario'): ?>
        <!-- ===================== LIBRO DIARIO ===================== -->
        <div class="row">
            <div class="col-sm-3"><div class="kpi k1"><h2><?= (int)$kD['total'] ?></h2><small>Comprobantes</small></div></div>
            <div class="col-sm-3"><div class="kpi k2"><h2><?= (int)$kD['ingresos'] ?></h2><small>Ingresos</small></div></div>
            <div class="col-sm-3"><div class="kpi k3"><h2><?= (int)$kD['egresos'] ?></h2><small>Egresos</small></div></div>
            <div class="col-sm-3"><div class="kpi k4"><h2><?= number_format(((float)$sD['debe']-(float)$sD['haber']),2) ?></h2><small>Diferencia</small></div></div>
        </div>

        <div class="filter-bar no-print">
            <form class="form-inline" method="get">
                <input type="hidden" name="tab" value="diario">
                <label>Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= h($desde) ?>">
                <label>Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= h($hasta) ?>">
                <label>Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="">TODOS</option>
                    <?php foreach(['INGRESO','EGRESO','TRASPASO','AJUSTE','APERTURA','CIERRE'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($tipoF===$t?'selected':'') ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-default"><i class="fa fa-search"></i> Filtrar</button>
                <a class="btn btn-primary" href="contabilidad.php?tab=nuevo"><i class="fa fa-plus"></i> Nuevo</a>
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
                            <?php if ($ListaDiario): while($a=$ListaDiario->fetch_assoc()): ?>
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
                                        <a class="btn btn-info btn-xs" href="contabilidad.php?tab=ver&id=<?= (int)$a['id'] ?>">
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

    <?php elseif ($tab==='nuevo'): ?>
        <!-- ===================== REGISTRO COMPROBANTE ===================== -->
        <form method="post" id="frmComp">
            <div class="voucher-header">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="title">REGISTRO DE COMPROBANTE DIARIO</div>
                        <div class="muted">Cabecera + Detalle + Totales (flujo tipo contable).</div>
                    </div>
                    <div class="col-sm-4 text-right no-print">
                        <a class="btn btn-default" href="contabilidad.php?tab=diario"><i class="fa fa-arrow-left"></i> Volver</a>
                    </div>
                </div>

                <div class="row" style="margin-top:10px;">
                    <div class="col-sm-2">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-sm-3">
                        <label>Tipo de Comprobante</label>
                        <select name="tipo_comprobante" class="form-control" required>
                            <?php foreach(['INGRESO','EGRESO','TRASPASO','AJUSTE','APERTURA','CIERRE'] as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>Comprob. Nro</label>
                        <input type="text" name="nro_comprobante" class="form-control" placeholder="Auto (I-000001)">
                    </div>
                    <div class="col-sm-4">
                        <label>Razón Social</label>
                        <input type="text" name="razon_social" class="form-control" placeholder="Cliente / Proveedor">
                    </div>
                </div>

                <div class="row" style="margin-top:10px;">
                    <div class="col-sm-5">
                        <label>Glosa</label>
                        <input type="text" name="glosa" class="form-control" required placeholder="Ej: Venta de servicio, pago proveedor...">
                    </div>
                    <div class="col-sm-3">
                        <label>Proyecto</label>
                        <input type="text" name="proyecto" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-sm-2">
                        <label>Cheque Nro</label>
                        <input type="text" name="cheque_nro" class="form-control">
                    </div>
                    <div class="col-sm-2">
                        <label>Referencia</label>
                        <input type="text" name="referencia" class="form-control">
                    </div>
                </div>
            </div>

            <div class="panel panel-default" style="margin-top:12px;">
                <div class="panel-heading"><strong>Detalle</strong></div>
                <div class="panel-body">

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tblDetalle">
                            <thead class="tbl-head">
                                <tr>
                                    <th style="width:300px;">CUENTA</th>
                                    <th>DESCRIPCIÓN</th>
                                    <th style="width:180px;">REFERENCIA</th>
                                    <th style="width:120px;" class="text-right">DEBE</th>
                                    <th style="width:120px;" class="text-right">HABER</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="id_cuenta[]" class="form-control"><?= $optionsHtml ?></select>
                                    </td>
                                    <td><input type="text" name="desc_linea[]" class="form-control"></td>
                                    <td><input type="text" name="ref_linea[]" class="form-control"></td>
                                    <td><input type="number" step="0.01" name="debe[]" class="form-control inp-debe text-right" value="0.00"></td>
                                    <td><input type="number" step="0.01" name="haber[]" class="form-control inp-haber text-right" value="0.00"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-xs btnDel"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row" style="margin-top:10px;">
                        <div class="col-sm-6 no-print">
                            <button type="button" class="btn btn-default" id="btnAdd">
                                <i class="fa fa-plus"></i> Agregar línea
                            </button>
                        </div>
                        <div class="col-sm-6 totals-box">
                            <div class="card-soft">
                                <div><strong>Totales:</strong> Debe <span id="tDebe">0.00</span> | Haber <span id="tHaber">0.00</span></div>
                                <div><strong>Diferencia:</strong> <span id="tDiff">0.00</span></div>
                                <div class="muted" style="margin-top:6px;">Regla: Debe = Haber para guardar.</div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right no-print" style="margin-top:12px;">
                        <button type="submit" name="GuardarComprobante" class="btn btn-primary">
                            <i class="fa fa-save"></i> Guardar
                        </button>
                        <a href="contabilidad.php?tab=diario" class="btn btn-default">Cancelar</a>
                    </div>

                </div>
            </div>
        </form>

    <?php elseif ($tab==='ver'): ?>
        <!-- ===================== VER / IMPRIMIR COMPROBANTE ===================== -->
        <?php if ($cab): ?>
            <?php $tDebe=0; $tHaber=0; ?>
            <div class="panel panel-default">
                <div class="panel-heading"><strong>Comprobante</strong></div>
                <div class="panel-body">
                    <div class="card-soft">
                        <div class="row">
                            <div class="col-sm-3"><strong>Fecha:</strong> <?= h($cab['fecha']) ?></div>
                            <div class="col-sm-3"><strong>Tipo:</strong> <?= h($cab['tipo_comprobante']) ?></div>
                            <div class="col-sm-3"><strong>Nro:</strong> <?= h($cab['nro_comprobante']) ?></div>
                            <div class="col-sm-3"><strong>Cheque:</strong> <?= h($cab['cheque_nro']) ?></div>
                        </div>
                        <div class="row" style="margin-top:8px;">
                            <div class="col-sm-6"><strong>Razón Social:</strong> <?= h($cab['razon_social']) ?></div>
                            <div class="col-sm-6"><strong>Referencia:</strong> <?= h($cab['referencia']) ?></div>
                        </div>
                        <div class="row" style="margin-top:8px;">
                            <div class="col-sm-6"><strong>Glosa:</strong> <?= h($cab['glosa']) ?></div>
                            <div class="col-sm-6"><strong>Proyecto:</strong> <?= h($cab['proyecto']) ?></div>
                        </div>
                    </div>

                    <div class="table-responsive" style="margin-top:10px;">
                        <table class="table table-bordered table-striped">
                            <thead class="tbl-head">
                                <tr>
                                    <th>Código</th>
                                    <th>Cuenta</th>
                                    <th>Descripción</th>
                                    <th>Referencia</th>
                                    <th class="text-right">Debe</th>
                                    <th class="text-right">Haber</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($det): while($d=$det->fetch_assoc()): ?>
                                    <?php $tDebe += (float)$d['debe']; $tHaber += (float)$d['haber']; ?>
                                    <tr>
                                        <td><?= h($d['codigo']) ?></td>
                                        <td><?= h($d['nombre']) ?></td>
                                        <td><?= h($d['descripcion_linea']) ?></td>
                                        <td><?= h($d['referencia_linea']) ?></td>
                                        <td class="text-right"><?= number_format((float)$d['debe'],2) ?></td>
                                        <td class="text-right"><?= number_format((float)$d['haber'],2) ?></td>
                                    </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">Totales</th>
                                    <th class="text-right"><?= number_format($tDebe,2) ?></th>
                                    <th class="text-right"><?= number_format($tHaber,2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="alert alert-info">
                        <strong>Diferencia:</strong> <?= number_format(($tDebe-$tHaber),2) ?>
                    </div>

                    <div class="no-print text-right">
                        <a class="btn btn-default" href="contabilidad.php?tab=diario"><i class="fa fa-arrow-left"></i> Volver</a>
                        <button class="btn btn-primary" onclick="window.print();"><i class="fa fa-print"></i> Imprimir</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($tab==='mayor'): ?>
        <!-- ===================== LIBRO MAYOR ===================== -->
        <div class="filter-bar no-print">
            <form class="form-inline" method="get">
                <input type="hidden" name="tab" value="mayor">
                <label>Cuenta</label>
                <select name="id_cuenta" class="form-control" required>
                    <option value="0">Seleccione...</option>
                    <?php foreach($cuentas as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($idMayor==(int)$c['id']?'selected':'') ?>>
                            <?= h($c['codigo'].' - '.$c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= h($desde) ?>">
                <label>Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= h($hasta) ?>">

                <button class="btn btn-default"><i class="fa fa-search"></i> Ver</button>
            </form>
        </div>

        <?php if ($idMayor>0): ?>
        <div class="row">
            <div class="col-sm-4"><div class="kpi k1"><h2><?= number_format($tDebeM,2) ?></h2><small>Total Debe</small></div></div>
            <div class="col-sm-4"><div class="kpi k2"><h2><?= number_format($tHaberM,2) ?></h2><small>Total Haber</small></div></div>
            <div class="col-sm-4"><div class="kpi k3"><h2><?= number_format(($tDebeM-$tHaberM),2) ?></h2><small>Saldo</small></div></div>
        </div>
        <?php endif; ?>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Movimientos</strong></div>
            <div class="panel-body">
                <?php if ($idMayor<=0): ?>
                    <div class="muted">Seleccione una cuenta para ver su mayor.</div>
                <?php else: ?>
                    <?php $saldo=0; ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tabla_mayor">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Nro</th>
                                    <th>Glosa</th>
                                    <th class="text-right">Debe</th>
                                    <th class="text-right">Haber</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($movs as $m): ?>
                                    <?php
                                        $debe=(float)$m['debe'];
                                        $haber=(float)$m['haber'];
                                        $saldo += ($debe - $haber);
                                    ?>
                                    <tr>
                                        <td><?= h($m['fecha']) ?></td>
                                        <td><?= (int)$m['id'] ?></td>
                                        <td><?= h($m['tipo_comprobante']) ?></td>
                                        <td><?= h($m['nro_comprobante']) ?></td>
                                        <td><?= h($m['glosa']) ?></td>
                                        <td class="text-right"><?= number_format($debe,2) ?></td>
                                        <td class="text-right"><?= number_format($haber,2) ?></td>
                                        <td class="text-right"><?= number_format($saldo,2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($tab==='reportes'): ?>
        <!-- ===================== REPORTES ===================== -->
        <div class="filter-bar no-print">
            <form class="form-inline" method="get">
                <input type="hidden" name="tab" value="reportes">
                <label>Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= h($desde) ?>">
                <label>Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= h($hasta) ?>">
                <button class="btn btn-default"><i class="fa fa-search"></i> Ver</button>
            </form>
        </div>

        <div class="row">
            <div class="col-sm-4"><div class="kpi k1"><h2><?= number_format($tDebeB,2) ?></h2><small>Total Debe</small></div></div>
            <div class="col-sm-4"><div class="kpi k2"><h2><?= number_format($tHaberB,2) ?></h2><small>Total Haber</small></div></div>
            <div class="col-sm-4"><div class="kpi k3"><h2><?= number_format(($tDebeB-$tHaberB),2) ?></h2><small>Diferencia</small></div></div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Balance de Comprobación</strong></div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tabla_balance">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cuenta</th>
                                <th>Tipo</th>
                                <th class="text-right">Debe</th>
                                <th class="text-right">Haber</th>
                                <th class="text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rowsBal as $r): ?>
                                <?php $saldo = (float)$r['debe'] - (float)$r['haber']; ?>
                                <tr>
                                    <td><?= h($r['codigo']) ?></td>
                                    <td><?= h($r['nombre']) ?></td>
                                    <td><?= h($r['tipo']) ?></td>
                                    <td class="text-right"><?= number_format((float)$r['debe'],2) ?></td>
                                    <td class="text-right"><?= number_format((float)$r['haber'],2) ?></td>
                                    <td class="text-right"><?= number_format($saldo,2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Totales</th>
                                <th class="text-right"><?= number_format($tDebeB,2) ?></th>
                                <th class="text-right"><?= number_format($tHaberB,2) ?></th>
                                <th class="text-right"><?= number_format(($tDebeB-$tHaberB),2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-info" style="margin-top:10px;">
                    Si el sistema está bien asentado, la diferencia debería ser <strong>0.00</strong>.
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
(function(){
    /* ======= Tree toggle ======= */
    $(document).on('click', '.tree-toggle', function(e){
        e.preventDefault();
        var target = $(this).data('target');
        if (!target) return;
        $(this).toggleClass('open');
        $(target).collapse('toggle');
    });

    /* ======= DataTables ======= */
    if ($('#tabla_diario').length) $('#tabla_diario').dataTable({ "order": [[1,"desc"],[0,"desc"]] });
    if ($('#tabla_cuentas').length) $('#tabla_cuentas').dataTable({ "order": [[1,"asc"]] });
    if ($('#tabla_mayor').length) $('#tabla_mayor').dataTable({ "ordering": false });
    if ($('#tabla_balance').length) $('#tabla_balance').dataTable({ "order": [[0,"asc"]] });

    /* ======= Modal Cuentas ======= */
    window.NuevaCuenta = function(){
        $('#FormCuenta')[0].reset();
        $('#id_cuenta').val('');
        $('#btnCrear').show();
        $('#btnEditar').hide();
    }
    window.EditarCuenta = function(c){
        $('#id_cuenta').val(c.id);
        $('#codigo').val(c.codigo || '');
        $('#nombre').val(c.nombre || '');
        $('#tipo').val(c.tipo || '');
        $('#nivel').val(c.nivel || 1);
        $('#padre_id').val(c.padre_id || '');
        $('#btnCrear').hide();
        $('#btnEditar').show();
    }

    /* ======= Voucher ======= */
    var optionsHtml = <?php echo $optionsJson; ?>;

    function recalc(){
        var debe=0, haber=0;
        $('#tblDetalle tbody tr').each(function(){
            var d = parseFloat($(this).find('.inp-debe').val() || 0);
            var h = parseFloat($(this).find('.inp-haber').val() || 0);
            debe += d; haber += h;
        });
        var diff = debe - haber;
        $('#tDebe').text(debe.toFixed(2));
        $('#tHaber').text(haber.toFixed(2));
        $('#tDiff').text(diff.toFixed(2));
    }

    $('#btnAdd').on('click', function(){
        if (!$('#tblDetalle').length) return;

        var row =
        '<tr>' +
            '<td><select name="id_cuenta[]" class="form-control">' + optionsHtml + '</select></td>' +
            '<td><input type="text" name="desc_linea[]" class="form-control"></td>' +
            '<td><input type="text" name="ref_linea[]" class="form-control"></td>' +
            '<td><input type="number" step="0.01" name="debe[]" class="form-control inp-debe text-right" value="0.00"></td>' +
            '<td><input type="number" step="0.01" name="haber[]" class="form-control inp-haber text-right" value="0.00"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-danger btn-xs btnDel"><i class="fa fa-trash"></i></button></td>' +
        '</tr>';

        $('#tblDetalle tbody').append(row);
        recalc();
    });

    $('#tblDetalle').on('click', '.btnDel', function(){
        var rows = $('#tblDetalle tbody tr').length;
        if (rows <= 1) return;
        $(this).closest('tr').remove();
        recalc();
    });

    $('#tblDetalle').on('keyup change', '.inp-debe, .inp-haber', recalc);

    $('#frmComp').on('submit', function(e){
        recalc();
        var diff = parseFloat($('#tDiff').text());
        if (Math.abs(diff) > 0.001){
            e.preventDefault();
            alert('El comprobante está descuadrado. Diferencia: ' + diff.toFixed(2));
            return false;
        }
    });

    recalc();
})();
</script>

</body>
</html>
