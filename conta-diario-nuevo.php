<?php
session_start();
include("sistema/configuracion.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$cn = $db->Conectar();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc($cn, $v){ return $cn->real_escape_string((string)$v); }

// Usuario creador
$usuario_id = isset($usuarioApp['id']) ? (int)$usuarioApp['id'] : (isset($usuarioApp['id_usuario']) ? (int)$usuarioApp['id_usuario'] : 0);

$msg = '';
$err = '';

/* Cuentas para combos */
$CuentasSQL = $db->SQL("SELECT id, codigo, nombre FROM contabilidad_cuentas WHERE habilitado=1 ORDER BY codigo ASC");
$cuentas = [];
if ($CuentasSQL) while($r = $CuentasSQL->fetch_assoc()) $cuentas[] = $r;

function buildOptions($cuentas){
    $html = '<option value="0">Seleccione...</option>';
    foreach($cuentas as $c){
        $html .= '<option value="'.(int)$c['id'].'">'.h($c['codigo'].' - '.$c['nombre']).'</option>';
    }
    return $html;
}
$optionsHtml = buildOptions($cuentas);

/* Guardar */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['GuardarComprobante'])) {

    $fecha = esc($cn, $_POST['fecha'] ?? date('Y-m-d'));
    $tipo  = esc($cn, $_POST['tipo_comprobante'] ?? 'INGRESO');
    $nro   = trim($_POST['nro_comprobante'] ?? '');
    $nroSQL= ($nro==='' ? "NULL" : ("'".esc($cn,$nro)."'"));

    $razon = trim($_POST['razon_social'] ?? '');
    $glosa = trim($_POST['glosa'] ?? '');
    $ref   = trim($_POST['referencia'] ?? '');
    $cheq  = trim($_POST['cheque_nro'] ?? '');
    $proy  = trim($_POST['proyecto'] ?? '');

    $razonSQL = ($razon==='' ? "NULL" : ("'".esc($cn,$razon)."'"));
    $glosaSQL = ($glosa==='' ? "NULL" : ("'".esc($cn,$glosa)."'"));
    $refSQL   = ($ref==='' ? "NULL" : ("'".esc($cn,$ref)."'"));
    $cheqSQL  = ($cheq==='' ? "NULL" : ("'".esc($cn,$cheq)."'"));
    $proySQL  = ($proy==='' ? "NULL" : ("'".esc($cn,$proy)."'"));

    $ids   = $_POST['id_cuenta'] ?? [];
    $descs = $_POST['desc_linea'] ?? [];
    $refs  = $_POST['ref_linea'] ?? [];
    $debes = $_POST['debe'] ?? [];
    $habes = $_POST['haber'] ?? [];

    $sumDebe = 0.0;
    $sumHaber= 0.0;

    $lineas = [];
    for ($i=0; $i<count($ids); $i++){
        $idC = (int)$ids[$i];
        if ($idC <= 0) continue;

        $debe  = (float)str_replace(',', '.', (string)($debes[$i] ?? 0));
        $haber = (float)str_replace(',', '.', (string)($habes[$i] ?? 0));

        if ($debe == 0 && $haber == 0) continue;

        $sumDebe  += $debe;
        $sumHaber += $haber;

        $dl = trim($descs[$i] ?? '');
        $rl = trim($refs[$i] ?? '');

        $lineas[] = [
            'id_cuenta' => $idC,
            'debe' => $debe,
            'haber'=> $haber,
            'descripcion_linea' => ($dl==='' ? "NULL" : ("'".esc($cn,$dl)."'")),
            'referencia_linea'  => ($rl==='' ? "NULL" : ("'".esc($cn,$rl)."'")),
        ];
    }

    $diff = round($sumDebe - $sumHaber, 2);

    if ($glosa === '') {
        $err = "Debe registrar una glosa.";
    } elseif (count($lineas) < 2) {
        $err = "Debe registrar al menos 2 líneas contables.";
    } elseif ($diff != 0.00) {
        $err = "Comprobante descuadrado. Diferencia: {$diff}";
    } else {

        $cn->begin_transaction();
        try {
            $ok = $db->SQL("
                INSERT INTO contabilidad_diario
                (fecha,tipo_comprobante,nro_comprobante,razon_social,glosa,descripcion,referencia,cheque_nro,proyecto,creado_por)
                VALUES
                ('{$fecha}','{$tipo}',{$nroSQL},{$razonSQL},{$glosaSQL},{$glosaSQL},{$refSQL},{$cheqSQL},{$proySQL},{$usuario_id})
            ");
            if (!$ok) throw new Exception("No se pudo guardar cabecera.");

            $idDiario = (int)$cn->insert_id;

            // Auto nro si viene vacío
            if ($nro === '') {
                $pref = substr($tipo,0,1); // I/E/T/A...
                $nroGen = $pref . "-" . str_pad((string)$idDiario, 6, "0", STR_PAD_LEFT);
                $nroGenEsc = esc($cn,$nroGen);
                $db->SQL("UPDATE contabilidad_diario SET nro_comprobante='{$nroGenEsc}' WHERE id={$idDiario} LIMIT 1");
            }

            foreach($lineas as $ln){
                $idC = (int)$ln['id_cuenta'];
                $debe  = number_format((float)$ln['debe'], 2, '.', '');
                $haber = number_format((float)$ln['haber'],2, '.', '');

                $ok2 = $db->SQL("
                    INSERT INTO contabilidad_diario_detalle
                    (id_diario,id_cuenta,descripcion_linea,referencia_linea,debe,haber)
                    VALUES
                    ({$idDiario},{$idC},{$ln['descripcion_linea']},{$ln['referencia_linea']},{$debe},{$haber})
                ");
                if (!$ok2) throw new Exception("No se pudo guardar detalle.");
            }

            $cn->commit();
            header("Location: conta-diario-ver.php?id=".$idDiario);
            exit;

        } catch(Exception $e){
            $cn->rollback();
            $err = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nuevo Comprobante | <?= TITULO ?></title>
    <link rel="stylesheet" href="<?= ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
        .panel-heading strong{ font-weight:700; }
        .tot-box{ background:#fafafa; border:1px solid #e7e7e7; padding:10px; border-radius:6px; }
        .tbl-head{ background:#f5f5f5; }
    </style>
</head>
<body>

<?php
if ($usuarioApp['id_perfil']==1) include(MODULO.'menu_admin.php');
else include(MODULO.'menu_vendedor.php');
?>

<div class="container" id="wrap">
    <div class="page-header">
        <h1>Registrar Comprobante Diario</h1>
        <a href="conta-diario.php" class="btn btn-default pull-right"><i class="fa fa-arrow-left"></i> Volver</a>
        <div style="clear:both;"></div>
    </div>

    <?php if ($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

    <form method="post" id="frmComp">

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Cabecera</strong></div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-sm-3">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-sm-3">
                        <label>Tipo</label>
                        <select name="tipo_comprobante" class="form-control" required>
                            <?php foreach(['INGRESO','EGRESO','TRASPASO','AJUSTE','APERTURA','CIERRE'] as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>Nro Comprobante</label>
                        <input type="text" name="nro_comprobante" class="form-control" placeholder="Auto (I-000001)">
                    </div>
                    <div class="col-sm-3">
                        <label>Cheque Nro</label>
                        <input type="text" name="cheque_nro" class="form-control">
                    </div>
                </div>

                <div class="row" style="margin-top:10px;">
                    <div class="col-sm-6">
                        <label>Razón Social</label>
                        <input type="text" name="razon_social" class="form-control" placeholder="Cliente / Proveedor">
                    </div>
                    <div class="col-sm-6">
                        <label>Referencia</label>
                        <input type="text" name="referencia" class="form-control">
                    </div>
                </div>

                <div class="row" style="margin-top:10px;">
                    <div class="col-sm-6">
                        <label>Glosa</label>
                        <input type="text" name="glosa" class="form-control" required>
                    </div>
                    <div class="col-sm-6">
                        <label>Proyecto</label>
                        <input type="text" name="proyecto" class="form-control" placeholder="Opcional">
                    </div>
                </div>

            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Detalle (Debe / Haber)</strong></div>
            <div class="panel-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tblDetalle">
                        <thead class="tbl-head">
                            <tr>
                                <th style="width:270px;">Cuenta</th>
                                <th>Descripción</th>
                                <th style="width:180px;">Referencia</th>
                                <th style="width:120px;">Debe</th>
                                <th style="width:120px;">Haber</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="id_cuenta[]" class="form-control">
                                        <?= $optionsHtml ?>
                                    </select>
                                </td>
                                <td><input type="text" name="desc_linea[]" class="form-control"></td>
                                <td><input type="text" name="ref_linea[]" class="form-control"></td>
                                <td><input type="number" step="0.01" name="debe[]" class="form-control inp-debe" value="0.00"></td>
                                <td><input type="number" step="0.01" name="haber[]" class="form-control inp-haber" value="0.00"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-xs btnDel"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <button type="button" class="btn btn-default" id="btnAdd">
                            <i class="fa fa-plus"></i> Agregar línea
                        </button>
                    </div>
                    <div class="col-sm-6 text-right">
                        <div class="tot-box">
                            <strong>Totales:</strong>
                            Debe: <span id="tDebe">0.00</span> |
                            Haber: <span id="tHaber">0.00</span> |
                            Diferencia: <span id="tDiff">0.00</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="text-right">
            <button type="submit" name="GuardarComprobante" class="btn btn-primary">
                <i class="fa fa-save"></i> Guardar Comprobante
            </button>
            <a href="conta-diario.php" class="btn btn-default">Cancelar</a>
        </div>

    </form>
</div>

<?php include(MODULO.'footer.php'); ?>
<?php include(MODULO.'Tema.JS.php'); ?>

<script>
(function(){
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
        var row = `
        <tr>
            <td><select name="id_cuenta[]" class="form-control"><?= str_replace("\n","", addslashes($optionsHtml)) ?></select></td>
            <td><input type="text" name="desc_linea[]" class="form-control"></td>
            <td><input type="text" name="ref_linea[]" class="form-control"></td>
            <td><input type="number" step="0.01" name="debe[]" class="form-control inp-debe" value="0.00"></td>
            <td><input type="number" step="0.01" name="haber[]" class="form-control inp-haber" value="0.00"></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-xs btnDel"><i class="fa fa-trash"></i></button></td>
        </tr>`;
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
