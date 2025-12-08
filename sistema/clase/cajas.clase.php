<?php

class Cajas extends Conexion
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /* ============================================================
       📌 OBTENER SALDO ACTUAL — CAJA GENERAL
    ============================================================ */
    public function SaldoCajaGeneral()
    {
        $sql = $this->db->SQL("
            SELECT saldo_caja 
            FROM caja_general_movimientos
            ORDER BY id DESC LIMIT 1
        ");

        return ($sql->num_rows > 0)
            ? floatval($sql->fetch_assoc()['saldo_caja'])
            : 0;
    }

    /* ============================================================
       📌 OBTENER SALDO ACTUAL — CAJA CHICA
    ============================================================ */
    public function SaldoCajaChica()
    {
        $sql = $this->db->SQL("
            SELECT saldo_resultante 
            FROM caja_chica_movimientos
            ORDER BY id DESC LIMIT 1
        ");

        return ($sql->num_rows > 0)
            ? floatval($sql->fetch_assoc()['saldo_resultante'])
            : 0;
    }


   /**
     * ============================================================
     *  📌 Registrar movimiento en CAJA GENERAL
     *  Funciona para egresos automáticos (pagos a proveedores)
     * ============================================================
     */
    public function CajaGeneralMovimiento($tipo, $monto, $concepto, $metodo_pago, $id_banco, $referencia, $responsable)
    {
        $tipo = strtoupper($tipo);

        if (!in_array($tipo, ['INGRESO', 'EGRESO'])) {
            return false;
        }

        $fecha = date("Y-m-d");
        $hora  = date("H:i:s");

        /**
         * 1️⃣ OBTENER SALDO ACTUAL DE LA CAJA GENERAL
         */
        $SaldoSQL = $this->db->SQL("SELECT saldo_caja FROM caja_general_movimientos ORDER BY id DESC LIMIT 1");

        if ($SaldoSQL && $SaldoSQL->num_rows > 0) {
            $saldoAnterior = floatval($SaldoSQL->fetch_assoc()['saldo_caja']);
        } else {
            $saldoAnterior = 0;
        }

        /**
         * 2️⃣ CALCULAR NUEVO SALDO
         */
        $saldoNuevo = ($tipo == "INGRESO")
            ? $saldoAnterior + $monto
            : $saldoAnterior - $monto;

        if ($saldoNuevo < 0) {
            $saldoNuevo = 0; // protección anti saldo negativo
        }

        /**
         * 3️⃣ REGISTRAR MOVIMIENTO EN CAJA GENERAL
         */
        $this->db->SQL("
            INSERT INTO caja_general_movimientos
            (
                fecha, hora, tipo, monto, concepto,
                metodo_pago, id_banco, referencia,
                responsable, saldo_caja
            ) VALUES (
                '{$fecha}', '{$hora}', '{$tipo}', {$monto}, '".addslashes($concepto)."',
                '{$metodo_pago}', ".($id_banco ?: "NULL").",
                ".($referencia ? "'".addslashes($referencia)."'" : "NULL").",
                {$responsable}, {$saldoNuevo}
            )
        ");

        return true;
    }

    /* ============================================================
       📌 REGISTRAR INGRESO/EGRESO EN CAJA GENERAL
    ============================================================ */
    public function CajaGeneralMovimiento1($tipo, $monto, $concepto, $metodo, $id_banco, $referencia, $responsable)
    {
        $fecha = date("Y-m-d");
        $hora  = date("H:i:s");
        $saldoAnterior = $this->SaldoCajaGeneral();

        $saldoNuevo = ($tipo === "INGRESO")
            ? $saldoAnterior + $monto
            : $saldoAnterior - $monto;

        /* ------- MANEJO DE BANCOS ------- */
        $saldoBanco = "NULL";

        if ($metodo != "EFECTIVO" && $id_banco) {
            $this->db->SQL("
                INSERT INTO banco_movimientos (id_banco, fecha, tipo, monto, concepto)
                VALUES ({$id_banco}, NOW(), '{$tipo}', {$monto}, '".addslashes($concepto)."')
            ");

            $bancoSQL = $this->db->SQL("
                SELECT b.saldo_inicial +
                COALESCE(SUM(CASE WHEN bm.tipo='INGRESO' THEN bm.monto ELSE -bm.monto END),0) AS saldo
                FROM bancos b
                LEFT JOIN banco_movimientos bm ON bm.id_banco=b.id
                WHERE b.id={$id_banco}
            ");

            $saldoBanco = $bancoSQL->num_rows ? $bancoSQL->fetch_assoc()['saldo'] : "NULL";
        }

        return $this->db->SQL("
            INSERT INTO caja_general_movimientos
            (fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia, responsable, saldo_caja, saldo_banco)
            VALUES 
            ('{$fecha}','{$hora}','{$tipo}',{$monto},'".addslashes($concepto)."',
            '{$metodo}', ".($id_banco ?: "NULL").",
            ".($referencia ? "'".addslashes($referencia)."'" : "NULL").",
            '{$responsable}', '{$saldoNuevo}', {$saldoBanco})
        ");
    }

    /* ============================================================
       📌 REGISTRAR INGRESO/EGRESO EN CAJA CHICA
    ============================================================ */
    public function CajaChicaMovimiento($tipo, $monto, $concepto, $responsable, $referencia = null)
    {
        $fecha = date("Y-m-d");
        $hora  = date("H:i:s");

        $saldoAnterior = $this->SaldoCajaChica();

        $saldoNuevo = ($tipo === "INGRESO")
            ? $saldoAnterior + $monto
            : $saldoAnterior - $monto;

        return $this->db->SQL("
            INSERT INTO caja_chica_movimientos
            (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
            VALUES (
                '{$fecha}', '{$hora}', '{$tipo}', {$monto}, '".addslashes($concepto)."', 
                {$responsable}, {$saldoNuevo},
                ".($referencia ? "'".addslashes($referencia)."'" : "NULL")."
            )
        ");
    }

    /* ============================================================
       📌 APERTURA CAJA GENERAL
    ============================================================ */
    public function AperturaCajaGeneral($monto, $responsable)
    {
        $fecha = date("Y-m-d");
        $hora  = date("H:i:s");

        // Verificar si ya está abierta
        $sql = $this->db->SQL("SELECT id FROM caja WHERE tipo_caja='GENERAL' AND estado=1 LIMIT 1");
        if ($sql->num_rows > 0) return false;

        // Registrar apertura
        $this->db->SQL("
            INSERT INTO caja (monto, fecha, hora, estado, habilitado, tipo_caja, responsable, observacion)
            VALUES ({$monto}, '{$fecha}', '{$hora}', 1, 1, 'GENERAL', {$responsable}, 'Apertura de caja general')
        ");

        // Registrar en movimientos
        return $this->CajaGeneralMovimiento("INGRESO", $monto, "Apertura de caja general", "EFECTIVO", null, "APERTURA", $responsable);
    }

    /* ============================================================
       📌 CIERRE CAJA GENERAL
    ============================================================ */
    public function CierreCajaGeneral($monto_cierre, $responsable)
    {
        // Obtener caja activa
        $sql = $this->db->SQL("
            SELECT id FROM caja 
            WHERE tipo_caja='GENERAL' AND estado=1 
            ORDER BY id DESC LIMIT 1
        ");

        if ($sql->num_rows == 0) return false;

        $row = $sql->fetch_assoc();
        $idCaja = $row['id'];

        // Cerrar caja
        $this->db->SQL("UPDATE caja SET estado=0, observacion='Cierre de caja general' WHERE id={$idCaja}");

        // Registrar movimiento
        return $this->CajaGeneralMovimiento("EGRESO", $monto_cierre, "Cierre de caja general", "EFECTIVO", null, "CIERRE", $responsable);
    }

    /* ============================================================
       📌 APERTURA CAJA CHICA
    ============================================================ */
    public function AperturaCajaChica($monto, $responsable)
    {
        $fecha = date("Y-m-d");
        $hora  = date("H:i:s");

        // Verificar si hay caja chica abierta
        $sql = $this->db->SQL("SELECT id FROM cajachica WHERE habilitado=1 LIMIT 1");
        if ($sql->num_rows > 0) return false;

        $this->db->SQL("
            INSERT INTO cajachica (monto, fecha, hora, tipo, responsable, observacion, habilitado)
            VALUES ({$monto}, '{$fecha}', '{$hora}', 1, {$responsable}, 'Apertura de caja chica', 1)
        ");

        return $this->CajaChicaMovimiento("INGRESO", $monto, "Apertura de caja chica", $responsable, "APERTURA");
    }

    /* ============================================================
       📌 CIERRE CAJA CHICA
    ============================================================ */
    public function CerrarCajaChica($monto_cierre, $responsable)
    {
        $sql = $this->db->SQL("SELECT id FROM cajachica WHERE habilitado=1 LIMIT 1");
        if ($sql->num_rows == 0) return false;

        $idCaja = $sql->fetch_assoc()['id'];

        $this->db->SQL("UPDATE cajachica SET habilitado=0, observacion='Cierre de caja chica' WHERE id={$idCaja}");

        return $this->CajaChicaMovimiento("EGRESO", $monto_cierre, "Cierre de caja chica", $responsable, "CIERRE");
    }
}

?>
