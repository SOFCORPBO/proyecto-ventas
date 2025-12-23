<?php
class Contabilidad extends Conexion {

    private $colsCache = [];

    private function db() {
        return $this->Conectar(); // en tu sistema es mysqli (por cómo usas query/num_rows)
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
        // soporta coma decimal
        $s = trim((string)$v);
        $s = str_replace([' ', "\t"], '', $s);
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
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

    public function GuardarCuenta($id, $codigo, $nombre, $tipo, $nivel, $padre_id, $habilitado=1) {
        $db = $this->db();

        $id = (int)$id;
        $nivel = (int)$nivel;
        $padre_id = ($padre_id === '' || $padre_id === null) ? null : (int)$padre_id;
        $habilitado = (int)$habilitado;

        $codigo = $this->esc($codigo);
        $nombre = $this->esc($nombre);
        $tipo   = $this->esc($tipo);

        if ($id > 0) {
            $padreSQL = is_null($padre_id) ? "NULL" : $padre_id;
            $sql = "UPDATE contabilidad_cuentas
                    SET codigo='{$codigo}', nombre='{$nombre}', tipo='{$tipo}', nivel={$nivel}, padre_id={$padreSQL}, habilitado={$habilitado}
                    WHERE id={$id}";
            return $db->query($sql);
        } else {
            $padreSQL = is_null($padre_id) ? "NULL" : $padre_id;
            $sql = "INSERT INTO contabilidad_cuentas (codigo,nombre,tipo,nivel,padre_id,habilitado)
                    VALUES ('{$codigo}','{$nombre}','{$tipo}',{$nivel},{$padreSQL},{$habilitado})";
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

        $fecha       = $cabecera['fecha'] ?? date('Y-m-d');
        $descripcion = $cabecera['descripcion'] ?? '';
        $referencia  = $cabecera['referencia'] ?? null;
        $creado_por  = (int)($cabecera['creado_por'] ?? 0);

        $tipo = $cabecera['tipo_comprobante'] ?? 'INGRESO';
        $nro  = $cabecera['nro_comprobante'] ?? null;
        $razon= $cabecera['razon_social'] ?? null;
        $glosa= $cabecera['glosa'] ?? null;
        $cheq = $cabecera['cheque_nro'] ?? null;
        $proy = $cabecera['proyecto'] ?? null;

        $this->txBegin();

        try {
            // Insert cabecera (según columnas existentes)
            $cols = ["fecha","descripcion","referencia","creado_por"];
            $vals = [
                "'".$this->esc($fecha)."'",
                "'".$this->esc($descripcion)."'",
                is_null($referencia) ? "NULL" : "'".$this->esc($referencia)."'",
                $creado_por
            ];

            if ($this->hasCol('contabilidad_diario','tipo_comprobante')) {
                $cols[] = "tipo_comprobante";
                $vals[] = "'".$this->esc($tipo)."'";
            }
            if ($this->hasCol('contabilidad_diario','nro_comprobante')) {
                $cols[] = "nro_comprobante";
                $vals[] = is_null($nro) ? "NULL" : "'".$this->esc($nro)."'";
            }
            if ($this->hasCol('contabilidad_diario','razon_social')) {
                $cols[] = "razon_social";
                $vals[] = is_null($razon) ? "NULL" : "'".$this->esc($razon)."'";
            }
            if ($this->hasCol('contabilidad_diario','glosa')) {
                $cols[] = "glosa";
                $vals[] = is_null($glosa) ? "NULL" : "'".$this->esc($glosa)."'";
            }
            if ($this->hasCol('contabilidad_diario','cheque_nro')) {
                $cols[] = "cheque_nro";
                $vals[] = is_null($cheq) ? "NULL" : "'".$this->esc($cheq)."'";
            }
            if ($this->hasCol('contabilidad_diario','proyecto')) {
                $cols[] = "proyecto";
                $vals[] = is_null($proy) ? "NULL" : "'".$this->esc($proy)."'";
            }

            $sqlCab = "INSERT INTO contabilidad_diario (".implode(",",$cols).") VALUES (".implode(",",$vals).")";
            if (!$db->query($sqlCab)) throw new Exception("Error cabecera: ".$db->error);

            $idAsiento = (int)$db->insert_id;

            // Autogenerar nro comprobante si la columna existe y no vino
            if ($this->hasCol('contabilidad_diario','nro_comprobante') && empty($nro)) {
                $nroGen = "I-".str_pad((string)$idAsiento, 6, "0", STR_PAD_LEFT);
                $db->query("UPDATE contabilidad_diario SET nro_comprobante='".$this->esc($nroGen)."' WHERE id={$idAsiento}");
            }

            // Insert detalle
            $hasDesc = $this->hasCol('contabilidad_diario_detalle','descripcion_linea');
            $hasRefL = $this->hasCol('contabilidad_diario_detalle','referencia_linea');

            foreach ($lineas as $ln) {
                $idCuenta = (int)$ln['id_cuenta'];
                $debe  = (float)$ln['debe'];
                $haber = (float)$ln['haber'];

                $colsD = ["id_diario","id_cuenta","debe","haber"];
                $valsD = [$idAsiento,$idCuenta, number_format($debe,2,'.',''), number_format($haber,2,'.','')];

                if ($hasDesc) {
                    $colsD[] = "descripcion_linea";
                    $valsD[] = is_null($ln['descripcion_linea']) ? "NULL" : "'".$this->esc($ln['descripcion_linea'])."'";
                }
                if ($hasRefL) {
                    $colsD[] = "referencia_linea";
                    $valsD[] = is_null($ln['referencia_linea']) ? "NULL" : "'".$this->esc($ln['referencia_linea'])."'";
                }

                $sqlDet = "INSERT INTO contabilidad_diario_detalle (".implode(",",$colsD).") VALUES (".implode(",",$valsD).")";
                if (!$db->query($sqlDet)) throw new Exception("Error detalle: ".$db->error);
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
                ORDER BY c.codigo ASC";
        $res2 = $db->query($sql);
        if ($res2) while ($r = $res2->fetch_assoc()) $det[] = $r;

        return ["cabecera"=>$cab, "detalle"=>$det];
    }

    public function ListarDiario($desde, $hasta, $tipo=null) {
        $db = $this->db();
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $where = "WHERE fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."'";

        if ($tipo && $this->hasCol('contabilidad_diario','tipo_comprobante')) {
            $where .= " AND tipo_comprobante='".$this->esc($tipo)."'";
        }

        $res = $db->query("SELECT * FROM contabilidad_diario {$where} ORDER BY fecha DESC, id DESC");
        $data = [];
        if ($res) while ($r = $res->fetch_assoc()) $data[] = $r;
        return $data;
    }

    public function MayorPorCuenta($idCuenta, $desde, $hasta) {
        $db = $this->db();
        $idCuenta = (int)$idCuenta;
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $res = $db->query("
            SELECT a.*, d.debe, d.haber, c.codigo, c.nombre, c.tipo,
                   d.descripcion_linea, d.referencia_linea
            FROM contabilidad_diario_detalle d
            INNER JOIN contabilidad_diario a ON a.id=d.id_diario
            INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
            WHERE d.id_cuenta={$idCuenta}
              AND a.fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."'
            ORDER BY a.fecha ASC, a.id ASC
        ");
        $data = [];
        if ($res) while ($r = $res->fetch_assoc()) $data[] = $r;
        return $data;
    }

    public function BalanceComprobacion($desde, $hasta) {
        $db = $this->db();
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $res = $db->query("
            SELECT c.id, c.codigo, c.nombre, c.tipo,
                   SUM(d.debe) AS debe, SUM(d.haber) AS haber
            FROM contabilidad_diario_detalle d
            INNER JOIN contabilidad_diario a ON a.id=d.id_diario
            INNER JOIN contabilidad_cuentas c ON c.id=d.id_cuenta
            WHERE a.fecha BETWEEN '".$this->esc($desde)."' AND '".$this->esc($hasta)."'
            GROUP BY c.id
            ORDER BY c.codigo ASC
        ");
        $data = [];
        if ($res) while ($r = $res->fetch_assoc()) $data[] = $r;
        return $data;
    }
}
