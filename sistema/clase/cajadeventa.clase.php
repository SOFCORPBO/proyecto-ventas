<?php
class CajaDeVenta {

    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    /**
     * Agregar servicio/producto al carrito
     */
    public function AgregarProducto($producto_id, $cantidad, $vendedor_id) {

        $producto = $this->db->SQL("SELECT * FROM producto WHERE id='{$producto_id}'")->fetch_assoc();

        if (!$producto) return false;

        $precio_unit  = (float)$producto['precioventa'];
        $subtotal     = $precio_unit * $cantidad;

        // Valores por defecto heredados del producto
        $iva_pct      = (float)$producto['iva'];
        $com_pct      = (float)$producto['comision'];
        $imp_monto    = 0;

        // Cálculo dinámico
        $iva_monto      = $subtotal * ($iva_pct / 100);
        $com_monto      = $subtotal * ($com_pct / 100);

        $this->db->SQL("
            INSERT INTO cajatmp
            (vendedor, producto, cantidad, precio, totalprecio,
             iva_porcentaje, impuesto_monto, comision_porcentaje, comision_monto)
            VALUES
            ('{$vendedor_id}', '{$producto_id}', '{$cantidad}', '{$precio_unit}', '{$subtotal}',
             '{$iva_pct}', '{$imp_monto}', '{$com_pct}', '{$com_monto}')
        ");

        return true;
    }

    /**
     * Actualizar dinámicamente impuestos en el carrito
     */
    public function ActualizarImpuestos() {

        if (!isset($_POST['ActualizarImpuestos'])) return;

        $id_tmp = intval($_POST['IdCajaTmp']);

        $iva_pct   = floatval($_POST['iva_porcentaje']);
        $imp_monto = floatval($_POST['impuesto_monto']);
        $com_pct   = floatval($_POST['comision_porcentaje']);

        // obtener subtotal actual
        $row = $this->db->SQL("SELECT cantidad, precio FROM cajatmp WHERE id='{$id_tmp}'")->fetch_assoc();

        if (!$row) return;

        $subtotal   = $row['cantidad'] * $row['precio'];
        $com_monto  = $subtotal * ($com_pct / 100);

        $this->db->SQL("
            UPDATE cajatmp
            SET 
                iva_porcentaje      = '{$iva_pct}',
                impuesto_monto      = '{$imp_monto}',
                comision_porcentaje = '{$com_pct}',
                comision_monto      = '{$com_monto}'
            WHERE id='{$id_tmp}'
        ");
    }

    /**
     * Vaciar carrito
     */
    public function LimpiarCarrito($vendedor_id) {
        $this->db->SQL("DELETE FROM cajatmp WHERE vendedor='{$vendedor_id}'");
    }

    /**
     * Eliminar un item del carrito
     */
    public function EliminarItem($id_tmp) {
        $this->db->SQL("DELETE FROM cajatmp WHERE id='{$id_tmp}' LIMIT 1");
    }

    /**
     * Obtener items del carrito
     */
    public function ObtenerCarrito($vendedor_id) {
        return $this->db->SQL("
            SELECT c.*, p.nombre 
            FROM cajatmp c 
            LEFT JOIN producto p ON p.id = c.producto
            WHERE vendedor='{$vendedor_id}'
        ");
    }
}