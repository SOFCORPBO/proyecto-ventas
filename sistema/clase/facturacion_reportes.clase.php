<?php

class FacturacionReportes extends Conexion
{
    private $db;

    public function __construct()
    {
        global $db;          // En tu sistema $db es instancia de Conexion (wrapper)
        $this->db = $db;
    }

    /** Escapar string usando la conexión real mysqli */
    private function esc($val)
    {
        $cn = $this->db->Conectar(); // mysqli
        return $cn->real_escape_string((string)$val);
    }

    /** Convertir a int seguro */
    private function intValSafe($val)
    {
        return (int)$val;
    }

    /* ============================================================
       📌 RESUMEN DIARIO (una fecha específica)
    ============================================================ */
    public function ResumenDiario($fecha)
    {
        $fecha = $this->esc($fecha);

        $sql = $this->db->SQL("
            SELECT
                fecha,
                COUNT(*) AS total_ventas,
                SUM(CASE WHEN con_factura = 1 THEN 1 ELSE 0 END) AS ventas_con_factura,
                SUM(CASE WHEN con_factura = 0 THEN 1 ELSE 0 END) AS ventas_sin_factura,
                SUM(totalprecio) AS total_bruto,
                SUM(iva_monto)      AS total_iva,
                SUM(impuesto_monto) AS total_it,
                SUM(CASE WHEN con_factura = 1 THEN totalprecio ELSE 0 END) AS total_facturado,
                SUM(CASE WHEN con_factura = 0 THEN totalprecio ELSE 0 END) AS total_no_facturado
            FROM ventas
            WHERE fecha = '{$fecha}'
              AND habilitada = 1
              AND anulada = 0
            GROUP BY fecha
        ");

        return $sql ? $sql->fetch_assoc() : null;
    }

    /* ============================================================
       📌 RESUMEN POR RANGO DE FECHAS
    ============================================================ */
    public function ResumenRango($desde, $hasta)
    {
        $desde = $this->esc($desde);
        $hasta = $this->esc($hasta);

        $sql = $this->db->SQL("
            SELECT
                COUNT(*) AS total_ventas,
                SUM(CASE WHEN con_factura = 1 THEN 1 ELSE 0 END) AS ventas_con_factura,
                SUM(CASE WHEN con_factura = 0 THEN 1 ELSE 0 END) AS ventas_sin_factura,
                SUM(totalprecio) AS total_bruto,
                SUM(iva_monto)      AS total_iva,
                SUM(impuesto_monto) AS total_it,
                SUM(CASE WHEN con_factura = 1 THEN totalprecio ELSE 0 END) AS total_facturado,
                SUM(CASE WHEN con_factura = 0 THEN totalprecio ELSE 0 END) AS total_no_facturado
            FROM ventas
            WHERE fecha >= '{$desde}'
              AND fecha <= '{$hasta}'
              AND habilitada = 1
              AND anulada = 0
        ");

        return $sql ? $sql->fetch_assoc() : null;
    }

    /* ============================================================
       📌 LISTADO DETALLADO POR RANGO + ESTADO FACTURA
    ============================================================ */
    public function ListadoDetallado($desde, $hasta, $con_factura = '')
    {
        $desde = $this->esc($desde);
        $hasta = $this->esc($hasta);

        $where = "v.fecha >= '{$desde}' AND v.fecha <= '{$hasta}' AND v.habilitada=1 AND v.anulada=0";

        if ($con_factura !== '' && $con_factura !== null) {
            $cf = $this->intValSafe($con_factura);
            $where .= " AND v.con_factura = {$cf}";
        }

        return $this->db->SQL("
            SELECT
                v.*,
                c.nombre AS cliente_nombre,
                p.nombre AS servicio_nombre,
                u.usuario AS vendedor_nombre
            FROM ventas v
            LEFT JOIN cliente  c ON c.id = v.cliente
            LEFT JOIN producto p ON p.id = v.producto
            LEFT JOIN usuario  u ON u.id = v.vendedor
            WHERE {$where}
            ORDER BY v.fecha ASC, v.hora ASC
        ");
    }

    /* ============================================================
       📌 RESUMEN POR VENDEDOR
    ============================================================ */
    public function ResumenPorVendedor($desde, $hasta)
    {
        $desde = $this->esc($desde);
        $hasta = $this->esc($hasta);

        return $this->db->SQL("
            SELECT
                v.vendedor,
                u.usuario AS vendedor_nombre,
                COUNT(*) AS total_ventas,
                SUM(totalprecio) AS total_bruto,
                SUM(CASE WHEN v.con_factura = 1 THEN totalprecio ELSE 0 END) AS total_facturado,
                SUM(CASE WHEN v.con_factura = 0 THEN totalprecio ELSE 0 END) AS total_no_facturado,
                SUM(iva_monto)      AS total_iva,
                SUM(impuesto_monto) AS total_it
            FROM ventas v
            LEFT JOIN usuario u ON u.id = v.vendedor
            WHERE v.fecha >= '{$desde}'
              AND v.fecha <= '{$hasta}'
              AND v.habilitada = 1
              AND v.anulada = 0
            GROUP BY v.vendedor, u.usuario
            ORDER BY total_bruto DESC
        ");
    }

    /* ============================================================
       📌 RESUMEN POR CLIENTE
    ============================================================ */
    public function ResumenPorCliente($desde, $hasta)
    {
        $desde = $this->esc($desde);
        $hasta = $this->esc($hasta);

        return $this->db->SQL("
            SELECT
                v.cliente,
                c.nombre AS cliente_nombre,
                COUNT(*) AS total_ventas,
                SUM(totalprecio) AS total_bruto,
                SUM(CASE WHEN v.con_factura = 1 THEN totalprecio ELSE 0 END) AS total_facturado,
                SUM(CASE WHEN v.con_factura = 0 THEN totalprecio ELSE 0 END) AS total_no_facturado,
                SUM(iva_monto)      AS total_iva,
                SUM(impuesto_monto) AS total_it
            FROM ventas v
            LEFT JOIN cliente c ON c.id = v.cliente
            WHERE v.fecha >= '{$desde}'
              AND v.fecha <= '{$hasta}'
              AND v.habilitada = 1
              AND v.anulada = 0
            GROUP BY v.cliente, c.nombre
            ORDER BY total_bruto DESC
        ");
    }

    /* ============================================================
       📌 RESUMEN POR SERVICIO / PRODUCTO
    ============================================================ */
    public function ResumenPorServicio($desde, $hasta)
    {
        $desde = $this->esc($desde);
        $hasta = $this->esc($hasta);

        return $this->db->SQL("
            SELECT
                v.producto,
                p.nombre AS servicio_nombre,
                SUM(v.cantidad) AS cantidad_total,
                SUM(v.totalprecio) AS total_bruto,
                SUM(CASE WHEN v.con_factura = 1 THEN v.totalprecio ELSE 0 END) AS total_facturado,
                SUM(CASE WHEN v.con_factura = 0 THEN v.totalprecio ELSE 0 END) AS total_no_facturado,
                SUM(v.iva_monto)      AS total_iva,
                SUM(v.impuesto_monto) AS total_it
            FROM ventas v
            LEFT JOIN producto p ON p.id = v.producto
            WHERE v.fecha >= '{$desde}'
              AND v.fecha <= '{$hasta}'
              AND v.habilitada = 1
              AND v.anulada = 0
            GROUP BY v.producto, p.nombre
            ORDER BY total_bruto DESC
        ");
    }
}
