<?php

class FacturacionVentas extends Conexion
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /* ============================================================
       📌 OBTENER VENTA POR ID
    ============================================================ */
    public function ObtenerVenta($id)
    {
        $id = intval($id);

        $sql = $this->db->SQL("
            SELECT v.*,
                   c.nombre AS cliente_nombre,
                   p.nombre AS servicio_nombre
            FROM ventas v
            LEFT JOIN cliente c ON c.id = v.cliente
            LEFT JOIN producto p ON p.id = v.producto
            WHERE v.id = {$id}
            LIMIT 1
        ");

        return $sql->fetch_assoc();
    }

    /* ============================================================
       📌 LISTAR VENTAS (usa filtros simples)
    ============================================================ */
    public function ListarVentas($filtros = [])
    {
        $mysqli = $this->db->Conectar();
        $where = "1=1";

        if (!empty($filtros['desde'])) {
            $desde = $mysqli->real_escape_string($filtros['desde']);
            $where .= " AND v.fecha >= '{$desde}'";
        }

        if (!empty($filtros['hasta'])) {
            $hasta = $mysqli->real_escape_string($filtros['hasta']);
            $where .= " AND v.fecha <= '{$hasta}'";
        }

        if (isset($filtros['con_factura']) && $filtros['con_factura'] !== '') {
            $cf = intval($filtros['con_factura']);
            $where .= " AND v.con_factura = {$cf}";
        }

        if (!empty($filtros['cliente_id'])) {
            $cliente_id = intval($filtros['cliente_id']);
            $where .= " AND v.cliente = {$cliente_id}";
        }

        if (!empty($filtros['vendedor_id'])) {
            $vendedor_id = intval($filtros['vendedor_id']);
            $where .= " AND v.vendedor = {$vendedor_id}";
        }

        if (!empty($filtros['metodo_pago'])) {
            $mp = $mysqli->real_escape_string($filtros['metodo_pago']);
            $where .= " AND v.metodo_pago = '{$mp}'";
        }

        if (isset($filtros['anulada']) && $filtros['anulada'] !== '') {
            $anulada = intval($filtros['anulada']);
            $where .= " AND v.anulada = {$anulada}";
        }

        $where .= " AND v.habilitada = 1";

        return $this->db->SQL("
            SELECT 
                v.*,
                c.nombre AS cliente_nombre,
                p.nombre AS servicio_nombre,
                u.usuario AS usuario_facturado
            FROM ventas v
            LEFT JOIN cliente  c ON c.id = v.cliente
            LEFT JOIN producto p ON p.id = v.producto
            LEFT JOIN usuario  u ON u.id = v.usuario_factura
            WHERE {$where}
            ORDER BY v.fecha DESC, v.hora DESC, v.id DESC
        ");
    }

    /* ============================================================
       📌 FACTURAR TODAS LAS VENTAS QUE COMPARTEN idfactura
    ============================================================ */
    public function FacturarPorIdFactura($idFactura, $datosFactura = [])
    {
        $mysqli = $this->db->Conectar();

        $idFactura = intval($idFactura);
        if ($idFactura <= 0) {
            error_log("ERROR FacturarPorIdFactura: ID inválido");
            return false;
        }

        // 1) Resumen del grupo de ventas
        $res = $this->db->SQL("
            SELECT
                SUM(totalprecio) AS total_bruto,
                SUM(comision)    AS total_comision,
                MIN(metodo_pago) AS metodo_pago,
                MIN(id_banco)    AS id_banco,
                MIN(referencia_pago) AS referencia_pago,
                MIN(cliente)     AS id_cliente
            FROM ventas
            WHERE idfactura = {$idFactura}
              AND anulada = 0
              AND habilitada = 1
        ");

        if (!$res || $res->num_rows == 0) {
            error_log("ERROR Facturar: ventas no encontradas para {$idFactura}");
            return false;
        }

        $sum = $res->fetch_assoc();
        $totalBruto     = (float)($sum['total_bruto'] ?: 0);
        $totalComision  = (float)($sum['total_comision'] ?: 0);
        $metodoPago     = $sum['metodo_pago'] ?: 'EFECTIVO';
        $idBanco        = !empty($sum['id_banco']) ? intval($sum['id_banco']) : null;
        $referenciaPago = !empty($sum['referencia_pago']) ? $mysqli->real_escape_string($sum['referencia_pago']) : null;
        $idCliente      = !empty($sum['id_cliente']) ? intval($sum['id_cliente']) : null;

        if ($totalBruto <= 0) {
            error_log("ERROR Facturar: total bruto 0 para ID {$idFactura}");
            return false;
        }

        // 2) Datos del modal
        $nit          = isset($datosFactura['nit']) ? $mysqli->real_escape_string($datosFactura['nit']) : '';
        $razon_social = isset($datosFactura['razon_social']) ? $mysqli->real_escape_string($datosFactura['razon_social']) : '';
        $nro_comp     = isset($datosFactura['nro_comprobante']) ? $mysqli->real_escape_string($datosFactura['nro_comprobante']) : '';
        $usuario_fact = isset($datosFactura['usuario_factura']) ? intval($datosFactura['usuario_factura']) : 0;

        // 3) Impuestos
        $iva_monto = round($totalBruto * 0.13, 2);
        $it_monto  = round($totalBruto * 0.03, 2);

        $subtotal  = $totalBruto;
        $total_caja = $subtotal; // si luego descontarás comisión, aquí debe cambiarse

        // Debug
        error_log("FACTURAR: Total={$subtotal}, IVA={$iva_monto}, IT={$it_monto}");

        // 4) Actualizar TABLA FACTURA
        $this->db->SQL("
            UPDATE factura SET
                subtotal        = {$subtotal},
                iva             = {$iva_monto},
                it              = {$it_monto},
                total           = '{$subtotal}',
                total_comision  = {$totalComision},
                total_caja      = {$total_caja},
                cliente         = ".($idCliente ?: "NULL").",
                nit_cliente     = ".($nit !== '' ? "'{$nit}'" : "NULL").",
                razon_social    = ".($razon_social !== '' ? "'{$razon_social}'" : "NULL").",
                tipo_comprobante = 'FACTURA',
                metodo_pago     = '{$metodoPago}',
                referencia      = ".($referenciaPago ? "'{$referenciaPago}'" : "NULL").",
                id_banco        = ".($idBanco ? $idBanco : "NULL").",
                habilitado      = 1
            WHERE id = {$idFactura}
        ");

        // 5) Actualizar TABLA VENTAS
        $this->db->SQL("
            UPDATE ventas SET
                con_factura     = 1,
                nit             = ".($nit !== '' ? "'{$nit}'" : "NULL").",
                razon_social    = ".($razon_social !== '' ? "'{$razon_social}'" : "NULL").",
                nro_comprobante = ".($nro_comp !== '' ? "'{$nro_comp}'" : "NULL").",
                usuario_factura = {$usuario_fact},
                iva_monto       = {$iva_monto},
                impuesto_monto  = {$it_monto}
            WHERE idfactura = {$idFactura}
        ");

        error_log("ACTUALIZACIÓN VENTAS OK para ID {$idFactura}");

        return true;
    }

    /* ============================================================
       📌 KPI FACTURACIÓN
    ============================================================ */
    public function KPIs($desde = null, $hasta = null)
    {
        $mysqli = $this->db->Conectar();
        $where = "habilitada = 1 AND anulada = 0";

        if (!empty($desde)) {
            $desde = $mysqli->real_escape_string($desde);
            $where .= " AND fecha >= '{$desde}'";
        }
        if (!empty($hasta)) {
            $hasta = $mysqli->real_escape_string($hasta);
            $where .= " AND fecha <= '{$hasta}'";
        }

        $sql = $this->db->SQL("
            SELECT
                COUNT(*) AS total_ventas,
                SUM(CASE WHEN con_factura = 1 THEN 1 ELSE 0 END) AS con_factura,
                SUM(CASE WHEN con_factura = 0 THEN 1 ELSE 0 END) AS sin_factura,
                SUM(CASE WHEN con_factura = 1 THEN totalprecio ELSE 0 END) AS monto_facturado,
                SUM(CASE WHEN con_factura = 0 THEN totalprecio ELSE 0 END) AS monto_no_facturado
            FROM ventas
            WHERE {$where}
        ");

        return $sql->fetch_assoc();
    }
}

?>