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
        // soporta 3.030,00 o 3,030.00
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            // si hay ambos, asume que el último separador es decimal
            $lastComma = strrpos($s, ',');
            $lastDot   = strrpos($s, '.');
            if ($lastComma > $lastDot) { // decimal = ,
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else { // decimal = .
                $s = str_replace(',', '', $s);
            }
        } else {
            // si solo hay coma: decimal coma
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

        if ($id > 0) {
            $sql = "UPDATE contabilidad_cuentas
                    SET codigo='{$codigo}', nombre='{$nombre}', tipo='{$tipo}', nivel={$nivel},
                        mayor_codigo={$mayorSQL}, padre_id={$padreSQL},
                        permite_movimiento={$permite_movimiento}, habilitado={$habilitado}
                    WHERE id={$id} LIMIT 1";
            return $db->query($sql);
        } else {
            $sql = "INSERT INTO contabilidad_cuentas (codigo,nombre,tipo,nivel,mayor_codigo,padre_id,permite_movimiento,habilitado)
                    VALUES ('{$codigo}','{$nombre}','{$tipo}',{$nivel},{$mayorSQL},{$padreSQL},{$permite_movimiento},{$habilitado})";
            return $db->query($sql);
        }
    }

    public function ToggleCuenta($id) {
        $db = $this->db();
        $id = (int)$id;
        return $db->query("UPDATE contabilidad_cuentas SET habilitado = IF(habilitado=1,0,1) WHERE id={$id}");
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

            $sqlCab = "INSERT INTO contabilidad_diario (".implode(",",$cols).") VALUES (".implode(",",$vals).")";
            if (!$db->query($sqlCab)) throw new Exception("Error cabecera: ".$db->error);

            $idAsiento = (int)$db->insert_id;

            // Autogenerar nro comprobante si existe y no vino
            if ($this->hasCol('contabilidad_diario','nro_comprobante') && empty($nro)) {
                $pref = ($tipo==='EGRESO'?'E':($tipo==='TRASPASO'?'T':'I'));
                $nroGen = $pref."-".str_pad((string)$idAsiento, 6, "0", STR_PAD_LEFT);
                $db->query("UPDATE contabilidad_diario SET nro_comprobante='".$this->esc($nroGen)."' WHERE id={$idAsiento}");
            }

            $hasDesc = $this->hasCol('contabilidad_diario_detalle','descripcion_linea');
            $hasRefL = $this->hasCol('contabilidad_diario_detalle','referencia_linea');

            $orden = 1;
            foreach ($lineas as $ln) {
                $idCuenta = (int)$ln['id_cuenta'];
                $debe  = (float)$ln['debe'];
                $haber = (float)$ln['haber'];

                $colsD = ["id_diario","id_cuenta","debe","haber","orden"];
                $valsD = [$idAsiento,$idCuenta, number_format($debe,2,'.',''), number_format($haber,2,'.',''), $orden];

                if ($hasDesc) { $colsD[]="descripcion_linea"; $valsD[]= empty($ln['descripcion_linea']) ? "NULL" : "'".$this->esc($ln['descripcion_linea'])."'"; }
                if ($hasRefL) { $colsD[]="referencia_linea";  $valsD[]= empty($ln['referencia_linea'])  ? "NULL" : "'".$this->esc($ln['referencia_linea'])."'"; }

                $sqlDet = "INSERT INTO contabilidad_diario_detalle (".implode(",",$colsD).") VALUES (".implode(",",$valsD).")";
                if (!$db->query($sqlDet)) throw new Exception("Error detalle: ".$db->error);

                $orden++;
            }

            // Guardar totales si existen columnas
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
        $sql = "SELECT d.*, c.codigo, c.nombre, c.tipo
                FROM contabilidad_diario_detalle d
                INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
                WHERE d.id_diario={$id}
                ORDER BY d.orden ASC, c.codigo ASC";
        $res2 = $db->query($sql);
        if ($res2) while ($r = $res2->fetch_assoc()) $det[] = $r;

        return ["cabecera"=>$cab, "detalle"=>$det];
    }

    public function ListarDiario($desde, $hasta, $tipo=null, $incluyeAnulados=false) {
        $db = $this->db();
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $where = "WHERE a.fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."'";
        if ($tipo) $where .= " AND a.tipo_comprobante='".$this->esc($tipo)."'";
        if (!$incluyeAnulados) $where .= " AND a.habilitado=1";

        // totales: usa columnas si existen; si no, suma detalle
        $selTot = "a.*";
        if ($this->hasCol('contabilidad_diario','total_debe')) {
            $selTot .= ", a.total_debe, a.total_haber";
            $sql = "SELECT {$selTot}
                    FROM contabilidad_diario a
                    {$where}
                    ORDER BY a.fecha DESC, a.id DESC";
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

    
    public function AnularAsiento($id, $comentario='Anulado', $usuarioId=0) {
        $db = $this->db();
        $id = (int)$id;
        $usuarioId = (int)$usuarioId;

        $mot = $this->esc($comentario);

        $set = "habilitado=0";
        if ($this->hasCol('contabilidad_diario','anulado_motivo')) $set .= ", anulado_motivo='{$mot}'";
        if ($this->hasCol('contabilidad_diario','anulado_en'))     $set .= ", anulado_en=NOW()";
        if ($this->hasCol('contabilidad_diario','anulado_por'))    $set .= ", anulado_por={$usuarioId}";

        $ok = $db->query("UPDATE contabilidad_diario SET {$set} WHERE id={$id} LIMIT 1");
        return $ok ? ["ok"=>true] : ["ok"=>false, "msg"=>$db->error];
    }
}