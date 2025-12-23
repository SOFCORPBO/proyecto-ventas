<?php
session_start();
include("sistema/configuracion.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$cn = $db->Conectar();

/* =========================
   Helpers
========================= */
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc($cn, $v){ return $cn->real_escape_string((string)$v); }
function postv($k, $d=''){ return isset($_POST[$k]) ? $_POST[$k] : $d; }

$msg = '';
$err = '';

/* ============================================================
   CRUD CUENTAS (en este mismo archivo)
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Crear
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
            $codigoSQL = esc($cn, $codigo);
            $nombreSQL = esc($cn, $nombre);
            $tipoSQL   = esc($cn, $tipo);

            $ex = $db->SQL("SELECT id FROM contabilidad_cuentas WHERE codigo='{$codigoSQL}' LIMIT 1");
            if ($ex && $ex->num_rows > 0) {
                $err = "Ya existe una cuenta con ese código.";
            } else {
                $ok = $db->SQL("
                    INSERT INTO contabilidad_cuentas (codigo,nombre,tipo,nivel,padre_id,habilitado)
                    VALUES ('{$codigoSQL}','{$nombreSQL}','{$tipoSQL}',{$nivel},{$padre_id},1)
                ");
                $msg = $ok ? "Cuenta creada correctamente." : "No se pudo crear la cuenta.";
                if (!$ok) $err = $msg;
                if ($ok) $msg = "Cuenta creada correctamente.";
            }
        }
    }

    // Editar
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
            $codigoSQL = esc($cn, $codigo);
            $nombreSQL = esc($cn, $nombre);
            $tipoSQL   = esc($cn, $tipo);

            $ex = $db->SQL("SELECT id FROM contabilidad_cuentas WHERE codigo='{$codigoSQL}' AND id<>{$id} LIMIT 1");
            if ($ex && $ex->num_rows > 0) {
                $err = "Ese código ya está en uso por otra cuenta.";
            } else {
                if ($padre_id !== 'NULL' && (int)$padre_id === $id) $padre_id = 'NULL';
                $ok = $db->SQL("
                    UPDATE contabilidad_cuentas
                    SET codigo='{$codigoSQL}', nombre='{$nombreSQL}', tipo='{$tipoSQL}',
                        nivel={$nivel}, padre_id={$padre_id}
                    WHERE id={$id}
                    LIMIT 1
                ");
                $msg = $ok ? "Cuenta actualizada correctamente." : "";
                if (!$ok) $err = "No se pudo actualizar la cuenta.";
            }
        }
    }

    // Deshabilitar
    if (isset($_POST['EliminarCuenta'])) {
        $id = (int)postv('id', 0);
        if ($id<=0) $err = "ID inválido.";
        else {
            $ok = $db->SQL("UPDATE contabilidad_cuentas SET habilitado=0 WHERE id={$id} LIMIT 1");
            $msg = $ok ? "Cuenta deshabilitada correctamente." : "";
            if (!$ok) $err = "No se pudo deshabilitar la cuenta.";
        }
    }
}

/* ============================================================
   DATOS (cuentas + KPIs + árbol)
============================================================ */
$CuentasSQL = $db->SQL("
    SELECT c.*,
           p.codigo AS padre_codigo, p.nombre AS padre_nombre
    FROM contabilidad_cuentas c
    LEFT JOIN contabilidad_cuentas p ON p.id=c.padre_id
    WHERE c.habilitado=1
    ORDER BY c.codigo ASC
");

$cuentas = [];
if ($CuentasSQL) while($r = $CuentasSQL->fetch_assoc()) $cuentas[] = $r;

$KpiSQL = $db->SQL("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN tipo='ACTIVO' THEN 1 ELSE 0 END) AS activos,
        SUM(CASE WHEN tipo='PASIVO' THEN 1 ELSE 0 END) AS pasivos,
        SUM(CASE WHEN tipo='PATRIMONIO' THEN 1 ELSE 0 END) AS patrimonio,
        SUM(CASE WHEN tipo='INGRESO' THEN 1 ELSE 0 END) AS ingresos,
        SUM(CASE WHEN tipo='GASTO' THEN 1 ELSE 0 END) AS gastos
    FROM contabilidad_cuentas
    WHERE habilitado=1
");
$k = $KpiSQL ? $KpiSQL->fetch_assoc() : ['total'=>0,'activos'=>0,'pasivos'=>0,'patrimonio'=>0,'ingresos'=>0,'gastos'=>0];

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

function renderTree($pid, $children, $byId){
    if (!isset($children[$pid])) return '';
    $html = '<ul class="acct-tree">';
    foreach($children[$pid] as $id){
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Plan de Cuentas | <?= TITULO ?></title>

    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
        .page-header{ margin-top:10px; }
        .card{
            background:#fff;
            border:1px solid #e7e7e7;
            border-radius:10px;
            box-shadow:0 1px 1px rgba(0,0,0,.04);
            margin-bottom:15px;
        }
        .card-h{
            padding:12px 14px;
            border-bottom:1px solid #eee;
        }
        .card-b{ padding:14px; }

        .kpi{
            border-radius:10px;
            padding:14px;
            color:#fff;
            text-align:center;
            margin-bottom:12px;
        }
        .k1{ background:#3f51b5; }
        .k2{ background:#009688; }
        .k3{ background:#795548; }
        .k4{ background:#607d8b; }
        .k5{ background:#2e7d32; }
        .k6{ background:#6a1b9a; }

        .tree-wrap{ max-height:420px; overflow:auto; }
        .tree-title{ font-size:12px; text-transform:uppercase; color:#777; letter-spacing:.4px; margin:0 0 10px; }

        .acct-tree{ list-style:none; padding-left:0; margin:0; }
        .acct-tree li{ margin:4px 0; }
        .acct-tree .code{ font-family: monospace; font-weight:700; }
        .leaf, .tree-toggle{
            display:block; padding:6px 8px; border-radius:8px; color:#333; text-decoration:none;
        }
        .leaf:hover, .tree-toggle:hover{ background:#f5f5f5; text-decoration:none; }
        .dot{ display:inline-block; width:8px; height:8px; border-radius:50%; background:#9e9e9e; margin-right:6px; }
        .caret-right{ transform: rotate(-90deg); display:inline-block; transition: transform .15s ease; margin-right:6px; }
        .tree-toggle.open .caret-right{ transform: rotate(0deg); }

        .table>thead>tr>th{ background:#f7f7f7; }
        .btn-xs{ border-radius:7px; }
    </style>
</head>
<body>

<?php
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

<div class="container" id="wrap">

    <div class="page-header">
        <div class="row">
            <div class="col-sm-8">
                <h1 style="margin:0;">Plan de Cuentas</h1>
                <div class="text-muted">Gestión del catálogo contable (estructura tipo árbol, como en contabilidad).</div>
            </div>
            <div class="col-sm-4 text-right" style="padding-top:10px;">
                <button class="btn btn-primary" data-toggle="modal" data-target="#ModalCuenta" onclick="NuevaCuenta()">
                    <i class="fa fa-plus"></i> Nueva Cuenta
                </button>
            </div>
        </div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

    <div class="row">
        <div class="col-sm-4">
            <div class="card">
                <div class="card-h">
                    <strong>Plan de Cuentas (Árbol)</strong>
                </div>
                <div class="card-b tree-wrap">

                    <?php
                    $tiposOrden = ['ACTIVO','PASIVO','PATRIMONIO','INGRESO','GASTO'];
                    foreach($tiposOrden as $tp):
                        $gid = "grp_".strtolower($tp);
                    ?>
                        <div style="margin-bottom:10px;">
                            <a href="#" class="tree-toggle open" data-target="#<?= $gid ?>">
                                <span class="caret caret-right"></span> <strong><?= h($tp) ?></strong>
                            </a>
                            <div class="tree-node collapse in" id="<?= $gid ?>">
                                <?php
                                // raíces (padre_id null/0) del tipo
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
                                        echo '<a href="#" class="tree-toggle" data-target="#node_'.$rid.'">
                                                <span class="caret caret-right"></span>
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
            </div>

            <div class="kpi k1"><h2 style="margin:0;"><?= (int)$k['total'] ?></h2><small>Total Cuentas</small></div>
            <div class="row">
                <div class="col-xs-6"><div class="kpi k2"><h3 style="margin:0;"><?= (int)$k['activos'] ?></h3><small>Activos</small></div></div>
                <div class="col-xs-6"><div class="kpi k3"><h3 style="margin:0;"><?= (int)$k['pasivos'] ?></h3><small>Pasivos</small></div></div>
            </div>
            <div class="row">
                <div class="col-xs-6"><div class="kpi k4"><h3 style="margin:0;"><?= (int)$k['patrimonio'] ?></h3><small>Patrimonio</small></div></div>
                <div class="col-xs-6"><div class="kpi k5"><h3 style="margin:0;"><?= (int)$k['ingresos'] ?></h3><small>Ingresos</small></div></div>
            </div>
            <div class="kpi k6"><h3 style="margin:0;"><?= (int)$k['gastos'] ?></h3><small>Gastos</small></div>
        </div>

        <div class="col-sm-8">
            <div class="card">
                <div class="card-h">
                    <div class="row">
                        <div class="col-sm-6">
                            <strong>Listado de Cuentas</strong>
                        </div>
                        <div class="col-sm-6 text-right">
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#ModalCuenta" onclick="NuevaCuenta()">
                                <i class="fa fa-plus"></i> Nueva Cuenta
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-b">
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
                                <?php foreach($cuentas as $c): ?>
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
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted">Sugerencia: usa cuentas padre para agrupar (igual que el árbol).</div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- MODAL -->
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

<?php include(MODULO.'footer.php'); ?>
<?php include(MODULO.'Tema.JS.php'); ?>
<script src="<?= ESTATICO ?>js/jquery.dataTables.min.js"></script>
<script src="<?= ESTATICO ?>js/dataTables.bootstrap.js"></script>

<script>
(function(){
    // Tree toggle
    $(document).on('click', '.tree-toggle', function(e){
        e.preventDefault();
        var target = $(this).data('target');
        if (!target) return;
        $(this).toggleClass('open');
        $(target).collapse('toggle');
    });

    // DataTable
    $('#tabla_cuentas').dataTable({ "order": [[1,"asc"]] });

    // Modal
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
})();
</script>

</body>
</html>
