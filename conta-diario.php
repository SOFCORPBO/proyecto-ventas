<?php
session_start();
include("sistema/configuracion.php");
include("sistema/clase/contabilidad.clase.php");

$usuario->LoginCuentaConsulta();
$usuario->VerificacionCuenta();

$Cont = new Contabilidad();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* =========================
   Utils: Excel/CSV sin composer
========================= */
function out_download_headers($filename, $contentType){
    header("Content-Type: {$contentType}; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Pragma: no-cache");
    header("Expires: 0");
}

function parse_decimal($v){
    $s = trim((string)$v);
    if ($s==='') return 0.0;
    $s = str_replace(["\t"," "], "", $s);
    // si viene "1.234,56" => "1234.56"
    if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    } else {
        $s = str_replace(',', '.', $s);
    }
    return (float)$s;
}

function parse_date_any($v){
    $s = trim((string)$v);
    if ($s==='') return null;

    // si viene yyyy-mm-dd
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;

    // si viene dd/mm/yyyy
    if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $s)) {
        $p = explode('/', $s);
        $d = str_pad($p[0],2,'0',STR_PAD_LEFT);
        $m = str_pad($p[1],2,'0',STR_PAD_LEFT);
        $y = $p[2];
        return "{$y}-{$m}-{$d}";
    }

    $ts = strtotime($s);
    if ($ts) return date('Y-m-d', $ts);
    return null;
}

/* ============= XLSX Reader mínimo (sin composer) =============
   Lee la 1ra hoja y devuelve filas (array) con header en la fila 1.
*/
function xlsx_read_rows($path){
    if (!class_exists('ZipArchive')) return ["ok"=>false,"msg"=>"ZipArchive no disponible en PHP."];
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return ["ok"=>false,"msg"=>"No se pudo abrir XLSX."];

    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sx = @simplexml_load_string($sharedXml);
        if ($sx && isset($sx->si)) {
            foreach ($sx->si as $si) {
                // puede venir en <t> o en múltiples <r>
                if (isset($si->t)) $shared[] = (string)$si->t;
                else {
                    $txt = '';
                    if (isset($si->r)) foreach ($si->r as $r) $txt .= (string)$r->t;
                    $shared[] = $txt;
                }
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        return ["ok"=>false,"msg"=>"No existe sheet1.xml en el XLSX."];
    }

    $sheet = @simplexml_load_string($sheetXml);
    if (!$sheet || !isset($sheet->sheetData)) {
        $zip->close();
        return ["ok"=>false,"msg"=>"XLSX inválido (sheetData)."];
    }

    // convertir referencia A1->colIndex
    $colIndex = function($cellRef){
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($cellRef));
        $n = 0;
        for ($i=0; $i<strlen($letters); $i++){
            $n = $n*26 + (ord($letters[$i]) - 64);
        }
        return $n-1; // 0-based
    };

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $r = [];
        foreach ($row->c as $c) {
            $ref = (string)$c['r'];
            $idx = $colIndex($ref);
            $t = (string)$c['t'];
            $v = isset($c->v) ? (string)$c->v : '';
            $val = '';

            if ($t === 's') {
                $si = (int)$v;
                $val = isset($shared[$si]) ? $shared[$si] : '';
            } elseif ($t === 'inlineStr') {
                $val = (string)$c->is->t;
            } else {
                $val = $v;
            }
            $r[$idx] = $val;
        }
        if (!empty($r)) $rows[] = $r;
    }

    $zip->close();

    if (count($rows) < 1) return ["ok"=>true,"rows"=>[]];

    // normalizar a matriz completa por columnas
    $maxCol = 0;
    foreach ($rows as $r) {
        $keys = array_keys($r);
        if ($keys) $maxCol = max($maxCol, max($keys));
    }
    for ($i=0; $i<count($rows); $i++){
        $full = [];
        for ($c=0; $c<=$maxCol; $c++){
            $full[] = isset($rows[$i][$c]) ? $rows[$i][$c] : '';
        }
        $rows[$i] = $full;
    }

    // convertir a assoc por encabezados (fila 1)
    $headers = array_map('trim', $rows[0]);
    $data = [];
    for ($i=1; $i<count($rows); $i++){
        $row = [];
        for ($c=0; $c<count($headers); $c++){
            $key = $headers[$c] !== '' ? $headers[$c] : ('COL'.$c);
            $row[$key] = $rows[$i][$c] ?? '';
        }
        // omitir filas vacías
        $has = false;
        foreach ($row as $vv) { if (trim((string)$vv) !== '') { $has=true; break; } }
        if ($has) $data[] = $row;
    }

    return ["ok"=>true,"rows"=>$data];
}

/* =========================
   TEMPLATE para importar (Excel .xls)
   Columnas (1 fila = 1 línea del comprobante):
   FECHA | TIPO | NRO_DOC | RAZON_SOCIAL | GLOSA | TIPO_CAMBIO | CHEQUE_NRO | PROYECTO | REFERENCIA
   CUENTA_CODIGO | LINEA_REFERENCIA | DEBE | HABER
========================= */
function export_template_import($as='xls'){
    $cols = [
        'FECHA','TIPO','NRO_DOC','RAZON_SOCIAL','GLOSA','TIPO_CAMBIO','CHEQUE_NRO','PROYECTO','REFERENCIA',
        'CUENTA_CODIGO','LINEA_REFERENCIA','DEBE','HABER'
    ];

    if ($as === 'csv'){
        out_download_headers("plantilla_comprobantes.csv","text/csv");
        $out = fopen("php://output","w");
        fputcsv($out, $cols);
        fclose($out);
        exit;
    }

    out_download_headers("plantilla_comprobantes.xls","application/vnd.ms-excel");
    echo "<html><head><meta charset='utf-8'></head><body>";
    echo "<table border='1'>";
    echo "<tr>";
    foreach($cols as $c) echo "<th style='background:#f2f2f2;'>".h($c)."</th>";
    echo "</tr>";
    // fila ejemplo (vacía)
    echo "<tr>";
    foreach($cols as $c) echo "<td></td>";
    echo "</tr>";
    echo "</table></body></html>";
    exit;
}

/* =========================
   EXPORT: Libro Diario a Excel (HTML .xls)
   Formato tipo imagen: bloque por comprobante
========================= */
function export_libro_diario_excel($Cont, $lista, $empresa, $nit, $ciudad){
    out_download_headers("LIBRO_DIARIO_".date('Ymd_His').".xls","application/vnd.ms-excel");

    echo "<html><head><meta charset='utf-8'>";
    echo "<style>
        body{font-family:Arial;font-size:12px;color:#111;}
        .title{font-size:18px;font-weight:bold;text-align:center;margin:12px 0;}
        .hdr td{vertical-align:top;}
        .box{border:2px solid #000;padding:8px;margin:10px 0;}
        table{border-collapse:collapse;width:100%;}
        th,td{border:1px solid #000;padding:6px;}
        th{background:#f2f2f2;text-align:left;}
        .num{text-align:right;}
        .no-border td{border:none!important;}
        .spacer{height:10px;}
    </style>";
    echo "</head><body>";

    // Encabezado general (como el reporte)
    echo "<table class='no-border' style='width:100%;margin-bottom:8px;'>";
    echo "<tr class='hdr'>
            <td style='width:50%;'>
                <div><strong>".h($empresa)."</strong></div>
                <div>NIT: ".h($nit)."</div>
                <div>".h($ciudad)."</div>
            </td>
            <td style='width:50%;text-align:right;'>
                <div>Página: 1</div>
                <div>Fecha: ".h(date('d/m/Y'))."</div>
                <div>Hora: ".h(date('H:i:s'))."</div>
            </td>
          </tr>";
    echo "</table>";

    echo "<div class='title'>LIBRO DIARIO</div>";

    foreach($lista as $a){
        $id = (int)$a['id'];
        $A = $Cont->ObtenerAsiento($id);
        $cab = $A['cabecera'];
        $det = $A['detalle'];
        if (!$cab) continue;

        echo "<div class='box'>";

        // Cabecera comprobante (2 columnas)
        echo "<table class='no-border' style='width:100%;'>";
        echo "<tr>
                <td style='width:50%;border:none;'>
                    <div><strong>Tipo:</strong> ".h($cab['tipo_comprobante'] ?? '')."</div>
                    <div><strong>Nro. Doc.:</strong> ".h($cab['nro_comprobante'] ?? $cab['id'])."</div>
                    <div><strong>Razon Social:</strong> ".h($cab['razon_social'] ?? '')."</div>
                    <div><strong>Glosa:</strong> ".h($cab['glosa'] ?? '')."</div>
                </td>
                <td style='width:50%;border:none;text-align:right;'>
                    <div><strong>Fecha:</strong> ".h(date('d/m/Y', strtotime($cab['fecha'])))."</div>
                    <div><strong>T.C.:</strong> ".h($cab['tipo_cambio'] ?? '')."</div>
                    <div><strong>Cheque N°:</strong> ".h($cab['cheque_nro'] ?? '')."</div>
                </td>
              </tr>";
        echo "</table>";

        // Tabla detalle
        echo "<table style='margin-top:8px;'>";
        echo "<thead>
                <tr>
                    <th style='width:140px;'>CUENTA</th>
                    <th>NOMBRE DE CUENTA</th>
                    <th style='width:220px;'>Referencia</th>
                    <th class='num' style='width:120px;'>DEBE Bs.</th>
                    <th class='num' style='width:120px;'>HABER Bs.</th>
                </tr>
              </thead><tbody>";

        $tDebe=0; $tHaber=0;
        foreach($det as $d){
            $tDebe += (float)$d['debe'];
            $tHaber += (float)$d['haber'];
            $ref = $d['referencia_linea'] ?? ($d['descripcion_linea'] ?? '');
            echo "<tr>
                    <td>".h($d['codigo'])."</td>
                    <td>".h($d['nombre'])."</td>
                    <td>".h($ref)."</td>
                    <td class='num'>".number_format((float)$d['debe'],2,'.','')."</td>
                    <td class='num'>".number_format((float)$d['haber'],2,'.','')."</td>
                  </tr>";
        }
        echo "</tbody>";
        echo "<tfoot>
                <tr>
                    <td colspan='3' class='num' style='font-weight:bold;'>Total:</td>
                    <td class='num' style='font-weight:bold;'>".number_format($tDebe,2,'.','')."</td>
                    <td class='num' style='font-weight:bold;'>".number_format($tHaber,2,'.','')."</td>
                </tr>
              </tfoot>";
        echo "</table>";

        echo "</div>";
    }

    echo "</body></html>";
    exit;
}

/* =========================
   EXPORT: Word (.doc) sin composer (HTML)
========================= */
function export_libro_diario_doc($Cont, $lista, $empresa, $nit, $ciudad){
    out_download_headers("LIBRO_DIARIO_".date('Ymd_His').".doc","application/msword");

    echo "<html><head><meta charset='utf-8'>
    <style>
        body{font-family:Arial;font-size:12px;color:#111;}
        .top{display:flex;justify-content:space-between;margin-bottom:10px;}
        .title{text-align:center;font-size:18px;font-weight:700;margin:10px 0 14px;}
        .box{border:2px solid #000;padding:10px;margin-bottom:14px;}
        .row{display:flex;justify-content:space-between;gap:10px;}
        .col{width:50%;}
        .lbl{font-weight:700;}
        table{width:100%;border-collapse:collapse;margin-top:8px;}
        th,td{border:1px solid #000;padding:6px;}
        th{background:#f2f2f2;text-align:left;}
        .num{text-align:right;}
        .total{font-weight:700;}
    </style></head><body>";

    echo "<div class='top'>
            <div>
                <div><strong>".h($empresa)."</strong></div>
                <div>NIT: ".h($nit)."</div>
                <div>".h($ciudad)."</div>
            </div>
            <div style='text-align:right;'>
                <div>Página: 1</div>
                <div>Fecha: ".h(date('d/m/Y'))."</div>
                <div>Hora: ".h(date('H:i:s'))."</div>
            </div>
          </div>";

    echo "<div class='title'>LIBRO DIARIO</div>";

    foreach($lista as $a){
        $id = (int)$a['id'];
        $A = $Cont->ObtenerAsiento($id);
        $cab = $A['cabecera'];
        $det = $A['detalle'];
        if (!$cab) continue;

        echo "<div class='box'>
            <div class='row'>
                <div class='col'>
                    <div><span class='lbl'>Tipo:</span> ".h($cab['tipo_comprobante'] ?? '')."</div>
                    <div><span class='lbl'>Nro. Doc.:</span> ".h($cab['nro_comprobante'] ?? ('ID-'.$cab['id']))."</div>
                    <div><span class='lbl'>Razon Social:</span> ".h($cab['razon_social'] ?? '')."</div>
                    <div><span class='lbl'>Glosa:</span> ".h($cab['glosa'] ?? '')."</div>
                </div>
                <div class='col'>
                    <div><span class='lbl'>Fecha:</span> ".h(date('d/m/Y', strtotime($cab['fecha'])))."</div>
                    <div><span class='lbl'>T.C.:</span> ".h($cab['tipo_cambio'] ?? '')."</div>
                    <div><span class='lbl'>Cheque N°:</span> ".h($cab['cheque_nro'] ?? '')."</div>
                </div>
            </div>";

        echo "<table>
            <thead>
                <tr>
                    <th style='width:140px;'>CUENTA</th>
                    <th>NOMBRE DE CUENTA</th>
                    <th style='width:220px;'>Referencia</th>
                    <th class='num' style='width:120px;'>DEBE Bs.</th>
                    <th class='num' style='width:120px;'>HABER Bs.</th>
                </tr>
            </thead><tbody>";

        $tDebe=0; $tHaber=0;
        foreach($det as $d){
            $tDebe += (float)$d['debe'];
            $tHaber += (float)$d['haber'];
            $ref = $d['referencia_linea'] ?? ($d['descripcion_linea'] ?? '');
            echo "<tr>
                <td>".h($d['codigo'])."</td>
                <td>".h($d['nombre'])."</td>
                <td>".h($ref)."</td>
                <td class='num'>".number_format((float)$d['debe'],2)."</td>
                <td class='num'>".number_format((float)$d['haber'],2)."</td>
            </tr>";
        }

        echo "</tbody><tfoot>
            <tr class='total'>
                <td colspan='3' class='num'>Total:</td>
                <td class='num'>".number_format($tDebe,2)."</td>
                <td class='num'>".number_format($tHaber,2)."</td>
            </tr>
        </tfoot></table>";

        echo "</div>";
    }

    echo "</body></html>";
    exit;
}

/* =========================
   IMPORT: CSV o XLSX (sin composer)
========================= */
function import_comprobantes($Cont, $db, $filePath, $ext, $usuarioId){
    $usuarioId = (int)$usuarioId;

    // cache cuentas por código
    $map = [];
    $res = $db->SQL("SELECT id, codigo FROM contabilidad_cuentas WHERE habilitado=1");
    if ($res) while($r = $res->fetch_assoc()) $map[trim($r['codigo'])] = (int)$r['id'];

    $rows = [];

    if ($ext === 'csv') {
        $fh = fopen($filePath, 'r');
        if (!$fh) return ["ok"=>false,"msg"=>"No se pudo leer CSV."];
        $headers = fgetcsv($fh);
        if (!$headers) { fclose($fh); return ["ok"=>false,"msg"=>"CSV vacío."]; }
        $headers = array_map('trim', $headers);

        while(($data = fgetcsv($fh)) !== false){
            $row = [];
            for($i=0;$i<count($headers);$i++){
                $row[$headers[$i]] = $data[$i] ?? '';
            }
            $rows[] = $row;
        }
        fclose($fh);
    } else { // xlsx
        $r = xlsx_read_rows($filePath);
        if (!$r['ok']) return ["ok"=>false,"msg"=>$r['msg']];
        $rows = $r['rows'];
    }

    if (!$rows) return ["ok"=>false,"msg"=>"No hay filas para importar."];

    // Normaliza claves (acepta variantes)
    $get = function($row, $k){
        // intenta exacto
        if (isset($row[$k])) return $row[$k];
        // intenta sin espacios
        $k2 = str_replace(' ', '_', $k);
        if (isset($row[$k2])) return $row[$k2];
        // intenta upper
        foreach($row as $kk=>$vv){
            if (strtoupper(trim($kk)) === strtoupper(trim($k))) return $vv;
            if (strtoupper(str_replace(' ', '_', trim($kk))) === strtoupper($k2)) return $vv;
        }
        return '';
    };

    // agrupar por comprobante (NRO_DOC + FECHA + TIPO)
    $groups = [];
    foreach($rows as $row){
        $fecha = parse_date_any($get($row,'FECHA'));
        $tipo  = strtoupper(trim((string)$get($row,'TIPO')));
        $nro   = trim((string)$get($row,'NRO_DOC'));
        $cta   = trim((string)$get($row,'CUENTA_CODIGO'));

        if (!$fecha || $tipo==='' || $nro==='' || $cta==='') continue;

        $key = $fecha.'|'.$tipo.'|'.$nro;

        if (!isset($groups[$key])) {
            $groups[$key] = [
                "cab" => [
                    "fecha" => $fecha,
                    "tipo_comprobante" => $tipo,
                    "nro_comprobante" => $nro,
                    "razon_social" => trim((string)$get($row,'RAZON_SOCIAL')),
                    "glosa" => trim((string)$get($row,'GLOSA')),
                    "tipo_cambio" => trim((string)$get($row,'TIPO_CAMBIO')),
                    "cheque_nro" => trim((string)$get($row,'CHEQUE_NRO')),
                    "proyecto" => trim((string)$get($row,'PROYECTO')),
                    "referencia" => trim((string)$get($row,'REFERENCIA')),
                    "descripcion" => trim((string)$get($row,'GLOSA')),
                    "creado_por" => $usuarioId
                ],
                "lineas" => []
            ];
        }

        $idCuenta = $map[$cta] ?? 0;
        if ($idCuenta <= 0) {
            // cuenta no existe => se marca error pero no rompe todo
            $groups[$key]["_err"][] = "Cuenta no encontrada: {$cta}";
            continue;
        }

        $debe  = parse_decimal($get($row,'DEBE'));
        $haber = parse_decimal($get($row,'HABER'));
        $refL  = trim((string)$get($row,'LINEA_REFERENCIA'));

        $groups[$key]["lineas"][] = [
            "id_cuenta" => $idCuenta,
            "debe" => $debe,
            "haber" => $haber,
            "referencia_linea" => ($refL!=='' ? $refL : null),
            "descripcion_linea" => ($refL!=='' ? $refL : null)
        ];
    }

    if (!$groups) return ["ok"=>false,"msg"=>"No se detectaron comprobantes válidos (revisa columnas/filas)."];

    $okCount = 0; $fail = [];
    foreach($groups as $key=>$g){
        if (!empty($g["_err"])) {
            $fail[] = $key." => ".implode(" | ", $g["_err"]);
            continue;
        }
        if (count($g["lineas"]) < 2) {
            $fail[] = $key." => comprobante requiere mínimo 2 líneas.";
            continue;
        }

        // si tipo_cambio vino vacío, no forzar
        $r = $Cont->RegistrarAsiento($g["cab"], $g["lineas"]);
        if (!empty($r["ok"])) $okCount++;
        else $fail[] = $key." => ".($r["msg"] ?? "Error al registrar");
    }

    return [
        "ok"=>true,
        "okCount"=>$okCount,
        "fail"=>$fail
    ];
}

/* =========================
   PARAMS filtros (global)
========================= */
$msg=''; $err='';

$hoy = date('Y-m-d');
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? $hoy;
$tipo  = $_GET['tipo'] ?? '';
$estado = $_GET['estado'] ?? '1'; // 1=solo habilitados, 0=incluye anulados
$incluyeAnulados = ($estado === '0');

$empresa = defined('TITULO') ? TITULO : 'EMPRESA';
$nit = defined('NIT') ? NIT : '-';
$ciudad = defined('CIUDAD') ? CIUDAD : 'SANTA CRUZ - BOLIVIA';

/* =========================
   Descarga plantilla
========================= */
if (isset($_GET['template']) && $_GET['template'] === 'comprobantes') {
    export_template_import('xls'); // o 'csv'
}

/* =========================
   Exportaciones (Excel / Word)
========================= */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $lista = $Cont->ListarDiario($desde, $hasta, ($tipo!==''?$tipo:null), $incluyeAnulados);
    export_libro_diario_excel($Cont, $lista, $empresa, $nit, $ciudad);
}
if (isset($_GET['export']) && $_GET['export'] === 'doc') {
    $lista = $Cont->ListarDiario($desde, $hasta, ($tipo!==''?$tipo:null), $incluyeAnulados);
    export_libro_diario_doc($Cont, $lista, $empresa, $nit, $ciudad);
}

/* =========================
   Importación (CSV/XLSX)
========================= */
if (isset($_POST['ImportarDiario'])) {
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $err = "Debe seleccionar un archivo válido (CSV o XLSX).";
    } else {
        $name = $_FILES['archivo']['name'];
        $tmp  = $_FILES['archivo']['tmp_name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv','xlsx'], true)) {
            $err = "Formato no soportado. Use CSV o XLSX.";
        } else {
            $r = import_comprobantes($Cont, $db, $tmp, $ext, (int)($usuarioApp['id'] ?? 0));
            if (!empty($r['ok'])) {
                $msg = "Importación finalizada. Comprobantes creados: ".(int)$r['okCount'];
                if (!empty($r['fail'])) {
                    $msg .= " | Con errores: ".count($r['fail']);
                    // Mostrar primeros 8 errores
                    $errList = array_slice($r['fail'], 0, 8);
                    $err = "Algunos comprobantes fallaron:<br>- ".implode("<br>- ", array_map('h', $errList));
                }
            } else {
                $err = $r['msg'] ?? "No se pudo importar.";
            }
        }
    }
}

/* =========================
   Anular
========================= */
if (isset($_POST['AnularAsiento'])) {
  $id = (int)($_POST['id'] ?? 0);
  $com = trim($_POST['comentario'] ?? 'Anulado');
  $r = $Cont->AnularAsiento($id, $com, (int)($usuarioApp['id'] ?? 0));
  if (!empty($r['ok'])) $msg = "Asiento anulado.";
  else $err = $r['msg'] ?? "No se pudo anular.";
}

/* =========================
   Ver / imprimir (se mantiene tu print HTML)
========================= */
if (isset($_GET['ver']) && (int)$_GET['ver']>0) {
  $idView = (int)$_GET['ver'];
  $A = $Cont->ObtenerAsiento($idView);
  if (!$A['cabecera']) { $err = "Asiento no encontrado."; }
}

if (isset($_GET['print']) && (int)$_GET['print']>0) {
  $idP = (int)$_GET['print'];
  $A = $Cont->ObtenerAsiento($idP);
  $cab = $A['cabecera'];
  $det = $A['detalle'];

  if (!$cab) { echo "No existe."; exit; }

  ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Libro Diario - <?php echo h($cab['nro_comprobante'] ?? $cab['id']); ?></title>
    <style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #111;
    }

    .top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .title {
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        margin: 8px 0 14px;
    }

    .box {
        border: 2px solid #000;
        padding: 10px;
        margin-bottom: 14px;
    }

    .row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .col {
        width: 50%;
    }

    .lbl {
        font-weight: 700;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    th,
    td {
        border: 1px solid #000;
        padding: 6px;
    }

    th {
        background: #f2f2f2;
        text-align: left;
    }

    .num {
        text-align: right;
    }

    .total {
        font-weight: 700;
    }

    @media print {
        .noprint {
            display: none;
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
            <div><strong><?php echo h($empresa); ?></strong></div>
            <div>NIT: <?php echo h($nit); ?></div>
            <div><?php echo h($ciudad); ?></div>
        </div>
        <div style="text-align:right;">
            <div>Página: 1</div>
            <div>Fecha: <?php echo h(date('d/m/Y')); ?></div>
            <div>Hora: <?php echo h(date('H:i:s')); ?></div>
        </div>
    </div>

    <div class="title">LIBRO DIARIO</div>

    <div class="box">
        <div class="row">
            <div class="col">
                <div><span class="lbl">Tipo:</span> <?php echo h($cab['tipo_comprobante'] ?? ''); ?></div>
                <div><span class="lbl">Nro. Doc.:</span> <?php echo h($cab['nro_comprobante'] ?? ('ID-'.$cab['id'])); ?>
                </div>
                <div><span class="lbl">Razón Social:</span> <?php echo h($cab['razon_social'] ?? ''); ?></div>
                <div><span class="lbl">Glosa:</span> <?php echo h($cab['glosa'] ?? ''); ?></div>
            </div>
            <div class="col">
                <div><span class="lbl">Fecha:</span> <?php echo h(date('d/m/Y', strtotime($cab['fecha']))); ?></div>
                <div><span class="lbl">T.C.:</span> <?php echo h($cab['tipo_cambio'] ?? ''); ?></div>
                <div><span class="lbl">Cheque N°:</span> <?php echo h($cab['cheque_nro'] ?? ''); ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:140px;">CUENTA</th>
                    <th>NOMBRE DE CUENTA</th>
                    <th style="width:220px;">Referencia</th>
                    <th class="num" style="width:120px;">DEBE Bs.</th>
                    <th class="num" style="width:120px;">HABER Bs.</th>
                </tr>
            </thead>
            <tbody>
                <?php $tDebe=0; $tHaber=0; foreach($det as $d): $tDebe+=(float)$d['debe']; $tHaber+=(float)$d['haber']; ?>
                <tr>
                    <td><?php echo h($d['codigo']); ?></td>
                    <td><?php echo h($d['nombre']); ?></td>
                    <td><?php echo h($d['referencia_linea'] ?? $d['descripcion_linea'] ?? ''); ?></td>
                    <td class="num"><?php echo number_format((float)$d['debe'],2); ?></td>
                    <td class="num"><?php echo number_format((float)$d['haber'],2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="3" class="num">Total:</td>
                    <td class="num"><?php echo number_format($tDebe,2); ?></td>
                    <td class="num"><?php echo number_format($tHaber,2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>

</html>
<?php
  exit;
}

/* =========================
   Lista + KPIs
========================= */
$lista = $Cont->ListarDiario($desde, $hasta, ($tipo!==''?$tipo:null), $incluyeAnulados);

$totalComp = count($lista);
$totalMonto = 0;
$cntIng=0; $cntEgr=0; $cntTra=0;
foreach($lista as $a){
  $totalMonto += (float)($a['total_debe'] ?? 0);
  if (($a['tipo_comprobante'] ?? '')==='INGRESO') $cntIng++;
  if (($a['tipo_comprobante'] ?? '')==='EGRESO') $cntEgr++;
  if (($a['tipo_comprobante'] ?? '')==='TRASPASO') $cntTra++;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Libro Diario | <?php echo TITULO; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo ESTATICO ?>img/favicon.ico">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ESTATICO ?>css/dataTables.bootstrap.css">
    <?php include(MODULO."Tema.CSS.php"); ?>

    <style>
    .page-header {
        margin-top: 10px;
    }

    .kpi {
        background: #fff;
        border: 1px solid #e7e7e7;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    }

    .kpi .t {
        font-size: 12px;
        color: #777;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .kpi .v {
        font-size: 20px;
        font-weight: 700;
        margin-top: 6px;
    }

    .panel-clean {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    }

    .panel-clean .panel-heading {
        background: #f7f7f7;
        border-bottom: 1px solid #eee;
    }

    .badge-off {
        background: #ffebee;
        color: #b71c1c;
        padding: 5px 10px;
        border-radius: 999px;
    }

    .top-actions .btn {
        margin-left: 6px;
        margin-bottom: 6px;
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

            <div class="page-header">
                <div class="row">
                    <div class="col-sm-7">
                        <h1 style="margin:0;">Libro Diario</h1>
                        <p class="text-muted" style="margin:6px 0 0;">Listado + ver + imprimir + exportar/importar (sin
                            composer).</p>
                    </div>
                    <div class="col-sm-5 text-right top-actions" style="padding-top:10px;">
                        <a class="btn btn-primary" href="conta-comprobante.php"><i class="fa fa-plus"></i> Nuevo
                            Comprobante</a>

                        <a class="btn btn-default"
                            href="conta-diario.php?<?php echo http_build_query(['desde'=>$desde,'hasta'=>$hasta,'tipo'=>$tipo,'estado'=>$estado,'export'=>'excel']); ?>">
                            <i class="fa fa-file-excel-o"></i> Exportar Excel
                        </a>

                        <a class="btn btn-default"
                            href="conta-diario.php?<?php echo http_build_query(['desde'=>$desde,'hasta'=>$hasta,'tipo'=>$tipo,'estado'=>$estado,'export'=>'doc']); ?>">
                            <i class="fa fa-file-word-o"></i> Exportar Doc
                        </a>

                        <a class="btn btn-default" href="conta-diario.php?template=comprobantes">
                            <i class="fa fa-download"></i> Plantilla
                        </a>

                        <button class="btn btn-success" data-toggle="modal" data-target="#ModalImport">
                            <i class="fa fa-upload"></i> Importar
                        </button>
                    </div>
                </div>
            </div>

            <?php if($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
            <?php if($err): ?><div class="alert alert-danger"><?php echo $err; ?></div><?php endif; ?>

            <!-- KPIs -->
            <div class="row">
                <div class="col-sm-3">
                    <div class="kpi">
                        <div class="t">Comprobantes</div>
                        <div class="v"><?php echo (int)$totalComp; ?></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="kpi">
                        <div class="t">Monto total</div>
                        <div class="v">Bs <?php echo number_format($totalMonto,2); ?></div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="kpi">
                        <div class="t">Ingresos</div>
                        <div class="v"><?php echo (int)$cntIng; ?></div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="kpi">
                        <div class="t">Egresos</div>
                        <div class="v"><?php echo (int)$cntEgr; ?></div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="kpi">
                        <div class="t">Traspasos</div>
                        <div class="v"><?php echo (int)$cntTra; ?></div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="panel panel-default panel-clean">
                <div class="panel-heading"><strong>Filtros</strong></div>
                <div class="panel-body">
                    <form class="form-inline" method="get">
                        <label>Desde</label>
                        <input type="date" name="desde" value="<?php echo h($desde); ?>" class="form-control">

                        <label>Hasta</label>
                        <input type="date" name="hasta" value="<?php echo h($hasta); ?>" class="form-control">

                        <label>Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            <option value="INGRESO" <?php if($tipo==='INGRESO') echo 'selected'; ?>>INGRESO</option>
                            <option value="EGRESO" <?php if($tipo==='EGRESO') echo 'selected'; ?>>EGRESO</option>
                            <option value="TRASPASO" <?php if($tipo==='TRASPASO') echo 'selected'; ?>>TRASPASO</option>
                        </select>

                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            <option value="1" <?php if($estado==='1') echo 'selected'; ?>>Solo habilitados</option>
                            <option value="0" <?php if($estado==='0') echo 'selected'; ?>>Incluir anulados</option>
                        </select>

                        <button class="btn btn-default">Aplicar</button>
                    </form>
                </div>
            </div>

            <!-- Tabla -->
            <div class="panel panel-default panel-clean">
                <div class="panel-heading"><strong>Comprobantes</strong></div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tbl">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Nro</th>
                                    <th>Razón Social</th>
                                    <th>Glosa</th>
                                    <th class="text-right">Total</th>
                                    <th>Estado</th>
                                    <th width="240">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($lista as $a): ?>
                                <?php $ok = ((int)$a['habilitado']===1); ?>
                                <tr>
                                    <td><?php echo h($a['fecha']); ?></td>
                                    <td><?php echo h($a['tipo_comprobante']); ?></td>
                                    <td><?php echo h($a['nro_comprobante'] ?? $a['id']); ?></td>
                                    <td><?php echo h($a['razon_social'] ?? ''); ?></td>
                                    <td><?php echo h($a['glosa'] ?? ''); ?></td>
                                    <td class="text-right">
                                        <?php echo number_format((float)($a['total_debe'] ?? 0),2); ?></td>
                                    <td>
                                        <?php if($ok): ?>
                                        <span class="label label-success">Habilitado</span>
                                        <?php else: ?>
                                        <span class="badge-off">Anulado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="btn btn-info btn-xs"
                                            href="conta-diario.php?ver=<?php echo (int)$a['id']; ?>">
                                            <i class="fa fa-eye"></i> Ver
                                        </a>
                                        <a class="btn btn-default btn-xs" target="_blank"
                                            href="conta-diario.php?print=<?php echo (int)$a['id']; ?>">
                                            <i class="fa fa-print"></i> Imprimir
                                        </a>

                                        <?php if($ok): ?>
                                        <form method="post" style="display:inline-block;"
                                            onsubmit="return confirm('¿Anular asiento?');">
                                            <input type="hidden" name="AnularAsiento" value="1">
                                            <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                            <input type="hidden" name="comentario" value="Anulado desde Libro Diario">
                                            <button class="btn btn-danger btn-xs"><i class="fa fa-times"></i>
                                                Anular</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (isset($A) && !empty($A['cabecera'])): ?>
            <?php $cab = $A['cabecera']; $det = $A['detalle']; ?>
            <div class="panel panel-default panel-clean">
                <div class="panel-heading"><strong>Vista rápida del comprobante</strong></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-sm-3"><strong>Tipo:</strong> <?php echo h($cab['tipo_comprobante']); ?></div>
                        <div class="col-sm-3"><strong>Nro:</strong>
                            <?php echo h($cab['nro_comprobante'] ?? $cab['id']); ?></div>
                        <div class="col-sm-3"><strong>Fecha:</strong> <?php echo h($cab['fecha']); ?></div>
                        <div class="col-sm-3"><strong>T.C.:</strong> <?php echo h($cab['tipo_cambio'] ?? ''); ?></div>
                    </div>
                    <div style="margin-top:8px;"><strong>Razón Social:</strong>
                        <?php echo h($cab['razon_social'] ?? ''); ?></div>
                    <div><strong>Glosa:</strong> <?php echo h($cab['glosa'] ?? ''); ?></div>

                    <hr>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Cuenta</th>
                                    <th>Nombre</th>
                                    <th>Referencia</th>
                                    <th class="text-right">Debe</th>
                                    <th class="text-right">Haber</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $tD=0;$tH=0; foreach($det as $d): $tD+=(float)$d['debe']; $tH+=(float)$d['haber']; ?>
                                <tr>
                                    <td><?php echo h($d['codigo']); ?></td>
                                    <td><?php echo h($d['nombre']); ?></td>
                                    <td><?php echo h($d['referencia_linea'] ?? $d['descripcion_linea'] ?? ''); ?></td>
                                    <td class="text-right"><?php echo number_format((float)$d['debe'],2); ?></td>
                                    <td class="text-right"><?php echo number_format((float)$d['haber'],2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">Total</th>
                                    <th class="text-right"><?php echo number_format($tD,2); ?></th>
                                    <th class="text-right"><?php echo number_format($tH,2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <a class="btn btn-default" target="_blank"
                        href="conta-diario.php?print=<?php echo (int)$cab['id']; ?>">
                        <i class="fa fa-print"></i> Imprimir con formato
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Modal Import -->
    <div class="modal fade" id="ModalImport" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-upload"></i> Importar Comprobantes</h4>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted" style="margin-top:0;">
                            Formato: 1 fila = 1 línea del comprobante (DEBE/HABER). Puede ser <strong>CSV</strong> o
                            <strong>XLSX</strong>.
                            Descarga la plantilla desde el botón <strong>Plantilla</strong>.
                        </p>
                        <div class="form-group">
                            <label>Archivo (CSV / XLSX)</label>
                            <input type="file" name="archivo" class="form-control" accept=".csv,.xlsx" required>
                        </div>
                        <div class="alert alert-info" style="margin-bottom:0;">
                            Reglas:
                            <ul style="margin:8px 0 0;">
                                <li>La cuenta se identifica por <strong>CUENTA_CODIGO</strong> (debe existir y estar
                                    habilitada).</li>
                                <li>El comprobante se agrupa por <strong>FECHA + TIPO + NRO_DOC</strong>.</li>
                                <li>Debe = Haber para guardar.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button class="btn btn-success" name="ImportarDiario" value="1">
                            <i class="fa fa-upload"></i> Importar
                        </button>
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
    $(function() {
        $('#tbl').dataTable({
            "order": [
                [0, "desc"]
            ]
        });
    });
    </script>
</body>

</html>