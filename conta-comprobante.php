<?php
session_start();
include("sistema/configuracion.php");
include("sistema/clase/contabilidad.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Cont = new Contabilidad();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$msg=''; $err='';

$cuentas = $Cont->ListarCuentas(true); // SOLO habilitadas

// Guardar comprobante
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['GuardarComprobante'])) {

    $cab = [
        "fecha"            => $_POST['fecha'] ?? date('Y-m-d'),
        "tipo_comprobante" => $_POST['tipo_comprobante'] ?? 'INGRESO',
        "nro_comprobante"  => ($_POST['nro_comprobante'] ?? '') !== '' ? $_POST['nro_comprobante'] : null,
        "razon_social"     => ($_POST['razon_social'] ?? '') !== '' ? $_POST['razon_social'] : null,
        "glosa"            => ($_POST['glosa'] ?? '') !== '' ? $_POST['glosa'] : null,
        "proyecto"         => ($_POST['proyecto'] ?? '') !== '' ? $_POST['proyecto'] : null,
        "cheque_nro"       => ($_POST['cheque_nro'] ?? '') !== '' ? $_POST['cheque_nro'] : null,
        "referencia"       => ($_POST['referencia'] ?? '') !== '' ? $_POST['referencia'] : null,
        "tipo_cambio"      => ($_POST['tipo_cambio'] ?? '') !== '' ? $_POST['tipo_cambio'] : null,
        "moneda"           => ($_POST['moneda'] ?? '') !== '' ? $_POST['moneda'] : null,
        "creado_por"       => (int)($usuarioApp['id'] ?? 0),
    ];

    $lineas = [];
    $id_cuenta = $_POST['id_cuenta'] ?? [];
    $desc_linea= $_POST['descripcion_linea'] ?? [];
    $ref_linea = $_POST['referencia_linea'] ?? [];
    $debe      = $_POST['debe'] ?? [];
    $haber     = $_POST['haber'] ?? [];

    for ($i=0; $i<count($id_cuenta); $i++){
        $cid = (int)($id_cuenta[$i] ?? 0);
        $d = (float)str_replace(',', '.', (string)($debe[$i] ?? 0));
        $hbr = (float)str_replace(',', '.', (string)($haber[$i] ?? 0));

        if ($cid<=0) continue;
        if ($d<=0 && $hbr<=0) continue;

        $lineas[] = [
            "id_cuenta"         => $cid,
            "descripcion_linea" => ($desc_linea[$i] ?? '') !== '' ? $desc_linea[$i] : null,
            "referencia_linea"  => ($ref_linea[$i] ?? '') !== '' ? $ref_linea[$i] : null,
            "debe"              => $d,
            "haber"             => $hbr
        ];
    }

    $r = $Cont->RegistrarAsiento($cab, $lineas);

    if (!empty($r['ok'])) {
        header("Location: conta-diario.php?ver=".(int)$r['id_asiento']);
        exit;
    } else {
        $err = $r['msg'] ?? "No se pudo guardar.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registro Comprobante | <?php echo h(TITULO); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
        .card{background:#fff;border:1px solid #e7e7e7;border-radius:10px;box-shadow:0 1px 1px rgba(0,0,0,.04);margin-bottom:12px;}
        .card-h{padding:12px 14px;border-bottom:1px solid #eee;background:#f7f7f7;border-radius:10px 10px 0 0;}
        .card-b{padding:14px;}
        .totbox{border:1px dashed #bbb;border-radius:10px;padding:10px;background:#fcfcfc;}
        .tlabel{font-size:12px;color:#777;text-transform:uppercase;letter-spacing:.4px;}
        .tval{font-size:18px;font-weight:700;}
        .btn-round{border-radius:10px;}
        .table>thead>tr>th{background:#fafafa;}
        .num{text-align:right;}
        .help-inline{font-size:12px;color:#777;margin-top:4px;}
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
                    <h1 style="margin:0;">Registro de Comprobante Diario</h1>
                    <p class="text-muted" style="margin:6px 0 0;">Cabecera + Detalle. Regla: Debe = Haber.</p>
                </div>
                <div class="col-sm-4 text-right" style="padding-top:10px;">
                    <a class="btn btn-default btn-round" href="conta-diario.php">
                        <i class="fa fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>

        <?php if($err): ?><div class="alert alert-danger"><?php echo h($err); ?></div><?php endif; ?>

        <?php if (empty($cuentas)): ?>
            <div class="alert alert-warning">
                No hay cuentas habilitadas en el Plan de Cuentas. Registra/activa cuentas en <strong>Plan de Cuentas</strong>
                para poder seleccionar en el comprobante.
            </div>
        <?php endif; ?>

        <form method="post" id="formComp">
            <input type="hidden" name="GuardarComprobante" value="1">

            <div class="card">
                <div class="card-h"><strong>Cabecera</strong></div>
                <div class="card-b">

                    <div class="row">
                        <div class="col-sm-2">
                            <label>Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-sm-3">
                            <label>Tipo de Comprobante</label>
                            <select name="tipo_comprobante" class="form-control">
                                <option value="INGRESO">INGRESO</option>
                                <option value="EGRESO">EGRESO</option>
                                <option value="TRASPASO">TRASPASO</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label>Comprob. Nro</label>
                            <input type="text" name="nro_comprobante" class="form-control" placeholder="Auto (I-000001)">
                            <div class="help-inline">Déjalo vacío para autogenerar.</div>
                        </div>
                        <div class="col-sm-4">
                            <label>Razón Social</label>
                            <input type="text" name="razon_social" class="form-control" placeholder="Cliente / Proveedor">
                        </div>
                    </div>

                    <div class="row" style="margin-top:10px;">
                        <div class="col-sm-6">
                            <label>Glosa</label>
                            <input type="text" name="glosa" class="form-control" placeholder="Ej: Venta, pago proveedor...">
                        </div>
                        <div class="col-sm-2">
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

                    <div class="row" style="margin-top:10px;">
                        <div class="col-sm-2">
                            <label>T.C.</label>
                            <input type="number" step="0.0001" name="tipo_cambio" class="form-control" placeholder="6.96">
                        </div>
                        <div class="col-sm-2">
                            <label>Moneda</label>
                            <input type="text" name="moneda" class="form-control" placeholder="BOB / USD">
                        </div>
                    </div>

                </div>
            </div>

            <div class="card">
                <div class="card-h">
                    <div class="row">
                        <div class="col-sm-6"><strong>Detalle</strong></div>
                        <div class="col-sm-6 text-right">
                            <button type="button" class="btn btn-default btn-round" id="btnAdd" <?php echo empty($cuentas)?'disabled':''; ?>>
                                <i class="fa fa-plus"></i> Agregar línea
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-b">

                    <div class="table-responsive">
                        <table class="table table-bordered" id="tblDet">
                            <thead>
                                <tr>
                                    <th style="min-width:260px;">Cuenta</th>
                                    <th>Descripción</th>
                                    <th style="min-width:160px;">Referencia</th>
                                    <th class="num" style="width:120px;">Debe</th>
                                    <th class="num" style="width:120px;">Haber</th>
                                    <th style="width:60px;"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="text-muted">Regla: Debe = Haber para guardar. Mínimo 2 líneas.</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="totbox text-right">
                                <div><span class="tlabel">Total Debe</span> <span class="tval" id="tDebe">0.00</span></div>
                                <div><span class="tlabel">Total Haber</span> <span class="tval" id="tHaber">0.00</span></div>
                                <div><span class="tlabel">Diferencia</span> <span class="tval" id="tDiff">0.00</span></div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary btn-round" id="btnSave" <?php echo empty($cuentas)?'disabled':''; ?>>
                            <i class="fa fa-save"></i> Guardar
                        </button>
                        <a class="btn btn-default btn-round" href="conta-diario.php">Cancelar</a>
                    </div>

                </div>
            </div>

        </form>

    </div>
</div>

<?php include(MODULO.'footer.php'); ?>
<?php include(MODULO.'Tema.JS.php'); ?>

<script>
(function() {
    var cuentas = <?php echo json_encode($cuentas, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

    function optionCuentas() {
        var html = '<option value="">Seleccione...</option>';
        if (!cuentas || !cuentas.length) {
            html += '<option value="">(Sin cuentas habilitadas)</option>';
            return html;
        }
        var groups = {};
        cuentas.forEach(function(c) {
            groups[c.tipo] = groups[c.tipo] || [];
            groups[c.tipo].push(c);
        });

        Object.keys(groups).forEach(function(tp) {
            html += '<optgroup label="' + tp + '">';
            groups[tp].forEach(function(c) {
                html += '<option value="' + c.id + '">' + c.codigo + ' - ' + c.nombre + '</option>';
            });
            html += '</optgroup>';
        });
        return html;
    }

    function addRow() {
        var tr = document.createElement('tr');
        tr.innerHTML = ''
            + '<td><select name="id_cuenta[]" class="form-control">' + optionCuentas() + '</select></td>'
            + '<td><input type="text" name="descripcion_linea[]" class="form-control" placeholder="Detalle..."></td>'
            + '<td><input type="text" name="referencia_linea[]" class="form-control" placeholder="Referencia..."></td>'
            + '<td><input type="number" step="0.01" min="0" name="debe[]" class="form-control num inpDebe" value="0"></td>'
            + '<td><input type="number" step="0.01" min="0" name="haber[]" class="form-control num inpHaber" value="0"></td>'
            + '<td class="text-center"><button type="button" class="btn btn-danger btn-xs btnDel">X</button></td>';

        document.querySelector('#tblDet tbody').appendChild(tr);
        calc();
    }

    function calc() {
        var tDebe = 0, tHaber = 0;
        document.querySelectorAll('.inpDebe').forEach(function(i){ tDebe += parseFloat(i.value || 0); });
        document.querySelectorAll('.inpHaber').forEach(function(i){ tHaber += parseFloat(i.value || 0); });

        tDebe = Math.round(tDebe * 100) / 100;
        tHaber = Math.round(tHaber * 100) / 100;
        var diff = Math.round((tDebe - tHaber) * 100) / 100;

        document.getElementById('tDebe').innerText = tDebe.toFixed(2);
        document.getElementById('tHaber').innerText = tHaber.toFixed(2);
        document.getElementById('tDiff').innerText = diff.toFixed(2);

        var rows = document.querySelectorAll('#tblDet tbody tr').length;
        var btn = document.getElementById('btnSave');
        if (btn) btn.disabled = (diff !== 0 || rows < 2 || !cuentas.length);
    }

    var btnAdd = document.getElementById('btnAdd');
    if (btnAdd) btnAdd.addEventListener('click', addRow);

    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btnDel')) {
            e.target.closest('tr').remove();
            calc();
        }
    });

    document.addEventListener('input', function(e) {
        if (!e.target) return;
        if (e.target.classList.contains('inpDebe') || e.target.classList.contains('inpHaber')) calc();
    });

    // iniciar con 2 líneas si hay cuentas
    if (cuentas && cuentas.length) {
        addRow(); addRow();
    }
})();
</script>
</body>
</html>
