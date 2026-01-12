<?php
class Contabilidad extends Conexion {

    private $colsCache = [];

    private function db() {
        return $this->Conectar(); // mysqli
    }

    private function hasCol($table, $col) {
        $key = $table . ':' . $col;
        if (isset($this->colsCache[$key])) return $this->colsCache[$key];

        $db = $this->db();
        $ok = false;
        $res = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
        if ($res && $res->num_rows > 0) $ok = true;

        $this->colsCache[$key] = $ok;
        return $ok;
    }

    private function esc($val) {
        $db = $this->db();
        if (is_null($val)) return null;
        $val = (string)$val;
        if (method_exists($db, 'real_escape_string')) return $db->real_escape_string($val);
        return addslashes($val);
    }

    private function money($v) {
        $s = trim((string)$v);
        $s = str_replace([" ", "\t"], "", $s);

        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $lastComma = strrpos($s, ',');
            $lastDot   = strrpos($s, '.');
            if ($lastComma > $lastDot) { // decimal = ,
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else { // decimal = .
                $s = str_replace(',', '', $s);
            }
        } else {
            if (strpos($s, ',') !== false) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        }
        return (float)$s;
    }

    private function txBegin() {
        $db = $this->db();
        if (method_exists($db, 'begin_transaction')) $db->begin_transaction();
        else $db->query("START TRANSACTION");
    }
    private function txCommit() {
        $db = $this->db();
        if (method_exists($db, 'commit')) $db->commit();
        else $db->query("COMMIT");
    }
    private function txRollback() {
        $db = $this->db();
        if (method_exists($db, 'rollback')) $db->rollback();
        else $db->query("ROLLBACK");
    }

    private function whereHabilitado($alias = 'a') {
        // si no existe columna, no filtra
        if ($this->hasCol('contabilidad_diario', 'habilitado')) {
            return " AND {$alias}.habilitado=1 ";
        }
        return "";
    }

    private function saldoPorTipo($tipo, $debe, $haber) {
        $tipo = strtoupper((string)$tipo);
        $debe = (float)$debe; $haber = (float)$haber;

        // Convención típica:
        // Activo/Gasto: saldo = Debe - Haber
        // Pasivo/Patrimonio/Ingreso: saldo = Haber - Debe
        if ($tipo === 'ACTIVO' || $tipo === 'GASTO') return $debe - $haber;
        return $haber - $debe;
    }

    /* ==========================
       PLAN DE CUENTAS
    ========================== */

    public function ListarCuentas($soloHabilitadas = false) {
        $db = $this->db();
        $where = $soloHabilitadas ? "WHERE habilitado=1" : "";
        $res = $db->query("SELECT * FROM contabilidad_cuentas {$where} ORDER BY codigo ASC");
        $data = [];
        if ($res) while ($r = $res->fetch_assoc()) $data[] = $r;
        return $data;
    }

    public function ObtenerCuenta($id) {
        $db = $this->db();
        $id = (int)$id;
        $res = $db->query("SELECT * FROM contabilidad_cuentas WHERE id={$id} LIMIT 1");
        return $res ? $res->fetch_assoc() : null;
    }

    public function ObtenerCuentaPorCodigo($codigo) {
        $db = $this->db();
        $codigo = $this->esc(trim((string)$codigo));
        $res = $db->query("SELECT * FROM contabilidad_cuentas WHERE codigo='{$codigo}' LIMIT 1");
        return $res ? $res->fetch_assoc() : null;
    }

    public function GuardarCuenta($id, $codigo, $nombre, $tipo, $nivel, $padre_id, $habilitado=1, $mayor_codigo=null, $permite_movimiento=1) {
        $db = $this->db();

        $id = (int)$id;
        $nivel = (int)$nivel;
        $padre_id = ($padre_id === '' || $padre_id === null) ? null : (int)$padre_id;
        $habilitado = (int)$habilitado;
        $permite_movimiento = (int)$permite_movimiento;

        $codigo = $this->esc($codigo);
        $nombre = $this->esc($nombre);
        $tipo   = $this->esc($tipo);
        $mayor  = is_null($mayor_codigo) || $mayor_codigo==='' ? null : $this->esc($mayor_codigo);

        $padreSQL = is_null($padre_id) ? "NULL" : $padre_id;
        $mayorSQL = is_null($mayor) ? "NULL" : "'{$mayor}'";

        // Si tu tabla aún no tiene mayor_codigo/permite_movimiento, no romper:
        $setMayor = $this->hasCol('contabilidad_cuentas','mayor_codigo') ? ", mayor_codigo={$mayorSQL}" : "";
        $setPerm  = $this->hasCol('contabilidad_cuentas','permite_movimiento') ? ", permite_movimiento={$permite_movimiento}" : "";

        if ($id > 0) {
            $sql = "UPDATE contabilidad_cuentas
                    SET codigo='{$codigo}', nombre='{$nombre}', tipo='{$tipo}', nivel={$nivel},
                        padre_id={$padreSQL}
                        {$setMayor}
                        {$setPerm},
                        habilitado={$habilitado}
                    WHERE id={$id} LIMIT 1";
            return $db->query($sql);
        } else {
            // Construir columnas dinámicas
            $cols = "codigo,nombre,tipo,nivel,padre_id,habilitado";
            $vals = "'{$codigo}','{$nombre}','{$tipo}',{$nivel},{$padreSQL},{$habilitado}";

            if ($this->hasCol('contabilidad_cuentas','mayor_codigo')) {
                $cols .= ",mayor_codigo";
                $vals .= ",{$mayorSQL}";
            }
            if ($this->hasCol('contabilidad_cuentas','permite_movimiento')) {
                $cols .= ",permite_movimiento";
                $vals .= ",{$permite_movimiento}";
            }

            $sql = "INSERT INTO contabilidad_cuentas ({$cols}) VALUES ({$vals})";
            return $db->query($sql);
        }
    }

    public function ToggleCuenta($id) {
        $db = $this->db();
        $id = (int)$id;
        return $db->query("UPDATE contabilidad_cuentas SET habilitado = IF(habilitado=1,0,1) WHERE id={$id}");
    }

    public function KPI_Cuentas() {
        $db = $this->db();
        $sql = "SELECT
                    COUNT(*) total,
                    SUM(CASE WHEN habilitado=1 THEN 1 ELSE 0 END) habilitadas,
                    SUM(CASE WHEN habilitado=0 THEN 1 ELSE 0 END) deshabilitadas,
                    SUM(CASE WHEN tipo='ACTIVO' THEN 1 ELSE 0 END) activos,
                    SUM(CASE WHEN tipo='PASIVO' THEN 1 ELSE 0 END) pasivos,
                    SUM(CASE WHEN tipo='PATRIMONIO' THEN 1 ELSE 0 END) patrimonio,
                    SUM(CASE WHEN tipo='INGRESO' THEN 1 ELSE 0 END) ingresos,
                    SUM(CASE WHEN tipo='GASTO' THEN 1 ELSE 0 END) gastos
                FROM contabilidad_cuentas";
        $res = $db->query($sql);
        return $res ? $res->fetch_assoc() : [
            'total'=>0,'habilitadas'=>0,'deshabilitadas'=>0,'activos'=>0,'pasivos'=>0,'patrimonio'=>0,'ingresos'=>0,'gastos'=>0
        ];
    }

    /* ==========================
       EXPORT/IMPORT PLAN DE CUENTAS (CSV Excel-friendly)
       Columnas esperadas:
       CUENTA | NOMBRE DE CUENTA | NIVEL | MAYOR | TIPO | (opcional) MOVIMIENTO
    ========================== */

    public function ExportarPlanCuentasCSV($soloHabilitadas = true) {
        $db = $this->db();
        $where = $soloHabilitadas ? "WHERE habilitado=1" : "";
        $res = $db->query("SELECT * FROM contabilidad_cuentas {$where} ORDER BY codigo ASC");

        $out = fopen('php://temp', 'w+');

        // BOM para Excel
        fwrite($out, "\xEF\xBB\xBF");

        $headers = ['CUENTA','NOMBRE DE CUENTA','NIVEL','MAYOR','TIPO','MOVIMIENTO','HABILITADO'];
        fputcsv($out, $headers);

        $hasMayor = $this->hasCol('contabilidad_cuentas','mayor_codigo');
        $hasPerm  = $this->hasCol('contabilidad_cuentas','permite_movimiento');

        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $mov = $hasPerm ? (int)$r['permite_movimiento'] : 1;
                $may = $hasMayor ? ($r['mayor_codigo'] ?? '') : '';
                fputcsv($out, [
                    $r['codigo'] ?? '',
                    $r['nombre'] ?? '',
                    (int)($r['nivel'] ?? 1),
                    $may,
                    $r['tipo'] ?? '',
                    $mov,
                    (int)($r['habilitado'] ?? 1)
                ]);
            }
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    public function ImportarPlanCuentasCSV($tmpFile) {
        $db = $this->db();

        $stats = [
            'insertados'=>0,
            'actualizados'=>0,
            'errores'=>[]
        ];

        $content = file_get_contents($tmpFile);
        if ($content === false || trim($content)==='') {
            $stats['errores'][] = "Archivo vacío.";
            return ['ok'=>false, 'stats'=>$stats];
        }

        // detectar delimitador
        $firstLine = strtok($content, "\n");
        $delim = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $fh = fopen($tmpFile, 'r');
        if (!$fh) {
            $stats['errores'][] = "No se pudo abrir el archivo.";
            return ['ok'=>false, 'stats'=>$stats];
        }

        // leer header
        $header = fgetcsv($fh, 0, $delim);
        if (!$header) {
            fclose($fh);
            $stats['errores'][] = "CSV sin cabecera.";
            return ['ok'=>false, 'stats'=>$stats];
        }

        // limpiar BOM
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        $map = [];
        foreach ($header as $i=>$h) {
            $k = strtoupper(trim((string)$h));
            $map[$k] = $i;
        }

        $idxCuenta = $map['CUENTA'] ?? null;
        $idxNombre = $map['NOMBRE DE CUENTA'] ?? null;
        $idxNivel  = $map['NIVEL'] ?? null;
        $idxMayor  = $map['MAYOR'] ?? null;
        $idxTipo   = $map['TIPO'] ?? null;
        $idxMov    = $map['MOVIMIENTO'] ?? null;
        $idxHab    = $map['HABILITADO'] ?? null;

        if ($idxCuenta===null || $idxNombre===null || $idxTipo===null) {
            fclose($fh);
            $stats['errores'][] = "Faltan columnas obligatorias: CUENTA, NOMBRE DE CUENTA, TIPO.";
            return ['ok'=>false, 'stats'=>$stats];
        }

        $hasMayor = $this->hasCol('contabilidad_cuentas','mayor_codigo');
        $hasPerm  = $this->hasCol('contabilidad_cuentas','permite_movimiento');

        $pendientesMayor = []; // codigo => mayor_codigo

        $this->txBegin();
        try {
            while (($row = fgetcsv($fh, 0, $delim)) !== false) {
                $codigo = trim((string)($row[$idxCuenta] ?? ''));
                $nombre = trim((string)($row[$idxNombre] ?? ''));
                $tipo   = strtoupper(trim((string)($row[$idxTipo] ?? '')));

                if ($codigo==='' || $nombre==='' || $tipo==='') continue;

                $nivel = ($idxNivel!==null) ? (int)$row[$idxNivel] : 1;
                if ($nivel<=0) $nivel = 1;

                $mayor = ($idxMayor!==null) ? trim((string)$row[$idxMayor]) : '';
                $mov   = ($idxMov!==null) ? (int)$row[$idxMov] : 1;
                $hab   = ($idxHab!==null) ? (int)$row[$idxHab] : 1;

                if (!in_array($tipo, ['ACTIVO','PASIVO','PATRIMONIO','INGRESO','GASTO'], true)) {
                    $stats['errores'][] = "Tipo inválido en {$codigo}: {$tipo}";
                    continue;
                }

                $ex = $this->ObtenerCuentaPorCodigo($codigo);
                if ($ex) {
                    $ok = $this->GuardarCuenta((int)$ex['id'], $codigo, $nombre, $tipo, $nivel, $ex['padre_id'] ?? null, $hab, ($hasMayor?$mayor:null), ($hasPerm?$mov:1));
                    if ($ok) $stats['actualizados']++;
                } else {
                    $ok = $this->GuardarCuenta(0, $codigo, $nombre, $tipo, $nivel, null, $hab, ($hasMayor?$mayor:null), ($hasPerm?$mov:1));
                    if ($ok) $stats['insertados']++;
                }

                if ($hasMayor && $mayor!=='') {
                    $pendientesMayor[$codigo] = $mayor;
                }
            }

            // Segunda pasada: enlazar padre por MAYOR
            if (!empty($pendientesMayor)) {
                foreach ($pendientesMayor as $cod=>$may) {
                    $hijo = $this->ObtenerCuentaPorCodigo($cod);
                    $padre = $this->ObtenerCuentaPorCodigo($may);
                    if ($hijo && $padre) {
                        $idH = (int)$hijo['id'];
                        $idP = (int)$padre['id'];
                        $db->query("UPDATE contabilidad_cuentas SET padre_id={$idP} WHERE id={$idH} LIMIT 1");
                    }
                }
            }

            $this->txCommit();
            fclose($fh);
            return ['ok'=>true, 'stats'=>$stats];

        } catch (Exception $e) {
            $this->txRollback();
            fclose($fh);
            $stats['errores'][] = $e->getMessage();
            return ['ok'=>false, 'stats'=>$stats];
        }
    }

    /* ==========================
       ASIENTOS / COMPROBANTES
    ========================== */

    public function RegistrarAsiento($cabecera, $lineas) {
        $db = $this->db();

        if (!is_array($lineas) || count($lineas) < 2) {
            return ["ok"=>false, "msg"=>"El comprobante requiere al menos 2 líneas."];
        }

        $sumDebe = 0; $sumHaber = 0;
        foreach ($lineas as $ln) {
            $sumDebe  += (float)($ln['debe'] ?? 0);
            $sumHaber += (float)($ln['haber'] ?? 0);
        }
        $diff = round($sumDebe - $sumHaber, 2);
        if ($diff != 0.00) {
            return ["ok"=>false, "msg"=>"Comprobante descuadrado. Diferencia: {$diff}"];
        }

        $fecha      = $cabecera['fecha'] ?? date('Y-m-d');
        $creado_por = (int)($cabecera['creado_por'] ?? 0);

        $tipo = $cabecera['tipo_comprobante'] ?? 'INGRESO';
        $nro  = $cabecera['nro_comprobante'] ?? null;
        $razon= $cabecera['razon_social'] ?? null;
        $glosa= $cabecera['glosa'] ?? null;
        $cheq = $cabecera['cheque_nro'] ?? null;
        $proy = $cabecera['proyecto'] ?? null;
        $ref  = $cabecera['referencia'] ?? null;
        $tc   = $cabecera['tipo_cambio'] ?? null;
        $mon  = $cabecera['moneda'] ?? null;

        $this->txBegin();

        try {
            $cols = ["fecha","creado_por"];
            $vals = [
                "'".$this->esc($fecha)."'",
                $creado_por
            ];

            if ($this->hasCol('contabilidad_diario','tipo_comprobante')) { $cols[]="tipo_comprobante"; $vals[]="'".$this->esc($tipo)."'"; }
            if ($this->hasCol('contabilidad_diario','nro_comprobante'))  { $cols[]="nro_comprobante";  $vals[]= is_null($nro) ? "NULL" : "'".$this->esc($nro)."'"; }
            if ($this->hasCol('contabilidad_diario','razon_social'))     { $cols[]="razon_social";     $vals[]= is_null($razon) ? "NULL" : "'".$this->esc($razon)."'"; }
            if ($this->hasCol('contabilidad_diario','glosa'))            { $cols[]="glosa";            $vals[]= is_null($glosa) ? "NULL" : "'".$this->esc($glosa)."'"; }
            if ($this->hasCol('contabilidad_diario','cheque_nro'))       { $cols[]="cheque_nro";       $vals[]= is_null($cheq) ? "NULL" : "'".$this->esc($cheq)."'"; }
            if ($this->hasCol('contabilidad_diario','proyecto'))         { $cols[]="proyecto";         $vals[]= is_null($proy) ? "NULL" : "'".$this->esc($proy)."'"; }
            if ($this->hasCol('contabilidad_diario','referencia'))       { $cols[]="referencia";       $vals[]= is_null($ref) ? "NULL" : "'".$this->esc($ref)."'"; }
            if ($this->hasCol('contabilidad_diario','tipo_cambio'))      { $cols[]="tipo_cambio";      $vals[]= is_null($tc) ? "NULL" : number_format((float)$tc,4,'.',''); }
            if ($this->hasCol('contabilidad_diario','moneda'))           { $cols[]="moneda";           $vals[]= is_null($mon) ? "NULL" : "'".$this->esc($mon)."'"; }
            if ($this->hasCol('contabilidad_diario','habilitado'))       { $cols[]="habilitado";       $vals[]= "1"; }

            $sqlCab = "INSERT INTO contabilidad_diario (".implode(",",$cols).") VALUES (".implode(",",$vals).")";
            if (!$db->query($sqlCab)) throw new Exception("Error cabecera: ".$db->error);

            $idAsiento = (int)$db->insert_id;

            if ($this->hasCol('contabilidad_diario','nro_comprobante') && empty($nro)) {
                $pref = ($tipo==='EGRESO'?'E':($tipo==='TRASPASO'?'T':'I'));
                $nroGen = $pref."-".str_pad((string)$idAsiento, 6, "0", STR_PAD_LEFT);
                $db->query("UPDATE contabilidad_diario SET nro_comprobante='".$this->esc($nroGen)."' WHERE id={$idAsiento}");
            }

            $hasDesc = $this->hasCol('contabilidad_diario_detalle','descripcion_linea');
            $hasRefL = $this->hasCol('contabilidad_diario_detalle','referencia_linea');
            $hasOrd  = $this->hasCol('contabilidad_diario_detalle','orden');

            $orden = 1;
            foreach ($lineas as $ln) {
                $idCuenta = (int)$ln['id_cuenta'];
                $debe  = (float)$ln['debe'];
                $haber = (float)$ln['haber'];

                $colsD = ["id_diario","id_cuenta","debe","haber"];
                $valsD = [$idAsiento,$idCuenta, number_format($debe,2,'.',''), number_format($haber,2,'.','')];

                if ($hasOrd) { $colsD[]="orden"; $valsD[]=$orden; }

                if ($hasDesc) { $colsD[]="descripcion_linea"; $valsD[]= empty($ln['descripcion_linea']) ? "NULL" : "'".$this->esc($ln['descripcion_linea'])."'"; }
                if ($hasRefL) { $colsD[]="referencia_linea";  $valsD[]= empty($ln['referencia_linea'])  ? "NULL" : "'".$this->esc($ln['referencia_linea'])."'"; }

                $sqlDet = "INSERT INTO contabilidad_diario_detalle (".implode(",",$colsD).") VALUES (".implode(",",$valsD).")";
                if (!$db->query($sqlDet)) throw new Exception("Error detalle: ".$db->error);

                $orden++;
            }

            if ($this->hasCol('contabilidad_diario','total_debe')) {
                $db->query("UPDATE contabilidad_diario SET total_debe=".number_format($sumDebe,2,'.','').", total_haber=".number_format($sumHaber,2,'.','')." WHERE id={$idAsiento}");
            }

            $this->txCommit();
            return ["ok"=>true, "id_asiento"=>$idAsiento];

        } catch (Exception $e) {
            $this->txRollback();
            return ["ok"=>false, "msg"=>$e->getMessage()];
        }
    }

    public function ObtenerAsiento($id) {
        $db = $this->db();
        $id = (int)$id;

        $cab = null;
        $res = $db->query("SELECT * FROM contabilidad_diario WHERE id={$id} LIMIT 1");
        if ($res) $cab = $res->fetch_assoc();

        $det = [];
        $order = $this->hasCol('contabilidad_diario_detalle','orden') ? "d.orden ASC," : "";
        $sql = "SELECT d.*, c.codigo, c.nombre, c.tipo
                FROM contabilidad_diario_detalle d
                INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
                WHERE d.id_diario={$id}
                ORDER BY {$order} c.codigo ASC";
        $res2 = $db->query($sql);
        if ($res2) while ($r = $res2->fetch_assoc()) $det[] = $r;

        return ["cabecera"=>$cab, "detalle"=>$det];
    }

    public function ListarDiario($desde, $hasta, $tipo=null, $incluyeAnulados=false) {
        $db = $this->db();
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $where = "WHERE a.fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."'";
        if ($tipo && $this->hasCol('contabilidad_diario','tipo_comprobante')) {
            $where .= " AND a.tipo_comprobante='".$this->esc($tipo)."'";
        }
        if (!$incluyeAnulados && $this->hasCol('contabilidad_diario','habilitado')) {
            $where .= " AND a.habilitado=1";
        }

        if ($this->hasCol('contabilidad_diario','total_debe')) {
            $sql = "SELECT a.* FROM contabilidad_diario a {$where} ORDER BY a.fecha DESC, a.id DESC";
        } else {
            $sql = "SELECT a.*,
                           SUM(d.debe) AS total_debe,
                           SUM(d.haber) AS total_haber
                    FROM contabilidad_diario a
                    LEFT JOIN contabilidad_diario_detalle d ON d.id_diario=a.id
                    {$where}
                    GROUP BY a.id
                    ORDER BY a.fecha DESC, a.id DESC";
        }

        $res = $db->query($sql);
        $data = [];
        if ($res) while ($r = $res->fetch_assoc()) $data[] = $r;
        return $data;
    }

    public function BalanceComprobacion($desde, $hasta) {
        $db = $this->db();
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $whereHab = $this->whereHabilitado('a');

        $sql = "
            SELECT c.id, c.codigo, c.nombre, c.tipo, c.nivel,
                   SUM(d.debe) AS debe, SUM(d.haber) AS haber
            FROM contabilidad_diario_detalle d
            INNER JOIN contabilidad_diario a ON a.id=d.id_diario
            INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
            WHERE a.fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."'
            {$whereHab}
            GROUP BY c.id
            ORDER BY c.codigo ASC
        ";
        $res = $db->query($sql);
        $data = [];
        if ($res) while ($r = $res->fetch_assoc()) $data[] = $r;
        return $data;
    }

    public function AnularAsiento($id, $comentario='Anulado', $usuarioId=0) {
        $db = $this->db();
        $id = (int)$id;
        $usuarioId = (int)$usuarioId;

        if (!$this->hasCol('contabilidad_diario','habilitado')) {
            return ["ok"=>false, "msg"=>"La tabla contabilidad_diario no tiene columna habilitado."];
        }

        $mot = $this->esc($comentario);
        $set = "habilitado=0";
        if ($this->hasCol('contabilidad_diario','anulado_motivo')) $set .= ", anulado_motivo='{$mot}'";
        if ($this->hasCol('contabilidad_diario','anulado_en'))     $set .= ", anulado_en=NOW()";
        if ($this->hasCol('contabilidad_diario','anulado_por'))    $set .= ", anulado_por={$usuarioId}";

        $ok = $db->query("UPDATE contabilidad_diario SET {$set} WHERE id={$id} LIMIT 1");
        return $ok ? ["ok"=>true] : ["ok"=>false, "msg"=>$db->error];
    }

    /* ==========================
       REPORTES / ESTADOS FINANCIEROS
    ========================== */

    public function KPI_Reportes($desde, $hasta) {
        $db = $this->db();
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $whereHab = $this->whereHabilitado('a');

        $k = [
            'comprobantes'=>0,
            'total_debe'=>0,
            'total_haber'=>0,
            'ingresos'=>0,
            'gastos'=>0,
            'utilidad'=>0
        ];

        // comprobantes
        $sqlC = "SELECT COUNT(*) c FROM contabilidad_diario a WHERE a.fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."' {$whereHab}";
        $rC = $db->query($sqlC);
        if ($rC) { $row = $rC->fetch_assoc(); $k['comprobantes'] = (int)$row['c']; }

        // sumas globales
        $sqlS = "
            SELECT
              SUM(d.debe) debe,
              SUM(d.haber) haber,
              SUM(CASE WHEN c.tipo='INGRESO' THEN (d.haber - d.debe) ELSE 0 END) ingresos,
              SUM(CASE WHEN c.tipo='GASTO'   THEN (d.debe - d.haber) ELSE 0 END) gastos
            FROM contabilidad_diario_detalle d
            INNER JOIN contabilidad_diario a ON a.id=d.id_diario
            INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
            WHERE a.fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."'
            {$whereHab}
        ";
        $rS = $db->query($sqlS);
        if ($rS) {
            $row = $rS->fetch_assoc();
            $k['total_debe']  = (float)($row['debe'] ?? 0);
            $k['total_haber'] = (float)($row['haber'] ?? 0);
            $k['ingresos']    = (float)($row['ingresos'] ?? 0);
            $k['gastos']      = (float)($row['gastos'] ?? 0);
            $k['utilidad']    = (float)$k['ingresos'] - (float)$k['gastos'];
        }

        return $k;
    }

    public function SerieMensualIngresosGastos($desde, $hasta) {
        $db = $this->db();
        $desde = $desde ?: date('Y-01-01');
        $hasta = $hasta ?: date('Y-m-d');

        $whereHab = $this->whereHabilitado('a');

        $sql = "
            SELECT DATE_FORMAT(a.fecha,'%Y-%m') ym,
                   SUM(CASE WHEN c.tipo='INGRESO' THEN (d.haber - d.debe) ELSE 0 END) ingresos,
                   SUM(CASE WHEN c.tipo='GASTO'   THEN (d.debe - d.haber) ELSE 0 END) gastos
            FROM contabilidad_diario_detalle d
            INNER JOIN contabilidad_diario a ON a.id=d.id_diario
            INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
            WHERE a.fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."'
            {$whereHab}
            GROUP BY ym
            ORDER BY ym ASC
        ";
        $res = $db->query($sql);
        $rows = [];
        if ($res) while($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    public function TopCuentasMovimiento($desde, $hasta, $limit=10) {
        $db = $this->db();
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');
        $limit = (int)$limit;

        $whereHab = $this->whereHabilitado('a');

        $sql = "
            SELECT c.codigo, c.nombre, c.tipo,
                   SUM(d.debe) debe,
                   SUM(d.haber) haber,
                   SUM(d.debe + d.haber) movimiento
            FROM contabilidad_diario_detalle d
            INNER JOIN contabilidad_diario a ON a.id=d.id_diario
            INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
            WHERE a.fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."'
            {$whereHab}
            GROUP BY c.id
            ORDER BY movimiento DESC
            LIMIT {$limit}
        ";
        $res = $db->query($sql);
        $rows = [];
        if ($res) while($r=$res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    public function BalanceComprobacionDetallado($desde, $hasta) {
        $rows = $this->BalanceComprobacion($desde, $hasta);
        foreach ($rows as &$r) {
            $saldo = $this->saldoPorTipo($r['tipo'], $r['debe'], $r['haber']);
            $r['saldo'] = $saldo;
        }
        return $rows;
    }

    public function EstadoResultadosData($desde, $hasta) {
        $rows = $this->BalanceComprobacionDetallado($desde, $hasta);

        $ing = [];
        $gas = [];
        $tIng = 0; $tGas = 0;

        foreach ($rows as $r) {
            if ($r['tipo']==='INGRESO') { $ing[] = $r; $tIng += (float)$r['saldo']; }
            if ($r['tipo']==='GASTO')   { $gas[] = $r; $tGas += (float)$r['saldo']; }
        }

        // Para ingresos el saldo ya viene positivo (H-D). Para gastos positivo (D-H).
        $util = $tIng - $tGas;

        return [
            'ingresos'=>$ing,
            'gastos'=>$gas,
            'total_ingresos'=>$tIng,
            'total_gastos'=>$tGas,
            'utilidad'=>$util
        ];
    }

    public function BalanceGeneralData($desde, $hasta) {
        $rows = $this->BalanceComprobacionDetallado($desde, $hasta);

        $act = []; $pas = []; $pat = [];
        $tA = 0; $tP = 0; $tPa = 0;

        foreach ($rows as $r) {
            if ($r['tipo']==='ACTIVO') { $act[]=$r; $tA += (float)$r['saldo']; }
            if ($r['tipo']==='PASIVO') { $pas[]=$r; $tP += (float)$r['saldo']; }
            if ($r['tipo']==='PATRIMONIO') { $pat[]=$r; $tPa += (float)$r['saldo']; }
        }

        return [
            'activos'=>$act,
            'pasivos'=>$pas,
            'patrimonio'=>$pat,
            'total_activo'=>$tA,
            'total_pasivo'=>$tP,
            'total_patrimonio'=>$tPa
        ];
    }
}