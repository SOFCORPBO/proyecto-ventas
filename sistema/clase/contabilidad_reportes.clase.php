<?php

class ContabilidadReportes extends Conexion
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /* ============================================================
       📌 LIBRO MAYOR POR CUENTA
    ============================================================ */
    public function LibroMayorCuenta($id_cuenta)
    {
        return $this->db->SQL("
            SELECT d.fecha, dd.debe, dd.haber, d.descripcion
            FROM contabilidad_diario_detalle dd
            INNER JOIN contabilidad_diario d ON d.id = dd.id_diario
            WHERE dd.id_cuenta = {$id_cuenta}
            ORDER BY d.fecha ASC, dd.id ASC
        ");
    }

    /* ============================================================
       📌 ESTADO DE RESULTADOS
       Ingresos – Gastos = Utilidad
    ============================================================ */
    public function EstadoResultados()
    {
        return $this->db->SQL("
            SELECT 
                (SELECT SUM(haber - debe)
                 FROM contabilidad_diario_detalle dd
                 INNER JOIN contabilidad_cuentas c ON c.id = dd.id_cuenta
                 WHERE c.tipo='INGRESO') AS ingresos,

                (SELECT SUM(debe - haber)
                 FROM contabilidad_diario_detalle dd
                 INNER JOIN contabilidad_cuentas c ON c.id = dd.id_cuenta
                 WHERE c.tipo='GASTO') AS gastos
        ")->fetch_assoc();
    }

    /* ============================================================
       📌 BALANCE GENERAL
    ============================================================ */
    public function BalanceGeneral()
    {
        return $this->db->SQL("
            SELECT 
                (SELECT SUM(debe - haber)
                 FROM contabilidad_diario_detalle dd
                 INNER JOIN contabilidad_cuentas c ON c.id = dd.id_cuenta
                 WHERE c.tipo='ACTIVO') AS activos,

                (SELECT SUM(haber - debe)
                 FROM contabilidad_diario_detalle dd
                 INNER JOIN contabilidad_cuentas c ON c.id = dd.id_cuenta
                 WHERE c.tipo IN ('PASIVO','PATRIMONIO')) AS pasivo_patrimonio
        ")->fetch_assoc();
    }
}

?>
