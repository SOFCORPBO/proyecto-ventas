<?php

class Cajas extends Conexion
{
<<<<<<< HEAD
=======
    /**
     * @var mixed
     */
>>>>>>> 80e5b70 (modulos factura y contabilidad)
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /* ============================================================
<<<<<<< HEAD
       📌 OBTENER SALDO ACTUAL — CAJA GENERAL
    ============================================================ */
=======
       📌 SALDOS
    ============================================================ */

    /**
     * Obtener saldo actual de Caja General
     */
>>>>>>> 80e5b70 (modulos factura y contabilidad)
    public function SaldoCajaGeneral()
    {
        $sql = $this->db->SQL("
            SELECT saldo_caja 
<<<<<<< HEAD
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

=======
            FROM caja_general_movimientos 
            ORDER BY id DESC 
            LIMIT 1
        ");

        if ($sql && $sql->num_rows > 0) {
            $row = $sql->fetch_assoc();
            return (float)$row['saldo_caja'];
        }

        return 0.0;
    }

    /**
     * Obtener saldo actual de Caja Chica
     */
    public function SaldoCajaChica()
    {
        $sql = $this->db->SQL("
            SELECT SUM(
                CASE WHEN tipo='INGRESO' THEN monto ELSE -monto END
            ) AS saldo
            FROM caja_chica_movimientos
        ");

        if ($sql && $sql->num_rows > 0) {
            $row = $sql->fetch_assoc();
            return $row['saldo'] !== null ? (float)$row['saldo'] : 0.0;
        }

        return 0.0;
    }

    /* ============================================================
       📌 CAJA GENERAL – APERTURA / CIERRE
    ============================================================ */

    /**
     * Aperturar Caja General
     */
    public function AperturarCajaGeneral($montoInicial, $responsable = null, $fecha = null, $hora = null)
    {
        $montoInicial = (float)$montoInicial;
        if ($montoInicial < 0) $montoInicial = 0;

        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

        // ¿Ya hay caja general abierta?
        $abierta = $this->db->SQL("
            SELECT id 
            FROM caja
            WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($abierta && $abierta->num_rows > 0) {
            // Ya está abierta, no hacemos nada
            return false;
        }

        // Insertar registro en tabla caja
        $this->db->SQL("
            INSERT INTO caja 
                (monto, fecha, hora, estado, habilitado, tipo_caja, responsable, observacion)
            VALUES
                ('{$montoInicial}', '{$fecha}', '{$hora}', 1, 1, 'GENERAL',
                 ".($responsable ? (int)$responsable : "NULL").",
                 'Apertura de caja general')
        ");

        // Registrar movimiento inicial en caja_general_movimientos
        $this->CajaGeneralMovimiento(
            'INGRESO',
            $montoInicial,
            'Apertura de caja general',
            'EFECTIVO',
            null,
            'APERTURA',
            $responsable,
            $fecha,
            $hora
        );

        return true;
    }

    /**
     * Cerrar Caja General
     */
    public function CerrarCajaGeneral($montoCierre, $responsable = null, $fecha = null, $hora = null)
    {
        $montoCierre = (float)$montoCierre;
        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

        // Buscar caja general abierta
        $cajaRes = $this->db->SQL("
            SELECT id 
            FROM caja
            WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1
            ORDER BY id DESC 
            LIMIT 1
        ");

        if (!$cajaRes || $cajaRes->num_rows == 0) {
            return false;
        }

        $caja = $cajaRes->fetch_assoc();
        $idCaja = (int)$caja['id'];

        // Marcar como cerrada
        $this->db->SQL("
            UPDATE caja 
            SET estado=0, observacion='Cierre de caja general'
            WHERE id={$idCaja}
        ");

        // Registrar egreso de cierre (dejando saldo_caja = 0)
        $this->CajaGeneralMovimiento(
            'EGRESO',
            $montoCierre,
            'Cierre de caja general',
            'EFECTIVO',
            null,
            'CIERRE',
            $responsable,
            $fecha,
            $hora,
            true // forzar saldo a 0
        );

>>>>>>> 80e5b70 (modulos factura y contabilidad)
        return true;
    }

    /* ============================================================
<<<<<<< HEAD
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
=======
       📌 CAJA CHICA – APERTURA / CIERRE
    ============================================================ */

    /**
     * Aperturar Caja Chica
     */
    public function AperturarCajaChica($montoInicial, $responsable = null, $fecha = null, $hora = null)
    {
        $montoInicial = (float)$montoInicial;
        if ($montoInicial < 0) $montoInicial = 0;

        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

        // ¿Existe caja chica abierta?
        $abierta = $this->db->SQL("
            SELECT id 
            FROM cajachica
            WHERE tipo=1 AND habilitado=1
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($abierta && $abierta->num_rows > 0) {
            return false;
        }

        // Insertar en cajachica
        $this->db->SQL("
            INSERT INTO cajachica 
                (monto, fecha, hora, tipo, responsable, observacion, habilitado)
            VALUES
                ('{$montoInicial}', '{$fecha}', '{$hora}', 1,
                 ".($responsable ? (int)$responsable : "NULL").",
                 'Apertura de caja chica', 1)
        ");

        // Registrar movimiento inicial en caja_chica_movimientos
        $this->CajaChicaMovimiento(
            'INGRESO',
            $montoInicial,
            'Apertura de caja chica',
            $responsable,
            'APERTURA',
            $fecha,
            $hora
        );

        return true;
    }

    /**
     * Cerrar Caja Chica
     */
    public function CerrarCajaChica($montoCierre, $responsable = null, $fecha = null, $hora = null)
    {
        $montoCierre = (float)$montoCierre;
        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

        // Buscar caja chica abierta
        $cajaRes = $this->db->SQL("
            SELECT id 
            FROM cajachica
            WHERE tipo=1 AND habilitado=1
            ORDER BY id DESC
            LIMIT 1
        ");

        if (!$cajaRes || $cajaRes->num_rows == 0) {
            return false;
        }

        $caja = $cajaRes->fetch_assoc();
        $idCaja = (int)$caja['id'];

        // Marcar cierre
        $this->db->SQL("
            UPDATE cajachica 
            SET habilitado=0, observacion='Cierre de caja chica'
            WHERE id={$idCaja}
        ");

        // Registrar egreso (saldo_resultante = 0)
        $this->CajaChicaMovimiento(
            'EGRESO',
            $montoCierre,
            'Cierre de caja chica',
            $responsable,
            'CIERRE',
            $fecha,
            $hora,
            true // forzar saldo 0
        );

        return true;
    }

    /* ============================================================
       📌 MOVIMIENTOS – CAJA GENERAL
    ============================================================ */

    /**
     * Registrar movimiento en Caja General
     *
     * 🔴 IMPORTANTE: Se mantiene esta firma para compatibilidad
     * con proveedor-pagos.php:
     *    CajaGeneralMovimiento($tipo,$monto,$concepto,$metodo_pago,$id_banco,$referencia,$responsable)
     */
    public function CajaGeneralMovimiento(
        $tipo,
        $monto,
        $concepto,
        $metodo_pago = 'EFECTIVO',
        $id_banco = null,
        $referencia = null,
        $responsable = null,
        $fecha = null,
        $hora = null,
        $forzarSaldoCero = false
    ) {
        $tipo = ($tipo === 'INGRESO') ? 'INGRESO' : 'EGRESO';
        $monto = (float)$monto;
        if ($monto <= 0) return false;

        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

        // Obtener saldo anterior
        $SaldoSQL = $this->db->SQL("
            SELECT saldo_caja 
            FROM caja_general_movimientos
            ORDER BY id DESC
            LIMIT 1
        ");

        $saldoAnterior = ($SaldoSQL && $SaldoSQL->num_rows > 0)
            ? (float)$SaldoSQL->fetch_assoc()['saldo_caja']
            : 0.0;

        // Nuevo saldo
        if ($forzarSaldoCero) {
            $saldoNuevo = 0.0;
        } else {
            $saldoNuevo = ($tipo === 'INGRESO')
                ? $saldoAnterior + $monto
                : $saldoAnterior - $monto;
        }

        // Control bancario (si no es EFECTIVO y hay banco)
        $saldoBanco = null;
        if ($metodo_pago !== 'EFECTIVO' && $id_banco) {

            $this->db->SQL("
                INSERT INTO banco_movimientos
                    (id_banco, fecha, tipo, monto, concepto, id_venta)
                VALUES
                    ('{$id_banco}', NOW(), '{$tipo}', '{$monto}', '".addslashes($concepto)."', NULL)
            ");

            $SaldoBancoSQL = $this->db->SQL("
                SELECT 
                    b.saldo_inicial +
                    COALESCE(
                        SUM(
                            CASE WHEN bm.tipo='INGRESO' THEN bm.monto ELSE -bm.monto END
                        ), 0
                    ) AS saldo
                FROM bancos b
                LEFT JOIN banco_movimientos bm ON bm.id_banco = b.id
                WHERE b.id = '{$id_banco}'
                GROUP BY b.id
            ");

            if ($SaldoBancoSQL && $SaldoBancoSQL->num_rows > 0) {
                $saldoBanco = (float)$SaldoBancoSQL->fetch_assoc()['saldo'];
            }
        }

        // Insertar movimiento en caja_general_movimientos
        $sql = "
            INSERT INTO caja_general_movimientos
                (fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, referencia,
                 responsable, saldo_caja, saldo_banco)
            VALUES
                (
                    '{$fecha}',
                    '{$hora}',
                    '{$tipo}',
                    '{$monto}',
                    '".addslashes($concepto)."',
                    '{$metodo_pago}',
                    ".($id_banco ? "'{$id_banco}'" : "NULL").",
                    ".($referencia ? "'".addslashes($referencia)."'" : "NULL").",
                    ".($responsable ? (int)$responsable : "NULL").",
                    '{$saldoNuevo}',
                    ".($saldoBanco !== null ? "'{$saldoBanco}'" : "NULL")."
                )
        ";

        return $this->db->SQL($sql);
    }

    /**
     * Listar movimientos de Caja General con filtros
     *
     * $filtros = [
     *   'desde'       => 'YYYY-mm-dd',
     *   'hasta'       => 'YYYY-mm-dd',
     *   'metodo_pago' => 'EFECTIVO|TRANSFERENCIA|DEPOSITO|TARJETA',
     *   'id_banco'    => int
     * ]
     */
    public function ListarMovimientosCajaGeneral(array $filtros = [])
    {
        $where = "1=1";

        if (!empty($filtros['desde'])) {
            $desde = $this->db->real_escape_string($filtros['desde']);
            $where .= " AND m.fecha >= '{$desde}'";
        }

        if (!empty($filtros['hasta'])) {
            $hasta = $this->db->real_escape_string($filtros['hasta']);
            $where .= " AND m.fecha <= '{$hasta}'";
        }

        if (!empty($filtros['metodo_pago'])) {
            $met = $this->db->real_escape_string($filtros['metodo_pago']);
            $where .= " AND m.metodo_pago = '{$met}'";
        }

        if (!empty($filtros['id_banco'])) {
            $idb = (int)$filtros['id_banco'];
            $where .= " AND m.id_banco = '{$idb}'";
        }

        return $this->db->SQL("
            SELECT 
                m.*,
                u.usuario AS responsable_usuario,
                b.nombre AS banco_nombre
            FROM caja_general_movimientos m
            LEFT JOIN usuario u ON u.id = m.responsable
            LEFT JOIN bancos b  ON b.id = m.id_banco
            WHERE {$where}
            ORDER BY m.id DESC
>>>>>>> 80e5b70 (modulos factura y contabilidad)
        ");
    }

    /* ============================================================
<<<<<<< HEAD
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
=======
       📌 MOVIMIENTOS – CAJA CHICA
    ============================================================ */

    /**
     * Registrar movimiento en Caja Chica
     */
    public function CajaChicaMovimiento(
        $tipo,
        $monto,
        $concepto,
        $responsable = null,
        $referencia = null,
        $fecha = null,
        $hora = null,
        $forzarSaldoCero = false
    ) {
        $tipo  = ($tipo === 'INGRESO') ? 'INGRESO' : 'EGRESO';
        $monto = (float)$monto;
        if ($monto <= 0) return false;

        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

        // Saldo anterior
        $sql = $this->db->SQL("
            SELECT saldo_resultante 
            FROM caja_chica_movimientos
            ORDER BY id DESC
            LIMIT 1
        ");

        $saldoAnterior = ($sql && $sql->num_rows > 0)
            ? (float)$sql->fetch_assoc()['saldo_resultante']
            : 0.0;

        // Nuevo saldo
        if ($forzarSaldoCero) {
            $saldoNuevo = 0.0;
        } else {
            $saldoNuevo = ($tipo === 'INGRESO')
                ? $saldoAnterior + $monto
                : $saldoAnterior - $monto;
        }

        $query = "
            INSERT INTO caja_chica_movimientos
                (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
            VALUES
                (
                    '{$fecha}',
                    '{$hora}',
                    '{$tipo}',
                    '{$monto}',
                    '".addslashes($concepto)."',
                    ".($responsable ? (int)$responsable : "NULL").",
                    '{$saldoNuevo}',
                    ".($referencia ? "'".addslashes($referencia)."'" : "NULL")."
                )
        ";

        return $this->db->SQL($query);
    }

    /**
     * Listar movimientos de Caja Chica
     */
    public function ListarMovimientosCajaChica(array $filtros = [])
    {
        $where = "1=1";

        if (!empty($filtros['desde'])) {
            $desde = $this->db->real_escape_string($filtros['desde']);
            $where .= " AND fecha >= '{$desde}'";
        }

        if (!empty($filtros['hasta'])) {
            $hasta = $this->db->real_escape_string($filtros['hasta']);
            $where .= " AND fecha <= '{$hasta}'";
        }

        return $this->db->SQL("
            SELECT *
            FROM caja_chica_movimientos
            WHERE {$where}
            ORDER BY id DESC
        ");
    }
}
>>>>>>> 80e5b70 (modulos factura y contabilidad)
