<?php
session_start();
include("sistema/configuracion.php");
include("sistema/clase/contabilidad.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Cont = new Contabilidad();
$cn = $db->Conectar();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$msg = '';
$err = '';

/* ==========================
   EXPORT CSV (formato Excel)
========================== */
if (isset($_GET['export']) && $_GET['export'] == '1') {
    $solo = isset($_GET['solo']) ? (int)$_GET['solo'] : 1; // 1=habilitadas
    $csv = $Cont->ExportarPlanCuentasCSV($solo===1);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="plan_de_cuentas.csv"');
    echo $csv;
    exit;
}

/* ==========================
   ACCIONES
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Import CSV
    if (isset($_POST['ImportarCSV'])) {
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $err = "Debe seleccionar un archivo CSV válido.";
        } else {
            $tmp = $_FILES['archivo']['tmp_name'];
            $r = $Cont->ImportarPlanCuentasCSV($tmp);
            if ($r['ok']) {
                $s = $r['stats'];
                $msg = "Importación OK. Insertados: {$s['insertados']} | Actualizados: {$s['actualizados']}";
                if (!empty($s['errores'])) {
                    $err = "Importación con observaciones: ".implode(" | ", array_slice($s['errores'], 0, 5));
                }
            } else {
                $err = "No se pudo importar. ".implode(" | ", $r['stats']['errores']);
            }
        }
    }

    // Guardar (crear/editar)
    if (isset($_POST['GuardarCuenta'])) {
        $id   = (int)($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo   = trim($_POST['tipo'] ?? '');
        $nivel  = (int)($_POST['nivel'] ?? 1);
        $padre_id = ($_POST['padre_id'] ?? '');
        $padre_id = ($padre_id === '' ? null : (int)$padre_id);
        $habilitado = (int)($_POST['habilitado'] ?? 1);

        if ($codigo==='' || $nombre==='' || $tipo==='') {
            $err = "Complete Código, Nombre y Tipo.";
        } else {
            // mayor_codigo desde padre (para compatibilidad con Excel)
            $mayor_codigo = null;
            if (!empty($padre_id)) {
                $p = $Cont->ObtenerCuenta($padre_id);
                if ($p && !empty($p['codigo'])) $mayor_codigo = $p['codigo'];
            }

            $ok = $Cont->GuardarCuenta($id, $codigo, $nombre, $tipo, $nivel, $padre_id, $habilitado, $mayor_codigo);
            if ($ok) {
                $msg = ($id>0) ? "Cuenta actualizada correctamente." : "Cuenta creada correctamente.";
            } else {
                $err = "No se pudo guardar la cuenta.";
            }
        }
    }

    // Toggle habilitado
    if (isset($_POST['ToggleCuenta'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id>0) {
            $ok = $Cont->ToggleCuenta($id);
            if ($ok) $msg = "Estado actualizado.";
            else $err = "No se pudo cambiar el estado.";
        }
    }
}

/* ==========================
   DATOS + FILTROS
========================== */
$f_tipo   = trim($_GET['tipo'] ?? '');
$f_nivel  = trim($_GET['nivel'] ?? '');
$f_estado = trim($_GET['estado'] ?? ''); // 1/0
$f_q      = trim($_GET['q'] ?? '');

$cuentas = $Cont->ListarCuentas(false);
$kpi = $Cont->KPI_Cuentas();

// Filtrado en PHP (simple y rápido)
$cuentasFil = array_filter($cuentas, function($c) use ($f_tipo,$f_nivel,$f_estado,$f_q){
    if ($f_tipo !== '' && $c['tipo'] !== $f_tipo) return false;
    if ($f_nivel !== '' && (int)$c['nivel'] !== (int)$f_nivel) return false;
    if ($f_estado !== '' && (int)$c['habilitado'] !== (int)$f_estado) return false;
    if ($f_q !== '') {
        $q = mb_strtolower($f_q);
        $hay = mb_strtolower($c['codigo'].' '.$c['nombre']);
        if (mb_strpos($hay, $q) === false) return false;
    }
    return true;
});

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Plan de Cuentas | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">

    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
    .kpi-card {
        background: #fff;
        border: 1px solid #e7e7e7;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 12px;
    }

    .kpi-title {
        color: #777;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .kpi-value {
        font-size: 22px;
        font-weight: 700;
        margin-top: 4px;
    }

    .toolbar {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .panel-clean {
        border-radius: 10px;
        overflow: hidden;
    }

    .panel-clean .panel-heading {
        background: #f7f7f7;
        border-bottom: 1px solid #eee;
    }

    .badge-soft {
        padding: 6px 10px;
        border-radius: 999px;
    }

    .badge-on {
        background: #e8f5e9;
        color: #1b5e20;
    }

    .badge-off {
        background: #ffebee;
        color: #b71c1c;
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
                        <h1 style="margin:0;">Plan de Cuentas</h1>
                        <p class="text-muted" style="margin:6px 0 0;">Import/Export estilo Excel + estados
                            habilitado/deshabilitado.</p>
                    </div>
                    <div class="col-sm-5 text-right">
                        <div class="toolbar" style="margin-top:10px;">
                            <a class="btn btn-default" href="?export=1&solo=1"><i class="fa fa-download"></i> Exportar
                                (Habilitadas)</a>
                            <a class="btn btn-default" href="?export=1&solo=0"><i class="fa fa-download"></i> Exportar
                                (Todas)</a>
                            <button class="btn btn-info" data-toggle="modal" data-target="#ModalImport"><i
                                    class="fa fa-upload"></i> Importar CSV</button>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#ModalCuenta"
                                onclick="NuevaCuenta()">
                                <i class="fa fa-plus"></i> Nueva Cuenta
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($msg): ?><div class="alert alert-success"><?php echo h($msg); ?></div><?php endif; ?>
            <?php if ($err): ?><div class="alert alert-danger"><?php echo h($err); ?></div><?php endif; ?>

            <!-- KPIs ARRIBA -->
            <div class="row">
                <div class="col-sm-3">
                    <div class="kpi-card">
                        <div class="kpi-title">Total</div>
                        <div class="kpi-value"><?php echo (int)$kpi['total']; ?></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi-card">
                        <div class="kpi-title">Habilitadas</div>
                        <div class="kpi-value"><?php echo (int)$kpi['habilitadas']; ?></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi-card">
                        <div class="kpi-title">Deshabilitadas</div>
                        <div class="kpi-value"><?php echo (int)$kpi['deshabilitadas']; ?></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi-card">
                        <div class="kpi-title">Ingresos/Gastos</div>
                        <div class="kpi-value"><?php echo (int)$kpi['ingresos'].' / '.(int)$kpi['gastos']; ?></div>
                    </div>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="panel panel-default panel-clean">
                <div class="panel-heading"><strong>Filtros</strong></div>
                <div class="panel-body">
                    <form class="form-inline" method="get">
                        <div class="form-group">
                            <label>Buscar</label>
                            <input type="text" name="q" value="<?php echo h($f_q); ?>" class="form-control"
                                placeholder="Código o nombre">
                        </div>
                        <div class="form-group" style="margin-left:8px;">
                            <label>Tipo</label>
                            <select name="tipo" class="form-control">
                                <option value="">Todos</option>
                                <?php foreach(['ACTIVO','PASIVO','PATRIMONIO','INGRESO','GASTO'] as $tp): ?>
                                <option value="<?php echo $tp; ?>" <?php if($f_tipo===$tp) echo 'selected'; ?>>
                                    <?php echo $tp; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-left:8px;">
                            <label>Nivel</label>
                            <select name="nivel" class="form-control">
                                <option value="">Todos</option>
                                <?php for($i=1;$i<=8;$i++): ?>
                                <option value="<?php echo $i; ?>"
                                    <?php if((string)$f_nivel===(string)$i) echo 'selected'; ?>><?php echo $i; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-left:8px;">
                            <label>Estado</label>
                            <select name="estado" class="form-control">
                                <option value="">Todos</option>
                                <option value="1" <?php if($f_estado==='1') echo 'selected'; ?>>Habilitado</option>
                                <option value="0" <?php if($f_estado==='0') echo 'selected'; ?>>Deshabilitado</option>
                            </select>
                        </div>
                        <button class="btn btn-default" style="margin-left:8px;">Aplicar</button>
                        <a class="btn btn-link" href="contabilidad-cuentas.php">Limpiar</a>
                    </form>
                </div>
            </div>

            <!-- TABLA -->
            <div class="panel panel-default panel-clean">
                <div class="panel-heading"><strong>Listado</strong></div>
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
                                    <th>Estado</th>
                                    <th width="150">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cuentasFil as $c): ?>
                                <?php
                  $on = ((int)$c['habilitado']===1);
                  $padre = (!empty($c['padre_id']) ? (($c['padre_codigo']??'').' - '.($c['padre_nombre']??'')) : '-');
                ?>
                                <tr>
                                    <td><?php echo (int)$c['id']; ?></td>
                                    <td><?php echo h($c['codigo']); ?></td>
                                    <td><?php echo h($c['nombre']); ?></td>
                                    <td><?php echo h($c['tipo']); ?></td>
                                    <td><?php echo (int)$c['nivel']; ?></td>
                                    <td><?php echo h($padre); ?></td>
                                    <td>
                                        <?php if ($on): ?>
                                        <span class="badge-soft badge-on">Habilitado</span>
                                        <?php else: ?>
                                        <span class="badge-soft badge-off">Deshabilitado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-xs"
                                            onclick='EditarCuenta(<?php echo json_encode($c, JSON_HEX_QUOT|JSON_HEX_APOS); ?>)'
                                            data-toggle="modal" data-target="#ModalCuenta">
                                            <i class="fa fa-pencil"></i>
                                        </button>

                                        <form method="post" style="display:inline-block;"
                                            onsubmit="return confirm('¿Cambiar estado de la cuenta?');">
                                            <input type="hidden" name="ToggleCuenta" value="1">
                                            <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                            <button class="btn btn-default btn-xs" title="Habilitar/Deshabilitar">
                                                <i class="fa fa-toggle-on"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted">Export/Import mantiene el formato del Excel: CUENTA, NOMBRE DE CUENTA,
                        NIVEL, MAYOR, TIPO.</div>
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
                        <input type="hidden" name="id" id="id_cuenta" value="0">
                        <input type="hidden" name="GuardarCuenta" value="1">

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
                            <div class="col-sm-4">
                                <label>Tipo</label>
                                <select name="tipo" id="tipo" class="form-control" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach(['ACTIVO','PASIVO','PATRIMONIO','INGRESO','GASTO'] as $tp): ?>
                                    <option value="<?php echo $tp; ?>"><?php echo $tp; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label>Nivel</label>
                                <input type="number" name="nivel" id="nivel" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-sm-4">
                                <label>Estado</label>
                                <select name="habilitado" id="habilitado" class="form-control">
                                    <option value="1">Habilitado</option>
                                    <option value="0">Deshabilitado</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top:10px;">
                            <label>Cuenta Padre (opcional)</label>
                            <select name="padre_id" id="padre_id" class="form-control">
                                <option value="">Sin padre</option>
                                <?php foreach($cuentas as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>">
                                    <?php echo h($p['codigo'].' - '.$p['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-block">Si eliges padre, el sistema sincroniza MAYOR (Excel) automáticamente.
                            </p>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL IMPORT -->
    <div class="modal fade" id="ModalImport" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-upload"></i> Importar Plan de Cuentas (CSV)</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="ImportarCSV" value="1">
                        <div class="alert alert-info">
                            El CSV debe tener columnas: <strong>CUENTA, NOMBRE DE CUENTA, NIVEL, MAYOR, TIPO</strong>.
                            Excel lo abre y lo guarda sin problema.
                        </div>
                        <input type="file" name="archivo" class="form-control" accept=".csv" required>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-default" data-dismiss="modal" type="button">Cancelar</button>
                        <button class="btn btn-info" type="submit"><i class="fa fa-upload"></i> Importar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include(MODULO.'footer.php'); ?>
    <?php include(MODULO.'Tema.JS.php'); ?>
    <script src="<?php echo ESTATICO ?>js/jquery.dataTables.min.js"></script>
    <script src="<?php echo ESTATICO ?>js/dataTables.bootstrap.js"></script>

    <script>
    (function() {
        $('#tabla_cuentas').dataTable({
            "order": [
                [1, "asc"]
            ]
        });

        window.NuevaCuenta = function() {
            $('#FormCuenta')[0].reset();
            $('#id_cuenta').val('0');
            $('#habilitado').val('1');
            $('#nivel').val('1');
        };

        window.EditarCuenta = function(c) {
            $('#id_cuenta').val(c.id || 0);
            $('#codigo').val(c.codigo || '');
            $('#nombre').val(c.nombre || '');
            $('#tipo').val(c.tipo || '');
            $('#nivel').val(c.nivel || 1);
            $('#padre_id').val(c.padre_id || '');
            $('#habilitado').val(c.habilitado || 0);
        };
    })();
    </script>

</body>

</html>