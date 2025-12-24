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
       📌 UTILIDADES
    ============================================================ */
    private function esc($v)
    {
        return addslashes(trim((string)$v));
    }

    /* ============================================================
       📌 SALDOS
    ============================================================ */

    public function SaldoCajaGeneral()
    {
        $sql = $this->db->SQL("
            SELECT saldo_caja
            FROM caja_general_movimientos
            ORDER BY id DESC
            LIMIT 1
        ");

        return ($sql && $sql->num_rows > 0)
            ? (float)$sql->fetch_assoc()['saldo_caja']
            : 0.0;
    }

    public function SaldoCajaChica()
    {
        $sql = $this->db->SQL("
            SELECT saldo_resultante
            FROM caja_chica_movimientos
            ORDER BY id DESC
            LIMIT 1
        ");

        return ($sql && $sql->num_rows > 0)
            ? (float)$sql->fetch_assoc()['saldo_resultante']
            : 0.0;
    }

    /* ============================================================
       📌 MOVIMIENTOS – CAJA GENERAL (COMPAT + SOPORTE QR)
       - Mantiene compatibilidad con llamadas existentes.
       - Si tienes columna id_qr en caja_general_movimientos, funcionará.
    ============================================================ */

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
        $forzarSaldoCero = false,
        $id_qr = null
    ) {
        $tipo = strtoupper(trim($tipo)) === 'INGRESO' ? 'INGRESO' : 'EGRESO';
        $metodo_pago = strtoupper(trim((string)$metodo_pago));
        $monto = (float)$monto;

        if ($monto <= 0) return false;

        // Métodos permitidos (si aún no agregaste QR al ENUM, úsalo solo cuando esté migrado)
        $metodosPermitidos = ['EFECTIVO','TRANSFERENCIA','DEPOSITO','TARJETA','QR'];
        if (!in_array($metodo_pago, $metodosPermitidos, true)) {
            $metodo_pago = 'EFECTIVO';
        }

        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

        $concepto   = $this->esc($concepto);
        $referencia = $referencia !== null && $referencia !== '' ? $this->esc($referencia) : null;

        $responsable = $responsable !== null ? (int)$responsable : null;
        $id_banco    = !empty($id_banco) ? (int)$id_banco : null;
        $id_qr       = !empty($id_qr) ? (int)$id_qr : null;

        // Si método es QR, exigir id_qr (si lo quieres estricto)
        if ($metodo_pago === 'QR' && empty($id_qr)) {
            return false;
        }

        // Si es QR y no llega banco, intentar derivar banco desde qr_cuentas
        if ($metodo_pago === 'QR' && empty($id_banco) && !empty($id_qr)) {
            $QrSQL = $this->db->SQL("SELECT id_banco FROM qr_cuentas WHERE id={$id_qr} AND habilitado=1 LIMIT 1");
            if ($QrSQL && $QrSQL->num_rows > 0) {
                $id_banco_qr = (int)$QrSQL->fetch_assoc()['id_banco'];
                if ($id_banco_qr > 0) $id_banco = $id_banco_qr;
            }
        }

        // Saldo anterior caja general
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
            $saldoNuevo = ($tipo === 'INGRESO') ? ($saldoAnterior + $monto) : ($saldoAnterior - $monto);
            if ($saldoNuevo < 0) $saldoNuevo = 0.0; // protección anti saldo negativo
        }

        // Control bancario (si no es EFECTIVO y hay banco)
        $saldoBanco = null;

        if ($metodo_pago !== 'EFECTIVO' && !empty($id_banco)) {

            // Registrar movimiento bancario
            $this->db->SQL("
                INSERT INTO banco_movimientos
                    (id_banco, fecha, tipo, monto, concepto, id_venta)
                VALUES
                    ({$id_banco}, NOW(), '{$tipo}', {$monto}, '{$concepto}', NULL)
            ");

            // Calcular saldo del banco
            $SaldoBancoSQL = $this->db->SQL("
                SELECT
                    b.saldo_inicial +
                    COALESCE(
                        SUM(CASE WHEN bm.tipo='INGRESO' THEN bm.monto ELSE -bm.monto END),
                        0
                    ) AS saldo
                FROM bancos b
                LEFT JOIN banco_movimientos bm ON bm.id_banco = b.id
                WHERE b.id = {$id_banco}
                GROUP BY b.id
            ");

            if ($SaldoBancoSQL && $SaldoBancoSQL->num_rows > 0) {
                $saldoBanco = (float)$SaldoBancoSQL->fetch_assoc()['saldo'];
            }
        }

        // Insertar movimiento en caja_general_movimientos (incluye id_qr)
        $sql = "
            INSERT INTO caja_general_movimientos
                (fecha, hora, tipo, monto, concepto, metodo_pago, id_banco, id_qr, referencia,
                 responsable, saldo_caja, saldo_banco)
            VALUES
                (
                    '{$fecha}',
                    '{$hora}',
                    '{$tipo}',
                    {$monto},
                    '{$concepto}',
                    '{$metodo_pago}',
                    ".($id_banco ? $id_banco : "NULL").",
                    ".($id_qr ? $id_qr : "NULL").",
                    ".($referencia ? "'{$referencia}'" : "NULL").",
                    ".($responsable !== null ? $responsable : "NULL").",
                    {$saldoNuevo},
                    ".($saldoBanco !== null ? $saldoBanco : "NULL")."
                )
        ";

        return $this->db->SQL($sql);
    }

    /**
     * Alias útil para registrar desde formularios (mantiene tu uso actual)
     * Soporta id_qr como 8vo parámetro.
     */
    public function CajaGeneralMovimiento1($tipo, $monto, $concepto, $metodo, $id_banco, $referencia, $responsable, $id_qr = null)
    {
        return $this->CajaGeneralMovimiento(
            $tipo,
            $monto,
            $concepto,
            $metodo,
            $id_banco,
            $referencia,
            $responsable,
            null,
            null,
            false,
            $id_qr
        );
    }

    /* ============================================================
       📌 CAJA GENERAL – APERTURA / CIERRE
       (Incluye compat con nombres antiguos)
    ============================================================ */

    public function AperturarCajaGeneral($montoInicial, $responsable = null, $fecha = null, $hora = null)
    {
        $montoInicial = (float)$montoInicial;
        if ($montoInicial < 0) $montoInicial = 0;

        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

        $abierta = $this->db->SQL("
            SELECT id
            FROM caja
            WHERE tipo_caja='GENERAL' AND estado=1 AND habilitado=1
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($abierta && $abierta->num_rows > 0) {
            return false;
        }

        $this->db->SQL("
            INSERT INTO caja
                (monto, fecha, hora, estado, habilitado, tipo_caja, responsable, observacion)
            VALUES
                ({$montoInicial}, '{$fecha}', '{$hora}', 1, 1, 'GENERAL',
                 ".($responsable ? (int)$responsable : "NULL").",
                 'Apertura de caja general')
        ");

        return $this->CajaGeneralMovimiento(
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
    }

    public function CerrarCajaGeneral($montoCierre, $responsable = null, $fecha = null, $hora = null)
    {
        $montoCierre = (float)$montoCierre;
        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

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

        $idCaja = (int)$cajaRes->fetch_assoc()['id'];

        $this->db->SQL("
            UPDATE caja
            SET estado=0, observacion='Cierre de caja general'
            WHERE id={$idCaja}
        ");

        return $this->CajaGeneralMovimiento(
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
    }

    // Compatibilidad con tus nombres antiguos
    public function AperturaCajaGeneral($monto, $responsable)
    {
        return $this->AperturarCajaGeneral($monto, $responsable);
    }

    public function CierreCajaGeneral($monto_cierre, $responsable)
    {
        return $this->CerrarCajaGeneral($monto_cierre, $responsable);
    }

    /* ============================================================
       📌 MOVIMIENTOS – CAJA CHICA
    ============================================================ */

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
        $tipo = strtoupper(trim($tipo)) === 'INGRESO' ? 'INGRESO' : 'EGRESO';
        $monto = (float)$monto;
        if ($monto <= 0) return false;

        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

        $concepto = $this->esc($concepto);
        $referencia = $referencia !== null && $referencia !== '' ? $this->esc($referencia) : null;

        $sql = $this->db->SQL("
            SELECT saldo_resultante
            FROM caja_chica_movimientos
            ORDER BY id DESC
            LIMIT 1
        ");

        $saldoAnterior = ($sql && $sql->num_rows > 0)
            ? (float)$sql->fetch_assoc()['saldo_resultante']
            : 0.0;

        if ($forzarSaldoCero) {
            $saldoNuevo = 0.0;
        } else {
            $saldoNuevo = ($tipo === 'INGRESO') ? ($saldoAnterior + $monto) : ($saldoAnterior - $monto);
        }

        $query = "
            INSERT INTO caja_chica_movimientos
                (fecha, hora, tipo, monto, concepto, responsable, saldo_resultante, referencia)
            VALUES
                (
                    '{$fecha}',
                    '{$hora}',
                    '{$tipo}',
                    {$monto},
                    '{$concepto}',
                    ".($responsable ? (int)$responsable : "NULL").",
                    {$saldoNuevo},
                    ".($referencia ? "'{$referencia}'" : "NULL")."
                )
        ";

        return $this->db->SQL($query);
    }

    public function AperturarCajaChica($montoInicial, $responsable = null, $fecha = null, $hora = null)
    {
        $montoInicial = (float)$montoInicial;
        if ($montoInicial < 0) $montoInicial = 0;

        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

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

        $this->db->SQL("
            INSERT INTO cajachica
                (monto, fecha, hora, tipo, responsable, observacion, habilitado)
            VALUES
                ({$montoInicial}, '{$fecha}', '{$hora}', 1,
                 ".($responsable ? (int)$responsable : "NULL").",
                 'Apertura de caja chica', 1)
        ");

        return $this->CajaChicaMovimiento(
            'INGRESO',
            $montoInicial,
            'Apertura de caja chica',
            $responsable,
            'APERTURA',
            $fecha,
            $hora
        );
    }

    public function CerrarCajaChica($montoCierre, $responsable = null, $fecha = null, $hora = null)
    {
        $montoCierre = (float)$montoCierre;
        $fecha = $fecha ?: date('Y-m-d');
        $hora  = $hora  ?: date('H:i:s');

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

        $idCaja = (int)$cajaRes->fetch_assoc()['id'];

        $this->db->SQL("
            UPDATE cajachica
            SET habilitado=0, observacion='Cierre de caja chica'
            WHERE id={$idCaja}
        ");

        return $this->CajaChicaMovimiento(
            'EGRESO',
            $montoCierre,
            'Cierre de caja chica',
            $responsable,
            'CIERRE',
            $fecha,
            $hora,
            true // forzar saldo 0
        );
    }

    // Compatibilidad con tus nombres antiguos
    public function AperturaCajaChica($monto, $responsable)
    {
        return $this->AperturarCajaChica($monto, $responsable);
    }

    /* ============================================================
       ✅ SUBMÓDULO BANCOS
       Tabla: bancos(id, nombre, numero_cuenta, tipo_cuenta, moneda, saldo_inicial)
    ============================================================ */

    public function BancosListarConSaldo()
    {
        return $this->db->SQL("
            SELECT
                b.*,
                (b.saldo_inicial + COALESCE(SUM(CASE WHEN bm.tipo='INGRESO' THEN bm.monto ELSE -bm.monto END),0)) AS saldo_actual
            FROM bancos b
            LEFT JOIN banco_movimientos bm ON bm.id_banco = b.id
            GROUP BY b.id
            ORDER BY b.nombre ASC
        ");
    }

    public function BancosListar()
    {
        return $this->db->SQL("SELECT id, nombre FROM bancos ORDER BY nombre ASC");
    }

    public function BancoObtenerPorId($id)
    {
        $id = (int)$id;
        return $this->db->SQL("SELECT * FROM bancos WHERE id={$id} LIMIT 1");
    }

    public function BancoCrear($nombre, $numero_cuenta, $tipo_cuenta, $moneda, $saldo_inicial)
    {
        $nombre        = $this->esc($nombre);
        $numero_cuenta = $this->esc($numero_cuenta);
        $tipo_cuenta   = strtoupper(trim((string)$tipo_cuenta));
        $moneda        = $this->esc($moneda);
        $saldo_inicial = (float)$saldo_inicial;

        if ($nombre === '' || $numero_cuenta === '') return false;
        if (!in_array($tipo_cuenta, ['CAJA_AHORRO','CUENTA_CORRIENTE'], true)) $tipo_cuenta = 'CAJA_AHORRO';
        if ($moneda === '') $moneda = 'BOB';
        if ($saldo_inicial < 0) $saldo_inicial = 0;

        return $this->db->SQL("
            INSERT INTO bancos (nombre, numero_cuenta, tipo_cuenta, moneda, saldo_inicial)
            VALUES ('{$nombre}','{$numero_cuenta}','{$tipo_cuenta}','{$moneda}',{$saldo_inicial})
        ");
    }

    public function BancoActualizar($id, $nombre, $numero_cuenta, $tipo_cuenta, $moneda, $saldo_inicial)
    {
        $id            = (int)$id;
        $nombre        = $this->esc($nombre);
        $numero_cuenta = $this->esc($numero_cuenta);
        $tipo_cuenta   = strtoupper(trim((string)$tipo_cuenta));
        $moneda        = $this->esc($moneda);
        $saldo_inicial = (float)$saldo_inicial;

        if ($id <= 0) return false;
        if ($nombre === '' || $numero_cuenta === '') return false;
        if (!in_array($tipo_cuenta, ['CAJA_AHORRO','CUENTA_CORRIENTE'], true)) $tipo_cuenta = 'CAJA_AHORRO';
        if ($moneda === '') $moneda = 'BOB';
        if ($saldo_inicial < 0) $saldo_inicial = 0;

        return $this->db->SQL("
            UPDATE bancos
            SET nombre='{$nombre}',
                numero_cuenta='{$numero_cuenta}',
                tipo_cuenta='{$tipo_cuenta}',
                moneda='{$moneda}',
                saldo_inicial={$saldo_inicial}
            WHERE id={$id}
            LIMIT 1
        ");
    }
/* ============================================================
   ✅ SUBMÓDULO QR
   Tabla: qr_cuentas(id, nombre, proveedor, moneda, id_banco, imagen_qr, habilitado, created_at)
============================================================ */

public function QrListarConBanco($soloActivos = false)
{
    $where = $soloActivos ? "WHERE q.habilitado=1" : "WHERE 1=1";
    return $this->db->SQL("
        SELECT 
            q.*,
            b.nombre AS banco_nombre
        FROM qr_cuentas q
        LEFT JOIN bancos b ON b.id = q.id_banco
        {$where}
        ORDER BY q.nombre ASC
    ");
}

public function QrObtenerPorId($id)
{
    $id = (int)$id;
    return $this->db->SQL("SELECT * FROM qr_cuentas WHERE id={$id} LIMIT 1");
}

public function QrCrear($nombre, $proveedor, $moneda, $id_banco, $imagen_qr, $habilitado = 1)
{
    $nombre    = addslashes(trim($nombre));
    $proveedor = addslashes(trim($proveedor));
    $moneda    = addslashes(trim($moneda));
    $id_banco  = !empty($id_banco) ? (int)$id_banco : "NULL";
    $habilitado = (int)$habilitado;

    if ($nombre === '') return false;
    if ($moneda === '') $moneda = 'BOB';

    $imagen_qr_sql = (!empty($imagen_qr)) ? "'" . addslashes($imagen_qr) . "'" : "NULL";

    return $this->db->SQL("
        INSERT INTO qr_cuentas (nombre, proveedor, moneda, id_banco, imagen_qr, habilitado)
        VALUES ('{$nombre}', ".($proveedor!=='' ? "'{$proveedor}'" : "NULL").", '{$moneda}', {$id_banco}, {$imagen_qr_sql}, {$habilitado})
    ");
}

public function QrActualizar($id, $nombre, $proveedor, $moneda, $id_banco, $imagen_qr = null, $habilitado = 1)
{
    $id       = (int)$id;
    $nombre   = addslashes(trim($nombre));
    $proveedor= addslashes(trim($proveedor));
    $moneda   = addslashes(trim($moneda));
    $id_banco = !empty($id_banco) ? (int)$id_banco : "NULL";
    $habilitado = (int)$habilitado;

    if ($id <= 0) return false;
    if ($nombre === '') return false;
    if ($moneda === '') $moneda = 'BOB';

    $setImagen = "";
    if ($imagen_qr !== null) {
        $setImagen = ", imagen_qr=" . (!empty($imagen_qr) ? "'" . addslashes($imagen_qr) . "'" : "NULL");
    }

    return $this->db->SQL("
        UPDATE qr_cuentas
        SET nombre='{$nombre}',
            proveedor=" . ($proveedor!=='' ? "'{$proveedor}'" : "NULL") . ",
            moneda='{$moneda}',
            id_banco={$id_banco},
            habilitado={$habilitado}
            {$setImagen}
        WHERE id={$id}
        LIMIT 1
    ");
}

/* Seguridad: NO eliminar si ya fue usado en caja_general_movimientos */
public function QrPuedeEliminar($id)
{
    $id = (int)$id;

    $c = $this->db->SQL("SELECT COUNT(*) AS c FROM caja_general_movimientos WHERE id_qr={$id}");
    $n = ($c && $c->num_rows) ? (int)$c->fetch_assoc()['c'] : 0;

    return ($n === 0);
}

public function QrEliminar($id)
{
    $id = (int)$id;
    if ($id <= 0) return false;

    if (!$this->QrPuedeEliminar($id)) return false;

    return $this->db->SQL("DELETE FROM qr_cuentas WHERE id={$id} LIMIT 1");
}

    public function BancoPuedeEliminar($id)
    {
        $id = (int)$id;

        $c1 = $this->db->SQL("SELECT COUNT(*) AS c FROM banco_movimientos WHERE id_banco={$id}");
        $n1 = ($c1 && $c1->num_rows) ? (int)$c1->fetch_assoc()['c'] : 0;

        $c2 = $this->db->SQL("SELECT COUNT(*) AS c FROM caja_general_movimientos WHERE id_banco={$id}");
        $n2 = ($c2 && $c2->num_rows) ? (int)$c2->fetch_assoc()['c'] : 0;

        return ($n1 === 0 && $n2 === 0);
    }

    public function BancoEliminar($id)
    {
        $id = (int)$id;
        if ($id <= 0) return false;

        if (!$this->BancoPuedeEliminar($id)) {
            return false;
        }

        return $this->db->SQL("DELETE FROM bancos WHERE id={$id} LIMIT 1");
    }
}

?>