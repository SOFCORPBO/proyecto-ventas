<?php

class Factura extends Conexion
{
    // Total facturado hoy
    public function totalFacturadoHoy()
    {
        $db = $this->Conectar();
        $hoy = date("Y-m-d");
        return $db->query("SELECT SUM(total) AS total FROM factura WHERE fecha='$hoy'");
    }

    // Ventas sin factura
    public function totalSinFactura()
    {
        $db = $this->Conectar();
        return $db->query("SELECT COUNT(*) AS total FROM ventas WHERE con_factura=0");
    }

    // Listado de ventas
    public function listarVentas()
    {
        $db = $this->Conectar();
        return $db->query("
            SELECT v.*, c.nombre AS cliente,
                   IF(v.con_factura=1,'CON FACTURA','SIN FACTURA') AS estado_factura
            FROM ventas v
            LEFT JOIN cliente c ON c.id=v.idcliente
            ORDER BY v.id DESC
        ");
    }

    // Registrar factura
    public function crearFactura($idVenta, $subtotal)
    {
        $db = $this->Conectar();

        $iva = $subtotal * 0.13;
        $it  = $subtotal * 0.03;
        $total = $subtotal + $iva + $it;

        $fecha = date("Y-m-d");
        $hora  = date("H:i:s");

        $sql = "
            INSERT INTO factura (idventa, subtotal, iva, it, total, fecha, hora)
            VALUES ('$idVenta','$subtotal','$iva','$it','$total','$fecha','$hora')
        ";

        if ($db->query($sql)) {

            $db->query("UPDATE ventas SET con_factura=1 WHERE id='$idVenta'");
            return true;
        }

        return false;
    }

    // Obtener factura
    public function obtenerFactura($idVenta)
    {
        $db = $this->Conectar();
        return $db->query("
            SELECT f.*, c.nombre AS cliente, v.monto
            FROM factura f
            INNER JOIN ventas v ON v.id=f.idventa
            LEFT JOIN cliente c ON c.id=v.idcliente
            WHERE f.idventa='$idVenta'
        ");
    }

    public function ventasFacturadas()
    {
        $db = $this->Conectar();
        return $db->query("
            SELECT v.id, c.nombre AS cliente, f.total, f.iva, f.it, f.fecha
            FROM factura f
            INNER JOIN ventas v ON v.id=f.idventa
            LEFT JOIN cliente c ON c.id=v.idcliente
            ORDER BY f.id DESC
        ");
    }

    public function ventasSinFactura()
    {
        $db = $this->Conectar();
        return $db->query("
            SELECT v.id, c.nombre AS cliente, v.monto, v.fecha
            FROM ventas v
            LEFT JOIN cliente c ON c.id=v.idcliente
            WHERE v.con_factura=0
            ORDER BY v.id DESC
        ");
    }
}
?>
