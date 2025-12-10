<?php

class Impuestos extends Conexion
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /* ============================================================
       📌 RESUMEN DE IMPUESTOS POR RANGO DE FECHAS
       Solo toma ventas habilitadas, no anuladas, con factura
    ============================================================ */
    public function ResumenImpuestos($desde, $hasta)
    {
        $desde = $this->db->real_escape_string($desde);
        $hasta = $this->db->real_escape_string($hasta);

        $sql = $this->db->SQL("
            SELECT
                COUNT(*) AS total_ventas_facturadas,
                SUM(totalprecio) AS base_imponible,
                SUM(iva_monto)      AS total_iva,
                SUM(impuesto_monto) AS total_it
            FROM ventas
            WHERE fecha >= '{$desde}'
              AND fecha <= '{$hasta}'
              AND habilitada = 1
              AND anulada   = 0
              AND con_factura = 1
        ");

        return $sql->fetch_assoc();
    }

    /* ============================================================
       📌 DETALLE DIARIO DE IMPUESTOS PARA UN MES
       $anioMes formato 'YYYY-mm' (ej: '2025-12')
    ============================================================ */
    public function DetalleMensual($anioMes)
    {
        $anioMes = $this->db->real_escape_string($anioMes);

        return $this->db->SQL("
            SELECT
                fecha,
                COUNT(*) AS total_ventas,
                SUM(totalprecio) AS base_imponible,
                SUM(iva_monto)      AS total_iva,
                SUM(impuesto_monto) AS total_it
            FROM ventas
            WHERE fecha LIKE '{$anioMes}-%'
              AND habilitada = 1
              AND anulada   = 0
              AND con_factura = 1
            GROUP BY fecha
            ORDER BY fecha ASC
        ");
    }

    /* ============================================================
       📌 CALCULAR IVA E IT EN BASE A UN MONTO
       (por si quieres usarlo fuera de ventas)
    ============================================================ */
    public function CalcularDesdeMonto($monto, $porcIVA = 13, $porcIT = 3)
    {
        $monto = (float)$monto;
        $iva   = round($monto * ($porcIVA / 100), 2);
        $it    = round($monto * ($porcIT / 100), 2);

        return [
            'base' => $monto,
            'iva'  => $iva,
            'it'   => $it
        ];
    }
}

?>